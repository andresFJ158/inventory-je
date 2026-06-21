<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{
  moduleName: string
}>()

const auth = useAuthStore()
const toast = useToast()

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
  caja: { id_module: 18, title_module: 'cashs', suffix_module: 'cash', title: 'Caja', editable_module: 0 },
  gastos: { id_module: 20, title_module: 'bills', suffix_module: 'bill', title: 'Gastos', editable_module: 1 },
  proveedores: { id_module: 40, title_module: 'suppliers', suffix_module: 'supplier', title: 'Proveedores', editable_module: 1 },
  almacenes: { id_module: 44, title_module: 'warehouses', suffix_module: 'warehouse', title: 'Almacenes', editable_module: 1 },
  qrs: { id_module: 99, title_module: 'qrs', suffix_module: 'qr', title: 'Códigos QR', editable_module: 1 }
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
    return firstKey ? String(r[firstKey]) === String(id) : false
  })
  if (!match) return id

  // Try to find a 'name_' or 'title_' key first
  const keys = Object.keys(match)
  let labelKey = keys.find(k => k.startsWith('name_') || k.startsWith('title_') || k.startsWith('email_'))
  
  if (!labelKey) {
     labelKey = keys[1] // fallback to second key
  }
  
  // Combine name and surname if both exist
  if (match.name_client && match.surname_client) {
    return decodeURIComponent(match.name_client + ' ' + match.surname_client).replace(/\+/g, ' ')
  }
  
  return labelKey ? decodeURIComponent(match[labelKey] || '').replace(/\+/g, ' ') : id
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
      orderMode: 'DESC',
      token: auth.token
    }

    // Apply office/warehouse filter if needed
    const hasOfficeCol = columns.value.some(c => c.title_column === `id_office_${config.suffix_module}`)
    if (hasOfficeCol && config.title_module !== 'clients' && config.title_module !== 'products') {
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
      let fetchedRows = data.results || []

      // Aplicar filtros adicionales de privacidad por rol
      if (config.title_module === 'orders') {
        if (auth.role === 'vendedor' || auth.role === 'cajero') {
           // Solo ver sus propias órdenes
           fetchedRows = fetchedRows.filter((r: any) => String(r.id_admin_order) === String(auth.user?.id_admin))
        } else if (auth.role === 'despachador') {
           // Solo ver órdenes de vendedores que pertenecen al mismo almacén del despachador
           const admins = relationsCache.value['admins'] || []
           const allowedAdmins = admins.filter((a: any) => String(a.id_warehouse_admin) === String(auth.warehouseId)).map((a: any) => String(a.id_admin))
           fetchedRows = fetchedRows.filter((r: any) => allowedAdmins.includes(String(r.id_admin_order)))
        }
      } else if (config.title_module === 'clients') {
        if (auth.role === 'vendedor') {
           // Solo ver sus propios clientes
           fetchedRows = fetchedRows.filter((r: any) => String(r.id_admin_client) === String(auth.user?.id_admin))
        } else if (auth.role === 'cajero') {
           // Solo ver clientes globales (sin vendedor asignado)
           fetchedRows = fetchedRows.filter((r: any) => !r.id_admin_client || String(r.id_admin_client) === '0')
        }
      }

      rows.value = fetchedRows
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

// Columnas a ocultar por módulo (los productos son globales, id_office_product no aplica)
const HIDDEN_COLUMNS: Record<string, string[]> = {
  products: ['id_office_product', 'origin_office_product']
}

// Columnas visibles (reutilizado en header, filas y skeletons)
const visibleColumns = computed(() => {
  const hidden = moduleConfig.value ? (HIDDEN_COLUMNS[moduleConfig.value.title_module] || []) : []
  return columns.value.filter((c: any) => c.visible_column === 1 && !hidden.includes(c.title_column))
})

// Check if we show edit/delete actions
const showActions = computed(() => {
  if (!moduleConfig.value) return false
  const config = moduleConfig.value
  const isSuperOrAdmin = auth.role === 'superadmin' || auth.role === 'admin'
  // If products, ALWAYS show actions so we can show the "View Branches" button
  if (config.title_module === 'products') return true
  
  return isSuperOrAdmin || config.editable_module === 1
})

const canEditOrDelete = computed(() => {
  if (!moduleConfig.value) return false
  const isSuperOrAdmin = auth.role === 'superadmin' || auth.role === 'admin'
  const isLabAdminCatalog = auth.role === 'lab_admin' && ['products', 'purchases', 'suppliers', 'categories'].includes(moduleConfig.value.title_module)
  if (moduleConfig.value.title_module === 'products') return isSuperOrAdmin || isLabAdminCatalog
  if (isLabAdminCatalog) return true
  return isSuperOrAdmin || moduleConfig.value.editable_module === 1
})

const canCreateRecord = computed(() => {
  if (!moduleConfig.value) return false
  
  const isSuperOrAdmin = auth.role === 'superadmin' || auth.role === 'admin'

  if (moduleConfig.value.title_module === 'products') {
    return auth.role === 'lab_admin' || isSuperOrAdmin
  }
  
  if (isSuperOrAdmin) return true
  if (auth.role === 'lab_admin' && ['purchases', 'suppliers', 'categories'].includes(moduleConfig.value.title_module)) {
    return true
  }
  if (moduleConfig.value.title_module === 'purchases') return false
  return moduleConfig.value.editable_module === 1
})


// Money Column Color Logic
function getMoneyColorClass(colName: string, val: any) {
  const num = parseFloat(val || 0)
  if (num < 0) return 'text-rose-500' // Faltantes (gap_cash negativo) o deudas en rojo
  if (colName === 'bills_cash' || colName === 'cost_bill') {
    return num > 0 ? 'text-rose-500' : 'text-slate-500' // Gastos en rojo
  }
  if (colName === 'gap_cash' && num > 0) return 'text-green-500' // Sobrantes en verde
  if (colName === 'gap_cash' && num === 0) return 'text-slate-500' // Caja cuadrada en gris
  if (colName === 'money_cash' || colName === 'total_order' || colName === 'amount_income') {
    return num > 0 ? 'text-green-600' : 'text-slate-500' // Ventas/Ingresos en verde
  }
  return 'text-teal-600' // Default money color
}

// Fix dead domains in image URLs
function getImageUrl(imgStr: string) {
  if (!imgStr) return '/views/assets/img/multimedia.png'
  const decoded = decodeURIComponent(imgStr).replace(/\+/g, ' ')
  
  const viewsIndex = decoded.indexOf('views/')
  if (viewsIndex !== -1) {
    return '/' + decoded.substring(viewsIndex)
  }

  if (decoded.startsWith('http') || decoded.startsWith('/')) {
    return decoded
  }
  return '/' + decoded
}

// Filtered Rows
const filteredRows = computed(() => {
  if (!search.value) return rows.value
  const query = search.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "")
  return rows.value.filter(item => {
    return Object.values(item).some(val => {
      if (val === null || val === undefined) return false
      return String(val).toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").includes(query)
    })
  })
})

