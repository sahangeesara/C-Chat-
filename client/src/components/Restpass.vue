<template>
  <div class="auth-container">
    <div class="auth-card auth-card-wide">
      <div class="logo">Skytalk</div>

      <h1 class="title">Reset Password</h1>

      <form @submit.prevent="onSubmit" autocomplete="off" class="auth-form">
        <div class="input-group">
          <input
            type="email"
            name="email"
            id="exampleInputEmail1"
            v-model.trim="userData.email"
            placeholder="Email"
            class="input-field"
            required
          >
        </div>

        <div class="input-group">
          <input
            :type="hide ? 'password' : 'text'"
            class="input-field"
            id="exampleInputPassword1"
            name="password"
            v-model="userData.password"
            placeholder="New Password"
            required
          >
          <span class="password-toggle" @click="hide = !hide">
            <i :class="hide ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
          </span>
        </div>

        <div class="input-group">
          <input
            :type="hide2 ? 'password' : 'text'"
            class="input-field"
            name="password_confirmation"
            id="exampleInputPassword2"
            v-model="userData.password_confirmation"
            placeholder="Confirm Password"
            required
          >
          <span class="password-toggle" @click="hide2 = !hide2">
            <i :class="hide2 ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
          </span>
        </div>

        <button type="submit" class="submit-btn">Change Password</button>
        <router-link to="/" class="btn secondary-btn">Sign In</router-link>
      </form>
    </div>
  </div>
</template>

  
    <script>

    import { toast } from "vue3-toastify";
    import axios from "axios";

    export default {
      data() {
        return {
          hide: true,
          hide2: true,
          userData: {
            email: '',
            password: '',
            password_confirmation: '',
            resetToken: '',
          },
        };
      },

      mounted() {
        this.userData.resetToken = this.$route.query.token;
      },

      methods: {
        onSubmit() {
          axios.post("http://localhost:8000/api/resetPassword", this.userData)
              .then(() => {
                toast("Done! Now login with new password", {
                  autoClose: 1000,
                  position: 'bottom-right',
                });
                this.$router.push('/');
              })
              .catch(error => {
                toast(error.response.data.error, {
                  autoClose: 1000,
                  position: 'bottom-right',
                });
              });
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

.submit-btn:hover {
  background-color: #3b82f6;
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
</style>
