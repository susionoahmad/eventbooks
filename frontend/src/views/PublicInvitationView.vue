<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const rawId = route.params.id as string
const eventId = rawId ? rawId.split('-')[0] : ''

const loading = ref(true)
const error = ref<string | null>(null)

interface EventInfo {
  nama_event: string
  lokasi: string
  tanggal_mulai: string
  tanggal_selesai: string
  client?: {
    nama: string
    perusahaan: string
  }
}

interface InvitationData {
  title: string
  date_time_info: string
  maps_url: string
  is_custom_template: boolean
  preset_template: string
  template_background_url?: string
  background_color: string
  primary_color: string
  accent_color: string
  text_color: string
  button_text_color: string
  font_family: string
  maps_btn_top?: number | string
  maps_btn_left?: number | string
  maps_btn_width?: number | string
  maps_btn_height?: number | string
  maps_btn_text?: string
}

const eventInfo = ref<EventInfo>({
  nama_event: '',
  lokasi: '',
  tanggal_mulai: '',
  tanggal_selesai: ''
})

const invitation = ref<InvitationData>({
  title: '',
  date_time_info: '',
  maps_url: '',
  is_custom_template: false,
  preset_template: 'classic',
  background_color: '#ffffff',
  primary_color: '#1a1a1a',
  accent_color: '#4f46e5',
  text_color: '#1a1a1a',
  button_text_color: '#ffffff',
  font_family: 'Inter',
  maps_btn_top: 72,
  maps_btn_left: 15,
  maps_btn_width: 70,
  maps_btn_height: 6,
  maps_btn_text: 'Buka Google Maps'
})

// Dynamic Google Fonts loader
const loadGoogleFont = (fontName: string) => {
  if (!fontName || fontName === 'Inter') return
  const fontId = 'dynamic-invitation-font-' + fontName.replace(/\s+/g, '-').toLowerCase()
  if (document.getElementById(fontId)) return
  
  const link = document.createElement('link')
  link.id = fontId
  link.rel = 'stylesheet'
  
  // Handled list of fonts
  let googleFontName = fontName
  if (fontName === 'Sacramento') {
    googleFontName = 'Sacramento'
  } else if (fontName === 'Great Vibes') {
    googleFontName = 'Great+Vibes'
  }
  
  link.href = `https://fonts.googleapis.com/css2?family=${googleFontName.replace(/\s+/g, '+')}:wght@400;700&display=swap`
  document.head.appendChild(link)
}

const fetchPublicInvitation = async () => {
  loading.value = true
  try {
    const baseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1'
    const res = await axios.get(`${baseURL}/events/${eventId}/invitation/public?t=${Date.now()}`)
    
    eventInfo.value = res.data.event
    invitation.value = res.data.data
    
    // Load dynamic font if set
    if (invitation.value.font_family) {
      loadGoogleFont(invitation.value.font_family)
    }
  } catch (err: any) {
    console.error('Error fetching public invitation:', err)
    error.value = 'Undangan tidak ditemukan atau event tidak aktif.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPublicInvitation()
})

const getInvitationStyle = computed(() => {
  const styles: any = {
    fontFamily: invitation.value.font_family || 'Inter',
    color: invitation.value.text_color
  }

  if (invitation.value.is_custom_template && invitation.value.template_background_url) {
    styles.backgroundImage = `url(${invitation.value.template_background_url})`
    styles.backgroundSize = '100% 100%'
    styles.backgroundPosition = 'center'
    styles.backgroundRepeat = 'no-repeat'
  } else {
    styles.backgroundColor = invitation.value.background_color
  }

  return styles
})

const formatDateRange = (start: string, end: string) => {
  if (!start) return ''
  const opt: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' }
  const startDate = new Date(start).toLocaleDateString('id-ID', opt)
  const endDate = new Date(end).toLocaleDateString('id-ID', opt)
  
  if (start === end || !end) return startDate
  return `${startDate} s/d ${endDate}`
}

