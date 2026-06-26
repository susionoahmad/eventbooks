<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const route     = useRoute()
const router    = useRouter()
const authStore = useAuthStore()

// Primary nav items for bottom bar (max 5 for mobile ergonomics)
// Role-filtered just like the sidebar
const primaryItems = [
  {
    name: 'Dashboard',
    path: '/dashboard',
    roles: ['owner', 'finance_manager', 'admin', 'staff'],
    icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />`
  },
  {
    name: 'Events',
    path: '/events',
    roles: ['owner', 'finance_manager', 'admin', 'staff'],
    icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />`
  },
  {
    name: 'Buku Kas',
    path: '/transactions',
    roles: ['owner', 'finance_manager', 'admin'],
    icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />`
  },
  {
    name: 'Invoices',
    path: '/invoices',
    roles: ['owner', 'finance_manager', 'admin'],
    icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />`
  },
  {
    name: 'Lainnya',
    path: '__more__',
    roles: ['owner', 'finance_manager', 'admin', 'staff'],
    icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />`
  },
]

// "More" drawer items (lower-priority pages)
const moreItems = [
  { name: 'Klien',           path: '/clients',      roles: ['owner', 'finance_manager', 'admin', 'staff'] },
  { name: 'Vendor',          path: '/vendors',      roles: ['owner', 'finance_manager', 'admin', 'staff'] },
  { name: 'Rekap Pajak',     path: '/taxes',        roles: ['owner', 'finance_manager'] },
  { name: 'Laporan',         path: '/reports',      roles: ['owner', 'finance_manager'] },
  { name: 'Pengaturan',      path: '/settings',     roles: ['owner'] },
]

import { ref, computed } from 'vue'
const showMoreDrawer = ref(false)

const visiblePrimary = computed(() =>
  primaryItems.filter(i => i.roles.includes(authStore.userRole))
)
const visibleMore = computed(() =>
  moreItems.filter(i => i.roles.includes(authStore.userRole))
)

const isActive = (path: string) => route.path === path || route.path.startsWith(path + '/')

const navigate = (path: string) => {
  if (path === '__more__') {
    showMoreDrawer.value = !showMoreDrawer.value
    return
  }
  showMoreDrawer.value = false
  router.push(path)
}

// Check if any "more" item is currently active (to highlight the more button)
const moreIsActive = computed(() =>
  visibleMore.value.some(i => isActive(i.path))
)
</script>

<template>
  <!-- Only visible on mobile (md breakpoint and below) -->
  <div class="md:hidden">

    <!-- More Drawer Overlay -->
    <Transition name="fade">
      <div
        v-if="showMoreDrawer"
        class="fixed inset-0 z-40 bg-slate-900/70 backdrop-blur-sm"
        @click="showMoreDrawer = false"
      ></div>
    </Transition>

    <!-- More Drawer Panel (slides up from bottom) -->
    <Transition name="slide-up">
      <div
        v-if="showMoreDrawer"
        class="fixed bottom-16 left-0 right-0 z-50 bg-slate-900 border-t border-slate-800 rounded-t-3xl px-4 pt-4 pb-5 shadow-2xl"
      >
        <!-- Handle bar -->
        <div class="flex justify-center mb-4">
          <div class="w-10 h-1 rounded-full bg-slate-700"></div>
        </div>

        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider px-1 mb-3">Menu Lainnya</p>

        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="item in visibleMore"
            :key="item.path"
            @click="navigate(item.path)"
            :class="[
              'flex flex-col items-center gap-1.5 py-3 px-2 rounded-2xl transition-all text-center',
              isActive(item.path)
                ? 'bg-emerald-950/60 border border-emerald-800/50 text-emerald-400'
                : 'bg-slate-800/60 text-slate-400 hover:text-slate-200 active:bg-slate-700'
            ]"
          >
            <span class="text-xl">
              <!-- Icons mapped by name -->
              <span v-if="item.name === 'Klien'">👥</span>
              <span v-else-if="item.name === 'Vendor'">🤝</span>
              <span v-else-if="item.name === 'Rekap Pajak'">⚖️</span>
              <span v-else-if="item.name === 'Laporan'">📊</span>
              <span v-else-if="item.name === 'Pengaturan'">⚙️</span>
            </span>
            <span class="text-xs font-semibold leading-tight">{{ item.name }}</span>
          </button>
        </div>

        <!-- Logout -->
        <div class="mt-4 pt-4 border-t border-slate-800">
          <button
            @click="() => { authStore.logout(); showMoreDrawer = false; router.push('/login') }"
            class="w-full flex items-center justify-center gap-2 py-3 rounded-2xl bg-rose-950/40 border border-rose-900/40 text-rose-400 text-sm font-semibold"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
            </svg>
            Keluar
          </button>
        </div>

      </div>
    </Transition>

    <!-- Bottom Navigation Bar -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 flex items-stretch safe-area-pb">
      <button
        v-for="item in visiblePrimary"
        :key="item.path"
        @click="navigate(item.path)"
        class="flex-1 flex flex-col items-center justify-center py-2 px-1 relative transition-all duration-200 active:scale-95"
        :class="[
          item.path === '__more__'
            ? (showMoreDrawer || moreIsActive)
              ? 'text-emerald-400'
              : 'text-slate-500'
            : isActive(item.path)
              ? 'text-emerald-400'
              : 'text-slate-500'
        ]"
      >
        <!-- Active indicator pill -->
        <span
          v-if="(item.path !== '__more__' && isActive(item.path)) || (item.path === '__more__' && (showMoreDrawer || moreIsActive))"
          class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 rounded-full bg-emerald-500"
        ></span>

        <!-- Icon -->
        <svg
          class="w-6 h-6 transition-transform duration-200"
          :class="{ 'scale-110': item.path !== '__more__' && isActive(item.path) }"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          v-html="item.icon"
        ></svg>

        <!-- Label -->
        <span class="text-[10px] font-semibold mt-0.5 leading-none">{{ item.name }}</span>
      </button>
    </nav>
  </div>
</template>

<style scoped>
/* Slide up transition for the More drawer */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.28s cubic-bezier(0.32, 0.72, 0, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}

/* Fade for overlay */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* iOS safe area support */
.safe-area-pb {
  padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>
