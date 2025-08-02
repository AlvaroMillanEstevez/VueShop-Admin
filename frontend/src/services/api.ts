import axios, { type AxiosResponse, AxiosError } from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

// Configuración base de axios
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
})

// Flag para evitar múltiples redirects simultáneos
let isRedirecting = false

// Interceptor para agregar token a las requests
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor mejorado para manejar respuestas y errores
api.interceptors.response.use(
  (response: AxiosResponse) => {
    console.log('API Response:', response.data)
    return response
  },
  async (error: AxiosError) => {
    console.error('API Error Details:', {
      message: error.message,
      response: error.response?.data,
      status: error.response?.status,
      config: {
        url: error.config?.url,
        method: error.config?.method,
        data: error.config?.data
      }
    })
    
    // Manejo especial para respuestas HTML (páginas de error)
    if (error.response && error.response.headers['content-type']?.includes('text/html')) {
      console.error('Received HTML response instead of JSON:', error.response.data)
      const htmlError = new Error(`Server returned HTML instead of JSON (Status: ${error.response.status})`)
      htmlError.name = 'HTMLResponseError'
      return Promise.reject(htmlError)
    }
    
    // Manejo de errores 401 (Unauthorized)
    if (error.response?.status === 401) {
      console.log('401 Unauthorized detected, handling token expiration...')
      
      // Evitar múltiples redirects simultáneos
      if (!isRedirecting) {
        isRedirecting = true
        
        try {
          // Intentar usar el store para hacer logout limpio
          const authStore = useAuthStore()
          await authStore.handleTokenExpiration()
          
          // Redirigir al login con mensaje
          router.push({
            name: 'login',
            query: { 
              message: 'session_expired',
              redirect: router.currentRoute.value.fullPath 
            }
          })
        } catch (logoutError) {
          console.error('Error during logout:', logoutError)
          
          // Fallback: limpiar localStorage y redirigir
          localStorage.removeItem('token')
          localStorage.removeItem('user')
          window.location.href = '/login?message=session_expired'
        } finally {
          // Reset flag después de un tiempo
          setTimeout(() => {
            isRedirecting = false
          }, 1000)
        }
      }
    }
    
    return Promise.reject(error)
  }
)

// Helper function para manejar errores de API de forma consistente
const handleApiError = (error: any): string => {
  if (error.name === 'HTMLResponseError') {
    return error.message
  }
  
  if (error.response) {
    const status = error.response.status
    const data = error.response.data
    
    switch (status) {
      case 401:
        return 'Your session has expired. Please log in again.'
      case 403:
        return 'Access denied. You do not have permission for this action.'
      case 404:
        return 'Resource not found.'
      case 422:
        // Laravel validation errors
        if (data.errors) {
          const errorMessages = Object.values(data.errors).flat()
          return errorMessages.join(', ')
        }
        return data.message || 'Validation failed.'
      case 500:
        return 'Server error. Please try again later.'
      default:
        return data.message || `HTTP ${status}: ${error.response.statusText}`
    }
  }
  
  if (error.code === 'ECONNABORTED') {
    return 'Request timeout. Please check your connection.'
  }
  
  if (error.message.includes('Network Error')) {
    return 'Network error. Please check your connection.'
  }
  
  return error.message || 'An unexpected error occurred.'
}

// Enhanced API response wrapper
interface ApiResponseWrapper<T> {
  success: boolean
  data?: T
  error?: string
  status?: number
}

const makeRequest = async <T>(requestPromise: Promise<AxiosResponse<T>>): Promise<ApiResponseWrapper<T>> => {
  try {
    const response = await requestPromise
    return {
      success: true,
      data: response.data,
      status: response.status
    }
  } catch (error) {
    const err = error as any
    return {
      success: false,
      error: handleApiError(err),
      status: err.response?.status || 0
    }
  }
}

