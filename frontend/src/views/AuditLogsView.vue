<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import api from '../services/api'

// State
const logs = ref<any[]>([])
const users = ref<any[]>([])
const summary = ref({
  today_count: 0,
  active_users_today: 0,
  recent_crud: [] as any[]
})

// Filters
const search = ref('')
const selectedUser = ref('')
const selectedType = ref('')
const startDate = ref('')
const endDate = ref('')

// Pagination
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(15)
const totalLogs = ref(0)
const isLoading = ref(false)

// Detail Modal
const selectedLog = ref<any>(null)
const isModalOpen = ref(false)

// Fetch Data
const fetchUsers = async () => {
  try {
    const res = await api.get('/tenant/users')
    users.value = res.data.data
  } catch (err) {
    console.error('Error fetching users for filter:', err)
  }
}

const fetchSummary = async () => {
  try {
    const res = await api.get('/audit-logs/summary')
    summary.value = res.data
  } catch (err) {
    console.error('Error fetching logs summary:', err)
  }
}

const fetchLogs = async (page = 1) => {
  isLoading.value = true
  try {
    const params: any = {
      page,
      per_page: perPage.value,
      search: search.value || undefined,
      user_id: selectedUser.value || undefined,
      activity_type: selectedType.value || undefined,
      start_date: startDate.value || undefined,
      end_date: endDate.value || undefined,
    }

    const res = await api.get('/audit-logs', { params })
    logs.value = res.data.data
    currentPage.value = res.data.current_page
    totalPages.value = res.data.last_page
    totalLogs.value = res.data.total
  } catch (err) {
    console.error('Error fetching audit logs:', err)
  } finally {
    isLoading.value = false
  }
}

// Watchers for filtering
watch([selectedUser, selectedType, startDate, endDate], () => {
  fetchLogs(1)
})

let searchTimeout: any = null
watch(search, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchLogs(1)
  }, 400)
})

// Clear Filters
const clearFilters = () => {
  search.value = ''
  selectedUser.value = ''
  selectedType.value = ''
  startDate.value = ''
  endDate.value = ''
  fetchLogs(1)
}