// Paginated Rows
const paginatedRows = computed(() => {
  const start = (page.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredRows.value.slice(start, end)
})

watch(filteredRows, () => {
  totalItems.value = filteredRows.value.length
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
        token: auth.token,
        table: 'admins',
        suffix: 'admin',
        except: idKey
      }
    })

    if (res.status === 200) {
      // Refresh
      await fetchRows()
    } else {
      toast.add({ title: `Error al eliminar: ${res.results || 'Intenta de nuevo'}`, color: 'error' })
    }
  } catch (e) {
    console.error('Error deleting item:', e)
    toast.add({ title: 'Error de red al intentar eliminar el registro.', color: 'error' })
  }
}

function onFormSaved() {
  isSlideoverOpen.value = false
  fetchRows()
}

// Order PDF Logic
const isReceiptModalOpen = ref(false)
const selectedOrderId = ref<number | string | null>(null)

function exportToCSV() {
  if (filteredRows.value.length === 0) {
    toast.add({ title: 'No hay datos para exportar', color: 'error' })
    return
  }

  const visibleCols = columns.value.filter(c => c.visible_column === 1)
  const header = visibleCols.map(c => `"${(c.alias_column || c.title_column).replace(/"/g, '""')}"`).join(',')
  
  const csvRows = filteredRows.value.map((row: any) => {
    return visibleCols.map(col => {
      let cellData = row[col.title_column]
      if (col.type_column === 'money') {
        cellData = typeof cellData === 'number' ? cellData.toFixed(2) : cellData
      } else if (col.type_column === 'date' || col.type_column === 'timestamp') {
        cellData = String(cellData || '')
      } else if (col.type_column === 'relations') {
        cellData = getRelationLabel(col.matrix_column, cellData)
      } else {
        cellData = cellData !== null ? decodeURIComponent(String(cellData)).replace(/\+/g, ' ') : '-'
      }
      const safeData = String(cellData ?? '').replace(/"/g, '""')
      return `"${safeData}"`
    }).join(',')
  })

  const csvString = [header, ...csvRows].join('\n')
  const blob = new Blob(['\ufeff' + csvString], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `${moduleConfig.value?.title_module || 'export'}_${new Date().toISOString().split('T')[0]}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

function openReceipt(id: string | number) {
  selectedOrderId.value = id
  isReceiptModalOpen.value = true
}

// Order Details Modal Logic
const isOrderDetailsModalOpen = ref(false)
const selectedOrderIdForDetails = ref<number | string | null>(null)

function openOrderDetails(row: any) {
  selectedOrderIdForDetails.value = row[`id_${moduleConfig.value?.suffix_module}`]
  isOrderDetailsModalOpen.value = true
}

// Cash Details Logic
const isCashDetailsModalOpen = ref(false)
const selectedCash = ref<any>(null)

function openCashDetails(cashRow: any) {
  selectedCash.value = cashRow
  isCashDetailsModalOpen.value = true
}

// Branches Modal Logic
const isBranchesModalOpen = ref(false)
const selectedProduct = ref<any>(null)
const productBranches = ref<any[]>([])
const loadingBranches = ref(false)

async function openBranchesModal(row: any) {
  selectedProduct.value = row
  isBranchesModalOpen.value = true
  loadingBranches.value = true
  productBranches.value = []

  try {
    const res = await $fetch<any>(`/api/relations?rel=product_inventory,offices&type=inventory,office&linkTo=id_product_inventory&equalTo=${row.id_product}`, {
      headers: apiHeaders
    })
    if (res.status === 200 && res.results) {
      productBranches.value = res.results
        .filter((r: any) => parseFloat(r.stock_inventory || 0) > 0)
        .map((r: any) => ({
          warehouse_name: decodeURIComponent(r.title_office).replace(/\+/g, ' '),
          stock_inventory: parseFloat(r.stock_inventory || 0)
        }))
    }
  } catch (e) {
    console.error('Error fetching warehouses:', e)
    toast.add({ title: 'Error al cargar almacenes', color: 'error' })
  } finally {
    loadingBranches.value = false
  }
}
</script>

<template>
  <div class="flex flex-col flex-1 min-h-0 gap-6">
    <!-- Header Controls -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white border border-slate-200 p-4 rounded-xl shadow-sm shrink-0">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight capitalize">
          {{ moduleConfig?.title || 'Administración' }}
        </h1>
        <p class="text-xs text-slate-500 mt-1">
          <span v-if="!loading">{{ totalItems }} {{ totalItems === 1 ? 'registro' : 'registros' }}<span v-if="search"> · filtrando “{{ search }}”</span></span>
          <span v-else>Cargando registros…</span>
        </p>
      </div>

      <div class="flex items-center gap-3 w-full sm:w-auto">
        <UInput
          v-model="search"
          icon="i-lucide-search"
          placeholder="Buscar..."
          class="w-full sm:w-64"
        >
          <template v-if="search" #trailing>
            <UButton
              icon="i-lucide-x"
              color="neutral"
              variant="link"
              size="xs"
              aria-label="Limpiar búsqueda"
              @click="search = ''"
            />
          </template>
        </UInput>
        <UButton
          icon="i-lucide-file-spreadsheet"
          color="neutral"
          variant="outline"
          @click="exportToCSV"
        >
          <span class="hidden md:inline">Exportar CSV</span>
        </UButton>
        <UButton
          v-if="canCreateRecord"
          icon="i-lucide-plus"
          color="primary"
          @click="openCreate"
        >
          Agregar
        </UButton>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm flex-1 flex flex-col min-h-0">
      <!-- Skeleton de carga -->
      <div v-if="loading" class="overflow-x-auto flex-1 overflow-y-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <th class="p-4 font-semibold text-xs uppercase tracking-wider">#</th>
              <th v-for="col in visibleColumns" :key="col.title_column" class="p-4 font-semibold text-xs uppercase tracking-wider">
                {{ col.alias_column || col.title_column }}
              </th>
              <th v-if="showActions" class="p-4" />
            </tr>
          </thead>
          <tbody>
            <tr v-for="n in 8" :key="n" class="border-b border-slate-100">
              <td v-for="c in (visibleColumns.length + (showActions ? 2 : 1))" :key="c" class="p-4">
                <div class="h-3.5 rounded bg-slate-100 animate-pulse" :class="c === 1 ? 'w-6' : 'w-full max-w-[8rem]'" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Estado vacío -->
      <div v-else-if="rows.length === 0" class="flex-1 flex flex-col items-center justify-center text-center py-16 px-6">
        <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
          <UIcon :name="search ? 'i-lucide-search-x' : 'i-lucide-inbox'" class="w-7 h-7 text-slate-400" />
        </div>
        <p class="text-sm font-semibold text-slate-700">
          {{ search ? 'Sin coincidencias' : 'No hay registros todavía' }}
        </p>
        <p class="text-xs text-slate-500 mt-1 max-w-xs">
          {{ search ? 'Prueba con otro término de búsqueda o limpia el filtro.' : 'Cuando agregues registros, aparecerán aquí.' }}
        </p>
        <UButton
          v-if="search"
          class="mt-4"
          color="neutral"
          variant="outline"
          size="sm"
          icon="i-lucide-x"
          @click="search = ''"
        >
          Limpiar búsqueda
        </UButton>
        <UButton
          v-else-if="canCreateRecord"
          class="mt-4"
          color="primary"
          size="sm"
          icon="i-lucide-plus"
          @click="openCreate"
        >
          Agregar el primero
        </UButton>
      </div>

      <!-- Tabla con datos -->
      <div v-else class="overflow-x-auto flex-1 overflow-y-auto">
        <table class="w-full text-left border-collapse text-sm text-slate-700">
          <thead class="sticky top-0 z-10">
            <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <th class="p-4 font-semibold text-xs uppercase tracking-wider">#</th>
              <th v-for="col in visibleColumns" :key="col.title_column" class="p-4 font-semibold text-xs uppercase tracking-wider whitespace-nowrap">
                {{ col.alias_column || col.title_column }}
              </th>
              <th v-if="showActions" class="p-4 text-right font-semibold text-xs uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in paginatedRows" :key="idx" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
              <td class="p-4 text-xs text-slate-400 font-mono">
                {{ (page - 1) * itemsPerPage + idx + 1 }}
              </td>
              <td v-for="col in visibleColumns" :key="col.title_column" class="p-4">
                <!-- Image -->
                <div v-if="col.type_column === 'image'" class="flex items-center">
                  <UAvatar
                    :src="getImageUrl(row[col.title_column])"
                    size="lg"
                    class="border border-slate-200 bg-slate-100 ring-1 ring-slate-100"
                  />
                </div>

                <!-- Boolean -->
                <span v-else-if="col.type_column === 'boolean'">
                  <UBadge
                    :color="row[col.title_column] == 1 ? 'success' : 'error'"
                    variant="subtle"
                    class="capitalize"
                  >
                    {{ row[col.title_column] == 1 ? 'ON' : 'OFF' }}
                  </UBadge>
                </span>

                <!-- Money -->
                <span v-else-if="col.type_column === 'money'" class="font-semibold font-mono" :class="getMoneyColorClass(col.title_column, row[col.title_column])">
                  {{ col.title_column === 'gap_cash' && parseFloat(row[col.title_column] || 0) > 0 ? '+' : '' }}Bs. {{ parseFloat(row[col.title_column] || 0).toFixed(2) }}
                </span>

                <!-- Relation -->
                <span v-else-if="col.type_column === 'relations'">
                  <UBadge color="primary" variant="outline">
                    {{ getRelationLabel(col.matrix_column, row[col.title_column]) }}
                  </UBadge>
                </span>

                <!-- Select -->
                <span v-else-if="col.type_column === 'select'">
                  <UBadge color="neutral" variant="subtle" class="capitalize">
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
                  <!-- Custom View Order Details Action -->
                  <UButton
                    v-if="moduleConfig?.title_module === 'orders'"
                    icon="i-lucide-eye"
                    color="info"
                    variant="soft"
                    size="xs"
                    @click="openOrderDetails(row)"
                    title="Ver Detalles"
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
                  <UButton
                    v-if="moduleConfig?.title_module === 'products'"
                    icon="i-lucide-warehouse"
                    color="primary"
                    variant="soft"
                    size="xs"
                    @click="openBranchesModal(row)"
                    title="Ver Stock por Almacén"
                  />
                  <template v-if="canEditOrDelete">
                    <UButton
                      icon="i-lucide-edit"
                      color="neutral"
                      variant="ghost"
                      size="xs"
                      @click="openEdit(row)"
                    />
                    <UButton
                      icon="i-lucide-trash"
                      color="error"
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
      <div v-if="totalItems > itemsPerPage" class="p-4 border-t border-slate-200 flex justify-between items-center bg-slate-50/60 shrink-0">
        <span class="text-xs text-slate-500">
          Mostrando {{ (page - 1) * itemsPerPage + 1 }} a {{ Math.min(page * itemsPerPage, totalItems) }} de {{ totalItems }} registros
        </span>
        <UPagination
          v-model:page="page"
          :total="totalItems"
          :items-per-page="itemsPerPage"
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
      v-if="moduleConfig?.title_module === 'orders' && isReceiptModalOpen"
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

    <!-- Warehouse Stock Modal -->
    <UModal v-model:open="isBranchesModalOpen">
      <template #content>
        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="font-bold flex items-center gap-2">
                <UIcon name="i-lucide-warehouse" class="w-4 h-4 text-indigo-500" />
                Stock por Almacén
              </h3>
              <UButton icon="i-lucide-x" color="neutral" variant="ghost" @click="isBranchesModalOpen = false" />
            </div>
          </template>
          <div class="space-y-4">
            <p class="text-sm text-slate-500">Producto: <strong>{{ selectedProduct?.title_product !== null ? decodeURIComponent(String(selectedProduct?.title_product)).replace(/\+/g, ' ') : 'Desconocido' }}</strong></p>
            <div v-if="loadingBranches" class="flex justify-center py-4">
              <UIcon name="i-lucide-loader-2" class="animate-spin w-6 h-6 text-indigo-500" />
            </div>
            <div v-else-if="productBranches.length === 0" class="text-center text-sm text-slate-500 py-4">
              Este producto no tiene stock en ningún almacén.
            </div>
            <UTable v-else :data="productBranches" :columns="[{accessorKey: 'warehouse_name', header: 'Almacén'}, {accessorKey: 'stock_inventory', header: 'Stock'}]">
              <template #warehouse_name-cell="{ row }"><span class="font-semibold">{{ row.original.warehouse_name }}</span></template>
              <template #stock_inventory-cell="{ row }">
                <UBadge color="success">{{ row.original.stock_inventory }}</UBadge>
              </template>
            </UTable>
          </div>
        </UCard>
      </template>
    </UModal>
    <!-- Order Details Modal -->
    <OrderDetailsModal
      v-model:isOpen="isOrderDetailsModalOpen"
      :order-id="selectedOrderIdForDetails"
      @close="selectedOrderIdForDetails = null"
      @updated="fetchRows"
    />

  </div>
</template>
