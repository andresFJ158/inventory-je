<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()
const api = useApi()

const consignments = ref<any[]>([])
const products = ref<any[]>([])
const inventory = ref<Record<string, number>>({})
const loading = ref(true)

const createModal = ref(false)
const newItems = ref<{ id_product: string, qty: number, price: number }[]>([])
const newNotes = ref('')
const creating = ref(false)

const detailModal = ref(false)
const selected = ref<any>(null)
const items = ref<any[]>([])
const loadingItems = ref(false)
const liquidating = ref(false)
const liqReference = ref('')
const liqProofFile = ref<File | null>(null)

function onLiqProofChange(e: Event) {
  const files = (e.target as HTMLInputElement).files
  liqProofFile.value = files && files.length ? files[0]! : null
}

const decode = decodeText
const fmt = formatBob

async function fetchProducts() {
  const [pd, id] = await Promise.all([
    api.rest<any>('/api/products?linkTo=status_product&equalTo=1'),
    api.rest<any>(`/api/product_inventory?linkTo=id_office_inventory&equalTo=${auth.officeId || 3}`)
  ])
  if (pd?.status === 200) products.value = pd.results || []
  if (id?.status === 200 && id.results) {
    const inv: Record<string, number> = {}
    id.results.forEach((i: any) => { inv[i.id_product_inventory] = parseFloat(i.stock_inventory) || 0 })
    inventory.value = inv
  }
}

async function fetchConsignments() {
  loading.value = true
  const d = await api.ajax({ getConsignments: 'ok', id_office: auth.officeId || 0 })
  consignments.value = d?.status === 200 ? d.results : []
  loading.value = false
}

function addItemRow() { newItems.value.push({ id_product: '', qty: 1, price: 0 }) }
function removeItemRow(i: number) { newItems.value.splice(i, 1) }

const itemsValid = computed(() => newItems.value.length > 0 && newItems.value.every(i => i.id_product && i.qty > 0))

async function createConsignment() {
  if (!itemsValid.value) return
  creating.value = true
  const d = await api.ajax({
    createConsignment: 'ok', id_admin: auth.user?.id_admin || 0, id_office: auth.officeId || 0,
    notes: newNotes.value,
    items: JSON.stringify(newItems.value.map(i => ({ id_product: i.id_product, qty: i.qty, price: i.price })))
  })
  if (d?.status === 200) {
    toast.add({ title: 'Consignación creada', color: 'success' })
    createModal.value = false
    newItems.value = []; newNotes.value = ''
    await fetchConsignments()
    await fetchProducts()
  } else {
    toast.add({ title: d?.message || 'Error al crear consignación', color: 'error' })
  }
  creating.value = false
}

async function openDetail(c: any) {
  selected.value = c
  detailModal.value = true
  liqReference.value = ''
  liqProofFile.value = null
  loadingItems.value = true
  const d = await api.ajax({ getConsignmentItems: 'ok', id_consignment: c.id_consignment })
  items.value = d?.status === 200 ? d.results.map((i: any) => ({ ...i, qty_sold_input: i.qty_sold || 0, qty_returned_input: i.qty_returned || 0 })) : []
  loadingItems.value = false
}

const accountTotal = computed(() => {
  return items.value.reduce((acc, i) => {
    const sold = parseFloat(i.qty_sold_input) || 0
    return acc + sold * parseFloat(i.price_consignment || 0)
  }, 0)
})

const pendingTotal = computed(() => {
  return items.value.reduce((acc, i) => {
    const assigned = parseFloat(i.qty_assigned) || 0
    const sold = parseFloat(i.qty_sold_input) || 0
    const returned = parseFloat(i.qty_returned_input) || 0
    return acc + (assigned - sold - returned) * parseFloat(i.price_consignment || 0)
  }, 0)
})

async function liquidate() {
  if (!selected.value) return
  liquidating.value = true
  const payload = items.value.map(i => ({ id_consignment_item: i.id_consignment_item, id_product_consignment: i.id_product_consignment, qty_sold: i.qty_sold_input, qty_returned: i.qty_returned_input }))
  // FormData para adjuntar el comprobante de la liquidación
  const fd = new FormData()
  fd.append('liquidateConsignment', 'ok')
  fd.append('id_consignment', String(selected.value.id_consignment))
  fd.append('id_office', String(auth.officeId || 0))
  fd.append('items', JSON.stringify(payload))
  fd.append('reference', liqReference.value)
  if (liqProofFile.value) fd.append('proof', liqProofFile.value)
  const d = await api.ajaxForm(fd)
  if (d?.status === 200) {
    toast.add({ title: 'Liquidación completada', color: 'success' })
    if (d.file_warning) toast.add({ title: 'Comprobante no adjuntado', description: d.file_warning, color: 'warning' })
    liqReference.value = ''
    liqProofFile.value = null
    detailModal.value = false
    await fetchConsignments()
  } else {
    toast.add({ title: d?.message || 'Error en la liquidación', color: 'error' })
  }
  liquidating.value = false
}

