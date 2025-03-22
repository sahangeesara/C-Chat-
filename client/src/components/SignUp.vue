<template>
  <div class="container">
    <div class="row justify-content-center" style="padding-top: 50px">
      <div class="card p-0 col-md-6 col-sm-12" style="height: 650px; width: 500px;">
        <div class="card-header bg-primary text-white text-center">Register Here</div>
        <div class="card-body bground">
          <form @submit.prevent="onSubmit">
            <!-- Name Input -->
            <div class="mb-3">
              <label for="name" class="form-label">Name:</label>
              <input type="text" class="form-control" id="name" v-model.trim="userData.name" placeholder="Enter name" required />
              <div v-if="error.name" class="text-danger">{{ error.name[0] }}</div>
            </div>

            <!-- Email Input -->
            <div class="mb-3">
              <label for="email" class="form-label">Email address:</label>
              <input type="email" class="form-control" id="email" v-model.trim="userData.email" placeholder="Enter email" required />
              <div v-if="error.email" class="text-danger">{{ error.email[0] }}</div>
            </div>

            <!-- Password Input -->
            <div class="mb-3 position-relative">
              <label for="password" class="form-label">Password:</label>
              <input :type="hide ? 'password' : 'text'" class="form-control" id="password" v-model="userData.password" placeholder="Enter password" required />
              <i class="bi" :class="hide ? 'bi-eye-slash' : 'bi-eye'" @click="hide = !hide" style="cursor: pointer; position: absolute; right: 10px; top: 40px;"></i>
              <div v-if="error.password" class="text-danger">{{ error.password[0] }}</div>
            </div>

            <!-- Confirm Password Input -->
            <div class="mb-3 position-relative">
              <label for="password_confirmation" class="form-label">Confirm Password:</label>
              <input :type="hide2 ? 'password' : 'text'" class="form-control" id="password_confirmation" v-model="userData.password_confirmation" placeholder="Confirm password" required />
              <i class="bi" :class="hide2 ? 'bi-eye-slash' : 'bi-eye'" @click="hide2 = !hide2" style="cursor: pointer; position: absolute; right: 10px; top: 40px;"></i>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-between mt-3">
              <button type="submit" class="btn btn-primary" :disabled="loading">
                {{ loading ? 'Registering...' : 'Register' }}
              </button>
              <router-link to="/" class="btn btn-outline-primary">Sign In</router-link>
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
.bground {
  background: linear-gradient(90deg, rgba(2, 0, 36, 1) 0%, rgba(221, 255, 222, 0.05) 0%, rgba(221, 242, 218, 0.48) 84%);
}
</style>
