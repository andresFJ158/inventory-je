<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '~/stores/auth'
import { getImageUrl } from '~/utils/image'

const auth = useAuthStore()
const api = useApi()
const toast = useToast()
const route = useRoute()

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
const searchVendedor = ref('')
const searchHistory = ref('')
const searchOrders = ref('')
const searchDispatched = ref('')
const filteredPendingRequests = computed(() => {
  if (!searchVendedor.value) return pendingRequests.value
  const q = searchVendedor.value.toLowerCase()
  return pendingRequests.value.filter(req => req.name_admin && req.name_admin.toLowerCase().includes(q))
})
const filteredHistoryRequests = computed(() => {
  if (!searchHistory.value) return historyRequests.value
  const q = searchHistory.value.toLowerCase()
  return historyRequests.value.filter(req => req.name_admin && req.name_admin.toLowerCase().includes(q))
})
const filteredPendingOrders = computed(() => {
  if (!searchOrders.value) return pendingOrders.value
  const q = searchOrders.value.toLowerCase()
  return pendingOrders.value.filter((o: any) => (o.vendedor_name && o.vendedor_name.toLowerCase().includes(q)) || (o.name_client && o.name_client.toLowerCase().includes(q)))
})
const filteredDispatchedOrders = computed(() => {
  if (!searchDispatched.value) return dispatchedOrders.value
  const q = searchDispatched.value.toLowerCase()
  return dispatchedOrders.value.filter((o: any) => (o.vendedor_name && o.vendedor_name.toLowerCase().includes(q)) || (o.name_client && o.name_client.toLowerCase().includes(q)))
})
const historyRequests = ref<any[]>([])
const loadingPending = ref(true)
const loadingHistory = ref(true)
const activeTab = ref(route.query.tab ? parseInt(String(route.query.tab), 10) : 0)

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
  fetchPendingOrders()
  fetchExpenseTemplates()
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
  { label: 'Órdenes por Despachar', icon: 'i-lucide-package-check', value: 0 },
  { label: 'Órdenes Despachadas', icon: 'i-lucide-check-circle', value: 1 }
]

// ─── ÓRDENES POR DESPACHAR ───────────────────────────────────────────────────
const pendingOrders = ref<any[]>([])
const loadingOrders = ref(false)
const expandedOrderId = ref<number | null>(null)
const orderExpenses = ref<Record<number, any[]>>({})
const loadingExpenses = ref<Record<number, boolean>>({})
const dispatchingOrderId = ref<number | null>(null)

// ─── ÓRDENES DESPACHADAS ─────────────────────────────────────────────────────
const dispatchedOrders = ref<any[]>([])
const loadingDispatched = ref(false)

// Gastos: modal para añadir gasto al orden
const isAddExpenseOpen = ref(false)
const expenseOrderId = ref<number | null>(null)
const expenseConcept = ref('')
const expenseAmount = ref('')
const expenseChargeClient = ref(false)
const savingExpense = ref(false)
const expenseQuantity = ref(1)

const expenseTemplates = ref<any[]>([])
const selectedTemplateId = ref('')

const templateOptions = computed(() => {
  return [
    { label: 'Personalizado (Sin plantilla)', value: 'none' },
    ...expenseTemplates.value.map(t => ({
      label: `${t.concept_template} (Bs. ${Number(t.amount_template).toFixed(2)})`,
      value: String(t.id_template)
    }))
  ]
})

const detailsOrderExpenses = computed(() => {
  if (!selectedOrderDetails.value) return []
  return orderExpenses.value[selectedOrderDetails.value.id_order] || []
})

const detailsClientExpensesTotal = computed(() => {
  return detailsOrderExpenses.value
    .filter((e: any) => parseInt(e.charge_to_client_expense) === 1)
    .reduce((sum: number, e: any) => sum + parseFloat(e.amount_expense || 0), 0)
})

const detailsCompanyExpensesTotal = computed(() => {
  return detailsOrderExpenses.value
    .filter((e: any) => parseInt(e.charge_to_client_expense) === 0)
    .reduce((sum: number, e: any) => sum + parseFloat(e.amount_expense || 0), 0)
})

async function fetchExpenseTemplates() {
  try {
    const res = await api.ajax({ getWarehouseExpenseTemplates: 'ok' })
    if (res?.status === 200) {
      expenseTemplates.value = res.results || []
    }
  } catch (error) {
    console.error('Error fetching templates:', error)
  }
}

