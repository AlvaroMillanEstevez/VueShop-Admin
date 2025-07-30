<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-none">
    <!-- Header -->
    <div class="bg-white p-4 sm:p-6 lg:p-8 rounded-2xl shadow-sm mb-6 sm:mb-8 border-l-4 border-l-blue-500">
      <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-2 sm:mb-3">Gestión de Productos</h1>
      <p class="text-gray-600 text-base sm:text-lg">Administra tu catálogo completo de productos</p>
    </div>

    <div v-if="loading" class="flex justify-center items-center h-64 text-gray-500">
      <div class="spinner mr-3"></div>
      <span class="text-lg">Cargando productos...</span>
    </div>

    <div v-else class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="p-4 sm:p-6 lg:p-8 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <div>
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900">Lista de Productos</h3>
            <p class="text-gray-500 mt-1 text-sm sm:text-base">Total: {{ pagination?.total || 0 }} productos</p>
          </div>
          <button 
            @click="openModal()"
            class="w-full sm:w-auto px-6 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-colors font-medium"
          >
            Añadir Producto
          </button>
        </div>
      </div>

      <!-- Vista móvil (cards) -->
      <div class="block sm:hidden p-4 space-y-4" v-if="products.length > 0">
        <div v-for="product in products" :key="product.id" class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex items-start space-x-3">
            <img 
              :src="product.image_url || 'https://via.placeholder.com/150'" 
              :alt="product.name" 
              class="w-20 h-20 rounded-lg object-cover flex-shrink-0 bg-gray-100"
              @error="(e) => (e.target as HTMLImageElement).src = 'https://via.placeholder.com/150'"
            >
            <div class="flex-1 min-w-0">
              <h4 class="font-semibold text-gray-900 truncate">{{ product.name }}</h4>
              <p class="text-sm text-gray-500 mb-1">SKU: {{ product.sku }}</p>
              <p class="text-sm text-gray-500">{{ product.category || 'Sin categoría' }}</p>
              <p class="font-bold text-lg text-gray-900 mt-1">€{{ formatNumber(product.price) }}</p>
            </div>
          </div>
          
          <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <span :class="product.stock <= 10 ? 'text-red-600 bg-red-50' : 'text-green-600 bg-green-50'" 
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                {{ product.stock }} unidades
              </span>
              <button
                @click="toggleProductStatus(product)"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors"
                :class="product.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ product.active ? 'Activo' : 'Inactivo' }}
              </button>
            </div>
            
            <div class="flex items-center space-x-2">
              <button 
                @click="openModal(product)"
                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
              </button>
              <button 
                @click="deleteProduct(product)"
                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Vista desktop (tabla) -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full" v-if="products.length > 0">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Producto</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">SKU</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Categoría</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Precio</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Stock</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Estado</th>
              <th class="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50 transition-colors">
              <td class="py-4 px-6">
                <div class="flex items-center space-x-4">
                  <img 
                    :src="product.image_url || 'https://via.placeholder.com/150'" 
                    :alt="product.name" 
                    class="w-16 h-16 rounded-xl object-cover flex-shrink-0 bg-gray-100"
                    @error="(e) => (e.target as HTMLImageElement).src = 'https://via.placeholder.com/150'"
                  >
                  <div class="min-w-0 flex-1">
                    <h4 class="font-semibold text-gray-900 truncate">{{ product.name }}</h4>
                    <p class="text-sm text-gray-500 truncate">{{ product.description?.substring(0, 50) }}{{ product.description && product.description.length > 50 ? '...' : '' }}</p>
                  </div>
                </div>
              </td>
              <td class="py-4 px-6 font-mono text-sm text-blue-600">{{ product.sku }}</td>
              <td class="py-4 px-6">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ product.category || 'Sin categoría' }}
                </span>
              </td>
              <td class="py-4 px-6 font-bold text-lg text-gray-900">€{{ formatNumber(product.price) }}</td>
              <td class="py-4 px-6">
                <span 
                  :class="product.stock <= 10 ? 'text-red-600 bg-red-50' : 'text-green-600 bg-green-50'" 
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ product.stock }} unidades
                </span>
              </td>
              <td class="py-4 px-6">
                <button
                  @click="toggleProductStatus(product)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors"
                  :class="product.active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'"
                >
                  {{ product.active ? 'Activo' : 'Inactivo' }}
                </button>
              </td>
              <td class="py-4 px-6">
                <div class="flex items-center space-x-2">
                  <button 
                    @click="openModal(product)"
                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                    title="Editar"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </button>
                  <button 
                    @click="deleteProduct(product)"
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
          </svg>
          <p class="text-lg">No hay productos registrados</p>
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

    <!-- Modal de Producto -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="closeModal">
      <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-2xl font-bold text-gray-900">{{ editingProduct.id ? 'Editar Producto' : 'Nuevo Producto' }}</h3>
        </div>
        
        <form @submit.prevent="saveProduct" class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del producto *</label>
              <input 
                v-model="editingProduct.name" 
                type="text" 
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Ej: Camiseta deportiva"
              >
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
              <textarea 
                v-model="editingProduct.description" 
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Descripción detallada del producto"
              ></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">SKU *</label>
              <input 
                v-model="editingProduct.sku" 
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Ej: PROD-001"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
              <input 
                v-model="editingProduct.category" 
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Ej: Ropa deportiva"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Precio (€) *</label>
              <input 
                v-model.number="editingProduct.price" 
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="0.00"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Stock *</label>
              <input 
                v-model.number="editingProduct.stock" 
                type="number"
                min="0"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="0"
              >
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">URL de imagen</label>
              <input 
                v-model="editingProduct.image_url" 
                type="url"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="https://ejemplo.com/imagen.jpg"
              >
            </div>
            
            <div class="md:col-span-2">
              <label class="flex items-center space-x-3">
                <input 
                  v-model="editingProduct.active" 
                  type="checkbox"
                  class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                >
                <span class="text-sm font-medium text-gray-700">Producto activo</span>
              </label>
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
              class="px-6 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ saving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div v-if="productToDelete" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click="productToDelete = null">
      <div class="bg-white rounded-2xl max-w-md w-full p-6" @click.stop>
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">Eliminar Producto</h3>
          <p class="text-gray-500 mb-6">¿Estás seguro de que deseas eliminar <strong>{{ productToDelete.name }}</strong>? Esta acción no se puede deshacer.</p>
          
          <div class="flex justify-center space-x-3">
            <button 
              @click="productToDelete = null"
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
import { dashboardApi, type Product } from '@/services/api'

