<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{
  moduleName: string
  initialData?: any
}>()

const emit = defineEmits(['saved', 'cancel'])

const auth = useAuthStore()

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
const formModel = ref<Record<string, any>>({})
const selectOptions = ref<Record<string, any[]>>({})
const loading = ref(true)
const saving = ref(false)
const toast = useToast()

const apiHeaders = {
  Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
}

// Fetch columns metadata
async function loadFormMetadata() {
  if (!moduleConfig.value) return
  loading.value = true
  try {
    const data = await $fetch<any>(`/api/columns?linkTo=id_module_column&equalTo=${moduleConfig.value.id_module}`, {
      headers: apiHeaders
    })

    if (data.status === 200) {
      // We only render fields that are visible or required.
      // Usually type "text", "relations", "select", "boolean", "image", "money", "email", "password"
      const cols = data.results || []
      columns.value = cols

      // Initialize formModel with default values
      const initial = props.initialData || {}
      const model: Record<string, any> = {}

      for (const col of cols) {
        // Skip metadata auto-updated fields unless visible
        if (col.title_column.startsWith('date_') || col.title_column === 'token_admin' || col.title_column === 'token_exp_admin') {
          continue
        }

        const colName = col.title_column
        const val = initial[colName]

        if (col.type_column === 'boolean') {
          model[colName] = val !== undefined ? (Number(val) === 1) : true
        } else if (col.type_column === 'relations') {
          model[colName] = val !== undefined && val !== '' ? String(val) : undefined
          await loadRelationOptions(col.matrix_column)
        } else if (col.type_column === 'select') {
          let options = col.matrix_column ? col.matrix_column.split(',') : []
          // If rol_admin, override with all system roles
          if (colName === 'rol_admin') {
            options = ['superadmin', 'admin', 'cajero', 'vendedor', 'despachador', 'lab_admin', 'lab_worker', 'lab_calidad']
          }
          // Set model to first option or undefined (not empty string)
          model[colName] = val !== undefined && val !== '' ? String(val) : (options.length > 0 ? options[0].trim() : undefined)
          
          if (colName === 'rol_admin') {
            const roleLabels: Record<string, string> = {
              superadmin: 'Super Administrador',
              admin: 'Administrador',
              cajero: 'Cajero / Caja',
              vendedor: 'Vendedor / Venta Despacho',
              despachador: 'Despachador de Envíos',
              lab_admin: 'Administrador de Laboratorio',
              lab_worker: 'Operador de Laboratorio',
              lab_calidad: 'Control de Calidad'
            }
            selectOptions.value = {
              ...selectOptions.value,
              [colName]: options.map((opt: string) => ({
                value: opt.trim(),
                label: roleLabels[opt.trim()] || opt.trim()
              }))
            }
          } else {
            selectOptions.value = {
              ...selectOptions.value,
              [colName]: options.map((opt: string) => ({
                value: opt.trim(),
                label: opt.trim()
              }))
            }
          }
        } else if (['money', 'double', 'int', 'order'].includes(col.type_column)) {
          if (val !== undefined && val !== null && val !== '') {
            const parsed = parseFloat(String(val))
            if (!isNaN(parsed)) {
              const parts = String(parsed).split('.')
              let intPart = (parts[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')
              if (parts[1]) {
                model[colName] = intPart + ',' + parts[1]
              } else {
                model[colName] = intPart
              }
            } else {
              model[colName] = ''
            }
          } else {
            model[colName] = ''
          }
        } else {
          // If password, keep blank
          if (col.type_column === 'password') {
            model[colName] = ''
          } else {
            model[colName] = val !== undefined ? decodeURIComponent(String(val)).replace(/\+/g, ' ') : ''
          }
        }
      }

      // Auto-populate warehouse for purchases if new and user is not superadmin
      if (moduleConfig.value.title_module === 'purchases' && auth.role !== 'superadmin' && !props.initialData) {
        if (auth.role === 'despachador') {
          model['id_office_purchase'] = String(auth.warehouseId || '0')
        } else if (auth.officeId) {
          // Fetch corresponding warehouse for their office
          try {
            const whData = await $fetch<any>(`/api/warehouses?linkTo=id_office_warehouse&equalTo=${auth.officeId}`, {
              headers: apiHeaders
            })
            if (whData.status === 200 && whData.results && whData.results.length > 0) {
              model['id_office_purchase'] = String(whData.results[0].id_warehouse)
            }
          } catch (e) {
            console.error('Error fetching warehouse for purchases:', e)
          }
        }
      }

      // Auto-populate for bills (Gastos) and incomes (Ingresos Extras)
      if ((moduleConfig.value.title_module === 'bills' || moduleConfig.value.title_module === 'incomes') && !props.initialData) {
        const isBill = moduleConfig.value.title_module === 'bills'
        const adminField = isBill ? 'id_admin_bill' : 'id_admin_income'
        const officeField = isBill ? 'id_office_bill' : 'id_office_income'
        const cashField = isBill ? 'id_cash_bill' : 'id_cash_income'

        if (auth.user?.id_admin) model[adminField] = String(auth.user.id_admin)
        if (auth.officeId) model[officeField] = String(auth.officeId)
        
        // Find active cash register for this office
        if (auth.officeId) {
          try {
            const cashData = await $fetch<any>(`/api/cashs?linkTo=id_office_cash,status_cash&equalTo=${auth.officeId},1`, {
              headers: apiHeaders
            })
            if (cashData.status === 200 && cashData.results) {
              const activeCash = Array.isArray(cashData.results) ? cashData.results[0] : cashData.results
              if (activeCash && activeCash.id_cash) {
                model[cashField] = String(activeCash.id_cash)
              }
            }
          } catch (e) {
            console.error('Error fetching active cash:', e)
          }
        }
      }

      formModel.value = model
    }
  } catch (e) {
    console.error('Error loading form metadata:', e)
  } finally {
    loading.value = false
  }
}

// Load relational options in dropdown
async function loadRelationOptions(matrixTable: string) {
  if (selectOptions.value[matrixTable]) return
  try {
    const data = await $fetch<any>(`/api/${matrixTable}`, {
      headers: apiHeaders
    })
    if (data.status === 200 && data.results) {
      const mapped = data.results
        .filter((r: any) => {
          const firstKey = Object.keys(r)[0]
          return firstKey ? r[firstKey] && String(r[firstKey]).trim() !== '' : false
        })
        .map((r: any) => {
          const firstKey = Object.keys(r)[0]
          const secondKey = Object.keys(r)[1]
          return {
            value: firstKey ? String(r[firstKey]).trim() : '',
            label: secondKey ? decodeURIComponent(r[secondKey] || '').replace(/\+/g, ' ') : ''
          }
        })
      selectOptions.value = {
        ...selectOptions.value,
        [matrixTable]: mapped
      }
    } else {
      selectOptions.value = {
        ...selectOptions.value,
        [matrixTable]: []
      }
    }
  } catch (e) {
    console.error(`Error loading relations for ${matrixTable}:`, e)
    selectOptions.value = {
      ...selectOptions.value,
      [matrixTable]: []
    }
  }
}

// Save Form
async function handleSubmit() {
  if (!moduleConfig.value) return
  saving.value = true

  // Basic validation for purchases
  if (props.moduleName === 'compras') {
    if (!formModel.value.id_supplier_purchase) {
      toast.add({ title: 'Por favor selecciona un proveedor.', color: 'error' })
      saving.value = false
      return
    }
    if (!formModel.value.id_office_purchase) {
      toast.add({ title: 'Por favor selecciona un almacén.', color: 'error' })
      saving.value = false
      return
    }
    if (!formModel.value.id_product_purchase) {
      toast.add({ title: 'Por favor selecciona un producto.', color: 'error' })
      saving.value = false
      return
    }
    const qty = parseFormattedNumber(formModel.value.qty_purchase)
    if (qty <= 0) {
      toast.add({ title: 'Por favor ingresa una cantidad válida mayor a 0.', color: 'error' })
      saving.value = false
      return
    }
  }

  // Basic validation for bills (gastos) and incomes
  if (props.moduleName === 'gastos') {
    if (!formModel.value.id_cash_bill) {
      toast.add({ title: 'Debes tener una caja abierta para poder registrar gastos.', color: 'error' })
      saving.value = false
      return
    }
  }
  if (props.moduleName === 'ingresos') {
    if (!formModel.value.id_cash_income) {
      toast.add({ title: 'Debes tener una caja abierta para poder registrar ingresos.', color: 'error' })
      saving.value = false
      return
    }
  }

  try {
    const config = moduleConfig.value
    const isEdit = !!props.initialData
    const idKey = `id_${config.suffix_module}`

    // Construct url-encoded form body
    const body = new URLSearchParams()
    
    // Bind all fields in formModel
    Object.entries(formModel.value).forEach(([key, val]) => {
      let finalVal = val
      const matchedCol = columns.value.find(c => c.title_column === key)
      if (typeof val === 'boolean') {
        finalVal = val ? '1' : '0'
      } else if (matchedCol && ['money', 'double', 'int', 'order'].includes(matchedCol.type_column)) {
        let strVal = String(val).replace(/\./g, '').replace(',', '.')
        let numVal = parseFloat(strVal) || 0
        if (numVal < 0) {
          numVal = Math.abs(numVal)
        }
        finalVal = String(numVal)
      }
      body.append(key, String(finalVal))
    })

    // If new record, check if there's a date_created column
    if (!isEdit) {
      const dateCreatedCol = `date_created_${config.suffix_module}`
      const hasDateCreated = columns.value.some(c => c.title_column === dateCreatedCol)
      if (hasDateCreated) {
        body.append(dateCreatedCol, new Date().toISOString().split('T')[0] || '')
      }
    }

    let url = `/api/${config.title_module}`
    let method: 'POST' | 'PUT' = 'POST'
    const queryParams: Record<string, any> = {
      token: 'no',
      except: idKey
    }

    if (isEdit) {
      method = 'PUT'
      queryParams.id = props.initialData[idKey]
      queryParams.nameId = idKey
    }

    const res = await $fetch<any>(url, {
      method,
      headers: {
        ...apiHeaders,
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      query: queryParams,
      body: body.toString()
    })

    if (res.status === 200) {
      toast.add({ title: 'Registro guardado exitosamente', color: 'success' })
      emit('saved')
    } else {
      toast.add({ title: `Error al guardar: ${res.results || 'Verifica los campos e intenta de nuevo'}`, color: 'error' })
    }
  } catch (e) {
    console.error('Error saving form:', e)
    toast.add({ title: 'Error al enviar los datos del formulario.', color: 'error' })
  } finally {
    saving.value = false
  }
}

// Helper to parse localized numeric strings (e.g. "1.250,50" -> 1250.5)
function parseFormattedNumber(val: any): number {
  if (val === undefined || val === null || val === '') return 0
  const str = String(val).replace(/\./g, '').replace(',', '.')
  return parseFloat(str) || 0
}

// Helper to format numbers back to localized strings
function formatNumber(num: number): string {
  if (isNaN(num)) return '0'
  const parts = String(num).split('.')
  let intPart = (parts[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')
  if (parts[1]) {
    let decPart = parts[1].substring(0, 2)
    return intPart + ',' + decPart
  } else {
    return intPart
  }
}

// Watcher for auto-calculating investment in purchases
watch(
  () => [formModel.value.cost_purchase, formModel.value.qty_purchase],
  ([newCost, newQty]) => {
    if (props.moduleName === 'compras') {
      const cost = parseFormattedNumber(newCost)
      const qty = parseFormattedNumber(newQty)
      formModel.value.invest_purchase = formatNumber(cost * qty)
    }
  }
)

onMounted(() => {
  loadFormMetadata()
})

watch(() => props.initialData, () => {
  loadFormMetadata()
})
</script>

<template>
  <div class="h-full flex flex-col justify-between">
    <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
      <div v-if="loading" class="flex justify-center items-center py-12">
        <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-green-500" />
      </div>

      <form v-else class="space-y-4" @submit.prevent="handleSubmit">
        <div v-for="col in columns" :key="col.title_column">
          <div v-if="!col.title_column.startsWith('date_') && col.title_column !== 'token_admin' && col.title_column !== 'token_exp_admin' && col.title_column !== `id_${moduleConfig?.suffix_module}` && !(col.title_column === 'id_warehouse_admin' && formModel.rol_admin !== 'despachador') && !(moduleConfig?.title_module === 'purchases' && col.title_column === 'id_office_purchase' && auth.role === 'despachador')">

            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
              {{ col.alias_column || col.title_column }}
            </label>

            <div v-if="col.type_column === 'boolean'" class="flex items-center gap-3">
              <USwitch v-model="formModel[col.title_column]" />
              <span class="text-sm text-slate-600 dark:text-slate-400">
                {{ formModel[col.title_column] ? 'Activo (ON)' : 'Inactivo (OFF)' }}
              </span>
            </div>

            <div v-else-if="col.type_column === 'relations'">
              <USelectMenu
                v-model="formModel[col.title_column]"
                :items="selectOptions[col.matrix_column] || []"
                class="w-full"
                placeholder="Seleccionar opción..."
                :ui="{ content: 'z-[100]' }"
                value-key="value"
                label-key="label"
                :disabled="(moduleConfig?.title_module === 'bills' && ['id_admin_bill', 'id_office_bill', 'id_cash_bill'].includes(col.title_column)) || (moduleConfig?.title_module === 'incomes' && ['id_admin_income', 'id_office_income', 'id_cash_income'].includes(col.title_column))"
              />
            </div>

            <div v-else-if="col.type_column === 'select'">
              <USelect
                v-model="formModel[col.title_column]"
                :items="selectOptions[col.title_column] || []"
                class="w-full capitalize"
                :ui="{ content: 'z-[100]' }"
              />
            </div>

            <div v-else-if="['money', 'double', 'int', 'order'].includes(col.type_column)">
              <UInput
                v-model="formModel[col.title_column]"
                type="text"
                class="w-full format-numeric"
                data-format-numeric="true"
                inputmode="decimal"
                placeholder="0,00"
                :disabled="moduleConfig?.title_module === 'purchases' && col.title_column === 'invest_purchase'"
              />
            </div>

            <div v-else-if="col.type_column === 'password'">
              <UInput
                v-model="formModel[col.title_column]"
                type="password"
                placeholder="•••••••• (dejar en blanco para mantener)"
                class="w-full"
              />
            </div>

            <div v-else-if="col.type_column === 'image'" class="space-y-2">
              <UInput
                v-model="formModel[col.title_column]"
                placeholder="URL de la imagen..."
                class="w-full"
              />
              <div v-if="formModel[col.title_column]" class="mt-2 flex items-center border border-slate-200 dark:border-slate-700 rounded-lg p-2 bg-slate-50 dark:bg-slate-900 w-max">
                <img :src="formModel[col.title_column]" class="w-16 h-16 rounded object-cover" />
              </div>
            </div>

            <div v-else-if="col.title_column.includes('description') || col.title_column.includes('notes')">
              <UTextarea
                v-model="formModel[col.title_column]"
                :rows="3"
                class="w-full"
              />
            </div>

            <div v-else>
              <UInput
                v-model="formModel[col.title_column]"
                class="w-full"
              />
            </div>

          </div>
        </div>
      </form>
    </div>

    <!-- Actions Footer -->
    <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3 bg-slate-50 dark:bg-slate-950">
      <UButton
        color="neutral"
        variant="ghost"
        @click="emit('cancel')"
      >
        Cancelar
      </UButton>
      <UButton
        color="primary"
        :loading="saving"
        @click="handleSubmit"
      >
        Guardar
      </UButton>
    </div>
  </div>
</template>
