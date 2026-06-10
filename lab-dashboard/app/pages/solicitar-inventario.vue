<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { decodeText } from '~/utils/format'

definePageMeta({ middleware: ['auth'] })

const auth = useAuthStore()
const api = useApi()
const toast = useToast()
const route = useRoute()

// State for Warehouses and Products
const warehouses = ref<any[]>([])
const offices = ref<any[]>([])
const products = ref<any[]>([])
const transferProducts = ref<any[]>([])
const myRequests = ref<any[]>([])

const loadingWarehouses = ref(true)
const loadingOffices = ref(true)
const loadingProducts = ref(false)
const loadingTransferProducts = ref(false)
const loadingRequests = ref(true)
const submitting = ref(false)
const submittingTransfer = ref(false)

// Tabs config
const currentTab = ref('solicitar')

// Form states
const form = ref({
  warehouseId: '',
  productId: '',
  qty: 1,
  notes: ''
})

const transferForm = ref({
  originOfficeId: auth.officeId ? String(auth.officeId) : '',
  destOfficeId: '',
  productId: '',
  qty: 1,
  notes: ''
})

function requireUser() {
  if (!auth.user?.id_admin) {
    toast.add({ title: 'Sesión incompleta', description: 'Vuelva a iniciar sesión', color: 'error' })
    return null
  }
  return auth.user.id_admin
}

function requireOffice() {
  const oid = auth.officeId
  if (!oid) {
    toast.add({ title: 'Oficina no asignada', description: 'Contacte al administrador', color: 'error' })
    return null
  }
  return oid
}

// Fetch Warehouses
async function fetchWarehouses() {
  loadingWarehouses.value = true
  try {
    const data = await api.rest<any>('/api/warehouses?orderBy=id_warehouse&orderMode=DESC')
    warehouses.value = data.status === 200 && data.results ? data.results : []
  } catch (e) {
    console.error('Error fetching warehouses:', e)
    warehouses.value = []
  } finally {
    loadingWarehouses.value = false
  }
}

// Fetch Offices
async function fetchOffices() {
  loadingOffices.value = true
  try {
    const data = await api.rest<any>('/api/offices')
    offices.value = data.status === 200 && data.results ? data.results : []
  } catch (e) {
    console.error('Error fetching offices:', e)
    offices.value = []
  } finally {
    loadingOffices.value = false
  }
}

// Fetch Products based on selected Warehouse
async function fetchWarehouseProducts(warehouseId: string) {
  if (!warehouseId) {
    products.value = []
    return
  }
  loadingProducts.value = true
  try {
    const data = await api.ajax({ getWarehouseProducts: 'true', id_warehouse: warehouseId })
    products.value = Array.isArray(data) ? data : (Array.isArray(data?.results) ? data.results : [])
    
    if (products.value.length === 0 && !loadingProducts.value) {
      toast.add({
        title: 'Atención',
        description: 'Este almacén no tiene productos con stock disponible.',
        color: 'warning'
      })
    }
  } catch (e) {
    console.error('Error fetching warehouse products:', e)
    products.value = []
  } finally {
    loadingProducts.value = false
  }
}

// Fetch Products based on selected Office (For Transfers)
async function fetchOfficeProducts(officeId: string) {
  if (!officeId) {
    transferProducts.value = []
    return
  }
  loadingTransferProducts.value = true
  try {
    const data = await api.ajax({ getOfficeProducts: 'true', id_office: officeId })
    transferProducts.value = Array.isArray(data?.results) ? data.results : []
    
    if (transferProducts.value.length === 0) {
      toast.add({
        title: 'Atención',
        description: 'Esta sucursal no tiene productos con stock disponible para traspaso.',
        color: 'warning'
      })
    }
  } catch (e) {
    console.error('Error fetching office products:', e)
    transferProducts.value = []
  } finally {
    loadingTransferProducts.value = false
  }
}

