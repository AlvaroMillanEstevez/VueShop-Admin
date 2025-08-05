<template>
  <div v-if="visible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg w-full max-w-lg">
      <h2 class="text-xl font-semibold mb-4">{{ isEdit ? 'Edit User' : 'New User' }}</h2>

      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 gap-4">
          <input v-model="form.name" class="input-field" placeholder="Full Name" />
          <input v-model="form.email" class="input-field" type="email" placeholder="Email Address" />
          <input v-if="!isEdit" v-model="form.password" class="input-field" type="password" placeholder="Password" />
          <select v-model="form.role" class="select-field">
            <option disabled value="">Select Role</option>
            <option value="admin">Administrator</option>
            <option value="user">User</option>
          </select>
          <select v-model="form.is_active" class="select-field">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </select>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="$emit('close')" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary">
            {{ isEdit ? 'Update' : 'Create' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  visible: Boolean,
  isEdit: Boolean,
  userData: Object,
  token: String
})
const emit = defineEmits(['close', 'saved'])

const form = ref({
  name: '',
  email: '',
  password: '',
  role: '',
  is_active: true
})

watch(() => props.userData, (u) => {
  if (props.isEdit && u) {
    form.value = {
      name: u.name || '',
      email: u.email || '',
      password: '',
      role: u.role || 'user',
      is_active: u.is_active ?? true
    }
  } else {
    form.value = { name: '', email: '', password: '', role: '', is_active: true }
  }
}, { immediate: true })

const handleSubmit = async () => {
  try {
    const config = { headers: { Authorization: `Bearer ${props.token}` } }

    const payload = { ...form.value }
    if (props.isEdit && props.userData?.id) {
      await axios.put(`/api/admin/users/${props.userData.id}`, payload, config)
    } else {
      await axios.post('/api/admin/users', payload, config)
    }

    emit('saved')
    emit('close')
  } catch (err) {
    console.error('Error saving user:', err)
  }
}
</script>

<style scoped>
</style>
