<!-- Vue 3 Call Component Example -->
<template>
  <div class="call-container">
    <!-- Incoming Call Popup -->
    <div v-if="incomingCall" class="incoming-call-popup">
      <div class="popup-header">
        <img :src="incomingCallData.senderPhoto" class="caller-avatar" />
        <h3>{{ incomingCallData.senderName }}</h3>
        <p v-if="incomingCallData.callType === 'video'">📹 Video Call</p>
        <p v-else>🔊 Audio Call</p>
      </div>
      <div class="popup-actions">
        <button @click="acceptCall" class="btn-accept">✓ Accept</button>
        <button @click="rejectCall" class="btn-reject">✗ Reject</button>
      </div>
    </div>

    <!-- Active Call Interface -->
    <div v-if="activeCall" class="call-interface">
      <div class="call-status">
        <span v-if="callStatus === 'ringing'">Calling...</span>
        <span v-else-if="callStatus === 'connecting'">Connecting...</span>
        <span v-else-if="callStatus === 'connected'">
          Connected {{ formatCallDuration(callDuration) }}
        </span>
      </div>

      <div v-if="isGroupCall" class="group-participants">
        <div v-for="participant in activeParticipants" :key="participant.id" class="participant">
          <img :src="participant.photo" :title="participant.name" />
        </div>
      </div>

      <div class="call-controls">
        <button @click="toggleAudio" :class="{ disabled: !audioEnabled }">
          {{ audioEnabled ? '🔊' : '🔇' }}
        </button>
        <button @click="toggleVideo" :class="{ disabled: !videoEnabled }">
          {{ videoEnabled ? '📹' : '📷' }}
        </button>
        <button @click="endCall" class="btn-end-call">☎ End Call</button>
      </div>
    </div>

    <!-- Call History View -->
    <div v-if="showCallHistory" class="call-history">
      <h3>Call History</h3>
      <div v-if="callHistory.length === 0" class="empty-state">
        No calls yet
      </div>
      <div v-else v-for="call in callHistory" :key="call.id" class="call-item">
        <img :src="call.counterpart.profile_photo_url" class="avatar" />
        <div class="call-details">
          <p class="call-name">{{ call.counterpart.name }}</p>
          <p class="call-meta">
            {{ formatDate(call.started_at) }} •
            {{ call.duration_seconds || 0 }}s
          </p>
        </div>
        <span v-if="call.direction === 'outgoing'" class="call-direction">📞</span>
        <span v-else class="call-direction">📱</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import CallService from '@/services/CallService';
import allService from '@/services/all-service';
import { Echo } from 'laravel-echo';

// State
const incomingCall = ref(false);
const activeCall = ref(false);
const isGroupCall = ref(false);
const callStatus = ref('ringing'); // ringing, connecting, connected
const callDuration = ref(0);
const audioEnabled = ref(true);
const videoEnabled = ref(true);
const showCallHistory = ref(false);
const currentUserId = ref(null);
const currentCallId = ref(null);

const incomingCallData = reactive({
  fromId: null,
  callId: null,
  senderName: '',
  senderPhoto: '',
  callType: 'audio',
});

const activeParticipants = ref([]);
const callHistory = ref([]);

// WebRTC
let peerConnection = null;
let localStream = null;
let callDurationTimer = null;

// ============================================================================
// INCOMING CALL HANDLING
// ============================================================================

const setupCallListeners = () => {
  // Listen for incoming 1:1 calls
  Echo.private(`call.${currentUserId.value}`)
    .listen('incoming.call', async (data) => {
      console.log('📞 Incoming call from:', data.from_id);

      // Fetch caller details
      try {
        const callerDetails = await allService.getUser(data.from_id);
        incomingCallData.fromId = data.from_id;
        incomingCallData.callId = data.call_id;
        incomingCallData.senderName = callerDetails.name;
        incomingCallData.senderPhoto = callerDetails.profile_photo_url;
        incomingCallData.callType = data.call_type || 'audio';
      } catch (error) {
        console.error('Failed to fetch caller details:', error);
      }

      incomingCall.value = true;
      // Play ringtone sound
      playRingtone();
    })
    .listen('call.accepted', (data) => {
      console.log('✓ Call accepted by:', data.from_id);
      callStatus.value = 'connecting';
      // Caller: transition to connected state
      // Add remote offer to peer connection
      handleRemoteOffer(data);
    })
    .listen('call.ended', (data) => {
      console.log('✗ Call ended by:', data.from_id);
      endCallUI();
    })
    .listen('ice.candidate', (data) => {
      console.log('❄ ICE candidate received');
      if (peerConnection && data.candidate) {
        peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate))
          .catch(err => console.error('Failed to add ICE candidate:', err));
      }
    });

  // Listen for group calls
  Echo.private(`group-call.${currentGroupId.value}`)
    .listen('group.call', (data) => {
      console.log('👥 Group call event:', data.action);

      switch (data.action) {
        case 'start':
          handleGroupCallStart(data);
          break;
        case 'join':
          handleGroupCallJoin(data);
          break;
        case 'signal':
          handleGroupSignal(data);
          break;
        case 'end':
          handleGroupCallEnd(data);
          break;
      }
    });
};

