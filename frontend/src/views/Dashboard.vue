<template>
  <div class="p-6">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
      <p class="text-gray-600">Welcome back, {{ authStore.user?.name }}</p>
    </div>

    <!-- Error Display -->
    <div v-if="error" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error Loading Dashboard</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{{ error }}</p>
          </div>
          <div class="mt-3">
            <button
              @click="retryLoad"
              class="bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded text-sm font-medium"
            >
              Try Again
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Dashboard Content -->
    <div v-else-if="!error">
      <!-- Stats Cards - CORREGIDAS -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Revenue -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                  <dd class="text-lg font-medium text-gray-900">
                    €{{ formatCurrency(stats.total_revenue.amount) }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Orders</dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ stats.total_orders.count }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Active Customers -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Active Customers</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ stats.active_customers }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Products in Stock -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Products in Stock</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ stats.products_in_stock }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts and Tables Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Sales Chart -->
        <div class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Sales Overview</h3>
          <div v-if="chartError" class="text-red-600 text-sm">
            Error loading chart: {{ chartError }}
          </div>
          <div v-else-if="!salesData || salesData.length === 0" class="text-gray-500 text-sm">
            No sales data available
          </div>
          <div v-else class="relative" style="height: 300px;">
            <canvas ref="chartCanvas" class="w-full h-full"></canvas>
          </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Top Products</h3>
          <div v-if="topProducts && topProducts.length > 0" class="flow-root">
            <ul class="-my-5 divide-y divide-gray-200">
              <li v-for="product in topProducts" :key="product.id" class="py-4">
                <div class="flex items-center space-x-4">
                  <div class="flex-shrink-0">
                    <img 
                      v-if="product.image_url" 
                      :src="product.image_url" 
                      :alt="product.name" 
                      class="h-8 w-8 rounded object-cover"
                      @error="(e) => (e.target as HTMLImageElement).style.display = 'none'"
                    >
                    <div v-else class="h-8 w-8 bg-gray-200 rounded flex items-center justify-center">
                      <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                      </svg>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ product.name }}</p>
                    <p class="text-sm text-gray-500">€{{ formatCurrency(product.price) }}</p>
                  </div>
                  <div class="flex-shrink-0 text-sm text-gray-500">
                    {{ product.sales_count || 0 }} sold
                  </div>
                </div>
              </li>
            </ul>
          </div>
          <div v-else class="text-gray-500 text-sm">
            No product data available
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Recent Orders</h3>
        </div>
        <div v-if="recentOrders && recentOrders.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ order.order_number }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ order.customer_name || order.customer?.name || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  €{{ formatCurrency(order.total) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusClass(order.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ translateStatus(order.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(order.created_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="px-6 py-4 text-gray-500 text-sm">
          No recent orders available
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { dashboardApi, handleAPIError } from '@/services/api'

// Types
interface DashboardStats {
  total_revenue: {
    amount: number
    currency: string
  }
  total_orders: {
    count: number
  }
  active_customers: number
  products_in_stock: number
}

interface Product {
  id: number
  name: string
  price: number
  image_url?: string
  sales_count?: number
}

interface Order {
  id: number
  order_number: string
  customer_name?: string
  customer?: {
    name: string
  }
  total: number
  status: string
  created_at: string
}

interface SalesDataPoint {
  date: string
  amount: number
}

const authStore = useAuthStore()

// State
const loading = ref<boolean>(true)
const error = ref<string>('')
const chartError = ref<string>('')

// Dashboard data
const stats = ref<DashboardStats>({
  total_revenue: { amount: 0, currency: 'EUR' },
  total_orders: { count: 0 },
  active_customers: 0,
  products_in_stock: 0
})

const salesData = ref<SalesDataPoint[]>([])
const topProducts = ref<Product[]>([])
const recentOrders = ref<Order[]>([])

// Chart ref
const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chart: any = null

