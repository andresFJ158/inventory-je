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
  almacenes: { id_module: 44, title_module: 'warehouses', suffix_module: 'warehouse', title: 'Almacenes', editable_module: 1 },
  qrs: { id_module: 99, title_module: 'qrs', suffix_module: 'qr', title: 'Códigos QR', editable_module: 1 }
}

const moduleConfig = computed(() => MODULE_MAPPING[props.moduleName])

// State
const columns = ref<any[]>([])
const formModel = ref<Record<string, any>>({})
const selectOptions = ref<Record<string, any[]>>({})
const loadedRelations = new Set<string>() // tracks which tables have been fetched (even if result is empty)
const loading = ref(true)
const saving = ref(false)
const toast = useToast()
const ajaxBase = '/ajax/pos.ajax.php'

const vendedoresList = ref<Array<{ value: string; label: string }>>([
  { value: '0', label: 'Sin asignar (Pool)' }
])

const canManageClients = computed(() => {
  const r = auth.role
  let p: Record<string, string> = {}
  try {
    const raw = auth.permissions
    p = typeof raw === 'string' ? JSON.parse(decodeURIComponent(raw)) : (raw || {})
  } catch {}
  return ['superadmin', 'admin'].includes(r) || (r === 'vendedor' && p.gestionar_clientes === 'on')
})

const apiHeaders = {
  Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
}

// ¿Estamos editando un registro existente o creando uno nuevo?
const isEdit = computed(() => !!props.initialData)

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
            options = ['superadmin', 'admin', 'cajero', 'vendedor', 'despachador', 'despachador_laboratorio', 'lab_admin', 'lab_worker', 'lab_calidad']
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
              despachador_laboratorio: 'Despachador de Laboratorio',
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

      if (moduleConfig.value.title_module === 'purchases') {
        const isDespachadorOrLab = auth.role === 'despachador' || auth.role === 'lab_admin'
        const hasOfficeField = cols.some((c: any) => c.title_column === 'id_office_purchase')
        if (hasOfficeField && isDespachadorOrLab) {
          if (!isEdit.value && !props.initialData?.id_office_purchase) {
            if (auth.effectiveOfficeId) {
              model['id_office_purchase'] = String(auth.effectiveOfficeId)
            }
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

      if (moduleConfig.value.title_module === 'clients') {
        if (!formModel.value.id_admin_client) {
          formModel.value.id_admin_client = '0'
        }
        if (canManageClients.value) {
          await loadVendedores()
        }
      }
    }
  } catch (e) {
    console.error('Error loading form metadata:', e)
  } finally {
    loading.value = false
  }
}

