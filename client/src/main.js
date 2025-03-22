import { createApp } from "vue";
import { createStore } from "vuex";
import App from "./App.vue";
import routes from "./router";
import { createRouter, createWebHashHistory } from "vue-router";
import Vue3Toasity from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import store from './store';
import echo from './services/echo'; // Import Echo

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

const app = createApp(App);

app.use(store); // ✅ Use the merged store
app.use(router);
app.use(Vue3Toasity, { autoClose: 3000 });

app.config.globalProperties.$echo = echo;
app.mount("#app");
