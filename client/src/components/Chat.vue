<template>
  <div class="chat-container">
    <div class="chat-message" :class="badgeClass">
      <div class="message-content">
        <small class="name" :class="messageClass">{{ isSender ? "You" : "Them" }}</small>
        <div class="message-bubble">{{ body }}</div>
      </div>
    </div>
  </div>
</template>

<script>
import echo from "@/services/echo";

export default {
  props: {
    senderId: Number,
    selectedUserId: Number,
    currentUserId: Number,
    body:String,
  },

  mounted() {
    echo.private(`chat.${this.currentUserId}`)
        .listen('.chat.message', (e) => {
          console.log('Realtime message:', e);

          this.messages.push({
            from_id: e.from_id,
            to_id: e.to_id,
            body: e.message
          });
        })
        .error(err => {
          console.error('Echo error:', err);
        });

  },

  computed: {
    isSender() {
      return this.senderId === this.currentUserId;
    },
    messageClass() {
      return this.isSender ? "sent-message" : "received-message";
    },
    badgeClass() {
      return this.isSender ? "sent" : "received";
    },
    // selectedUserId() {
    //   return Number(this.$route.params.userId);
    // },
  },
};
</script>
<style scoped>

.chat-container {
  display: flex;
  flex-direction: column;
  width: 100%;
  padding: 10px;
}

.chat-message {
  max-width: 60%;
  margin: 5px 0;
  padding: 8px 12px;
  border-radius: 15px;
  font-size: 14px;
  word-wrap: break-word;
  display: flex;
}

.sent {
  align-self: flex-end; /* Moves sent messages to the right */
  background-color: #007bff;
  color: white;
  text-align: right; /* Ensures text is aligned properly */
}

.received {
  align-self: flex-start; /* Keeps received messages on the left */
  background-color: #f1f1f1;
  color: black;
  text-align: left;
}

.sent .message-bubble {
  border-radius: 18px 18px 0 18px;
}

.received .message-bubble {
  border-radius: 18px 18px 18px 0;
}
.sent-message{
  color: rgba(123, 248, 248, 0.7);
}
.received-message{
  color: rgba(238, 18, 18, 0.9);
}

/* Small adjustments */
.name {
  font-size: 12px;
  margin-bottom: 2px;
}

.timestamp {
  font-size: 10px;
  color: #ff0000;
  align-self: flex-end;
}

</style>
