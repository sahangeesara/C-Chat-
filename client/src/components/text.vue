<template>
  <div class="container-fluid vh-100 g-0">
    <div class="row g-0 h-100">
      <!-- Left Sidebar - Fixed width like WhatsApp -->
      <div class="col-md-4 d-flex flex-column border-end bg-light" style="width: 380px;">
        <!-- Profile Header -->
        <div class="p-3 border-bottom">
          <h1 class="mb-0 fw-bold">Skytalk</h1>
          <small class="text-muted">chat</small>
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
            <span class="input-group-text">
              <i class="bi bi-search"></i>
            </span>
          </div>
        </div>

        <!-- User List - Scrollable area -->
        <div class="flex-grow-1 overflow-auto">
          <div
              v-for="user in filteredUsers"
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
                  <span class="text-white">{{ user?.name?.charAt(0) || '?' }}</span>
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
      <div class="col-md-8 d-flex flex-column" v-if="selectedUser">
        <!-- Chat Header -->
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <div class="me-3">
              <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <span class="text-white">{{ selectedUser.name.charAt(0) }}</span>
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
            <button class="btn btn-sm btn-outline-primary">
              <i class="bi bi-person-plus"></i>
            </button>
          </div>
        </div>

        <!-- Chat Messages -->
        <div
            class="flex-grow-1 overflow-auto p-3 bg-success bg-opacity-10"
            ref="chatContainer"
            style="height: 0;"
        >
          <div v-for="(msg, index) in chat.messages" :key="index"
               class="mb-2 d-flex"
               :class="{ 'justify-content-end': isCurrentUserMessage(msg) }"
          >
            <div
                class="p-2 rounded-3 chat-message-bubble"
                :class="{
                'bg-primary text-white chat-message-sent': isCurrentUserMessage(msg),
                'bg-white chat-message-received': !isCurrentUserMessage(msg)
              }"
                style="max-width: 70%;"
            >
              <div class="chat-message-body">{{ msg.body }}</div>
              <div class="chat-message-meta" :class="isCurrentUserMessage(msg) ? 'justify-content-end' : 'justify-content-start'">
                <span class="chat-message-date">{{ formatMessageDate(msg.created_at) }}</span>
                <span v-if="isCurrentUserMessage(msg)" class="ms-1">
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
                @keyup.enter.prevent="sendMessage"
            >
            <button
                class="btn btn-primary rounded-circle"
                :disabled="isSendingMessage"
                @click="sendMessage"
            >
              <i class="bi bi-send-fill"></i>
            </button>
          </div>
        </div>
      </div>
      <!-- Empty State when no user is selected -->
      <div class="col-md-8 d-flex flex-column justify-content-center align-items-center bg-light" v-else>
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
import { ref, onMounted, onUpdated, computed } from 'vue';
import Chat from './Chat.vue';
import Pusher from 'pusher-js';
import { useStore } from 'vuex';
import AllServiceService from '@/services/all-service';

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
    const isSendingMessage = ref(false);

    const store = useStore();
    const token = computed(() => store.getters.getToken);

    // Get current date in DD/MM/YYYY format
    const currentDate = computed(() => {
      const today = new Date();
      const day = String(today.getDate()).padStart(2, '0');
      const month = String(today.getMonth() + 1).padStart(2, '0');
      const year = today.getFullYear();
      return `${day}/${month}/${year}`;
    });

    const allService = new AllServiceService();

    // Filter users based on search query
    const filteredUsers = computed(() => {
      if (!searchQuery.value.trim()) {
        return userView.value;
      }
      return userView.value.filter(user =>
          user.name.toLowerCase().includes(searchQuery.value.toLowerCase())
      );
    });

    const fetchUserData = async (query) => {
      try {
        const response = await allService.searchUser(query);
        if (response && Array.isArray(response)) {
          userView.value = response;
        } else if (response?.data && Array.isArray(response.data)) {
          userView.value = response.data;
        } else {
          userView.value = [];
        }
      } catch (error) {
        console.error('Error fetching user data:', error);
        userView.value = [];
      }
    };

    const fetchUserId = async () => {
      try {
        const response = await allService.getUser();
        if (response && response.user && response.user.id) {
          currentUserId.value = response.user.id;
        } else {
          console.warn("No user ID found in response.");
        }
      } catch (error) {
        console.error("Error fetching user:", error);
      }
    };

    const normalizeId = (value) => {
      const numeric = Number(value);
      return Number.isNaN(numeric) ? value : numeric;
    };

    const sameUserId = (a, b) => normalizeId(a) === normalizeId(b);

    const normalizeMessagePayload = (payload = {}, fallback = {}) => ({
      id: payload?.id ?? payload?.message_id ?? fallback?.id ?? null,
      from_id: payload?.from_id ?? payload?.sender_id ?? payload?.user_id ?? fallback?.from_id ?? null,
      to_id: payload?.to_id ?? payload?.receiver_id ?? fallback?.to_id ?? null,
      body: payload?.body ?? payload?.message ?? payload?.text ?? fallback?.body ?? '',
      created_at: payload?.created_at ?? payload?.date ?? fallback?.created_at ?? new Date().toISOString(),
    });

    const normalizeTimestampToSecond = (value) => {
      if (!value) {
        return '';
      }

      const parsed = new Date(value);
      if (Number.isNaN(parsed.getTime())) {
        return '';
      }

      parsed.setMilliseconds(0);
      return parsed.toISOString();
    };

    const buildMessageSignature = (message) => {
      const normalized = normalizeMessagePayload(message);
      return [
        String(normalizeId(normalized.from_id) ?? ''),
        String(normalizeId(normalized.to_id) ?? ''),
        String((normalized.body || '').trim()),
        normalizeTimestampToSecond(normalized.created_at),
      ].join('|');
    };

    const dedupeMessages = (messages) => {
      const unique = [];
      const seenIds = new Set();
      const seenSignatures = new Set();

      messages.forEach((item) => {
        const normalized = normalizeMessagePayload(item);
        if (!normalized.body) {
          return;
        }

        if (normalized.id !== null && normalized.id !== undefined) {
          const idKey = String(normalized.id);
          if (seenIds.has(idKey)) {
            return;
          }
          seenIds.add(idKey);
        }

        const signature = buildMessageSignature(normalized);
        if (seenSignatures.has(signature)) {
          return;
        }

        seenSignatures.add(signature);
        unique.push(normalized);
      });

      return unique;
    };

    const appendUniqueMessage = (incoming) => {
      const normalizedIncoming = normalizeMessagePayload(incoming);

      if (!normalizedIncoming?.body) {
        return;
      }

      const exists = chat.value.messages.some((item) => {
        const normalizedItem = normalizeMessagePayload(item);

        if (normalizedItem?.id && normalizedIncoming?.id) {
          return String(normalizedItem.id) === String(normalizedIncoming.id);
        }

        return buildMessageSignature(normalizedItem) === buildMessageSignature(normalizedIncoming);
      });

      if (!exists) {
        chat.value.messages.push(normalizedIncoming);
      }
    };

    const sendMessage = async () => {
      const text = (message.value || '').trim();
      if (!text || !selectedUser.value || isSendingMessage.value) {
        console.error("No user selected or message empty!");
        return;
      }

      isSendingMessage.value = true;

      const optimisticMessage = normalizeMessagePayload({
        id: `tmp-${Date.now()}-${Math.random().toString(36).slice(2)}`,
        from_id: currentUserId.value,
        to_id: selectedUser.value.id,
        body: text,
        created_at: new Date().toISOString(),
      });

      appendUniqueMessage(optimisticMessage);

      try {
        const response = await allService.sendMessages({
          user_id: selectedUser.value.id,
          message: text
        });

        if (response.error) {
          alert(response.error);
        }

        const serverMessage = normalizeMessagePayload(
          response?.message ?? response?.data ?? response,
          {
            from_id: currentUserId.value,
            to_id: selectedUser.value.id,
            body: text,
            created_at: new Date().toISOString(),
          }
        );

        appendUniqueMessage(serverMessage);
        message.value = '';
      } catch (error) {
        console.error('Error sending message:', error);
        chat.value.messages = chat.value.messages.filter((item) => item.id !== optimisticMessage.id);
      } finally {
        isSendingMessage.value = false;
      }
    };

    const selectUser = (user) => {
      selectedUser.value = user;
      getMessage(user.id);
    };

    const searchUser = async () => {
      await fetchUserData(searchQuery.value);
    };

    const getMessage = async (userId) => {
      try {
        const response = await allService.getMessage(userId);
        if (response && response.messages && response.messages.length > 0) {
          chat.value.messages = dedupeMessages(response.messages);
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
      if (!createdAt) {
        return '';
      }

      const parsed = new Date(createdAt);
      if (Number.isNaN(parsed.getTime())) {
        return '';
      }

      return parsed.toLocaleDateString([], {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
      });
    };

    const isCurrentUserMessage = (msg) => sameUserId(msg?.from_id, currentUserId.value);

    const initializePusher = () => {
      const apiOrigin = (process.env.VUE_APP_API_BASE_URL || `${window.location.protocol}//${window.location.hostname}:8001`).replace(/\/$/, '')
      Pusher.logToConsole = true;
      const pusherInstance = new Pusher('d48f36eb19647382a1d0', {
        cluster: 'ap2',
        channelAuthorization: {
          endpoint: `${apiOrigin}/api/broadcasting/auth`,
          auth: {
            headers: { Authorization: 'Bearer ' + token.value },
          },
        },
      });

      const channel = pusherInstance.subscribe('chat');
      channel.bind('my-event', (data) => {
        if (!selectedUser.value || !currentUserId.value) {
          return;
        }

        const incoming = normalizeMessagePayload(data);

        const isCurrentConversation =
          (sameUserId(incoming.from_id, selectedUser.value.id) && sameUserId(incoming.to_id, currentUserId.value)) ||
          (sameUserId(incoming.from_id, currentUserId.value) && sameUserId(incoming.to_id, selectedUser.value.id));

        if (!isCurrentConversation) {
          return;
        }

        // The sender already appends the message locally; skip self-echo duplicates.
        if (sameUserId(incoming.from_id, currentUserId.value)) {
          return;
        }

        appendUniqueMessage(incoming);
      });
    };

    onMounted(() => {
      fetchUserId();
      fetchUserData('');
      initializePusher();
    });

    onUpdated(() => {
      scrollToBottom();
    });

    return {
      chatContainer,
      message,
      userView,
      chat,
      searchQuery,
      selectedUser,
      currentUserId,
      currentDate,
      filteredUsers,
      isSendingMessage,
      sendMessage,
      selectUser,
      searchUser,
      getMessage,
      formatMessageDate,
      isCurrentUserMessage
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

/* Message bubbles */
.message-bubble {
  max-width: 70%;
  padding: 8px 12px;
  border-radius: 18px;
  margin-bottom: 8px;
  position: relative;
}

.message-bubble.sent {
  background-color: #dcf8c6;
  align-self: flex-end;
}

.message-bubble.received {
  background-color: #ffffff;
  align-self: flex-start;
  border: 1px solid #e5e5ea;
}
/* Add these styles to your style section */
.message-content {
  word-wrap: break-word;
  white-space: pre-wrap;
}

.chat-message-bubble {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.chat-message-body {
  white-space: pre-wrap;
}

.chat-message-meta {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.7rem;
  margin-top: 2px;
}

.chat-message-date {
  color: inherit;
  opacity: 0.7;
}

/* Add these to your style section */
.h-100 {
  height: 100%;
}


.flex-grow-1 {
  flex-grow: 1;
  min-height: 0; /* This is ESSENTIAL for proper flexbox scrolling */
}

/* Ensure the container uses full height */
.container-fluid.vh-100 {
  height: 100vh;
  overflow: hidden;
}

/* Fix for the row height */
.row.h-100 {
  height: 750px !important;
  margin: 0;
}
</style>
