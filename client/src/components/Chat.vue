<template>
  <div>
    <li class="list-group-item" :class="computedClass">
      <slot></slot>
      <small class="badge float-end bg-danger" :class="badgeClass">You</small>
    </li>
  </div>
</template>

<script>
export default {
  props: {
    color: {
      type: String,
      default: "primary", // Default to 'primary' if no color is provided
      validator: (value) => ["primary", "secondary", "success", "danger", "warning", "info", "light", "dark"].includes(value),
    },
  },
  computed: {
    computedClass() {
      return `list-group-item-${this.color}`;
    },
    badgeClass() {
      return `badge-${this.color}`;
    },
  },
};
</script>

<style scoped>
/* You can add additional styling if needed */
</style>
