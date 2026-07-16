<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const vendors = ref<any[]>([])
const categoriesList = [
  { value: 'all', label: 'Semua Kategori' },
  { value: 'dekorasi', label: 'Dekorasi' },
  { value: 'sound_system', label: 'Sound System' },
  { value: 'lighting', label: 'Lighting' },
  { value: 'catering', label: 'Catering' },
  { value: 'venue', label: 'Venue' },
  { value: 'talent', label: 'Talent' },
  { value: 'mc', label: 'MC / Host' },
  { value: 'dokumentasi', label: 'Dokumentasi' },
  { value: 'transportasi', label: 'Transportasi' },
  { value: 'lainnya', label: 'Lainnya' }
]

const activeCategoryFilter = ref('all')
const isModalOpen = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const activeVendor = ref<any>({
  id: null, kode_vendor: '', nama_vendor: '', kategori: 'lainnya', npwp: '', email: '', telepon: '', alamat: '', file_ktp_url: null, file_npwp_url: null
})
const fileKtpInput = ref<File | null>(null)
const fileNpwpInput = ref<File | null>(null)
const isSaving = ref(false)

const fetchVendors = async () => {
  try {
    const params: any = {}
    if (activeCategoryFilter.value !== 'all') {
      params.kategori = activeCategoryFilter.value
    }
    const res = await api.get('/vendors', { params })
    vendors.value = res.data.data
  } catch (err) {
    console.error('Error fetching vendors:', err)
  }
}

watch(activeCategoryFilter, () => {
  fetchVendors()
})

onMounted(() => {
  fetchVendors()
})

const filteredVendors = () => {
  return vendors.value
}

const openCreateModal = async () => {
  modalMode.value = 'create'
  activeVendor.value = { id: null, kode_vendor: 'Loading...', nama_vendor: '', kategori: 'lainnya', npwp: '', email: '', telepon: '', alamat: '', file_ktp_url: null, file_npwp_url: null }
  fileKtpInput.value = null
  fileNpwpInput.value = null
  isModalOpen.value = true
  try {
    const res = await api.get('/vendors/next-code')
    activeVendor.value.kode_vendor = res.data.next_code
  } catch (err) {
    console.error('Error fetching next vendor code:', err)
    activeVendor.value.kode_vendor = `VND-00${vendors.value.length + 1}`
  }
}

const openEditModal = (vendor: any) => {
  modalMode.value = 'edit'
  activeVendor.value = { ...vendor }
  fileKtpInput.value = null
  fileNpwpInput.value = null
  isModalOpen.value = true
}

const handleKtpFileChange = (e: any) => {
  const files = e.target.files
  if (files && files.length > 0) {
    const file = files[0]
    if (file.size > 5 * 1024 * 1024) {
      alert('Ukuran berkas KTP melebihi batas 5MB!')
      e.target.value = ''
      fileKtpInput.value = null
      return
    }
    fileKtpInput.value = file
  }
}

const handleNpwpFileChange = (e: any) => {
  const files = e.target.files
  if (files && files.length > 0) {
    const file = files[0]
    if (file.size > 5 * 1024 * 1024) {
      alert('Ukuran berkas NPWP melebihi batas 5MB!')
      e.target.value = ''
      fileNpwpInput.value = null
      return
    }
    fileNpwpInput.value = file
  }
}

