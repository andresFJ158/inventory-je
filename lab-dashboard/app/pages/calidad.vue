<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()
const apiBase = '/ajax/pos.ajax.php'

// Helpers numéricos inline para QC (bloquea negativos, format en display)
function blockNegative(e: KeyboardEvent) {
  if (e.key === '-' || e.key === 'e' || e.key === 'E') e.preventDefault()
}

function formatQcInput(val: string): string {
  if (!val) return ''
  const n = parseFloat(val)
  return isNaN(n) || n < 0 ? '0' : n.toLocaleString('de-DE', { maximumFractionDigits: 2 })
}

function onQcNumInput(e: Event, field: 'qty_approved' | 'qty_rejected') {
  const input = e.target as HTMLInputElement
  const raw = input.value.replace(/[^\d,]/g, '').replace(',', '.')
  const n = parseFloat(raw)
  const val = isNaN(n) || n < 0 ? 0 : n
  qcForm.value[field] = String(val)
  syncQtyFields(field === 'qty_approved' ? 'approved' : 'rejected')
  input.value = val > 0 ? val.toLocaleString('de-DE', { maximumFractionDigits: 2 }) : ''
}

// Tabs
const activeTab = ref<'pending' | 'history'>('pending')

// Loading states
const loadingPending = ref(false)
const loadingHistory = ref(false)

// Lists
const pendingList = ref<any[]>([])
const historyList = ref<any[]>([])

// Modal Evaluación
const isQCOpen = ref(false)
const qcForm = ref({
  id_production: '',
  recipe_name: '',
  title_product: '',
  total_qty: '0',
  unit_product: 'und',
  date_updated: '',
  result_qc: 'aprobado', // 'aprobado' | 'aprobado_con_obs' | 'rechazado'
  qty_approved: '0',
  qty_rejected: '0',
  notes_qc: ''
})

// KPI Stats para Historial
const stats = computed(() => {
  if (historyList.value.length === 0) {
    return { total: 0, approved: 0, obs: 0, rejected: 0, avgShrinkage: '0.0%' }
  }
  const total = historyList.value.length
  let approved = 0
  let obs = 0
  let rejected = 0
  let totalShrinkagePct = 0

  historyList.value.forEach(x => {
    if (x.result_qc === 'aprobado') approved++
    else if (x.result_qc === 'aprobado_con_obs') obs++
    else if (x.result_qc === 'rechazado') rejected++

    const appQty = parseFloat(x.qty_approved_qc) || 0
    const rejQty = parseFloat(x.qty_rejected_qc) || 0
    const totalQty = appQty + rejQty
    if (totalQty > 0) {
      totalShrinkagePct += (rejQty / totalQty) * 100
    }
  })

  return {
    total,
    approved,
    obs,
    rejected,
    avgShrinkage: (totalShrinkagePct / total).toFixed(1) + '%'
  }
})

// Cargar pendientes
async function fetchPending() {
  loadingPending.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getPendingQC: 'ok',
        id_office: String(auth.officeId || 6)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    pendingList.value = Array.isArray(data) ? data : []
  } catch (error) {
    console.error('Error fetching pending QC:', error)
    pendingList.value = []
  } finally {
    loadingPending.value = false
  }
}

// Cargar historial
async function fetchHistory() {
  loadingHistory.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getQCHistory: 'ok',
        id_office: String(auth.officeId || 6)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    historyList.value = Array.isArray(data) ? data : []
  } catch (error) {
    console.error('Error fetching QC history:', error)
    historyList.value = []
  } finally {
    loadingHistory.value = false
  }
}

// Abrir modal de evaluación
function openQCModal(item: any) {
  const qtyPackaged = item.qty_packaged_production ? parseFloat(item.qty_packaged_production) : parseFloat(item.total_qty_production)
  qcForm.value = {
    id_production: String(item.id_production),
    recipe_name: item.name_recipe || 'Fórmula de Laboratorio',
    title_product: item.title_product || 'Producto Terminado',
    total_qty: String(qtyPackaged),
    unit_product: item.unit_product || 'und',
    date_updated: item.date_updated_production,
    result_qc: 'aprobado',
    qty_approved: String(qtyPackaged),
    qty_rejected: '0',
    notes_qc: ''
  }
  isQCOpen.value = true
}

