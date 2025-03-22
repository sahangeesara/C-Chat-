<template>
  <div>
    <div class="row">
      <!-- Sidebar -->
      <div class="col-4 pe-0">
        <nav class="nav flex-column bg-light" style="height: 761px !important;">
          <h1>Chat</h1>
          <form>
            <div class="input-group mt-3 mx-2" style="width: 380px;">
              <input
                type="text"
                class="form-control"
                v-model="searchQuery"
                placeholder="Search"
                @input="searchUser"
              />
              <span class="input-group-text">
                <i class="bi bi-search"></i>
              </span>
            </div>
          </form>

          <!-- User List -->
          <div v-for="user in userView" :key="user.id">
            <button class="btn btn-outline-primary mt-3 but" @click="getUser(user.id)">
              {{ user.name }}
            </button>
          </div>

          <!-- Logout -->
          <div class="mt-auto">
            <router-link to="/" class="nav-link">
              <i class="bi bi-box-arrow-in-right"></i> Logout
            </router-link>
          </div>
        </nav>
      </div>

      <!-- Chat Window -->
      <div class="col-8 p-lg-0">
        <h3 class="list-group-item active bg-light text-primary border-0">Ch-Chat</h3>

        <ul class="list-group chat-container" ref="chatContainer"    style="background-color: #5dee5d">
          <chat
              v-for="(msg, index) in chat.messages"
              :key="index"
              :senderId="msg.from_id"
              :currentUserId="currentUserId"
              :body="msg.body"
          />
        </ul>

        <!-- Message Input -->
        <div class="input-group mb-3">
          <input
            type="text"
            class="form-control"
            placeholder="Type your Message..."
            v-model="message"
            @keyup.enter="sendMessage"
          />
          <button class="btn btn-outline-secondary" @click="sendMessage">
            <i class="bi bi-send-fill"></i>
          </button>
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
import send from "send";  // Import the AllServiceService

export default {
  computed: {
    send() {
      return send
    }
  },
  components: { Chat },
  setup() {
    const chatContainer = ref(null);
    const message = ref('');
    const userView = ref([]);
    const chat = ref({ messages: [] });
    const searchQuery = ref('');
    const selectedUserId = ref(null);
    let currentUserId = ref(null);

    const store = useStore();
    const token = computed(() => store.getters.getToken);

    // Initialize the chat service
    const allService = new AllServiceService();

    // Fetch user data using the service
    const fetchUserData = async (query) => {
      try {
        const response = await allService.searchUser(query);

         // Ensure the correct path to data
        if (response && Array.isArray(response)) {
          userView.value = response; // Directly assign the array
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

// Fetch authenticated user ID
    const fetchUserId = async () => {
      try {
        const response = await allService.getUser();

        if (response && response.user && response.user.id) {
          currentUserId.value = response.user.id; // ✅ Correct way to update ref

        } else {
          console.warn("No user ID found in response.");
        }
      } catch (error) {
        console.error("Error fetching user:", error);
      }
    };

    // Send message
    const sendMessage = async () => {
      if (!message.value && !selectedUserId.value) {
        console.error("No user selected!");
        return;
      }

      try {
        const response = await allService.sendMessages({
          user_id: selectedUserId.value,
          message: message.value.trim()
        });

        if(response.error){
          alert(response.error);
        }

        if (response.status === 'Message sent!') {
          chat.value.messages.push(`${message.value}`);
          message.value = '';
        }
      } catch (error) {
        console.error('Error sending message:', error);
      }
    };


    // Search user when typing
    const searchUser = async () => {
      if (!searchQuery.value.trim()) {
        userView.value = [];
        return;
      }
      await fetchUserData(searchQuery.value);
    };

    const getUser = (user_id) => {
      selectedUserId.value = user_id;
      getMessage(selectedUserId.value);
    };
    const getMessage = async (selectedUserId) => {
      try {
        const response = await allService.getMessage(selectedUserId);

        if (response && response.messages && response.messages.length > 0) {
          chat.value.messages = []; // Clear previous messages
          for (let i = 0; i < response.messages.length; i++) {
            chat.value.messages.push(response.messages[i]);
          }
        } else {
          console.warn("No messages found.");
        }
      } catch (error) {
        console.error("Error fetching messages:", error);
      }
    };


    // Scroll to bottom
    onMounted(() => {
      if (chatContainer.value) {
        console.log('Chat container mounted');
        scrollToBottom();
        fetchUserId();
      }
    });

    onUpdated(() => scrollToBottom());

    function scrollToBottom() {
      if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
      }
    }

    // Initialize Pusher
    const initializePusher = () => {
      Pusher.logToConsole = true;

      // Initialize Pusher with the token from Vuex store
      const pusherInstance = new Pusher('d48f36eb19647382a1d0', {
        cluster: 'ap2',
        channelAuthorization: {
          endpoint: 'http://127.0.0.1:8000/api/broadcasting/auth',
          auth: {
            headers: { Authorization: 'Bearer ' + token.value },
          },
        },
      });

      const channel = pusherInstance.subscribe('chat');
      channel.bind('my-event', (data) => {
        chat.value.messages.push(`${data.user}: ${data.message}`);
      });
    };

    return {
      chatContainer,
      message,
      userView,
      chat,
      searchQuery,
      selectedUserId,
      sendMessage,
      getUser,
      searchUser,
      getMessage,
      token,
      initializePusher,
      currentUserId,
    };
  },

  mounted() {
    this.initializePusher();
  },
};
</script>

<style>
h1 {
  color: rgba(42, 13, 238, 0.5);
  margin-top: 30px!important;
  margin-left: -40px !important;
}

.list-group {
  overflow-y: scroll;
  height: 670px;
}

a {
  text-decoration: none;
  color: black;
}
.but{
  width: 300px !important;
  border-radius: 20px;
}


</style>
