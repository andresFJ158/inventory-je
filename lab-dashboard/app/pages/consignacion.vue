<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()
const api = useApi()

definePageMeta({ middleware: ['auth'] })


// ─── Utils ────────────────────────────────────────────────────────────────────
const decode = decodeText
const fmt = formatBob

// ─── Data ─────────────────────────────────────────────────────────────────────
const consignments = ref<any[]>([])
const products = ref<any[]>([])
const inventory = ref<Record<string, number>>({})
const clients = ref<any[]>([])
const loading = ref(true)

// Filters
const filterStatus = ref('todos')
const filterSearch = ref('')

const filteredConsignments = computed(() => {
  let list = consignments.value
  if (filterStatus.value !== 'todos') list = list.filter(c => c.status_consignment === filterStatus.value)
  if (filterSearch.value) {
    const q = filterSearch.value.toLowerCase()
    list = list.filter(c =>
      (c.client_name || '').toLowerCase().includes(q) ||
      (c.name_admin || '').toLowerCase().includes(q) ||
      String(c.id_consignment).includes(q) ||
      (c.notes_consignment || '').toLowerCase().includes(q)
    )
  }
  return list
})

// KPIs
const kpiActivas = computed(() => consignments.value.filter(c => c.status_consignment === 'activa' || c.status_consignment === 'parcial').length)
const kpiPendiente = computed(() => consignments.value.reduce((s, c) => s + (parseFloat(c.balance_consignment) || 0), 0))
const kpiCobrado = computed(() => consignments.value.reduce((s, c) => s + (parseFloat(c.paid_consignment) || 0), 0))

// ─── Create Modal ─────────────────────────────────────────────────────────────
const createModal = ref(false)
const creating = ref(false)
const newNotes = ref('')
const newClientId = ref('')
const newItems = ref<{ id_product: string, qty: number, price: number }[]>([])

const newTotal = computed(() => newItems.value.reduce((s, i) => s + (i.qty || 0) * (i.price || 0), 0))
const itemsValid = computed(() => newItems.value.length > 0 && newItems.value.every(i => i.id_product && i.qty > 0 && i.price >= 0))

function addItemRow() { newItems.value.push({ id_product: '', qty: 1, price: 0 }) }
function removeItemRow(i: number) { newItems.value.splice(i, 1) }

function onProductSelect(item: any) {
  if (item.id_product) {
    const p = products.value.find(p => String(p.id_product) === String(item.id_product))
    if (p) item.price = parseFloat(p.price_product) || 0
  }
}

const productOptions = computed(() => products.value
  .filter(p => (inventory.value[p.id_product] || 0) > 0)
  .map(p => ({
  value: String(p.id_product),
  label: `${decode(p.title_product)} (Stock: ${inventory.value[p.id_product] || 0})`
})))

const clientOptions = computed(() => clients.value.map(c => ({
  value: String(c.id_client),
  label: `${decode(c.name_client || '')} ${decode(c.surname_client || '')}`.trim() + (c.phone_client ? ` · ${c.phone_client}` : '')
})))

// ─── Detail Modal ─────────────────────────────────────────────────────────────
const detailModal = ref(false)
const detailTab = ref<'cuenta' | 'pago' | 'devolucion'>('cuenta')
const loadingDetail = ref(false)
const selected = ref<any>(null)
const detailItems = ref<any[]>([])
const detailPayments = ref<any[]>([])
const detailReplacements = ref<any[]>([])

// Payment form
const payAmount = ref<number | string>('')
const payMethod = ref('efectivo')
const payRef = ref('')
const payNotes = ref('')
const payFile = ref<File | null>(null)
const payFilePreview = ref('')
const qrImage = ref('')
const payingLoading = ref(false)

// Return form
const returnItemId = ref('')
const returnQty = ref(1)
const returningLoading = ref(false)

// Replacement form
const replItemOutId = ref('')
const replProductInId = ref('')
const replQty = ref(1)
const replPrice = ref(0)
const replNotes = ref('')
const replacingLoading = ref(false)

const methodOptions = [
  { value: 'efectivo', label: 'Efectivo' },
  { value: 'QR', label: 'QR / Transferencia' },
  { value: 'deposito', label: 'Depósito Bancario' },
  { value: 'otro', label: 'Otro' },
]

// Active items for return/replacement (those still with qty > 0 active)
const activeItems = computed(() => detailItems.value.filter(i => {
  const active = parseInt(i.qty_assigned) - parseInt(i.qty_returned || 0) - parseInt(i.qty_reponed || 0)
  return active > 0
}))

const returnItem = computed(() => detailItems.value.find(i => String(i.id_consignment_item) === String(returnItemId.value)))
const returnMax = computed(() => {
  if (!returnItem.value) return 0
  return parseInt(returnItem.value.qty_assigned) - parseInt(returnItem.value.qty_returned || 0) - parseInt(returnItem.value.qty_reponed || 0)
})