// Funciones del API para Dashboard con mejor manejo de errores
export const dashboardApi = {
  // Dashboard endpoints
  getStats: () => 
    makeRequest(api.get('/dashboard/stats')),
  
  getSalesChart: (days: number = 30) => 
    makeRequest(api.get('/dashboard/sales-chart', { params: { days } })),
  
  getTopProducts: () => 
    makeRequest(api.get('/dashboard/top-products')),
  
  getRecentOrders: () => 
    makeRequest(api.get('/dashboard/recent-orders')),

  // Products endpoints con parámetros mejorados
  getProducts: (params: { 
    page?: number
    category?: string
    active?: string
    search?: string
  } = {}) => {
    const queryParams = new URLSearchParams()
    
    if (params.page) queryParams.append('page', params.page.toString())
    if (params.category) queryParams.append('category', params.category)
    if (params.active) queryParams.append('active', params.active)
    if (params.search) queryParams.append('search', params.search)
    
    return makeRequest(api.get(`/dashboard/products?${queryParams.toString()}`))
  },
  
  getProduct: (id: number) => 
    makeRequest(api.get(`/dashboard/products/${id}`)),
  
  createProduct: (product: any) =>
    makeRequest(api.post('/dashboard/products', product)),
  
  updateProduct: (id: number, product: any) =>
    makeRequest(api.put(`/dashboard/products/${id}`, product)),
  
  deleteProduct: (id: number) =>
    makeRequest(api.delete(`/dashboard/products/${id}`)),

  // Orders endpoints con parámetros mejorados
  getOrders: (params: { 
    page?: number
    status?: string
    search?: string
    seller_id?: string
  } = {}) => {
    const queryParams = new URLSearchParams()
    
    if (params.page) queryParams.append('page', params.page.toString())
    if (params.status) queryParams.append('status', params.status)
    if (params.search) queryParams.append('search', params.search)
    if (params.seller_id) queryParams.append('seller_id', params.seller_id)
    
    return makeRequest(api.get(`/dashboard/orders?${queryParams.toString()}`))
  },
  
  getOrder: (id: number) => 
    makeRequest(api.get(`/dashboard/orders/${id}`)),
  
  createOrder: (order: any) =>
    makeRequest(api.post('/dashboard/orders', order)),
  
  updateOrderStatus: (id: number, status: string) => 
    makeRequest(api.put(`/dashboard/orders/${id}/status`, { status })),
  
  deleteOrder: (id: number) =>
    makeRequest(api.delete(`/dashboard/orders/${id}`)),

  // Customers endpoints
  getCustomers: (params: { page?: number; search?: string } = {}) => {
    const queryParams = new URLSearchParams()
    
    if (params.page) queryParams.append('page', params.page.toString())
    if (params.search) queryParams.append('search', params.search)
    
    return makeRequest(api.get(`/dashboard/customers?${queryParams.toString()}`))
  },
  
  getCustomer: (id: number) => 
    makeRequest(api.get(`/dashboard/customers/${id}`)),
  
  createCustomer: (customer: any) =>
    makeRequest(api.post('/dashboard/customers', customer)),
  
  updateCustomer: (id: number, customer: any) =>
    makeRequest(api.put(`/dashboard/customers/${id}`, customer)),
  
  deleteCustomer: (id: number) =>
    makeRequest(api.delete(`/dashboard/customers/${id}`)),

  // Test connection y health check
  testConnection: () => 
    makeRequest(api.get('/dashboard/test')),
    
  healthCheck: () =>
    makeRequest(api.get('/health')),
}

// Funciones del API para Auth
export const authApi = {
  login: (credentials: { email: string; password: string }) =>
    makeRequest(api.post('/auth/login', credentials)),
  
  register: (userData: any) =>
    makeRequest(api.post('/auth/register', userData)),
  
  logout: () =>
    makeRequest(api.post('/auth/logout')),
  
  refresh: () =>
    makeRequest(api.post('/auth/refresh')),
  
  getProfile: () =>
    makeRequest(api.get('/auth/profile')),
  
  updateProfile: (profileData: any) =>
    makeRequest(api.put('/auth/profile', profileData)),
  
  changePassword: (passwordData: any) =>
    makeRequest(api.post('/auth/change-password', passwordData)),
}

// Funciones del API para Admin
export const adminApi = {
  getAllUsers: () =>
    makeRequest(api.get('/admin/users')),
  
  toggleUserStatus: (userId: number) =>
    makeRequest(api.put(`/admin/users/${userId}/toggle-status`)),
}

// Compatibilidad con el código anterior
export class APIService {
  private token: string | null = null

  setToken(token: string) {
    this.token = token
    if (token) {
      localStorage.setItem('token', token)
    }
  }

  async getProducts(params: Record<string, string> = {}) {
    return dashboardApi.getProducts(params)
  }

  async getProduct(id: number) {
    return dashboardApi.getProduct(id)
  }

  async getOrders(params: Record<string, string> = {}) {
    return dashboardApi.getOrders(params)
  }

  async getOrder(id: number) {
    return dashboardApi.getOrder(id)
  }

  async healthCheck() {
    return dashboardApi.healthCheck()
  }
}

export const apiService = new APIService()

// Helper function para usar en componentes
export const handleAPIError = (response: ApiResponseWrapper<any>, fallbackMessage = 'An error occurred'): string => {
  if (response.success) return ''
  
  if (response.error) return response.error
  
  if (response.status === 401) {
    return 'Your session has expired. Please log in again.'
  }
  
  if (response.status === 403) {
    return 'Access denied. You do not have permission to perform this action.'
  }
  
  if (response.status === 404) {
    return 'Resource not found.'
  }
  
  if (response.status === 500) {
    return 'Server error. Please try again later.'
  }
  
  return fallbackMessage
}

export default api