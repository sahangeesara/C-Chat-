<?php

namespace App\Http\Controllers;

use App\Events\ChatEvent;
use App\Events\GroupChatEvent;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function groupMessages(Request $request, Group $group)
    {
        return $this->chat('group-' . $group->id);
    }

    public function sendToGroup(Request $request, Group $group)
    {
        $request->merge([
            'group_id' => $group->id,
            'user_id' => null,
        ]);

        return $this->send($request);
    }

    public function chat($id)
    {
        try {
            $authUserId = Auth::id();
            $groupId = $this->extractGroupId($id);

            if ($groupId !== null) {
                $isMember = GroupMember::query()
                    ->where('group_id', $groupId)
                    ->where('user_id', $authUserId)
                    ->exists();

                if (!$isMember) {
                    return response()->json([
                        'message' => 'You are not a member of this group.',
                    ], 403);
                }

                $messages = Message::with('user')
                    ->where('group_id', $groupId)
                    ->orderBy('created_at', 'ASC')
                    ->get();

                return response()->json([
                    'messages' => $messages,
                    'target' => 'group',
                    'group_id' => $groupId,
                ], 200);
            }

            $peerId = (int) $id;

            $messages = Message::with('user')
                ->where(function ($query) use ($authUserId, $peerId) {
                    $query->where(function ($inner) use ($authUserId, $peerId) {
                        $inner->where('from_id', $authUserId)
                            ->where('to_id', $peerId);
                    })->orWhere(function ($inner) use ($authUserId, $peerId) {
                        $inner->where('from_id', $peerId)
                            ->where('to_id', $authUserId);
                    });
                })
                ->orderBy('created_at', 'ASC')
                ->get();

            return response()->json([
                'messages' => $messages,
                'target' => 'direct',
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'An error occurred while retrieving messages.'], 500);
        }
    }

    public function send(Request $request)
    {
        $attachmentFile = $this->resolveAttachmentFile($request);

        // Normalize common mobile/client aliases.
        $request->merge([
            'user_id' => $request->input('user_id')
                ?? $request->input('to_id')
                ?? $request->input('toId')
                ?? $request->input('receiver_id')
                ?? $request->input('receiverId')
                ?? $request->input('recipient_id')
                ?? $request->input('recipientId'),
        ]);

        // Accept legacy/alternate recipient keys from different clients.
        if (!$request->filled('user_id')) {
            $fallbackToId = $request->input('to_id')
                ?? $request->input('toId')
                ?? $request->input('receiver_id')
                ?? $request->input('receiverId')
                ?? $request->input('recipient_id')
                ?? $request->input('recipientId');
            if ($fallbackToId !== null && $fallbackToId !== '') {
                $request->merge(['user_id' => $fallbackToId]);
            }
        }

        if (!$request->filled('group_id') && $request->filled('user_id')) {
            $candidateGroupId = $this->extractGroupId($request->input('user_id'));
            if ($candidateGroupId !== null) {
                $request->merge([
                    'group_id' => $candidateGroupId,
                    'user_id' => null,
                ]);
            }
        }

        // Accept common message keys used by web/mobile clients.
        $messageBody = trim((string) (
            $request->input('message')
                ?? $request->input('body')
                ?? $request->input('text')
                ?? $request->input('content')
                ?? $request->input('msg')
                ?? $request->input('messageText')
                ?? ''
        ));
        $attachmentInput = trim((string) $request->input('attachment', ''));
        $hasAttachmentFile = $attachmentFile instanceof UploadedFile;

        if ($messageBody === '' && !$hasAttachmentFile && $attachmentInput === '') {
            return response()->json([
                'error' => 'A message body or attachment is required.',
            ], 422);
        }

        if ($hasAttachmentFile && !$attachmentFile->isValid()) {
            return response()->json([
                'error' => 'The attachment failed to upload.',
            ], 422);
        }

        $validationRules = [
            'message' => 'nullable|string|max:5000',
            'body' => 'nullable|string|max:5000',
            'text' => 'nullable|string|max:5000',
            'content' => 'nullable|string|max:5000',
            'msg' => 'nullable|string|max:5000',
            'messageText' => 'nullable|string|max:5000',
            'attachment' => 'nullable|string|max:2048',
            'group_id' => 'nullable|integer|exists:chat_groups,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'to_id' => 'nullable|integer|exists:users,id',
            'toId' => 'nullable|integer|exists:users,id',
            'receiver_id' => 'nullable|integer|exists:users,id',
            'receiverId' => 'nullable|integer|exists:users,id',
            'recipient_id' => 'nullable|integer|exists:users,id',
            'recipientId' => 'nullable|integer|exists:users,id',
        ];

        if ($hasAttachmentFile) {
            $allowedMimes = config('upload.allowed_mimetypes', [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml',
                'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/x-flv', 'video/mpeg',
                'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/webm', 'audio/ogg', 'audio/mp4', 'audio/flac',
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain', 'application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed', 'application/x-7z-compressed',
            ]);

            $imageMimes = config('upload.mimetypes.image', []);
            $documentMimes = config('upload.mimetypes.document', []);
            $detectedMime = (string) $attachmentFile->getClientMimeType();

            $maxFileSize = (int) config('upload.max_file_size_validation', config('upload.max_file_size_kb', 1048576));
            if (in_array($detectedMime, $imageMimes, true)) {
                $maxFileSize = (int) config('upload.max_image_size_kb', 40960); // 40MB
            } elseif (in_array($detectedMime, $documentMimes, true)) {
                $maxFileSize = (int) config('upload.max_document_size_kb', 10240); // 10MB
            }

            $mimeTypesString = implode(',', $allowedMimes);
            $validationRules['attachment'] = "nullable|file|max:{$maxFileSize}|mimetypes:{$mimeTypesString}";
        }

        $request->validate($validationRules);

        $fromId = auth()->id();
        $groupId = $request->input('group_id');

        if ($groupId === null && $request->filled('user_id')) {
            $groupId = $this->extractGroupId($request->input('user_id'));
        }

        if ($groupId === null && $request->filled('to_id')) {
            $groupId = $this->extractGroupId($request->input('to_id'));
        }

        $toId = $request->input('user_id')
            ?? $request->input('to_id')
            ?? $request->input('toId')
            ?? $request->input('receiver_id')
            ?? $request->input('receiverId')
            ?? $request->input('recipient_id')
            ?? $request->input('recipientId');
        $attachment = $attachmentInput;

        if ($groupId !== null) {
            $isMember = GroupMember::query()
                ->where('group_id', (int) $groupId)
                ->where('user_id', $fromId)
                ->exists();

            if (!$isMember) {
                return response()->json([
                    'error' => 'You are not a member of this group.',
                ], 403);
            }
        } elseif (!$toId) {
            return response()->json([
                'error' => 'A target user or group is required.',
            ], 422);
        }

        $attachmentMime = null;
        $attachmentName = null;
        $attachmentSize = null;

        if ($hasAttachmentFile) {
            $storedPath = $attachmentFile->store('chat-attachments', 'public');
            $attachment = asset('storage/' . ltrim($storedPath, '/'));
            $attachmentMime = $attachmentFile->getClientMimeType();
            $attachmentName = $attachmentFile->getClientOriginalName();
            $attachmentSize = $attachmentFile->getSize();
        }

        try {
            $message = Message::create([
                'from_id' => $fromId,
                // Keep to_id populated for compatibility with legacy schema and clients.
                'to_id' => $groupId !== null ? $fromId : $toId,
                'group_id' => $groupId,
                'body' => $messageBody,
                'attachment' => $attachment,
                'attachment_mime' => $attachmentMime,
                'attachment_name' => $attachmentName,
                'attachment_size' => $attachmentSize,
                'seen' => false,
                'is_active' => true,
            ]);

            $broadcasted = true;

            try {
                if ($groupId !== null) {
                    broadcast(new GroupChatEvent(
                        (int) $groupId,
                        $fromId,
                        $message->body,
                        $message->attachment,
                        $message->id
                    ))->toOthers();
                } else {
                    broadcast(new ChatEvent(
                        $fromId,
                        $toId,
                        $message->body,
                        $message->attachment,
                        $message->id
                    ))->toOthers();
                }
            } catch (\Throwable $broadcastError) {
                $broadcasted = false;
                Log::warning('Message saved but realtime broadcast failed', [
                    'error' => $broadcastError->getMessage(),
                    'from_id' => $fromId,
                    'to_id' => $toId,
                    'group_id' => $groupId,
                    'message_id' => $message->id,
                ]);
            }

            return response()->json([
                'status' => 'Message sent',
                'message' => $message,
                'realtime' => $broadcasted,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Message send failed', [
                'error' => $e->getMessage(),
                'from_id' => $fromId ?? null,
                'to_id' => $toId ?? null,
                'group_id' => $groupId ?? null,
            ]);

            return response()->json(['error' => 'Message failed'], 500);
        }
    }

    private function extractGroupId($rawValue): ?int
    {
        if ($rawValue === null) {
            return null;
        }

        if (is_int($rawValue)) {
            return $rawValue > 0 ? $rawValue : null;
        }

        $value = trim((string) $rawValue);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^group-(\d+)$/', $value, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function resolveAttachmentFile(Request $request): ?UploadedFile
    {
        if ($request->hasFile('attachment')) {
            return $request->file('attachment');
        }

        foreach (['file', 'image', 'document', 'video', 'audio'] as $key) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $request->files->set('attachment', $file);
                return $file;
            }
        }

        return null;
    }
}
