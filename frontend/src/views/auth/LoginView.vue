<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
      <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900">VueShop Admin</h2>
        <p class="text-gray-600 mt-2">Sign in to your account</p>
      </div>
      
      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Email address"
          />
        </div>
        
        <div>
          <input
            v-model="form.password"
            type="password"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Password"
          />
        </div>

        <div v-if="authStore.error" class="bg-red-50 border border-red-200 rounded p-3 text-red-600 text-sm">
          {{ authStore.error }}
        </div>

        <button
          type="submit"
          :disabled="authStore.isLoading"
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
        >
          <span v-if="authStore.isLoading">Signing in...</span>
          <span v-else>Sign in</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const form = reactive({
  email: '',
  password: '',
})

const handleLogin = async () => {
  authStore.clearError()
  const result = await authStore.login(form)
  if (result.success) {
    router.push('/dashboard')
  }
}
</script>