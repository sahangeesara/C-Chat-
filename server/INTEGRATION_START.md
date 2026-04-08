# 🚀 INTEGRATION QUICK START - Call System

## What Was Fixed

Your Laravel chat app now has **real-time 1:1 and group call signaling**:

✅ Caller can reach receiver (CallAccepted event added)  
✅ Video/audio type preserved (call_type in payload)  
✅ Group calls fully functional (groupStart/Join/Signal/End)  
✅ Call termination notified (CallEnded event)  
✅ History persists (POST /api/call/history)  

---

## 5-Minute Integration

### Add to Your Vue Component

```javascript
import CallService from '@/services/CallService'

export default {
  setup() {
    const currentUserId = ref(null)
    const incomingCall = ref(null)

    onMounted(async () => {
      // Get current user
      const user = await allService.getUser()
      currentUserId.value = user.id

      // Listen for incoming calls
      echo.private(`call.${currentUserId.value}`)
        .listen('incoming.call', (data) => {
          console.log('📞 Incoming call:', data)
          incomingCall.value = data
        })
        .listen('call.accepted', (data) => {
          console.log('✓ Call accepted')
        })
        .listen('call.ended', (data) => {
          console.log('✗ Call ended')
        })
        .listen('ice.candidate', (data) => {
          console.log('❄ ICE:', data.candidate)
        })

      // Listen for group calls
      echo.private(`group-call.${groupId}`)
        .listen('group.call', (data) => {
          console.log('👥 Group event:', data.action)
        })
    })

    const acceptCall = async () => {
      try {
        // Get media stream
        const stream = await navigator.mediaDevices.getUserMedia({
          audio: true,
          video: incomingCall.value.call_type === 'video'
        })

        // Create peer connection + answer
        const pc = new RTCPeerConnection()
        stream.getTracks().forEach(t => pc.addTrack(t, stream))

        // Listen for remote stream
        pc.ontrack = (e) => {
          remoteVideo.srcObject = e.streams[0]
        }

        // Send ICE candidates
        pc.onicecandidate = (e) => {
          if (e.candidate) {
            CallService.sendIce(
              incomingCall.value.from_id,
              e.candidate,
              incomingCall.value.call_id
            )
          }
        }

        // Create & send answer
        const answer = await pc.createAnswer()
        await pc.setLocalDescription(answer)
        await CallService.answerCall(incomingCall.value.call_id, answer.sdp)

        incomingCall.value = null
      } catch (err) {
        console.error('Call failed:', err)
      }
    }

    return { incomingCall, acceptCall }
  }
}
```

---

## API Endpoints (Use CallService or fetch directly)

### 1:1 Calls
```javascript
// Start call
await CallService.startCall(toUserId, offerSDP)

// Accept call
await CallService.answerCall(callId, answerSDP)

// Send ICE candidate
await CallService.sendIce(toUserId, candidate, callId)

// End call
await CallService.endCall(callId)

// Get history
await CallService.getCallHistory()
```

### Group Calls
```javascript
// Start group call
await CallService.http.post('/api/call/group/start', {
  group_id: groupId,
  call_type: 'audio',
  offer: offerSDP
})

// Join group call
await CallService.http.post('/api/call/group/join', {
  group_id: groupId,
  call_id: callId,
  sdp: answerSDP
})

// Send group signal
await CallService.http.post('/api/call/group/signal', {
  group_id: groupId,
  call_id: callId,
  signal: candidate
})

// End group call
await CallService.http.post('/api/call/group/end', {
  group_id: groupId,
  call_id: callId
})
```

---

## Broadcast Events to Listen

**Channel:** `call.{userId}`
- `incoming.call` → New call incoming
- `call.accepted` → Recipient accepted
- `call.ended` → Call ended
- `ice.candidate` → ICE candidate for connection

**Channel:** `group-call.{groupId}`
- `group.call` → Group event (start/join/signal/end)

---

## Test It

```bash
# Terminal 1: WebSocket server
php artisan websockets:serve

# Terminal 2: Laravel
php artisan serve

# Terminal 3: Frontend
npm run dev

# Open two browser windows, login as different users, make call!
```

---

## Files to Reference

- **CALL_COMPONENT_EXAMPLE.vue** - Full Vue 3 example with WebRTC
- **CALL_SYSTEM_IMPLEMENTATION.md** - Detailed guide
- **CALL_SYSTEM_TEST_GUIDE.sh** - Curl testing examples

---

**Status:** ✅ Backend Complete | ⏳ Frontend Integration Needed


