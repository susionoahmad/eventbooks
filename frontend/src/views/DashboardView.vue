<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const stats = ref({
  totalEvent: 0,
  eventAktif: 0,
  pendapatanBulanIni: 0,
  pengeluaranBulanIni: 0,
  labaBersih: 0,
  piutangKlien: 0,
  hutangVendor: 0,
  pajakTerutang: 0
})

const eventProfitability = ref<any[]>([])

// ── Cash Flow by Method ──────────────────────────────────────────────────────
const cashFlow = ref<{
  total_masuk: number
  total_keluar: number
  net_total: number
  by_method: {
    metode: string
    label: string
    kas_masuk: number
    kas_keluar: number
    net: number
    pct_masuk: number
    pct_keluar: number
  }[]
  trend_6_bulan: {
    bulan: string
    bulan_num: string
    kas_masuk: number
    kas_keluar: number
    net: number
  }[]
  bulan_ini_by_method: {
    metode: string
    label: string
    kas_masuk: number
    kas_keluar: number
    net: number
  }[]
} | null>(null)

const activeTab = ref<'total' | 'bulan_ini'>('total')

// Memoized max values for bar scaling
const maxMasuk = computed(() => {
  if (!cashFlow.value) return 1
  const arr = cashFlow.value.by_method.map(m => m.kas_masuk)
  return Math.max(...arr, 1)
})
const maxKeluar = computed(() => {
  if (!cashFlow.value) return 1
  const arr = cashFlow.value.by_method.map(m => m.kas_keluar)
  return Math.max(...arr, 1)
})

const trendMax = computed(() => {
  if (!cashFlow.value) return 1
  const vals = cashFlow.value.trend_6_bulan.flatMap(t => [t.kas_masuk, t.kas_keluar])
  return Math.max(...vals, 1)
})

// Method icons & colors
const methodConfig: Record<string, { icon: string; color: string; bg: string; border: string; dot: string }> = {
  cash:          { icon: '💵', color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-950/50', border: 'border-emerald-200 dark:border-emerald-800', dot: '#10b981' },
  transfer_bank: { icon: '🏦', color: 'text-sky-600 dark:text-sky-400',     bg: 'bg-sky-50 dark:bg-sky-950/50',     border: 'border-sky-200 dark:border-sky-800',     dot: '#0ea5e9' },
  card:          { icon: '💳', color: 'text-violet-600 dark:text-violet-400', bg: 'bg-violet-50 dark:bg-violet-950/50', border: 'border-violet-200 dark:border-violet-800', dot: '#8b5cf6' },
  e_wallet:      { icon: '📱', color: 'text-amber-600 dark:text-amber-400',  bg: 'bg-amber-50 dark:bg-amber-950/50',  border: 'border-amber-200 dark:border-amber-800',  dot: '#f59e0b' },
}

const fetchDashboardData = async () => {
  try {
    const [summaryRes, profitabilityRes, cfRes] = await Promise.all([
      api.get('/dashboard/summary'),
      api.get('/dashboard/event-profitability'),
      api.get('/dashboard/cash-flow-by-method'),
    ])

    stats.value = {
      totalEvent: summaryRes.data.total_event,
      eventAktif: summaryRes.data.event_aktif,
      pendapatanBulanIni: summaryRes.data.pendapatan_bulan_ini,
      pengeluaranBulanIni: summaryRes.data.pengeluaran_bulan_ini,
      labaBersih: summaryRes.data.laba_bersih,
      piutangKlien: summaryRes.data.piutang_klien,
      hutangVendor: summaryRes.data.hutang_vendor,
      pajakTerutang: summaryRes.data.pajak_terutang
    }

    eventProfitability.value = profitabilityRes.data
    cashFlow.value = cfRes.data
  } catch (err) {
    console.error('Error loading dashboard data:', err)
  }
}

onMounted(() => {
  fetchDashboardData()
})

// ── Kalkulator PPN/PPh 23 Dinamis ────────────────────────────────────────────
const kalkulatorDpp = ref<number>(10_000_000)
const pph23Rate = 2 // PPh 23 tarif standar jasa 2% (PMK 141/2015)

const ppnRate = computed(() => {
  const rate = authStore.user?.tenant?.default_ppn_rate
  return rate !== undefined && rate !== null ? parseFloat(rate as any) : 12
})

const kalkulatorPpn = computed(() => Math.round((kalkulatorDpp.value || 0) * ppnRate.value / 100))
const kalkulatorPph23 = computed(() => Math.round((kalkulatorDpp.value || 0) * pph23Rate / 100))

const formatIDR = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value)
}

