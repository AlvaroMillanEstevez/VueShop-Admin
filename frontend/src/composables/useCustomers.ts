// composables/useCustomers.ts
import { ref, reactive, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { dashboardApi, handleAPIError } from '@/services/api'

export interface Customer {
  data: Customer
  id: number
  name: string
  email: string
  phone?: string
  address?: string
  city?: string
  country?: string
  notes?: string
  orders_count?: number
  total_spent?: number
  last_order_at?: string
  created_at: string
  recent_orders?: Array<{
    id: number
    order_number: string
    total: number
    status: string
    items_count: number
    seller_name: string
    created_at: string
  }>
}

export interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export const useCustomers = () => {
  const authStore = useAuthStore()
  
  // State
  const loading = ref(false)
  const saving = ref(false)
  const customers = ref<Customer[]>([])
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0
  })
  const error = ref('')
  const searchQuery = ref('')
  const orderFilter = ref('')

  // Forms
  const customerForm = reactive({
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    country: 'Spain',
    notes: ''
  })

  let searchTimeout: number | null = null

  // Computed
  const hasAccess = computed(() => {
    return authStore.user && ['admin', 'manager', 'seller'].includes(authStore.user.role)
  })

  const canCreate = computed(() => {
    return authStore.user && ['admin', 'manager', 'seller'].includes(authStore.user.role)
  })

  const canDelete = (customer: Customer) => {
    return authStore.isAdmin && (customer.orders_count === 0)
  }

  // Methods
  const loadCustomers = async (page: number = 1) => {
    if (!hasAccess.value) {
      loading.value = false
      return
    }

    try {
      loading.value = true
      error.value = ''
      
      const params: any = { page }
      if (searchQuery.value.trim()) {
        params.search = searchQuery.value.trim()
      }
      if (orderFilter.value) {
        params.has_orders = orderFilter.value
      }
      
      const response = await dashboardApi.getCustomers(params)
      
      if (response.success && response.data) {
        const data = response.data as any
        
        if (Array.isArray(data)) {
          customers.value = data
          pagination.value = {
            current_page: 1,
            last_page: 1,
            per_page: data.length,
            total: data.length,
            from: data.length > 0 ? 1 : 0,
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
        }
      } else {
        error.value = handleAPIError(response, 'Failed to load customers')
        if (response.status === 403) {
          error.value = 'Access denied. You do not have permission to view customers.'
        }
      }
    } catch (err) {
      console.error('Error loading customers:', err)
      error.value = 'An unexpected error occurred while loading customers'
    } finally {
      loading.value = false
    }
  }

  const getCustomer = async (id: number): Promise<Customer | null> => {
    try {
      const response = await dashboardApi.getCustomer(id)
      if (response.success && response.data) {
        return response.data as Customer
      }
    } catch (err) {
      console.error('Error loading customer:', err)
    }
    return null
  }

  const createCustomer = async (): Promise<boolean> => {
    try {
      saving.value = true
      const response = await dashboardApi.createCustomer(customerForm)
      
      if (response.success && response.data) {
        customers.value.unshift(response.data as Customer)
        resetForm()
        return true
      } else {
        error.value = response.error || 'Failed to create customer'
        return false
      }
    } catch (err) {
      console.error('Error creating customer:', err)
      error.value = 'An error occurred while creating the customer'
      return false
    } finally {
      saving.value = false
    }
  }

  const updateCustomer = async (id: number, data: Partial<Customer>): Promise<boolean> => {
    try {
      saving.value = true
      const response = await dashboardApi.updateCustomer(id, data)
      
      if (response.success) {
        const index = customers.value.findIndex(c => c.id === id)
        if (index !== -1) {
          customers.value[index] = { ...customers.value[index], ...data }
        }
        return true
      } else {
        error.value = response.error || 'Failed to update customer'
        return false
      }
    } catch (err) {
      console.error('Error updating customer:', err)
      error.value = 'An error occurred while updating the customer'
      return false
    } finally {
      saving.value = false
    }
  }

  const deleteCustomer = async (id: number): Promise<boolean> => {
    try {
      const response = await dashboardApi.deleteCustomer(id)
      
      if (response.success) {
        customers.value = customers.value.filter(c => c.id !== id)
        return true
      } else {
        error.value = response.error || 'Failed to delete customer'
        return false
      }
    } catch (err) {
      console.error('Error deleting customer:', err)
      error.value = 'An error occurred while deleting the customer'
      return false
    }
  }

  const debounceSearch = () => {
    if (searchTimeout) {
      clearTimeout(searchTimeout)
    }
    searchTimeout = setTimeout(() => {
      loadCustomers()
    }, 500)
  }

  const changePage = (page: number) => {
    if (page >= 1 && page <= pagination.value.last_page) {
      loadCustomers(page)
    }
  }

  const resetForm = () => {
    Object.keys(customerForm).forEach(key => {
      if (key === 'country') {
        customerForm[key as keyof typeof customerForm] = 'Spain'
      } else {
        customerForm[key as keyof typeof customerForm] = ''
      }
    })
  }

  const populateForm = (customer: Customer) => {
    customerForm.name = customer.name
    customerForm.email = customer.email
    customerForm.phone = customer.phone || ''
    customerForm.address = customer.address || ''
    customerForm.city = customer.city || ''
    customerForm.country = customer.country || 'Spain'
    customerForm.notes = customer.notes || ''
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

  const getStatusColor = (status: string): string => {
    const statusColors: Record<string, string> = {
      'pending': 'bg-yellow-100 text-yellow-800',
      'processing': 'bg-blue-100 text-blue-800',
      'completed': 'bg-green-100 text-green-800',
      'cancelled': 'bg-red-100 text-red-800',
      'shipped': 'bg-purple-100 text-purple-800',
    }
    
    return statusColors[status?.toLowerCase()] || 'bg-gray-100 text-gray-800'
  }

  return {
    // State
    loading,
    saving,
    customers,
    pagination,
    error,
    searchQuery,
    orderFilter,
    customerForm,

    // Computed
    hasAccess,
    canCreate,
    canDelete,

    // Methods
    loadCustomers,
    getCustomer,
    createCustomer,
    updateCustomer,
    deleteCustomer,
    debounceSearch,
    changePage,
    resetForm,
    populateForm,

    // Utils
    formatCurrency,
    formatDate,
    getInitials,
    getStatusColor
  }
}