// Watchers
function onWarehouseChange() {
  form.value.productId = ''
  form.value.qty = 1
  fetchWarehouseProducts(form.value.warehouseId)
}

watch(() => transferForm.value.originOfficeId, (newVal) => {
  transferForm.value.productId = ''
  transferForm.value.qty = 1
  if(newVal) {
    fetchOfficeProducts(newVal)
  } else {
    transferProducts.value = []
  }
})

const selectedProductData = computed(() => {
  if (!form.value.productId) return null
  return products.value.find(p => String(p.id_product) === String(form.value.productId)) || null
})

const selectedTransferProductData = computed(() => {
  if (!transferForm.value.productId) return null
  return transferProducts.value.find(p => String(p.id_product) === String(transferForm.value.productId)) || null
})

const maxStock = computed(() => {
  return selectedProductData.value ? parseFloat(selectedProductData.value.stock) : 0
})

const maxTransferStock = computed(() => {
  return selectedTransferProductData.value ? parseFloat(selectedTransferProductData.value.stock) : 0
})

// Fetch My Requests
async function fetchMyRequests() {
  loadingRequests.value = true
  try {
    const adminId = requireUser()
    if (!adminId) { loadingRequests.value = false; return }
    const data = await api.ajax({ getMyRequests: 'true', id_admin: adminId })
    myRequests.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Error fetching my requests:', e)
    myRequests.value = []
  } finally {
    loadingRequests.value = false
  }
}

// Submit Request to Central
async function submitRequest() {
  if (!form.value.warehouseId) return toast.add({ title: 'Error', description: 'Selecciona un almacén', color: 'error' })
  if (!form.value.productId) return toast.add({ title: 'Error', description: 'Selecciona un producto', color: 'error' })
  if (!form.value.qty || form.value.qty <= 0) return toast.add({ title: 'Error', description: 'Ingresa una cantidad válida', color: 'error' })
  if (form.value.qty > maxStock.value) return toast.add({ title: 'Error', description: `La cantidad supera el stock disponible (${maxStock.value})`, color: 'error' })

  const adminId = requireUser()
  const officeId = requireOffice()
  if (!adminId || !officeId) return

  submitting.value = true
  try {
    const response = await api.ajax({
      createInventoryRequest: 'true',
      id_product: form.value.productId,
      qty: String(form.value.qty),
      notes: form.value.notes,
      id_admin: String(adminId),
      id_office: String(officeId),
      id_warehouse: form.value.warehouseId
    })
    
    if (response?.status === 200 || response?.message === 'ok') {
      toast.add({ title: 'Éxito', description: 'Solicitud enviada correctamente', color: 'success' })
      form.value.warehouseId = ''
      form.value.productId = ''
      form.value.qty = 1
      form.value.notes = ''
      products.value = []
      await fetchMyRequests()
    } else {
      toast.add({ title: 'Error', description: response || 'No se pudo enviar la solicitud', color: 'error' })
    }
  } catch (e) {
    console.error('Error submitting request:', e)
    toast.add({ title: 'Error', description: 'Ocurrió un error inesperado.', color: 'error' })
  } finally {
    submitting.value = false
  }
}

