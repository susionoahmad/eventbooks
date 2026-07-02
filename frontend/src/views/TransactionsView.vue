<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const transactions = ref<any[]>([])
const events = ref<any[]>([])
const vendors = ref<any[]>([])

const filterTipe = ref('all')
const isModalOpen = ref(false)

const newTrx = ref({
  tanggal: new Date().toISOString().split('T')[0],
  tipe: 'kas_keluar',
  kategori: 'operasional',
  event_id: '',
  vendor_id: '',
  deskripsi: '',
  nominal: 0,
  metode_pembayaran: 'transfer_bank',
  calculate_pph23: false,
  calculate_pph21: false,
  calculate_ppn_masukan: false,
  pihak_terkait_nama: '',
  pihak_terkait_npwp: '',
  nomor_faktur_pajak: ''
})

const getKategoriLabel = (kat: string) => {
  const map: Record<string, string> = {
    dp_event: 'DP Event (Masuk)',
    pelunasan_event: 'Pelunasan Event (Masuk)',
    pendapatan_lain: 'Pendapatan Lain',
    pembayaran_vendor: 'Bayar Vendor',
    transportasi: 'Transportasi',
    konsumsi: 'Konsumsi',
    operasional: 'Operasional',
    marketing: 'Marketing'
  }
  return map[kat] || kat
}

const fetchTransactions = async () => {
  try {
    const params: any = {}
    if (filterTipe.value !== 'all') {
      params.tipe = filterTipe.value
    }
    const res = await api.get('/transactions', { params })
    transactions.value = res.data.data
  } catch (err) {
    console.error('Error fetching transactions:', err)
  }
}

const fetchEventsAndVendors = async () => {
  try {
    const [eventsRes, vendorsRes] = await Promise.all([
      api.get('/events'),
      api.get('/vendors')
    ])
    events.value = eventsRes.data.data
    vendors.value = vendorsRes.data.data
  } catch (err) {
    console.error('Error fetching events or vendors:', err)
  }
}

onMounted(() => {
  fetchTransactions()
  fetchEventsAndVendors()
})

const changeFilterTipe = (tipe: string) => {
  filterTipe.value = tipe
  fetchTransactions()
}

const onVendorChange = () => {
  if (newTrx.value.vendor_id) {
    const selectedVendor = vendors.value.find(v => v.id === newTrx.value.vendor_id)
    if (selectedVendor) {
      newTrx.value.pihak_terkait_nama = selectedVendor.nama_vendor
      newTrx.value.pihak_terkait_npwp = selectedVendor.npwp || ''
    }
  }
}

const onKategoriChange = () => {
  if (newTrx.value.kategori !== 'pembayaran_vendor') {
    newTrx.value.vendor_id = ''
  }
}

// Dynamic tax preview inside form
const taxPreview = computed(() => {
  const nominalGross = parseFloat(newTrx.value.nominal as any) || 0
  if (!nominalGross) return null

  const hasNpwp = !!newTrx.value.pihak_terkait_npwp
  let pphAmount = 0
  let pphRateText = ''
  let pphType = ''

  let ppnAmount = 0
  let ppnRateText = ''

  if (newTrx.value.calculate_pph23) {
    const rate = hasNpwp ? 2.00 : 4.00
    pphAmount = (nominalGross * rate) / 100
    pphRateText = `${rate}%`
    pphType = 'PPh 23'
  } else if (newTrx.value.calculate_pph21) {
    const rate = hasNpwp ? 5.00 : 6.00
    const dpp = nominalGross * 0.50
    pphAmount = (dpp * rate) / 100
    pphRateText = `${rate}% (dari 50% DPP)`
    pphType = 'PPh 21'
  }

  if (newTrx.value.calculate_ppn_masukan) {
    const rate = 12.00
    ppnAmount = (nominalGross * rate) / 100
    ppnRateText = `${rate}%`
  }

  const netPayout = nominalGross + ppnAmount - pphAmount

  if (pphAmount === 0 && ppnAmount === 0) return null

  return {
    pphAmount,
    pphRateText,
    pphType,
    ppnAmount,
    ppnRateText,
    netPayout
  }
})

watch(() => newTrx.value.tipe, (newTipe) => {
  if (newTipe === 'kas_masuk') {
    newTrx.value.kategori = 'pendapatan_lain'
  } else {
    newTrx.value.kategori = 'operasional'
  }
})

