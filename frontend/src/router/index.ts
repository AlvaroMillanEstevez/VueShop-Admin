import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Layout
import AppLayout from '@/components/AppLayout.vue'

// Vistas existentes
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
      component: AppLayout,
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
    },
    // Catch all route - debe ir al final
    {
      path: '/:pathMatch(.*)*',
      redirect: '/dashboard'
    }
  ]
})

// Enhanced navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  console.log(`Navigating to: ${to.path}`)
  console.log('Current auth state:', {
    isAuthenticated: authStore.isAuthenticated,
    hasToken: !!authStore.token,
    hasUser: !!authStore.user,
    sessionExpired: authStore.sessionExpired
  })
  
  try {
    // Si hay un token pero no estamos autenticados, intentar inicializar
    if (authStore.token && !authStore.isAuthenticated && !authStore.sessionExpired) {
      console.log('Initializing auth...')
      const initialized = await authStore.initialize()
      console.log('Auth initialized:', initialized)
    }
    
    // Check if route requires authentication
    if (to.meta.requiresAuth) {
      if (!authStore.isAuthenticated) {
        console.log('Route requires auth but user not authenticated, redirecting to login')
        
        // Si la sesión expiró, agregar parámetro de mensaje
        const query: any = {}
        if (authStore.sessionExpired) {
          query.message = 'session_expired'
        }
        
        // Guardar la ruta de destino para redireccionar después del login
        if (to.path !== '/dashboard') {
          query.redirect = to.fullPath
        }
        
        next({
          name: 'login',
          query
        })
        return
      }
      
      // Verificar que el usuario sigue siendo válido
      if (!authStore.user) {
        console.log('User data is missing, redirecting to login')
        next({
          name: 'login',
          query: { message: 'session_expired' }
        })
        return
      }
    }
    
    // Check if route requires admin role
    if (to.meta.requiresAdmin) {
      if (!authStore.isAuthenticated) {
        console.log('Route requires admin but user not authenticated, redirecting to login')
        next({
          name: 'login',
          query: { message: 'session_expired' }
        })
        return
      }
      
      if (!authStore.isAdmin) {
        console.log('Route requires admin but user is not admin, redirecting to dashboard')
        next('/dashboard')
        return
      }
    }
    
    // Check if route requires guest (not authenticated)
    if (to.meta.requiresGuest) {
      if (authStore.isAuthenticated && authStore.user) {
        console.log('Route requires guest but user is authenticated, redirecting to dashboard')
        next('/dashboard')
        return
      }
    }
    
    // All checks passed
    console.log('Navigation allowed')
    next()
    
  } catch (error) {
    console.error('Router navigation error:', error)
    
    // Si hay error durante la navegación, limpiar auth y redirigir a login
    if (to.meta.requiresAuth) {
      console.log('Auth error during navigation, clearing state and redirecting to login')
      
      // Usar handleTokenExpiration para limpiar correctamente
      await authStore.handleTokenExpiration()
      
      next({
        name: 'login',
        query: { 
          message: 'session_expired',
          redirect: to.fullPath !== '/dashboard' ? to.fullPath : undefined
        }
      })
    } else {
      next()
    }
  }
})

// Optional: Log successful navigations
router.afterEach((to, from) => {
  console.log(`Successfully navigated from ${from.path} to ${to.path}`)
})

export default router