// Load relational options in dropdown
async function loadRelationOptions(matrixTable: string) {
  // Use loadedRelations Set so we don't re-fetch the same table twice.
  // We can't use `if (selectOptions.value[matrixTable]) return` because
  // an empty array [] is truthy in JS and would block all future retries.
  if (loadedRelations.has(matrixTable)) return
  loadedRelations.add(matrixTable)
  try {
    const data = props.moduleName === 'compras' && matrixTable === 'products'
      ? await $fetch<any>('/api/purchasable-products', {
          headers: apiHeaders
        })
      : await $fetch<any>(`/api/${matrixTable}`, {
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
      // Remove from loaded set so a retry is possible on error
      loadedRelations.delete(matrixTable)
      selectOptions.value = {
        ...selectOptions.value,
        [matrixTable]: []
      }
    }
  } catch (e) {
    // Remove from loaded set so a retry is possible after network errors
    loadedRelations.delete(matrixTable)
    console.error(`Error loading relations for ${matrixTable}:`, e)
    selectOptions.value = {
      ...selectOptions.value,
      [matrixTable]: []
    }
  }
}

async function resolvePurchaseWarehouse(): Promise<string> {
  if (auth.warehouseId) return String(auth.warehouseId)
  if (!auth.officeId) return '0'
  try {
    const data = await $fetch<any>(`/api/warehouses?linkTo=id_office_warehouse&equalTo=${auth.officeId}`, {
      headers: apiHeaders
    })
    if (data.status === 200 && data.results && data.results.length > 0) {
      return String(data.results[0].id_warehouse || '0')
    }
  } catch (e) {
    console.error('Error resolving purchase warehouse:', e)
  }
  return '0'
}

async function loadVendedores() {
  try {
    const data = await $fetch<any>('/api/admins?linkTo=rol_admin&equalTo=vendedor', {
      headers: apiHeaders
    })
    if (data.status === 200 && data.results) {
      vendedoresList.value = [
        { value: '0', label: 'Sin asignar (Pool)' },
        ...data.results.map((a: any) => ({
          value: String(a.id_admin),
          label: [a.name_admin, a.surname_admin].filter(Boolean).join(' ') || a.email_admin
        }))
      ]
    }
  } catch (e) {
    console.error('Error loading vendedores:', e)
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
    const availableProducts = selectOptions.value.products || []
    const selectedIsPurchasable = availableProducts.some((p: any) => String(p.value) === String(formModel.value.id_product_purchase))
    if (!selectedIsPurchasable) {
      toast.add({ title: 'Este producto tiene receta o producción asignada y no puede comprarse.', color: 'error' })
      saving.value = false
      return
    }
    const qty = parseFormattedNumber(formModel.value.qty_purchase)
    if (qty <= 0) {
      toast.add({ title: 'Por favor ingresa una cantidad válida mayor a 0.', color: 'error' })
      saving.value = false
      return
    }
    const cost = parseFormattedNumber(formModel.value.cost_purchase)
    if (cost <= 0) {
      toast.add({ title: 'El costo de compra debe ser mayor a 0.', color: 'error' })
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

      if (config.title_module === 'products' && auth.role === 'lab_admin') {
        body.set('id_office_product', '0')
        body.set('initial_stock_product', '0')
        body.set('is_combo_product', '0')
        body.set('is_manufactured_product', '0')
        body.set('source_type_product', 'externo')
        body.set('origin_office_product', String(auth.officeId || 0))
      }
    }

    let url = `/api/${config.title_module}`
    let method: 'POST' | 'PUT' = 'POST'
    const queryParams: Record<string, any> = {
      token: auth.token,
      table: 'admins',
      suffix: 'admin',
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
      
      // Update cash totals instantly in the backend if this was an expense or income
      if (props.moduleName === 'gastos' || props.moduleName === 'ingresos') {
        const cashField = props.moduleName === 'gastos' ? formModel.value.id_cash_bill : formModel.value.id_cash_income
        if (cashField) {
          $fetch('/ajax/pos.ajax.php', {
            method: 'POST',
            body: new URLSearchParams({ getCashDetails: 'ok', id_cash: String(cashField) }),
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
          }).catch(console.error)
        }
      }

      emit('saved')
    } else {
      toast.add({ title: res.results || 'Error al guardar el registro', color: 'error' })
    }
  } catch (e: any) {
    console.error('Error saving form:', e)
    const backendMessage = e.response?._data?.results || e.data?.results || e.message
    toast.add({ 
      title: backendMessage || 'Error interno del servidor o de red', 
      color: 'error' 
    })
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

// Image upload state
const uploadingImage = ref<Record<string, boolean>>({})

async function handleImageUpload(colName: string, event: Event) {
  const fileInput = event.target as HTMLInputElement
  const file = fileInput.files?.[0]
  if (!file) return

  uploadingImage.value[colName] = true
  const formData = new FormData()
  formData.append('imageFile', file)
  formData.append('uploadImage', 'ok')

  try {
    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: formData
    })
    
    // Convert string response to JSON if necessary
    const data = typeof res === 'string' ? JSON.parse(res) : res
    
    if (data.status === 200 && data.url) {
      formModel.value[colName] = data.url
      toast.add({ title: 'Imagen subida exitosamente', color: 'success' })
      toast.add({ title: 'Error al subir la imagen', color: 'error' })
    }
  } catch (e) {
    console.error('Upload error:', e)
    toast.add({ title: 'Fallo la conexión al subir imagen', color: 'error' })
  } finally {
    uploadingImage.value[colName] = false
    fileInput.value = '' // reset input
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
      <!-- Skeleton de carga -->
      <div v-if="loading" class="space-y-5">
        <div v-for="n in 5" :key="n" class="space-y-2">
          <div class="h-3 w-24 rounded bg-slate-100 animate-pulse" />
          <div class="h-9 w-full rounded-lg bg-slate-100 animate-pulse" />
        </div>
      </div>

      <form v-else class="space-y-4" @submit.prevent="handleSubmit">
        <div v-for="col in columns" :key="col.title_column">
          <div v-if="!col.title_column.startsWith('date_') && col.title_column !== 'token_admin' && col.title_column !== 'token_exp_admin' && col.title_column !== `id_${moduleConfig?.suffix_module}` && !(col.title_column === 'id_warehouse_admin' && formModel.rol_admin !== 'despachador') && !(moduleConfig?.title_module === 'purchases' && col.title_column === 'id_office_purchase' && auth.role === 'despachador') && !(moduleConfig?.title_module === 'purchases' && col.title_column === 'utility_purchase') && !(moduleConfig?.title_module === 'clients' && col.title_column === 'id_admin_client')">

            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
              {{ col.alias_column || col.title_column }}
            </label>

            <div v-if="col.type_column === 'boolean'" class="flex items-center gap-3">
              <USwitch v-model="formModel[col.title_column]" />
              <span class="text-sm text-slate-600">
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
                :disabled="(moduleConfig?.title_module === 'bills' && ['id_admin_bill', 'id_office_bill', 'id_cash_bill'].includes(col.title_column)) || (moduleConfig?.title_module === 'incomes' && ['id_admin_income', 'id_office_income', 'id_cash_income'].includes(col.title_column)) || (moduleConfig?.title_module === 'purchases' && auth.role === 'lab_admin' && col.title_column === 'id_office_purchase' && !!formModel.id_office_purchase && String(formModel.id_office_purchase) !== '0')"
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
              <div class="relative">
                <input
                  type="file"
                  accept="image/*"
                  class="block w-full text-sm text-slate-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-emerald-50 file:text-emerald-700
                    hover:file:bg-emerald-100 cursor-pointer"
                  @change="handleImageUpload(col.title_column, $event)"
                  :disabled="uploadingImage[col.title_column]"
                />
                <div v-if="uploadingImage[col.title_column]" class="absolute right-3 top-2 flex items-center gap-2 text-xs text-emerald-600 font-medium">
                  <UIcon name="i-heroicons-arrow-path" class="animate-spin w-4 h-4" /> Subiendo...
                </div>
              </div>
              <div v-if="formModel[col.title_column]" class="mt-2 flex flex-col gap-2 border border-slate-200 rounded-lg p-2 bg-slate-50 w-max">
                <img :src="formModel[col.title_column]" class="w-24 h-24 rounded-lg object-cover ring-1 ring-slate-200" alt="Vista previa">
                <button type="button" class="text-xs text-red-500 hover:text-red-700 font-medium text-left" @click="formModel[col.title_column] = ''">Quitar imagen</button>
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

      <!-- Vendor assignment (clients module, gestor/admin only) -->
      <div v-if="moduleConfig?.title_module === 'clients' && canManageClients && !loading">
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
          Vendedor Asignado
        </label>
        <USelectMenu
          v-model="formModel.id_admin_client"
          :items="vendedoresList"
          class="w-full"
          placeholder="Sin asignar (Pool)"
          :ui="{ content: 'z-[100]' }"
          value-key="value"
          label-key="label"
        />
      </div>
    </div>

    <!-- Actions Footer -->
    <div class="p-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
      <UButton
        color="neutral"
        variant="ghost"
        :disabled="saving"
        @click="emit('cancel')"
      >
        Cancelar
      </UButton>
      <UButton
        color="primary"
        :icon="isEdit ? 'i-lucide-save' : 'i-lucide-plus'"
        :loading="saving"
        :disabled="loading"
        @click="handleSubmit"
      >
        {{ saving ? 'Guardando…' : (isEdit ? 'Guardar cambios' : 'Crear') }}
      </UButton>
    </div>
  </div>
</template>
