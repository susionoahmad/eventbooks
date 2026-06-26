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
    if (!user.value || !user.value.tenant) return false
    const tenant = user.value.tenant
    if (tenant.subscription_plan !== 'trial') return false
    if (!tenant.trial_ends_at) return false
    
    // Parse the DB datetime (which is usually UTC or local from API)
    // Replace space with T to make it standard ISO if it is in YYYY-MM-DD HH:MM:SS format
    const trialDateStr = tenant.trial_ends_at.includes(' ') && !tenant.trial_ends_at.includes('T')
      ? tenant.trial_ends_at.replace(' ', 'T')
      : tenant.trial_ends_at
    const endsAt = new Date(trialDateStr)
    return endsAt < new Date()
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
      const response = await axios.get('http://127.0.0.1:8000/api/v1/auth/me', {
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
