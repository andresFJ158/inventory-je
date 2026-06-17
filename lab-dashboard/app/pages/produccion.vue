<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useNumericInput } from '~/composables/useNumericInput'

const auth = useAuthStore()
const toast = useToast()

const confirmDialog = ref({ open: false, title: '', message: '' })
const confirmResolve = ref<((v: boolean) => void) | null>(null)

function confirmAction(title: string, message: string): Promise<boolean> {
  return new Promise(resolve => {
    confirmDialog.value = { open: true, title, message }
    confirmResolve.value = resolve
  })
}
function onConfirmOk() {
  confirmResolve.value?.(true)
  confirmDialog.value.open = false
}
function onConfirmCancel() {
  confirmResolve.value?.(false)
  confirmDialog.value.open = false
}

const isAdmin = computed(() => ['superadmin', 'admin', 'lab_admin'].includes(auth.role || ''))

const cleanFloat = (val: any) => {
  if (val === undefined || val === null || val === '') return 0
  return parseFloat(String(val).replace(/\./g, '').replace(/,/g, '.')) || 0
}

const productions = ref<any[]>([])
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

// Estado para el modal de Iniciar Producción
const isCreateOpen = ref(false)
const recipes = ref<any[]>([])
const loadingRecipes = ref(false)

const labSupplies = ref<any[]>([])
async function fetchLabSupplies() {
  try {
    const res = await $fetch<any>('/api/lab_supplies', { headers: { 'Authorization': auth.token || '' } })
    if (res.status === 200) labSupplies.value = res.results.filter((s:any) => parseInt(s.status_supply) === 1)
  } catch (e) {
    console.error('Error fetching lab supplies', e)
  }
}

// Inputs numéricos del modal de crear producción
const totalQtyInput = useNumericInput('', { decimals: 3, min: 0 })
const cifInput = useNumericInput('0', { decimals: 2, min: 0 })
const moInput = useNumericInput('0', { decimals: 2, min: 0 })

const newProduction = ref({
  id_recipe: ''
})

const selectedRecipe = computed(() => {
  return recipes.value.find(r => String(r.id) === String(newProduction.value.id_recipe))
})

const scaleFactor = computed(() => {
  if (!selectedRecipe.value || !totalQtyInput.raw.value) return 0
  const base = parseFloat(selectedRecipe.value.batch_size) || 1
  return totalQtyInput.raw.value / base
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
        unit: r.unit_batch_recipe || 'u',
        // Costo por unidad: real si existe QC, si no el estimado por ingredientes
        cost_unit: parseFloat(r.cost_real) > 0 ? parseFloat(r.cost_real) : parseFloat(r.cost_estimated) || 0,
        cost_batch: r.ingredients
          ? r.ingredients.reduce((acc: number, ing: any) => acc + (parseFloat(ing.qty_ingredient) || 0) * (parseFloat(ing.unit_price_mp) || 0), 0)
          : 0,
        has_prices: r.ingredients ? r.ingredients.some((ing: any) => parseFloat(ing.unit_price_mp) > 0) : false
      }))
    }
  } catch (error) {
    console.error('Error fetching recipes:', error)
  } finally {
    loadingRecipes.value = false
  }
}

// CIF = 10% del costo total de materiales del lote planificado
// Se recalcula al cambiar receta O cantidad
watch([() => newProduction.value.id_recipe, totalQtyInput.raw], () => {
  if (!selectedRecipe.value) {
    cifInput.setValue(0)
    return
  }
  const batchSize = selectedRecipe.value.batch_size || 1
  const costBatch = selectedRecipe.value.cost_batch || 0
  const qty = totalQtyInput.raw.value
  if (qty > 0 && costBatch > 0) {
    const batches = qty / batchSize
    const totalMpCost = batches * costBatch
    cifInput.setValue(totalMpCost * 0.10)
  } else {
    cifInput.setValue(0)
  }
})

// Al seleccionar una receta, inicializar la cantidad con el tamaño de lote por defecto de la receta
// Esto disparará automáticamente el cálculo del Costo Indirecto de Fabricación (CIF)
watch(() => newProduction.value.id_recipe, (newVal) => {
  if (newVal) {
    const recipe = recipes.value.find(r => String(r.id) === String(newVal))
    if (recipe) {
      totalQtyInput.setValue(recipe.batch_size)
    }
  } else {
    totalQtyInput.setValue(0)
  }
})

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

// Inputs numéricos del modal de envasado
const pkgRealBulkInput = useNumericInput('', { decimals: 3, min: 0 })
const pkgVolumeInput = useNumericInput('', { decimals: 0, min: 0 })
const pkgExtraMoInput = useNumericInput('0', { decimals: 2, min: 0 })
const pkgExtraCifInput = useNumericInput('0', { decimals: 2, min: 0 })

// Helper para cantidad de materiales de envasado (array dinámico)
function blockNegative(e: KeyboardEvent) {
  if (e.key === '-' || e.key === 'e' || e.key === 'E') e.preventDefault()
}

function onPkgMatQtyInput(e: Event, idx: number) {
  const input = e.target as HTMLInputElement
  const raw = input.value.replace(/[^\d]/g, '')
  const n = parseInt(raw, 10) || 0
  if (pkgForm.value.extra_mats[idx]) {
    pkgForm.value.extra_mats[idx].qty = String(n)
  }
  input.value = n > 0 ? n.toLocaleString('de-DE') : ''
}

const pkgForm = ref({
  id_production: '',
  id_recipe: '',
  batches: '',
  id_product: '',
  recipe_name: '',
  total_qty: 0,
  bulk_unit: 'L',

  yield_type: 'same',

  envase_type: 'botellas',
  unit: 'ml',
  final_name: '',
  waste_packaged_qty: '0',
  waste_loss_qty: '0',
  waste_packaged_id_raw: '',
  extra_mats: [] as Array<{ id_raw: string; qty: string }>
})

