<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()
const router = useRouter()

const events = ref<any[]>([])
const clients = ref<any[]>([])
const isModalOpen = ref(false)
const activeEvent = ref<any>({
  nomor_event: '', nama_event: '', jenis_event: 'Wedding', kategori: 'medium', tanggal_mulai: '', tanggal_selesai: '', lokasi: '', nilai_kontrak: 0, status: 'draft', client_id: null
})

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'draft': return 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-350 border-slate-300 dark:border-slate-700'
    case 'negosiasi': return 'bg-sky-950 text-sky-400 border-sky-850'
    case 'dp': return 'bg-indigo-950 text-indigo-400 border-indigo-850'
    case 'berjalan': return 'bg-amber-950 text-amber-400 border-amber-850'
    case 'selesai': return 'bg-emerald-950 text-emerald-400 border-emerald-850'
    case 'batal': return 'bg-rose-950 text-rose-400 border-rose-850'
    default: return 'bg-slate-200 text-slate-700 border-slate-300'
  }
}

const fetchEvents = async () => {
  try {
    const res = await api.get('/events')
    events.value = res.data.data
  } catch (err) {
    console.error('Error fetching events:', err)
  }
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
  fetchEvents()
  fetchClients()
})

const openCreateModal = () => {
  activeEvent.value = {
    nomor_event: `EV-260600${events.value.length + 1}`,
    nama_event: '',
    jenis_event: 'Wedding',
    kategori: 'medium',
    tanggal_mulai: new Date().toISOString().split('T')[0],
    tanggal_selesai: new Date().toISOString().split('T')[0],
    lokasi: '',
    nilai_kontrak: 0,
    status: 'draft',
    client_id: clients.value.length > 0 ? clients.value[0].id : null
  }
  isModalOpen.value = true
}

const saveEvent = async () => {
  try {
    await api.post('/events', activeEvent.value)
    fetchEvents()
    isModalOpen.value = false
  } catch (err) {
    console.error('Error saving event:', err)
  }
}

const navigateToDetail = (id: number) => {
  router.push(`/events/${id}`)
}

const formatIDR = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Kelola Event</h1>
        <p class="text-xs text-slate-500 mt-1">Daftar seluruh event, timeline eksekusi, nilai kontrak klien, dan status.</p>
      </div>
      <button 
        v-if="authStore.userRole !== 'staff'"
        @click="openCreateModal"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-colors cursor-pointer"
      >
        + Registrasi Event
      </button>
    </div>

    <!-- Events Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div 
        v-for="ev in events" 
        :key="ev.id"
        @click="navigateToDetail(ev.id)"
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 rounded-2xl shadow-sm hover:shadow-md dark:hover:border-slate-700/80 transition-all duration-300 p-5 cursor-pointer flex flex-col justify-between"
      >
        <div>
          <!-- Top badge -->
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <span class="font-mono text-2xs text-slate-400 font-semibold">{{ ev.nomor_event }}</span>
              <span v-if="ev.kategori" class="px-2 py-0.5 text-3xs font-bold rounded-full bg-indigo-100/80 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-850">
                {{ ev.kategori }}
              </span>
            </div>
            <span :class="[getStatusBadgeClass(ev.status), 'px-2 py-0.5 border rounded text-3xs font-bold uppercase tracking-wider']">
              {{ ev.status }}
            </span>
          </div>

          <!-- Event title -->
          <h3 class="font-bold text-slate-900 dark:text-white text-base mt-2.5 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors">
            {{ ev.nama_event }}
          </h3>
          
          <div class="text-xs text-slate-500 dark:text-slate-400 mt-2 space-y-1">
            <div class="flex items-center">
              <span class="font-bold text-2xs uppercase tracking-wider text-slate-400 w-20">Klien</span>
              <span>: {{ typeof ev.client === 'object' ? (ev.client?.perusahaan || ev.client?.nama) : ev.client }}</span>
            </div>
            <div class="flex items-center">
              <span class="font-bold text-2xs uppercase tracking-wider text-slate-400 w-20">Tanggal</span>
              <span>: {{ ev.tanggal_mulai }} s/d {{ ev.tanggal_selesai }}</span>
            </div>
            <div class="flex items-center">
              <span class="font-bold text-2xs uppercase tracking-wider text-slate-400 w-20">Lokasi</span>
              <span class="truncate">: {{ ev.lokasi }}</span>
            </div>
          </div>
        </div>

        <div class="border-t border-slate-100 dark:border-slate-800/60 pt-4 mt-5 flex items-center justify-between">
          <div>
            <span class="text-3xs uppercase font-bold text-slate-400 block tracking-wider">Nilai Kontrak</span>
            <span class="text-sm font-extrabold text-slate-900 dark:text-slate-200">{{ formatIDR(ev.nilai_kontrak) }}</span>
          </div>
          <span class="text-xs text-emerald-500 dark:text-emerald-400 font-bold hover:translate-x-1 transition-transform inline-flex items-center">
            Buka Workspace &rarr;
          </span>
        </div>
      </div>
    </div>

    <!-- Create Event Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Registrasi Event Baru</h3>

        <form @submit.prevent="saveEvent" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-300">
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nomor Event</label>
              <input v-model="activeEvent.nomor_event" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Jenis Event</label>
              <select v-model="activeEvent.jenis_event" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
                <option value="Wedding">Wedding</option>
                <option value="Concert">Concert</option>
                <option value="Corporate Event">Corporate Event</option>
                <option value="Agency Expo">Agency Expo</option>
                <option value="Lainnya">Lainnya</option>
              </select>
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Kategori</label>
              <select v-model="activeEvent.kategori" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
                <option value="small">Small</option>
                <option value="medium">Medium</option>
                <option value="large">Large</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Event</label>
            <input v-model="activeEvent.nama_event" type="text" placeholder="e.g. Festival Kuliner Nusantara" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Mulai</label>
              <input v-model="activeEvent.tanggal_mulai" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Selesai</label>
              <input v-model="activeEvent.tanggal_selesai" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pilih Klien</label>
              <select v-model="activeEvent.client_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500" required>
                <option v-if="clients.length === 0" value="" disabled>Tidak ada klien, silakan buat dahulu</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">
                  {{ client.perusahaan ? `${client.perusahaan} (${client.nama})` : client.nama }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nilai Kontrak (Rupiah)</label>
              <input v-model="activeEvent.nilai_kontrak" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Lokasi Venue</label>
            <textarea v-model="activeEvent.lokasi" rows="2" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500"></textarea>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Registrasikan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
