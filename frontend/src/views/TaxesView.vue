<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const taxes = ref<any[]>([])
const summaries = ref<any[]>([])
const filterMasa = ref('2026-06')

// Calculate summaries for active filter period
const activeSummary = computed(() => {
  const found = summaries.value.find(s => s.masa_pajak === filterMasa.value)
  if (found) {
    return {
      ppnKeluaran: (floatOrZero(found.total_ppn_keluaran)),
      pph23: (floatOrZero(found.total_pph_23)),
      pph21: (floatOrZero(found.total_pph_21)),
      terutang: (floatOrZero(found.total_terutang)),
      dibayar: (floatOrZero(found.total_dibayar))
    }
  }
  return { ppnKeluaran: 0, pph23: 0, pph21: 0, terutang: 0, dibayar: 0 }
})

const floatOrZero = (val: any) => {
  const parsed = parseFloat(val)
  return isNaN(parsed) ? 0 : parsed
}

const fetchTaxes = async () => {
  try {
    const res = await api.get('/taxes', {
      params: { masa_pajak: filterMasa.value }
    })
    taxes.value = res.data.data
  } catch (err) {
    console.error('Error fetching taxes list:', err)
  }
}

const fetchSummaries = async () => {
  try {
    const res = await api.get('/taxes/summary')
    summaries.value = res.data.data
    
    // Auto populate filterMasa with the latest available period if none selected or not in summary
    if (summaries.value.length > 0 && !summaries.value.some(s => s.masa_pajak === filterMasa.value)) {
      filterMasa.value = summaries.value[0].masa_pajak
    }
  } catch (err) {
    console.error('Error fetching tax summaries:', err)
  }
}

const loadData = () => {
  fetchSummaries()
  fetchTaxes()
}

onMounted(() => {
  loadData()
})

// Refetch details when filter month changes
watch(filterMasa, () => {
  fetchTaxes()
})

const toggleTaxStatus = async (tax: any) => {
  const targetStatus = tax.status === 'terutang' ? 'dibayar' : 'terutang'
  try {
    await api.put(`/taxes/${tax.id}/status`, { status: targetStatus })
    loadData()
  } catch (err) {
    console.error('Error updating tax status:', err)
  }
}