watch(selectedTemplateId, (newVal) => {
  if (!newVal || newVal === 'none') return
  const found = expenseTemplates.value.find(t => String(t.id_template) === newVal)
  if (found) {
    expenseConcept.value = found.concept_template
    expenseAmount.value = String(found.amount_template)
    expenseChargeClient.value = parseInt(found.charge_to_client_template) === 1
    expenseQuantity.value = 1
  }
})

// Detalles de Orden
const isOrderDetailsOpen = ref(false)
const selectedOrderDetails = ref<any>(null)
const orderItems = ref<any[]>([])
const loadingOrderItems = ref(false)

function decodeStr(val: any) {
  if (!val) return ''
  return decodeURIComponent(String(val)).replace(/\+/g, ' ')
}

async function openOrderDetails(order: any) {
  selectedOrderDetails.value = order
  isOrderDetailsOpen.value = true
  loadingOrderItems.value = true
  orderItems.value = []

  // Fetch expenses
  try {
    const expData = await api.ajax({ getOrderExpenses: 'ok', id_order: String(order.id_order) })
    orderExpenses.value[order.id_order] = expData.results || []
  } catch (e) {
    console.error('Error fetching order expenses for details:', e)
    orderExpenses.value[order.id_order] = []
  }

  try {
    const data = await api.rest<any>(`/api/relations?rel=sales,products&type=sale,product&linkTo=id_order_sale&equalTo=${order.id_order}`)
    if (data && data.status === 200) {
      orderItems.value = data.results || []
    }
  } catch (e) {
    console.error('Error fetching order items:', e)
  } finally {
    loadingOrderItems.value = false
  }
}

async function fetchDispatchedOrders() {
  loadingDispatched.value = true
  try {
    const officeId = auth.officeId ?? 0
    const warehouseId = auth.warehouseId ?? 0
    const data = await api.ajax({ getDispatchedOrdersHistory: 'ok', id_office: String(officeId), id_warehouse: String(warehouseId) })
    dispatchedOrders.value = data.results || []
  } catch (e) {
    console.error('Error fetching dispatched orders:', e)
  } finally {
    loadingDispatched.value = false
  }
}


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

async function openAddExpense(orderId: number) {
  expenseOrderId.value = orderId
  expenseConcept.value = ''
  expenseAmount.value = ''
  expenseChargeClient.value = false
  expenseQuantity.value = 1
  selectedTemplateId.value = 'none'
  isAddExpenseOpen.value = true

  // Fetch existing expenses to make sure they are loaded for validation
  if (!orderExpenses.value[orderId]) {
    loadingExpenses.value[orderId] = true
    try {
      const data = await api.ajax({ getOrderExpenses: 'ok', id_order: String(orderId) })
      orderExpenses.value[orderId] = data.results || []
    } catch (e) {
      console.error('Error fetching expenses:', e)
    } finally {
      loadingExpenses.value[orderId] = false
    }
  }
}

async function saveExpense() {
  if (!expenseOrderId.value || !expenseConcept.value.trim() || !expenseAmount.value) return
  
  const qty = Math.max(1, parseInt(String(expenseQuantity.value)) || 1)
  const amountVal = parseFloat(expenseAmount.value) || 0
  const expenseTotal = amountVal * qty
  const orderId = expenseOrderId.value

  // Client-side validation: total cannot be negative for company expenses
  if (!expenseChargeClient.value) {
    const order = pendingOrders.value.find((o: any) => o.id_order === orderId) || 
                  dispatchedOrders.value.find((o: any) => o.id_order === orderId)
    
    if (order) {
      const baseTotal = parseFloat(order.total_order) || 0
      const existingExpenses = orderExpenses.value[orderId] || []
      const existingClientTotal = existingExpenses
        .filter((e: any) => parseInt(e.charge_to_client_expense) === 1)
        .reduce((sum: number, e: any) => sum + parseFloat(e.amount_expense || 0), 0)
      const existingCompanyTotal = existingExpenses
        .filter((e: any) => parseInt(e.charge_to_client_expense) === 0)
        .reduce((sum: number, e: any) => sum + parseFloat(e.amount_expense || 0), 0)
      
      const currentMax = baseTotal + existingClientTotal - existingCompanyTotal
      if (expenseTotal > currentMax) {
        toast.add({ 
          title: 'Gasto excedido', 
          description: `El total de la orden no puede ser negativo. El saldo disponible es Bs. ${currentMax.toFixed(2)}.`, 
          color: 'error' 
        })
        return
      }
    }
  }

  savingExpense.value = true
  
  let finalConcept = expenseConcept.value.trim()
  if (qty > 1) {
    finalConcept = `${finalConcept} x${qty}`
  }
  const totalAmount = String(expenseTotal.toFixed(2))

  try {
    const res = await api.ajax({
      addOrderExpense: 'ok',
      id_order: String(expenseOrderId.value),
      concept: finalConcept,
      amount: totalAmount,
      charge_to_client: expenseChargeClient.value ? '1' : '0',
      id_admin: String(auth.user?.id_admin ?? 0)
    })
    
    if (res.status === 200) {
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
    } else {
      toast.add({ title: res.message || 'Error al guardar gasto', color: 'error' })
    }
  } catch (e) {
    toast.add({ title: 'Error al guardar gasto', color: 'error' })
  } finally {
    savingExpense.value = false
  }
}

