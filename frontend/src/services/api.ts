import axios from 'axios'

// Configuración base de axios
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  timeout: 30000, // Aumentar a 30 segundos
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
})

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

// Interceptor para manejar respuestas y errores
api.interceptors.response.use(
  (response) => {
    console.log('API Response:', response.data)
    return response
  },
  async (error) => {
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
    
    if (error.response?.status === 401) {
      // Token expirado, limpiar storage y redirigir a login
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    
    return Promise.reject(error)
  }
)

// Funciones del API para Dashboard
export const dashboardApi = {
  // Dashboard endpoints
  getStats: () => 
    api.get('/dashboard/stats').then(res => res.data),
  
  getSalesChart: (days: number = 30) => 
    api.get('/dashboard/sales-chart', { params: { days } }).then(res => res.data),
  
  getTopProducts: () => 
    api.get('/dashboard/top-products').then(res => res.data),
  
  getRecentOrders: () => 
    api.get('/dashboard/recent-orders').then(res => res.data),

  // Products endpoints
  getProducts: (page = 1) => 
    api.get(`/dashboard/products?page=${page}`).then(res => res.data),
  
  getProduct: (id: number) => 
    api.get(`/dashboard/products/${id}`).then(res => res.data),
  
  createProduct: (product: any) =>
    api.post('/dashboard/products', product).then(res => res.data),
  
  updateProduct: (id: number, product: any) =>
    api.put(`/dashboard/products/${id}`, product).then(res => res.data),
  
  deleteProduct: (id: number) =>
    api.delete(`/dashboard/products/${id}`),

  // Orders endpoints
  getOrders: (page = 1) => 
    api.get(`/dashboard/orders?page=${page}`).then(res => res.data),
  
  getOrder: (id: number) => 
    api.get(`/dashboard/orders/${id}`).then(res => res.data),
  
  createOrder: (order: any) =>
    api.post('/dashboard/orders', order).then(res => res.data),
  
  updateOrderStatus: (id: number, status: string) => 
    api.put(`/dashboard/orders/${id}/status`, { status }).then(res => res.data),
  
  deleteOrder: (id: number) =>
    api.delete(`/dashboard/orders/${id}`),

  // Customers endpoints
  getCustomers: (page = 1) => 
    api.get(`/dashboard/customers?page=${page}`).then(res => res.data),
  
  getCustomer: (id: number) => 
    api.get(`/dashboard/customers/${id}`).then(res => res.data),
  
  createCustomer: (customer: any) =>
    api.post('/dashboard/customers', customer).then(res => res.data),
  
  updateCustomer: (id: number, customer: any) =>
    api.put(`/dashboard/customers/${id}`, customer).then(res => res.data),
  
  deleteCustomer: (id: number) =>
    api.delete(`/dashboard/customers/${id}`),

  // Test connection
  testConnection: () => 
    api.get('/dashboard/test').then(res => res.data),
}

// Funciones del API para Auth
export const authApi = {
  login: (credentials: { email: string; password: string }) =>
    api.post('/auth/login', credentials).then(res => res.data),
  
  register: (userData: any) =>
    api.post('/auth/register', userData).then(res => res.data),
  
  logout: () =>
    api.post('/auth/logout').then(res => res.data),
  
  refresh: () =>
    api.post('/auth/refresh').then(res => res.data),
  
  getProfile: () =>
    api.get('/auth/profile').then(res => res.data),
  
  updateProfile: (profileData: any) =>
    api.put('/auth/profile', profileData).then(res => res.data),
  
  changePassword: (passwordData: any) =>
    api.post('/auth/change-password', passwordData).then(res => res.data),
}

// Funciones del API para Admin
export const adminApi = {
  getAllUsers: () =>
    api.get('/admin/users').then(res => res.data),
  
  toggleUserStatus: (userId: number) =>
    api.put(`/admin/users/${userId}/toggle-status`).then(res => res.data),
}

export default api