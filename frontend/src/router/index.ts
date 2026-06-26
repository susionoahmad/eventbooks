import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/RegisterView.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/setup',
    name: 'Setup',
    component: () => import('../views/SetupWizardView.vue'),
    meta: { requiresAuth: true, setupOnly: true }
  },
  {
    path: '/',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard', name: 'Dashboard', component: () => import('../views/DashboardView.vue') },
      { path: 'clients', name: 'Clients', component: () => import('../views/ClientsView.vue') },
      { path: 'vendors', name: 'Vendors', component: () => import('../views/VendorsView.vue') },
      { path: 'events', name: 'Events', component: () => import('../views/EventsView.vue') },
      { path: 'events/:id', name: 'EventDetail', component: () => import('../views/EventDetailView.vue'), props: true },
      {
        path: 'transactions',
        name: 'Transactions',
        component: () => import('../views/TransactionsView.vue'),
        meta: { roles: ['owner', 'finance_manager', 'admin'] }
      },
      {
        path: 'invoices',
        name: 'Invoices',
        component: () => import('../views/InvoicesView.vue'),
        meta: { roles: ['owner', 'finance_manager', 'admin'] }
      },
      {
        path: 'taxes',
        name: 'Taxes',
        component: () => import('../views/TaxesView.vue'),
        meta: { roles: ['owner', 'finance_manager'] }
      },
      {
        path: 'reports',
        name: 'Reports',
        component: () => import('../views/ReportsView.vue'),
        meta: { roles: ['owner', 'finance_manager'] }
      },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('../views/SettingsView.vue'),
        meta: { roles: ['owner'] }
      }
    ]
  },
  { path: '/:pathMatch(.*)*', redirect: '/dashboard' }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Route Guards
router.beforeEach((to, _from, next) => {
  const authStore = useAuthStore()

  // Not authenticated → login
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login')
  }

  // Guest-only pages (login/register) while authenticated
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    // If setup is pending, send to setup wizard
    if (!authStore.setupComplete) return next('/setup')
    return next('/dashboard')
  }

  // Authenticated but setup not complete → force /setup
  // (except when already going to /setup)
  if (
    authStore.isAuthenticated &&
    !authStore.setupComplete &&
    !to.meta.setupOnly
  ) {
    return next('/setup')
  }

  // /setup page should only be accessible when setup is NOT complete
  if (to.meta.setupOnly && authStore.isAuthenticated && authStore.setupComplete) {
    return next('/dashboard')
  }

  // Role-based access
  if (to.meta.roles) {
    const requiredRoles = to.meta.roles as string[]
    if (!requiredRoles.includes(authStore.userRole)) {
      return next('/dashboard')
    }
  }

  next()
})

export default router
