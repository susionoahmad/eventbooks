<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const props = defineProps({
  id: {
    type: String,
    required: true
  }
})

// Active tab
const activeTab = ref<'detail' | 'rab' | 'transactions' | 'documents'>('rab')

// Event reactive state initialized with placeholders to prevent mount exceptions
const event = ref<any>({
  id: Number(props.id),
  nomor_event: '',
  nama_event: '',
  jenis_event: '',
  tanggal_mulai: '',
  tanggal_selesai: '',
  lokasi: '',
  nilai_kontrak: 0,
  status: 'draft',
  client: {
    nama: '',
    perusahaan: '',
    npwp: '',
    email: '',
    telepon: ''
  }
})

// RAB items state
const rabItems = ref<any[]>([])

// Documents state and methods
const documents = ref<any[]>([])
const isDocModalOpen = ref(false)
const docForm = ref({
  nama_dokumen: '',
  tipe_dokumen: 'kontrak',
  file: null as File | null
})

const fetchDocuments = async () => {
  try {
    const res = await api.get(`/events/${props.id}/documents`)
    documents.value = res.data.items
  } catch (err) {
    console.error('Error fetching documents:', err)
  }
}

const openDocModal = () => {
  docForm.value = {
    nama_dokumen: '',
    tipe_dokumen: 'kontrak',
    file: null
  }
  isDocModalOpen.value = true
}

const handleFileChange = (e: any) => {
  const files = e.target.files
  if (files && files.length > 0) {
    docForm.value.file = files[0]
    if (!docForm.value.nama_dokumen) {
      docForm.value.nama_dokumen = files[0].name.replace(/\.[^/.]+$/, "")
    }
  }
}

const uploadDocument = async () => {
  if (!docForm.value.file) {
    alert('Silakan pilih file terlebih dahulu')
    return
  }

  const formData = new FormData()
  formData.append('nama_dokumen', docForm.value.nama_dokumen)
  formData.append('tipe_dokumen', docForm.value.tipe_dokumen)
  formData.append('file', docForm.value.file)

  try {
    await api.post(`/events/${props.id}/documents`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    fetchDocuments()
    isDocModalOpen.value = false
  } catch (err) {
    console.error('Error uploading document:', err)
    alert('Gagal mengunggah berkas')
  }
}

const downloadDocument = async (doc: any) => {
  try {
    const res = await api.get(`/events/${props.id}/documents/${doc.id}/download`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', doc.nama_dokumen)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (err) {
    console.error('Error downloading document:', err)
    alert('Gagal mengunduh berkas')
  }
}

const deleteDocument = async (docId: number) => {
  if (confirm('Hapus berkas lampiran ini?')) {
    try {
      await api.delete(`/events/${props.id}/documents/${docId}`)
      fetchDocuments()
    } catch (err) {
      console.error('Error deleting document:', err)
      alert('Gagal menghapus berkas')
    }
  }
}

const formatBytes = (bytes: number, decimals = 1) => {
  if (!bytes) return '0 Bytes'
  const k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
}

const getDocTypeName = (type: string) => {
  const types: Record<string, string> = {
    kontrak: 'Surat Kontrak',
    invoice: 'Invoice',
    kwitansi: 'Kwitansi',
    faktur_pajak: 'Faktur Pajak',
    bukti_transfer: 'Bukti Transfer'
  }
  return types[type] || type
}

const fetchEventAndRab = async () => {
  try {
    const resEvent = await api.get(`/events/${props.id}`)
    event.value = resEvent.data.data
    
    const resRab = await api.get(`/events/${props.id}/rab`)
    rabItems.value = resRab.data.items.map((item: any) => ({
      id: item.id,
      kategori: item.kategori,
      deskripsi: item.deskripsi,
      qty: item.qty,
      harga: item.harga,
      subtotal: item.subtotal,
      aktual: item.aktual_terbayar || 0
    }))
  } catch (err) {
    console.error('Error fetching event data:', err)
  }
}

onMounted(() => {
  fetchEventAndRab()
  fetchDocuments()
})

// RAB Calculations
const totalRabBudget = computed(() => {
  return rabItems.value.reduce((sum, item) => sum + item.subtotal, 0)
})

const totalRealisasi = computed(() => {
  return rabItems.value.reduce((sum, item) => sum + item.aktual, 0)
})

const profitMargin = computed(() => {
  return event.value.nilai_kontrak - totalRabBudget.value
})

const marginPercentage = computed(() => {
  if (event.value.nilai_kontrak <= 0) return '0.00'
  return ((profitMargin.value / event.value.nilai_kontrak) * 100).toFixed(2)
})

// Modal add RAB item
const isRabModalOpen = ref(false)
const newRabItem = ref({
  kategori: 'Dekorasi', deskripsi: '', qty: 1, harga: 0
})

const openRabModal = () => {
  newRabItem.value = { kategori: 'Dekorasi', deskripsi: '', qty: 1, harga: 0 }
  isRabModalOpen.value = true
}

const addRabItem = async () => {
  try {
    await api.post(`/events/${props.id}/rab`, newRabItem.value)
    fetchEventAndRab()
    isRabModalOpen.value = false
  } catch (err) {
    console.error('Error adding budget line:', err)
  }
}

const deleteRabItem = async (id: number) => {
  if (confirm('Hapus item anggaran ini?')) {
    try {
      await api.delete(`/events/${props.id}/rab/${id}`)
      fetchEventAndRab()
    } catch (err) {
      console.error('Error deleting budget line:', err)
    }
  }
}

const formatIDR = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value)
}