// Load dashboard data - FUNCIÓN COMPLETAMENTE CORREGIDA
const loadDashboardData = async (): Promise<void> => {
  try {
    loading.value = true
    error.value = ''
    
    console.log('Loading dashboard data for', authStore.isAdmin ? 'admin' : 'user')
    
    // Load all dashboard data concurrently
    const [statsResponse, salesResponse, productsResponse, ordersResponse] = await Promise.all([
      dashboardApi.getStats(),
      dashboardApi.getSalesChart(30),
      dashboardApi.getTopProducts(),
      dashboardApi.getRecentOrders()
    ])
    
    // Handle stats - CORREGIDO: Acceder a statsResponse.data.data
    console.log('Stats response completa:', statsResponse)
    
    if (statsResponse.success && statsResponse.data) {
      // PROBLEMA: statsResponse.data contiene {success: true, data: {...}}
      // Necesitamos acceder a statsResponse.data.data
      const actualStatsData = statsResponse.data.data || statsResponse.data
      console.log('Actual stats data:', actualStatsData)
      
      stats.value = {
        total_revenue: {
          amount: parseFloat(actualStatsData.total_revenue?.amount || 0),
          currency: actualStatsData.total_revenue?.currency || 'EUR'
        },
        total_orders: {
          count: parseInt(actualStatsData.total_orders?.count || 0)
        },
        active_customers: parseInt(actualStatsData.active_customers || 0),
        products_in_stock: parseInt(actualStatsData.products_in_stock || 0)
      }
      
      console.log('Stats procesadas correctamente:', stats.value)
    } else {
      console.error('Stats response failed:', statsResponse)
      stats.value = {
        total_revenue: { amount: 0, currency: 'EUR' },
        total_orders: { count: 0 },
        active_customers: 0,
        products_in_stock: 0
      }
    }
    
    // Handle sales data - CORREGIDO: Acceder a salesResponse.data.data
    console.log('Sales response:', salesResponse)
    if (salesResponse.success && salesResponse.data) {
      // PROBLEMA: salesResponse.data contiene {success: true, data: [...]}
      const actualSalesData = salesResponse.data.data || salesResponse.data
      console.log('Actual sales data:', actualSalesData)
      
      if (Array.isArray(actualSalesData)) {
        salesData.value = actualSalesData
      } else {
        console.warn('Sales data is still not an array:', actualSalesData)
        salesData.value = []
      }
    } else {
      console.warn('Sales response failed:', salesResponse)
      salesData.value = []
    }
    
    console.log('Final salesData:', salesData.value, 'Length:', salesData.value.length)
    
    // Handle top products - CORREGIDO: Acceder a productsResponse.data.data
    console.log('Products response:', productsResponse)
    if (productsResponse.success && productsResponse.data) {
      // PROBLEMA: productsResponse.data contiene {success: true, data: [...]}
      const actualProductsData = productsResponse.data.data || productsResponse.data
      console.log('Actual products data:', actualProductsData)
      
      if (Array.isArray(actualProductsData)) {
        topProducts.value = actualProductsData
      } else {
        console.warn('Products data is still not an array:', actualProductsData)
        topProducts.value = []
      }
      console.log('Top products loaded:', topProducts.value.length)
    }
    
    // Handle recent orders - CORREGIDO: Acceder a ordersResponse.data.data
    console.log('Orders response:', ordersResponse)
    if (ordersResponse.success && ordersResponse.data) {
      // PROBLEMA: ordersResponse.data contiene {success: true, data: [...]}
      const actualOrdersData = ordersResponse.data.data || ordersResponse.data
      console.log('Actual orders data:', actualOrdersData)
      
      if (Array.isArray(actualOrdersData)) {
        recentOrders.value = actualOrdersData
      } else {
        console.warn('Orders data is still not an array:', actualOrdersData)
        recentOrders.value = []
      }
      console.log('Recent orders loaded:', recentOrders.value.length)
    }
    
    console.log('Dashboard data loaded successfully')
    
    // Create chart after data is loaded and DOM is updated
    await nextTick()
    createChart()
    
  } catch (err) {
    console.error('Error loading dashboard data:', err)
    error.value = 'Failed to load dashboard data. Please try again.'
    
    // Set default values in case of error
    stats.value = {
      total_revenue: { amount: 0, currency: 'EUR' },
      total_orders: { count: 0 },
      active_customers: 0,
      products_in_stock: 0
    }
    salesData.value = []
    topProducts.value = []
    recentOrders.value = []
  } finally {
    loading.value = false
  }
}

// Create sales chart - FUNCIÓN CORREGIDA
const createChart = async (): Promise<void> => {
  try {
    chartError.value = ''
    
    // Esperar múltiples ticks para asegurar que el DOM esté renderizado
    await nextTick()
    await nextTick() // Doble nextTick para mayor seguridad
    
    // Validar que tenemos datos antes de intentar crear el canvas
    if (!Array.isArray(salesData.value) || salesData.value.length === 0) {
      console.log('No sales data available for chart')
      return
    }
    
    console.log('Attempting to create chart with data:', salesData.value)
    
    // Intentar encontrar el canvas con retry
    let attempts = 0
    const maxAttempts = 5
    
    while (!chartCanvas.value && attempts < maxAttempts) {
      console.log(`Canvas attempt ${attempts + 1}/${maxAttempts}`)
      await new Promise(resolve => setTimeout(resolve, 100)) // Esperar 100ms
      attempts++
    }
    
    if (!chartCanvas.value) {
      console.error('Canvas element not found after multiple attempts')
      chartError.value = 'Cannot find chart canvas element'
      return
    }
    
    console.log('Canvas found:', chartCanvas.value)
    
    // Destroy existing chart if it exists
    if (chart) {
      console.log('Destroying existing chart')
      chart.destroy()
      chart = null
    }
    
    // Dynamically import Chart.js
    console.log('Importing Chart.js...')
    const { Chart, registerables } = await import('chart.js')
    Chart.register(...registerables)
    
    const ctx = chartCanvas.value.getContext('2d')
    if (!ctx) {
      throw new Error('Could not get canvas context')
    }
    
    console.log('Canvas context obtained')
    
    // Prepare chart data with validation
    const chartLabels = salesData.value.map(item => {
      if (item && item.date) {
        return formatChartDate(item.date)
      }
      return 'N/A'
    })
    
    const chartData = salesData.value.map(item => {
      if (item && typeof item.amount === 'number') {
        return item.amount
      }
      return 0
    })
    
    console.log('Chart labels:', chartLabels)
    console.log('Chart data:', chartData)
    
    // Create the chart
    chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: chartLabels,
        datasets: [{
          label: 'Sales',
          data: chartData,
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return '€' + formatCurrency(Number(value))
              }
            }
          }
        }
      }
    })
    
    console.log('Chart created successfully:', chart)
    
  } catch (err) {
    console.error('Error creating chart:', err)
    chartError.value = err instanceof Error ? err.message : 'Failed to create chart'
  }
}

// Utility functions
const retryLoad = (): void => {
  loadDashboardData()
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatChartDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

const translateStatus = (status: string): string => {
  const translations: Record<string, string> = {
    pending: 'Pending',
    processing: 'Processing',
    shipped: 'Shipped',
    delivered: 'Delivered',
    cancelled: 'Cancelled'
  }
  return translations[status] || status
}

const getStatusClass = (status: string): string => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

// Cleanup
onUnmounted(() => {
  if (chart) {
    chart.destroy()
  }
})

onMounted(() => {
  loadDashboardData()
})
</script>