async function fetchMaterials() {
  loadingMaterials.value = true
  try {
    // Fuente 1: insumos en raw_materials (is_insumo=1)
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabMaterials: 'ok',
        id_office: String(auth.officeId || 6),
        is_insumo: '1'
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    const fromRaw = data.status === 200 ? data.results : []

    // Fuente 2: insumos en lab_supplies (tabla legada)
    let fromLabSupplies: any[] = []
    try {
      const res2 = await $fetch<any>('/api/lab_supplies', {
        headers: { Authorization: auth.token || '' }
      })
      if (res2.status === 200) {
        fromLabSupplies = res2.results
          .filter((s: any) => parseInt(s.status_supply) === 1)
          .map((s: any) => ({
            id_raw_material: `ls_${s.id_supply}`,
            name_raw_material: (s.name_supply || '').replace(/\+/g, ' '),
            unit_raw_material: s.unit_supply || 'und',
            stock_raw_material: parseFloat(s.stock_supply) || 0,
            is_insumo: 1,
            _source: 'lab_supplies',
            id_supply: s.id_supply
          }))
      }
    } catch (_) { /* silently ignore */ }

    // Unificar: raw_materials primero, luego lab_supplies (evitar duplicados por nombre)
    const namesSeen = new Set(fromRaw.map((m: any) => (m.name_raw_material || '').toLowerCase()))
    const unique = fromLabSupplies.filter(s => !namesSeen.has((s.name_raw_material || '').toLowerCase()))
    materials.value = [...fromRaw, ...unique]
  } catch (error) {
    console.error('Error fetching packaging materials:', error)
  } finally {
    loadingMaterials.value = false
  }
}

const calculatedEnvases = computed(() => {
  let qty = pkgForm.value.yield_type === 'diff' ? pkgRealBulkInput.raw.value : cleanFloat(pkgForm.value.total_qty)
  if (pkgForm.value.yield_type === 'diff') {
    qty -= (parseFloat(pkgForm.value.waste_packaged_qty) || 0)
    qty -= (parseFloat(pkgForm.value.waste_loss_qty) || 0)
  }
  const vol = pkgVolumeInput.raw.value
  if (vol <= 0 || qty <= 0) return 0

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

watch([pkgVolumeInput.raw, () => pkgForm.value.unit, () => pkgForm.value.recipe_name], () => {
  if (pkgVolumeInput.raw.value > 0 && pkgForm.value.recipe_name) {
    const base = pkgForm.value.recipe_name.replace(/a granel/ig, '').trim()
    pkgForm.value.final_name = `${base} ${pkgVolumeInput.raw.value}${pkgForm.value.unit}`
  }
})

watch(() => pkgForm.value.bulk_unit, (newUnit) => {
  pkgForm.value.unit = newUnit === 'L' ? 'ml' : newUnit === 'kg' ? 'g' : 'und'
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
    envase_type: 'botellas',
    unit: prod.unit === 'L' ? 'ml' : prod.unit === 'kg' ? 'g' : 'und',
    final_name: '',
    waste_packaged_qty: '0',
    waste_loss_qty: '0',
    waste_packaged_id_raw: '',
    extra_mats: []
  }

  pkgRealBulkInput.reset()
  pkgVolumeInput.reset()
  pkgExtraMoInput.setValue(0)
  pkgExtraCifInput.setValue(0)

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
  const final_name = pkgForm.value.final_name.trim()
  const final_qty = calculatedEnvases.value

  if (!final_name || final_qty <= 0) {
    toast.add({ title: 'Ingresa el volumen por envase (mayor a 0) y el nombre del producto final.', color: 'error' })
    return
  }

  const extra_mats = pkgForm.value.extra_mats
    .filter(m => m.id_raw && parseInt(m.qty) > 0)
    .map(m => ({ id_raw: m.id_raw, qty: String(parseInt(m.qty)) }))

  try {
    const isYieldDiff = pkgForm.value.yield_type === 'diff'
    const real_bulk_qty = isYieldDiff ? pkgRealBulkInput.raw.value : null

    const payload = new URLSearchParams()
    payload.append('completeProduction', 'ok')
    payload.append('id_production', pkgForm.value.id_production)
    payload.append('id_recipe', pkgForm.value.id_recipe)
    payload.append('batches', pkgForm.value.batches)
    payload.append('id_product', pkgForm.value.id_product)
    payload.append('extra_mats', JSON.stringify(extra_mats))
    payload.append('extra_mo', String(pkgExtraMoInput.raw.value))
    payload.append('extra_cif', String(pkgExtraCifInput.raw.value))
    payload.append('pkg_final_qty', String(final_qty))
    payload.append('pkg_final_name', final_name)
    payload.append('pkg_envase_type', pkgForm.value.envase_type)
    payload.append('id_office', String(auth.officeId || 6))
    payload.append('real_bulk_qty', real_bulk_qty !== null ? String(real_bulk_qty) : '')
    payload.append('original_bulk_qty', String(cleanFloat(pkgForm.value.total_qty)))
    payload.append('waste_packaged_qty', String(parseFloat(pkgForm.value.waste_packaged_qty) || 0))
    payload.append('waste_loss_qty', String(parseFloat(pkgForm.value.waste_loss_qty) || 0))
    payload.append('waste_packaged_id_raw', pkgForm.value.waste_packaged_id_raw)

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
      toast.add({ title: 'Stock Insuficiente', description: `No hay suficiente inventario de envases/materiales: ${itemName}`, color: 'error' })
    } else {
      toast.add({ title: 'Error al completar producción', description: resText, color: 'error' })
    }
  } catch (error: any) {
    console.error('Error completing production:', error)
    toast.add({ title: 'Error de red al completar producción', description: error.message || String(error), color: 'error' })
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

async function handleCancelProduction(prod: any) {
  if (!await confirmAction('Cancelar producción', `¿Cancelar la orden de producción #${prod.id} de "${prod.recipe}"? Solo se pueden cancelar producciones en estado Pendiente.`)) return
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({ cancelProduction: 'ok', id_production: String(prod.id) }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText === 'ok') {
      await fetchProductions()
    } else {
      toast.add({ title: 'No se puede cancelar', description: resText.startsWith('error|') ? resText.split('|')[1] : resText, color: 'error' })
    }
  } catch (error) {
    console.error('Error cancelling production:', error)
    toast.add({ title: 'Error de conexión con el servidor.', color: 'error' })
  }
}

async function handleStartProduction(prodId: number) {
  if (!await confirmAction('Iniciar fabricación', '¿Desea iniciar la fabricación de este lote? Se descontarán las materias primas del inventario.')) return
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        startProduction: 'ok',
        id_production: String(prodId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText === 'ok') {
      await fetchProductions()
    } else if (resText.startsWith('stock_insuficiente')) {
      const parts = resText.split('|')
      toast.add({ title: 'Stock insuficiente', description: `No hay suficiente stock de "${parts[1] || 'materia prima'}" para iniciar esta producción.`, color: 'error' })
    } else {
      toast.add({ title: 'Error al iniciar producción', description: resText, color: 'error' })
    }
  } catch (error) {
    console.error('Error starting production:', error)
    toast.add({ title: 'Error al conectar con el servidor.', color: 'error' })
  }
}

async function handleOpenCreateModal() {
  await fetchRecipes()
  newProduction.value = { id_recipe: '' }
  totalQtyInput.reset()
  cifInput.setValue(0)
  moInput.setValue(0)
  isCreateOpen.value = true
}

async function handleSaveProduction() {
  if (!newProduction.value.id_recipe || totalQtyInput.raw.value <= 0) {
    toast.add({ title: 'Selecciona una receta y define la cantidad a producir (mayor a 0).', color: 'error' })
    return
  }

  try {
    const body = new URLSearchParams()
    body.append('saveProduction', 'ok')
    body.append('id_recipe', newProduction.value.id_recipe)
    body.append('id_product', String(selectedRecipe.value?.id_product || ''))
    body.append('batches', String(scaleFactor.value))
    body.append('total_qty', String(totalQtyInput.raw.value))
    body.append('cif', String(cifInput.raw.value))
    body.append('mo', String(moInput.raw.value))
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
      toast.add({ title: 'Stock insuficiente', description: `No hay suficiente stock de "${parts[1] || 'materia prima'}" para esta producción.`, color: 'error' })
    } else {
      toast.add({ title: 'Error al iniciar producción', description: resText, color: 'error' })
    }
  } catch (error) {
    console.error('Error creating production:', error)
    toast.add({ title: 'Error al conectar con el servidor.', color: 'error' })
  }
}

