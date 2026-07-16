<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const transactions = ref<any[]>([])
const events = ref<any[]>([])
const vendors = ref<any[]>([])

const filterTipe = ref('all')
const filterStartDate = ref('')
const filterEndDate = ref('')
const isModalOpen = ref(false)

const newTrx = ref({
  tanggal: new Date().toISOString().split('T')[0],
  tipe: 'kas_keluar',
  kategori: 'operasional',
  sub_kategori: '',
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
const fileDocumentInput = ref<File | null>(null)
const isSaving = ref(false)

const getKategoriLabel = (kat: string) => {
  const map: Record<string, string> = {
    dp_event: 'DP Event (Masuk)',
    pelunasan_event: 'Pelunasan Event (Masuk)',
    pendapatan_lain: 'Pendapatan Lain',
    pembayaran_vendor: 'Bayar Vendor',
    transportasi: 'Transportasi',
    konsumsi: 'Konsumsi',
    operasional: 'Operasional',
    marketing: 'Marketing',
    pajak: 'Setor Pajak DJP'
  }
  return map[kat] || kat
}

const getSubKategoriLabel = (sub: string) => {
  const map: Record<string, string> = {
    sewa_kantor_utilitas: 'Sewa Kantor & Utilitas',
    gaji_honor_staff: 'Gaji & Honor Staf',
    perlengkapan_alat_tulis: 'Perlengkapan & ATK',
    komunikasi_internet: 'Komunikasi & Internet',
    perizinan_admin: 'Perizinan & Administrasi',
    operasional_lainnya: 'Operasional Lainnya'
  }
  return map[sub] || sub
}

const fetchTransactions = async () => {
  try {
    const params: any = {}
    if (filterTipe.value !== 'all') {
      params.tipe = filterTipe.value
    }
    if (filterStartDate.value) {
      params.start_date = filterStartDate.value
    }
    if (filterEndDate.value) {
      params.end_date = filterEndDate.value
    }
    const res = await api.get('/transactions', { params })
    transactions.value = res.data.data
  } catch (err) {
    console.error('Error fetching transactions:', err)
  }
}

watch([filterStartDate, filterEndDate], () => {
  fetchTransactions()
})

const clearDateFilter = () => {
  filterStartDate.value = ''
  filterEndDate.value = ''
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
  if (newTrx.value.kategori !== 'operasional') {
    newTrx.value.sub_kategori = ''
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

const handleDocumentFileChange = (e: any) => {
  const files = e.target.files
  if (files && files.length > 0) {
    const file = files[0]
    if (file.size > 10 * 1024 * 1024) {
      alert('Ukuran berkas dokumen pendukung melebihi batas 10MB!')
      e.target.value = ''
      fileDocumentInput.value = null
      return
    }
    fileDocumentInput.value = file
  }
}

const viewDocumentFile = (url: string) => {
  const token = authStore.token || localStorage.getItem('token') || ''
  const baseUrl = api.defaults.baseURL || import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1'
  const cleanUrl = url.startsWith('/') ? url : '/' + url
  const fullUrl = `${baseUrl}${cleanUrl}?token=${token}`
  window.open(fullUrl, '_blank')
}

const saveTransaction = async () => {
  if (isSaving.value) return
  isSaving.value = true
  try {
    const formData = new FormData()
    formData.append('tanggal', newTrx.value.tanggal)
    formData.append('tipe', newTrx.value.tipe)
    formData.append('kategori', newTrx.value.kategori)
    formData.append('sub_kategori', newTrx.value.sub_kategori || '')
    
    if (newTrx.value.event_id) {
      formData.append('event_id', newTrx.value.event_id)
    }
    if (newTrx.value.vendor_id) {
      formData.append('vendor_id', newTrx.value.vendor_id)
    }
    
    formData.append('deskripsi', newTrx.value.deskripsi)
    formData.append('nominal', String(newTrx.value.nominal))
    formData.append('metode_pembayaran', newTrx.value.metode_pembayaran)
    
    formData.append('calculate_pph23', newTrx.value.calculate_pph23 ? '1' : '0')
    formData.append('calculate_pph21', newTrx.value.calculate_pph21 ? '1' : '0')
    formData.append('calculate_ppn_masukan', newTrx.value.calculate_ppn_masukan ? '1' : '0')
    
    formData.append('pihak_terkait_nama', newTrx.value.pihak_terkait_nama || '')
    formData.append('pihak_terkait_npwp', newTrx.value.pihak_terkait_npwp || '')
    formData.append('nomor_faktur_pajak', newTrx.value.nomor_faktur_pajak || '')

    if (fileDocumentInput.value) {
      formData.append('dokumen_pendukung', fileDocumentInput.value)
    }
    
    await api.post('/transactions', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    // reset form
    newTrx.value = {
      tanggal: new Date().toISOString().split('T')[0],
      tipe: 'kas_keluar',
      kategori: 'operasional',
      sub_kategori: '',
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
    fileDocumentInput.value = null
    
    isModalOpen.value = false
    fetchTransactions()
  } catch (err: any) {
    console.error('Error saving transaction:', err)
    const errors = err.response?.data?.errors
    if (errors) {
      alert(Object.values(errors).flat().join('\n'))
    } else {
      alert(err.response?.data?.message || 'Gagal menyimpan transaksi.')
    }
  } finally {
    isSaving.value = false
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
        class="px-4 py-2 bg-[#d4af37] hover:bg-[#e5c158] text-[#001b13] rounded-lg text-xs font-bold transition-colors cursor-pointer shadow-sm shadow-[#d4af37]/10"
      >
        + Catat Transaksi
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex space-x-1.5">
        <button @click="changeFilterTipe('all')" :class="[filterTipe === 'all' ? 'bg-[#00271c] text-[#d4af37] border-[#d4af37]/30' : 'bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-slate-200', 'px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800/85 text-xs font-semibold cursor-pointer']">Semua</button>
        <button @click="changeFilterTipe('kas_masuk')" :class="[filterTipe === 'kas_masuk' ? 'bg-[#00271c] text-[#d4af37] border-[#d4af37]/30' : 'bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-slate-200', 'px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800/85 text-xs font-semibold cursor-pointer']">Kas Masuk</button>
        <button @click="changeFilterTipe('kas_keluar')" :class="[filterTipe === 'kas_keluar' ? 'bg-[#00271c] text-[#d4af37] border-[#d4af37]/30' : 'bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-slate-200', 'px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800/85 text-xs font-semibold cursor-pointer']">Kas Keluar</button>
      </div>

      <!-- Date Range Filters -->
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-1.5 rounded-xl">
          <span class="text-3xs font-extrabold text-slate-400 uppercase tracking-wider">Mulai</span>
          <input
            v-model="filterStartDate"
            type="date"
            class="bg-transparent border-none text-slate-850 dark:text-slate-100 outline-none text-xs font-semibold focus:ring-0 w-28 sm:w-auto"
          />
        </div>
        <div class="flex items-center gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-1.5 rounded-xl">
          <span class="text-3xs font-extrabold text-slate-400 uppercase tracking-wider">Akhir</span>
          <input
            v-model="filterEndDate"
            type="date"
            class="bg-transparent border-none text-slate-850 dark:text-slate-100 outline-none text-xs font-semibold focus:ring-0 w-28 sm:w-auto"
          />
        </div>
        <button
          v-if="filterStartDate || filterEndDate"
          @click="clearDateFilter"
          class="text-xs text-rose-500 hover:text-rose-400 font-bold transition-colors cursor-pointer px-2 py-1"
        >
          Reset Filter
        </button>
      </div>
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
                <div v-if="trx.dokumen_pendukung_url" class="mt-1">
                  <button @click="viewDocumentFile(trx.dokumen_pendukung_url)" class="text-3xs text-emerald-500 hover:text-emerald-400 font-bold uppercase tracking-wider flex items-center bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer inline-block">
                    📄 Lampiran
                  </button>
                </div>
              </td>
              <td class="p-4">
                <span class="font-bold text-slate-900 dark:text-white block">{{ trx.event?.nama_event || 'Pengeluaran Umum' }}</span>
                <span class="text-xs text-slate-500 block mt-0.5 truncate max-w-sm">{{ trx.deskripsi }}</span>
              </td>
              <td class="p-4">
                <span class="text-xs font-semibold block">
                  {{ getKategoriLabel(trx.kategori) }}
                  <span v-if="trx.sub_kategori" class="text-slate-400 font-normal">
                    ({{ getSubKategoriLabel(trx.sub_kategori) }})
                  </span>
                </span>
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

        <form @submit.prevent="saveTransaction" class="space-y-3.5 text-xs text-slate-850 dark:text-slate-200">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-550 dark:text-slate-350 uppercase tracking-wider mb-1">Tanggal Transaksi</label>
              <input v-model="newTrx.tanggal" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-550 dark:text-slate-350 uppercase tracking-wider mb-1">Tipe Kas</label>
              <select v-model="newTrx.tipe" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100">
                <option value="kas_masuk">Kas Masuk (Inflow)</option>
                <option value="kas_keluar">Kas Keluar (Outflow)</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Kategori Transaksi</label>
              <select v-model="newTrx.kategori" @change="onKategoriChange" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100">
                <template v-if="newTrx.tipe === 'kas_masuk'">
                  <option value="dp_event" disabled class="opacity-50 text-slate-500">DP Event (Wajib dibuat via Invoice)</option>
                  <option value="pelunasan_event" disabled class="opacity-50 text-slate-500">Pelunasan Event (Wajib dibuat via Invoice)</option>
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
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Metode Pembayaran</label>
              <select v-model="newTrx.metode_pembayaran" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100">
                <option value="transfer_bank">Transfer Bank</option>
                <option value="cash">Tunai / Cash</option>
                <option value="card">Kartu Kredit/Debet</option>
                <option value="e_wallet">E-Wallet</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Nominal (Rupiah)</label>
              <input v-model="newTrx.nominal" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Tautkan ke Event</label>
              <select v-model="newTrx.event_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100">
                <option value="">-- Tanpa Event --</option>
                <option v-for="ev in events" :key="ev.id" :value="ev.id">{{ ev.nama_event }}</option>
              </select>
            </div>
          </div>

          <div v-if="newTrx.tipe === 'kas_keluar' && newTrx.kategori === 'operasional'" class="grid grid-cols-1 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Sub-Kategori Biaya Operasional</label>
              <select v-model="newTrx.sub_kategori" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100" required>
                <option value="">-- Pilih Sub-Kategori --</option>
                <option value="gaji_honor_staff">Gaji & Honor Staf</option>
                <option value="sewa_kantor_utilitas">Sewa Kantor & Utilitas</option>
                <option value="perlengkapan_alat_tulis">Perlengkapan & ATK</option>
                <option value="komunikasi_internet">Komunikasi & Internet</option>
                <option value="perizinan_admin">Perizinan & Administrasi</option>
                <option value="operasional_lainnya">Operasional Lainnya</option>
              </select>
            </div>
          </div>

          <div v-if="newTrx.tipe === 'kas_keluar' && newTrx.kategori === 'pembayaran_vendor'" class="grid grid-cols-1 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Pilih Vendor</label>
              <select v-model="newTrx.vendor_id" @change="onVendorChange" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100">
                <option value="">-- Pilih Vendor --</option>
                <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.nama_vendor }} ({{ v.kategori }})</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Deskripsi Detail</label>
            <textarea v-model="newTrx.deskripsi" rows="2" placeholder="e.g. Pembayaran DP sewa panggung ke vendor Dekorindo" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100" required></textarea>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-555 dark:text-slate-350 uppercase tracking-wider mb-1">Dokumen Pendukung / Bukti Transaksi</label>
            <input type="file" @change="handleDocumentFileChange" accept="image/*,application/pdf,application/zip,application/x-rar-compressed" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-2xs file:font-semibold file:bg-slate-100 file:text-slate-750 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300 dark:hover:file:bg-slate-700 cursor-pointer" />
            <span class="text-3xs text-slate-550 dark:text-slate-400 block mt-1 font-semibold">Format: PDF, Gambar, atau ZIP/RAR (Maks. 10MB)</span>
          </div>

          <!-- Tax Action Box (Outflows only) -->
          <div v-if="newTrx.tipe === 'kas_keluar'" class="border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-3 bg-slate-50/50 dark:bg-slate-800/20">
            <h4 class="font-bold text-2xs uppercase tracking-wider text-slate-500 dark:text-slate-350">Integrasi Pajak Vendor & Payout</h4>
            
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newTrx.calculate_pph23" type="checkbox" :disabled="newTrx.calculate_pph21" class="rounded text-[#00271c] focus:ring-[#d4af37] accent-[#d4af37]" />
                <span class="text-slate-750 dark:text-slate-250">PPh 23 (Jasa/Sewa)</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newTrx.calculate_pph21" type="checkbox" :disabled="newTrx.calculate_pph23" class="rounded text-[#00271c] focus:ring-[#d4af37] accent-[#d4af37]" />
                <span class="text-slate-750 dark:text-slate-250">PPh 21 (Freelancer)</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newTrx.calculate_ppn_masukan" type="checkbox" class="rounded text-[#00271c] focus:ring-[#d4af37] accent-[#d4af37]" />
                <span class="text-slate-750 dark:text-slate-250">PPN Masukan (12%)</span>
              </label>
            </div>

            <div v-if="newTrx.calculate_pph23 || newTrx.calculate_pph21 || newTrx.calculate_ppn_masukan" class="space-y-3 pt-2">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-3xs font-bold text-slate-550 dark:text-slate-350 uppercase tracking-wider mb-1">Nama Vendor/Penerima</label>
                  <input v-model="newTrx.pihak_terkait_nama" type="text" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 rounded-lg text-xs outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100 font-semibold" required />
                </div>
                <div>
                  <label class="block text-3xs font-bold text-slate-550 dark:text-slate-350 uppercase tracking-wider mb-1">NPWP Vendor/Penerima</label>
                  <input v-model="newTrx.pihak_terkait_npwp" type="text" placeholder="Jika tidak ada, PPh 2x lipat" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 rounded-lg text-xs outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100 font-semibold" />
                </div>
              </div>

              <div v-if="newTrx.calculate_ppn_masukan" class="grid grid-cols-1 gap-2">
                <div>
                  <label class="block text-3xs font-bold text-slate-550 dark:text-slate-350 uppercase tracking-wider mb-1">Nomor Faktur Pajak Masukan</label>
                  <input v-model="newTrx.nomor_faktur_pajak" type="text" placeholder="Masukkan 16 digit Nomor Seri Faktur Pajak" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 rounded-lg text-xs outline-none focus:border-[#d4af37] text-slate-900 dark:text-slate-100 font-semibold" />
                </div>
              </div>

              <!-- Dynamic Preview -->
              <div v-if="taxPreview" class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 p-4 rounded-xl text-xs space-y-2 text-emerald-800 dark:text-emerald-400">
                <div class="flex justify-between items-center border-b border-emerald-200 dark:border-emerald-900/40 pb-2">
                  <span class="font-bold">Buku Kas Bersih (Net Payout Ledger)</span>
                  <span class="text-3xs text-emerald-650 dark:text-slate-400 uppercase font-extrabold tracking-wider">Kalkulasi Otomatis</span>
                </div>
                <div class="space-y-1.5 text-emerald-700 dark:text-slate-300 font-medium">
                  <div class="flex justify-between">
                    <span>Nominal Bruto (Gross Contract):</span>
                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ formatIDR(newTrx.nominal) }}</span>
                  </div>
                  <div v-if="taxPreview.pphAmount > 0" class="flex justify-between text-rose-600 dark:text-rose-400 font-semibold">
                    <span>Potongan {{ taxPreview.pphType }} ({{ taxPreview.pphRateText }}):</span>
                    <span class="font-mono">- {{ formatIDR(taxPreview.pphAmount) }}</span>
                  </div>
                  <div v-if="taxPreview.ppnAmount > 0" class="flex justify-between text-emerald-600 dark:text-emerald-400 font-semibold">
                    <span>Tambahan PPN Masukan ({{ taxPreview.ppnRateText }}):</span>
                    <span class="font-mono">+ {{ formatIDR(taxPreview.ppnAmount) }}</span>
                  </div>
                </div>
                <div class="flex justify-between items-center border-t border-emerald-200 dark:border-emerald-900/40 pt-2 font-extrabold text-sm text-emerald-800 dark:text-emerald-400">
                  <span>Kas Bersih Ditransfer (Net Payout):</span>
                  <span class="font-mono">{{ formatIDR(taxPreview.netPayout) }}</span>
                </div>
                <span class="block text-3xs text-emerald-655/90 dark:text-slate-400 italic font-semibold">Ledger akan membukukan nominal Net Payout untuk sinkronisasi mutasi bank yang presisi. Selisih PPh akan diposting ke Utang Pajak.</span>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" :disabled="isSaving" @click="isModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-4 py-2 bg-[#d4af37] hover:bg-[#e5c158] text-[#001b13] rounded-lg font-bold text-xs cursor-pointer flex items-center justify-center space-x-1.5 disabled:opacity-60 disabled:cursor-not-allowed shadow-sm shadow-[#d4af37]/10">
              <svg v-if="isSaving" class="animate-spin h-3.5 w-3.5 text-[#001b13]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ isSaving ? 'Menyimpan...' : 'Simpan & Posting' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
