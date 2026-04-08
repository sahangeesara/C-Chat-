# 🚀 Quick Start - Get Calls Working in 5 Minutes

## The Problem
Call button doesn't respond when clicked.

## The Solution  
The frontend is now fixed ✅. Your backend needs the call endpoints.

---

## 5-Minute Setup

### Step 1: Copy This Code to Your Backend

**In your Laravel project**, create file:  
`app/Http/Controllers/CallController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CallController extends Controller
{
    public function start(Request $request)
    {
        $toId = $request->input('to_id');
        event(new \App\Events\IncomingCallEvent($toId, [
            'from_id' => $request->user()->id,
            'sdp' => $request->input('sdp'),
            'type' => 'offer',
            'call_type' => $request->input('call_type', 'audio'),
        ]));
        return response()->json(['status' => 'ok']);
    }

    public function answer(Request $request)
    {
        $toId = $request->input('to_id');
        event(new \App\Events\CallAnsweredEvent($toId, [
            'from_id' => $request->user()->id,
            'sdp' => $request->input('sdp'),
            'type' => 'answer',
        ]));
        return response()->json(['status' => 'ok']);
    }

    public function sendIce(Request $request)
    {
        $toId = $request->input('to_id');
        event(new \App\Events\CallIceEvent($toId, [
            'from_id' => $request->user()->id,
            'candidate' => $request->input('candidate'),
        ]));
        return response()->json(['status' => 'ok']);
    }

    public function endCall(Request $request)
    {
        $toId = $request->input('to_id');
        event(new \App\Events\CallEndedEvent($toId, [
            'from_id' => $request->user()->id,
        ]));
        return response()->json(['status' => 'ok']);
    }
}
```

### Step 2: Create Event Files

Create `app/Events/IncomingCallEvent.php`:
```php
<?php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class IncomingCallEvent implements ShouldBroadcast
{
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

Do the same for:
- `app/Events/CallAnsweredEvent.php` (change `broadcastAs()` to `.call.answered`)
- `app/Events/CallIceEvent.php` (change to `.ice.candidate`)
- `app/Events/CallEndedEvent.php` (change to `.call.ended`)

(Template in BACKEND_CALL_ROUTES_CHECKLIST.md)

### Step 3: Add Routes

In `routes/api.php`:
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/call/start', [App\Http\Controllers\CallController::class, 'start']);
    Route::post('/call/answer', [App\Http\Controllers\CallController::class, 'answer']);
    Route::post('/call/ice', [App\Http\Controllers\CallController::class, 'sendIce']);
    Route::post('/call/end', [App\Http\Controllers\CallController::class, 'endCall']);
});
```

### Step 4: Verify .env

Check `.env`:
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_KEY=d48f36eb19647382a1d0
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
```

### Step 5: Run Servers

```bash
# Terminal 1
php artisan websockets:serve

# Terminal 2
php artisan serve

# Terminal 3 (frontend already built)
# Just reload browser
```

---

## Test It

1. **Open DevTools:** Press F12
2. **Click Phone Button:** Select a user, click call
3. **Check Console:** Should see `✅ Call start signal sent successfully`

If you see `❌ startCall failed: 404` → You missed a route or controller.

---

## That's It! 🎉

The call system should now work. If not:
- Check the full `BACKEND_CALL_ROUTES_CHECKLIST.md`
- Or the `CALL_DEBUGGING_GUIDE.md`

Both files have all the code you need.

