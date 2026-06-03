<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{
  moduleName: string
}>()

const auth = useAuthStore()

// Module mapping
const MODULE_MAPPING: Record<string, { id_module: number, title_module: string, suffix_module: string, title: string, editable_module: number }> = {
  admins: { id_module: 2, title_module: 'admins', suffix_module: 'admin', title: 'Administradores', editable_module: 0 },
  sucursales: { id_module: 4, title_module: 'offices', suffix_module: 'office', title: 'Sucursales', editable_module: 1 },
  clientes: { id_module: 6, title_module: 'clients', suffix_module: 'client', title: 'Clientes', editable_module: 1 },
  categorias: { id_module: 8, title_module: 'categories', suffix_module: 'category', title: 'Categorías', editable_module: 1 },
  productos: { id_module: 10, title_module: 'products', suffix_module: 'product', title: 'Productos', editable_module: 1 },
  compras: { id_module: 41, title_module: 'purchases', suffix_module: 'purchase', title: 'Compras', editable_module: 1 },
  ordenes: { id_module: 14, title_module: 'orders', suffix_module: 'order', title: 'Órdenes', editable_module: 0 },
  ventas: { id_module: 16, title_module: 'sales', suffix_module: 'sale', title: 'Ventas', editable_module: 0 },
  caja: { id_module: 18, title_module: 'cashs', suffix_module: 'cash', title: 'Caja', editable_module: 1 },
  gastos: { id_module: 20, title_module: 'bills', suffix_module: 'bill', title: 'Gastos', editable_module: 1 },
  proveedores: { id_module: 40, title_module: 'suppliers', suffix_module: 'supplier', title: 'Proveedores', editable_module: 1 },
  almacenes: { id_module: 42, title_module: 'warehouses', suffix_module: 'warehouse', title: 'Almacenes', editable_module: 1 }
}

const moduleConfig = computed(() => MODULE_MAPPING[props.moduleName])

// State
const columns = ref<any[]>([])
const rows = ref<any[]>([])
const loading = ref(true)
const search = ref('')
const page = ref(1)
const totalItems = ref(0)
const itemsPerPage = 10

// Slideover state for edit/create
const isSlideoverOpen = ref(false)
const selectedItem = ref<any>(null)

// Relational Cache
const relationsCache = ref<Record<string, any[]>>({})

const apiHeaders = {
  Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
}

// Fetch Columns Metadata
async function fetchMetadata() {
  if (!moduleConfig.value) return
  try {
    const data = await $fetch<any>(`/api/columns?linkTo=id_module_column&equalTo=${moduleConfig.value.id_module}`, {
      headers: apiHeaders
    })
    if (data.status === 200) {
      columns.value = data.results
      // Fetch relational caches
      for (const col of columns.value) {
        if (col.type_column === 'relations' && col.matrix_column) {
          await fetchRelationCache(col.matrix_column)
        }
      }
    }
  } catch (e) {
    console.error('Error fetching table metadata:', e)
  }
}

// Fetch Relation Cache in bulk
async function fetchRelationCache(matrixTable: string) {
  if (relationsCache.value[matrixTable]) return
  try {
    const data = await $fetch<any>(`/api/${matrixTable}`, {
      headers: apiHeaders
    })
    if (data.status === 200) {
      relationsCache.value[matrixTable] = data.results || []
    }
  } catch (e) {
    console.error(`Error loading relation cache for ${matrixTable}:`, e)
    relationsCache.value[matrixTable] = []
  }
}

// Map Relation ID to Display Name
function getRelationLabel(matrixTable: string, id: any) {
  const tableData = relationsCache.value[matrixTable]
  if (!tableData || !tableData.length) return id
  const match = tableData.find((r: any) => {
    const firstKey = Object.keys(r)[0]
    return String(r[firstKey]) === String(id)
  })
  if (!match) return id
  const secondKey = Object.keys(match)[1]
  return decodeURIComponent(match[secondKey] || '').replace(/\+/g, ' ')
}

