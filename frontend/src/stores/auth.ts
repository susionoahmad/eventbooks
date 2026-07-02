import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

interface TenantInfo {
  id: number
  name: string
  slug?: string
  is_setup_complete: boolean
  subscription_plan?: string
  trial_ends_at?: string
  alamat?: string
  email?: string
  telepon?: string
  npwp?: string
  default_ppn_rate?: number
}

interface User {
  id: number
  name: string
  email: string
  role: 'owner' | 'finance_manager' | 'admin' | 'staff'
  tenant: TenantInfo
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(
    JSON.parse(localStorage.getItem('user') || 'null')
  )
  const token = ref<string | null>(
    localStorage.getItem('token') || null
  )

  const isAuthenticated = computed(() => !!token.value)
  const userRole        = computed(() => user.value?.role || 'staff')
  const organizationName = computed(() => user.value?.tenant?.name || 'My Organization')
  const setupComplete   = computed(() => user.value?.tenant?.is_setup_complete === true)

  const isTrialExpired = computed(() => {
    return false // Trial check disabled for single tenant custom deployment
  })

  const login = (newToken: string, newUser: User) => {
    token.value = newToken
    user.value  = newUser
    localStorage.setItem('token', newToken)
    localStorage.setItem('user', JSON.stringify(newUser))
    axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
  }

  const logout = () => {
    token.value = null
    user.value  = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    delete axios.defaults.headers.common['Authorization']
  }

  const fetchCurrentUser = async () => {
    if (!token.value) return
    try {
      const baseUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1'
      const response = await axios.get(`${baseUrl}/auth/me`, {
        headers: {
          Authorization: `Bearer ${token.value}`
        }
      })
      const userData = response.data.data
      user.value = userData
      localStorage.setItem('user', JSON.stringify(userData))
    } catch (error) {
      console.error('Failed to fetch current user:', error)
      throw error
    }
  }

  /**
   * Called after completing setup wizard step 3.
   * Updates the local tenant state so the guard redirects to /dashboard.
   */
  const markSetupComplete = () => {
    if (user.value) {
      user.value = {
        ...user.value,
        tenant: { ...user.value.tenant, is_setup_complete: true }
      }
      localStorage.setItem('user', JSON.stringify(user.value))
    }
  }

  // Setup initial axios header if token exists
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  return {
    user,
    token,
    isAuthenticated,
    userRole,
    organizationName,
    setupComplete,
    isTrialExpired,
    login,
    logout,
    fetchCurrentUser,
    markSetupComplete,
  }
})
