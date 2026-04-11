<template>
  <div v-if="isInitialLoading" class="initial-loading-screen">
    <div class="initial-loading-card text-center">
      <h2 class="mb-2">Skytalk</h2>
      <p class="text-muted mb-3">Loading your chats...</p>
      <output class="spinner-border text-primary" aria-label="Loading"></output>
      <div class="powered-by-text mt-3">Powered by CodeHelio</div>
    </div>
  </div>

  <div class="container-fluid vh-100 g-0 chat-shell" :style="chatThemeStyle">
    <div class="row g-0 h-100 chat-layout flex-nowrap">
      <!-- Left Sidebar - Fixed width like WhatsApp -->
      <div class="chat-sidebar border-end" v-show="showSidebarPane">
        <!-- Profile Header -->
        <div class="p-3 d-flex justify-content-between align-items-center border-bottom border-dark position-relative" style="background-color: #d7ebff; height: 60px;">
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
            <button type="button" class="btn btn-sm btn-outline-light" @click="openGroupManager" title="Create group">
              <i class="bi bi-people-fill"></i>
            </button>
            <i class="bi bi-chat-left-text-fill"></i>
            <i class="bi bi-three-dots-vertical" style="cursor: pointer;" @click="toggleMenu"></i>
          </div>

          <div v-if="showHeaderMenu" class="header-menu">
            <button type="button" class="dropdown-item dropdown-item-disabled" disabled title="Coming soon">
              <i class="bi bi-shield-check me-2"></i>HR Management <small class="text-muted">(coming soon)</small>
            </button>
            <button type="button" class="dropdown-item dropdown-item-disabled" disabled title="Coming soon">
              <i class="bi bi-building me-2"></i>Hotel Booking <small class="text-muted">(coming soon)</small>
            </button>
            <div class="dropdown-divider my-1"></div>
            <button type="button" class="dropdown-item" @click="openSettings">
              <i class="bi bi-gear me-2"></i>Settings
            </button>
            <router-link to="/" class="dropdown-item">
              <i class="bi bi-box-arrow-right me-2"></i>Logout
            </router-link>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="p-2" style="background-color: #eaf4ff;">
          <div class="input-group">
            <span class="input-group-text border-0" style="background-color: #d7ebff; color: #163d66;">
              <i class="bi bi-search"></i>
            </span>
            <input
                type="text"
                class="form-control border-0 shadow-none"
                v-model="searchQuery"
                style="background-color: #d7ebff; color: #163d66;"
                placeholder="Search or start a new chat"
                @input="searchUser"
            >
            <button
                type="button"
                class="input-group-text border-0"
                style="background-color: #d7ebff; color: #163d66;"
                @click="clearSearch"
                :title="searchQuery.trim() ? 'Clear search' : 'Search'"
            >
              <i class="bi" :class="searchQuery.trim() ? 'bi-x-lg' : 'bi-search'"></i>
            </button>
          </div>
        </div>

        <!-- User List - Scrollable area -->
        <div class="flex-grow-1 overflow-auto sidebar-user-list">
          <div class="px-2 pt-2" v-if="visibleGroups.length">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <small class="text-muted text-uppercase">Groups</small>
              <small class="text-muted">{{ visibleGroups.length }}</small>
            </div>
            <div v-for="group in visibleGroups" :key="group.id" class="p-2 border-bottom position-relative d-flex align-items-center gap-2">
              <button
                type="button"
                class="list-group-item list-group-item-action border-0 d-flex align-items-center flex-grow-1"
                :class="{ 'active': selectedUser?.id === group?.id && selectedUser?.type === 'group' }"
                @click="selectGroup(group)"
              >
                <span class="flex-shrink-0 me-2">
                  <span class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-people-fill text-white"></i>
                  </span>
                </span>
                <span class="flex-grow-1 text-start">
                  {{ group?.name || 'Untitled Group' }}
                  <small class="text-muted text-truncate d-block">{{ formatGroupPreview(group) }}</small>
                </span>
              </button>
            </div>
          </div>

          <div class="px-2 pt-3" v-if="visibleGroups.length || visibleUsers.length">
            <small class="text-muted text-uppercase">People</small>
          </div>
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
                  <span class="text-white">{{ user?.name?.charAt(0) || '?' }}</span>
                </span>
              </span>
              <span class="flex-grow-1">
                {{ user?.name || 'Unknown User' }}
                <small class="text-muted text-truncate d-block" style="max-width: 250px;">Chat with {{ user?.name || 'Unknown' }}</small>
              </span>
              <span class="flex-shrink-0">
                <small class="text-muted">12:30 PM</small>
              </span>
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="chat-footer p-3 border-top border-dark" style="background-color: #eaf4ff;">
          <div class="d-flex justify-content-between small mt-2" style="color: #163d66;">
            <span>{{ currentWeather }}</span>
            <span>{{ currentLocation }}</span>
            <span>{{ currentDate }}</span>
          </div>
        </div>
      </div>
      <!-- Right Chat Area - Only shown when user is selected -->
      <div class="chat-main" v-if="selectedUser && showChatPane && !isDetailsOpen">
        <!-- Chat Header -->
        <div class="p-3 border-bottom border-dark d-flex justify-content-between align-items-center" style="background-color: #d7ebff; color: #163d66;">
          <div class="d-flex align-items-center">
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary me-2 d-md-none"
                @click="goBackToUsers"
            >
              <i class="bi bi-arrow-left"></i>
            </button>
            <div class="me-3" style="cursor: pointer;" @click="isGroupConversation(selectedUser) ? openGroupDetails(selectedUser) : selectUserprofile(selectedUser)">
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
            <div style="cursor: pointer;" @click="isGroupConversation(selectedUser) ? openGroupDetails(selectedUser) : selectUserprofile(selectedUser)">
              <h6 class="mb-0">Chat with {{ selectedUser.name }}</h6>
              <small style="color: #163d66;">{{ isGroupConversation(selectedUser) ? 'Group conversation' : getUserPresenceText(selectedUser) }}</small>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button
              v-if="selectedCallHistory.length"
              class="btn btn-sm btn-outline-secondary"
              @click="openCallHistoryDetails()"
              title="Click to view call details"
            >
              <i class="bi bi-clock-history"></i>
            </button>
            <button v-if="!isGroupConversation(selectedUser)" class="btn btn-sm btn-outline-primary" @click="startVideoCall(selectedUser.id)">
              <i class="bi bi-camera-video"></i>
            </button>
            <button v-if="!isGroupConversation(selectedUser)" class="btn btn-sm btn-outline-primary" @click="startCall(selectedUser.id)">
              <i class="bi bi-telephone fs-6"></i>
            </button>
            <button
              v-if="!isGroupConversation(selectedUser) && partnerId && sameUserId(partnerId, selectedUser.id)"
              class="btn btn-sm btn-outline-danger"
              @click="endCurrentCall"
            >
              <i class="bi bi-telephone-x fs-6"></i>
            </button>
            <button v-if="isGroupConversation(selectedUser)" class="btn btn-sm btn-outline-secondary" @click="openGroupSettings(selectedUser)">
              <i class="bi bi-gear"></i>
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
          <div v-for="(msg, index) in chat.messages" :key="msg.id || `${msg.created_at || ''}-${index}`"
               class="mb-2 d-flex"
               :class="{ 'justify-content-end': isCurrentUserMessage(msg) }"
          >
            <!-- color message-->
            <div
                class="p-2 px-3 chat-message-bubble shadow-sm"
                :style="{
                  'background-color': isCurrentUserMessage(msg) ? settings.accentColor : '#d7ebff',
                  'color': '#163d66',
                  'border-radius': isCurrentUserMessage(msg) ? '8px 0px 8px 8px' : '0px 8px 8px 8px',
                  'max-width': '65%',
                  'position': 'relative'
                }"
                style="font-size: 0.9rem;"
            >
              <template v-if="getMessageAttachment(msg)">
                <div class="attachment-container mb-2">
                  <img
                    v-if="getMessageAttachment(msg).kind === 'image'"
                    :src="resolveAttachmentUrl(getMessageAttachment(msg))"
                    class="chat-attachment-image mb-2"
                    style="cursor: zoom-in;"
                    @click="viewAttachment(getMessageAttachment(msg))"
                    alt="Attachment"
                  >
                  <video
                    v-else-if="getMessageAttachment(msg).kind === 'video'"
                    :src="resolveAttachmentUrl(getMessageAttachment(msg))"
                    class="chat-attachment-video mb-2"
                    controls
                    preload="metadata"
                    :aria-label="getMessageAttachment(msg).name"
                  >
                    <track kind="subtitles" label="Subtitles" srclang="en" :src="getMessageAttachment(msg).captionsUrl || ''" default>
                    <track kind="descriptions" label="Descriptions" srclang="en" :src="getMessageAttachment(msg).descriptionsUrl || getMessageAttachment(msg).captionsUrl || ''">
                  </video>
                  <audio
                    v-else-if="getMessageAttachment(msg).kind === 'audio'"
                    :src="resolveAttachmentUrl(getMessageAttachment(msg))"
                    controls
                    preload="metadata"
                    class="w-100 mb-2"
                  ></audio>
                  <div v-else class="document-preview mb-2 p-2 border rounded" style="background-color: rgba(255,255,255,0.1);">
                    <i class="bi bi-file-earmark me-2"></i>
                    <span class="document-name">{{ getMessageAttachment(msg).name || 'Document' }}</span>
                  </div>

                  <!-- Action buttons for all attachment types -->
                  <div class="attachment-actions d-flex gap-2">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-light"
                      @click="viewAttachment(getMessageAttachment(msg))"
                      title="View file"
                    >
                      <i class="bi bi-eye me-1"></i>View
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-light"
                      @click="downloadAttachment(getMessageAttachment(msg))"
                      title="Download file"
                    >
                      <i class="bi bi-download me-1"></i>Download
                    </button>
                  </div>
                </div>
              </template>
              <div v-if="shouldShowMessageBody(msg)" style="word-break: break-word;">{{ getVisibleMessageBody(msg) }}</div>
              <div class="text-end" style="font-size: 0.65rem; color: #163d66; margin-top: 4px;">
                {{ formatMessageDate(msg.created_at) }}
                <span v-if="isCurrentUserMessage(msg)" class="ms-1">
                  <i class="bi bi-check2-all text-info"></i>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Message Input -->
        <div class="p-3 border-top border-dark" style="background-color: #d7ebff;">
          <div class="input-group position-relative">
            <button class="btn btn-outline-secondary" type="button" @click="toggleEmojiPicker">
              <i class="bi bi-emoji-smile"></i>
            </button>
            <button class="btn btn-outline-secondary" type="button" @click="triggerAttachmentPicker" title="Attach file, image, or video">
              <i class="bi bi-paperclip"></i>
            </button>
            <div v-if="showEmojiPicker" class="emoji-picker">
              <button
                v-for="emoji in emojiList"
                :key="emoji"
                type="button"
                class="emoji-item"
                @click="appendEmoji(emoji)"
              >
                {{ emoji }}
              </button>
            </div>
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

          <div v-if="selectedAttachment" class="attachment-preview mt-2 d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2 text-truncate flex-grow-1">
              <i class="bi" :class="selectedAttachment.kind === 'image' ? 'bi-image' : selectedAttachment.kind === 'video' ? 'bi-camera-video' : 'bi-file-earmark-text'"></i>
              <div class="text-truncate">
                <div class="small fw-semibold text-truncate">{{ selectedAttachment.name || selectedAttachment.kindLabel }}</div>
                <small class="text-muted">{{ selectedAttachment.kindLabel }} · {{ formatFileSize(selectedAttachment.size) }}</small>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-light" @click="clearSelectedAttachment">Remove</button>
          </div>

          <div v-if="attachmentValidationError" class="text-danger small mt-2">
            {{ attachmentValidationError }}
          </div>

          <input
            ref="attachmentInput"
            type="file"
            class="d-none"
            accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.rtf,.xls,.xlsx,.ppt,.pptx,.csv,.zip,.rar"
            @change="handleAttachmentChange"
          >
        </div>
      </div>
      <!-- Empty State when no user is selected -->
      <div class="chat-main justify-content-center align-items-center" style="background-color: #eaf4ff; color: #163d66;" v-else-if="showChatPane && !isDetailsOpen">
        <div class="text-center p-5">
          <i class="bi bi-people-fill" style="font-size: 3rem; color: #8fb6e0;"></i>
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
            <label class="form-label mb-1" for="settings-phone">Phone</label>
            <input id="settings-phone" type="text" class="form-control" v-model="profileForm.phone" placeholder="Phone number" />
          </div>

          <div class="mb-2">
            <label class="form-label mb-1" for="settings-address">Address</label>
            <input id="settings-address" type="text" class="form-control" v-model="profileForm.address" placeholder="Address" />
          </div>

          <div class="mb-2">
            <label class="form-label mb-1" for="settings-city">City</label>
            <input id="settings-city" type="text" class="form-control" v-model="profileForm.city" placeholder="City" />
          </div>

          <div class="mb-2">
            <label class="form-label mb-1" for="settings-country">Country</label>
            <input id="settings-country" type="text" class="form-control" v-model="profileForm.country" placeholder="Country" />
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

          <div class="powered-by-text mt-3 text-center">Powered by CodeHelio</div>
        </div>
      </div>

      <div v-if="showUserDetails" class="settings-overlay" @click.self="closeUserDetails">
        <div class="settings-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Profile Details</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeUserDetails">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <p class="details-note">Friendly view of the selected chat profile.</p>

          <div v-if="isUserDetailsLoading" class="text-center text-muted py-4">Loading details...</div>
          <div v-else-if="userDetailsError" class="alert alert-danger py-2 mb-0">{{ userDetailsError }}</div>

          <template v-else-if="userDetails">
            <div class="mb-3 text-center">
              <img v-if="getUserPhoto(userDetails)" :src="getUserPhoto(userDetails)" alt="User profile" class="settings-avatar" />
              <div v-else class="settings-avatar-placeholder">{{ (userDetails?.name || '?').charAt(0).toUpperCase() }}</div>
            </div>

            <div class="user-details-grid">
              <div class="details-item">
                <small class="details-label">Full name</small>
                <div class="details-value">{{ userDetails?.name || 'Unknown user' }}</div>
              </div>
              <div class="details-item">
                <small class="details-label">Email</small>
                <div class="details-value text-break">{{ userDetails?.email || 'Not available' }}</div>
              </div>
              <div class="details-item">
                <small class="details-label">Username</small>
                <div class="details-value">{{ userDetails?.username || 'Not set' }}</div>
              </div>
              <div class="details-item">
                <small class="details-label">Joined</small>
                <div class="details-value">{{ formatProfileDate(userDetails?.created_at) || 'Not available' }}</div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <div v-if="showGroupDetails" class="settings-overlay" @click.self="closeGroupDetails">
        <div class="settings-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Group Details</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeGroupDetails">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <p class="details-note">Group details are available from the active group chat header.</p>

          <template v-if="activeGroupDetails">
            <div class="mb-3 text-center">
              <img
                v-if="getUserPhoto(activeGroupDetails)"
                :src="getUserPhoto(activeGroupDetails)"
                alt="Group profile"
                class="settings-avatar"
              />
              <div v-else class="settings-avatar-placeholder">{{ (activeGroupDetails?.name || '?').charAt(0).toUpperCase() }}</div>
            </div>

            <div class="user-details-grid">
              <div class="details-item">
                <small class="details-label">Group name</small>
                <div class="details-value">{{ activeGroupDetails?.name || 'Untitled Group' }}</div>
              </div>
              <div class="details-item">
                <small class="details-label">Members</small>
                <div class="details-value">{{ activeGroupDetails?.members?.length || 0 }}</div>
              </div>
              <div class="details-item">
                <small class="details-label">Created</small>
                <div class="details-value">{{ formatProfileDate(activeGroupDetails?.created_at) || 'Not available' }}</div>
              </div>
            </div>

            <div class="group-members-list mt-3">
              <div
                v-for="member in (activeGroupDetails?.members || [])"
                :key="`group-details-${activeGroupDetails?.id}-${member?.id}`"
                class="group-member-item d-flex align-items-center justify-content-between p-2 mb-2 rounded"
              >
                <div class="d-flex align-items-center gap-2">
                  <span class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                    <span class="text-white small">{{ (member?.name?.charAt(0) || '?').toUpperCase() }}</span>
                  </span>
                  <div class="small" style="color: #163d66;">
                    <div>{{ member?.name || 'Unknown User' }}</div>
                    <small class="text-muted">{{ memberLocationLabel(member) }}</small>
                  </div>
                </div>
                <small class="text-muted">{{ sameUserId(member?.id, currentUserId) ? 'You' : 'Member' }}</small>
              </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-3">
              <button type="button" class="btn btn-outline-secondary" @click="closeGroupDetails">Close</button>
              <button type="button" class="btn btn-outline-primary" @click="openGroupChatFromDetails">Open Chat</button>
              <button type="button" class="btn btn-primary" @click="openGroupSettingsFromDetails">Manage Group</button>
            </div>
          </template>
        </div>
      </div>

      <div v-if="showCallHistoryDetails" class="settings-overlay" @click.self="closeCallHistoryDetails">
        <div class="settings-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Call Details</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeCallHistoryDetails">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <p class="details-note">Opened from the call details button for a quick, readable summary.</p>

          <div v-if="selectedCallHistory.length" class="mb-3">
            <small class="details-label">All call history</small>
            <div class="call-history-list">
              <button
                v-for="(item, index) in selectedCallHistory"
                :key="item.id || `${item.started_at}-${index}`"
                type="button"
                class="call-history-item"
                :class="{ active: isCallHistoryItemActive(item) }"
                @click="openCallHistoryDetails(item)"
              >
                <span class="call-history-item-title">{{ formatCallHistoryTitle(item) }}</span>
                <span class="call-history-item-subtitle">{{ formatCallHistorySubtitle(item) }}</span>
              </button>
            </div>
          </div>

          <div v-if="activeCallHistoryItem" class="user-details-grid">
            <div class="call-details-summary">
              <span class="call-badge" :class="callDirectionBadgeClass(activeCallHistoryItem.direction)">
                {{ formatCallDirectionLabel(activeCallHistoryItem.direction) }}
              </span>
              <span class="call-badge" :class="callStatusBadgeClass(activeCallHistoryItem.status)">
                {{ formatCallStatusLabel(activeCallHistoryItem.status) }}
              </span>
            </div>
            <div>
              <small class="details-label">Caller</small>
              <div class="details-value">{{ getCallSenderName(activeCallHistoryItem) }}</div>
            </div>
            <div>
              <small class="details-label">Callee</small>
              <div class="details-value">{{ getCallReceiverName(activeCallHistoryItem) }}</div>
            </div>
            <div>
              <small class="details-label">Started</small>
              <div class="details-value">{{ formatCallDetailDateTime(activeCallHistoryItem.started_at) || 'Not available' }}</div>
            </div>
            <div>
              <small class="details-label">Ended</small>
              <div class="details-value">{{ formatCallDetailDateTime(activeCallHistoryItem.ended_at) || 'Not available' }}</div>
            </div>
            <div>
              <small class="details-label">Duration</small>
              <div class="details-value">{{ formatCallDuration(activeCallHistoryItem.duration_seconds) }}</div>
            </div>
            <div v-if="activeCallHistoryItem.meta">
              <small class="details-label">Extra details</small>
              <pre class="call-history-meta mb-0">{{ formatCallMeta(activeCallHistoryItem.meta) }}</pre>
            </div>
          </div>
          <div v-else class="text-muted text-center py-3">No call selected. Click the call details button in chat header.</div>
        </div>
      </div>

      <div v-if="showIncomingCallModal" class="settings-overlay" @click.self="rejectIncomingCall('rejected')">
        <div class="settings-card incoming-call-card">
          <div class="text-center">
            <div class="incoming-call-photo mb-3">
              <img
                v-if="incomingCallerPhoto"
                :src="incomingCallerPhoto"
                class="rounded-circle"
                style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #b9d8ff;"
                alt="Caller"
              >
              <div v-else class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; margin: 0 auto; border: 4px solid #b9d8ff;">
                <span class="text-white" style="font-size: 2rem;">{{ (incomingCallDisplayName || '?').charAt(0).toUpperCase() }}</span>
              </div>
            </div>
            <div class="incoming-call-icon mb-2">
              <i class="bi bi-telephone-fill"></i>
            </div>
            <h5 class="mb-1">Incoming Call</h5>
            <p class="text-muted mb-3">{{ incomingCallDisplayName }}</p>
            <div class="d-flex gap-2 justify-content-center">
              <button
                type="button"
                class="btn btn-danger"
                :disabled="isCallActionPending"
                @click="rejectIncomingCall('rejected')"
              >
                <i class="bi bi-telephone-x me-2"></i>Decline
              </button>
              <button
                type="button"
                class="btn btn-success"
                :disabled="isCallActionPending"
                @click="acceptIncomingCall"
              >
                <i class="bi bi-telephone me-2"></i>Accept
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="showOutgoingCallModal" class="settings-overlay" @click.self="endCurrentCall">
        <div class="settings-card incoming-call-card">
          <div class="text-center">
            <div class="incoming-call-icon mb-2">
              <i class="bi bi-telephone-outbound-fill"></i>
            </div>
            <h5 class="mb-1">Calling...</h5>
            <p class="text-muted mb-3">{{ outgoingCallDisplayName }} · {{ outgoingCallStatusText }}</p>
            <div class="d-flex gap-2 justify-content-center">
              <button
                type="button"
                class="btn btn-danger"
                :disabled="isCallActionPending"
                @click="endCurrentCall"
              >
                End Call
              </button>
            </div>
          </div>
        </div>
      </div>

      <audio ref="remoteAudio" autoplay playsinline style="display: none;"></audio>
      <video ref="remoteVideo" autoplay playsinline style="display: none;"></video>
      <video ref="localVideo" autoplay muted playsinline style="display: none;"></video>
    </div>
    </div>

  <div v-if="showImagePreview" class="image-preview-overlay" @click.self="closeImagePreview">
    <div class="image-preview-toolbar">
      <button type="button" class="btn btn-sm btn-light" @click="closeImagePreview">
        <i class="bi bi-arrow-left me-1"></i>Back
      </button>
      <div class="d-flex align-items-center gap-2">
        <button
          v-if="previewAttachment"
          type="button"
          class="btn btn-sm btn-primary"
          @click="downloadAttachment(previewAttachment)"
        >
          <i class="bi bi-download me-1"></i>Download
        </button>
        <button type="button" class="btn btn-sm btn-outline-light" @click="closeImagePreview">
          <i class="bi bi-x-lg me-1"></i>Close
        </button>
      </div>
    </div>
    <div class="image-preview-stage">
      <img
        v-if="previewAttachmentKind === 'image'"
        :src="imagePreviewUrl"
        :alt="imagePreviewName || 'Attachment file'"
        class="image-preview-full"
      >
      <video
        v-else-if="previewAttachmentKind === 'video'"
        :src="imagePreviewUrl"
        class="image-preview-full"
        controls
        autoplay
      ></video>
    </div>
  </div>

  <div v-if="showGroupManager" class="settings-overlay" @click.self="closeGroupManager">
    <div class="settings-card group-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ editingGroupId ? 'Edit Group' : 'Create Group' }}</h5>
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeGroupManager">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

          <div class="mb-3">
            <label class="form-label mb-1" for="group-name-input">Group name</label>
            <input id="group-name-input" v-model="groupForm.name" type="text" class="form-control" placeholder="Enter group name">
          </div>

      <div class="mb-2">
        <small class="text-muted d-block mb-2">Select members</small>
        <div class="group-member-picker">
          <label v-for="user in groupCandidateUsers" :key="user.id" class="group-member-row">
            <input type="checkbox" class="form-check-input me-2" :value="user.id" v-model="groupForm.members">
            <span class="flex-grow-1">{{ user.name || 'Unknown User' }}</span>
          </label>
        </div>
      </div>

      <div v-if="groupFormError" class="alert alert-danger py-2 px-3 mb-2" role="alert">
        {{ groupFormError }}
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <button type="button" class="btn btn-outline-secondary" @click="closeGroupManager">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="groupFormSubmitting" @click="saveGroup">
          {{ groupFormSubmitting ? 'Saving...' : 'Save Group' }}
        </button>
      </div>
    </div>
  </div>

  <div v-if="showGroupSettings" class="settings-overlay" @click.self="closeGroupSettings">
    <div class="settings-card group-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ editingGroupForSettings?.name || 'Group' }} - Members</h5>
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeGroupSettings">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="mb-3">
        <label class="form-label mb-1" for="group-settings-name">Group Name</label>
        <input id="group-settings-name" type="text" class="form-control" v-model="groupSettingsForm.name" placeholder="Group name" />
      </div>

      <div class="mb-3 text-center">
        <img
          v-if="groupSettingsPreviewUrl || getUserPhoto(editingGroupForSettings)"
          :src="groupSettingsPreviewUrl || getUserPhoto(editingGroupForSettings)"
          alt="Group"
          class="settings-avatar"
        />
        <div v-else class="settings-avatar-placeholder">{{ (groupSettingsForm.name || editingGroupForSettings?.name || '?').charAt(0).toUpperCase() }}</div>
      </div>

      <div class="mb-3">
        <label class="form-label mb-1" for="group-settings-image">Group Profile Image</label>
        <input id="group-settings-image" type="file" class="form-control" accept="image/*" @change="handleGroupImageUpload" />
      </div>

      <div class="mb-3">
        <div class="d-flex gap-2 mb-2">
          <span class="text-muted">Add members to this group</span>
          <button
            type="button"
            class="btn btn-sm btn-primary"
            @click="showAddMemberForm = !showAddMemberForm"
          >
            <i class="bi bi-plus-lg"></i> Add
          </button>
        </div>

        <div v-if="showAddMemberForm" class="mb-3 p-2 bg-dark rounded">
          <small class="text-muted d-block mb-2">Select users to add:</small>
          <div class="group-member-picker" style="max-height: 200px;">
            <label v-for="user in availableUsersForGroup" :key="user.id" class="group-member-row">
              <input
                type="checkbox"
                class="form-check-input me-2"
                :value="user.id"
                v-model="newGroupMembers"
              >
              <span class="flex-grow-1">{{ user.name || 'Unknown User' }}</span>
            </label>
          </div>
          <div class="d-flex gap-2 mt-2 justify-content-end">
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="showAddMemberForm = false"
            >Cancel</button>
            <button
              type="button"
              class="btn btn-sm btn-primary"
              @click="addMembersToGroup"
            >Add Selected</button>
          </div>
        </div>
      </div>

      <div class="mb-2">
        <small class="text-muted d-block mb-2">Group Members ({{ editingGroupForSettings?.members?.length || 0 }})</small>
        <div class="group-members-list">
          <div
            v-for="member in editingGroupForSettings?.members"
            :key="member.id"
            class="group-member-item d-flex align-items-center justify-content-between p-2 mb-2 bg-dark rounded"
          >
            <div class="d-flex align-items-center gap-2">
              <span class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                <span class="text-white small">{{ (member.name?.charAt(0) || '?').toUpperCase() }}</span>
              </span>
              <div>
                <div class="text-white small">{{ member.name || 'Unknown User' }}</div>
                <small class="text-muted">{{ sameUserId(member.id, currentUserId) ? 'You (Admin)' : 'Member' }}</small>
              </div>
            </div>
            <button
              v-if="!sameUserId(member.id, currentUserId)"
              type="button"
              class="btn btn-sm btn-outline-danger"
              @click="removeMemberFromGroup(member.id)"
              title="Remove member"
            >
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end mt-3">
        <button type="button" class="btn btn-primary" :disabled="groupFormSubmitting" @click="saveGroupSettings">
          {{ groupFormSubmitting ? 'Saving...' : 'Save Changes' }}
        </button>
        <button type="button" class="btn btn-outline-secondary" @click="closeGroupSettings">Close</button>
        <button type="button" class="btn btn-outline-danger" @click="deleteGroup">Delete Group</button>
      </div>
    </div>
  </div>

