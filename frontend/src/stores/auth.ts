import { defineStore } from 'pinia'
import { authApi } from '@/services/api'

interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'manager'
}

interface AuthState {
  user: User | null
  token: string | null
  isLoading: boolean
  error: string | null
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: localStorage.getItem('token'),
    isLoading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state): boolean => !!state.token,
    userRole: (state): string => state.user?.role || 'manager',
    isAdmin: (state): boolean => state.user?.role === 'admin',
    isManager: (state): boolean => state.user?.role === 'manager',
    canManageProducts: (state): boolean => ['admin', 'manager'].includes(state.user?.role || ''),
    canManageOrders: (state): boolean => ['admin', 'manager'].includes(state.user?.role || ''),
    canManageCustomers: (state): boolean => ['admin', 'manager'].includes(state.user?.role || ''),
  },

  actions: {
    async login(credentials: { email: string; password: string }) {
      this.isLoading = true
      this.error = null
      
      try {
        console.log('Attempting login with:', credentials.email)
        const response = await authApi.login(credentials)
        console.log('Login response:', response)
        
        // Laravel JWT devuelve access_token, no token
        const token = response.access_token || response.token
        
        if (token && response.user) {
          this.token = token
          this.user = response.user
          localStorage.setItem('token', token)
          return { success: true }
        } else {
          this.error = 'Invalid response from server'
          return { success: false, error: this.error }
        }
      } catch (error: any) {
        console.error('Login error:', error)
        console.error('Error response:', error.response?.data)
        
        if (error.response?.data?.message) {
          this.error = error.response.data.message
        } else if (error.message) {
          this.error = error.message
        } else {
          this.error = 'Network error or server unavailable'
        }
        return { success: false, error: this.error }
      } finally {
        this.isLoading = false
      }
    },

    async logout() {
      try {
        await authApi.logout()
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.user = null
        this.token = null
        localStorage.removeItem('token')
      }
    },

    async refreshToken() {
      try {
        const response = await authApi.refresh()
        
        // Laravel JWT devuelve access_token
        const token = response.access_token || response.token
        
        if (token) {
          this.token = token
          localStorage.setItem('token', token)
          return token
        } else {
          throw new Error('Token refresh failed')
        }
      } catch (error) {
        this.logout()
        throw error
      }
    },

    async initialize() {
      if (!this.token) return
      
      try {
        const response = await authApi.getProfile()
        
        if (response.user) {
          this.user = response.user
        } else {
          this.logout()
        }
      } catch (error) {
        console.error('Initialize auth error:', error)
        this.logout()
      }
    },

    async updateProfile(profileData: { name?: string; email?: string; avatar?: string }) {
      this.isLoading = true
      this.error = null
      
      try {
        const response = await authApi.updateProfile(profileData)
        
        if (response.success) {
          this.user = response.user
          return { success: true }
        } else {
          this.error = response.message || 'Profile update failed'
          return { success: false, error: this.error }
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Profile update failed'
        return { success: false, error: this.error }
      } finally {
        this.isLoading = false
      }
    },

    async changePassword(passwordData: { current_password: string; new_password: string; new_password_confirmation: string }) {
      this.isLoading = true
      this.error = null
      
      try {
        const response = await authApi.changePassword(passwordData)
        
        if (response.success) {
          return { success: true }
        } else {
          this.error = response.message || 'Password change failed'
          return { success: false, error: this.error }
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Password change failed'
        return { success: false, error: this.error }
      } finally {
        this.isLoading = false
      }
    },

        async register(userData: { name: string; email: string; password: string; password_confirmation: string }) {
      this.isLoading = true
      this.error = null
      
      try {
        const response = await authApi.register(userData)
        
        // Laravel JWT devuelve access_token
        const token = response.access_token || response.token
        
        if (token && response.user) {
          this.token = token
          this.user = response.user
          localStorage.setItem('token', token)
          return { success: true }
        } else {
          this.error = 'Registration failed'
          return { success: false, error: this.error }
        }
      } catch (error: any) {
        if (error.response?.data?.message) {
          this.error = error.response.data.message
        } else if (error.response?.data?.errors) {
          // Manejar errores de validación
          const errors = error.response.data.errors
          this.error = Object.values(errors).flat().join(', ')
        } else {
          this.error = 'Registration failed'
        }
        return { success: false, error: this.error }
      } finally {
        this.isLoading = false
      }
    },
    clearError() {
      this.error = null
    }
  }
})