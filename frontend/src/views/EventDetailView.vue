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
const activeTab = ref<'detail' | 'rab' | 'transactions' | 'documents' | 'tasks' | 'invitation'>('rab')

// Invitation states
const invitationForm = ref({
  title: '',
  date_time_info: '',
  maps_url: '',
  is_custom_template: false,
  preset_template: 'classic',
  background_image: null as File | null,
  background_color: '#ffffff',
  primary_color: '#1a1a1a',
  accent_color: '#4f46e5',
  text_color: '#1a1a1a',
  button_text_color: '#ffffff',
  font_family: 'Inter',
  maps_btn_top: 72,
  maps_btn_left: 15,
  maps_btn_width: 70,
  maps_btn_height: 6,
  maps_btn_text: 'Buka Google Maps'
})

const isCustomImagePreview = ref<string | null>(null)
const isSavingInvitation = ref(false)
const invitationLinkCopied = ref(false)

const fetchInvitation = async () => {
  try {
    const res = await api.get(`/events/${props.id}/invitation?t=${Date.now()}`)
    const invData = res.data.data
    invitationForm.value.title = invData.title || ''
    invitationForm.value.date_time_info = invData.date_time_info || ''
    invitationForm.value.maps_url = invData.maps_url || ''
    invitationForm.value.is_custom_template = !!invData.is_custom_template
    invitationForm.value.preset_template = invData.preset_template || 'classic'
    invitationForm.value.background_color = invData.background_color || '#ffffff'
    invitationForm.value.primary_color = invData.primary_color || '#1a1a1a'
    invitationForm.value.accent_color = invData.accent_color || '#4f46e5'
    invitationForm.value.text_color = invData.text_color || '#1a1a1a'
    invitationForm.value.button_text_color = invData.button_text_color || '#ffffff'
    invitationForm.value.font_family = invData.font_family || 'Inter'
    invitationForm.value.maps_btn_top = invData.maps_btn_top !== undefined && invData.maps_btn_top !== null ? parseFloat(invData.maps_btn_top) : 72
    invitationForm.value.maps_btn_left = invData.maps_btn_left !== undefined && invData.maps_btn_left !== null ? parseFloat(invData.maps_btn_left) : 15
    invitationForm.value.maps_btn_width = invData.maps_btn_width !== undefined && invData.maps_btn_width !== null ? parseFloat(invData.maps_btn_width) : 70
    invitationForm.value.maps_btn_height = invData.maps_btn_height !== undefined && invData.maps_btn_height !== null ? parseFloat(invData.maps_btn_height) : 6
    invitationForm.value.maps_btn_text = invData.maps_btn_text || 'Buka Google Maps'
    
    if (invData.template_background_url) {
      isCustomImagePreview.value = invData.template_background_url
    } else {
      isCustomImagePreview.value = null
    }
    
    if (invitationForm.value.font_family) {
      loadGoogleFont(invitationForm.value.font_family)
    }
  } catch (err) {
    console.error('Error fetching invitation settings:', err)
  }
}

const handleBgFileChange = (e: any) => {
  const files = e.target.files
  if (files && files.length > 0) {
    invitationForm.value.background_image = files[0]
    isCustomImagePreview.value = URL.createObjectURL(files[0])
  }
}

const selectPresetColors = (presetName: string) => {
  const presets: Record<string, any> = {
    classic: { bg: '#ffffff', primary: '#1a1a1a', accent: '#4f46e5', text: '#1a1a1a', btnText: '#ffffff', font: 'Inter' },
    elegant: { bg: '#111827', primary: '#f9fafb', accent: '#d4af37', text: '#f9fafb', btnText: '#111827', font: 'Playfair Display' },
    floral: { bg: '#fdf2f8', primary: '#831843', accent: '#db2777', text: '#831843', btnText: '#ffffff', font: 'Sacramento' },
    modern: { bg: '#f3f4f6', primary: '#111827', accent: '#06b6d4', text: '#111827', btnText: '#ffffff', font: 'Montserrat' },
    sunset: { bg: '#fff7ed', primary: '#7c2d12', accent: '#ea580c', text: '#7c2d12', btnText: '#ffffff', font: 'Lora' },
    rustic_elegance: { bg: '#fafaf9', primary: '#44403c', accent: '#78716c', text: '#44403c', btnText: '#ffffff', font: 'Playfair Display' },
    cyber_neon: { bg: '#030712', primary: '#f3f4f6', accent: '#ec4899', text: '#f3f4f6', btnText: '#ffffff', font: 'Montserrat' },
    royal_corporate: { bg: '#0f172a', primary: '#f8fafc', accent: '#2563eb', text: '#f8fafc', btnText: '#ffffff', font: 'Cinzel' },
    warm_leafy: { bg: '#f0fdf4', primary: '#14532d', accent: '#16a34a', text: '#14532d', btnText: '#ffffff', font: 'Lora' }
  }
  
  const selected = presets[presetName] || presets.classic
  invitationForm.value.background_color = selected.bg
  invitationForm.value.primary_color = selected.primary
  invitationForm.value.accent_color = selected.accent
  invitationForm.value.text_color = selected.text
  invitationForm.value.button_text_color = selected.btnText
  invitationForm.value.font_family = selected.font
  
  loadGoogleFont(selected.font)
}

