<template>
  <div v-if="visible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg w-full max-w-lg">
      <h2 class="text-xl font-semibold mb-4">{{ isEdit ? 'Editar Usuario' : 'Nuevo Usuario' }}</h2>

      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 gap-4">
          <input v-model="form.name" class="form-input" placeholder="Nombre completo" />
          <input v-model="form.email" class="form-input" type="email" placeholder="Correo electrónico" />
          <input v-if="!isEdit" v-model="form.password" class="form-input" type="password" placeholder="Contraseña" />
          <select v-model="form.role" class="form-select">
            <option disabled value="">Seleccione rol</option>
            <option value="admin">Administrador</option>
            <option value="user">Usuario</option>
          </select>
          <select v-model="form.is_active" class="form-select">
            <option :value="true">Activo</option>
            <option :value="false">Inactivo</option>
          </select>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="$emit('close')" class="bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white px-4 py-2 rounded-xl">Cancelar</button>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl shadow">
            {{ isEdit ? 'Actualizar' : 'Crear' }}
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
    console.error('Error guardando usuario:', err)
  }
}
</script>

<style scoped>
</style>
