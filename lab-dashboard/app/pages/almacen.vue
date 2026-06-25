<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()

function getImageUrl(imgStr: string) {
  if (!imgStr) return '/views/assets/img/multimedia.png'
  const decoded = decodeURIComponent(imgStr).replace(/\+/g, ' ')
  
  // Si la ruta absoluta incluye nuestro propio directorio de views, la forzamos a ser relativa
  // Esto arregla el problema de dominios inactivos guardados en BD (ej. pos.desarrolloweb24siete.com)
  const viewsIndex = decoded.indexOf('views/')
  if (viewsIndex !== -1) {
    return '/' + decoded.substring(viewsIndex)
  }

  if (decoded.startsWith('http') || decoded.startsWith('/')) {
    return decoded
  }
  return '/' + decoded
}

// State
const admins = ref<any[]>([])
const officesMap = ref<Record<string, string>>({})
const warehouses = ref<any[]>([])
const movements = ref<any[]>([])
const wastePackaged = ref<any[]>([])

const loadingWarehouses = ref(true)
const loadingMoves = ref(true)
const loadingWaste = ref(true)
const activeTab = ref(0)

const officesList = ref<any[]>([])
const warehousesList = ref<any[]>([])

const apiHeaders = {
  Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
}

// Fetch general resources
async function fetchAdmins() {
  try {
    const data = await $fetch<any>('/api/admins?linkTo=status_admin&equalTo=1', {
      headers: apiHeaders
    })
    if (data.status === 200) {
      admins.value = (data.results || []).filter((a: any) => a.rol_admin !== 'superadmin')
    }
  } catch (e) {
    console.error('Error fetching admins:', e)
  }
}

