<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const clients = ref<any[]>([])
const isModalOpen = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const activeClient = ref<any>({
  id: null, kode_klien: '', nama: '', tipe: 'perorangan', perusahaan: '', npwp: '', email: '', telepon: '', alamat: '', file_ktp: null, file_npwp: null, file_ktp_url: null, file_npwp_url: null
})
const fileKtpInput = ref<File | null>(null)
const fileNpwpInput = ref<File | null>(null)

// NPWP auto-format: support 15-digit (00.000.000.0-000.000) and 16-digit (000.000.000.0-000.000 / flat NIK)
const formatNpwp = (val: string) => {
  const digits = val.replace(/\D/g, '').slice(0, 16)
  
  if (digits.length === 16) {
    if (digits.startsWith('0')) {
      let out = ''
      digits.split('').forEach((c, i) => {
        if (i === 3 || i === 6 || i === 9 || i === 10 || i === 13) {
          out += i === 10 ? '-' : '.'
        }
        out += c
      })
      return out
    } else {
      return digits
    }
  }
  
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
  const digits = raw.replace(/\D/g, '').slice(0, 16)
  activeClient.value.npwp = formatNpwp(digits)
}

const handleKtpFileChange = (e: any) => {
  const files = e.target.files
  if (files && files.length > 0) {
    fileKtpInput.value = files[0]
  }
}

const handleNpwpFileChange = (e: any) => {
  const files = e.target.files
  if (files && files.length > 0) {
    fileNpwpInput.value = files[0]
  }
}

const viewClientFile = (url: string, filename: string) => {
  const token = authStore.token || localStorage.getItem('token') || ''
  const baseUrl = api.defaults.baseURL || import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1'
  const cleanUrl = url.startsWith('/') ? url : '/' + url
  const fullUrl = `${baseUrl}${cleanUrl}?token=${token}`
  window.open(fullUrl, '_blank')
}

const fetchClients = async () => {
  try {
    const res = await api.get('/clients')
    clients.value = res.data.data
  } catch (err) {
    console.error('Error fetching clients:', err)
  }
}

onMounted(() => {
  fetchClients()
})

const openCreateModal = async () => {
  modalMode.value = 'create'
  activeClient.value = { id: null, kode_klien: 'Loading...', nama: '', tipe: 'perorangan', perusahaan: '', npwp: '', email: '', telepon: '', alamat: '', file_ktp: null, file_npwp: null, file_ktp_url: null, file_npwp_url: null }
  fileKtpInput.value = null
  fileNpwpInput.value = null
  isModalOpen.value = true
  try {
    const res = await api.get('/clients/next-code')
    activeClient.value.kode_klien = res.data.next_code
  } catch (err) {
    console.error('Error fetching next client code:', err)
    const today = new Date().toISOString().slice(0, 10).replace(/-/g, '')
    activeClient.value.kode_klien = `${today}-${String(clients.value.length + 1).padStart(3, '0')}`
  }
}

const openEditModal = (client: any) => {
  modalMode.value = 'edit'
  activeClient.value = { ...client, npwp: formatNpwp(client.npwp || ''), tipe: client.tipe || 'perorangan' }
  fileKtpInput.value = null
  fileNpwpInput.value = null
  isModalOpen.value = true
}

