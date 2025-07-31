// stores/auth.ts
import { defineStore } from 'pinia'
import { authApi } from '@/services/api'

interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'seller' | 'customer'
  is_active: boolean
  created_at?: string
  updated_at?: string
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: localStorage.getItem('token'),
    isAuthenticated: false,
  }),

  getters: {
    isAdmin: (state): boolean => {
      return state.user?.role === 'admin' || false
    },
    
    isSeller: (state): boolean => {
      return state.user?.role === 'seller' || false
    },
    
    isCustomer: (state): boolean => {
      return state.user?.role === 'customer' || false
    }
  },

  actions: {
    async login(credentials: { email: string; password: string }) {
      try {
        console.log('Attempting login with:', credentials.email)
        
        const response = await authApi.login(credentials)
        console.log('Login response:', response)
        
        // Verificar que la respuesta sea exitosa
        if (!response.success) {
          throw new Error(response.error || 'Login failed')
        }
        
        // Verificar que tenemos los datos necesarios
        if (!response.data) {
          throw new Error('No data received from server')
        }
        
        const { access_token, user } = response.data
        
        if (!access_token) {
          throw new Error('No access token received')
        }
        
        if (!user) {
          throw new Error('No user data received')
        }
        
        // Guardar token y usuario
        this.token = access_token
        this.user = user
        this.isAuthenticated = true
        
        // Persistir en localStorage
        localStorage.setItem('token', access_token)
        localStorage.setItem('user', JSON.stringify(user))
        
        console.log('Login successful, user:', user)
        return { success: true }
        
      } catch (error) {
        console.error('Login error:', error)
        
        // Limpiar estado en caso de error
        this.logout()
        
        // Determinar el mensaje de error apropiado
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
        console.log('Attempting registration...')
        
        const response = await authApi.register(userData)
        console.log('Registration response:', response)
        
        if (!response.success) {
          throw new Error(response.error || 'Registration failed')
        }
        
        if (!response.data) {
          throw new Error('No data received from server')
        }
        
        const { access_token, user } = response.data
        
        if (!access_token || !user) {
          throw new Error('Invalid response from server')
        }
        
        // Guardar token y usuario
        this.token = access_token
        this.user = user
        this.isAuthenticated = true
        
        // Persistir en localStorage
        localStorage.setItem('token', access_token)
        localStorage.setItem('user', JSON.stringify(user))
        
        console.log('Registration successful, user:', user)
        return { success: true }
        
      } catch (error) {
        console.error('Registration error:', error)
        
        this.logout()
        
        let errorMessage = 'Registration failed'
        if (error instanceof Error) {
          errorMessage = error.message
        }
        
        return { 
          success: false, 
          error: errorMessage 
        }
      }
    },

    async logout() {
      try {
        // Intentar hacer logout en el servidor si tenemos token
        if (this.token) {
          await authApi.logout()
        }
      } catch (error) {
        console.error('Logout error:', error)
        // Continuar con el logout local aunque falle el servidor
      } finally {
        // Limpiar estado local
        this.user = null
        this.token = null
        this.isAuthenticated = false
        
        // Limpiar localStorage
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        
        console.log('Logout completed')
      }
    },

    async initialize() {
      try {
        // Obtener token y usuario del localStorage
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
        
        // Verificar que el token sigue siendo válido obteniendo el perfil
        const response = await authApi.getProfile()
        
        if (response.success && response.data) {
          // Actualizar con datos frescos del servidor
          this.token = token
          this.user = response.data
          this.isAuthenticated = true
          
          // Actualizar localStorage con datos frescos
          localStorage.setItem('user', JSON.stringify(response.data))
          
          console.log('Auth initialized successfully:', response.data)
          return true
        } else {
          console.log('Token is invalid, logging out')
          this.logout()
          return false
        }
        
      } catch (error) {
        console.error('Auth initialization error:', error)
        this.logout()
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
        
        // Actualizar token y usuario
        this.token = access_token
        if (user) {
          this.user = user
          localStorage.setItem('user', JSON.stringify(user))
        }
        
        localStorage.setItem('token', access_token)
        
        console.log('Token refreshed successfully')
        return { success: true }
        
      } catch (error) {
        console.error('Token refresh error:', error)
        this.logout()
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
    }
  }
})