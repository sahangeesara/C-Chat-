<template>
    <div class="container">
        <div class="row">
          <div class="col-12" >
            <div class="row">
              <router-view></router-view>   
            </div> 
        </div>  
      </div> 
    </div>
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
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #f60303;
  margin-top: 50px;
  
}

</style>
