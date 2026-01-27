<template>
    <div class="contemer">
  <div class="row" style="padding-top: 100px !important;">
    <div class="col-4"></div>
        <div class="card p-0 col-6" style="height: 400px !important; width: 500px;">
          <div class="card-header bg-primary" style="color:white"> Reset Password Here </div>
       <div class="card-body  bground">
              <form  @submit.prevent="onSubmit" autocomplete="off">
                      <div class="form-group d-lg-inline row">
                              <label for="exampleInputEmail1">Email address :</label>
                              <input type="email" class="form-control col-4 " name="email" id="exampleInputEmail1" aria-describedby="emailHelp" v-model="userData.email" placeholder="Enter email" required>
                      </div>
                          <br>
                      <div class="form-group row d-lg-inline">
                              <label for="exampleInputPassword1">New Password :</label>
                              <input v-bind:type="hide ? 'password' : 'text'"   class="form-control" id="exampleInputPassword1" name="password" v-model="userData.password" placeholder="New Password" required>
                             <b @click="hide = !hide" :aria-label="'Hide password'" :aria-pressed="hide">
                                 <i class="material-icons">{{ hide ? 'visibility_off' : 'visibility' }}</i>
                              </b> 
                       </div>
                          <br>
                          <div class="form-group row d-lg-inline">
                              <label for="exampleInputPassword1">Comform Password :</label>
                              <input v-bind:type="hide2 ? 'password' : 'text'" class="form-control" name="password_confirmation" id="exampleInputPassword1" v-model="userData.password_confirmation" placeholder="Comform Password" required>
                              <b @click="hide2 = !hide2" :aria-label="'Hide password'" :aria-pressed="hide2">
                                     <i class="material-icons">{{ hide2 ? 'visibility_off' : 'visibility' }}</i>
                             </b> 
                            </div>
                          <br>
                          <div class=" float-lg-start">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                          </div>
              </form>
          </div>
       </div>
       <div class="col-4"></div>
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
  h1 {
    color: aqua;
  }
  .text-center {
    padding: 200px 500px;
  }
  .bground{
      background: rgb(2,0,36);
      background: linear-gradient(90deg, rgba(2,0,36,1) 0%, rgba(221,255,222,0.050945378151260545) 0%, rgba(221,242,218,0.48792016806722693) 84%);
  }
  
  </style>
    
