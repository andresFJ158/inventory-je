<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { decodeText } from '~/utils/format'

definePageMeta({
  middleware: ['auth', 'permission'],
  permission: 'productos'
})

const api = useApi()
const auth = useAuthStore()
const toast = useToast()

const products = ref<any[]>([])
const combos = ref<any[]>([])
const loading = ref(true)

const slideOpen = ref(false)
const editingCombo = ref<any>(null)
const comboItems = ref<any[]>([])
const loadingItems = ref(false)
const newItem = ref({ id_product: '', qty: 1 })
const savingItem = ref(false)
const savingMode = ref(false)
const deletingId = ref<number | null>(null)

async function fetchProducts() {
  const d = await api.rest<any>('/api/products?linkTo=status_product&equalTo=1')
  if (d?.status === 200) products.value = d.results || []
}

async function fetchCombos() {
  loading.value = true
  const d = await api.rest<any>('/api/products?linkTo=is_compound_product&equalTo=1')
  combos.value = d?.status === 200 ? (d.results || []) : []
  if (!d) {
    toast.add({ title: 'Error', description: 'No se pudieron cargar los combos', color: 'error' })
  }
  loading.value = false
}

async function openCombo(combo: any) {
  editingCombo.value = combo
  slideOpen.value = true
  loadingItems.value = true
  const d = await api.ajax({ getComboItems: 'ok', id_combo: combo.id_product })
  comboItems.value = d?.status === 200 ? (d.results || []) : []
  if (d?.status !== 200) {
    toast.add({ title: 'Error', description: d?.message || 'No se pudieron cargar los componentes', color: 'error' })
  }
  loadingItems.value = false
}

async function addItem() {
  if (!newItem.value.id_product || newItem.value.qty <= 0) return
  savingItem.value = true
  const d = await api.ajax({
    saveComboItem: 'ok',
    id_combo: editingCombo.value.id_product,
    id_product: newItem.value.id_product,
    qty: newItem.value.qty
  })
  if (d?.status === 200) {
    newItem.value = { id_product: '', qty: 1 }
    await openCombo(editingCombo.value)
    toast.add({ title: 'Componente agregado', color: 'success' })
  } else {
    toast.add({ title: 'Error', description: d?.message || 'No se pudo agregar', color: 'error' })
  }
  savingItem.value = false
}

async function deleteItem(id: number) {
  if (!confirm('¿Eliminar este componente del combo?')) return
  deletingId.value = id
  const d = await api.ajax({ deleteComboItem: 'ok', id_combo_item: id })
  if (d?.status === 200) {
    await openCombo(editingCombo.value)
    toast.add({ title: 'Componente eliminado', color: 'success' })
  } else {
    toast.add({ title: 'Error', description: d?.message || 'No se pudo eliminar', color: 'error' })
  }
  deletingId.value = null
}

async function saveComboMode() {
  if (!editingCombo.value || !auth.token) return
  savingMode.value = true
  const body = new URLSearchParams()
  body.append('combo_price_mode', editingCombo.value.combo_price_mode || 'descuento')
  const d = await api.rest<any>(`/api/products?id=${editingCombo.value.id_product}&nameId=id_product&token=${auth.token}&table=admins&suffix=admin`, {
    method: 'PUT',
    body: body.toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  })
  savingMode.value = false
  if (d?.status === 200) {
    toast.add({ title: 'Modo de precio actualizado', color: 'success' })
  } else {
    toast.add({ title: 'Error', description: 'No se pudo guardar el modo de precio', color: 'error' })
  }
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
            <UIcon name="i-lucide-layers" class="w-6 h-6 text-purple-600" />
          </div>
          <div class="min-w-0">
            <p class="font-semibold truncate">{{ decodeText(combo.title_product) }}</p>
            <p class="text-xs text-slate-500">{{ combo.combo_price_mode === 'fijo' ? 'Precio fijo' : 'Descuento %' }}</p>
          </div>
        </div>
      </UCard>
    </div>

    <USlideover v-model:open="slideOpen" :title="editingCombo ? decodeText(editingCombo.title_product) : 'Combo'">
      <template #body>
        <div v-if="editingCombo" class="space-y-6 p-4">
          <UFormField label="Modo de precio">
            <USelect
              v-model="editingCombo.combo_price_mode"
              :items="[{ label: 'Descuento %', value: 'descuento' }, { label: 'Precio fijo', value: 'fijo' }]"
            />
          </UFormField>
          <UButton :loading="savingMode" @click="saveComboMode">Guardar modo</UButton>

          <hr class="border-slate-200 dark:border-slate-700">

          <h4 class="font-semibold">Componentes</h4>
          <div v-if="loadingItems" class="flex justify-center py-8">
            <UIcon name="i-lucide-loader-2" class="animate-spin" />
          </div>
          <ul v-else class="space-y-2">
            <li v-for="item in comboItems" :key="item.id_combo_item" class="flex justify-between items-center text-sm border-b pb-2">
              <span>{{ decodeText(item.title_product) }} × {{ item.qty_ci }}</span>
              <UButton
                size="xs" color="error" variant="ghost" icon="i-lucide-trash"
                :loading="deletingId === item.id_combo_item"
                @click.stop="deleteItem(item.id_combo_item)"
              />
            </li>
          </ul>

          <div class="flex gap-2 items-end">
            <UFormField label="Producto" class="flex-1">
              <USelect
                v-model="newItem.id_product"
                :items="products.map(p => ({ label: decodeText(p.title_product), value: String(p.id_product) }))"
                placeholder="Seleccionar..."
              />
            </UFormField>
            <UFormField label="Cant.">
              <UInput v-model.number="newItem.qty" type="number" min="1" class="w-20" />
            </UFormField>
            <UButton :loading="savingItem" icon="i-lucide-plus" @click="addItem">Agregar</UButton>
          </div>
        </div>
      </template>
    </USlideover>
  </div>
</template>