const saveTransaction = async () => {
  try {
    const payload: any = { ...newTrx.value }
    if (!payload.event_id) delete payload.event_id
    if (!payload.vendor_id) delete payload.vendor_id
    
    await api.post('/transactions', payload)
    
    // reset form
    newTrx.value = {
      tanggal: new Date().toISOString().split('T')[0],
      tipe: 'kas_keluar',
      kategori: 'operasional',
      event_id: '',
      vendor_id: '',
      deskripsi: '',
      nominal: 0,
      metode_pembayaran: 'transfer_bank',
      calculate_pph23: false,
      calculate_pph21: false,
      calculate_ppn_masukan: false,
      pihak_terkait_nama: '',
      pihak_terkait_npwp: '',
      nomor_faktur_pajak: ''
    }
    
    isModalOpen.value = false
    fetchTransactions()
  } catch (err) {
    console.error('Error saving transaction:', err)
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
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Buku Kas Pembukuan</h1>
        <p class="text-xs text-slate-500 mt-1">Ledger transaksi kas masuk dan kas keluar terintegrasi perpajakan.</p>
      </div>
      <button 
        @click="isModalOpen = true"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-colors cursor-pointer"
      >
        + Catat Transaksi
      </button>
    </div>

    <!-- Filters -->
    <div class="flex space-x-1.5">
      <button @click="changeFilterTipe('all')" :class="[filterTipe === 'all' ? 'bg-slate-900 dark:bg-slate-800 text-white' : 'bg-white dark:bg-slate-900 text-slate-400', 'px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-xs font-semibold cursor-pointer']">Semua</button>
      <button @click="changeFilterTipe('kas_masuk')" :class="[filterTipe === 'kas_masuk' ? 'bg-emerald-950 text-emerald-400 border-emerald-800' : 'bg-white dark:bg-slate-900 text-slate-400', 'px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-xs font-semibold cursor-pointer']">Kas Masuk</button>
      <button @click="changeFilterTipe('kas_keluar')" :class="[filterTipe === 'kas_keluar' ? 'bg-rose-950 text-rose-400 border-rose-800' : 'bg-white dark:bg-slate-900 text-slate-400', 'px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-xs font-semibold cursor-pointer']">Kas Keluar</button>
    </div>

    <!-- Ledger Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/85 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 text-slate-400 text-3xs font-bold uppercase tracking-wider">
              <th class="p-4">No. Ref / Tanggal</th>
              <th class="p-4">Event / Deskripsi</th>
              <th class="p-4">Kategori / Metode</th>
              <th class="p-4 text-right">Nominal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-sm">
            <tr v-for="trx in transactions" :key="trx.id" class="hover:bg-slate-50/40 dark:hover:bg-slate-800/10 text-slate-700 dark:text-slate-350 transition-colors">
              <td class="p-4">
                <span class="font-mono text-xs text-slate-400 block font-semibold">{{ trx.nomor_transaksi }}</span>
                <span class="text-2xs text-slate-450 block mt-0.5">{{ trx.tanggal }}</span>
              </td>
              <td class="p-4">
                <span class="font-bold text-slate-900 dark:text-white block">{{ trx.event?.nama_event || 'Pengeluaran Umum' }}</span>
                <span class="text-xs text-slate-500 block mt-0.5 truncate max-w-sm">{{ trx.deskripsi }}</span>
              </td>
              <td class="p-4">
                <span class="text-xs font-semibold block">{{ getKategoriLabel(trx.kategori) }}</span>
                <span class="text-3xs uppercase text-slate-400 tracking-wider block mt-0.5">{{ trx.metode_pembayaran.replace('_', ' ') }}</span>
              </td>
              <td class="p-4 text-right font-extrabold">
                <span :class="trx.tipe === 'kas_masuk' ? 'text-emerald-500' : 'text-rose-500'">
                  {{ trx.tipe === 'kas_masuk' ? '+' : '-' }} {{ formatIDR(trx.nominal) }}
                </span>
                <span v-if="trx.nominal_gross && Math.abs(trx.nominal_gross - trx.nominal) > 0.01" class="text-3xs text-slate-400 font-semibold block mt-0.5">
                  Gross: {{ formatIDR(trx.nominal_gross) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Record Transaction Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Pencatatan Buku Kas Baru</h3>

        <form @submit.prevent="saveTransaction" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-300">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Transaksi</label>
              <input v-model="newTrx.tanggal" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tipe Kas</label>
              <select v-model="newTrx.tipe" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
                <option value="kas_masuk">Kas Masuk (Inflow)</option>
                <option value="kas_keluar">Kas Keluar (Outflow)</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Kategori Transaksi</label>
              <select v-model="newTrx.kategori" @change="onKategoriChange" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
                <template v-if="newTrx.tipe === 'kas_masuk'">
                  <option value="dp_event" disabled class="opacity-50 text-slate-400">DP Event (Wajib dibuat via Invoice)</option>
                  <option value="pelunasan_event" disabled class="opacity-50 text-slate-400">Pelunasan Event (Wajib dibuat via Invoice)</option>
                  <option value="pendapatan_lain">Pendapatan Lain</option>
                </template>
                <template v-else>
                  <option value="pembayaran_vendor">Pembayaran Vendor</option>
                  <option value="transportasi">Transportasi</option>
                  <option value="konsumsi">Konsumsi</option>
                  <option value="operasional">Operasional</option>
                  <option value="marketing">Marketing</option>
                </template>
              </select>
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Metode Pembayaran</label>
              <select v-model="newTrx.metode_pembayaran" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
                <option value="transfer_bank">Transfer Bank</option>
                <option value="cash">Tunai / Cash</option>
                <option value="card">Kartu Kredit/Debet</option>
                <option value="e_wallet">E-Wallet</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nominal (Rupiah)</label>
              <input v-model="newTrx.nominal" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tautkan ke Event</label>
              <select v-model="newTrx.event_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
                <option value="">-- Tanpa Event --</option>
                <option v-for="ev in events" :key="ev.id" :value="ev.id">{{ ev.nama_event }}</option>
              </select>
            </div>
          </div>

          <div v-if="newTrx.tipe === 'kas_keluar' && newTrx.kategori === 'pembayaran_vendor'" class="grid grid-cols-1 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pilih Vendor</label>
              <select v-model="newTrx.vendor_id" @change="onVendorChange" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
                <option value="">-- Pilih Vendor --</option>
                <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.nama_vendor }} ({{ v.kategori }})</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Deskripsi Detail</label>
            <textarea v-model="newTrx.deskripsi" rows="2" placeholder="e.g. Pembayaran DP sewa panggung ke vendor Dekorindo" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required></textarea>
          </div>

          <!-- Tax Action Box (Outflows only) -->
          <div v-if="newTrx.tipe === 'kas_keluar'" class="border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-3 bg-slate-50/50 dark:bg-slate-800/20">
            <h4 class="font-bold text-2xs uppercase tracking-wider text-slate-400">Integrasi Pajak Vendor & Payout</h4>
            
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newTrx.calculate_pph23" type="checkbox" :disabled="newTrx.calculate_pph21" class="rounded text-emerald-600 focus:ring-emerald-500" />
                <span>PPh 23 (Jasa/Sewa)</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newTrx.calculate_pph21" type="checkbox" :disabled="newTrx.calculate_pph23" class="rounded text-emerald-600 focus:ring-emerald-500" />
                <span>PPh 21 (Freelancer)</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newTrx.calculate_ppn_masukan" type="checkbox" class="rounded text-emerald-600 focus:ring-emerald-500" />
                <span>PPN Masukan (12%)</span>
              </label>
            </div>

            <div v-if="newTrx.calculate_pph23 || newTrx.calculate_pph21 || newTrx.calculate_ppn_masukan" class="space-y-3 pt-2">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Vendor/Penerima</label>
                  <input v-model="newTrx.pihak_terkait_nama" type="text" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 rounded-lg text-xs" required />
                </div>
                <div>
                  <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">NPWP Vendor/Penerima</label>
                  <input v-model="newTrx.pihak_terkait_npwp" type="text" placeholder="Jika tidak ada, PPh 2x lipat" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 rounded-lg text-xs" />
                </div>
              </div>

              <div v-if="newTrx.calculate_ppn_masukan" class="grid grid-cols-1 gap-2">
                <div>
                  <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nomor Faktur Pajak Masukan</label>
                  <input v-model="newTrx.nomor_faktur_pajak" type="text" placeholder="Masukkan 16 digit Nomor Seri Faktur Pajak" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 rounded-lg text-xs" />
                </div>
              </div>

              <!-- Dynamic Preview -->
              <div v-if="taxPreview" class="bg-emerald-950/30 border border-emerald-900/50 p-4 rounded-xl text-xs space-y-2 text-emerald-400">
                <div class="flex justify-between items-center border-b border-emerald-900/40 pb-2">
                  <span class="font-semibold">Buku Kas Bersih (Net Payout Ledger)</span>
                  <span class="text-3xs text-slate-400 uppercase font-bold tracking-wider">Kalkulasi Otomatis</span>
                </div>
                <div class="space-y-1.5 text-slate-350">
                  <div class="flex justify-between">
                    <span>Nominal Bruto (Gross Contract):</span>
                    <span class="font-mono">{{ formatIDR(newTrx.nominal) }}</span>
                  </div>
                  <div v-if="taxPreview.pphAmount > 0" class="flex justify-between text-rose-400">
                    <span>Potongan {{ taxPreview.pphType }} ({{ taxPreview.pphRateText }}):</span>
                    <span class="font-mono">- {{ formatIDR(taxPreview.pphAmount) }}</span>
                  </div>
                  <div v-if="taxPreview.ppnAmount > 0" class="flex justify-between text-emerald-400">
                    <span>Tambahan PPN Masukan ({{ taxPreview.ppnRateText }}):</span>
                    <span class="font-mono">+ {{ formatIDR(taxPreview.ppnAmount) }}</span>
                  </div>
                </div>
                <div class="flex justify-between items-center border-t border-emerald-900/40 pt-2 font-extrabold text-sm text-emerald-400">
                  <span>Kas Bersih Ditransfer (Net Payout):</span>
                  <span class="font-mono">{{ formatIDR(taxPreview.netPayout) }}</span>
                </div>
                <span class="block text-3xs text-slate-400 italic">Ledger akan membukukan nominal Net Payout untuk sinkronisasi mutasi bank yang presisi. Selisih PPh akan diposting ke Utang Pajak.</span>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-655 dark:text-slate-350 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Simpan & Posting</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
