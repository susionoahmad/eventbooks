<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'
import BottomNav from '../components/BottomNav.vue'
import { useThemeStore } from '../stores/theme'
import { useAuthStore } from '../stores/auth'

const themeStore = useThemeStore()
const authStore = useAuthStore()
const router = useRouter()

const isMobileSidebarOpen = ref(false)
const isChecking = ref(false)
const checkMessage = ref('')
const checkMessageType = ref<'success' | 'error' | ''>('')

const toggleMobileSidebar = () => {
  isMobileSidebarOpen.value = !isMobileSidebarOpen.value
}

const formatTrialEndDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  try {
    const trialDateStr = dateStr.includes(' ') && !dateStr.includes('T')
      ? dateStr.replace(' ', 'T')
      : dateStr
    const date = new Date(trialDateStr)
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    }).format(date)
  } catch (e) {
    return dateStr
  }
}

const checkSubscriptionStatus = async () => {
  isChecking.value = true
  checkMessage.value = ''
  checkMessageType.value = ''
  try {
    await authStore.fetchCurrentUser()
    if (!authStore.isTrialExpired) {
      checkMessage.value = 'Status langganan aktif! Memuat ulang portal...'
      checkMessageType.value = 'success'
      setTimeout(() => {
        window.location.href = '/dashboard'
      }, 1000)
    } else {
      checkMessage.value = 'Status langganan Anda masih belum diperbarui.'
      checkMessageType.value = 'error'
    }
  } catch (error: any) {
    checkMessage.value = error?.response?.data?.message || 'Gagal memeriksa status. Silakan coba lagi.'
    checkMessageType.value = 'error'
  } finally {
    isChecking.value = false
  }
}

const handleLogout = async () => {
  authStore.logout()
  router.push('/login')
}

onMounted(() => {
  themeStore.initTheme()
})
</script>

<template>
  <div class="flex h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
    <!-- Desktop Sidebar -->
    <div v-if="!authStore.isTrialExpired" class="hidden md:block">
      <Sidebar />
    </div>

    <!-- Mobile Sidebar Drawer (Slide-over overlay) -->
    <div
      v-if="!authStore.isTrialExpired && isMobileSidebarOpen"
      class="fixed inset-0 z-40 flex md:hidden"
      role="dialog"
      aria-modal="true"
    >
      <!-- Backdrop overlay -->
      <div
        @click="toggleMobileSidebar"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
      ></div>

      <!-- Drawer Content -->
      <div class="relative flex w-64 max-w-xs flex-col bg-slate-900 text-slate-100 animate-slide-in">
        <div class="absolute right-2 top-2 p-2">
          <button @click="toggleMobileSidebar" class="p-1 rounded hover:bg-slate-800 text-slate-400 hover:text-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <Sidebar class="w-full" />
      </div>
    </div>

    <!-- Right Side: Header + Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <Navbar v-if="!authStore.isTrialExpired" @toggleMobileSidebar="toggleMobileSidebar" />

      <!-- Content Window – extra bottom padding on mobile so content clears the bottom nav -->
      <main v-if="!authStore.isTrialExpired" class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8 pb-20 md:pb-6 lg:pb-8">
        <RouterView />
      </main>
    </div>

    <!-- Mobile Bottom Navigation (hidden on md+) -->
    <BottomNav v-if="!authStore.isTrialExpired" />

    <!-- Trial Expired Overlay -->
    <div
      v-if="authStore.isTrialExpired"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950 p-4 transition-all duration-300"
    >
      <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-rose-500/20 bg-slate-900 p-8 text-center shadow-2xl shadow-rose-950/20 backdrop-blur-lg">
        
        <!-- Glowing background effect -->
        <div class="absolute -left-16 -top-16 -z-10 h-40 w-40 rounded-full bg-rose-500/10 blur-3xl"></div>
        <div class="absolute -right-16 -bottom-16 -z-10 h-40 w-40 rounded-full bg-rose-500/5 blur-3xl"></div>

        <!-- Lock Icon / Header -->
        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-rose-500/10 text-rose-400 ring-8 ring-rose-500/5">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 animate-pulse">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
          </svg>
        </div>

        <!-- Alert Title -->
        <h2 class="mb-2 text-2xl font-bold tracking-tight text-white md:text-3xl">
          Masa Uji Coba Berakhir
        </h2>
        
        <!-- Subscription Details -->
        <p class="mb-6 text-sm text-slate-300 md:text-base leading-relaxed">
          Masa uji coba gratis 30 hari untuk organisasi 
          <span class="font-semibold text-emerald-400">{{ authStore.organizationName }}</span> 
          telah berakhir pada <span class="font-semibold text-rose-400">{{ formatTrialEndDate(authStore.user?.tenant?.trial_ends_at) }}</span>.
        </p>

        <div class="mb-8 rounded-lg bg-slate-950/60 p-4 text-xs md:text-sm text-slate-400 border border-slate-800/80 leading-relaxed text-left">
          <p class="mb-2 font-medium text-slate-300">Bagaimana cara mengaktifkan kembali?</p>
          <ul class="list-disc pl-4 space-y-1">
            <li>Hubungi Administrator / Owner organisasi Anda.</li>
            <li>Lakukan pembaruan langganan ke paket <strong>Basic</strong> atau <strong>Pro</strong> melalui menu penagihan (billing).</li>
            <li>Setelah pembayaran selesai dilakukan, klik tombol <strong>Periksa Status</strong> di bawah ini.</li>
          </ul>
        </div>

        <!-- Inline messages feedback -->
        <div v-if="checkMessage" :class="[
          'mb-4 p-3 rounded-lg text-sm border',
          checkMessageType === 'success' 
            ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' 
            : 'bg-rose-500/10 border-rose-500/20 text-rose-400'
        ]">
          {{ checkMessage }}
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <button
            @click="checkSubscriptionStatus"
            :disabled="isChecking"
            class="flex items-center justify-center px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 disabled:bg-emerald-800 disabled:opacity-50 text-slate-950 font-semibold shadow-lg shadow-emerald-500/20 transition-all duration-200"
          >
            <svg v-if="isChecking" class="animate-spin -ml-1 mr-2 h-4 w-4 text-slate-950" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isChecking ? 'Memeriksa...' : 'Periksa Status' }}
          </button>
          
          <button
            @click="handleLogout"
            class="px-5 py-2.5 rounded-lg border border-slate-700 hover:bg-slate-800 text-slate-300 font-medium transition-all duration-200"
          >
            Keluar dari Akun
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<style>
@keyframes slide-in {
  from { transform: translateX(-100%); }
  to   { transform: translateX(0); }
}
.animate-slide-in {
  animation: slide-in 0.25s ease-out forwards;
}
</style>
