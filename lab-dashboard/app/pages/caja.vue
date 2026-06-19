<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

function blockNegative(e: KeyboardEvent) {
  if (e.key === '-' || e.key === 'e' || e.key === 'E') e.preventDefault()
}

function onOpenAmountInput(e: Event) {
  const input = e.target as HTMLInputElement
  let raw = input.value.replace(/[^\d,]/g, '').replace(',', '.')
  const parts = raw.split('.')
  const intVal = parseInt(parts[0] || '0', 10) || 0
  const decStr = parts[1] !== undefined ? parts[1].slice(0, 2) : undefined
  const n = decStr !== undefined ? parseFloat(`${intVal}.${decStr}`) : intVal
  openAmount.value = n < 0 ? 0 : n
  const formatted = decStr !== undefined
    ? intVal.toLocaleString('de-DE') + ',' + decStr
    : intVal > 0 ? intVal.toLocaleString('de-DE') : ''
  input.value = formatted
}
const toast = useToast()

const apiHeaders = { Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }

function getLocalDate() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
}

const todayStr = ref('')

// State
const loading = ref(true)
const todaysCash = ref<any>(null)
const allCashs = ref<any[]>([])
const offices = ref<any[]>([])

// Apertura de caja
const openModal = ref(false)
const openAmount = ref<number | null>(200)
const opening = ref(false)

// Cierre de caja
const closeModal = ref(false)
const closing = ref(false)
const cashDetails = ref<any>(null)
const loadingDetails = ref(false)
const physicalCash = ref<number | null>(null)

// Detalles de Caja en Slideover
const isDetailsOpen = ref(false)
const selectedDetailsCash = ref<any>(null)

const isSuperAdmin = computed(() => auth.role === 'superadmin' || auth.role === 'admin')

function formatCurrency(val: number) {
  return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(val)
}

async function fetchOffices() {
  try {
    const data = await $fetch<any>('/api/offices', { headers: apiHeaders })
    if (data.status === 200) offices.value = data.results || []
  } catch {}
}

async function fetchCashStatus() {
  loading.value = true
  try {
    if (isSuperAdmin.value) {
      // Superadmin: ver todas las cajas de hoy
      const data = await $fetch<any>(`/api/cashs?linkTo=date_created_cash&equalTo=${todayStr.value}&orderBy=id_cash&orderMode=DESC`, {
        headers: apiHeaders
      })
      if (data.status === 200) allCashs.value = data.results || []
    } else {
      // Cajero: ver su caja ABIERTA actualmente (sin importar si se abrió ayer en la noche)
      const data = await $fetch<any>(`/api/cashs?linkTo=id_office_cash,status_cash&equalTo=${auth.officeId},1&orderBy=id_cash&orderMode=DESC`, {
        headers: apiHeaders
      })
      if (data.status === 200 && data.results) {
        todaysCash.value = Array.isArray(data.results) ? data.results[0] : data.results
      } else {
        todaysCash.value = null
      }
    }
  } catch {}
  loading.value = false
}

