<template>
  <div class="login-container">
    <div class="login-card">
      <div class="logo">CH-CHAT</div>

      <h1 class="title">Login</h1>

      <form @submit.prevent="onSubmit" class="login-form">
        <div v-if="error" class="error-message">{{ error }}</div>

        <div class="input-group">
          <input
              type="email"
              v-model.trim="userData.email"
              placeholder="Email"
              required
              class="input-field"
          >
        </div>

        <div class="input-group">
          <input
              :type="hide ? 'password' : 'text'"
              v-model.trim="userData.password"
              placeholder="Password"
              required
              class="input-field"
          >
          <span
              class="password-toggle"
              @click="hide = !hide"
          >
            <i :class="hide ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
          </span>
        </div>

        <button
            type="submit"
            class="submit-btn"
            :disabled="loading"
        >
          <span v-if="!loading">Sign in</span>
          <span v-else class="spinner-container">
            <i class="bi bi-arrow-repeat spinner"></i> Processing...
          </span>
        </button>

       <router-link to="/signup" class="btn btn-outline-primary">Sign up</router-link>

        <div class="forgot-password">
          <router-link to="/request-password-reset">Forgot your password?</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import { mapActions } from "vuex";

export default {
  name: 'LoginForm',
  data() {
    return {
      error: null,
      hide: true,
      loading: false,
      userData: {
        email: "",
        password: "",
      },
    };
  },
  methods: {
    ...mapActions(["saveToken"]),

    async onSubmit() {
      if (this.loading) return;
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.post("http://localhost:8000/api/login", this.userData);
        this.handleResponse(response.data);
      } catch (error) {
        this.handleError(error);
      } finally {
        this.loading = false;
      }
    },

    handleResponse(data) {
      if (data.access_token) {
        this.$store.commit("authsev/setToken", data.access_token);
        axios.defaults.headers.common["Authorization"] = `Bearer ${data.access_token}`;
        this.$router.push("/chatapp");
      } else {
        this.error = "Invalid login response.";
      }
    },

    handleError(error) {
      this.error = error.response?.data?.message || "Login failed. Please check your credentials.";
    },
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #3b82f6;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.login-card {
  background: white;
  border-radius: 30px;
  padding: 2rem;
  width: 100%;
  max-width: 360px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  position: relative;
  text-align: center;
}

.logo {
  position: absolute;
  top: -40px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #2563eb;
  color: white;
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
  color: #333;
  font-size: 1.5rem;
  font-weight: 600;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.input-group {
  position: relative;
}

.input-field {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.input-field:focus {
  outline: none;
  border-color: #3b82f6;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  user-select: none;
  color: #6b7280;
}

.submit-btn {
  background-color: #2563eb;
  color: white;
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.3s;
  margin-top: 1rem;
  height: 44px;
}

.submit-btn:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.forgot-password {
  margin-top: 1rem;
}

.forgot-password a {
  color: #3b82f6;
  text-decoration: none;
  font-size: 0.875rem;
}

.forgot-password a:hover {
  text-decoration: underline;
}

.error-message {
  color: white;
  background-color: #ef4444;
  padding: 8px 12px;
  border-radius: 4px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.spinner-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>