const saveClient = async () => {
  try {
    const formData = new FormData()
    formData.append('kode_klien', activeClient.value.kode_klien)
    formData.append('nama', activeClient.value.nama)
    formData.append('tipe', activeClient.value.tipe)
    formData.append('perusahaan', activeClient.value.perusahaan || '')
    formData.append('npwp', activeClient.value.npwp || '')
    formData.append('email', activeClient.value.email || '')
    formData.append('telepon', activeClient.value.telepon)
    formData.append('alamat', activeClient.value.alamat || '')

    if (fileKtpInput.value) {
      formData.append('file_ktp', fileKtpInput.value)
    }
    if (fileNpwpInput.value) {
      formData.append('file_npwp', fileNpwpInput.value)
    }

    if (modalMode.value === 'create') {
      await api.post('/clients', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    } else {
      formData.append('_method', 'PUT')
      await api.post(`/clients/${activeClient.value.id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    }
    fetchClients()
    isModalOpen.value = false
  } catch (err: any) {
    console.error('Error saving client:', err)
    const errors = err.response?.data?.errors
    if (errors) {
      alert(Object.values(errors).flat().join('\n'))
    } else {
      alert(err.response?.data?.message || 'Gagal menyimpan data klien.')
    }
  }
}

const deleteClient = async (id: number) => {
  if (confirm('Apakah Anda yakin ingin menghapus klien ini?')) {
    try {
      await api.delete(`/clients/${id}`)
      fetchClients()
    } catch (err) {
      console.error('Error deleting client:', err)
    }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Master Data Klien</h1>
        <p class="text-xs text-slate-500 mt-1">Daftar klien, perusahaan, dan kontak legal perpajakan.</p>
      </div>
      <button 
        v-if="authStore.userRole !== 'staff'"
        @click="openCreateModal"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-colors cursor-pointer"
      >
        + Tambah Klien
      </button>
    </div>

    <!-- Table Section -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/85 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 text-slate-400 text-3xs font-bold uppercase tracking-wider">
              <th class="p-4">Kode</th>
              <th class="p-4">Nama / Perusahaan</th>
              <th class="p-4">NPWP</th>
              <th class="p-4">Kontak</th>
              <th class="p-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-sm">
            <tr v-for="client in clients" :key="client.id" class="hover:bg-slate-50/40 dark:hover:bg-slate-800/10 text-slate-700 dark:text-slate-300 transition-colors">
              <td class="p-4 font-mono text-xs text-slate-400">
                <div>{{ client.kode_klien }}</div>
                <div class="mt-1.5">
                  <span v-if="client.tipe === 'non_perorangan'" class="px-2 py-0.5 text-3xs font-bold rounded-full bg-indigo-100/80 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-850">Non-Perorangan</span>
                  <span v-else class="px-2 py-0.5 text-3xs font-bold rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200/50 dark:border-slate-750">Perorangan</span>
                </div>
              </td>
              <td class="p-4">
                <span class="font-bold text-slate-900 dark:text-white block">{{ client.nama }}</span>
                <span class="text-xs text-slate-400 block mt-0.5">{{ client.perusahaan || 'Personal Klien' }}</span>
              </td>
              <td class="p-4 text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span>{{ client.npwp || 'Tidak Ada NPWP' }}</span>
                <div class="flex items-center space-x-2 mt-1.5" v-if="client.file_ktp_url || client.file_npwp_url">
                  <button v-if="client.file_ktp_url" @click="viewClientFile(client.file_ktp_url, 'KTP_' + client.nama)" class="text-3xs text-emerald-500 hover:text-emerald-400 font-bold uppercase tracking-wider flex items-center bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer">
                    📄 KTP
                  </button>
                  <button v-if="client.file_npwp_url" @click="viewClientFile(client.file_npwp_url, 'NPWP_' + client.nama)" class="text-3xs text-emerald-500 hover:text-emerald-400 font-bold uppercase tracking-wider flex items-center bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer">
                    📄 NPWP
                  </button>
                </div>
              </td>
              <td class="p-4">
                <span class="block text-xs font-medium">{{ client.email || '-' }}</span>
                <span class="block text-2xs text-slate-400 mt-0.5">{{ client.telepon }}</span>
              </td>
              <td class="p-4 text-right space-x-2">
                <button 
                  v-if="authStore.userRole !== 'staff'"
                  @click="openEditModal(client)"
                  class="text-xs bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 px-2.5 py-1.5 rounded font-semibold transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  v-if="authStore.userRole === 'owner'"
                  @click="deleteClient(client.id)"
                  class="text-xs bg-rose-950/20 text-rose-400 hover:bg-rose-950/40 px-2.5 py-1.5 rounded font-semibold transition-colors cursor-pointer"
                >
                  Hapus
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit/Create Modal (Simple popup) -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
          {{ modalMode === 'create' ? 'Tambah Klien Baru' : 'Edit Detail Klien' }}
        </h3>

        <form @submit.prevent="saveClient" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-300">
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Tipe Klien</label>
            <div class="flex items-center space-x-5 py-1">
              <label class="flex items-center space-x-2 text-sm text-slate-800 dark:text-slate-200 cursor-pointer">
                <input type="radio" v-model="activeClient.tipe" value="perorangan" class="accent-emerald-600 w-4 h-4" />
                <span>Perorangan</span>
              </label>
              <label class="flex items-center space-x-2 text-sm text-slate-800 dark:text-slate-200 cursor-pointer">
                <input type="radio" v-model="activeClient.tipe" value="non_perorangan" class="accent-emerald-600 w-4 h-4" />
                <span>Non-Perorangan (Badan/Perusahaan)</span>
              </label>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Kode Klien</label>
              <input v-model="activeClient.kode_klien" type="text" class="w-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none opacity-60 cursor-not-allowed" disabled />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                NPWP Klien <span v-if="activeClient.tipe === 'non_perorangan'" class="text-rose-500">*</span>
              </label>
              <input :value="activeClient.npwp" @input="onNpwpInput" type="text" placeholder="00.000.000.0-000.000" maxlength="21" :required="activeClient.tipe === 'non_perorangan'" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500 font-mono" />
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Kontak Utama / Nama Klien</label>
            <input v-model="activeClient.nama" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Perusahaan <span v-if="activeClient.tipe === 'non_perorangan'" class="text-slate-400">(opsional)</span></label>
            <input v-model="activeClient.perusahaan" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Email</label>
              <input v-model="activeClient.email" type="email" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Telepon</label>
              <input v-model="activeClient.telepon" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Alamat Lengkap</label>
            <textarea v-model="activeClient.alamat" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Unggah KTP</label>
              <input type="file" @change="handleKtpFileChange" accept="image/*,application/pdf" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-2xs file:font-semibold file:bg-slate-100 file:text-slate-750 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300 dark:hover:file:bg-slate-700 cursor-pointer" />
              <span v-if="activeClient.file_ktp" class="text-3xs text-emerald-500 font-semibold block mt-1">✓ KTP Terunggah</span>
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Unggah NPWP</label>
              <input type="file" @change="handleNpwpFileChange" accept="image/*,application/pdf" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-2xs file:font-semibold file:bg-slate-100 file:text-slate-750 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300 dark:hover:file:bg-slate-700 cursor-pointer" />
              <span v-if="activeClient.file_npwp" class="text-3xs text-emerald-500 font-semibold block mt-1">✓ NPWP Terunggah</span>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
