# Backend Call Routes Checklist

## ⚠️ IMPORTANT: Your Laravel Backend MUST Have These Routes

The frontend call system will NOT work without these backend API endpoints.

---

## Required Routes in Laravel

Add these routes to your `routes/api.php`:

```php
<?php

Route::middleware('auth:sanctum')->group(function () {
    // Call signaling endpoints
    Route::post('/call/start', [CallController::class, 'start']);
    Route::post('/call/answer', [CallController::class, 'answer']);
    Route::post('/call/ice', [CallController::class, 'sendIce']);
    Route::post('/call/end', [CallController::class, 'endCall']);
    
    // Call history (optional but recommended)
    Route::post('/call/history', [CallController::class, 'saveHistory']);
    Route::get('/call/history', [CallController::class, 'getHistory']);
    Route::get('/call/history/{userId}', [CallController::class, 'getUserCallHistory']);
});
```

---

## Required Controller: `app/Http/Controllers/CallController.php`

Create this file with minimal implementation:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use App\Events\CallEvent;

class CallController extends Controller
{
    /**
     * POST /api/call/start - Initiate a call
     */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'to_id' => 'required|integer|exists:users,id',
            'sdp' => 'nullable|string',
            'type' => 'nullable|string|in:offer,answer',
            'call_type' => 'nullable|string|in:audio,video',
        ]);

        $fromId = $request->user()->id;
        $toId = $validated['to_id'];

        // Broadcast the offer to the recipient
        $payload = [
            'from_id' => $fromId,
            'fromId' => $fromId,
            'to_id' => $toId,
            'sdp' => $validated['sdp'] ?? '',
            'type' => $validated['type'] ?? 'offer',
            'call_type' => $validated['call_type'] ?? 'audio',
            'offer' => [
                'type' => 'offer',
                'sdp' => $validated['sdp'] ?? '',
            ],
        ];

        // Broadcast to the recipient's private channel
        // IMPORTANT: Change 'pusher' to your broadcast driver if different
        \Illuminate\Support\Facades\Broadcast::channel("call.{$toId}")->broadcast(
            new class {
                public function broadcastOn()
                {
                    return new \Illuminate\Broadcasting\PrivateChannel("call.{$toId}");
                }

                public function broadcastAs()
                {
                    return '.incoming.call';
                }

                public function broadcastWith()
                {
                    return $payload;
                }
            }
        );

        // Or using events (recommended):
        event(new \App\Events\IncomingCallEvent($toId, $payload));

        return response()->json([
            'status' => 'call_initiated',
            'to_user_id' => $toId,
        ], 201);
    }

    /**
     * POST /api/call/answer - Answer incoming call
     */
    public function answer(Request $request)
    {
        $validated = $request->validate([
            'to_id' => 'required|integer|exists:users,id',
            'sdp' => 'nullable|string',
            'type' => 'nullable|string|in:offer,answer',
            'call_type' => 'nullable|string|in:audio,video',
        ]);

        $fromId = $request->user()->id;
        $toId = $validated['to_id'];

        $payload = [
            'from_id' => $fromId,
            'fromId' => $fromId,
            'to_id' => $toId,
            'sdp' => $validated['sdp'] ?? '',
            'type' => $validated['type'] ?? 'answer',
            'call_type' => $validated['call_type'] ?? 'audio',
            'answer' => [
                'type' => 'answer',
                'sdp' => $validated['sdp'] ?? '',
            ],
        ];

        // Broadcast to the caller
        event(new \App\Events\CallAnsweredEvent($toId, $payload));

        return response()->json([
            'status' => 'call_answered',
            'to_user_id' => $toId,
        ], 200);
    }

    /**
     * POST /api/call/ice - Send ICE candidate
     */
    public function sendIce(Request $request)
    {
        $validated = $request->validate([
            'to_id' => 'required|integer|exists:users,id',
            'candidate' => 'required|array',
        ]);

        $fromId = $request->user()->id;
        $toId = $validated['to_id'];

        $payload = [
            'from_id' => $fromId,
            'fromId' => $fromId,
            'candidate' => $validated['candidate'],
            'ice' => $validated['candidate'],
        ];

        // Broadcast ICE candidate
        event(new \App\Events\CallIceEvent($toId, $payload));

        return response()->json(['status' => 'ice_sent']);
    }

    /**
     * POST /api/call/end - End call
     */
    public function endCall(Request $request)
    {
        $validated = $request->validate([
            'to_id' => 'required|integer|exists:users,id',
        ]);

        $fromId = $request->user()->id;
        $toId = $validated['to_id'];

        $payload = [
            'from_id' => $fromId,
            'fromId' => $fromId,
            'to_id' => $toId,
            'status' => 'ended',
            'reason' => 'user_ended',
        ];

        // Broadcast call ended
        event(new \App\Events\CallEndedEvent($toId, $payload));

        return response()->json(['status' => 'call_ended']);
    }

    /**
     * POST /api/call/history - Save call history
     */
    public function saveHistory(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'direction' => 'required|string|in:incoming,outgoing',
            'status' => 'required|string|in:answered,missed,rejected,ended,failed',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date',
            'duration_seconds' => 'nullable|integer',
            'meta' => 'nullable|array',
        ]);

        // Save to database if you have a CallHistory model
        // \App\Models\CallHistory::create([
        //     'user_id' => $request->user()->id,
        //     'other_user_id' => $validated['user_id'],
        //     ...
        // ]);

        return response()->json(['status' => 'history_saved']);
    }

    /**
     * GET /api/call/history - Get all call history
     */
    public function getHistory(Request $request)
    {
        // Return call history from database
        // $history = \App\Models\CallHistory::where('user_id', $request->user()->id)->get();
        // return response()->json(['history' => $history]);

        return response()->json(['history' => []]);
    }

    /**
     * GET /api/call/history/{userId} - Get calls with specific user
     */
    public function getUserCallHistory(Request $request, $userId)
    {
        // Return calls between current user and specified user
        return response()->json(['history' => []]);
    }
}
```

---

## Required Events

Create these broadcast events:

### `app/Events/IncomingCallEvent.php`
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class IncomingCallEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $toUserId;
    public $payload;

    public function __construct($toUserId, $payload)
    {
        $this->toUserId = $toUserId;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("call.{$this->toUserId}");
    }

    public function broadcastAs()
    {
        return '.incoming.call';
    }

    public function broadcastWith()
    {
        return $this->payload;
    }
}
```

