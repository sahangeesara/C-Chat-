<template>
  <div class="auth-container">
    <div class="auth-card auth-card-wide">
      <div class="logo">Skytalk</div>

      <h1 class="title">Sign up</h1>

      <form @submit.prevent="onSubmit" class="auth-form">
        <div class="input-group">
          <input
            type="text"
            id="name"
            v-model.trim="userData.name"
            placeholder="Name"
            required
            class="input-field"
          />
          <div v-if="error.name" class="field-error">{{ error.name[0] }}</div>
        </div>

        <div class="input-group">
          <input
            type="email"
            id="email"
            v-model.trim="userData.email"
            placeholder="Email"
            required
            class="input-field"
          />
          <div v-if="error.email" class="field-error">{{ error.email[0] }}</div>
        </div>

        <div class="input-group">
          <input
            :type="hide ? 'password' : 'text'"
            id="password"
            v-model="userData.password"
            placeholder="Password"
            required
            class="input-field"
          />
          <span class="password-toggle" @click="hide = !hide">
            <i :class="hide ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
          </span>
          <div v-if="error.password" class="field-error">{{ error.password[0] }}</div>
        </div>

        <div class="input-group">
          <input
            :type="hide2 ? 'password' : 'text'"
            id="password_confirmation"
            v-model="userData.password_confirmation"
            placeholder="Confirm password"
            required
            class="input-field"
          />
          <span class="password-toggle" @click="hide2 = !hide2">
            <i :class="hide2 ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
          </span>
        </div>

        <button type="submit" class="submit-btn" :disabled="loading">
          {{ loading ? 'Registering...' : 'Register' }}
        </button>

        <router-link to="/" class="btn secondary-btn">Sign In</router-link>
      </form>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import { mapActions } from "vuex";

export default {
  data() {
    return {
      error: {}, // Store validation errors as an object
      hide: true,
      hide2: true,
      loading: false, // Prevent multiple submissions
      userData: {
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
      },
    };
  },
  methods: {
    ...mapActions(["handleToken"]),

    async onSubmit() {
      if (this.loading) return;
      this.loading = true;

      try {
        const response = await axios.post("http://localhost:8000/api/signup", this.userData);
        this.handleResponse(response.data);
      } catch (error) {
        this.handleError(error);
      } finally {
        this.loading = false;
      }
    },

    handleResponse(data) {
      this.handleToken(data.access_token);
      this.$router.push("/chatapp");
    },

    handleError(error) {
      this.error = error.response?.data?.errors || {};
    },
  },
};
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #eaf4ff 0%, #d7ebff 55%, #c5e2ff 100%);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.auth-card {
  background: #f4f9ff;
  border-radius: 30px;
  padding: 2rem;
  width: 100%;
  max-width: 360px;
  border: 1px solid #b9d8ff;
  box-shadow: 0 12px 32px rgba(59, 130, 246, 0.2);
  position: relative;
  text-align: center;
}

.auth-card-wide {
  max-width: 430px;
}

.logo {
  position: absolute;
  top: -40px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #60a5fa;
  color: #ffffff;
  width: 130px;
  height: 100px;
  border-radius: 75%;
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: bold;
  font-size: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.title {
  margin-top: 2rem;
  margin-bottom: 1.5rem;
  color: #133c6d;
  font-size: 1.5rem;
  font-weight: 600;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.input-group {
  position: relative;
  text-align: left;
}

.input-field {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #bfdcff;
  background-color: #ffffff;
  color: #163d66;
  border-radius: 8px;
  font-size: 1rem;
}

.input-field::placeholder {
  color: #6f92b9;
}

.input-field:focus {
  outline: none;
  border-color: #60a5fa;
  box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.2);
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #5e84ad;
}

.submit-btn {
  background-color: #60a5fa;
  color: #ffffff;
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.3s;
}

.submit-btn:hover:not(:disabled) {
  background-color: #3b82f6;
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.secondary-btn {
  border: 1px solid #b9d8ff;
  color: #1f4f82;
  background-color: #eaf4ff;
}

.secondary-btn:hover {
  border-color: #60a5fa;
  color: #0f3c70;
  background-color: #d7ebff;
}

.field-error {
  color: #1f4f82;
  font-size: 0.78rem;
  margin-top: 4px;
}
</style>
