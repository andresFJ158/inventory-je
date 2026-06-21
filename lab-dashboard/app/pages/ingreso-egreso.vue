<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useNumericInput } from '~/composables/useNumericInput'

function getLocalDate(): string {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
}

const auth = useAuthStore()
const toast = useToast()

const canApprove = computed(() => {
  return ['superadmin', 'admin', 'lab_admin'].includes(auth.role) || auth.permissions.aprobar_entradas === 'on'
})

const confirmDialog = ref({ open: false, title: '', message: '' })
const confirmResolve = ref<((v: boolean) => void) | null>(null)
function confirmAction(title: string, message: string): Promise<boolean> {
  return new Promise(resolve => {
    confirmDialog.value = { open: true, title, message }
    confirmResolve.value = resolve
  })
}
function onConfirmOk() { confirmResolve.value?.(true); confirmDialog.value.open = false }
function onConfirmCancel() { confirmResolve.value?.(false); confirmDialog.value.open = false }

const entries = ref<any[]>([])
const loading = ref(true)
const config = useRuntimeConfig()
const apiBase = config.public.ajaxBase
const API_KEY = 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'

// Catálogo de materiales/insumos cargado desde el backend
const materials = ref<any[]>([])
const loadingMaterials = ref(false)

// Proveedores unificados
const suppliers = ref<any[]>([])
const NO_SUPPLIER_VALUE = '__none__'
async function fetchSuppliers() {
  const res = await $fetch<any>(apiBase, {
    method: 'POST',
    body: new URLSearchParams({ getSuppliers: 'ok', type: 'materias_primas' }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  suppliers.value = d?.status === 200 ? d.results.filter((s: any) => s.type_supplier === 'materias_primas') : []
}

const supplierOptions = computed(() => [
  { value: NO_SUPPLIER_VALUE, label: 'Sin proveedor' },
  ...suppliers.value.map(s => ({
    value: String(s.id_supplier),
    label: (s.supplier_name || '').replace(/\+/g, ' ')
  }))
])

// Filtros de la tabla
const filterType = ref<'todos' | 'ingreso' | 'egreso'>('todos')
const filterCategory = ref<'todos' | 'mp' | 'insumo'>('todos')
const searchQuery = ref('')

// Estado para el modal de Registrar Ingreso (Entrada)
const isCreateOpen = ref(false)
const entryType = ref<'mp' | 'insumo'>('mp')
const qtyInput = useNumericInput('', { decimals: 3, min: 0 })
const newEntry = ref({
  id_raw_material: '',
  lot_number: '',
  id_supplier: '',
  date: getLocalDate()
})

// Estado para el modal de Registrar Egreso (Baja)
const isAdjustmentOpen = ref(false)
const adjType = ref<'mp' | 'insumo'>('mp')
const adjQtyInput = useNumericInput('', { decimals: 3, min: 0 })
const newAdjustment = ref({
  id_raw_material: '',
  concept: 'merma',
  notes: ''
})

const conceptOptions = [
  { value: 'merma', label: 'Merma de Proceso' },
  { value: 'vencimiento', label: 'Vencimiento de Producto' },
  { value: 'rotura', label: 'Rotura / Daño Físico' },
  { value: 'ajuste', label: 'Ajuste de Inventario (Conteo)' },
  { value: 'otro', label: 'Otro Concepto' }
]

const conceptLabels: Record<string, string> = {
  merma: 'Merma de Proceso',
  vencimiento: 'Vencimiento',
  rotura: 'Rotura / Daño',
  ajuste: 'Ajuste de Conteo',
  otro: 'Otro Concepto'
}

// Listas filtradas para los formularios
const rawMaterialsList = computed(() => materials.value.filter(m => !m.is_insumo || m.is_insumo === 0 || m.is_insumo === '0'))
const insumosList = computed(() => materials.value.filter(m => m.is_insumo === 1 || m.is_insumo === '1'))

const filteredEntryMaterials = computed(() => {
  return entryType.value === 'mp' ? rawMaterialsList.value : insumosList.value
})

const filteredAdjMaterials = computed(() => {
  return adjType.value === 'mp' ? rawMaterialsList.value : insumosList.value
})

const selectedMaterialForEntry = computed(() => {
  return materials.value.find(m => String(m.id_raw_material) === String(newEntry.value.id_raw_material))
})

const selectedMaterialForAdj = computed(() => {
  return materials.value.find(m => String(m.id_raw_material) === String(newAdjustment.value.id_raw_material))
})

// Filtrado de la bitácora principal
const filteredEntries = computed(() => {
  let list = entries.value

  if (filterType.value !== 'todos') {
    list = list.filter(e => e.type === filterType.value)
  }

  if (filterCategory.value !== 'todos') {
    const wantInsumo = filterCategory.value === 'insumo'
    list = list.filter(e => {
      const isIns = e.is_insumo === 1 || e.is_insumo === '1'
      return wantInsumo ? isIns : !isIns
    })
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(e =>
      e.item.toLowerCase().includes(q) ||
      e.doc.toLowerCase().includes(q) ||
      (e.supplier && e.supplier.toLowerCase().includes(q)) ||
      (e.concept && e.concept.toLowerCase().includes(q))
    )
  }

  return list
})

async function fetchMaterials() {
  loadingMaterials.value = true
  try {
    // Fuente 1: raw_materials (MP e insumos con is_insumo=1)
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabMaterials: 'ok',
        id_office: String(auth.effectiveOfficeId || auth.officeId || 6)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    const fromRaw = data.status === 200 ? data.results : []

    // Fuente 2: lab_supplies (insumos de la tabla legada de insumos Lab)
    let fromLabSupplies: any[] = []
    try {
      const res2 = await $fetch<any>('/api/lab_supplies', {
        headers: { Authorization: API_KEY },
        params: { linkTo: 'id_office_supply', equalTo: String(auth.effectiveOfficeId || auth.officeId || 6) }
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
    } catch (_) { /* ignorar si falla */ }

    // Unificar: raw_materials primero, lab_supplies sin duplicar por nombre
    const namesSeen = new Set(fromRaw.map((m: any) => (m.name_raw_material || '').toLowerCase()))
    const unique = fromLabSupplies.filter(s => !namesSeen.has((s.name_raw_material || '').toLowerCase()))
    materials.value = [...fromRaw, ...unique]
  } catch (error) {
    console.error('Error fetching materials:', error)
  } finally {
    loadingMaterials.value = false
  }
}

async function fetchEntries() {
  loading.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabEntries: 'ok',
        id_office: String(auth.effectiveOfficeId || auth.officeId || 6)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      entries.value = data.results.map((e: any) => ({
        id: e.id_entry,                          // 'rme_X' para raw_materials, 'ls_X' para lab_supplies (único)
        id_entry_raw: e.id_entry_raw ?? null,    // ID numérico real de raw_material_entries (para aprobación)
        doc: e.lot_number_entry || ('ENT-' + e.id_entry),
        id_raw_material: e.id_raw_material_entry,
        item: e.name_raw_material || 'ID: ' + e.id_raw_material_entry,
        qty: parseFloat(e.qty_entry) || 0,
        unit: e.unit_raw_material || 'u',
        date: e.date_created_entry || e.date_entry,
        status: e.status_entry || 'pendiente',
        type: e.type_entry || 'ingreso',
        concept: e.concept_entry || '',
        notes: e.notes_entry || '',
        is_insumo: e.is_insumo == 1 || e.is_insumo === '1' ? 1 : 0,
        supplier: e.supplier_entry || ''
      }))
    } else {
      entries.value = []
    }
  } catch (error) {
    console.error('Error fetching entries:', error)
    entries.value = []
  } finally {
    loading.value = false
  }
}

// Estado para el modal de Aprobación de Costo (Ingresos)
const isPriceModalOpen = ref(false)
const selectedEntryForApproval = ref<any>(null)
const priceInput = useNumericInput('', { decimals: 2, min: 0 })

const priceLabel = computed(() => {
  const unit = selectedEntryForApproval.value?.unit
  if (!unit) return 'Precio de Compra (Bs)'
  const uLower = unit.toLowerCase()
  if (uLower === 'kg') return 'Precio por Kilogramo (Bs/kg)'
  if (uLower === 'g') return 'Precio por Gramo (Bs/g)'
  if (uLower === 'l') return 'Precio por Litro (Bs/L)'
  if (uLower === 'ml') return 'Precio por Mililitro (Bs/ml)'
  if (uLower === 'und' || uLower === 'u') return 'Precio por Unidad (Bs/und)'
  return `Precio por ${unit} (Bs/${unit})`
})

const approvalTotal = computed(() => {
  const qty = parseFloat(String(selectedEntryForApproval.value?.qty)) || 0
  return (qty * priceInput.raw.value).toFixed(2)
})

function confirmEntry(entry: any) {
  if (!canApprove.value) {
    toast.add({ title: 'Solo el administrador puede aprobar y asignar precios a los ingresos.', color: 'error' })
    return
  }
  selectedEntryForApproval.value = entry
  priceInput.reset()
  isPriceModalOpen.value = true
}

async function submitApprovalPrice() {
  if (!canApprove.value) {
    toast.add({ title: 'Solo el administrador puede aprobar y asignar precios a los ingresos.', color: 'error' })
    return
  }
  if (!selectedEntryForApproval.value) return
  const price = priceInput.raw.value
  if (price <= 0) {
    toast.add({ title: 'El precio debe ser mayor a 0.', color: 'error' })
    return
  }
  const entry = selectedEntryForApproval.value
  const qty = parseFloat(entry.qty) || 0
  const total = qty * price

  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        approveRawMaterialEntry: 'ok',
        id_entry: String(entry.id_entry_raw ?? entry.id),
        id_raw_material: String(entry.id_raw_material),
        qty: String(qty),
        price: String(price),
        total: String(total),
        id_admin: String(auth.user?.id_admin || 1)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText === 'ok') {
      isPriceModalOpen.value = false
      await fetchEntries()
      await fetchMaterials() // Actualiza existencias locales
    } else {
      toast.add({ title: 'Error al confirmar ingreso', description: resText, color: 'error' })
    }
  } catch (error: any) {
    console.error('Error confirming entry:', error)
    toast.add({ title: 'Error al conectar con el servidor', description: error.message || String(error), color: 'error' })
  }
}

async function handleOpenCreateModal() {
  if (!canApprove.value) {
    toast.add({ title: 'No tiene permisos para registrar ingresos.', color: 'error' })
    return
  }
  await Promise.all([fetchMaterials(), fetchSuppliers()])
  entryType.value = 'mp'
  newEntry.value = {
    id_raw_material: '',
    lot_number: '',
    id_supplier: '',
    date: getLocalDate()
  }
  qtyInput.reset()
  isCreateOpen.value = true
}

async function handleSaveEntry() {
  if (!canApprove.value) {
    toast.add({ title: 'No tiene permisos para registrar ingresos.', color: 'error' })
    return
  }
  const qty = qtyInput.raw.value
  if (!newEntry.value.id_raw_material || qty <= 0 || !newEntry.value.date) {
    toast.add({ title: 'Completa los campos obligatorios. La cantidad debe ser mayor a 0.', color: 'error' })
    return
  }

  const idStr = String(newEntry.value.id_raw_material)

  // Si el insumo viene de lab_supplies (id comienza con 'ls_'), actualizar stock allí
  if (idStr.startsWith('ls_')) {
    const supplyId = idStr.replace('ls_', '')
    try {
      const material = materials.value.find(m => String(m.id_raw_material) === idStr)

      const res = await $fetch<any>(apiBase, {
        method: 'POST',
        body: new URLSearchParams({
          updateLabSupplyStock: 'ok',
          id_supply: supplyId,
          qty: String(qty),
          lot_number: String(newEntry.value.lot_number || ''),
          supplier: newEntry.value.id_supplier === NO_SUPPLIER_VALUE ? '' : String(suppliers.value.find(s => String(s.id_supplier) === String(newEntry.value.id_supplier))?.supplier_name || ''),
          id_admin: String(auth.user?.id_admin || 1)
        }).toString(),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      })
      const data = typeof res === 'string' ? JSON.parse(res) : res

      if (data?.status === 200) {
        toast.add({ title: 'Ingreso registrado', description: `Se sumaron ${qty} ${material?.unit_raw_material || ''} al stock del insumo.`, color: 'success' })
        isCreateOpen.value = false
        await fetchMaterials()
        await fetchEntries()
      } else {
        toast.add({ title: 'Error al registrar ingreso', description: data?.message || 'Error desconocido', color: 'error' })
      }
    } catch (error: any) {
      console.error('Error saving lab supply entry:', error)
      toast.add({ title: 'Error al conectar con el servidor', description: error.message || String(error), color: 'error' })
    }
    return
  }

  // Para raw_materials, flujo normal
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        saveLabEntry: 'ok',
        id_raw_material_entry: idStr,
        qty_entry: String(qty),
        lot_number_entry: String(newEntry.value.lot_number),
        supplier_entry: newEntry.value.id_supplier === NO_SUPPLIER_VALUE ? '' : String(suppliers.value.find(s => String(s.id_supplier) === String(newEntry.value.id_supplier))?.supplier_name || ''),
        id_supplier_entry: newEntry.value.id_supplier === NO_SUPPLIER_VALUE ? '' : String(newEntry.value.id_supplier),
        date_entry: String(newEntry.value.date),
        id_admin_entry: String(auth.user?.id_admin || 1)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      isCreateOpen.value = false
      await fetchEntries()
    } else {
      toast.add({ title: 'Error al guardar la entrada', description: data.results || data.message || JSON.stringify(data), color: 'error' })
    }
  } catch (error: any) {
    console.error('Error saving entry:', error)
    toast.add({ title: 'Error al conectar con el servidor', description: error.message || String(error), color: 'error' })
    isCreateOpen.value = false
  }
}

