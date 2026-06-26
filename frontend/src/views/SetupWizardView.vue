<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()
const router    = useRouter()

// ─── Step state ──────────────────────────────────────────────────────────────
const currentStep = ref(1)
const totalSteps  = 3
const isSaving    = ref(false)
const errorMsg    = ref('')

// ─── Step 1: Portal ──────────────────────────────────────────────────────────
const s1 = ref({
  name:        authStore.user?.tenant?.name || '',
  jenis_usaha: 'event_organizer',
  website:     '',
})

// ─── Step 2: Legal/Tax ───────────────────────────────────────────────────────
const s2 = ref({
  npwp:     '',
  email:    '',
  telepon:  '',
  alamat:   '',
  kota:     '',
  provinsi: '',
  kode_pos: '',
})

// NPWP auto-format: 00.000.000.0-000.000
const rawNpwp = ref('')
const formatNpwp = (val: string) => {
  const digits = val.replace(/\D/g, '').slice(0, 15)
  let out = ''
  digits.split('').forEach((c, i) => {
    if (i === 2 || i === 5 || i === 8 || i === 9 || i === 12) {
      out += i === 9 ? '-' : '.'
    }
    out += c
  })
  return out
}
const onNpwpInput = (e: Event) => {
  const raw = (e.target as HTMLInputElement).value
  rawNpwp.value = raw.replace(/\D/g, '')
  s2.value.npwp = formatNpwp(rawNpwp.value)
}

// ─── Step 3: Team ────────────────────────────────────────────────────────────
const members = ref<{ name: string; email: string; role: string }[]>([])
const addMember = () => members.value.push({ name: '', email: '', role: 'staff' })
const removeMember = (i: number) => members.value.splice(i, 1)

// ─── Labels ──────────────────────────────────────────────────────────────────
const jenisUsahaLabels: Record<string, string> = {
  event_organizer:   'Event Organizer (EO)',
  wedding_organizer: 'Wedding Organizer (WO)',
  production_house:  'Production House',
  lainnya:           'Lainnya',
}
const roleLabels: Record<string, string> = {
  finance_manager: 'Finance Manager',
  admin:           'Admin',
  staff:           'Staff',
}

// ─── Progress ─────────────────────────────────────────────────────────────────
const progress = computed(() => Math.round(((currentStep.value - 1) / totalSteps) * 100))

// ─── Save step ───────────────────────────────────────────────────────────────
const saveStep = async () => {
  errorMsg.value = ''
  isSaving.value = true

  try {
    let payload: Record<string, any> = { step: currentStep.value }

    if (currentStep.value === 1) {
      if (!s1.value.name.trim()) {
        errorMsg.value = 'Nama organisasi wajib diisi.'
        return
      }
      payload = { ...payload, ...s1.value }
    } else if (currentStep.value === 2) {
      payload = { ...payload, ...s2.value }
    } else if (currentStep.value === 3) {
      payload.members = members.value.filter(m => m.name && m.email)
    }

    const res = await api.post('/tenant/setup', payload)

    if (currentStep.value === 3) {
      // Mark complete in store
      authStore.markSetupComplete()
      // Refresh tenant data
      await new Promise(r => setTimeout(r, 400))
      router.push('/dashboard')
    } else {
      currentStep.value++
    }
  } catch (err: any) {
    const errors = err.response?.data?.errors
    if (errors) {
      errorMsg.value = Object.values(errors).flat().join(' ')
    } else {
      errorMsg.value = err.response?.data?.message || 'Gagal menyimpan. Coba lagi.'
    }
  } finally {
    isSaving.value = false
  }
}

const goBack = () => {
  if (currentStep.value > 1) currentStep.value--
}

