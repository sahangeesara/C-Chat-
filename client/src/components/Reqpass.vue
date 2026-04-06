<template>
  <div class="auth-container">
    <div class="auth-card auth-card-wide">
      <div class="logo">Chatrio</div>

      <h1 class="title">Request Password Reset</h1>

      <form @submit.prevent="onSubmit" autocomplete="off" class="auth-form">
        <div class="input-group">
          <input
            v-model.trim="userData.email"
            type="email"
            name="email"
            class="input-field"
            id="exampleInputEmail1"
            placeholder="Enter email"
            :class="{ 'input-invalid': errors.email }"
            required
          />
          <div v-if="errors.email" class="field-error">{{ errors.email[0] }}</div>
        </div>

        <button type="submit" class="submit-btn">
          Send Password reset Email
        </button>

        <router-link to="/" class="btn secondary-btn">Sign In</router-link>
      </form>
    </div>
  </div>
</template>

    <script>
import axios from "axios";
import { toast } from "vue3-toastify";


export default {
   data() {
    return {
      userData: {
        email: "",
      },
      errors: {},
    };
  },
  methods: {
    onSubmit() {
      toast("Wet............!", {
        autoClose: 1000,
        position: toast.POSITION.BOTTOM_RIGHT,
      });
      axios.post("http://localhost:8000/api/sendPasswordResetLink", this.userData)
      .then((response) => {
          this.handleResponse(response.data);
        })
        .catch((error) => {
     
          toast(error.response.data.error, {
            autoClose: 1000,
            position: toast.POSITION.BOTTOM_RIGHT,
          });
        });
    },
    handleResponse(res) {

      toast(res.data, {
        autoClose: 1000,
        position: toast.POSITION.BOTTOM_RIGHT,
      });
      this.userData.email = "";
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
          font-size: 1.35rem;
          font-weight: 600;
        }

        .auth-form {
          display: flex;
          flex-direction: column;
          gap: 1rem;
        }

        .input-group {
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

        .input-field:focus {
          outline: none;
          border-color: #60a5fa;
          box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.2);
        }

        .input-invalid {
          border-color: #60a5fa;
        }

        .field-error {
          color: #1f4f82;
          font-size: 0.78rem;
          margin-top: 4px;
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

  
