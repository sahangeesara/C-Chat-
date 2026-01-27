<?php

namespace App\Http\Controllers;

use App\Events\ChatEvent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chat($id)
    {
        try {
            $authUserId = Auth::id(); // Get authenticated user ID

            // Fetch messages where auth user is either sender or receiver
            $messages = Message::with('user')
                ->where(function ($query) use ($authUserId, $id) {
                    $query->where('from_id', $authUserId)->where('to_id', $id)
                        ->orWhere('from_id', $id)->where('to_id', $authUserId);
                })
                ->where('is_active',1)
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
        $request->validate([
            'message' => 'required|string',
            'user_id' => 'required|exists:user,id', // your table name is `user`
        ]);

        $fromId = auth()->id();
        $toId   = $request->user_id;

        try {
            $message = new Message();
            $message->from_id = $fromId;
            $message->to_id   = $toId;
            $message->body    = $request->message;
            $message->save();

            broadcast(new ChatEvent(
                auth()->id(),
                $request->user_id,
                $request->message
            ))->toOthers();


            return response()->json([
                'status' => 'Message sent',
                'message' => $message
            ], 200);

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['error' => 'Message failed'], 500);
        }
    }


}