// Edit Event Modal State
const isEditModalOpen = ref(false)
const editForm = ref({
  nomor_event: '',
  nama_event: '',
  jenis_event: '',
  tanggal_mulai: '',
  tanggal_selesai: '',
  lokasi: '',
  nilai_kontrak: 0,
  status: '',
  client_id: null as number | null
})

const openEditModal = () => {
  editForm.value = {
    nomor_event: event.value.nomor_event,
    nama_event: event.value.nama_event,
    jenis_event: event.value.jenis_event || 'Wedding',
    tanggal_mulai: event.value.tanggal_mulai,
    tanggal_selesai: event.value.tanggal_selesai,
    lokasi: event.value.lokasi,
    nilai_kontrak: event.value.nilai_kontrak,
    status: event.value.status,
    client_id: event.value.client_id || event.value.client?.id || null
  }
  isEditModalOpen.value = true
}

const updateEvent = async () => {
  try {
    const res = await api.put(`/events/${props.id}`, editForm.value)
    event.value = res.data.data
    isEditModalOpen.value = false
    fetchEventAndRab()
  } catch (err) {
    console.error('Error updating event:', err)
    alert('Gagal memperbarui event')
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header/Workspace Navbar -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 text-white relative overflow-hidden shadow">
      <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl"></div>
      
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
          <div class="flex items-center space-x-2">
            <span class="text-3xs uppercase font-bold tracking-wider px-2 py-0.5 bg-slate-800 border border-slate-700 rounded text-slate-400 font-mono">{{ event.nomor_event }}</span>
            <span class="text-3xs uppercase font-extrabold px-2 py-0.5 bg-emerald-950 text-emerald-400 border border-emerald-800 rounded tracking-wider">{{ event.status }}</span>
          </div>
          <h2 class="text-xl font-bold mt-2.5">{{ event.nama_event }}</h2>
          <p class="text-xs text-slate-400 mt-1 flex items-center">
            <span class="mr-3">📍 {{ event.lokasi }}</span>
            <span>📅 {{ event.tanggal_mulai }} s/d {{ event.tanggal_selesai }}</span>
          </p>
        </div>
        <div class="text-left md:text-right">
          <span class="text-3xs uppercase font-bold text-slate-500 tracking-wider block">Nilai Kontrak Klien</span>
          <span class="text-lg font-extrabold text-slate-100">{{ formatIDR(event.nilai_kontrak) }}</span>
        </div>
      </div>

      <!-- Tab Buttons -->
      <div class="flex space-x-1 border-t border-slate-800/80 pt-4 mt-6">
        <button 
          @click="activeTab = 'detail'"
          :class="[activeTab === 'detail' ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40', 'px-4 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer']"
        >
          Detail Event
        </button>
        <button 
          @click="activeTab = 'rab'"
          :class="[activeTab === 'rab' ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40', 'px-4 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer']"
        >
          RAB Anggaran
        </button>
        <button 
          @click="activeTab = 'documents'"
          :class="[activeTab === 'documents' ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40', 'px-4 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer']"
        >
          Berkas Lampiran ({{ documents.length }})
        </button>
      </div>
    </div>

    <!-- Active Tab Content -->
    <!-- Tab 1: Detail -->
    <div v-if="activeTab === 'detail'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm md:col-span-2 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
          <h3 class="font-bold text-slate-900 dark:text-white">Informasi Utama</h3>
          <button @click="openEditModal" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-800 dark:border-slate-700 text-white rounded-lg text-2xs font-bold transition-colors cursor-pointer">
            Edit Event
          </button>
        </div>
        <div class="grid grid-cols-2 gap-4 text-xs">
          <div>
            <span class="text-slate-450 block font-semibold mb-0.5">Nama Event</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ event.nama_event }}</span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold mb-0.5">Jenis Event</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ event.jenis_event }}</span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold mb-0.5">Tanggal Mulai</span>
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ event.tanggal_mulai }}</span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold mb-0.5">Tanggal Selesai</span>
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ event.tanggal_selesai }}</span>
          </div>
          <div class="col-span-2">
            <span class="text-slate-450 block font-semibold mb-0.5">Lokasi Venue</span>
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ event.lokasi }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-4">
        <h3 class="font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">Informasi Klien</h3>
        <div class="text-xs space-y-2.5">
          <div>
            <span class="text-slate-450 block font-semibold">Nama Klien</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ event.client.nama }}</span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold">Perusahaan</span>
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ event.client.perusahaan }}</span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold">NPWP Klien</span>
            <span class="font-mono text-slate-700 dark:text-slate-400">{{ event.client.npwp || 'Tidak ada NPWP' }}</span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold">Email</span>
            <span class="font-medium text-slate-900 dark:text-slate-350">{{ event.client.email || 'Tidak ada Email' }}</span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold">Telepon</span>
            <span class="font-medium text-slate-900 dark:text-slate-350">{{ event.client.telepon || 'Tidak ada Telepon' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 2: RAB Budget Builder -->
    <div v-if="activeTab === 'rab'" class="space-y-6">
      <!-- RAB Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 rounded-xl shadow-xs">
          <span class="text-3xs uppercase font-bold text-slate-400 tracking-wider">Nilai Kontrak</span>
          <span class="text-lg font-bold text-slate-800 dark:text-white block mt-1">{{ formatIDR(event.nilai_kontrak) }}</span>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 rounded-xl shadow-xs">
          <span class="text-3xs uppercase font-bold text-slate-400 tracking-wider">Anggaran RAB</span>
          <span class="text-lg font-bold text-slate-800 dark:text-white block mt-1">{{ formatIDR(totalRabBudget) }}</span>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 rounded-xl shadow-xs">
          <span class="text-3xs uppercase font-bold text-slate-400 tracking-wider">Realisasi Biaya</span>
          <span class="text-lg font-bold text-amber-500 block mt-1">{{ formatIDR(totalRealisasi) }}</span>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 rounded-xl shadow-xs">
          <span class="text-3xs uppercase font-bold text-slate-400 tracking-wider">Sisa / Laba (RAB)</span>
          <span class="text-lg font-bold text-emerald-500 block mt-1">
            {{ formatIDR(profitMargin) }} <span class="text-2xs font-semibold uppercase bg-emerald-950 text-emerald-400 px-1 py-0.5 rounded border border-emerald-800/40 ml-1">{{ marginPercentage }}%</span>
          </span>
        </div>
      </div>

      <!-- RAB Table -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800">
          <h3 class="font-bold text-slate-900 dark:text-white text-sm">Rincian Rencana Anggaran Biaya (RAB)</h3>
          <button @click="openRabModal" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-800 dark:border-slate-700 text-white rounded-lg text-2xs font-bold transition-colors cursor-pointer">
            + Tambah Item Anggaran
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 text-slate-400 text-3xs font-bold uppercase tracking-wider">
                <th class="p-4">Kategori</th>
                <th class="p-4">Deskripsi</th>
                <th class="p-4 text-center">Qty</th>
                <th class="p-4">Harga Satuan</th>
                <th class="p-4">Subtotal</th>
                <th class="p-4">Realisasi Aktual</th>
                <th class="p-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-sm">
              <tr v-for="item in rabItems" :key="item.id" class="text-slate-700 dark:text-slate-350 hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                <td class="p-4 font-semibold text-xs">{{ item.kategori }}</td>
                <td class="p-4 text-xs font-medium">{{ item.deskripsi }}</td>
                <td class="p-4 text-center font-mono text-xs">{{ item.qty }}</td>
                <td class="p-4">{{ formatIDR(item.harga) }}</td>
                <td class="p-4 font-bold text-slate-900 dark:text-white">{{ formatIDR(item.subtotal) }}</td>
                <td class="p-4">
                  <span :class="[item.aktual > 0 ? 'text-amber-500 font-semibold' : 'text-slate-400', 'text-xs']">
                    {{ item.aktual > 0 ? formatIDR(item.aktual) : 'Belum Realisasi' }}
                  </span>
                </td>
                <td class="p-4 text-right">
                  <button @click="deleteRabItem(item.id)" class="text-rose-450 hover:text-rose-400 p-1 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tab 3: Documents -->
    <div v-if="activeTab === 'documents'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
        <h3 class="font-bold text-slate-900 dark:text-white text-sm">Dokumen & Lampiran Legal</h3>
        <button @click="openDocModal" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-2xs font-bold transition-colors cursor-pointer">
          + Upload Berkas
        </button>
      </div>

      <div v-if="documents.length === 0" class="text-center py-10 text-xs text-slate-500 dark:text-slate-400">
        Belum ada berkas lampiran untuk event ini. Klik "+ Upload Berkas" untuk menambahkan.
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div v-for="doc in documents" :key="doc.id" class="border border-slate-200 dark:border-slate-800 p-4 rounded-xl flex items-center justify-between hover:border-slate-350 dark:hover:border-slate-700 transition-colors">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-slate-50 dark:bg-slate-800 rounded-lg flex items-center justify-center text-slate-450">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
              </svg>
            </div>
            <div>
              <span class="font-bold text-xs text-slate-900 dark:text-white block">{{ doc.nama_dokumen }}</span>
              <span class="text-3xs text-slate-400 font-semibold tracking-wider block mt-0.5">
                {{ getDocTypeName(doc.tipe_dokumen) }} &bull; {{ formatBytes(doc.file_size) }}
              </span>
              <span class="text-4xs text-slate-500 block mt-0.5">
                Oleh {{ doc.uploaded_by }} &bull; {{ new Date(doc.created_at).toLocaleDateString('id-ID') }}
              </span>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <button @click="downloadDocument(doc)" class="text-emerald-500 hover:text-emerald-400 text-xs font-semibold cursor-pointer">Unduh</button>
            <span class="text-slate-300 dark:text-slate-700 text-xs">&bull;</span>
            <button @click="deleteDocument(doc.id)" class="text-rose-500 hover:text-rose-400 text-xs font-semibold cursor-pointer">Hapus</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Anggaran Item -->
    <div v-if="isRabModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isRabModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Tambah Anggaran RAB</h3>

        <form @submit.prevent="addRabItem" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-350">
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Kategori Biaya</label>
            <select v-model="newRabItem.kategori" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
              <option value="Catering">Catering</option>
              <option value="Dekorasi">Dekorasi</option>
              <option value="Sound System">Sound System</option>
              <option value="Lighting">Lighting</option>
              <option value="Venue">Venue</option>
              <option value="Talent">Talent</option>
              <option value="MC / Host">MC / Host</option>
              <option value="Dokumentasi">Dokumentasi</option>
              <option value="Operasional">Operasional</option>
            </select>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Deskripsi Item</label>
            <input v-model="newRabItem.deskripsi" type="text" placeholder="e.g. Sewa LED Par Lights 20 unit" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Qty</label>
              <input v-model="newRabItem.qty" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Harga Satuan (Rp)</label>
              <input v-model="newRabItem.harga" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isRabModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-655 dark:text-slate-350 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Tambahkan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Upload Berkas -->
    <div v-if="isDocModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isDocModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Upload Berkas Lampiran</h3>

        <form @submit.prevent="uploadDocument" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-350">
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pilih File</label>
            <input type="file" @change="handleFileChange" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Dokumen</label>
            <input v-model="docForm.nama_dokumen" type="text" placeholder="e.g. Surat Perjanjian Kerjasama" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Jenis Dokumen</label>
            <select v-model="docForm.tipe_dokumen" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
              <option value="kontrak">Surat Kontrak</option>
              <option value="invoice">Invoice</option>
              <option value="kwitansi">Kwitansi</option>
              <option value="faktur_pajak">Faktur Pajak</option>
              <option value="bukti_transfer">Bukti Transfer</option>
            </select>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isDocModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-655 dark:text-slate-350 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Unggah</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Edit Event -->
    <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isEditModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Detail Event</h3>

        <form @submit.prevent="updateEvent" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-350">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nomor Event</label>
              <input v-model="editForm.nomor_event" type="text" class="w-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none opacity-60 cursor-not-allowed" disabled />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Jenis Event</label>
              <select v-model="editForm.jenis_event" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
                <option value="Wedding">Wedding</option>
                <option value="Concert">Concert</option>
                <option value="Corporate Event">Corporate Event</option>
                <option value="Agency Expo">Agency Expo</option>
                <option value="Lainnya">Lainnya</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Event</label>
            <input v-model="editForm.nama_event" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Mulai</label>
              <input v-model="editForm.tanggal_mulai" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Selesai</label>
              <input v-model="editForm.tanggal_selesai" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Lokasi Venue</label>
            <input v-model="editForm.lokasi" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nilai Kontrak Klien (Rp)</label>
              <input v-model="editForm.nilai_kontrak" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Status Event</label>
              <select 
                v-model="editForm.status" 
                :disabled="authStore.userRole === 'staff'"
                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed"
              >
                <option value="draft">Draft</option>
                <option value="negosiasi">Negosiasi</option>
                <option value="dp">DP Terbayar</option>
                <option value="berjalan">Sedang Berjalan</option>
                <option value="selesai">Selesai</option>
                <option value="batal">Dibatalkan</option>
              </select>
              <span v-if="authStore.userRole === 'staff'" class="text-3xs text-rose-500 mt-1 block font-semibold">Staff tidak dapat mengubah status.</span>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isEditModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-655 dark:text-slate-350 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