// Submit Direct Transfer
async function submitTransfer() {
  if (!transferForm.value.originOfficeId) return toast.add({ title: 'Error', description: 'Selecciona un origen', color: 'error' })
  if (!transferForm.value.destOfficeId) return toast.add({ title: 'Error', description: 'Selecciona un destino', color: 'error' })
  if (transferForm.value.originOfficeId === transferForm.value.destOfficeId) return toast.add({ title: 'Error', description: 'El origen y destino deben ser diferentes', color: 'error' })
  if (!transferForm.value.productId) return toast.add({ title: 'Error', description: 'Selecciona un producto', color: 'error' })
  if (!transferForm.value.qty || transferForm.value.qty <= 0) return toast.add({ title: 'Error', description: 'Ingresa una cantidad válida', color: 'error' })
  if (transferForm.value.qty > maxTransferStock.value) return toast.add({ title: 'Error', description: `La cantidad supera el stock disponible (${maxTransferStock.value})`, color: 'error' })

  const adminId = requireUser()
  const officeId = requireOffice()
  if (!adminId || !officeId) return
  if (String(transferForm.value.originOfficeId) !== String(officeId) && auth.role !== 'superadmin') {
    return toast.add({ title: 'Error', description: 'Solo puede traspasar desde su sucursal', color: 'error' })
  }

  submittingTransfer.value = true
  try {
    const data = await api.ajax({
      createStockTransfer: 'true',
      id_origin_office: transferForm.value.originOfficeId,
      id_dest_office: transferForm.value.destOfficeId,
      id_product: transferForm.value.productId,
      qty: String(transferForm.value.qty),
      notes: transferForm.value.notes,
      id_admin: String(adminId)
    })
    if (data?.status === 200) {
      toast.add({ title: 'Éxito', description: 'Traspaso completado correctamente', color: 'success' })
      transferForm.value.productId = ''
      transferForm.value.qty = 1
      transferForm.value.notes = ''
      // Refresh stock
      await fetchOfficeProducts(transferForm.value.originOfficeId)
    } else {
      toast.add({ title: 'Error', description: data.message || 'No se pudo completar el traspaso', color: 'error' })
    }
  } catch (e) {
    console.error('Error submitting transfer:', e)
    toast.add({ title: 'Error', description: 'Ocurrió un error inesperado.', color: 'error' })
  } finally {
    submittingTransfer.value = false
  }
}

onMounted(async () => {
  await fetchWarehouses()
  await fetchOffices()
  await fetchMyRequests()
  
  if (route.query.tab === 'traspaso') {
    currentTab.value = 'traspaso'
    transferForm.value.originOfficeId = String(auth.officeId || 6)
    if (route.query.product_id) {
      await fetchOfficeProducts(transferForm.value.originOfficeId)
      transferForm.value.productId = String(route.query.product_id)
    }
  } else {
    if (transferForm.value.originOfficeId) {
      fetchOfficeProducts(transferForm.value.originOfficeId)
    }
  }
})

const columns = [
  { accessorKey: 'date_created_request', header: 'Fecha' },
  { accessorKey: 'title_warehouse', header: 'Almacén' },
  { accessorKey: 'title_product', header: 'Producto' },
  { accessorKey: 'qty_request', header: 'Solicitado' },
  { accessorKey: 'qty_dispatched_request', header: 'Despachado' },
  { accessorKey: 'status_request', header: 'Estado' },
  { accessorKey: 'notes_request', header: 'Notas' }
]

const formatText = decodeText

function getStatusColor(status: string) {
  switch (status) {
    case 'pendiente': return 'warning'
    case 'despachada': return 'success'
    case 'rechazada': return 'error'
    default: return 'neutral'
  }
}

function getStatusLabel(status: string) {
  switch (status) {
    case 'pendiente': return 'Pendiente'
    case 'despachada': return 'Despachada'
    case 'rechazada': return 'Rechazada'
    default: return status || 'Desconocido'
  }
}
</script>

