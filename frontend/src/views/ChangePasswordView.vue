<script setup lang="ts">
import { ref } from 'vue'
import api from '../services/api'

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const isSubmitting = ref(false)
const message = ref('')
const messageType = ref<'success' | 'error' | ''>('')

const handleChangePassword = async () => {
  message.value = ''
  messageType.value = ''

  if (newPassword.value.length < 8) {
    message.value = 'Password baru minimal harus 8 karakter.'
    messageType.value = 'error'
    return
  }

  if (newPassword.value !== confirmPassword.value) {
    message.value = 'Konfirmasi password baru tidak cocok.'
    messageType.value = 'error'
    return
  }

  isSubmitting.value = true

  try {
    const res = await api.put('/auth/password', {
      current_password: currentPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: confirmPassword.value
    })

    message.value = res.data.message || 'Password berhasil diperbarui!'
    messageType.value = 'success'
    
    // Clear inputs on success
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (err: any) {
    console.error('Error changing password:', err)
    message.value = err.response?.data?.message || 'Gagal mengubah password. Silakan periksa kembali password saat ini Anda.'
    messageType.value = 'error'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="max-w-md mx-auto space-y-6">
    <!-- Header -->
    <div class="border-b border-slate-200 dark:border-slate-800 pb-4">
      <h1 class="text-xl font-bold text-slate-900 dark:text-white">Keamanan Akun</h1>
      <p class="text-xs text-slate-500 mt-1">Perbarui kata sandi Anda secara berkala untuk menjaga keamanan akun.</p>
    </div>

    <!-- Change Password Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-5">
      <div class="flex items-center space-x-3 pb-3 border-b border-slate-100 dark:border-slate-850">
        <div class="p-2 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl text-emerald-600 dark:text-emerald-450">
          <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
          </svg>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 dark:text-white text-sm">Ganti Password</h3>
          <p class="text-3xs text-slate-400">Kata sandi baru Anda harus unik dibandingkan dengan kata sandi sebelumnya.</p>
        </div>
      </div>

      <!-- Notification message banner -->
      <div v-if="message" :class="[
        'p-3 rounded-lg text-xs font-semibold border transition-all duration-300 flex items-start gap-2.5',
        messageType === 'success' 
          ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400' 
          : 'bg-rose-500/10 border-rose-500/20 text-rose-600 dark:text-rose-450'
      ]">
        <!-- Success Icon -->
        <svg v-if="messageType === 'success'" class="h-4 w-4 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <!-- Error Icon -->
        <svg v-else class="h-4 w-4 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        <span>{{ message }}</span>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleChangePassword" class="space-y-4 text-xs text-slate-700 dark:text-slate-300">
        <div>
          <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Password Saat Ini</label>
          <input 
            v-model="currentPassword" 
            type="password" 
            placeholder="Masukkan kata sandi lama" 
            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" 
            required 
          />
        </div>

        <div>
          <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Password Baru</label>
          <input 
            v-model="newPassword" 
            type="password" 
            placeholder="Minimal 8 karakter" 
            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" 
            required 
            minlength="8"
          />
        </div>

        <div>
          <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Konfirmasi Password Baru</label>
          <input 
            v-model="confirmPassword" 
            type="password" 
            placeholder="Ulangi kata sandi baru" 
            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" 
            required 
            minlength="8"
          />
        </div>

        <button 
          type="submit" 
          :disabled="isSubmitting"
          class="w-full flex items-center justify-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:bg-emerald-800 text-white rounded-lg font-bold text-xs cursor-pointer transition-colors shadow-sm"
        >
          <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ isSubmitting ? 'Memproses...' : 'Simpan Password Baru' }}
        </button>
      </form>
    </div>
  </div>
</template>
