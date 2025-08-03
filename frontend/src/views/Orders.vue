<template>
  <div class="p-4">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Pedidos</h1>
      <button @click="openCreateModal" class="bg-blue-600 text-white px-4 py-2 rounded-xl shadow">Nuevo Pedido</button>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full table-auto border-collapse border border-gray-300 dark:border-gray-700">
        <thead class="bg-gray-200 dark:bg-gray-800">
          <tr>
            <th class="px-4 py-2 text-left">Número</th>
            <th class="px-4 py-2 text-left">Cliente</th>
            <th class="px-4 py-2 text-left">Estado</th>
            <th class="px-4 py-2 text-left">Total</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orders" :key="order.id" class="border-t dark:border-gray-700">
            <td class="px-4 py-2">{{ order.order_number }}</td>
            <td class="px-4 py-2">{{ order.customer_name }}</td>
            <td class="px-4 py-2">
              <span :class="statusColor(order.status)" class="text-sm font-medium">
                {{ order.status }}
              </span>
            </td>
            <td class="px-4 py-2">€{{ order.total.toFixed(2) }}</td>
            <td class="px-4 py-2">
              <button @click="openEditModal(order)" class="text-blue-600 hover:underline mr-2">Editar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <OrderModal
      :visible="showModal"
      :isEdit="isEditing"
      :orderData="selectedOrder"
      :token="jwtToken"
      @close="showModal = false"
      @saved="fetchOrders"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import OrderModal from './OrderModal.vue'

const orders = ref<any[]>([])
const showModal = ref(false)
const isEditing = ref(false)
const selectedOrder = ref<any>(null)
const jwtToken = localStorage.getItem('token') || ''

const fetchOrders = async () => {
  try {
    const { data } = await axios.get('/api/dashboard/orders', {
      headers: {
        Authorization: `Bearer ${jwtToken}`
      }
    })
    orders.value = data.data
  } catch (err) {
    console.error('Error cargando pedidos:', err)
  }
}

const openCreateModal = () => {
  selectedOrder.value = null
  isEditing.value = false
  showModal.value = true
}

const openEditModal = (order: any) => {
  selectedOrder.value = order
  isEditing.value = true
  showModal.value = true
}

const statusColor = (status: string) => {
  switch (status) {
    case 'pending': return 'text-yellow-500'
    case 'processing': return 'text-blue-500'
    case 'shipped': return 'text-indigo-500'
    case 'delivered': return 'text-green-600'
    case 'cancelled': return 'text-red-600'
    default: return 'text-gray-500'
  }
}

onMounted(fetchOrders)
</script>

<style scoped>
</style>
