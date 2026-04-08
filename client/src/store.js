import { createStore } from 'vuex';
import auth from './store/modules/auth';
export default createStore({
  modules: {
    auth
  },
  state: {
    loggedIn: JSON.parse(localStorage.getItem('loggedIn')) || false,
    token: localStorage.getItem('token') || null, // ✅ Store token
  },
  getters: {
    isAuthenticated: (state) => state.loggedIn || !!localStorage.getItem('token'),
    getToken: (state) => state.token || localStorage.getItem('token') || null, // ✅ Token getter
  },
  mutations: {
    setLoggedIn(state, value) {
      state.loggedIn = value;
      localStorage.setItem('loggedIn', JSON.stringify(value));
    },
    setToken(state, token) {
      state.token = token;
      if (token) {
        localStorage.setItem('token', token);
      } else {
        localStorage.removeItem('token'); // ✅ Remove if logout
      }
    },
  },
  actions: {
    saveToken({ commit }, token) {
      commit('setToken', token);
      commit('setLoggedIn', true);
      commit('auth/setToken', token, { root: true });
    },
    logout({ commit }) {
      commit('setLoggedIn', false);
      commit('setToken', null);
      commit('auth/clearToken', null, { root: true });
    },
  },
});
