<script setup lang="ts">
import { ref, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useThemeStore } from '../stores/theme'
import { useRouter } from 'vue-router'
import api from '../services/api'

const authStore = useAuthStore()
const themeStore = useThemeStore()
const router = useRouter()

const searchQuery = ref('')
const searchResults = ref<any[]>([])
const isSearching = ref(false)
const isDropdownOpen = ref(false)

let searchTimeout: any = null

const performSearch = async () => {
  if (searchQuery.value.trim().length < 2) {
    searchResults.value = []
    isDropdownOpen.value = false
    return
  }

  isSearching.value = true
  isDropdownOpen.value = true

  try {
    const res = await api.get('/search', {
      params: { q: searchQuery.value }
    })
    searchResults.value = res.data
  } catch (err) {
    console.error('Error performing global search:', err)
  } finally {
    isSearching.value = false
  }
}

watch(searchQuery, (newVal) => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    performSearch()
  }, 300)
})

const handleResultClick = (link: string) => {
  searchQuery.value = ''
  searchResults.value = []
  isDropdownOpen.value = false
  router.push(link)
}

const closeSearchDropdown = () => {
  setTimeout(() => {
    isDropdownOpen.value = false
  }, 200)
}

const handleLogout = async () => {
  if (confirm('Apakah Anda yakin ingin keluar?')) {
    authStore.logout()
    router.push('/login')
  }
}

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

      <!-- Search Bar Wrapper -->
      <div class="relative hidden sm:block">
        <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-lg px-3 py-1.5 w-64 md:w-80 transition-colors border border-transparent focus-within:border-emerald-500 dark:focus-within:border-emerald-500">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-slate-400 flex-shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
          </svg>
          <input 
            type="text" 
            v-model="searchQuery"
            @focus="isDropdownOpen = true"
            @blur="closeSearchDropdown"
            placeholder="Cari event, klien, atau invoice..." 
            class="ml-2 bg-transparent text-sm w-full outline-none text-slate-700 dark:text-slate-200"
          />
          <!-- Spinner/Loading Indicator -->
          <span v-if="isSearching" class="w-3.5 h-3.5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin flex-shrink-0"></span>
        </div>

        <!-- Search Results Dropdown -->
        <div 
          v-if="isDropdownOpen && (searchResults.length > 0 || (searchQuery.trim().length >= 2 && !isSearching))"
          class="absolute left-0 mt-2 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 overflow-hidden py-1 max-h-80 overflow-y-auto"
        >
          <!-- Results Found -->
          <template v-if="searchResults.length > 0">
            <button
              v-for="item in searchResults"
              :key="item.type + '-' + item.id"
              @mousedown="handleResultClick(item.link)"
              class="w-full text-left px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/60 flex items-center gap-3 transition-colors border-b border-slate-100 dark:border-slate-800/40 last:border-b-0 cursor-pointer"
            >
              <span class="text-base flex-shrink-0">
                <span v-if="item.type === 'event'">📅</span>
                <span v-else-if="item.type === 'client'">👥</span>
                <span v-else-if="item.type === 'invoice'">📄</span>
              </span>
              <div class="min-w-0 flex-1 text-left">
                <p class="text-xs font-bold text-slate-850 dark:text-slate-105 truncate leading-tight">{{ item.title }}</p>
                <p class="text-3xs text-slate-400 font-semibold truncate mt-0.5 uppercase tracking-wider leading-none">{{ item.subtitle }}</p>
              </div>
            </button>
          </template>

          <!-- No Results -->
          <div v-else class="p-4 text-center text-xs text-slate-400 font-medium">
            Tidak ada hasil untuk "{{ searchQuery }}"
          </div>
        </div>
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

      <!-- User Profile Widget & Logout -->
      <div class="flex items-center space-x-3">
        <!-- Logout Button -->
        <button 
          @click="handleLogout"
          class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition-colors cursor-pointer text-xs font-semibold"
          title="Keluar / Logout"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
          </svg>
          <span>Keluar</span>
        </button>

        <!-- Small Vertical Divider -->
        <span class="w-px h-4 bg-slate-200 dark:bg-slate-800"></span>

        <!-- User Profile Info -->
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
    </div>
  </header>
</template>
