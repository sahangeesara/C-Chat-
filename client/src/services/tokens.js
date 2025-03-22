export default {
  namespaced: true, // ✅ Important!
  state: {
    token: localStorage.getItem('token') || null,
  },
  mutations: {
    setToken(state, token) {
      state.token = token;
    },
    removeToken(state) {
      state.token = null;
    },
  },
  getters: {
    getToken: (state) => state.token,
  },
};
