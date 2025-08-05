<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
      <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
        <p class="text-gray-600 mt-2">Register your manager account</p>
      </div>

      <form @submit.prevent="handleRegister" class="space-y-6">
        <div>
          <input
            v-model="form.name"
            type="text"
            required
            class="form-input"
            placeholder="Full Name"
          />
        </div>

        <div>
          <input
            v-model="form.email"
            type="email"
            required
            class="form-input"
            placeholder="Email"
          />
        </div>

        <div>
          <input
            v-model="form.password"
            type="password"
            required
            class="form-input"
            placeholder="Password"
          />
        </div>

        <div>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            class="form-input"
            placeholder="Confirm Password"
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
          <span v-if="authStore.isLoading">Creating account...</span>
          <span v-else>Create Account</span>
        </button>
      </form>

      <div class="mt-6 text-center">
        <router-link
          to="/login"
          class="text-blue-600 hover:text-blue-500 text-sm"
        >
          Already have an account? Sign in
        </router-link>
      </div>
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
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const handleRegister = async () => {
  authStore.clearError()

  // Basic frontend validation
  if (!form.name.trim()) {
    authStore.error = 'Name is required'
    return
  }

  if (!form.email.trim() || !form.email.includes('@')) {
    authStore.error = 'Enter a valid email'
    return
  }

  if (!form.password || form.password.length < 6) {
    authStore.error = 'Password must be at least 6 characters'
    return
  }

  if (form.password !== form.password_confirmation) {
    authStore.error = 'Passwords do not match'
    return
  }

  const response = await authStore.register(form)

  if (response.success) {
    router.push('/dashboard')
  } else {
    authStore.error = response.error || 'User registration failed'
  }
}
</script>
