<?php

namespace App\Http\Controllers;

use App\Events\ChatEvent;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chat($id)
    {
        try {
            $authUserId = Auth::id(); // Get authenticated user ID

            // Fetch messages where auth user is either sender or receiver.
            $messages = Message::with('user')
                ->where(function ($query) use ($authUserId, $id) {
                    $query->where(function ($inner) use ($authUserId, $id) {
                        $inner->where('from_id', $authUserId)
                            ->where('to_id', $id);
                    })->orWhere(function ($inner) use ($authUserId, $id) {
                        $inner->where('from_id', $id)
                            ->where('to_id', $authUserId);
                    });
                })
                ->orderBy('created_at', "ASC") // Order by oldest to newest
                ->get();

            return response()->json(['messages' => $messages], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'An error occurred while retrieving messages.'], 500);
        }
    }

    public function send(Request $request)
    {
        if (!$request->filled('user_id') && $request->filled('to_id')) {
            $request->merge(['user_id' => $request->to_id]);
        }

        if (!$request->filled('message') && !$request->filled('attachment')) {
            return response()->json([
                'error' => 'A message body or attachment is required.'
            ], 422);
        }

        $request->validate([
            'message' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $fromId = auth()->id();
        $toId   = $request->user_id;
        $messageBody = trim((string) $request->input('message', ''));

        try {
            $message = Message::create([
                'from_id' => $fromId,
                'to_id' => $toId,
                'body' => $messageBody,
                'attachment' => $request->input('attachment', ''),
                'seen' => false,
                'is_active' => true,
            ]);

            $broadcasted = true;

            try {
                broadcast(new ChatEvent(
                    $fromId,
                    $toId,
                    $message->body,
                    $message->attachment,
                    $message->id
                ));
            } catch (\Throwable $broadcastError) {
                $broadcasted = false;
                Log::warning('Message saved but realtime broadcast failed', [
                    'error' => $broadcastError->getMessage(),
                    'from_id' => $fromId,
                    'to_id' => $toId,
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
            ]);
            return response()->json(['error' => 'Message failed'], 500);
        }
    }


}
