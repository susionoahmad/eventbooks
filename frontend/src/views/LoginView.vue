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
  <div class="min-h-screen bg-slate-900 flex items-center justify-center px-4 transition-colors">
    <div class="w-full max-w-md bg-slate-950 border border-slate-800 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
      <!-- Decorative Backdrop Blur -->
      <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl"></div>
      <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>

      <div class="text-center mb-8 relative">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl mb-4">
          <svg class="w-7 h-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
        </div>
        <h2 class="text-3xl font-extrabold text-white tracking-tight">EventBooks</h2>
        <p class="text-slate-400 text-sm mt-2">Sistem Pembukuan &amp; Pajak Event Organizer</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-5 relative">
        <div v-if="errorMsg" class="bg-rose-950/40 border border-rose-900/50 text-rose-400 p-3 rounded-lg text-xs font-semibold">
          {{ errorMsg }}
        </div>

        <div>
          <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Email</label>
          <input 
            v-model="email" 
            type="email" 
            placeholder="email@organisasi.com"
            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 transition-colors"
            required 
          />
        </div>

        <div>
          <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Password</label>
          <input 
            v-model="password" 
            type="password" 
            placeholder="••••••••"
            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 transition-colors"
            required 
          />
        </div>

        <button 
          type="submit" 
          :disabled="isLoading"
          class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-800 text-white font-bold py-3 rounded-xl transition-colors shadow-md shadow-emerald-950/20 text-sm cursor-pointer"
        >
          <span v-if="!isLoading">Masuk ke Portal</span>
          <span v-else class="flex items-center justify-center space-x-2">
            <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Memvalidasi...</span>
          </span>
        </button>

        <!-- Register Link -->
        <div class="relative flex items-center">
          <div class="flex-grow border-t border-slate-800"></div>
          <span class="flex-shrink mx-3 text-slate-600 text-xs">atau</span>
          <div class="flex-grow border-t border-slate-800"></div>
        </div>

        <router-link
          to="/register"
          class="block w-full text-center bg-transparent border border-slate-700 hover:border-emerald-600 hover:bg-emerald-950/30 text-slate-300 hover:text-emerald-400 font-semibold py-3 rounded-xl transition-all text-sm"
        >
          Daftar Akun Organisasi Baru
        </router-link>
      </form>
    </div>
  </div>
</template>
