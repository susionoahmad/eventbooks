<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const activeReportTab = ref<'pl' | 'cashflow' | 'ledger'>('pl')
const filterYear = ref('2026')

const plData = ref({
  pendapatanKontrak: 0,
  pendapatanLain: 0,
  biayaVendor: 0,
  biayaOperasional: 0,
  biayaMarketing: 0,
  biayaKonsumsi: 0,
  bebanPajak: 0,
  bebanPajakPpn: 0,
  bebanPajakPph: 0,
  ppnKeluaran: 0,
  ppnMasukan: 0,
  pph21: 0,
  pph23: 0,
})

const cashflowData = ref({
  inflowCategories: {} as Record<string, number>,
  outflowCategories: {} as Record<string, number>,
  monthly: [] as Array<{ bulan: string, inflow: number, outflow: number, net: number }>,
  totalInflow: 0,
  totalOutflow: 0,
  netCashFlow: 0
})

const ledgerData = ref([] as Array<{
  kategori: string,
  totalMasuk: number,
  totalKeluar: number,
  saldoAkhir: number,
  transactions: Array<{
    tanggal: string | null,
    nomor_transaksi: string,
    deskripsi: string,
    tipe: string,
    nominal: number,
    event: string | null,
    vendor: string | null
  }>
}>)

const totalPendapatan = computed(() => plData.value.pendapatanKontrak + plData.value.pendapatanLain)
const totalBiaya = computed(() => {
  return plData.value.biayaVendor + 
         plData.value.biayaOperasional + 
         plData.value.biayaMarketing + 
         plData.value.biayaKonsumsi
})

const labaKotor = computed(() => totalPendapatan.value - totalBiaya.value)
const labaBersih = computed(() => labaKotor.value - plData.value.bebanPajak)
const netMargin = computed(() => {
  if (totalPendapatan.value === 0) return '0.00'
  return ((labaBersih.value / totalPendapatan.value) * 100).toFixed(2)
})

const fetchProfitLoss = async () => {
  try {
    const res = await api.get('/reports/profit-loss', {
      params: { tahun: filterYear.value }
    })
    plData.value = res.data
  } catch (err) {
    console.error('Error fetching profit loss report:', err)
  }
}

const fetchCashFlow = async () => {
  try {
    const res = await api.get('/reports/cash-flow', {
      params: { tahun: filterYear.value }
    })
    cashflowData.value = res.data
  } catch (err) {
    console.error('Error fetching cash flow report:', err)
  }
}

const fetchLedger = async () => {
  try {
    const res = await api.get('/reports/ledger', {
      params: { tahun: filterYear.value }
    })
    ledgerData.value = res.data.ledger
  } catch (err) {
    console.error('Error fetching ledger report:', err)
  }
}

const fetchActiveReport = () => {
  if (activeReportTab.value === 'pl') {
    fetchProfitLoss()
  } else if (activeReportTab.value === 'cashflow') {
    fetchCashFlow()
  } else if (activeReportTab.value === 'ledger') {
    fetchLedger()
  }
}

onMounted(() => {
  fetchActiveReport()
})

watch([filterYear, activeReportTab], () => {
  fetchActiveReport()
})

const formatCategoryLabel = (cat: string) => {
  switch (cat) {
    case 'dp_event': return 'Down Payment (DP) Event'
    case 'pelunasan_event': return 'Pelunasan Event'
    case 'pendapatan_lain': return 'Pendapatan Lain-lain'
    case 'pembayaran_vendor': return 'Pembayaran Vendor'
    case 'operasional': return 'Biaya Operasional'
    case 'transportasi': return 'Biaya Transportasi'
    case 'konsumsi': return 'Biaya Konsumsi'
    case 'marketing': return 'Biaya Marketing'
    case 'pajak': return 'Penyetoran Pajak DJP'
    case 'beban_pajak': return 'Beban Pajak'
    default: return cat.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
  }
}

