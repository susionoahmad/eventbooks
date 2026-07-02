<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const authStore = useAuthStore()

const tenantInfo = ref<any>(null)

const subscriptionPlan = computed(() => {
  return tenantInfo.value?.subscription_plan || authStore.user?.tenant?.subscription_plan || 'trial'
})

const trialEndsAt = computed(() => {
  return tenantInfo.value?.trial_ends_at || authStore.user?.tenant?.trial_ends_at
})

const formatTrialEndDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  try {
    const trialDateStr = dateStr.includes(' ') && !dateStr.includes('T')
      ? dateStr.replace(' ', 'T')
      : dateStr
    const date = new Date(trialDateStr)
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    }).format(date)
  } catch (e) {
    return dateStr
  }
}

const formattedTrialEndDate = computed(() => {
  return formatTrialEndDate(trialEndsAt.value)
})

const trialRemainingDays = computed(() => {
  const dateStr = trialEndsAt.value
  if (!dateStr) return 0
  try {
    const trialDateStr = dateStr.includes(' ') && !dateStr.includes('T')
      ? dateStr.replace(' ', 'T')
      : dateStr
    const endsAt = new Date(trialDateStr)
    const now = new Date()
    
    // Set to start of the day for date comparison
    const endsAtDate = new Date(endsAt.getFullYear(), endsAt.getMonth(), endsAt.getDate())
    const nowDate = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    
    const diffTime = endsAtDate.getTime() - nowDate.getTime()
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return diffDays > 0 ? diffDays : 0
  } catch (e) {
    return 0
  }
})


const organization = ref({
  name: '',
  npwp: '',
  email: '',
  phone: '',
  address: '',
  default_ppn_rate: 12.00
})

const users = ref<any[]>([])

// Modal management
const isUserModalOpen = ref(false)
const modalMode = ref<'add' | 'edit' | 'password'>('add')
const editingUser = ref<any>(null)

// Form fields
const userForm = ref({
  name: '',
  email: '',
  role: 'staff',
  password: ''
})

const openAddModal = () => {
  modalMode.value = 'add'
  editingUser.value = null
  userForm.value = {
    name: '',
    email: '',
    role: 'staff',
    password: ''
  }
  isUserModalOpen.value = true
}

const openEditModal = (user: any) => {
  modalMode.value = 'edit'
  editingUser.value = user
  userForm.value = {
    name: user.name,
    email: user.email,
    role: user.role,
    password: ''
  }
  isUserModalOpen.value = true
}

const openPasswordModal = (user: any) => {
  modalMode.value = 'password'
  editingUser.value = user
  userForm.value = {
    name: user.name,
    email: user.email,
    role: user.role,
    password: ''
  }
  isUserModalOpen.value = true
}

const fetchTenant = async () => {
  try {
    const res = await api.get('/tenant')
    tenantInfo.value = res.data.data
    organization.value = {
      name: res.data.data.name || '',
      npwp: res.data.data.npwp || '',
      email: res.data.data.email || '',
      phone: res.data.data.telepon || '',
      address: res.data.data.alamat || '',
      default_ppn_rate: res.data.data.default_ppn_rate !== null ? parseFloat(res.data.data.default_ppn_rate) : 12.00
    }
  } catch (err) {
    console.error('Error fetching tenant details:', err)
  }
}

const fetchUsers = async () => {
  try {
    const res = await api.get('/tenant/users')
    users.value = res.data.data
  } catch (err) {
    console.error('Error fetching tenant users:', err)
  }
}

onMounted(() => {
  fetchTenant()
  fetchUsers()
})

// Notification State
const showSuccessNotif = ref(false)
const notifMessage = ref('')

const triggerNotif = (msg: string) => {
  notifMessage.value = msg
  showSuccessNotif.value = true
  setTimeout(() => {
    showSuccessNotif.value = false
  }, 3000)
}

const saveOrganization = async () => {
  try {
    const res = await api.put('/tenant', {
      name: organization.value.name,
      npwp: organization.value.npwp,
      email: organization.value.email,
      telepon: organization.value.phone,
      alamat: organization.value.address,
      default_ppn_rate: organization.value.default_ppn_rate
    })
    
    tenantInfo.value = res.data.data
    organization.value = {
      name: res.data.data.name || '',
      npwp: res.data.data.npwp || '',
      email: res.data.data.email || '',
      phone: res.data.data.telepon || '',
      address: res.data.data.alamat || '',
      default_ppn_rate: res.data.data.default_ppn_rate !== null ? parseFloat(res.data.data.default_ppn_rate) : 12.00
    }
    
    triggerNotif('Profil organisasi berhasil diperbarui.')
  } catch (err) {
    console.error('Error saving organization:', err)
  }
}

