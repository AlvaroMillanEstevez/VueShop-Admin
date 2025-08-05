<template>
  <div v-if="visible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-xl w-full max-w-lg">
      <h2 class="text-xl font-bold mb-4 text-primary">{{ isEdit ? 'Edit Product' : 'New Product' }}</h2>

      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 gap-4">
          <input v-model="form.name" class="form-input" placeholder="Product name" />
          <textarea v-model="form.description" class="form-input" placeholder="Description"></textarea>
          <input type="number" v-model.number="form.price" class="form-input" placeholder="Price" />
          <input type="number" v-model.number="form.stock" class="form-input" placeholder="Available stock" />
          <input v-model="form.sku" class="form-input" placeholder="SKU" />
          <input v-model="form.category" class="form-input" placeholder="Category" />
          <input v-model="form.image_url" class="form-input" placeholder="Image URL" />
          <select v-model="form.active" class="form-select">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </select>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button type="button" @click="close" class="px-4 py-2 rounded-lg bg-gray-300 dark:bg-gray-700 text-black dark:text-white">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white">{{ isEdit ? 'Update' : 'Create' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps<{
  visible: boolean
  isEdit: boolean
  productData?: any
  token: string
}>()

const emit = defineEmits(['close', 'saved'])

const form = ref({
  name: '',
  description: '',
  price: 0,
  stock: 0,
  sku: '',
  category: '',
  image_url: '',
  active: true,
})

watch(() => props.productData, (newVal) => {
  if (props.isEdit && newVal) {
    form.value = { ...newVal }
  }
}, { immediate: true })

function close() {
  emit('close')
}

async function handleSubmit() {
  const config = {
    headers: {
      Authorization: `Bearer ${props.token}`
    }
  }

  try {
    if (props.isEdit && props.productData?.id) {
      await axios.put(`/api/dashboard/products/${props.productData.id}`, form.value, config)
    } else {
      await axios.post('/api/dashboard/products', form.value, config)
    }
    emit('saved')
    close()
  } catch (err) {
    console.error('Error saving product:', err)
  }
}
</script>
