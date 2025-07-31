<template>
  <div class="p-6">
    <div class="mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Customers</h1>
        <p class="text-gray-600">
          {{ authStore.isAdmin ? 'Manage all customers' : 'View customer information' }}
        </p>
      </div>
      <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <span>New Customer</span>
      </button>
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
          <h3 class="text-sm font-medium text-red-800">Error Loading Customers</h3>
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

    <!-- Search -->
    <div class="mb-6">
      <input 
        v-model="searchQuery" 
        @input="debounceSearch"
        type="text" 
        placeholder="Search customers..."
        class="border rounded px-3 py-2 w-64"
      >
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Customers Table -->
    <div v-else-if="!error" class="bg-white shadow rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spent</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                      <span class="text-sm font-medium text-gray-700">
                        {{ getInitials(customer.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ customer.name }}</div>
                    <div v-if="customer.is_verified" class="text-sm text-green-600">Verified</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ customer.email }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ customer.phone || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ customer.orders_count || 0 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                €{{ formatCurrency(customer.total_spent || 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(customer.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                <button 
                  @click="viewCustomer(customer)"
                  class="text-blue-600 hover:text-blue-900"
                >
                  View
                </button>
                <button 
                  v-if="authStore.isAdmin"
                  @click="editCustomer(customer)"
                  class="text-green-600 hover:text-green-900"
                >
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
            <button 
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-gray-50"
            >
              Previous
            </button>
            <button 
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-gray-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && !error && customers.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No Customers Found</h3>
      <p class="mt-1 text-sm text-gray-500">No customers match your search criteria.</p>
    </div>

    <!-- Customer Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Customer Details</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div v-if="selectedCustomer" class="space-y-6">
            <!-- Customer Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="text-lg font-semibold mb-4">Customer Information</h3>
                <dl class="space-y-2">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="text-sm text-gray-900">{{ selectedCustomer.name }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="text-sm text-gray-900">{{ selectedCustomer.email }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="text-sm text-gray-900">{{ selectedCustomer.phone || 'N/A' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                    <dd class="text-sm text-gray-900">{{ selectedCustomer.address || 'N/A' }}</dd>
                  </div>
                </dl>
              </div>

              <div>
                <h3 class="text-lg font-semibold mb-4">Statistics</h3>
                <dl class="space-y-2">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Total Orders</dt>
                    <dd class="text-sm text-gray-900">{{ selectedCustomer.orders_count || 0 }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Total Spent</dt>
                    <dd class="text-sm text-gray-900">€{{ formatCurrency(selectedCustomer.total_spent || 0) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                    <dd class="text-sm text-gray-900">{{ formatDate(selectedCustomer.created_at) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd>
                      <span :class="selectedCustomer.is_verified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'" 
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                        {{ selectedCustomer.is_verified ? 'Verified' : 'Unverified' }}
                      </span>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { dashboardApi, handleAPIError } from '@/services/api'

// Types
interface Customer {
  id: number
  name: string
  email: string
  phone?: string
  address?: string
  orders_count?: number
  total_spent?: number
  is_verified?: boolean
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
const customers = ref<Customer[]>([])
const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})
const error = ref<string>('')
const searchQuery = ref<string>('')
const showModal = ref<boolean>(false)
const selectedCustomer = ref<Customer | null>(null)

let searchTimeout: number | null = null

// Load customers with enhanced error handling
const loadCustomers = async (page: number = 1): Promise<void> => {
  try {
    loading.value = true
    error.value = ''
    
    const params: any = { page }
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }
    
    console.log('Loading customers with params:', params)
    
    const response = await dashboardApi.getCustomers(params)
    
    if (response.success && response.data) {
      const data = response.data as any
      
      // Handle different response structures
      if (Array.isArray(data)) {
        customers.value = data
        // Set default pagination if not provided
        pagination.value = {
          current_page: 1,
          last_page: 1,
          per_page: data.length,
          total: data.length,
          from: 1,
          to: data.length
        }
      } else if (data.data && Array.isArray(data.data)) {
        customers.value = data.data
        pagination.value = {
          current_page: data.current_page || 1,
          last_page: data.last_page || 1,
          per_page: data.per_page || 15,
          total: data.total || 0,
          from: data.from || 0,
          to: data.to || 0
        }
      } else {
        console.warn('Unexpected data structure:', data)
        customers.value = []
      }
      
      console.log('Customers loaded successfully:', customers.value.length)
    } else {
      error.value = handleAPIError(response, 'Failed to load customers')
      customers.value = []
    }
  } catch (err) {
    console.error('Unexpected error loading customers:', err)
    error.value = 'An unexpected error occurred while loading customers'
    customers.value = []
  } finally {
    loading.value = false
  }
}

// Debounced search
const debounceSearch = (): void => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  searchTimeout = setTimeout(() => {
    loadCustomers()
  }, 500)
}

// Change page
const changePage = (page: number): void => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadCustomers(page)
  }
}

// View customer
const viewCustomer = (customer: Customer): void => {
  selectedCustomer.value = customer
  showModal.value = true
}

// Edit customer
const editCustomer = (customer: Customer): void => {
  console.log('Edit customer:', customer)
  // TODO: Implement edit functionality
}

// Close modal
const closeModal = (): void => {
  showModal.value = false
  selectedCustomer.value = null
}

// Retry loading
const retryLoad = (): void => {
  loadCustomers()
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
    day: 'numeric'
  })
}

const getInitials = (name: string): string => {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

onMounted(() => {
  loadCustomers()
})
</script>