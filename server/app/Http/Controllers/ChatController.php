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
            'message' => 'required',
            'user_id' => 'required|exists:user,id',
        ]);

        $inputMessage = $request->message;
        $toUserId = $request->user_id;
        $fromUserId = auth()->id();

        try {
            // Save the message
            $message = new Message();
            $message->to_id = $toUserId;
            $message->from_id = $fromUserId;
            $message->body = $inputMessage;
            $message->save();

            // Fire the event for real-time broadcasting
            broadcast(new ChatEvent(auth()->user(), $inputMessage))->toOthers();

            return response()->json(['status' => 'Message sent!', 'message' => $message], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'An error occurred while sending the message.'], 500);
        }
    }


}
