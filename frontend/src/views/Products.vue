<template>
  <div class="p-6">
    <div class="mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Products</h1>
        <p class="text-gray-600">
          {{ authStore.isAdmin ? 'Manage all products from all sellers' : 'Manage your products' }}
        </p>
      </div>
      <button @click="openNewProductModal"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">

        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <span>New Product</span>
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
          <h3 class="text-sm font-medium text-red-800">Error Loading Products</h3>
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
      <select v-model="filters.category" @change="() => loadProducts()" class="form-select">
        <option value="">All Categories</option>
        <option value="Electronics">Electronics</option>
        <option value="Fashion">Fashion</option>
        <option value="Gaming">Gaming</option>
        <option value="Audio">Audio</option>
        <option value="Laptops">Laptops</option>
        <option value="Smartphones">Smartphones</option>
        <option value="Tablets">Tablets</option>
        <option value="Wearables">Wearables</option>
      </select>

      <select v-model="filters.active" @change="() => loadProducts()" class="form-select">
        <option value="">All Status</option>
        <option value="true">Active</option>
        <option value="false">Inactive</option>
      </select>

      <input v-model="filters.search" @input="debounceSearch" type="text" placeholder="Search products..."
        class="search-input">
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Products Grid -->
    <div v-else-if="!error" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div v-for="product in products" :key="product.id"
        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
        <!-- Product Image -->
        <div class="h-48 bg-gray-200 flex items-center justify-center">
          <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="w-full h-full object-cover"
            @error="handleImageError">
          <svg v-else class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
          </svg>
        </div>

        <!-- Product Info -->
        <div class="p-4">
          <div class="flex justify-between items-start mb-2">
            <h3 class="text-lg font-semibold text-gray-900 truncate">{{ product.name }}</h3>
            <span :class="product.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              class="px-2 py-1 text-xs font-semibold rounded-full">
              {{ product.active ? 'Active' : 'Inactive' }}
            </span>
          </div>

          <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ product.description }}</p>

          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-500">Price:</span>
              <span class="text-lg font-bold text-green-600">€{{ formatCurrency(product.price) }}</span>
            </div>

            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-500">Stock:</span>
              <span :class="getStockClass(product.stock)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ product.stock }} units
              </span>
            </div>

            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-500">SKU:</span>
              <span class="text-sm font-mono text-gray-900">{{ product.sku }}</span>
            </div>

            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-500">Category:</span>
              <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">{{ product.category
                }}</span>
            </div>

            <!-- Seller info (only for admin) -->
            <div v-if="authStore.isAdmin && product.seller"
              class="flex justify-between items-center border-t pt-2 mt-2">
              <span class="text-sm text-gray-500">Seller:</span>
              <span class="text-sm font-medium text-blue-600">{{ product.seller.name }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="mt-4 flex space-x-2">
            <button v-if="canEditProduct(product)" @click="editProduct(product)"
              class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-medium transition-colors">
              Edit
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="mt-8 flex justify-center">
      <div class="flex space-x-2">
        <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
          class="px-4 py-2 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
          Previous
        </button>

        <span class="px-4 py-2 bg-blue-600 text-white rounded">
          {{ pagination.current_page }} of {{ pagination.last_page }}
        </span>

        <button @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-4 py-2 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
          Next
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && !error && products.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No Products Found</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating a new product.</p>
    </div>
  </div>

  <ProductModal :visible="showProductModal" :isEdit="!!selectedProduct" :productData="selectedProduct" :token="token"
    @close="showProductModal = false" @saved="handleProductSaved" />
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { dashboardApi, handleAPIError } from '@/services/api'
import ProductModal from './ProductModal.vue'

const authStore = useAuthStore()
const token = computed(() => authStore.token ?? '')


const showProductModal = ref(false)
const selectedProduct = ref<Product | null>(null)

const openNewProductModal = () => {
  selectedProduct.value = null
  showProductModal.value = true
}

const editProduct = (product: Product): void => {
  selectedProduct.value = product
  showProductModal.value = true
}

const viewProduct = (product: Product): void => {
  selectedProduct.value = product
  showProductModal.value = true
}

const handleProductSaved = (): void => {
  loadProducts().catch(err => {
    console.error('Error reloading products:', err)
    error.value = 'Failed to reload products after save'
  })
}

interface Product {
  id: number
  name: string
  description: string
  price: number
  stock: number
  sku: string
  category: string
  active: boolean
  image_url?: string
  seller?: {
    id: number
    name: string
  }
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

const loading = ref<boolean>(true)
const products = ref<Product[]>([])
const pagination = ref<Pagination>({ current_page: 1, last_page: 1, per_page: 12, total: 0, from: 0, to: 0 })
const error = ref<string>('')

const filters = reactive({ category: '', active: '', search: '' })
let searchTimeout: number | null = null

const loadProducts = async (page: number = 1): Promise<void> => {
  try {
    loading.value = true
    error.value = ''

    const params: any = { page }
    if (filters.category) params.category = filters.category
    if (filters.active) params.active = filters.active
    if (filters.search) params.search = filters.search

    const response = await dashboardApi.getProducts(params)
    if (response.success && response.data) {
      const data = response.data as any
      products.value = data.data || []
      pagination.value = {
        current_page: data.current_page || 1,
        last_page: data.last_page || 1,
        per_page: data.per_page || 12,
        total: data.total || 0,
        from: data.from || 0,
        to: data.to || 0
      }
    } else {
      error.value = handleAPIError(response, 'Failed to load products')
      products.value = []
    }
  } catch (err) {
    console.error('Unexpected error loading products:', err)
    error.value = 'An unexpected error occurred while loading products'
    products.value = []
  } finally {
    loading.value = false
  }
}

const debounceSearch = (): void => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadProducts(), 500)
}

const changePage = (page: number): void => {
  if (page >= 1 && page <= pagination.value.last_page) loadProducts(page)
}

const canEditProduct = (product: Product): boolean => {
  if (authStore.isAdmin) return true
  return product.seller?.id === authStore.user?.id
}

const handleImageError = (event: Event): void => {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'
}

const retryLoad = (): void => loadProducts()

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount)
}

const getStockClass = (stock: number): string => {
  if (stock === 0) return 'bg-red-100 text-red-800'
  if (stock <= 10) return 'bg-yellow-100 text-yellow-800'
  return 'bg-green-100 text-green-800'
}

onMounted(() => loadProducts())
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