const getTaxTypeLabel = (tipe: string) => {
  switch (tipe) {
    case 'ppn_keluaran': return 'PPN Keluaran'
    case 'ppn_masukan': return 'PPN Masukan'
    case 'pph_21': return 'PPh 21 (Wajib Pajak Orang Pribadi)'
    case 'pph_23': return 'PPh 23 (Wajib Pajak Badan/Jasa)'
    default: return tipe
  }
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
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Rekapitulasi Perpajakan Bulanan</h1>
        <p class="text-xs text-slate-500 mt-1">Lacak kewajiban pemotongan pajak PPN Keluaran/Masukan, PPh 21, dan PPh 23.</p>
      </div>
      <div class="flex items-center space-x-2">
        <select v-model="filterMasa" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 outline-none">
          <option v-for="s in summaries" :key="s.masa_pajak" :value="s.masa_pajak">
            {{ s.masa_pajak }}
          </option>
          <option v-if="summaries.length === 0" value="2026-06">Juni 2026</option>
        </select>
      </div>
    </div>

    <!-- Tax Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 rounded-xl">
        <span class="text-3xs uppercase font-bold text-slate-400 tracking-wider">PPN Keluaran</span>
        <span class="text-base font-extrabold text-slate-800 dark:text-white block mt-1">{{ formatIDR(activeSummary.ppnKeluaran) }}</span>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 rounded-xl">
        <span class="text-3xs uppercase font-bold text-slate-400 tracking-wider">PPh 23 Terpotong</span>
        <span class="text-base font-extrabold text-slate-800 dark:text-white block mt-1">{{ formatIDR(activeSummary.pph23) }}</span>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 rounded-xl">
        <span class="text-3xs uppercase font-bold text-slate-400 tracking-wider">PPh 21 Talent</span>
        <span class="text-base font-extrabold text-slate-800 dark:text-white block mt-1">{{ formatIDR(activeSummary.pph21) }}</span>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 rounded-xl">
        <span class="text-3xs uppercase font-bold text-slate-400 tracking-wider">Total Terutang</span>
        <span class="text-base font-extrabold text-amber-500 block mt-1">{{ formatIDR(activeSummary.terutang) }}</span>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 rounded-xl">
        <span class="text-3xs uppercase font-bold text-slate-400 tracking-wider">Total Disetor (Lunas)</span>
        <span class="text-base font-extrabold text-emerald-500 block mt-1">{{ formatIDR(activeSummary.dibayar) }}</span>
      </div>
    </div>

    <!-- Taxes Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/85 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 text-slate-400 text-3xs font-bold uppercase tracking-wider">
              <th class="p-4">Jenis Pajak / Event</th>
              <th class="p-4">Pihak Terkait / NPWP</th>
              <th class="p-4">Masa Pajak / DPP</th>
              <th class="p-4">Tarif & Nominal</th>
              <th class="p-4">Status</th>
              <th class="p-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-sm">
            <tr v-for="tax in taxes" :key="tax.id" class="hover:bg-slate-50/40 dark:hover:bg-slate-800/10 text-slate-700 dark:text-slate-350 transition-colors">
              <td class="p-4">
                <span class="font-bold text-slate-900 dark:text-white block">{{ getTaxTypeLabel(tax.tipe_pajak) }}</span>
                <span class="text-xs text-slate-455 block mt-0.5 truncate max-w-sm">{{ tax.event?.nama_event || 'General' }}</span>
              </td>
              <td class="p-4">
                <span class="font-semibold text-slate-800 dark:text-slate-200 block">{{ tax.pihak_terkait_nama || '-' }}</span>
                <span class="font-mono text-3xs text-slate-400 block mt-0.5">{{ tax.pihak_terkait_npwp || 'Tanpa NPWP (Tarif Maks)' }}</span>
              </td>
              <td class="p-4 text-xs">
                <span class="block">Masa: <strong class="font-mono">{{ tax.masa_pajak }}</strong></span>
                <span class="block text-3xs text-slate-400 mt-0.5">DPP: {{ formatIDR(tax.dpp) }}</span>
              </td>
              <td class="p-4">
                <span class="text-xs font-semibold block">{{ tax.tarif }}%</span>
                <span class="text-xs text-amber-500 font-bold block mt-0.5">{{ formatIDR(tax.nominal_pajak) }}</span>
              </td>
              <td class="p-4">
                <span :class="[tax.status === 'terutang' ? 'bg-amber-950 text-amber-450 border-amber-900/50' : 'bg-emerald-950 text-emerald-450 border-emerald-900/50', 'px-2 py-0.5 border rounded text-3xs font-bold uppercase tracking-wider']">
                  {{ tax.status }}
                </span>
              </td>
              <td class="p-4 text-right">
                <button 
                  v-if="authStore.userRole !== 'staff'"
                  @click="toggleTaxStatus(tax)"
                  :class="[tax.status === 'terutang' ? 'bg-emerald-950/20 text-emerald-400 hover:bg-emerald-950/40' : 'bg-rose-950/20 text-rose-400 hover:bg-rose-950/40', 'text-2xs px-2.5 py-1.5 rounded font-semibold transition-colors cursor-pointer']"
                >
                  Set {{ tax.status === 'terutang' ? 'Dibayar' : 'Terutang' }}
                </button>
              </td>
            </tr>
            <tr v-if="taxes.length === 0">
              <td colspan="6" class="p-8 text-center text-slate-500 text-xs">
                Tidak ada kewajiban pajak tercatat untuk masa pajak ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