// Fetch Rows Data
async function fetchRows() {
  if (!moduleConfig.value) return
  // Evitar cargar datos sin filtrar antes de que la sesión del usuario esté cargada en el cliente
  if (import.meta.client && !auth.user) return
  loading.value = true
  try {
    const config = moduleConfig.value
    const idKey = `id_${config.suffix_module}`

    // Base URL
    let url = `/api/${config.title_module}`
    const params: Record<string, any> = {
      orderBy: idKey,
      orderMode: 'DESC'
    }

    // Apply office/warehouse filter if needed
    const hasOfficeCol = columns.value.some(c => c.title_column === `id_office_${config.suffix_module}`)
    if (hasOfficeCol && config.title_module !== 'clients') {
      let equalToVal: any = auth.officeId
      let shouldFilter = auth.officeId && auth.officeId > 0

      if (config.title_module === 'purchases' && auth.role !== 'superadmin') {
        shouldFilter = true
        if (auth.role === 'despachador') {
          equalToVal = auth.warehouseId
        } else if (auth.officeId) {
          // Fetch corresponding warehouse for office
          try {
            const whData = await $fetch<any>(`/api/warehouses?linkTo=id_office_warehouse&equalTo=${auth.officeId}`, {
              headers: apiHeaders
            })
            if (whData.status === 200 && whData.results && whData.results.length > 0) {
              equalToVal = whData.results[0].id_warehouse
            } else {
              equalToVal = 0
            }
          } catch (e) {
            console.error('Error fetching warehouse for purchases filter:', e)
            equalToVal = 0
          }
        } else {
          equalToVal = 0
        }
      }

      if (shouldFilter && equalToVal && equalToVal > 0) {
        params.linkTo = `id_office_${config.suffix_module}`
        params.equalTo = equalToVal
      }
    }

    // Handle search query
    if (search.value) {
      params.linkTo = columns.value.find(c => c.type_column === 'text')?.title_column || idKey
      params.search = search.value
    }

    console.log('[fetchRows]', {
      url,
      params,
      role: auth.role,
      officeId: auth.officeId,
      warehouseId: auth.warehouseId,
      columnsCount: columns.value.length
    })

    const data = await $fetch<any>(url, {
      headers: apiHeaders,
      query: params
    })

    if (data.status === 200) {
      rows.value = data.results || []
      totalItems.value = rows.value.length
    } else {
      rows.value = []
      totalItems.value = 0
    }
  } catch (e) {
    console.error('Error fetching rows:', e)
    rows.value = []
    totalItems.value = 0
  } finally {
    loading.value = false
  }
}

// Check if we show edit/delete actions
const showActions = computed(() => {
  if (!moduleConfig.value) return false
  const config = moduleConfig.value
  const isProducts = config.title_module === 'products'
  const isSuperOrAdmin = auth.role === 'superadmin' || auth.role === 'admin'
  return (!isProducts && (isSuperOrAdmin || config.editable_module === 1)) || (isProducts && isSuperOrAdmin)
})