const replItemOut = computed(() => detailItems.value.find(i => String(i.id_consignment_item) === String(replItemOutId.value)))
const replMax = computed(() => {
  if (!replItemOut.value) return 0
  return parseInt(replItemOut.value.qty_assigned) - parseInt(replItemOut.value.qty_returned || 0) - parseInt(replItemOut.value.qty_reponed || 0)
})

// Progress bar
const progressPct = computed(() => {
  if (!selected.value) return 0
  const total = parseFloat(selected.value.total_consignment) || 0
  if (total <= 0) return 0
  const paid = parseFloat(selected.value.paid_consignment) || 0
  return Math.min(100, Math.round((paid / total) * 100))
})

const balance = computed(() => {
  if (!selected.value) return 0
  return Math.max(0, parseFloat(selected.value.total_consignment || 0) - parseFloat(selected.value.paid_consignment || 0))
})

const getImageUrl = (path: string) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const config = useRuntimeConfig()
  return `${config.public.apiBase}/${path.replace(/^\//, '')}`
}

// ─── API Calls ────────────────────────────────────────────────────────────────
async function fetchData() {
  loading.value = true
  const officeId = auth.effectiveOfficeId ?? 3
  const [pd, id, cl, cons, off] = await Promise.all([
    api.rest<any>('/api/products?linkTo=status_product&equalTo=1'),
    api.rest<any>(`/api/product_inventory?linkTo=id_office_inventory&equalTo=${officeId}`),
    api.rest<any>('/api/clients'),
    api.ajax({ getConsignments: 'ok', id_office: officeId }),
    api.rest<any>(`/api/offices?linkTo=id_office&equalTo=${officeId}`)
  ])
  if (pd?.status === 200) products.value = pd.results || []
  if (id?.status === 200 && id.results) {
    const inv: Record<string, number> = {}
    id.results.forEach((i: any) => { inv[i.id_product_inventory] = parseFloat(i.stock_inventory) || 0 })
    inventory.value = inv
  }
  if (cl?.status === 200) {
    clients.value = cl.results?.sort((a: any, b: any) => {
      const nameA = (a.name_client || '').toLowerCase()
      const nameB = (b.name_client || '').toLowerCase()
      return nameA.localeCompare(nameB)
    }) || []
  }
  consignments.value = cons?.status === 200 ? cons.results : []
  if (off?.status === 200 && off.results?.length > 0) {
    qrImage.value = off.results[0].image_qr || ''
  }
  loading.value = false
}

async function openCreate() {
  newItems.value = [{ id_product: '', qty: 1, price: 0 }]
  newNotes.value = ''
  newClientId.value = ''
  createModal.value = true
}

async function createConsignment() {
  if (!itemsValid.value) return
  creating.value = true
  const d = await api.ajax({
    createConsignment: 'ok',
    id_admin: auth.user?.id_admin || 0,
    id_office: auth.effectiveOfficeId || 0,
    id_client: newClientId.value || 0,
    notes: newNotes.value,
    items: JSON.stringify(newItems.value.map(i => ({ id_product: i.id_product, qty: i.qty, price: i.price })))
  })
  if (d?.status === 200) {
    toast.add({ title: 'Consignación creada exitosamente', color: 'success' })
    createModal.value = false
    await fetchData()
  } else {
    toast.add({ title: d?.message || 'Error al crear consignación', color: 'error' })
  }
  creating.value = false
}

async function openDetail(c: any) {
  selected.value = c
  detailTab.value = 'cuenta'
  detailModal.value = true
  resetForms()
  await loadDetail(c.id_consignment)
}

async function loadDetail(id: number) {
  loadingDetail.value = true
  const d = await api.ajax({ getConsignmentFull: 'ok', id_consignment: id })
  if (d?.status === 200) {
    selected.value = d.results.consignment
    detailItems.value = d.results.items || []
    detailPayments.value = d.results.payments || []
    detailReplacements.value = d.results.replacements || []
  }
  loadingDetail.value = false
}

function resetForms() {
  payAmount.value = ''
  payMethod.value = 'efectivo'
  payRef.value = ''
  payNotes.value = ''
  payFile.value = null
  payFilePreview.value = ''
  returnItemId.value = ''
  returnQty.value = 1
  replItemOutId.value = ''
  replProductInId.value = ''
  replQty.value = 1
  replPrice.value = 0
  replNotes.value = ''
}

function onPayFile(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files?.length) {
    payFile.value = target.files[0]
    payFilePreview.value = URL.createObjectURL(target.files[0])
  } else {
    payFile.value = null
    payFilePreview.value = ''
  }
}