async function openCash() {
  if (!openAmount.value || openAmount.value <= 0) {
    toast.add({ title: 'Ingresa un monto inicial válido', color: 'error' })
    return
  }
  opening.value = true
  try {
    const body = new URLSearchParams({
      date_created_cash: todayStr.value,
      id_office_cash: String(auth.officeId),
      id_admin_cash: String(auth.user?.id_admin),
      start_cash: String(openAmount.value),
      status_cash: '1',
      bills_cash: '0',
      money_cash: '0',
      diff_cash: '0'
    })
    const res = await $fetch<any>(`/api/cashs?token=${auth.token}&table=admins&suffix=admin&except=date_end_cash`, {
      method: 'POST',
      body: body.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...apiHeaders }
    })
    const data = typeof res === 'string' ? JSON.parse(res) : res
    if (data.status === 200) {
      toast.add({ title: 'Caja abierta correctamente', color: 'success' })
      openModal.value = false
      openAmount.value = null
      await fetchCashStatus()
    } else {
      toast.add({ title: data.message || 'Error al abrir caja', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de conexión', color: 'error' })
  }
  opening.value = false
}

async function loadCashDetails(cashRow?: any) {
  loadingDetails.value = true
  const cashId = cashRow?.id_cash || todaysCash.value?.id_cash
  try {
    const body = new URLSearchParams({
      getCashDetails: 'ok',
      id_cash: String(cashId)
    })
    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof res === 'string' ? JSON.parse(res) : res
    if (data.status === 200) cashDetails.value = data.results
  } catch {}
  loadingDetails.value = false
}

async function closeCash() {
  if (!isSuperAdmin.value && (physicalCash.value === null || physicalCash.value < 0)) {
    toast.add({ title: 'Ingresa el monto físico contado en caja', color: 'error' })
    return
  }
  closing.value = true
  try {
    const body = new URLSearchParams({
      closeCashRegister: 'ok',
      id_cash: String(todaysCash.value?.id_cash),
      physical_cash: String(physicalCash.value || 0)
    })
    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof res === 'string' ? JSON.parse(res) : res
    if (data.status === 200) {
      toast.add({ title: 'Caja cerrada correctamente', color: 'success' })
      closeModal.value = false
      cashDetails.value = null
      physicalCash.value = null
      await fetchCashStatus()
    } else {
      toast.add({ title: data.message || 'Error al cerrar caja', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de conexión', color: 'error' })
  }
  closing.value = false
}

function getOfficeName(id: any) {
  const office = offices.value.find((o: any) => String(o.id_office) === String(id))
  return office ? decodeURIComponent(office.title_office || '').replace(/\+/g, ' ') : `Sucursal ${id}`
}

onMounted(async () => {
  if (auth.role === 'vendedor') {
    navigateTo('/pos')
    return
  }
  todayStr.value = getLocalDate()
  await fetchOffices()
  await fetchCashStatus()
})
</script>

<template>
  <div class="space-y-6">

    <!-- Vista Cajero -->
    <template v-if="!isSuperAdmin">
      <div v-if="loading" class="flex justify-center py-20">
        <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-green-500" />
      </div>

      <template v-else>
        <!-- Caja ABIERTA -->
        <template v-if="todaysCash && todaysCash.status_cash == 1">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <UCard class="bg-gradient-to-br from-emerald-500 to-green-600 border-0 shadow-lg">
              <div class="flex justify-between items-center text-white">
                <div>
                  <p class="text-emerald-100 text-xs font-bold uppercase tracking-wider">Estado de Caja</p>
                  <h2 class="text-3xl font-black mt-1">ABIERTA</h2>
                  <p class="text-sm mt-1 text-emerald-100">Desde {{ todayStr }}</p>
                </div>
                <UIcon name="i-lucide-wallet" class="w-14 h-14 text-white/30" />
              </div>
            </UCard>

            <UCard>
              <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Monto Inicial</p>
              <h2 class="text-2xl font-black text-slate-800 mt-1">
                {{ formatCurrency(parseFloat(todaysCash.start_cash || 0)) }}
              </h2>
              <p class="text-xs text-slate-400 mt-1">Capital de apertura</p>
            </UCard>

            <UCard class="flex items-center justify-center gap-4 cursor-pointer hover:bg-slate-50 transition-colors" @click="() => { closeModal = true; loadCashDetails() }">
              <UIcon name="i-lucide-x-circle" class="w-10 h-10 text-rose-400" />
              <div>
                <p class="font-bold text-slate-700">Cerrar Caja</p>
                <p class="text-xs text-slate-500">Finalizar jornada del día</p>
              </div>
            </UCard>
          </div>

        </template>

        <!-- Caja CERRADA / Sin abrir -->
        <template v-else>
          <div class="flex flex-col items-center justify-center py-20 space-y-6">
            <div class="w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center">
              <UIcon name="i-lucide-wallet" class="w-12 h-12 text-slate-400" />
            </div>
            <div class="text-center">
              <h2 class="text-2xl font-bold text-slate-700">Caja Cerrada</h2>
              <p class="text-slate-500 mt-1">No hay caja abierta para hoy. Abre la caja para comenzar a operar.</p>
            </div>
            <UButton size="lg" color="primary" icon="i-lucide-plus-circle" @click="openModal = true">
              Abrir Caja
            </UButton>
          </div>
        </template>

        <!-- Historial de Cajas de la Sucursal -->
        <UCard class="mt-6">
          <template #header>
            <div class="flex items-center gap-2">
              <UIcon name="i-lucide-receipt" class="text-green-500" />
              <h3 class="font-bold">Historial de Cajas</h3>
            </div>
          </template>
          <DynamicTable module-name="caja" />
        </UCard>
      </template>
    </template>

    <!-- Vista Superadmin / Admin -->
    <template v-else>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-slate-800">Cajas por Sucursal — Hoy</h2>
          <p class="text-xs text-slate-500">{{ todayStr }}</p>
        </div>
        <UButton icon="i-lucide-refresh-cw" variant="ghost" color="neutral" @click="fetchCashStatus">Actualizar</UButton>
      </div>

      <div v-if="loading" class="flex justify-center py-20">
        <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-green-500" />
      </div>

      <div v-else-if="allCashs.length === 0" class="text-center py-16 text-slate-400">
        No hay cajas registradas para hoy.
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <UCard v-for="cash in allCashs" :key="cash.id_cash">
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="font-bold text-slate-800">{{ getOfficeName(cash.id_office_cash) }}</h3>
              <p class="text-xs text-slate-500">Caja #{{ cash.id_cash }}</p>
            </div>
            <UBadge :color="cash.status_cash == 1 ? 'success' : 'neutral'" variant="subtle">
              {{ cash.status_cash == 1 ? 'Abierta' : 'Cerrada' }}
            </UBadge>
          </div>
          <div class="space-y-1 text-sm">
            <div class="flex justify-between">
              <span class="text-slate-500">Apertura:</span>
              <span class="font-semibold">{{ formatCurrency(parseFloat(cash.start_cash || 0)) }}</span>
            </div>
            <div v-if="cash.end_cash" class="flex justify-between">
              <span class="text-slate-500">Cierre:</span>
              <span class="font-semibold text-rose-500">{{ formatCurrency(parseFloat(cash.end_cash || 0)) }}</span>
            </div>
          </div>
          <div class="mt-3">
            <UButton
              size="xs"
              variant="soft"
              color="primary"
              icon="i-lucide-receipt"
              @click="() => { selectedDetailsCash = cash; isDetailsOpen = true }"
            >
              Ver Detalles
            </UButton>
          </div>
        </UCard>
      </div>

      <!-- Histórico completo -->
      <UCard>
        <template #header>
          <h3 class="font-bold">Histórico de Cajas</h3>
        </template>
        <DynamicTable module-name="caja" />
      </UCard>
    </template>

    <!-- Modal Apertura de Caja -->
    <UModal v-model:open="openModal" title="Abrir Caja del Día">
      <template #body>
        <div class="space-y-4 p-1">
          <p class="text-sm text-slate-600">
            Ingresa el monto inicial con el que abres la caja para hoy <strong>({{ todayStr }})</strong>.
          </p>
          <UFormField label="Monto Inicial (Bs.)">
            <input
              :value="openAmount !== null && openAmount > 0 ? openAmount.toLocaleString('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) : ''"
              type="text"
              inputmode="decimal"
              placeholder="0,00"
              class="block w-full py-3 px-4 text-lg bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-500/50"
              @input="onOpenAmountInput($event)"
              @keydown="blockNegative($event as KeyboardEvent)"
            />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="openModal = false">Cancelar</UButton>
          <UButton color="primary" icon="i-lucide-wallet" :loading="opening" @click="openCash">
            Confirmar Apertura
          </UButton>
        </div>
      </template>
    </UModal>

    <!-- Modal Cierre de Caja -->
    <UModal v-model:open="closeModal" title="Detalles y Cierre de Caja" :ui="{ body: 'max-h-[70vh] overflow-y-auto' }">
      <template #body>
        <div class="space-y-4 p-1">
          <div v-if="loadingDetails" class="flex justify-center py-8">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-6 h-6 text-green-500" />
          </div>
          <template v-else-if="cashDetails">
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div class="bg-slate-50 rounded-lg p-3">
                <p class="text-slate-500 text-xs">Apertura</p>
                <p class="font-bold text-lg">{{ formatCurrency(parseFloat(cashDetails.start_cash || 0)) }}</p>
              </div>
              <div class="bg-slate-50 rounded-lg p-3">
                <p class="text-slate-500 text-xs">Total Ventas</p>
                <p class="font-bold text-lg text-green-600">{{ formatCurrency(parseFloat(cashDetails.money_cash || cashDetails.total_sales || 0)) }}</p>
                <p class="text-xs text-slate-400 mt-1">Efectivo: {{ formatCurrency(parseFloat(cashDetails.cash_efectivo || 0)) }}</p>
                <p class="text-xs text-slate-400">QR: {{ formatCurrency(parseFloat(cashDetails.cash_qr || 0)) }}</p>
              </div>
              <div class="bg-slate-50 rounded-lg p-3">
                <p class="text-slate-500 text-xs">Gastos</p>
                <p class="font-bold text-lg text-rose-500">{{ formatCurrency(parseFloat(cashDetails.bills_cash || 0)) }}</p>
              </div>
              <div class="bg-green-50 rounded-lg p-3 border border-green-200">
                <p class="text-green-600 text-xs font-semibold">Saldo Final</p>
                <p class="font-black text-xl text-green-600">{{ formatCurrency(parseFloat(cashDetails.final_cash || 0)) }}</p>
              </div>
            </div>
          </template>
          <div v-else class="text-center py-4 text-slate-400 text-sm">No hay detalles disponibles.</div>

          <div v-if="!isSuperAdmin && todaysCash?.status_cash == 1" class="pt-2 border-t border-slate-200">
            <UFormField label="Efectivo físico en caja (Bs.)" class="mb-4">
              <input
                v-model="physicalCash"
                type="number"
                step="0.01"
                min="0"
                placeholder="Ingresa lo que contaste..."
                class="block w-full py-2 px-3 text-lg bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-500/50"
              />
            </UFormField>
            <p class="text-xs text-slate-500 mb-3">
              Al cerrar la caja se finalizará la jornada y se registrará cualquier faltante o sobrante basado en el efectivo físico que indiques. Esta acción no se puede deshacer.
            </p>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="() => { closeModal = false; cashDetails = null }">Cerrar</UButton>
          <UButton
            v-if="!isSuperAdmin && todaysCash?.status_cash == 1"
            color="error"
            icon="i-lucide-x-circle"
            :loading="closing"
            @click="closeCash"
          >
            Cerrar Caja
          </UButton>
        </div>
      </template>
    </UModal>

    <!-- Cash Details Slideover -->
    <CashDetailsModal
      v-model:isOpen="isDetailsOpen"
      :cash="selectedDetailsCash"
      @close="selectedDetailsCash = null"
    />

  </div>
</template>
