<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { dashboardApi, handleAPIError } from '@/services/api'

import OrderModal from './OrderModal.vue'

const showOrderModal = ref(false)
const editingOrder = ref<Order | null>(null)

const openNewOrderModal = () => {
  editingOrder.value = null
  showOrderModal.value = true
}

const editOrder = (order: Order): void => {
  editingOrder.value = order
  showOrderModal.value = true
}


// Types - Puedes importarlos desde types/index.ts si los tienes
interface Customer {
  id: number
  name: string
  email: string
}

interface Seller {
  id: number
  name: string
}

interface OrderItem {
  id: number
  product_name?: string
  product?: {
    name: string
  }
  quantity: number
  unit_price?: number
  price?: number
  total_price?: number
}

interface Order {
  id: number
  order_number: string
  customer_name?: string
  customer_email?: string
  customer?: Customer
  seller?: Seller
  items_count?: number
  items?: OrderItem[]
  total: number
  subtotal?: number
  tax?: number
  shipping?: number
  status: 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled'
  created_at: string
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

const authStore = useAuthStore()

// State
const loading = ref<boolean>(true)
const orders = ref<Order[]>([])
const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})
const showModal = ref<boolean>(false)
const selectedOrder = ref<Order | null>(null)
const error = ref<string>('')
const loadingOrderDetails = ref<boolean>(false)

// Filters
const filters = reactive({
  status: '',
  search: '',
  seller_id: ''
})

let searchTimeout: number | null = null

// Enhanced load orders using new API service
const loadOrders = async (page: number = 1): Promise<void> => {
  try {
    loading.value = true
    error.value = ''

    const params: any = { page }

    if (filters.status) params.status = filters.status
    if (filters.search) params.search = filters.search
    if (filters.seller_id) params.seller_id = filters.seller_id

    console.log('Loading orders with params:', params)

    const response = await dashboardApi.getOrders(params)

    if (response.success && response.data) {
      const data = response.data as any
      orders.value = data.data || []
      pagination.value = {
        current_page: data.current_page || 1,
        last_page: data.last_page || 1,
        per_page: data.per_page || 15,
        total: data.total || 0,
        from: data.from || 0,
        to: data.to || 0
      }
      console.log('Orders loaded successfully:', orders.value.length)
    } else {
      error.value = handleAPIError(response, 'Failed to load orders')
      orders.value = []
    }
  } catch (err) {
    console.error('Unexpected error loading orders:', err)
    error.value = 'An unexpected error occurred while loading orders'
    orders.value = []
  } finally {
    loading.value = false
  }
}

// View order with enhanced error handling using new API service
const viewOrder = async (orderId: number): Promise<void> => {
  try {
    loadingOrderDetails.value = true

    const response = await dashboardApi.getOrder(orderId)

    if (response.success && response.data) {
      selectedOrder.value = response.data.data || response.data
      showModal.value = true
    } else {
      const errorMessage = handleAPIError(response, 'Failed to load order details')
      alert(errorMessage) // You might want to show this in a toast or modal
    }
  } catch (error) {
    console.error('Error loading order details:', error)
    alert('An unexpected error occurred while loading order details')
  } finally {
    loadingOrderDetails.value = false
  }
}

// Debounced search
const debounceSearch = (): void => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  searchTimeout = setTimeout(() => {
    loadOrders()
  }, 500)
}

// Close modal
const closeModal = (): void => {
  showModal.value = false
  selectedOrder.value = null
}

// Change page
const changePage = (page: number): void => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadOrders(page)
  }
}

// Retry loading
const retryLoad = (): void => {
  loadOrders()
}

// Utility functions
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
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
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

onMounted(() => {
  loadOrders()
})
</script>

