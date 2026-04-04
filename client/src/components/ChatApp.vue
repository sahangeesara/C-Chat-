<template>
  <div class="container-fluid vh-100 g-0 chat-shell" :style="chatThemeStyle">
    <div class="row g-0 h-100 chat-layout flex-nowrap">
      <!-- Left Sidebar - Fixed width like WhatsApp -->
      <div class="chat-sidebar border-end" v-show="showSidebarPane">
        <!-- Profile Header -->
        <div class="p-3 d-flex justify-content-between align-items-center border-bottom border-dark position-relative" style="background-color: #202c33; height: 60px;">
          <div @click="openCurrentUserDetails" style="cursor: pointer;" class="d-flex align-items-center gap-2">
            <img
              v-if="currentUserPhoto"
              :src="currentUserPhoto"
              class="rounded-circle"
              style="width: 40px; height: 40px; object-fit: cover;"
              alt="Profile"
            >
            <span v-else class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px;">
              {{ (displayCurrentUserName || '?').charAt(0).toUpperCase() }}
            </span>
            <small class="text-muted d-none d-md-inline">{{ displayCurrentUserName }}</small>
          </div>

          <div class="d-flex gap-3 text-white align-items-center">
            <i class="bi bi-chat-left-text-fill"></i>
            <i class="bi bi-three-dots-vertical" style="cursor: pointer;" @click="toggleMenu"></i>
          </div>

          <div v-if="showHeaderMenu" class="header-menu">
            <button type="button" class="dropdown-item" @click="openSettings">Settings</button>
            <router-link to="/" class="dropdown-item">Logout</router-link>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="p-2" style="background-color: #111b21;">
          <div class="input-group">
            <span class="input-group-text border-0" style="background-color: #202c33; color: #8696a0;">
              <i class="bi bi-search"></i>
            </span>
            <input
                type="text"
                class="form-control border-0 text-white shadow-none"
                v-model="searchQuery"
                style="background-color: #202c33;"
                placeholder="Search or start a new chat"
                @input="searchUser"
            >
            <button
                type="button"
                class="input-group-text border-0"
                style="background-color: #202c33; color: #8696a0;"
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
                <img
                  v-if="getUserPhoto(user)"
                  :src="getUserPhoto(user)"
                  class="rounded-circle"
                  style="width: 40px; height: 40px; object-fit: cover;"
                  alt="User"
                >
                <span v-else class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
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
        <div class="chat-footer p-3 border-top border-dark" style="background-color: #111b21;">
          <div class="d-flex justify-content-between small mt-2" style="color: #8696a0;">
            <span>{{ currentWeather }}</span>
            <span>{{ currentLocation }}</span>
            <span>{{ currentDate }}</span>
          </div>
        </div>
      </div>
      <!-- Right Chat Area - Only shown when user is selected -->
      <div class="chat-main" v-if="selectedUser && showChatPane">
        <!-- Chat Header -->
        <div class="p-3 border-bottom border-dark d-flex justify-content-between align-items-center" style="background-color: #202c33; color: #e9edef;">
          <div class="d-flex align-items-center">
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary me-2 d-md-none"
                @click="goBackToUsers"
            >
              <i class="bi bi-arrow-left"></i>
            </button>
            <div class="me-3" style="cursor: pointer;" @click="selectUserprofile(selectedUser)">
              <img
                v-if="getUserPhoto(selectedUser)"
                :src="getUserPhoto(selectedUser)"
                class="rounded-circle"
                style="width: 40px; height: 40px; object-fit: cover;"
                alt="Selected user"
              >
              <div v-else class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <span class="text-white" >{{ selectedUser?.name?.charAt(0) || '?' }}</span>
              </div>
            </div>
            <div style="cursor: pointer;" @click="selectUserprofile(selectedUser)">
              <h6 class="mb-0">Chat with {{ selectedUser.name }}</h6>
              <small style="color: #8696a0;">Online</small>
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
            class="flex-grow-1 overflow-auto p-3"
            ref="chatContainer"
            style="height: 0; background-color: transparent;"
        >
          <div v-for="(msg, index) in chat.messages" :key="index"
               class="mb-2 d-flex"
               :class="{ 'justify-content-end': isCurrentUserMessage(msg) }"
          >
            <!-- color message-->
            <div
                class="p-2 px-3 chat-message-bubble shadow-sm"
                :style="{
                  'background-color': isCurrentUserMessage(msg) ? settings.accentColor : '#202c33',
                  'color': '#e9edef',
                  'border-radius': isCurrentUserMessage(msg) ? '8px 0px 8px 8px' : '0px 8px 8px 8px',
                  'max-width': '65%',
                  'position': 'relative'
                }"
                style="font-size: 0.9rem;"
            >
              <div style="word-break: break-word;">{{ msg.body }}</div>
              <div class="text-end" style="font-size: 0.65rem; color: #8696a0; margin-top: 4px;">
                {{ formatMessageDate(msg.created_at) }}
                <span v-if="isCurrentUserMessage(msg)" class="ms-1">
                  <i class="bi bi-check2-all text-info"></i>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Message Input -->
        <div class="p-3 border-top border-dark" style="background-color: #202c33;">
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
                :style="{ backgroundColor: settings.accentColor, borderColor: settings.accentColor }"
                @click="sendMessage"
            >
              <i class="bi bi-send-fill"></i>
            </button>
          </div>
        </div>
      </div>
      <!-- Empty State when no user is selected -->
      <div class="chat-main justify-content-center align-items-center" style="background-color: #0b141a; color: #e9edef;" v-else-if="showChatPane">
        <div class="text-center p-5">
          <i class="bi bi-people-fill" style="font-size: 3rem; color: #ccc;"></i>
          <h3 class="mt-3 text-muted">Select a user to start chatting</h3>
          <p class="text-muted">Search and select a friend from the list to view your conversation</p>
        </div>
      </div>

      <div v-if="showSettings" class="settings-overlay" @click.self="closeSettings">
        <div class="settings-card">
          <h5 class="mb-3">Profile Settings</h5>

          <div class="mb-3 text-center">
            <img v-if="profilePreviewUrl || currentUserPhoto" :src="profilePreviewUrl || currentUserPhoto" alt="Profile" class="settings-avatar" />
            <div v-else class="settings-avatar-placeholder">{{ (displayCurrentUserName || '?').charAt(0).toUpperCase() }}</div>
          </div>

          <div class="mb-2">
            <label class="form-label mb-1" for="settings-display-name">Display Name</label>
            <input id="settings-display-name" type="text" class="form-control" v-model="profileForm.name" placeholder="Your name" />
          </div>

          <div class="mb-2">
            <label class="form-label mb-1" for="settings-profile-file">Or Upload Picture</label>
            <input id="settings-profile-file" type="file" class="form-control" accept="image/*" @change="handleImageUpload" />
          </div>

          <div class="mb-3">
            <label class="form-label mb-1" for="settings-accent-color">Accent Color</label>
            <input id="settings-accent-color" type="color" class="form-control form-control-color" v-model="profileForm.accentColor" />
          </div>

          <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-outline-secondary" @click="closeSettings">Cancel</button>
            <button type="button" class="btn btn-outline-danger" @click="resetSettings">Reset</button>
            <button type="button" class="btn btn-primary" @click="updateProfile">Save</button>
          </div>
        </div>
      </div>

      <div v-if="showUserDetails" class="settings-overlay" @click.self="closeUserDetails">
        <div class="settings-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">User Details</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeUserDetails">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div v-if="isUserDetailsLoading" class="text-center text-muted py-4">Loading details...</div>
          <div v-else-if="userDetailsError" class="alert alert-danger py-2 mb-0">{{ userDetailsError }}</div>

          <template v-else-if="userDetails">
            <div class="mb-3 text-center">
              <img v-if="getUserPhoto(userDetails)" :src="getUserPhoto(userDetails)" alt="User profile" class="settings-avatar" />
              <div v-else class="settings-avatar-placeholder">{{ (userDetails?.name || '?').charAt(0).toUpperCase() }}</div>
            </div>

            <div class="user-details-grid">
              <div>
                <small class="text-muted d-block">Name</small>
                <div>{{ userDetails?.name || 'Unknown User' }}</div>
              </div>
              <div v-if="userDetails?.email">
                <small class="text-muted d-block">Email</small>
                <div class="text-break">{{ userDetails.email }}</div>
              </div>
              <div v-if="userDetails?.username">
                <small class="text-muted d-block">Username</small>
                <div>{{ userDetails.username }}</div>
              </div>
              <div v-if="userDetails?.created_at">
                <small class="text-muted d-block">Joined</small>
                <div>{{ formatProfileDate(userDetails.created_at) }}</div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import Chat from './Chat.vue';
