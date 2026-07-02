<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const isCollapsed = ref(false)

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
}

// Navigation links configuration with role check
const navigationItems = [
  { name: 'Dashboard', path: '/dashboard', icon: 'ChartBarIcon', roles: ['owner', 'finance_manager', 'admin', 'staff'] },
  { name: 'Klien', path: '/clients', icon: 'UserGroupIcon', roles: ['owner', 'finance_manager', 'admin', 'staff'] },
  { name: 'Vendor', path: '/vendors', icon: 'BriefcaseIcon', roles: ['owner', 'finance_manager', 'admin', 'staff'] },
  { name: 'Events', path: '/events', icon: 'CalendarIcon', roles: ['owner', 'finance_manager', 'admin', 'staff'] },
  { name: 'Buku Kas (Ledger)', path: '/transactions', icon: 'BookOpenIcon', roles: ['owner', 'finance_manager', 'admin'] },
  { name: 'Invoices', path: '/invoices', icon: 'DocumentTextIcon', roles: ['owner', 'finance_manager', 'admin'] },
  { name: 'Rekap Pajak', path: '/taxes', icon: 'ScaleIcon', roles: ['owner', 'finance_manager'] },
  { name: 'Laporan Keuangan', path: '/reports', icon: 'PresentationChartBarIcon', roles: ['owner', 'finance_manager'] },
  { name: 'Pengaturan', path: '/settings', icon: 'CogIcon', roles: ['owner', 'admin'] },
]

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <aside 
    :class="[
      isCollapsed ? 'w-20' : 'w-64', 
      'h-screen sticky top-0 bg-slate-900 text-slate-100 flex flex-col justify-between transition-all duration-300 border-r border-slate-800'
    ]"
  >
    <!-- Brand / Header -->
    <div>
      <div class="h-16 flex items-center justify-between px-4 border-b border-slate-800">
        <div v-if="!isCollapsed" class="flex items-center space-x-2 font-bold text-lg text-emerald-400">
          <span>EventBooks</span>
        </div>
        <div v-else class="mx-auto text-emerald-400 font-extrabold text-xl">EB</div>
        
        <button @click="toggleSidebar" class="p-1 rounded hover:bg-slate-800 hidden md:block">
          <svg v-if="!isCollapsed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
          </svg>
        </button>
      </div>

      <!-- Navigation links -->
      <nav class="mt-6 px-3 space-y-1">
        <template v-for="item in navigationItems" :key="item.name">
          <router-link 
            v-if="item.roles.includes(authStore.userRole)"
            :to="item.path"
            :class="[
              route.path === item.path ? 'bg-slate-800 text-emerald-400' : 'hover:bg-slate-800 text-slate-300 hover:text-slate-100',
              'flex items-center px-3 py-2.5 rounded-lg transition-colors group cursor-pointer'
            ]"
          >
            <!-- SVG Icon Placeholder matching navigation name -->
            <span class="w-6 h-6 mr-3 text-slate-400 group-hover:text-emerald-400 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
              </svg>
            </span>
            <span v-if="!isCollapsed" class="text-sm font-medium">{{ item.name }}</span>
          </router-link>
        </template>
      </nav>
    </div>

    <!-- User Profile Area / Logout -->
    <div class="p-4 border-t border-slate-800">
      <div v-if="!isCollapsed" class="mb-4">
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">{{ authStore.organizationName }}</p>
        <p class="text-sm font-bold truncate text-slate-200">{{ authStore.user?.name }}</p>
        <span class="inline-block mt-1 px-2 py-0.5 text-2xs font-bold uppercase tracking-wider bg-emerald-950 text-emerald-400 border border-emerald-800 rounded">
          {{ authStore.userRole }}
        </span>
      </div>
      
      <button 
        @click="handleLogout" 
        :class="[
          isCollapsed ? 'justify-center' : 'space-x-3', 
          'w-full flex items-center px-3 py-2 text-sm text-rose-400 hover:bg-slate-800 rounded-lg transition-colors cursor-pointer'
        ]"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
        </svg>
        <span v-if="!isCollapsed">Keluar</span>
      </button>
    </div>
  </aside>
</template>
