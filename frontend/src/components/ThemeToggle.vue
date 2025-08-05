<template>
  <button
    @click="themeStore.toggleTheme()"
    class="p-2 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center"
    :title="themeStore.isDark ? 'switch to light mode' : 'switch to dark mode'"
  >
    <!-- Sun (light mode) -->
    <svg
      v-if="themeStore.isDark"
      class="w-5 h-5 text-gray-600 dark:text-gray-300 transition-transform hover:rotate-12"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" 
      />
    </svg>
    
    <!-- Moon (dark mode) -->
    <svg
      v-else
      class="w-5 h-5 text-gray-600 dark:text-gray-300 transition-transform hover:rotate-12"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" 
      />
    </svg>
  </button>
</template>

<script setup lang="ts">
import { useThemeStore } from '@/stores/theme'

const themeStore = useThemeStore()
</script>

<style scoped>
svg {
  transition: transform 0.2s ease;
}

button:hover svg {
  transform: rotate(12deg);
}
</style>