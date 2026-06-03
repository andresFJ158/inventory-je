<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

// State for Warehouses and Products
const warehouses = ref<any[]>([])
const products = ref<any[]>([])
const myRequests = ref<any[]>([])

const loadingWarehouses = ref(true)
const loadingProducts = ref(false)
const loadingRequests = ref(true)
const submitting = ref(false)

// Form state
const form = ref({
  warehouseId: '',
  productId: '',
  qty: 1,
  notes: ''
})

const apiHeaders = {
  Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
}

const toast = useToast()

// Fetch Warehouses
async function fetchWarehouses() {
  loadingWarehouses.value = true
  try {
    const data = await $fetch<any>('/api/warehouses?orderBy=id_warehouse&orderMode=DESC', {
      headers: apiHeaders
    })
    warehouses.value = data.status === 200 && data.results ? data.results : []
  } catch (e) {
    console.error('Error fetching warehouses:', e)
    warehouses.value = []
  } finally {
    loadingWarehouses.value = false
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
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getWarehouseProducts: 'true',
        id_warehouse: warehouseId
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    products.value = Array.isArray(data) ? data : []
    
    if (products.value.length === 0) {
      toast.add({
        title: 'Atención',
        description: 'Este almacén no tiene productos con stock disponible.',
        color: 'amber'
      })
    }
  } catch (e) {
    console.error('Error fetching warehouse products:', e)
    products.value = []
  } finally {
    loadingProducts.value = false
  }
}

// Watch warehouse selection to fetch products
function onWarehouseChange() {
  form.value.productId = ''
  form.value.qty = 1
  fetchWarehouseProducts(form.value.warehouseId)
}

const selectedProductData = computed(() => {
  if (!form.value.productId) return null
  return products.value.find(p => String(p.id_product) === String(form.value.productId)) || null
})

const maxStock = computed(() => {
  return selectedProductData.value ? parseFloat(selectedProductData.value.stock) : 0
})

// Fetch My Requests
async function fetchMyRequests() {
  loadingRequests.value = true
  try {
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getMyRequests: 'true',
        id_admin: String(auth.user?.id_admin || 1)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    myRequests.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Error fetching my requests:', e)
    myRequests.value = []
  } finally {
    loadingRequests.value = false
  }
}

// Submit Request
async function submitRequest() {
  if (!form.value.warehouseId) return toast.add({ title: 'Error', description: 'Selecciona un almacén', color: 'red' })
  if (!form.value.productId) return toast.add({ title: 'Error', description: 'Selecciona un producto', color: 'red' })
  if (!form.value.qty || form.value.qty <= 0) return toast.add({ title: 'Error', description: 'Ingresa una cantidad válida', color: 'red' })
  if (form.value.qty > maxStock.value) return toast.add({ title: 'Error', description: `La cantidad supera el stock disponible (${maxStock.value})`, color: 'red' })

  submitting.value = true
  try {
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        createInventoryRequest: 'true',
        id_product: form.value.productId,
        qty: String(form.value.qty),
        notes: form.value.notes,
        id_admin: String(auth.user?.id_admin || 1),
        id_office: String(auth.officeId || 3),
        id_warehouse: form.value.warehouseId
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    
    if (typeof response === 'string' && response.trim() === 'ok') {
      toast.add({ title: 'Éxito', description: 'Solicitud enviada correctamente', color: 'green' })
      // Reset form
      form.value.warehouseId = ''
      form.value.productId = ''
      form.value.qty = 1
      form.value.notes = ''
      products.value = []
      
      // Refresh requests
      await fetchMyRequests()
    } else {
      toast.add({ title: 'Error', description: response || 'No se pudo enviar la solicitud', color: 'red' })
    }
  } catch (e) {
    console.error('Error submitting request:', e)
    toast.add({ title: 'Error', description: 'Ocurrió un error inesperado.', color: 'red' })
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchWarehouses()
  fetchMyRequests()
})

const columns = [
  { accessorKey: 'date_created_request', header: 'Fecha' },
  { accessorKey: 'title_warehouse', header: 'Almacén' },
  { accessorKey: 'title_product', header: 'Producto' },
  { accessorKey: 'qty_request', header: 'Solicitado' },
  { accessorKey: 'qty_dispatched', header: 'Despachado' },
  { accessorKey: 'status_request', header: 'Estado' },
  { accessorKey: 'notes_request', header: 'Notas' }
]

function formatText(t: string | undefined): string {
  if (!t) return ''
  return decodeURIComponent(t).replace(/\+/g, ' ')
}

function getStatusColor(status: string) {
  switch (status) {
    case 'pendiente': return 'amber'
    case 'despachada': return 'green'
    case 'rechazada': return 'red'
    default: return 'gray'
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
    <div class="mb-6">
      <h1 class="text-2xl font-bold flex items-center gap-2">
        <UIcon name="i-lucide-clipboard-list" class="w-6 h-6 text-green-600" />
        Solicitar Inventario
      </h1>
      <p class="text-sm text-slate-500 mt-1">
        Crea solicitudes de mercadería al almacén central o centros de distribución.
      </p>
    </div>

    <!-- Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Formulario -->
      <div class="lg:col-span-5">
        <UCard>
          <template #header>
            <h3 class="font-semibold text-lg flex items-center gap-2">
              <UIcon name="i-lucide-plus-circle" class="w-5 h-5 text-gray-500" />
              Nueva Solicitud
            </h3>
          </template>

          <form @submit.prevent="submitRequest" class="space-y-4">
            <UFormGroup label="Almacén *">
              <USelect
                v-model="form.warehouseId"
                :options="warehouses.map(w => ({ value: String(w.id_warehouse), label: formatText(w.title_warehouse) }))"
                placeholder="-- Seleccionar almacén --"
                :loading="loadingWarehouses"
                @update:model-value="onWarehouseChange"
                required
              />
            </UFormGroup>

            <UFormGroup label="Producto *">
              <USelect
                v-model="form.productId"
                :options="products.map(p => ({ value: String(p.id_product), label: `${formatText(p.title_product)} (Stock: ${p.stock})` }))"
                placeholder="-- Seleccionar producto --"
                :disabled="!form.warehouseId"
                :loading="loadingProducts"
                required
              />
            </UFormGroup>

            <UFormGroup label="Cantidad *">
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
                rows="2"
              />
            </UFormGroup>

            <UButton
              type="submit"
              color="primary"
              class="w-full justify-center mt-4 bg-green-600 hover:bg-green-700"
              icon="i-lucide-paper-plane"
              :loading="submitting"
            >
              Enviar Solicitud
            </UButton>
          </form>
        </UCard>
      </div>

      <!-- Mis Solicitudes -->
      <div class="lg:col-span-7">
        <UCard>
          <template #header>
            <h3 class="font-semibold text-lg flex items-center gap-2">
              <UIcon name="i-lucide-list" class="w-5 h-5 text-gray-500" />
              Mis Solicitudes
            </h3>
          </template>

          <div v-if="loadingRequests" class="py-8 flex justify-center text-green-600">
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
            <template #qty_dispatched-cell="{ row }">
              <span :class="row.original.qty_dispatched > 0 ? 'text-green-600 font-bold' : 'text-slate-500'">
                {{ row.original.qty_dispatched || '0' }}
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
  </div>
</template>
