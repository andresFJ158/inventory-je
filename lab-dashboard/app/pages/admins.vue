<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable @typescript-eslint/no-unused-vars */
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()

const apiHeaders = {
  Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
}

// State
const admins = ref<any[]>([])
const offices = ref<any[]>([])
const warehouses = ref<any[]>([])
const loading = ref(true)
const search = ref('')
const page = ref(1)
const itemsPerPage = 10

// Modals / Actions State
const isSlideoverOpen = ref(false)
const selectedAdmin = ref<any>(null)
const isResetPasswordOpen = ref(false)
const resetPasswordAdminId = ref<any>(null)
const newPassword = ref('')
const resettingPassword = ref(false)

// Permissions State
const isPermissionsOpen = ref(false)
const permAdmin = ref<any>(null)
const permForm = ref<Record<string, boolean>>({
  pos: false,
  ordenes: false,
  ventas: false,
  caja: false,
  gastos: false,
  productos: false,
  categorias: false,
  compras: false,
  proveedores: false,
  almacen: false,
  mi_inventario: false,
  solicitar_inventario: false,
  reportes: false
})
const savingPerms = ref(false)
const ignoreRoleWatch = ref(false)

// Form Admin Fields
const formModel = ref({
  name_admin: '',
  surname_admin: '',
  email_admin: '',
  password_admin: '',
  rol_admin: 'cajero',
  id_office_admin: '',
  id_warehouse_admin: '',
  status_admin: true
})
const savingAdmin = ref(false)

async function fetchAdmins() {
  loading.value = true
  try {
    const data = await $fetch<any>('/api/admins?orderBy=id_admin&orderMode=DESC', {
      headers: apiHeaders
    })
    if (data.status === 200) {
      admins.value = data.results || []
    }
  } catch (e) {
    console.error('Error fetching admins:', e)
  } finally {
    loading.value = false
  }
}

async function fetchOffices() {
  try {
    const data = await $fetch<any>('/api/offices', {
      headers: apiHeaders
    })
    if (data.status === 200) {
      offices.value = data.results || []
    }
  } catch (e) {
    console.error('Error fetching offices:', e)
  }
}

async function fetchWarehouses() {
  try {
    const data = await $fetch<any>('/api/warehouses', {
      headers: apiHeaders
    })
    if (data.status === 200) {
      warehouses.value = data.results || []
    }
  } catch (e) {
    console.error('Error fetching warehouses:', e)
  }
}

function getOfficeName(id: any) {
  if (!id || id == 0) return 'Todas (Super)'
  const o = offices.value.find(off => String(off.id_office) === String(id))
  return o ? decodeURIComponent(o.title_office || '').replace(/\+/g, ' ') : `Sucursal ${id}`
}

// KPIs
const totalAdmins = computed(() => admins.value.length)
const activeAdmins = computed(() => admins.value.filter(a => a.status_admin == 1).length)
const roleStats = computed(() => {
  const stats: Record<string, number> = {}
  admins.value.forEach(a => {
    stats[a.rol_admin] = (stats[a.rol_admin] || 0) + 1
  })
  return stats
})

// Search & Filtered Admins
const filteredAdmins = computed(() => {
  return admins.value.filter(a => {
    const name = decode(a.name_admin || '') + ' ' + decode(a.surname_admin || '')
    const email = decode(a.email_admin || '')
    const query = search.value.toLowerCase()
    return name.toLowerCase().includes(query) || email.toLowerCase().includes(query) || (a.rol_admin || '').toLowerCase().includes(query)
  })
})

const paginatedAdmins = computed(() => {
  const start = (page.value - 1) * itemsPerPage
  return filteredAdmins.value.slice(start, start + itemsPerPage)
})

function decode(s: string) {
  if (!s) return '-'
  return decodeURIComponent(s).replace(/\+/g, ' ')
}

