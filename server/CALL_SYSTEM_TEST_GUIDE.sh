#!/bin/bash

# ==============================================================================
# FULL CALL SYSTEM REAL-TIME TESTING GUIDE
# ==============================================================================

# Prerequisites:
# - Backend running: php artisan serve (port 8000)
# - WebSocket server running: php artisan websockets:serve (port 6001)
# - Frontend running: npm run dev (port 8080 or 3000)
# - Database seeded with test users

# ==============================================================================
# TEST 1: Direct 1:1 Audio Call Flow
# ==============================================================================

echo "=== TEST 1: Direct 1:1 Audio Call ==="

# User 1 starts call to User 2
curl -X POST http://127.0.0.1:8000/api/call/start \
  -H "Authorization: Bearer USER1_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "to_id": 2,
    "call_type": "audio",
    "offer": {"sdp": "v=0\r\no=- 0 0 IN IP4 127.0.0.1\r\n..."}
  }'

# Expected:
# - Backend broadcasts IncomingCall event to call.2 channel
# - Frontend (User 2) receives via Echo.private('call.2').listen('incoming.call', ...)
# - Frontend triggers incoming call UI/popup

# User 2 accepts the call
curl -X POST http://127.0.0.1:8000/api/call/accept \
  -H "Authorization: Bearer USER2_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"call_id": 1}'

# Expected:
# - Backend broadcasts CallAccepted event to call.1 channel
# - Frontend (User 1) receives via Echo.private('call.1').listen('call.accepted', ...)
# - Frontend transitions from "Calling..." to "Connected"

# User 1 sends ICE candidate
curl -X POST http://127.0.0.1:8000/api/call/ice \
  -H "Authorization: Bearer USER1_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "to_id": 2,
    "call_id": 1,
    "candidate": {"candidate": "candidate:...", "sdpMLineIndex": 0}
  }'

# Expected:
# - Backend broadcasts IceCandidate event to call.2 channel
# - Frontend (User 2) receives and adds to RTCPeerConnection

# Either user ends the call
curl -X POST http://127.0.0.1:8000/api/call/end \
  -H "Authorization: Bearer USER1_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"call_id": 1, "status": "ended"}'

# Expected:
# - Backend broadcasts CallEnded event to call.2 channel
# - Frontend (User 2) receives and cleans up call UI
# - Call history persisted to database

# ==============================================================================
# TEST 2: Direct 1:1 Video Call Flow
# ==============================================================================

echo "=== TEST 2: Direct 1:1 Video Call ==="

curl -X POST http://127.0.0.1:8000/api/call/start \
  -H "Authorization: Bearer USER1_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "to_id": 2,
    "call_type": "video",
    "offer": {"sdp": "v=0\r\n..."}
  }'

# Frontend should detect call_type=video and request camera/mic permissions

# ==============================================================================
# TEST 3: Group Audio Call Flow
# ==============================================================================

echo "=== TEST 3: Group Audio Call ==="

# User 1 starts group call
curl -X POST http://127.0.0.1:8000/api/call/group/start \
  -H "Authorization: Bearer USER1_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "group_id": 1,
    "call_type": "audio",
    "offer": {"sdp": "v=0\r\n..."}
  }'

# Expected:
# - Backend broadcasts GroupCallEvent(action=start) to group-call.1 channel
# - All group members receive event
# - Each member's frontend shows "Group call in progress" indicator

# User 2 joins group call
curl -X POST http://127.0.0.1:8000/api/call/group/join \
  -H "Authorization: Bearer USER2_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "group_id": 1,
    "call_id": 5,
    "sdp": {"sdp": "v=0\r\n..."}
  }'

# Expected:
# - Backend adds User 2 to participants list
# - Broadcasts GroupCallEvent(action=join) to all group members
# - All members' UIs update to show User 2 as active

