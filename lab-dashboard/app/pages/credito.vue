<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()
const api = useApi()

const credits = ref<any[]>([])
const clients = ref<any[]>([])
const products = ref<any[]>([])
const loading = ref(true)
const tab = ref<'activos' | 'pagados'>('activos')

// Nuevo crédito
const createModal = ref(false)
const newCredit = ref({ id_client: '', amount: '', due_date: '', notes: '' })
const creating = ref(false)

// Ver detalle + abonar
const detailModal = ref(false)
const selectedCredit = ref<any>(null)
const payments = ref<any[]>([])
const loadingPayments = ref(false)
const salesDetails = ref<any[]>([])
const orderDetails = ref<any>(null)
const newPayment = ref({ amount: '', method: 'efectivo' })
const proofFile = ref<File | null>(null)
const paying = ref(false)

function onProofChange(e: Event) {
  const files = (e.target as HTMLInputElement).files
  proofFile.value = files && files.length ? files[0]! : null
}

const decode = decodeText
const fmt = formatBob

function daysDiff(dateStr: string) {
  if (!dateStr) return null
  const diff = Math.floor((new Date(dateStr).getTime() - Date.now()) / 86400000)
  return diff
}

const filteredCredits = computed(() => credits.value.filter(c => tab.value === 'activos' ? (c.status_credit !== 'pagado' && c.status_credit !== 'anulado') : (c.status_credit === 'pagado' || c.status_credit === 'anulado')))

async function fetchClients() {
  const d = await api.rest<any>('/api/clients')
  if (d?.status === 200) clients.value = d.results || []
}

async function fetchProducts() {
  const d = await api.rest<any>('/api/products')
  if (d?.status === 200) products.value = d.results || []
}

async function fetchCredits() {
  loading.value = true
  const d = await api.ajax({ getCredits: 'ok', id_office: auth.officeId || 0 })
  credits.value = d?.status === 200 ? d.results : []
  loading.value = false
}

async function createCredit() {
  if (!newCredit.value.id_client || !newCredit.value.amount) return
  creating.value = true
  const d = await api.ajax({
    createCredit: 'ok', id_client: newCredit.value.id_client, id_office: auth.officeId || 0,
    id_admin: auth.user?.id_admin || 0, amount: newCredit.value.amount,
    due_date: newCredit.value.due_date, notes: newCredit.value.notes
  })
  if (d?.status === 200) {
    toast.add({ title: 'Crédito creado', color: 'success' })
    createModal.value = false
    newCredit.value = { id_client: '', amount: '', due_date: '', notes: '' }
    await fetchCredits()
  }
  creating.value = false
}

async function openDetail(credit: any) {
  selectedCredit.value = credit
  detailModal.value = true
  loadingPayments.value = true
  
  if (credit.id_order_credit && credit.id_order_credit !== '0') {
    const o = await api.rest<any>(`/api/orders?linkTo=id_order&equalTo=${credit.id_order_credit}`)
    orderDetails.value = (o?.status === 200 && o.results && o.results.length > 0) ? o.results[0] : null

    const s = await api.rest<any>(`/api/sales?linkTo=id_order_sale&equalTo=${credit.id_order_credit}`)
    salesDetails.value = s?.status === 200 ? s.results : []
  } else {
    orderDetails.value = null
    salesDetails.value = []
  }

  const d = await api.ajax({ getCreditPayments: 'ok', id_credit: credit.id_credit })
  payments.value = d?.status === 200 ? d.results : []
  loadingPayments.value = false
}

async function addPayment() {
  if (!newPayment.value.amount || !selectedCredit.value) return
  if (parseFloat(newPayment.value.amount) > parseFloat(selectedCredit.value.balance_credit)) {
    toast.add({ title: `El abono no puede ser mayor al saldo pendiente (Bs. ${selectedCredit.value.balance_credit})`, color: 'error' })
    return
  }
  paying.value = true
  // FormData para adjuntar el comprobante del abono
  const fd = new FormData()
  fd.append('addCreditPayment', 'ok')
  fd.append('id_credit', String(selectedCredit.value.id_credit))
  fd.append('amount', newPayment.value.amount)
  fd.append('method', newPayment.value.method)
  fd.append('reference', '')
  fd.append('id_admin', String(auth.user?.id_admin || 0))
  if (proofFile.value) fd.append('proof', proofFile.value)
  const d = await api.ajaxForm(fd)
  if (d?.status === 200) {
    selectedCredit.value.balance_credit = d.new_balance
    selectedCredit.value.status_credit = d.new_status
    newPayment.value = { amount: '', method: 'efectivo' }
    proofFile.value = null
    await openDetail(selectedCredit.value)
    await fetchCredits()
    toast.add({ title: 'Abono registrado', color: 'success' })
    if (d.file_warning) toast.add({ title: 'Comprobante no adjuntado', description: d.file_warning, color: 'warning' })
  }
  paying.value = false
}