// Sincronizar campos de cantidad
function syncQtyFields(changed: 'approved' | 'rejected') {
  const total = parseFloat(qcForm.value.total_qty) || 0
  const approved = parseFloat(qcForm.value.qty_approved) || 0
  const rejected = parseFloat(qcForm.value.qty_rejected) || 0

  if (changed === 'approved') {
    qcForm.value.qty_rejected = String(Math.max(0, total - approved))
  } else {
    qcForm.value.qty_approved = String(Math.max(0, total - rejected))
  }
}

// Validar y enviar
async function submitQC() {
  const approved = parseFloat(qcForm.value.qty_approved) || 0
  const rejected = parseFloat(qcForm.value.qty_rejected) || 0
  const total = parseFloat(qcForm.value.total_qty) || 0

  if (approved + rejected > total + 0.01) {
    toast.add({ title: 'La suma de cantidades aprobada y rechazada supera el total producido.', color: 'error' })
    return
  }

  const result = qcForm.value.result_qc
  const notes = qcForm.value.notes_qc.trim()

  if ((result === 'rechazado' || rejected > 0 || result === 'aprobado_con_obs') && !notes) {
    toast.add({ title: 'Debes describir el problema o motivo de la merma en el campo de observaciones.', color: 'error' })
    return
  }

  try {
    const payload = new URLSearchParams()
    payload.append('submitQualityCheck', 'ok')
    payload.append('id_production', qcForm.value.id_production)
    payload.append('id_admin', String(auth.user?.id_admin || 1))
    payload.append('id_office', String(auth.officeId || 6))
    payload.append('result_qc', result)
    payload.append('qty_approved', String(approved))
    payload.append('qty_rejected', String(rejected))
    payload.append('notes_qc', notes)

    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: payload.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 'ok') {
      toast.add({ title: data.result === 'completado' ? 'Lote evaluado y disponible en inventario final.' : 'Lote rechazado y merma registrada.', color: 'success' })
      isQCOpen.value = false
      await fetchPending()
      if (activeTab.value === 'history') {
        await fetchHistory()
      }
    } else {
      toast.add({ title: 'Error al registrar control de calidad', description: data.message || 'Respuesta inválida', color: 'error' })
    }
  } catch (error: any) {
    console.error('Error submitting QC:', error)
    toast.add({ title: 'Error al conectar con el servidor', description: error.message || String(error), color: 'error' })
  }
}

