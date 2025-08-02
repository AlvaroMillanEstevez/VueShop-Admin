// services/authCheck.ts
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

class AuthCheckService {
  private checkInterval: number | null = null
  private readonly CHECK_INTERVAL_MS = 60000 // 1 minuto
  private isChecking = false

  start() {
    console.log('Starting auth check service...')
    
    // Verificar inmediatamente al inicio
    this.checkAuthStatus()
    
    // Configurar verificación periódica
    this.checkInterval = window.setInterval(() => {
      this.checkAuthStatus()
    }, this.CHECK_INTERVAL_MS)
  }

  stop() {
    console.log('Stopping auth check service...')
    
    if (this.checkInterval) {
      clearInterval(this.checkInterval)
      this.checkInterval = null
    }
  }

  private async checkAuthStatus() {
    // Evitar múltiples verificaciones simultáneas
    if (this.isChecking) return
    
    const authStore = useAuthStore()
    
    // Solo verificar si estamos supuestamente autenticados
    if (!authStore.isAuthenticated || !authStore.token) {
      return
    }

    this.isChecking = true

    try {
      console.log('Checking auth status...')
      
      // Intentar obtener el perfil para verificar que el token sigue válido
      const response = await fetch('http://localhost:8000/api/auth/profile', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        // Timeout más corto para verificaciones periódicas
        signal: AbortSignal.timeout(10000)
      })

      if (response.status === 401) {
        console.log('Token expired detected by auth check service')
        await this.handleTokenExpiration()
      } else if (response.ok) {
        console.log('Auth status check passed')
      } else {
        console.warn('Auth check returned unexpected status:', response.status)
      }

    } catch (error) {
      // Solo manejar como expiración si es un error 401 explícito
      if (error instanceof Error && error.message.includes('401')) {
        console.log('Token expired detected via error in auth check')
        await this.handleTokenExpiration()
      } else {
        console.warn('Auth check failed with network error:', error)
        // No hacer nada en caso de errores de red temporales
      }
    } finally {
      this.isChecking = false
    }
  }

  private async handleTokenExpiration() {
    console.log('Handling token expiration from auth check service')
    
    try {
      const authStore = useAuthStore()
      await authStore.handleTokenExpiration()
      
      // Solo redirigir si estamos en una ruta protegida
      const currentRoute = router.currentRoute.value
      if (currentRoute.meta.requiresAuth) {
        console.log('Redirecting to login due to expired token')
        
        router.push({
          name: 'login',
          query: { 
            message: 'session_expired',
            redirect: currentRoute.fullPath !== '/dashboard' ? currentRoute.fullPath : undefined
          }
        })
      }
    } catch (error) {
      console.error('Error handling token expiration:', error)
    }
  }
}

// Crear instancia singleton
export const authCheckService = new AuthCheckService()

// Auto-inicializar cuando se importa el módulo
// (solo en producción o cuando específicamente se habilite)
if (import.meta.env.PROD || import.meta.env.VITE_AUTH_CHECK === 'true') {
  // Iniciar después de un pequeño delay para asegurar que la app esté cargada
  setTimeout(() => {
    authCheckService.start()
  }, 5000)

  // Limpiar al descargar la página
  window.addEventListener('beforeunload', () => {
    authCheckService.stop()
  })
}