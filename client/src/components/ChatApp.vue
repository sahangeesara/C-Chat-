<template>
  <div class="container-fluid vh-100 g-0 chat-shell">
    <div class="row g-0 h-100 chat-layout flex-nowrap">
      <!-- Left Sidebar - Fixed width like WhatsApp -->
      <div class="chat-sidebar d-flex flex-column border-end bg-light" v-show="showSidebarPane">
        <!-- Profile Header -->
        <div class="p-3 border-bottom">
          <h1 class="mb-0 fw-bold">Ch-Chat</h1>
          <small class="text-muted">{{ currentUserName }}</small>
        </div>

        <!-- Search Bar -->
        <div class="p-3 border-bottom">
          <div class="input-group">
            <input
                type="text"
                class="form-control"
                v-model="searchQuery"
                placeholder="Search Friends"
                @input="searchUser"
            >
            <button
                type="button"
                class="input-group-text"
                @click="clearSearch"
                :title="searchQuery.trim() ? 'Clear search' : 'Search'"
            >
              <i class="bi" :class="searchQuery.trim() ? 'bi-x-lg' : 'bi-search'"></i>
            </button>
          </div>
        </div>

        <!-- User List - Scrollable area -->
        <div class="flex-grow-1 overflow-auto">
          <div
              v-for="user in visibleUsers"
              :key="user.id"
              class="p-2 border-bottom"
          >
            <button
                class="list-group-item list-group-item-action border-0 d-flex align-items-center"
                :class="{ 'active': selectedUser?.id === user?.id }"
                @click="selectUser(user)"
            >
              <span class="flex-shrink-0 me-2">
                <span class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                  <span class="text-white" >{{ user?.name?.charAt(0) || '?' }}</span>
                </span>
              </span>
              <span class="flex-grow-1">
                {{ user?.name || 'Unknown User' }}
                <small class="text-muted text-truncate d-block" style="max-width: 250px;">Message for {{ user?.name || 'Unknown' }}</small>
              </span>
              <span class="flex-shrink-0">
                <small class="text-muted">12:30 PM</small>
              </span>
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-3 border-top bg-white">
          <router-link to="/" class="btn btn-outline-secondary w-100">
            <i class="bi bi-box-arrow-in-right"></i> Logout
          </router-link>
          <div class="d-flex justify-content-between small text-muted mt-2">
            <span>31°C Light rain</span>
            <span>US</span>
            <span>{{ currentDate }}</span>
          </div>
        </div>
      </div>
      <!-- Right Chat Area - Only shown when user is selected -->
      <div class="chat-main d-flex flex-column" v-if="selectedUser" v-show="showChatPane">
        <!-- Chat Header -->
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary me-2 d-md-none"
                @click="goBackToUsers"
            >
              <i class="bi bi-arrow-left"></i>
            </button>
            <div class="me-3">
              <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <span class="text-white" >{{ selectedUser?.name?.charAt(0) || '?' }}</span>
              </div>
            </div>
            <div>
              <h6 class="mb-0">Chat with {{ selectedUser.name }}</h6>
              <small class="text-muted">Online</small>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary">
              <i class="bi bi-camera-video"></i>
            </button>
            <button class="btn btn-sm btn-outline-primary" @click="startCall(selectedUser.id)">
              <i class="bi bi-telephone fs-6"></i>
            </button>
          </div>
        </div>

        <!-- ADD COLOR -->
        <!-- Chat Messages -->
        <div
            class="flex-grow-1 overflow-auto p-3 bg-light bg-opacity-10"
            ref="chatContainer"
            style="height: 0;"
        >
          <div v-for="(msg, index) in chat.messages" :key="index"
               class="mb-2 d-flex"
               :class="{ 'justify-content-end': msg.from_id === currentUserId }"
          >
            <!-- color message-->
            <div
                class="p-2 rounded-3 chat-message-bubble"
                :class="{
                'bg-primary text-white': msg.from_id === currentUserId,
                'bg-success text-white': msg.from_id !== currentUserId
              }"
                style="max-width: 70%;"
            >
              {{ msg.body }}
              <div class="text-end" style="font-size: 0.7rem; margin-top: 2px;">
                <span style="color: rgba(245,245,245,0.7) !important;">{{ formatMessageDate(msg.created_at) }}</span>
                <span v-if="msg.from_id === currentUserId" class="ms-1">
                  <i class="bi bi-check2-all text-info"></i>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Message Input -->
        <div class="p-3 border-top bg-light">
          <div class="input-group">
            <button class="btn btn-outline-secondary" type="button">
              <i class="bi bi-emoji-smile"></i>
            </button>
            <input
                type="text"
                class="form-control rounded-pill mx-2"
                v-model="message"
                placeholder="Type your Message..."
                @keyup.enter="sendMessage"
            >
            <button
                class="btn btn-primary rounded-circle"
                @click="sendMessage"
            >
              <i class="bi bi-send-fill"></i>
            </button>
          </div>
        </div>
      </div>
      <!-- Empty State when no user is selected -->
      <div class="chat-main d-flex flex-column justify-content-center align-items-center bg-light" v-else v-show="showChatPane">
        <div class="text-center p-5">
          <i class="bi bi-people-fill" style="font-size: 3rem; color: #ccc;"></i>
          <h3 class="mt-3 text-muted">Select a user to start chatting</h3>
          <p class="text-muted">Search and select a friend from the list to view your conversation</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import Chat from './Chat.vue';