const totalBalance = computed(() => credits.value.filter(c => c.status_credit !== 'pagado' && c.status_credit !== 'anulado').reduce((a, c) => a + parseFloat(c.balance_credit || 0), 0))
const overdue = computed(() => credits.value.filter(c => c.status_credit !== 'pagado' && c.status_credit !== 'anulado' && c.due_date_credit && daysDiff(c.due_date_credit)! < 0))

async function cancelCredit(credit: any) {
  if (!confirm('¿Estás seguro de que deseas cancelar este crédito?\n\nSi proviene de una venta, se devolverá el stock pero se mantendrán los abonos registrados como dinero ingresado.')) return
  
  if (credit.id_order_credit && credit.id_order_credit !== '0') {
    const fd = new FormData()
    fd.append('cancelCreditOrder', String(credit.id_order_credit))
    const d = await api.ajaxForm(fd)
    if (d?.status === 200) {
      toast.add({ title: 'Crédito cancelado y stock devuelto', color: 'success' })
      detailModal.value = false
      await fetchCredits()
    } else {
      toast.add({ title: 'Error al cancelar la orden', color: 'error' })
    }
  } else {
    try {
      const res = await $fetch<any>(`/api/credits?id=${credit.id_credit}&nameId=id_credit&token=${auth.token}&table=admins&suffix=admin`, {
        method: 'PUT',
        body: new URLSearchParams({ status_credit: 'anulado' }).toString(),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Authorization': 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }
      })
      if (res.status === 200) {
        toast.add({ title: 'Crédito anulado', color: 'success' })
        detailModal.value = false
        await fetchCredits()
      } else {
        toast.add({ title: 'Error al anular', color: 'error' })
      }
    } catch {
      toast.add({ title: 'Error de conexión', color: 'error' })
    }
  }
}

onMounted(async () => { await Promise.all([fetchClients(), fetchProducts(), fetchCredits()]) })
</script>

