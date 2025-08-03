<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
      <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Crear Cuenta</h2>
        <p class="text-gray-600 mt-2">Registra tu cuenta de manager</p>
      </div>
      
      <form @submit.prevent="handleRegister" class="space-y-6">
        <div>
          <input
            v-model="form.name"
            type="text"
            required
            class="form-input"
            placeholder="Nombre completo"
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
            placeholder="Contraseña"
          />
        </div>
        
        <div>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            class="form-input"
            placeholder="Confirmar contraseña"
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
          <span v-if="authStore.isLoading">Creando cuenta...</span>
          <span v-else>Crear cuenta</span>
        </button>
      </form>

      <div class="mt-6 text-center">
        <router-link
          to="/login"
          class="text-blue-600 hover:text-blue-500 text-sm"
        >
          ¿Ya tienes cuenta? Inicia sesión
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
  
  if (form.password !== form.password_confirmation) {
    authStore.error = 'Las contraseñas no coinciden'
    return
  }
  
  try {
    const response = await authStore.register(form)
    if (response.success) {
      router.push('/dashboard')
    }
  } catch (error) {
    console.error('Registration error:', error)
  }
}
</script>