const loadGoogleFont = (fontName: string) => {
  if (!fontName || fontName === 'Inter') return
  const fontId = 'dynamic-invitation-font-' + fontName.replace(/\s+/g, '-').toLowerCase()
  if (document.getElementById(fontId)) return
  
  const link = document.createElement('link')
  link.id = fontId
  link.rel = 'stylesheet'
  link.href = `https://fonts.googleapis.com/css2?family=${fontName.replace(/\s+/g, '+')}:wght@400;700&display=swap`
  document.head.appendChild(link)
}

const saveInvitation = async () => {
  isSavingInvitation.value = true
  const formData = new FormData()
  formData.append('title', invitationForm.value.title)
  formData.append('date_time_info', invitationForm.value.date_time_info)
  formData.append('maps_url', invitationForm.value.maps_url || '')
  formData.append('is_custom_template', invitationForm.value.is_custom_template ? '1' : '0')
  formData.append('preset_template', invitationForm.value.preset_template)
  formData.append('font_family', invitationForm.value.font_family)
  formData.append('maps_btn_text', invitationForm.value.maps_btn_text || 'Buka Google Maps')
  
  if (invitationForm.value.is_custom_template) {
    formData.append('maps_btn_top', String(invitationForm.value.maps_btn_top))
    formData.append('maps_btn_left', String(invitationForm.value.maps_btn_left))
    formData.append('maps_btn_width', String(invitationForm.value.maps_btn_width))
    formData.append('maps_btn_height', String(invitationForm.value.maps_btn_height))
    if (invitationForm.value.background_image) {
      formData.append('background_image', invitationForm.value.background_image)
    }
  } else {
    formData.append('background_color', invitationForm.value.background_color)
    formData.append('primary_color', invitationForm.value.primary_color)
    formData.append('accent_color', invitationForm.value.accent_color)
    formData.append('text_color', invitationForm.value.text_color)
    formData.append('button_text_color', invitationForm.value.button_text_color)
  }

  try {
    await api.post(`/events/${props.id}/invitation`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    alert('Desain undangan berhasil disimpan!')
    await fetchInvitation()
  } catch (err) {
    console.error('Error saving invitation:', err)
    alert('Gagal menyimpan desain undangan.')
  } finally {
    isSavingInvitation.value = false
  }
}

const publicInvitationUrl = computed(() => window.location.origin + '/invitation/' + props.id)

const copyInvitationLink = () => {
  navigator.clipboard.writeText(publicInvitationUrl.value).then(() => {
    invitationLinkCopied.value = true
    setTimeout(() => {
      invitationLinkCopied.value = false
    }, 2000)
  })
}

// Event reactive state initialized with placeholders to prevent mount exceptions
const event = ref<any>({
  id: Number(props.id),
  nomor_event: '',
  nama_event: '',
  jenis_event: '',
  kategori: 'medium',
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
  fetchTasks()
  fetchInvitation()
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
  kategori: 'medium',
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
    kategori: event.value.kategori || 'medium',
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

// Tasks state and methods
const tasks = ref<any[]>([])
const isTaskModalOpen = ref(false)
const isEditTask = ref(false)
const currentTaskId = ref<number | null>(null)
const taskForm = ref({
  nama_task: '',
  pic: '',
  target_date: '',
  status: 'pending',
  keterangan: ''
})

const fetchTasks = async () => {
  try {
    const res = await api.get(`/events/${props.id}/tasks`)
    tasks.value = res.data.items
  } catch (err) {
    console.error('Error fetching tasks:', err)
  }
}

const openAddTaskModal = () => {
  isEditTask.value = false
  currentTaskId.value = null
  taskForm.value = {
    nama_task: '',
    pic: '',
    target_date: '',
    status: 'pending',
    keterangan: ''
  }
  isTaskModalOpen.value = true
}

const openEditTaskModal = (task: any) => {
  isEditTask.value = true
  currentTaskId.value = task.id
  taskForm.value = {
    nama_task: task.nama_task,
    pic: task.pic || '',
    target_date: task.target_date || '',
    status: task.status,
    keterangan: task.keterangan || ''
  }
  isTaskModalOpen.value = true
}

const saveTask = async () => {
  try {
    if (isEditTask.value && currentTaskId.value) {
      await api.put(`/events/${props.id}/tasks/${currentTaskId.value}`, taskForm.value)
    } else {
      await api.post(`/events/${props.id}/tasks`, taskForm.value)
    }
    fetchTasks()
    isTaskModalOpen.value = false
  } catch (err) {
    console.error('Error saving task:', err)
    alert('Gagal menyimpan tugas')
  }
}

const deleteTask = async (taskId: number) => {
  if (confirm('Hapus tugas ini?')) {
    try {
      await api.delete(`/events/${props.id}/tasks/${taskId}`)
      fetchTasks()
    } catch (err) {
      console.error('Error deleting task:', err)
      alert('Gagal menghapus tugas')
    }
  }
}

const toggleTaskStatus = async (task: any) => {
  const nextStatusMap: Record<string, string> = {
    pending: 'in_progress',
    in_progress: 'completed',
    completed: 'pending'
  }
  const nextStatus = nextStatusMap[task.status] || 'pending'
  
  try {
    await api.put(`/events/${props.id}/tasks/${task.id}`, {
      nama_task: task.nama_task,
      pic: task.pic,
      target_date: task.target_date,
      keterangan: task.keterangan,
      status: nextStatus
    })
    fetchTasks()
  } catch (err) {
    console.error('Error updating task status:', err)
    alert('Gagal memperbarui status tugas')
  }
}

const getTaskStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'Belum Mulai',
    in_progress: 'Dalam Proses',
    completed: 'Selesai'
  }
  return labels[status] || status
}

const getTaskStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
    in_progress: 'bg-amber-50 text-amber-700 dark:bg-amber-950/45 dark:text-amber-400 border-amber-250 dark:border-amber-900/50',
    completed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/45 dark:text-emerald-400 border-emerald-250 dark:border-emerald-900/50'
  }
  return classes[status] || ''
}

