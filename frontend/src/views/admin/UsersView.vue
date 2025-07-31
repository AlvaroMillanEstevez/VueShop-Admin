<template>
  <div class="p-6">
    <div class="mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
        <p class="text-gray-600">Manage system users and their permissions</p>
      </div>
      <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <span>Add User</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Users Table -->
    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stats</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="user in users" :key="user.id">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                  <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <span class="text-sm font-medium text-blue-600">{{ getInitials(user.name) }}</span>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                  <div class="text-sm text-gray-500">{{ user.email }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getRoleClass(user.role)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                {{ getRoleLabel(user.role) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <div class="space-y-1">
                <div>Orders: {{ user.orders_count || 0 }}</div>
                <div>Products: {{ user.products_count || 0 }}</div>
                <div>Customers: {{ user.customers_count || 0 }}</div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(user.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
              <button 
                @click="toggleUserStatus(user)"
                :class="user.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
              >
                {{ user.is_active ? 'Deactivate' : 'Activate' }}
              </button>
              <button class="text-blue-600 hover:text-blue-900">
                Edit
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// State
const loading = ref(true)
const users = ref<any[]>([])

// Mock data for now - later connect to API
const loadUsers = async () => {
  try {
    loading.value = true
    
    // Simulate API call
    setTimeout(() => {
      users.value = [
        {
          id: 1,
          name: 'Super Admin',
          email: 'admin@vueshop.com',
          role: 'admin',
          is_active: true,
          orders_count: 0,
          products_count: 0,
          customers_count: 0,
          created_at: '2025-07-31T08:59:44.000000Z'
        },
        {
          id: 2,
          name: 'Juan García Pérez',
          email: 'juan@vueshop.com',
          role: 'manager',
          is_active: true,
          orders_count: 22,
          products_count: 9,
          customers_count: 10,
          created_at: '2025-07-31T08:59:44.000000Z'
        },
        {
          id: 3,
          name: 'María López Silva',
          email: 'maria@vueshop.com',
          role: 'manager',
          is_active: true,
          orders_count: 18,
          products_count: 7,
          customers_count: 8,
          created_at: '2025-07-31T08:59:44.000000Z'
        },
        {
          id: 4,
          name: 'Carlos Rodríguez Martín',
          email: 'carlos@vueshop.com',
          role: 'manager',
          is_active: true,
          orders_count: 15,
          products_count: 6,
          customers_count: 9,
          created_at: '2025-07-31T08:59:44.000000Z'
        }
      ]
      loading.value = false
    }, 1000)
    
  } finally {
    loading.value = false
  }
}

// Utility functions
const getInitials = (name: string): string => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getRoleLabel = (role: string): string => {
  const labels = {
    admin: 'Administrator',
    manager: 'Manager'
  }
  return labels[role as keyof typeof labels] || role
}

const getRoleClass = (role: string): string => {
  const classes = {
    admin: 'bg-red-100 text-red-800',
    manager: 'bg-blue-100 text-blue-800'
  }
  return classes[role as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const toggleUserStatus = (user: any) => {
  // TODO: Implement API call to toggle user status
  user.is_active = !user.is_active
  console.log(`Toggling user ${user.name} status to: ${user.is_active}`)
}

onMounted(() => {
  loadUsers()
})
</script>