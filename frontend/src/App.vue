<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  // Initialize auth state when app starts
  if (authStore.token && !authStore.user) {
    authStore.initialize()
  }
})
</script>

<style>
#app {
  min-height: 100vh;
}
</style>