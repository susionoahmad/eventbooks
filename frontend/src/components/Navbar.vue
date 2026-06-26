<script setup lang="ts">
import { useAuthStore } from '../stores/auth'
import { useThemeStore } from '../stores/theme'

const authStore = useAuthStore()
const themeStore = useThemeStore()

defineEmits(['toggleMobileSidebar'])
</script>

<template>
  <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 sticky top-0 z-30 transition-colors">
    <!-- Left Section: Mobile toggle & Search -->
    <div class="flex items-center space-x-4">
      <button 
        @click="$emit('toggleMobileSidebar')" 
        class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 md:hidden cursor-pointer"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
      </button>

      <div class="hidden sm:flex items-center bg-slate-100 dark:bg-slate-800 rounded-lg px-3 py-1.5 w-64 md:w-80 transition-colors border border-transparent focus-within:border-emerald-500 dark:focus-within:border-emerald-500">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-slate-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
        </svg>
        <input 
          type="text" 
          placeholder="Cari event, klien, atau invoice..." 
          class="ml-2 bg-transparent text-sm w-full outline-none text-slate-700 dark:text-slate-200"
        />
      </div>
    </div>

    <!-- Right Section: Actions -->
    <div class="flex items-center space-x-3">
      <!-- Dark Mode Toggle -->
      <button 
        @click="themeStore.toggleDarkMode" 
        class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
        aria-label="Toggle Dark Mode"
      >
        <!-- Sun Icon for Dark Mode -->
        <svg v-if="themeStore.isDarkMode" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-amber-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21m8.97-8.97h-2.25C10.53 12 12 10.53 12 9.03V3m0 18v-2.25M4.93 4.93l1.59 1.59m10.96 10.96l1.59 1.59m0-13.85l-1.59 1.59m-10.96 10.96l-1.59 1.59M12 8.25a3.75 3.75 0 100 7.5 3.75 3.75 0 000-7.5z" />
        </svg>
        <!-- Moon Icon for Light Mode -->
        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
        </svg>
      </button>

      <!-- Vertical Divider -->
      <span class="w-px h-6 bg-slate-200 dark:bg-slate-800"></span>

      <!-- User Profile Widget -->
      <div class="flex items-center space-x-2">
        <div class="w-8 h-8 rounded-full bg-slate-900 dark:bg-slate-700 text-slate-100 flex items-center justify-center font-bold text-sm border border-emerald-500">
          {{ authStore.user?.name.charAt(0) }}
        </div>
        <div class="hidden md:block text-left">
          <p class="text-xs font-bold text-slate-800 dark:text-slate-100 leading-none">{{ authStore.user?.name }}</p>
          <span class="text-3xs text-emerald-600 dark:text-emerald-400 font-semibold uppercase tracking-wider">{{ authStore.userRole }}</span>
        </div>
      </div>
    </div>
  </header>
</template>
