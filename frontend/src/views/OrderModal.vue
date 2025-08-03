<template>
  <div v-if="visible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg w-full max-w-3xl">
      <h2 class="text-xl font-semibold mb-4">{{ isEdit ? 'Editar Pedido' : 'Nuevo Pedido' }}</h2>

      <form @submit.prevent="handleSubmit">
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Cliente</label>
          <select v-model="form.customer_id" class="w-full rounded border px-3 py-2 dark:bg-gray-900 dark:border-gray-700">
            <option disabled value="">Seleccione un cliente</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Productos</label>
          <div v-for="(item, index) in form.items" :key="index" class="flex space-x-2 mb-2">
            <select v-model.number="item.product_id" class="w-1/2 rounded border px-3 py-2 dark:bg-gray-900 dark:border-gray-700">
              <option disabled value="">Seleccione producto</option>
              <option v-for="product in products" :key="product.id" :value="product.id">
                {{ product.name }} - €{{ product.price }}
              </option>
            </select>
            <input type="number" v-model.number="item.quantity" min="1" class="w-1/4 rounded border px-3 py-2 dark:bg-gray-900 dark:border-gray-700" placeholder="Cantidad" />
            <button type="button" @click="removeItem(index)" class="text-red-500 text-sm">Eliminar</button>
          </div>
          <button type="button" @click="addItem" class="text-blue-600 text-sm mt-1">+ Añadir producto</button>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium mb-1">Impuestos</label>
            <input type="number" v-model.number="form.tax" min="0" step="0.01" class="w-full rounded border px-3 py-2 dark:bg-gray-900 dark:border-gray-700" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Envío</label>
            <input type="number" v-model.number="form.shipping" min="0" step="0.01" class="w-full rounded border px-3 py-2 dark:bg-gray-900 dark:border-gray-700" />
          </div>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Notas</label>
          <textarea v-model="form.notes" rows="2" class="w-full rounded border px-3 py-2 dark:bg-gray-900 dark:border-gray-700"></textarea>
        </div>

        <div class="text-right text-sm text-gray-700 dark:text-gray-300 mb-4">
          Subtotal: €{{ subtotal.toFixed(2) }}<br />
          Total: €{{ total.toFixed(2) }}
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" @click="$emit('close')" class="bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white px-4 py-2 rounded-xl">Cancelar</button>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl shadow">
            {{ isEdit ? 'Guardar Cambios' : 'Crear Pedido' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import axios from 'axios'
import type { PropType } from 'vue'

const props = defineProps({
  visible: Boolean,
  isEdit: Boolean,
  orderData: Object as PropType<any>,
  token: String
})
const emit = defineEmits(['close', 'saved'])

const products = ref<any[]>([])
const customers = ref<any[]>([])

interface OrderItem {
  product_id: number
  quantity: number
}

interface OrderForm {
  customer_id: string | number
  items: OrderItem[]
  tax: number
  shipping: number
  notes: string
}


const form = ref<OrderForm>({
  customer_id: '',
  items: [],
  tax: 0,
  shipping: 0,
  notes: ''
})

const subtotal = computed(() => {
  return form.value.items.reduce((sum, item) => {
    const product = products.value.find(p => p.id === item.product_id)
    const price = product?.price || 0
    return sum + item.quantity * price
  }, 0)
})

const total = computed(() => subtotal.value + form.value.tax + form.value.shipping)

const fetchProducts = async () => {
  const { data } = await axios.get('/api/dashboard/products', {
    headers: { Authorization: `Bearer ${props.token}` }
  })
  products.value = data.data
}

const fetchCustomers = async () => {
  const { data } = await axios.get('/api/dashboard/customers', {
    headers: { Authorization: `Bearer ${props.token}` }
  })
  customers.value = data.data
}

const initializeForm = () => {
  if (props.isEdit && props.orderData) {
    const o = props.orderData
    form.value = {
      customer_id: o.customer?.id || '',
      items: o.items?.map((i: any) => ({
        product_id: i.product_id || i.product?.id,
        quantity: i.quantity
      })) || [],
      tax: o.tax || 0,
      shipping: o.shipping || 0,
      notes: o.notes || ''
    }
  } else {
    form.value = { customer_id: '', items: [], tax: 0, shipping: 0, notes: '' }
  }
}

const handleSubmit = async () => {
  try {
    const payload = {
      ...form.value,
      subtotal: subtotal.value,
      total: total.value,
      items: form.value.items.map(item => {
        const product = products.value.find(p => p.id === item.product_id)
        return {
          ...item,
          unit_price: product?.price || 0
        }
      })
    }

    if (props.isEdit && props.orderData?.id) {
      await axios.put(`/api/dashboard/orders/${props.orderData.id}`, payload, {
        headers: { Authorization: `Bearer ${props.token}` }
      })
    } else {
      await axios.post('/api/dashboard/orders', payload, {
        headers: { Authorization: `Bearer ${props.token}` }
      })
    }

    emit('saved')
    emit('close')
  } catch (err) {
    console.error('Error guardando pedido:', err)
  }
}

const addItem = () => {
  form.value.items.push({ product_id: 0, quantity: 1 })
}

const removeItem = (index: number) => {
  form.value.items.splice(index, 1)
}

watch(() => props.visible, (v) => {
  if (v) {
    fetchProducts()
    fetchCustomers()
    initializeForm()
  }
})
</script>

<style scoped>
</style>
