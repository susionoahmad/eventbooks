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

const isDjpDropdownOpen = ref(false)

const exportEFakturPPN = () => {
  const ppnList = taxes.value.filter(tax => tax.tipe_pajak === 'ppn_keluaran')
  
  if (ppnList.length === 0) {
    alert('Tidak ada PPN Keluaran pada periode ini.')
    return
  }

  const headers = [
    'FK', 'KD_AP', 'FG_PENGGANTI', 'NOMOR_FAKTUR', 'MASA_PAJAK', 'TAHUN_PAJAK', 
    'TANGGAL_FAKTUR', 'NPWP', 'NAMA', 'ALAMAT_LENGKAP', 'JUMLAH_DPP', 'JUMLAH_PPN', 
    'JUMLAH_PPNBM', 'ID_KETERANGAN_TAMBAHAN', 'FG_UANG_MUKA', 'UANG_MUKA_DPP', 
    'UANG_MUKA_PPN', 'UANG_MUKA_PPNBM', 'REFERENSI'
  ]

  const rows = ppnList.map(tax => {
    const [year, month] = tax.masa_pajak.split('-')
    
    let formattedDate = ''
    if (tax.created_at) {
      const d = new Date(tax.created_at)
      const dd = String(d.getDate()).padStart(2, '0')
      const mm = String(d.getMonth() + 1).padStart(2, '0')
      const yyyy = d.getFullYear()
      formattedDate = `${dd}/${mm}/${yyyy}`
    } else {
      formattedDate = `01/${month}/${year}`
    }

    const cleanNpwp = (tax.pihak_terkait_npwp || '').replace(/\D/g, '')

    const isDp = tax.invoice?.jenis_invoice === 'dp'

    return [
      'FK',
      '01',
      '0',
      tax.nomor_faktur_pajak || tax.invoice?.nomor_faktur_pajak || '',
      parseInt(month).toString(),
      year,
      formattedDate,
      cleanNpwp || '0000000000000000',
      tax.pihak_terkait_nama || tax.event?.client?.nama || 'Umum',
      tax.event?.client?.alamat || '-',
      Math.round(tax.dpp),
      Math.round(tax.nominal_pajak),
      '0',
      '',
      isDp ? '1' : '0',
      '0',
      '0',
      '0',
      tax.invoice?.nomor_invoice ? `${tax.invoice.nomor_invoice} - ${tax.event?.nama_event || ''}` : (tax.event?.nama_event || 'Invoice')
    ]
  })

  let csvContent = "\uFEFF"
  csvContent += [headers.join(','), ...rows.map(row => row.map(val => `"${val}"`).join(','))].join('\r\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement("a")
  link.href = url
  link.download = `eFaktur_PPN_${filterMasa.value}.csv`
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

const exportEBupotPPh23 = () => {
  const pph23List = taxes.value.filter(tax => tax.tipe_pajak === 'pph_23')

  if (pph23List.length === 0) {
    alert('Tidak ada PPh 23 pada periode ini.')
    return
  }

  const headers = [
    'No', 'Tanggal Bukti Potong', 'Penerima Penghasilan (NPWP/NIK)', 'No Identitas', 
    'Nama Penerima', 'Kode Objek Pajak', 'Jumlah Bruto', 'Tarif', 'PPh Dipotong', 
    'Fasilitas', 'No SKB/Suket', 'Dokumen Dasar Pemotongan', 'No Dokumen Dasar', 'Tgl Dokumen Dasar'
  ]

  const rows = pph23List.map((tax, index) => {
    const [year, month] = tax.masa_pajak.split('-')
    
    let formattedDate = ''
    if (tax.created_at) {
      const d = new Date(tax.created_at)
      const dd = String(d.getDate()).padStart(2, '0')
      const mm = String(d.getMonth() + 1).padStart(2, '0')
      const yyyy = d.getFullYear()
      formattedDate = `${dd}/${mm}/${yyyy}`
    } else {
      formattedDate = `01/${month}/${year}`
    }

    const cleanNpwp = (tax.pihak_terkait_npwp || '').replace(/\D/g, '')
    const identitas = cleanNpwp.length === 16 && !cleanNpwp.startsWith('0') ? 'NIK' : 'NPWP'

    return [
      index + 1,
      formattedDate,
      identitas,
      cleanNpwp || '0000000000000000',
      tax.pihak_terkait_nama || 'Vendor',
      tax.kode_objek_pajak || '24-104-14',
      Math.round(tax.dpp),
      tax.tarif,
      Math.round(tax.nominal_pajak),
      'TANPA',
      '',
      'Invoice/Kwitansi',
      tax.nomor_bukti_potong || 'TRX-' + tax.id,
      formattedDate
    ]
  })

  let csvContent = "\uFEFF"
  csvContent += [headers.join(','), ...rows.map(row => row.map(val => `"${val}"`).join(','))].join('\r\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement("a")
  link.href = url
  link.download = `eBupot_PPh23_${filterMasa.value}.csv`
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

const exportEBupotPPh21 = () => {
  const pph21List = taxes.value.filter(tax => tax.tipe_pajak === 'pph_21')

  if (pph21List.length === 0) {
    alert('Tidak ada PPh 21 pada periode ini.')
    return
  }

  const headers = [
    'No', 'Tanggal Bukti Potong', 'Penerima Penghasilan (NPWP/NIK)', 'No Identitas', 
    'Nama Penerima', 'Kode Objek Pajak', 'Jumlah Bruto', 'DPP (50% Bruto)', 'PPh Dipotong', 'Fasilitas'
  ]

  const rows = pph21List.map((tax, index) => {
    const [year, month] = tax.masa_pajak.split('-')
    
    let formattedDate = ''
    if (tax.created_at) {
      const d = new Date(tax.created_at)
      const dd = String(d.getDate()).padStart(2, '0')
      const mm = String(d.getMonth() + 1).padStart(2, '0')
      const yyyy = d.getFullYear()
      formattedDate = `${dd}/${mm}/${yyyy}`
    } else {
      formattedDate = `01/${month}/${year}`
    }

    const cleanNpwp = (tax.pihak_terkait_npwp || '').replace(/\D/g, '')
    const identitas = cleanNpwp.length === 16 && !cleanNpwp.startsWith('0') ? 'NIK' : 'NPWP'

    return [
      index + 1,
      formattedDate,
      identitas,
      cleanNpwp || '0000000000000000',
      tax.pihak_terkait_nama || 'Freelancer',
      tax.kode_objek_pajak || '21-100-02',
      Math.round(tax.dpp * 2),
      Math.round(tax.dpp),
      Math.round(tax.nominal_pajak),
      'TANPA'
    ]
  })

  let csvContent = "\uFEFF"
  csvContent += [headers.join(','), ...rows.map(row => row.map(val => `"${val}"`).join(','))].join('\r\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement("a")
  link.href = url
  link.download = `eBupot_PPh21_${filterMasa.value}.csv`
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

const exportToExcel = () => {
  let html = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
      <!--[if gte mso 9]>
      <xml>
        <x:ExcelWorkbook>
          <x:ExcelWorksheets>
            <x:ExcelWorksheet>
              <x:Name>Rekap Pajak ${filterMasa.value}</x:Name>
              <x:WorksheetOptions>
                <x:DisplayGridlines/>
              </x:WorksheetOptions>
            </x:ExcelWorksheet>
          </x:ExcelWorksheets>
        </x:ExcelWorkbook>
      </xml>
      <![endif]-->
      <style>
        table { border-collapse: collapse; }
        th { background-color: #10B981; color: white; font-weight: bold; border: 1px solid #ddd; padding: 8px; }
        td { border: 1px solid #ddd; padding: 8px; }
        .number { mso-number-format:"\\#\\,\\#\\#0"; }
        .text { mso-number-format:"\\@"; }
      </style>
    </head>
    <body>
      <h2>Rekapitulasi Pajak Periode ${filterMasa.value}</h2>
      <table>
        <thead>
          <tr>
            <th>Jenis Pajak</th>
            <th>Nama Event</th>
            <th>Nama Pihak Terkait</th>
            <th>NPWP</th>
            <th>Masa Pajak</th>
            <th>Dasar Pengenaan Pajak (DPP)</th>
            <th>Tarif</th>
            <th>Nominal Pajak</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
  `;

  taxes.value.forEach(tax => {
    html += `
      <tr>
        <td>${getTaxTypeLabel(tax.tipe_pajak)}</td>
        <td>${tax.event?.nama_event || 'General'}</td>
        <td>${tax.pihak_terkait_nama || '-'}</td>
        <td class="text">${tax.pihak_terkait_npwp || 'Tanpa NPWP'}</td>
        <td>${tax.masa_pajak}</td>
        <td class="number">${parseFloat(tax.dpp) || 0}</td>
        <td>${tax.tarif}%</td>
        <td class="number">${parseFloat(tax.nominal_pajak) || 0}</td>
        <td>${tax.status.toUpperCase()}</td>
      </tr>
    `;
  });

  html += `
        </tbody>
      </table>
    </body>
    </html>
  `;

  const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = `Rekap_Pajak_${filterMasa.value}.xls`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

const exportToPDF = () => {
  const printWindow = window.open('', '_blank');
  if (!printWindow) return;

  const style = `
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; padding: 20px; }
    h1 { font-size: 20px; margin-bottom: 5px; color: #111; }
    p { font-size: 12px; color: #666; margin-bottom: 20px; }
    .summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 25px; }
    .summary-card { border: 1px solid #ddd; padding: 10px; border-radius: 8px; font-size: 11px; }
    .summary-card span { display: block; color: #888; font-weight: bold; margin-bottom: 5px; }
    .summary-card strong { font-size: 14px; color: #111; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10px; }
    th { background-color: #f3f4f6; color: #374151; font-weight: bold; border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
    td { border-bottom: 1px solid #e5e7eb; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; padding: 8px; }
    .text-right { text-align: right; }
    .font-bold { font-weight: bold; }
    .status-terutang { color: #d97706; font-weight: bold; }
    .status-dibayar { color: #059669; font-weight: bold; }
  `;

  let htmlContent = `
    <html>
    <head>
      <title>Rekapitulasi Pajak Periode ${filterMasa.value}</title>
      <style>${style}</style>
    </head>
    <body>
      <h1>Rekapitulasi Perpajakan Bulanan</h1>
      <p>Organisasi: ${authStore.user?.tenant?.name || 'EventBooks'} | Periode: ${filterMasa.value}</p>
      
      <div class="summary-grid">
        <div class="summary-card">
          <span>PPN KELUARAN</span>
          <strong>${formatIDR(activeSummary.value.ppnKeluaran)}</strong>
        </div>
        <div class="summary-card">
          <span>PPH 23 TERPOTONG</span>
          <strong>${formatIDR(activeSummary.value.pph23)}</strong>
        </div>
        <div class="summary-card">
          <span>PPH 21 TALENT</span>
          <strong>${formatIDR(activeSummary.value.pph21)}</strong>
        </div>
        <div class="summary-card">
          <span>TOTAL TERUTANG</span>
          <strong style="color: #d97706">${formatIDR(activeSummary.value.terutang)}</strong>
        </div>
        <div class="summary-card">
          <span>TOTAL DISETER</span>
          <strong style="color: #059669">${formatIDR(activeSummary.value.dibayar)}</strong>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Jenis Pajak / Event</th>
            <th>Pihak Terkait / NPWP</th>
            <th>Masa / DPP</th>
            <th>Tarif</th>
            <th class="text-right">Nominal Pajak</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
  `;

  taxes.value.forEach(tax => {
    htmlContent += `
      <tr>
        <td>
          <div class="font-bold">${getTaxTypeLabel(tax.tipe_pajak)}</div>
          <div style="color: #666; font-size: 9px;">${tax.event?.nama_event || 'General'}</div>
        </td>
        <td>
          <div class="font-bold">${tax.pihak_terkait_nama || '-'}</div>
          <div style="color: #888; font-size: 8px;">${tax.pihak_terkait_npwp || 'Tanpa NPWP'}</div>
        </td>
        <td>
          <div>${tax.masa_pajak}</div>
          <div style="color: #888; font-size: 8px;">DPP: ${formatIDR(tax.dpp)}</div>
        </td>
        <td>${tax.tarif}%</td>
        <td class="text-right font-bold">${formatIDR(tax.nominal_pajak)}</td>
        <td class="${tax.status === 'terutang' ? 'status-terutang' : 'status-dibayar'}">${tax.status.toUpperCase()}</td>
      </tr>
    `;
  });

  if (taxes.value.length === 0) {
    htmlContent += `
      <tr>
        <td colspan="6" style="text-align: center; color: #888; padding: 20px;">
          Tidak ada kewajiban pajak tercatat untuk masa pajak ini.
        </td>
      </tr>
    `;
  }

  htmlContent += `
        </tbody>
      </table>
      <script>
        window.onload = function() {
          window.print();
          setTimeout(function() { window.close(); }, 500);
        }
      <\/script>
    </body>
    </html>
  `;

  printWindow.document.write(htmlContent);
  printWindow.document.close();
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
        <button 
          @click="exportToExcel" 
          class="flex items-center space-x-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-colors cursor-pointer"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span>Excel</span>
        </button>

        <button 
          @click="exportToPDF" 
          class="flex items-center space-x-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold transition-colors cursor-pointer"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          <span>PDF</span>
        </button>

        <!-- Dropdown Ekspor DJP -->
        <div class="relative inline-block text-left" id="djp-dropdown-container">
          <button 
            @click="isDjpDropdownOpen = !isDjpDropdownOpen" 
            class="flex items-center space-x-1.5 px-3 py-1.5 bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 border border-slate-800 dark:border-slate-700 text-white rounded-lg text-xs font-bold transition-colors cursor-pointer"
          >
            <svg class="h-3.5 w-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
            </svg>
            <span>Ekspor DJP</span>
            <svg class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          
          <div 
            v-if="isDjpDropdownOpen" 
            class="absolute right-0 mt-1 w-44 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl z-50 py-1"
          >
            <button 
              @click="exportEFakturPPN(); isDjpDropdownOpen = false" 
              class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-250 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
            >
              e-Faktur PPN (CSV)
            </button>
            <button 
              @click="exportEBupotPPh23(); isDjpDropdownOpen = false" 
              class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-250 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
            >
              e-Bupot PPh 23 (CSV)
            </button>
            <button 
              @click="exportEBupotPPh21(); isDjpDropdownOpen = false" 
              class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-250 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
            >
              e-Bupot PPh 21 (CSV)
            </button>
          </div>
        </div>

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
