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
    public function chat(){

        try {
            $message = Message::with('user')
                ->where('is_active',1)
                ->orderBy('created_at',"DESC")
                ->get();

            return response()->json($customers);
        } catch (\Exception $e) {
            // Log the error and return an appropriate response
            Log::error($e->getMessage());
            return response()->json(['message' => 'An error occurred while retrieving customer.'], 500);
        }

    }
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required',
        ]);

        // Directly access the data, no need for json_encode
        $data = $request->all();  // $data is now an array, not a string
        $inputMessage  = $data['message'];  // Directly access 'message'
        $userId = $data['user_id'];  // Directly access 'user_id'


        if (is_array($userId)) {
            $userId = $userId[0]; // Adjust as necessary if userId is an array
        }

        $athUser = Auth::user();
        $user = User::find($userId);

        if ($user) {
            // Fire the event
            event(new ChatEvent($inputMessage, $user));
        } else {
            return response()->json(['error' => 'Select User']);
        }

        try{
            $message =new Message();
            $message->to_id = $user->id;
            $message->from_id = $athUser->id;
            $message->body = $inputMessage;
            $message->save();

            return response()->json(['status' => 'Message sent!'],200);
        }catch (\Exception $e) {
            // Log the error and return an appropriate response
            Log::error($e->getMessage());
            return response()->json(['message' => 'An error occurred while retrieving Message.'], 500);
        }
    }

}
