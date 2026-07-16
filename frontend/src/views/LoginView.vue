<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const errorMsg = ref('')
const isLoading = ref(false)

const handleLogin = async () => {
  if (!email.value || !password.value) {
    errorMsg.value = 'Silakan masukkan email dan password.'
    return
  }

  isLoading.value = true
  errorMsg.value = ''

  try {
    const res = await api.post('/auth/login', {
      email: email.value,
      password: password.value
    })
    
    authStore.login(res.data.token, res.data.user)
    // If setup not complete, go to wizard
    if (!res.data.user.tenant?.is_setup_complete) {
      router.push('/setup')
    } else {
      router.push('/dashboard')
    }
  } catch (err: any) {
    errorMsg.value = err.response?.data?.message || 'Login gagal. Periksa koneksi Anda.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#00271c] flex items-center justify-center px-4 transition-colors">
    <div class="w-full max-w-md bg-[#001b13] border border-[#d4af37]/20 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
      <!-- Decorative Backdrop Blur -->
      <div class="absolute -top-10 -right-10 w-32 h-32 bg-[#d4af37]/5 rounded-full blur-2xl"></div>
      <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-[#d4af37]/5 rounded-full blur-2xl"></div>
 
      <div class="text-center mb-8 relative">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-[#001710] border border-[#d4af37]/25 rounded-2xl mb-4">
          <svg class="w-10 h-10" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 75 C 22 75, 18 68, 25 58 C 32 48, 42 55, 48 62 L 62 20 C 64 15, 66 12, 68 12 C 70 12, 73 15, 76 22 L 90 75" stroke="#d4af37" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M 33 55 C 47 55, 57 48, 64 48 C 72 48, 79 53, 76 60 C 73 67, 57 62, 45 62 C 38 62, 31 64, 29 67 C 27 70, 31 72, 36 72" stroke="#d4af37" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M78 18 C78 13, 78 13, 83 13 C78 13, 78 13, 78 8 C78 13, 78 13, 73 13 C78 13, 78 13, 78 18 Z" fill="#d4af37" />
          </svg>
        </div>
        <h2 class="text-3.5xl font-serif font-bold text-white tracking-tight leading-none">arunika.co</h2>
        <p class="text-5xs uppercase tracking-widest text-[#d4af37] font-bold block mt-1">creative companion</p>
        <p class="text-slate-400 text-sm mt-3">Portal Pembukuan &amp; Keuangan Terintegrasi</p>
      </div>
 
      <form @submit.prevent="handleLogin" class="space-y-5 relative">
        <div v-if="errorMsg" class="bg-rose-950/40 border border-rose-900/50 text-rose-400 p-3 rounded-lg text-xs font-semibold">
          {{ errorMsg }}
        </div>
 
        <div>
          <label class="block text-2xs font-bold text-slate-450 uppercase tracking-wider mb-1">Email</label>
          <input 
            v-model="email" 
            type="email" 
            placeholder="email@organisasi.com"
            class="w-full bg-[#001710] border border-[#002d20] rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-[#d4af37] transition-colors"
            required 
          />
        </div>
 
        <div>
          <label class="block text-2xs font-bold text-slate-450 uppercase tracking-wider mb-1">Password</label>
          <input 
            v-model="password" 
            type="password" 
            placeholder="••••••••"
            class="w-full bg-[#001710] border border-[#002d20] rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-[#d4af37] transition-colors"
            required 
          />
        </div>
 
        <button 
          type="submit" 
          :disabled="isLoading"
          class="w-full bg-[#d4af37] hover:bg-[#e5c158] text-[#001b13] font-bold py-3 rounded-xl transition-colors shadow-md shadow-[#d4af37]/10 text-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="!isLoading">Masuk ke Portal</span>
          <span v-else class="flex items-center justify-center space-x-2">
            <svg class="animate-spin h-5 w-5 text-[#001b13]" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Memvalidasi...</span>
          </span>
        </button>
 
        <!-- Register Link -->
        <div class="relative flex items-center">
          <div class="flex-grow border-t border-[#002d20]"></div>
          <span class="flex-shrink mx-3 text-slate-650 text-xs">atau</span>
          <div class="flex-grow border-t border-[#002d20]"></div>
        </div>
 
        <router-link
          to="/register"
          class="block w-full text-center bg-transparent border border-slate-700 hover:border-[#d4af37] hover:bg-[#d4af37]/5 text-slate-300 hover:text-[#d4af37] font-semibold py-3 rounded-xl transition-all text-sm"
        >
          Daftar Akun Organisasi Baru
        </router-link>
      </form>
    </div>
  </div>
</template>
