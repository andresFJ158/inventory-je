<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()
const apiHeaders = { Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }
const ajaxBase = '/ajax/pos.ajax.php'

const products = ref<any[]>([])
const combos = ref<any[]>([])
const loading = ref(true)

// Slide para editar combo
const slideOpen = ref(false)
const editingCombo = ref<any>(null)
const comboItems = ref<any[]>([])
const loadingItems = ref(false)
const newItem = ref({ id_product: '', qty: 1 })
const savingItem = ref(false)

function decode(s: string) { return s ? decodeURIComponent(s).replace(/\+/g, ' ') : '' }

async function fetchProducts() {
  const d = await $fetch<any>('/api/products?linkTo=status_product&equalTo=1', { headers: apiHeaders }).catch(() => null)
  if (d?.status === 200) products.value = d.results || []
}

async function fetchCombos() {
  loading.value = true
  const d = await $fetch<any>('/api/products?linkTo=is_compound_product&equalTo=1', { headers: apiHeaders }).catch(() => null)
  combos.value = d?.status === 200 ? (d.results || []) : []
  loading.value = false
}

async function openCombo(combo: any) {
  editingCombo.value = combo
  slideOpen.value = true
  loadingItems.value = true
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ getComboItems: 'ok', id_combo: String(combo.id_product) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  comboItems.value = d?.status === 200 ? d.results : []
  loadingItems.value = false
}

async function addItem() {
  if (!newItem.value.id_product || newItem.value.qty <= 0) return
  savingItem.value = true
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ saveComboItem: 'ok', id_combo: String(editingCombo.value.id_product), id_product: newItem.value.id_product, qty: String(newItem.value.qty) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  if (d?.status === 200) {
    newItem.value = { id_product: '', qty: 1 }
    await openCombo(editingCombo.value)
    toast.add({ title: 'Componente agregado', color: 'success' })
  }
  savingItem.value = false
}

async function deleteItem(id: number) {
  await $fetch<any>(ajaxBase, { method: 'POST', body: new URLSearchParams({ deleteComboItem: 'ok', id_combo_item: String(id) }).toString(), headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }).catch(() => null)
  await openCombo(editingCombo.value)
}

onMounted(async () => {
  await Promise.all([fetchProducts(), fetchCombos()])
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Gestiona los productos compuestos (combos). El inventario de cada componente se descuenta al vender el combo.</p>
      </div>
      <UButton icon="i-lucide-refresh-cw" variant="ghost" color="neutral" size="sm" @click="fetchCombos">Actualizar</UButton>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-green-500" />
    </div>

    <div v-else-if="combos.length === 0" class="text-center py-16">
      <UIcon name="i-lucide-package" class="w-12 h-12 mx-auto text-slate-300 mb-4" />
      <h3 class="font-semibold text-slate-600 dark:text-slate-300">Sin combos registrados</h3>
      <p class="text-sm text-slate-400 mt-1">Crea un producto con la opción <strong>is_compound_product = 1</strong> desde el módulo de Productos, luego regresa aquí para agregar sus componentes.</p>
      <UButton to="/productos" class="mt-4" color="primary" icon="i-lucide-box">Ir a Productos</UButton>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <UCard
        v-for="combo in combos" :key="combo.id_product"
        class="cursor-pointer hover:border-purple-400 dark:hover:border-purple-600 transition-colors"
        @click="openCombo(combo)"
      >
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-700 flex items-center justify-center shrink-0">
            <UIcon name="i-lucide-package" class="w-6 h-6 text-purple-500" />
          </div>
          <div class="min-w-0">
            <h3 class="font-bold text-slate-800 dark:text-white truncate">{{ decode(combo.title_product) }}</h3>
            <p class="text-xs text-slate-500">SKU: {{ combo.sku_product }}</p>
          </div>
        </div>
        <div class="mt-3 flex items-center justify-between">
          <UBadge color="secondary" variant="subtle">Combo</UBadge>
          <UButton size="xs" variant="ghost" color="neutral" icon="i-lucide-settings-2">Editar componentes</UButton>
        </div>
      </UCard>
    </div>

    <!-- Slideover: componentes del combo -->
    <USlideover v-model:open="slideOpen" :title="editingCombo ? `Componentes: ${decode(editingCombo.title_product)}` : 'Combo'">
      <template #body>
        <div class="space-y-4 p-1">
          <div v-if="loadingItems" class="flex justify-center py-8">
            <UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin text-green-500" />
          </div>
          <template v-else>
            <!-- Lista de componentes -->
            <div class="space-y-2">
              <div v-for="item in comboItems" :key="item.id_combo_item"
                class="flex items-center justify-between bg-slate-50 dark:bg-slate-800 rounded-lg px-3 py-2.5"
              >
                <div>
                  <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ decode(item.title_product) }}</p>
                  <p class="text-xs text-slate-500">{{ item.sku_product }} · {{ item.unit_product }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <UBadge color="neutral" variant="soft">× {{ item.qty_combo_item }}</UBadge>
                  <UButton icon="i-lucide-trash" color="error" variant="ghost" size="xs" @click="deleteItem(item.id_combo_item)" />
                </div>
              </div>
              <div v-if="comboItems.length === 0" class="text-center py-4 text-slate-400 text-sm">Sin componentes aún</div>
            </div>

            <!-- Agregar componente -->
            <div class="border-t border-slate-200 dark:border-slate-700 pt-4 space-y-3">
              <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Agregar Componente</p>
              <USelect
                v-model="newItem.id_product"
                :items="products.filter(p => p.is_compound_product != 1).map(p => ({ value: String(p.id_product), label: `${decode(p.title_product)} (${p.sku_product})` }))"
                placeholder="Seleccionar producto..."
                class="w-full"
              />
              <div class="flex gap-2 items-center">
                <UFormField label="Cantidad" class="flex-1">
                  <UInput v-model.number="newItem.qty" type="number" step="0.5" min="0.1" class="w-full" />
                </UFormField>
                <UButton class="mt-5" color="primary" icon="i-lucide-plus" :loading="savingItem" @click="addItem">Agregar</UButton>
              </div>
            </div>

            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-3">
              <p class="text-xs text-amber-700 dark:text-amber-300">
                Al vender este combo, el sistema descontará automáticamente el inventario de cada componente en las cantidades definidas aquí.
              </p>
            </div>
          </template>
        </div>
      </template>
    </USlideover>
  </div>
</template>