async function handleOpenAdjModal() {
  await fetchMaterials()
  adjType.value = 'mp'
  adjQtyInput.reset()
  newAdjustment.value = {
    id_raw_material: '',
    concept: 'merma',
    notes: ''
  }
  isAdjustmentOpen.value = true
}

async function handleSaveAdjustment() {
  const qty = adjQtyInput.raw.value
  const material = selectedMaterialForAdj.value
  if (!newAdjustment.value.id_raw_material || qty <= 0) {
    toast.add({ title: 'Completa los campos obligatorios. La cantidad debe ser mayor a 0.', color: 'error' })
    return
  }

  if (material && parseFloat(material.stock_raw_material) < qty) {
    toast.add({ title: 'Stock insuficiente', description: `No hay suficiente stock de "${material.name_raw_material}" para dar de baja. Stock actual: ${parseFloat(material.stock_raw_material).toFixed(2)} ${material.unit_raw_material}`, color: 'error' })
    return
  }

  if (!await confirmAction('Confirmar baja de stock', `¿Confirmar la baja de ${qty} ${material?.unit_raw_material || ''} de "${material?.name_raw_material}" por concepto de "${conceptLabels[newAdjustment.value.concept]}"? Esta operación descontará stock inmediatamente.`)) return

  const idStr = String(newAdjustment.value.id_raw_material)

  // Si el insumo viene de lab_supplies (id comienza con 'ls_'), descontar allí
  if (idStr.startsWith('ls_')) {
    const supplyId = idStr.replace('ls_', '')
    try {
      const res = await $fetch<any>(apiBase, {
        method: 'POST',
        body: new URLSearchParams({
          adjustLabSupplyStock: 'ok',
          id_supply: supplyId,
          qty: String(qty),
          concept: String(newAdjustment.value.concept),
          notes: String(newAdjustment.value.notes),
          id_admin: String(auth.user?.id_admin || 1)
        }).toString(),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      })
      const data = typeof res === 'string' ? JSON.parse(res) : res

      if (data?.status === 200) {
        toast.add({ title: 'Baja registrada', description: `Se descontaron ${qty} ${material?.unit_raw_material || ''} del stock del insumo.`, color: 'success' })
        isAdjustmentOpen.value = false
        await fetchMaterials()
        await fetchEntries()
      } else {
        toast.add({ title: 'Error al registrar baja', description: data?.message || 'Error desconocido', color: 'error' })
        isAdjustmentOpen.value = false
      }
    } catch (error: any) {
      console.error('Error saving lab supply adjustment:', error)
      toast.add({ title: 'Error al conectar con el servidor', description: error.message || String(error), color: 'error' })
      isAdjustmentOpen.value = false
    }
    return
  }

  // Para raw_materials, flujo normal
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        saveLabAdjustment: 'ok',
        id_raw_material: idStr,
        qty: String(qty),
        concept: String(newAdjustment.value.concept),
        notes: String(newAdjustment.value.notes),
        id_admin: String(auth.user?.id_admin || 1)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      isAdjustmentOpen.value = false
      await fetchEntries()
      await fetchMaterials()
    } else {
      toast.add({ title: 'Error al registrar la baja', description: data.message || JSON.stringify(data), color: 'error' })
    }
  } catch (error: any) {
    console.error('Error saving adjustment:', error)
    toast.add({ title: 'Error al conectar con el servidor', description: error.message || String(error), color: 'error' })
    isAdjustmentOpen.value = false
  }
}

