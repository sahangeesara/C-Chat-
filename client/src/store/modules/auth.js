// src/store/modules/auth.js
export default {
    namespaced: true,
    state: () => ({
        token: localStorage.getItem('token') || null,
    }),
    mutations: {
        setToken(state, token) {
            state.token = token
            localStorage.setItem('token', token)
        },
        clearToken(state) {
            state.token = null
            localStorage.removeItem('token')
        },
    },
    actions: {
        saveToken({ commit }, token) {
            commit('setToken', token)
        },
        logout({ commit }) {
            commit('clearToken')
        },
    },
    getters: {
        isAuthenticated: (state) => !!state.token,
        getToken: (state) => state.token,
    },
}