### `app/Events/CallAnsweredEvent.php`
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class CallAnsweredEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $toUserId;
    public $payload;

    public function __construct($toUserId, $payload)
    {
        $this->toUserId = $toUserId;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("call.{$this->toUserId}");
    }

    public function broadcastAs()
    {
        return '.call.answered';
    }

    public function broadcastWith()
    {
        return $this->payload;
    }
}
```

### `app/Events/CallIceEvent.php`
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class CallIceEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $toUserId;
    public $payload;

    public function __construct($toUserId, $payload)
    {
        $this->toUserId = $toUserId;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("call.{$this->toUserId}");
    }

    public function broadcastAs()
    {
        return '.ice.candidate';
    }

    public function broadcastWith()
    {
        return $this->payload;
    }
}
```

### `app/Events/CallEndedEvent.php`
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class CallEndedEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $toUserId;
    public $payload;

    public function __construct($toUserId, $payload)
    {
        $this->toUserId = $toUserId;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("call.{$this->toUserId}");
    }

    public function broadcastAs()
    {
        return '.call.ended';
    }

    public function broadcastWith()
    {
        return $this->payload;
    }
}
```

---

## Broadcasting Configuration

Verify your `.env` has:
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=d48f36eb19647382a1d0
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=ap2
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

Or if using Laravel WebSockets:
```env
BROADCAST_DRIVER=pusher
LARAVEL_WEBSOCKETS_PORT=6001
```

---

## Database Migration (Optional)

If you want to store call history:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('other_user_id');
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->enum('status', ['answered', 'missed', 'rejected', 'ended', 'failed']);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('other_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('other_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_histories');
    }
};
```

---

## Testing Checklist

- [ ] Routes created in `routes/api.php`
- [ ] `CallController` created in `app/Http/Controllers/`
- [ ] All 4 events created in `app/Events/`
- [ ] `.env` has correct `BROADCAST_DRIVER` setting
- [ ] WebSocket server running (`php artisan websockets:serve`)
- [ ] Test endpoint with Postman (POST `/api/call/start`)
- [ ] Test broadcasting by watching Network tab for WebSocket messages

---

## Quick Test Command

```bash
php artisan tinker

# Test broadcasting to a user
event(new \App\Events\IncomingCallEvent(2, ['from_id' => 1]));
# Check frontend console for incoming call
```

---

## Troubleshooting

**Issue:** "Route /api/call/start not found"
- Solution: Add routes to `routes/api.php`

**Issue:** Call events not reaching frontend
- Solution: Check if broadcasting driver is configured
- Run `php artisan websockets:serve` in separate terminal
- Check Network tab (WebSocket connections)

**Issue:** "NotFoundHttpException"
- Solution: Ensure users exist in database (foreign key constraint)

---

Now with these backend routes, the frontend call system should work! 🎉

