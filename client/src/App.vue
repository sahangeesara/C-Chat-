<template>
  <router-view></router-view>
</template>

<script>

import { mapGetters } from "vuex";

export default {

  computed: {
    ...mapGetters(["getToken", "isAuthenticated"]), // ✅ Ensure correct mapping
  },
};
</script>

<style>
@import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css");

html,
body,
#app {
  height: 100%;
  margin: 0;
  padding: 0;
}

#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
}

</style>