const skipStep = () => {
  if (currentStep.value < totalSteps) {
    currentStep.value++
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 flex items-center justify-center px-4 py-10">
    <!-- Background -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
      <div class="absolute top-0 left-1/4 w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-2xl relative">

      <!-- Top bar: logo + step info -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-emerald-500/20 border border-emerald-500/30 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <span class="text-white font-bold text-lg tracking-tight">EventBooks</span>
        </div>
        <span class="text-slate-500 text-sm">Langkah {{ currentStep }} dari {{ totalSteps }}</span>
      </div>

      <!-- Progress bar -->
      <div class="mb-8">
        <div class="flex items-center gap-2 mb-3">
          <template v-for="n in totalSteps" :key="n">
            <div class="flex-1">
              <div class="h-1.5 rounded-full overflow-hidden bg-slate-800">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :class="n <= currentStep ? 'bg-emerald-500' : 'bg-slate-800'"
                  :style="n === currentStep ? `width: 60%` : n < currentStep ? `width: 100%` : `width: 0%`"
                ></div>
              </div>
            </div>
          </template>
        </div>
        <!-- Step labels -->
        <div class="grid grid-cols-3 text-center">
          <div v-for="(label, i) in ['Pengaturan Portal', 'Data Legal & Pajak', 'Hak Akses Tim']" :key="i"
            :class="i + 1 === currentStep ? 'text-emerald-400 font-semibold' : i + 1 < currentStep ? 'text-slate-500' : 'text-slate-600'"
            class="text-xs transition-colors"
          >{{ label }}</div>
        </div>
      </div>

      <!-- Card -->
      <div class="bg-slate-950 border border-slate-800 rounded-3xl p-8 shadow-2xl">

        <!-- Error -->
        <div v-if="errorMsg" class="mb-5 bg-rose-950/40 border border-rose-900/50 text-rose-400 p-3 rounded-xl text-xs font-semibold flex items-start gap-2">
          <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ errorMsg }}
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!--  STEP 1: Pengaturan Portal                             -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div v-if="currentStep === 1">
          <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-10 h-10 bg-emerald-500/15 border border-emerald-500/30 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-white">Pengaturan Portal</h2>
                <p class="text-slate-400 text-sm">Konfigurasi identitas organisasi Anda</p>
              </div>
            </div>
          </div>

          <div class="space-y-5">
            <!-- Nama Organisasi -->
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                Nama Organisasi <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="s1.name"
                type="text"
                placeholder="contoh: Royal Event Organizer"
                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"
                required
              />
            </div>

            <!-- Jenis Usaha -->
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                Jenis Usaha <span class="text-rose-500">*</span>
              </label>
              <div class="grid grid-cols-2 gap-3">
                <button
                  v-for="(label, val) in jenisUsahaLabels" :key="val"
                  type="button"
                  @click="s1.jenis_usaha = val"
                  :class="[
                    'px-4 py-3 rounded-xl border text-sm font-semibold text-left transition-all',
                    s1.jenis_usaha === val
                      ? 'border-emerald-500 bg-emerald-500/10 text-emerald-400'
                      : 'border-slate-800 bg-slate-900 text-slate-400 hover:border-slate-600 hover:text-slate-300'
                  ]"
                >
                  {{ label }}
                </button>
              </div>
            </div>

            <!-- Website -->
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                Website / Instagram <span class="text-slate-600">(opsional)</span>
              </label>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">🌐</span>
                <input
                  v-model="s1.website"
                  type="text"
                  placeholder="https://organisasi.com atau @handle_ig"
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!--  STEP 2: Data Legal & Pajak                            -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div v-if="currentStep === 2">
          <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-10 h-10 bg-blue-500/15 border border-blue-500/30 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-white">Data Legal &amp; Wajib Pajak</h2>
                <p class="text-slate-400 text-sm">Informasi untuk keperluan perpajakan &amp; penagihan</p>
              </div>
            </div>
            <div class="mt-3 bg-blue-950/30 border border-blue-900/40 rounded-xl px-4 py-2.5 text-xs text-blue-300 flex items-center gap-2">
              <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Semua field di halaman ini bersifat opsional, dapat dilengkapi nanti di Pengaturan
            </div>
          </div>

          <div class="space-y-4">
            <!-- NPWP -->
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">NPWP</label>
              <input
                :value="s2.npwp"
                @input="onNpwpInput"
                type="text"
                placeholder="00.000.000.0-000.000"
                maxlength="20"
                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all font-mono placeholder:text-slate-600"
              />
            </div>

            <!-- Email & Telepon -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Email Kontak</label>
                <input v-model="s2.email" type="email" placeholder="billing@org.com"
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Telepon</label>
                <input v-model="s2.telepon" type="text" placeholder="0812-xxxx-xxxx"
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"/>
              </div>
            </div>

            <!-- Alamat -->
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Alamat Lengkap</label>
              <textarea v-model="s2.alamat" rows="2" placeholder="Jl. contoh No. 123..."
                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all resize-none placeholder:text-slate-600"></textarea>
            </div>

            <!-- Kota, Provinsi, Kode Pos -->
            <div class="grid grid-cols-3 gap-3">
              <div class="col-span-1">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Kota</label>
                <input v-model="s2.kota" type="text" placeholder="Jakarta"
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"/>
              </div>
              <div class="col-span-1">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Provinsi</label>
                <input v-model="s2.provinsi" type="text" placeholder="DKI Jakarta"
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"/>
              </div>
              <div class="col-span-1">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Kode Pos</label>
                <input v-model="s2.kode_pos" type="text" maxlength="5" placeholder="12345"
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all placeholder:text-slate-600"/>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!--  STEP 3: Hak Akses Tim                                 -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div v-if="currentStep === 3">
          <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-10 h-10 bg-purple-500/15 border border-purple-500/30 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-white">Hak Akses Tim</h2>
                <p class="text-slate-400 text-sm">Undang anggota tim ke dalam portal Anda</p>
              </div>
            </div>
          </div>

          <!-- Current owner -->
          <div class="mb-5 p-4 bg-slate-900/60 border border-slate-800 rounded-xl">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-emerald-500/20 rounded-full flex items-center justify-center text-emerald-400 font-bold text-sm">
                  {{ authStore.user?.name?.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <p class="text-sm font-semibold text-white">{{ authStore.user?.name }}</p>
                  <p class="text-xs text-slate-400">{{ authStore.user?.email }}</p>
                </div>
              </div>
              <span class="text-xs bg-emerald-950/60 border border-emerald-800/50 text-emerald-400 px-2.5 py-1 rounded-full font-semibold">Owner</span>
            </div>
          </div>

          <!-- Add members -->
          <div class="space-y-3 mb-4">
            <div
              v-for="(m, i) in members" :key="i"
              class="p-4 bg-slate-900/40 border border-slate-800 rounded-xl space-y-3"
            >
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Anggota {{ i + 1 }}</span>
                <button type="button" @click="removeMember(i)" class="text-slate-600 hover:text-rose-400 transition-colors">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <input v-model="m.name" type="text" placeholder="Nama lengkap"
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-emerald-500 transition-all placeholder:text-slate-600"/>
                <input v-model="m.email" type="email" placeholder="email@org.com"
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-emerald-500 transition-all placeholder:text-slate-600"/>
              </div>
              <select v-model="m.role"
                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-emerald-500 transition-all">
                <option v-for="(label, val) in roleLabels" :key="val" :value="val">{{ label }}</option>
              </select>
            </div>
          </div>

          <button
            type="button"
            @click="addMember"
            class="w-full py-3 border border-dashed border-slate-700 hover:border-emerald-600 text-slate-500 hover:text-emerald-400 rounded-xl transition-all text-sm font-semibold flex items-center justify-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Anggota Tim
          </button>

          <!-- Info password -->
          <div v-if="members.length > 0" class="mt-4 bg-amber-950/30 border border-amber-800/40 rounded-xl px-4 py-3 text-xs text-amber-300 flex items-start gap-2">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Akun tim dibuat dengan password default <strong class="font-mono">eventbooks123</strong>. Bagikan ke anggota &amp; minta ganti password setelah login.</span>
          </div>
        </div>

        <!-- ─── Action Buttons ─────────────────────────────────────── -->
        <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-800">
          <button
            v-if="currentStep > 1"
            type="button"
            @click="goBack"
            class="flex items-center gap-2 text-slate-400 hover:text-white text-sm font-semibold transition-colors px-4 py-2.5 rounded-xl hover:bg-slate-800"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
          </button>
          <div v-else></div>

          <div class="flex items-center gap-3">
            <!-- Skip button (step 2 & 3) -->
            <button
              v-if="currentStep >= 2"
              type="button"
              @click="currentStep === 3 ? saveStep() : skipStep()"
              class="px-4 py-2.5 text-slate-500 hover:text-slate-300 text-sm font-semibold transition-colors rounded-xl hover:bg-slate-800"
            >
              {{ currentStep === 3 ? 'Lewati & Selesai' : 'Lewati' }}
            </button>

            <!-- Next / Finish -->
            <button
              type="button"
              @click="saveStep"
              :disabled="isSaving"
              class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-700 text-white font-bold px-6 py-2.5 rounded-xl transition-all text-sm shadow-lg shadow-emerald-950/30"
            >
              <svg v-if="isSaving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else-if="currentStep < totalSteps" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <span>{{ isSaving ? 'Menyimpan...' : currentStep < totalSteps ? 'Lanjut' : 'Selesai & Masuk Dashboard' }}</span>
            </button>
          </div>
        </div>

      </div>

      <!-- Step indicators -->
      <div class="flex justify-center gap-2 mt-6">
        <div v-for="n in totalSteps" :key="n"
          :class="[
            'h-1.5 rounded-full transition-all duration-300',
            n === currentStep ? 'w-8 bg-emerald-500' : n < currentStep ? 'w-3 bg-emerald-700' : 'w-3 bg-slate-700'
          ]"
        ></div>
      </div>

    </div>
  </div>
</template>