onMounted(() => {
  fetchEntries()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white border border-slate-200 p-6 rounded-2xl shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
          <UIcon
            name="i-lucide-arrow-left-right"
            class="text-green-500"
          />
          Movimientos de Stock: Ingreso / Egreso
        </h1>
        <p class="text-slate-500 text-sm mt-1">
          Historial de entradas de proveedores y egresos por bajas de inventario del laboratorio.
        </p>
      </div>
      
      <div class="flex items-center gap-2 w-full md:w-auto">
        <UButton
          v-if="canApprove"
          icon="i-lucide-plus"
          color="success"
          size="md"
          class="font-bold! flex-1 md:flex-initial"
          @click="handleOpenCreateModal"
        >
          Registrar Ingreso
        </UButton>
        <UButton
          icon="i-lucide-minus"
          color="warning"
          size="md"
          class="font-bold! flex-1 md:flex-initial"
          @click="handleOpenAdjModal"
        >
          Registrar Baja / Egreso
        </UButton>
      </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="bg-white border border-slate-200 p-4 rounded-xl shadow-sm flex flex-col sm:flex-row gap-4 justify-between items-center">
      <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
        <!-- Filtro Tipo de Movimiento -->
        <div class="flex flex-col gap-1 w-32">
          <label class="text-[10px] font-bold uppercase text-slate-400">Movimiento</label>
          <select v-model="filterType" class="py-1.5 px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-none">
            <option value="todos">Todos</option>
            <option value="ingreso">Ingresos (+)</option>
            <option value="egreso">Bajas (-)</option>
          </select>
        </div>

        <!-- Filtro Categoria de Item -->
        <div class="flex flex-col gap-1 w-36">
          <label class="text-[10px] font-bold uppercase text-slate-400">Categoría</label>
          <select v-model="filterCategory" class="py-1.5 px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-none">
            <option value="todos">Todos</option>
            <option value="mp">Materia Prima</option>
            <option value="insumo">Insumos</option>
          </select>
        </div>
      </div>

      <!-- Buscador -->
      <UInput
        v-model="searchQuery"
        icon="i-lucide-search"
        placeholder="Buscar lote, insumo, proveedor..."
        size="sm"
        class="w-full sm:w-72"
      />
    </div>

    <!-- Tabla -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div class="p-5 border-b border-slate-200 flex justify-between items-center">
        <h3 class="font-bold text-slate-800 tracking-wide">
          Historial de Transacciones ({{ filteredEntries.length }})
        </h3>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="p-8 text-center text-slate-500">
          <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2" />
          Cargando registros de movimientos...
        </div>
        <div v-else-if="filteredEntries.length === 0" class="text-center p-8 text-slate-500">
          No se encontraron movimientos registrados con los filtros seleccionados.
        </div>
        <table v-else class="w-full text-left text-sm text-slate-600">
          <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
            <tr>
              <th class="px-6 py-4">Tipo</th>
              <th class="px-6 py-4">Elemento</th>
              <th class="px-6 py-4">Lote / Factura</th>
              <th class="px-6 py-4">Cantidad</th>
              <th class="px-6 py-4">Fecha</th>
              <th class="px-6 py-4">Estado / Concepto</th>
              <th v-if="canApprove" class="px-6 py-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="entry in filteredEntries" :key="entry.id" class="hover:bg-slate-50 transition-all duration-150">
              <!-- Tipo Movimiento Badge -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="entry.type === 'ingreso'" class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1.5 bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                  <UIcon name="i-lucide-arrow-up-right" class="w-3.5 h-3.5" /> Ingreso
                </span>
                <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1.5 bg-amber-500/10 text-amber-600 border border-amber-500/20">
                  <UIcon name="i-lucide-arrow-down-left" class="w-3.5 h-3.5" /> Baja
                </span>
              </td>

              <!-- Elemento y Categoría -->
              <td class="px-6 py-4">
                <div class="font-bold text-slate-800 uppercase tracking-wide">
                  {{ entry.item }}
                </div>
                <div class="mt-0.5 flex items-center gap-1">
                  <span v-if="entry.is_insumo === 1 || entry.is_insumo === '1'" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-100 text-blue-600">
                    INSUMO
                  </span>
                  <span v-else class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-green-100 text-green-600">
                    MATERIA PRIMA
                  </span>
                  <span v-if="entry.supplier" class="text-xxs text-slate-400 truncate max-w-xs font-medium">
                    Prov: {{ entry.supplier }}
                  </span>
                  <span v-if="entry.notes && entry.type === 'egreso'" class="text-xxs text-slate-400 truncate max-w-xs italic">
                    "{{ entry.notes }}"
                  </span>
                </div>
              </td>

              <!-- Lote -->
              <td class="px-6 py-4 font-mono text-xs text-slate-500">
                {{ entry.doc }}
              </td>

              <!-- Cantidad -->
              <td class="px-6 py-4 font-mono font-bold text-sm" :class="entry.type === 'ingreso' ? 'text-slate-800' : 'text-amber-600'">
                {{ entry.type === 'ingreso' ? '+' : '-' }}{{ entry.qty.toFixed(2) }}
                <span class="text-xs text-slate-500 font-sans font-normal ml-0.5">{{ entry.unit }}</span>
              </td>

              <!-- Fecha -->
              <td class="px-6 py-4 text-slate-500">
                {{ entry.date }}
              </td>

              <!-- Estado / Concepto -->
              <td class="px-6 py-4">
                <!-- Para Ingresos -->
                <div v-if="entry.type === 'ingreso'">
                  <span v-if="entry.status === 'recibido' || entry.status === 'confirmado' || entry.status === 'aprobado'" class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-500/10 text-green-600 border border-green-500/20">
                    Recibido
                  </span>
                  <span v-else class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-600 border border-amber-500/20 animate-pulse">
                    Pendiente
                  </span>
                </div>
                <!-- Para Egresos -->
                <div v-else>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-600 border border-amber-550/20 uppercase tracking-wider">
                    {{ conceptLabels[entry.concept] || entry.concept || 'Baja' }}
                  </span>
                </div>
              </td>

              <!-- Acciones Administrador -->
              <td v-if="canApprove" class="px-6 py-4 text-center">
                <div v-if="entry.type === 'ingreso' && entry.status === 'pendiente'">
                  <UButton label="Confirmar Ingreso" color="success" size="xs" class="font-bold!" @click="confirmEntry(entry)" />
                </div>
                <div v-else-if="entry.type === 'ingreso'">
                  <span class="text-xs text-slate-400 font-bold uppercase">Aprobada</span>
                </div>
                <div v-else>
                  <span class="text-xs text-slate-400 font-bold uppercase">-</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal REGISTRAR INGRESO (UModal v-model:open) -->
    <UModal v-model:open="isCreateOpen">
      <template #content>
        <div class="w-full p-6 space-y-4 text-slate-900 bg-white rounded-xl border border-slate-200">
          <div class="flex justify-between items-center border-b border-slate-200 pb-3 text-green-600">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-plus" class="w-5 h-5" /> Registrar Ingreso de Stock (Entrada)
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isCreateOpen = false" />
          </div>

          <form class="space-y-4" @submit.prevent="handleSaveEntry">
            <!-- Selector Tipo de Elemento -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipo de Elemento</label>
              <div class="grid grid-cols-2 gap-2 text-sm font-medium">
                <label class="flex items-center justify-center gap-2 py-2 px-3 border border-slate-200 rounded-lg cursor-pointer transition-all" :class="entryType === 'mp' ? 'bg-green-500/10 text-green-600 border-green-500/30 font-bold' : 'bg-slate-50'">
                  <input type="radio" v-model="entryType" value="mp" class="hidden" @change="newEntry.id_raw_material = ''">
                  Materia Prima
                </label>
                <label class="flex items-center justify-center gap-2 py-2 px-3 border border-slate-200 rounded-lg cursor-pointer transition-all" :class="entryType === 'insumo' ? 'bg-blue-500/10 text-blue-600 border-blue-500/30 font-bold' : 'bg-slate-50'">
                  <input type="radio" v-model="entryType" value="insumo" class="hidden" @change="newEntry.id_raw_material = ''">
                  Insumo
                </label>
              </div>
            </div>

            <!-- Selección de Elemento -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                Seleccionar {{ entryType === 'mp' ? 'Materia Prima' : 'Insumo' }}
              </label>
              <select v-model="newEntry.id_raw_material" class="block w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
                <option value="">Seleccione...</option>
                <option v-for="m in filteredEntryMaterials" :key="m.id_raw_material" :value="m.id_raw_material">
                  {{ m.name_raw_material }} (Stock: {{ parseFloat(m.stock_raw_material || 0).toFixed(2) }} {{ m.unit_raw_material }})
                </option>
              </select>
            </div>

            <!-- Cantidad -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cantidad Recibida</label>
              <div class="relative rounded-lg shadow-sm">
                <input
                  :value="qtyInput.display.value"
                  type="text"
                  inputmode="decimal"
                  :disabled="!newEntry.id_raw_material"
                  :placeholder="newEntry.id_raw_material ? '0,000' : 'Elige un elemento primero'"
                  class="block w-full py-2.5 px-3 pr-12 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-50"
                  @input="qtyInput.onInput($event as InputEvent)"
                  @keydown="qtyInput.onKeydown($event as KeyboardEvent)"
                >
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-sm font-bold">
                  {{ selectedMaterialForEntry?.unit_raw_material || '--' }}
                </div>
              </div>
              <p v-if="qtyInput.raw.value > 0" class="mt-1 text-xs text-green-600 font-mono">
                = {{ qtyInput.raw.value.toLocaleString('de-DE', { maximumFractionDigits: 3 }) }} {{ selectedMaterialForEntry?.unit_raw_material || '' }}
              </p>
            </div>

            <!-- Lote (solo para MP) -->
            <div v-if="entryType === 'mp'">
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Número de Lote / Factura</label>
              <input v-model="newEntry.lot_number" type="text" placeholder="Lote o Factura" class="block w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
            </div>

            <!-- Proveedor (selector unificado) -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Proveedor</label>
              <USelect
                v-model="newEntry.id_supplier"
                :items="supplierOptions"
                placeholder="Seleccionar proveedor..."
                class="w-full"
              />
              <NuxtLink to="/proveedores-lab" class="text-xs text-green-600 hover:underline mt-1 block">
                + Gestionar proveedores de laboratorio
              </NuxtLink>
            </div>

            <!-- Fecha -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha de Llegada</label>
              <input v-model="newEntry.date" type="date" class="block w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isCreateOpen = false" />
              <UButton type="submit" label="Registrar Entrada" color="success" class="font-bold!" />
            </div>
          </form>
        </div>
      </template>
    </UModal>

    <!-- Modal REGISTRAR EGRESO / BAJA (UModal v-model:open) -->
    <UModal v-model:open="isAdjustmentOpen">
      <template #content>
        <div class="w-full p-6 space-y-4 text-slate-900 bg-white rounded-xl border border-slate-200">
          <div class="flex justify-between items-center border-b border-slate-200 pb-3 text-amber-500">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-minus" class="w-5 h-5" /> Registrar Baja / Ajuste (Egreso)
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isAdjustmentOpen = false" />
          </div>

          <form class="space-y-4" @submit.prevent="handleSaveAdjustment">
            <!-- Selector Tipo de Item -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipo de Elemento</label>
              <div class="grid grid-cols-2 gap-2 text-sm font-medium">
                <label class="flex items-center justify-center gap-2 py-2 px-3 border border-slate-200 rounded-lg cursor-pointer transition-all" :class="adjType === 'mp' ? 'bg-green-500/10 text-green-600 border-green-500/30 font-bold' : 'bg-slate-50'">
                  <input type="radio" v-model="adjType" value="mp" class="hidden" @change="newAdjustment.id_raw_material = ''">
                  Materia Prima
                </label>
                <label class="flex items-center justify-center gap-2 py-2 px-3 border border-slate-200 rounded-lg cursor-pointer transition-all" :class="adjType === 'insumo' ? 'bg-blue-500/10 text-blue-600 border-blue-500/30 font-bold' : 'bg-slate-50'">
                  <input type="radio" v-model="adjType" value="insumo" class="hidden" @change="newAdjustment.id_raw_material = ''">
                  Insumo
                </label>
              </div>
            </div>

            <!-- Selección de Elemento -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                Seleccionar {{ adjType === 'mp' ? 'Materia Prima' : 'Insumo' }}
              </label>
              <select v-model="newAdjustment.id_raw_material" class="block w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
                <option value="">Seleccione...</option>
                <option v-for="m in filteredAdjMaterials" :key="m.id_raw_material" :value="m.id_raw_material">
                  {{ m.name_raw_material }} (Stock: {{ parseFloat(m.stock_raw_material).toFixed(2) }} {{ m.unit_raw_material }})
                </option>
              </select>
            </div>

            <!-- Cantidad a retirar -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cantidad a Dar de Baja</label>
              <div class="relative rounded-lg shadow-sm">
                <input
                  :value="adjQtyInput.display.value"
                  type="text"
                  inputmode="decimal"
                  :disabled="!newAdjustment.id_raw_material"
                  :placeholder="newAdjustment.id_raw_material ? '0,000' : 'Elige elemento primero'"
                  class="block w-full py-2.5 px-3 pr-12 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-50"
                  @input="adjQtyInput.onInput($event as InputEvent)"
                  @keydown="adjQtyInput.onKeydown($event as KeyboardEvent)"
                >
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-sm font-bold">
                  {{ selectedMaterialForAdj?.unit_raw_material || '--' }}
                </div>
              </div>
              
              <!-- Info de Stock Actual y Stock Final -->
              <div v-if="selectedMaterialForAdj" class="mt-2 text-xs flex justify-between font-mono bg-slate-50 p-2 rounded border border-slate-200">
                <span class="text-slate-500">Stock Actual: {{ parseFloat(selectedMaterialForAdj.stock_raw_material).toFixed(2) }} {{ selectedMaterialForAdj.unit_raw_material }}</span>
                <span :class="parseFloat(selectedMaterialForAdj.stock_raw_material) - adjQtyInput.raw.value >= 0 ? 'text-green-600 font-bold' : 'text-rose-500 font-bold'">
                  Quedará: {{ (parseFloat(selectedMaterialForAdj.stock_raw_material) - adjQtyInput.raw.value).toFixed(2) }}
                </span>
              </div>
            </div>

            <!-- Concepto de Baja -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Concepto / Motivo de Baja</label>
              <select v-model="newAdjustment.concept" class="block w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
                <option v-for="c in conceptOptions" :key="c.value" :value="c.value">
                  {{ c.label }}
                </option>
              </select>
            </div>

            <!-- Observaciones -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Observaciones / Detalles</label>
              <UTextarea v-model="newAdjustment.notes" placeholder="Describa brevemente el motivo específico (lote vencido, frasco quebrado, etc.)..." :rows="3" />
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isAdjustmentOpen = false" />
              <UButton type="submit" label="Registrar Baja de Stock" color="warning" class="font-bold!" :disabled="!newAdjustment.id_raw_material || adjQtyInput.raw.value <= 0 || (selectedMaterialForAdj && parseFloat(selectedMaterialForAdj.stock_raw_material) < adjQtyInput.raw.value)" />
            </div>
          </form>
        </div>
      </template>
    </UModal>

    <!-- Modal Aprobación Costo Ingreso (UModal v-model:open) -->
    <UModal v-model:open="isPriceModalOpen">
      <template #content>
        <div class="w-full max-w-sm p-6 bg-white text-slate-900 rounded-xl shadow-2xl space-y-4 border border-slate-200">
          <div class="flex justify-between items-center border-b border-slate-200 pb-3">
            <h3 class="text-lg font-bold text-slate-800 tracking-wide">
              Asignar Costo de Compra
            </h3>
            <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" @click="isPriceModalOpen = false" />
          </div>

          <div class="space-y-4">
            <p class="text-xs text-slate-500">
              Lote: <span class="font-bold text-slate-700">{{ selectedEntryForApproval?.doc }}</span> | Insumo: <span class="font-bold text-slate-700 uppercase">{{ selectedEntryForApproval?.item }}</span>
            </p>
            <p class="text-xs text-slate-500">
              Cantidad Recibida: <span class="font-bold text-slate-700">{{ selectedEntryForApproval?.qty }} {{ selectedEntryForApproval?.unit }}</span>
            </p>

            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ priceLabel }}</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none">Bs.</span>
                <input
                  :value="priceInput.display.value"
                  type="text"
                  inputmode="decimal"
                  placeholder="0,00"
                  class="block w-full py-2.5 pl-10 pr-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                  :class="{ 'border-rose-400': priceInput.raw.value <= 0 && priceInput.display.value !== '' }"
                  @input="priceInput.onInput($event as InputEvent)"
                  @keydown="priceInput.onKeydown($event as KeyboardEvent)"
                >
              </div>
              <p v-if="priceInput.raw.value > 0" class="text-xs text-slate-500 font-mono mt-1">
                Total: <span class="font-bold text-green-600">Bs. {{ Number(approvalTotal).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
              </p>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isPriceModalOpen = false" />
              <UButton label="Aprobar Ingreso" color="success" class="font-bold!" :disabled="priceInput.raw.value <= 0" @click="submitApprovalPrice" />
            </div>
          </div>
        </div>
      </template>
    </UModal>

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