// Active State Toggle
async function toggleStatus(admin: any) {
  const newStatus = admin.status_admin == 1 ? 0 : 1
  try {
    const body = new URLSearchParams()
    body.append('status_admin', String(newStatus))
    const res = await $fetch<any>(`/api/admins`, {
      method: 'PUT',
      headers: { ...apiHeaders, 'Content-Type': 'application/x-www-form-urlencoded' },
      query: { id: admin.id_admin, nameId: 'id_admin', token: 'no', except: 'id_admin' },
      body: body.toString()
    })
    if (res.status === 200) {
      toast.add({ title: 'Estado actualizado correctamente', color: 'success' })
      await fetchAdmins()
    } else {
      toast.add({ title: 'Error al cambiar estado', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de red', color: 'error' })
  }
}

// Reset Password Logic
function openResetPassword(admin: any) {
  resetPasswordAdminId.value = admin.id_admin
  newPassword.value = ''
  isResetPasswordOpen.value = true
}

async function handleResetPassword() {
  if (!newPassword.value || newPassword.value.length < 4) {
    alert('Ingresa una contraseña de al menos 4 caracteres')
    return
  }
  resettingPassword.value = true
  try {
    const body = new URLSearchParams()
    body.append('password_admin', newPassword.value)
    const res = await $fetch<any>('/api/admins', {
      method: 'PUT',
      headers: { ...apiHeaders, 'Content-Type': 'application/x-www-form-urlencoded' },
      query: { id: resetPasswordAdminId.value, nameId: 'id_admin', token: 'no', except: 'id_admin' },
      body: body.toString()
    })
    if (res.status === 200) {
      toast.add({ title: 'Contraseña actualizada correctamente', color: 'success' })
      isResetPasswordOpen.value = false
    } else {
      toast.add({ title: 'Error al actualizar contraseña', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de red al actualizar contraseña', color: 'error' })
  } finally {
    resettingPassword.value = false
  }
}

// Permission Management Matrix
function openPermissions(admin: any) {
  permAdmin.value = admin
  let perms = {}
  try {
    perms = typeof admin.permissions_admin === 'string' 
      ? JSON.parse(decodeURIComponent(admin.permissions_admin)) 
      : (admin.permissions_admin || {})
  } catch {
    perms = {}
  }
  
  // reset all to false
  Object.keys(permForm.value).forEach(k => {
    permForm.value[k] = false
  })

  // set values
  Object.entries(perms).forEach(([key, val]) => {
    if (permForm.value[key] !== undefined) {
      permForm.value[key] = val === 'on'
    }
  })
  isPermissionsOpen.value = true
}

async function savePermissions() {
  savingPerms.value = true
  try {
    const resultObj: Record<string, string> = {}
    Object.entries(permForm.value).forEach(([key, val]) => {
      resultObj[key] = val ? 'on' : 'off'
    })

    const body = new URLSearchParams()
    body.append('permissions_admin', JSON.stringify(resultObj))

    const res = await $fetch<any>('/api/admins', {
      method: 'PUT',
      headers: { ...apiHeaders, 'Content-Type': 'application/x-www-form-urlencoded' },
      query: { id: permAdmin.value.id_admin, nameId: 'id_admin', token: 'no', except: 'id_admin' },
      body: body.toString()
    })

    if (res.status === 200) {
      toast.add({ title: 'Permisos actualizados correctamente', color: 'success' })
      isPermissionsOpen.value = false
      await fetchAdmins()
    } else {
      toast.add({ title: 'Error al actualizar permisos', color: 'error' })
    }
  } catch (e) {
    console.error(e)
    toast.add({ title: 'Error de conexión', color: 'error' })
  } finally {
    savingPerms.value = false
  }
}

// Watch role change to auto-populate perm recommendations (still customizable)
watch(() => formModel.value.rol_admin, (newRole) => {
  if (ignoreRoleWatch.value) return
  
  // Reset all to false
  Object.keys(permForm.value).forEach(k => {
    permForm.value[k] = false
  })
  
  if (newRole === 'superadmin' || newRole === 'admin') {
    Object.keys(permForm.value).forEach(k => {
      permForm.value[k] = true
    })
  } else if (newRole === 'cajero') {
    permForm.value.pos = true
    permForm.value.ordenes = true
    permForm.value.ventas = true
    permForm.value.caja = true
    permForm.value.gastos = true
    permForm.value.productos = true
    permForm.value.mi_inventario = true
    permForm.value.solicitar_inventario = true
    permForm.value.reportes = true
  } else if (newRole === 'vendedor') {
    permForm.value.pos = true
    permForm.value.ordenes = true
    permForm.value.ventas = true
    permForm.value.caja = true
    permForm.value.gastos = true
    permForm.value.mi_inventario = true
    permForm.value.solicitar_inventario = true
    permForm.value.reportes = true
  } else if (newRole === 'despachador') {
    permForm.value.productos = true
    permForm.value.compras = true
    permForm.value.almacen = true
    permForm.value.proveedores = true
    permForm.value.mi_inventario = true
  } else if (newRole.startsWith('lab_')) {
    permForm.value.almacen = true
    permForm.value.mi_inventario = true
  }
})

// Add / Edit logic
function openCreate() {
  ignoreRoleWatch.value = true
  selectedAdmin.value = null
  formModel.value = {
    name_admin: '',
    surname_admin: '',
    email_admin: '',
    password_admin: '',
    rol_admin: 'cajero',
    id_office_admin: '',
    id_warehouse_admin: '',
    status_admin: true
  }
  // Reset permissions form
  Object.keys(permForm.value).forEach(k => {
    permForm.value[k] = false
  })
  
  // Pre-load Cajero defaults
  permForm.value.pos = true
  permForm.value.caja = true
  permForm.value.mi_inventario = true
  
  isSlideoverOpen.value = true
  
  setTimeout(() => {
    ignoreRoleWatch.value = false
  }, 100)
}

function openEdit(admin: any) {
  ignoreRoleWatch.value = true
  selectedAdmin.value = admin
  formModel.value = {
    name_admin: decode(admin.name_admin),
    surname_admin: decode(admin.surname_admin),
    email_admin: decode(admin.email_admin),
    password_admin: '', // Keep blank
    rol_admin: admin.rol_admin || 'cajero',
    id_office_admin: admin.id_office_admin ? String(admin.id_office_admin) : '',
    id_warehouse_admin: admin.id_warehouse_admin ? String(admin.id_warehouse_admin) : '',
    status_admin: admin.status_admin == 1
  }
  // Load permissions form
  let perms: any = {}
  try {
    perms = typeof admin.permissions_admin === 'string' 
      ? JSON.parse(decodeURIComponent(admin.permissions_admin)) 
      : (admin.permissions_admin || {})
  } catch {
    perms = {}
  }
  Object.keys(permForm.value).forEach(k => {
    permForm.value[k] = perms[k] === 'on'
  })
  isSlideoverOpen.value = true
  
  setTimeout(() => {
    ignoreRoleWatch.value = false
  }, 100)
}

async function handleSaveAdmin() {
  if (!formModel.value.name_admin || !formModel.value.email_admin) {
    alert('Por favor ingresa nombre y correo')
    return
  }

  savingAdmin.value = true
  try {
    const isEdit = !!selectedAdmin.value
    const body = new URLSearchParams()
    body.append('name_admin', formModel.value.name_admin)
    body.append('surname_admin', formModel.value.surname_admin)
    body.append('email_admin', formModel.value.email_admin)
    if (formModel.value.password_admin) {
      body.append('password_admin', formModel.value.password_admin)
    }
    body.append('rol_admin', formModel.value.rol_admin)
    body.append('id_office_admin', formModel.value.id_office_admin || '0')
    body.append('id_warehouse_admin', formModel.value.id_warehouse_admin || '0')
    body.append('status_admin', formModel.value.status_admin ? '1' : '0')

    // Append visual permissions directly
    const resultObj: Record<string, string> = {}
    Object.entries(permForm.value).forEach(([key, val]) => {
      resultObj[key] = val ? 'on' : 'off'
    })
    body.append('permissions_admin', JSON.stringify(resultObj))

    let url = '/api/admins'
    let method: 'POST' | 'PUT' = 'POST'
    const queryParams: Record<string, any> = {
      token: 'no',
      except: 'id_admin'
    }

    if (isEdit) {
      method = 'PUT'
      queryParams.id = selectedAdmin.value.id_admin
      queryParams.nameId = 'id_admin'
    }

    const res = await $fetch<any>(url, {
      method,
      headers: { ...apiHeaders, 'Content-Type': 'application/x-www-form-urlencoded' },
      query: queryParams,
      body: body.toString()
    })

    if (res.status === 200) {
      toast.add({ title: isEdit ? 'Administrador actualizado' : 'Administrador creado', color: 'success' })
      isSlideoverOpen.value = false
      await fetchAdmins()
    } else {
      toast.add({ title: res.results || 'Error al guardar', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de red', color: 'error' })
  } finally {
    savingAdmin.value = false
  }
}

async function handleDelete(admin: any) {
  if (!confirm(`¿Eliminar al administrador ${decode(admin.name_admin)}?`)) return
  try {
    const res = await $fetch<any>('/api/admins', {
      method: 'DELETE',
      headers: apiHeaders,
      query: { id: admin.id_admin, nameId: 'id_admin', token: 'no', except: 'id_admin' }
    })
    if (res.status === 200) {
      toast.add({ title: 'Eliminado correctamente', color: 'success' })
      await fetchAdmins()
    } else {
      toast.add({ title: res.results || 'Error al eliminar', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de conexión', color: 'error' })
  }
}

// Import/Export Logic
function handleExportCSV() {
  if (admins.value.length === 0) return
  const headers = ['ID', 'Nombre', 'Apellido', 'Email', 'Rol', 'Sucursal', 'Estado']
  const rows = admins.value.map(a => [
    a.id_admin,
    decode(a.name_admin),
    decode(a.surname_admin),
    decode(a.email_admin),
    a.rol_admin,
    getOfficeName(a.id_office_admin),
    a.status_admin == 1 ? 'Activo' : 'Inactivo'
  ])

  const csvContent = "\ufeffsep=;\n" + [headers.join(';'), ...rows.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(';'))].join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement("a")
  link.setAttribute("href", url)
  link.setAttribute("download", `export_admins_${new Date().toISOString().split('T')[0]}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

onMounted(async () => {
  await fetchOffices()
  await fetchWarehouses()
  await fetchAdmins()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Top KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <UCard class="bg-gradient-to-br from-indigo-500 to-indigo-600 border-0 shadow-md">
        <div class="flex justify-between items-center text-white">
          <div>
            <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-wider">Total Administradores</p>
            <h2 class="text-3xl font-black mt-1">{{ totalAdmins }}</h2>
          </div>
          <UIcon name="i-lucide-users" class="w-10 h-10 text-white/30" />
        </div>
      </UCard>

      <UCard class="bg-gradient-to-br from-emerald-500 to-emerald-600 border-0 shadow-md">
        <div class="flex justify-between items-center text-white">
          <div>
            <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-wider">Activos en el Sistema</p>
            <h2 class="text-3xl font-black mt-1">{{ activeAdmins }}</h2>
          </div>
          <UIcon name="i-lucide-user-check" class="w-10 h-10 text-white/30" />
        </div>
      </UCard>

      <UCard>
        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Superadmins / Admins</p>
        <h2 class="text-2xl font-black text-slate-800 dark:text-white mt-1">
          {{ (roleStats['superadmin'] || 0) + (roleStats['admin'] || 0) }}
        </h2>
        <p class="text-[10px] text-slate-400 mt-1">Personal de control central</p>
      </UCard>

      <UCard>
        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Operadores / Cajeros</p>
        <h2 class="text-2xl font-black text-slate-800 dark:text-white mt-1">
          {{ (roleStats['cajero'] || 0) + (roleStats['vendedor'] || 0) + (roleStats['lab_worker'] || 0) }}
        </h2>
        <p class="text-[10px] text-slate-400 mt-1">Personal operativo</p>
      </UCard>
    </div>

    <!-- Table header controls -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-4 rounded-xl shadow-sm">
      <div>
        <h1 class="text-base font-extrabold text-slate-800 dark:text-white">Gestión Avanzada de Administradores</h1>
        <p class="text-[11px] text-slate-400 mt-0.5">Control de cuentas de acceso, roles y asignación de permisos interactivos.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
        <UInput
          v-model="search"
          icon="i-lucide-search"
          placeholder="Buscar administrador..."
          size="sm"
          class="w-full sm:w-48"
        />
        <UButton icon="i-lucide-download" color="neutral" variant="outline" size="sm" @click="handleExportCSV">Exportar</UButton>
        <UButton icon="i-lucide-user-plus" color="primary" size="sm" class="active:scale-95 duration-100 transition-transform font-bold" @click="openCreate">Crear Admin</UButton>
      </div>
    </div>

    <!-- Main Admins Table -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
      <div v-if="loading" class="flex justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-indigo-500" />
      </div>
      <div v-else-if="filteredAdmins.length === 0" class="text-center py-12 text-slate-400">
        No se encontraron cuentas de administradores.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-500 text-xs font-bold uppercase">
              <th class="px-4 py-3">Administrador</th>
              <th class="px-4 py-3">Contacto (Email)</th>
              <th class="px-4 py-3">Rol del Sistema</th>
              <th class="px-4 py-3">Sucursal Asignada</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
            <tr v-for="a in paginatedAdmins" :key="a.id_admin" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
              <td class="px-4 py-3 flex items-center gap-3">
                <UAvatar
                  :src="a.img_admin ? decode(a.img_admin) : ''"
                  :alt="decode(a.name_admin)"
                  size="md"
                  class="border shadow-xs shrink-0"
                />
                <div class="min-w-0">
                  <p class="font-bold text-slate-950 dark:text-white text-sm">{{ decode(a.name_admin) }} {{ decode(a.surname_admin) }}</p>
                  <p class="text-xs text-slate-400 font-mono">ID: {{ a.id_admin }}</p>
                </div>
              </td>
              <td class="px-4 py-3">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ decode(a.email_admin) }}</p>
              </td>
              <td class="px-4 py-3">
                <UBadge
                  :color="['superadmin','admin'].includes(a.rol_admin) ? 'error' : 'primary'"
                  variant="subtle"
                  size="xs"
                  class="uppercase font-bold"
                >
                  {{ a.rol_admin }}
                </UBadge>
              </td>
              <td class="px-4 py-3">
                <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ getOfficeName(a.id_office_admin) }}</span>
              </td>
              <td class="px-4 py-3">
                <!-- Custom Tailwind switch for table status -->
                <button
                  type="button"
                  @click="toggleStatus(a)"
                  :class="[
                    a.status_admin == 1 ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-800',
                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none'
                  ]"
                >
                  <span
                    :class="[
                      a.status_admin == 1 ? 'translate-x-5' : 'translate-x-0',
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <UButtonGroup size="xs">
                    <UButton
                      icon="i-lucide-key-round"
                      color="neutral"
                      variant="soft"
                      title="Cambiar Contraseña"
                      @click="openResetPassword(a)"
                    />
                    <UButton
                      icon="i-lucide-edit"
                      color="neutral"
                      variant="ghost"
                      title="Editar cuenta y permisos"
                      @click="openEdit(a)"
                    />
                    <UButton
                      icon="i-lucide-trash"
                      color="error"
                      variant="ghost"
                      title="Eliminar"
                      @click="handleDelete(a)"
                    />
                  </UButtonGroup>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Slideover Form to Add/Edit (Contains both credentials and permission matrix) -->
    <USlideover
      v-model:open="isSlideoverOpen"
      :title="selectedAdmin ? 'Editar Administrador y Permisos' : 'Nuevo Administrador'"
      class="z-50"
    >
      <template #body>
        <div class="space-y-4 p-1">
          <UFormField label="Nombre(s) *">
            <UInput v-model="formModel.name_admin" placeholder="Ej. Juan" class="w-full text-sm" />
          </UFormField>
          <UFormField label="Apellido(s)">
            <UInput v-model="formModel.surname_admin" placeholder="Ej. Pérez" class="w-full text-sm" />
          </UFormField>
          <UFormField label="Correo Electrónico *">
            <UInput v-model="formModel.email_admin" type="email" placeholder="Ej. juan@unitech.com" class="w-full text-sm" />
          </UFormField>
          <UFormField :label="selectedAdmin ? 'Contraseña (Dejar en blanco para mantener)' : 'Contraseña *'">
            <UInput v-model="formModel.password_admin" type="password" placeholder="••••••••" class="w-full text-sm" />
          </UFormField>
          <UFormField label="Rol del Sistema">
            <select v-model="formModel.rol_admin" class="block w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1.5 focus:outline-none focus:border-indigo-500">
              <option value="superadmin">Super Administrador</option>
              <option value="admin">Administrador</option>
              <option value="cajero">Cajero / Caja</option>
              <option value="vendedor">Vendedor / Ventas</option>
              <option value="despachador">Despachador</option>
              <option value="lab_admin">Admin Laboratorio</option>
              <option value="lab_worker">Operador Laboratorio</option>
              <option value="lab_calidad">Control Calidad</option>
            </select>
          </UFormField>
          <UFormField v-if="formModel.rol_admin !== 'despachador'" label="Sucursal Asignada">
            <select v-model="formModel.id_office_admin" class="block w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1.5 focus:outline-none focus:border-indigo-500">
              <option value="">Todas (Super)</option>
              <option v-for="o in offices" :key="o.id_office" :value="String(o.id_office)">
                {{ decodeURIComponent(o.title_office || '').replace(/\+/g, ' ') }}
              </option>
            </select>
          </UFormField>
          <UFormField v-if="formModel.rol_admin === 'despachador'" label="Almacén Asignado">
            <select v-model="formModel.id_warehouse_admin" class="block w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1.5 focus:outline-none focus:border-indigo-500">
              <option value="">Ninguno</option>
              <option v-for="w in warehouses" :key="w.id_warehouse" :value="String(w.id_warehouse)">
                {{ decodeURIComponent(w.title_warehouse || '').replace(/\+/g, ' ') }}
              </option>
            </select>
          </UFormField>

          <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900 rounded-lg">
            <span class="text-sm font-bold text-slate-500 uppercase">Estado Cuenta</span>
            <button
              type="button"
              @click="formModel.status_admin = !formModel.status_admin"
              :class="[
                formModel.status_admin ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-800',
                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none'
              ]"
            >
              <span
                :class="[
                  formModel.status_admin ? 'translate-x-5' : 'translate-x-0',
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                ]"
              />
            </button>
          </div>

          <!-- INTEGRATED PERMISSIONS MATRIX -->
          <div class="border-t border-slate-200 dark:border-slate-800 pt-4 mt-4 space-y-3">
            <div class="flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
              <UIcon name="i-lucide-shield-check" class="w-4.5 h-4.5" />
              <h3 class="text-sm font-black uppercase tracking-wider">Asignación Directa de Permisos</h3>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">Selecciona los módulos o pantallas que esta cuenta tendrá permitido visualizar e interactuar.</p>
            
            <div class="grid grid-cols-1 gap-1 max-h-60 overflow-y-auto pr-1 border border-slate-100 dark:border-slate-800/60 rounded-lg p-2 bg-slate-50/50 dark:bg-slate-900/40">
              <div v-for="(val, key) in permForm" :key="key" class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/40 last:border-b-0">
                <span class="text-sm font-bold text-slate-700 dark:text-slate-300 capitalize">{{ key.replace('_', ' ') }}</span>
                <button
                  type="button"
                  @click="permForm[key] = !permForm[key]"
                  :class="[
                    permForm[key] ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-800',
                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none'
                  ]"
                >
                  <span
                    :class="[
                      permForm[key] ? 'translate-x-5' : 'translate-x-0',
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2 p-4 border-t w-full bg-slate-50 dark:bg-slate-900">
          <UButton color="neutral" variant="ghost" size="sm" @click="isSlideoverOpen = false">Cancelar</UButton>
          <UButton color="primary" size="sm" :loading="savingAdmin" @click="handleSaveAdmin">Guardar Administrador</UButton>
        </div>
      </template>
    </USlideover>


    <!-- Change Password Modal -->
    <UModal v-model:open="isResetPasswordOpen" title="Establecer Nueva Contraseña">
      <template #body>
        <div class="space-y-4 p-1">
          <p class="text-xs text-slate-500">Ingresa la nueva clave secreta para esta cuenta. Se guardará de forma encriptada en la base de datos.</p>
          <UFormField label="Nueva Contraseña">
            <UInput v-model="newPassword" type="password" placeholder="Mínimo 4 caracteres..." class="w-full" />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" size="sm" @click="isResetPasswordOpen = false">Cancelar</UButton>
          <UButton color="primary" size="sm" :loading="resettingPassword" @click="handleResetPassword">Confirmar Clave</UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>
