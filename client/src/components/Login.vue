<template>
  <div class="container">
    <div class="row justify-content-center" style="padding-top: 100px">
      <div class="card p-0 col-md-6 col-sm-12" style="height: 506px; width: 500px;">
        <div class="card-header bg-primary text-white text-center">Login Here</div>
        <div class="card-body bground">
          <form @submit.prevent="onSubmit" autocomplete="off">
            <!-- Error Alert -->
            <div v-if="error" class="alert alert-danger">
              {{ error }}
            </div>

            <!-- Email Input -->
            <div class="mb-3">
              <label for="email" class="form-label">Email address:</label>
              <input type="email" class="form-control" id="email" v-model.trim="userData.email" placeholder="Enter email" required />
            </div>

            <!-- Password Input -->
            <div class="mb-3 position-relative">
              <label for="password" class="form-label">Password:</label>
              <input :type="hide ? 'password' : 'text'" class="form-control" id="password" v-model.trim="userData.password" placeholder="Enter password" required />
              <i class="bi" :class="hide ? 'bi-eye-slash' : 'bi-eye'" @click="hide = !hide" style="cursor: pointer; position: absolute; right: 10px; top: 40px;"></i>
            </div>

            <!-- Remember Me Checkbox -->
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="rememberMe" />
              <label class="form-check-label text-danger" for="rememberMe">Remember me</label>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-between mt-3">
              <button type="submit" class="btn btn-primary" :disabled="loading">
                {{ loading ? 'Signing in...' : 'Sign in' }}
              </button>
              <router-link to="/signup" class="btn btn-outline-primary">Sign Up</router-link>
            </div>

            <!-- Forgot Password -->
            <div class="text-end mt-2">
              <router-link to="/request-password-reset">Reset password</router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import { mapActions } from "vuex";

export default {
  data() {
    return {
      error: null,
      hide: true,
      loading: false, // Prevents multiple submissions
      userData: {
        email: "",
        password: "",
      },
    };
  },
  methods: {
    ...mapActions(["saveToken"]), // ✅ Correct action name

    async onSubmit() {
      if (this.loading) return;
      this.loading = true;
      this.error = null; // ✅ Clear previous errors

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
        this.$store.commit("authsev/setToken", data.access_token); // ✅ Save token
        axios.defaults.headers.common["Authorization"] = `Bearer ${data.access_token}`; // ✅ Attach globally
        this.$router.push("/chatapp"); // ✅ Redirect to chat app
      } else {
        this.error = "Invalid login response.";
      }
    },

    handleError(error) {
      this.error = error.response?.data?.message || "Login failed. Please check your credentials.";
    },
  },
};
</script>


<style scoped>
.bground {
  background: linear-gradient(90deg, rgba(2, 0, 36, 1) 0%, rgba(221, 255, 222, 0.05) 0%, rgba(221, 242, 218, 0.48) 84%);
}
</style>
