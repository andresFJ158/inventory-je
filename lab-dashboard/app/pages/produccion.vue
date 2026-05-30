<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

const productions = ref<any[]>([])
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

// Estado para el modal de Iniciar Producción
const isCreateOpen = ref(false)
const recipes = ref<any[]>([])
const loadingRecipes = ref(false)

const newProduction = ref({
  id_recipe: '',
  total_qty: '',
  cif: '0.00',
  mo: '0.00'
})

const selectedRecipe = computed(() => {
  return recipes.value.find(r => String(r.id) === String(newProduction.value.id_recipe))
})

const scaleFactor = computed(() => {
  if (!selectedRecipe.value || !newProduction.value.total_qty) return 0
  const qty = parseFloat(newProduction.value.total_qty)
  const base = parseFloat(selectedRecipe.value.batch_size) || 1
  return qty / base
})

async function fetchRecipes() {
  loadingRecipes.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabRecipes: 'ok',
        id_office: String(auth.officeId || 6)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      recipes.value = data.results.map((r: any) => ({
        id: r.id_recipe,
        id_product: r.id_product_recipe,
        name: r.title_product,
        batch_size: parseFloat(r.batch_size_recipe) || 1,
        unit: r.unit_batch_recipe || 'u'
      }))
    }
  } catch (error) {
    console.error('Error fetching recipes:', error)
  } finally {
    loadingRecipes.value = false
  }
}

async function fetchProductions() {
  loading.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabProductions: 'ok',
        id_office: String(auth.officeId || 6)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      productions.value = data.results.map((p: any) => ({
        id: p.id_production,
        recipe: p.name_product || 'Fórmula Compuesta',
        qty: parseFloat(p.total_qty_production) || 0,
        date: p.start_date_production || p.date_created_production,
        status: p.status_production || 'pendiente',
        id_recipe: p.id_recipe_production,
        batches: p.batches_production,
        id_product: p.id_product_production,
        unit: p.unit_batch_recipe || 'L',
        qty_packaged: parseFloat(p.qty_packaged_production) || 0,
        qty_approved: parseFloat(p.qty_approved_production) || 0,
        qty_rejected: parseFloat(p.qty_rejected_production) || 0,
        result_qc: p.result_qc_production || '',
        notes_qc: p.notes_qc_production || ''
      }))
    } else {
      productions.value = []
    }
  } catch (error) {
    console.error('Error fetching productions:', error)
    productions.value = []
  } finally {
    loading.value = false
  }
}

// ------ FASE DE ENVASADO Y FINALIZACIÓN ------
const isPkgOpen = ref(false)
const materials = ref<any[]>([])
const loadingMaterials = ref(false)

const pkgForm = ref({
  id_production: '',
  id_recipe: '',
  batches: '',
  id_product: '',
  recipe_name: '',
  total_qty: 0,
  bulk_unit: 'L',

  yield_type: 'same', // 'same' | 'diff'
  real_bulk_qty: '',

  envase_type: 'botellas',
  volume: '',
  unit: 'ml',
  final_name: '',

  extra_mats: [] as Array<{ id_raw: string; qty: string }>,
  extra_mo: '0.00',
  extra_cif: '0.00'
})

async function fetchMaterials() {
  loadingMaterials.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabMaterials: 'ok',
        id_office: String(auth.officeId || 6)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      materials.value = data.results
    }
  } catch (error) {
    console.error('Error fetching packaging materials:', error)
  } finally {
    loadingMaterials.value = false
  }
}

const calculatedEnvases = computed(() => {
  const qty = pkgForm.value.yield_type === 'diff' ? (parseFloat(pkgForm.value.real_bulk_qty) || 0) : pkgForm.value.total_qty
  const vol = parseFloat(pkgForm.value.volume) || 0
  if (vol <= 0) return 0

  let volInBase = vol
  const baseUnit = pkgForm.value.bulk_unit
  const envUnit = pkgForm.value.unit

  if (baseUnit === 'L' && envUnit === 'ml') {
    volInBase = vol / 1000
  } else if (baseUnit === 'kg' && envUnit === 'g') {
    volInBase = vol / 1000
  } else if (baseUnit === 'ml' && envUnit === 'L') {
    volInBase = vol * 1000
  } else if (baseUnit === 'g' && envUnit === 'kg') {
    volInBase = vol * 1000
  }

  return Math.floor(qty / volInBase)
})

