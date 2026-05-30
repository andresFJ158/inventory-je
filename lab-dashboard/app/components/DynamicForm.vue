<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{
  moduleName: string
  initialData?: any
}>()

const emit = defineEmits(['saved', 'cancel'])

const auth = useAuthStore()

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
const formModel = ref<Record<string, any>>({})
const selectOptions = ref<Record<string, any[]>>({})
const loading = ref(true)
const saving = ref(false)

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
          model[colName] = val !== undefined ? String(val) : ''
          await loadRelationOptions(col.matrix_column)
        } else if (col.type_column === 'select') {
          model[colName] = val !== undefined ? String(val) : (col.matrix_column ? col.matrix_column.split(',')[0] : '')
          selectOptions.value[colName] = col.matrix_column ? col.matrix_column.split(',') : []
        } else {
          // If password, keep blank
          if (col.type_column === 'password') {
            model[colName] = ''
          } else {
            model[colName] = val !== undefined ? decodeURIComponent(String(val)).replace(/\+/g, ' ') : ''
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
      selectOptions.value[matrixTable] = data.results.map((r: any) => {
        const firstKey = Object.keys(r)[0]
        const secondKey = Object.keys(r)[1]
        return {
          value: String(r[firstKey]),
          label: decodeURIComponent(r[secondKey] || '').replace(/\+/g, ' ')
        }
      })
    } else {
      selectOptions.value[matrixTable] = []
    }
  } catch (e) {
    console.error(`Error loading relations for ${matrixTable}:`, e)
    selectOptions.value[matrixTable] = []
  }
}

// Save Form
async function handleSubmit() {
  if (!moduleConfig.value) return
  saving.value = true

  try {
    const config = moduleConfig.value
    const isEdit = !!props.initialData
    const idKey = `id_${config.suffix_module}`

    // Construct url-encoded form body
    const body = new URLSearchParams()
    
    // Bind all fields in formModel
    Object.entries(formModel.value).forEach(([key, val]) => {
      let finalVal = val
      if (typeof val === 'boolean') {
        finalVal = val ? '1' : '0'
      }
      body.append(key, String(finalVal))
    })

    // If new record, check if there's a date_created column
    if (!isEdit) {
      const dateCreatedCol = `date_created_${config.suffix_module}`
      const hasDateCreated = columns.value.some(c => c.title_column === dateCreatedCol)
      if (hasDateCreated) {
        body.append(dateCreatedCol, new Date().toISOString().split('T')[0])
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
      emit('saved')
    } else {
      alert(`Error al guardar: ${res.results || 'Verifica los campos e intenta de nuevo'}`)
    }
  } catch (e) {
    console.error('Error saving form:', e)
    alert('Error al enviar los datos del formulario.')
  } finally {
    saving.value = false
  }
}

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
        <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-indigo-500" />
      </div>

      <form v-else class="space-y-4" @submit.prevent="handleSubmit">
        <div v-for="col in columns" :key="col.title_column">
          <!-- Skip if field matches auto-updated date metadata -->
          <div v-if="!col.title_column.startsWith('date_') && col.title_column !== 'token_admin' && col.title_column !== 'token_exp_admin' && col.title_column !== `id_${moduleConfig.suffix_module}`">
            
            <label class="block text-xs font-semibold text-slate-350 uppercase tracking-wider mb-2">
              {{ col.alias_column || col.title_column }}
            </label>

            <!-- Boolean / Toggle Switch -->
            <div v-if="col.type_column === 'boolean'" class="flex items-center gap-3">
              <USwitch v-model="formModel[col.title_column]" />
              <span class="text-sm text-slate-400">
                {{ formModel[col.title_column] ? 'Activo (ON)' : 'Inactivo (OFF)' }}
              </span>
            </div>

            <!-- Relational Options drop down -->
            <div v-else-if="col.type_column === 'relations'">
              <USelect
                v-model="formModel[col.title_column]"
                :options="selectOptions[col.matrix_column] || []"
                class="w-full bg-slate-950 border border-slate-800 rounded-lg text-white"
                placeholder="Seleccionar opción..."
              />
            </div>

            <!-- Enum Select dropdown -->
            <div v-else-if="col.type_column === 'select'">
              <USelect
                v-model="formModel[col.title_column]"
                :options="selectOptions[col.title_column] || []"
                class="w-full bg-slate-950 border border-slate-800 rounded-lg text-white capitalize"
              />
            </div>

            <!-- Double/Money/Int numbers input -->
            <div v-else-if="['money', 'double', 'int', 'order'].includes(col.type_column)">
              <UInput
                v-model.number="formModel[col.title_column]"
                type="number"
                step="any"
                class="w-full"
              />
            </div>

            <!-- Password type -->
            <div v-else-if="col.type_column === 'password'">
              <UInput
                v-model="formModel[col.title_column]"
                type="password"
                placeholder="•••••••• (dejar en blanco para mantener la actual)"
                class="w-full"
              />
            </div>

            <!-- Image URL with Preview -->
            <div v-else-if="col.type_column === 'image'" class="space-y-2">
              <UInput
                v-model="formModel[col.title_column]"
                placeholder="URL de la imagen (ej: https://...)"
                class="w-full"
              />
              <div v-if="formModel[col.title_column]" class="mt-2 flex items-center justify-start border border-slate-800 rounded-lg p-2 bg-slate-950/40 w-max">
                <img :src="formModel[col.title_column]" class="w-16 h-16 rounded object-cover" />
              </div>
            </div>

            <!-- Text Area for descriptions -->
            <div v-else-if="col.title_column.includes('description') || col.title_column.includes('notes')">
              <UTextarea
                v-model="formModel[col.title_column]"
                rows="3"
                class="w-full"
              />
            </div>

            <!-- Text / Default Input -->
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
    <div class="p-4 border-t border-slate-800 flex justify-end gap-3 bg-slate-950">
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
