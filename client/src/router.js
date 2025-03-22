
import ChatApp from './components/ChatApp.vue';
import Login from './components/Login.vue';
import SignUp from './components/SignUp.vue';
import Reqpass from './components/Reqpass.vue';
import Restpass from './components/Restpass.vue';
import { createRouter, createWebHistory } from "vue-router";

const routes = [
    { path: '/', component: Login },
    { path: '/chatapp', component: ChatApp },
    { path: '/signup', component: SignUp },
    { path: '/request-password-reset', component: Reqpass },
    { path: '/resetpassword', component: Restpass },
   
  //   { path: '/:pathMatch(.*)*', component: NotFound }, // Catch-all route for 404 handling
  ];const router = createRouter({
    history: createWebHistory(),
    routes,
  });
  
 

export default routes;
