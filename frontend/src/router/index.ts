import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Layout
import AppLayout from '@/components/AppLayout.vue'

// Tus vistas existentes
import Dashboard from '@/views/Dashboard.vue'
import Products from '@/views/Products.vue'
import Orders from '@/views/Orders.vue'
import Customers from '@/views/Customers.vue'

// Vista admin
import UsersView from '@/views/admin/UsersView.vue'

// Vistas de auth
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { requiresGuest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { requiresGuest: true }
    },
    {
      path: '/',
      component: AppLayout, // Usar tu AppLayout como wrapper
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          redirect: '/dashboard'
        },
        {
          path: 'dashboard',
          name: 'dashboard',
          component: Dashboard
        },
        {
          path: 'products',
          name: 'products',
          component: Products
        },
        {
          path: 'orders',
          name: 'orders',
          component: Orders
        },
        {
          path: 'customers',
          name: 'customers',
          component: Customers
        },
        {
          path: 'admin/users',
          name: 'users',
          component: UsersView,
          meta: { requiresAdmin: true }
        }
      ]
    }
  ]
})

// Navigation guards
router.beforeEach(async (to) => {
  const authStore = useAuthStore()
  
  // Initialize auth if not done yet
  if (authStore.token && !authStore.user) {
    await authStore.initialize()
  }
  
  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return '/login'
  }
  
  // Check if route requires guest (not authenticated)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return '/dashboard'
  }
})

export default router