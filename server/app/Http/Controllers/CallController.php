<?php

namespace App\Http\Controllers;

use App\Events\CallAnswered;
use App\Events\IceCandidate;
use App\Events\IncomingCall;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function start(Request $request)
    {
        try {
            broadcast(new IncomingCall(auth()->id(), $request->to_id, $request->offer))->toOthers();
            return response()->json(['status' => 'calling']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function answer(Request $request)
    {
        broadcast(new CallAnswered(
            auth()->id(),
            $request->to_id,
            $request->sdp
        ))->toOthers();
    }

    public function ice(Request $request)
    {
        broadcast(new IceCandidate(
            auth()->id(),
            $request->to_id,
            $request->candidate
        ))->toOthers();
    }
}