const saveUser = async () => {
  try {
    if (modalMode.value === 'add') {
      await api.post('/tenant/users', {
        name: userForm.value.name,
        email: userForm.value.email,
        role: userForm.value.role,
        password: userForm.value.password || undefined
      })
      triggerNotif('Pengguna baru berhasil ditambahkan.')
    } else if (modalMode.value === 'edit') {
      await api.put(`/tenant/users/${editingUser.value.id}`, {
        name: userForm.value.name,
        email: userForm.value.email,
        role: userForm.value.role
      })
      triggerNotif('Data pengguna berhasil diperbarui.')
    } else if (modalMode.value === 'password') {
      if (!userForm.value.password) {
        alert('Password baru wajib diisi.')
        return
      }
      await api.put(`/tenant/users/${editingUser.value.id}/password`, {
        password: userForm.value.password
      })
      triggerNotif('Password pengguna berhasil diubah.')
    }
    
    isUserModalOpen.value = false
    fetchUsers()
  } catch (err: any) {
    console.error('Error saving user:', err)
    alert(err.response?.data?.message || 'Gagal menyimpan data pengguna.')
  }
}

const toggleUserStatus = async (user: any) => {
  try {
    await api.put(`/tenant/users/${user.id}/toggle`)
    fetchUsers()
    triggerNotif(`Status akses ${user.name} berhasil diperbarui.`)
  } catch (err) {
    console.error('Error toggling user status:', err)
  }
}