// Cargar al montar
onMounted(() => {
  fetchPending()
  fetchHistory()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white border border-slate-200 p-6 rounded-2xl shadow-sm">
      <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
        <UIcon
          name="i-lucide-shield-check"
          class="text-green-500"
        />
        Control de Calidad (QC)
      </h1>
      <p class="text-slate-500 text-sm mt-1">
        Inspección analítica, reporte de mermas, y liberación final a inventario central.
      </p>
    </div>

    <!-- Restricción de rol -->
    <div
      v-if="auth.role === 'lab_worker'"
      class="bg-rose-500/10 border border-rose-500/20 p-5 rounded-xl text-rose-500 text-sm font-bold flex items-center gap-2"
    >
      <UIcon
        name="i-lucide-alert-triangle"
        class="w-5 h-5 shrink-0"
      />
      No tienes permisos para acceder a las pruebas de aprobación del Control de Calidad.
    </div>

    <div v-else class="space-y-6">
      <!-- Tabs Selector -->
      <div class="flex gap-2 border-b border-slate-200 pb-px">
        <button
          class="px-4 py-2 text-sm font-bold tracking-wide border-b-2 transition-all duration-200 flex items-center gap-2"
          :class="activeTab === 'pending'
 ? 'border-green-500 text-green-600'
 : 'border-transparent text-slate-500 hover:text-slate-700'"
          @click="activeTab = 'pending'"
        >
          <UIcon name="i-lucide-clock" class="w-4 h-4" />
          Pendientes de Revisión
          <span
            v-if="pendingList.length > 0"
            class="ml-1 px-2 py-0.5 text-xxs font-extrabold bg-green-500 text-white rounded-full"
          >
            {{ pendingList.length }}
          </span>
        </button>
        <button
          class="px-4 py-2 text-sm font-bold tracking-wide border-b-2 transition-all duration-200 flex items-center gap-2"
          :class="activeTab === 'history'
 ? 'border-green-500 text-green-600'
 : 'border-transparent text-slate-500 hover:text-slate-700'"
          @click="activeTab = 'history'"
        >
          <UIcon name="i-lucide-history" class="w-4 h-4" />
          Historial de Evaluaciones
        </button>
      </div>

      <!-- ===== TAB PENDIENTES ===== -->
      <div v-if="activeTab === 'pending'" class="space-y-4">
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
          <div class="p-5 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 tracking-wide">
              Lotes Recién Envasados en Proceso de Aprobación
            </h3>
            <UButton icon="i-lucide-refresh-cw" variant="ghost" color="neutral" size="xs" @click="fetchPending" />
          </div>

          <div class="overflow-x-auto">
            <div v-if="loadingPending" class="p-8 text-center text-slate-500">
              <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2" />
              Cargando lotes pendientes de inspección...
            </div>
            <div v-else-if="pendingList.length === 0" class="text-center p-12 text-slate-500">
              <UIcon name="i-lucide-shield-check" class="w-12 h-12 mx-auto text-slate-400 mb-3" />
              <p class="font-bold text-slate-700">¡Todo al día!</p>
              <p class="text-xs text-slate-400 mt-1">No hay lotes envasados pendientes de liberación por Control de Calidad.</p>
            </div>
            <table v-else class="w-full text-left text-sm text-slate-600">
              <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                <tr>
                  <th class="px-6 py-4">ID Orden</th>
                  <th class="px-6 py-4">Producto Final</th>
                  <th class="px-6 py-4">Receta de Base</th>
                  <th class="px-6 py-4 text-right">Cant. Envasada</th>
                  <th class="px-6 py-4">Fecha de Envasado</th>
                  <th class="px-6 py-4 text-center">Acción</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr v-for="p in pendingList" :key="p.id_production" class="hover:bg-slate-50 transition-all duration-150">
                  <td class="px-6 py-4 font-mono font-bold text-slate-600">#{{ p.id_production }}</td>
                  <td class="px-6 py-4 font-bold text-slate-850 uppercase">{{ p.title_product || 'Producto Terminado' }}</td>
                  <td class="px-6 py-4 text-slate-500">{{ p.name_recipe }}</td>
                  <td class="px-6 py-4 text-right font-mono font-bold text-slate-700">
                    {{ parseFloat(p.qty_packaged_production || p.total_qty_production).toLocaleString() }}
                    <span class="text-xs text-slate-500">{{ p.unit_product }}</span>
                  </td>
                  <td class="px-6 py-4 text-slate-500">{{ p.date_updated_production }}</td>
                  <td class="px-6 py-4 text-center">
                    <UButton
                      label="Evaluar Calidad"
                      icon="i-lucide-clipboard-check"
                      color="success"
                      size="xs"
                      class="font-bold!"
                      @click="openQCModal(p)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== TAB HISTORIAL ===== -->
      <div v-if="activeTab === 'history'" class="space-y-6">
        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
            <p class="text-2xl font-black text-slate-800">{{ stats.total }}</p>
            <p class="text-xxs font-extrabold text-slate-500 uppercase tracking-wider mt-1">Total Evaluados</p>
          </div>
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center border-l-4 border-l-emerald-500">
            <p class="text-2xl font-black text-emerald-600">{{ stats.approved }}</p>
            <p class="text-xxs font-extrabold text-slate-500 uppercase tracking-wider mt-1">Aprobados Directos</p>
          </div>
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center border-l-4 border-l-amber-500">
            <p class="text-2xl font-black text-amber-600">{{ stats.obs }}</p>
            <p class="text-xxs font-extrabold text-slate-500 uppercase tracking-wider mt-1">Con Observaciones</p>
          </div>
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center border-l-4 border-l-rose-500">
            <p class="text-2xl font-black text-rose-600">{{ stats.rejected }}</p>
            <p class="text-xxs font-extrabold text-slate-500 uppercase tracking-wider mt-1">Rechazados</p>
          </div>
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center border-l-4 border-l-indigo-500">
            <p class="text-2xl font-black text-indigo-600">{{ stats.avgShrinkage }}</p>
            <p class="text-xxs font-extrabold text-slate-500 uppercase tracking-wider mt-1">Pérdida Promedio</p>
          </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
          <div class="p-5 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 tracking-wide">
              Registro Histórico de Auditorías de Calidad
            </h3>
            <UButton icon="i-lucide-refresh-cw" variant="ghost" color="neutral" size="xs" @click="fetchHistory" />
          </div>

          <div class="overflow-x-auto">
            <div v-if="loadingHistory" class="p-8 text-center text-slate-500">
              <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2" />
              Cargando registros históricos de calidad...
            </div>
            <div v-else-if="historyList.length === 0" class="text-center p-8 text-slate-500">
              No hay evaluaciones de calidad en el historial de esta sucursal.
            </div>
            <table v-else class="w-full text-left text-sm text-slate-700">
              <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                <tr>
                  <th class="px-4 py-3">ID CC</th>
                  <th class="px-4 py-3">ID Prod</th>
                  <th class="px-4 py-3">Producto Final</th>
                  <th class="px-4 py-3">Inspector</th>
                  <th class="px-4 py-3 text-right">Aprobado</th>
                  <th class="px-4 py-3 text-right">Rechazado (Merma)</th>
                  <th class="px-4 py-3">Resultado</th>
                  <th class="px-4 py-3">Observaciones</th>
                  <th class="px-4 py-3">Fecha</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 text-xs">
                <tr v-for="h in historyList" :key="h.id_qc" class="hover:bg-slate-50">
                  <td class="px-4 py-3 font-mono font-bold text-slate-500">#{{ h.id_qc }}</td>
                  <td class="px-4 py-3 font-mono font-bold text-slate-500">#{{ h.id_production_qc }}</td>
                  <td class="px-4 py-3 font-bold text-slate-800 uppercase">{{ h.title_product }}</td>
                  <td class="px-4 py-3 font-medium text-slate-600">{{ h.qc_inspector_name || 'N/A' }}</td>
                  <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600">
                    {{ parseFloat(h.qty_approved_qc).toLocaleString() }}
                    <span class="text-xxs text-slate-500 font-normal">{{ h.unit_product }}</span>
                  </td>
                  <td class="px-4 py-3 text-right font-mono font-bold" :class="parseFloat(h.qty_rejected_qc) > 0 ? 'text-rose-500' : 'text-slate-400'">
                    {{ parseFloat(h.qty_rejected_qc).toLocaleString() }}
                    <span class="text-xxs text-slate-500 font-normal">{{ h.unit_product }}</span>
                  </td>
                  <td class="px-4 py-3">
                    <span v-if="h.result_qc === 'aprobado'" class="px-2 py-0.5 rounded text-xxs font-extrabold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                      APROBADO
                    </span>
                    <span v-else-if="h.result_qc === 'aprobado_con_obs'" class="px-2 py-0.5 rounded text-xxs font-extrabold bg-amber-500/10 text-amber-600 border border-amber-500/20">
                      CON OBSERVACIÓN
                    </span>
                    <span v-else class="px-2 py-0.5 rounded text-xxs font-extrabold bg-rose-500/10 text-rose-600 border border-rose-500/20">
                      RECHAZADO
                    </span>
                  </td>
                  <td class="px-4 py-3 text-slate-500 max-w-xs truncate" :title="h.notes_qc">
                    {{ h.notes_qc || 'Sin observaciones.' }}
                  </td>
                  <td class="px-4 py-3 text-slate-500">{{ h.date_created_qc }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Evaluación de Calidad (Grande y legible para mejor experiencia) -->
    <UModal v-model:open="isQCOpen" class="modal-large">
      <template #content>
        <div class="w-full max-w-2xl lg:max-w-3xl p-6 bg-white text-slate-900 rounded-xl shadow-2xl space-y-6 border border-slate-200 max-h-[90vh] overflow-y-auto">
          <div class="flex justify-between items-center border-b border-slate-200 pb-3 text-green-600">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-shield-check" class="w-6 h-6 animate-pulse" />
              Evaluación de Calidad de Producto Terminado (Lote #{{ qcForm.id_production }})
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isQCOpen = false" />
          </div>

          <!-- Ficha de producción evaluada -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
              <p class="text-xxs text-slate-500 uppercase font-black tracking-wider">Producto Final Envasado</p>
              <p class="text-base font-black text-slate-800 uppercase mt-1">
                {{ qcForm.title_product }}
              </p>
              <p class="text-xxs text-slate-400 mt-0.5">Receta base: {{ qcForm.recipe_name }}</p>
            </div>
            <div class="md:text-right">
              <p class="text-xxs text-slate-500 uppercase font-black tracking-wider">Cantidad Envasada Declarada</p>
              <p class="text-xl font-black text-indigo-600 mt-1">
                {{ parseFloat(qcForm.total_qty).toLocaleString() }} <span class="text-sm font-bold">{{ qcForm.unit_product }}</span>
              </p>
            </div>
          </div>

          <!-- SECCIÓN: Resultado General de Control de Calidad -->
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Resultado del Control</label>
            <div class="flex flex-col sm:flex-row gap-4 bg-slate-50/50 p-4 rounded-xl border border-slate-200">
              <label class="flex items-center gap-2 cursor-pointer font-bold text-sm text-green-600">
                <input type="radio" v-model="qcForm.result_qc" value="aprobado" class="accent-green-600 w-4 h-4">
                Aprobado Directo
              </label>
              <label class="flex items-center gap-2 cursor-pointer font-bold text-sm text-amber-600">
                <input type="radio" v-model="qcForm.result_qc" value="aprobado_con_obs" class="accent-amber-600 w-4 h-4">
                Aprobado con Observación
              </label>
              <label class="flex items-center gap-2 cursor-pointer font-bold text-sm text-rose-600">
                <input type="radio" v-model="qcForm.result_qc" value="rechazado" class="accent-rose-600 w-4 h-4">
                Rechazado
              </label>
            </div>
          </div>

          <!-- SECCIÓN: Cantidades (Aprobadas / Rechazadas) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-xs font-bold text-green-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <UIcon name="i-lucide-check-circle" /> Cantidad Aprobada
              </label>
              <div class="relative rounded-lg shadow-sm">
                <input
                  :value="formatQcInput(qcForm.qty_approved)"
                  type="text"
                  inputmode="decimal"
                  placeholder="0"
                  class="block w-full py-2.5 px-3 pr-12 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                  @input="onQcNumInput($event, 'qty_approved')"
                  @keydown="blockNegative($event as KeyboardEvent)"
                >
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-xs font-bold uppercase">
                  {{ qcForm.unit_product }}
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-rose-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <UIcon name="i-lucide-x-circle" /> Cantidad Rechazada (Merma QC)
              </label>
              <div class="relative rounded-lg shadow-sm">
                <input
                  :value="formatQcInput(qcForm.qty_rejected)"
                  type="text"
                  inputmode="decimal"
                  placeholder="0"
                  class="block w-full py-2.5 px-3 pr-12 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500/50"
                  @input="onQcNumInput($event, 'qty_rejected')"
                  @keydown="blockNegative($event as KeyboardEvent)"
                >
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-xs font-bold uppercase">
                  {{ qcForm.unit_product }}
                </div>
              </div>
            </div>
          </div>

          <!-- SECCIÓN: Observaciones -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
              Observaciones / Detalle Técnico del Defecto
              <span v-if="qcForm.result_qc !== 'aprobado' || parseFloat(qcForm.qty_rejected) > 0" class="text-rose-500 font-extrabold">(Obligatorio)</span>
            </label>
            <textarea
              v-model="qcForm.notes_qc"
              rows="4"
              placeholder="Describa el motivo de la merma, rechazo u observaciones sobre la calidad del envasado, envases rotos, problemas de tapado, etc..."
              class="block w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
            ></textarea>
          </div>

          <!-- Footer Buttons -->
          <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 mt-6">
            <UButton label="Cancelar" variant="ghost" color="neutral" @click="isQCOpen = false" />
            <UButton
              label="Registrar Auditoría de Calidad"
              color="success"
              class="font-bold!"
              @click="submitQC"
            />
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>

<style scoped>
.text-xxs {
  font-size: 0.7rem;
}
</style>