const printReport = () => {
  const printWindow = window.open('', '_blank')
  if (!printWindow) return

  const year = filterYear.value
  let title = ''
  let contentHtml = ''

  if (activeReportTab.value === 'pl') {
    title = `Laporan Laba Rugi (P&L) - ${year}`
    contentHtml = `
      <div class="space-y-6">
        <table class="w-full text-left text-xs border-collapse border border-slate-200 rounded-lg overflow-hidden">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-3xs font-bold">
              <th class="p-3">Keterangan Akun</th>
              <th class="p-3 text-right">Rincian Nominal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <!-- Revenue -->
            <tr class="font-bold bg-slate-50/50 text-slate-900">
              <td class="p-3 text-emerald-600">I. PENDAPATAN OPERASIONAL</td>
              <td></td>
            </tr>
            <tr>
              <td class="p-3 pl-8">Pendapatan Kontrak Event</td>
              <td class="p-3 text-right font-mono">${formatIDR(plData.value.pendapatanKontrak)}</td>
            </tr>
            <tr>
              <td class="p-3 pl-8">Pendapatan Lain-lain</td>
              <td class="p-3 text-right font-mono">${formatIDR(plData.value.pendapatanLain)}</td>
            </tr>
            <tr class="font-extrabold bg-slate-50 text-slate-900">
              <td class="p-3 pl-4">Total Pendapatan Bersih</td>
              <td class="p-3 text-right font-mono">${formatIDR(totalPendapatan.value)}</td>
            </tr>

            <!-- Expenses -->
            <tr class="font-bold bg-slate-50/50 text-slate-900">
              <td class="p-3 text-rose-600">II. BIAYA OPERASIONAL & VENDOR</td>
              <td></td>
            </tr>
            <tr>
              <td class="p-3 pl-8">Biaya Pembayaran Vendor Utama</td>
              <td class="p-3 text-right font-mono">(${formatIDR(plData.value.biayaVendor)})</td>
            </tr>
            <tr>
              <td class="p-3 pl-8">Operasional & Peralatan</td>
              <td class="p-3 text-right font-mono">(${formatIDR(plData.value.biayaOperasional)})</td>
            </tr>
            <tr>
              <td class="p-3 pl-8">Konsumsi & Akomodasi Lapangan</td>
              <td class="p-3 text-right font-mono">(${formatIDR(plData.value.biayaKonsumsi)})</td>
            </tr>
            <tr>
              <td class="p-3 pl-8">Marketing & Komisi Talent</td>
              <td class="p-3 text-right font-mono">(${formatIDR(plData.value.biayaMarketing)})</td>
            </tr>
            <tr class="font-extrabold bg-slate-50 text-slate-900">
              <td class="p-3 pl-4">Total Pengeluaran / Beban</td>
              <td class="p-3 text-right font-mono">(${formatIDR(totalBiaya.value)})</td>
            </tr>

            <!-- Gross Margin -->
            <tr class="font-extrabold bg-emerald-50 text-slate-900">
              <td class="p-3">III. LABA OPERASIONAL (KOTOR)</td>
              <td class="p-3 text-right font-mono">${formatIDR(labaKotor.value)}</td>
            </tr>

            <!-- Taxes -->
            <tr class="font-bold bg-slate-50/50 text-slate-900">
              <td class="p-3 text-amber-600">IV. BEBAN PERPAJAKAN</td>
              <td></td>
            </tr>
            <tr>
              <td class="p-3 pl-8 text-slate-700">
                PPh 21 &amp; PPh 23 (Withheld — Utang Pajak ke DJP)
                <span class="text-2xs text-slate-400 block font-normal">Dipotong dari pembayaran vendor/freelancer, belum disetor DJP</span>
              </td>
              <td class="p-3 text-right font-mono">(${formatIDR(plData.value.bebanPajakPph)})</td>
            </tr>
            <tr>
              <td class="p-3 pl-8 text-slate-700">
                PPN Terutang Bersih (Keluaran − Masukan)
                <span class="text-2xs text-slate-400 block font-normal">PPN Keluaran ${formatIDR(plData.value.ppnKeluaran)} − PPN Masukan ${formatIDR(plData.value.ppnMasukan)}</span>
              </td>
              <td class="p-3 text-right font-mono">(${formatIDR(plData.value.bebanPajakPpn)})</td>
            </tr>
            <tr class="font-semibold bg-amber-50 text-amber-800">
              <td class="p-3 pl-8">Total Beban Perpajakan</td>
              <td class="p-3 text-right font-mono">(${formatIDR(plData.value.bebanPajak)})</td>
            </tr>

            <!-- Net Income -->
            <tr class="font-extrabold bg-emerald-600 text-white">
              <td class="p-3">V. LABA BERSIH TAHUN BERJALAN</td>
              <td class="p-3 text-right font-mono">${formatIDR(labaBersih.value)}</td>
            </tr>
          </tbody>
        </table>
      </div>
    `
  } else if (activeReportTab.value === 'cashflow') {
    title = `Laporan Arus Kas - ${year}`
    
    let inflowRows = ''
    if (Object.keys(cashflowData.value.inflowCategories).length === 0) {
      inflowRows = `<tr><td class="p-3 pl-8 text-slate-400">Tidak ada data arus kas masuk.</td><td></td></tr>`
    } else {
      for (const [cat, val] of Object.entries(cashflowData.value.inflowCategories)) {
        inflowRows += `
          <tr>
            <td class="p-3 pl-8 text-slate-700">${formatCategoryLabel(cat)}</td>
            <td class="p-3 text-right font-mono">${formatIDR(val)}</td>
          </tr>
        `
      }
    }

    let outflowRows = ''
    if (Object.keys(cashflowData.value.outflowCategories).length === 0) {
      outflowRows = `<tr><td class="p-3 pl-8 text-slate-400">Tidak ada data arus kas keluar.</td><td></td></tr>`
    } else {
      for (const [cat, val] of Object.entries(cashflowData.value.outflowCategories)) {
        outflowRows += `
          <tr>
            <td class="p-3 pl-8 text-slate-700">${formatCategoryLabel(cat)}</td>
            <td class="p-3 text-right font-mono">(${formatIDR(val)})</td>
          </tr>
        `
      }
    }

    let monthlyRows = ''
    for (const item of cashflowData.value.monthly) {
      monthlyRows += `
        <tr class="text-slate-700">
          <td class="p-3 font-semibold">${item.bulan}</td>
          <td class="p-3 text-right text-emerald-600 font-mono">${formatIDR(item.inflow)}</td>
          <td class="p-3 text-right text-rose-500 font-mono">${item.outflow > 0 ? `(${formatIDR(item.outflow)})` : '-'}</td>
          <td class="p-3 text-right font-bold font-mono ${item.net >= 0 ? 'text-emerald-600' : 'text-rose-600'}">${formatIDR(item.net)}</td>
        </tr>
      `
    }

    contentHtml = `
      <div class="space-y-8">
        <table class="w-full text-left text-xs border-collapse border border-slate-200 rounded-lg overflow-hidden">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-3xs font-bold">
              <th class="p-3">Aktivitas / Kategori</th>
              <th class="p-3 text-right">Rincian Nominal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <!-- Inflows -->
            <tr class="font-bold bg-slate-50/50 text-slate-900">
              <td class="p-3 text-emerald-600">I. ARUS KAS MASUK (INFLOWS)</td>
              <td></td>
            </tr>
            ${inflowRows}
            <tr class="font-extrabold bg-slate-50 text-slate-900">
              <td class="p-3 pl-4">Total Arus Kas Masuk</td>
              <td class="p-3 text-right font-mono">${formatIDR(cashflowData.value.totalInflow)}</td>
            </tr>

            <!-- Outflows -->
            <tr class="font-bold bg-slate-50/50 text-slate-900">
              <td class="p-3 text-rose-600">II. ARUS KAS KELUAR (OUTFLOWS)</td>
              <td></td>
            </tr>
            ${outflowRows}
            <tr class="font-extrabold bg-slate-50 text-slate-900">
              <td class="p-3 pl-4">Total Arus Kas Keluar</td>
              <td class="p-3 text-right font-mono">(${formatIDR(cashflowData.value.totalOutflow)})</td>
            </tr>

            <!-- Net Cash Flow -->
            <tr class="font-extrabold bg-emerald-50 text-slate-900">
              <td class="p-3">III. PERUBAHAN KAS BERSIH</td>
              <td class="p-3 text-right font-mono ${cashflowData.value.netCashFlow >= 0 ? 'text-emerald-600' : 'text-rose-600'}">${formatIDR(cashflowData.value.netCashFlow)}</td>
            </tr>
          </tbody>
        </table>

        <!-- Monthly Breakdown -->
        <div class="mt-8">
          <h3 class="text-xs font-bold text-slate-900 mb-3 border-b border-slate-200 pb-2">Rincian Arus Kas Bulanan</h3>
          <table class="w-full text-left text-xs border-collapse border border-slate-200 rounded-lg overflow-hidden">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-3xs">
                <th class="p-3">Bulan</th>
                <th class="p-3 text-right">Kas Masuk</th>
                <th class="p-3 text-right">Kas Keluar</th>
                <th class="p-3 text-right">Net Flow</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              ${monthlyRows}
            </tbody>
          </table>
        </div>
      </div>
    `
  } else if (activeReportTab.value === 'ledger') {
    title = `Buku Besar Ringkas - ${year}`

    let groupsHtml = ''
    if (ledgerData.value.length === 0) {
      groupsHtml = `<div class="text-center text-slate-400 py-6 text-xs">Belum ada transaksi tercatat.</div>`
    } else {
      for (const group of ledgerData.value) {
        let rows = ''
        for (const tx of group.transactions) {
          rows += `
            <tr class="text-slate-700">
              <td class="p-2 pl-3 font-mono text-slate-400">${tx.tanggal || '-'}</td>
              <td class="p-2 font-mono font-bold">${tx.nomor_transaksi}</td>
              <td class="p-2">${tx.deskripsi}</td>
              <td class="p-2 text-slate-500">${tx.event || tx.vendor || '-'}</td>
              <td class="p-2 text-right pr-3 font-mono font-bold ${tx.tipe === 'kas_masuk' ? 'text-emerald-600' : 'text-rose-500'}">
                ${tx.tipe === 'kas_masuk' ? '+' : '-'} ${formatIDR(tx.nominal)}
              </td>
            </tr>
          `
        }

        groupsHtml += `
          <div class="border border-slate-200 rounded-lg overflow-hidden mb-6">
            <div class="bg-slate-50/85 p-3 flex justify-between items-center border-b border-slate-200">
              <div>
                <h4 class="font-bold text-slate-900 text-xs">${formatCategoryLabel(group.kategori)}</h4>
                <p class="text-4xs text-slate-400 uppercase tracking-widest mt-0.5 font-semibold">Kategori: ${group.kategori}</p>
              </div>
              <div class="text-right flex items-center space-x-6 text-3xs font-mono">
                <div>
                  <span class="text-slate-400 block uppercase">Total Masuk</span>
                  <span class="text-emerald-600 font-bold">${formatIDR(group.totalMasuk)}</span>
                </div>
                <div>
                  <span class="text-slate-400 block uppercase">Total Keluar</span>
                  <span class="text-rose-500 font-bold">${formatIDR(group.totalKeluar)}</span>
                </div>
                <div>
                  <span class="text-slate-400 block uppercase">Saldo Bersih</span>
                  <span class="font-extrabold text-slate-900">${formatIDR(group.saldoAkhir)}</span>
                </div>
              </div>
            </div>
            <table class="w-full text-left text-3xs border-collapse">
              <thead>
                <tr class="bg-slate-50/20 text-slate-400 font-bold border-b border-slate-200/50 uppercase tracking-wider text-4xs">
                  <th class="p-2 pl-3">Tanggal</th>
                  <th class="p-2">No Ref</th>
                  <th class="p-2">Keterangan</th>
                  <th class="p-2">Ref Terkait</th>
                  <th class="p-2 text-right pr-3">Nominal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                ${rows}
              </tbody>
            </table>
          </div>
        `
      }
    }

    contentHtml = `
      <div class="space-y-6">
        ${groupsHtml}
      </div>
    `
  }

  printWindow.document.write(`
    <html>
      <head>
        <title>${title}</title>
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
              <h1 class="text-2xl font-extrabold text-emerald-600 tracking-tight uppercase">${title}</h1>
              <p class="text-xs text-slate-400 font-semibold mt-1">Royal Event Organizer - Portal Keuangan</p>
            </div>
            <div class="text-right text-xs">
              <h2 class="font-bold text-slate-900 text-sm">Royal Event Organizer</h2>
              <p class="text-slate-500 mt-1">Kuningan Tower Lt. 12, Sudirman, Jakarta Selatan</p>
              <p class="text-slate-500">Email: info@royalevent.co.id | Tel: 021-88887777</p>
            </div>
          </div>

          <!-- Report Body -->
          ${contentHtml}

          <!-- Footer/Note -->
          <div class="border-t border-slate-200 pt-6 mt-8 text-center text-3xs text-slate-400">
            <p class="font-bold text-slate-500 uppercase tracking-widest">Terima Kasih atas Kerja Keras Anda</p>
            <p class="mt-1">Laporan keuangan ini dikonsolidasikan secara otomatis dari transaksi Buku Kas organisasi Anda.</p>
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

const formatIDR = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value)
}

// ── Excel Export (CSV with UTF-8 BOM agar terbaca Excel) ──────────────────
const exportExcel = () => {
  const year = filterYear.value
  let rows: (string | number)[][] = []
  let filename = ''

  if (activeReportTab.value === 'pl') {
    filename = `LaporanLabaRugi_${year}.csv`
    rows = [
      ['LAPORAN LABA RUGI', year],
      [],
      ['Keterangan', 'Nominal (IDR)'],
      ['I. PENDAPATAN OPERASIONAL', ''],
      ['Pendapatan Kontrak Event', plData.value.pendapatanKontrak],
      ['Pendapatan Lain-lain', plData.value.pendapatanLain],
      ['Total Pendapatan Bersih', totalPendapatan.value],
      [],
      ['II. BIAYA OPERASIONAL & VENDOR', ''],
      ['Biaya Pembayaran Vendor Utama', -plData.value.biayaVendor],
      ['Operasional & Peralatan', -plData.value.biayaOperasional],
      ['Konsumsi & Akomodasi', -plData.value.biayaKonsumsi],
      ['Marketing & Komisi Talent', -plData.value.biayaMarketing],
      ['Total Pengeluaran / Beban', -totalBiaya.value],
      [],
      ['III. LABA OPERASIONAL (KOTOR)', labaKotor.value],
      [],
      ['IV. BEBAN PERPAJAKAN', ''],
      ['PPh 21 & PPh 23 (Withheld - Utang Pajak ke DJP)', -plData.value.bebanPajakPph],
      ['PPN Terutang Bersih (Keluaran - Masukan)', -plData.value.bebanPajakPpn],
      ['Total Beban Perpajakan', -plData.value.bebanPajak],
      [],
      ['V. LABA BERSIH TAHUN BERJALAN', labaBersih.value],
      ['Net Margin (%)', `${netMargin.value}%`],
    ]
  } else if (activeReportTab.value === 'cashflow') {
    filename = `LaporanArusKas_${year}.csv`
    rows = [
      ['LAPORAN ARUS KAS', year],
      [],
      ['Kategori', 'Nominal (IDR)'],
      ['I. ARUS KAS MASUK (INFLOWS)', ''],
      ...Object.entries(cashflowData.value.inflowCategories).map(([cat, val]): (string | number)[] => [formatCategoryLabel(cat), val]),
      ['Total Arus Kas Masuk', cashflowData.value.totalInflow],
      [],
      ['II. ARUS KAS KELUAR (OUTFLOWS)', ''],
      ...Object.entries(cashflowData.value.outflowCategories).map(([cat, val]): (string | number)[] => [formatCategoryLabel(cat), -val]),
      ['Total Arus Kas Keluar', -cashflowData.value.totalOutflow],
      [],
      ['III. PERUBAHAN KAS BERSIH', cashflowData.value.netCashFlow],
      [],
      ['RINCIAN BULANAN', ''],
      ['Bulan', 'Kas Masuk', 'Kas Keluar', 'Net Flow'],
      ...cashflowData.value.monthly.map((m): (string | number)[] => [m.bulan, m.inflow, -m.outflow, m.net]),
    ]
  } else if (activeReportTab.value === 'ledger') {
    filename = `BukuBesar_${year}.csv`
    rows = [
      ['BUKU BESAR RINGKAS', year],
      [],
    ]
    for (const group of ledgerData.value) {
      rows.push(
        [formatCategoryLabel(group.kategori), '', '', '', ''],
        ['Tanggal', 'No Transaksi', 'Deskripsi', 'Referensi', 'Nominal'],
        ...group.transactions.map((tx): (string | number)[] => [
          tx.tanggal ?? '-',
          tx.nomor_transaksi,
          tx.deskripsi,
          tx.event ?? tx.vendor ?? '-',
          tx.tipe === 'kas_masuk' ? tx.nominal : -tx.nominal,
        ]),
        ['', 'Total Masuk', group.totalMasuk, 'Total Keluar', -group.totalKeluar],
        [],
      )
    }
  }

  // Buat CSV string dengan BOM agar Excel bisa baca karakter Indonesia
  const csvContent = '\uFEFF' + rows
    .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.click()
  URL.revokeObjectURL(url)
}
</script>


<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Laporan Keuangan & Konsolidasi</h1>
        <p class="text-xs text-slate-500 mt-1">Konsolidasi otomatis laba rugi, arus kas, dan buku besar untuk pelaporan pajak tahunan.</p>
      </div>
      <!-- Action buttons: on mobile wrap to full row below title -->
      <div class="flex items-center gap-2 flex-wrap">
        <select v-model="filterYear" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 outline-none">
          <option value="2026">Tahun Buku 2026</option>
          <option value="2025">Tahun Buku 2025</option>
        </select>
        <button
          @click="exportExcel"
          class="flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 rounded-lg text-xs font-bold transition-colors cursor-pointer"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Export Excel
        </button>
        <button
          @click="printReport"
          class="flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-colors cursor-pointer shadow-sm"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Cetak PDF
        </button>
      </div>
    </div>

    <!-- Report Types Tab Buttons -->
    <div class="flex space-x-1 border-b border-slate-200 dark:border-slate-800/80 pb-px">
      <button 
        @click="activeReportTab = 'pl'"
        :class="[activeReportTab === 'pl' ? 'border-emerald-500 text-emerald-500 font-bold' : 'border-transparent text-slate-400 hover:text-slate-200', 'px-4 py-2 border-b-2 text-xs font-semibold transition-all cursor-pointer']"
      >
        Laporan Laba Rugi (P&L)
      </button>
      <button 
        @click="activeReportTab = 'cashflow'"
        :class="[activeReportTab === 'cashflow' ? 'border-emerald-500 text-emerald-500 font-bold' : 'border-transparent text-slate-400 hover:text-slate-200', 'px-4 py-2 border-b-2 text-xs font-semibold transition-all cursor-pointer']"
      >
        Laporan Arus Kas
      </button>
      <button 
        @click="activeReportTab = 'ledger'"
        :class="[activeReportTab === 'ledger' ? 'border-emerald-500 text-emerald-500 font-bold' : 'border-transparent text-slate-400 hover:text-slate-200', 'px-4 py-2 border-b-2 text-xs font-semibold transition-all cursor-pointer']"
      >
        Buku Besar Ringkas
      </button>
    </div>

    <!-- P&L Sheet -->
    <div v-if="activeReportTab === 'pl'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-6">
      <div class="text-center">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-wider">Laporan Laba Rugi</h2>
        <p class="text-xs text-slate-400 mt-1">Periode yang berakhir pada 31 Desember {{ filterYear }}</p>
      </div>

      <div class="max-w-2xl mx-auto text-sm divide-y divide-slate-100 dark:divide-slate-800/50 space-y-4">
        <!-- Revenue -->
        <div class="pt-4 space-y-2">
          <h3 class="font-bold text-emerald-500 text-2xs uppercase tracking-wider">I. PENDAPATAN OPERASIONAL</h3>
          <div class="flex justify-between pl-4 text-slate-700 dark:text-slate-350">
            <span>Pendapatan Kontrak Event</span>
            <span>{{ formatIDR(plData.pendapatanKontrak) }}</span>
          </div>
          <div class="flex justify-between pl-4 text-slate-700 dark:text-slate-350">
            <span>Pendapatan Lain-lain</span>
            <span>{{ formatIDR(plData.pendapatanLain) }}</span>
          </div>
          <div class="flex justify-between border-t border-slate-100 dark:border-slate-800/60 pt-2 font-bold text-slate-900 dark:text-white">
            <span>Total Pendapatan Bersih</span>
            <span>{{ formatIDR(totalPendapatan) }}</span>
          </div>
        </div>

        <!-- Expenses -->
        <div class="pt-6 space-y-2">
          <h3 class="font-bold text-rose-500 text-2xs uppercase tracking-wider">II. BIAYA OPERASIONAL & VENDOR</h3>
          <div class="flex justify-between pl-4 text-slate-700 dark:text-slate-350">
            <span>Biaya Pembayaran Vendor Utama</span>
            <span>({{ formatIDR(plData.biayaVendor) }})</span>
          </div>
          <div class="flex justify-between pl-4 text-slate-700 dark:text-slate-350">
            <span>Operasional & Peralatan</span>
            <span>({{ formatIDR(plData.biayaOperasional) }})</span>
          </div>
          <div class="flex justify-between pl-4 text-slate-700 dark:text-slate-350">
            <span>Konsumsi & Akomodasi Lapangan</span>
            <span>({{ formatIDR(plData.biayaKonsumsi) }})</span>
          </div>
          <div class="flex justify-between pl-4 text-slate-700 dark:text-slate-350">
            <span>Marketing & Komisi Talent</span>
            <span>({{ formatIDR(plData.biayaMarketing) }})</span>
          </div>
          <div class="flex justify-between border-t border-slate-100 dark:border-slate-800/60 pt-2 font-bold text-slate-900 dark:text-white">
            <span>Total Pengeluaran / Beban</span>
            <span>({{ formatIDR(totalBiaya) }})</span>
          </div>
        </div>

        <!-- Gross Margin -->
        <div class="pt-6 flex justify-between font-extrabold text-slate-900 dark:text-white text-base">
          <span>III. LABA OPERASIONAL (KOTOR)</span>
          <span>{{ formatIDR(labaKotor) }}</span>
        </div>

        <!-- Taxes -->
        <div class="pt-6 space-y-2">
          <h3 class="font-bold text-amber-500 text-2xs uppercase tracking-wider">IV. BEBAN PERPAJAKAN</h3>
          <div class="flex justify-between pl-4 text-slate-700 dark:text-slate-350 text-xs">
            <span>
              PPh 21 & PPh 23 (Withheld)
              <span class="text-slate-400 block text-3xs">Dipotong dari vendor/freelancer, utang ke DJP</span>
            </span>
            <span class="font-mono">({{ formatIDR(plData.bebanPajakPph) }})</span>
          </div>
          <div class="flex justify-between pl-4 text-slate-700 dark:text-slate-350 text-xs">
            <span>
              PPN Terutang Bersih
              <span class="text-slate-400 block text-3xs">Keluaran {{ formatIDR(plData.ppnKeluaran) }} − Masukan {{ formatIDR(plData.ppnMasukan) }}</span>
            </span>
            <span class="font-mono">({{ formatIDR(plData.bebanPajakPpn) }})</span>
          </div>
          <div class="flex justify-between pl-4 font-semibold text-amber-700 dark:text-amber-400 border-t border-amber-100 dark:border-amber-900/30 pt-1.5">
            <span>Total Beban Perpajakan</span>
            <span>({{ formatIDR(plData.bebanPajak) }})</span>
          </div>
        </div>

        <!-- Net Income -->
        <div class="pt-6 border-t border-dashed border-slate-200 dark:border-slate-800 flex justify-between font-extrabold text-emerald-500 text-lg">
          <span>V. LABA BERSIH TAHUN BERJALAN</span>
          <div class="text-right">
            <span>{{ formatIDR(labaBersih) }}</span>
            <span class="block text-2xs uppercase text-slate-400 mt-1 font-semibold tracking-wider">Margin Laba: {{ netMargin }}%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Cashflow Sheet -->
    <div v-else-if="activeReportTab === 'cashflow'" class="space-y-6">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-6">
        <div class="text-center">
          <h2 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-wider">Laporan Arus Kas</h2>
          <p class="text-xs text-slate-400 mt-1">Periode yang berakhir pada 31 Desember {{ filterYear }}</p>
        </div>

        <div class="max-w-2xl mx-auto text-sm divide-y divide-slate-100 dark:divide-slate-800/50 space-y-4">
          <!-- Inflows -->
          <div class="pt-4 space-y-2">
            <h3 class="font-bold text-emerald-500 text-2xs uppercase tracking-wider">I. ARUS KAS MASUK (INFLOWS)</h3>
            <div v-if="Object.keys(cashflowData.inflowCategories).length === 0" class="text-slate-400 text-xs py-2 pl-4">
              Tidak ada data arus kas masuk.
            </div>
            <div v-else v-for="(total, cat) in cashflowData.inflowCategories" :key="cat" class="flex justify-between pl-4 text-slate-700 dark:text-slate-350">
              <span>{{ formatCategoryLabel(cat) }}</span>
              <span>{{ formatIDR(total) }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-100 dark:border-slate-800/60 pt-2 font-bold text-slate-900 dark:text-white">
              <span>Total Arus Kas Masuk</span>
              <span>{{ formatIDR(cashflowData.totalInflow) }}</span>
            </div>
          </div>

          <!-- Outflows -->
          <div class="pt-6 space-y-2">
            <h3 class="font-bold text-rose-500 text-2xs uppercase tracking-wider">II. ARUS KAS KELUAR (OUTFLOWS)</h3>
            <div v-if="Object.keys(cashflowData.outflowCategories).length === 0" class="text-slate-400 text-xs py-2 pl-4">
              Tidak ada data arus kas keluar.
            </div>
            <div v-else v-for="(total, cat) in cashflowData.outflowCategories" :key="cat" class="flex justify-between pl-4 text-slate-700 dark:text-slate-350">
              <span>{{ formatCategoryLabel(cat) }}</span>
              <span>({{ formatIDR(total) }})</span>
            </div>
            <div class="flex justify-between border-t border-slate-100 dark:border-slate-800/60 pt-2 font-bold text-slate-900 dark:text-white">
              <span>Total Arus Kas Keluar</span>
              <span>({{ formatIDR(cashflowData.totalOutflow) }})</span>
            </div>
          </div>

          <!-- Net Cash Flow -->
          <div class="pt-6 flex justify-between font-extrabold text-slate-900 dark:text-white text-base">
            <span>III. PERUBAHAN KAS BERSIH</span>
            <span :class="cashflowData.netCashFlow >= 0 ? 'text-emerald-500' : 'text-rose-500'">{{ formatIDR(cashflowData.netCashFlow) }}</span>
          </div>
        </div>
      </div>

      <!-- Monthly Summary Card -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Rincian Arus Kas Bulanan</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 text-slate-400 font-bold uppercase tracking-wider">
                <th class="p-3">Bulan</th>
                <th class="p-3 text-right">Kas Masuk</th>
                <th class="p-3 text-right">Kas Keluar</th>
                <th class="p-3 text-right">Net Flow</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
              <tr v-for="item in cashflowData.monthly" :key="item.bulan" class="hover:bg-slate-50/40 dark:hover:bg-slate-800/10 text-slate-700 dark:text-slate-350 transition-colors">
                <td class="p-3 font-semibold">{{ item.bulan }}</td>
                <td class="p-3 text-right text-emerald-500 font-mono">{{ formatIDR(item.inflow) }}</td>
                <td class="p-3 text-right text-rose-500 font-mono">{{ item.outflow > 0 ? `(${formatIDR(item.outflow)})` : '-' }}</td>
                <td class="p-3 text-right font-bold font-mono" :class="item.net >= 0 ? 'text-emerald-500' : 'text-rose-500'">
                  {{ formatIDR(item.net) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Ledger Sheet -->
    <div v-else-if="activeReportTab === 'ledger'" class="space-y-6">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-6">
        <div class="text-center">
          <h2 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-wider">Buku Besar Ringkas</h2>
          <p class="text-xs text-slate-400 mt-1">Ringkasan per Kategori Transaksi Tahun {{ filterYear }}</p>
        </div>

        <div v-if="ledgerData.length === 0" class="text-center py-8 text-slate-400 text-sm">
          Belum ada transaksi tercatat di tahun buku ini.
        </div>

        <div v-else class="space-y-4">
          <!-- Accordion / List of categories -->
          <div v-for="group in ledgerData" :key="group.kategori" class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
            <!-- Header of Category -->
            <div class="bg-slate-50 dark:bg-slate-900/50 p-4 flex justify-between items-center hover:bg-slate-100/50 dark:hover:bg-slate-800/50 transition-colors">
              <div>
                <h4 class="font-bold text-slate-900 dark:text-white text-sm">{{ formatCategoryLabel(group.kategori) }}</h4>
                <p class="text-3xs text-slate-400 uppercase tracking-widest mt-0.5 font-semibold">Kategori: {{ group.kategori }}</p>
              </div>
              <div class="text-right flex items-center space-x-6 text-xs font-mono">
                <div class="hidden sm:block">
                  <span class="text-slate-405 block text-3xs uppercase tracking-wider font-semibold">Total Masuk</span>
                  <span class="text-emerald-500 font-bold">{{ formatIDR(group.totalMasuk) }}</span>
                </div>
                <div class="hidden sm:block">
                  <span class="text-slate-405 block text-3xs uppercase tracking-wider font-semibold">Total Keluar</span>
                  <span class="text-rose-500 font-bold">{{ formatIDR(group.totalKeluar) }}</span>
                </div>
                <div>
                  <span class="text-slate-405 block text-3xs uppercase tracking-wider font-semibold">Saldo Bersih</span>
                  <span class="font-extrabold text-slate-900 dark:text-white">{{ formatIDR(group.saldoAkhir) }}</span>
                </div>
              </div>
            </div>

            <!-- Transaction list for this category -->
            <div class="border-t border-slate-200 dark:border-slate-800 overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-slate-50/50 dark:bg-slate-900/20 text-slate-400 font-bold border-b border-slate-100 dark:border-slate-800/50 uppercase tracking-wider text-3xs">
                    <th class="p-3 pl-4">Tanggal</th>
                    <th class="p-3">Ref/No Transaksi</th>
                    <th class="p-3">Keterangan</th>
                    <th class="p-3">Referensi Terkait</th>
                    <th class="p-3 text-right pr-4">Nominal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/30">
                  <tr v-for="tx in group.transactions" :key="tx.nomor_transaksi" class="hover:bg-slate-50/20 dark:hover:bg-slate-800/5">
                    <td class="p-3 pl-4 text-slate-450 font-mono">{{ tx.tanggal || '-' }}</td>
                    <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">{{ tx.nomor_transaksi }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-350">{{ tx.deskripsi }}</td>
                    <td class="p-3 text-slate-500">
                      <span v-if="tx.event" class="block">Event: {{ tx.event }}</span>
                      <span v-if="tx.vendor" class="block text-3xs mt-0.5">Vendor: {{ tx.vendor }}</span>
                      <span v-if="!tx.event && !tx.vendor">-</span>
                    </td>
                    <td class="p-3 text-right pr-4 font-mono font-bold" :class="tx.tipe === 'kas_masuk' ? 'text-emerald-500' : 'text-rose-500'">
                      {{ tx.tipe === 'kas_masuk' ? '+' : '-' }} {{ formatIDR(tx.nominal) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
