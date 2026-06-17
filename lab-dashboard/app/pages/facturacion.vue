<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()
const ajaxBase = '/ajax/pos.ajax.php'

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

const invoices = ref<any[]>([])
const loading = ref(true)
const statusFilter = ref<'todos' | 'pendiente' | 'emitida' | 'anulada'>('todos')
const search = ref('')

// Modal emitir factura
const emitModal = ref(false)
const selected = ref<any>(null)
const emitForm = ref({ invoice_number: '', nit: '', client_name: '' })
const emitting = ref(false)

function decode(s: string) { return s ? decodeURIComponent(String(s)).replace(/\+/g, ' ') : '' }
function fmt(v: number) { return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(v || 0) }

const filtered = computed(() => {
  let list = invoices.value
  if (statusFilter.value !== 'todos') list = list.filter(i => i.status_invoice === statusFilter.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(i =>
      (i.transaction_order || '').includes(q) ||
      decode(i.name_client || '').toLowerCase().includes(q) ||
      (i.nit_invoice || '').includes(q) ||
      (i.invoice_number || '').toLowerCase().includes(q)
    )
  }
  return list
})

const kpis = computed(() => ({
  total: invoices.value.length,
  pendientes: invoices.value.filter(i => i.status_invoice === 'pendiente').length,
  emitidas: invoices.value.filter(i => i.status_invoice === 'emitida').length,
  totalBs: invoices.value.filter(i => i.status_invoice === 'emitida').reduce((a, i) => a + parseFloat(i.total_invoice || 0), 0)
}))

async function fetchInvoices() {
  loading.value = true
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ getInvoices: 'ok', id_office: String(auth.role === 'superadmin' || auth.role === 'admin' ? 0 : auth.officeId || 0) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  invoices.value = d?.status === 200 ? d.results : []
  loading.value = false
}

function openEmit(inv: any) {
  selected.value = inv
  emitForm.value = {
    invoice_number: `FAC-${new Date().getFullYear()}-${String(inv.id_invoice).padStart(5, '0')}`,
    nit: inv.nit_invoice || '',
    client_name: `${decode(inv.name_client || '')} ${decode(inv.surname_client || '')}`.trim()
  }
  emitModal.value = true
}

async function emitInvoice() {
  if (!emitForm.value.nit) { toast.add({ title: 'El NIT del cliente es requerido para emitir factura', color: 'error' }); return }
  emitting.value = true
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ emitInvoice: 'ok', id_invoice: String(selected.value.id_invoice), invoice_number: emitForm.value.invoice_number, nit: emitForm.value.nit, client_name: emitForm.value.client_name }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  if (d?.status === 200) {
    toast.add({ title: `Factura ${d.invoice_number} emitida`, color: 'success' })
    emitModal.value = false
    await fetchInvoices()
  } else {
    toast.add({ title: 'Error al emitir factura', color: 'error' })
  }
  emitting.value = false
}

async function voidInvoice(inv: any) {
  if (!await confirmAction('Anular factura', `¿Anular factura ${inv.invoice_number || '#' + inv.id_invoice}? Esta acción no se puede deshacer.`)) return
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ voidInvoice: 'ok', id_invoice: String(inv.id_invoice) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  if (d?.status === 200) {
    toast.add({ title: 'Factura anulada', color: 'success' })
    await fetchInvoices()
  }
}

const statusConfig: Record<string, { color: any, label: string }> = {
  pendiente: { color: 'warning', label: 'Pendiente' },
  emitida:   { color: 'success', label: 'Emitida' },
  anulada:   { color: 'error',   label: 'Anulada' }
}

onMounted(fetchInvoices)
</script>

