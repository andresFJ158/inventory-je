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
const itemsPerPage = 18

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

const hasRowActions = computed(() =>
  showActions.value || moduleConfig.value?.title_module === 'orders' || moduleConfig.value?.title_module === 'cashs'
)

const visibleColumns = computed(() => columns.value.filter(c => c.visible_column === 1))

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
  if (props.moduleName === 'ventas' || props.moduleName === 'ordenes') {
    navigateTo('/pos')
    return
  }
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

function decodeCell(value: any) {
  if (value === null || value === undefined || value === '') return '-'
  return decodeURIComponent(String(value)).replace(/\+/g, ' ')
}

function cellTitle(value: any) {
  const text = decodeCell(value)
  return text === '-' ? '' : text
}

function isCompactCodeColumn(colName: string) {
  return colName.startsWith('id_') ||
    colName.includes('sku') ||
    colName.includes('code') ||
    colName.includes('date') ||
    colName.includes('dni') ||
    colName.includes('phone') ||
    colName.includes('transaction')
}

// Formatting Dates Elegant
function formatDateCell(value: any) {
  if (!value) return '-'
  const decoded = decodeCell(value)
  if (decoded === '-') return '-'
  try {
    const parts = decoded.split(' ')
    const datePart = parts[0]
    const timePart = parts[1] ? ' ' + parts[1].slice(0, 5) : ''
    const d = new Date(datePart)
    if (isNaN(d.getTime())) return decoded
    const day = String(d.getDate()).padStart(2, '0')
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
    const month = months[d.getMonth()]
    const year = d.getFullYear()
    return `${day}/${month}/${year}${timePart}`
  } catch {
    return decoded
  }
}