async function submitPayment() {
  const amount = parseFloat(String(payAmount.value))
  if (!amount || amount <= 0) { toast.add({ title: 'Ingrese un monto válido', color: 'error' }); return }
  if (amount > balance.value + 0.01) { toast.add({ title: `El monto no puede superar el saldo pendiente (${fmt(balance.value)})`, color: 'error' }); return }
  payingLoading.value = true

  const fd = new FormData()
  fd.append('addConsignmentPayment', 'ok')
  fd.append('id_consignment', String(selected.value.id_consignment))
  fd.append('id_office', String(auth.effectiveOfficeId || 0))
  fd.append('id_admin', String(auth.user?.id_admin || 0))
  fd.append('amount', String(amount))
  fd.append('method', payMethod.value)
  fd.append('reference', payRef.value)
  fd.append('notes_payment', payNotes.value)
  if (payFile.value) fd.append('proof', payFile.value)

  const d = await api.ajaxForm(fd)
  if (d?.status === 200) {
    toast.add({ title: 'Pago registrado', color: 'success' })
    await loadDetail(selected.value.id_consignment)
    await fetchData()
    resetForms()
    if (selected.value?.status_consignment === 'completada') {
      toast.add({ title: '¡Consignación completada! Se generó una orden de venta.', color: 'success', duration: 5000 })
    }
  } else {
    toast.add({ title: d?.message || 'Error al registrar pago', color: 'error' })
  }
  payingLoading.value = false
}

async function submitReturn() {
  if (!returnItemId.value || returnQty.value <= 0) { toast.add({ title: 'Seleccione un producto y cantidad válida', color: 'error' }); return }
  if (returnQty.value > returnMax.value) { toast.add({ title: `Máximo a devolver: ${returnMax.value}`, color: 'error' }); return }
  returningLoading.value = true
  const d = await api.ajax({
    addConsignmentReturn: 'ok',
    id_consignment: selected.value.id_consignment,
    id_consignment_item: returnItemId.value,
    qty: returnQty.value,
    id_office: auth.effectiveOfficeId || 0,
    id_admin: auth.user?.id_admin || 0
  })
  if (d?.status === 200) {
    toast.add({ title: 'Devolución registrada. Stock devuelto al inventario.', color: 'success' })
    await loadDetail(selected.value.id_consignment)
    await fetchData()
    returnItemId.value = ''
    returnQty.value = 1
  } else {
    toast.add({ title: d?.message || 'Error al registrar devolución', color: 'error' })
  }
  returningLoading.value = false
}

async function submitReplacement() {
  if (!replItemOutId.value || !replProductInId.value || replQty.value <= 0) {
    toast.add({ title: 'Complete todos los campos de reposición', color: 'error' }); return
  }
  if (replQty.value > replMax.value) { toast.add({ title: `Máximo disponible: ${replMax.value}`, color: 'error' }); return }
  replacingLoading.value = true
  const d = await api.ajax({
    addConsignmentReplacement: 'ok',
    id_consignment: selected.value.id_consignment,
    id_item_out: replItemOutId.value,
    id_product_in: replProductInId.value,
    qty: replQty.value,
    price_in: replPrice.value,
    id_office: auth.effectiveOfficeId || 0,
    id_admin: auth.user?.id_admin || 0,
    notes: replNotes.value
  })
  if (d?.status === 200) {
    toast.add({ title: 'Reposición registrada exitosamente', color: 'success' })
    await loadDetail(selected.value.id_consignment)
    await fetchData()
    replItemOutId.value = ''
    replProductInId.value = ''
    replQty.value = 1
    replPrice.value = 0
    replNotes.value = ''
  } else {
    toast.add({ title: d?.message || 'Error al registrar reposición', color: 'error' })
  }
  replacingLoading.value = false
}

// Auto-fill price when product changes for replacement
watch(replProductInId, (val) => {
  if (val) {
    const p = products.value.find(p => String(p.id_product) === String(val))
    if (p) replPrice.value = parseFloat(p.price_product) || 0
  }
})

const statusColor = (s: string) => {
  if (s === 'completada') return 'success'
  if (s === 'parcial') return 'warning'
  if (s === 'activa') return 'primary'
  return 'neutral'
}
const statusLabel = (s: string) => {
  if (s === 'completada') return 'Completada'
  if (s === 'parcial') return 'Pago Parcial'
  if (s === 'activa') return 'Activa'
  return s
}

onMounted(() => { fetchData() })
</script>