watch([() => pkgForm.value.volume, () => pkgForm.value.unit, () => pkgForm.value.recipe_name], () => {
  if (pkgForm.value.volume && pkgForm.value.recipe_name) {
    const base = pkgForm.value.recipe_name.replace(/a granel/ig, '').trim()
    pkgForm.value.final_name = `${base} ${pkgForm.value.volume}${pkgForm.value.unit}`
  }
})

watch(() => pkgForm.value.bulk_unit, (newUnit) => {
  if (newUnit === 'L') {
    pkgForm.value.unit = 'ml'
  } else if (newUnit === 'kg') {
    pkgForm.value.unit = 'g'
  } else {
    pkgForm.value.unit = 'und'
  }
})

async function openPkgModal(prod: any) {
  await fetchMaterials()
  
  pkgForm.value = {
    id_production: String(prod.id),
    id_recipe: String(prod.id_recipe),
    batches: String(prod.batches),
    id_product: String(prod.id_product),
    recipe_name: prod.recipe,
    total_qty: prod.qty,
    bulk_unit: prod.unit || 'L',
    yield_type: 'same',
    real_bulk_qty: '',
    envase_type: 'botellas',
    volume: '',
    unit: prod.unit === 'L' ? 'ml' : prod.unit === 'kg' ? 'g' : 'und',
    final_name: '',
    extra_mats: [],
    extra_mo: '0.00',
    extra_cif: '0.00'
  }
  
  isPkgOpen.value = true
}

function addPkgMaterial() {
  pkgForm.value.extra_mats.push({
    id_raw: '',
    qty: String(calculatedEnvases.value || 0)
  })
}

function removePkgMaterial(index: number) {
  pkgForm.value.extra_mats.splice(index, 1)
}

async function submitPackaging() {
  // Validaciones obligatorias de Mano de Obra
  if (!pkgForm.value.extra_mo || parseFloat(pkgForm.value.extra_mo) < 0) {
    alert('El costo de mano de obra (MO) de envasado es obligatorio y debe ser mayor o igual a 0.')
    return
  }

  const final_name = pkgForm.value.final_name.trim()
  const final_qty = calculatedEnvases.value

  if (!final_name || final_qty <= 0) {
    alert('Ingresa el volumen por envase y el nombre del producto final.')
    return
  }

  // Filtrar insumos válidos
  const extra_mats = pkgForm.value.extra_mats.filter(m => m.id_raw && parseFloat(m.qty) > 0)

  try {
    const isYieldDiff = pkgForm.value.yield_type === 'diff'
    const real_bulk_qty = isYieldDiff ? pkgForm.value.real_bulk_qty : null

    const payload = new URLSearchParams()
    payload.append('completeProduction', 'ok')
    payload.append('id_production', pkgForm.value.id_production)
    payload.append('id_recipe', pkgForm.value.id_recipe)
    payload.append('batches', pkgForm.value.batches)
    payload.append('id_product', pkgForm.value.id_product)
    payload.append('extra_mats', JSON.stringify(extra_mats))
    payload.append('extra_mo', pkgForm.value.extra_mo)
    payload.append('extra_cif', pkgForm.value.extra_cif)
    payload.append('pkg_final_qty', String(final_qty))
    payload.append('pkg_final_name', final_name)
    payload.append('pkg_envase_type', pkgForm.value.envase_type)
    payload.append('id_office', String(auth.officeId || 6))
    payload.append('real_bulk_qty', real_bulk_qty !== null ? String(real_bulk_qty) : '')
    payload.append('original_bulk_qty', String(pkgForm.value.total_qty))

    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: payload.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText === 'ok') {
      isPkgOpen.value = false
      await fetchProductions()
    } else if (resText.includes('stock_insuficiente')) {
      const parts = resText.split('|')
      const itemName = parts[1] || 'Materia Prima'
      alert(`Stock Insuficiente: No hay suficiente inventario de envases/materiales: ${itemName}`)
    } else {
      alert('Error al completar producción: ' + resText)
    }
  } catch (error: any) {
    console.error('Error completing production:', error)
    alert('Error de red al completar producción: ' + (error.message || error))
  }
}

const isDetailsOpen = ref(false)
const detailsLoading = ref(false)
const detailsData = ref<any>(null)

async function openDetailsModal(prodId: number) {
  detailsLoading.value = true
  isDetailsOpen.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getProductionDetails: 'ok',
        id_production: String(prodId)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    detailsData.value = data
  } catch (error) {
    console.error('Error fetching production details:', error)
    detailsData.value = null
  } finally {
    detailsLoading.value = false
  }
}

