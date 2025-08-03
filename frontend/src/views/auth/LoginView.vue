<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Sign in to your account
        </h2>
      </div>
      
      <!-- Session Expired Message -->
      <div v-if="sessionExpiredMessage" class="bg-amber-50 border border-amber-200 rounded-md p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-amber-800">Session Expired</h3>
            <div class="mt-2 text-sm text-amber-700">
              <p>{{ sessionExpiredMessage }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Login Error</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ error }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Message -->
      <div v-if="success" class="bg-green-50 border border-green-200 rounded-md p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-green-800">Login successful! Redirecting...</p>
          </div>
        </div>
      </div>
      
      <form @submit.prevent="handleLogin" class="mt-8 space-y-6">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">Email address</label>
            <input
              id="email"
              v-model="form.email"
              name="email"
              type="email"
              autocomplete="email"
              required
              :disabled="loading"
              class="auth-input"
              placeholder="Email address"
            >
          </div>
          <div>
            <label for="password" class="sr-only">Password</label>
            <input
              id="password"
              v-model="form.password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
              :disabled="loading"
              class="auth-input"
              placeholder="Password"
            >
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember-me"
              v-model="form.remember"
              name="remember-me"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            >
            <label for="remember-me" class="ml-2 block text-sm text-gray-900">
              Remember me
            </label>
          </div>

          <div class="text-sm">
            <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
              Forgot your password?
            </a>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <svg v-if="loading" class="animate-spin h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
              </svg>
            </span>
            {{ loading ? 'Signing in...' : 'Sign in' }}
          </button>
        </div>

        <div class="text-center">
          <span class="text-sm text-gray-600">
            Don't have an account?
            <router-link to="/register" class="font-medium text-indigo-600 hover:text-indigo-500">
              Sign up
            </router-link>
          </span>
        </div>
      </form>

      <!-- Debug Info (only in development) -->
      <div v-if="showDebug" class="mt-8 p-4 bg-gray-100 rounded-lg">
        <h4 class="text-sm font-medium text-gray-900 mb-2">Debug Info:</h4>
        <div class="text-xs text-gray-600 space-y-1">
          <div>Loading: {{ loading }}</div>
          <div>Error: {{ error || 'None' }}</div>
          <div>Session Expired: {{ sessionExpiredMessage || 'None' }}</div>
          <div>Route Query: {{ JSON.stringify(route.query) }}</div>
          <div>Form: {{ JSON.stringify(form, null, 2) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// State
const loading = ref<boolean>(false)
const error = ref<string>('')
const success = ref<boolean>(false)
const sessionExpiredMessage = ref<string>('')

// Form data
const form = reactive({
  email: 'admin@vueshop.com', // Pre-filled for testing
  password: 'password123', // Pre-filled for testing
  remember: false
})

// Show debug info in development
const showDebug = computed(() => {
  return import.meta.env.DEV && import.meta.env.VITE_APP_DEBUG === 'true'
})

// Check for session expired message on mount
onMounted(() => {
  const message = route.query.message as string
  
  if (message === 'session_expired') {
    sessionExpiredMessage.value = 'Your session has expired due to inactivity. Please log in again to continue.'
    
    // Clear the query parameter from URL after a delay
    setTimeout(() => {
      router.replace({ query: { ...route.query, message: undefined } })
    }, 100)
  }
  
  // Clear session expired flag in store
  if (authStore.clearSessionExpired) {
    authStore.clearSessionExpired()
  }
})

const handleLogin = async () => {
  try {
    loading.value = true
    error.value = ''
    success.value = false
    sessionExpiredMessage.value = ''
    
    // Validación básica
    if (!form.email.trim()) {
      throw new Error('Email is required')
    }
    
    if (!form.password.trim()) {
      throw new Error('Password is required')
    }
    
    if (!isValidEmail(form.email)) {
      throw new Error('Please enter a valid email address')
    }
    
    console.log('Starting login process...')
    
    // Intentar login
    const result = await authStore.login({
      email: form.email.trim(),
      password: form.password
    })
    
    console.log('Login result:', result)
    
    if (result.success) {
      success.value = true
      console.log('Login successful, redirecting...')
      
      // Check if there's a redirect URL from the query
      const redirectTo = route.query.redirect as string || '/dashboard'
      
      // Pequeña pausa para mostrar el mensaje de éxito
      setTimeout(() => {
        router.push(redirectTo)
      }, 1000)
      
    } else {
      throw new Error(result.error || 'Login failed')
    }
    
  } catch (err) {
    console.error('Login error in component:', err)
    
    // Determinar mensaje de error apropiado
    if (err instanceof Error) {
      error.value = err.message
    } else if (typeof err === 'string') {
      error.value = err
    } else {
      error.value = 'An unexpected error occurred during login'
    }
    
    success.value = false
    
  } finally {
    loading.value = false
  }
}

// Helper function para validar email
const isValidEmail = (email: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// Limpiar error cuando el usuario empiece a escribir
const clearError = () => {
  if (error.value) {
    error.value = ''
  }
  if (sessionExpiredMessage.value) {
    sessionExpiredMessage.value = ''
  }
}

// Watch form changes to clear errors
import { watch } from 'vue'
watch([() => form.email, () => form.password], clearError)
</script>