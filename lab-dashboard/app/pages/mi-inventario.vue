<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

const hasSubWarehouse = ref(false)
const inventory = ref<any[]>([])
const movements = ref<any[]>([])

const loadingInventory = ref(true)
const loadingMovements = ref(true)

const apiHeaders = {
  Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
}

const isCaja = computed(() => auth.role === 'cajero' || auth.role === 'caja')

async function checkHasSubWarehouse() {
  if (auth.role === 'vendedor' || isCaja.value) {
    hasSubWarehouse.value = true
    return
  }
  
  try {
    const data = await $fetch<any>(`/api/sub_warehouses?linkTo=id_office_sub_warehouse&equalTo=${auth.officeId}`, {
      headers: apiHeaders
    })
    hasSubWarehouse.value = data.status === 200 && data.results && data.results.length > 0
  } catch (e) {
    hasSubWarehouse.value = false
  }
}

async function fetchInventory() {
  loadingInventory.value = true
  try {
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getSubWarehouseStock: 'true',
        id_admin: String(auth.user?.id_admin || 1),
        id_office: String(auth.officeId || 3),
        role: auth.role || 'cajero'
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    inventory.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Error fetching inventory:', e)
    inventory.value = []
  } finally {
    loadingInventory.value = false
  }
}

async function fetchMovements() {
  if (!hasSubWarehouse.value) return
  loadingMovements.value = true
  try {
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getMyWarehouseMovements: 'true',
        id_admin: String(auth.user?.id_admin || 1),
        id_office: String(auth.officeId || 3)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    movements.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Error fetching movements:', e)
    movements.value = []
  } finally {
    loadingMovements.value = false
  }
}

onMounted(async () => {
  await checkHasSubWarehouse()
  await fetchInventory()
  await fetchMovements()
})

const invColumns: any[] = [
  { accessorKey: 'title_product', header: 'Producto' },
  { accessorKey: 'sku_product', header: 'SKU' },
  { accessorKey: 'unit_product', header: 'Unidad' },
  { accessorKey: 'stock', header: 'Cantidad Disponible' },
  { accessorKey: 'status', header: 'Estado' }
]

const moveColumns: any[] = [
  { accessorKey: 'date_created_assignment', header: 'Fecha' },
  { accessorKey: 'type', header: 'Tipo' },
  { accessorKey: 'title_product', header: 'Producto' },
  { accessorKey: 'qty_assignment', header: 'Cantidad' },
  { accessorKey: 'notes_assignment', header: 'Notas' }
]

function formatText(t: string | undefined): string {
  if (!t) return ''
  return decodeURIComponent(t).replace(/\+/g, ' ')
}

function getTypeColor(type: string): string {
  if (type === 'despacho') return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
  if (type === 'venta') return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
  return 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300'
}

function getTypeLabel(type: string): string {
  if (type === 'despacho') return 'Recibido'
  if (type === 'venta') return 'Venta'
  return 'Devolución'
}
</script>

<template>
  <div class="h-full flex flex-col p-6 space-y-6 overflow-y-auto w-full">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
          <UIcon name="i-lucide-box" class="w-6 h-6 text-green-600" />
          {{ !hasSubWarehouse ? 'Inventario Almacén' : 'Mi Inventario' }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ !hasSubWarehouse ? 'Consulta de stock general de sucursal' : 'Tus productos asignados' }}
        </p>
      </div>
      <UButton
        v-if="hasSubWarehouse && !isCaja"
        to="/solicitar-inventario"
        icon="i-lucide-plus-circle"
        color="primary"
        class="bg-green-600 hover:bg-green-700"
      >
        Solicitar Inventario
      </UButton>
    </div>

    <!-- Inventory Table -->
    <UCard>
      <template #header>
        <h3 class="font-semibold text-lg flex items-center gap-2">
          <UIcon name="i-lucide-boxes" class="w-5 h-5 text-gray-500" />
          {{ !hasSubWarehouse ? 'Productos en Almacén' : 'Productos en mi Sub-Almacén' }}
        </h3>
      </template>

      <div v-if="loadingInventory" class="py-8 flex justify-center">
        <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-green-600" />
      </div>
      
      <div v-else-if="inventory.length === 0" class="py-12 text-center flex flex-col items-center">
        <UIcon name="i-lucide-box-select" class="w-12 h-12 text-gray-300 mb-3" />
        <p class="text-gray-500 font-medium">No tienes productos asignados.</p>
        <UButton v-if="hasSubWarehouse && !isCaja" to="/solicitar-inventario" color="neutral" variant="soft" class="mt-4">
          Ir a solicitar inventario
        </UButton>
      </div>

      <UTable v-else :columns="invColumns" :data="inventory">
        <template #title_product-cell="{ row }">
          <span class="font-medium">{{ formatText(row.original.title_product) }}</span>
        </template>
        <template #sku_product-cell="{ row }">
          <UBadge color="neutral" variant="soft">{{ row.original.sku_product || '-' }}</UBadge>
        </template>
        <template #unit_product-cell="{ row }">
          {{ row.original.unit_product || '-' }}
        </template>
        <template #stock-cell="{ row }">
          <span :class="[
            'font-bold px-2 py-1 rounded text-sm',
            parseFloat(row.original.stock) > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
          ]">
            {{ row.original.stock }}
          </span>
        </template>
        <template #status-cell="{ row }">
          <UBadge :color="parseFloat(row.original.stock) > 0 ? 'success' : 'error'">
            {{ parseFloat(row.original.stock) > 0 ? 'Disponible' : 'Agotado' }}
          </UBadge>
        </template>
      </UTable>
    </UCard>

    <!-- Movements Table -->
    <UCard v-if="hasSubWarehouse">
      <template #header>
        <h3 class="font-semibold text-lg flex items-center gap-2">
          <UIcon name="i-lucide-history" class="w-5 h-5 text-gray-500" />
          Últimos Movimientos
        </h3>
      </template>

      <div v-if="loadingMovements" class="py-8 flex justify-center">
        <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-green-600" />
      </div>

      <div v-else-if="movements.length === 0" class="py-8 text-center text-gray-500">
        Sin movimientos registrados.
      </div>

      <UTable v-else :columns="moveColumns" :data="movements">
        <template #title_product-cell="{ row }">
          <strong>{{ formatText(row.original.title_product) }}</strong>
        </template>
        <template #type-cell="{ row }">
          <span :class="['px-2 py-1 text-xs font-semibold rounded', getTypeColor(row.original.type_assignment)]">
            {{ getTypeLabel(row.original.type_assignment) }}
          </span>
        </template>
        <template #notes_assignment-cell="{ row }">
          <span class="text-sm text-gray-500">{{ row.original.notes_assignment || '-' }}</span>
        </template>
      </UTable>
    </UCard>
  </div>
</template>