// Export to CSV
const isExporting = ref(false)
const exportCSV = async () => {
  isExporting.value = true
  try {
    const params: any = {
      per_page: 10000,
      search: search.value || undefined,
      user_id: selectedUser.value || undefined,
      activity_type: selectedType.value || undefined,
      start_date: startDate.value || undefined,
      end_date: endDate.value || undefined,
    }

    const res = await api.get('/audit-logs', { params })
    const allLogs = res.data.data

    const headers = ['Waktu', 'Pengguna', 'Email', 'Role', 'Aktivitas', 'Deskripsi', 'IP Address', 'User Agent']
    const rows = allLogs.map((log: any) => [
      formatDate(log.created_at),
      log.user ? log.user.name : 'System',
      log.user ? log.user.email : '-',
      log.user ? log.user.role : 'SYSTEM',
      getActivityTypeLabel(log.activity),
      log.description,
      log.ip_address || '-',
      log.user_agent || '-'
    ])

    const csvContent = '\uFEFF' + [headers, ...rows]
      .map((row: any[]) => row.map((cell: any) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
      .join('\n')

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `AuditLogs_${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Error exporting audit logs:', err)
    alert('Gagal mengekspor log audit.')
  } finally {
    isExporting.value = false
  }
}

// Helpers
const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  try {
    const date = new Date(dateStr)
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    }).format(date)
  } catch (e) {
    return dateStr
  }
}

const getActivityBadgeClass = (activity: string) => {
  const act = activity.toLowerCase()
  if (act.includes('created') || act.includes('register')) {
    return 'bg-emerald-950 text-emerald-400 border-emerald-800'
  }
  if (act.includes('updated') || act.includes('password') || act.includes('setup')) {
    return 'bg-amber-950 text-amber-400 border-amber-800'
  }
  if (act.includes('deleted')) {
    return 'bg-rose-950 text-rose-400 border-rose-800'
  }
  return 'bg-blue-950 text-blue-400 border-blue-800' // e.g. Login, Logout, Download
}

const getActivityTypeLabel = (activity: string) => {
  const act = activity.toLowerCase()
  if (act.includes('created')) return 'Tambah Data'
  if (act.includes('updated')) return 'Ubah Data'
  if (act.includes('deleted')) return 'Hapus Data'
  if (act === 'login') return 'Masuk'
  if (act === 'logout') return 'Keluar'
  if (act === 'register') return 'Pendaftaran'
  if (act.includes('password')) return 'Ubah Sandi'
  if (act.includes('download')) return 'Unduh Dokumen'
  if (act.includes('setup')) return 'Setup Wizard'
  return activity
}

const openDetailModal = (log: any) => {
  selectedLog.value = log
  isModalOpen.value = true
}

const closeDetailModal = () => {
  isModalOpen.value = false
  selectedLog.value = null
}

const formatFieldName = (name: string) => {
  return name.replace(/_/g, ' ').toUpperCase()
}

onMounted(() => {
  fetchUsers()
  fetchSummary()
  fetchLogs()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Audit Logs & Aktivitas</h1>
        <p class="text-xs text-slate-500 mt-1">Rekam jejak aktivitas tim, login, dan mutasi data pada sistem.</p>
      </div>
      <button 
        @click="exportCSV"
        :disabled="isExporting"
        class="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:bg-emerald-800 text-white rounded-lg text-xs font-bold transition-all cursor-pointer shadow-sm shadow-emerald-600/10 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="isExporting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
        <span v-else>📥</span>
        <span>{{ isExporting ? 'Mengekspor...' : 'Ekspor CSV' }}</span>
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-500/10 text-blue-500 rounded-xl flex items-center justify-center text-2xl">
          📋
        </div>
        <div>
          <p class="text-3xs font-bold text-slate-400 uppercase tracking-wider">Aktivitas Hari Ini</p>
          <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ summary.today_count }}</h3>
          <p class="text-4xs text-slate-500 mt-0.5">log tercatat sejak tengah malam</p>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-emerald-500/10 text-emerald-500 rounded-xl flex items-center justify-center text-2xl">
          👥
        </div>
        <div>
          <p class="text-3xs font-bold text-slate-400 uppercase tracking-wider">User Aktif Hari Ini</p>
          <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ summary.active_users_today }}</h3>
          <p class="text-4xs text-slate-500 mt-0.5">anggota tim beraktivitas</p>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-500/10 text-amber-500 rounded-xl flex items-center justify-center text-2xl">
          ⚡
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-3xs font-bold text-slate-400 uppercase tracking-wider">CRUD Terakhir</p>
          <p v-if="summary.recent_crud.length > 0" class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate mt-1">
            {{ summary.recent_crud[0].description }}
          </p>
          <p v-else class="text-xs text-slate-500 mt-1">Belum ada perubahan data hari ini</p>
          <p class="text-4xs text-slate-500 mt-0.5">riwayat mutasi database terakhir</p>
        </div>
      </div>
    </div>

    <!-- Filter Panel -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
        <h4 class="font-bold text-slate-900 dark:text-white text-xs">Filter Pencarian</h4>
        <button 
          @click="clearFilters"
          class="text-3xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer"
        >
          Bersihkan Filter
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 text-xs">
        <!-- Search input -->
        <div class="md:col-span-2">
          <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">Cari Deskripsi / Aktivitas</label>
          <input 
            v-model="search"
            type="text" 
            placeholder="Cari kata kunci..." 
            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500" 
          />
        </div>

        <!-- User filter -->
        <div>
          <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">Anggota Tim</label>
          <select 
            v-model="selectedUser"
            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500"
          >
            <option value="">Semua Anggota</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.role.replace('_', ' ') }})
            </option>
          </select>
        </div>

        <!-- Activity type filter -->
        <div>
          <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tipe Aktivitas</label>
          <select 
            v-model="selectedType"
            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500"
          >
            <option value="">Semua Tipe</option>
            <option value="auth">Autentikasi (Login/Logout)</option>
            <option value="created">Tambah Data (Create)</option>
            <option value="updated">Ubah Data (Update)</option>
            <option value="deleted">Hapus Data (Delete)</option>
          </select>
        </div>

        <!-- Date filters -->
        <div class="grid grid-cols-2 gap-2 md:col-span-1 sm:col-span-2">
          <div>
            <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">Mulai</label>
            <input 
              v-model="startDate"
              type="date" 
              class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-2 py-1.5 rounded-lg text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500" 
            />
          </div>
          <div>
            <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">Selesai</label>
            <input 
              v-model="endDate"
              type="date" 
              class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-2 py-1.5 rounded-lg text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500" 
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 text-slate-400 text-3xs font-bold uppercase tracking-wider">
              <th class="py-4 px-5">Waktu</th>
              <th class="py-4 px-5">Pengguna</th>
              <th class="py-4 px-5">Aktivitas</th>
              <th class="py-4 px-5">Deskripsi</th>
              <th class="py-4 px-5">IP Address</th>
              <th class="py-4 px-5 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40">
            <tr v-if="isLoading">
              <td colspan="6" class="py-10 text-center text-slate-400">
                <span class="inline-block w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></span>
                <p class="mt-2 text-2xs">Memuat log aktivitas...</p>
              </td>
            </tr>

            <tr v-else-if="logs.length === 0">
              <td colspan="6" class="py-12 text-center text-slate-400">
                <span class="text-3xl">📭</span>
                <p class="mt-2 font-bold text-xs">Tidak ada log aktivitas ditemukan</p>
                <p class="text-2xs text-slate-500 mt-1">Coba bersihkan atau ubah filter pencarian Anda.</p>
              </td>
            </tr>

            <tr 
              v-else 
              v-for="log in logs" 
              :key="log.id" 
              class="text-slate-700 dark:text-slate-300 hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors"
            >
              <td class="py-3.5 px-5 font-medium whitespace-nowrap text-slate-500">
                {{ formatDate(log.created_at) }}
              </td>
              <td class="py-3.5 px-5">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-slate-900 dark:bg-slate-700 text-slate-100 flex items-center justify-center font-bold text-3xs">
                    {{ log.user ? log.user.name.charAt(0) : 'S' }}
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 dark:text-white block leading-tight">{{ log.user ? log.user.name : 'System' }}</span>
                    <span class="text-3xs text-emerald-600 dark:text-emerald-400 font-semibold uppercase tracking-wider block mt-0.5" style="font-size: 8px;">
                      {{ log.user ? log.user.role.replace('_', ' ') : 'SYSTEM' }}
                    </span>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-5 whitespace-nowrap">
                <span :class="[getActivityBadgeClass(log.activity), 'px-2 py-0.5 border rounded-full text-[10px] font-semibold tracking-wide uppercase']">
                  {{ getActivityTypeLabel(log.activity) }}
                </span>
              </td>
              <td class="py-3.5 px-5 font-semibold text-slate-800 dark:text-slate-200">
                {{ log.description }}
              </td>
              <td class="py-3.5 px-5 text-slate-400 font-mono text-2xs">
                {{ log.ip_address || '-' }}
              </td>
              <td class="py-3.5 px-5 text-right">
                <button 
                  @click="openDetailModal(log)"
                  class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-750 text-slate-700 dark:text-slate-300 font-bold rounded-lg transition-colors cursor-pointer"
                >
                  Detail
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="logs.length > 0" class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800 px-5 py-4 text-slate-500">
        <span class="text-2xs font-medium">
          Menampilkan <span class="font-bold text-slate-900 dark:text-white">{{ logs.length }}</span> dari <span class="font-bold text-slate-900 dark:text-white">{{ totalLogs }}</span> log aktivitas
        </span>

        <div class="flex items-center gap-2">
          <button 
            @click="fetchLogs(currentPage - 1)" 
            :disabled="currentPage === 1"
            class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed border border-slate-250 dark:border-slate-700 rounded-lg text-2xs font-bold transition-all text-slate-700 dark:text-slate-350 cursor-pointer"
          >
            Sebelumnya
          </button>
          
          <span class="text-2xs font-semibold px-2">
            Halaman <span class="text-slate-900 dark:text-white">{{ currentPage }}</span> dari <span class="text-slate-900 dark:text-white">{{ totalPages }}</span>
          </span>

          <button 
            @click="fetchLogs(currentPage + 1)" 
            :disabled="currentPage === totalPages"
            class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed border border-slate-250 dark:border-slate-700 rounded-lg text-2xs font-bold transition-all text-slate-700 dark:text-slate-350 cursor-pointer"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>

    <!-- Log Detail Modal -->
    <div v-if="isModalOpen && selectedLog" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="closeDetailModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-2xl space-y-4 max-h-[85vh] overflow-y-auto">
        
        <!-- Modal Title -->
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
          <div class="flex items-center gap-2">
            <span :class="[getActivityBadgeClass(selectedLog.activity), 'px-2.5 py-0.5 border rounded-full text-3xs font-bold uppercase tracking-wider']">
              {{ getActivityTypeLabel(selectedLog.activity) }}
            </span>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Detail Aktivitas</h3>
          </div>
          <button @click="closeDetailModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold">×</button>
        </div>

        <!-- Log Description & Overview -->
        <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-150 dark:border-slate-800/80 space-y-2">
          <p class="text-sm font-bold text-slate-900 dark:text-white leading-relaxed">{{ selectedLog.description }}</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 text-3xs text-slate-400 font-medium">
            <div>
              <span class="block uppercase tracking-wider text-4xs">Waktu Kejadian</span>
              <span class="text-slate-700 dark:text-slate-300 font-bold text-2xs block mt-0.5">{{ formatDate(selectedLog.created_at) }}</span>
            </div>
            <div>
              <span class="block uppercase tracking-wider text-4xs">Dipicu Oleh</span>
              <span class="text-slate-700 dark:text-slate-300 font-bold text-2xs block mt-0.5 break-all">
                {{ selectedLog.user ? selectedLog.user.name : 'System' }} ({{ selectedLog.user ? selectedLog.user.email : '-' }})
              </span>
            </div>
          </div>
        </div>

        <!-- Technical Context -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">IP Address</label>
            <div class="bg-slate-100 dark:bg-slate-800 px-3 py-2 rounded-lg font-mono text-slate-700 dark:text-slate-300 text-2xs">
              {{ selectedLog.ip_address || 'Tidak terekam' }}
            </div>
          </div>
          <div>
            <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">User Agent (Browser/Platform)</label>
            <div class="bg-slate-100 dark:bg-slate-800 px-3 py-2 rounded-lg font-mono text-slate-750 dark:text-slate-350 text-[10px] break-all leading-tight">
              {{ selectedLog.user_agent || 'Tidak terekam' }}
            </div>
          </div>
        </div>

        <!-- Metadata Changes Visualizer (For CRUD updates) -->
        <div v-if="selectedLog.metadata && selectedLog.metadata.changed_fields" class="space-y-2.5">
          <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider">Perubahan Nilai Data (Lama vs Baru)</label>
          <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl text-2xs">
            <table class="w-full text-left border-collapse min-w-[500px]">
              <thead>
                <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 font-bold text-3xs uppercase tracking-wider border-b border-slate-200 dark:border-slate-850">
                  <th class="py-2.5 px-3.5">Nama Kolom / Field</th>
                  <th class="py-2.5 px-3.5 bg-rose-500/5 text-rose-500 dark:text-rose-400">Nilai Sebelum</th>
                  <th class="py-2.5 px-3.5 bg-emerald-500/5 text-emerald-500 dark:text-emerald-400">Nilai Sesudah</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-150 dark:divide-slate-800/80">
                <tr 
                  v-for="(val, field) in selectedLog.metadata.changed_fields" 
                  :key="field"
                  class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20"
                >
                  <td class="py-2.5 px-3.5 font-bold text-slate-700 dark:text-slate-300">
                    {{ formatFieldName(String(field)) }}
                  </td>
                  <td class="py-2.5 px-3.5 bg-rose-500/5 text-rose-600 dark:text-rose-400/90 font-mono break-all leading-tight">
                    {{ val.old === null || val.old === '' ? '(kosong)' : val.old }}
                  </td>
                  <td class="py-2.5 px-3.5 bg-emerald-500/5 text-emerald-600 dark:text-emerald-450 font-mono break-all font-semibold leading-tight">
                    {{ val.new === null || val.new === '' ? '(kosong)' : val.new }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button 
            type="button" 
            @click="closeDetailModal" 
            class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-850 text-slate-650 dark:text-slate-350 cursor-pointer"
          >
            Tutup Detail
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