const loading = ref(true)
const products = ref<Product[]>([])
const pagination = ref<any>(null)
const showModal = ref(false)
const productToDelete = ref<Product | null>(null)
const saving = ref(false)

const editingProduct = reactive<Partial<Product>>({
  name: '',
  description: '',
  price: 0,
  stock: 0,
  sku: '',
  category: '',
  image_url: '',
  active: true
})

const formatNumber = (num: number): string => {
  return new Intl.NumberFormat('es-ES', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num)
}

const loadPage = async (page: number) => {
  try {
    loading.value = true
    const response = await dashboardApi.getProducts(page)
    products.value = response.data
    pagination.value = response
  } catch (error) {
    console.error('Error loading products:', error)
  } finally {
    loading.value = false
  }
}

const openModal = (product?: Product) => {
  if (product) {
    Object.assign(editingProduct, product)
  } else {
    Object.assign(editingProduct, {
      name: '',
      description: '',
      price: 0,
      stock: 0,
      sku: '',
      category: '',
      image_url: '',
      active: true
    })
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  Object.assign(editingProduct, {
    name: '',
    description: '',
    price: 0,
    stock: 0,
    sku: '',
    category: '',
    image_url: '',
    active: true
  })
}

const saveProduct = async () => {
  try {
    saving.value = true
    
    if (editingProduct.id) {
      // Actualizar producto existente
      await dashboardApi.updateProduct(editingProduct.id, editingProduct)
    } else {
      // Crear nuevo producto
      await dashboardApi.createProduct(editingProduct)
    }
    
    closeModal()
    // Recargar la página actual
    loadPage(pagination.value?.current_page || 1)
  } catch (error: any) {
    console.error('Error saving product:', error)
    alert(error.response?.data?.message || 'Error al guardar el producto. Por favor, intenta de nuevo.')
  } finally {
    saving.value = false
  }
}

const toggleProductStatus = async (product: Product) => {
  try {
    await dashboardApi.updateProduct(product.id, { active: !product.active })
    product.active = !product.active
  } catch (error) {
    console.error('Error updating product status:', error)
    // Recargar en caso de error
    loadPage(pagination.value?.current_page || 1)
  }
}

const deleteProduct = (product: Product) => {
  productToDelete.value = product
}

const confirmDelete = async () => {
  if (!productToDelete.value) return
  
  try {
    await dashboardApi.deleteProduct(productToDelete.value.id)
    productToDelete.value = null
    // Recargar la página actual
    loadPage(pagination.value?.current_page || 1)
  } catch (error) {
    console.error('Error deleting product:', error)
    alert('Error al eliminar el producto. Por favor, intenta de nuevo.')
  }
}

onMounted(() => {
  loadPage(1)
})
</script>

<style scoped>
.spinner {
  border: 2px solid #f3f4f6;
  border-top: 2px solid #3b82f6;
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