const isOverdue = (dateString: string) => {
  if (!dateString) return false
  const target = new Date(dateString)
  target.setHours(23, 59, 59, 999)
  return target < new Date()
}

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
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
            <span v-if="event.kategori" class="text-3xs uppercase font-extrabold px-2 py-0.5 bg-indigo-950 text-indigo-400 border border-indigo-800 rounded tracking-wider">Kategori: {{ event.kategori }}</span>
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
      <div class="flex overflow-x-auto no-scrollbar space-x-1 border-t border-slate-800/80 pt-4 mt-6 pb-1">
        <button 
          @click="activeTab = 'detail'"
          :class="[activeTab === 'detail' ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40', 'px-4 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer shrink-0']"
        >
          Detail Event
        </button>
        <button 
          @click="activeTab = 'rab'"
          :class="[activeTab === 'rab' ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40', 'px-4 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer shrink-0']"
        >
          RAB Anggaran
        </button>
        <button 
          @click="activeTab = 'documents'"
          :class="[activeTab === 'documents' ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40', 'px-4 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer shrink-0']"
        >
          Berkas Lampiran ({{ documents.length }})
        </button>
        <button 
          @click="activeTab = 'tasks'"
          :class="[activeTab === 'tasks' ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40', 'px-4 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer shrink-0']"
        >
          Daftar Tugas ({{ tasks.length }})
        </button>
        <button 
          @click="activeTab = 'invitation'"
          :class="[activeTab === 'invitation' ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40', 'px-4 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer shrink-0']"
        >
          Desain Undangan
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
        <div class="grid grid-cols-3 gap-4 text-xs">
          <div>
            <span class="text-slate-450 block font-semibold mb-0.5">Nama Event</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ event.nama_event }}</span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold mb-0.5">Jenis Event</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ event.jenis_event }}</span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold mb-0.5">Kategori</span>
            <span class="font-bold uppercase text-slate-800 dark:text-slate-200">
              <span v-if="event.kategori === 'small'" class="px-2 py-0.5 text-3xs font-bold rounded bg-blue-100/80 text-blue-800 dark:bg-blue-950/50 dark:text-blue-400 border border-blue-200/50 dark:border-blue-900/50">Small</span>
              <span v-else-if="event.kategori === 'large'" class="px-2 py-0.5 text-3xs font-bold rounded bg-rose-100/80 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400 border border-rose-200/50 dark:border-rose-900/50">Large</span>
              <span v-else class="px-2 py-0.5 text-3xs font-bold rounded bg-emerald-100/80 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-900/50">Medium</span>
            </span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold mb-0.5">Tanggal Mulai</span>
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ event.tanggal_mulai }}</span>
          </div>
          <div>
            <span class="text-slate-450 block font-semibold mb-0.5">Tanggal Selesai</span>
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ event.tanggal_selesai }}</span>
          </div>
          <div></div>
          <div class="col-span-3">
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

    <!-- Tab 4: Tasks (Daftar Tugas) -->
    <div v-if="activeTab === 'tasks'" class="space-y-6">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800">
          <h3 class="font-bold text-slate-900 dark:text-white text-sm">Daftar Tugas Event</h3>
          <button @click="openAddTaskModal" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-800 dark:border-slate-700 text-white rounded-lg text-2xs font-bold transition-colors cursor-pointer">
            + Tambah Tugas
          </button>
        </div>

        <div v-if="tasks.length === 0" class="text-center py-10 text-xs text-slate-500 dark:text-slate-400">
          Belum ada tugas untuk event ini. Klik "+ Tambah Tugas" untuk membuat tugas baru.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 text-slate-400 text-3xs font-bold uppercase tracking-wider">
                <th class="p-4 w-28 text-center">Status</th>
                <th class="p-4">Nama Tugas</th>
                <th class="p-4">PIC</th>
                <th class="p-4">Target Selesai</th>
                <th class="p-4">Keterangan</th>
                <th class="p-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-sm">
              <tr v-for="task in tasks" :key="task.id" class="text-slate-700 dark:text-slate-350 hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                <td class="p-4 text-center">
                  <button @click="toggleTaskStatus(task)" class="focus:outline-none cursor-pointer" title="Klik untuk mengubah status">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-semibold border" :class="getTaskStatusClass(task.status)">
                      {{ getTaskStatusLabel(task.status) }}
                    </span>
                  </button>
                </td>
                <td class="p-4 font-semibold text-xs text-slate-900 dark:text-white">
                  <span :class="{ 'line-through text-slate-400 dark:text-slate-500': task.status === 'completed' }">
                    {{ task.nama_task }}
                  </span>
                </td>
                <td class="p-4 text-xs font-semibold text-slate-700 dark:text-slate-350">
                  <span v-if="task.pic" class="inline-flex items-center gap-1 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-2xs text-slate-650 dark:text-slate-350">
                    👤 {{ task.pic }}
                  </span>
                  <span v-else class="text-slate-400 dark:text-slate-600 font-mono text-2xs">-</span>
                </td>
                <td class="p-4 text-xs font-medium font-mono">
                  <span v-if="task.target_date" :class="{ 'text-rose-500 font-bold': isOverdue(task.target_date) && task.status !== 'completed' }">
                    {{ formatDate(task.target_date) }}
                    <span v-if="isOverdue(task.target_date) && task.status !== 'completed'" class="text-3xs block font-semibold uppercase tracking-wider text-rose-500">Terlambat</span>
                  </span>
                  <span v-else class="text-slate-400 dark:text-slate-600">-</span>
                </td>
                <td class="p-4 text-xs text-slate-500 dark:text-slate-400 max-w-xs truncate" :title="task.keterangan">
                  {{ task.keterangan || '-' }}
                </td>
                <td class="p-4 text-right space-x-2">
                  <button @click="openEditTaskModal(task)" class="text-emerald-500 hover:text-emerald-400 p-1 inline-block cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                    </svg>
                  </button>
                  <button @click="deleteTask(task.id)" class="text-rose-450 hover:text-rose-400 p-1 inline-block cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
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

    <!-- Tab 5: Invitation Design -->
    <div v-if="activeTab === 'invitation'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Controls Panel (Left, 7 cols) -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-7 space-y-6 text-xs text-slate-750 dark:text-slate-300">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pengaturan Desain Undangan Digital</h3>
          <span class="text-3xs uppercase font-extrabold px-2 py-0.5 bg-emerald-950 text-emerald-400 border border-emerald-800 rounded tracking-wider">Aktif</span>
        </div>

        <form @submit.prevent="saveInvitation" class="space-y-4">
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Judul Undangan</label>
            <input v-model="invitationForm.title" type="text" placeholder="e.g. The Grand Wedding of Alice & Bob" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-xs outline-none focus:border-emerald-500 font-semibold" required />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Waktu & Tanggal Informasi</label>
              <input v-model="invitationForm.date_time_info" type="text" placeholder="e.g. Minggu, 12 Oktober 2026 jam 19.00 WIB" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-xs outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Font Family</label>
              <select v-model="invitationForm.font_family" @change="loadGoogleFont(invitationForm.font_family)" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-xs outline-none focus:border-emerald-500">
                <option value="Inter">Inter (Sans-serif Modern)</option>
                <option value="Playfair Display">Playfair Display (Serif Elegan)</option>
                <option value="Montserrat">Montserrat (Sans Bold)</option>
                <option value="Lora">Lora (Classic Serif)</option>
                <option value="Sacramento">Sacramento (Script Cantik)</option>
                <option value="Cinzel">Cinzel (Roman Klasik)</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Link Google Maps Lokasi</label>
              <input v-model="invitationForm.maps_url" type="text" placeholder="e.g. https://maps.app.goo.gl/..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-xs outline-none focus:border-emerald-500" />
              <p class="text-4xs text-slate-450 dark:text-slate-500 mt-1">Masukkan URL Google Maps lengkap.</p>
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Label Tombol Maps</label>
              <input v-model="invitationForm.maps_btn_text" type="text" placeholder="e.g. Buka Google Maps" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-xs outline-none focus:border-emerald-500 font-semibold" />
              <p class="text-4xs text-slate-450 dark:text-slate-500 mt-1">Teks yang akan muncul di tombol peta.</p>
            </div>
          </div>

          <div class="border-t border-slate-100 dark:border-slate-800 pt-4">
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tipe Latar Belakang</label>
            <div class="flex space-x-4">
              <label class="inline-flex items-center space-x-2 cursor-pointer">
                <input type="radio" :value="false" v-model="invitationForm.is_custom_template" class="text-emerald-500 focus:ring-emerald-500" />
                <span class="text-xs font-semibold">Gunakan Preset Bawaan</span>
              </label>
              <label class="inline-flex items-center space-x-2 cursor-pointer">
                <input type="radio" :value="true" v-model="invitationForm.is_custom_template" class="text-emerald-500 focus:ring-emerald-500" />
                <span class="text-xs font-semibold">Upload Gambar Kustom (Desainer Sendiri)</span>
              </label>
            </div>
          </div>

          <!-- Preset template options -->
          <div v-if="!invitationForm.is_custom_template" class="space-y-3">
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pilihan Preset Desain</label>
            <select v-model="invitationForm.preset_template" @change="selectPresetColors(invitationForm.preset_template)" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-xs outline-none focus:border-emerald-500 font-semibold capitalize">
              <option value="classic">Classic (Clean White)</option>
              <option value="elegant">Elegant (Gold & Dark Gray)</option>
              <option value="floral">Floral (Romantic Pink)</option>
              <option value="modern">Modern (Cyan Accent)</option>
              <option value="sunset">Sunset (Orange warmth)</option>
              <option value="rustic_elegance">Rustic Elegance (Stone Gray)</option>
              <option value="cyber_neon">Cyber Neon (Deep Dark Pink)</option>
              <option value="royal_corporate">Royal Corporate (Navy Blue)</option>
              <option value="warm_leafy">Warm Leafy (Green forest)</option>
            </select>
          </div>

          <!-- Custom background image upload option -->
          <div v-else class="space-y-4">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Unggah Gambar Latar Belakang</label>
              <div class="flex items-center space-x-4">
                <input type="file" @change="handleBgFileChange" accept="image/*" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-xs outline-none focus:border-emerald-500" />
              </div>
              <p class="text-4xs text-emerald-500 dark:text-emerald-400 font-medium mt-1">Sistem otomatis mendeteksi palet warna dominan (Color Extraction) dan menjamin visibilitas teks agar anti teks mati.</p>
            </div>

            <!-- Posisi Tombol Google Maps (Persentase) -->
            <div class="border-t border-slate-100 dark:border-slate-800 pt-4 space-y-3">
              <div class="flex items-center justify-between">
                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Atur Posisi Tombol Google Maps</h4>
                <button type="button" @click="invitationForm.maps_btn_top = 72; invitationForm.maps_btn_left = 15; invitationForm.maps_btn_width = 70; invitationForm.maps_btn_height = 6" class="text-4xs text-emerald-500 hover:text-emerald-400 font-bold uppercase transition-colors">Reset Posisi</button>
              </div>
              
              <div>
                <label class="flex justify-between text-4xs font-bold text-slate-450 dark:text-slate-500 uppercase mb-1">
                  <span>Posisi Vertikal (Tinggi dari Atas)</span>
                  <span class="font-mono">{{ invitationForm.maps_btn_top }}%</span>
                </label>
                <input type="range" min="0" max="100" step="0.5" v-model="invitationForm.maps_btn_top" class="w-full h-1 bg-slate-100 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500" />
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="flex justify-between text-4xs font-bold text-slate-450 dark:text-slate-500 uppercase mb-1">
                    <span>Posisi Horizontal (Kiri)</span>
                    <span class="font-mono">{{ invitationForm.maps_btn_left }}%</span>
                  </label>
                  <input type="range" min="0" max="100" step="0.5" v-model="invitationForm.maps_btn_left" class="w-full h-1 bg-slate-100 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500" />
                </div>
                <div>
                  <label class="flex justify-between text-4xs font-bold text-slate-450 dark:text-slate-500 uppercase mb-1">
                    <span>Lebar Tombol</span>
                    <span class="font-mono">{{ invitationForm.maps_btn_width }}%</span>
                  </label>
                  <input type="range" min="10" max="100" step="0.5" v-model="invitationForm.maps_btn_width" class="w-full h-1 bg-slate-100 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500" />
                </div>
                <div>
                  <label class="flex justify-between text-4xs font-bold text-slate-450 dark:text-slate-500 uppercase mb-1">
                    <span>Tinggi Tombol</span>
                    <span class="font-mono">{{ invitationForm.maps_btn_height }}%</span>
                  </label>
                  <input type="range" min="2" max="30" step="0.5" v-model="invitationForm.maps_btn_height" class="w-full h-1 bg-slate-100 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500" />
                </div>
              </div>
            </div>
          </div>

          <!-- Advanced: Color adjustments (visible but automated) -->
          <div class="border-t border-slate-100 dark:border-slate-800 pt-4 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Warna Palette Terdeteksi</span>
              <span class="text-4xs bg-slate-100 dark:bg-slate-800 text-slate-500 px-1.5 py-0.5 rounded font-mono uppercase">Latar Belakang Otomatis</span>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
              <div>
                <label class="block text-4xs font-semibold text-slate-400 mb-0.5">Background</label>
                <div class="flex items-center space-x-1.5">
                  <input type="color" v-model="invitationForm.background_color" class="w-6 h-6 rounded border-0 cursor-pointer p-0" />
                  <span class="font-mono text-3xs">{{ invitationForm.background_color }}</span>
                </div>
              </div>
              <div>
                <label class="block text-4xs font-semibold text-slate-400 mb-0.5">Aksen / Tombol</label>
                <div class="flex items-center space-x-1.5">
                  <input type="color" v-model="invitationForm.accent_color" class="w-6 h-6 rounded border-0 cursor-pointer p-0" />
                  <span class="font-mono text-3xs">{{ invitationForm.accent_color }}</span>
                </div>
              </div>
              <div>
                <label class="block text-4xs font-semibold text-slate-400 mb-0.5">Warna Judul</label>
                <div class="flex items-center space-x-1.5">
                  <input type="color" v-model="invitationForm.primary_color" class="w-6 h-6 rounded border-0 cursor-pointer p-0" />
                  <span class="font-mono text-3xs">{{ invitationForm.primary_color }}</span>
                </div>
              </div>
              <div>
                <label class="block text-4xs font-semibold text-slate-400 mb-0.5">Warna Teks</label>
                <div class="flex items-center space-x-1.5">
                  <div class="w-6 h-6 rounded border border-slate-350 dark:border-slate-700 flex items-center justify-center font-bold text-3xs" :style="{ backgroundColor: invitationForm.background_color, color: invitationForm.text_color }">T</div>
                  <span class="font-mono text-3xs">{{ invitationForm.text_color }}</span>
                </div>
              </div>
              <div>
                <label class="block text-4xs font-semibold text-slate-400 mb-0.5">Teks Tombol</label>
                <div class="flex items-center space-x-1.5">
                  <div class="w-6 h-6 rounded border border-slate-350 dark:border-slate-700 flex items-center justify-center font-bold text-3xs" :style="{ backgroundColor: invitationForm.accent_color, color: invitationForm.button_text_color }">B</div>
                  <span class="font-mono text-3xs">{{ invitationForm.button_text_color }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-800 dark:border-slate-700 text-white rounded-xl font-bold cursor-pointer transition-colors" :disabled="isSavingInvitation">
              {{ isSavingInvitation ? 'Menyimpan...' : 'Simpan Konfigurasi Undangan' }}
            </button>
          </div>
        </form>

        <!-- Share & Public Link section -->
        <div class="border-t border-slate-100 dark:border-slate-800 pt-4 space-y-3">
          <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Bagikan Undangan Digital</h4>
          <div class="flex items-center space-x-2">
            <input type="text" readonly :value="publicInvitationUrl" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg font-mono text-xs select-all text-slate-500" />
            <button @click="copyInvitationLink" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg cursor-pointer transition-colors flex items-center space-x-1 shrink-0">
              <span>{{ invitationLinkCopied ? 'Tersalin!' : 'Salin Link' }}</span>
            </button>
            <a :href="'/invitation/' + props.id" target="_blank" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 font-semibold transition-colors flex items-center justify-center shrink-0">
              Buka
            </a>
          </div>
        </div>
      </div>

      <!-- Live Mockup Preview Panel (Right, 5 cols) -->
      <div class="lg:col-span-5 flex flex-col items-center">
        <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2 self-start">Live Preview (Tampilan HP)</span>
        
        <!-- Smartphone Container -->
        <div class="relative w-[300px] h-[580px] bg-slate-950 rounded-[40px] border-[10px] border-slate-800 dark:border-slate-700 shadow-2xl overflow-hidden flex flex-col">
          <!-- Speaker/Camera Notch -->
          <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-slate-800 rounded-b-2xl z-10 flex items-center justify-center">
            <div class="w-12 h-1 bg-slate-900 rounded-full mb-1"></div>
            <div class="w-2.5 h-2.5 bg-slate-900 rounded-full absolute right-6 mb-1"></div>
          </div>

          <!-- Dynamic Screen View -->
          <div class="flex-1 p-6 pt-12 flex flex-col text-center relative overflow-hidden transition-all duration-300" :class="[invitationForm.is_custom_template ? 'justify-end' : 'justify-between']" :style="[
            invitationForm.is_custom_template && isCustomImagePreview
              ? { backgroundImage: `url(${isCustomImagePreview})`, backgroundSize: '100% 100%', backgroundPosition: 'center', fontFamily: invitationForm.font_family, color: invitationForm.text_color }
              : { backgroundColor: invitationForm.background_color, fontFamily: invitationForm.font_family, color: invitationForm.text_color }
          ]">
            <!-- Card Header -->
            <div v-if="!invitationForm.is_custom_template" class="relative z-1 space-y-2">
              <span class="text-[8px] uppercase tracking-widest font-extrabold px-2 py-0.5 bg-white/15 dark:bg-black/25 rounded-full inline-block backdrop-blur-xs border border-white/10" :style="{ color: invitationForm.accent_color }">
                Undangan Resmi
              </span>
              <h2 class="text-lg font-bold tracking-tight leading-snug mt-2" :style="{ color: invitationForm.primary_color }">
                {{ invitationForm.title || 'Judul Undangan Event' }}
              </h2>
              <div class="h-0.5 w-10 mx-auto my-2 opacity-50" :style="{ backgroundColor: invitationForm.accent_color }"></div>
            </div>

            <!-- Event Details -->
            <div v-if="!invitationForm.is_custom_template" class="relative z-1 space-y-3 bg-black/5 dark:bg-white/5 p-4 rounded-xl border border-white/5 backdrop-blur-xs text-[10px]">
              <div>
                <span class="block font-bold opacity-50 uppercase text-[7px] tracking-wider mb-0.5">Penyelenggara</span>
                <p class="font-bold opacity-85 leading-normal">{{ event.client?.perusahaan || event.client?.nama || 'EO Client' }}</p>
              </div>

              <div>
                <span class="block font-bold opacity-50 uppercase text-[7px] tracking-wider mb-0.5">Waktu Event</span>
                <p class="font-extrabold opacity-90 leading-normal">{{ invitationForm.date_time_info || 'Minggu, 12 Oktober 2026 jam 19.00 WIB' }}</p>
              </div>

              <div>
                <span class="block font-bold opacity-50 uppercase text-[7px] tracking-wider mb-0.5">Lokasi Venue</span>
                <p class="font-medium opacity-85 leading-normal">📍 {{ event.lokasi || 'Venue Terjadwal' }}</p>
              </div>
            </div>

            <!-- Absolute Map Button for Custom Template (Mockup Preview) -->
            <div 
              v-if="invitationForm.is_custom_template && invitationForm.maps_url" 
              class="absolute z-10 flex items-center justify-center pointer-events-none"
              :style="{ 
                top: invitationForm.maps_btn_top + '%', 
                left: invitationForm.maps_btn_left + '%', 
                width: invitationForm.maps_btn_width + '%',
                height: invitationForm.maps_btn_height + '%'
              }"
            >
              <button 
                type="button" 
                class="w-full h-full rounded-lg text-[8px] font-bold shadow-md transition-all flex items-center justify-center space-x-1 focus:outline-none pointer-events-auto cursor-pointer" 
                :style="{ backgroundColor: invitationForm.accent_color, color: invitationForm.button_text_color }"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-2.5 h-2.5 shrink-0">
                  <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                </svg>
                <span>{{ invitationForm.maps_btn_text || 'Buka Google Maps' }}</span>
              </button>
            </div>

            <!-- Button Action (Non-Custom Template) -->
            <div v-if="!invitationForm.is_custom_template" class="relative z-1 pt-2 space-y-1 flex flex-col items-center">
              <button v-if="invitationForm.maps_url" type="button" class="px-4 py-2 rounded-lg text-[9px] font-bold shadow-md transition-all flex items-center space-x-1 focus:outline-none cursor-pointer" :style="{ backgroundColor: invitationForm.accent_color, color: invitationForm.button_text_color }">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3">
                  <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                </svg>
                <span>{{ invitationForm.maps_btn_text || 'Buka Google Maps' }}</span>
              </button>
              <span class="text-[7px] opacity-40 font-mono tracking-wider block mt-2">Powered by EventBooks</span>
            </div>
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
          <div class="grid grid-cols-3 gap-3">
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
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Kategori</label>
              <select v-model="editForm.kategori" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
                <option value="small">Small</option>
                <option value="medium">Medium</option>
                <option value="large">Large</option>
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
                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500"
              >
                <option value="draft">Draft</option>
                <option value="negosiasi">Negosiasi</option>
                <option value="dp">DP Terbayar</option>
                <option value="berjalan">Sedang Berjalan</option>
                <option value="selesai">Selesai</option>
                <option value="batal">Dibatalkan</option>
              </select>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isEditModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-655 dark:text-slate-350 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Tambah/Edit Tugas -->
    <div v-if="isTaskModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isTaskModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ isEditTask ? 'Edit Tugas' : 'Tambah Tugas Baru' }}</h3>

        <form @submit.prevent="saveTask" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-350">
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Tugas</label>
            <input v-model="taskForm.nama_task" type="text" placeholder="e.g. Booking Sound System" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">PIC (Penanggung Jawab)</label>
            <input v-model="taskForm.pic" type="text" placeholder="e.g. John Doe" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Target Tanggal Selesai</label>
            <input v-model="taskForm.target_date" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Status</label>
            <select v-model="taskForm.status" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
              <option value="pending">Belum Mulai</option>
              <option value="in_progress">Dalam Proses</option>
              <option value="completed">Selesai</option>
            </select>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Keterangan / Catatan</label>
            <textarea v-model="taskForm.keterangan" rows="3" placeholder="Detail deskripsi tugas..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500"></textarea>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isTaskModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-655 dark:text-slate-350 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