const formatIDRCompact = (value: number) => {
  if (Math.abs(value) >= 1_000_000_000) return `Rp ${(value / 1_000_000_000).toFixed(1)}M`
  if (Math.abs(value) >= 1_000_000) return `Rp ${(value / 1_000_000).toFixed(1)}Jt`
  if (Math.abs(value) >= 1_000) return `Rp ${(value / 1_000).toFixed(0)}Rb`
  return formatIDR(value)
}

// Sparkline helper: build SVG polyline points from an array of values
const trendPolyline = (key: 'kas_masuk' | 'kas_keluar' | 'net', w = 300, h = 60) => {
  if (!cashFlow.value) return ''
  const data = cashFlow.value.trend_6_bulan.map(t => t[key])
  const maxV = Math.max(...data, 1)
  const minV = Math.min(...data, 0)
  const range = maxV - minV || 1
  const step = w / (data.length - 1)
  return data.map((v, i) => {
    const x = i * step
    const y = h - ((v - minV) / range) * (h - 4) - 2
    return `${x.toFixed(1)},${y.toFixed(1)}`
  }).join(' ')
}
</script>

<template>
  <div class="space-y-6">
    <!-- ── Top Welcome ──────────────────────────────────────────── -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Dashboard Keuangan</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
          Selamat datang kembali, {{ authStore.user?.name }}. Berikut adalah ringkasan finansial {{ authStore.organizationName }} bulan ini.
        </p>
      </div>
      <div class="flex items-center space-x-2">
        <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-semibold transition-colors flex items-center shadow cursor-pointer">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          Buat Event Baru
        </button>
      </div>
    </div>

    <!-- ── Main Stats ───────────────────────────────────────────── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
      <!-- Total Event -->
      <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm flex items-center justify-between transition-colors">
        <div>
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Event</span>
          <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-1">{{ stats.totalEvent }} Event</h3>
          <span class="text-xs text-slate-400 dark:text-slate-500 block mt-2">
            <strong class="text-emerald-500">{{ stats.eventAktif }} Aktif</strong> berjalan bulan ini
          </span>
        </div>
        <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl flex items-center justify-center text-emerald-500 border border-emerald-100 dark:border-emerald-900">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
          </svg>
        </div>
      </div>

      <!-- Pendapatan -->
      <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm flex items-center justify-between transition-colors">
        <div>
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pendapatan Bulan Ini</span>
          <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-1">{{ formatIDR(stats.pendapatanBulanIni) }}</h3>
          <span class="text-xs text-emerald-500 block mt-2">
            &uarr; Kas Masuk aktif
          </span>
        </div>
        <div class="w-12 h-12 bg-sky-50 dark:bg-sky-950/50 rounded-xl flex items-center justify-center text-sky-500 border border-sky-100 dark:border-sky-900">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h16.5m-18 1.5h18M3.75 7.5h16.5M5.25 9h13.5m-15 1.5h16.5m-18 1.5h18M3.75 15h16.5M5.25 16.5h13.5m-15 1.5h15m-18 1.5h18" />
          </svg>
        </div>
      </div>

      <!-- Laba Bersih -->
      <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm flex items-center justify-between transition-colors">
        <div>
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Laba Bersih</span>
          <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-1">{{ formatIDR(stats.labaBersih) }}</h3>
          <span class="text-xs text-slate-400 dark:text-slate-500 block mt-2">
            Pendapatan – Pengeluaran bulan ini
          </span>
        </div>
        <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-950/50 rounded-xl flex items-center justify-center text-indigo-500 border border-indigo-100 dark:border-indigo-900">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
          </svg>
        </div>
      </div>

      <!-- Piutang / Hutang / Pajak -->
      <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm flex items-center justify-between transition-colors">
        <div>
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Piutang / Hutang / Pajak</span>
          <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-1">{{ formatIDR(stats.piutangKlien) }}</h3>
          <div class="text-2xs space-x-2 mt-2 text-slate-400 dark:text-slate-500">
            <span>Hutang: <strong class="text-rose-500">{{ formatIDR(stats.hutangVendor) }}</strong></span>
            <span>&bull;</span>
            <span>Pajak: <strong class="text-amber-500">{{ formatIDR(stats.pajakTerutang) }}</strong></span>
          </div>
        </div>
        <div class="w-12 h-12 bg-rose-50 dark:bg-rose-950/50 rounded-xl flex items-center justify-center text-rose-500 border border-rose-100 dark:border-rose-900">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.03L3.07 19.5a1.5 1.5 0 001.27 2.25h15.32a1.5 1.5 0 001.27-2.25L12 2.72z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- ── Chart & Calculator ───────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="bg-white dark:bg-slate-900 p-5 md:p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm lg:col-span-2 transition-colors">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
          <h3 class="font-bold text-slate-900 dark:text-white text-base">Perkembangan Kas Masuk vs Kas Keluar</h3>
          <span class="text-xs bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded text-slate-500 dark:text-slate-400">Semester 1</span>
        </div>
        <div class="h-64 w-full relative">
          <svg viewBox="0 0 500 200" class="w-full h-full text-emerald-500 overflow-visible">
            <line x1="0" y1="20" x2="500" y2="20" stroke="#f1f5f9" stroke-width="1" class="dark:stroke-slate-800" />
            <line x1="0" y1="80" x2="500" y2="80" stroke="#f1f5f9" stroke-width="1" class="dark:stroke-slate-800" />
            <line x1="0" y1="140" x2="500" y2="140" stroke="#f1f5f9" stroke-width="1" class="dark:stroke-slate-800" />
            <line x1="0" y1="190" x2="500" y2="190" stroke="#cbd5e1" stroke-width="2" class="dark:stroke-slate-700" />
            <path d="M 0 150 Q 80 160 160 90 T 320 60 T 480 30" fill="none" stroke="rgb(16, 185, 129)" stroke-width="4" stroke-linecap="round" />
            <path d="M 0 180 Q 80 185 160 140 T 320 120 T 480 90" fill="none" stroke="rgb(244, 63, 94)" stroke-width="3" stroke-linecap="round" stroke-dasharray="4 2" />
            <circle cx="160" cy="90" r="5" fill="rgb(16, 185, 129)" stroke="white" stroke-width="1.5" />
            <circle cx="320" cy="60" r="5" fill="rgb(16, 185, 129)" stroke="white" stroke-width="1.5" />
            <circle cx="480" cy="30" r="5" fill="rgb(16, 185, 129)" stroke="white" stroke-width="1.5" />
          </svg>
          <div class="flex justify-between text-2xs font-semibold text-slate-400 px-1 mt-2">
            <span>Januari</span><span>Februari</span><span>Maret</span><span>April</span><span>Mei</span><span>Juni</span>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-900 p-5 md:p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm transition-colors flex flex-col justify-between">
        <div>
          <h3 class="font-bold text-slate-900 dark:text-white text-base border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">Kalkulator PPN/PPh 23 Cepat</h3>
          <p class="text-xs text-slate-400 mb-4">Gunakan widget pembantu ini untuk menghitung draf pajak langsung.</p>
          <div class="space-y-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nilai Transaksi (DPP)</label>
              <input
                v-model.number="kalkulatorDpp"
                type="number"
                min="0"
                step="100000"
                placeholder="0"
                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500"
              />
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div class="bg-slate-50 dark:bg-slate-800 p-2.5 rounded-lg border border-slate-100 dark:border-slate-700">
                <span class="text-slate-400 block font-semibold text-3xs uppercase mb-0.5">PPN ({{ ppnRate }}%)</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ formatIDR(kalkulatorPpn) }}</span>
              </div>
              <div class="bg-slate-50 dark:bg-slate-800 p-2.5 rounded-lg border border-slate-100 dark:border-slate-700">
                <span class="text-slate-400 block font-semibold text-3xs uppercase mb-0.5">PPh 23 ({{ pph23Rate }}%)</span>
                <span class="font-bold text-rose-500">{{ formatIDR(kalkulatorPph23) }}</span>
              </div>
            </div>
            <!-- Ringkasan Net -->
            <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50 p-2.5 rounded-lg">
              <span class="text-slate-400 block font-semibold text-3xs uppercase mb-0.5">Total Tagihan Klien (DPP + PPN)</span>
              <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">{{ formatIDR((kalkulatorDpp || 0) + kalkulatorPpn) }}</span>
            </div>
          </div>
        </div>
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 mt-4 text-center">
          <router-link to="/taxes" class="text-xs text-emerald-500 dark:text-emerald-400 font-semibold hover:underline">
            Lihat Modul Pajak Lengkap &rarr;
          </router-link>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         DASHBOARD ARUS KAS BERDASARKAN METODE PEMBAYARAN
    ══════════════════════════════════════════════════════════════ -->
    <div v-if="cashFlow" class="space-y-6">

      <!-- Section header -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <span class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-sky-500 rounded-lg flex items-center justify-center text-white text-sm">💸</span>
            Arus Kas – Metode Pembayaran
          </h2>
          <p class="text-xs text-slate-400 mt-1 ml-10">
            Breakdown total pemasukan &amp; pengeluaran berdasarkan cara transaksi dilakukan
          </p>
        </div>
        <!-- Tab toggle -->
        <div class="flex bg-slate-100 dark:bg-slate-800 rounded-xl p-1 text-xs font-semibold gap-1">
          <button
            @click="activeTab = 'total'"
            :class="activeTab === 'total' ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow' : 'text-slate-400'"
            class="px-3 py-1.5 rounded-lg transition-all"
          >Semua Waktu</button>
          <button
            @click="activeTab = 'bulan_ini'"
            :class="activeTab === 'bulan_ini' ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow' : 'text-slate-400'"
            class="px-3 py-1.5 rounded-lg transition-all"
          >Bulan Ini</button>
        </div>
      </div>

      <!-- Grand total strip -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 text-white p-4 rounded-2xl shadow">
          <p class="text-xs font-semibold opacity-75 uppercase tracking-wider">Total Kas Masuk</p>
          <p class="text-2xl font-bold mt-1">{{ formatIDRCompact(cashFlow.total_masuk) }}</p>
        </div>
        <div class="bg-gradient-to-r from-rose-600 to-rose-500 text-white p-4 rounded-2xl shadow">
          <p class="text-xs font-semibold opacity-75 uppercase tracking-wider">Total Kas Keluar</p>
          <p class="text-2xl font-bold mt-1">{{ formatIDRCompact(cashFlow.total_keluar) }}</p>
        </div>
        <div :class="cashFlow.net_total >= 0 ? 'from-sky-600 to-indigo-600' : 'from-amber-600 to-rose-600'" class="bg-gradient-to-r text-white p-4 rounded-2xl shadow">
          <p class="text-xs font-semibold opacity-75 uppercase tracking-wider">Selisih Bersih</p>
          <p class="text-2xl font-bold mt-1">{{ formatIDRCompact(cashFlow.net_total) }}</p>
        </div>
      </div>

      <!-- Per-method cards grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div
          v-for="m in (activeTab === 'total' ? cashFlow.by_method : cashFlow.bulan_ini_by_method)"
          :key="m.metode"
          class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm overflow-hidden"
        >
          <!-- Card header -->
          <div :class="[methodConfig[m.metode]?.bg ?? 'bg-slate-50 dark:bg-slate-800', 'px-4 pt-4 pb-3 border-b', methodConfig[m.metode]?.border ?? '']">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-xl">{{ methodConfig[m.metode]?.icon }}</span>
                <span :class="[methodConfig[m.metode]?.color ?? '', 'font-bold text-sm leading-tight']">{{ m.label }}</span>
              </div>
              <span
                :class="m.net >= 0 ? 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300'"
                class="text-2xs font-bold px-2 py-0.5 rounded-full"
              >{{ m.net >= 0 ? '+' : '' }}{{ formatIDRCompact(m.net) }}</span>
            </div>
          </div>

          <!-- Card body -->
          <div class="px-4 py-3 space-y-3">
            <!-- Kas Masuk bar -->
            <div>
              <div class="flex justify-between text-2xs mb-1">
                <span class="text-slate-400 font-semibold">↑ Kas Masuk</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ formatIDRCompact(m.kas_masuk) }}</span>
              </div>
              <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div
                  class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600 rounded-full transition-all duration-700"
                  :style="{ width: `${Math.min((m.kas_masuk / maxMasuk) * 100, 100)}%` }"
                />
              </div>
              <div v-if="activeTab === 'total'" class="text-3xs text-slate-400 mt-0.5 text-right">
                {{ (m as any).pct_masuk }}% dari total
              </div>
            </div>

            <!-- Kas Keluar bar -->
            <div>
              <div class="flex justify-between text-2xs mb-1">
                <span class="text-slate-400 font-semibold">↓ Kas Keluar</span>
                <span class="text-rose-500 font-bold">{{ formatIDRCompact(m.kas_keluar) }}</span>
              </div>
              <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div
                  class="h-full bg-gradient-to-r from-rose-400 to-rose-600 rounded-full transition-all duration-700"
                  :style="{ width: `${Math.min((m.kas_keluar / maxKeluar) * 100, 100)}%` }"
                />
              </div>
              <div v-if="activeTab === 'total'" class="text-3xs text-slate-400 mt-0.5 text-right">
                {{ (m as any).pct_keluar }}% dari total
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Trend + Monthly Table -->
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- 6-Month Trend Sparkline -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 mb-5">
            <div>
              <h3 class="font-bold text-slate-900 dark:text-white text-sm">Tren 6 Bulan Terakhir</h3>
              <p class="text-xs text-slate-400 mt-0.5">Grafik pergerakan arus kas masuk &amp; keluar</p>
            </div>
            <div class="flex items-center gap-3 text-2xs font-semibold">
              <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded-full bg-emerald-500 inline-block"></span>Masuk</span>
              <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded-full bg-rose-500 inline-block"></span>Keluar</span>
            </div>
          </div>

          <!-- SVG Sparkline chart -->
          <div class="relative h-40">
            <svg viewBox="0 0 300 60" class="w-full h-full overflow-visible" preserveAspectRatio="none">
              <!-- Grid -->
              <line x1="0" y1="20" x2="300" y2="20" stroke="#f1f5f9" stroke-width="0.5" class="dark:stroke-slate-800"/>
              <line x1="0" y1="40" x2="300" y2="40" stroke="#f1f5f9" stroke-width="0.5" class="dark:stroke-slate-800"/>
              <line x1="0" y1="58" x2="300" y2="58" stroke="#e2e8f0" stroke-width="0.8" class="dark:stroke-slate-700"/>

              <!-- Shaded area: Kas Masuk -->
              <defs>
                <linearGradient id="gradMasuk" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#10b981" stop-opacity="0.3"/>
                  <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                </linearGradient>
              </defs>
              <polygon
                :points="`0,60 ${trendPolyline('kas_masuk')} 300,60`"
                fill="url(#gradMasuk)"
              />
              <polyline
                :points="trendPolyline('kas_masuk')"
                fill="none"
                stroke="#10b981"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />

              <!-- Kas Keluar line -->
              <polyline
                :points="trendPolyline('kas_keluar')"
                fill="none"
                stroke="#f43f5e"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-dasharray="3 2"
              />
            </svg>

            <!-- Month labels -->
            <div class="flex justify-between text-3xs font-semibold text-slate-400 px-0.5 mt-1">
              <span v-for="t in cashFlow.trend_6_bulan" :key="t.bulan_num">{{ t.bulan }}</span>
            </div>
          </div>

          <!-- Trend table -->
          <div class="mt-4 overflow-x-auto">
            <table class="w-full text-xs border-collapse">
              <thead>
                <tr class="text-slate-400 text-3xs uppercase font-bold tracking-wider border-b border-slate-100 dark:border-slate-800">
                  <th class="py-2 text-left">Bulan</th>
                  <th class="py-2 text-right text-emerald-500">Kas Masuk</th>
                  <th class="py-2 text-right text-rose-500">Kas Keluar</th>
                  <th class="py-2 text-right">Net</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                <tr v-for="t in cashFlow.trend_6_bulan" :key="t.bulan_num" class="text-slate-700 dark:text-slate-300">
                  <td class="py-2 font-medium">{{ t.bulan }}</td>
                  <td class="py-2 text-right text-emerald-600 dark:text-emerald-400 font-semibold">{{ formatIDRCompact(t.kas_masuk) }}</td>
                  <td class="py-2 text-right text-rose-500 font-semibold">{{ formatIDRCompact(t.kas_keluar) }}</td>
                  <td class="py-2 text-right font-bold" :class="t.net >= 0 ? 'text-sky-600 dark:text-sky-400' : 'text-amber-600 dark:text-amber-400'">
                    {{ t.net >= 0 ? '+' : '' }}{{ formatIDRCompact(t.net) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Method breakdown donut-style table -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm">
          <div class="border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
            <h3 class="font-bold text-slate-900 dark:text-white text-sm">Proporsi per Metode</h3>
            <p class="text-xs text-slate-400 mt-0.5">Persentase kontribusi masing-masing metode</p>
          </div>

          <div class="space-y-4">
            <!-- Kas Masuk breakdown -->
            <div>
              <p class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">↑ Kas Masuk</p>
              <div v-for="m in cashFlow.by_method" :key="'in-' + m.metode" class="mb-2">
                <div class="flex justify-between text-xs mb-1">
                  <span class="flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                    <span>{{ methodConfig[m.metode]?.icon }}</span>
                    {{ m.label }}
                  </span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ m.pct_masuk }}%</span>
                </div>
                <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-700"
                    :style="{ width: `${m.pct_masuk}%`, backgroundColor: methodConfig[m.metode]?.dot }"
                  />
                </div>
              </div>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-4">
              <p class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">↓ Kas Keluar</p>
              <div v-for="m in cashFlow.by_method" :key="'out-' + m.metode" class="mb-2">
                <div class="flex justify-between text-xs mb-1">
                  <span class="flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                    <span>{{ methodConfig[m.metode]?.icon }}</span>
                    {{ m.label }}
                  </span>
                  <span class="font-bold text-rose-500">{{ m.pct_keluar }}%</span>
                </div>
                <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full opacity-70 transition-all duration-700"
                    :style="{ width: `${m.pct_keluar}%`, backgroundColor: methodConfig[m.metode]?.dot }"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Total summary footer -->
          <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-3 text-xs">
            <div class="bg-emerald-50 dark:bg-emerald-950/40 rounded-xl p-2.5 border border-emerald-100 dark:border-emerald-900">
              <p class="text-slate-400 text-3xs font-bold uppercase">Total Masuk</p>
              <p class="text-emerald-700 dark:text-emerald-400 font-bold mt-0.5">{{ formatIDRCompact(cashFlow.total_masuk) }}</p>
            </div>
            <div class="bg-rose-50 dark:bg-rose-950/40 rounded-xl p-2.5 border border-rose-100 dark:border-rose-900">
              <p class="text-slate-400 text-3xs font-bold uppercase">Total Keluar</p>
              <p class="text-rose-600 dark:text-rose-400 font-bold mt-0.5">{{ formatIDRCompact(cashFlow.total_keluar) }}</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ── Event Profitability Table ────────────────────────────── -->
    <div class="bg-white dark:bg-slate-900 p-5 md:p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm transition-colors">
      <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
        <div>
          <h3 class="font-bold text-slate-900 dark:text-white text-base">Profitabilitas Event Terbesar</h3>
          <p class="text-xs text-slate-400 mt-1">Ranking laba bersih dihitung dari nilai kontrak dikurangi biaya vendor, operasional, dan beban perpajakan.</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-150 dark:border-slate-800 text-slate-400 text-3xs uppercase font-bold tracking-wider">
              <th class="pb-3 pt-1">Event / Kategori</th>
              <th class="pb-3 pt-1">Nilai Kontrak</th>
              <th class="pb-3 pt-1">Realisasi Biaya</th>
              <th class="pb-3 pt-1">Estimasi Pajak</th>
              <th class="pb-3 pt-1">Laba Bersih</th>
              <th class="pb-3 pt-1 text-right">Margin %</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm">
            <tr v-for="(ev) in eventProfitability" :key="ev.event_id" class="text-slate-700 dark:text-slate-300 hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
              <td class="py-3.5">
                <span class="font-semibold block text-slate-900 dark:text-white leading-tight">{{ ev.nama_event }}</span>
                <span class="text-3xs uppercase font-semibold text-slate-400 tracking-wider inline-block mt-1">{{ ev.jenis_event }}</span>
              </td>
              <td class="py-3.5 font-medium">{{ formatIDR(ev.nilai_kontrak) }}</td>
              <td class="py-3.5 text-slate-500 dark:text-slate-400">{{ formatIDR(ev.total_biaya) }}</td>
              <td class="py-3.5 text-amber-500 font-medium">{{ formatIDR(ev.pajak_dikeluarkan) }}</td>
              <td class="py-3.5 text-emerald-500 font-semibold">{{ formatIDR(ev.laba_bersih) }}</td>
              <td class="py-3.5 text-right">
                <span :class="[
                  ev.margin_persentase > 40 ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/80' :
                  ev.margin_persentase > 25 ? 'bg-sky-950 text-sky-400 border border-sky-800/80' : 'bg-amber-950 text-amber-400 border border-amber-800/80',
                  'px-2 py-1 rounded text-2xs font-bold'
                ]">{{ ev.margin_persentase }}%</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
