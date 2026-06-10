<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

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
    alert('Cantidad de despacho no válida o excede el stock disponible.')
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
      isDispatchOpen.value = false
      await fetchPending()
      await fetchHistory()
    } else {
      alert(res || 'Error al despachar la solicitud.')
    }
  } catch (e) {
    console.error('Dispatch error:', e)
    alert('Error al conectar con la API de despacho.')
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
      alert(res || 'Error al rechazar la solicitud.')
    }
  } catch (e) {
    console.error('Reject error:', e)
    alert('Error al conectar con la API de despacho.')
  } finally {
    processingAction.value = false
  }
}

const tabsItems = [
  { label: 'Solicitudes Pendientes', icon: 'i-lucide-clock' },
  { label: 'Historial', icon: 'i-lucide-history' }
]
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-slate-900/60 border border-slate-800 p-6 rounded-xl flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight bg-gradient-to-r from-teal-400 to-emerald-300 bg-clip-text text-transparent">
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
        @click="activeTab === 0 ? fetchPending() : fetchHistory()"
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

          <div v-else-if="pendingRequests.length === 0" class="text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500 text-sm">
            <UIcon name="i-lucide-check-circle" class="w-10 h-10 mx-auto mb-2 text-slate-650" />
            No hay solicitudes de despacho pendientes.
          </div>

          <div v-else class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse text-sm text-slate-300">
              <thead>
                <tr class="bg-slate-950 text-slate-400 border-b border-slate-800">
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
                <tr v-for="req in pendingRequests" :key="req.id_request" class="border-b border-slate-850 hover:bg-slate-900/20">
                  <td class="p-4 font-mono text-xs">{{ req.date_created_request }}</td>
                  <td class="p-4 font-semibold text-white">{{ req.name_admin }}</td>
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

          <div v-else-if="historyRequests.length === 0" class="text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500 text-sm">
            No hay historial de solicitudes registradas.
          </div>

          <div v-else class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse text-sm text-slate-300">
              <thead>
                <tr class="bg-slate-950 text-slate-400 border-b border-slate-800">
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
                <tr v-for="req in historyRequests" :key="req.id_request" class="border-b border-slate-850 hover:bg-slate-900/20">
                  <td class="p-4 font-mono text-xs">{{ req.date_created_request }}</td>
                  <td class="p-4">{{ req.name_admin }}</td>
                  <td class="p-4">{{ decodeURIComponent(req.title_product || '').replace(/\+/g, ' ') }}</td>
                  <td class="p-4 font-mono">{{ req.qty_request }}</td>
                  <td class="p-4 font-mono">{{ req.qty_dispatched_request || '-' }}</td>
                  <td class="p-4">
                    <UBadge
                      :color="req.status_request === 'despachada' ? 'success' : req.status_request === 'rechazada' ? 'error' : 'warning'"
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
      </template>
    </UTabs>

    <!-- Dispatch Quantity Modal -->
    <UModal v-model:open="isDispatchOpen" title="Confirmar Despacho">
      <template #body>
        <div v-if="selectedRequest" class="space-y-4">
          <div class="grid grid-cols-2 gap-4 bg-slate-950 p-3 rounded-lg border border-slate-850">
            <div>
              <span class="text-[10px] text-slate-500 block">Solicitante:</span>
              <span class="text-xs font-bold text-white">{{ selectedRequest.name_admin }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 block">Producto:</span>
              <span class="text-xs font-bold text-white truncate block">{{ decodeURIComponent(selectedRequest.title_product || '').replace(/\+/g, ' ') }}</span>
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
            <label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1">Cantidad a Despachar *</label>
            <input
              :value="dispatchQty > 0 ? dispatchQty.toLocaleString('de-DE') : ''"
              type="text"
              inputmode="numeric"
              placeholder="0"
              class="block w-full py-2.5 px-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
              @input="onDispatchQtyInput($event)"
              @keydown="blockNegative($event as KeyboardEvent)"
            />
          </div>

          <div>
            <label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1">Notas del Despachador</label>
            <UTextarea v-model="dispatchNotes" :rows="2" placeholder="Opcional..." class="w-full" />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <UButton color="neutral" variant="ghost" @click="isDispatchOpen = false">Cancelar</UButton>
            <UButton color="primary" :loading="processingAction" @click="confirmDispatch">Completar Despacho</UButton>
          </div>
        </div>
      </template>
    </UModal>

    <!-- Reject Modal -->
    <UModal v-model:open="isRejectOpen" title="Rechazar Solicitud de Despacho">
      <template #body>
        <div v-if="selectedRequest" class="space-y-4">
          <p class="text-sm text-slate-300">
            ¿Confirmas el rechazo de la solicitud de {{ selectedRequest.qty_request }} u de <strong>{{ decodeURIComponent(selectedRequest.title_product || '').replace(/\+/g, ' ') }}</strong>?
          </p>

          <div>
            <label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1">Motivo de Rechazo</label>
            <UTextarea v-model="rejectNotes" :rows="2" placeholder="Escribe el motivo del rechazo aquí..." class="w-full" required />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <UButton color="neutral" variant="ghost" @click="isRejectOpen = false">Cancelar</UButton>
            <UButton color="error" :loading="processingAction" @click="confirmReject">Rechazar Solicitud</UButton>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>
