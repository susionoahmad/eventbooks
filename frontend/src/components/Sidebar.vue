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
  {
    name: 'Dashboard',
    path: '/dashboard',
    roles: ['owner', 'finance_manager', 'admin', 'staff'],
    icon: `<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>`
  },
  {
    name: 'Klien',
    path: '/clients',
    roles: ['owner', 'finance_manager', 'admin', 'staff'],
    icon: `<span class="text-xl">👥</span>`
  },
  {
    name: 'Vendor',
    path: '/vendors',
    roles: ['owner', 'finance_manager', 'admin', 'staff'],
    icon: `<span class="text-xl">🤝</span>`
  },
  {
    name: 'Events',
    path: '/events',
    roles: ['owner', 'finance_manager', 'admin', 'staff'],
    icon: `<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>`
  },
  {
    name: 'Buku Kas (Ledger)',
    path: '/transactions',
    roles: ['owner', 'finance_manager', 'admin'],
    icon: `<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>`
  },
  {
    name: 'Invoices',
    path: '/invoices',
    roles: ['owner', 'finance_manager', 'admin'],
    icon: `<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>`
  },
  {
    name: 'Rekap Pajak',
    path: '/taxes',
    roles: ['owner', 'finance_manager'],
    icon: `<span class="text-xl">⚖️</span>`
  },
  {
    name: 'Laporan Keuangan',
    path: '/reports',
    roles: ['owner', 'finance_manager'],
    icon: `<span class="text-xl">📊</span>`
  },
  {
    name: 'Pengaturan',
    path: '/settings',
    roles: ['owner', 'admin'],
    icon: `<span class="text-xl">⚙️</span>`
  },
  {
    name: 'Ganti Password',
    path: '/change-password',
    roles: ['owner', 'finance_manager', 'admin', 'staff'],
    icon: `<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>`
  }
]
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
            <!-- Dynamic Icon matching bottom nav configuration -->
            <span 
              class="w-6 h-6 mr-3 text-slate-400 group-hover:text-emerald-400 transition-colors flex items-center justify-center"
              v-html="item.icon"
            ></span>
            <span v-if="!isCollapsed" class="text-sm font-medium">{{ item.name }}</span>
          </router-link>
        </template>
      </nav>
    </div>

    <!-- User Profile Area -->
    <div class="p-4 border-t border-slate-800">
      <div v-if="!isCollapsed">
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">{{ authStore.organizationName }}</p>
        <p class="text-sm font-bold truncate text-slate-200">{{ authStore.user?.name }}</p>
        <span class="inline-block mt-1 px-2 py-0.5 text-2xs font-bold uppercase tracking-wider bg-emerald-950 text-emerald-400 border border-emerald-800 rounded">
          {{ authStore.userRole }}
        </span>
      </div>
      <div v-else class="text-center font-bold text-xs text-emerald-400">
        {{ authStore.user?.name.charAt(0) }}
      </div>
    </div>
  </aside>
</template>
