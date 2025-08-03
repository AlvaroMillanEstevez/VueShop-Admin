<template>
  <div class="p-4">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Productos</h1>
      <button @click="openCreateModal" class="bg-blue-600 text-white px-4 py-2 rounded-xl shadow">Nuevo Producto</button>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full table-auto border-collapse border border-gray-300 dark:border-gray-700">
        <thead class="bg-gray-200 dark:bg-gray-800">
          <tr>
            <th class="px-4 py-2 text-left">Nombre</th>
            <th class="px-4 py-2 text-left">SKU</th>
            <th class="px-4 py-2 text-left">Precio</th>
            <th class="px-4 py-2 text-left">Stock</th>
            <th class="px-4 py-2 text-left">Categoría</th>
            <th class="px-4 py-2 text-left">Activo</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in products" :key="product.id" class="border-t dark:border-gray-700">
            <td class="px-4 py-2">{{ product.name }}</td>
            <td class="px-4 py-2">{{ product.sku }}</td>
            <td class="px-4 py-2">€{{ product.price.toFixed(2) }}</td>
            <td class="px-4 py-2">{{ product.stock }}</td>
            <td class="px-4 py-2">{{ product.category }}</td>
            <td class="px-4 py-2">
              <span class="text-sm font-medium" :class="product.active ? 'text-green-600' : 'text-red-500'">
                {{ product.active ? 'Sí' : 'No' }}
              </span>
            </td>
            <td class="px-4 py-2">
              <button @click="openEditModal(product)" class="text-blue-600 hover:underline mr-2">Editar</button>
              <!-- <button @click="deleteProduct(product.id)" class="text-red-600 hover:underline">Eliminar</button> -->
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <ProductModal
      :visible="showModal"
      :isEdit="isEditing"
      :productData="selectedProduct"
      :token="jwtToken"
      @close="showModal = false"
      @saved="fetchProducts"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ProductModal from './ProductModal.vue'

const products = ref<any[]>([])
const showModal = ref(false)
const isEditing = ref(false)
const selectedProduct = ref<any>(null)
const jwtToken = localStorage.getItem('token') || ''

const fetchProducts = async () => {
  try {
    const { data } = await axios.get('/api/dashboard/products', {
      headers: {
        Authorization: `Bearer ${jwtToken}`
      }
    })
    products.value = data.data
  } catch (err) {
    console.error('Error cargando productos:', err)
  }
}

const openCreateModal = () => {
  selectedProduct.value = null
  isEditing.value = false
  showModal.value = true
}

const openEditModal = (product: any) => {
  selectedProduct.value = product
  isEditing.value = true
  showModal.value = true
}

onMounted(fetchProducts)
</script>

<style scoped>
</style>