</template>

<script>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import store from '@/store';
import Chat from './Chat.vue';
import AllServiceService from '@/services/all-service';
import echo from '@/services/echo'
import CallService from "@/services/CallService";
import { getApiOrigin } from '@/services/api-origin'

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
    const showGroupDetails = ref(false)
    const activeGroupDetails = ref(null)
    const isUserDetailsLoading = ref(false)
    const userDetails = ref(null)
    const userDetailsError = ref('')
    const showEmojiPicker = ref(false)
    const showCallHistoryDetails = ref(false)
    const activeCallHistoryItem = ref(null)
    const emojiList = ['😀', '😂', '😍', '😎', '😊', '😁', '🤔', '😢', '👍', '🙏', '🔥', '🎉', '❤️', '💬', '✅', '🙌']
    const currentUserPhoto = ref('')
    const currentUserProfile = ref(null)
    const defaultSettings = {
      accentColor: '#60a5fa'
    }
    const settings = ref({ ...defaultSettings })
    const profileForm = ref({
      name: '',
      phone: '',
      address: '',
      city: '',
      country: '',
      image: null,
      accentColor: defaultSettings.accentColor
    })
    const profilePreviewUrl = ref('')
    const currentLocation = ref('Locating...')
    const currentWeather = ref('Weather...')
    const now = ref(new Date())
    const dateTickIntervalId = ref(null)
    const weatherRefreshIntervalId = ref(null)
    const weatherRefreshInFlight = ref(false)
    const pc = ref(null)
    const localAudioStream = ref(null)
    const partnerId = ref(null)
    const remoteAudio = ref(null)
    const remoteVideo = ref(null)
    const localVideo = ref(null)
    const callStartedAt = ref(null)
    const selectedCallHistory = ref([])
    const showIncomingCallModal = ref(false)
    const showOutgoingCallModal = ref(false)
    const incomingCall = ref(null)
    const outgoingCall = ref(null)
    const isCallActionPending = ref(false)
    const incomingCallTimeoutId = ref(null)
    const attachmentInput = ref(null)
    const selectedAttachment = ref(null)
    const attachmentValidationError = ref('')
    const isSendingMessage = ref(false)
    const showImagePreview = ref(false)
    const imagePreviewUrl = ref('')
    const imagePreviewName = ref('')
    const previewAttachment = ref(null)
    const previewAttachmentKind = ref('image')
    const activeChannelNames = ref([])
    const activeGroupChannelNames = ref([])
    const activeCallChannelNames = ref([])
    const knownChatUserIds = ref([])
    const latestProfileRequestId = ref(0)
    const pendingIceCandidates = ref([])
    const isInitialLoading = ref(true)
    const MAX_MEDIA_ATTACHMENT_SIZE_BYTES = 40 * 1024 * 1024
    const MAX_DOCUMENT_ATTACHMENT_SIZE_BYTES = 10 * 1024 * 1024
    const groupSettingsForm = ref({ name: '', image: null })
    const groupSettingsPreviewUrl = ref('')
    const updateViewportMode = () => {
      isMobileView.value = window.innerWidth < 768;
      if (!isMobileView.value) {
        activeMobilePane.value = 'list';
      }
    };

    const getAttachmentKind = (file) => {
      if (!file) return 'file'

      const type = (file.type || '').toString().toLowerCase()
      if (type.startsWith('image/')) return 'image'
      if (type.startsWith('video/')) return 'video'
      if (type.startsWith('audio/')) return 'audio'

      const hint = `${file.name || ''} ${file.url || ''}`.toLowerCase()
      if (/\.(png|jpe?g|gif|webp|bmp|svg)(\?|#|$)/.test(hint)) return 'image'
      if (/\.(mp4|webm|ogg|mov|mkv)(\?|#|$)/.test(hint)) return 'video'
      if (/\.(mp3|wav|m4a|aac|flac|oga|opus)(\?|#|$)/.test(hint)) return 'audio'

      return 'document'
    }

    const formatFileSize = (bytes) => {
      const size = Number(bytes || 0)
      if (!size) return '0 B'

      const units = ['B', 'KB', 'MB', 'GB']
      let unitIndex = 0
      let value = size

      while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024
        unitIndex += 1
      }

      return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`
    }

    const getAttachmentLimitBytes = (kind) => (
      kind === 'document' ? MAX_DOCUMENT_ATTACHMENT_SIZE_BYTES : MAX_MEDIA_ATTACHMENT_SIZE_BYTES
    )

    const validateAttachmentFile = (file) => {
      const kind = getAttachmentKind(file)
      const maxBytes = getAttachmentLimitBytes(kind)

      if (Number(file?.size || 0) <= maxBytes) {
        return { valid: true, kind, maxBytes }
      }

      const limitLabel = formatFileSize(maxBytes)
      const kindLabel = kind === 'document' ? 'Document' : (kind.charAt(0).toUpperCase() + kind.slice(1))
      return {
        valid: false,
        kind,
        maxBytes,
        message: `"${file.name}" exceeds the ${kindLabel} limit (${limitLabel}).`,
      }
    }

    const normalizeAttachment = (attachment, fallback = {}) => {
      if (!attachment) return null

      if (typeof attachment === 'string') {
        const directUrl = attachment.trim()
        if (!directUrl) return null

        const inferredName = directUrl.split('/').pop()?.split('?')[0] || ''
        const inferredKind = getAttachmentKind({ url: directUrl, name: inferredName })
        return {
          url: directUrl,
          name: fallback?.name || inferredName,
          mimeType: fallback?.mimeType || '',
          kind: fallback?.kind || inferredKind,
          size: Number(fallback?.size || 0),
        }
      }

      const url = attachment.url || attachment.preview_url || attachment.attachment_url || attachment.file_url || attachment.media_url || attachment.path || attachment.file_path || attachment.attachment_path || fallback?.url || ''
      const name = attachment.name || attachment.original_name || attachment.file_name || attachment.filename || fallback?.name || ''
      const mimeType = attachment.mime_type || attachment.type || attachment.content_type || fallback?.mimeType || ''
      const rawKind = (attachment.kind || fallback?.kind || '').toString().trim()
      const size = Number(attachment.size || attachment.file_size || fallback?.size || 0)

      // Empty fallback objects should not be treated as real attachments.
      const hasAttachmentData = Boolean(url || name || mimeType || rawKind || size > 0)
      if (!hasAttachmentData) {
        return null
      }

      const kind = rawKind || getAttachmentKind({ type: mimeType, name, url })

      return { url, name, mimeType, kind, size }
    }

    const getUserPhoto = (user) => {
      if (!user) {
        return ''
      }

      if (user.profile_photo_url) {
        return user.profile_photo_url
      }

      const rawPath = user.profile_photo_path || user.group_profile_path || user.group_image_path || user.avatar || user.image || ''
      if (!rawPath) {
        return ''
      }

      if (rawPath.startsWith('http://') || rawPath.startsWith('https://')) {
        return rawPath
      }

      const baseUrl = getApiOrigin().replace(/\/$/, '')
      const normalizedPath = rawPath.replace(/^\/+/, '')

      if (normalizedPath.startsWith('storage/')) {
        return `${baseUrl}/${normalizedPath}`
      }

      return `${baseUrl}/storage/${normalizedPath}`
    }

    const getMessageAttachment = (msg) => {
      if (!msg) return null

      return normalizeAttachment(
        msg.attachment || msg.file || msg.media || {
          url: msg.attachment_url || msg.file_url || msg.media_url || msg.file_path || msg.attachment_path,
          name: msg.attachment_name || msg.file_name || msg.original_name || msg.filename,
          mime_type: msg.attachment_mime_type || msg.mime_type || msg.file_type,
          kind: msg.attachment_kind || msg.message_kind,
          size: msg.attachment_size || msg.file_size,
        },
        { url: '', name: '', mimeType: '', size: 0 }
      )
    }

    const triggerAttachmentPicker = () => {
      attachmentInput.value?.click()
    }

    const clearSelectedAttachment = () => {
      if (selectedAttachment.value?.previewUrl) {
        URL.revokeObjectURL(selectedAttachment.value.previewUrl)
      }
      selectedAttachment.value = null
      attachmentValidationError.value = ''
      if (attachmentInput.value) {
        attachmentInput.value.value = ''
      }
    }

    const handleAttachmentChange = (event) => {
      const file = event?.target?.files?.[0]
      if (!file) return

      const validation = validateAttachmentFile(file)
      if (!validation.valid) {
        attachmentValidationError.value = validation.message
        clearSelectedAttachment()
        if (event?.target) {
          event.target.value = ''
        }
        return
      }

      attachmentValidationError.value = ''

      if (selectedAttachment.value?.previewUrl) {
        URL.revokeObjectURL(selectedAttachment.value.previewUrl)
      }

      const kind = getAttachmentKind(file)
      const previewUrl = kind === 'image' || kind === 'video' ? URL.createObjectURL(file) : ''
      let kindLabel = 'Document'

      if (kind === 'image') {
        kindLabel = 'Image'
      } else if (kind === 'video') {
        kindLabel = 'Video'
      }

      selectedAttachment.value = {
        file,
        kind,
        kindLabel,
        name: file.name,
        size: file.size,
        mimeType: file.type,
        previewUrl,
      }
    }

    const playMediaElement = async (element) => {
      if (!element || typeof element.play !== 'function') {
        return
      }

      try {
        await element.play()
      } catch (error) {
        // Chrome can block autoplay until user gesture; the stream is still attached.
        console.warn('Media autoplay was blocked by browser policy:', error)
      }
    }

    const updateLocalPreview = () => {
      if (localVideo.value) {
        localVideo.value.srcObject = localAudioStream.value || null
        playMediaElement(localVideo.value)
      }
    }

    const streamHasVideo = (stream) => Array.isArray(stream?.getVideoTracks?.()) && stream.getVideoTracks().length > 0

    const requestLocalMediaStream = async (callType = 'audio') => {
      const wantsVideo = safeCallType(callType) === 'video'

      if (!navigator.mediaDevices?.getUserMedia) {
        throw new Error('Media devices API is not available in this browser.')
      }

      const constraints = wantsVideo
        ? { audio: true, video: { facingMode: 'user' } }
        : { audio: true, video: false }

      return navigator.mediaDevices.getUserMedia(constraints)
    }

    const syncConnectionTracks = async (connection, stream) => {
      if (!connection || !stream) {
        return
      }

      for (const track of stream.getTracks()) {
        const sender = connection.getSenders?.().find((item) => item.track?.kind === track.kind)
        if (sender) {
          await sender.replaceTrack(track)
        } else {
          connection.addTrack(track, stream)
        }
      }
    }

    const setupPeerConnection = () => {
      if (pc.value) return pc.value

      const connection = new RTCPeerConnection({
        iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
      })

      connection.ontrack = (e) => {
        const remoteStream = e.streams?.[0]
        if (!remoteStream) {
          return
        }

        if (remoteAudio.value) {
          remoteAudio.value.srcObject = remoteStream
          playMediaElement(remoteAudio.value)
        }

        if (remoteVideo.value && streamHasVideo(remoteStream)) {
          remoteVideo.value.srcObject = remoteStream
          playMediaElement(remoteVideo.value)
        }
      }

      connection.onicecandidate = (e) => {
        if (e.candidate && partnerId.value) {
          CallService.sendIce(partnerId.value, e.candidate)
        }
      }

      pc.value = connection
      return connection
    }

    const ensurePeerConnection = async (options = {}) => {
      const callType = safeCallType(options?.callType)
      const connection = setupPeerConnection()

      const requiresVideoTrack = callType === 'video'
      const hasLocalStream = Boolean(localAudioStream.value)
      const hasLocalVideo = hasLocalStream && streamHasVideo(localAudioStream.value)

      if (!hasLocalStream || (requiresVideoTrack && !hasLocalVideo)) {
        try {
          const freshStream = await requestLocalMediaStream(callType)

          if (localAudioStream.value) {
            localAudioStream.value.getTracks().forEach((track) => track.stop())
          }

          localAudioStream.value = freshStream
          updateLocalPreview()
        } catch (error) {
          console.warn(`Unable to access ${requiresVideoTrack ? 'camera/microphone' : 'microphone'}:`, error)
          return null
        }
      }

      if (localAudioStream.value) {
        try {
          await syncConnectionTracks(connection, localAudioStream.value)
        } catch (error) {
          console.warn('Unable to attach local media tracks:', error)
          return null
        }
      }

      return connection
    }

    const teardownPeerConnection = () => {
      if (remoteAudio.value) {
        remoteAudio.value.srcObject = null
      }

      if (remoteVideo.value) {
        remoteVideo.value.srcObject = null
      }

      if (localVideo.value) {
        localVideo.value.srcObject = null
      }

      if (pc.value) {
        try {
          pc.value.ontrack = null
          pc.value.onicecandidate = null
          pc.value.getSenders?.forEach((sender) => {
            try {
              sender.track?.stop?.()
            } catch {
              // ignore
            }
          })
          pc.value.close()
        } catch (error) {
          console.warn('Peer connection teardown failed:', error)
        }
      }

      pc.value = null

      if (localAudioStream.value) {
        localAudioStream.value.getTracks().forEach((track) => track.stop())
        localAudioStream.value = null
      }
    }

    const clearCallSessionState = () => {
      clearIncomingCallState()
      clearOutgoingCallState()
      callStartedAt.value = null
      partnerId.value = null
    }
    // Keep footer date reactive instead of computed once at mount time.
    const currentDate = computed(() => {
      const today = now.value;
      const day = String(today.getDate()).padStart(2, '0');
      const month = String(today.getMonth() + 1).padStart(2, '0');
      const year = today.getFullYear();
      return `${day}/${month}/${year}`;
    });

    const showSidebarPane = computed(() => !isMobileView.value || activeMobilePane.value === 'list')
    const showChatPane = computed(() => !isMobileView.value || activeMobilePane.value === 'chat')
    const isDetailsOpen = computed(() =>
      showUserDetails.value || showGroupDetails.value || showCallHistoryDetails.value
    )

    const incomingCallDisplayName = computed(() => {
      const fromId = incomingCall.value?.fromId || incomingCall.value?.from_id
      if (!fromId) {
        return 'Unknown caller'
      }

      if (selectedUser.value && sameUserId(selectedUser.value.id, fromId)) {
        return selectedUser.value.name || `User #${fromId}`
      }

      const matchedUser = userView.value.find((user) => sameUserId(user?.id, fromId))
      return matchedUser?.name || `User #${fromId}`
    })

    const incomingCallerPhoto = computed(() => {
      const fromId = incomingCall.value?.fromId || incomingCall.value?.from_id
      if (!fromId) {
        return ''
      }

      if (selectedUser.value && sameUserId(selectedUser.value.id, fromId)) {
        return getUserPhoto(selectedUser.value)
      }

      const matchedUser = userView.value.find((user) => sameUserId(user?.id, fromId))
      return getUserPhoto(matchedUser)
    })

    const outgoingCallDisplayName = computed(() => {
      const toId = outgoingCall.value?.toId || outgoingCall.value?.to_id || partnerId.value
      if (!toId) {
        return 'Unknown user'
      }

      if (selectedUser.value && sameUserId(selectedUser.value.id, toId)) {
        return selectedUser.value.name || `User #${toId}`
      }

      const matchedUser = userView.value.find((user) => sameUserId(user?.id, toId))
      return matchedUser?.name || `User #${toId}`
    })

    const outgoingCallStatusText = computed(() => outgoingCall.value?.status || 'Ringing')

    const filteredUsers = computed(() => {
      const term = searchQuery.value.trim().toLowerCase()
      if (!term) {
        return userView.value
      }

      return userView.value.filter((user) => {
        const name = (user?.name || '').toLowerCase()
        const username = (user?.username || '').toLowerCase()
        const email = (user?.email || '').toLowerCase()
        return name.includes(term) || username.includes(term) || email.includes(term)
      })
    })

    const visibleUsers = computed(() => {
      if (!selectedUser.value) {
        return filteredUsers.value.slice(0, 10)
      }

      const selectedId = selectedUser.value.id
      const rest = filteredUsers.value.filter((user) => !sameUserId(user?.id, selectedId))
      const selectedInList = filteredUsers.value.find((user) => sameUserId(user?.id, selectedId))

      if (!selectedInList) {
        return rest.slice(0, 10)
      }

      return [selectedInList, ...rest].slice(0, 10)
    })

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
        console.log(`[Weather] Fetching for coords: ${latitude}, ${longitude}`)

        // Use proxy endpoints to avoid CORS issues
        const weatherUrl = `/api/weather?latitude=${latitude}&longitude=${longitude}&current=temperature_2m,weather_code&timezone=auto`
        const locationUrl = `/api/location?latitude=${latitude}&longitude=${longitude}&language=en&format=json`

        // Fetch with timeout
        const fetchWithTimeout = (url, timeoutMs = 8000) => {
          return Promise.race([
            fetch(url),
            new Promise((_, reject) =>
              setTimeout(() => reject(new Error('Fetch timeout')), timeoutMs)
            )
          ])
        }

        const [weatherRes, locationRes] = await Promise.all([
          fetchWithTimeout(weatherUrl),
          fetchWithTimeout(locationUrl)
        ])

        const weatherData = weatherRes?.ok ? await weatherRes.json() : null
        const locationData = locationRes?.ok ? await locationRes.json() : null

        console.log('[Weather] Response:', { weatherData, locationData })

        // Process weather
        const temp = weatherData?.current?.temperature_2m
        const code = weatherData?.current?.weather_code
        const weatherLabel = code !== null && code !== undefined ? weatherCodeText(code) : 'Clear'

        if (typeof temp === 'number') {
          currentWeather.value = `${Math.round(temp)}°C ${weatherLabel}`
        } else {
          currentWeather.value = weatherLabel || 'Weather unavailable'
        }

        // Process location - display city and country if available
        const place = locationData?.location
        if (place && (place.city || place.name || place.country)) {
          const city = place.city || place.name || ''
          const country = place.country || ''
          const parts = [city, country].filter(p => p && p.trim())
          currentLocation.value = parts.length > 0 ? parts.join(', ') : 'Location unknown'
        } else {
          currentLocation.value = 'Location unavailable'
        }
        console.log('[Weather] Final values:', { weather: currentWeather.value, location: currentLocation.value })
      } catch (error) {
        console.warn('[Weather] Error:', error?.message || error)
        // If it's a timeout or network error, try again in 30 seconds
        currentWeather.value = 'Weather loading...'
        currentLocation.value = 'Location loading...'
      }
    }

    const loadCurrentWeather = async () => {
      if (!navigator?.geolocation) {
        console.warn('[Weather] Geolocation not available')
        // Fallback to user's profile location if available
        const userCity = currentUserProfile.value?.city
        const userCountry = currentUserProfile.value?.country
        if (userCity || userCountry) {
          const parts = [userCity, userCountry].filter(p => p && p.trim())
          currentLocation.value = parts.length > 0 ? parts.join(', ') : 'Location unavailable'
        } else {
          currentLocation.value = 'Location unavailable'
        }
        currentWeather.value = 'Weather unavailable'
        return
      }

      console.log('[Weather] Requesting geolocation...')

      await new Promise((resolve) => {
        navigator.geolocation.getCurrentPosition(
          async (position) => {
            const { latitude, longitude } = position.coords
            console.log('[Weather] Got coords:', { latitude, longitude })
            try {
              await fetchWeatherAndLocation(latitude, longitude)
            } catch (e) {
              console.error('[Weather] Fetch error:', e)
              // Fallback to profile location on error
              const userCity = currentUserProfile.value?.city
              const userCountry = currentUserProfile.value?.country
              if (userCity || userCountry) {
                const parts = [userCity, userCountry].filter(p => p && p.trim())
                currentLocation.value = parts.length > 0 ? parts.join(', ') : 'Location unavailable'
              } else {
                currentLocation.value = 'Location unavailable'
              }
              currentWeather.value = 'Weather unavailable'
            }
            resolve()
          },
          (error) => {
            console.warn('[Weather] Geolocation error:', error?.code, error?.message)
            // Fallback to user's profile location
            const userCity = currentUserProfile.value?.city
            const userCountry = currentUserProfile.value?.country
            if (userCity || userCountry) {
              const parts = [userCity, userCountry].filter(p => p && p.trim())
              currentLocation.value = parts.length > 0 ? `${parts.join(', ')} (profile)` : 'Location unavailable'
            } else if (error?.code === 1) {
              currentLocation.value = 'Location permission denied'
            } else if (error?.code === 2) {
              currentLocation.value = 'Location unavailable'
            } else if (error?.code === 3) {
              currentLocation.value = 'Location timeout'
            } else {
              currentLocation.value = 'Location error'
            }
            currentWeather.value = 'Weather unavailable'
            resolve()
          },
          { timeout: 15000, enableHighAccuracy: false }
        )
      })
    }

    const refreshWeatherAndLocation = async () => {
      if (weatherRefreshInFlight.value) {
        return
      }

      weatherRefreshInFlight.value = true
      try {
        await loadCurrentWeather()
      } finally {
        weatherRefreshInFlight.value = false
      }
    }

    const settingsStorageKey = computed(() => `chatapp.settings.${currentUserId.value || 'guest'}`)
    const chatUsersStorageKey = computed(() => `chatapp.chatUsers.${currentUserId.value || 'guest'}`)
    const groupStorageKey = computed(() => `chatapp.groups.${currentUserId.value || 'guest'}`)

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
      if (!userId || sameUserId(userId, currentUserId.value)) {
        return
      }

      const existing = knownChatUserIds.value.find((id) => sameUserId(id, userId))
      if (existing !== undefined) {
        knownChatUserIds.value = [userId, ...knownChatUserIds.value.filter((id) => !sameUserId(id, userId))]
      } else {
        knownChatUserIds.value = [userId, ...knownChatUserIds.value]
      }

      knownChatUserIds.value = knownChatUserIds.value.slice(0, 200)
      persistKnownChatUsers()
    }

    const clearIncomingCallTimer = () => {
      if (incomingCallTimeoutId.value) {
        clearTimeout(incomingCallTimeoutId.value)
        incomingCallTimeoutId.value = null
      }
    }

    const clearIncomingCallState = () => {
      clearIncomingCallTimer()
      showIncomingCallModal.value = false
      incomingCall.value = null
      isCallActionPending.value = false
    }

    const clearOutgoingCallState = () => {
      showOutgoingCallModal.value = false
      outgoingCall.value = null
      isCallActionPending.value = false
    }

    const parseGroupMembers = (members) => {
      if (Array.isArray(members)) {
        return members
          .map((member) => {
            if (member && typeof member === 'object') {
              return {
                id: member.id ?? member.user_id,
                name: member.name || member.user?.name || '',
              }
            }

            return { id: member, name: '' }
          })
          .filter((member) => member.id !== null && member.id !== undefined && member.id !== '')
      }

      if (typeof members === 'string') {
        try {
          const parsed = JSON.parse(members)
          return parseGroupMembers(parsed)
        } catch {
          return []
        }
      }

      return []
    }

    const formatProfileDate = (value) => {
      if (!value) {
        return ''
      }

      const parsed = new Date(value)
      if (Number.isNaN(parsed.getTime())) {
        return ''
      }

      return parsed.toLocaleDateString([], {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
      })
    }

    const getUserPresenceText = (user) => {
      if (!user) {
        return 'Offline'
      }

      if (user.is_online || user.online || user.status === 'online') {
        return 'Online'
      }

      const lastSeen = user.last_seen_at || user.last_seen || user.last_active_at
      if (!lastSeen) {
        return 'Offline'
      }

      const parsed = new Date(lastSeen)
      if (Number.isNaN(parsed.getTime())) {
        return 'Offline'
      }

      return `Last seen ${parsed.toLocaleString([], {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      })}`
    }

    const toggleMenu = () => {
      showHeaderMenu.value = !showHeaderMenu.value
    }

    const openSettings = () => {
      showHeaderMenu.value = false
      profileForm.value.name = currentUserName.value || profileForm.value.name || ''
      profileForm.value.accentColor = settings.value.accentColor || defaultSettings.accentColor
      showSettings.value = true
    }

    const closeSettings = () => {
      showSettings.value = false
    }

    const resetSettings = () => {
      settings.value = { ...defaultSettings }
      profileForm.value.accentColor = defaultSettings.accentColor
      profileForm.value.image = null
      profileForm.value.name = currentUserName.value || ''
      profilePreviewUrl.value = ''
      localStorage.removeItem(settingsStorageKey.value)
    }

    const handleImageUpload = (event) => {
      const file = event?.target?.files?.[0]
      profileForm.value.image = file || null

      if (profilePreviewUrl.value) {
        URL.revokeObjectURL(profilePreviewUrl.value)
        profilePreviewUrl.value = ''
      }

      if (file) {
        profilePreviewUrl.value = URL.createObjectURL(file)
      }
    }

    const closeUserDetails = () => {
      showUserDetails.value = false
      isUserDetailsLoading.value = false
      userDetailsError.value = ''
    }

    const userId = async (id) => {
      if (!id) {
        return null
      }

      try {
        const response = await allService.getUserProfile(id)
        const profile = response?.user || response?.data || response
        if (!profile || !profile.id) {
          return null
        }

        return profile
      } catch (error) {
        console.warn('Unable to fetch user profile:', error?.response?.data || error?.message || error)
        return null
      }
    }

    const updateProfile = async () => {
      const nextName = (profileForm.value.name || '').trim()
      const nextAccent = profileForm.value.accentColor || defaultSettings.accentColor

      settings.value = { accentColor: nextAccent }
      localStorage.setItem(settingsStorageKey.value, JSON.stringify(settings.value))

      const shouldSendProfileUpdate = Boolean(nextName || profileForm.value.image)
      if (!shouldSendProfileUpdate) {
        closeSettings()
        return
      }

      const formData = new FormData()
      if (nextName) {
        formData.append('name', nextName)
      }
      if (profileForm.value.phone) {
        formData.append('phone', profileForm.value.phone)
      }
      if (profileForm.value.address) {
        formData.append('address', profileForm.value.address)
      }
      if (profileForm.value.city) {
        formData.append('city', profileForm.value.city)
      }
      if (profileForm.value.country) {
        formData.append('country', profileForm.value.country)
      }
      if (profileForm.value.image) {
        formData.append('image', profileForm.value.image)
      }

      try {
        const response = await allService.updateProfile(formData)
        const updatedUser = response?.user || response?.data || response

        if (updatedUser?.name) {
          currentUserName.value = updatedUser.name
          profileForm.value.name = updatedUser.name
        }

        currentUserProfile.value = {
          ...(currentUserProfile.value || {}),
          ...updatedUser,
        }

        profileForm.value.phone = updatedUser?.phone ?? profileForm.value.phone
        profileForm.value.address = updatedUser?.address ?? profileForm.value.address
        profileForm.value.city = updatedUser?.city ?? profileForm.value.city
        profileForm.value.country = updatedUser?.country ?? profileForm.value.country

        const nextPhoto = getUserPhoto(updatedUser)
        if (nextPhoto) {
          currentUserPhoto.value = nextPhoto
        }

        if (currentUserId.value) {
          userView.value = userView.value.map((user) => {
            if (!sameUserId(user?.id, currentUserId.value)) {
              return user
            }

            return {
              ...user,
              ...updatedUser,
              profile_photo_url: nextPhoto || user.profile_photo_url,
            }
          })
        }
      } catch (error) {
        console.error('Profile update failed:', error?.response?.data || error?.message || error)
      } finally {
        closeSettings()
      }
    }

    const fetchUserId = async () => {
      const response = await allService.getUser()
      const authUser = response?.user || response?.data || response

      currentUserId.value = authUser?.id || null
      currentUserName.value = authUser?.name || authUser?.username || 'User'
      currentUserPhoto.value = getUserPhoto(authUser)
      currentUserProfile.value = authUser || null

      profileForm.value.name = currentUserName.value || ''
      profileForm.value.phone = authUser?.phone || ''
      profileForm.value.address = authUser?.address || ''
      profileForm.value.city = authUser?.city || ''
      profileForm.value.country = authUser?.country || ''

      loadSettings()
      loadKnownChatUsers()
      await loadGroups()
    }

    const fetchUserData = async (query = '') => {
      const requestId = ++latestSearchRequestId.value

      try {
        const response = await allService.searchUser(query, 1)
        const users = Array.isArray(response)
          ? response
          : (
              Array.isArray(response?.users)
                ? response.users
                : (
                    Array.isArray(response?.data)
                      ? response.data
                      : (
                          Array.isArray(response?.results)
                            ? response.results
                            : (
                                Array.isArray(response?.data?.data)
                                  ? response.data.data
                                  : []
                              )
                        )
                  )
            )

        if (requestId !== latestSearchRequestId.value) {
          return
        }

        const uniqueUsers = []
        const seen = new Set()

        users.forEach((user) => {
          const id = user?.id
          if (!id || sameUserId(id, currentUserId.value)) {
            return
          }

          const key = String(id)
          if (seen.has(key)) {
            return
          }

          seen.add(key)
          uniqueUsers.push({ ...user })
        })

        const trimmedQuery = query.trim()
        const knownIds = knownChatUserIds.value

        if (!trimmedQuery) {
          if (!knownIds.length) {
            userView.value = []
            return
          }

          const recentOnlyUsers = uniqueUsers.filter((user) =>
            knownIds.some((id) => sameUserId(id, user.id))
          )

          recentOnlyUsers.sort((a, b) => {
            const aIndex = knownIds.findIndex((id) => sameUserId(id, a.id))
            const bIndex = knownIds.findIndex((id) => sameUserId(id, b.id))
            return aIndex - bIndex
          })

          userView.value = recentOnlyUsers
          return
        }

        userView.value = uniqueUsers
      } catch (error) {
        if (requestId !== latestSearchRequestId.value) {
          return
        }

        console.error('Error fetching user data:', error?.response?.data || error?.message || error)
        // Keep current list to avoid flashing an empty sidebar on transient search errors.
      }
    }

    const groups = ref([])
    const showGroupManager = ref(false)
    const editingGroupId = ref(null)
    const groupFormError = ref('')
    const groupFormSubmitting = ref(false)
    const groupForm = ref({
      name: '',
      members: [],
    })
    const showGroupSettings = ref(false)
    const editingGroupForSettings = ref(null)
    const showAddMemberForm = ref(false)
    const newGroupMembers = ref([])

    const loadLocalGroups = () => {
      try {
        const raw = localStorage.getItem(groupStorageKey.value)
        if (!raw) {
          return []
        }

        const parsed = JSON.parse(raw)
        return Array.isArray(parsed) ? parsed : []
      } catch {
        return []
      }
    }

    const persistGroups = () => {
      localStorage.setItem(groupStorageKey.value, JSON.stringify(groups.value))
    }

    const normalizeServerGroup = (group, localGroup = null) => {
      const members = Array.isArray(group?.members)
        ? group.members.map((member) => normalizeGroupMember({
            id: member?.id,
            name: member?.name,
            phone: member?.phone,
            city: member?.city,
            country: member?.country,
            profile_photo_path: member?.profile_photo_path,
            profile_photo_url: member?.profile_photo_url,
          })).filter(Boolean)
        : (localGroup?.members || [])

      return {
        id: group?.id,
        name: group?.name || localGroup?.name || 'Untitled Group',
        group_image_path: group?.group_image_path || group?.image || group?.avatar || localGroup?.group_image_path || '',
        profile_photo_url: group?.group_image_url || group?.image_url || localGroup?.profile_photo_url || '',
        created_by: group?.created_by ?? group?.creator?.id ?? localGroup?.created_by ?? null,
        created_at: group?.created_at || localGroup?.created_at || new Date().toISOString(),
        members,
        messages: Array.isArray(localGroup?.messages) ? localGroup.messages : [],
        type: 'group',
      }
    }

    const loadGroups = async () => {
      const localGroups = loadLocalGroups()

      try {
        const response = await allService.getGroups()
        const serverGroups = Array.isArray(response?.groups)
          ? response.groups
          : (Array.isArray(response?.data) ? response.data : [])

        groups.value = serverGroups.map((group) => {
          const localMatch = localGroups.find((item) => sameUserId(item?.id, group?.id))
          return normalizeServerGroup(group, localMatch)
        })
        persistGroups()
      } catch (error) {
        console.warn('Falling back to local groups:', error?.response?.data || error?.message || error)
        groups.value = localGroups
      }
    }

    const normalizeGroupMember = (member) => {
      if (!member) {
        return null
      }

      const id = member?.id ?? member
      const user = userView.value.find((item) => sameUserId(item?.id, id))

      return {
        id,
        name: member?.name || user?.name || `User #${id}`,
        phone: member?.phone || user?.phone || '',
        city: member?.city || user?.city || '',
        country: member?.country || user?.country || '',
      }
    }

    const mutualGroupsWithSelectedUser = computed(() => {
      const targetId = userDetails.value?.id
      if (!targetId) return []

      return groups.value.filter((group) =>
        (group?.members || []).some((member) => sameUserId(member?.id, targetId))
      )
    })

    const sameCountryMembers = computed(() => {
      const targetCountry = (userDetails.value?.country || '').trim().toLowerCase()
      const targetId = userDetails.value?.id
      if (!targetCountry) return []

      return userView.value
        .filter((user) => user?.id && !sameUserId(user.id, targetId) && (user?.country || '').trim().toLowerCase() === targetCountry)
        .slice(0, 20)
    })

    const memberLocationLabel = (member) => {
      const city = member?.city || 'City n/a'
      const country = member?.country || 'Country n/a'
      return `${city}, ${country}`
    }

    const isGroupConversation = (conversation) => Boolean(
      conversation?.type === 'group' ||
      conversation?.group_id ||
      conversation?.conversation_type === 'group' ||
      (typeof conversation?.id === 'string' && conversation.id.startsWith('group-'))
    )

    const formatGroupPreview = (group) => {
      const members = Array.isArray(group?.members) ? group.members : []
      const names = members.map((member) => member?.name).filter(Boolean).slice(0, 3)
      const extraCount = Math.max(0, members.length - names.length)
      const prefix = names.length ? names.join(', ') : 'No members yet'
      return extraCount > 0 ? `${prefix} +${extraCount} more · you` : `${prefix} · you`
    }

    const groupCandidateUsers = computed(() =>
      userView.value.filter((user) => !sameUserId(user?.id, currentUserId.value))
    )

    const filteredGroups = computed(() => {
      const term = searchQuery.value.trim().toLowerCase()

      return groups.value.filter((group) => {
        if (!term) return true

        const memberNames = (group.members || []).map((member) => member.name || '').join(' ')
        return (
          (group.name || '').toLowerCase().includes(term) ||
          memberNames.toLowerCase().includes(term)
        )
      })
    })

    const visibleGroups = computed(() => filteredGroups.value.slice(0, 10))

    const loadGroupMessages = async (groupId) => {
      const group = groups.value.find((item) => sameUserId(item.id, groupId))
      const localMessages = Array.isArray(group?.messages) ? group.messages : []
      chat.value.messages = localMessages
      scheduleScrollToBottom()

      try {
        const response = await allService.getGroupMessages(groupId)
        let serverMessages = []

        if (Array.isArray(response?.messages)) {
          serverMessages = response.messages
        } else if (Array.isArray(response?.data)) {
          serverMessages = response.data
        } else if (Array.isArray(response)) {
          serverMessages = response
        }

        if (!serverMessages.length) {
          return
        }

        const normalizedServerMessages = serverMessages
          .map((item) => normalizeMessagePayload(item, { group_id: groupId, conversation_type: 'group' }))
          .filter((item) => sameUserId(item.group_id, groupId) || item.conversation_type === 'group')

        const mergedMessages = [...localMessages]
        normalizedServerMessages.forEach((incoming) => {
          const exists = mergedMessages.some((item) => areMessagesEqual(item, incoming))
          if (!exists) {
            mergedMessages.push(incoming)
          }
        })

        chat.value.messages = mergedMessages
        scheduleScrollToBottom()

        const groupIndex = groups.value.findIndex((item) => sameUserId(item.id, groupId))
        if (groupIndex !== -1) {
          groups.value[groupIndex] = {
            ...groups.value[groupIndex],
            messages: mergedMessages,
          }
          persistGroups()
        }
      } catch (error) {
        console.warn('Unable to load group messages from API, using cached messages only:', error?.response?.data || error?.message || error)
      }
    }

    const saveGroup = async () => {
      const name = (groupForm.value.name || '').trim()
      const selectedIds = Array.from(
        new Set(
          (groupForm.value.members || [])
            .map((id) => normalizeId(id))
            .filter((id) => id !== null && id !== undefined && id !== '')
        )
      )
      const participantIds = currentUserId.value && !selectedIds.some((id) => sameUserId(id, currentUserId.value))
        ? [currentUserId.value, ...selectedIds]
        : selectedIds

      if (!name) {
        groupFormError.value = 'Please enter a group name.'
        return
      }

      if (selectedIds.length < 1) {
        groupFormError.value = 'Please select at least one member.'
        return
      }

      groupFormError.value = ''
      groupFormSubmitting.value = true

      try {
        if (editingGroupId.value) {
          await allService.updateGroup(editingGroupId.value, { name })
        } else {
          await allService.createGroup({
            name,
            user_ids: participantIds,
            member_ids: participantIds,
            members: participantIds,
            users: participantIds,
          })
        }

        await loadGroups()
        closeGroupManager()
      } catch (error) {
        const responseData = error?.response?.data
        const message =
          responseData?.message ||
          Object.values(responseData?.errors || {}).flat().find(Boolean) ||
          error?.message ||
          'Failed to save group.'

        groupFormError.value = message
        console.error('Failed to save group:', error?.response?.data || error?.message || error)
      } finally {
        groupFormSubmitting.value = false
      }
    }

    const openGroupManager = (group = null) => {
      showHeaderMenu.value = false
      editingGroupId.value = group?.id || null
      groupFormError.value = ''

      if (group) {
        groupForm.value = {
          name: group.name || '',
          members: (group.members || [])
            .filter((member) => !sameUserId(member.id, currentUserId.value))
            .map((member) => member.id),
        }
      } else {
        groupForm.value = { name: '', members: [] }
      }

      showGroupManager.value = true
    }

    const closeGroupManager = () => {
      showGroupManager.value = false
      editingGroupId.value = null
      groupFormError.value = ''
      groupFormSubmitting.value = false
      groupForm.value = { name: '', members: [] }
    }

    const openGroupSettings = (group) => {
      if (!group) return

      const freshGroup = groups.value.find((item) => sameUserId(item.id, group.id)) || group
      editingGroupForSettings.value = freshGroup
      groupSettingsForm.value = {
        name: freshGroup?.name || '',
        image: null,
      }
      groupSettingsPreviewUrl.value = ''
      showAddMemberForm.value = false
      newGroupMembers.value = []
      showGroupSettings.value = true
    }

    const closeGroupSettings = () => {
      showGroupSettings.value = false
      editingGroupForSettings.value = null
      showAddMemberForm.value = false
      newGroupMembers.value = []
      groupSettingsForm.value = { name: '', image: null }
      if (groupSettingsPreviewUrl.value) {
        URL.revokeObjectURL(groupSettingsPreviewUrl.value)
      }
      groupSettingsPreviewUrl.value = ''
    }

    const handleGroupImageUpload = (event) => {
      const file = event?.target?.files?.[0] || null
      groupSettingsForm.value.image = file

      if (groupSettingsPreviewUrl.value) {
        URL.revokeObjectURL(groupSettingsPreviewUrl.value)
      }

      groupSettingsPreviewUrl.value = file ? URL.createObjectURL(file) : ''
    }

    const saveGroupSettings = async () => {
      if (!editingGroupForSettings.value?.id) {
        return
      }

      const nextName = (groupSettingsForm.value.name || '').trim()
      if (!nextName) {
        groupFormError.value = 'Group name is required.'
        return
      }

      groupFormSubmitting.value = true
      groupFormError.value = ''

      try {
        const payload = new FormData()
        payload.append('name', nextName)
        if (groupSettingsForm.value.image) {
          payload.append('image', groupSettingsForm.value.image)
        }

        await allService.updateGroup(editingGroupForSettings.value.id, payload)
        await loadGroups()

        const updated = groups.value.find((item) => sameUserId(item.id, editingGroupForSettings.value.id))
        if (updated) {
          editingGroupForSettings.value = updated

          if (selectedUser.value && sameUserId(selectedUser.value.id, updated.id)) {
            selectedUser.value = { ...selectedUser.value, ...updated, type: 'group' }
          }
        }
      } catch (error) {
        groupFormError.value = error?.response?.data?.message || error?.message || 'Unable to save group settings.'
      } finally {
        groupFormSubmitting.value = false
      }
    }

    const openGroupDetails = async (group) => {
      if (!group) return

      const localGroup = groups.value.find((item) => sameUserId(item.id, group.id)) || group
      activeGroupDetails.value = { ...localGroup, type: 'group' }
      showGroupDetails.value = true

      try {
        const details = await allService.getGroup(group.id)
        const detailsGroup = details?.group || details?.data?.group || details?.data || details
        if (!detailsGroup?.id) {
          return
        }

        const merged = normalizeServerGroup(detailsGroup, localGroup)
        const index = groups.value.findIndex((item) => sameUserId(item.id, merged.id))
        if (index === -1) {
          groups.value.unshift(merged)
        } else {
          groups.value[index] = { ...groups.value[index], ...merged }
        }

        persistGroups()
        activeGroupDetails.value = { ...merged, type: 'group' }

        if (selectedUser.value && sameUserId(selectedUser.value.id, merged.id) && isGroupConversation(selectedUser.value)) {
          selectedUser.value = { ...selectedUser.value, ...merged, type: 'group' }
        }
      } catch {
        // Keep current modal data if detail endpoint is unavailable.
      }
    }

    const closeGroupDetails = () => {
      showGroupDetails.value = false
      activeGroupDetails.value = null
    }

    const openGroupChatFromDetails = async () => {
      if (!activeGroupDetails.value) return

      await selectGroup(activeGroupDetails.value)
      closeGroupDetails()
    }

    const openGroupSettingsFromDetails = () => {
      if (!activeGroupDetails.value) return

      const targetGroup = activeGroupDetails.value
      closeGroupDetails()
      openGroupSettings(targetGroup)
    }

    const availableUsersForGroup = computed(() => {
      const currentMembers = new Set((editingGroupForSettings.value?.members || []).map((member) => String(member.id)))
      return userView.value.filter((user) => !currentMembers.has(String(user.id)) && !sameUserId(user.id, currentUserId.value))
    })

    const addMembersToGroup = async () => {
      if (!editingGroupForSettings.value || !newGroupMembers.value.length) return

      try {
        await allService.addGroupMembers(editingGroupForSettings.value.id, newGroupMembers.value)
        await loadGroups()

        const updatedGroup = groups.value.find((group) => sameUserId(group.id, editingGroupForSettings.value.id))
        if (updatedGroup) {
          editingGroupForSettings.value = updatedGroup

          if (selectedUser.value && sameUserId(selectedUser.value.id, updatedGroup.id)) {
            selectedUser.value = { ...selectedUser.value, ...updatedGroup, type: 'group' }
          }
        }

        showAddMemberForm.value = false
        newGroupMembers.value = []
      } catch (error) {
        console.error('Failed to add group members:', error?.response?.data || error?.message || error)
      }
    }

    const removeMemberFromGroup = async (memberId) => {
      if (!editingGroupForSettings.value || sameUserId(memberId, currentUserId.value)) return

      try {
        await allService.removeGroupMember(editingGroupForSettings.value.id, memberId)
        await loadGroups()

        const updatedGroup = groups.value.find((group) => sameUserId(group.id, editingGroupForSettings.value.id))
        editingGroupForSettings.value = updatedGroup || null

        if (selectedUser.value && updatedGroup && sameUserId(selectedUser.value.id, updatedGroup.id)) {
          selectedUser.value = { ...selectedUser.value, ...updatedGroup, type: 'group' }
        }
      } catch (error) {
        console.error('Failed to remove member:', error?.response?.data || error?.message || error)
      }
    }

    const deleteGroup = async () => {
      if (!editingGroupForSettings.value) return

      const targetId = editingGroupForSettings.value.id
      try {
        await allService.deleteGroup(targetId)
        await loadGroups()
        closeGroupSettings()

        if (selectedUser.value && sameUserId(selectedUser.value.id, targetId)) {
          selectedUser.value = null
          chat.value.messages = []
        }
      } catch (error) {
        console.error('Failed to delete group:', error?.response?.data || error?.message || error)
      }
    }

    const normalizeMessagePayload = (payload, fallback = {}) => {
      const source = typeof payload?.message === 'object' && payload.message !== null ? payload.message : payload
      const attachmentSource = source?.attachment || source?.file || source?.media || fallback?.attachment || null

      return {
        id: source?.id ?? source?.message_id ?? fallback?.id ?? null,
        from_id: source?.from_id ?? source?.sender_id ?? source?.user_id ?? fallback?.from_id ?? null,
        to_id: source?.to_id ?? source?.receiver_id ?? fallback?.to_id ?? null,
        group_id: source?.group_id ?? source?.groupId ?? fallback?.group_id ?? null,
        group_name: source?.group_name ?? source?.groupName ?? fallback?.group_name ?? '',
        group_members: parseGroupMembers(source?.group_members ?? source?.members ?? fallback?.group_members ?? fallback?.members),
        conversation_type: source?.conversation_type ?? source?.conversationType ?? fallback?.conversation_type ?? '',
        sender_name: source?.sender_name ?? source?.from_name ?? source?.from_user_name ?? fallback?.sender_name ?? '',
        receiver_name: source?.receiver_name ?? source?.to_name ?? source?.to_user_name ?? fallback?.receiver_name ?? '',
        body: source?.body ?? source?.message ?? source?.text ?? fallback?.body ?? '',
        created_at: source?.created_at ?? source?.date ?? fallback?.created_at ?? new Date().toISOString(),
        attachment: normalizeAttachment(attachmentSource, {
          ...(fallback?.attachment || {}),
          name: source?.attachment_name ?? source?.file_name ?? source?.original_name ?? fallback?.attachment_name ?? '',
          mimeType: source?.attachment_mime_type ?? source?.attachment_mime ?? source?.mime_type ?? fallback?.attachment_mime_type ?? '',
          size: source?.attachment_size ?? source?.file_size ?? fallback?.attachment_size ?? 0,
        }),
        attachment_url: source?.attachment_url ?? source?.attachment ?? source?.file_url ?? source?.media_url ?? fallback?.attachment_url ?? '',
        attachment_name: source?.attachment_name ?? source?.file_name ?? source?.original_name ?? fallback?.attachment_name ?? '',
        attachment_mime_type: source?.attachment_mime_type ?? source?.attachment_mime ?? source?.mime_type ?? fallback?.attachment_mime_type ?? '',
        attachment_kind: source?.attachment_kind ?? fallback?.attachment_kind ?? '',
        attachment_size: source?.attachment_size ?? source?.file_size ?? fallback?.attachment_size ?? 0,
        _optimistic: Boolean(fallback?._optimistic),
      }
    }

    const sendGroupMessage = async () => {
      const group = groups.value.find((item) => sameUserId(item.id, selectedUser.value?.id))
      if (!group) return

      const text = message.value.trim()
      const attachment = selectedAttachment.value
      const hasAttachment = Boolean(attachment?.file)
      const effectiveText = text || (hasAttachment ? (attachment?.name || 'Attachment') : '')

      const attachmentValidation = hasAttachment ? validateAttachmentFile(attachment.file) : { valid: true }
      if (hasAttachment && !attachmentValidation.valid) {
        attachmentValidationError.value = attachmentValidation.message
        clearSelectedAttachment()
        message.value = text
        return
      }

      if (!text && !hasAttachment) {
        message.value = ''
        return
      }

      const optimisticAttachment = hasAttachment
        ? {
            url: attachment.previewUrl || '',
            name: attachment.name,
            mimeType: attachment.mimeType,
            kind: attachment.kind,
            size: attachment.size,
          }
        : null

      const optimisticMessage = normalizeMessagePayload(
        {
          id: `tmp-${Date.now()}-${Math.random().toString(36).slice(2)}`,
          from_id: currentUserId.value,
          group_id: group.id,
          group_name: group.name,
          conversation_type: 'group',
          body: effectiveText,
          created_at: new Date().toISOString(),
          attachment: optimisticAttachment,
        },
        { _optimistic: true, attachment: optimisticAttachment }
      )

      registerPendingOutgoingMessage(optimisticMessage)
      chat.value.messages.push(optimisticMessage)
      scheduleScrollToBottom()
      message.value = ''

      try {
        const payload = hasAttachment
          ? (() => {
              const formData = new FormData()
              formData.append('group_id', String(group.id))
              formData.append('group_name', group.name || '')
              formData.append('conversation_type', 'group')
              formData.append('message', effectiveText)
              formData.append('body', effectiveText)
              formData.append('attachment', attachment.file)
              formData.append('attachment_name', attachment.name)
              formData.append('attachment_mime_type', attachment.mimeType || '')
              formData.append('attachment_kind', attachment.kind || '')
              formData.append('attachment_size', String(attachment.size || 0))
              return formData
            })()
          : {
              group_id: Number(group.id),
              group_name: group.name,
              conversation_type: 'group',
              message: effectiveText,
              body: effectiveText,
            }

        const response = await allService.sendGroupMessage(Number(group.id), payload)
        const serverMessage = normalizeMessagePayload(response?.message ?? response?.data ?? response, optimisticMessage)

        const finalMessage = {
          ...optimisticMessage,
          ...serverMessage,
          group_id: group.id,
          group_name: group.name,
          conversation_type: 'group',
          _optimistic: false,
        }

        chat.value.messages = chat.value.messages.map((item) => (item.id === optimisticMessage.id ? finalMessage : item))
        scheduleScrollToBottom()

        const index = groups.value.findIndex((item) => sameUserId(item.id, group.id))
        if (index !== -1) {
          const existing = groups.value[index]
          const nextMessages = [...(existing.messages || [])]
          if (!nextMessages.some((item) => areMessagesEqual(item, finalMessage))) {
            nextMessages.push(finalMessage)
          }
          groups.value[index] = { ...existing, messages: nextMessages }
          persistGroups()
        }

        clearSelectedAttachment()
      } catch (error) {
        clearPendingOutgoingMessage(optimisticMessage)
        chat.value.messages = chat.value.messages.filter((item) => item.id !== optimisticMessage.id)
        message.value = text
        console.error('Group send failed:', error?.response?.data || error?.message || error)
      }
    }

    const sendMessage = async () => {
      if (!selectedUser.value || isSendingMessage.value) {
        return
      }

      isSendingMessage.value = true
      let text = ''
      let optimisticMessage = null

      try {
        if (isGroupConversation(selectedUser.value)) {
          await sendGroupMessage()
          return
        }

        text = message.value.trim()
        const attachment = selectedAttachment.value
        const hasAttachment = Boolean(attachment?.file)
        const effectiveText = text || (hasAttachment ? (attachment?.name || 'Attachment') : '')

        if (!text && !hasAttachment) {
          message.value = ''
          return
        }

        const attachmentValidation = hasAttachment ? validateAttachmentFile(attachment.file) : { valid: true }
        if (hasAttachment && !attachmentValidation.valid) {
          attachmentValidationError.value = attachmentValidation.message
          clearSelectedAttachment()
          message.value = text
          return
        }

        const optimisticAttachment = hasAttachment
          ? {
              url: attachment.previewUrl || '',
              name: attachment.name,
              mimeType: attachment.mimeType,
              kind: attachment.kind,
              size: attachment.size,
            }
          : null

        optimisticMessage = normalizeMessagePayload(
          {
            id: `tmp-${Date.now()}-${Math.random().toString(36).slice(2)}`,
            from_id: currentUserId.value,
            to_id: selectedUser.value.id,
            body: effectiveText,
            created_at: new Date().toISOString(),
            attachment: optimisticAttachment,
          },
          { _optimistic: true, attachment: optimisticAttachment }
        )

        registerPendingOutgoingMessage(optimisticMessage)
        chat.value.messages.push(optimisticMessage)
        scheduleScrollToBottom()
        message.value = ''

        const payload = hasAttachment
          ? (() => {
              const formData = new FormData()
              formData.append('user_id', String(selectedUser.value.id))
              formData.append('message', effectiveText)
              formData.append('body', effectiveText)
              formData.append('attachment', attachment.file)
              formData.append('attachment_name', attachment.name)
              formData.append('attachment_mime_type', attachment.mimeType || '')
              formData.append('attachment_kind', attachment.kind || '')
              formData.append('attachment_size', String(attachment.size || 0))
              return formData
            })()
          : {
              user_id: selectedUser.value.id,
              message: effectiveText,
            }

        const response = await allService.sendMessages(payload)
        const serverMessage = normalizeMessagePayload(response?.message ?? response?.data ?? response, optimisticMessage)

        chat.value.messages = chat.value.messages.map((item) =>
          item.id === optimisticMessage.id
            ? {
                ...optimisticMessage,
                ...serverMessage,
                _optimistic: false,
              }
            : item
        )
        scheduleScrollToBottom()

        rememberChattedUser(selectedUser.value.id)
        clearSelectedAttachment()
      } catch (error) {
        if (optimisticMessage) {
          clearPendingOutgoingMessage(optimisticMessage)
          chat.value.messages = chat.value.messages.filter((item) => item.id !== optimisticMessage.id)
        }
        message.value = text
        console.error('Send failed:', error?.response?.data || error?.message || error)
      } finally {
        isSendingMessage.value = false
      }
    }

    const subscribeToRealtimeMessages = (authUserId) => {
      if (!authUserId) return

      activeChannelNames.value.forEach((channelName) => {
        echo.leave(channelName)
      })

      const userChannel = `chat.${authUserId}`
      activeChannelNames.value = [userChannel]

      const handleRealtimeEvent = (event) => {
        const incoming = normalizeMessagePayload(event)

        if (isGroupConversation(incoming)) {
          upsertGroupFromIncomingMessage(incoming)
          const isSelectedGroup = selectedUser.value && isGroupConversation(selectedUser.value) && sameUserId(selectedUser.value.id, incoming.group_id)
          if (isSelectedGroup) {
            appendMessage(incoming)
          }
          return
        }

        const isCurrentConversation = selectedUser.value && isMessageBetweenUsers(incoming, currentUserId.value, selectedUser.value.id)
        if (isCurrentConversation) {
          const otherUserId = sameUserId(incoming.from_id, currentUserId.value) ? incoming.to_id : incoming.from_id
          rememberChattedUser(otherUserId)
          appendMessage(incoming)
        }
      }

      const bindEvents = (channel) => {
        ;['.chat.message', 'chat.message', '.message.sent', 'message.sent', 'MessageSent', '.MessageSent', '.my-event', 'my-event']
          .forEach((eventName) => channel.listen(eventName, handleRealtimeEvent))

        channel.error((err) => console.error('Echo channel error:', err))
      }

      bindEvents(echo.private(userChannel))

      // Some deployments accidentally broadcast on public channels; listen there too as a compatibility fallback.
      bindEvents(echo.channel(userChannel))
    }

    const loadCallHistoryForUser = async (targetUserId) => {
      if (!targetUserId) {
        selectedCallHistory.value = []
        return []
      }

      try {
        const items = await CallService.getCallHistory(targetUserId)
        selectedCallHistory.value = Array.isArray(items) ? items : []
        return selectedCallHistory.value
      } catch {
        selectedCallHistory.value = []
        return []
      }
    }

    const safeCallType = (value) => (value === 'video' ? 'video' : 'audio')

    const inferCallTypeFromDescription = (description) => {
      const normalized = toSessionDescription(description, 'offer')
      if (!normalized?.sdp) {
        return 'audio'
      }

      return /m=video/i.test(normalized.sdp) ? 'video' : 'audio'
    }

    const readSignalCandidate = (signal) => signal?.candidate || signal?.ice || signal?.iceCandidate || null

    const readSignalDescription = (signal, key) => {
      const fromKey = signal?.[key]
      if (fromKey && typeof fromKey === 'object') {
        return fromKey
      }

      if (typeof fromKey === 'string' && fromKey.trim()) {
        return {
          type: key,
          sdp: fromKey,
        }
      }

      const nested = signal?.data?.[key] || signal?.payload?.[key]
      if (nested && typeof nested === 'object') {
        return nested
      }

      if (typeof nested === 'string' && nested.trim()) {
        return {
          type: key,
          sdp: nested,
        }
      }

      const fallback = key === 'offer' ? signal?.sdp_offer : signal?.sdp_answer
      if (fallback && typeof fallback === 'object') {
        return fallback
      }

      const sdp = signal?.sdp
      if (typeof sdp === 'string' && sdp.trim()) {
        return {
          type: key,
          sdp,
        }
      }

      return null
    }

    const readSignalUserId = (signal, keys = []) => {
      for (const key of keys) {
        const value = signal?.[key]
        if (value !== undefined && value !== null && value !== '') {
          return value
        }
      }
      return null
    }

    const toSessionDescription = (description, fallbackType) => {
      if (!description) {
        return null
      }

      if (description instanceof RTCSessionDescription) {
        return description
      }

      if (description?.type && description?.sdp) {
        return new RTCSessionDescription({ type: description.type, sdp: description.sdp })
      }

      if (typeof description === 'string' && description.trim()) {
        return new RTCSessionDescription({ type: fallbackType, sdp: description })
      }

      return null
    }

    const buildCallHistoryEntry = ({ peerId = null, direction = 'outgoing', status = 'ended', startedAt = null, endedAt = null, meta = {} } = {}) => {
      const startValue = startedAt || callStartedAt.value || new Date().toISOString()
      const endValue = endedAt || new Date().toISOString()
      const startTime = new Date(startValue).getTime()
      const endTime = new Date(endValue).getTime()
      const durationSeconds = Number.isFinite(startTime) && Number.isFinite(endTime) && endTime > startTime
        ? Math.round((endTime - startTime) / 1000)
        : 0

      return {
        user_id: peerId,
        direction,
        status,
        started_at: startValue,
        ended_at: endValue,
        duration_seconds: durationSeconds,
        meta,
      }
    }

    const saveCallHistoryEntry = async (entry) => {
      try {
        await CallService.saveCallHistory(entry)

        if (selectedUser.value && !isGroupConversation(selectedUser.value)) {
          await loadCallHistoryForUser(selectedUser.value.id)
        }
      } catch (error) {
        console.warn('Unable to save call history entry:', error?.response?.data || error?.message || error)
      }
    }

    const flushQueuedIceCandidates = async (connection) => {
      if (!connection || !pendingIceCandidates.value.length) {
        return
      }

      const queued = [...pendingIceCandidates.value]
      pendingIceCandidates.value = []

      for (const candidate of queued) {
        try {
          await connection.addIceCandidate(new RTCIceCandidate(candidate))
        } catch (error) {
          console.warn('Failed to apply queued ICE candidate:', error)
        }
      }
    }

    const handleCallIncomingSignal = async (payload = {}) => {
      const fromId = readSignalUserId(payload, ['from_id', 'fromId', 'user_id', 'sender_id', 'caller_id', 'peer_id'])
      const offer = readSignalDescription(payload, 'offer')
      if (!fromId || !offer) {
        return
      }

      incomingCall.value = {
        ...payload,
        fromId,
        offer,
        callType: safeCallType(payload?.call_type || payload?.type_hint || inferCallTypeFromDescription(offer)),
      }

      showIncomingCallModal.value = true
      clearOutgoingCallState()
      clearIncomingCallTimer()

      incomingCallTimeoutId.value = setTimeout(() => {
        if (showIncomingCallModal.value) {
          rejectIncomingCall('missed')
        }
      }, 30000)
    }

    const handleCallAnsweredSignal = async (payload = {}) => {
      const signalPeerId = readSignalUserId(payload, ['from_id', 'fromId', 'user_id', 'sender_id', 'caller_id', 'peer_id', 'callee_id', 'to_id', 'toId', 'receiver_id'])
      if (signalPeerId && partnerId.value && !sameUserId(signalPeerId, partnerId.value)) {
        return
      }

      const answer = readSignalDescription(payload, 'answer')
      if (!answer) {
        return
      }

      try {
        const connection = await ensurePeerConnection({ callType: outgoingCall.value?.callType || 'audio' })
        if (!connection) {
          return
        }

        await connection.setRemoteDescription(toSessionDescription(answer, 'answer'))
        await flushQueuedIceCandidates(connection)

        outgoingCall.value = {
          ...(outgoingCall.value || {}),
          status: 'Connected',
        }
        showOutgoingCallModal.value = false
      } catch (error) {
        console.error('Failed to apply call answer:', error)
      }
    }

    const handleCallIceSignal = async (payload = {}) => {
      const candidate = readSignalCandidate(payload)
      if (!candidate) {
        return
      }

      const connection = await ensurePeerConnection({
        callType: outgoingCall.value?.callType || incomingCall.value?.callType || 'audio',
      })
      if (!connection) {
        return
      }

      if (!connection.remoteDescription?.type) {
        pendingIceCandidates.value.push(candidate)
        return
      }

      try {
        await connection.addIceCandidate(new RTCIceCandidate(candidate))
      } catch (error) {
        console.warn('Failed to add ICE candidate:', error)
      }
    }

    const handleCallEndedSignal = async (payload = {}) => {
      const endedWith = readSignalUserId(payload, ['from_id', 'fromId', 'user_id', 'sender_id']) || partnerId.value
      const status = (payload?.status || payload?.reason || 'ended').toString().toLowerCase()
      const direction = outgoingCall.value ? 'outgoing' : 'incoming'

      if (endedWith) {
        await saveCallHistoryEntry(
          buildCallHistoryEntry({
            peerId: endedWith,
            direction,
            status,
            meta: {
              ended_by_remote: true,
            },
          })
        )
      }

      clearCallSessionState()
      teardownPeerConnection()
      pendingIceCandidates.value = []
    }

    const startCall = async (toId, options = {}) => {
      if (!toId) {
        return
      }

      const callType = safeCallType(options?.callType)

      isCallActionPending.value = true
      try {
        const connection = await ensurePeerConnection({ callType })
        if (!connection) {
          return
        }

        partnerId.value = toId
        callStartedAt.value = new Date().toISOString()
        clearIncomingCallState()
        showOutgoingCallModal.value = true
        outgoingCall.value = {
          toId,
          status: 'Ringing',
          callType,
        }

        const offer = await connection.createOffer({
          offerToReceiveAudio: true,
          offerToReceiveVideo: callType === 'video',
        })
        await connection.setLocalDescription(offer)

        await CallService.startCall(toId, {
          type: offer.type,
          sdp: offer.sdp,
          call_type: callType,
        })
      } catch (error) {
        console.error('Unable to start call:', error?.response?.data || error?.message || error)
        await saveCallHistoryEntry(
          buildCallHistoryEntry({
            peerId: toId,
            direction: 'outgoing',
            status: 'failed',
            meta: { call_type: callType },
          })
        )
        clearCallSessionState()
        teardownPeerConnection()
      } finally {
        isCallActionPending.value = false
      }
    }

    const startVideoCall = async (toId) => {
      await startCall(toId, { callType: 'video' })
    }

    const endCurrentCall = async () => {
      const activeId = partnerId.value || outgoingCall.value?.toId || incomingCall.value?.fromId || incomingCall.value?.from_id
      const direction = outgoingCall.value ? 'outgoing' : 'incoming'

      isCallActionPending.value = true
      try {
        if (activeId) {
          await CallService.endCall(activeId)
          await saveCallHistoryEntry(
            buildCallHistoryEntry({
              peerId: activeId,
              direction,
              status: 'ended',
            })
          )
        }
      } catch (error) {
        console.warn('Unable to end call cleanly:', error?.response?.data || error?.message || error)
      } finally {
        clearCallSessionState()
        teardownPeerConnection()
        pendingIceCandidates.value = []
        isCallActionPending.value = false
      }
    }

    const rejectIncomingCall = async (reason = 'rejected') => {
      const fromId = incomingCall.value?.fromId || incomingCall.value?.from_id || partnerId.value

      isCallActionPending.value = true
      try {
        if (fromId) {
          await CallService.endCall(fromId)
          await saveCallHistoryEntry(
            buildCallHistoryEntry({
              peerId: fromId,
              direction: 'incoming',
              status: reason,
            })
          )
        }
      } catch (error) {
        console.warn('Unable to reject incoming call cleanly:', error?.response?.data || error?.message || error)
      } finally {
        clearCallSessionState()
        teardownPeerConnection()
        pendingIceCandidates.value = []
        isCallActionPending.value = false
      }
    }

    const acceptIncomingCall = async () => {
      const callPayload = incomingCall.value
      const fromId = callPayload?.fromId || callPayload?.from_id
      const offer = callPayload?.offer || readSignalDescription(callPayload, 'offer')
      if (!fromId || !offer) {
        clearIncomingCallState()
        return
      }

      const incomingCallType = safeCallType(
        callPayload?.callType || callPayload?.call_type || callPayload?.type_hint || inferCallTypeFromDescription(offer)
      )

      isCallActionPending.value = true
      try {
        clearIncomingCallTimer()

        const connection = await ensurePeerConnection({ callType: incomingCallType })
        if (!connection) {
          return
        }

        partnerId.value = fromId
        callStartedAt.value = new Date().toISOString()

        await connection.setRemoteDescription(toSessionDescription(offer, 'offer'))
        await flushQueuedIceCandidates(connection)

        const answer = await connection.createAnswer()
        await connection.setLocalDescription(answer)

        await CallService.answerCall(fromId, {
          type: answer.type,
          sdp: answer.sdp,
          call_type: incomingCallType,
        })

        outgoingCall.value = {
          toId: fromId,
          status: 'Connected',
          callType: incomingCallType,
        }
        clearIncomingCallState()
        showOutgoingCallModal.value = false
      } catch (error) {
        console.error('Unable to accept incoming call:', error?.response?.data || error?.message || error)
        await rejectIncomingCall('failed')
      } finally {
        isCallActionPending.value = false
      }
    }

    const subscribeToCallEvents = (authUserId) => {
      if (!authUserId) {
        return
      }

      activeCallChannelNames.value.forEach((channelName) => {
        echo.leave(channelName)
      })

      const channelNames = [`call.${authUserId}`, `calls.${authUserId}`, `user.${authUserId}.call`]
      activeCallChannelNames.value = channelNames

      const bindEvents = (channel) => {
        ;['.incoming.call', 'incoming.call', '.call.incoming', 'call.incoming', 'IncomingCall', '.IncomingCall']
          .forEach((eventName) => channel.listen(eventName, handleCallIncomingSignal))

        ;['.call.answered', 'call.answered', '.answer.call', 'answer.call', 'CallAnswered', '.CallAnswered']
          .forEach((eventName) => channel.listen(eventName, handleCallAnsweredSignal))

        ;['.ice.candidate', 'ice.candidate', '.call.ice', 'call.ice', 'CallIceCandidate', '.CallIceCandidate']
          .forEach((eventName) => channel.listen(eventName, handleCallIceSignal))

        ;['.call.ended', 'call.ended', '.end.call', 'end.call', 'CallEnded', '.CallEnded', '.call.rejected', 'call.rejected']
          .forEach((eventName) => channel.listen(eventName, handleCallEndedSignal))

        channel.error((err) => console.error('Echo call channel error:', err))
      }

      channelNames.forEach((channelName) => {
        if (!activeChannelNames.value.includes(channelName)) {
          activeChannelNames.value.push(channelName)
        }

        bindEvents(echo.private(channelName))
        bindEvents(echo.channel(channelName))
      })
    }

    const selectGroup = async (group) => {
      if (!group) {
        return
      }

      leaveGroupRealtimeChannels()

      let resolvedGroup = { ...group, type: 'group' }

      try {
        const details = await allService.getGroup(group.id)
        if (details?.group?.id) {
          const localMatch = groups.value.find((item) => sameUserId(item.id, details.group.id))
          resolvedGroup = normalizeServerGroup(details.group, localMatch)

          const index = groups.value.findIndex((item) => sameUserId(item.id, resolvedGroup.id))
          if (index !== -1) {
            groups.value[index] = { ...groups.value[index], ...resolvedGroup }
          } else {
            groups.value.unshift(resolvedGroup)
          }
          persistGroups()
        }
      } catch {
        // Keep local group data if detail fetch is unavailable.
      }

      selectedUser.value = { ...resolvedGroup, type: 'group' }
      searchQuery.value = ''
      showEmojiPicker.value = false
      clearSelectedAttachment()
      await loadGroupMessages(selectedUser.value.id)
      subscribeToGroupRealtimeMessages(selectedUser.value.id)
      selectedCallHistory.value = []
      scheduleScrollToBottom();

      if (isMobileView.value) {
        activeMobilePane.value = 'chat'
      }
    }

    const selectUser = (user) => {
      leaveGroupRealtimeChannels()
      selectedUser.value = user;
      chat.value.messages = [];
      searchQuery.value = '';
      showEmojiPicker.value = false
      clearSelectedAttachment()
      getMessage(user.id);
      loadCallHistoryForUser(user.id)

      // Try to enrich selected user with fresh online/last-seen fields.
      userId(user.id).then((profile) => {
        if (!profile || !selectedUser.value || !sameUserId(selectedUser.value.id, user.id)) {
          return
        }

        selectedUser.value = { ...selectedUser.value, ...profile }
      })

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

    const toggleEmojiPicker = () => {
      showEmojiPicker.value = !showEmojiPicker.value
    }

    const appendEmoji = (emoji) => {
      message.value = `${message.value || ''}${emoji}`
    }

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

    const logoutAndRedirect = () => {
      store.dispatch('logout')
      showHeaderMenu.value = false
      router.push('/')
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
          let filtered = normalizedMessages.filter((item) =>
            isMessageBetweenUsers(item, currentUserId.value, userId)
          );
          chat.value.messages = dedupeMessages(filtered);
          scheduleScrollToBottom();

          if (chat.value.messages.length > 0) {
            rememberChattedUser(userId)
          }
        } else {
          console.warn("No messages found.");
          chat.value.messages = [];
          scheduleScrollToBottom();
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

    const scheduleScrollToBottom = () => {
      setTimeout(() => {
        scrollToBottom()
      }, 0)
    }

    const formatMessageDate = (createdAt) => {
      if (!createdAt) return '';
      const parsed = new Date(createdAt);
      if (Number.isNaN(parsed.getTime())) return '';
      return parsed.toLocaleDateString([], { month: '2-digit', day: '2-digit', year: 'numeric' });
    };

    const formatCallHistoryItem = (item) => {
      if (!item) {
        return 'Call'
      }

      const direction = item.direction === 'incoming' ? 'Incoming' : 'Outgoing'
      const status = (item.status || 'ended').toString().replaceAll('_', ' ')
      const timeValue = item.started_at || item.ended_at
      const parsed = timeValue ? new Date(timeValue) : null
      const timeLabel = parsed && !Number.isNaN(parsed.getTime())
        ? parsed.toLocaleString([], {
          month: 'short',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit',
        })
        : ''

      const durationSeconds = Number(item.duration_seconds || 0)
      const durationLabel = durationSeconds > 0
        ? ` (${Math.floor(durationSeconds / 60)}m ${durationSeconds % 60}s)`
        : ''

      return direction + ' ' + status + (timeLabel ? ' ' + timeLabel : '') + durationLabel
    }

    const formatCallHistoryTitle = (item) => {
      if (!item) {
        return 'Call'
      }

      return `${formatCallDirectionLabel(item.direction)} - ${formatCallStatusLabel(item.status)}`
    }

    const formatCallHistorySubtitle = (item) => {
      if (!item) {
        return ''
      }

      const when = formatCallDetailDateTime(item.started_at || item.ended_at) || 'Time not available'
      const duration = formatCallDuration(item.duration_seconds)
      return `${when} - ${duration}`
    }

    const formatCallDetailDateTime = (value) => {
      if (!value) {
        return ''
      }

      const parsed = new Date(value)
      if (Number.isNaN(parsed.getTime())) {
        return ''
      }

      return parsed.toLocaleString([], {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    }

    const formatCallDuration = (seconds) => {
      const safeSeconds = Number(seconds || 0)
      if (!safeSeconds) {
        return '0s'
      }

      const mins = Math.floor(safeSeconds / 60)
      const secs = safeSeconds % 60
      return mins > 0 ? `${mins}m ${secs}s` : `${secs}s`
    }

    const formatCallStatusLabel = (status) => {
      const value = (status || 'ended').toString().replaceAll('_', ' ')
      return value.charAt(0).toUpperCase() + value.slice(1)
    }

    const formatCallDirectionLabel = (direction) => {
      return direction === 'incoming' ? 'Incoming call' : 'Outgoing call'
    }

    const callStatusBadgeClass = (status) => {
      const normalized = (status || 'ended').toString().toLowerCase()

      if (normalized === 'answered' || normalized === 'ended') {
        return 'call-badge-success'
      }

      if (normalized === 'missed' || normalized === 'failed' || normalized === 'rejected') {
        return 'call-badge-danger'
      }

      if (normalized === 'busy') {
        return 'call-badge-warning'
      }

      return 'call-badge-neutral'
    }

    const callDirectionBadgeClass = (direction) => {
      return direction === 'incoming' ? 'call-badge-incoming' : 'call-badge-outgoing'
    }

    const getCallPeerName = (item) => {
      const peerId = item?.user_id || selectedUser.value?.id
      const fallback = selectedUser.value?.name || ''
      return resolveParticipantName(peerId, fallback)
    }

    const getCallSenderName = (item) => {
      if ((item?.direction || 'outgoing') === 'incoming') {
        return getCallPeerName(item)
      }

      return currentUserName.value || 'You'
    }

    const getCallReceiverName = (item) => {
      if ((item?.direction || 'outgoing') === 'incoming') {
        return currentUserName.value || 'You'
      }

      return getCallPeerName(item)
    }

    const formatCallMeta = (meta) => {
      if (typeof meta === 'string') {
        return meta
      }

      try {
        return JSON.stringify(meta, null, 2)
      } catch {
        return ''
      }
    }

    const isCallHistoryItemActive = (item) => {
      if (!item || !activeCallHistoryItem.value) {
        return false
      }

      if (item.id && activeCallHistoryItem.value.id) {
        return item.id === activeCallHistoryItem.value.id
      }

      return (
        item.started_at === activeCallHistoryItem.value.started_at &&
        item.ended_at === activeCallHistoryItem.value.ended_at &&
        item.status === activeCallHistoryItem.value.status
      )
    }

    const openCallHistoryDetails = (item = null) => {
      const targetItem = item || selectedCallHistory.value?.[0] || null
      activeCallHistoryItem.value = targetItem
      showCallHistoryDetails.value = Boolean(targetItem)
    }

    const closeCallHistoryDetails = () => {
      showCallHistoryDetails.value = false
      activeCallHistoryItem.value = null
    }

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

    // Utility: Deduplicate messages by id or signature
    function dedupeMessages(messages) {
      const unique = [];
      const seenIds = new Set();
      const seenSignatures = new Set();
      messages.forEach((item) => {
        const idKey = item?.id ? String(item.id) : null;
        if (idKey && seenIds.has(idKey)) return;
        if (idKey) seenIds.add(idKey);
        const sig = [String(item?.from_id||''), String(item?.to_id||item?.group_id||''), String(item?.body||''), String(item?.created_at||'')].join('|');
        if (seenSignatures.has(sig)) return;
        seenSignatures.add(sig);
        unique.push(item);
      });
      return unique;
    }

    const isCurrentUserMessage = (msg) => sameUserId(msg?.from_id, currentUserId.value);

    const resolveParticipantName = (userId, fallback = '') => {
      if (!userId) {
        return fallback || 'Unknown User'
      }

      if (sameUserId(userId, currentUserId.value)) {
        return currentUserName.value || 'You'
      }

      const matchedUser = userView.value.find((user) => sameUserId(user?.id, userId))
      if (matchedUser?.name) {
        return matchedUser.name
      }

      return fallback || `User #${userId}`
    }

    const getMessageSenderName = (msg) => {
      if (!msg) {
        return 'Unknown User'
      }

      if (msg.sender_name) {
        return msg.sender_name
      }

      return resolveParticipantName(msg.from_id, isCurrentUserMessage(msg) ? 'You' : '')
    }

    const getMessageReceiverName = (msg) => {
      if (!msg) {
        return 'Unknown User'
      }

      if (msg.receiver_name) {
        return msg.receiver_name
      }

      if (isGroupConversation(selectedUser.value)) {
        return selectedUser.value?.name || 'Group'
      }

      return isCurrentUserMessage(msg)
        ? resolveParticipantName(selectedUser.value?.id, selectedUser.value?.name || '')
        : 'You'
    }

    const canDownloadAttachment = (attachment) => Boolean(attachment && ['image', 'video', 'audio', 'document'].includes(attachment.kind) && attachment.url)

    const resolveAttachmentUrl = (attachment) => {
      const rawUrl = (attachment?.url || '').toString().trim()
      if (!rawUrl) {
        return ''
      }

      const normalizeUrl = (value) => {
        try {
          return new URL(value).toString()
        } catch {
          return encodeURI(value)
        }
      }

      if (rawUrl.startsWith('http://') || rawUrl.startsWith('https://') || rawUrl.startsWith('blob:') || rawUrl.startsWith('data:')) {
        return normalizeUrl(rawUrl)
      }

      const baseUrl = getApiOrigin().replace(/\/$/, '')
      const normalizedPath = rawUrl.replace(/^\/+/, '')

      if (normalizedPath.startsWith('storage/')) {
        return normalizeUrl(`${baseUrl}/${normalizedPath}`)
      }

      return normalizeUrl(`${baseUrl}/storage/${normalizedPath}`)
    }

    const getAttachmentFilename = (attachment) => {
      const explicitName = (attachment?.name || '').toString().trim()
      if (explicitName) {
        return explicitName
      }

      const fileUrl = resolveAttachmentUrl(attachment)
      if (!fileUrl) {
        return 'attachment'
      }

      try {
        const pathname = new URL(fileUrl).pathname
        const fromPath = decodeURIComponent(pathname.split('/').pop() || '').trim()
        return fromPath || 'attachment'
      } catch {
        return 'attachment'
      }
    }

    const isPreviewableAttachment = (attachment, resolvedMimeType = '') => {
      const kind = (attachment?.kind || '').toString().toLowerCase()
      const mimeType = (resolvedMimeType || attachment?.mimeType || '').toString().toLowerCase()
      const fileUrl = resolveAttachmentUrl(attachment).toLowerCase()

      if (kind === 'image' || kind === 'video' || kind === 'audio') {
        return true
      }

      if (mimeType.startsWith('image/') || mimeType.startsWith('video/') || mimeType.startsWith('audio/')) {
        return true
      }

      if (mimeType === 'application/pdf' || /\.pdf(\?|#|$)/.test(fileUrl)) {
        return true
      }

      return false
    }

    const isImageAttachment = (attachment, mimeType = '') => {
      const kind = (attachment?.kind || '').toString().toLowerCase()
      const normalizedMimeType = (mimeType || attachment?.mimeType || '').toString().toLowerCase()
      const fileUrl = resolveAttachmentUrl(attachment).toLowerCase()

      return kind === 'image' || normalizedMimeType.startsWith('image/') || /\.(png|jpe?g|gif|webp|bmp|svg)(\?|#|$)/.test(fileUrl)
    }

    const fetchAttachmentBlob = async (attachment) => {
      const fileUrl = resolveAttachmentUrl(attachment)
      if (!fileUrl) {
        return null
      }

      const response = await fetch(fileUrl, { method: 'GET', mode: 'cors' })
      if (!response.ok) {
        throw new Error(`Attachment fetch failed with status ${response.status}`)
      }

      const blob = await response.blob()
      const dispositionName = getFilenameFromDisposition(response.headers.get('content-disposition') || '')

      return {
        blob,
        fileUrl,
        mimeType: (blob.type || attachment?.mimeType || '').toString(),
        filename: dispositionName || getAttachmentFilename(attachment),
      }
    }

    const fetchAttachmentBlobViaFetch = async (attachment) => {
      const fileUrl = resolveAttachmentUrl(attachment)
      if (!fileUrl) {
        return null
      }

      const token = localStorage.getItem('token') || ''
      const response = await fetch(fileUrl, {
        method: 'GET',
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      })

      if (!response.ok) {
        throw new Error(`Fetch failed with status ${response.status}`)
      }

      const blob = await response.blob()
      const dispositionName = getFilenameFromDisposition(response.headers.get('content-disposition') || '')

      return {
        blob,
        fileUrl,
        mimeType: (blob.type || attachment?.mimeType || '').toString(),
        filename: dispositionName || getAttachmentFilename(attachment),
      }
    }

    const triggerBlobDownload = (blob, filename) => {
      const blobUrl = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = blobUrl
      link.download = filename || 'attachment'
      link.rel = 'noopener'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      setTimeout(() => URL.revokeObjectURL(blobUrl), 1000)
    }

    const getFilenameFromDisposition = (disposition) => {
      const value = (disposition || '').toString()
      if (!value) {
        return ''
      }

      const utfMatch = value.match(/filename\*=UTF-8''([^;]+)/i)
      if (utfMatch?.[1]) {
        try {
          return decodeURIComponent(utfMatch[1]).trim()
        } catch {
          return utfMatch[1].trim()
        }
      }

      const simpleMatch = value.match(/filename="?([^";]+)"?/i)
      return (simpleMatch?.[1] || '').trim()
    }

    const closeImagePreview = () => {
      if (imagePreviewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(imagePreviewUrl.value)
      }

      showImagePreview.value = false
      imagePreviewUrl.value = ''
      imagePreviewName.value = ''
      previewAttachmentKind.value = 'image'
      previewAttachment.value = null
    }

    const openImagePreview = (url, name = '', attachment = null, kind = 'image') => {
      closeImagePreview()
      imagePreviewUrl.value = url
      imagePreviewName.value = name
      previewAttachmentKind.value = kind
      previewAttachment.value = attachment
      showImagePreview.value = true
    }

    const getVisibleMessageBody = (msg) => {
      const body = (msg?.body || '').toString().trim()
      if (!body) {
        return ''
      }

      const attachment = getMessageAttachment(msg)
      if (!attachment) {
        return body
      }

      const attachmentName = (attachment.name || '').toString().trim()
      if (!attachmentName) {
        return body
      }

      // Hide auto-filled body text when it is just the attachment filename.
      return body === attachmentName ? '' : body
    }

    const shouldShowMessageBody = (msg) => {
      // Attachment messages should render as preview/download only.
      if (getMessageAttachment(msg)) {
        return false
      }

      return Boolean(getVisibleMessageBody(msg))
    }

    const viewAttachment = async (attachment) => {
      const fileUrl = resolveAttachmentUrl(attachment)
      if (!fileUrl) {
        return
      }

      try {
        const payload = await fetchAttachmentBlob(attachment)
        if (!payload) {
          return
        }

        const isImage = isImageAttachment(attachment, payload.mimeType)
        if (isImage) {
          const previewUrl = URL.createObjectURL(payload.blob)
          openImagePreview(previewUrl, payload.filename, attachment, 'image')
          return
        }

        const isVideo = (attachment?.kind || '').toLowerCase() === 'video' || payload.mimeType.startsWith('video/')
        if (isVideo) {
          const previewUrl = URL.createObjectURL(payload.blob)
          openImagePreview(previewUrl, payload.filename, attachment, 'video')
          return
        }

        if (!isPreviewableAttachment(attachment, payload.mimeType)) {
          triggerBlobDownload(payload.blob, payload.filename)
          return
        }

        const previewUrl = URL.createObjectURL(payload.blob)
        const previewWindow = window.open(previewUrl, '_blank', 'noopener,noreferrer')

        if (!previewWindow) {
          URL.revokeObjectURL(previewUrl)
          triggerBlobDownload(payload.blob, payload.filename)
          return
        }

        setTimeout(() => URL.revokeObjectURL(previewUrl), 60000)
      } catch (error) {
        console.error('View attachment failed:', error)
        if (isImageAttachment(attachment)) {
          openImagePreview(fileUrl, getAttachmentFilename(attachment), attachment, 'image')
          return
        }

        if ((attachment?.kind || '').toLowerCase() === 'video') {
          openImagePreview(fileUrl, getAttachmentFilename(attachment), attachment, 'video')
          return
        }

        window.open(fileUrl, '_blank', 'noopener,noreferrer')
      }
    }

    const downloadAttachment = async (attachment) => {
      const fileUrl = resolveAttachmentUrl(attachment)
      if (!fileUrl) {
        return
      }

      const fallbackFilename = getAttachmentFilename(attachment)

      try {
        const payload = await fetchAttachmentBlob(attachment)
        if (!payload) {
          return
        }

        triggerBlobDownload(payload.blob, payload.filename || fallbackFilename)
      } catch (error) {
        console.error('Download attachment failed (axios):', error)

        try {
          const payload = await fetchAttachmentBlobViaFetch(attachment)
          if (payload) {
            triggerBlobDownload(payload.blob, payload.filename || fallbackFilename)
            return
          }
        } catch (fetchError) {
          console.error('Download attachment failed (fetch):', fetchError)
        }

        const link = document.createElement('a')
        const forcedDownloadUrl = fileUrl + (fileUrl.includes('?') ? '&' : '?') + 'download=1'
        link.href = forcedDownloadUrl
        link.download = fallbackFilename
        link.rel = 'noopener'
        link.target = '_blank'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        // Final fallback for strict cross-origin responses where `download` is ignored.
        window.open(forcedDownloadUrl, '_blank', 'noopener,noreferrer')
      }
    }

    const pendingOutgoingMessageCounts = new Map();

    const prunePendingOutgoingMessages = () => {
      const now = Date.now();

      pendingOutgoingMessageCounts.forEach((entry, key) => {
        if (!entry || entry.expiresAt <= now || entry.count <= 0) {
          pendingOutgoingMessageCounts.delete(key);
        }
      });
    };

    const buildMessageSignature = (message) => {
      const attachment = getMessageAttachment(message);
      const attachmentKind = attachment?.kind ?? message?.attachment_kind ?? '';
      const attachmentName = attachment?.name ?? message?.attachment_name ?? '';
      const attachmentSize = attachment?.size ?? message?.attachment_size ?? '';
      const isGroupMessage = isGroupConversation(message) || Boolean(message?.group_id);
      const destination = isGroupMessage
        ? `group:${String(message?.group_id ?? '')}`
        : `user:${String(message?.to_id ?? '')}`;

      return [
        sameUserId(message?.from_id, currentUserId.value) ? 'me' : String(message?.from_id ?? ''),
        destination,
        String(message?.conversation_type || ''),
        String((message?.body || '').trim()),
        String(attachmentKind),
        String(attachmentName),
        String(attachmentSize),
      ].join('|');
    };

    const registerPendingOutgoingMessage = (message) => {
      const signature = buildMessageSignature(message);
      const existing = pendingOutgoingMessageCounts.get(signature);

      pendingOutgoingMessageCounts.set(signature, {
        count: (existing?.count || 0) + 1,
        expiresAt: Date.now() + 15000,
      });

      return signature;
    };

    const clearPendingOutgoingMessage = (message) => {
      pendingOutgoingMessageCounts.delete(buildMessageSignature(message));
    };

    const consumePendingOutgoingMessage = (message) => {
      prunePendingOutgoingMessages();

      const signature = buildMessageSignature(message);
      const entry = pendingOutgoingMessageCounts.get(signature);
      if (!entry?.count) {
        return false;
      }

      entry.count -= 1;

      if (entry.count <= 0) {
        pendingOutgoingMessageCounts.delete(signature);
      } else {
        pendingOutgoingMessageCounts.set(signature, entry);
      }

      return true;
    };

    const areMessagesEqual = (first, second) => {
      if (!first || !second) return false

      if (first.id && second.id) {
        return first.id === second.id
      }

      const firstIsGroup = isGroupConversation(first) || Boolean(first.group_id)
      const secondIsGroup = isGroupConversation(second) || Boolean(second.group_id)

      if (firstIsGroup || secondIsGroup) {
        return buildMessageSignature(first) === buildMessageSignature(second)
      }

      return (
        sameUserId(first.from_id, second.from_id) &&
        sameUserId(first.to_id, second.to_id) &&
        sameUserId(first.group_id, second.group_id) &&
        first.body === second.body &&
        first.created_at === second.created_at
      )
    }

    const mergeGroupMembers = (...memberGroups) => {
      const merged = []

      memberGroups.flat().forEach((member) => {
        const normalized = normalizeGroupMember(member)
        if (!normalized?.id) return

        const existingIndex = merged.findIndex((item) => sameUserId(item.id, normalized.id))
        if (existingIndex === -1) {
          merged.push(normalized)
        } else {
          merged[existingIndex] = { ...merged[existingIndex], ...normalized }
        }
      })

      return merged
    }

    const upsertGroupFromIncomingMessage = (incoming) => {
      if (!incoming?.group_id) return null

      const existingIndex = groups.value.findIndex((group) => sameUserId(group.id, incoming.group_id))
      const existingGroup = existingIndex === -1 ? null : groups.value[existingIndex]
      const nextGroup = {
        id: incoming.group_id,
        name: incoming.group_name || existingGroup?.name || `Group ${incoming.group_id}`,
        type: 'group',
        created_by: existingGroup?.created_by ?? incoming.from_id ?? null,
        created_at: existingGroup?.created_at ?? incoming.created_at ?? new Date().toISOString(),
        members: mergeGroupMembers(
          existingGroup?.members || [],
          incoming.group_members || [],
          incoming.from_id ? [{ id: incoming.from_id, name: incoming.sender_name }] : [],
          currentUserId.value ? [{ id: currentUserId.value, name: currentUserName.value }] : []
        ),
        messages: [...(existingGroup?.messages || [])],
      }

      if (!nextGroup.messages.some((message) => areMessagesEqual(message, incoming))) {
        nextGroup.messages.push(incoming)
      }

      if (existingIndex === -1) {
        groups.value.unshift(nextGroup)
      } else {
        groups.value[existingIndex] = { ...existingGroup, ...nextGroup }
      }

      persistGroups()

      if (selectedUser.value && isGroupConversation(selectedUser.value) && sameUserId(selectedUser.value.id, nextGroup.id)) {
        selectedUser.value = { ...selectedUser.value, ...nextGroup, type: 'group' }
      }

      return nextGroup
    }

    const appendMessage = (incoming) => {
      const incomingAttachment = getMessageAttachment(incoming)
      if (!incoming?.body && !incomingAttachment) {
        return;
      }

      const incomingIsGroup = isGroupConversation(incoming) || Boolean(incoming?.group_id)

      if (sameUserId(incoming?.from_id, currentUserId.value) && consumePendingOutgoingMessage(incoming)) {
        const optimisticIndex = chat.value.messages.findIndex((item) =>
          item._optimistic &&
          sameUserId(item.from_id, incoming.from_id) &&
          (incomingIsGroup
            ? sameUserId(item.group_id, incoming.group_id)
            : sameUserId(item.to_id, incoming.to_id)) &&
          item.body === incoming.body
        )

        if (optimisticIndex !== -1) {
          chat.value.messages.splice(optimisticIndex, 1, incoming)
          chat.value.messages = dedupeMessages(chat.value.messages);
          scheduleScrollToBottom()
        }

        return
      }

      const optimisticIndex = chat.value.messages.findIndex((item) =>
        item._optimistic &&
        sameUserId(item.from_id, incoming.from_id) &&
        (incomingIsGroup
          ? sameUserId(item.group_id, incoming.group_id)
          : sameUserId(item.to_id, incoming.to_id)) &&
        item.body === incoming.body
      );

      if (optimisticIndex !== -1) {
        chat.value.messages.splice(optimisticIndex, 1, incoming);
        chat.value.messages = dedupeMessages(chat.value.messages);
        scheduleScrollToBottom()
        return;
      }

      const alreadyExists = chat.value.messages.some((item) => {
        return areMessagesEqual(item, incoming)
      });

      if (!alreadyExists) {
        chat.value.messages.push(incoming);
        chat.value.messages = dedupeMessages(chat.value.messages);
        scheduleScrollToBottom()
      }
    };

    const leaveGroupRealtimeChannels = () => {
      activeGroupChannelNames.value.forEach((channelName) => {
        echo.leave(channelName)
      })

      if (activeGroupChannelNames.value.length) {
        activeChannelNames.value = activeChannelNames.value.filter((channelName) => !activeGroupChannelNames.value.includes(channelName))
      }

      activeGroupChannelNames.value = []
    }

    const subscribeToGroupRealtimeMessages = (groupId) => {
      if (!groupId) {
        return
      }

      leaveGroupRealtimeChannels()

      const channelNames = [`group.${groupId}`, `groups.${groupId}`]
      activeGroupChannelNames.value = channelNames

      const handleGroupRealtimeEvent = (event) => {
        const incoming = normalizeMessagePayload(event, {
          group_id: groupId,
          conversation_type: 'group',
        })

        const sameGroupMessage =
          sameUserId(incoming.group_id, groupId) ||
          sameUserId(incoming.to_id, groupId) ||
          sameUserId(incoming.groupId, groupId) ||
          incoming.conversation_type === 'group'

        if (!sameGroupMessage) {
          return
        }

        const normalizedIncoming = {
          ...incoming,
          group_id: incoming.group_id || groupId,
          conversation_type: 'group',
        }

        upsertGroupFromIncomingMessage(normalizedIncoming)

        if (selectedUser.value && isGroupConversation(selectedUser.value) && sameUserId(selectedUser.value.id, groupId)) {
          appendMessage(normalizedIncoming)
        }
      }

      channelNames.forEach((channelName) => {
        if (!activeChannelNames.value.includes(channelName)) {
          activeChannelNames.value.push(channelName)
        }

        const bindEvents = (channel) => {
          ;['.chat.message', 'chat.message', '.group.message', 'group.message', '.group.message.sent', 'group.message.sent', '.message.sent', 'message.sent', '.my-event', 'my-event']
            .forEach((eventName) => {
              channel.listen(eventName, handleGroupRealtimeEvent)
            })

          channel.error(err => console.error('Echo group channel error:', err))
        }

        bindEvents(echo.private(channelName))
        bindEvents(echo.channel(channelName))
      })
    }

    const initWebRTC = async () => {
      try {
        setupPeerConnection()
      } catch (error) {
        console.warn('WebRTC init skipped:', error)
      }
    }

    onMounted(async () => {
      window.addEventListener('resize', updateViewportMode);
      updateViewportMode();

      // Update DD/MM/YYYY in real-time.
      dateTickIntervalId.value = setInterval(() => {
        now.value = new Date()
      }, 60000)

      await refreshWeatherAndLocation()
      // Refresh weather/location periodically so sidebar data stays current.
      weatherRefreshIntervalId.value = setInterval(refreshWeatherAndLocation, 5 * 60 * 1000)

      const token = localStorage.getItem('token');
      if (!token) {
        isInitialLoading.value = false
        router.push('/');
        return;
      }

      try {
        await fetchUserId()
        await Promise.all([
          fetchUserData(''),
          initWebRTC(),
          loadCallHistoryForUser(null),
        ])
      } finally {
        isInitialLoading.value = false
      }
    })

    watch(currentUserId, (id) => {
      if (!id) return

      subscribeToRealtimeMessages(id)
      subscribeToCallEvents(id)

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

    watch(
      () => [
        selectedUser.value?.id || '',
        selectedUser.value?.type || '',
        chat.value.messages.map((msg) => String(msg?.id || msg?.created_at || msg?.body || '')).join('|'),
      ],
      () => {
        scheduleScrollToBottom()
      }
    )

    onBeforeUnmount(() => {
      activeChannelNames.value.forEach((channelName) => echo.leave(channelName));
      activeCallChannelNames.value.forEach((channelName) => echo.leave(channelName));

      window.removeEventListener('resize', updateViewportMode);

      if (searchDebounceTimer.value) {
        clearTimeout(searchDebounceTimer.value);
      }

      if (dateTickIntervalId.value) {
        clearInterval(dateTickIntervalId.value)
      }

      if (weatherRefreshIntervalId.value) {
        clearInterval(weatherRefreshIntervalId.value)
      }

      if (profilePreviewUrl.value) {
        URL.revokeObjectURL(profilePreviewUrl.value)
      }

      closeImagePreview()

      clearSelectedAttachment()
      clearIncomingCallTimer()
      teardownPeerConnection()
      pendingIceCandidates.value = []
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
      showGroupDetails,
      activeGroupDetails,
      showCallHistoryDetails,
      activeCallHistoryItem,
      showIncomingCallModal,
      showOutgoingCallModal,
      isInitialLoading,
      showGroupManager,
      showGroupSettings,
      groupSettingsForm,
      groupSettingsPreviewUrl,
      editingGroupForSettings,
      showAddMemberForm,
      newGroupMembers,
      groupForm,
      groupCandidateUsers,
      availableUsersForGroup,
      visibleGroups,
      editingGroupId,
      groupFormError,
      groupFormSubmitting,
      incomingCallDisplayName,
      incomingCallerPhoto,
      outgoingCallDisplayName,
      outgoingCallStatusText,
      isCallActionPending,
      attachmentInput,
      selectedAttachment,
      showImagePreview,
      imagePreviewUrl,
      imagePreviewName,
      previewAttachmentKind,
      previewAttachment,
      remoteAudio,
      remoteVideo,
      localVideo,
      isUserDetailsLoading,
      userDetails,
      userDetailsError,
      showEmojiPicker,
      attachmentValidationError,
      isSendingMessage,
      emojiList,
      currentDate,
      filteredUsers,
      visibleUsers,
      showSidebarPane,
      showChatPane,
      isDetailsOpen,
      isCurrentUserMessage,
      sendMessage,
      selectUser,
      goBackToUsers,
      clearSearch,
      openSettings,
      logoutAndRedirect,
      toggleMenu,
      closeSettings,
      updateProfile,
      resetSettings,
      handleImageUpload,
      searchUser,
      getMessage,
      selectUserprofile,
      selectGroup,
      toggleEmojiPicker,
      appendEmoji,
      openCurrentUserDetails,
      closeUserDetails,
      openGroupDetails,
      closeGroupDetails,
      openGroupChatFromDetails,
      openGroupSettingsFromDetails,
      userId,
      startCall,
      startVideoCall,
      endCurrentCall,
      openGroupManager,
      closeGroupManager,
      openGroupSettings,
      closeGroupSettings,
      addMembersToGroup,
      removeMemberFromGroup,
      deleteGroup,
      saveGroup,
      saveGroupSettings,
      handleGroupImageUpload,
      formatGroupPreview,
      formatMessageDate,
      formatProfileDate,
      getUserPresenceText,
      mutualGroupsWithSelectedUser,
      sameCountryMembers,
      memberLocationLabel,
      selectedCallHistory,
      formatCallHistoryItem,
      formatCallHistoryTitle,
      formatCallHistorySubtitle,
      formatCallDetailDateTime,
      formatCallDuration,
      formatCallStatusLabel,
      formatCallDirectionLabel,
      callStatusBadgeClass,
      callDirectionBadgeClass,
      getCallSenderName,
      getCallReceiverName,
      formatCallMeta,
      openCallHistoryDetails,
      closeCallHistoryDetails,
      isCallHistoryItemActive,
      acceptIncomingCall,
      rejectIncomingCall,
      triggerAttachmentPicker,
      handleAttachmentChange,
      clearSelectedAttachment,
      formatFileSize,
      getMessageAttachment,
      getVisibleMessageBody,
      shouldShowMessageBody,
      getMessageSenderName,
      getMessageReceiverName,
      canDownloadAttachment,
      resolveAttachmentUrl,
      closeImagePreview,
      viewAttachment,
      downloadAttachment,
      isGroupConversation,
      partnerId,
      sameUserId
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
  background-color: #e3f0ff !important;
}

/* Active user highlight */
.list-group-item.active {
  background-color: #cfe5ff !important;
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
  background-color: #eaf4ff;
  padding: 0;
  margin: 0;
  overflow: hidden;
}

.initial-loading-screen {
  position: fixed;
  inset: 0;
  z-index: 13000;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eaf4ff;
  color: #163d66;
}

.initial-loading-card {
  width: min(360px, calc(100% - 32px));
  background: #f4f9ff;
  border: 1px solid #b9d8ff;
  border-radius: 14px;
  padding: 24px;
}

/* Keep the sidebar and chat pane on one row on desktop */
.chat-layout {
  width: 100%;
  height: 100%;
  min-height: 100%;
  display: flex;
  flex-direction: row;
  background-color: #d7ebff;
  flex-wrap: nowrap;
  overflow: hidden;
}

.chat-sidebar {
  display: flex;
  flex-direction: column;
  flex: 0 0 30%;
  min-width: 350px;
  max-width: 450px;
  background-color: #eaf4ff;
  border-right: 1px solid #b9d8ff;
}

.chat-main {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  background-color: #f4f9ff;
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


.attachment-container {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.attachment-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.attachment-actions .btn {
  padding: 4px 12px;
  font-size: 0.75rem;
}

.attachment-actions .btn:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

.document-preview {
  display: flex;
  align-items: center;
  gap: 8px;
  word-break: break-word;
}

.document-name {
  font-size: 0.9rem;
  font-weight: 500;
}

.chat-attachment-image,
.chat-attachment-video {
  width: 200px;
  height: 150px;
  object-fit: cover;
  display: block;
  border-radius: 8px;
}

.image-preview-overlay {
  position: fixed;
  inset: 0;
  z-index: 13000;
  background: rgba(7, 17, 30, 0.92);
  display: flex;
  flex-direction: column;
}

.image-preview-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
}

.image-preview-stage {
  flex: 1;
  min-height: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
}

.image-preview-full {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  border-radius: 8px;
}

.emoji-picker {
  position: absolute;
  bottom: 46px;
  left: 0;
  background: #f4f9ff;
  border: 1px solid #b9d8ff;
  border-radius: 10px;
  padding: 8px;
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 6px;
  z-index: 200;
}

.emoji-item {
  border: 0;
  background: transparent;
  color: #163d66;
  border-radius: 6px;
  line-height: 1;
  padding: 4px;
  font-size: 1.1rem;
}

.emoji-item:hover {
  background: #d7ebff;
}

.sidebar-user-list {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.sidebar-user-list::-webkit-scrollbar {
  width: 0;
  height: 0;
}

.call-history-list {
  display: grid;
  gap: 8px;
  max-height: 220px;
  overflow-y: auto;
}

.call-history-item {
  font-size: 0.78rem;
  background: #edf6ff;
  border: 1px solid #b9d8ff;
  color: #163d66;
  cursor: pointer;
  border-radius: 10px;
  padding: 8px 10px;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.call-history-item:hover {
  background: #dceeff;
  border-color: #8fbced;
}

.call-history-item.active {
  background: #cfe5ff;
  color: #163d66;
  border-color: #60a5fa;
}

.call-history-item-title {
  font-weight: 600;
  line-height: 1.25;
}

.call-history-item-subtitle {
  font-size: 0.72rem;
  color: #4f78a8;
}

.call-history-meta {
  white-space: pre-wrap;
  word-break: break-word;
  background: #eaf4ff;
  color: #355b87;
  border-radius: 8px;
  padding: 8px;
  font-size: 0.75rem;
}

.call-details-summary {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.call-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 3px 10px;
  font-size: 0.75rem;
  font-weight: 600;
}


.details-note {
  margin: -4px 0 12px;
  color: #4f78a8;
  font-size: 0.82rem;
}

.details-item {
  padding: 8px 10px;
  border: 1px solid #b9d8ff;
  border-radius: 10px;
  background: #edf6ff;
}

.details-label {
  color: #4f78a8;
  display: block;
  margin-bottom: 3px;
  font-size: 0.74rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.details-value {
  color: #163d66;
  font-size: 0.92rem;
}


.user-details-grid {
  display: grid;
  gap: 12px;
}

.settings-overlay {
  position: fixed;
  inset: 0;
  background: rgba(26, 62, 104, 0.35);
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
  background: #edf6ff;
  border: 1px solid #b9d8ff;
  border-radius: 8px;
  padding: 6px 0;
  z-index: 50;
}

.header-menu .dropdown-item {
  color: #163d66;
  background: transparent;
  border: 0;
  width: 100%;
  text-align: left;
  padding: 8px 12px;
  display: block;
  text-decoration: none;
}

.header-menu .dropdown-item:hover {
  background: #d7ebff;
}

.dropdown-item-disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.dropdown-item-disabled small {
  color: #6b88ae;
}

.header-menu .dropdown-divider {
  border-color: #b9d8ff;
}

.settings-card {
  width: min(420px, 100%);
  background: #f4f9ff;
  color: #163d66;
  border: 1px solid #b9d8ff;
  border-radius: 12px;
  padding: 16px;
}

.powered-by-text {
  color: #4f78a8;
  font-size: 0.74rem;
  letter-spacing: 0.02em;
}

.incoming-call-card {
  width: min(340px, 100%);
}

.group-card {
  width: min(520px, 100%);
}

.group-member-picker {
  display: grid;
  gap: 8px;
  max-height: 320px;
  overflow: auto;
}

.group-member-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border: 1px solid #b9d8ff;
  border-radius: 10px;
  background: #edf6ff;
}


.group-members-list {
  max-height: 320px;
  overflow-y: auto;
}

.group-member-item {
  background: #edf6ff !important;
  border: 1px solid #b9d8ff;
}

.group-member-item:hover {
  background: #dceeff !important;
}


.incoming-call-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #d7ebff;
  color: #163d66;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
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
  background: #d7ebff;
  color: #163d66;
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
    background-color: #eaf4ff;
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