// ============================================================================
// CALL ACTIONS
// ============================================================================

const acceptCall = async () => {
  try {
    incomingCall.value = false;
    activeCall.value = true;
    currentCallId.value = incomingCallData.callId;
    callStatus.value = 'connecting';

    // Get local stream (audio/video)
    const constraints = {
      audio: true,
      video: incomingCallData.callType === 'video'
    };
    localStream = await navigator.mediaDevices.getUserMedia(constraints);

    // Create peer connection
    peerConnection = new RTCPeerConnection({
      iceServers: [
        { urls: ['stun:stun.l.google.com:19302', 'stun:stun1.l.google.com:19302'] }
      ]
    });

    // Add local stream tracks
    localStream.getTracks().forEach(track => {
      peerConnection.addTrack(track, localStream);
    });

    // Handle remote stream
    peerConnection.ontrack = (event) => {
      console.log('🎥 Remote stream received');
      const remoteVideo = document.getElementById('remote-video');
      if (remoteVideo && event.streams[0]) {
        remoteVideo.srcObject = event.streams[0];
      }
    };

    // Handle ICE candidates
    peerConnection.onicecandidate = (event) => {
      if (event.candidate) {
        CallService.sendIce(incomingCallData.fromId, event.candidate, incomingCallData.callId);
      }
    };

    // Create and send answer
    const answer = await peerConnection.createAnswer();
    await peerConnection.setLocalDescription(answer);

    // Send answer to backend
    await CallService.answerCall(incomingCallData.callId, answer.sdp);

    callStatus.value = 'connected';
    startCallDurationTimer();

  } catch (error) {
    console.error('❌ Failed to accept call:', error);
    endCallUI();
  }
};

const rejectCall = async () => {
  incomingCall.value = false;
  // Optionally: notify caller of rejection
  console.log('Call rejected');
};

const endCall = async () => {
  try {
    // Send end signal to backend
    await CallService.endCall(currentCallId.value, 'ended');

    // Cleanup
    endCallUI();

  } catch (error) {
    console.error('❌ Failed to end call:', error);
    endCallUI();
  }
};

const endCallUI = () => {
  activeCall.value = false;
  incomingCall.value = false;
  callStatus.value = 'ringing';
  currentCallId.value = null;

  // Cleanup streams and peer connection
  if (localStream) {
    localStream.getTracks().forEach(track => track.stop());
    localStream = null;
  }

  if (peerConnection) {
    peerConnection.close();
    peerConnection = null;
  }

  stopCallDurationTimer();
  stopRingtone();

  // Save call to history
  saveCallHistory();
};

// ============================================================================
// GROUP CALL HANDLING
// ============================================================================

const handleGroupCallStart = async (data) => {
  console.log('Group call started by:', data.from_id);
  isGroupCall.value = true;
  activeParticipants.value = data.participants.map(id => ({
    id,
    name: 'User ' + id,
    photo: ''
  }));

  // Show "join group call?" prompt
  const shouldJoin = confirm('Join group call?');
  if (shouldJoin) {
    joinGroupCall(data.group_id, data.call_id);
  }
};

const joinGroupCall = async (groupId, callId) => {
  try {
    activeCall.value = true;
    isGroupCall.value = true;
    currentCallId.value = callId;

    // Get local stream
    localStream = await navigator.mediaDevices.getUserMedia({
      audio: true,
      video: false
    });

    // Create peer connection (simplified; full implementation needs SFU/MCU)
    peerConnection = new RTCPeerConnection({
      iceServers: [{ urls: ['stun:stun.l.google.com:19302'] }]
    });

    localStream.getTracks().forEach(track => {
      peerConnection.addTrack(track, localStream);
    });

    // Create answer
    const answer = await peerConnection.createAnswer();
    await peerConnection.setLocalDescription(answer);

    // Send join request
    await CallService.http.post('/api/call/group/join', {
      group_id: groupId,
      call_id: callId,
      sdp: answer.sdp
    });

    callStatus.value = 'connected';
    startCallDurationTimer();

  } catch (error) {
    console.error('❌ Failed to join group call:', error);
    endCallUI();
  }
};