// ---- EXPORT AND IMPORT LOGIC ----
function handleExportCSV() {
  if (rows.value.length === 0) {
    alert('No hay datos para exportar.')
    return
  }
  const colsToExport = columns.value.filter(c => c.visible_column === 1)
  const headers = colsToExport.map(c => c.alias_column || c.title_column)
  
  const csvRows = rows.value.map(row => {
    return colsToExport.map(col => {
      let val = row[col.title_column]
      if (val === null || val === undefined) {
        val = ''
      } else {
        val = decodeURIComponent(String(val)).replace(/\+/g, ' ')
      }
      if (val.includes(';') || val.includes('"') || val.includes('\n')) {
        val = '"' + val.replace(/"/g, '""') + '"'
      }
      return val
    }).join(';')
  })
  
  const csvContent = "\ufeffsep=;\n" + headers.join(';') + "\n" + csvRows.join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement("a")
  link.setAttribute("href", url)
  link.setAttribute("download", `export_${props.moduleName}_${new Date().toISOString().split('T')[0]}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const isImportModalOpen = ref(false)
const importFile = ref<File | null>(null)
const importLogs = ref<string[]>([])
const importing = ref(false)

function onImportFileChange(e: Event) {
  const files = (e.target as HTMLInputElement).files
  importFile.value = files && files.length ? files[0] : null
}

async function handleImportCSV() {
  if (!importFile.value) {
    alert('Por favor selecciona un archivo CSV válido')
    return
  }
  
  importing.value = true
  importLogs.value = ['Iniciando importación...']
  
  const reader = new FileReader()
  reader.onload = async (event) => {
    try {
      const text = event.target?.result as string
      let lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 0)
      if (lines[0] && lines[0].toLowerCase().startsWith('sep=')) {
        lines.shift()
      }
      if (lines.length < 2) {
        importLogs.value.push('Error: El archivo no contiene filas de datos.')
        importing.value = false
        return
      }
      
      // Auto-detect separator
      const isSemicolon = lines[0].includes(';')
      const separator = isSemicolon ? ';' : ','
      
      const headers = lines[0].split(separator).map(h => h.replace(/^"|"$/g, '').trim())
      const headerMap: Record<number, string> = {}
      headers.forEach((h, index) => {
        const matchedCol = columns.value.find(c => 
          c.title_column.toLowerCase() === h.toLowerCase() || 
          (c.alias_column && c.alias_column.toLowerCase() === h.toLowerCase())
        )
        if (matchedCol) {
          headerMap[index] = matchedCol.title_column
        }
      })
      
      let importedCount = 0
      let failedCount = 0
      
      for (let i = 1; i < lines.length; i++) {
        const line = lines[i]
        const values: string[] = []
        let current = ''
        let inQuotes = false
        for (let charIndex = 0; charIndex < line.length; charIndex++) {
          const char = line[charIndex]
          if (char === '"') {
            inQuotes = !inQuotes
          } else if (char === separator && !inQuotes) {
            values.push(current.trim())
            current = ''
          } else {
            current += char
          }
        }
        values.push(current.trim())
        
        const payload = new URLSearchParams()
        values.forEach((val, idx) => {
          const key = headerMap[idx]
          if (key) {
            const cleanVal = val.replace(/^"|"$/g, '').replace(/""/g, '"')
            payload.append(key, cleanVal)
          }
        })
        
        const config = moduleConfig.value
        const idKey = `id_${config.suffix_module}`
        
        const dateCreatedCol = `date_created_${config.suffix_module}`
        const hasDateCreated = columns.value.some(c => c.title_column === dateCreatedCol)
        if (hasDateCreated && !payload.has(dateCreatedCol)) {
          payload.append(dateCreatedCol, new Date().toISOString().split('T')[0])
        }
        
        try {
          const res = await $fetch<any>(`/api/${config.title_module}`, {
            method: 'POST',
            headers: {
              ...apiHeaders,
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            query: {
              token: 'no',
              except: idKey
            },
            body: payload.toString()
          })
          
          if (res.status === 200) {
            importedCount++
          } else {
            failedCount++
            importLogs.value.push(`Línea ${i+1}: Error al guardar - ${res.results || 'Verifique valores'}`)
          }
        } catch (e: any) {
          failedCount++
          importLogs.value.push(`Línea ${i+1}: Fallo de red - ${e.message}`)
        }
      }
      
      importLogs.value.push(`✓ Importación completada. Exitosos: ${importedCount}, Fallidos: ${failedCount}`)
      await fetchRows()
    } catch (err: any) {
      importLogs.value.push(`Error general de procesamiento: ${err.message}`)
    } finally {
      importing.value = false
    }
  }
  reader.readAsText(importFile.value, 'UTF-8')
}
</script>

<template>
  <div class="space-y-3">
    <!-- Header Controls -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white dark:bg-slate-950/60 backdrop-blur border border-slate-200 dark:border-slate-800 p-3 rounded-lg shadow-sm">
      <div>
        <h1 class="text-base font-extrabold text-slate-800 dark:text-white tracking-tight capitalize">
          {{ moduleConfig?.title || 'Administración' }}
        </h1>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
          {{ totalItems }} registros · {{ itemsPerPage }} por página
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
        <UInput
          v-model="search"
          icon="i-lucide-search"
          placeholder="Buscar..."
          size="sm"
          class="w-full sm:w-48"
        />
        
        <UButton
          icon="i-lucide-download"
          color="neutral"
          variant="outline"
          size="sm"
          title="Exportar registros a CSV"
          @click="handleExportCSV"
        >
          Exportar
        </UButton>

        <UButton
          v-if="auth.role === 'superadmin' || auth.role === 'admin' || moduleConfig?.editable_module === 1"
          icon="i-lucide-upload"
          color="neutral"
          variant="outline"
          size="sm"
          title="Importar registros desde CSV"
          @click="isImportModalOpen = true"
        >
          Importar
        </UButton>

        <UButton
          v-if="auth.role === 'superadmin' || auth.role === 'admin' || moduleConfig?.editable_module === 1"
          icon="i-lucide-plus"
          color="primary"
          size="sm"
          class="active:scale-95 duration-100 transition-transform font-bold"
          @click="openCreate"
        >
          Agregar
        </UButton>
      </div>
    </div>

    <!-- Table -->
    <div class="table-compact-card bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm rounded-lg">
      <div v-if="loading" class="flex justify-center py-10">
        <UIcon name="i-lucide-loader-2" class="animate-spin w-6 h-6 text-green-500" />
      </div>
      <div v-else-if="rows.length === 0" class="text-center py-10 text-slate-400 dark:text-slate-500 text-sm">
        No se encontraron registros.
      </div>
      <div v-else class="table-scroll overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm text-slate-700 dark:text-slate-300">
          <thead>
            <tr class="bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <th class="table-index font-semibold text-sm uppercase px-3 py-2">#</th>
              <th v-for="col in visibleColumns" :key="col.title_column" class="font-semibold text-sm uppercase px-3 py-2">
                {{ col.alias_column || col.title_column }}
              </th>
              <th v-if="hasRowActions" class="table-actions text-right font-semibold text-sm uppercase px-3 py-2">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="(row, idx) in paginatedRows" 
              :key="row[Object.keys(row)[0]]" 
              :class="[
                'border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors',
                props.moduleName === 'caja' && row.status_cash == 1 ? 'bg-emerald-50/50 dark:bg-emerald-950/10' : '',
                props.moduleName === 'caja' && row.status_cash == 0 ? 'bg-slate-50/20 dark:bg-slate-900/5' : ''
              ]"
            >
              <td class="table-index text-[13px] text-slate-400 dark:text-slate-500 font-mono px-3 py-2.5">
                {{ (page - 1) * itemsPerPage + idx + 1 }}
              </td>
              <td v-for="col in visibleColumns" :key="col.title_column" class="px-3 py-2.5">
                <!-- Image -->
                <div v-if="col.type_column === 'image'" class="flex items-center">
                  <UAvatar
                    :src="row[col.title_column] ? decodeURIComponent(row[col.title_column]).replace(/\+/g, ' ') : '/views/assets/img/multimedia.png'"
                    size="sm"
                    class="border border-slate-200 dark:border-slate-700 shadow-sm"
                  />
                </div>

                <!-- Boolean -->
                <span v-else-if="col.type_column === 'boolean'">
                  <UBadge
                    :color="row[col.title_column] == 1 ? 'emerald' : 'rose'"
                    variant="subtle"
                    size="sm"
                    class="capitalize whitespace-nowrap font-semibold"
                  >
                    {{ row[col.title_column] == 1 ? 'ON' : 'OFF' }}
                  </UBadge>
                </span>

                <!-- Special Render for Cash status -->
                <span v-else-if="col.title_column === 'status_cash'">
                  <UBadge
                    :color="row[col.title_column] == 1 ? 'emerald' : 'neutral'"
                    variant="solid"
                    size="sm"
                    class="font-black tracking-wider text-xs"
                  >
                    {{ row[col.title_column] == 1 ? 'ABIERTA' : 'CERRADA' }}
                  </UBadge>
                </span>

                <!-- Money -->
                <span 
                  v-else-if="col.type_column === 'money'" 
                  :class="[
                    'table-cell-code font-bold font-mono text-sm',
                    parseFloat(row[col.title_column] || 0) < 0 || col.title_column.includes('bill') || col.title_column.includes('bill_cash') || col.title_column.includes('diff') 
                      ? 'text-rose-600 dark:text-rose-400' 
                      : 'text-emerald-600 dark:text-emerald-400'
                  ]"
                >
                  Bs. {{ parseFloat(row[col.title_column] || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </span>

                <!-- Relation -->
                <span v-else-if="col.type_column === 'relations'">
                  <UBadge color="indigo" variant="outline" size="sm" class="max-w-48 truncate font-medium">
                    {{ getRelationLabel(col.matrix_column, row[col.title_column]) }}
                  </UBadge>
                </span>

                <!-- Select -->
                <span v-else-if="col.type_column === 'select'">
                  <UBadge color="neutral" variant="solid" size="sm" class="capitalize max-w-40 truncate font-semibold">
                    {{ row[col.title_column] }}
                  </UBadge>
                </span>

                <!-- Date / Time Elegant Columns -->
                <span v-else-if="col.title_column.includes('date') || col.title_column.startsWith('date_')" class="table-cell-code text-sm text-slate-500 dark:text-slate-400 font-mono">
                  {{ formatDateCell(row[col.title_column]) }}
                </span>

                <!-- Text/Default -->
                <span
                  v-else
                  :class="isCompactCodeColumn(col.title_column) ? 'table-cell-code text-sm text-slate-600 dark:text-slate-300 font-mono' : 'table-cell-text text-sm text-slate-700 dark:text-slate-200'"
                  :title="cellTitle(row[col.title_column])"
                >
                  {{ decodeCell(row[col.title_column]) }}
                </span>
              </td>
              
              <!-- Actions -->
              <td v-if="hasRowActions" class="table-actions text-right px-3 py-2.5">
                <div class="flex items-center justify-end gap-1.5">
                  <UButtonGroup size="xs">
                    <!-- Custom Print Order Action -->
                    <UButton
                      v-if="moduleConfig?.title_module === 'orders'"
                      icon="i-lucide-printer"
                      color="primary"
                      variant="soft"
                      @click="openReceipt(row[`id_${moduleConfig.suffix_module}`])"
                      title="Imprimir Comprobante"
                    />
                    <!-- Custom View Cash Details Action -->
                    <UButton
                      v-if="moduleConfig?.title_module === 'cashs'"
                      icon="i-lucide-receipt"
                      color="primary"
                      variant="soft"
                      @click="openCashDetails(row)"
                      title="Ver Detalles de Caja"
                    />
                    <template v-if="showActions">
                      <UButton
                        icon="i-lucide-edit"
                        color="neutral"
                        variant="ghost"
                        title="Editar"
                        @click="openEdit(row)"
                      />
                      <UButton
                        icon="i-lucide-trash"
                        color="rose"
                        variant="ghost"
                        title="Eliminar"
                        @click="handleDelete(row)"
                      />
                    </template>
                  </UButtonGroup>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalItems > itemsPerPage" class="px-3 py-2 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row gap-2 justify-between items-center bg-slate-50 dark:bg-slate-950/40">
        <span class="text-xs text-slate-500 dark:text-slate-400">
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

    <!-- CSV Import Dialog -->
    <UModal v-model:open="isImportModalOpen" title="Importar Datos desde CSV">
      <template #body>
        <div class="space-y-4 p-1">
          <p class="text-xs text-slate-600 dark:text-slate-400">
            Selecciona un archivo CSV que coincida con las columnas del módulo <strong>{{ moduleConfig?.title }}</strong>.
          </p>
          <UFormField label="Archivo CSV (.csv)">
            <input
              type="file"
              accept=".csv"
              class="block w-full text-xs text-slate-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-300"
              @change="onImportFileChange"
            />
          </UFormField>

          <!-- Logs of importing -->
          <div v-if="importLogs.length > 0" class="bg-slate-900 text-slate-200 font-mono text-[10px] p-3 rounded-lg max-h-40 overflow-y-auto space-y-1">
            <div v-for="(log, idx) in importLogs" :key="idx">{{ log }}</div>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" size="sm" @click="() => { isImportModalOpen = false; importLogs = [] }">Cerrar</UButton>
          <UButton color="primary" size="sm" icon="i-lucide-upload-cloud" :loading="importing" @click="handleImportCSV">Iniciar Importación</UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>
