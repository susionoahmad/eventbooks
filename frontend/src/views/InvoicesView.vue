<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const invoices = ref<any[]>([])
const clients = ref<any[]>([])
const events = ref<any[]>([])

const isCreateModalOpen = ref(false)
const isPaymentModalOpen = ref(false)
const selectedInvoice = ref<any>(null)

const newInvoice = ref({
  client_id: '',
  event_id: '',
  nomor_invoice: '',
  tanggal: new Date().toISOString().split('T')[0],
  jatuh_tempo: new Date().toISOString().split('T')[0],
  jenis_invoice: 'dp',
  subtotal: 0,
  apply_ppn: true,
  nomor_faktur_pajak: ''
})

const paymentForm = ref({
  tanggal: new Date().toISOString().split('T')[0],
  nominal: 0,
  metode_pembayaran: 'transfer_bank',
  bukti_transfer: ''
})

const invoiceTotalPreview = computed(() => {
  const sub = newInvoice.value.subtotal
  const rate = authStore.user?.tenant?.default_ppn_rate !== undefined ? parseFloat(authStore.user.tenant.default_ppn_rate as any) : 12.00
  const ppn = newInvoice.value.apply_ppn ? (sub * rate) / 100 : 0
  return { sub, ppn, total: sub + ppn }
})

const fetchInvoices = async () => {
  try {
    const res = await api.get('/invoices')
    invoices.value = res.data.data
  } catch (err) {
    console.error('Error fetching invoices:', err)
  }
}

const fetchClientsAndEvents = async () => {
  try {
    const [clientsRes, eventsRes] = await Promise.all([
      api.get('/clients'),
      api.get('/events')
    ])
    clients.value = clientsRes.data.data
    events.value = eventsRes.data.data
  } catch (err) {
    console.error('Error fetching clients or events:', err)
  }
}

const openCreateModal = async () => {
  isCreateModalOpen.value = true
  newInvoice.value.nomor_invoice = 'Loading...'
  try {
    const res = await api.get('/invoices/next-code', { params: { tanggal: newInvoice.value.tanggal } })
    newInvoice.value.nomor_invoice = res.data.next_code
  } catch (err) {
    console.error('Error fetching next invoice number:', err)
    newInvoice.value.nomor_invoice = ''
  }
}

watch(() => newInvoice.value.tanggal, async (newVal) => {
  if (isCreateModalOpen.value && newVal) {
    try {
      const res = await api.get('/invoices/next-code', { params: { tanggal: newVal } })
      newInvoice.value.nomor_invoice = res.data.next_code
    } catch (err) {
      console.error('Error fetching next invoice number:', err)
    }
  }
})

onMounted(() => {
  fetchInvoices()
  fetchClientsAndEvents()
})

const openPaymentModal = (invoice: any) => {
  selectedInvoice.value = invoice
  paymentForm.value.nominal = invoice.outstanding_amount
  isPaymentModalOpen.value = true
}

const onEventChange = () => {
  const selectedEvent = events.value.find(e => e.id === newInvoice.value.event_id)
  if (selectedEvent && selectedEvent.client) {
    newInvoice.value.client_id = selectedEvent.client.id
    if (newInvoice.value.jenis_invoice === 'pelunasan') {
      newInvoice.value.subtotal = selectedEvent.sisa_nilai_kontrak || 0
    }
  } else {
    newInvoice.value.client_id = ''
  }
}

watch(() => newInvoice.value.jenis_invoice, (newVal) => {
  if (newVal === 'pelunasan' && newInvoice.value.event_id) {
    const selectedEvent = events.value.find(e => e.id === newInvoice.value.event_id)
    if (selectedEvent) {
      newInvoice.value.subtotal = selectedEvent.sisa_nilai_kontrak || 0
    }
  }
})

const saveInvoice = async () => {
  try {
    await api.post('/invoices', newInvoice.value)
    newInvoice.value = {
      client_id: '',
      event_id: '',
      nomor_invoice: '',
      tanggal: new Date().toISOString().split('T')[0],
      jatuh_tempo: new Date().toISOString().split('T')[0],
      jenis_invoice: 'dp',
      subtotal: 0,
      apply_ppn: true,
      nomor_faktur_pajak: ''
    }
    isCreateModalOpen.value = false
    fetchInvoices()
  } catch (err) {
    console.error('Error creating invoice:', err)
  }
}

const submitPayment = async () => {
  if (!selectedInvoice.value) return
  try {
    await api.post(`/invoices/${selectedInvoice.value.id}/payments`, paymentForm.value)
    isPaymentModalOpen.value = false
    fetchInvoices()
  } catch (err) {
    console.error('Error posting payment:', err)
  }
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'belum_bayar': return 'bg-rose-950 text-rose-455 border-rose-900/50'
    case 'sebagian': return 'bg-amber-950 text-amber-450 border-amber-900/50'
    case 'lunas': return 'bg-emerald-950 text-emerald-450 border-emerald-900/50'
    default: return 'bg-slate-200 text-slate-700'
  }
}