async function fetchOffices() {
  try {
    const data = await $fetch<any>('/api/offices', {
      headers: apiHeaders
    })
    if (data.status === 200 && data.results) {
      officesList.value = data.results
      const map: Record<string, string> = {}
      data.results.forEach((o: any) => {
        map[o.id_office] = decodeURIComponent(o.title_office).replace(/\+/g, ' ')
      })
      officesMap.value = map
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
    if (data.status === 200 && data.results) {
      warehousesList.value = data.results
    }
  } catch (e) {
    console.error('Error fetching warehouses:', e)
  }
}

// Fetch All Warehouses Stock (Tab 0)
async function fetchAllWarehousesStock() {
  loadingWarehouses.value = true
  try {
    // Para superadmin (sin sucursal asignada), enviar 0 para traer TODOS los almacenes
    const officeId = auth.effectiveOfficeId || 0
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getAllWarehousesStock: 'true',
        id_office: String(officeId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      warehouses.value = data.results || []
    } else {
      warehouses.value = []
    }
  } catch (e) {
    console.error('Error fetching warehouses stock:', e)
    warehouses.value = []
  } finally {
    loadingWarehouses.value = false
  }
}

// Fetch Movements (Tab 1)
async function fetchMovements() {
  loadingMoves.value = true
  try {
    const officeId = auth.effectiveOfficeId ?? 3
    const adminId = auth.user?.id_admin || 1
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getWarehouseMovements: 'true',
        id_office: String(officeId),
        id_dispatcher: String(adminId),
        id_warehouse: String(auth.warehouseId || 0)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    movements.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Error fetching movements:', e)
    movements.value = []
  } finally {
    loadingMoves.value = false
  }
}

// Fetch Waste Packaged (Tab 2)
async function fetchWastePackaged() {
  loadingWaste.value = true
  try {
    const officeId = auth.effectiveOfficeId ?? 3
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getWastePackaged: 'true',
        id_office: String(officeId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      wastePackaged.value = data.results
    }
  } catch (e) {
    console.error('Error fetching waste packaged:', e)
  } finally {
    loadingWaste.value = false
  }
}

onMounted(async () => {
  await fetchOffices()
  await fetchWarehouses()
  await fetchAdmins()
  await fetchAllWarehousesStock()
  await fetchMovements()
  await fetchWastePackaged()
})

const tabsItems = [
  { label: 'Almacenes', icon: 'i-lucide-boxes', value: 0 },
  { label: 'Movimientos', icon: 'i-lucide-arrow-right-left', value: 1 },
  { label: 'Merma Envasada', icon: 'i-lucide-recycle', value: 2 }
]

function exportCSV() {
  if (activeTab.value === 0) {
    if (warehouses.value.length === 0) return
    const headers = ['Almacén', 'SKU', 'Producto', 'Disponible']
    const rows: any[] = []
    
    warehouses.value.forEach(wh => {
      const whName = decodeURIComponent(wh.title_warehouse || '').replace(/\+/g, ' ')
      if (wh.products && wh.products.length > 0) {
        wh.products.forEach((p: any) => {
          rows.push([
            whName,
            p.sku_product,
            decodeURIComponent(p.title_product || '').replace(/\+/g, ' '),
            parseFloat(p.stock) || 0
          ])
        })
      }
    })
    
    if (rows.length === 0) return

    const csvContent = "\ufeff" + [headers.join(','), ...rows.map(r => r.map(v => `"${v}"`).join(','))].join('\n')
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.setAttribute("href", url)
    link.setAttribute("download", `inventario_almacenes_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } else if (activeTab.value === 1) {
    if (movements.value.length === 0) return
    const headers = ['Fecha', 'Tipo', 'Producto', 'Cantidad', 'Destinatario', 'Sucursal Destino', 'Despachador', 'Notas']
    const rows = movements.value.map(m => [
      m.date_created_assignment,
      m.type_assignment,
      decodeURIComponent(m.title_product || '').replace(/\+/g, ' '),
      m.qty_assignment,
      m.name_admin,
      m.office_name ? decodeURIComponent(m.office_name).replace(/\+/g, ' ') : '',
      m.dispatcher_name,
      m.notes_assignment || ''
    ])
    const csvContent = "\ufeff" + [headers.join(','), ...rows.map(r => r.map(v => `"${v}"`).join(','))].join('\n')
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.setAttribute("href", url)
    link.setAttribute("download", `movimientos_almacen_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } else {
    toast.add({ title: 'Exportación disponible para almacenes y movimientos.', color: 'info' })
  }
}

function getMovementColor(type: string): 'primary' | 'warning' | 'success' | 'error' | 'neutral' {
  if (type === 'despacho') return 'primary'
  if (type === 'despacho_pendiente') return 'warning'
  if (type === 'devolucion') return 'warning'
  if (type === 'traspaso') return 'success'
  if (type === 'venta') return 'error'
  if (type === 'rechazado') return 'error'
  if (type === 'enviado_pendiente') return 'warning'
  if (type === 'enviado_confirmado') return 'success'
  return 'neutral'
}

function getMovementLabel(type: string): string {
  if (type === 'despacho') return 'Recibido'
  if (type === 'despacho_pendiente') return 'En tránsito'
  if (type === 'devolucion') return 'Devolución'
  if (type === 'traspaso') return 'Traspaso'
  if (type === 'venta') return 'Venta'
  if (type === 'rechazado') return 'Rechazado'
  if (type === 'enviado_pendiente') return 'Enviado (pendiente)'
  if (type === 'enviado_confirmado') return 'Enviado (confirmado)'
  return type || '-'
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white border border-slate-200 p-6 rounded-xl flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight bg-gradient-to-r from-teal-400 to-emerald-300 bg-clip-text text-transparent">
          Almacén Principal
        </h1>
        <p class="text-xs text-slate-400 mt-1">
          Visualiza el stock de todos los almacenes de tu sucursal.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <UButton
          v-if="activeTab === 0 || activeTab === 1"
          icon="i-lucide-download"
          color="neutral"
          variant="outline"
          size="xs"
          @click="exportCSV"
        >
          Exportar CSV
        </UButton>
        <UButton
          icon="i-lucide-refresh-cw"
          color="neutral"
          variant="soft"
          size="xs"
          @click="activeTab === 0 ? fetchAllWarehousesStock() : activeTab === 1 ? fetchMovements() : fetchWastePackaged()"
        >
          Refrescar
        </UButton>
      </div>
    </div>

    <!-- Tabs Layout -->
    <UTabs :items="tabsItems" v-model="activeTab" class="w-full">
      <template #content="{ index }">
        <!-- TAB 0: Almacenes -->
        <div v-if="index === 0" class="mt-4">
          <div v-if="loadingWarehouses" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="warehouses.length === 0" class="text-center py-12 bg-slate-50 border border-slate-200 rounded-xl text-slate-500">
            No se encontraron almacenes en esta sucursal.
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="wh in warehouses"
              :key="wh.id_warehouse"
              class="bg-white border border-slate-200 rounded-xl overflow-hidden flex flex-col justify-between"
            >
              <!-- Card Header -->
              <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div>
                  <h3 class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                    <UIcon name="i-lucide-box" class="text-teal-400" />
                    {{ decodeURIComponent(wh.title_warehouse || '').replace(/\+/g, ' ') }}
                  </h3>
                  <span class="text-[10px] text-slate-400 block mt-0.5">
                    Encargado: {{ wh.admin_name ? decodeURIComponent(wh.admin_name).replace(/\+/g, ' ') : 'Sin asignar' }}
                    <span v-if="wh.office_name" class="ml-2 text-slate-300">·</span>
                    <span v-if="wh.office_name" class="ml-1 font-medium text-slate-500">{{ decodeURIComponent(wh.office_name || '').replace(/\+/g, ' ') }}</span>
                  </span>
                </div>
                <UBadge color="success" size="xs">Total: {{ wh.total_stock }} u.</UBadge>
              </div>

              <!-- Card Body (Table of products in this warehouse) -->
              <div class="p-0">
                <table v-if="wh.products && wh.products.length > 0" class="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr class="bg-slate-100/60 text-slate-400 border-b border-slate-200">
                      <th class="p-2.5">Producto</th>
                      <th class="p-2.5 text-right">Stock Real</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="p in wh.products" :key="p.id_product" class="border-b border-slate-200/40 hover:bg-slate-50">
                      <td class="p-2.5 text-slate-700">
                        {{ decodeURIComponent(p.title_product || '').replace(/\+/g, ' ') }}
                      </td>
                      <td class="p-2.5 text-right font-mono font-bold text-teal-400">
                        {{ p.stock }}
                      </td>
                    </tr>
                  </tbody>
                </table>
                <div v-else class="text-center py-6 text-slate-500 text-xs">
                  Sin stock disponible en este almacén.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 1: Warehouse Movements Log -->
        <div v-if="index === 1" class="mt-4">
          <div v-if="loadingMoves" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="movements.length === 0" class="text-center py-12 bg-slate-50 border border-slate-200 rounded-xl text-slate-500">
            No se encontraron movimientos registrados en la bitácora.
          </div>

          <div v-else class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse text-sm text-slate-700 bg-white">
              <thead>
                <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                  <th class="p-4">Fecha</th>
                  <th class="p-4">Tipo</th>
                  <th class="p-4">Producto</th>
                  <th class="p-4 text-center">Cant.</th>
                  <th class="p-4">Destinatario</th>
                  <th class="p-4">Sucursal Destino</th>
                  <th class="p-4">Despachador</th>
                  <th class="p-4">Notas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="m in movements" :key="m.date_created_assignment" class="border-b border-slate-100 hover:bg-slate-50">
                  <td class="p-4 font-mono text-xs">{{ m.date_created_assignment }}</td>
                  <td class="p-4">
                    <UBadge
                      :color="getMovementColor(m.type_assignment)"
                      variant="subtle"
                      class="capitalize font-semibold"
                    >
                      {{ getMovementLabel(m.type_assignment) }}
                    </UBadge>
                  </td>
                  <td class="p-4 font-semibold text-slate-800">{{ decodeURIComponent(m.title_product || '').replace(/\+/g, ' ') }}</td>
                  <td class="p-4 text-center font-mono font-bold">{{ m.qty_assignment }}</td>
                  <td class="p-4">{{ m.name_admin }}</td>
                  <td class="p-4">{{ m.office_name ? decodeURIComponent(m.office_name).replace(/\+/g, ' ') : '-' }}</td>
                  <td class="p-4 text-xs">{{ m.dispatcher_name }}</td>
                  <td class="p-4 text-xs italic">{{ m.notes_assignment || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 2: Merma Envasada -->
        <div v-if="index === 2" class="mt-4">
          <div v-if="loadingWaste" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="wastePackaged.length === 0" class="text-center py-12 bg-slate-50 border border-slate-200 rounded-xl text-slate-500">
            No se encontró merma envasada.
          </div>

          <div v-else class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse text-sm text-slate-700 bg-white">
              <thead>
                <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                  <th class="p-4">ID Producción</th>
                  <th class="p-4">Fecha de Registro</th>
                  <th class="p-4">Producto Final Envasado</th>
                  <th class="p-4 text-center">Cantidad Mermada</th>
                  <th class="p-4">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="w in wastePackaged" :key="w.id_waste" class="border-b border-slate-100 hover:bg-slate-50">
                  <td class="p-4 font-mono text-xs">#{{ w.id_production_waste }}</td>
                  <td class="p-4 font-mono text-xs">{{ w.date_created_waste }}</td>
                  <td class="p-4 font-semibold text-slate-800">{{ decodeURIComponent(w.pkg_name_production || w.title_product || '').replace(/\+/g, ' ') }}</td>
                  <td class="p-4 text-center font-mono font-bold text-rose-400">{{ w.qty_waste }} {{ w.unit_product }}</td>
                  <td class="p-4">
                    <UBadge color="warning" variant="soft">{{ w.status_waste === 'en_almacen' ? 'En Almacén' : w.status_waste }}</UBadge>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </UTabs>
  </div>
</template>
