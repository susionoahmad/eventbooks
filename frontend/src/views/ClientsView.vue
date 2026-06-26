<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const clients = ref<any[]>([])
const isModalOpen = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const activeClient = ref<any>({
  id: null, kode_klien: '', nama: '', perusahaan: '', npwp: '', email: '', telepon: '', alamat: ''
})

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
  activeClient.value = { id: null, kode_klien: 'Loading...', nama: '', perusahaan: '', npwp: '', email: '', telepon: '', alamat: '' }
  isModalOpen.value = true
  try {
    const res = await api.get('/clients/next-code')
    activeClient.value.kode_klien = res.data.next_code
  } catch (err) {
    console.error('Error fetching next client code:', err)
    activeClient.value.kode_klien = `CLI-00${clients.value.length + 1}`
  }
}

const openEditModal = (client: any) => {
  modalMode.value = 'edit'
  activeClient.value = { ...client }
  isModalOpen.value = true
}

const saveClient = async () => {
  try {
    if (modalMode.value === 'create') {
      await api.post('/clients', activeClient.value)
    } else {
      await api.put(`/clients/${activeClient.value.id}`, activeClient.value)
    }
    fetchClients()
    isModalOpen.value = false
  } catch (err) {
    console.error('Error saving client:', err)
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
              <td class="p-4 font-mono text-xs text-slate-400">{{ client.kode_klien }}</td>
              <td class="p-4">
                <span class="font-bold text-slate-900 dark:text-white block">{{ client.nama }}</span>
                <span class="text-xs text-slate-400 block mt-0.5">{{ client.perusahaan || 'Personal Klien' }}</span>
              </td>
              <td class="p-4 text-xs font-semibold text-slate-500 dark:text-slate-400">
                {{ client.npwp || 'Tidak Ada NPWP' }}
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
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Kode Klien</label>
              <input v-model="activeClient.kode_klien" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">NPWP Klien</label>
              <input v-model="activeClient.npwp" type="text" placeholder="00.000.000.0-000.000" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" />
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Kontak Utama</label>
            <input v-model="activeClient.nama" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Perusahaan</label>
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

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