const formatIDR = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value)
}

const printInvoice = (invoice: any) => {
  const printWindow = window.open('', '_blank')
  if (!printWindow) return

  const formatIDRLocal = (val: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(val)
  }

  const subtotal = Number(invoice.subtotal)
  const ppn = Number(invoice.ppn)
  const total = subtotal + ppn
  const ppnRateUsed = subtotal > 0 ? Math.round((ppn / subtotal) * 100) : 12
  const paid = Number(invoice.paid_amount || 0)
  const outstanding = total - paid
  const clientName = invoice.client?.nama || '-'
  const clientCompany = invoice.client?.perusahaan || '-'
  const clientNpwp = invoice.client?.npwp || '-'
  const clientEmail = invoice.client?.email || '-'
  const clientPhone = invoice.client?.telepon || '-'
  const clientAddress = invoice.client?.alamat || '-'

  const eventName = invoice.event?.nama_event || '-'
  const eventNo = invoice.event?.nomor_event || '-'

  const tenantName = authStore.user?.tenant?.name || 'EventBooks Organizer'
  const tenantAddress = authStore.user?.tenant?.alamat || 'Alamat belum dikonfigurasi'
  const tenantEmail = authStore.user?.tenant?.email || ''
  const tenantPhone = authStore.user?.tenant?.telepon || ''

  printWindow.document.write(`
    <html>
      <head>
        <title>Invoice - ${invoice.nomor_invoice}</title>
        <script src="https://cdn.tailwindcss.com"><\/script>
        <style>
          @page {
            size: auto;
            margin: 0;
          }
          @media print {
            body { padding: 20mm; margin: 0; }
            .no-print { display: none; }
          }
        </style>
      </head>
      <body class="bg-white text-slate-800 p-8 font-sans">
        <div class="max-w-3xl mx-auto border border-slate-200 p-8 rounded-xl shadow-xs">
          <!-- Header -->
          <div class="flex justify-between items-start border-b border-slate-200 pb-6 mb-6">
            <div>
              <h1 class="text-3xl font-extrabold text-emerald-600 tracking-tight">INVOICE</h1>
              <p class="text-xs text-slate-400 font-mono mt-1">NO: ${invoice.nomor_invoice}</p>
            </div>
            <div class="text-right text-xs">
              <h2 class="font-bold text-slate-900 text-sm">${tenantName}</h2>
              <p class="text-slate-500 mt-1">${tenantAddress}</p>
              <p class="text-slate-500">${tenantEmail ? 'Email: ' + tenantEmail : ''} ${tenantPhone ? '| Tel: ' + tenantPhone : ''}</p>
            </div>
          </div>

          <!-- Info Section -->
          <div class="grid grid-cols-2 gap-8 text-xs mb-8">
            <div>
              <h3 class="font-bold text-slate-400 uppercase tracking-wider text-2xs mb-2">Tagihan Kepada:</h3>
              <p class="font-extrabold text-slate-900 text-sm">${clientCompany}</p>
              <p class="text-slate-600 font-semibold mt-0.5">${clientName}</p>
              <p class="text-slate-500 mt-1">${clientAddress}</p>
              <p class="text-slate-500">Tel: ${clientPhone} | Email: ${clientEmail}</p>
              ${clientNpwp && clientNpwp !== '-' && clientNpwp !== 'Tidak ada NPWP' ? '<p class="text-slate-500 font-mono mt-1">NPWP: ' + clientNpwp + '</p>' : ''}
            </div>
            <div class="text-right">
              <h3 class="font-bold text-slate-400 uppercase tracking-wider text-2xs mb-2">Rincian Invoice:</h3>
              <p class="text-slate-600"><span class="font-bold text-slate-800">Tanggal Terbit:</span> ${invoice.tanggal ? invoice.tanggal.split('T')[0] : '-'}</p>
              <p class="text-slate-600 mt-0.5"><span class="font-bold text-slate-800">Jatuh Tempo:</span> ${invoice.jatuh_tempo ? invoice.jatuh_tempo.split('T')[0] : '-'}</p>
              <p class="text-slate-600 mt-0.5"><span class="font-bold text-slate-800">Termin/Jenis:</span> <span class="uppercase font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/50">${invoice.jenis_invoice}</span></p>
              <p class="text-slate-600 mt-2"><span class="font-bold text-slate-800">Event Referensi:</span> ${eventName} (${eventNo})</p>
              ${invoice.nomor_faktur_pajak ? `<p class="text-slate-600 mt-1"><span class="font-bold text-slate-800">Faktur Pajak (NSFP):</span> <span class="font-mono text-3xs">${invoice.nomor_faktur_pajak}</span></p>` : ''}
            </div>
          </div>

          <!-- Table -->
          <table class="w-full text-left text-xs border-collapse border border-slate-200 rounded-lg overflow-hidden mb-6">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-3xs font-bold">
                <th class="p-3">Deskripsi Tagihan</th>
                <th class="p-3 text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-b border-slate-100">
                <td class="p-3 font-semibold text-slate-850 text-sm">Pembayaran ${invoice.jenis_invoice === 'dp' ? 'Down Payment (DP)' : invoice.jenis_invoice === 'termin' ? 'Termin / Milestone' : 'Pelunasan'} untuk Event "${eventName}"</td>
                <td class="p-3 text-right text-slate-850 text-sm font-mono font-bold">${formatIDRLocal(subtotal)}</td>
              </tr>
            </tbody>
          </table>

          <!-- Totals -->
          <div class="flex justify-end mb-8">
            <div class="w-64 text-xs space-y-2">
              <div class="flex justify-between text-slate-500 border-b border-slate-100 pb-1.5">
                <span>Subtotal</span>
                <span class="font-mono font-semibold">${formatIDRLocal(subtotal)}</span>
              </div>
              ${ppn > 0 ? `
              <div class="flex justify-between text-slate-500 border-b border-slate-100 pb-1.5">
                <span>PPN Keluaran (${ppnRateUsed}%)</span>
                <span class="font-mono font-semibold">+ ${formatIDRLocal(ppn)}</span>
              </div>
              ` : ''}
              <div class="flex justify-between text-slate-900 font-extrabold text-sm border-b border-slate-200 pb-2">
                <span>Total Tagihan</span>
                <span class="font-mono">${formatIDRLocal(total)}</span>
              </div>
              <div class="flex justify-between text-emerald-600 font-bold border-b border-slate-100 pb-1.5">
                <span>Sudah Dibayar</span>
                <span class="font-mono">- ${formatIDRLocal(paid)}</span>
              </div>
              <div class="flex justify-between text-slate-900 font-extrabold text-sm pt-1">
                <span>Sisa Tagihan</span>
                <span class="font-mono text-rose-600">${formatIDRLocal(outstanding)}</span>
              </div>
            </div>
          </div>

          <!-- Footer/Note -->
          <div class="border-t border-slate-200 pt-6 text-center text-3xs text-slate-400">
            <p class="font-bold text-slate-500 uppercase tracking-widest">Terima Kasih atas Kepercayaan Anda</p>
            <p class="mt-1">Invoice ini sah dibuat secara elektronik dan berfungsi sebagai bukti penagihan legal.</p>
            <div class="mt-6 no-print">
              <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer shadow">Cetak / Simpan PDF</button>
            </div>
          </div>
        </div>
      </body>
    </html>
  `)
  printWindow.document.close()
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Invoicing & Billing</h1>
        <p class="text-xs text-slate-500 mt-1">Buat invoice termin klien, tagih PPN, dan pantau status pelunasan.</p>
      </div>
      <button 
        @click="openCreateModal"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-colors cursor-pointer"
      >
        + Buat Invoice
      </button>
    </div>

    <!-- Invoices Grid -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/85 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 text-slate-400 text-3xs font-bold uppercase tracking-wider">
              <th class="p-4">Invoice No / Tanggal</th>
              <th class="p-4">Event / Klien</th>
              <th class="p-4">Subtotal / PPN</th>
              <th class="p-4">Total Tagihan</th>
              <th class="p-4">Status</th>
              <th class="p-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-sm">
            <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-slate-50/40 dark:hover:bg-slate-800/10 text-slate-700 dark:text-slate-350 transition-colors">
              <td class="p-4">
                <span class="font-mono text-xs text-slate-400 block font-semibold">{{ inv.nomor_invoice }}</span>
                <span class="text-2xs text-slate-450 block mt-0.5">JT: {{ inv.jatuh_tempo }}</span>
              </td>
              <td class="p-4">
                <span class="font-bold text-slate-900 dark:text-white block">{{ inv.event?.nama_event || 'General Invoicing' }}</span>
                <span class="text-xs text-slate-450 block mt-0.5">{{ inv.client?.perusahaan || inv.client?.nama || '-' }}</span>
              </td>
              <td class="p-4 text-xs">
                <span class="block">DPP: {{ formatIDR(inv.subtotal) }}</span>
                <span class="block text-3xs text-slate-400 mt-0.5">PPN: {{ formatIDR(inv.ppn) }}</span>
              </td>
              <td class="p-4 font-bold text-slate-900 dark:text-white">{{ formatIDR(inv.total) }}</td>
              <td class="p-4">
                <span :class="[getStatusBadge(inv.status), 'px-2 py-0.5 border rounded text-3xs font-bold uppercase tracking-wider']">
                  {{ inv.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="p-4 text-right space-x-2">
                <button 
                  v-if="inv.status !== 'lunas'"
                  @click="openPaymentModal(inv)"
                  class="text-xs bg-emerald-950/20 text-emerald-400 hover:bg-emerald-950/40 px-2.5 py-1.5 rounded font-semibold transition-colors cursor-pointer"
                >
                  Bayar
                </button>
                <button 
                  @click="printInvoice(inv)"
                  class="text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 px-2.5 py-1.5 rounded font-semibold transition-colors cursor-pointer"
                >
                  PDF
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Invoice Modal -->
    <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isCreateModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Pembuatan Invoice Baru</h3>

        <form @submit.prevent="saveInvoice" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-350">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nomor Invoice</label>
              <input v-model="newInvoice.nomor_invoice" type="text" placeholder="e.g. INV-2026-0046" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Termin Invoice</label>
              <select v-model="newInvoice.jenis_invoice" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
                <option value="dp">Invoice DP (Down Payment)</option>
                <option value="termin">Invoice Termin / Milestone</option>
                <option value="pelunasan">Invoice Pelunasan</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Terbit</label>
              <input v-model="newInvoice.tanggal" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Jatuh Tempo</label>
              <input v-model="newInvoice.jatuh_tempo" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pilih Event</label>
              <select v-model="newInvoice.event_id" @change="onEventChange" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500" required>
                <option value="">-- Pilih Event --</option>
                <option v-for="ev in events" :key="ev.id" :value="ev.id">{{ ev.nama_event }}</option>
              </select>
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nominal Sebelum Pajak</label>
              <input v-model="newInvoice.subtotal" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
          </div>

          <div class="border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-3 bg-slate-50/50 dark:bg-slate-800/20">
            <label class="flex items-center space-x-2 cursor-pointer text-xs font-semibold">
              <input v-model="newInvoice.apply_ppn" type="checkbox" class="rounded text-emerald-600 focus:ring-emerald-500" />
              <span>Kenakan PPN Indonesia ({{ authStore.user?.tenant?.default_ppn_rate || 12 }}% DPP)</span>
            </label>

            <!-- NSFP Input (muncul hanya jika apply_ppn dicentang) -->
            <div v-if="newInvoice.apply_ppn" class="space-y-1 pt-1">
              <label class="block text-3xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nomor Seri Faktur Pajak (NSFP)</label>
              <input 
                v-model="newInvoice.nomor_faktur_pajak" 
                type="text" 
                placeholder="Contoh: 002.26.12345678" 
                class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-xs outline-none focus:border-emerald-500" 
              />
            </div>
            
            <div class="text-xs space-y-1.5 pt-1.5 border-t border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400">
              <div class="flex justify-between">
                <span>Subtotal (DPP)</span>
                <span>{{ formatIDR(invoiceTotalPreview.sub) }}</span>
              </div>
              <div class="flex justify-between">
                <span>PPN Keluaran ({{ authStore.user?.tenant?.default_ppn_rate || 12 }}%)</span>
                <span>+ {{ formatIDR(invoiceTotalPreview.ppn) }}</span>
              </div>
              <div class="flex justify-between text-sm font-bold text-slate-900 dark:text-slate-200 pt-1.5 border-t border-dashed border-slate-200 dark:border-slate-800">
                <span>Total Nilai Invoice</span>
                <span>{{ formatIDR(invoiceTotalPreview.total) }}</span>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isCreateModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-650 dark:text-slate-350 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Terbitkan Invoice</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Record Payment Modal -->
    <div v-if="isPaymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isPaymentModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Registrasi Pembayaran Masuk</h3>

        <form @submit.prevent="submitPayment" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-350">
          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Invoice Referensi</label>
            <input :value="selectedInvoice?.nomor_invoice" type="text" class="w-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm text-slate-500 dark:text-slate-400 outline-none" disabled />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Bayar</label>
              <input v-model="paymentForm.tanggal" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nominal Pembayaran</label>
              <input v-model="paymentForm.nominal" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Metode Pembayaran</label>
            <select v-model="paymentForm.metode_pembayaran" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500">
              <option value="transfer_bank">Transfer Bank</option>
              <option value="cash">Tunai / Cash</option>
              <option value="card">Kartu Kredit/Debet</option>
              <option value="e_wallet">E-Wallet</option>
            </select>
          </div>

          <div>
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Bukti Transfer (Referensi/Catatan)</label>
            <input v-model="paymentForm.bukti_transfer" type="text" placeholder="e.g. Ref No. BCA-982341" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" />
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isPaymentModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-650 dark:text-slate-350 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Posting Pembayaran</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