onMounted(() => {
  fetchProductions()
  fetchLabSupplies()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white border border-slate-200 p-6 rounded-2xl shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
          <UIcon
            name="i-lucide-cog"
            class="text-green-500"
          />
          Producción de Laboratorio
        </h1>
        <p class="text-slate-500 text-sm mt-1">
          Monitoreo y ejecución de producción de compuestos del laboratorio.
        </p>
      </div>
      <UButton
        v-if="auth.role !== 'lab_calidad'"
        icon="i-lucide-plus"
        color="success"
        size="md"
        class="font-bold!"
        @click="handleOpenCreateModal"
      >
        Nueva Producción
      </UButton>
    </div>

    <!-- Tabla -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div class="p-5 border-b border-slate-200">
        <h3 class="font-bold text-slate-800 tracking-wide">
          Órdenes de Producción y Estado
        </h3>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="p-8 text-center text-slate-500">
          <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2" />
          Cargando producciones desde la base de datos...
        </div>
        <div v-else-if="productions.length === 0" class="text-center p-8 text-slate-500">
          No hay órdenes de producción en curso.
        </div>
        <table v-else class="w-full text-left text-sm text-slate-600">
          <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
            <tr>
              <th class="px-6 py-4">ID Producción</th>
              <th class="px-6 py-4">Producto</th>
              <th class="px-6 py-4">Cantidad</th>
              <th class="px-6 py-4">Fecha</th>
              <th class="px-6 py-4">Estado</th>
              <th v-if="auth.role !== 'lab_calidad'" class="px-6 py-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="prod in productions" :key="prod.id" class="hover:bg-slate-50 transition-all duration-150">
              <td class="px-6 py-4 font-mono font-bold text-slate-600">#{{ prod.id }}</td>
              <td class="px-6 py-4 font-bold text-slate-800 uppercase">{{ prod.recipe }}</td>
              <td class="px-6 py-4 font-mono text-xs">
                <div class="font-bold text-sm text-slate-700">
                  {{ prod.qty }} <span class="text-xs text-slate-500 font-normal">{{ prod.unit }}</span>
                </div>
                <div v-if="prod.qty_packaged > 0" class="mt-1 space-y-0.5 border-t border-slate-100 pt-1 text-slate-400">
                  <div>Envasado: <strong class="text-slate-700">{{ prod.qty_packaged }} und</strong></div>
                  <div v-if="prod.qty_approved > 0" class="text-emerald-600 font-medium">
                    Aprobadas: <strong>{{ prod.qty_approved }} und</strong>
                  </div>
                  <div v-if="prod.qty_rejected > 0" class="text-rose-500 font-semibold flex items-center gap-0.5">
                    <UIcon name="i-lucide-alert-circle" class="w-3 h-3" /> Merma (QC): <strong>{{ prod.qty_rejected }} und</strong>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-slate-500">{{ prod.date }}</td>
              <td class="px-6 py-4">
                <span v-if="prod.status === 'completado'" class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                  <UIcon name="i-lucide-check-circle" class="w-3.5 h-3.5" /> Completado (Listo en Almacén)
                </span>
                <span v-else-if="prod.status === 'pendiente_qc'" class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-indigo-500/10 text-indigo-600 border border-indigo-500/20">
                  <UIcon name="i-lucide-shield-alert" class="w-3.5 h-3.5" /> Envasado (En Control Calidad)
                </span>
                <span v-else-if="prod.status === 'rechazado'" class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-rose-500/10 text-rose-600 border border-rose-500/20">
                  <UIcon name="i-lucide-x-circle" class="w-3.5 h-3.5" /> Rechazado por QC (Merma)
                </span>
                <span v-else-if="prod.status === 'en_proceso' || prod.status === 'proceso'" class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-blue-500/10 text-blue-600 border border-blue-500/20 animate-pulse">
                  <UIcon name="i-lucide-loader-2" class="w-3.5 h-3.5 animate-spin" /> Fabricación en Proceso
                </span>
                <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-amber-500/10 text-amber-600 border border-amber-500/20">
                  <UIcon name="i-lucide-hourglass" class="w-3.5 h-3.5" /> Pendiente de Envasar
                </span>
              </td>
              <td v-if="auth.role !== 'lab_calidad'" class="px-6 py-4 text-center flex justify-center items-center gap-2 flex-wrap">
                <template v-if="prod.status === 'pendiente'">
                  <UButton label="Iniciar Fabricación" color="primary" size="xs" class="font-bold!" @click="handleStartProduction(prod.id)" />
                  <UButton v-if="isAdmin" icon="i-lucide-x-circle" color="error" size="xs" variant="ghost" title="Cancelar orden" @click="handleCancelProduction(prod)" />
                </template>
                <UButton v-else-if="prod.status === 'proceso' || prod.status === 'en_proceso'" label="Producción Finalizada" color="success" size="xs" class="font-bold!" @click="openPkgModal(prod)" />
                <span v-else class="text-xs font-bold uppercase" :class="prod.status === 'pendiente_qc' ? 'text-indigo-500' : prod.status === 'rechazado' ? 'text-rose-500' : 'text-slate-400'">{{ prod.status === 'pendiente_qc' ? 'En QC' : prod.status === 'rechazado' ? 'Rechazado' : 'Listo' }}</span>
                <UButton icon="i-lucide-eye" color="info" size="xs" variant="ghost" class="font-bold!" @click="openDetailsModal(prod.id)" title="Ver Historial y Detalles" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Iniciar Producción (UModal v-model:open) -->
    <UModal v-model:open="isCreateOpen">
      <template #content>
        <div class="w-full max-w-xl md:max-w-2xl p-6 space-y-4 text-slate-900 bg-white border border-slate-205 rounded-xl shadow-2xl">
          <div class="flex justify-between items-center border-b border-slate-200 pb-3 text-green-600">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-plus" class="w-5 h-5" /> Nueva Orden de Producción
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isCreateOpen = false" />
          </div>

          <form class="space-y-4" @submit.prevent="handleSaveProduction">
            <!-- Receta / Fórmula -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Producto a Producir</label>
              <select v-model="newProduction.id_recipe" class="block w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
                <option value="">Seleccione...</option>
                <option v-for="r in recipes" :key="r.id" :value="r.id">
                  {{ r.name }}
                </option>
              </select>
            </div>

            <!-- Cantidad total a granel -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cantidad Total a Producir (A Granel)</label>
              <div class="relative rounded-lg shadow-sm">
                <input
                  :value="totalQtyInput.display.value"
                  type="text"
                  inputmode="decimal"
                  :disabled="!newProduction.id_recipe"
                  :placeholder="newProduction.id_recipe ? '0,000' : 'Elige una receta primero'"
                  class="block w-full py-2.5 px-3 pr-12 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-50"
                  @input="totalQtyInput.onInput($event as InputEvent)"
                  @keydown="totalQtyInput.onKeydown($event as KeyboardEvent)"
                >
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-sm font-bold">
                  {{ selectedRecipe?.unit || '--' }}
                </div>
              </div>
              <p v-if="scaleFactor > 0" class="mt-2 text-xs text-green-600 font-semibold">
                Factor de Escala: {{ scaleFactor.toFixed(3) }}x — Los ingredientes se multiplicarán adecuadamente.
              </p>
            </div>

            <!-- Vista previa de costo de MP (solo admin) -->
            <div v-if="isAdmin && selectedRecipe && selectedRecipe.cost_batch > 0 && scaleFactor > 0"
              class="flex items-center justify-between px-3 py-2 bg-blue-500/10 border border-blue-500/20 rounded-lg text-xs">
              <span class="text-blue-700 font-medium">Costo MP estimado del lote:</span>
              <span class="font-mono font-bold text-blue-700">
                Bs. {{ (scaleFactor * selectedRecipe.cost_batch).toFixed(2) }}
              </span>
            </div>

            <!-- CIF — solo visible para lab_admin / admin / superadmin -->
            <div v-if="auth.role === 'lab_admin' || auth.role === 'admin' || auth.role === 'superadmin'">
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <UIcon name="i-lucide-settings-2" class="w-3.5 h-3.5" />
                CIF — Costo Indirecto de Fabricación (Bs)
                <span class="text-[10px] font-normal text-slate-400 ml-auto">Calculado automáticamente (editable)</span>
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none">Bs.</span>
                <input
                  :value="cifInput.display.value"
                  type="text"
                  inputmode="decimal"
                  placeholder="0,00"
                  class="block w-full py-2.5 pl-10 pr-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                  @input="cifInput.onInput($event as InputEvent)"
                  @keydown="cifInput.onKeydown($event as KeyboardEvent)"
                >
              </div>
              <p class="mt-1 text-[10px] text-slate-400">≈ 10% del costo total de materias primas del lote.</p>
            </div>

            <!-- MO — solo visible para lab_admin / admin / superadmin, NO obligatoria -->
            <div v-if="auth.role === 'lab_admin' || auth.role === 'admin' || auth.role === 'superadmin'">
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <UIcon name="i-lucide-user-round" class="w-3.5 h-3.5" />
                Mano de Obra Estimada (Bs)
                <span class="ml-auto text-[10px] font-normal text-slate-400">Opcional</span>
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none">Bs.</span>
                <input
                  :value="moInput.display.value"
                  type="text"
                  inputmode="decimal"
                  placeholder="0,00 (opcional)"
                  class="block w-full py-2.5 pl-10 pr-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                  @input="moInput.onInput($event as InputEvent)"
                  @keydown="moInput.onKeydown($event as KeyboardEvent)"
                >
              </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isCreateOpen = false" />
              <UButton type="submit" label="Programar Producción" color="success" class="font-bold!" />
            </div>
          </form>
        </div>
      </template>
    </UModal>

    <!-- Modal Envasado y Finalización (UModal v-model:open) -->
    <UModal v-model:open="isPkgOpen">
      <template #content>
        <div class="w-full max-w-2xl lg:max-w-3xl p-6 bg-white text-slate-900 rounded-xl shadow-2xl space-y-5 border border-slate-200 max-h-[90vh] overflow-y-auto">
          <div class="flex justify-between items-center border-b border-slate-200 pb-3 text-green-600">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-boxes" class="w-6 h-6 animate-pulse" /> Fase de Envasado y Finalización
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isPkgOpen = false" />
          </div>

          <!-- Ficha de la producción -->
          <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
              <p class="text-xs text-slate-400 uppercase font-bold">Total Producido (A Granel)</p>
              <p class="text-lg font-black text-green-600 mt-1">
                {{ pkgForm.total_qty }} {{ pkgForm.bulk_unit }}
              </p>
            </div>
            <div class="text-right">
              <p class="text-xs text-slate-400 uppercase font-bold">Envases Calculados</p>
              <p class="text-lg font-black text-blue-600 mt-1">
                {{ calculatedEnvases }} <span class="text-xs font-normal text-slate-500">Unidades</span>
              </p>
            </div>
          </div>

          <!-- SECCIÓN: Rendimiento Real / Merma -->
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 space-y-3">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
              <UIcon name="i-lucide-scale" class="w-4 h-4 text-green-500" /> Resultado del Proceso de Elaboración
            </h4>
            <p class="text-xs text-slate-400">Rendimiento esperado: <strong class="text-green-600">{{ pkgForm.total_qty }} {{ pkgForm.bulk_unit }}</strong></p>
            
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

            <div v-if="pkgForm.yield_type === 'diff'" class="pt-2 border-t border-slate-200 space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cantidad Real Obtenida</label>
                  <div class="relative rounded-lg shadow-sm">
                    <input
                      :value="pkgRealBulkInput.display.value"
                      type="text"
                      inputmode="decimal"
                      placeholder="0,000"
                      class="block w-full py-2 px-3 pr-10 bg-white border border-slate-200 rounded-lg text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-green-500/50"
                      @input="pkgRealBulkInput.onInput($event as InputEvent)"
                      @keydown="pkgRealBulkInput.onKeydown($event as KeyboardEvent)"
                    >
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                      {{ pkgForm.bulk_unit }}
                    </div>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-rose-500 uppercase tracking-wider mb-2">Merma Envasada (L/kg)</label>
                  <div class="flex gap-2">
                    <input
                      v-model="pkgForm.waste_packaged_qty"
                      type="number"
                      step="any"
                      min="0"
                      placeholder="0"
                      class="block w-20 py-2 px-3 bg-white border border-slate-200 rounded-lg text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/50"
                    >
                    <select v-model="pkgForm.waste_packaged_id_raw" class="flex-1 py-2 px-2 bg-white border border-slate-200 rounded-lg text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/50">
                      <option value="">Sin Envase Lab</option>
                      <option v-for="s in labSupplies" :key="s.id_supply" :value="s.id_supply">{{ s.name_supply }}</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-rose-500 uppercase tracking-wider mb-2">Merma Desperdicio (L/kg)</label>
                  <input
                    v-model="pkgForm.waste_loss_qty"
                    type="number"
                    step="any"
                    min="0"
                    placeholder="0"
                    class="block w-full py-2 px-3 bg-white border border-slate-200 rounded-lg text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/50"
                  >
                </div>
              </div>
            </div>
          </div>

          <!-- SECCIÓN: Tipo, Capacidad y Nombre Final -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipo de Empaque</label>
              <select v-model="pkgForm.envase_type" class="block w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
                <option value="botellas">Botellas</option>
                <option value="frascos">Frascos</option>
                <option value="bolsas">Bolsas</option>
                <option value="cajas">Cajas</option>
                <option value="galones">Galones</option>
                <option value="unidades">Unidades Sueltas</option>
              </select>
            </div>
            
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Capacidad del Envase</label>
              <div class="flex">
                <input
                  :value="pkgVolumeInput.display.value"
                  type="text"
                  inputmode="decimal"
                  placeholder="500"
                  class="block w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-l-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                  @input="pkgVolumeInput.onInput($event as InputEvent)"
                  @keydown="pkgVolumeInput.onKeydown($event as KeyboardEvent)"
                >
                <select v-model="pkgForm.unit" class="py-2.5 px-2 bg-slate-100 border-y border-r border-slate-200 rounded-r-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 w-24">
                  <option v-if="pkgForm.bulk_unit === 'L'" value="ml">ml</option>
                  <option v-if="pkgForm.bulk_unit === 'L'" value="L">L</option>
                  <option v-if="pkgForm.bulk_unit === 'kg'" value="g">g</option>
                  <option v-if="pkgForm.bulk_unit === 'kg'" value="kg">kg</option>
                  <option value="und">und</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nombre a Inventario</label>
              <input v-model="pkgForm.final_name" type="text" placeholder="Ej: Jabón Líquido 500ml" class="block w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
            </div>
          </div>

          <!-- SECCIÓN: Materiales de Envasado -->
          <div class="space-y-3">
            <div class="flex justify-between items-center border-b border-slate-200 pb-2">
              <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Insumos/Materiales de Envasado</h4>
              <UButton icon="i-lucide-plus" label="Añadir Insumo" color="success" variant="ghost" size="xs" @click="addPkgMaterial" />
            </div>

            <div class="space-y-3 max-h-[25vh] overflow-y-auto pr-1">
              <div v-if="pkgForm.extra_mats.length === 0" class="text-xs text-slate-500 py-4 text-center">
                Ningún material de envasado seleccionado. Puedes añadir botellas, cajas o etiquetas desde el catálogo.
              </div>
              <div v-else v-for="(mat, idx) in pkgForm.extra_mats" :key="idx" class="flex items-center gap-3 bg-slate-50 p-3 rounded-lg border border-slate-200">
                <!-- Select Insumo -->
                <select v-model="mat.id_raw" @change="mat.qty = String(calculatedEnvases || 0)" class="flex-1 py-1.5 px-2 bg-white border border-slate-200 rounded text-slate-900 text-xs focus:outline-none focus:ring-1 focus:ring-green-500">
                  <option value="">Seleccione Insumo...</option>
                  <option v-for="m in materials" :key="m.id_raw_material" :value="m.id_raw_material">
                    {{ m.name_raw_material }} ({{ m.unit_raw_material }})
                  </option>
                </select>

                <!-- Cantidad -->
                <input
                  :value="parseInt(mat.qty) > 0 ? parseInt(mat.qty).toLocaleString('de-DE') : ''"
                  type="text"
                  inputmode="numeric"
                  placeholder="0"
                  class="w-24 py-1.5 px-2 bg-white border border-slate-200 rounded text-slate-900 text-xs focus:outline-none focus:ring-1 focus:ring-green-500"
                  @input="onPkgMatQtyInput($event, idx)"
                  @keydown="blockNegative($event as KeyboardEvent)"
                >

                <!-- Botón eliminar -->
                <UButton icon="i-lucide-trash" color="error" variant="ghost" size="xs" @click="removePkgMaterial(idx)" />
              </div>
            </div>
          </div>

          <!-- SECCIÓN: Mano de obra y CIF de envasado -->
          <div v-if="isAdmin" class="row grid grid-cols-2 gap-4 border-t border-slate-200 pt-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                Mano de Obra Extra (Envasado)
                <UIcon v-if="auth.role !== 'lab_admin'" name="i-lucide-lock" class="text-amber-500 w-3 h-3 animate-bounce" />
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none">Bs.</span>
                <input
                  :value="pkgExtraMoInput.display.value"
                  type="text"
                  inputmode="decimal"
                  placeholder="0,00"
                  :disabled="auth.role !== 'lab_admin'"
                  class="block w-full py-2.5 pl-10 pr-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-60 disabled:cursor-not-allowed"
                  @input="pkgExtraMoInput.onInput($event as InputEvent)"
                  @keydown="pkgExtraMoInput.onKeydown($event as KeyboardEvent)"
                >
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                Costos Indirectos Extra (Energía, etc.)
                <UIcon v-if="auth.role !== 'lab_admin'" name="i-lucide-lock" class="text-amber-500 w-3 h-3 animate-bounce" />
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none">Bs.</span>
                <input
                  :value="pkgExtraCifInput.display.value"
                  type="text"
                  inputmode="decimal"
                  placeholder="0,00"
                  :disabled="auth.role !== 'lab_admin'"
                  class="block w-full py-2.5 pl-10 pr-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-60 disabled:cursor-not-allowed"
                  @input="pkgExtraCifInput.onInput($event as InputEvent)"
                  @keydown="pkgExtraCifInput.onKeydown($event as KeyboardEvent)"
                >
              </div>
            </div>
          </div>

          <!-- Footer: Botones de acción del modal envasado -->
          <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 mt-2">
            <UButton label="Cancelar" variant="ghost" color="neutral" @click="isPkgOpen = false" />
            <UButton label="Confirmar Envasado y Enviar a QC" color="success" class="font-bold!" @click="submitPackaging" />
          </div>
        </div>
      </template>
    </UModal>

    <!-- Modal Detalle e Historial de Producción (UModal v-model:open) -->
    <UModal v-model:open="isDetailsOpen">
      <template #content>
        <div class="w-full max-w-2xl lg:max-w-3xl p-6 bg-white text-slate-900 rounded-xl shadow-2xl space-y-6 border border-slate-200 max-h-[90vh] overflow-y-auto">
          <div class="flex justify-between items-center border-b border-slate-200 pb-3 text-blue-600">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
              <div class="space-y-1.5 text-sm">
                <div>
                  <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Producto a Granel:</span>
                  <strong class="text-slate-800 uppercase ms-1">{{ detailsData.production?.title_product }}</strong>
                </div>
                <div v-if="detailsData.production?.pkg_name_production">
                  <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Producto Final Envasado:</span>
                  <strong class="text-indigo-600 ms-1">{{ detailsData.production?.pkg_name_production }}</strong>
                </div>
                <div>
                  <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Estado:</span>
                  <span class="uppercase font-bold text-xs ms-1"
                    :class="{
 'text-emerald-600': detailsData.production?.status_production === 'completado',
 'text-indigo-600': detailsData.production?.status_production === 'pendiente_qc',
 'text-blue-600': detailsData.production?.status_production === 'en_proceso',
 'text-rose-500': detailsData.production?.status_production === 'rechazado',
 'text-amber-600': detailsData.production?.status_production === 'pendiente',
 }">
                    {{ detailsData.production?.status_production?.replace(/_/g, ' ') }}
                  </span>
                </div>
                <div>
                  <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Fecha Inicio:</span>
                  <span class="text-slate-600 ms-1">{{ detailsData.production?.start_date_production || detailsData.production?.date_created_production }}</span>
                </div>
              </div>
              <div class="space-y-1.5 text-sm md:text-right">
                <div>
                  <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Factor de Escala:</span>
                  <strong class="text-slate-800 ms-1">{{ parseFloat(detailsData.production?.batches_production || 0).toFixed(3) }}x</strong>
                </div>
                <div>
                  <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Planificado a Granel:</span>
                  <strong class="text-indigo-600 ms-1">{{ parseFloat(detailsData.production?.total_qty_production || 0).toLocaleString('de-DE', { maximumFractionDigits: 3 }) }} {{ detailsData.production?.unit_product }}</strong>
                </div>
                <div v-if="detailsData.production?.real_bulk_qty">
                  <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Real Obtenido:</span>
                  <strong class="text-emerald-600 ms-1">{{ parseFloat(detailsData.production?.real_bulk_qty || 0).toLocaleString('de-DE', { maximumFractionDigits: 3 }) }} {{ detailsData.production?.unit_product }}</strong>
                </div>
                <div v-if="detailsData.production?.qty_packaged_production">
                  <span class="text-slate-400 font-bold text-xs uppercase tracking-wider">Unidades Envasadas:</span>
                  <strong class="text-blue-600 ms-1">{{ parseFloat(detailsData.production?.qty_packaged_production || 0).toLocaleString() }} und</strong>
                </div>
              </div>
            </div>

            <!-- Alerta si la producción no tiene costos aún -->
            <div v-if="detailsData.production?.status_production === 'pendiente' || detailsData.production?.status_production === 'en_proceso'"
              class="flex items-center gap-2 px-4 py-3 bg-amber-500/10 border border-amber-500/20 rounded-lg text-xs text-amber-700 font-medium">
              <UIcon name="i-lucide-clock" class="w-4 h-4 shrink-0" />
              Los costos de insumos y el desglose financiero estarán disponibles cuando la producción sea envasada y aprobada por QC.
            </div>

            <!-- Ficha de Desviación / Merma Bulk si existe -->
            <div v-if="detailsData.production?.real_bulk_qty" class="p-3 bg-amber-500/10 border border-amber-500/20 text-amber-600 rounded-lg text-xs font-bold flex justify-between">
              <span>Variación de Proceso Bulk (Fabricación):</span>
              <span>
                {{ detailsData.production?.yield_variance >= 0 ? '+' : '' }}{{ parseFloat(detailsData.production?.yield_variance).toFixed(2) }} {{ detailsData.production?.unit_product }} 
                ({{ detailsData.production?.yield_variance >= 0 ? '+' : '' }}{{ parseFloat(detailsData.production?.yield_variance_pct).toFixed(1) }}%)
              </span>
            </div>

            <!-- Ficha de Control de Calidad si aplica -->
            <div v-if="detailsData.production?.result_qc" class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 space-y-3">
              <h4 class="text-xs font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 pb-2">
                <UIcon name="i-lucide-shield-check" class="text-green-500 w-4 h-4" /> Control de Calidad & Liberación Final
              </h4>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs font-mono">
                <div>
                  <p class="text-slate-400 font-sans font-bold">Unidades Evaluadas</p>
                  <p class="text-sm font-bold text-slate-700 mt-0.5">{{ parseFloat(detailsData.production?.qty_packaged_production || detailsData.production?.total_qty_production).toLocaleString() }} und</p>
                </div>
                <div>
                  <p class="text-emerald-600 font-sans font-bold">✅ Aprobadas</p>
                  <p class="text-sm font-bold text-emerald-600 mt-0.5">{{ parseFloat(detailsData.production?.qty_approved_qc).toLocaleString() }} und</p>
                </div>
                <div>
                  <p class="text-rose-500 font-sans font-bold">❌ Rechazadas (Merma)</p>
                  <p class="text-sm font-bold text-rose-500 mt-0.5">{{ parseFloat(detailsData.production?.qty_rejected_qc).toLocaleString() }} und</p>
                </div>
                <div>
                  <p class="text-slate-400 font-sans font-bold">Resultado / Inspector</p>
                  <p class="text-sm font-bold text-slate-700 mt-0.5 uppercase">{{ detailsData.production?.result_qc }}</p>
                  <p class="text-xxs text-slate-400 font-sans mt-0.5">{{ detailsData.production?.qc_inspector_name }}</p>
                </div>
              </div>
              <div v-if="detailsData.production?.qc_notes" class="mt-3 p-3 bg-slate-100 border-l-4 border-l-amber-500 rounded text-xs text-slate-600 italic">
                <strong>Observaciones inspector:</strong> "{{ detailsData.production?.qc_notes }}"
              </div>
            </div>

            <!-- Insumos Consumidos (Detalle de Ingredientes y Envases) -->
            <div class="space-y-2">
              <h4 class="text-xs font-black text-slate-500 uppercase tracking-wider border-b border-slate-200 pb-2">
                Insumos & Materiales Consumidos de Inventario
              </h4>
              <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-left text-xs text-slate-700">
                  <thead class="bg-slate-50 font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                      <th class="px-4 py-2.5">Insumo / Materia Prima</th>
                      <th class="px-4 py-2.5 text-right">Cantidad Usada</th>
                      <th v-if="isAdmin" class="px-4 py-2.5 text-right">Costo Unitario</th>
                      <th v-if="isAdmin" class="px-4 py-2.5 text-right">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-150 font-mono">
                    <tr v-for="m in detailsData.materials" :key="m.id_production_mat_cost" class="hover:bg-slate-50">
                      <td class="px-4 py-2.5 font-sans font-semibold text-slate-800 uppercase">{{ m.name_raw_material }}</td>
                      <td class="px-4 py-2.5 text-right font-bold text-slate-700">
                        {{ parseFloat(m.qty_used_mat_cost).toLocaleString() }}
                        <span class="text-xxs text-slate-500 font-normal">{{ m.unit_raw_material }}</span>
                      </td>
                      <td v-if="isAdmin" class="px-4 py-2.5 text-right text-slate-500">Bs {{ parseFloat(m.unit_price_at_production).toFixed(2) }}</td>
                      <td v-if="isAdmin" class="px-4 py-2.5 text-right font-bold text-slate-800">Bs {{ parseFloat(m.total_cost_mat_cost).toFixed(2) }}</td>
                    </tr>
                    <tr v-if="!detailsData.materials || detailsData.materials.length === 0">
                      <td :colspan="isAdmin ? 4 : 2" class="px-4 py-6 text-center text-slate-500 font-sans italic">
                        No hay insumos consumidos registrados para esta orden.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Hoja de Costos y Rentabilidad — solo admin y solo si hay datos -->
            <div v-if="isAdmin && detailsData.production?.real_total_cost > 0"
              class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-200 pt-4">
              <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs font-mono">
                <h5 class="font-sans font-bold text-slate-700 border-b pb-1.5 mb-2">Desglose de Costos de Manufactura</h5>
                <div class="flex justify-between text-slate-600">
                  <span>MP Consumidas (total):</span>
                  <strong class="text-slate-800">
                    Bs. {{ detailsData.materials?.reduce((a: number, m: any) => a + parseFloat(m.total_cost_mat_cost || 0), 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </strong>
                </div>
                <div class="flex justify-between">
                  <span>MO Elaboración:</span>
                  <strong>Bs. {{ parseFloat(detailsData.production?.real_labor_cost || detailsData.production?.proj_labor_cost || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</strong>
                </div>
                <div v-if="parseFloat(detailsData.production?.pkg_labor_cost) > 0" class="flex justify-between text-blue-600">
                  <span>MO Envasado:</span>
                  <strong>Bs. {{ parseFloat(detailsData.production?.pkg_labor_cost || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</strong>
                </div>
                <div class="flex justify-between">
                  <span>CIF Elaboración:</span>
                  <strong>Bs. {{ parseFloat(detailsData.production?.real_indirect_cost || detailsData.production?.proj_indirect_cost || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</strong>
                </div>
                <div v-if="parseFloat(detailsData.production?.pkg_indirect_cost) > 0" class="flex justify-between text-blue-600">
                  <span>CIF Envasado:</span>
                  <strong>Bs. {{ parseFloat(detailsData.production?.pkg_indirect_cost || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</strong>
                </div>
              </div>

              <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs font-mono flex flex-col justify-between">
                <div>
                  <h5 class="font-sans font-bold text-slate-700 border-b pb-1.5 mb-2">Resumen Financiero del Lote</h5>
                  <div class="flex justify-between">
                    <span>Costo Total Lote:</span>
                    <strong class="text-indigo-600 text-sm">
                      Bs. {{ parseFloat(detailsData.production?.real_total_cost || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </strong>
                  </div>
                </div>
                <div class="border-t pt-2 mt-2">
                  <div class="flex justify-between items-end">
                    <span class="font-sans font-bold text-slate-700">Costo Unitario Real:</span>
                    <strong class="text-emerald-600 text-base">
                      Bs. {{ parseFloat(detailsData.production?.real_unit_cost || 0).toLocaleString('de-DE', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) }}
                    </strong>
                  </div>
                  <p class="text-[10px] text-slate-400 font-sans mt-1">
                    Calculado sobre {{ parseFloat(detailsData.production?.qty_approved_qc || detailsData.production?.qty_packaged_production || 0).toLocaleString() }} unidades aprobadas por QC.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 mt-6">
            <UButton label="Cerrar Detalles" color="neutral" @click="isDetailsOpen = false" />
          </div>
        </div>
      </template>
    </UModal>

    <!-- Diálogo de confirmación -->
    <UModal v-model:open="confirmDialog.open" :ui="{ width: 'max-w-sm' }">
      <template #content>
        <div class="p-6 space-y-4">
          <h3 class="text-base font-semibold text-slate-800">{{ confirmDialog.title }}</h3>
          <p class="text-sm text-slate-600">{{ confirmDialog.message }}</p>
          <div class="flex justify-end gap-2 pt-2">
            <UButton label="Cancelar" color="neutral" variant="ghost" @click="onConfirmCancel" />
            <UButton label="Confirmar" color="error" @click="onConfirmOk" />
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>