<template>
  <div class="space-y-6">
    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <UCard class="bg-gradient-to-br from-indigo-500 to-indigo-600 border-0 shadow-md">
        <div class="flex justify-between items-center text-white">
          <div>
            <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-wider">Consignaciones Activas</p>
            <h2 class="text-3xl font-black mt-1">{{ kpiActivas }}</h2>
          </div>
          <UIcon name="i-lucide-package" class="w-10 h-10 text-white/30" />
        </div>
      </UCard>
      <UCard class="bg-gradient-to-br from-amber-500 to-amber-600 border-0 shadow-md">
        <div class="flex justify-between items-center text-white">
          <div>
            <p class="text-amber-100 text-[10px] font-bold uppercase tracking-wider">Saldo Pendiente Total</p>
            <h2 class="text-2xl font-black mt-1">{{ fmt(kpiPendiente) }}</h2>
          </div>
          <UIcon name="i-lucide-clock" class="w-10 h-10 text-white/30" />
        </div>
      </UCard>
      <UCard class="bg-gradient-to-br from-emerald-500 to-emerald-600 border-0 shadow-md">
        <div class="flex justify-between items-center text-white">
          <div>
            <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-wider">Total Cobrado</p>
            <h2 class="text-2xl font-black mt-1">{{ fmt(kpiCobrado) }}</h2>
          </div>
          <UIcon name="i-lucide-trending-up" class="w-10 h-10 text-white/30" />
        </div>
      </UCard>
    </div>

    <!-- Table Header Controls -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white border border-slate-200 p-4 rounded-xl shadow-sm">
      <div>
        <h1 class="text-base font-extrabold text-slate-800">Consignaciones</h1>
        <p class="text-[11px] text-slate-400 mt-0.5">Seguimiento de productos entregados en consignación con pagos progresivos.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
        <UInput v-model="filterSearch" icon="i-lucide-search" placeholder="Buscar..." size="sm" class="w-full sm:w-44" />
        <select v-model="filterStatus" class="text-sm bg-white border border-slate-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-indigo-500">
          <option value="todos">Todos los estados</option>
          <option value="activa">Activa</option>
          <option value="parcial">Pago Parcial</option>
          <option value="completada">Completada</option>
        </select>
        <UButton icon="i-lucide-plus" color="primary" size="sm" class="font-bold!" @click="openCreate">
          Nueva Consignación
        </UButton>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div v-if="loading" class="flex justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-indigo-500" />
      </div>
      <div v-else-if="filteredConsignments.length === 0" class="text-center py-12 text-slate-400">
        <UIcon name="i-lucide-package-open" class="w-12 h-12 mx-auto mb-3 text-slate-200" />
        <p class="text-sm font-semibold">Sin consignaciones</p>
        <p class="text-xs mt-1">Crea una nueva consignación para comenzar</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
            <tr>
              <th class="px-4 py-3">#</th>
              <th class="px-4 py-3">Cliente</th>
              <th class="px-4 py-3">Vendedor</th>
              <th class="px-4 py-3">Total</th>
              <th class="px-4 py-3">Cobrado</th>
              <th class="px-4 py-3">Saldo</th>
              <th class="px-4 py-3">Progreso</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3">Fecha</th>
              <th class="px-4 py-3 text-right">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="c in filteredConsignments" :key="c.id_consignment" class="hover:bg-slate-50/50 transition-colors">
              <td class="px-4 py-3 font-mono text-slate-400 text-xs">#{{ c.id_consignment }}</td>
              <td class="px-4 py-3">
                <p class="font-semibold text-slate-800 text-sm">{{ c.client_name?.trim() || '—' }}</p>
                <p v-if="c.phone_client" class="text-xs text-slate-400">{{ c.phone_client }}</p>
              </td>
              <td class="px-4 py-3 text-slate-600 text-sm">{{ decode(c.name_admin || '') }}</td>
              <td class="px-4 py-3 font-mono font-bold text-slate-700">{{ fmt(parseFloat(c.total_consignment) || 0) }}</td>
              <td class="px-4 py-3 font-mono text-emerald-600 font-bold">{{ fmt(parseFloat(c.paid_consignment) || 0) }}</td>
              <td class="px-4 py-3 font-mono font-bold" :class="parseFloat(c.balance_consignment) > 0 ? 'text-amber-600' : 'text-slate-400'">
                {{ fmt(Math.max(0, parseFloat(c.balance_consignment) || 0)) }}
              </td>
              <td class="px-4 py-3 w-28">
                <div class="w-full bg-slate-100 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all duration-500"
                    :class="c.status_consignment === 'completada' ? 'bg-emerald-500' : 'bg-indigo-500'"
                    :style="{ width: (Math.min(100, Math.round(((parseFloat(c.paid_consignment)||0) / (parseFloat(c.total_consignment)||1)) * 100))) + '%' }"
                  />
                </div>
                <p class="text-[10px] text-slate-400 mt-0.5 font-mono text-center">
                  {{ Math.min(100, Math.round(((parseFloat(c.paid_consignment)||0) / (parseFloat(c.total_consignment)||1)) * 100)) }}%
                </p>
              </td>
              <td class="px-4 py-3">
                <UBadge :color="statusColor(c.status_consignment)" variant="subtle" size="xs" class="font-bold">
                  {{ statusLabel(c.status_consignment) }}
                </UBadge>
              </td>
              <td class="px-4 py-3 text-slate-400 text-xs">{{ c.date_created_consignment }}</td>
              <td class="px-4 py-3 text-right">
                <UButton size="xs" icon="i-lucide-eye" color="primary" variant="soft" @click="openDetail(c)">
                  Gestionar
                </UButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══ MODAL: NUEVA CONSIGNACIÓN ══════════════════════════════════════════ -->
    <UModal v-model:open="createModal" title="Nueva Consignación" :ui="{ body: 'max-h-[75vh] overflow-y-auto' }">
      <template #body>
        <div class="space-y-4 p-1">
          <!-- Cliente -->
          <UFormField label="Cliente">
            <USelect
              v-model="newClientId"
              :items="clientOptions"
              placeholder="Seleccionar cliente..."
              class="w-full"
            />
          </UFormField>

          <!-- Notas -->
          <UFormField label="Notas / Observaciones">
            <UTextarea v-model="newNotes" :rows="2" placeholder="Zona, referencias, observaciones..." class="w-full" />
          </UFormField>

          <!-- Productos -->
          <div class="space-y-3">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Productos a Consignar</p>
            <div v-for="(item, idx) in newItems" :key="idx" class="bg-slate-50 rounded-xl p-3 space-y-2 border border-slate-200">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Producto {{ idx + 1 }}</span>
                <UButton icon="i-lucide-x" color="error" variant="ghost" size="xs" @click="removeItemRow(idx)" />
              </div>
              <USelect
                v-model="item.id_product"
                :items="productOptions"
                placeholder="Seleccionar producto..."
                class="w-full"
                @update:model-value="() => onProductSelect(item)"
              />
              <div class="grid grid-cols-2 gap-2">
                <UFormField label="Cantidad">
                  <UInput v-model.number="item.qty" type="number" step="1" min="1" class="w-full" />
                </UFormField>
                <UFormField label="Precio unit. (Bs.)">
                  <UInput v-model.number="item.price" type="number" step="0.01" min="0" class="w-full" disabled />
                </UFormField>
              </div>
              <p class="text-xs text-right font-mono text-indigo-600 font-bold">Subtotal: {{ fmt(item.qty * item.price) }}</p>
            </div>
            <UButton variant="soft" color="neutral" icon="i-lucide-plus" block @click="addItemRow">Agregar Producto</UButton>
          </div>

          <!-- Total -->
          <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 flex justify-between items-center">
            <span class="text-sm font-bold text-indigo-700">Total Consignación</span>
            <span class="text-xl font-black font-mono text-indigo-700">{{ fmt(newTotal) }}</span>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="createModal = false">Cancelar</UButton>
          <UButton color="primary" icon="i-lucide-send" :loading="creating" :disabled="!itemsValid" @click="createConsignment">
            Crear Consignación
          </UButton>
        </div>
      </template>
    </UModal>

    <!-- ═══ MODAL: GESTIÓN DE CONSIGNACIÓN ═════════════════════════════════════ -->
    <UModal v-model:open="detailModal" :title="`Consignación #${selected?.id_consignment}`" :ui="{ body: 'max-h-[80vh] overflow-y-auto', width: 'max-w-2xl' }">
      <template #body>
        <div v-if="loadingDetail" class="flex justify-center py-10">
          <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-indigo-500" />
        </div>
        <div v-else-if="selected" class="space-y-4 p-1">

          <!-- Header info -->
          <div class="flex flex-wrap items-start justify-between gap-3 bg-slate-50 rounded-xl p-4 border border-slate-200">
            <div>
              <p class="text-sm font-bold text-slate-800">{{ selected.client_name?.trim() || 'Sin cliente' }}</p>
              <p class="text-xs text-slate-500">Vendedor: {{ decode(selected.name_admin || '') }}</p>
              <p v-if="selected.notes_consignment" class="text-xs text-slate-400 mt-1 italic">"{{ selected.notes_consignment }}"</p>
            </div>
            <UBadge :color="statusColor(selected.status_consignment)" variant="subtle" size="sm" class="font-bold capitalize">
              {{ statusLabel(selected.status_consignment) }}
            </UBadge>
          </div>

          <!-- Progress -->
          <div class="space-y-2">
            <div class="flex justify-between text-xs text-slate-500 font-medium">
              <span>{{ fmt(parseFloat(selected.paid_consignment) || 0) }} cobrado</span>
              <span class="font-mono font-bold text-indigo-700">{{ progressPct }}%</span>
              <span>{{ fmt(parseFloat(selected.total_consignment) || 0) }} total</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-3">
              <div
                class="h-3 rounded-full transition-all duration-700"
                :class="selected.status_consignment === 'completada' ? 'bg-emerald-500' : 'bg-indigo-500'"
                :style="{ width: progressPct + '%' }"
              />
            </div>
            <div class="flex justify-between">
              <span class="text-xs text-emerald-600 font-bold">Cobrado: {{ fmt(parseFloat(selected.paid_consignment) || 0) }}</span>
              <span class="text-xs text-amber-600 font-bold">Saldo: {{ fmt(balance) }}</span>
            </div>
          </div>

          <!-- Tabs -->
          <div class="flex gap-1 bg-slate-100 p-1 rounded-xl">
            <button
              v-for="tab in [{ key: 'cuenta', icon: 'i-lucide-list', label: 'Estado de Cuenta' }, { key: 'pago', icon: 'i-lucide-banknote', label: 'Registrar Abono' }, { key: 'devolucion', icon: 'i-lucide-package-x', label: 'Dev. / Reposición' }]"
              :key="tab.key"
              class="flex-1 flex items-center justify-center gap-1.5 py-2 px-2 rounded-lg text-xs font-bold transition-all duration-150"
              :class="detailTab === tab.key ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
              @click="detailTab = tab.key as any"
            >
              <UIcon :name="tab.icon" class="w-3.5 h-3.5" />
              <span class="hidden sm:inline">{{ tab.label }}</span>
            </button>
          </div>

          <!-- TAB 1: Estado de Cuenta -->
          <div v-if="detailTab === 'cuenta'" class="space-y-3">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Productos Consignados</p>
            <div v-for="item in detailItems" :key="item.id_consignment_item"
              class="bg-white border border-slate-200 rounded-xl p-3"
            >
              <div class="flex items-start justify-between gap-2 mb-2">
                <div>
                  <p class="font-bold text-slate-800 text-sm uppercase tracking-wide">{{ decode(item.title_product) }}</p>
                  <p class="text-xs text-slate-400">{{ item.sku_product }} · {{ item.unit_product }}</p>
                </div>
                <span class="font-mono text-xs font-bold text-indigo-600">{{ fmt(parseFloat(item.price_consignment)) }}/u</span>
              </div>
              <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="bg-slate-50 rounded-lg p-1.5">
                  <p class="text-slate-400 font-medium">Asignado</p>
                  <p class="font-black text-slate-700 text-base">{{ item.qty_assigned }}</p>
                </div>
                <div class="bg-emerald-50 rounded-lg p-1.5">
                  <p class="text-emerald-500 font-medium">Vendido</p>
                  <p class="font-black text-emerald-700 text-base">{{ item.qty_sold || 0 }}</p>
                </div>
                <div class="bg-amber-50 rounded-lg p-1.5">
                  <p class="text-amber-500 font-medium">Devuelto</p>
                  <p class="font-black text-amber-700 text-base">{{ item.qty_returned || 0 }}</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-1.5">
                  <p class="text-blue-500 font-medium">Pendiente</p>
                  <p class="font-black text-blue-700 text-base">
                    {{ Math.max(0, parseInt(item.qty_assigned) - parseInt(item.qty_sold || 0) - parseInt(item.qty_returned || 0) - parseInt(item.qty_reponed || 0)) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Historial de pagos -->
            <div v-if="detailPayments.length > 0" class="space-y-2">
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mt-4">Historial de Pagos</p>
              <div v-for="pay in detailPayments" :key="pay.id_payment"
                class="flex items-center justify-between bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-2"
              >
                <div>
                  <p class="text-sm font-bold text-emerald-700 font-mono">{{ fmt(parseFloat(pay.amount_payment)) }}</p>
                  <p class="text-xs text-slate-500">{{ pay.method_payment }} · {{ pay.date_created_payment }}
                    <span v-if="pay.reference_payment"> · Ref: {{ pay.reference_payment }}</span>
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <UButton
                    v-if="String(pay.method_payment).toUpperCase() === 'QR' && pay.file_payment"
                    size="2xs" color="neutral" variant="soft"
                    icon="i-lucide-eye"
                    :href="getImageUrl(pay.file_payment)" target="_blank"
                  >
                    Comprobante
                  </UButton>
                  <UIcon name="i-lucide-check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />
                </div>
              </div>
            </div>

            <!-- Historial de reposiciones -->
            <div v-if="detailReplacements.length > 0" class="space-y-2">
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mt-2">Reposiciones Realizadas</p>
              <div v-for="rep in detailReplacements" :key="rep.id_replacement"
                class="flex items-center gap-3 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2"
              >
                <UIcon name="i-lucide-arrow-left-right" class="w-4 h-4 text-blue-500 shrink-0" />
                <div class="text-xs">
                  <p class="font-semibold text-slate-700">{{ rep.qty_replacement }}x <span class="line-through text-slate-400">{{ decode(rep.product_out_name || '') }}</span> → {{ decode(rep.product_in_name || '') }}</p>
                  <p class="text-slate-400">{{ rep.date_created_replacement }}</p>
                </div>
              </div>
            </div>

            <!-- Completada badge -->
            <div v-if="selected.status_consignment === 'completada'" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
              <UIcon name="i-lucide-check-circle-2" class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
              <p class="font-bold text-emerald-700">¡Consignación Completada!</p>
              <p v-if="selected.id_order_consignment" class="text-xs text-emerald-600 mt-1">Orden de venta generada: <span class="font-mono font-bold">#{{ selected.id_order_consignment }}</span></p>
            </div>
          </div>

          <!-- TAB 2: Registrar Abono -->
          <div v-if="detailTab === 'pago'" class="space-y-4">
            <div v-if="selected.status_consignment === 'completada'" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
              <UIcon name="i-lucide-check-circle-2" class="w-8 h-8 text-emerald-500 mx-auto mb-1" />
              <p class="text-sm font-bold text-emerald-700">Esta consignación ya está completamente pagada</p>
            </div>
            <template v-else>
              <!-- Saldo resumen -->
              <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-center justify-between mb-4">
                <span class="text-sm font-bold text-amber-700">Saldo Pendiente</span>
                <span class="text-xl font-black font-mono text-amber-700">{{ fmt(balance) }}</span>
              </div>

              <!-- Payment methods toggle -->
              <div>
                <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-2">Método de Pago</label>
                <div class="grid grid-cols-2 gap-2">
                  <UButton
                    :color="payMethod === 'efectivo' ? 'primary' : 'neutral'"
                    variant="soft"
                    icon="i-lucide-banknote"
                    size="sm"
                    class="flex-col py-3.5"
                    @click="payMethod = 'efectivo'"
                  >
                    Efectivo
                  </UButton>
                  <UButton
                    :color="payMethod === 'QR' ? 'primary' : 'neutral'"
                    variant="soft"
                    icon="i-lucide-qr-code"
                    size="sm"
                    class="flex-col py-3.5"
                    @click="payMethod = 'QR'"
                  >
                    QR
                  </UButton>
                </div>
              </div>

              <!-- QR details form -->
              <div v-if="payMethod === 'QR'">
                <div v-if="qrImage" class="flex justify-center my-6">
                  <img :src="getImageUrl(qrImage)" alt="Código QR" class="max-w-[350px] w-full border-4 border-white rounded-xl shadow-xl" />
                </div>
                <div v-else class="text-center text-xs text-slate-400 my-4 italic">
                  No hay código QR configurado para esta sucursal.
                </div>
              </div>

              <div v-if="payMethod === 'QR' && (auth.role === 'vendedor' || auth.user?.type_seller)" class="space-y-4 mb-4">

                <div>
                  <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Imagen / Comprobante (Opcional)</label>
                  <input
                    type="file"
                    accept="image/*,.pdf"
                    class="block w-full text-sm text-slate-500
                      file:mr-4 file:py-2 file:px-4
                      file:rounded-full file:border-0
                      file:text-sm file:font-semibold
                      file:bg-emerald-50 file:text-emerald-700
                      hover:file:bg-emerald-100"
                    @change="onPayFile"
                  />
                  <div v-if="payFilePreview" class="mt-4 flex justify-center">
                    <img :src="payFilePreview" alt="Vista previa comprobante" class="max-h-48 rounded-lg shadow-md border border-slate-200" />
                  </div>
                </div>
              </div>

              <UFormField label="Monto del Abono (Bs.)">
                <UInput v-model="payAmount" type="number" step="0.01" min="0.01" :max="balance" placeholder="0.00" size="md" class="w-full" />
                <template #help>
                  <span class="text-xs text-slate-400">Saldo pendiente: {{ fmt(balance) }}</span>
                </template>
              </UFormField>

              <UFormField label="Notas del Pago (opcional)">
                <UTextarea v-model="payNotes" :rows="2" placeholder="Observaciones del pago..." class="w-full" />
              </UFormField>

              <UButton
                block color="success" icon="i-lucide-banknote" size="md" class="font-bold!"
                :loading="payingLoading"
                :disabled="!payAmount || parseFloat(String(payAmount)) <= 0"
                @click="submitPayment"
              >
                Registrar Abono de {{ payAmount ? fmt(parseFloat(String(payAmount))) : '...' }}
              </UButton>
            </template>

            <!-- Payment history in this tab too -->
            <div v-if="detailPayments.length > 0" class="space-y-2 mt-2">
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pagos Registrados</p>
              <div v-for="pay in detailPayments" :key="pay.id_payment"
                class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-lg px-3 py-2"
              >
                <div>
                  <p class="text-sm font-bold text-slate-800 font-mono">{{ fmt(parseFloat(pay.amount_payment)) }}</p>
                  <p class="text-xs text-slate-400">{{ pay.method_payment }}
                    <span v-if="pay.reference_payment"> · {{ pay.reference_payment }}</span>
                    · {{ pay.date_created_payment }}
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <UButton
                    v-if="String(pay.method_payment).toUpperCase() === 'QR' && pay.file_payment"
                    size="2xs" color="neutral" variant="soft"
                    icon="i-lucide-eye"
                    :href="getImageUrl(pay.file_payment)" target="_blank"
                  >
                    Comprobante
                  </UButton>
                  <span class="text-xs font-semibold text-slate-500">{{ decode(pay.admin_name || '') }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 3: Devoluciones y Reposiciones -->
          <div v-if="detailTab === 'devolucion'" class="space-y-6">
            <!-- DEVOLUCIÓN -->
            <div class="space-y-3">
              <div class="flex items-center gap-2">
                <UIcon name="i-lucide-package-x" class="w-4 h-4 text-amber-500" />
                <h3 class="text-sm font-bold text-slate-700">Devolución de Producto</h3>
              </div>
              <p class="text-xs text-slate-500">El cliente devuelve unidades que se reintegran a tu inventario. El total de la consignación se ajusta automáticamente.</p>

              <UFormField label="Producto a Devolver">
                <select v-model="returnItemId" class="block w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
                  <option value="">Seleccionar producto...</option>
                  <option v-for="item in activeItems" :key="item.id_consignment_item" :value="String(item.id_consignment_item)">
                    {{ decode(item.title_product) }} (Disponible: {{ parseInt(item.qty_assigned) - parseInt(item.qty_returned || 0) - parseInt(item.qty_reponed || 0) }})
                  </option>
                </select>
              </UFormField>

              <UFormField :label="`Cantidad a Devolver ${returnItem ? `(máx. ${returnMax})` : ''}`">
                <UInput v-model.number="returnQty" type="number" step="1" min="1" :max="returnMax || 999" class="w-full" />
              </UFormField>

              <UButton
                block color="warning" icon="i-lucide-package-minus" class="font-bold!"
                :loading="returningLoading"
                :disabled="!returnItemId || returnQty <= 0"
                @click="submitReturn"
              >
                Registrar Devolución
              </UButton>
            </div>

            <div class="border-t border-slate-200" />

            <!-- REPOSICIÓN -->
            <div class="space-y-3">
              <div class="flex items-center gap-2">
                <UIcon name="i-lucide-arrow-left-right" class="w-4 h-4 text-blue-500" />
                <h3 class="text-sm font-bold text-slate-700">Reposición / Cambio de Producto</h3>
              </div>
              <p class="text-xs text-slate-500">El cliente devuelve un producto y recibe otro en su lugar (swap). Ambos stocks se ajustan automáticamente.</p>

              <UFormField label="Producto que Devuelve el Cliente">
                <select v-model="replItemOutId" class="block w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
                  <option value="">Seleccionar producto a devolver...</option>
                  <option v-for="item in activeItems" :key="item.id_consignment_item" :value="String(item.id_consignment_item)">
                    {{ decode(item.title_product) }} (Disponible: {{ parseInt(item.qty_assigned) - parseInt(item.qty_returned || 0) - parseInt(item.qty_reponed || 0) }})
                  </option>
                </select>
              </UFormField>

              <UFormField label="Producto que le Entregas">
                <select v-model="replProductInId" class="block w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
                  <option value="">Seleccionar producto nuevo...</option>
                  <option v-for="p in products" :key="p.id_product" :value="String(p.id_product)">
                    {{ decode(p.title_product) }} (Stock: {{ inventory[p.id_product] || 0 }})
                  </option>
                </select>
              </UFormField>

              <div class="grid grid-cols-2 gap-2">
                <UFormField :label="`Cantidad ${replItemOut ? `(máx. ${replMax})` : ''}`">
                  <UInput v-model.number="replQty" type="number" step="1" min="1" :max="replMax || 999" class="w-full" />
                </UFormField>
                <UFormField label="Precio unitario del nuevo (Bs.)">
                  <UInput v-model.number="replPrice" type="number" step="0.01" min="0" class="w-full" />
                </UFormField>
              </div>

              <UFormField label="Notas (opcional)">
                <UInput v-model="replNotes" placeholder="Motivo del cambio..." class="w-full" />
              </UFormField>

              <UButton
                block color="primary" icon="i-lucide-arrow-left-right" class="font-bold!"
                :loading="replacingLoading"
                :disabled="!replItemOutId || !replProductInId || replQty <= 0"
                @click="submitReplacement"
              >
                Registrar Reposición
              </UButton>
            </div>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end">
          <UButton color="neutral" variant="ghost" @click="detailModal = false">Cerrar</UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>
