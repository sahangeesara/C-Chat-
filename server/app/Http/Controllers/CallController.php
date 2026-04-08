<?php

namespace App\Http\Controllers;

use App\Events\CallAnswered;
use App\Events\IceCandidate;
use App\Events\IncomingCall;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{
    public function start(Request $request)
    {
        $request->merge([
            'to_id' => $request->input('to_id')
                ?? $request->input('toId')
                ?? $request->input('callee_id')
                ?? $request->input('calleeId'),
            'offer' => $request->input('offer')
                ?? $request->input('sdp')
                ?? $request->input('rtc_offer')
                ?? $request->input('rtcOffer'),
            'call_type' => $request->input('call_type')
                ?? $request->input('type')
                ?? ($request->boolean('is_video') ? 'video' : null),
        ]);

        $data = $request->validate([
            'to_id' => 'required|integer|exists:users,id|not_in:' . auth()->id(),
            'offer' => 'nullable',
            'sdp' => 'nullable',
            'call_type' => 'nullable|string|in:audio,video',
        ]);

        $offerPayload = $data['offer'] ?? $data['sdp'] ?? $request->input('rtc_offer');

        if ($offerPayload === null || $offerPayload === '') {
            return response()->json([
                'error' => 'Offer SDP is required.',
            ], 422);
        }

        try {
            $call = Call::create([
                'caller_id' => auth()->id(),
                'callee_id' => $data['to_id'],
                'status' => 'ringing',
                'call_type' => $data['call_type'] ?? 'audio',
                'offer' => is_array($offerPayload) ? $offerPayload : ['sdp' => $offerPayload],
                'started_at' => now(),
            ]);

            broadcast(new IncomingCall(auth()->id(), $data['to_id'], $offerPayload, $call->id))->toOthers();

            return response()->json([
                'status' => 'calling',
                'call_id' => $call->id,
                'call' => $call,
            ]);
        } catch (\Exception $e) {
            Log::error('Call start failed', [
                'error' => $e->getMessage(),
                'caller_id' => auth()->id(),
                'callee_id' => $data['to_id'] ?? null,
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function answer(Request $request)
    {
        $request->merge([
            'call_id' => $request->input('call_id')
                ?? $request->input('callId'),
            'to_id' => $request->input('to_id')
                ?? $request->input('toId')
                ?? $request->input('caller_id')
                ?? $request->input('callerId'),
            'sdp' => $request->input('sdp')
                ?? $request->input('answer')
                ?? $request->input('rtc_answer')
                ?? $request->input('rtcAnswer'),
        ]);

        $data = $request->validate([
            'call_id' => 'nullable|integer|exists:calls,id|required_without:to_id',
            'to_id' => 'nullable|integer|exists:users,id|not_in:' . auth()->id() . '|required_without:call_id',
            'sdp' => 'nullable',
            'answer' => 'nullable',
            'rtc_answer' => 'nullable',
        ]);

        $sdpPayload = $data['sdp'] ?? $data['answer'] ?? $data['rtc_answer'] ?? null;
        if ($sdpPayload === null || $sdpPayload === '') {
            return response()->json([
                'error' => 'Answer SDP is required.',
            ], 422);
        }

        $callQuery = Call::query()
            ->where('callee_id', auth()->id())
            ->whereNull('ended_at');

        if (!empty($data['call_id'])) {
            $call = $callQuery
                ->where('id', $data['call_id'])
                ->firstOrFail();
        } else {
            $call = $callQuery
                ->where('caller_id', $data['to_id'])
                ->latest('id')
                ->first();

            if (!$call) {
                return response()->json([
                    'error' => 'No active incoming call found for this user.',
                ], 404);
            }
        }

        if ($call->ended_at) {
            return response()->json(['error' => 'Call already ended'], 422);
        }

        if (!$call->answered_at) {
            $call->status = 'answered';
            $call->answered_at = now();
        }

        $call->answer_sdp = is_array($sdpPayload) ? $sdpPayload : ['sdp' => $sdpPayload];
        $call->save();

        broadcast(new CallAnswered(
            auth()->id(),
            $call->caller_id,
            $call->answer_sdp,
            $call->id
        ))->toOthers();

        return response()->json([
            'status' => 'answered',
            'call_id' => $call->id,
            'call' => $call,
        ]);
    }

    // Compatibility endpoint for clients that send /call/accept before SDP is ready.
    public function accept(Request $request)
    {
        $request->merge([
            'call_id' => $request->input('call_id')
                ?? $request->input('callId'),
            'to_id' => $request->input('to_id')
                ?? $request->input('toId')
                ?? $request->input('caller_id')
                ?? $request->input('callerId'),
        ]);

        $data = $request->validate([
            'call_id' => 'nullable|integer|exists:calls,id|required_without:to_id',
            'to_id' => 'nullable|integer|exists:users,id|not_in:' . auth()->id() . '|required_without:call_id',
        ]);

        $callQuery = Call::query()
            ->where('callee_id', auth()->id())
            ->whereNull('ended_at');

        if (!empty($data['call_id'])) {
            $call = $callQuery->where('id', $data['call_id'])->firstOrFail();
        } else {
            $call = $callQuery->where('caller_id', $data['to_id'])->latest('id')->first();
            if (!$call) {
                return response()->json([
                    'error' => 'No active incoming call found for this user.',
                ], 404);
            }
        }

        if ($call->ended_at) {
            return response()->json(['error' => 'Call already ended'], 422);
        }

        if (!$call->answered_at) {
            $call->status = 'answered';
            $call->answered_at = now();
            $call->save();
        }

        // Notify caller that callee accepted; SDP can arrive via /call/answer later.
        broadcast(new CallAnswered(
            auth()->id(),
            $call->caller_id,
            $call->answer_sdp,
            $call->id
        ))->toOthers();

        return response()->json([
            'status' => 'accepted',
            'call_id' => $call->id,
            'call' => $call,
        ]);
    }

    public function ice(Request $request)
    {
        $request->merge([
            'to_id' => $request->input('to_id')
                ?? $request->input('toId'),
            'candidate' => $request->input('candidate')
                ?? $request->input('iceCandidate')
                ?? $request->input('rtcCandidate'),
            'call_id' => $request->input('call_id')
                ?? $request->input('callId'),
        ]);

        $data = $request->validate([
            'to_id' => 'required|integer|exists:users,id|not_in:' . auth()->id(),
            'candidate' => 'required',
            'call_id' => 'nullable|integer|exists:calls,id',
        ]);

        broadcast(new IceCandidate(
            auth()->id(),
            $data['to_id'],
            $data['candidate'],
            $data['call_id'] ?? null
        ))->toOthers();

        return response()->json(['status' => 'ice_sent']);
    }

    public function end(Request $request)
    {
        $data = $request->validate([
            'call_id' => 'required|integer|exists:calls,id',
            'status' => 'nullable|in:ended,missed,rejected,cancelled,failed',
            'reason' => 'nullable|string|max:255',
        ]);

        $call = Call::where('id', $data['call_id'])
            ->where(function ($query) {
                $query->where('caller_id', auth()->id())
                    ->orWhere('callee_id', auth()->id());
            })
            ->firstOrFail();

        if ($call->ended_at) {
            return response()->json([
                'status' => 'already_ended',
                'call' => $call,
            ]);
        }

        $fallbackStatus = $call->status === 'ringing' && auth()->id() === $call->caller_id
            ? 'cancelled'
            : ($call->status === 'ringing' ? 'missed' : 'ended');

        $call->status = $data['status'] ?? $fallbackStatus;
        $call->end_reason = $data['reason'] ?? null;
        $call->ended_by = auth()->id();
        $call->ended_at = now();
        $call->save();

        return response()->json([
            'status' => 'ended',
            'call' => $call,
        ]);
    }

    public function history(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        $calls = Call::query()
            ->with(['caller:id,name,profile_photo_path,last_seen_at', 'callee:id,name,profile_photo_path,last_seen_at'])
            ->where(function ($query) {
                $query->where('caller_id', auth()->id())
                    ->orWhere('callee_id', auth()->id());
            })
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $userId = (int) $request->input('user_id');
                $query->where(function ($q) use ($userId) {
                    $q->where('caller_id', $userId)
                        ->orWhere('callee_id', $userId);
                });
            })
            ->latest('started_at')
            ->paginate($perPage);

        $calls->getCollection()->transform(function (Call $call) {
            $isOutgoing = (int) $call->caller_id === (int) auth()->id();
            $counterpart = $isOutgoing ? $call->callee : $call->caller;

            $durationSeconds = null;
            if ($call->answered_at && $call->ended_at) {
                $durationSeconds = $call->answered_at->diffInSeconds($call->ended_at);
            }

            return [
                'id' => $call->id,
                'status' => $call->status,
                'call_type' => $call->call_type ?? 'audio',
                'direction' => $isOutgoing ? 'outgoing' : 'incoming',
                'counterpart' => $counterpart,
                'started_at' => $call->started_at?->toIso8601String(),
                'answered_at' => $call->answered_at?->toIso8601String(),
                'ended_at' => $call->ended_at?->toIso8601String(),
                'duration_seconds' => $durationSeconds,
                'end_reason' => $call->end_reason,
            ];
        });

        return response()->json($calls);
    }

    public function show(string $id)
    {
        $call = Call::with(['caller:id,name,profile_photo_path,last_seen_at', 'callee:id,name,profile_photo_path,last_seen_at'])
            ->where('id', $id)
            ->where(function ($query) {
                $query->where('caller_id', auth()->id())
                    ->orWhere('callee_id', auth()->id());
            })
            ->firstOrFail();

                        $payload = $call->toArray();
                        $payload['call_type'] = $call->call_type ?? 'audio';

                        return response()->json($payload);
    }
}
