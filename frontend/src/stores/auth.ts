// stores/auth.ts
import { defineStore } from 'pinia'
import { authApi } from '@/services/api'

interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'manager' | 'customer'
  is_active: boolean
  created_at?: string
  updated_at?: string
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  sessionExpired: boolean
  error: string | null
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: localStorage.getItem('token'),
    isAuthenticated: false,
    sessionExpired: false,
    error: null,
  }),

  getters: {
    isAdmin: (state): boolean => state.user?.role === 'admin' || false,
    isManager: (state): boolean => state.user?.role === 'manager' || false,
    isCustomer: (state): boolean => state.user?.role === 'customer' || false,

    // Safe fallback user display name
    userDisplayName: (state): string => {
      if (!state.isAuthenticated || !state.user) return 'User'
      return state.user.name || 'User'
    },

    userDisplayRole: (state): string => {
      if (!state.isAuthenticated || !state.user) return 'user'

      const roleMap = {
        admin: 'Administrator',
        manager: 'Manager',
        customer: 'Customer'
      }

      return roleMap[state.user.role] || 'user'
    }
  },

  actions: {
    async login(credentials: { email: string; password: string }) {
      try {
        console.log('Attempting login with:', credentials.email)

        const response = await authApi.login(credentials)
        console.log('Login response:', response)

        if (!response.success) {
          throw new Error(response.error || 'Login failed')
        }

        if (!response.data) {
          throw new Error('No data received from server')
        }

        const { access_token, user } = response.data

        if (!access_token) throw new Error('No access token received')
        if (!user) throw new Error('No user data received')

        this.token = access_token
        this.user = user
        this.isAuthenticated = true
        this.sessionExpired = false

        localStorage.setItem('token', access_token)
        localStorage.setItem('user', JSON.stringify(user))

        console.log('Login successful, user:', user)
        return { success: true }

      } catch (error) {
        console.error('Login error:', error)
        this.logout()

        let errorMessage = 'Login failed'
        if (error instanceof Error) {
          errorMessage = error.message
        } else if (typeof error === 'string') {
          errorMessage = error
        }

        return {
          success: false,
          error: errorMessage
        }
      }
    },

    async register(userData: any) {
      try {
        this.clearError()

        const response = await authApi.register(userData)

        if (!response.success) {
          throw new Error(response.error || 'Registration failed')
        }

        const { access_token, user } = response.data

        this.token = access_token
        this.user = user
        this.isAuthenticated = true
        this.sessionExpired = false

        localStorage.setItem('token', access_token)
        localStorage.setItem('user', JSON.stringify(user))

        return { success: true }
      } catch (error: any) {
        this.logout()

        const errorMessage =
          error.response?.data?.message ||
          error.response?.data?.errors?.[0] ||
          (error instanceof Error ? error.message : 'Registration failed')

        this.error = errorMessage

        return {
          success: false,
          error: errorMessage
        }
      }
    },

    async logout() {
      try {
        if (this.token && this.isAuthenticated) {
          await authApi.logout()
        }
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.user = null
        this.token = null
        this.isAuthenticated = false
        this.sessionExpired = false

        localStorage.removeItem('token')
        localStorage.removeItem('user')

        console.log('Logout completed')
      }
    },

    async handleTokenExpiration() {
      console.log('Handling token expiration...')

      this.sessionExpired = true
      this.isAuthenticated = false

      const currentUser = this.user
      this.token = null
      localStorage.removeItem('token')

      try {
        await authApi.logout()
      } catch (error) {
        console.error('Server logout failed during token expiration:', error)
      }

      setTimeout(() => {
        this.user = null
        localStorage.removeItem('user')
      }, 1000)

      return currentUser
    },

    async initialize() {
      try {
        const token = localStorage.getItem('token')
        const userStr = localStorage.getItem('user')

        if (!token || !userStr) {
          console.log('No stored credentials found')
          return false
        }

        let user: User
        try {
          user = JSON.parse(userStr)
        } catch (error) {
          console.error('Invalid user data in localStorage:', error)
          this.logout()
          return false
        }

        const response = await authApi.getProfile()

        if (response.success && response.data) {
          this.token = token
          this.user = response.data.user || response.data
          this.isAuthenticated = true
          this.sessionExpired = false

          localStorage.setItem('user', JSON.stringify(this.user))

          console.log('Auth initialized successfully:', this.user)
          return true
        } else {
          console.log('Token is invalid, handling expiration...')
          await this.handleTokenExpiration()
          return false
        }

      } catch (error) {
        console.error('Auth initialization error:', error)

        if (error && typeof error === 'object' && 'status' in error && error.status === 401) {
          await this.handleTokenExpiration()
        } else {
          this.logout()
        }
        return false
      }
    },

    async refreshToken() {
      try {
        if (!this.token) {
          throw new Error('No token to refresh')
        }

        const response = await authApi.refresh()

        if (!response.success || !response.data) {
          throw new Error('Token refresh failed')
        }

        const { access_token, user } = response.data

        if (!access_token) {
          throw new Error('No new token received')
        }

        this.token = access_token
        if (user) {
          this.user = user
          localStorage.setItem('user', JSON.stringify(user))
        }

        localStorage.setItem('token', access_token)
        this.sessionExpired = false

        console.log('Token refreshed successfully')
        return { success: true }

      } catch (error) {
        console.error('Token refresh error:', error)
        await this.handleTokenExpiration()
        return { success: false, error: error instanceof Error ? error.message : 'Token refresh failed' }
      }
    },

    async updateProfile(profileData: any) {
      try {
        const response = await authApi.updateProfile(profileData)

        if (!response.success) {
          throw new Error(response.error || 'Profile update failed')
        }

        if (response.data) {
          this.user = response.data
          localStorage.setItem('user', JSON.stringify(response.data))
        }

        return { success: true }

      } catch (error) {
        console.error('Profile update error:', error)
        return {
          success: false,
          error: error instanceof Error ? error.message : 'Profile update failed'
        }
      }
    },

    async changePassword(passwordData: any) {
      try {
        const response = await authApi.changePassword(passwordData)

        if (!response.success) {
          throw new Error(response.error || 'Password change failed')
        }

        return { success: true }

      } catch (error) {
        console.error('Password change error:', error)
        return {
          success: false,
          error: error instanceof Error ? error.message : 'Password change failed'
        }
      }
    },

    clearSessionExpired() {
      this.sessionExpired = false
    },

    clearError() {
      this.error = null
    }
  }
})