<template>
  <div class="p-6">
    <div class="mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Orders</h1>
        <p class="text-gray-600">
          {{ authStore.isAdmin ? 'Manage all orders from all sellers' : 'Manage your orders' }}
        </p>
      </div>
      <button @click="openNewOrderModal"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <span>New Order</span>
      </button>
    </div>

    <!-- Error Display -->
    <div v-if="error" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error Loading Orders</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{{ error }}</p>
          </div>
          <div class="mt-3">
            <button @click="retryLoad"
              class="bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded text-sm font-medium">
              Try Again
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-wrap gap-4">
      <select v-model="filters.status" @change="() => loadOrders()" class="form-select">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="processing">Processing</option>
        <option value="shipped">Shipped</option>
        <option value="delivered">Delivered</option>
        <option value="cancelled">Cancelled</option>
      </select>

      <input v-model="filters.search" @input="debounceSearch" type="text" placeholder="Search orders..."
        class="search-input">

      <select v-if="authStore.isAdmin" v-model="filters.seller_id" @change="() => loadOrders()" class="form-select">
        <option value="">All Sellers</option>
        <option value="1">Super Admin</option>
        <option value="2">Juan García Pérez</option>
        <option value="3">María López Silva</option>
        <option value="4">Carlos Rodríguez Martín</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Orders Table -->
    <div v-else-if="!error" class="bg-white shadow rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
              <th v-if="authStore.isAdmin"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seller</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ order.order_number }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ order.customer_name || order.customer?.name || 'N/A' }}</div>
                <div class="text-sm text-gray-500">{{ order.customer_email || order.customer?.email || 'N/A' }}</div>
              </td>
              <td v-if="authStore.isAdmin" class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-blue-600">{{ order.seller?.name || 'Sin asignar' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">
                  {{ order.items_count || (order.items ? order.items.length : 0) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                €{{ formatCurrency(order.total) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusClass(order.status)"
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ translateStatus(order.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(order.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                <button @click="viewOrder(order.id)" :disabled="loadingOrderDetails"
                  class="text-blue-600 hover:text-blue-900 disabled:opacity-50">
                  {{ loadingOrderDetails ? 'Loading...' : 'View' }}
                </button>
                <button @click="editOrder(order)" class="text-green-600 hover:text-green-900">
                  Edit
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
          </div>
          <div class="flex space-x-2">
            <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
              class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-gray-50">
              Previous
            </button>
            <button @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-gray-50">
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && !error && orders.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
        </path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No Orders Found</h3>
      <p class="mt-1 text-sm text-gray-500">Orders will appear here when customers make purchases.</p>
    </div>

    <!-- Order Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Order Details</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div v-if="selectedOrder" class="space-y-6">
            <!-- Order Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="text-lg font-semibold mb-4">Order Information</h3>
                <dl class="space-y-2">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Order Number</dt>
                    <dd class="text-sm text-gray-900">{{ selectedOrder.order_number }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd>
                      <span :class="getStatusClass(selectedOrder.status)"
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                        {{ translateStatus(selectedOrder.status) }}
                      </span>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="text-sm text-gray-900">{{ formatDate(selectedOrder.created_at) }}</dd>
                  </div>
                </dl>
              </div>

              <div>
                <h3 class="text-lg font-semibold mb-4">Customer Information</h3>
                <dl class="space-y-2">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="text-sm text-gray-900">{{ selectedOrder.customer?.name || selectedOrder.customer_name ||
                      'N/A' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="text-sm text-gray-900">{{ selectedOrder.customer?.email || selectedOrder.customer_email
                      || 'N/A' }}</dd>
                  </div>
                  <div v-if="authStore.isAdmin && selectedOrder.seller">
                    <dt class="text-sm font-medium text-gray-500">Seller</dt>
                    <dd class="text-sm text-blue-600 font-medium">{{ selectedOrder.seller?.name }}</dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Order Items -->
            <div>
              <h3 class="text-lg font-semibold mb-4">Order Items</h3>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in selectedOrder.items" :key="item.id">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.product_name ||
                        item.product?.name || 'N/A' }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.quantity }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">€{{ formatCurrency(item.unit_price
                        || item.price || 0) }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">€{{ formatCurrency(item.total_price
                        || (item.quantity * (item.unit_price || item.price || 0))) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Order Summary -->
            <div class="border-t pt-6">
              <div class="flex justify-end">
                <div class="w-full max-w-xs space-y-2">
                  <div class="flex justify-between text-sm">
                    <span>Subtotal:</span>
                    <span>€{{ formatCurrency(selectedOrder.subtotal || 0) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span>Tax:</span>
                    <span>€{{ formatCurrency(selectedOrder.tax || 0) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span>Shipping:</span>
                    <span>€{{ formatCurrency(selectedOrder.shipping || 0) }}</span>
                  </div>
                  <div class="flex justify-between text-lg font-bold border-t pt-2">
                    <span>Total:</span>
                    <span>€{{ formatCurrency(selectedOrder.total) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <OrderModal v-model="showOrderModal" :order="editingOrder" @close="showOrderModal = false" />

</template>