async function handleOpenCreateModal() {
  await fetchRecipes()
  newProduction.value = {
    id_recipe: '',
    total_qty: '',
    cif: '0.00',
    mo: ''
  }
  isCreateOpen.value = true
}

async function handleSaveProduction() {
  if (!newProduction.value.id_recipe || !newProduction.value.total_qty) {
    alert('Completa los campos obligatorios.')
    return
  }

  // INC-09: Mano de obra obligatoria y mayor a 0
  if (!newProduction.value.mo || parseFloat(newProduction.value.mo) <= 0) {
    alert('El costo de mano de obra (MO) es obligatorio para iniciar la producción y debe ser mayor a 0.')
    return
  }

  try {
    const body = new URLSearchParams()
    body.append('saveProduction', 'ok')
    body.append('id_recipe', newProduction.value.id_recipe)
    body.append('id_product', String(selectedRecipe.value?.id_product || ''))
    body.append('batches', String(scaleFactor.value))
    body.append('total_qty', newProduction.value.total_qty)
    body.append('cif', newProduction.value.cif)
    body.append('mo', newProduction.value.mo)
    body.append('id_office', String(auth.officeId || 6))
    body.append('id_admin', String(auth.user?.id_admin || 1))

    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: body.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText === 'ok') {
      isCreateOpen.value = false
      await fetchProductions()
    } else if (resText.startsWith('stock_insuficiente')) {
      const parts = resText.split('|')
      alert(`Stock insuficiente: No hay suficiente stock de "${parts[1] || 'materia prima'}" para esta producción.`)
    } else {
      alert('Error al iniciar producción: ' + resText)
    }
  } catch (error) {
    console.error('Error creating production:', error)
    alert('Error al conectar con el servidor.')
  }
}