<template>
  <div class="w-full space-y-6">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
          <UIcon name="i-lucide-arrow-left-right" class="w-6 h-6 text-indigo-600" />
          Movimientos de Inventario
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          Crea solicitudes al almacén central o realiza traspasos directos entre sucursales.
        </p>
      </div>
      <UButtonGroup size="sm" class="bg-slate-100 p-1 rounded-lg">
        <UButton :variant="currentTab === 'solicitar' ? 'solid' : 'ghost'" color="primary" @click="currentTab = 'solicitar'">Solicitar a Central</UButton>
        <UButton :variant="currentTab === 'traspaso' ? 'solid' : 'ghost'" color="primary" @click="currentTab = 'traspaso'">Traspaso Directo</UButton>
      </UButtonGroup>
    </div>

    <!-- Content Layout -->
    <div v-if="currentTab === 'solicitar'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Formulario Solicitud -->
      <div class="lg:col-span-4">
        <UCard class="border-t-4 border-t-indigo-500">
          <template #header>
            <h3 class="font-semibold text-lg flex items-center gap-2">
              <UIcon name="i-lucide-plus-circle" class="w-5 h-5 text-gray-500" />
              Nueva Solicitud
            </h3>
          </template>

          <form @submit.prevent="submitRequest" class="space-y-4">
            <UFormGroup label="Almacén de Origen *">
              <USelect
                v-model="form.warehouseId"
                :items="warehouses.map(w => ({ value: String(w.id_warehouse), label: formatText(w.title_warehouse) }))"
                placeholder="-- Seleccionar almacén --"
                :loading="loadingWarehouses"
                @update:model-value="onWarehouseChange"
                required
              />
            </UFormGroup>

            <UFormGroup label="Producto a solicitar *">
              <USelect
                v-model="form.productId"
                :items="products.map(p => ({ value: String(p.id_product), label: `${formatText(p.title_product)} (Stock: ${p.stock})` }))"
                placeholder="-- Seleccionar producto --"
                :disabled="!form.warehouseId"
                :loading="loadingProducts"
                required
              />
            </UFormGroup>

            <UFormGroup label="Cantidad a solicitar *">
              <UInput
                v-model.number="form.qty"
                type="number"
                min="1"
                :max="maxStock"
                required
              />
              <p v-if="selectedProductData" class="text-xs text-slate-500 mt-1">Max disponible: {{ maxStock }}</p>
            </UFormGroup>

            <UFormGroup label="Notas (opcional)">
              <UTextarea
                v-model="form.notes"
                placeholder="Justificación de la solicitud..."
                :rows="2"
              />
            </UFormGroup>

            <UButton
              type="submit"
              color="primary"
              class="w-full justify-center mt-4 bg-indigo-600 hover:bg-indigo-700"
              icon="i-lucide-paper-plane"
              :loading="submitting"
            >
              Enviar Solicitud
            </UButton>
          </form>
        </UCard>
      </div>

      <!-- Mis Solicitudes -->
      <div class="lg:col-span-8">
        <UCard>
          <template #header>
            <h3 class="font-semibold text-lg flex items-center gap-2">
              <UIcon name="i-lucide-list" class="w-5 h-5 text-gray-500" />
              Historial de Solicitudes
            </h3>
          </template>

          <div v-if="loadingRequests" class="py-8 flex justify-center text-indigo-600">
            <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin" />
          </div>

          <div v-else-if="myRequests.length === 0" class="py-10 flex flex-col items-center">
            <UIcon name="i-lucide-inbox" class="w-12 h-12 text-slate-300 mb-3" />
            <p class="text-slate-500 font-medium">No tienes solicitudes registradas</p>
          </div>

          <UTable v-else :columns="columns" :data="myRequests" class="w-full">
            <template #title_warehouse-cell="{ row }">
              {{ formatText(row.original.title_warehouse) }}
            </template>
            <template #title_product-cell="{ row }">
              <span class="font-semibold">{{ formatText(row.original.title_product) }}</span>
            </template>
            <template #qty_request-cell="{ row }">
              {{ row.original.qty_request }}
            </template>
            <template #qty_dispatched_request-cell="{ row }">
              <span :class="row.original.qty_dispatched_request > 0 ? 'text-indigo-600 font-bold' : 'text-slate-500'">
                {{ row.original.qty_dispatched_request || '0' }}
              </span>
            </template>
            <template #status_request-cell="{ row }">
              <UBadge :color="getStatusColor(row.original.status_request)" variant="soft">
                {{ getStatusLabel(row.original.status_request) }}
              </UBadge>
            </template>
            <template #notes_request-cell="{ row }">
              <span class="text-sm text-slate-500" :title="row.original.notes_request">
                {{ row.original.notes_request ? (row.original.notes_request.length > 20 ? row.original.notes_request.substring(0,20)+'...' : row.original.notes_request) : '-' }}
              </span>
            </template>
          </UTable>
        </UCard>
      </div>
    </div>

    <!-- Layout Traspasos -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <div class="lg:col-span-5">
        <UCard class="border-t-4 border-t-emerald-500">
          <template #header>
            <h3 class="font-semibold text-lg flex items-center gap-2">
              <UIcon name="i-lucide-truck" class="w-5 h-5 text-emerald-600" />
              Ejecutar Traspaso Directo
            </h3>
            <p class="text-xs text-slate-500 mt-1">Mueve stock inmediatamente de una sucursal a otra.</p>
          </template>

          <form @submit.prevent="submitTransfer" class="space-y-4">
            <div class="flex items-center gap-3">
              <UFormGroup label="Sucursal Origen *" class="flex-1">
                <USelect
                  v-model="transferForm.originOfficeId"
                  :items="offices.map(o => ({ value: String(o.id_office), label: formatText(o.title_office) }))"
                  placeholder="Origen"
                  :loading="loadingOffices"
                  required
                />
              </UFormGroup>
              <UIcon name="i-lucide-arrow-right" class="w-6 h-6 text-slate-400 mt-6 shrink-0" />
              <UFormGroup label="Sucursal Destino *" class="flex-1">
                <USelect
                  v-model="transferForm.destOfficeId"
                  :items="offices.map(o => ({ value: String(o.id_office), label: formatText(o.title_office) }))"
                  placeholder="Destino"
                  :loading="loadingOffices"
                  required
                />
              </UFormGroup>
            </div>

            <UFormGroup label="Producto a transferir *">
              <USelect
                v-model="transferForm.productId"
                :items="transferProducts.map(p => ({ value: String(p.id_product), label: `${formatText(p.title_product)} (Stock disp: ${p.stock})` }))"
                placeholder="-- Seleccionar producto --"
                :disabled="!transferForm.originOfficeId"
                :loading="loadingTransferProducts"
                required
              />
            </UFormGroup>

            <UFormGroup label="Cantidad a transferir *">
              <UInput
                v-model.number="transferForm.qty"
                type="number"
                min="1"
                :max="maxTransferStock"
                required
              />
              <p v-if="selectedTransferProductData" class="text-xs text-slate-500 mt-1">Stock máximo en origen: {{ maxTransferStock }}</p>
            </UFormGroup>

            <UFormGroup label="Notas o Referencia (opcional)">
              <UTextarea
                v-model="transferForm.notes"
                placeholder="Motivo del traspaso..."
                :rows="2"
              />
            </UFormGroup>

            <UButton
              type="submit"
              color="primary"
              class="w-full justify-center mt-4 bg-emerald-600 hover:bg-emerald-700"
              icon="i-lucide-check-circle"
              :loading="submittingTransfer"
            >
              Confirmar Traspaso
            </UButton>
          </form>
        </UCard>
      </div>

      <div class="lg:col-span-7">
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 p-6 rounded-xl flex flex-col items-center justify-center text-center h-full">
          <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-800 text-emerald-600 dark:text-emerald-300 rounded-full flex items-center justify-center mb-4">
            <UIcon name="i-lucide-shield-check" class="w-8 h-8" />
          </div>
          <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Traspaso Inmediato</h4>
          <p class="text-sm text-slate-600 dark:text-slate-400 max-w-sm">
            Los traspasos directos entre sucursales se aplican instantáneamente en el inventario. Asegúrate de que la mercadería física se encuentre en tránsito antes de confirmar esta acción en el sistema.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