<template>
  <div class="space-y-5">

    <!-- KPIs -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-white border border-slate-200 rounded-xl p-4 text-center shadow-sm">
        <p class="text-2xl font-black text-slate-800">{{ kpis.total }}</p>
        <p class="text-xs text-slate-500 mt-0.5">Total</p>
      </div>
      <div class="bg-white border border-amber-200 rounded-xl p-4 text-center shadow-sm">
        <p class="text-2xl font-black text-amber-600">{{ kpis.pendientes }}</p>
        <p class="text-xs text-slate-500 mt-0.5">Pendientes</p>
      </div>
      <div class="bg-white border border-emerald-200 rounded-xl p-4 text-center shadow-sm">
        <p class="text-2xl font-black text-emerald-600">{{ kpis.emitidas }}</p>
        <p class="text-xs text-slate-500 mt-0.5">Emitidas</p>
      </div>
      <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-4 text-center shadow-sm">
        <p class="text-lg font-black text-white">{{ fmt(kpis.totalBs) }}</p>
        <p class="text-xs text-green-100 mt-0.5">Total Facturado</p>
      </div>
    </div>

    <!-- Controles -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
      <div class="flex flex-wrap gap-2">
        <UButton v-for="s in ['todos','pendiente','emitida','anulada']" :key="s"
          :color="statusFilter === s ? 'primary' : 'neutral'" variant="soft" size="sm"
          class="capitalize" @click="statusFilter = (s as any)"
        >{{ s }}</UButton>
      </div>
      <div class="flex gap-2 w-full sm:w-auto">
        <UInput v-model="search" icon="i-lucide-search" placeholder="Buscar orden, cliente, NIT..." size="sm" class="flex-1 sm:w-64" />
        <UButton icon="i-lucide-refresh-cw" variant="ghost" color="neutral" size="sm" @click="fetchInvoices" />
      </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div v-if="loading" class="flex justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-7 h-7 animate-spin text-green-500" />
      </div>
      <div v-else-if="filtered.length === 0" class="text-center py-14">
        <UIcon name="i-lucide-file-text" class="w-12 h-12 mx-auto text-slate-300 mb-3" />
        <p class="text-slate-500 text-sm">No hay facturas en este estado</p>
        <p class="text-xs text-slate-400 mt-1">Las facturas se crean automáticamente cuando una venta tiene marcada la opción "Emitir Factura"</p>
      </div>
      <template v-else>
        <!-- Cards en móvil -->
        <div class="block sm:hidden divide-y divide-slate-100">
          <div v-for="inv in filtered" :key="inv.id_invoice" class="p-4 space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-bold text-slate-800 text-sm">
                  {{ inv.invoice_number || `FAC Pendiente #${inv.id_invoice}` }}
                </p>
                <p class="text-xs text-slate-500 font-mono">Orden: {{ inv.transaction_order }}</p>
              </div>
              <UBadge :color="statusConfig[inv.status_invoice]?.color || 'neutral'" variant="subtle" size="xs">
                {{ statusConfig[inv.status_invoice]?.label || inv.status_invoice }}
              </UBadge>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div><p class="text-slate-400">Cliente</p><p class="font-semibold text-slate-700 truncate">{{ decode(inv.name_client || 'S/N') }}</p></div>
              <div><p class="text-slate-400">Total</p><p class="font-bold text-green-600 font-mono">{{ fmt(parseFloat(inv.total_invoice)) }}</p></div>
              <div v-if="inv.nit_invoice"><p class="text-slate-400">NIT</p><p class="font-mono text-slate-600">{{ inv.nit_invoice }}</p></div>
              <div><p class="text-slate-400">Fecha</p><p class="text-slate-600">{{ inv.date_created_invoice }}</p></div>
            </div>
            <div class="flex gap-2">
              <UButton v-if="inv.status_invoice === 'pendiente'" size="xs" color="primary" icon="i-lucide-file-check" class="flex-1 justify-center" @click="openEmit(inv)">Emitir</UButton>
              <UButton v-if="inv.status_invoice === 'emitida'" size="xs" color="error" variant="ghost" icon="i-lucide-ban" @click="voidInvoice(inv)">Anular</UButton>
            </div>
          </div>
        </div>

        <!-- Tabla en desktop -->
        <div class="hidden sm:block overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase">
                <th class="px-4 py-3">N° Factura</th>
                <th class="px-4 py-3">Orden</th>
                <th class="px-4 py-3">Cliente</th>
                <th class="px-4 py-3 hidden md:table-cell">NIT</th>
                <th class="px-4 py-3">Total</th>
                <th class="px-4 py-3 hidden lg:table-cell">Fecha</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in filtered" :key="inv.id_invoice" class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-3 font-mono font-semibold text-slate-700">
                  {{ inv.invoice_number || `— Pendiente #${inv.id_invoice}` }}
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ inv.transaction_order }}</td>
                <td class="px-4 py-3 text-slate-700">
                  <p class="font-semibold">{{ decode(inv.name_client || '') }} {{ decode(inv.surname_client || '') }}</p>
                  <p class="text-xs text-slate-400">{{ inv.dni_client || '' }}</p>
                </td>
                <td class="px-4 py-3 hidden md:table-cell font-mono text-xs text-slate-500">{{ inv.nit_invoice || '—' }}</td>
                <td class="px-4 py-3 font-bold text-green-600 font-mono">{{ fmt(parseFloat(inv.total_invoice)) }}</td>
                <td class="px-4 py-3 hidden lg:table-cell text-xs text-slate-500">{{ inv.date_created_invoice }}</td>
                <td class="px-4 py-3">
                  <UBadge :color="statusConfig[inv.status_invoice]?.color || 'neutral'" variant="subtle" size="xs">
                    {{ statusConfig[inv.status_invoice]?.label || inv.status_invoice }}
                  </UBadge>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <UButton v-if="inv.status_invoice === 'pendiente'" size="xs" color="primary" variant="soft" icon="i-lucide-file-check" @click="openEmit(inv)">Emitir</UButton>
                    <UButton v-if="inv.status_invoice === 'emitida'" size="xs" color="error" variant="ghost" icon="i-lucide-ban" title="Anular" @click="voidInvoice(inv)" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- Modal: Emitir Factura -->
    <UModal v-model:open="emitModal" title="Emitir Factura">
      <template #body>
        <div class="space-y-4 p-1" v-if="selected">
          <!-- Resumen de la orden -->
          <div class="bg-slate-50 rounded-xl p-4 space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Orden</span>
              <span class="font-mono font-bold">{{ selected.transaction_order }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Total</span>
              <span class="font-bold text-green-600">{{ fmt(parseFloat(selected.total_invoice)) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Método</span>
              <span class="capitalize">{{ selected.method_order || '—' }}</span>
            </div>
          </div>

          <UFormField label="N° de Factura">
            <UInput v-model="emitForm.invoice_number" placeholder="FAC-2026-00001" class="w-full font-mono" />
          </UFormField>
          <UFormField label="NIT del Cliente *">
            <UInput v-model="emitForm.nit" placeholder="Número de NIT / RUC" class="w-full" />
          </UFormField>
          <UFormField label="Nombre para la Factura">
            <UInput v-model="emitForm.client_name" placeholder="Razón social o nombre completo" class="w-full" />
          </UFormField>

          <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700">
            <UIcon name="i-lucide-alert-triangle" class="w-3.5 h-3.5 inline mr-1" />
            Una vez emitida, la factura solo puede anularse. Verifica el NIT antes de confirmar.
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="emitModal = false">Cancelar</UButton>
          <UButton color="primary" icon="i-lucide-file-check" :loading="emitting" @click="emitInvoice">Emitir Factura</UButton>
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