async function dispatchOrder(order: any) {
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


watch(activeTab, (newTab) => {
  const tabIdx = typeof newTab === 'number' ? newTab : parseInt(String(newTab), 10)
  if (tabIdx === 0) fetchPendingOrders()
  else if (tabIdx === 1) fetchDispatchedOrders()
})

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
        @click="activeTab === 0 ? fetchPendingOrders() : fetchDispatchedOrders()"
      >
        Refrescar
      </UButton>
    </div>

    <!-- Tabs Layout -->
    <UTabs :items="tabsItems" v-model="activeTab" class="w-full">
      <template #content="{ index }">
        <!-- TAB 0: Órdenes por Despachar -->
        <div v-if="index === 0" class="mt-4 space-y-3">
          <div class="flex justify-end">
            <UInput
              v-model="searchOrders"
              icon="i-lucide-search"
              placeholder="Filtrar por vendedor..."
              class="w-full md:w-64"
            />
          </div>
          <div v-if="loadingOrders" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="filteredPendingOrders.length === 0" class="text-center py-12 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 text-sm">
            <UIcon name="i-lucide-package-check" class="w-10 h-10 mx-auto mb-2 text-slate-700" />
            No hay órdenes pendientes de despacho.
          </div>

          <template v-else>
            <div v-for="order in filteredPendingOrders" :key="order.id_order" class="bg-white border border-slate-200 rounded-xl overflow-hidden">
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
                  <span class="text-slate-800 font-medium">{{ order.name_client || 'Sin cliente' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Vendedor</span>
                  <span class="text-slate-800 font-medium">{{ order.vendedor_name || '-' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Método Pago</span>
                  <span class="text-slate-800 font-medium capitalize">{{ order.method_order || '-' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Total</span>
                  <span class="text-emerald-500 font-bold font-mono">Bs. {{ (Number(order.total_order || 0) + Number(order.client_expenses_total || 0) - Number(order.company_expenses_total || 0)).toFixed(2) }}</span>
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
                  size="xs" color="primary" variant="soft"
                  icon="i-lucide-eye"
                  @click="openOrderDetails(order)"
                >
                  Ver Detalles
                </UButton>
                <UButton
                  size="xs" color="info" variant="soft"
                  icon="i-lucide-plus"
                  @click="openAddExpense(order.id_order)"
                >
                  Añadir Gasto
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
        
        <!-- TAB 1: Órdenes Despachadas -->
        <div v-if="index === 1" class="mt-4 space-y-3">
          <div class="flex justify-end">
            <UInput
              v-model="searchDispatched"
              icon="i-lucide-search"
              placeholder="Filtrar por vendedor..."
              class="w-full md:w-64"
            />
          </div>
          <div v-if="loadingDispatched" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="filteredDispatchedOrders.length === 0" class="text-center py-12 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 text-sm">
            <UIcon name="i-lucide-check-circle" class="w-10 h-10 mx-auto mb-2 text-slate-700" />
            No hay historial de órdenes despachadas.
          </div>

          <template v-else>
            <div v-for="order in filteredDispatchedOrders" :key="order.id_order" class="bg-white border border-slate-200 rounded-xl overflow-hidden opacity-80 hover:opacity-100 transition-opacity">
              <!-- Order Header -->
              <div class="p-4 flex flex-wrap gap-3 items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="text-xs font-mono text-slate-400">{{ order.transaction_order || '#' + order.id_order }}</span>
                  <UBadge color="success" variant="subtle" size="xs">Despachado</UBadge>
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
                  <span class="text-slate-800 font-medium">{{ order.name_client || 'Sin cliente' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Vendedor</span>
                  <span class="text-slate-800 font-medium">{{ order.vendedor_name || '-' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Método Pago</span>
                  <span class="text-slate-800 font-medium capitalize">{{ order.method_order || '-' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Total</span>
                  <span class="text-emerald-500 font-bold font-mono">Bs. {{ (Number(order.total_order || 0) + Number(order.client_expenses_total || 0) - Number(order.company_expenses_total || 0)).toFixed(2) }}</span>
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
                  size="xs" color="primary" variant="soft"
                  icon="i-lucide-eye"
                  @click="openOrderDetails(order)"
                >
                  Ver Detalles
                </UButton>
                <UButton
                  v-if="order.has_proof == '1' || order.has_proof === 1"
                  size="xs" color="neutral" variant="soft"
                  icon="i-lucide-eye"
                  :href="getImageUrl(order.proof_file)" target="_blank"
                >
                  Ver Comprobante
                </UButton>
              </div>

              <!-- Gastos expandidos -->
              <div v-if="expandedOrderId === order.id_order" class="border-t border-slate-200 px-4 py-3 bg-slate-50">
                <div v-if="loadingExpenses[order.id_order]" class="text-xs text-slate-500 py-2">Cargando gastos...</div>
                <div v-else-if="!orderExpenses[order.id_order]?.length" class="text-xs text-slate-500 py-2">Sin gastos registrados para esta orden.</div>
                <table v-else class="w-full text-xs text-slate-700">
                  <thead>
                    <tr class="text-slate-500 border-b border-slate-200 font-bold uppercase tracking-wider">
                      <th class="py-1 text-left">Concepto</th>
                      <th class="py-1 text-center">Cobrar al Cliente</th>
                      <th class="py-1 text-right">Monto</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="exp in orderExpenses[order.id_order]" :key="exp.id_expense" class="border-b border-slate-100">
                      <td class="py-1.5 font-semibold text-slate-800">{{ exp.concept_expense }}</td>
                      <td class="py-1.5 text-center">
                        <UBadge :color="exp.charge_to_client_expense == 1 ? 'success' : 'neutral'" variant="soft" size="xs">
                          {{ exp.charge_to_client_expense == 1 ? 'Sí' : 'No' }}
                        </UBadge>
                      </td>
                      <td class="py-1.5 text-right font-mono font-bold text-slate-800">Bs. {{ Number(exp.amount_expense).toFixed(2) }}</td>
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
          <UFormField label="Plantilla de Gasto">
            <USelect
              v-model="selectedTemplateId"
              :items="templateOptions"
              class="w-full"
              placeholder="Selecciona una plantilla..."
            />
          </UFormField>
          <UFormField label="Concepto">
            <UInput
              v-model="expenseConcept"
              placeholder="Ej. Delivery, Empaque..."
              class="w-full"
              @input="selectedTemplateId = 'none'"
            />
          </UFormField>
          <div class="grid grid-cols-2 gap-4">
            <UFormField label="Monto Unitario (Bs.)">
              <UInput
                v-model="expenseAmount"
                type="number"
                min="0"
                step="0.01"
                placeholder="0.00"
                class="w-full"
                @input="selectedTemplateId = 'none'"
              />
            </UFormField>
            <UFormField label="Cantidad">
              <UInput
                v-model.number="expenseQuantity"
                type="number"
                min="1"
                step="1"
                placeholder="1"
                class="w-full"
              />
            </UFormField>
          </div>
          <div v-if="expenseAmount && expenseQuantity > 1" class="text-right text-xs font-semibold text-slate-500">
            Monto Total: <span class="text-emerald-600 font-mono">Bs. {{ (parseFloat(expenseAmount) * expenseQuantity).toFixed(2) }}</span>
          </div>
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

    <!-- Modal: Detalles de la Orden -->
    <UModal v-model:open="isOrderDetailsOpen" title="Detalles de la Orden" :ui="{ content: 'sm:max-w-2xl lg:max-w-3xl', body: 'max-h-[85vh] overflow-y-auto' }">
      <template #body>
        <div v-if="selectedOrderDetails" class="space-y-6">
          <!-- Loading State for items -->
          <div v-if="loadingOrderItems" class="flex flex-col items-center justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-teal-500 mb-2" />
            <p class="text-sm text-slate-500">Cargando productos de la orden...</p>
          </div>

          <div v-else class="space-y-6">
            <!-- Dos Columnas: Cliente y Orden -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              
              <!-- Información del Cliente -->
              <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60 space-y-3 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5 border-b border-slate-200/80 pb-1.5">
                  <UIcon name="i-lucide-user" class="text-teal-500 w-4 h-4 animate-pulse" />
                  Información del Cliente
                </h3>
                <div class="space-y-2 text-xs">
                  <div>
                    <span class="text-slate-400 block">Nombre Completo</span>
                    <span class="text-slate-800 font-semibold text-sm">
                      {{ decodeStr(selectedOrderDetails.name_client) }} {{ decodeStr(selectedOrderDetails.surname_client) }}
                    </span>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <span class="text-slate-400 block">Celular / Teléfono</span>
                      <span class="text-slate-800 font-medium">{{ selectedOrderDetails.phone_client || 'No registrado' }}</span>
                    </div>
                    <div>
                      <span class="text-slate-400 block">DNI / CI</span>
                      <span class="text-slate-800 font-medium font-mono">{{ selectedOrderDetails.dni_client || '-' }}</span>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <span class="text-slate-400 block">NIT</span>
                      <span class="text-slate-800 font-medium font-mono">{{ selectedOrderDetails.nit_client || '-' }}</span>
                    </div>
                    <div>
                      <span class="text-slate-400 block">Email</span>
                      <span class="text-slate-800 font-medium truncate block" :title="selectedOrderDetails.email_client">
                        {{ selectedOrderDetails.email_client || 'No registrado' }}
                      </span>
                    </div>
                  </div>
                  <div>
                    <span class="text-slate-400 block">Dirección</span>
                    <span class="text-slate-800 font-medium truncate block" :title="decodeStr(selectedOrderDetails.address_client)">
                      {{ decodeStr(selectedOrderDetails.address_client) || 'No registrada' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Información de la Orden -->
              <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60 space-y-3 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5 border-b border-slate-200/80 pb-1.5">
                  <UIcon name="i-lucide-file-text" class="text-emerald-500 w-4 h-4" />
                  Información de la Orden
                </h3>
                <div class="space-y-2 text-xs">
                  <div class="flex justify-between items-center">
                    <div>
                      <span class="text-slate-400 block">Transacción / ID</span>
                      <span class="text-slate-800 font-semibold font-mono text-sm">
                        {{ selectedOrderDetails.transaction_order || '#' + selectedOrderDetails.id_order }}
                      </span>
                    </div>
                    <div>
                      <UBadge :color="selectedOrderDetails.status_order === 'Despachado' ? 'success' : 'warning'" variant="subtle" size="xs">
                        {{ selectedOrderDetails.status_order }}
                      </UBadge>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <span class="text-slate-400 block">Fecha de Registro</span>
                      <span class="text-slate-800 font-medium">{{ selectedOrderDetails.date_created_order }}</span>
                    </div>
                    <div>
                      <span class="text-slate-400 block">Vendedor</span>
                      <span class="text-slate-800 font-medium">{{ decodeStr(selectedOrderDetails.vendedor_name) || '-' }}</span>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <span class="text-slate-400 block">Método de Pago / Factura</span>
                      <span class="text-slate-800 font-medium capitalize flex items-center gap-1">
                        {{ selectedOrderDetails.method_order || '-' }}
                        <span v-if="selectedOrderDetails.invoice_order" class="text-xxs px-1 py-0.25 bg-blue-100 text-blue-600 rounded">Factura</span>
                      </span>
                    </div>
                    <div>
                      <span class="text-slate-400 block">Total de la Venta</span>
                      <span class="text-emerald-600 font-bold font-mono text-sm">Bs. {{ Number(selectedOrderDetails.total_order || 0).toFixed(2) }}</span>
                    </div>
                  </div>
                </div>
              </div>

            </div>

            <!-- Tabla de Productos -->
            <div class="space-y-2">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <UIcon name="i-lucide-shopping-bag" class="text-indigo-500 w-4 h-4" />
                Productos en la Orden
              </h3>
              
              <div class="border border-slate-200/80 rounded-xl overflow-hidden bg-white shadow-sm">
                <table class="w-full text-left border-collapse text-xs text-slate-700">
                  <thead>
                    <tr class="bg-slate-50 text-slate-500 border-b border-slate-200 font-bold uppercase tracking-wider">
                      <th class="p-3">SKU</th>
                      <th class="p-3">Producto</th>
                      <th class="p-3 text-right">Cantidad</th>
                      <th class="p-3 text-right">Precio Unitario</th>
                      <th class="p-3 text-right">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in orderItems" :key="item.id_sale" class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                      <td class="p-3 font-mono text-slate-400">{{ item.sku_product || '-' }}</td>
                      <td class="p-3 font-semibold text-slate-800">{{ decodeStr(item.title_product) }}</td>
                      <td class="p-3 text-right font-mono font-medium">
                        <UBadge color="neutral" variant="soft" size="xs">{{ item.qty_sale }}</UBadge>
                      </td>
                      <td class="p-3 text-right font-mono text-slate-500">Bs. {{ Number(item.subtotal_sale / item.qty_sale).toFixed(2) }}</td>
                      <td class="p-3 text-right font-mono font-bold text-slate-800">Bs. {{ Number(item.subtotal_sale).toFixed(2) }}</td>
                    </tr>
                    <tr v-if="orderItems.length === 0">
                      <td colspan="5" class="p-8 text-center text-slate-400 italic">No se encontraron productos para esta orden.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Gastos de la Orden -->
            <div v-if="detailsOrderExpenses.length > 0" class="space-y-2">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <UIcon name="i-lucide-receipt" class="text-amber-500 w-4 h-4" />
                Gastos de la Orden
              </h3>
              <div class="border border-slate-200/80 rounded-xl overflow-hidden bg-white shadow-sm">
                <table class="w-full text-left border-collapse text-xs text-slate-700">
                  <thead>
                    <tr class="bg-slate-50 text-slate-500 border-b border-slate-200 font-bold uppercase tracking-wider">
                      <th class="p-3">Concepto</th>
                      <th class="p-3 text-center">Cobrar al Cliente</th>
                      <th class="p-3 text-right">Monto</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="exp in detailsOrderExpenses" :key="exp.id_expense" class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                      <td class="p-3 font-semibold text-slate-800">{{ exp.concept_expense }}</td>
                      <td class="p-3 text-center">
                        <UBadge :color="exp.charge_to_client_expense == 1 ? 'success' : 'neutral'" variant="soft" size="xs">
                          {{ exp.charge_to_client_expense == 1 ? 'Sí' : 'No' }}
                        </UBadge>
                      </td>
                      <td class="p-3 text-right font-mono font-bold text-slate-800">Bs. {{ Number(exp.amount_expense).toFixed(2) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Desglose Financiero de la Orden -->
            <div class="flex justify-end">
              <div class="w-full md:w-64 bg-slate-50 p-4 rounded-xl border border-slate-200/60 space-y-1.5 text-xs font-mono shadow-sm">
                <div class="flex justify-between text-slate-500">
                  <span>Subtotal:</span>
                  <span>Bs. {{ Number(selectedOrderDetails.subtotal_order || selectedOrderDetails.total_order || 0).toFixed(2) }}</span>
                </div>
                <div v-if="Number(selectedOrderDetails.discount_order) > 0" class="flex justify-between text-rose-500">
                  <span>Descuento:</span>
                  <span>- Bs. {{ Number(selectedOrderDetails.discount_order).toFixed(2) }}</span>
                </div>
                <div v-if="detailsClientExpensesTotal > 0" class="flex justify-between text-amber-600 font-bold">
                  <span>Gastos Cliente:</span>
                  <span>+ Bs. {{ detailsClientExpensesTotal.toFixed(2) }}</span>
                </div>
                <div v-if="detailsCompanyExpensesTotal > 0" class="flex justify-between text-rose-500 font-bold">
                  <span>Gastos Almacén:</span>
                  <span>- Bs. {{ detailsCompanyExpensesTotal.toFixed(2) }}</span>
                </div>
                <div class="border-t border-slate-200/80 pt-1.5 flex justify-between font-bold text-sm text-slate-850">
                  <span>TOTAL:</span>
                  <span class="text-emerald-600 font-bold">Bs. {{ (Number(selectedOrderDetails.total_order || 0) + detailsClientExpensesTotal - detailsCompanyExpensesTotal).toFixed(2) }}</span>
                </div>
              </div>
            </div>

            <!-- Botones de Acción en Footer -->
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
              <UButton color="neutral" variant="ghost" @click="isOrderDetailsOpen = false">Cerrar</UButton>
              <UButton
                v-if="selectedOrderDetails.status_order === 'Pendiente Despacho'"
                color="success"
                icon="i-lucide-truck"
                :loading="dispatchingOrderId === selectedOrderDetails.id_order"
                @click="dispatchOrder(selectedOrderDetails); isOrderDetailsOpen = false"
              >
                Despachar Orden
              </UButton>
            </div>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>
