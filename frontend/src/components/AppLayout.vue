<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Mobile Overlay -->
    <div v-if="mobileSidebarOpen" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
      @click="closeMobileSidebar"></div>

    <!-- Sidebar -->
    <div class="sidebar" :class="sidebarClasses">
      <div class="flex items-center justify-between p-5 border-b border-gray-200">
        <div class="flex items-center space-x-3">
          <svg class="w-8 h-8 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
          </svg>
          <span v-if="!sidebarCollapsed || isMobile" class="text-xl font-bold text-gray-900 whitespace-nowrap">VueShop
            Admin</span>
        </div>

        <!-- Close button on mobile -->
        <button @click="closeMobileSidebar"
          class="lg:hidden p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Collapse button on desktop -->
        <button @click="toggleSidebar"
          class="hidden lg:flex p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors z-50"
          :class="sidebarCollapsed ? 'ml-auto' : ''">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              :d="sidebarCollapsed ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7'" />
          </svg>
        </button>
      </div>

      <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
        <router-link v-for="item in filteredMenuItems" :key="item.path" :to="item.path" class="nav-item"
          :class="navItemClasses(item.path)" @click="closeMobileSidebar"
          :title="sidebarCollapsed && !isMobile ? item.name : ''">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
          </svg>
          <span v-if="!sidebarCollapsed || isMobile" class="font-medium ml-3 whitespace-nowrap">{{ item.name }}</span>
        </router-link>
      </nav>

      <!-- User Info Section -->
      <div class="p-4 border-t border-gray-200">
        <div class="flex items-center" :class="sidebarCollapsed && !isMobile ? 'justify-center' : 'space-x-3'">
          <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
            </svg>
          </div>
          <div v-if="!sidebarCollapsed || isMobile" class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-gray-900">{{ authStore.user?.name || 'User' }}</div>
            <div class="text-xs text-gray-500 capitalize flex items-center justify-between">
              <span :class="getRoleClass(authStore.user?.role)">
                {{ getRoleLabel(authStore.user?.role) }}
              </span>
              <button @click="handleLogout" class="ml-2 text-xs text-red-500 hover:text-red-700 transition-colors"
                title="Log out">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Logout button when collapsed -->
        <div v-if="sidebarCollapsed && !isMobile" class="mt-2 flex justify-center">
          <button @click="handleLogout"
            class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" title="Log out">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Bar -->
      <div class="bg-white border-b border-gray-200 px-4 py-4 sm:px-6 lg:px-8 relative z-30">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <button @click="toggleMobileSidebar"
              class="lg:hidden p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            <div class="text-xl font-semibold text-gray-900">{{ getCurrentPageTitle() }}</div>
          </div>

          <div class="flex items-center space-x-4">
            <div class="hidden sm:block relative">
              <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input type="text" placeholder="Search..." class="search-input" />
            </div>

            <!-- Theme Toggle -->
            <ThemeToggle />

            <!-- User info in header when collapsed -->
            <div v-if="sidebarCollapsed && !isMobile"
              class="hidden lg:flex items-center space-x-2 text-sm text-gray-600">
              <span>{{ authStore.user?.name }}</span>
              <span :class="getRoleClass(authStore.user?.role)" class="px-2 py-1 rounded-full text-xs">
                {{ getRoleLabel(authStore.user?.role) }}
              </span>
            </div>

            <div class="relative">
              <button class="p-2 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span
                  class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Page Content -->
      <div class="flex-1 overflow-y-auto">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ThemeToggle from '@/components/ThemeToggle.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const sidebarCollapsed = ref(false)
const mobileSidebarOpen = ref(false)
const isMobile = ref(false)

const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', () => {
    checkMobile()
    handleResize()
  })
})

const allMenuItems = [
  {
    path: '/',
    name: 'Dashboard',
    icon: 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z',
    roles: ['admin', 'manager']
  },
  {
    path: '/products',
    name: 'Products',
    icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    roles: ['admin', 'manager']
  },
  {
    path: '/orders',
    name: 'Orders',
    icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
    roles: ['admin', 'manager']
  },
  {
    path: '/customers',
    name: 'Customers',
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
    roles: ['admin', 'manager']
  },
  {
    path: '/admin/users',
    name: 'User Management',
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
    roles: ['admin']
  }
]

const filteredMenuItems = computed(() => {
  const userRole = authStore.user?.role || 'manager'
  return allMenuItems.filter(item => item.roles.includes(userRole))
})

const sidebarClasses = computed(() => {
  const base = 'bg-white border-r border-gray-200 flex flex-col transition-all duration-300 ease-in-out'

  if (isMobile.value) {
    return `${base} fixed inset-y-0 left-0 z-50 w-80 transform ${mobileSidebarOpen.value ? 'translate-x-0' : '-translate-x-full'} shadow-xl`
  }

  return `${base} relative ${sidebarCollapsed.value ? 'w-20' : 'w-64'}`
})

const navItemClasses = (path: string) => {
  const isActive = route.path === path
  const base = 'flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 nav-item'

  if (isActive) {
    return `${base} bg-blue-100 text-blue-700`
  }

  return `${base} text-gray-600 hover:bg-gray-100 hover:text-gray-900`
}

const toggleSidebar = () => {
  if (!isMobile.value) {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }
}

const toggleMobileSidebar = () => {
  mobileSidebarOpen.value = !mobileSidebarOpen.value
}

const closeMobileSidebar = () => {
  mobileSidebarOpen.value = false
}

const handleResize = () => {
  if (window.innerWidth >= 1024) {
    mobileSidebarOpen.value = false
  }
}

const getCurrentPageTitle = () => {
  const currentItem = filteredMenuItems.value.find(item => item.path === route.path)
  return currentItem?.name || 'Dashboard'
}

const handleLogout = async () => {
  try {
    await authStore.logout()
    router.push('/login')
  } catch (error) {
    console.error('Error during logout:', error)
  }
}

const getRoleLabel = (role: string | undefined) => {
  const labels = {
    admin: 'Admin',
    manager: 'Manager'
  }
  return labels[role as keyof typeof labels] || 'User'
}

const getRoleClass = (role: string | undefined) => {
  const classes = {
    admin: 'text-red-600 bg-red-100',
    manager: 'text-blue-600 bg-blue-100'
  }
  return classes[role as keyof typeof classes] || 'text-gray-600 bg-gray-100'
}
</script>

<style scoped>
.sidebar {
  min-height: 100vh;
  z-index: 40;
}

.sidebar button {
  position: relative;
  z-index: 50;
}

.nav-item {
  min-width: fit-content;
  white-space: nowrap;
}

.sidebar.w-20 .nav-item {
  justify-content: center;
}

.sidebar.w-20 .nav-item:hover::after {
  content: attr(title);
  position: absolute;
  left: 100%;
  margin-left: 0.5rem;
  padding: 0.25rem 0.75rem;
  background-color: rgba(0, 0, 0, 0.9);
  color: white;
  font-size: 0.875rem;
  border-radius: 0.375rem;
  white-space: nowrap;
  z-index: 60;
}

.sidebar * {
  transition: all 0.3s ease;
}
</style>