# User 1 sends ICE to all members
curl -X POST http://127.0.0.1:8000/api/call/group/signal \
  -H "Authorization: Bearer USER1_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "group_id": 1,
    "call_id": 5,
    "signal": {"candidate": "...", "sdpMLineIndex": 0}
  }'

# Expected:
# - Broadcasts GroupCallEvent(action=signal) to group-call.1
# - All members add ICE candidate to their peer connections

# User 1 ends group call
curl -X POST http://127.0.0.1:8000/api/call/group/end \
  -H "Authorization: Bearer USER1_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"group_id": 1, "call_id": 5, "status": "ended"}'

# Expected:
# - Broadcasts GroupCallEvent(action=end) to all members
# - All UIs close call interface
# - Call history saved to database

# ==============================================================================
# TEST 4: Verify Channel Auth
# ==============================================================================

echo "=== TEST 4: Verify Private Channel Auth ==="

# Check that Pusher dashboard shows auth calls
curl -X GET http://127.0.0.1:6001/apps/YOUR_APP_ID/channels \
  -H "X-PUSHER-Key: YOUR_APP_KEY"

# Each user should successfully authenticate to:
# - call.{userId}   (for incoming calls)
# - group.{groupId} (for group chat)
# - group-call.{groupId} (for group calls)

# ==============================================================================
# TEST 5: Call History
# ==============================================================================

echo "=== TEST 5: Get Call History ==="

curl -X GET "http://127.0.0.1:8000/api/call/history?per_page=10" \
  -H "Authorization: Bearer USER1_TOKEN"

# Expected response:
# {
#   "data": [
#     {
#       "id": 1,
#       "status": "ended",
#       "call_type": "audio",
#       "direction": "outgoing",
#       "counterpart": { "id": 2, "name": "User 2", ... },
#       "started_at": "2026-04-08T10:30:00Z",
#       "answered_at": "2026-04-08T10:30:05Z",
#       "ended_at": "2026-04-08T10:35:00Z",
#       "duration_seconds": 295
#     }
#   ],
#   "meta": { "current_page": 1, "total": 5 }
# }

# ==============================================================================
# DEBUGGING CHECKLIST
# ==============================================================================

echo "=== DEBUGGING CHECKLIST ==="
echo "✓ WebSocket server running on port 6001?"
echo "✓ Frontend can reach /api/broadcasting/auth (CORS enabled)?"
echo "✓ Database migrations run: calls, call_type, group_fields?"
echo "✓ JWT token valid and not expired?"
echo "✓ Group membership verified before groupStart/groupJoin?"
echo "✓ Private channel auth returns 200 (not 403)?"
echo "✓ Browser console shows 'Subscribe to channel call.{userId}'?"
echo "✓ Pusher/WebSocket dashboard shows events dispatched?"
echo "✓ Call records saved to database?"
echo "✓ All timestamps in UTC?"

# ==============================================================================
# COMMON ERRORS & FIXES
# ==============================================================================

echo "=== COMMON ERRORS & FIXES ==="

echo "Error: 'Receiving end does not exist'"
echo "Fix: Frontend is not listening on the correct channel"
echo "  - Verify Echo.private('call.' + userId).listen('incoming.call', ...)"
echo "  - Check that userId is correct (not null)"
echo ""

echo "Error: '403 Forbidden' on /api/broadcasting/auth"
echo "Fix: Token not being sent in Authorization header"
echo "  - Verify callService.http interceptor adds Bearer token"
echo "  - Check that token is not expired (JWT_TTL=120 min)"
echo ""

echo "Error: 'Group call not received by members'"
echo "Fix: User not in group_members table"
echo "  - Verify isGroupMember() check in controller"
echo "  - Confirm group_members.user_id matches auth()->id()"
echo ""

echo "Error: 'Call history shows 0 records'"
echo "Fix: Database not persisting call records"
echo "  - Check calls table has columns: call_type, group_id, participants"
echo "  - Run migrations: migrate:refresh (development only)"
echo ""

echo "=== END OF TEST GUIDE ==="

