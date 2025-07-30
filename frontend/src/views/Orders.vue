<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-none">
    <!-- Header -->
    <div class="bg-white p-4 sm:p-6 lg:p-8 rounded-2xl shadow-sm mb-6 sm:mb-8 border-l-4 border-l-green-500">
      <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-2 sm:mb-3">Gestión de Pedidos</h1>
      <p class="text-gray-600 text-base sm:text-lg">Administra todos los pedidos de tu tienda</p>
    </div>

    <div v-if="loading" class="flex justify-center items-center h-64 text-gray-500">
      <div class="spinner mr-3"></div>
      <span class="text-lg">Cargando pedidos...</span>
    </div>

    <div v-else class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="p-4 sm:p-6 lg:p-8 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <div>
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900">Lista de Pedidos</h3>
            <p class="text-gray-500 mt-1 text-sm sm:text-base">Total: {{ pagination?.total || 0 }} pedidos</p>
          </div>
          <!-- Aquí podrías añadir un botón de crear pedido si lo necesitas -->
        </div>
      </div>

      <!-- Vista móvil (cards) -->
      <div class="block lg:hidden p-4 space-y-4" v-if="orders.length > 0">
        <div v-for="order in orders" :key="order.id" class="bg-white border border-gray-200 rounded-lg p-4">
          <!-- Encabezado de la card -->
          <div class="flex justify-between items-start mb-3">
            <div>
              <span class="font-mono font-semibold text-blue-600 text-sm">#{{ order.order_number }}</span>
              <div class="mt-1">
                <div class="font-medium text-gray-900">{{ order.customer?.name }}</div>
                <div class="text-sm text-gray-500">{{ order.customer?.email }}</div>
              </div>
            </div>
            <span class="text-xs text-gray-500">{{ formatDate(order.created_at) }}</span>
          </div>

          <!-- Estado -->
          <div class="mb-3">
            <label class="text-xs text-gray-500 block mb-1">Estado:</label>
            <select 
              :value="order.status" 
              @change="updateOrderStatus(order.id, ($event.target as HTMLSelectElement).value)"
              class="w-full px-3 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="getStatusSelectClass(order.status)"
              :disabled="updating === order.id"
            >
              <option value="pending">Pendiente</option>
              <option value="processing">Procesando</option>
              <option value="shipped">Enviado</option>
              <option value="delivered">Entregado</option>
              <option value="cancelled">Cancelado</option>
            </select>
          </div>

          <!-- Información del pedido -->
          <div class="space-y-2 text-sm mb-4">
            <div class="flex justify-between">
              <span class="text-gray-500">Artículos:</span>
              <span class="font-medium">{{ order.items?.length || 0 }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Subtotal:</span>
              <span>€{{ formatNumber(order.subtotal) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">IVA:</span>
              <span>€{{ formatNumber(order.tax) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Envío:</span>
              <span>€{{ formatNumber(order.shipping) }}</span>
            </div>
            <div class="flex justify-between pt-2 border-t border-gray-200">
              <span class="font-medium text-gray-900">Total:</span>
              <span class="font-bold text-lg text-gray-900">€{{ formatNumber(order.total) }}</span>
            </div>
          </div>

          <!-- Botón de acción -->
          <button 
            @click="viewOrderDetails(order.id)"
            class="w-full px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors font-medium"
          >
            Ver detalles
          </button>
        </div>
      </div>

      <!-- Vista desktop (tabla) -->
      <div class="hidden lg:block overflow-x-auto">
        <table class="w-full" v-if="orders.length > 0">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Pedido</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Cliente</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Estado</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Artículos</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm hidden xl:table-cell">Subtotal</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm hidden xl:table-cell">IVA</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm hidden xl:table-cell">Envío</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Total</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Fecha</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50 transition-colors">
              <td class="py-4 px-6 font-mono font-semibold text-blue-600">#{{ order.order_number }}</td>
              <td class="py-4 px-6">
                <div>
                  <div class="font-medium text-gray-900">{{ order.customer?.name }}</div>
                  <div class="text-sm text-gray-500">{{ order.customer?.email }}</div>
                </div>
              </td>
              <td class="py-4 px-6">
                <select 
                  :value="order.status" 
                  @change="updateOrderStatus(order.id, ($event.target as HTMLSelectElement).value)"
                  class="px-3 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="getStatusSelectClass(order.status)"
                  :disabled="updating === order.id"
                >
                  <option value="pending">Pendiente</option>
                  <option value="processing">Procesando</option>
                  <option value="shipped">Enviado</option>
                  <option value="delivered">Entregado</option>
                  <option value="cancelled">Cancelado</option>
                </select>
              </td>
              <td class="py-4 px-6">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ order.items?.length || 0 }} artículos
                </span>
              </td>
              <td class="py-4 px-6 text-gray-700 hidden xl:table-cell">€{{ formatNumber(order.subtotal) }}</td>
              <td class="py-4 px-6 text-gray-700 hidden xl:table-cell">€{{ formatNumber(order.tax) }}</td>
              <td class="py-4 px-6 text-gray-700 hidden xl:table-cell">€{{ formatNumber(order.shipping) }}</td>
              <td class="py-4 px-6 font-bold text-lg text-gray-900">€{{ formatNumber(order.total) }}</td>
              <td class="py-4 px-6 text-sm text-gray-500">{{ formatDate(order.created_at) }}</td>
              <td class="py-4 px-6">
                <button 
                  @click="viewOrderDetails(order.id)"
                  class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors font-medium"
                >
                  Ver detalles
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="orders.length === 0" class="text-center py-12 text-gray-500">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
        </svg>
        <p class="text-lg">No hay pedidos registrados</p>
      </div>

      <!-- Paginación -->
      <div v-if="pagination && pagination.last_page > 1" class="p-4 sm:p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <button 
            @click="loadPage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Anterior
          </button>
          <span class="text-sm text-gray-700 order-first sm:order-none">
            Página {{ pagination.current_page }} de {{ pagination.last_page }}
          </span>
          <button 
            @click="loadPage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de detalles -->
    <div v-if="selectedOrder" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click="selectedOrder = null">
      <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="p-4 sm:p-6 border-b border-gray-200">
          <div class="flex justify-between items-start">
            <div>
              <h3 class="text-xl sm:text-2xl font-bold text-gray-900">Detalles del Pedido</h3>
              <p class="text-sm sm:text-base text-gray-600 mt-1">#{{ selectedOrder.order_number }}</p>
            </div>
            <button @click="selectedOrder = null" class="p-2 text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>
        
        <div class="p-4 sm:p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
              <h4 class="font-semibold text-gray-900 mb-4">Información del cliente</h4>
              <div class="space-y-3 text-sm">
                <div><span class="text-gray-500">Nombre:</span> <span class="font-medium">{{ selectedOrder.customer?.name }}</span></div>
                <div><span class="text-gray-500">Email:</span> <span class="font-medium break-all">{{ selectedOrder.customer?.email }}</span></div>
                <div><span class="text-gray-500">Teléfono:</span> <span class="font-medium">{{ selectedOrder.customer?.phone || '-' }}</span></div>
                <div><span class="text-gray-500">Dirección:</span> <span class="font-medium">{{ selectedOrder.customer?.address || '-' }}</span></div>
              </div>
            </div>
            
            <div>
              <h4 class="font-semibold text-gray-900 mb-4">Información del pedido</h4>
              <div class="space-y-3 text-sm">
                <div><span class="text-gray-500">Estado:</span> 
                  <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusClasses(selectedOrder.status)">
                    {{ translateStatus(selectedOrder.status) }}
                  </span>
                </div>
                <div><span class="text-gray-500">Fecha:</span> <span class="font-medium">{{ formatDate(selectedOrder.created_at) }}</span></div>
                <div><span class="text-gray-500">Subtotal:</span> <span class="font-medium">€{{ formatNumber(selectedOrder.subtotal) }}</span></div>
                <div><span class="text-gray-500">IVA:</span> <span class="font-medium">€{{ formatNumber(selectedOrder.tax) }}</span></div>
                <div><span class="text-gray-500">Envío:</span> <span class="font-medium">€{{ formatNumber(selectedOrder.shipping) }}</span></div>
                <div><span class="text-gray-500">Total:</span> <span class="font-bold text-lg text-green-600">€{{ formatNumber(selectedOrder.total) }}</span></div>
              </div>
            </div>
          </div>
          
          <div>
            <h4 class="font-semibold text-gray-900 mb-4">Artículos del pedido</h4>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <!-- Vista móvil de artículos -->
              <div class="block sm:hidden">
                <div v-for="item in selectedOrder.items" :key="item.id" class="p-4 border-b border-gray-200 last:border-b-0">
                  <div class="font-medium text-gray-900">{{ item.product?.name }}</div>
                  <div class="text-sm text-gray-500 mb-2">SKU: {{ item.product?.sku }}</div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ item.quantity }} x €{{ formatNumber(item.unit_price) }}</span>
                    <span class="font-medium">€{{ formatNumber(item.total_price) }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Vista desktop de artículos -->
              <table class="w-full hidden sm:table">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="text-left px-4 py-3 text-sm font-medium text-gray-900">Producto</th>
                    <th class="text-center px-4 py-3 text-sm font-medium text-gray-900">Cantidad</th>
                    <th class="text-right px-4 py-3 text-sm font-medium text-gray-900">Precio unit.</th>
                    <th class="text-right px-4 py-3 text-sm font-medium text-gray-900">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="item in selectedOrder.items" :key="item.id">
                    <td class="px-4 py-3">
                      <div class="font-medium text-gray-900">{{ item.product?.name }}</div>
                      <div class="text-sm text-gray-500">SKU: {{ item.product?.sku }}</div>
                    </td>
                    <td class="px-4 py-3 text-center">{{ item.quantity }}</td>
                    <td class="px-4 py-3 text-right">€{{ formatNumber(item.unit_price) }}</td>
                    <td class="px-4 py-3 text-right font-medium">€{{ formatNumber(item.total_price) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { dashboardApi, type Order } from '@/services/api'

const loading = ref(true)
const orders = ref<Order[]>([])
const pagination = ref<any>(null)
const selectedOrder = ref<Order | null>(null)
const updating = ref<number | null>(null)

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
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(dateString))
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

const getStatusSelectClass = (status: string): string => {
  const classes: Record<string, string> = {
    pending: 'border-l-4 border-l-yellow-500',
    processing: 'border-l-4 border-l-blue-500',
    shipped: 'border-l-4 border-l-green-500',
    delivered: 'border-l-4 border-l-emerald-500',
    cancelled: 'border-l-4 border-l-red-500'
  }
  return classes[status] || ''
}

const loadPage = async (page: number) => {
  try {
    loading.value = true
    const response = await dashboardApi.getOrders(page)
    orders.value = response.data
    pagination.value = response
  } catch (error) {
    console.error('Error loading orders:', error)
  } finally {
    loading.value = false
  }
}

const updateOrderStatus = async (orderId: number, newStatus: string) => {
  try {
    updating.value = orderId
    await dashboardApi.updateOrderStatus(orderId, newStatus)
    
    // Actualizar el estado local
    const order = orders.value.find(o => o.id === orderId)
    if (order) {
      order.status = newStatus
    }
  } catch (error) {
    console.error('Error updating order status:', error)
    // Recargar datos en caso de error
    loadPage(pagination.value?.current_page || 1)
  } finally {
    updating.value = null
  }
}

const viewOrderDetails = async (orderId: number) => {
  try {
    const order = await dashboardApi.getOrder(orderId)
    selectedOrder.value = order
  } catch (error) {
    console.error('Error loading order details:', error)
  }
}

onMounted(() => {
  loadPage(1)
})
</script>

<style scoped>
.spinner {
  border: 2px solid #f3f4f6;
  border-top: 2px solid #10b981;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive breakpoints */
@media (max-width: 1280px) {
  table {
    min-width: 800px;
  }
}
</style>