<template>
  <div class="space-y-6">
    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <UCard class="bg-gradient-to-br from-amber-500 to-orange-500 border-0 shadow-lg">
        <div class="flex justify-between items-center text-white">
          <div>
            <p class="text-amber-100 text-xs font-bold uppercase tracking-wider">Total por Cobrar</p>
            <h2 class="text-2xl font-black mt-1">{{ fmt(totalBalance) }}</h2>
          </div>
          <UIcon name="i-lucide-credit-card" class="w-10 h-10 text-white/30" />
        </div>
      </UCard>
      <UCard>
        <p class="text-slate-500 text-xs font-bold uppercase">Créditos Activos</p>
        <h2 class="text-2xl font-black text-slate-800 mt-1">{{ credits.filter(c => c.status_credit === 'activo').length }}</h2>
      </UCard>
      <UCard :class="overdue.length > 0 ? 'border-rose-300' : ''">
        <p class="text-xs font-bold uppercase" :class="overdue.length > 0 ? 'text-rose-500' : 'text-slate-500'">Créditos Vencidos</p>
        <h2 class="text-2xl font-black mt-1" :class="overdue.length > 0 ? 'text-rose-600' : 'text-slate-800'">{{ overdue.length }}</h2>
      </UCard>
    </div>

    <!-- Tabla -->
    <UCard>
      <template #header>
        <div class="flex justify-between items-center flex-wrap gap-3">
          <div class="flex gap-2">
            <UButton :color="tab === 'activos' ? 'primary' : 'neutral'" variant="soft" size="sm" @click="tab = 'activos'">Activos / Vencidos</UButton>
            <UButton :color="tab === 'pagados' ? 'primary' : 'neutral'" variant="soft" size="sm" @click="tab = 'pagados'">Pagados</UButton>
          </div>
          <UButton color="primary" icon="i-lucide-plus" size="sm" @click="createModal = true">Nuevo Crédito</UButton>
        </div>
      </template>

      <div v-if="loading" class="flex justify-center py-10">
        <UIcon name="i-lucide-loader-2" class="w-7 h-7 animate-spin text-green-500" />
      </div>
      <div v-else-if="filteredCredits.length === 0" class="text-center py-10 text-slate-400 text-sm">Sin registros</div>
      <!-- Cards en móvil, tabla en desktop -->
      <div class="block sm:hidden space-y-3">
        <div
          v-for="c in filteredCredits" :key="c.id_credit"
          class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3"
        >
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="font-bold text-slate-800 text-sm">{{ decode(c.name_client) }} {{ decode(c.surname_client || '') }}</p>
              <p v-if="c.nit_client" class="text-xs text-slate-400">NIT: {{ c.nit_client }}</p>
            </div>
            <UBadge
              :color="c.status_credit === 'pagado' ? 'success' : c.status_credit === 'anulado' ? 'neutral' : (c.due_date_credit && daysDiff(c.due_date_credit)! < 0) ? 'error' : 'warning'"
              variant="subtle" size="xs"
            >{{ c.status_credit === 'pagado' ? 'Pagado' : c.status_credit === 'anulado' ? 'Anulado' : (c.due_date_credit && daysDiff(c.due_date_credit)! < 0) ? 'Vencido' : 'Activo' }}</UBadge>
          </div>
          <div class="grid grid-cols-2 gap-2 text-sm">
            <div><p class="text-xs text-slate-400">Monto</p><p class="font-mono font-semibold">{{ fmt(parseFloat(c.amount_credit)) }}</p></div>
            <div><p class="text-xs text-slate-400">Saldo</p><p class="font-mono font-bold" :class="parseFloat(c.balance_credit) > 0 ? 'text-amber-600' : 'text-emerald-600'">{{ fmt(parseFloat(c.balance_credit)) }}</p></div>
          </div>
          <div v-if="c.due_date_credit" class="text-xs text-slate-500">
            Vence: {{ c.due_date_credit }}
            <span v-if="daysDiff(c.due_date_credit)! < 0" class="text-rose-500 ml-1">({{ Math.abs(daysDiff(c.due_date_credit)!) }} días vencido)</span>
          </div>
          <UButton size="xs" color="primary" variant="soft" icon="i-lucide-receipt" block @click="openDetail(c)">Ver / Abonar</UButton>
        </div>
      </div>

      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead>
            <tr class="border-b border-slate-200 text-xs text-slate-500 uppercase bg-slate-50">
              <th class="px-4 py-3">Cliente</th>
              <th class="px-4 py-3">Monto</th>
              <th class="px-4 py-3">Saldo</th>
              <th class="px-4 py-3 hidden md:table-cell">Vencimiento</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3 text-right">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in filteredCredits" :key="c.id_credit" class="border-b border-slate-100 hover:bg-slate-50">
              <td class="px-4 py-3 font-semibold text-slate-800">
                {{ decode(c.name_client) }} {{ decode(c.surname_client || '') }}
                <p v-if="c.nit_client" class="text-xs text-slate-400 font-normal">NIT: {{ c.nit_client }}</p>
              </td>
              <td class="px-4 py-3 font-mono text-slate-700">{{ fmt(parseFloat(c.amount_credit)) }}</td>
              <td class="px-4 py-3 font-mono font-bold" :class="parseFloat(c.balance_credit) > 0 ? 'text-amber-600' : 'text-emerald-600'">{{ fmt(parseFloat(c.balance_credit)) }}</td>
              <td class="px-4 py-3 hidden md:table-cell text-slate-600">
                <span v-if="c.due_date_credit">
                  {{ c.due_date_credit }}
                  <span v-if="daysDiff(c.due_date_credit)! < 0" class="text-rose-500 text-xs block">{{ Math.abs(daysDiff(c.due_date_credit)!) }} días vencido</span>
                  <span v-else-if="daysDiff(c.due_date_credit)! <= 7" class="text-amber-500 text-xs block">Vence en {{ daysDiff(c.due_date_credit) }} días</span>
                </span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="px-4 py-3">
                <UBadge :color="c.status_credit === 'pagado' ? 'success' : c.status_credit === 'anulado' ? 'neutral' : (c.due_date_credit && daysDiff(c.due_date_credit)! < 0) ? 'error' : 'warning'" variant="subtle" size="xs">
                  {{ c.status_credit === 'pagado' ? 'Pagado' : c.status_credit === 'anulado' ? 'Anulado' : (c.due_date_credit && daysDiff(c.due_date_credit)! < 0) ? 'Vencido' : 'Activo' }}
                </UBadge>
              </td>
              <td class="px-4 py-3 text-right">
                <UButton size="xs" color="primary" variant="soft" icon="i-lucide-receipt" @click="openDetail(c)">Ver / Abonar</UButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </UCard>

    <!-- Modal: Nuevo crédito -->
    <UModal v-model:open="createModal" title="Nuevo Crédito">
      <template #body>
        <div class="space-y-4 p-1">
          <UFormField label="Cliente *">
            <USelect v-model="newCredit.id_client" :items="clients.map(c => ({ value: String(c.id_client), label: `${decode(c.name_client)} ${decode(c.surname_client||'')} · ${c.dni_client}` }))" placeholder="Seleccionar cliente" class="w-full" />
          </UFormField>
          <UFormField label="Monto del Crédito (Bs.) *">
            <UInput v-model="newCredit.amount" type="number" step="any" min="0" placeholder="0.00" class="w-full" />
          </UFormField>
          <UFormField label="Fecha de Vencimiento">
            <UInput v-model="newCredit.due_date" type="date" class="w-full" />
          </UFormField>
          <UFormField label="Notas">
            <UTextarea v-model="newCredit.notes" :rows="2" placeholder="Concepto del crédito..." class="w-full" />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="createModal = false">Cancelar</UButton>
          <UButton color="primary" :loading="creating" @click="createCredit">Crear Crédito</UButton>
        </div>
      </template>
    </UModal>

    <!-- Modal: Detalle y abono -->
    <UModal v-model:open="detailModal" title="Estado de Cuenta" :ui="{ body: 'max-h-[70vh] overflow-y-auto' }">
      <template #body>
        <div v-if="selectedCredit" class="space-y-4 p-1">
          <!-- Resumen -->
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-slate-50 rounded-lg p-3"><p class="text-slate-400 text-xs">Monto Original</p><p class="font-bold text-lg">{{ fmt(parseFloat(selectedCredit.amount_credit)) }}</p></div>
            <div :class="['rounded-lg p-3', parseFloat(selectedCredit.balance_credit) > 0 ? 'bg-amber-50' : 'bg-emerald-50']">
              <p class="text-xs" :class="parseFloat(selectedCredit.balance_credit) > 0 ? 'text-amber-500' : 'text-emerald-500'">Saldo Pendiente</p>
              <p class="font-black text-xl" :class="parseFloat(selectedCredit.balance_credit) > 0 ? 'text-amber-600' : 'text-emerald-600'">{{ fmt(parseFloat(selectedCredit.balance_credit)) }}</p>
            </div>
          </div>

          <!-- Detalles Completos del Crédito y Cliente -->
          <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm space-y-2">
            <div class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500 text-xs font-semibold uppercase">Cliente</span>
              <span class="font-bold text-slate-800">{{ decode(selectedCredit.name_client) }} {{ decode(selectedCredit.surname_client || '') }}</span>
            </div>
            <div v-if="selectedCredit.nit_client" class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500 text-xs font-semibold uppercase">NIT / CI</span>
              <span class="text-slate-700">{{ selectedCredit.nit_client }}</span>
            </div>
            <div v-if="selectedCredit.phone_client || clients.find(c => c.id_client == selectedCredit.id_client_credit)?.phone_client" class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500 text-xs font-semibold uppercase">Teléfono</span>
              <span class="text-slate-700">{{ selectedCredit.phone_client || clients.find(c => c.id_client == selectedCredit.id_client_credit)?.phone_client }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500 text-xs font-semibold uppercase">Fecha Registro</span>
              <span class="text-slate-700">{{ selectedCredit.date_created_credit }}</span>
            </div>
            <div v-if="selectedCredit.due_date_credit" class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500 text-xs font-semibold uppercase">Vencimiento</span>
              <span :class="(daysDiff(selectedCredit.due_date_credit)! < 0 && selectedCredit.status_credit !== 'pagado') ? 'text-rose-600 font-bold' : 'text-slate-700'">{{ selectedCredit.due_date_credit }}</span>
            </div>
            <div v-if="orderDetails" class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500 text-xs font-semibold uppercase">Orden / Transacción</span>
              <span class="font-mono text-slate-700">{{ orderDetails.transaction_order }}</span>
            </div>
            <div v-if="selectedCredit.notes_credit" class="pt-1">
              <span class="text-slate-500 text-xs font-semibold uppercase block mb-1">Notas</span>
              <p class="text-slate-700 italic text-xs bg-white p-2 rounded border border-slate-200">{{ selectedCredit.notes_credit }}</p>
            </div>
          </div>

          <!-- Productos (Si aplica) -->
          <div v-if="salesDetails.length > 0">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Productos Vendidos</p>
            <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
              <div v-for="s in salesDetails" :key="s.id_sale" class="flex justify-between items-center bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-sm">
                <div>
                  <p class="font-semibold text-slate-700 text-xs line-clamp-1">{{ decode(products.find(p => p.id_product == s.id_product_sale)?.title_product || 'Producto') }}</p>
                  <p class="text-[10px] text-slate-400 font-mono">{{ s.qty_sale }} unid.</p>
                </div>
                <div class="font-mono font-bold text-slate-700 text-xs shrink-0">Bs. {{ parseFloat(s.subtotal_sale).toFixed(2) }}</div>
              </div>
            </div>
          </div>

          <!-- Historial de abonos -->
          <div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Historial de Abonos</p>
            <div v-if="loadingPayments" class="text-center py-3"><UIcon name="i-lucide-loader-2" class="w-5 h-5 animate-spin text-green-500 mx-auto" /></div>
            <div v-else class="space-y-1.5">
              <div v-for="p in payments" :key="p.id_payment" class="flex justify-between items-center bg-slate-50 rounded-lg px-3 py-2 text-sm gap-2">
                <div class="min-w-0">
                  <p class="font-semibold text-slate-700">{{ p.date_created_payment }}</p>
                  <p class="text-xs text-slate-400 capitalize truncate">{{ p.method_payment }}{{ p.reference_payment ? ` · ${p.reference_payment}` : '' }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <a v-if="p.file_payment" :href="`/${p.file_payment}`" target="_blank" class="text-primary-600 hover:text-primary-700 text-xs font-medium flex items-center gap-0.5" title="Ver comprobante">
                    <UIcon name="i-lucide-receipt" class="w-4 h-4" /> Ver
                  </a>
                  <span class="font-bold font-mono text-emerald-600">+{{ fmt(parseFloat(p.amount_payment)) }}</span>
                </div>
              </div>
              <div v-if="payments.length === 0" class="text-center py-3 text-slate-400 text-sm">Sin abonos registrados</div>
            </div>
          </div>

          <!-- Agregar abono -->
          <div v-if="selectedCredit.status_credit !== 'pagado' && selectedCredit.status_credit !== 'anulado'" class="border-t border-slate-200 pt-4 space-y-3">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Registrar Abono</p>
            <div class="grid grid-cols-2 gap-2">
              <UFormField label="Monto (Bs.) *">
                <UInput v-model="newPayment.amount" type="number" step="any" min="0" :max="selectedCredit.balance_credit" class="w-full" />
              </UFormField>
              <UFormField label="Método">
                <USelect v-model="newPayment.method" :items="[{value:'efectivo',label:'Efectivo'},{value:'qr',label:'QR'}]" class="w-full" />
              </UFormField>
            </div>
            <UFormField v-if="newPayment.method === 'qr'" label="Comprobante de pago (imagen/PDF)" help="Respaldo del abono. Máx 5MB.">
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp,application/pdf"
                class="block w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                @change="onProofChange"
              >
            </UFormField>
            <p v-if="proofFile && newPayment.method === 'qr'" class="text-xs text-green-600 flex items-center gap-1">
              <UIcon name="i-lucide-paperclip" class="w-3.5 h-3.5" /> {{ proofFile.name }}
            </p>
            <UButton color="primary" block :loading="paying" icon="i-lucide-plus" @click="addPayment">Registrar Abono</UButton>
          </div>
          <div v-else-if="selectedCredit.status_credit === 'pagado'" class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-center">
            <UIcon name="i-lucide-check-circle" class="w-7 h-7 text-emerald-500 mx-auto mb-1" />
            <p class="text-sm font-semibold text-emerald-700">Crédito completamente pagado</p>
          </div>
          <div v-else class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-center">
            <UIcon name="i-lucide-x-circle" class="w-7 h-7 text-slate-500 mx-auto mb-1" />
            <p class="text-sm font-semibold text-slate-700">Crédito anulado / cancelado</p>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-between w-full">
          <UButton 
            v-if="selectedCredit?.status_credit !== 'anulado' && selectedCredit?.status_credit !== 'pagado'" 
            color="error" 
            variant="soft" 
            icon="i-lucide-x-circle" 
            @click="cancelCredit(selectedCredit)"
          >
            Cancelar Crédito
          </UButton>
          <div v-else></div>
          <UButton color="neutral" variant="ghost" @click="detailModal = false">Cerrar</UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>