import AllServiceService from '@/services/all-service';
import echo from '@/services/echo'
import CallService from "@/services/CallService";

export default {
  components: { Chat },
  setup() {
    const router = useRouter();
    const chatContainer = ref(null);
    const message = ref('');
    const userView = ref([]);
    const chat = ref({ messages: [] });
    const searchQuery = ref('');
    const selectedUser = ref(null);
    let currentUserId = ref(null);
    let currentUserName = ref(null);
    const latestSearchRequestId = ref(0)
    const latestMessageRequestId = ref(0)
    const searchDebounceTimer = ref(null)
    const isMobileView = ref(window.innerWidth < 768)
    const activeMobilePane = ref('list')
    const showSettings = ref(false)
    const showHeaderMenu = ref(false)
    const showUserDetails = ref(false)
    const isUserDetailsLoading = ref(false)
    const userDetails = ref(null)
    const userDetailsError = ref('')
    const currentUserPhoto = ref('')
    const defaultSettings = {
      accentColor: '#005c4b'
    }
    const settings = ref({ ...defaultSettings })
    const profileForm = ref({
      name: '',
      image: null,
      accentColor: defaultSettings.accentColor
    })
    const profilePreviewUrl = ref('')
    const currentLocation = ref('Locating...')
    const currentWeather = ref('Weather...')
    const pc = ref(null)
    const partnerId = ref(null)
    const remoteAudio = ref(null)
    const activeChannelNames = ref([])
    const knownChatUserIds = ref([])
    const latestProfileRequestId = ref(0)
    const updateViewportMode = () => {
      isMobileView.value = window.innerWidth < 768;
      if (!isMobileView.value) {
        activeMobilePane.value = 'list';
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

    const weatherCodeText = (code) => {
      const map = {
        0: 'Clear',
        1: 'Mainly clear',
        2: 'Partly cloudy',
        3: 'Cloudy',
        45: 'Foggy',
        48: 'Foggy',
        51: 'Drizzle',
        53: 'Drizzle',
        55: 'Heavy drizzle',
        61: 'Rain',
        63: 'Rain',
        65: 'Heavy rain',
        71: 'Snow',
        73: 'Snow',
        75: 'Heavy snow',
        80: 'Rain showers',
        81: 'Rain showers',
        82: 'Heavy showers',
        95: 'Thunderstorm',
      }
      return map[code] || 'Weather'
    }

    const fetchWeatherAndLocation = async (latitude, longitude) => {
      try {
        const weatherUrl = `https://api.open-meteo.com/v1/forecast?latitude=${latitude}&longitude=${longitude}&current=temperature_2m,weather_code&timezone=auto`
        const locationUrl = `https://geocoding-api.open-meteo.com/v1/reverse?latitude=${latitude}&longitude=${longitude}&language=en&format=json`

        const [weatherRes, locationRes] = await Promise.all([
          fetch(weatherUrl),
          fetch(locationUrl)
        ])

        const weatherData = await weatherRes.json()
        const locationData = await locationRes.json()

        const temp = weatherData?.current?.temperature_2m
        const code = weatherData?.current?.weather_code
        const weatherLabel = weatherCodeText(code)

        if (typeof temp === 'number') {
          currentWeather.value = `${Math.round(temp)}°C ${weatherLabel}`
        } else {
          currentWeather.value = weatherLabel
        }

        const place = locationData?.results?.[0]
        if (place) {
          const parts = [place.city, place.country].filter(Boolean)
          currentLocation.value = parts.join(', ') || 'Unknown location'
        } else {
          currentLocation.value = 'Unknown location'
        }
      } catch (error) {
        console.warn('Unable to load weather/location:', error)
        currentWeather.value = 'Weather unavailable'
        currentLocation.value = 'Location unavailable'
      }
    }

    const loadCurrentWeather = () => {
      if (!navigator?.geolocation) {
        currentWeather.value = 'Weather unavailable'
        currentLocation.value = 'Location unavailable'
        return
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          const { latitude, longitude } = position.coords
          fetchWeatherAndLocation(latitude, longitude)
        },
        () => {
          currentWeather.value = 'Weather unavailable'
          currentLocation.value = 'Location denied'
        },
        { timeout: 10000 }
      )
    }

    const settingsStorageKey = computed(() => `chatapp.settings.${currentUserId.value || 'guest'}`)
    const chatUsersStorageKey = computed(() => `chatapp.chatUsers.${currentUserId.value || 'guest'}`)

    const displayCurrentUserName = computed(() => currentUserName.value || 'User')

    const chatThemeStyle = computed(() => ({
      '--chat-accent': settings.value.accentColor || defaultSettings.accentColor
    }))

    const loadSettings = () => {
      try {
        const raw = localStorage.getItem(settingsStorageKey.value)
        if (!raw) {
          settings.value = { ...defaultSettings }
          return
        }

        const parsed = JSON.parse(raw)
        settings.value = {
          accentColor: (parsed?.accentColor || defaultSettings.accentColor).toString()
        }
      } catch (error) {
        console.warn('Failed to load local chat settings:', error)
        settings.value = { ...defaultSettings }
      }
    }

    const loadKnownChatUsers = () => {
      try {
        const raw = localStorage.getItem(chatUsersStorageKey.value)
        if (!raw) {
          knownChatUserIds.value = []
          return
        }

        const parsed = JSON.parse(raw)
        knownChatUserIds.value = Array.isArray(parsed) ? parsed : []
      } catch {
        knownChatUserIds.value = []
      }
    }

    const persistKnownChatUsers = () => {
      localStorage.setItem(chatUsersStorageKey.value, JSON.stringify(knownChatUserIds.value))
    }

    const rememberChattedUser = (userId) => {
      if (!userId) {
        return
      }

      const normalized = Number.isNaN(Number(userId)) ? userId : Number(userId)
      const alreadyExists = knownChatUserIds.value.includes(normalized)
      if (alreadyExists) {
        return
      }

      knownChatUserIds.value = [...knownChatUserIds.value, normalized]
      persistKnownChatUsers()
    }

    const userHasChatHint = (user) =>
      Boolean(
        user?.last_message ||
        user?.lastMessage ||
        user?.latest_message ||
        user?.latestMessage ||
        user?.message_count > 0 ||
        user?.messages_count > 0 ||
        user?.conversation_id ||
        user?.chat_id
      )

    const chatContacts = computed(() => {
      const knownIds = new Set(knownChatUserIds.value)

      return userView.value.filter((user) =>
        userHasChatHint(user) ||
        knownIds.has(user?.id) ||
        (selectedUser.value && user?.id === selectedUser.value.id)
      )
    })

    const openSettings = () => {
      profileForm.value = {
        name: currentUserName.value || '',
        image: null,
        accentColor: settings.value.accentColor || defaultSettings.accentColor
      }
      profilePreviewUrl.value = ''
      showHeaderMenu.value = false
      showSettings.value = true
    }

    const closeSettings = () => {
      showSettings.value = false
      profilePreviewUrl.value = ''
    }

    const toggleMenu = () => {
      showHeaderMenu.value = !showHeaderMenu.value
    }

    const resetSettings = () => {
      settings.value = { ...defaultSettings }
      profileForm.value = {
        name: currentUserName.value || '',
        image: null,
        accentColor: defaultSettings.accentColor
      }
      profilePreviewUrl.value = ''
      localStorage.removeItem(settingsStorageKey.value)
    }

    const handleImageUpload = (event) => {
      const file = event?.target?.files?.[0]
      if (!file) {
        return
      }

      const isImage = file.type?.startsWith('image/')
      const maxBytes = 2 * 1024 * 1024

      if (!isImage) {
        console.warn('Please select a valid image file.')
        event.target.value = ''
        return
      }

      if (file.size > maxBytes) {
        console.warn('Image is too large. Max size is 2MB.')
        event.target.value = ''
        return
      }

      profileForm.value.image = file
      profilePreviewUrl.value = URL.createObjectURL(file)
    }

    const getUserPhoto = (user) => {
      if (!user) {
        return ''
      }

      if (user.profile_photo_url) {
        return user.profile_photo_url
      }

      if (!user.profile_photo_path) {
        return ''
      }

      const baseUrl = (process.env.VUE_APP_API_BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '')
      const normalizedPath = user.profile_photo_path.replace(/^\/+/, '')

      if (normalizedPath.startsWith('http://') || normalizedPath.startsWith('https://')) {
        return normalizedPath
      }

      return normalizedPath.startsWith('storage/')
        ? `${baseUrl}/${normalizedPath}`
        : `${baseUrl}/storage/${normalizedPath}`
    }

    const updateProfile = async () => {
      const formData = new FormData()
      formData.append('name', (profileForm.value.name || '').trim() || currentUserName.value || '')

      if (profileForm.value.image) {
        formData.append('image', profileForm.value.image)
      }

      try {
        const response = await allService.updateProfile(formData)
        const updatedUser = response?.user

        if (updatedUser?.name) {
          currentUserName.value = updatedUser.name
        }

        currentUserPhoto.value = getUserPhoto(updatedUser)

        settings.value = {
          accentColor: profileForm.value.accentColor || defaultSettings.accentColor
        }
        localStorage.setItem(settingsStorageKey.value, JSON.stringify(settings.value))

        closeSettings()
      } catch (error) {
        const details = error?.response?.data || error?.message || error
        console.error('Profile update failed:', details)
      }
    }

    const showSidebarPane = computed(() => !isMobileView.value || activeMobilePane.value === 'list');
    const showChatPane = computed(() => !isMobileView.value || activeMobilePane.value === 'chat');

    // Without a query, show chat contacts only. With a query, search across fetched users.
    const filteredUsers = computed(() => {
      const term = searchQuery.value.trim().toLowerCase();
      const source = term ? userView.value : chatContacts.value;

      return term
        ? source.filter(user =>
            (user?.name || '').toLowerCase().includes(term)
          )
        : source;
    });

    const visibleUsers = computed(() => {
      const term = searchQuery.value.trim();
      if (term) {
        return filteredUsers.value;
      }

      // Put selected user at top, followed by other users
      if (selectedUser.value) {
        const otherUsers = filteredUsers.value.filter(user => user.id !== selectedUser.value.id);
        return [selectedUser.value, ...otherUsers.slice(0, 9)];
      }

      return filteredUsers.value.slice(0, 10);
    });

    const extractUserPage = (response) => {
      if (Array.isArray(response)) {
        return {
          items: response,
          nextPage: null,
          currentPage: 1,
          lastPage: 1
        };
      }

      if (Array.isArray(response?.users)) {
        return {
          items: response.users,
          nextPage: response.next_page_url ? (response.current_page || 1) + 1 : null,
          currentPage: response.current_page || 1,
          lastPage: response.last_page || 1
        };
      }

      if (Array.isArray(response?.data?.data)) {
        return {
          items: response.data.data,
          nextPage: response.data.next_page_url ? (response.data.current_page || 1) + 1 : null,
          currentPage: response.data.current_page || 1,
          lastPage: response.data.last_page || 1
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
      const searchMode = Boolean((query || '').toString().trim())
      const targetLimit = searchMode ? 50 : 10

      try {
        const users = [];
        let page = 1;
        let shouldContinue = true;

        while (shouldContinue && page <= 20 && users.length < targetLimit) {
          const response = await allService.searchUser(query, page);
          const parsed = extractUserPage(response);

          users.push(...parsed.items);

          const hasNextByUrl = Boolean(response?.next_page_url);
          const hasNextByCount = parsed.currentPage < parsed.lastPage;
          shouldContinue = hasNextByUrl || hasNextByCount;
          page += 1;
        }

        if (requestId === latestSearchRequestId.value) {
          userView.value = Array.from(new Map(users.map((user) => [user.id, user])).values()).slice(0, targetLimit);
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
          currentUserPhoto.value = getUserPhoto(response.user);
          loadSettings();
          loadKnownChatUsers();
        } else {
          console.warn("No user ID found in response.");
        }
      } catch (error) {
        console.error("Error fetching user:", error);
        if (error?.response?.status === 401) {
          localStorage.removeItem('token');
          router.push('/');
        }
      }
    };

    const userId = async (id) => {
      try {
        const response = await allService.getUserProfile(id);
        const profile = response?.user || response?.data || response;
        if (profile && profile.id) {
          return profile;
        }

        console.warn("No user ID found in response.");
        return null;
      } catch (error) {
        console.error("Error fetching user:", error);
        return null;
      }
    };

    const closeUserDetails = () => {
      showUserDetails.value = false
      userDetailsError.value = ''
    }

    const formatProfileDate = (value) => {
      if (!value) {
        return ''
      }

      const parsed = new Date(value)
      if (Number.isNaN(parsed.getTime())) {
        return ''
      }

      return parsed.toLocaleDateString([], { month: 'short', day: '2-digit', year: 'numeric' })
    }

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

        rememberChattedUser(selectedUser.value.id)

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
      chat.value.messages = [];
      searchQuery.value = '';
      getMessage(user.id);

      // Switch to chat pane on mobile after selecting a user.
      if (isMobileView.value) {
        activeMobilePane.value = 'chat';
      }
    };

    const goBackToUsers = () => {
      activeMobilePane.value = 'list';
    };

    const selectUserprofile = async (user) => {
      if (!user?.id) {
        return
      }

      const requestId = ++latestProfileRequestId.value
      showUserDetails.value = true
      isUserDetailsLoading.value = true
      userDetailsError.value = ''
      userDetails.value = user

      const profile = await userId(user.id)

      if (requestId !== latestProfileRequestId.value) {
        return
      }

      if (!profile) {
        userDetailsError.value = 'Unable to load user details.'
        isUserDetailsLoading.value = false
        return
      }

      userDetails.value = profile
      isUserDetailsLoading.value = false
    };

    const openCurrentUserDetails = async () => {
      showHeaderMenu.value = false
      showUserDetails.value = true
      userDetailsError.value = ''

      // Always show at least locally known identity so modal opens immediately.
      userDetails.value = {
        id: currentUserId.value,
        name: currentUserName.value || 'User',
        profile_photo_url: currentUserPhoto.value || '',
      }

      if (!currentUserId.value) {
        isUserDetailsLoading.value = false
        return
      }

      isUserDetailsLoading.value = true
      const requestId = ++latestProfileRequestId.value
      const profile = await userId(currentUserId.value)

      if (requestId !== latestProfileRequestId.value) {
        return
      }

      if (!profile) {
        userDetailsError.value = 'Unable to load user details.'
        isUserDetailsLoading.value = false
        return
      }

      userDetails.value = profile
      isUserDetailsLoading.value = false
    }


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
      const requestId = ++latestMessageRequestId.value;

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

        // Ignore stale responses when user switches chats quickly.
        const isStillSelected = selectedUser.value && sameUserId(selectedUser.value.id, userId);
        if (!isStillSelected || requestId !== latestMessageRequestId.value) {
          return;
        }

        if (messages.length > 0) {
          const normalizedMessages = messages.map((item) => normalizeMessagePayload(item));
          chat.value.messages = normalizedMessages.filter((item) =>
            isMessageBetweenUsers(item, currentUserId.value, userId)
          );

          if (chat.value.messages.length > 0) {
            rememberChattedUser(userId)
          }
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

    const isMessageBetweenUsers = (message, firstUserId, secondUserId) => {
      if (!message) {
        return false;
      }

      const firstToSecond =
        sameUserId(message.from_id, firstUserId) && sameUserId(message.to_id, secondUserId);
      const secondToFirst =
        sameUserId(message.from_id, secondUserId) && sameUserId(message.to_id, firstUserId);

      return firstToSecond || secondToFirst;
    };

    const isCurrentUserMessage = (msg) => sameUserId(msg?.from_id, currentUserId.value);

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
          isMessageBetweenUsers(incoming, currentUserId.value, selectedUser.value.id);

        if (isCurrentConversation) {
          const otherUserId = sameUserId(incoming.from_id, currentUserId.value) ? incoming.to_id : incoming.from_id
          rememberChattedUser(otherUserId)
          appendMessage(incoming);
        }
      };

      echo.private(channelName)
          .listen('.chat.message', handleRealtimeEvent)
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
      loadCurrentWeather()

      const token = localStorage.getItem('token');
      if (!token) {
        router.push('/');
        return;
      }

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

      if (profilePreviewUrl.value) {
        URL.revokeObjectURL(profilePreviewUrl.value)
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
      currentUserPhoto,
      getUserPhoto,
      displayCurrentUserName,
      chatThemeStyle,
      settings,
      profileForm,
      profilePreviewUrl,
      currentLocation,
      currentWeather,
      showSettings,
      showHeaderMenu,
      showUserDetails,
      isUserDetailsLoading,
      userDetails,
      userDetailsError,
      currentDate,
      filteredUsers,
      visibleUsers,
      showSidebarPane,
      showChatPane,
      isCurrentUserMessage,
      sendMessage,
      selectUser,
      goBackToUsers,
      clearSearch,
      openSettings,
      toggleMenu,
      closeSettings,
      updateProfile,
      resetSettings,
      handleImageUpload,
      searchUser,
      getMessage,
      selectUserprofile,
      openCurrentUserDetails,
      closeUserDetails,
      userId,
      startCall,
      formatMessageDate,
      formatProfileDate
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

html, body {
  height: 100%;
  margin: 0;
  padding: 0;
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
  position: fixed;
  inset: 0;
  min-height: 100vh;
  height: 100dvh;
  width: 100vw;
  background-color: #0b141a;
  padding: 0;
  margin: 0;
  overflow: hidden;
}

/* Keep the sidebar and chat pane on one row on desktop */
.chat-layout {
  width: 100%;
  height: 100%;
  min-height: 100%;
  display: flex;
  flex-direction: row;
  background-color: #222e35;
  flex-wrap: nowrap;
  overflow: hidden;
}

.chat-sidebar {
  display: flex;
  flex-direction: column;
  flex: 0 0 30%;
  min-width: 350px;
  max-width: 450px;
  background-color: #111b21;
  border-right: 1px solid #2f3b43;
}

.chat-main {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  background-color: #0b141a;
  background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
  background-blend-mode: overlay;
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


.user-details-grid {
  display: grid;
  gap: 12px;
}

.settings-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  z-index: 12000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.header-menu {
  position: absolute;
  top: 56px;
  right: 8px;
  min-width: 160px;
  background: #233138;
  border: 1px solid #2f3b43;
  border-radius: 8px;
  padding: 6px 0;
  z-index: 50;
}

.header-menu .dropdown-item {
  color: #e9edef;
  background: transparent;
  border: 0;
  width: 100%;
  text-align: left;
  padding: 8px 12px;
  display: block;
  text-decoration: none;
}

.header-menu .dropdown-item:hover {
  background: #2f3b43;
}

.settings-card {
  width: min(420px, 100%);
  background: #1f2c33;
  color: #e9edef;
  border: 1px solid #2f3b43;
  border-radius: 12px;
  padding: 16px;
}

.settings-avatar,
.settings-avatar-placeholder {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  object-fit: cover;
  margin: 0 auto;
}

.settings-avatar-placeholder {
  background: #2f3b43;
  color: #e9edef;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  font-weight: 700;
}

/* Mobile-specific screen swap: only one pane is visible at a time. */
@media (max-width: 767.98px) {
  body, html {
    margin: 0;
    padding: 0;
    height: 100%;
    width: 100%;
    overflow: hidden;
  }

  .chat-shell {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    min-height: 100vh !important;
    height: 100dvh !important;
    margin: 0 !important;
    padding: 0 !important;
    border-radius: 0 !important;
    z-index: 9999;
    background-color: #fff;
    overflow: hidden;
    right: 0;
    bottom: 0;
    box-sizing: border-box;
  }

  .chat-layout {
    height: 100% !important;
    width: 100%;
    margin: 0 !important;
    flex-wrap: nowrap;
    border-radius: 0 !important;
    overflow: hidden;
  }

  .chat-sidebar,
  .chat-main {
    flex: 0 0 100%;
    width: 100%;
    max-width: 100%;
    height: 100% !important;
    border: none !important;
  }

  .chat-footer {
    padding: 4px !important;
  }

  .container-fluid.chat-shell {
    padding: 0 !important;
  }
}
</style>