const getProductName = (id: string) => {
  const p = products.value.find(p => String(p.id_product) === String(id))
  return p ? decode(p.title_product) : `Producto ${id}`
}

onMounted(async () => { await Promise.all([fetchProducts(), fetchConsignments()]) })
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <p class="text-sm text-slate-500 dark:text-slate-400">Gestiona las consignaciones de productos para vendedores externos. Registra la entrega, el inventario asignado y la liquidación posterior.</p>
      <UButton color="primary" icon="i-lucide-plus" @click="() => { newItems = [{ id_product: '', qty: 1, price: 0 }]; createModal = true }">Nueva Consignación</UButton>
    </div>

    <!-- Tabla -->
    <UCard>
      <div v-if="loading" class="flex justify-center py-10">
        <UIcon name="i-lucide-loader-2" class="w-7 h-7 animate-spin text-green-500" />
      </div>
      <div v-else-if="consignments.length === 0" class="text-center py-12 text-slate-400">
        <UIcon name="i-lucide-package" class="w-10 h-10 mx-auto mb-3 text-slate-300" />
        <p class="text-sm">Sin consignaciones registradas</p>
      </div>
      <!-- Cards en móvil -->
      <div class="block sm:hidden space-y-3">
        <div v-for="c in consignments" :key="c.id_consignment" class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl p-4 space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="font-bold text-slate-800 dark:text-white text-sm">{{ decode(c.name_admin || '') || `Admin #${c.id_admin_consignment}` }}</p>
              <p class="text-xs text-slate-400 font-mono">#{{ c.id_consignment }} · {{ c.date_created_consignment }}</p>
            </div>
            <UBadge :color="c.status_consignment === 'liquidada' ? 'success' : c.status_consignment === 'parcial' ? 'warning' : 'primary'" variant="subtle" size="xs" class="capitalize shrink-0">{{ c.status_consignment }}</UBadge>
          </div>
          <p v-if="c.notes_consignment" class="text-xs text-slate-500 dark:text-slate-400">{{ c.notes_consignment }}</p>
          <UButton size="xs" variant="soft" color="primary" icon="i-lucide-clipboard-list" block @click="openDetail(c)">
            {{ c.status_consignment === 'liquidada' ? 'Ver Detalle' : 'Liquidar' }}
          </UButton>
        </div>
      </div>

      <!-- Tabla en desktop -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-slate-900">
              <th class="px-4 py-3">#</th>
              <th class="px-4 py-3">Vendedor</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3 hidden md:table-cell">Fecha</th>
              <th class="px-4 py-3 hidden lg:table-cell">Notas</th>
              <th class="px-4 py-3 text-right">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in consignments" :key="c.id_consignment" class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <td class="px-4 py-3 font-mono text-slate-400 dark:text-slate-500 text-xs">#{{ c.id_consignment }}</td>
              <td class="px-4 py-3 font-semibold text-slate-800 dark:text-white">{{ decode(c.name_admin || '') || `Admin #${c.id_admin_consignment}` }}</td>
              <td class="px-4 py-3">
                <UBadge :color="c.status_consignment === 'liquidada' ? 'success' : c.status_consignment === 'parcial' ? 'warning' : 'primary'" variant="subtle" size="xs" class="capitalize">{{ c.status_consignment }}</UBadge>
              </td>
              <td class="px-4 py-3 hidden md:table-cell text-slate-500 dark:text-slate-400 text-xs">{{ c.date_created_consignment }}</td>
              <td class="px-4 py-3 hidden lg:table-cell text-slate-500 dark:text-slate-400 max-w-xs truncate text-xs">{{ c.notes_consignment || '—' }}</td>
              <td class="px-4 py-3 text-right">
                <UButton size="xs" variant="soft" color="primary" icon="i-lucide-clipboard-list" @click="openDetail(c)">
                  {{ c.status_consignment === 'liquidada' ? 'Ver' : 'Liquidar' }}
                </UButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </UCard>

    <!-- Modal: Nueva consignación -->
    <UModal v-model:open="createModal" title="Nueva Consignación" :ui="{ body: 'max-h-[70vh] overflow-y-auto' }">
      <template #body>
        <div class="space-y-4 p-1">
          <UFormField label="Notas / Descripción">
            <UTextarea v-model="newNotes" rows="2" placeholder="Vendedor, zona, observaciones..." class="w-full" />
          </UFormField>

          <div class="space-y-3">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Productos a Consignar</p>

            <div v-for="(item, idx) in newItems" :key="idx" class="bg-slate-50 dark:bg-slate-800 rounded-lg p-3 space-y-2">
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-semibold text-slate-500">Producto {{ idx + 1 }}</span>
                <UButton icon="i-lucide-x" color="error" variant="ghost" size="xs" @click="removeItemRow(idx)" />
              </div>
              <USelect
                v-model="item.id_product"
                :items="products.map(p => ({ value: String(p.id_product), label: `${decode(p.title_product)} (Stock: ${inventory[p.id_product] || 0})` }))"
                placeholder="Seleccionar producto..."
                class="w-full"
              />
              <div class="grid grid-cols-2 gap-2">
                <UFormField label="Cantidad">
                  <UInput v-model.number="item.qty" type="number" step="1" min="1" class="w-full" />
                </UFormField>
                <UFormField label="Precio unitario (Bs.)">
                  <UInput v-model.number="item.price" type="number" step="0.01" min="0" class="w-full" />
                </UFormField>
              </div>
            </div>

            <UButton variant="soft" color="neutral" icon="i-lucide-plus" block @click="addItemRow">Agregar Producto</UButton>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="createModal = false">Cancelar</UButton>
          <UButton color="primary" :loading="creating" :disabled="!itemsValid" @click="createConsignment">Crear Consignación</UButton>
        </div>
      </template>
    </UModal>

    <!-- Modal: Detalle y liquidación -->
    <UModal v-model:open="detailModal" title="Liquidación de Consignación" :ui="{ body: 'max-h-[75vh] overflow-y-auto' }">
      <template #body>
        <div v-if="selected" class="space-y-4 p-1">

          <div v-if="loadingItems" class="flex justify-center py-8">
            <UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin text-green-500" />
          </div>

          <template v-else>
            <!-- Items para liquidar -->
            <div class="space-y-3">
              <div v-for="item in items" :key="item.id_consignment_item" class="bg-slate-50 dark:bg-slate-800 rounded-xl p-3 space-y-2">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-semibold text-slate-800 dark:text-white text-sm">{{ decode(item.title_product) }}</p>
                    <p class="text-xs text-slate-500">{{ item.sku_product }} · {{ item.unit_product }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-slate-400">Asignado</p>
                    <p class="font-bold text-slate-700 dark:text-slate-200">{{ item.qty_assigned }}</p>
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <UFormField label="Vendidos">
                    <UInput v-model.number="item.qty_sold_input" type="number" step="1" min="0" :max="item.qty_assigned" class="w-full" :disabled="selected.status_consignment === 'liquidada'" />
                  </UFormField>
                  <UFormField label="Devueltos">
                    <UInput v-model.number="item.qty_returned_input" type="number" step="1" min="0" class="w-full" :disabled="selected.status_consignment === 'liquidada'" />
                  </UFormField>
                </div>
                <div class="flex justify-between text-xs text-slate-500">
                  <span>Precio: {{ fmt(parseFloat(item.price_consignment)) }}/u</span>
                  <span class="font-semibold text-green-600">Subtotal vendido: {{ fmt((parseFloat(item.qty_sold_input) || 0) * parseFloat(item.price_consignment)) }}</span>
                </div>
              </div>
            </div>

            <!-- Resumen de cuenta -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-2 border border-slate-200 dark:border-slate-700">
              <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado de Cuenta</p>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">Total Vendido</span>
                <span class="font-bold text-green-600 font-mono">{{ fmt(accountTotal) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">Pendiente</span>
                <span class="font-bold font-mono" :class="pendingTotal > 0 ? 'text-amber-600' : 'text-slate-400'">{{ fmt(Math.max(0, pendingTotal)) }}</span>
              </div>
            </div>

            <!-- Comprobante de pago de la liquidación -->
            <div v-if="selected.status_consignment !== 'liquidada'" class="border-t border-slate-200 dark:border-slate-700 pt-3 space-y-2">
              <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Comprobante de Pago</p>
              <UFormField label="Referencia / N° de operación">
                <UInput v-model="liqReference" placeholder="Número de operación..." class="w-full" />
              </UFormField>
              <UFormField label="Comprobante (imagen/PDF)" help="Respaldo del pago de la liquidación. Máx 5MB.">
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp,application/pdf"
                  class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-300"
                  @change="onLiqProofChange"
                >
              </UFormField>
              <p v-if="liqProofFile" class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                <UIcon name="i-lucide-paperclip" class="w-3.5 h-3.5" /> {{ liqProofFile.name }}
              </p>
            </div>

            <div v-if="selected.status_consignment === 'liquidada'" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-3 text-center">
              <UIcon name="i-lucide-check-circle" class="w-6 h-6 text-emerald-500 mx-auto mb-1" />
              <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Consignación liquidada</p>
              <p v-if="selected.reference_consignment" class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Ref: {{ selected.reference_consignment }}</p>
              <a v-if="selected.file_consignment" :href="`/${selected.file_consignment}`" target="_blank" class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-700 text-xs font-medium mt-1">
                <UIcon name="i-lucide-receipt" class="w-4 h-4" /> Ver comprobante
              </a>
            </div>
          </template>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="detailModal = false">Cerrar</UButton>
          <UButton
            v-if="selected?.status_consignment !== 'liquidada'"
            color="primary" icon="i-lucide-check"
            :loading="liquidating"
            @click="liquidate"
          >
            Confirmar Liquidación
          </UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>