const deleteUser = async (user: any) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus pengguna ${user.name}?`)) {
    return
  }
  try {
    await api.delete(`/tenant/users/${user.id}`)
    fetchUsers()
    triggerNotif(`Pengguna ${user.name} berhasil dihapus.`)
  } catch (err: any) {
    console.error('Error deleting user:', err)
    alert(err.response?.data?.message || 'Gagal menghapus pengguna.')
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Pengaturan Portal & Tim</h1>
        <p class="text-xs text-slate-500 mt-1">Konfigurasi organisasi, data legal wajib pajak, dan hak akses pengguna.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column: Profile & Subscription -->
      <div class="lg:col-span-1 space-y-6">
        <!-- Organization settings -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-4">
          <h3 class="font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 text-sm">Profil Organisasi</h3>
          
          <form @submit.prevent="saveOrganization" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-350">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Perusahaan / EO</label>
              <input v-model="organization.name" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">NPWP Badan Usaha</label>
              <input v-model="organization.npwp" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Email Korespondensi</label>
              <input v-model="organization.email" type="email" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Telepon</label>
              <input v-model="organization.phone" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500" required />
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Alamat Kantor Utama</label>
              <textarea v-model="organization.address" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500"></textarea>
            </div>
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tarif PPN Default (%)</label>
              <input v-model="organization.default_ppn_rate" type="number" step="0.01" min="0" max="99.99" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500" required />
            </div>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">Simpan Perubahan</button>
          </form>
        </div>


      </div>


      <!-- User management list -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm lg:col-span-2 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
          <h3 class="font-bold text-slate-900 dark:text-white text-sm">Manajemen Pengguna & Hak Akses</h3>
          <button @click="openAddModal" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-800 dark:border-slate-700 text-white rounded-lg text-2xs font-bold transition-colors cursor-pointer">
            + Tambah Pengguna
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 text-3xs font-bold uppercase tracking-wider">
                <th class="pb-3">Nama Lengkap / Email</th>
                <th class="pb-3">Role Sistem</th>
                <th class="pb-3">Status</th>
                <th class="pb-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
              <tr v-for="user in users" :key="user.id" class="text-slate-700 dark:text-slate-350 hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                <td class="py-3">
                  <span class="font-bold text-slate-900 dark:text-white block">{{ user.name }}</span>
                  <span class="text-2xs text-slate-400 block mt-0.5">{{ user.email }}</span>
                </td>
                <td class="py-3 font-semibold uppercase tracking-wider text-2xs">
                  <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                    {{ user.role.replace('_', ' ') }}
                  </span>
                </td>
                <td class="py-3">
                  <span :class="[user.is_active ? 'bg-emerald-950 text-emerald-400 border-emerald-800' : 'bg-rose-950 text-rose-400 border-rose-800', 'px-2 py-0.5 border rounded text-3xs font-bold uppercase tracking-wider']">
                    {{ user.is_active ? 'aktif' : 'suspend' }}
                  </span>
                </td>
                <td class="py-3 text-right space-x-1.5 whitespace-nowrap">
                  <button 
                    @click="openEditModal(user)"
                    class="text-2xs bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 px-2 py-1 rounded font-bold cursor-pointer"
                  >
                    Edit
                  </button>
                  <button 
                    @click="openPasswordModal(user)"
                    class="text-2xs bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/50 text-amber-600 dark:text-amber-400 px-2 py-1 rounded font-bold cursor-pointer"
                  >
                    Sandi
                  </button>
                  <button 
                    @click="toggleUserStatus(user)"
                    v-if="user.id !== authStore.user?.id"
                    class="text-2xs bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-2 py-1 rounded font-bold cursor-pointer"
                  >
                    {{ user.is_active ? 'Suspend' : 'Aktif' }}
                  </button>
                  <button 
                    @click="deleteUser(user)"
                    v-if="user.id !== authStore.user?.id"
                    class="text-2xs bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-400 px-2 py-1 rounded font-bold cursor-pointer"
                  >
                    Hapus
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- User Modal (Add / Edit / Password) -->
    <div v-if="isUserModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="isUserModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xl space-y-4">
        
        <h3 class="text-base font-bold text-slate-900 dark:text-white">
          {{ modalMode === 'add' ? 'Tambah Pengguna Baru' : modalMode === 'edit' ? 'Edit Data Pengguna' : 'Ubah Password Pengguna' }}
        </h3>

        <form @submit.prevent="saveUser" class="space-y-3.5 text-xs text-slate-700 dark:text-slate-350">
          
          <template v-if="modalMode === 'add' || modalMode === 'edit'">
            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Lengkap</label>
              <input v-model="userForm.name" type="text" placeholder="e.g. Shinta Aulia" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>

            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Email Pengguna</label>
              <input v-model="userForm.email" type="email" placeholder="e.g. shinta@royalevent.co.id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required />
            </div>

            <div>
              <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Peran Akses (Role)</label>
              <select 
                v-model="userForm.role" 
                :disabled="editingUser && editingUser.id === authStore.user?.id"
                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2.5 rounded-lg text-sm outline-none focus:border-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <option value="owner">Owner (Akses Penuh)</option>
                <option value="finance_manager">Manager Keuangan (Pembukuan & Pajak)</option>
                <option value="admin">Administrator (Event & Master)</option>
                <option value="staff">Staff Event (RAB & Lapangan)</option>
              </select>
              <p v-if="editingUser && editingUser.id === authStore.user?.id" class="text-3xs text-slate-400 mt-1">Anda tidak dapat mengubah peran Anda sendiri.</p>
            </div>
          </template>

          <!-- Password field for adding new user (optional, defaults to eventbooks123 if blank) -->
          <div v-if="modalMode === 'add'">
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Password (Opsional)</label>
            <input v-model="userForm.password" type="password" placeholder="Bila kosong, default: eventbooks123" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" />
          </div>

          <!-- Password field for change password mode -->
          <div v-if="modalMode === 'password'">
            <label class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1">Password Baru</label>
            <input v-model="userForm.password" type="password" placeholder="Minimal 8 karakter" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg text-sm outline-none focus:border-emerald-500" required minlength="8" />
          </div>

          <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="button" @click="isUserModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-350 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs cursor-pointer">
              {{ modalMode === 'add' ? 'Tambah Pengguna' : modalMode === 'edit' ? 'Simpan Perubahan' : 'Ubah Password' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Toast Notification -->
    <Transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showSuccessNotif" class="fixed bottom-5 right-5 z-50 max-w-sm bg-emerald-950/95 border border-emerald-800 text-emerald-400 p-4 rounded-xl shadow-xl flex items-center space-x-3 backdrop-blur-xs">
        <svg class="h-5 w-5 text-emerald-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-xs font-semibold">{{ notifMessage }}</span>
      </div>
    </Transition>
  </div>
</template>
