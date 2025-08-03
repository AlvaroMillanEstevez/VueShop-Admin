<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'

const authStore = useAuthStore()
const themeStore = useThemeStore()

onMounted(() => {
  // Initialize auth state when app starts
  if (authStore.token && !authStore.user) {
    authStore.initialize()
  }
  
  // Initialize theme
  themeStore.initTheme()
})
</script>

<style>
#app {
  min-height: 100vh;
}
</style>