const saveVendor = async () => {
  if (isSaving.value) return
  isSaving.value = true
  try {
    const formData = new FormData()
    formData.append('kode_vendor', activeVendor.value.kode_vendor)
    formData.append('nama_vendor', activeVendor.value.nama_vendor)
    formData.append('kategori', activeVendor.value.kategori)
    formData.append('npwp', activeVendor.value.npwp || '')
    formData.append('email', activeVendor.value.email || '')
    formData.append('telepon', activeVendor.value.telepon)
    formData.append('alamat', activeVendor.value.alamat || '')
    
    if (fileKtpInput.value) {
      formData.append('file_ktp', fileKtpInput.value)
    }
    if (fileNpwpInput.value) {
      formData.append('file_npwp', fileNpwpInput.value)
    }

    if (modalMode.value === 'create') {
      await api.post('/vendors', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    } else {
      formData.append('_method', 'PUT')
      await api.post(`/vendors/${activeVendor.value.id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    }
    fetchVendors()
    isModalOpen.value = false
  } catch (err) {
    console.error('Error saving vendor:', err)
    alert('Gagal menyimpan data vendor')
  } finally {
    isSaving.value = false
  }
}

const deleteVendor = async (id: number) => {
  if (confirm('Apakah Anda yakin ingin menghapus vendor ini?')) {
    try {
      await api.delete(`/vendors/${id}`)
      fetchVendors()
    } catch (err) {
      console.error('Error deleting vendor:', err)
    }
  }
}

const viewVendorFile = (url: string, filename: string) => {
  const token = authStore.token || localStorage.getItem('token') || ''
  const baseUrl = api.defaults.baseURL || import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1'
  const cleanUrl = url.startsWith('/') ? url : '/' + url
  const fullUrl = `${baseUrl}${cleanUrl}?token=${token}`
  window.open(fullUrl, '_blank')
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Master Data Vendor</h1>
        <p class="text-xs text-slate-500 mt-1">Kelola vendor sound system, dekorasi, catering, MC, dan talent.</p>
      </div>
      <button 
        v-if="authStore.userRole !== 'staff'"
        @click="openCreateModal"
        class="px-4 py-2 bg-[#d4af37] hover:bg-[#e5c158] text-[#001b13] rounded-lg text-xs font-bold transition-colors cursor-pointer shadow-sm shadow-[#d4af37]/10"
      >
        + Tambah Vendor
      </button>
    </div>

    <!-- Filters Section -->
    <div class="flex overflow-x-auto md:flex-wrap gap-2 pt-1 pb-3 md:pb-1 mb-1 md:mb-0 -mx-4 px-4 md:mx-0 md:px-0 no-scrollbar">
      <button 
        v-for="cat in categoriesList" 
        :key="cat.value"
        @click="activeCategoryFilter = cat.value"
        :class="[
          activeCategoryFilter === cat.value ? 'bg-[#00271c] text-[#d4af37] border-[#d4af37]/30' : 'bg-white dark:bg-slate-900 text-slate-550 dark:text-slate-400 border-slate-200 dark:border-slate-800',
          'px-3 py-1.5 rounded-lg border text-2xs font-semibold uppercase tracking-wider transition-colors cursor-pointer shrink-0'
        ]"
      >
        {{ cat.label }}
      </button>
    </div>

    <!-- Table Section -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/85 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 text-slate-400 text-3xs font-bold uppercase tracking-wider">
              <th class="p-4">Kode</th>
              <th class="p-4">Vendor / Kategori</th>
              <th class="p-4">NPWP</th>
              <th class="p-4">Kontak</th>
              <th class="p-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-sm">
            <tr v-for="vendor in filteredVendors()" :key="vendor.id" class="hover:bg-slate-50/40 dark:hover:bg-slate-800/10 text-slate-700 dark:text-slate-300 transition-colors">
              <td class="p-4 font-mono text-xs text-slate-400">{{ vendor.kode_vendor }}</td>
              <td class="p-4">
                <span class="font-bold text-slate-900 dark:text-white block">{{ vendor.nama_vendor }}</span>
                <span class="text-3xs uppercase font-semibold text-slate-400 tracking-wider inline-block mt-1 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">{{ vendor.kategori }}</span>
              </td>
              <td class="p-4 text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="block">{{ vendor.npwp || 'Tidak Ada NPWP' }}</span>
                <div class="flex items-center space-x-2 mt-1.5" v-if="vendor.file_ktp_url || vendor.file_npwp_url">
                  <button v-if="vendor.file_ktp_url" @click="viewVendorFile(vendor.file_ktp_url, 'KTP_' + vendor.nama_vendor)" class="text-3xs text-emerald-500 hover:text-emerald-400 font-bold uppercase tracking-wider flex items-center bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer">
                    📄 KTP
                  </button>
                  <button v-if="vendor.file_npwp_url" @click="viewVendorFile(vendor.file_npwp_url, 'NPWP_' + vendor.nama_vendor)" class="text-3xs text-emerald-500 hover:text-emerald-400 font-bold uppercase tracking-wider flex items-center bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer">
                    📄 NPWP
                  </button>
                </div>
              </td>
              <td class="p-4">
                <span class="block text-xs font-medium">{{ vendor.email || '-' }}</span>
                <span class="block text-2xs text-slate-400 mt-0.5">{{ vendor.telepon }}</span>
              </td>
              <td class="p-4 text-right space-x-2">
                <button 
                  v-if="authStore.userRole !== 'staff'"
                  @click="openEditModal(vendor)"
                  class="text-xs bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 px-2.5 py-1.5 rounded font-semibold transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  v-if="authStore.userRole === 'owner'"
                  @click="deleteVendor(vendor.id)"
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

    <!-- Edit/Create Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
          {{ modalMode === 'create' ? 'Tambah Vendor Baru' : 'Edit Detail Vendor' }}
        </h3>
        <form @submit.prevent="saveVendor" class="space-y-3.5 text-xs text-slate-800 dark:text-slate-200">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Kode Vendor</label>
              <input v-model="activeVendor.kode_vendor" type="text" class="w-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none opacity-60 cursor-not-allowed text-slate-750 dark:text-slate-400" disabled />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Kategori Jasa</label>
              <select v-model="activeVendor.kategori" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100">
                <option v-for="cat in categoriesList.slice(1)" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Nama Vendor</label>
              <input v-model="activeVendor.nama_vendor" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">NPWP Vendor (untuk PPh 23)</label>
              <input v-model="activeVendor.npwp" type="text" placeholder="00.000.000.0-000.000" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Email</label>
              <input v-model="activeVendor.email" type="email" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100" />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Telepon</label>
              <input v-model="activeVendor.telepon" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100" required />
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Alamat Kantor/Studio</label>
            <textarea v-model="activeVendor.alamat" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Unggah KTP (PDF/Gambar)</label>
              <input type="file" @change="handleKtpFileChange" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-xs outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100 file:bg-slate-100 file:border-0 file:rounded file:text-2xs file:font-semibold dark:file:bg-slate-800 dark:file:text-slate-300" />
              <span v-if="activeVendor.file_ktp_url" class="text-3xs text-slate-550 dark:text-slate-400 mt-1 block font-semibold">
                Sudah terunggah: <button @click.prevent="viewVendorFile(activeVendor.file_ktp_url, 'KTP_' + activeVendor.nama_vendor)" class="text-emerald-600 hover:text-emerald-500 font-bold underline cursor-pointer">Lihat File</button>
              </span>
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Unggah Kartu NPWP (PDF/Gambar)</label>
              <input type="file" @change="handleNpwpFileChange" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-xs outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100 file:bg-slate-100 file:border-0 file:rounded file:text-2xs file:font-semibold dark:file:bg-slate-800 dark:file:text-slate-300" />
              <span v-if="activeVendor.file_npwp_url" class="text-3xs text-slate-550 dark:text-slate-400 mt-1 block font-semibold">
                Sudah terunggah: <button @click.prevent="viewVendorFile(activeVendor.file_npwp_url, 'NPWP_' + activeVendor.nama_vendor)" class="text-emerald-600 hover:text-emerald-500 font-bold underline cursor-pointer">Lihat File</button>
              </span>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" :disabled="isSaving" @click="isModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-4 py-2 bg-[#d4af37] hover:bg-[#e5c158] text-[#001b13] rounded-lg font-bold text-xs cursor-pointer flex items-center justify-center space-x-1.5 disabled:opacity-60 disabled:cursor-not-allowed shadow-sm shadow-[#d4af37]/10">
              <svg v-if="isSaving" class="animate-spin h-3.5 w-3.5 text-[#001b13]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ isSaving ? 'Menyimpan...' : 'Simpan' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
/* Hide scrollbar for IE, Edge and Firefox */
.no-scrollbar {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>
