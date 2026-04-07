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

        if (!$request->filled('user_id') && $request->filled('to_id')) {
            $request->merge(['user_id' => $request->to_id]);
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

        $messageBody = trim((string) ($request->input('message') ?? $request->input('body', '')));
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
            'message' => 'nullable|string|max:500',
            'attachment' => 'nullable|string|max:2048',
            'group_id' => 'nullable|integer|exists:chat_groups,id',
            'user_id' => 'nullable|integer|exists:users,id',
        ];

        if ($hasAttachmentFile) {
            // Accept common document/image/video/audio formats, capped at 100MB.
            $validationRules['attachment'] = 'nullable|file|max:102400|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/bmp,image/svg+xml,video/mp4,video/webm,video/ogg,video/quicktime,audio/mpeg,audio/mp3,audio/wav,audio/webm,audio/ogg,audio/mp4,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain,application/zip,application/x-zip-compressed,application/x-rar-compressed';
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

        $toId = $request->input('user_id');
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