import AllServiceService from '@/services/all-service';
import echo from '@/services/echo'
import CallService from "@/services/CallService";

export default {
  components: { Chat },
  setup() {
    const chatContainer = ref(null);
    const message = ref('');
    const userView = ref([]);
    const chat = ref({ messages: [] });
    const searchQuery = ref('');
    const selectedUser = ref(null);
    let currentUserId = ref(null);
    let currentUserName = ref(null);
    const latestSearchRequestId = ref(0)
    const searchDebounceTimer = ref(null)
    const isMobileView = ref(window.innerWidth < 768)
    const showSidebarOnMobile = ref(true)
    const pc = ref(null)
    const partnerId = ref(null)
    const remoteAudio = ref(null)
    const activeChannelNames = ref([])
    const updateViewportMode = () => {
      isMobileView.value = window.innerWidth < 768;
      if (!isMobileView.value) {
        showSidebarOnMobile.value = true;
      }
    };
    // Get current date in DD/MM/YYYY format
    const currentDate = computed(() => {
      const today = new Date();
      const day = String(today.getDate()).padStart(2, '0');
      const month = String(today.getMonth() + 1).padStart(2, '0');
      const year = today.getFullYear();
      return `${day}/${month}/${year}`;
    });

    const allService = new AllServiceService();

    const showSidebarPane = computed(() => !isMobileView.value || showSidebarOnMobile.value);
    const showChatPane = computed(() => !isMobileView.value || !showSidebarOnMobile.value);

    // Filter users based on search query only.
    const filteredUsers = computed(() => {
      const term = searchQuery.value.trim().toLowerCase();

      return term
        ? userView.value.filter(user =>
            (user?.name || '').toLowerCase().includes(term)
          )
        : userView.value;
    });

    const visibleUsers = computed(() => filteredUsers.value.slice(0, 10));

    const extractUserPage = (response) => {
      if (Array.isArray(response)) {
        return {
          items: response,
          nextPage: null,
          currentPage: 1,
          lastPage: 1
        };
      }

      if (Array.isArray(response?.data)) {
        return {
          items: response.data,
          nextPage: response.next_page_url ? (response.current_page || 1) + 1 : null,
          currentPage: response.current_page || 1,
          lastPage: response.last_page || 1
        };
      }

      return {
        items: [],
        nextPage: null,
        currentPage: 1,
        lastPage: 1
      };
    };

    const fetchUserData = async (query) => {
      const requestId = ++latestSearchRequestId.value;

      try {
        const users = [];
        let page = 1;
        let shouldContinue = true;

        while (shouldContinue && page <= 20 && users.length < 10) {
          const response = await allService.searchUser(query, page);
          const parsed = extractUserPage(response);

          users.push(...parsed.items);

          const hasNextByUrl = Boolean(response?.next_page_url);
          const hasNextByCount = parsed.currentPage < parsed.lastPage;
          shouldContinue = hasNextByUrl || hasNextByCount;
          page += 1;
        }

        if (requestId === latestSearchRequestId.value) {
          userView.value = Array.from(new Map(users.map((user) => [user.id, user])).values()).slice(0, 10);
        }
      } catch (error) {
        console.error('Error fetching user data:', error);

        if (requestId === latestSearchRequestId.value) {
          userView.value = [];
        }
      }
    };

    const startCall = async (toId) => {
      partnerId.value = toId

      const offer = await pc.value.createOffer()
      await pc.value.setLocalDescription(offer)

      await CallService.startCall(toId, offer)
    }

    const fetchUserId = async () => {
      try {
        const response = await allService.getUser();
        if (response && response.user && response.user.id) {
          currentUserId.value = response.user.id;
          currentUserName.value = response.user.name;
        } else {
          console.warn("No user ID found in response.");
        }
      } catch (error) {
        console.error("Error fetching user:", error);
      }
    };

    const userId = async (id) => {
      try {
        const response = await allService.getUserProfile(id);
        if (response && response.user && response.user.id) {
          // Profile lookup is for the selected person only.
          selectedUser.value = response.user;
        } else {
          console.warn("No user ID found in response.");
        }
      } catch (error) {
        console.error("Error fetching user:", error);
      }
    };

    const normalizeMessagePayload = (payload, fallback = {}) => {
      const source = typeof payload?.message === 'object' && payload.message !== null ? payload.message : payload;

      return {
        id: source?.id ?? source?.message_id ?? fallback?.id ?? null,
        from_id: source?.from_id ?? source?.sender_id ?? source?.user_id ?? fallback?.from_id ?? null,
        to_id: source?.to_id ?? source?.receiver_id ?? fallback?.to_id ?? null,
        body: source?.body ?? source?.message ?? source?.text ?? fallback?.body ?? '',
        created_at: source?.created_at ?? source?.date ?? fallback?.created_at ?? new Date().toISOString(),
        _optimistic: Boolean(fallback?._optimistic),
      };
    };

    const sendMessage = async () => {
      if (!message.value || !selectedUser.value) {
        return;
      }

      const text = message.value.trim();
      if (!text) {
        message.value = '';
        return;
      }

      const optimisticMessage = normalizeMessagePayload({
        id: `tmp-${Date.now()}-${Math.random().toString(36).slice(2)}`,
        from_id: currentUserId.value,
        to_id: selectedUser.value.id,
        body: text,
        created_at: new Date().toISOString(),
      }, { _optimistic: true });

      chat.value.messages.push(optimisticMessage);
      message.value = '';

      try {
        const response = await allService.sendMessages({
          user_id: selectedUser.value.id,
          message: text,
        });

        const serverMessage = normalizeMessagePayload(response?.message ?? response?.data ?? response, optimisticMessage);

        if (serverMessage?.body) {
          const optimisticIndex = chat.value.messages.findIndex((item) => item._optimistic && item.id === optimisticMessage.id);
          if (optimisticIndex !== -1) {
            chat.value.messages.splice(optimisticIndex, 1, {
              ...optimisticMessage,
              ...serverMessage,
              _optimistic: false,
            });
          }
        }
      } catch (error) {
        console.error('Send failed:', error?.response?.data || error?.message || error);
        chat.value.messages = chat.value.messages.filter((item) => item.id !== optimisticMessage.id);
        message.value = text;
      }
    };

    const selectUser = (user) => {
      selectedUser.value = user;
      getMessage(user.id);

      if (isMobileView.value) {
        showSidebarOnMobile.value = false;
      }
    };

    const goBackToUsers = () => {
      showSidebarOnMobile.value = true;
    };

    const selectUserprofile = (user) => {
      selectedUser.value = user;
      userId(user.id);
    };


    const searchUser = async () => {
      if (searchDebounceTimer.value) {
        clearTimeout(searchDebounceTimer.value);
      }

      const query = searchQuery.value.trim();

      if (!query) {
        await fetchUserData('');
        return;
      }

      if (query.length < 2) {
        return;
      }

      searchDebounceTimer.value = setTimeout(() => {
        fetchUserData(query);
      }, 250);
    };

    const clearSearch = async () => {
      if (!searchQuery.value.trim()) {
        return;
      }

      searchQuery.value = '';
      await fetchUserData('');
    };

    const getMessage = async (userId) => {
      try {
        const response = await allService.getMessage(userId);
        let messages = [];

        if (Array.isArray(response?.messages)) {
          messages = response.messages;
        } else if (Array.isArray(response?.data)) {
          messages = response.data;
        } else if (Array.isArray(response)) {
          messages = response;
        }

        if (messages.length > 0) {
          chat.value.messages = messages.map((item) => normalizeMessagePayload(item));
        } else {
          console.warn("No messages found.");
          chat.value.messages = [];
        }
      } catch (error) {
        console.error("Error fetching messages:", error);
      }
    };

    const scrollToBottom = () => {
      if (chatContainer.value) {
        setTimeout(() => {
          chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }, 50);
      }
    };

    const formatMessageDate = (createdAt) => {
      if (!createdAt) return '';
      const parsed = new Date(createdAt);
      if (Number.isNaN(parsed.getTime())) return '';
      return parsed.toLocaleDateString([], { month: '2-digit', day: '2-digit', year: 'numeric' });
    };

    const normalizeId = (value) => {
      const numeric = Number(value);
      return Number.isNaN(numeric) ? value : numeric;
    };

    const sameUserId = (a, b) => normalizeId(a) === normalizeId(b);

    const mapRealtimeMessage = (event) => {
      return normalizeMessagePayload(event);
    };

    const appendMessage = (incoming) => {
      if (!incoming?.body) {
        return;
      }

      const optimisticIndex = chat.value.messages.findIndex((item) =>
        item._optimistic &&
        sameUserId(item.from_id, incoming.from_id) &&
        sameUserId(item.to_id, incoming.to_id) &&
        item.body === incoming.body
      );

      if (optimisticIndex !== -1) {
        chat.value.messages.splice(optimisticIndex, 1, incoming);
        return;
      }

      const alreadyExists = chat.value.messages.some((item) => {
        if (item.id && incoming.id) {
          return item.id === incoming.id;
        }

        return (
          sameUserId(item.from_id, incoming.from_id) &&
          sameUserId(item.to_id, incoming.to_id) &&
          item.body === incoming.body &&
          item.created_at === incoming.created_at
        );
      });

      if (!alreadyExists) {
        chat.value.messages.push(incoming);
      }
    };

    const subscribeToRealtimeMessages = (authUserId) => {
      if (!authUserId) {
        return;
      }

      activeChannelNames.value.forEach((channelName) => {
        echo.leave(channelName);
      });

      // Server authorizes only chat.{currentUserId} private channels.
      const channelName = `chat.${authUserId}`;
      activeChannelNames.value = [channelName];

      const handleRealtimeEvent = (event) => {
        const incoming = mapRealtimeMessage(event);

        const isCurrentConversation =
            selectedUser.value &&
            (sameUserId(incoming.from_id, selectedUser.value.id) || sameUserId(incoming.to_id, selectedUser.value.id));

        if (isCurrentConversation) {
          appendMessage(incoming);
        }
      };

      echo.private(channelName)
          .listen('.chat.message', handleRealtimeEvent)
          .listen('MessageSent', handleRealtimeEvent)
          .listen('.MessageSent', handleRealtimeEvent)
          .listen('ChatMessageSent', handleRealtimeEvent)
          .listen('my-event', handleRealtimeEvent)
          .error(err => console.error('Echo private error:', err));
    };

    const initWebRTC = async () => {
      try {
        pc.value = new RTCPeerConnection({
          iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
        })

        const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
        stream.getTracks().forEach(track => pc.value.addTrack(track, stream))

        pc.value.ontrack = (e) => {
          if (remoteAudio.value) {
            remoteAudio.value.srcObject = e.streams[0]
          }
        }

        pc.value.onicecandidate = (e) => {
          if (e.candidate && partnerId.value) {
            CallService.sendIce(partnerId.value, e.candidate)
          }
        }
      } catch (error) {
        console.warn('WebRTC init skipped:', error)
      }
    }

    onMounted(() => {
      window.addEventListener('resize', updateViewportMode);
      updateViewportMode();

      fetchUserId()
      fetchUserData('')
      initWebRTC()
    })

    watch(currentUserId, (id) => {
      if (!id) return

      subscribeToRealtimeMessages(id)

          //call service
      // echo.private(`call.${id}`)
      //     .listen('.incoming.call', async (e) => {
      //       console.log('📞 Incoming call from', e.fromId)

      //       partnerId.value = e.fromId
  
      //      await pc.value.setRemoteDescription(
      //       new RTCSessionDescription(e.offer)
      //     )

      //       const answer = await pc.value.createAnswer()
      //       await pc.value.setLocalDescription(answer)

      //       CallService.answerCall(e.fromId, answer)
      //     })
      //     .listen('.ice.candidate', (e) => {
      //       pc.value.addIceCandidate(e.candidate)
      //     })

    })

    watch(() => chat.value.messages.length, () => {
      scrollToBottom();
    });

    onBeforeUnmount(() => {
      activeChannelNames.value.forEach((channelName) => echo.leave(channelName));

      window.removeEventListener('resize', updateViewportMode);

      if (searchDebounceTimer.value) {
        clearTimeout(searchDebounceTimer.value);
      }
    });

    return {
      chatContainer,
      message,
      userView,
      chat,
      searchQuery,
      selectedUser,
      currentUserId,
      currentUserName,
      currentDate,
      filteredUsers,
      visibleUsers,
      showSidebarPane,
      showChatPane,
      sendMessage,
      selectUser,
      goBackToUsers,
      clearSearch,
      searchUser,
      getMessage,
      selectUserprofile,
      userId,
      startCall,
      formatMessageDate
    };
  }
};
</script>

