<template>
  <div class="p-4">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Usuarios</h1>
      <button @click="openCreateModal" class="bg-blue-600 text-white px-4 py-2 rounded-xl shadow">Nuevo Usuario</button>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full table-auto border-collapse border border-gray-300 dark:border-gray-700">
        <thead class="bg-gray-200 dark:bg-gray-800">
          <tr>
            <th class="px-4 py-2 text-left">Nombre</th>
            <th class="px-4 py-2 text-left">Email</th>
            <th class="px-4 py-2 text-left">Rol</th>
            <th class="px-4 py-2 text-left">Activo</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id" class="border-t dark:border-gray-700">
            <td class="px-4 py-2">{{ user.name }}</td>
            <td class="px-4 py-2">{{ user.email }}</td>
            <td class="px-4 py-2 capitalize">{{ user.role }}</td>
            <td class="px-4 py-2">
              <span :class="user.is_active ? 'text-green-600' : 'text-red-500'" class="text-sm font-medium">
                {{ user.is_active ? 'Sí' : 'No' }}
              </span>
            </td>
            <td class="px-4 py-2">
              <button @click="openEditModal(user)" class="text-blue-600 hover:underline mr-2">Editar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <UserModal
      :visible="showModal"
      :isEdit="isEditing"
      :userData="selectedUser"
      :token="jwtToken"
      @close="showModal = false"
      @saved="fetchUsers"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import UserModal from './UserModal.vue'

const users = ref<any[]>([])
const showModal = ref(false)
const isEditing = ref(false)
const selectedUser = ref<any>(null)
const jwtToken = localStorage.getItem('token') || ''

const fetchUsers = async () => {
  try {
    const { data } = await axios.get('/api/admin/users', {
      headers: { Authorization: `Bearer ${jwtToken}` }
    })
    users.value = data
  } catch (err) {
    console.error('Error cargando usuarios:', err)
  }
}

const openCreateModal = () => {
  selectedUser.value = null
  isEditing.value = false
  showModal.value = true
}

const openEditModal = (user: any) => {
  selectedUser.value = user
  isEditing.value = true
  showModal.value = true
}

onMounted(fetchUsers)
</script>

<style scoped>
</style>