// Paginated Rows
const paginatedRows = computed(() => {
  const start = (page.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return rows.value.slice(start, end)
})

// Lifecycle
onMounted(async () => {
  await fetchMetadata()
  await fetchRows()
})

watch(() => props.moduleName, async () => {
  page.value = 1
  search.value = ''
  await fetchMetadata()
  await fetchRows()
})

watch(search, () => {
  page.value = 1
  fetchRows()
})

watch(() => auth.user, async (newVal, oldVal) => {
  if (newVal !== oldVal) {
    await fetchRows()
  }
})

// Actions
function openCreate() {
  selectedItem.value = null
  isSlideoverOpen.value = true
}

function openEdit(item: any) {
  selectedItem.value = item
  isSlideoverOpen.value = true
}

async function handleDelete(item: any) {
  if (!moduleConfig.value) return
  const idKey = `id_${moduleConfig.value.suffix_module}`
  const idValue = item[idKey]

  if (!confirm('¿Estás seguro de que deseas eliminar este registro?')) return

  try {
    const res = await $fetch<any>(`/api/${moduleConfig.value.title_module}`, {
      method: 'DELETE',
      headers: apiHeaders,
      query: {
        id: idValue,
        nameId: idKey,
        token: 'no',
        except: idKey
      }
    })

    if (res.status === 200) {
      // Refresh
      await fetchRows()
    } else {
      alert(`Error al eliminar: ${res.results || 'Intenta de nuevo'}`)
    }
  } catch (e) {
    console.error('Error deleting item:', e)
    alert('Error de red al intentar eliminar el registro.')
  }
}

function onFormSaved() {
  isSlideoverOpen.value = false
  fetchRows()
}

// Order PDF Logic
const isReceiptModalOpen = ref(false)
const selectedOrderId = ref<number | string | null>(null)

function openReceipt(id: string | number) {
  selectedOrderId.value = id
  isReceiptModalOpen.value = true
}

// Cash Details Logic
const isCashDetailsModalOpen = ref(false)
const selectedCash = ref<any>(null)

function openCashDetails(cashRow: any) {
  selectedCash.value = cashRow
  isCashDetailsModalOpen.value = true
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Controls -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900/60 backdrop-blur border border-slate-800 p-4 rounded-xl">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight capitalize bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
          {{ moduleConfig?.title || 'Administración' }}
        </h1>
        <p class="text-xs text-slate-400 mt-1">
          Gestiona los registros de este módulo de forma dinámica y segura.
        </p>
      </div>

      <div class="flex items-center gap-3 w-full sm:w-auto">
        <UInput
          v-model="search"
          icon="i-lucide-search"
          placeholder="Buscar..."
          class="w-full sm:w-64"
        />
        <UButton
          v-slot:default
          v-if="auth.role === 'superadmin' || auth.role === 'admin' || moduleConfig?.editable_module === 1"
          icon="i-lucide-plus"
          color="primary"
          @click="openCreate"
        >
          Agregar
        </UButton>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
      <div v-if="loading" class="flex justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-indigo-500" />
      </div>
      <div v-else-if="rows.length === 0" class="text-center py-12 text-slate-500 text-sm">
        No se encontraron registros.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm text-slate-300">
          <thead>
            <tr class="bg-slate-950 text-slate-400 border-b border-slate-800">
              <th class="p-4">#</th>
              <th v-for="col in columns.filter(c => c.visible_column === 1)" :key="col.title_column" class="p-4">
                {{ col.alias_column || col.title_column }}
              </th>
              <th v-if="showActions" class="p-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in paginatedRows" :key="row[Object.keys(row)[0]]" class="border-b border-slate-850 hover:bg-slate-900/20">
              <td class="p-4 text-xs text-slate-500 font-mono">
                {{ (page - 1) * itemsPerPage + idx + 1 }}
              </td>
              <td v-for="col in columns.filter(c => c.visible_column === 1)" :key="col.title_column" class="p-4">
                <!-- Image -->
                <div v-if="col.type_column === 'image'" class="flex items-center">
                  <UAvatar
                    :src="row[col.title_column] ? decodeURIComponent(row[col.title_column]).replace(/\+/g, ' ') : '/views/assets/img/multimedia.png'"
                    size="lg"
                    class="border border-slate-700 bg-slate-800"
                  />
                </div>

                <!-- Boolean -->
                <span v-else-if="col.type_column === 'boolean'">
                  <UBadge
                    :color="row[col.title_column] == 1 ? 'emerald' : 'rose'"
                    variant="subtle"
                    class="capitalize"
                  >
                    {{ row[col.title_column] == 1 ? 'ON' : 'OFF' }}
                  </UBadge>
                </span>

                <!-- Money -->
                <span v-else-if="col.type_column === 'money'" class="font-semibold text-teal-400 font-mono">
                  Bs. {{ parseFloat(row[col.title_column] || 0).toFixed(2) }}
                </span>

                <!-- Relation -->
                <span v-else-if="col.type_column === 'relations'">
                  <UBadge color="indigo" variant="outline">
                    {{ getRelationLabel(col.matrix_column, row[col.title_column]) }}
                  </UBadge>
                </span>

                <!-- Select -->
                <span v-else-if="col.type_column === 'select'">
                  <UBadge color="neutral" variant="solid" class="capitalize">
                    {{ row[col.title_column] }}
                  </UBadge>
                </span>

                <!-- Text/Default -->
                <span v-else class="text-sm truncate max-w-xs block">
                  {{ row[col.title_column] !== null ? decodeURIComponent(String(row[col.title_column])).replace(/\+/g, ' ') : '-' }}
                </span>
              </td>
              
              <!-- Actions -->
              <td v-if="showActions || moduleConfig?.title_module === 'orders' || moduleConfig?.title_module === 'cashs'" class="p-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <!-- Custom Print Order Action -->
                  <UButton
                    v-if="moduleConfig?.title_module === 'orders'"
                    icon="i-lucide-printer"
                    color="primary"
                    variant="soft"
                    size="xs"
                    @click="openReceipt(row[`id_${moduleConfig.suffix_module}`])"
                    title="Imprimir Comprobante"
                  />
                  <!-- Custom View Cash Details Action -->
                  <UButton
                    v-if="moduleConfig?.title_module === 'cashs'"
                    icon="i-lucide-receipt"
                    color="primary"
                    variant="soft"
                    size="xs"
                    @click="openCashDetails(row)"
                    title="Ver Detalles de Caja"
                  />
                  <template v-if="showActions">
                    <UButton
                      icon="i-lucide-edit"
                      color="neutral"
                      variant="ghost"
                      size="xs"
                      @click="openEdit(row)"
                    />
                    <UButton
                      icon="i-lucide-trash"
                      color="rose"
                      variant="ghost"
                      size="xs"
                      @click="handleDelete(row)"
                    />
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalItems > itemsPerPage" class="p-4 border-t border-slate-800 flex justify-between items-center bg-slate-950/40">
        <span class="text-xs text-slate-400">
          Mostrando {{ (page - 1) * itemsPerPage + 1 }} a {{ Math.min(page * itemsPerPage, totalItems) }} de {{ totalItems }} registros
        </span>
        <UPagination
          v-model="page"
          :total="totalItems"
          :page-count="itemsPerPage"
        />
      </div>
    </div>

    <!-- Form Slideover -->
    <USlideover
      v-model:open="isSlideoverOpen"
      :title="selectedItem ? 'Editar Registro' : 'Nuevo Registro'"
      class="z-50"
    >
      <template #body>
        <DynamicForm
          :module-name="props.moduleName"
          :initial-data="selectedItem"
          @saved="onFormSaved"
          @cancel="isSlideoverOpen = false"
        />
      </template>
    </USlideover>

    <!-- Receipt Modal for Orders -->
    <OrderReceiptModal 
      v-if="moduleConfig?.title_module === 'orders'"
      v-model:isOpen="isReceiptModalOpen"
      :order-id="selectedOrderId"
      @close="selectedOrderId = null"
    />

    <!-- Cash Details Modal -->
    <CashDetailsModal
      v-if="moduleConfig?.title_module === 'cashs'"
      v-model:isOpen="isCashDetailsModalOpen"
      :cash="selectedCash"
      @close="selectedCash = null"
    />
  </div>
</template>