const handleGroupCallJoin = (data) => {
  console.log('User joined group call:', data.user_id);
  // Update participants list
  if (!activeParticipants.value.find(p => p.id === data.user_id)) {
    activeParticipants.value.push({
      id: data.user_id,
      name: 'User ' + data.user_id,
      photo: ''
    });
  }
};

const handleGroupSignal = (data) => {
  console.log('Group signal received:', data.signal);
  // Handle ICE/offer/answer exchange
  if (data.signal && peerConnection) {
    if (data.signal.candidate) {
      peerConnection.addIceCandidate(new RTCIceCandidate(data.signal));
    }
  }
};

const handleGroupCallEnd = (data) => {
  console.log('Group call ended');
  endCallUI();
};

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

const toggleAudio = () => {
  if (localStream) {
    localStream.getAudioTracks().forEach(track => {
      track.enabled = !track.enabled;
    });
    audioEnabled.value = !audioEnabled.value;
  }
};

const toggleVideo = () => {
  if (localStream) {
    localStream.getVideoTracks().forEach(track => {
      track.enabled = !track.enabled;
    });
    videoEnabled.value = !videoEnabled.value;
  }
};

const startCallDurationTimer = () => {
  callDurationTimer = setInterval(() => {
    callDuration.value++;
  }, 1000);
};

const stopCallDurationTimer = () => {
  if (callDurationTimer) {
    clearInterval(callDurationTimer);
    callDurationTimer = null;
    callDuration.value = 0;
  }
};

const formatCallDuration = (seconds) => {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  if (hours > 0) {
    return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  }
  return `${minutes}:${String(secs).padStart(2, '0')}`;
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString();
};

const playRingtone = () => {
  // Play ringtone sound
  const audio = new Audio('/sounds/ringtone.mp3');
  audio.loop = true;
  audio.play();
};

const stopRingtone = () => {
  // Stop ringtone
  document.querySelectorAll('audio').forEach(a => a.pause());
};

const saveCallHistory = async () => {
  try {
    await CallService.http.post('/api/call/history', {
      user_id: incomingCallData.fromId || currentUserId.value,
      direction: incomingCall.value ? 'incoming' : 'outgoing',
      status: 'ended',
      call_type: incomingCallData.callType,
      duration_seconds: callDuration.value
    });
  } catch (error) {
    console.error('Failed to save call history:', error);
  }
};

const loadCallHistory = async () => {
  try {
    const response = await CallService.getCallHistory();
    callHistory.value = response;
  } catch (error) {
    console.error('Failed to load call history:', error);
  }
};

// ============================================================================
// LIFECYCLE
// ============================================================================

onMounted(async () => {
  // Get current user
  const user = await allService.getUser();
  currentUserId.value = user.id;

  // Setup Echo listeners
  setupCallListeners();

  // Load call history
  loadCallHistory();
});

onUnmounted(() => {
  endCallUI();
});
</script>

<style scoped>
.call-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.incoming-call-popup {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  border-radius: 12px;
  padding: 40px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  z-index: 1000;
  animation: slideInUp 0.3s ease-out;
}

.popup-header {
  margin-bottom: 30px;
}

.caller-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  margin-bottom: 15px;
}

.popup-header h3 {
  font-size: 24px;
  margin: 0;
}

.popup-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
}

.btn-accept, .btn-reject {
  padding: 12px 30px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 16px;
}

.btn-accept {
  background: #4CAF50;
  color: white;
}

.btn-reject {
  background: #f44336;
  color: white;
}

.call-interface {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  gap: 20px;
  background: #1a1a1a;
  color: white;
}

.call-status {
  font-size: 18px;
  font-weight: bold;
}

.group-participants {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
  gap: 10px;
}

.participant img {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
}

.call-controls {
  display: flex;
  gap: 15px;
}

.call-controls button {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  font-size: 24px;
  background: #333;
  color: white;
  transition: all 0.3s;
}

.call-controls button:hover {
  background: #444;
  transform: scale(1.1);
}

.call-controls button.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-end-call {
  background: #f44336 !important;
}

.call-history {
  padding: 20px;
}

.call-item {
  display: flex;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #eee;
}

.avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-right: 15px;
}

.call-details {
  flex: 1;
}

.call-name {
  font-weight: bold;
  margin: 0;
}

.call-meta {
  font-size: 12px;
  color: #999;
  margin: 0;
}

.call-direction {
  font-size: 20px;
}

@keyframes slideInUp {
  from {
    transform: translate(-50%, 100%);
    opacity: 0;
  }
  to {
    transform: translate(-50%, -50%);
    opacity: 1;
  }
}
</style>

