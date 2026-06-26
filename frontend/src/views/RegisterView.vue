<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()
const router    = useRouter()

const orgName          = ref('')
const ownerName        = ref('')
const email            = ref('')
const password         = ref('')
const passwordConfirm  = ref('')
const errorMsg         = ref('')
const isLoading        = ref(false)
const showPassword     = ref(false)

const handleRegister = async () => {
  errorMsg.value = ''

  if (password.value !== passwordConfirm.value) {
    errorMsg.value = 'Konfirmasi password tidak cocok.'
    return
  }
  if (password.value.length < 8) {
    errorMsg.value = 'Password minimal 8 karakter.'
    return
  }

  isLoading.value = true
  try {
    const res = await api.post('/auth/register', {
      org_name:              orgName.value,
      name:                  ownerName.value,
      email:                 email.value,
      password:              password.value,
      password_confirmation: passwordConfirm.value,
    })

    authStore.login(res.data.token, res.data.user)
    router.push('/setup')
  } catch (err: any) {
    const errors = err.response?.data?.errors
    if (errors) {
      errorMsg.value = Object.values(errors).flat().join(' ')
    } else {
      errorMsg.value = err.response?.data?.message || 'Pendaftaran gagal. Coba lagi.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 flex items-center justify-center px-4 py-12">
    <!-- Background blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 -right-40 w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md relative">
      <!-- Card -->
      <div class="bg-slate-950 border border-slate-800 rounded-3xl p-8 shadow-2xl">

        <!-- Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-14 h-14 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl mb-4">
            <svg class="w-7 h-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
          </div>
          <h1 class="text-2xl font-extrabold text-white">Daftar Organisasi Baru</h1>
          <p class="text-slate-400 text-sm mt-1.5">Mulai uji coba gratis 30 hari, tanpa kartu kredit</p>
          <!-- Trial badge -->
          <div class="inline-flex items-center gap-1.5 mt-3 bg-emerald-950/60 border border-emerald-800/60 text-emerald-400 text-xs font-semibold px-3 py-1 rounded-full">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Trial 30 hari · Gratis
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleRegister" class="space-y-4">
          <!-- Error -->
          <div v-if="errorMsg" class="bg-rose-950/40 border border-rose-900/50 text-rose-400 p-3 rounded-xl text-xs font-semibold flex items-start gap-2">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ errorMsg }}
          </div>

          <!-- Org Name -->
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Nama Organisasi / Perusahaan <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="orgName"
              type="text"
              placeholder="contoh: Royal Event Organizer"
              class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"
              required
            />
          </div>

          <!-- Owner Name -->
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Nama Pemilik / Owner <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="ownerName"
              type="text"
              placeholder="Nama lengkap Anda"
              class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"
              required
            />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Email Login <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="email"
              type="email"
              placeholder="email@organisasi.com"
              class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"
              required
            />
            <p class="text-2xs text-slate-500 mt-1">Email ini digunakan untuk login ke portal</p>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Password <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Minimal 8 karakter"
                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 pr-12 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"
                required
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors"
              >
                <svg v-if="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Konfirmasi Password <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="passwordConfirm"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Ulangi password"
              :class="[
                'w-full bg-slate-900 border rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600',
                passwordConfirm && password !== passwordConfirm
                  ? 'border-rose-600 focus:ring-1 focus:ring-rose-500/20'
                  : 'border-slate-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20'
              ]"
              required
            />
            <p v-if="passwordConfirm && password !== passwordConfirm" class="text-2xs text-rose-500 mt-1">
              Password tidak cocok
            </p>
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="isLoading || (!!passwordConfirm && password !== passwordConfirm)"
            class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-800 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-emerald-950/30 text-sm mt-2 flex items-center justify-center gap-2"
          >
            <svg v-if="!isLoading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
            <svg v-else class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ isLoading ? 'Mendaftar...' : 'Daftar & Lanjut Setup' }}</span>
          </button>
        </form>

        <!-- Back to login -->
        <div class="mt-6 text-center">
          <span class="text-slate-500 text-xs">Sudah punya akun? </span>
          <router-link to="/login" class="text-emerald-400 hover:text-emerald-300 text-xs font-semibold transition-colors">
            Masuk ke Portal
          </router-link>
        </div>
      </div>

      <!-- Terms note -->
      <p class="text-center text-slate-600 text-2xs mt-4">
        Dengan mendaftar, Anda menyetujui Syarat &amp; Ketentuan layanan EventBooks
      </p>
    </div>
  </div>
</template>
