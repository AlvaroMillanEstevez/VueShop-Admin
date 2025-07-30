<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-none">
    <!-- Header -->
    <div class="bg-white p-4 sm:p-6 lg:p-8 rounded-2xl shadow-sm mb-6 sm:mb-8 border-l-4 border-l-purple-500">
      <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-2 sm:mb-3">Gestión de Clientes</h1>
      <p class="text-gray-600 text-base sm:text-lg">Administra tu base de clientes</p>
    </div>

    <div v-if="loading" class="flex justify-center items-center h-64 text-gray-500">
      <div class="spinner mr-3"></div>
      <span class="text-lg">Cargando clientes...</span>
    </div>

    <div v-else class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="p-4 sm:p-6 lg:p-8 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <div>
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900">Lista de Clientes</h3>
            <p class="text-gray-500 mt-1 text-sm sm:text-base">Total: {{ pagination?.total || 0 }} clientes</p>
          </div>
          <button 
            @click="openModal()"
            class="w-full sm:w-auto px-6 py-3 bg-purple-500 text-white rounded-xl hover:bg-purple-600 transition-colors font-medium"
          >
            Añadir Cliente
          </button>
        </div>
      </div>

      <!-- Vista móvil (cards) -->
      <div class="block sm:hidden p-4 space-y-4" v-if="customers.length > 0">
        <div v-for="customer in customers" :key="customer.id" class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-purple-600 font-semibold text-sm">{{ getInitials(customer.name) }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <h4 class="font-semibold text-gray-900 truncate">{{ customer.name }}</h4>
                <p class="text-sm text-gray-500 truncate">{{ customer.email }}</p>
              </div>
            </div>
            <span v-if="customer.total_spent > 2000" class="text-xs bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full font-medium">
              VIP
            </span>
          </div>
          
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Total gastado:</span>
              <span class="font-bold text-gray-900">€{{ formatNumber(customer.total_spent || 0) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Pedidos:</span>
              <span class="font-medium">{{ customer.orders_count || 0 }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Último pedido:</span>
              <span class="font-medium">{{ customer.last_order_at ? formatDate(customer.last_order_at) : 'Nunca' }}</span>
            </div>
          </div>
          
          <div class="mt-4 flex items-center justify-end space-x-2">
            <button 
              @click="viewCustomerDetails(customer.id)"
              class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
            </button>
            <button 
              @click="openModal(customer)"
              class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </button>
            <button 
              @click="deleteCustomer(customer)"
              class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Vista desktop (tabla) -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full" v-if="customers.length > 0">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Cliente</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Teléfono</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Ubicación</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Pedidos</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Total Gastado</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Último Pedido</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50 transition-colors">
              <td class="py-4 px-6">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-purple-600 font-semibold text-sm">{{ getInitials(customer.name) }}</span>
                  </div>
                  <div class="min-w-0 flex-1">
                    <h4 class="font-semibold text-gray-900 truncate">{{ customer.name }}</h4>
                    <p class="text-sm text-gray-500 truncate">{{ customer.email }}</p>
                  </div>
                </div>
              </td>
              <td class="py-4 px-6 text-gray-600">{{ customer.phone || '-' }}</td>
              <td class="py-4 px-6">
                <div class="text-sm">
                  <div class="text-gray-900">{{ customer.city || '-' }}</div>
                  <div class="text-gray-500">{{ customer.country || '-' }}</div>
                </div>
              </td>
              <td class="py-4 px-6">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ customer.orders_count || 0 }} pedidos
                </span>
              </td>
              <td class="py-4 px-6">
                <span class="font-bold text-lg" :class="customer.total_spent > 2000 ? 'text-purple-600' : 'text-gray-900'">
                  €{{ formatNumber(customer.total_spent || 0) }}
                </span>
                <span v-if="customer.total_spent > 2000" class="ml-2 text-xs bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full font-medium">
                  VIP
                </span>
              </td>
              <td class="py-4 px-6 text-sm text-gray-500">
                {{ customer.last_order_at ? formatDate(customer.last_order_at) : 'Nunca' }}
              </td>
              <td class="py-4 px-6">
                <div class="flex items-center space-x-2">
                  <button 
                    @click="viewCustomerDetails(customer.id)"
                    class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    title="Ver detalles"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                  </button>
                  <button 
                    @click="openModal(customer)"
                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                    title="Editar"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </button>
                  <button 
                    @click="deleteCustomer(customer)"
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    title="Eliminar"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="text-center py-12 text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <p class="text-lg">No hay clientes registrados</p>
        </div>
      </div>

      <!-- Paginación -->
      <div v-if="pagination && pagination.last_page > 1" class="p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <button 
            @click="loadPage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Anterior
          </button>
          <span class="text-sm text-gray-700">
            Página {{ pagination.current_page }} de {{ pagination.last_page }}
          </span>
          <button 
            @click="loadPage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de Cliente -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="closeModal">
      <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-2xl font-bold text-gray-900">{{ editingCustomer.id ? 'Editar Cliente' : 'Nuevo Cliente' }}</h3>
        </div>
        
        <form @submit.prevent="saveCustomer" class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Nombre completo</label>
              <input 
                v-model="editingCustomer.name" 
                type="text" 
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
              <input 
                v-model="editingCustomer.email" 
                type="email" 
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
              <input 
                v-model="editingCustomer.phone" 
                type="tel"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
              <input 
                v-model="editingCustomer.address" 
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
              <input 
                v-model="editingCustomer.city" 
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">País</label>
              <input 
                v-model="editingCustomer.country" 
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
              >
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
            <button 
              type="button"
              @click="closeModal"
              class="px-6 py-3 text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors font-medium"
            >
              Cancelar
            </button>
            <button 
              type="submit"
              :disabled="saving"
              class="px-6 py-3 bg-purple-500 text-white rounded-xl hover:bg-purple-600 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ saving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Detalles -->
    <div v-if="selectedCustomer" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click="selectedCustomer = null">
      <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="p-6 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-900">Detalles del Cliente</h3>
              <p class="text-gray-500 mt-1">{{ selectedCustomer.name }}</p>
            </div>
            <button @click="selectedCustomer = null" class="p-2 text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>
        
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
              <h4 class="font-semibold text-gray-900 mb-4">Información de contacto</h4>
              <div class="space-y-3 text-sm">
                <div><span class="text-gray-500">Email:</span> <span class="font-medium">{{ selectedCustomer.email }}</span></div>
                <div><span class="text-gray-500">Teléfono:</span> <span class="font-medium">{{ selectedCustomer.phone || '-' }}</span></div>
                <div><span class="text-gray-500">Dirección:</span> <span class="font-medium">{{ selectedCustomer.address || '-' }}</span></div>
                <div><span class="text-gray-500">Ciudad:</span> <span class="font-medium">{{ selectedCustomer.city || '-' }}</span></div>
                <div><span class="text-gray-500">País:</span> <span class="font-medium">{{ selectedCustomer.country || '-' }}</span></div>
              </div>
            </div>
            
            <div>
              <h4 class="font-semibold text-gray-900 mb-4">Estadísticas</h4>
              <div class="space-y-3 text-sm">
                <div><span class="text-gray-500">Total gastado:</span> <span class="font-bold text-lg text-purple-600">€{{ formatNumber(selectedCustomer.total_spent || 0) }}</span></div>
                <div><span class="text-gray-500">Número de pedidos:</span> <span class="font-medium">{{ selectedCustomer.orders_count || 0 }}</span></div>
                <div><span class="text-gray-500">Último pedido:</span> <span class="font-medium">{{ selectedCustomer.last_order_at ? formatDate(selectedCustomer.last_order_at) : 'Nunca' }}</span></div>
                <div v-if="selectedCustomer.total_spent > 2000">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                    Cliente VIP
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <div v-if="selectedCustomer.orders && selectedCustomer.orders.length > 0">
            <h4 class="font-semibold text-gray-900 mb-4">Últimos pedidos</h4>
            <div class="space-y-3">
              <div v-for="order in selectedCustomer.orders" :key="order.id" class="bg-gray-50 p-4 rounded-lg">
                <div class="flex justify-between items-center">
                  <div>
                    <span class="font-mono text-sm font-medium text-blue-600">#{{ order.order_number }}</span>
                    <span class="ml-3 text-sm text-gray-500">{{ formatDate(order.created_at) }}</span>
                  </div>
                  <div class="text-right">
                    <div class="font-bold">€{{ formatNumber(order.total) }}</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="getStatusClasses(order.status)">
                      {{ translateStatus(order.status) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div v-if="customerToDelete" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click="customerToDelete = null">
      <div class="bg-white rounded-2xl max-w-md w-full p-6" @click.stop>
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">Eliminar Cliente</h3>
          <p class="text-gray-500 mb-6">¿Estás seguro de que deseas eliminar a <strong>{{ customerToDelete.name }}</strong>? Esta acción no se puede deshacer.</p>
          
          <div class="flex justify-center space-x-3">
            <button 
              @click="customerToDelete = null"
              class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors font-medium"
            >
              Cancelar
            </button>
            <button 
              @click="confirmDelete"
              class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium"
            >
              Eliminar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { dashboardApi, type Customer } from '@/services/api'

const loading = ref(true)
const customers = ref<Customer[]>([])
const pagination = ref<any>(null)
const showModal = ref(false)
const selectedCustomer = ref<Customer | null>(null)
const customerToDelete = ref<Customer | null>(null)
const saving = ref(false)

const editingCustomer = reactive<Partial<Customer>>({
  name: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  country: ''
})

const formatNumber = (num: number): string => {
  return new Intl.NumberFormat('es-ES', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num)
}

const formatDate = (dateString: string): string => {
  return new Intl.DateTimeFormat('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(new Date(dateString))
}

const getInitials = (name: string): string => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const translateStatus = (status: string): string => {
  const translations: Record<string, string> = {
    pending: 'Pendiente',
    processing: 'Procesando',
    shipped: 'Enviado',
    delivered: 'Entregado',
    cancelled: 'Cancelado'
  }
  return translations[status] || status
}

const getStatusClasses = (status: string): string => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-green-100 text-green-800',
    delivered: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const loadPage = async (page: number) => {
  try {
    loading.value = true
    const response = await dashboardApi.getCustomers(page)
    customers.value = response.data
    pagination.value = response
  } catch (error) {
    console.error('Error loading customers:', error)
  } finally {
    loading.value = false
  }
}

const openModal = (customer?: Customer) => {
  if (customer) {
    Object.assign(editingCustomer, customer)
  } else {
    Object.assign(editingCustomer, {
      name: '',
      email: '',
      phone: '',
      address: '',
      city: '',
      country: ''
    })
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  Object.assign(editingCustomer, {
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    country: ''
  })
}

const saveCustomer = async () => {
  try {
    saving.value = true
    
    if (editingCustomer.id) {
      // Actualizar cliente existente
      await dashboardApi.updateCustomer(editingCustomer.id, editingCustomer)
    } else {
      // Crear nuevo cliente
      await dashboardApi.createCustomer(editingCustomer)
    }
    
    closeModal()
    // Recargar la página actual
    loadPage(pagination.value?.current_page || 1)
  } catch (error) {
    console.error('Error saving customer:', error)
    alert('Error al guardar el cliente. Por favor, intenta de nuevo.')
  } finally {
    saving.value = false
  }
}

const viewCustomerDetails = async (customerId: number) => {
  try {
    const customer = await dashboardApi.getCustomer(customerId)
    selectedCustomer.value = customer
  } catch (error) {
    console.error('Error loading customer details:', error)
  }
}

const deleteCustomer = (customer: Customer) => {
  customerToDelete.value = customer
}

const confirmDelete = async () => {
  if (!customerToDelete.value) return
  
  try {
    await dashboardApi.deleteCustomer(customerToDelete.value.id)
    customerToDelete.value = null
    // Recargar la página actual
    loadPage(pagination.value?.current_page || 1)
  } catch (error) {
    console.error('Error deleting customer:', error)
    alert('Error al eliminar el cliente. Por favor, intenta de nuevo.')
  }
}

onMounted(() => {
  loadPage(1)
})
</script>

<style scoped>
.spinner {
  border: 2px solid #f3f4f6;
  border-top: 2px solid #8b5cf6;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

table {
  min-width: 900px;
}
</style>