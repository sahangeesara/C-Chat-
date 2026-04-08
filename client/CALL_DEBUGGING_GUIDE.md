# Call Button Debugging Guide

## Issue
Call and video call buttons are clicked but not responding.

## What I Fixed

I've added **comprehensive debug logging** throughout the call signaling pipeline. When you click the call button, you should now see detailed logs in the browser **DevTools Console (F12)** showing exactly where the issue is.

---

## How to Debug

### 1. **Open Browser DevTools**
- Press **F12** or **Ctrl+Shift+I** in Chrome/Firefox
- Go to **Console** tab
- Keep it open while testing calls

### 2. **Click Call Button**
- Select a user in the chat
- Click the **phone icon** (audio call) or **camera icon** (video call)
- **Watch the Console** for logs

---

## Expected Console Logs (Success Flow)

When everything works, you should see:

```
🔴 startCall initiated: {toId: 123, options: {callType: "audio"}}
🔴 ensurePeerConnection: setting up...
🔴 Requesting microphone access...
✅ Microphone access granted
🔴 Adding audio track to peer connection
✅ Peer connection ready
🔴 Creating offer...
🔴 Sending offer to backend: {toId: 123, callType: "audio", sdpLength: 1234}
✅ Call start signal sent successfully
```

---

## Possible Issues & Solutions

### Issue 1: Logs stop at "Requesting microphone access..."
**Problem:** Microphone permission denied or unavailable

**Console log shows:**
```
❌ Microphone access denied: NotAllowedError: Permission denied
```

**Solution:**
1. Check browser microphone permissions
2. Allow microphone access for your app
3. Ensure no other app is using the microphone
4. Test in a different browser

---

### Issue 2: Logs show "❌ startCall failed" with "404"
**Problem:** Backend call endpoint doesn't exist

**Console log shows:**
```
❌ startCall failed: {message: "The route api/call/start could not be found."...}
```

**Solution:**
1. Verify backend routes exist:
   - Check `routes/api.php` or `routes/web.php` in Laravel
   - Routes needed: `/api/call/start`, `/api/call/answer`, `/api/call/ice`, `/api/call/end`
2. If routes don't exist, create them in backend
3. Test the endpoint using Postman with sample data

---

### Issue 3: Logs show success but call modal doesn't appear
**Problem:** Frontend state not updating correctly

**Check:**
1. Look for `showOutgoingCallModal.value = true` in logs
2. Verify the modal HTML is in your template (search for `showOutgoingCallModal`)
3. Check if there's a CSS issue hiding the modal

**Solution:**
- Refresh the page
- Check for CSS `display: none` on `.settings-overlay`

---

### Issue 4: No call events received on other side
**Problem:** Echo channel subscription failing

**Console logs to check:**
```
📞 Subscribing to call events for userId: 123
📞 Binding call events on private channel: call.123
📞 Binding call events on public channel: call.123
```

If you see the above, subscriptions are registered. If the other user doesn't receive the call:
- Check if their WebSocket connection is active (look for "Echo" in Network tab)
- Verify backend is broadcasting the call event to the correct channel
- Check Laravel logs for broadcasting errors

---

## Call Flow Debugging Map

This shows where each log appears:

```
User clicks "Call Button"
  ↓
🔴 startCall initiated
  ↓
🔴 ensurePeerConnection: setting up
  ↓
🔴 Requesting microphone access
  ↓ (if denied)
❌ Microphone access denied → STOP
  ↓ (if granted)
✅ Microphone access granted
  ↓
✅ Peer connection ready
  ↓
🔴 Creating offer
  ↓
🔴 Sending offer to backend
  ↓ (if failed)
❌ startCall failed → CHECK BACKEND ENDPOINT
  ↓ (if success)
✅ Call start signal sent successfully
  ↓
[Call modal appears - "Calling..."]
  ↓
[Other user's side]
📞 Incoming call signal received
  ↓
showIncomingCallModal = true → [Incoming call modal appears]
  ↓
User clicks "Accept"
  ↓
✅ Call answered signal received
  ↓
✅ Peer connection ready
  ↓
✅ Call connected successfully
```

---

## Network Testing

To verify backend is working:

### Using Curl (in PowerShell):
```powershell
$body = @{
    to_id = 2
    sdp = "v=0..."  # (sample SDP)
    type = "offer"
    call_type = "audio"
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:8000/api/call/start" `
  -Method POST `
  -Headers @{Authorization="Bearer YOUR_TOKEN"; "Content-Type"="application/json"} `
  -Body $body
```

### Using Postman:
1. Create `POST` request to `http://localhost:8000/api/call/start`
2. Add header: `Authorization: Bearer YOUR_TOKEN`
3. Add body (JSON):
```json
{
  "to_id": 2,
  "sdp": "v=0\r\no=- 123 456...",
  "type": "offer",
  "call_type": "audio"
}
```
4. Send and check response

---

## WebSocket Connection Check

In the browser console, run:
```javascript
console.log(window.Echo); // Should show Echo object
```

If it's undefined, broadcasting isn't initialized.

---

## Clear All Logs

To focus on fresh call attempt:
```javascript
console.clear()
```

Then click call button again.

---

## Next Steps

1. **Test locally:** Click call button and watch console
2. **Report the first error message** you see (if any)
3. Provide:
   - The full error log from console
   - Backend endpoint response (from Postman test)
   - Check if WebSocket is connected (Network tab → WS filter)

---

## Call-Related Console Logs Reference

| Log | Meaning | Success? |
|-----|---------|----------|
| `🔴 startCall initiated` | Button clicked, function started | ✅ |
| `✅ Microphone access granted` | Permission dialog accepted | ✅ |
| `❌ Microphone access denied` | Permission denied | ❌ |
| `✅ Peer connection ready` | WebRTC ready | ✅ |
| `✅ Call start signal sent successfully` | Backend received offer | ✅ |
| `❌ startCall failed` | Backend error or network issue | ❌ |
| `📞 Incoming call signal received` | Other user got the offer (other device console) | ✅ |
| `📵 Call ended signal received` | Call ended normally | ✅ |
| `❌ Echo call channel error` | Real-time channel issue | ❌ |

---

## Need More Help?

Run this in console to get system info:
```javascript
console.log({
  userAgent: navigator.userAgent,
  mediaDevices: !!navigator.mediaDevices,
  getUserMedia: !!navigator.mediaDevices?.getUserMedia,
  Echo: typeof window.Echo,
  broadcastDriver: 'pusher', // from your config
});
```

Share the output if you need support.

