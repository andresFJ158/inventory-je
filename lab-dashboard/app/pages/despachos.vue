<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { getImageUrl } from '~/utils/image'

const auth = useAuthStore()
const api = useApi()
const toast = useToast()

function blockNegative(e: KeyboardEvent) {
  if (e.key === '-' || e.key === 'e' || e.key === 'E') e.preventDefault()
}

function onDispatchQtyInput(e: Event) {
  const input = e.target as HTMLInputElement
  const raw = input.value.replace(/[^\d]/g, '')
  const n = parseInt(raw, 10) || 0
  dispatchQty.value = Math.max(0, n)
  input.value = n > 0 ? n.toLocaleString('de-DE') : ''
}

// State
const pendingRequests = ref<any[]>([])
const historyRequests = ref<any[]>([])
const loadingPending = ref(true)
const loadingHistory = ref(true)
const activeTab = ref(0)

// Dispatch Dialog
const isDispatchOpen = ref(false)
const selectedRequest = ref<any>(null)
const dispatchQty = ref(1)
const dispatchNotes = ref('')
const processingAction = ref(false)

// Reject Dialog
const isRejectOpen = ref(false)
const rejectNotes = ref('')

// Fetch Pending
async function fetchPending() {
  loadingPending.value = true
  try {
    const officeId = auth.officeId ?? 3
    const whId = auth.warehouseId || 0
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getPendingRequests: 'true',
        id_office: String(officeId),
        id_warehouse: String(whId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    
    const data = typeof response === 'string' ? JSON.parse(response) : response
    pendingRequests.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Error fetching pending requests:', e)
    pendingRequests.value = []
  } finally {
    loadingPending.value = false
  }
}

// Fetch History
async function fetchHistory() {
  loadingHistory.value = true
  try {
    const officeId = auth.officeId ?? 3
    const whId = auth.warehouseId || 0
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getRequestHistory: 'true',
        id_office: String(officeId),
        id_warehouse: String(whId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    
    const data = typeof response === 'string' ? JSON.parse(response) : response
    historyRequests.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Error fetching history:', e)
    historyRequests.value = []
  } finally {
    loadingHistory.value = false
  }
}

onMounted(() => {
  fetchPending()
  fetchHistory()
  fetchPendingOrders()
})

// Action: Open Dispatch Dialog
function startDispatch(req: any) {
  selectedRequest.value = req
  dispatchQty.value = parseInt(req.qty_request)
  dispatchNotes.value = ''
  isDispatchOpen.value = true
}

// Action: Confirm Dispatch
async function confirmDispatch() {
  if (!selectedRequest.value) return
  if (dispatchQty.value <= 0 || dispatchQty.value > selectedRequest.value.available_stock) {
    toast.add({ title: 'Cantidad de despacho no válida o excede el stock disponible.', color: 'error' })
    return
  }

  processingAction.value = true
  try {
    const officeId = auth.officeId ?? 3
    const adminId = auth.user?.id_admin || 1

    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        dispatchRequest: 'true',
        id_request: String(selectedRequest.value.id_request),
        qty_dispatch: String(dispatchQty.value),
        notes_dispatcher: dispatchNotes.value,
        id_dispatched_by: String(adminId),
        id_office: String(officeId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    if (String(res).trim() === 'ok') {
      toast.add({ title: 'Despacho enviado.', description: 'Quedó pendiente de recepción en destino.', color: 'success' })
      isDispatchOpen.value = false
      await fetchPending()
      await fetchHistory()
    } else {
      toast.add({ title: String(res) || 'Error al despachar la solicitud.', color: 'error' })
    }
  } catch (e) {
    console.error('Dispatch error:', e)
    toast.add({ title: 'Error al conectar con la API de despacho.', color: 'error' })
  } finally {
    processingAction.value = false
  }
}

// Action: Open Reject Dialog
function startReject(req: any) {
  selectedRequest.value = req
  rejectNotes.value = ''
  isRejectOpen.value = true
}

// Action: Confirm Reject
async function confirmReject() {
  if (!selectedRequest.value) return
  processingAction.value = true
  try {
    const adminId = auth.user?.id_admin || 1

    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        rejectRequest: 'true',
        id_request: String(selectedRequest.value.id_request),
        notes_dispatcher: rejectNotes.value,
        id_dispatched_by: String(adminId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    if (String(res).trim() === 'ok') {
      isRejectOpen.value = false
      await fetchPending()
      await fetchHistory()
    } else {
      toast.add({ title: String(res) || 'Error al rechazar la solicitud.', color: 'error' })
    }
  } catch (e) {
    console.error('Reject error:', e)
    toast.add({ title: 'Error al conectar con la API de despacho.', color: 'error' })
  } finally {
    processingAction.value = false
  }
}

const tabsItems = [
  { label: 'Solicitudes Pendientes', icon: 'i-lucide-clock' },
  { label: 'Historial', icon: 'i-lucide-history' },
  { label: 'Órdenes por Despachar', icon: 'i-lucide-package-check' }
]

// ─── ÓRDENES POR DESPACHAR ───────────────────────────────────────────────────
const pendingOrders = ref<any[]>([])
const loadingOrders = ref(false)
const expandedOrderId = ref<number | null>(null)
const orderExpenses = ref<Record<number, any[]>>({})
const loadingExpenses = ref<Record<number, boolean>>({})
const dispatchingOrderId = ref<number | null>(null)
// Gastos: modal para añadir gasto al orden
const isAddExpenseOpen = ref(false)
const expenseOrderId = ref<number | null>(null)
const expenseConcept = ref('')
const expenseAmount = ref('')
const expenseChargeClient = ref(false)
const savingExpense = ref(false)
// Comprobante: modal para subir imagen
const isProofOpen = ref(false)
const proofOrderId = ref<number | null>(null)
const proofFile = ref<File | null>(null)
const uploadingProof = ref(false)

async function fetchPendingOrders() {
  loadingOrders.value = true
  try {
    const officeId = auth.officeId ?? 0
    const warehouseId = auth.warehouseId ?? 0
    const data = await api.ajax({ getPendingDispatchOrders: 'ok', id_office: String(officeId), id_warehouse: String(warehouseId) })
    pendingOrders.value = data.results || []
  } catch (e) {
    console.error('Error fetching pending orders:', e)
  } finally {
    loadingOrders.value = false
  }
}

async function toggleOrderExpenses(orderId: number) {
  if (expandedOrderId.value === orderId) {
    expandedOrderId.value = null
    return
  }
  expandedOrderId.value = orderId
  if (orderExpenses.value[orderId]) return
  loadingExpenses.value[orderId] = true
  try {
    const data = await api.ajax({ getOrderExpenses: 'ok', id_order: String(orderId) })
    orderExpenses.value[orderId] = data.results || []
  } catch (e) {
    orderExpenses.value[orderId] = []
  } finally {
    loadingExpenses.value[orderId] = false
  }
}

function openAddExpense(orderId: number) {
  expenseOrderId.value = orderId
  expenseConcept.value = ''
  expenseAmount.value = ''
  expenseChargeClient.value = false
  isAddExpenseOpen.value = true
}

async function saveExpense() {
  if (!expenseOrderId.value || !expenseConcept.value.trim() || !expenseAmount.value) return
  savingExpense.value = true
  try {
    await api.ajax({
      addOrderExpense: 'ok',
      id_order: String(expenseOrderId.value),
      concept: expenseConcept.value.trim(),
      amount: expenseAmount.value,
      charge_to_client: expenseChargeClient.value ? '1' : '0',
      id_admin: String(auth.user?.id_admin ?? 0)
    })
    delete orderExpenses.value[expenseOrderId.value]
    isAddExpenseOpen.value = false
    toast.add({ title: 'Gasto registrado', color: 'success' })
    if (expandedOrderId.value === expenseOrderId.value) {
      const id = expenseOrderId.value
      loadingExpenses.value[id] = true
      const data = await api.ajax({ getOrderExpenses: 'ok', id_order: String(id) })
      orderExpenses.value[id] = data.results || []
      loadingExpenses.value[id] = false
    }
  } catch (e) {
    toast.add({ title: 'Error al guardar gasto', color: 'error' })
  } finally {
    savingExpense.value = false
  }
}

function openUploadProof(order: any) {
  proofOrderId.value = order.id_order
  proofFile.value = null
  isProofOpen.value = true
}

async function uploadProof() {
  if (!proofOrderId.value || !proofFile.value) return
  uploadingProof.value = true
  try {
    const fd = new FormData()
    fd.append('uploadSalePayment', 'ok')
    fd.append('id_order', String(proofOrderId.value))
    fd.append('method', 'transferencia')
    fd.append('id_admin', String(auth.user?.id_admin ?? 0))
    fd.append('proof', proofFile.value)
    const data = await api.ajaxForm(fd)
    if (data.status === 200) {
      isProofOpen.value = false
      toast.add({ title: 'Comprobante subido', color: 'success' })
      await fetchPendingOrders()
    } else {
      toast.add({ title: data.message || 'Error al subir comprobante', color: 'error' })
    }
  } catch (e) {
    toast.add({ title: 'Error al subir comprobante', color: 'error' })
  } finally {
    uploadingProof.value = false
  }
}

async function dispatchOrder(order: any) {
  if (!order.has_proof || order.has_proof == '0') {
    toast.add({ title: 'Verifique el comprobante de pago antes de despachar', color: 'warning' })
    return
  }
  dispatchingOrderId.value = order.id_order
  try {
    const data = await api.ajax({ updateOrderStatus: 'ok', id_order: String(order.id_order), status: 'Despachado' })
    if (data.status === 200) {
      toast.add({ title: 'Orden despachada', color: 'success' })
      await fetchPendingOrders()
    } else {
      toast.add({ title: data.message || 'Error al despachar', color: 'error' })
    }
  } catch (e) {
    toast.add({ title: 'Error al despachar', color: 'error' })
  } finally {
    dispatchingOrderId.value = null
  }
}

</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white border border-slate-200 p-6 rounded-xl flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight bg-gradient-to-r from-teal-400 to-emerald-300 bg-clip-text text-transparent">
          Centro de Despachos
        </h1>
        <p class="text-xs text-slate-400 mt-1">
          Gestiona las solicitudes de despacho de sucursales del inventario principal.
        </p>
      </div>

      <UButton
        icon="i-lucide-refresh-cw"
        color="neutral"
        variant="soft"
        size="xs"
        @click="activeTab === 0 ? fetchPending() : activeTab === 1 ? fetchHistory() : fetchPendingOrders()"
      >
        Refrescar
      </UButton>
    </div>

    <!-- Tabs Layout -->
    <UTabs :items="tabsItems" v-model="activeTab" class="w-full">
      <template #content="{ index }">
        <!-- TAB 0: Pending Requests -->
        <div v-if="index === 0" class="mt-4">
          <div v-if="loadingPending" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="pendingRequests.length === 0" class="text-center py-12 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 text-sm">
            <UIcon name="i-lucide-check-circle" class="w-10 h-10 mx-auto mb-2 text-slate-700" />
            No hay solicitudes de despacho pendientes.
          </div>

          <div v-else class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse text-sm text-slate-700 bg-white">
              <thead>
                <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                  <th class="p-4">Fecha</th>
                  <th class="p-4">Solicitante</th>
                  <th class="p-4">Producto</th>
                  <th class="p-4">Cant. Solicitada</th>
                  <th class="p-4">Stock Disponible</th>
                  <th class="p-4">Notas</th>
                  <th class="p-4 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="req in pendingRequests" :key="req.id_request" class="border-b border-slate-100 hover:bg-slate-50">
                  <td class="p-4 font-mono text-xs">{{ req.date_created_request }}</td>
                  <td class="p-4 font-semibold text-slate-800">{{ req.name_admin }}</td>
                  <td class="p-4">{{ decodeURIComponent(req.title_product || '').replace(/\+/g, ' ') }}</td>
                  <td class="p-4">
                    <UBadge color="info" variant="soft">{{ req.qty_request }}</UBadge>
                  </td>
                  <td class="p-4">
                    <UBadge :color="req.available_stock > 0 ? 'success' : 'error'" variant="subtle">
                      {{ req.available_stock }}
                    </UBadge>
                  </td>
                  <td class="p-4 text-xs italic">{{ req.notes_request || '-' }}</td>
                  <td class="p-4 text-right flex gap-2 justify-end">
                    <UButton
                      color="success"
                      icon="i-lucide-check"
                      size="xs"
                      :disabled="req.available_stock <= 0"
                      @click="startDispatch(req)"
                    >
                      Despachar
                    </UButton>
                    <UButton
                      color="error"
                      icon="i-lucide-x"
                      size="xs"
                      variant="soft"
                      @click="startReject(req)"
                    >
                      Rechazar
                    </UButton>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 1: History -->
        <div v-if="index === 1" class="mt-4">
          <div v-if="loadingHistory" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="historyRequests.length === 0" class="text-center py-12 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 text-sm">
            No hay historial de solicitudes registradas.
          </div>

          <div v-else class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse text-sm text-slate-700 bg-white">
              <thead>
                <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                  <th class="p-4">Fecha</th>
                  <th class="p-4">Solicitante</th>
                  <th class="p-4">Producto</th>
                  <th class="p-4">Solicitado</th>
                  <th class="p-4">Despachado</th>
                  <th class="p-4">Estado</th>
                  <th class="p-4">Notas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="req in historyRequests" :key="req.id_request" class="border-b border-slate-100 hover:bg-slate-50">
                  <td class="p-4 font-mono text-xs">{{ req.date_created_request }}</td>
                  <td class="p-4">{{ req.name_admin }}</td>
                  <td class="p-4">{{ decodeURIComponent(req.title_product || '').replace(/\+/g, ' ') }}</td>
                  <td class="p-4 font-mono">{{ req.qty_request }}</td>
                  <td class="p-4 font-mono">{{ req.qty_dispatched_request || '-' }}</td>
                  <td class="p-4">
                    <UBadge
                      :color="req.status_request === 'recibida' ? 'success' : req.status_request === 'rechazada' ? 'error' : 'warning'"
                      variant="subtle"
                      class="capitalize"
                    >
                      {{ req.status_request }}
                    </UBadge>
                  </td>
                  <td class="p-4 text-xs italic">{{ req.notes_dispatcher_request || req.notes_request || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- TAB 2: Órdenes por Despachar -->
        <div v-if="index === 2" class="mt-4 space-y-3">
          <div v-if="loadingOrders" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="pendingOrders.length === 0" class="text-center py-12 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 text-sm">
            <UIcon name="i-lucide-package-check" class="w-10 h-10 mx-auto mb-2 text-slate-700" />
            No hay órdenes pendientes de despacho.
          </div>

          <template v-else>
            <div v-for="order in pendingOrders" :key="order.id_order" class="bg-white border border-slate-200 rounded-xl overflow-hidden">
              <!-- Order Header -->
              <div class="p-4 flex flex-wrap gap-3 items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="text-xs font-mono text-slate-400">{{ order.transaction_order || '#' + order.id_order }}</span>
                  <UBadge color="warning" variant="subtle" size="xs">Pendiente Despacho</UBadge>
                  <UBadge v-if="order.has_proof == '1' || order.has_proof === 1" color="success" variant="subtle" size="xs" icon="i-lucide-check">Comprobante OK</UBadge>
                  <UBadge v-else color="error" variant="subtle" size="xs" icon="i-lucide-alert-circle">Sin Comprobante</UBadge>
                </div>
                <div class="flex items-center gap-2 ml-auto">
                  <span class="text-xs text-slate-400">{{ order.date_created_order?.split(' ')[0] }}</span>
                </div>
              </div>

              <div class="px-4 pb-3 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                <div>
                  <span class="text-slate-500 block">Cliente</span>
                  <span class="text-white font-medium">{{ order.name_client || 'Sin cliente' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Vendedor</span>
                  <span class="text-white font-medium">{{ order.vendedor_name || '-' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Método Pago</span>
                  <span class="text-white font-medium capitalize">{{ order.method_order || '-' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Total</span>
                  <span class="text-emerald-400 font-bold font-mono">Bs. {{ Number(order.total_order || 0).toFixed(2) }}</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="px-4 pb-4 flex flex-wrap gap-2 border-t border-slate-200 pt-3">
                <UButton
                  size="xs" color="neutral" variant="soft"
                  icon="i-lucide-receipt"
                  @click="toggleOrderExpenses(order.id_order)"
                >
                  {{ expandedOrderId === order.id_order ? 'Ocultar Gastos' : 'Ver Gastos' }}
                </UButton>
                <UButton
                  size="xs" color="info" variant="soft"
                  icon="i-lucide-plus"
                  @click="openAddExpense(order.id_order)"
                >
                  Añadir Gasto
                </UButton>
                <UButton
                  size="xs" color="neutral" variant="soft"
                  icon="i-lucide-image-up"
                  @click="openUploadProof(order)"
                >
                  {{ (order.has_proof == '1' || order.has_proof === 1) ? 'Reemplazar Comprobante' : 'Subir Comprobante' }}
                </UButton>
                <UButton
                  v-if="order.has_proof == '1' || order.has_proof === 1"
                  size="xs" color="neutral" variant="soft"
                  icon="i-lucide-eye"
                  :href="getImageUrl(order.proof_file)" target="_blank"
                >
                  Ver Comprobante
                </UButton>
                <UButton
                  size="xs" color="success"
                  icon="i-lucide-truck"
                  class="ml-auto"
                  :loading="dispatchingOrderId === order.id_order"
                  :disabled="!(order.has_proof == '1' || order.has_proof === 1)"
                  @click="dispatchOrder(order)"
                >
                  Despachar
                </UButton>
              </div>

              <!-- Gastos expandidos -->
              <div v-if="expandedOrderId === order.id_order" class="border-t border-slate-200 px-4 py-3 bg-slate-50">
                <div v-if="loadingExpenses[order.id_order]" class="text-xs text-slate-500 py-2">Cargando gastos...</div>
                <div v-else-if="!orderExpenses[order.id_order]?.length" class="text-xs text-slate-500 py-2">Sin gastos registrados para esta orden.</div>
                <table v-else class="w-full text-xs text-slate-700">
                  <thead>
                    <tr class="text-slate-500 border-b border-slate-200">
                      <th class="py-1 text-left">Concepto</th>
                      <th class="py-1 text-right">Monto</th>
                      <th class="py-1 text-center">Cobrar cliente</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="exp in orderExpenses[order.id_order]" :key="exp.id_expense" class="border-b border-slate-100">
                      <td class="py-1">{{ exp.concept_expense }}</td>
                      <td class="py-1 text-right font-mono text-emerald-400">Bs. {{ Number(exp.amount_expense).toFixed(2) }}</td>
                      <td class="py-1 text-center">
                        <UBadge :color="exp.charge_to_client_expense == 1 ? 'success' : 'neutral'" variant="subtle" size="xs">
                          {{ exp.charge_to_client_expense == 1 ? 'Sí' : 'No' }}
                        </UBadge>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </template>
        </div>
      </template>
    </UTabs>

    <!-- Modal: Añadir Gasto a Orden -->
    <UModal v-model:open="isAddExpenseOpen" title="Añadir Gasto a la Orden">
      <template #body>
        <div class="space-y-4">
          <UFormField label="Concepto">
            <UInput v-model="expenseConcept" placeholder="Ej. Delivery, Empaque..." class="w-full" />
          </UFormField>
          <UFormField label="Monto (Bs.)">
            <UInput v-model="expenseAmount" type="number" min="0" step="0.01" placeholder="0.00" class="w-full" />
          </UFormField>
          <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
            <div>
              <span class="text-sm font-medium text-slate-700 block">Cobrar al cliente</span>
              <span class="text-xs text-slate-500">Si está activo suma al total de la orden</span>
            </div>
            <button
              type="button"
              @click="expenseChargeClient = !expenseChargeClient"
              :class="[
 expenseChargeClient ? 'bg-emerald-500' : 'bg-slate-300',
 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none'
 ]"
            >
              <span :class="[expenseChargeClient ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition']" />
            </button>
          </div>
          <div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
            <UButton color="neutral" variant="ghost" @click="isAddExpenseOpen = false">Cancelar</UButton>
            <UButton color="primary" :loading="savingExpense" @click="saveExpense">Guardar Gasto</UButton>
          </div>
        </div>
      </template>
    </UModal>

    <!-- Modal: Subir Comprobante -->
    <UModal v-model:open="isProofOpen" title="Subir Comprobante de Pago">
      <template #body>
        <div class="space-y-4">
          <p class="text-sm text-slate-600">Sube la imagen del comprobante de pago (JPG, PNG, WEBP o PDF, máx. 5MB).</p>
          <input
            type="file"
            accept="image/*,.pdf"
            class="block w-full text-sm text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
            @change="proofFile = ($event.target as HTMLInputElement).files?.[0] ?? null"
          />
          <div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
            <UButton color="neutral" variant="ghost" @click="isProofOpen = false">Cancelar</UButton>
            <UButton color="primary" :loading="uploadingProof" :disabled="!proofFile" @click="uploadProof">Subir Comprobante</UButton>
          </div>
        </div>
      </template>
    </UModal>

    <!-- Dispatch Quantity Modal -->
    <UModal v-model:open="isDispatchOpen" title="Enviar Despacho">
      <template #body>
        <div v-if="selectedRequest" class="space-y-4">
          <div class="grid grid-cols-2 gap-4 bg-slate-100 p-3 rounded-lg border border-slate-200">
            <div>
              <span class="text-[10px] text-slate-500 block">Solicitante:</span>
              <span class="text-xs font-bold text-slate-800">{{ selectedRequest.name_admin }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 block">Producto:</span>
              <span class="text-xs font-bold text-slate-800 truncate block">{{ decodeURIComponent(selectedRequest.title_product || '').replace(/\+/g, ' ') }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 block">Cant. Solicitada:</span>
              <span class="text-xs font-bold text-teal-400 font-mono">{{ selectedRequest.qty_request }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 block">Stock en Almacén:</span>
              <span class="text-xs font-bold text-emerald-400 font-mono">{{ selectedRequest.available_stock }}</span>
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Cantidad a Despachar *</label>
            <input
              :value="dispatchQty > 0 ? dispatchQty.toLocaleString('de-DE') : ''"
              type="text"
              inputmode="numeric"
              placeholder="0"
              class="block w-full py-2.5 px-3 bg-white border border-slate-200 rounded-lg text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
              @input="onDispatchQtyInput($event)"
              @keydown="blockNegative($event as KeyboardEvent)"
            />
          </div>

          <div>
            <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Notas del Despachador</label>
            <UTextarea v-model="dispatchNotes" :rows="2" placeholder="Opcional..." class="w-full" />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
            <UButton color="neutral" variant="ghost" @click="isDispatchOpen = false">Cancelar</UButton>
            <UButton color="primary" :loading="processingAction" @click="confirmDispatch">Enviar Despacho</UButton>
          </div>
        </div>
      </template>
    </UModal>

    <!-- Reject Modal -->
    <UModal v-model:open="isRejectOpen" title="Rechazar Solicitud de Despacho">
      <template #body>
        <div v-if="selectedRequest" class="space-y-4">
          <p class="text-sm text-slate-700">
            ¿Confirmas el rechazo de la solicitud de {{ selectedRequest.qty_request }} u de <strong>{{ decodeURIComponent(selectedRequest.title_product || '').replace(/\+/g, ' ') }}</strong>?
          </p>

          <div>
            <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Motivo de Rechazo</label>
            <UTextarea v-model="rejectNotes" :rows="2" placeholder="Escribe el motivo del rechazo aquí..." class="w-full" required />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
            <UButton color="neutral" variant="ghost" @click="isRejectOpen = false">Cancelar</UButton>
            <UButton color="error" :loading="processingAction" @click="confirmReject">Rechazar Solicitud</UButton>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>
