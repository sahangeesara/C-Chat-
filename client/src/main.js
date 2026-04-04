import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import routes from './router'
import store from './store'
import axios from 'axios'
import Vue3Toastify from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

// ✅ Restore token on refresh
const token = localStorage.getItem('token')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  store.commit('auth/setToken', token)
}

// ✅ Setup router
const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

// ✅ Create app
const app = createApp(App)
app.use(store)
app.use(router)
app.use(Vue3Toastify, { autoClose: 3000 })

app.mount('#app')