<style>
.container-fluid {
  padding-right: 0 !important;
  padding-left: 0 !important;
}

.g-0 {
  --bs-gutter-x: 0;
  --bs-gutter-y: 0;
}

/* Scrollbar styling */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #d1d1d1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #b8b8b8;
}

body, html {
  overflow: hidden;
}

/* User list item hover effect */
.list-group-item:hover {
  background-color: #f5f5f5 !important;
}

/* Active user highlight */
.list-group-item.active {
  background-color: #e9ecef !important;
}


/* Add these to your style section */
.h-100 {
  height: 100%;
}


.flex-grow-1 {
  flex-grow: 1;
  min-height: 0; /* This is ESSENTIAL for proper flexbox scrolling */
}

.chat-shell {
  height: 100vh;
  overflow: hidden;
  background: #3b82f6;
}

/* Keep the sidebar and chat pane on one row on desktop */
.chat-layout {
  width: 100%;
  height: 100%;
  flex-wrap: nowrap;
}

.chat-sidebar {
  flex: 0 0 380px;
  width: 380px;
  max-width: 380px;
  min-width: 280px;
}

.chat-main {
  flex: 1 1 auto;
  min-width: 0;
}

.chat-main > .flex-grow-1 {
  min-width: 0;
}

/* Ensure very long words like ttttttt... wrap into multiple lines. */
.chat-message-bubble {
  overflow-wrap: anywhere;
  word-break: break-word;
  white-space: pre-wrap;
  text-align: justify;
  text-justify: inter-word;
}

/* Mobile / narrow screens: stack the panes vertically */
@media (max-width: 767.98px) {
  .chat-shell {
    height: auto;
    min-height: 100vh;
    overflow: auto;
  }

  .chat-layout {
    flex-wrap: wrap;
    height: auto;
  }

  .chat-sidebar,
  .chat-main {
    flex: 0 0 100%;
    width: 100%;
    max-width: 100%;
  }
}
</style>
