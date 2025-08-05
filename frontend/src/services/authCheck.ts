// services/authCheck.ts
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

class AuthCheckService {
  private checkInterval: number | null = null
  private readonly CHECK_INTERVAL_MS = 60000 // 1 minute
  private isChecking = false

  start() {
    console.log('Starting auth check service...')

    // Immediately check auth status on start
    this.checkAuthStatus()

    // Set up periodic check
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
    // Prevent multiple concurrent checks
    if (this.isChecking) return

    const authStore = useAuthStore()

    // Only check if supposedly authenticated
    if (!authStore.isAuthenticated || !authStore.token) {
      return
    }

    this.isChecking = true

    try {
      console.log('Checking auth status...')

      // Try to fetch profile to validate token
      const response = await fetch('http://localhost:8000/api/auth/profile', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        // Shorter timeout for periodic checks
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
      // Only treat as expiration if explicitly a 401 error
      if (error instanceof Error && error.message.includes('401')) {
        console.log('Token expired detected via error in auth check')
        await this.handleTokenExpiration()
      } else {
        console.warn('Auth check failed with network error:', error)
        // Ignore temporary network issues
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

      // Only redirect if on a protected route
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

// Create singleton instance
export const authCheckService = new AuthCheckService()

// Auto-initialize on module import
// (only in production or when specifically enabled)
if (import.meta.env.PROD || import.meta.env.VITE_AUTH_CHECK === 'true') {
  // Start after a slight delay to ensure the app is loaded
  setTimeout(() => {
    authCheckService.start()
  }, 5000)

  // Clean up on page unload
  window.addEventListener('beforeunload', () => {
    authCheckService.stop()
  })
}