const handlePrint = () => {
  window.print()
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center p-4 sm:p-6 md:p-8 font-sans transition-colors duration-300">
    <div v-if="loading" class="flex flex-col items-center space-y-4 text-slate-400">
      <svg class="animate-spin h-10 w-10 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <span class="text-xs font-semibold uppercase tracking-wider animate-pulse">Memuat Undangan Digital...</span>
    </div>

    <div v-else-if="error" class="bg-slate-900 border border-slate-800 rounded-3xl p-8 max-w-md w-full text-center shadow-xl">
      <div class="inline-flex p-4 bg-rose-500/10 border border-rose-500/20 text-rose-500 rounded-2xl mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
      </div>
      <h3 class="text-lg font-bold text-white mb-2">Terjadi Kesalahan</h3>
      <p class="text-slate-400 text-xs leading-relaxed mb-6">{{ error }}</p>
      <router-link to="/" class="inline-flex px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition-all">
        Kembali ke Dashboard
      </router-link>
    </div>

    <div v-else class="relative w-full max-w-sm aspect-[300/580] bg-white rounded-[32px] shadow-2xl overflow-hidden print-container flex flex-col" :style="getInvitationStyle">
      <!-- Action buttons hidden in print mode -->
      <div class="absolute top-4 right-4 flex space-x-2 z-10 print-hidden">
        <button 
          @click="handlePrint" 
          class="p-2.5 bg-slate-900/60 hover:bg-slate-950/80 backdrop-blur-md text-white rounded-full transition-all border border-white/10 shadow-lg cursor-pointer"
          title="Simpan / Cetak PDF"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.865 48.865 0 00-14.326 0C3.768 7.44 3 8.375 3 9.456V15.75a2.25 2.25 0 002.25 2.25h1.091M9 9h6m-6 3h6m-9-6a3 3 0 013-3h6a3 3 0 013 3" />
          </svg>
        </button>
      </div>

      <!-- Absolute Map Button for Custom Template (Public View) -->
      <a 
        v-if="invitation.is_custom_template && invitation.maps_url && invitation.maps_btn_top !== undefined && invitation.maps_btn_top !== null"
        :href="invitation.maps_url"
        target="_blank"
        class="absolute flex items-center justify-center text-center px-4 rounded-xl text-xs font-bold tracking-wide shadow-md transition-all hover:scale-103 hover:shadow-lg focus:outline-none print-hidden z-10"
        :style="{ 
          top: invitation.maps_btn_top + '%', 
          left: invitation.maps_btn_left + '%', 
          width: invitation.maps_btn_width + '%',
          height: (invitation.maps_btn_height || 6) + '%',
          backgroundColor: invitation.accent_color,
          color: invitation.button_text_color
        }"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-2 shrink-0">
          <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
        </svg>
        <span>{{ invitation.maps_btn_text || 'Buka Google Maps' }}</span>
      </a>

      <!-- Content wrapper -->
      <div class="relative z-1 flex-1 flex flex-col text-center" :class="[invitation.is_custom_template ? 'justify-end p-6' : 'justify-between p-8 sm:p-12 md:p-16']">
        <!-- Header Section -->
        <div v-if="!invitation.is_custom_template" class="space-y-3">
          <span 
            class="text-3xs uppercase tracking-widest font-extrabold px-3 py-1 bg-white/15 dark:bg-black/25 rounded-full inline-block backdrop-blur-xs border border-white/10"
            :style="{ color: invitation.accent_color }"
          >
            Undangan Resmi
          </span>
          <h1 
            class="text-3xl sm:text-4xl font-extrabold tracking-tight pt-3 leading-tight"
            :style="{ color: invitation.primary_color }"
          >
            {{ invitation.title }}
          </h1>
          <div class="h-0.5 w-16 mx-auto my-4 opacity-50" :style="{ backgroundColor: invitation.accent_color }"></div>
        </div>

        <!-- Middle Event Details -->
        <div v-if="!invitation.is_custom_template" class="my-8 space-y-6 bg-black/5 dark:bg-white/5 p-6 rounded-2xl border border-white/5 backdrop-blur-xs">
          <div class="space-y-1">
            <span class="text-3xs uppercase font-bold tracking-wider opacity-60">Nama Penyelenggara</span>
            <p class="text-sm font-bold opacity-90">{{ eventInfo.client?.perusahaan || eventInfo.client?.nama || 'EO Client' }}</p>
          </div>

          <div class="space-y-1">
            <span class="text-3xs uppercase font-bold tracking-wider opacity-60">Tanggal Event</span>
            <p class="text-base font-extrabold opacity-95">
              {{ invitation.date_time_info || formatDateRange(eventInfo.tanggal_mulai, eventInfo.tanggal_selesai) }}
            </p>
          </div>

          <div class="space-y-1">
            <span class="text-3xs uppercase font-bold tracking-wider opacity-60">Lokasi Venue</span>
            <p class="text-sm font-medium opacity-90 leading-relaxed">📍 {{ eventInfo.lokasi || 'Venue Terjadwal' }}</p>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="pt-4 space-y-4">
          <!-- Only render inline button if it's NOT a custom template -->
          <a 
            v-if="!invitation.is_custom_template && invitation.maps_url"
            :href="invitation.maps_url"
            target="_blank"
            class="inline-flex items-center space-x-2.5 px-6 py-3.5 rounded-xl text-xs font-bold tracking-wide shadow-md transition-all hover:scale-103 hover:shadow-lg focus:outline-none print-hidden"
            :style="{ backgroundColor: invitation.accent_color, color: invitation.button_text_color }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
              <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
            </svg>
            <span>{{ invitation.maps_btn_text || 'Buka Google Maps' }}</span>
          </a>

          <!-- Functional maps text address for printed versions -->
          <div class="hidden print-block text-4xs opacity-50 mt-4 leading-relaxed font-mono">
            Link Peta Lokasi: {{ invitation.maps_url || 'https://maps.google.com' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media print {
  body {
    background: white !important;
  }
  .print-hidden {
    display: none !important;
  }
  .print-block {
    display: block !important;
  }
  .print-container {
    box-shadow: none !important;
    border: 1px solid #e2e8f0 !important;
    max-width: 100% !important;
    width: 100% !important;
    border-radius: 0 !important;
    min-height: auto !important;
    page-break-inside: avoid;
  }
}
</style>