onMounted(() => {
  fetchProductions()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
          <UIcon
            name="i-lucide-cog"
            class="text-green-500"
          />
          Producción de Laboratorio
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
          Monitoreo y ejecución de producción de compuestos del laboratorio.
        </p>
      </div>
      <UButton
        v-if="auth.role !== 'lab_calidad'"
        icon="i-lucide-plus"
        color="green"
        size="md"
        class="font-bold!"
        @click="handleOpenCreateModal"
      >
        Nueva Producción
      </UButton>
    </div>

    <!-- Tabla -->
    <div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm">
      <div class="p-5 border-b border-slate-200 dark:border-slate-800/80">
        <h3 class="font-bold text-slate-800 dark:text-white tracking-wide">
          Órdenes de Producción y Estado
        </h3>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="p-8 text-center text-slate-500 dark:text-slate-400">
          <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2" />
          Cargando producciones desde la base de datos...
        </div>
        <div v-else-if="productions.length === 0" class="text-center p-8 text-slate-500">
          No hay órdenes de producción en curso.
        </div>
        <table v-else class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
          <thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800/80">
            <tr>
              <th class="px-6 py-4">ID Producción</th>
              <th class="px-6 py-4">Producto</th>
              <th class="px-6 py-4">Cantidad</th>
              <th class="px-6 py-4">Fecha</th>
              <th class="px-6 py-4">Estado</th>
              <th v-if="auth.role !== 'lab_calidad'" class="px-6 py-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
            <tr v-for="prod in productions" :key="prod.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-all duration-150">
              <td class="px-6 py-4 font-mono font-bold text-slate-600 dark:text-slate-300">#{{ prod.id }}</td>
              <td class="px-6 py-4 font-bold text-slate-800 dark:text-white uppercase">{{ prod.recipe }}</td>
              <td class="px-6 py-4 font-mono text-xs">
                <div class="font-bold text-sm text-slate-700 dark:text-slate-200">
                  {{ prod.qty }} <span class="text-xs text-slate-500 font-normal">{{ prod.unit }}</span>
                </div>
                <div v-if="prod.qty_packaged > 0" class="mt-1 space-y-0.5 border-t border-slate-100 dark:border-slate-800/40 pt-1 text-slate-400">
                  <div>Envasado: <strong class="text-slate-650 dark:text-slate-350">{{ prod.qty_packaged }} und</strong></div>
                  <div v-if="prod.qty_approved > 0" class="text-emerald-600 dark:text-emerald-400 font-medium">
                    Aprobadas: <strong>{{ prod.qty_approved }} und</strong>
                  </div>
                  <div v-if="prod.qty_rejected > 0" class="text-rose-500 dark:text-rose-400 font-semibold flex items-center gap-0.5">
                    <UIcon name="i-lucide-alert-circle" class="w-3 h-3" /> Merma (QC): <strong>{{ prod.qty_rejected }} und</strong>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ prod.date }}</td>
              <td class="px-6 py-4">
                <span v-if="prod.status === 'completado'" class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 border border-emerald-500/20">
                  <UIcon name="i-lucide-check-circle" class="w-3.5 h-3.5" /> Completado (Listo en Almacén)
                </span>
                <span v-else-if="prod.status === 'pendiente_qc'" class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20">
                  <UIcon name="i-lucide-shield-alert" class="w-3.5 h-3.5" /> Envasado (En Control Calidad)
                </span>
                <span v-else-if="prod.status === 'rechazado'" class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                  <UIcon name="i-lucide-x-circle" class="w-3.5 h-3.5" /> Rechazado por QC (Merma)
                </span>
                <span v-else-if="prod.status === 'en_proceso' || prod.status === 'proceso'" class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-blue-500/10 text-blue-600 dark:text-blue-300 border border-blue-500/20 animate-pulse">
                  <UIcon name="i-lucide-loader-2" class="w-3.5 h-3.5 animate-spin" /> Fabricación en Proceso
                </span>
                <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/20">
                  <UIcon name="i-lucide-hourglass" class="w-3.5 h-3.5" /> Pendiente de Envasar
                </span>
              </td>
              <td v-if="auth.role !== 'lab_calidad'" class="px-6 py-4 text-center flex justify-center items-center gap-2">
                <UButton v-if="prod.status === 'proceso' || prod.status === 'pendiente' || prod.status === 'en_proceso'" label="Producción Finalizada" color="green" size="xs" class="font-bold!" @click="openPkgModal(prod)" />
                <span v-else class="text-xs font-bold uppercase" :class="prod.status === 'pendiente_qc' ? 'text-indigo-500' : prod.status === 'rechazado' ? 'text-rose-500' : 'text-slate-400 dark:text-slate-500'">{{ prod.status === 'pendiente_qc' ? 'En QC' : prod.status === 'rechazado' ? 'Rechazado' : 'Listo' }}</span>
                <UButton icon="i-lucide-eye" color="blue" size="xs" variant="ghost" class="font-bold!" @click="openDetailsModal(prod.id)" title="Ver Historial y Detalles" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Iniciar Producción (UModal v-model:open) -->
    <UModal v-model:open="isCreateOpen">
      <template #content>
        <div class="w-full max-w-xl md:max-w-2xl p-6 space-y-4 text-slate-900 dark:text-white bg-white dark:bg-slate-950 border border-slate-205 dark:border-slate-800 rounded-xl shadow-2xl">
          <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-green-600 dark:text-green-400">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-plus" class="w-5 h-5" /> Nueva Orden de Producción
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isCreateOpen = false" />
          </div>

          <form class="space-y-4" @submit.prevent="handleSaveProduction">
            <!-- Receta / Fórmula -->
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Producto a Producir</label>
              <select v-model="newProduction.id_recipe" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
                <option value="">Seleccione...</option>
                <option v-for="r in recipes" :key="r.id" :value="r.id">
                  {{ r.name }}
                </option>
              </select>
            </div>

            <!-- Cantidad total a granel -->
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Cantidad Total a Producir (A Granel)</label>
              <div class="relative rounded-lg shadow-sm">
                <input v-model="newProduction.total_qty" type="number" step="0.01" :disabled="!newProduction.id_recipe" :placeholder="newProduction.id_recipe ? '0.00' : 'Elige una receta primero'" class="block w-full py-2.5 px-3 pr-12 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-50">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500 text-sm font-bold">
                  {{ selectedRecipe?.unit || '--' }}
                </div>
              </div>
              <p v-if="scaleFactor > 0" class="mt-2 text-xs text-green-600 dark:text-green-400 font-semibold">
                Factor de Escala: {{ scaleFactor.toFixed(2) }}x. Los ingredientes se multiplicarán adecuadamente.
              </p>
            </div>

            <!-- Costo Indirecto Total -->
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                Costo Indirecto de Fabricación (CIF en Bs)
                <UIcon v-if="auth.role !== 'lab_admin'" name="i-lucide-lock" class="text-amber-500 w-3 h-3 animate-bounce" />
              </label>
              <input v-model="newProduction.cif" type="number" step="0.01" :disabled="auth.role !== 'lab_admin'" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-60 disabled:cursor-not-allowed">
            </div>

            <!-- Mano de Obra Estimada -->
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                Mano de Obra Estimada (Bs) <span class="text-red-500 font-bold">*</span>
                <UIcon v-if="auth.role !== 'lab_admin'" name="i-lucide-lock" class="text-amber-500 w-3 h-3 animate-bounce" />
              </label>
              <input v-model="newProduction.mo" type="number" step="0.01" :disabled="auth.role !== 'lab_admin'" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-60 disabled:cursor-not-allowed" placeholder="Ingrese el costo de mano de obra (Obligatorio)" required>
            </div>

            <!-- Footer integrado directamente sin márgenes negativos -->
            <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isCreateOpen = false" />
              <UButton type="submit" label="Iniciar Producción" color="green" class="font-bold!" />
            </div>
          </form>
        </div>
      </template>
    </UModal>

    <!-- Modal Envasado y Finalización (UModal v-model:open) -->
    <UModal v-model:open="isPkgOpen">
      <template #content>
        <div class="w-full max-w-2xl lg:max-w-3xl p-6 bg-white dark:bg-slate-900 text-slate-900 dark:text-white rounded-xl shadow-2xl space-y-5 border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto">
          <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-green-600 dark:text-green-400">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-boxes" class="w-6 h-6 animate-pulse" /> Fase de Envasado y Finalización
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isPkgOpen = false" />
          </div>

          <!-- Ficha de la producción -->
          <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-200 dark:border-slate-850">
            <div>
              <p class="text-xs text-slate-400 uppercase font-bold">Total Producido (A Granel)</p>
              <p class="text-lg font-black text-green-600 dark:text-green-400 mt-1">
                {{ pkgForm.total_qty }} {{ pkgForm.bulk_unit }}
              </p>
            </div>
            <div class="text-right">
              <p class="text-xs text-slate-400 uppercase font-bold">Envases Calculados</p>
              <p class="text-lg font-black text-blue-600 dark:text-blue-400 mt-1">
                {{ calculatedEnvases }} <span class="text-xs font-normal text-slate-500">Unidades</span>
              </p>
            </div>
          </div>

          <!-- SECCIÓN: Rendimiento Real / Merma -->
          <div class="border border-slate-200 dark:border-slate-800 rounded-xl p-4 bg-slate-50/50 dark:bg-slate-950/20 space-y-3">
            <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
              <UIcon name="i-lucide-scale" class="w-4 h-4 text-green-500" /> Resultado del Proceso de Elaboración
            </h4>
            <p class="text-xs text-slate-400">Rendimiento esperado: <strong class="text-green-600 dark:text-green-400">{{ pkgForm.total_qty }} {{ pkgForm.bulk_unit }}</strong></p>
            
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 text-sm">
              <label class="flex items-center gap-2 cursor-pointer font-medium">
                <input type="radio" v-model="pkgForm.yield_type" value="same" class="accent-green-600">
                Rendimiento óptimo (Obtenido exactamente lo esperado)
              </label>
              <label class="flex items-center gap-2 cursor-pointer font-medium">
                <input type="radio" v-model="pkgForm.yield_type" value="diff" class="accent-green-600">
                Rendimiento variable (Obtenido cantidad diferente)
              </label>
            </div>

            <div v-if="pkgForm.yield_type === 'diff'" class="pt-2 border-t border-slate-200 dark:border-slate-800">
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Cantidad Real Obtenida</label>
              <div class="relative rounded-lg shadow-sm w-48">
                <input v-model="pkgForm.real_bulk_qty" type="number" step="0.01" placeholder="0.00" class="block w-full py-2 px-3 pr-10 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-green-500/50">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                  {{ pkgForm.bulk_unit }}
                </div>
              </div>
            </div>
          </div>

          <!-- SECCIÓN: Tipo, Capacidad y Nombre Final -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Tipo de Empaque</label>
              <select v-model="pkgForm.envase_type" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
                <option value="botellas">Botellas</option>
                <option value="frascos">Frascos</option>
                <option value="bolsas">Bolsas</option>
                <option value="cajas">Cajas</option>
                <option value="galones">Galones</option>
                <option value="unidades">Unidades Sueltas</option>
              </select>
            </div>
            
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Capacidad del Envase</label>
              <div class="flex">
                <input v-model="pkgForm.volume" type="number" step="1" placeholder="Ej: 500" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-l-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
                <select v-model="pkgForm.unit" class="py-2.5 px-2 bg-slate-100 dark:bg-slate-900 border-y border-r border-slate-200 dark:border-slate-800 rounded-r-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 w-24">
                  <option v-if="pkgForm.bulk_unit === 'L'" value="ml">ml</option>
                  <option v-if="pkgForm.bulk_unit === 'L'" value="L">L</option>
                  <option v-if="pkgForm.bulk_unit === 'kg'" value="g">g</option>
                  <option v-if="pkgForm.bulk_unit === 'kg'" value="kg">kg</option>
                  <option value="und">und</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Nombre a Inventario</label>
              <input v-model="pkgForm.final_name" type="text" placeholder="Ej: Jabón Líquido 500ml" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
            </div>
          </div>

          <!-- SECCIÓN: Materiales de Envasado -->
          <div class="space-y-3">
            <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-2">
              <h4 class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">Insumos/Materiales de Envasado</h4>
              <UButton icon="i-lucide-plus" label="Añadir Insumo" color="green" variant="ghost" size="xs" @click="addPkgMaterial" />
            </div>

            <div class="space-y-3 max-h-[25vh] overflow-y-auto pr-1">
              <div v-if="pkgForm.extra_mats.length === 0" class="text-xs text-slate-550 dark:text-slate-400 py-4 text-center">
                Ningún material de envasado seleccionado. Puedes añadir botellas, cajas o etiquetas desde el catálogo.
              </div>
              <div v-else v-for="(mat, idx) in pkgForm.extra_mats" :key="idx" class="flex items-center gap-3 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-lg border border-slate-200 dark:border-slate-850">
                <!-- Select Insumo -->
                <select v-model="mat.id_raw" @change="mat.qty = String(calculatedEnvases || 0)" class="flex-1 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500">
                  <option value="">Seleccione Insumo...</option>
                  <option v-for="m in materials" :key="m.id_raw_material" :value="m.id_raw_material">
                    {{ m.name_raw_material }} ({{ m.unit_raw_material }})
                  </option>
                </select>

                <!-- Cantidad -->
                <input v-model="mat.qty" type="number" step="0.01" placeholder="0" class="w-24 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500">

                <!-- Botón eliminar -->
                <UButton icon="i-lucide-trash" color="red" variant="ghost" size="xs" @click="removePkgMaterial(idx)" />
              </div>
            </div>
          </div>

          <!-- SECCIÓN: Mano de obra y CIF de envasado -->
          <div class="row grid grid-cols-2 gap-4 border-t border-slate-200 dark:border-slate-800 pt-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                Mano de Obra Extra (Envasado)
                <UIcon v-if="auth.role !== 'lab_admin'" name="i-lucide-lock" class="text-amber-500 w-3 h-3 animate-bounce" />
              </label>
              <input v-model="pkgForm.extra_mo" type="number" step="0.01" :disabled="auth.role !== 'lab_admin'" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-60 disabled:cursor-not-allowed">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                Costos Indirectos Extra (Energía, etc.)
                <UIcon v-if="auth.role !== 'lab_admin'" name="i-lucide-lock" class="text-amber-500 w-3 h-3 animate-bounce" />
              </label>
              <input v-model="pkgForm.extra_cif" type="number" step="0.01" :disabled="auth.role !== 'lab_admin'" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-60 disabled:cursor-not-allowed">
            </div>
          </div>

          <!-- Footer: Botones de acción del modal envasado -->
          <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-2">
            <UButton label="Cancelar" variant="ghost" color="neutral" @click="isPkgOpen = false" />
            <UButton label="Confirmar Envasado y Enviar a QC" color="green" class="font-bold!" @click="submitPackaging" />
          </div>
        </div>
      </template>
    </UModal>

    <!-- Modal Detalle e Historial de Producción (UModal v-model:open) -->
    <UModal v-model:open="isDetailsOpen">
      <template #content>
        <div class="w-full max-w-2xl lg:max-w-3xl p-6 bg-white dark:bg-slate-900 text-slate-900 dark:text-white rounded-xl shadow-2xl space-y-6 border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto">
          <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-blue-600 dark:text-blue-400">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-file-text" class="w-6 h-6" /> Detalle Histórico de Producción #{{ detailsData?.production?.id_production }}
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isDetailsOpen = false" />
          </div>

          <div v-if="detailsLoading" class="p-12 text-center text-slate-500">
            <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-blue-500 mb-2" />
            Cargando historial completo de producción...
          </div>
          
          <div v-else-if="detailsData" class="space-y-6">
            <!-- Ficha General -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-200 dark:border-slate-850">
              <div class="space-y-1 text-sm">
                <div><span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Producto:</span> <strong class="text-slate-800 dark:text-white uppercase ms-1">{{ detailsData.production?.title_product }}</strong></div>
                <div><span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Estado Lote:</span> <span class="uppercase font-bold text-xs ms-1 text-green-600 dark:text-green-400">{{ detailsData.production?.status_production }}</span></div>
                <div><span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Fecha Inicio:</span> <span class="text-slate-600 dark:text-slate-350 ms-1">{{ detailsData.production?.start_date_production || detailsData.production?.date_created_production }}</span></div>
              </div>
              <div class="space-y-1 text-sm md:text-right">
                <div><span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Escala (Factor):</span> <strong class="text-slate-800 dark:text-white ms-1">{{ detailsData.production?.batches_production }}x</strong></div>
                <div><span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Esperado a Granel:</span> <strong class="text-indigo-600 dark:text-indigo-400 ms-1">{{ detailsData.production?.total_qty_production }} {{ detailsData.production?.unit_product }}</strong></div>
                <div v-if="detailsData.production?.real_bulk_qty"><span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Real Obtenido:</span> <strong class="text-emerald-600 dark:text-emerald-400 ms-1">{{ detailsData.production?.real_bulk_qty }} {{ detailsData.production?.unit_product }}</strong></div>
              </div>
            </div>

            <!-- Ficha de Desviación / Merma Bulk si existe -->
            <div v-if="detailsData.production?.real_bulk_qty" class="p-3 bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-300 rounded-lg text-xs font-bold flex justify-between">
              <span>Variación de Proceso Bulk (Fabricación):</span>
              <span>
                {{ detailsData.production?.yield_variance >= 0 ? '+' : '' }}{{ parseFloat(detailsData.production?.yield_variance).toFixed(2) }} {{ detailsData.production?.unit_product }} 
                ({{ detailsData.production?.yield_variance >= 0 ? '+' : '' }}{{ parseFloat(detailsData.production?.yield_variance_pct).toFixed(1) }}%)
              </span>
            </div>

            <!-- Ficha de Control de Calidad si aplica -->
            <div v-if="detailsData.production?.result_qc" class="border border-slate-200 dark:border-slate-800 rounded-xl p-4 bg-slate-50/50 dark:bg-slate-950/20 space-y-3">
              <h4 class="text-xs font-black text-slate-550 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 dark:border-slate-800 pb-2">
                <UIcon name="i-lucide-shield-check" class="text-green-500 w-4 h-4" /> Control de Calidad & Liberación Final
              </h4>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs font-mono">
                <div>
                  <p class="text-slate-400 font-sans font-bold">Unidades Evaluadas</p>
                  <p class="text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5">{{ parseFloat(detailsData.production?.qty_packaged_production || detailsData.production?.total_qty_production).toLocaleString() }} und</p>
                </div>
                <div>
                  <p class="text-emerald-600 font-sans font-bold">✅ Aprobadas</p>
                  <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ parseFloat(detailsData.production?.qty_approved_qc).toLocaleString() }} und</p>
                </div>
                <div>
                  <p class="text-rose-500 font-sans font-bold">❌ Rechazadas (Merma)</p>
                  <p class="text-sm font-bold text-rose-500 dark:text-rose-400 mt-0.5">{{ parseFloat(detailsData.production?.qty_rejected_qc).toLocaleString() }} und</p>
                </div>
                <div>
                  <p class="text-slate-400 font-sans font-bold">Resultado / Inspector</p>
                  <p class="text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5 uppercase">{{ detailsData.production?.result_qc }}</p>
                  <p class="text-xxs text-slate-400 font-sans mt-0.5">{{ detailsData.production?.qc_inspector_name }}</p>
                </div>
              </div>
              <div v-if="detailsData.production?.qc_notes" class="mt-3 p-3 bg-slate-100 dark:bg-slate-950 border-l-4 border-l-amber-500 rounded text-xs text-slate-600 dark:text-slate-400 italic">
                <strong>Observaciones inspector:</strong> "{{ detailsData.production?.qc_notes }}"
              </div>
            </div>

            <!-- Insumos Consumidos (Detalle de Ingredientes y Envases) -->
            <div class="space-y-2">
              <h4 class="text-xs font-black text-slate-550 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-2">
                Insumos & Materiales Consumidos de Inventario
              </h4>
              <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-lg">
                <table class="w-full text-left text-xs text-slate-650 dark:text-slate-350">
                  <thead class="bg-slate-50 dark:bg-slate-900/60 font-bold uppercase tracking-wider text-slate-500 dark:text-slate-450 border-b border-slate-200 dark:border-slate-800/80">
                    <tr>
                      <th class="px-4 py-2.5">Insumo / Materia Prima</th>
                      <th class="px-4 py-2.5 text-right">Cantidad Usada</th>
                      <th class="px-4 py-2.5 text-right">Costo Unitario</th>
                      <th class="px-4 py-2.5 text-right">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-150 dark:divide-slate-800 font-mono">
                    <tr v-for="m in detailsData.materials" :key="m.id_production_mat_cost" class="hover:bg-slate-50 dark:hover:bg-slate-800/10">
                      <td class="px-4 py-2.5 font-sans font-semibold text-slate-800 dark:text-white uppercase">{{ m.name_raw_material }}</td>
                      <td class="px-4 py-2.5 text-right font-bold text-slate-700 dark:text-slate-300">
                        {{ parseFloat(m.qty_used_mat_cost).toLocaleString() }}
                        <span class="text-xxs text-slate-500 font-normal">{{ m.unit_raw_material }}</span>
                      </td>
                      <td class="px-4 py-2.5 text-right text-slate-500">Bs {{ parseFloat(m.unit_price_at_production).toFixed(2) }}</td>
                      <td class="px-4 py-2.5 text-right font-bold text-slate-800 dark:text-white">Bs {{ parseFloat(m.total_cost_mat_cost).toFixed(2) }}</td>
                    </tr>
                    <tr v-if="!detailsData.materials || detailsData.materials.length === 0">
                      <td colspan="4" class="px-4 py-6 text-center text-slate-500 font-sans italic">
                        No hay insumos consumidos registrados para esta orden.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Hoja de Costos y Rentabilidad -->
            <div class="row grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-200 dark:border-slate-800 pt-4">
              <div class="bg-slate-50 dark:bg-slate-950/20 p-4 rounded-xl border border-slate-200 dark:border-slate-805 space-y-2 text-xs font-mono">
                <h5 class="font-sans font-bold text-slate-700 dark:text-slate-300 border-b pb-1.5 mb-2">Desglose de Costos de Manufactura</h5>
                <div class="flex justify-between">
                  <span>Costo Mano de Obra (Elaboración):</span>
                  <strong>Bs {{ parseFloat(detailsData.production?.real_labor_cost || detailsData.production?.proj_labor_cost || 0).toFixed(2) }}</strong>
                </div>
                <div class="flex justify-between text-blue-600 dark:text-blue-400">
                  <span>Costo Mano de Obra (Envasado):</span>
                  <strong>Bs {{ parseFloat(detailsData.production?.pkg_labor_cost || 0).toFixed(2) }}</strong>
                </div>
                <div class="flex justify-between">
                  <span>CIF (Elaboración):</span>
                  <strong>Bs {{ parseFloat(detailsData.production?.real_indirect_cost || detailsData.production?.proj_indirect_cost || 0).toFixed(2) }}</strong>
                </div>
                <div class="flex justify-between text-blue-600 dark:text-blue-400">
                  <span>CIF (Envasado):</span>
                  <strong>Bs {{ parseFloat(detailsData.production?.pkg_indirect_cost || 0).toFixed(2) }}</strong>
                </div>
              </div>

              <div class="bg-slate-50 dark:bg-slate-950/20 p-4 rounded-xl border border-slate-200 dark:border-slate-805 space-y-2 text-xs font-mono flex flex-col justify-between">
                <div>
                  <h5 class="font-sans font-bold text-slate-700 dark:text-slate-300 border-b pb-1.5 mb-2">Resumen Financiero del Lote</h5>
                  <div class="flex justify-between">
                    <span>Costo Total Lote:</span>
                    <strong class="text-indigo-600 dark:text-indigo-400 text-sm">Bs {{ parseFloat(detailsData.production?.real_total_cost || 0).toFixed(2) }}</strong>
                  </div>
                </div>
                <div class="border-t pt-2 mt-2">
                  <div class="flex justify-between">
                    <span class="font-sans font-bold text-slate-700 dark:text-slate-300">Costo Real Unitario:</span>
                    <strong class="text-emerald-600 dark:text-emerald-400 text-base">Bs {{ parseFloat(detailsData.production?.real_unit_cost || 0).toFixed(2) }}</strong>
                  </div>
                  <p class="text-xxs text-slate-400 font-sans mt-1">Calculado sobre las unidades finales reales aprobadas por Control de Calidad.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6">
            <UButton label="Cerrar Detalles" color="neutral" @click="isDetailsOpen = false" />
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>
