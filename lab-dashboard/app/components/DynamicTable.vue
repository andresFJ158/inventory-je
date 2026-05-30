<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{
  moduleName: string
}>()

const auth = useAuthStore()

// Module mapping
const MODULE_MAPPING: Record<string, { id_module: number, title_module: string, suffix_module: string, title: string }> = {
  admins: { id_module: 2, title_module: 'admins', suffix_module: 'admin', title: 'Administradores' },
  sucursales: { id_module: 4, title_module: 'offices', suffix_module: 'office', title: 'Sucursales' },
  clientes: { id_module: 6, title_module: 'clients', suffix_module: 'client', title: 'Clientes' },
  categorias: { id_module: 8, title_module: 'categories', suffix_module: 'category', title: 'Categorías' },
  productos: { id_module: 10, title_module: 'products', suffix_module: 'product', title: 'Productos' },
  compras: { id_module: 41, title_module: 'purchases', suffix_module: 'purchase', title: 'Compras' },
  ordenes: { id_module: 14, title_module: 'orders', suffix_module: 'order', title: 'Órdenes' },
  ventas: { id_module: 16, title_module: 'sales', suffix_module: 'sale', title: 'Ventas' },
  caja: { id_module: 18, title_module: 'cashs', suffix_module: 'cash', title: 'Caja' },
  gastos: { id_module: 20, title_module: 'bills', suffix_module: 'bill', title: 'Gastos' },
  proveedores: { id_module: 40, title_module: 'suppliers', suffix_module: 'supplier', title: 'Proveedores' }
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

    // Apply office filter if needed (matching original legacy logic)
    const hasOfficeCol = columns.value.some(c => c.title_column === `id_office_${config.suffix_module}`)
    if (auth.officeId && auth.officeId > 0 && hasOfficeCol && config.title_module !== 'clients') {
      params.linkTo = `id_office_${config.suffix_module}`
      params.equalTo = auth.officeId
    }

    // Handle search query
    if (search.value) {
      params.linkTo = columns.value.find(c => c.type_column === 'text')?.title_column || idKey
      params.search = search.value
    }

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
              <td v-if="showActions" class="p-4 text-right">
                <div class="flex items-center justify-end gap-2">
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
  </div>
</template>
