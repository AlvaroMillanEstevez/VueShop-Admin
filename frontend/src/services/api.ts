// src/services/api.ts
import axios from 'axios'

// Configuración base de axios
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
})

// Interceptor para manejar errores
api.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('API Error:', error.response?.data || error.message)
    return Promise.reject(error)
  }
)

// Tipos TypeScript
export interface DashboardStats {
  total_revenue: {
    current: number
    previous: number
    growth: number
  }
  total_orders: {
    current: number
    previous: number
  }
  total_customers: number
  low_stock_products: number
}

export interface SalesData {
  date: string
  orders: number
  revenue: number
}

export interface TopProduct {
  id: number
  name: string
  category: string
  total_sold: number
  revenue: number
  image_url: string
}

export interface RecentOrder {
  id: number
  order_number: string
  customer_name: string
  status: string
  total: number
  items_count: number
  created_at: string
}

export interface Product {
  id: number
  name: string
  description: string
  price: number
  stock: number
  sku: string
  category: string
  image_url: string
  active: boolean
  created_at: string
  updated_at: string
}

export interface Customer {
  id: number
  name: string
  email: string
  phone: string | null
  address: string | null
  city: string | null
  country: string | null
  total_spent: number
  last_order_at: string | null
  orders_count?: number
  orders?: Order[]
}

export interface Order {
  id: number
  order_number: string
  customer?: Customer
  customer_id?: number
  status: string
  subtotal: number
  tax: number
  shipping: number
  total: number
  items?: OrderItem[]
  created_at: string
  updated_at: string
}

export interface OrderItem {
  id: number
  product?: Product
  product_id?: number
  quantity: number
  unit_price: number
  total_price: number
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

// Funciones del API
export const dashboardApi = {
  // Dashboard endpoints
  getStats: (): Promise<DashboardStats> => 
    api.get('/dashboard/stats').then(res => res.data),
  
  getSalesChart: (days: number = 30): Promise<SalesData[]> => 
    api.get('/dashboard/sales-chart', { params: { days } }).then(res => res.data),
  
  getTopProducts: (): Promise<TopProduct[]> => 
    api.get('/dashboard/top-products').then(res => res.data),
  
  getRecentOrders: (): Promise<RecentOrder[]> => 
    api.get('/dashboard/recent-orders').then(res => res.data),

  // Products endpoints
  getProducts: (page = 1): Promise<PaginatedResponse<Product>> => 
    api.get(`/dashboard/products?page=${page}`).then(res => res.data),
  
  getProduct: (id: number): Promise<Product> => 
    api.get(`/dashboard/products/${id}`).then(res => res.data),
  
  createProduct: (product: Partial<Product>): Promise<Product> =>
    api.post('/dashboard/products', product).then(res => res.data),
  
  updateProduct: (id: number, product: Partial<Product>): Promise<Product> =>
    api.put(`/dashboard/products/${id}`, product).then(res => res.data),
  
  deleteProduct: (id: number): Promise<void> =>
    api.delete(`/dashboard/products/${id}`),

  // Orders endpoints
  getOrders: (page = 1): Promise<PaginatedResponse<Order>> => 
    api.get(`/dashboard/orders?page=${page}`).then(res => res.data),
  
  getOrder: (id: number): Promise<Order> => 
    api.get(`/dashboard/orders/${id}`).then(res => res.data),
  
  createOrder: (order: {
    customer_id: number,
    items: Array<{ product_id: number, quantity: number }>,
    shipping: number
  }): Promise<Order> =>
    api.post('/dashboard/orders', order).then(res => res.data),
  
  updateOrderStatus: (id: number, status: string): Promise<Order> => 
    api.put(`/dashboard/orders/${id}/status`, { status }).then(res => res.data),
  
  deleteOrder: (id: number): Promise<void> =>
    api.delete(`/dashboard/orders/${id}`),

  // Customers endpoints
  getCustomers: (page = 1): Promise<PaginatedResponse<Customer>> => 
    api.get(`/dashboard/customers?page=${page}`).then(res => res.data),
  
  getCustomer: (id: number): Promise<Customer> => 
    api.get(`/dashboard/customers/${id}`).then(res => res.data),
  
  createCustomer: (customer: Partial<Customer>): Promise<Customer> =>
    api.post('/dashboard/customers', customer).then(res => res.data),
  
  updateCustomer: (id: number, customer: Partial<Customer>): Promise<Customer> =>
    api.put(`/dashboard/customers/${id}`, customer).then(res => res.data),
  
  deleteCustomer: (id: number): Promise<void> =>
    api.delete(`/dashboard/customers/${id}`),

  // Test connection
  testConnection: (): Promise<any> => 
    api.get('/dashboard/test').then(res => res.data),
}

export default api