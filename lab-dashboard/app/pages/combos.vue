<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { decodeText } from '~/utils/format'
import { getImageUrl } from '~/utils/image'

definePageMeta({
  middleware: ['auth', 'permission'],
  permission: 'productos'
})

const api = useApi()
const auth = useAuthStore()
const toast = useToast()

// ─── Data ───────────────────────────────────────────────────────────────────
const combos    = ref<any[]>([])
const products  = ref<any[]>([])
const categories = ref<any[]>([])
const loading   = ref(true)

// ─── Modal state ─────────────────────────────────────────────────────────────
const showModal  = ref(false)
const isEditing  = ref(false)
const saving     = ref(false)
const deletingId = ref<number | null>(null)
const showDeleteModal    = ref(false)
const pendingDeleteId    = ref<number | null>(null)
const pendingDeleteName  = ref('')
const formErrors = ref({ title: '', items: '' })

const emptyForm = () => ({
  id_product: null as number | null,
  title: '',
  sku: '',
  id_category: '0' as string | number,
  discount: 0
})

const form      = ref(emptyForm())
const formItems = ref<{ id_product: number; title: string; qty: number; price: number }[]>([])

// Add-item row
const addRow = ref({ id_product: '' as string | number, qty: 1, price: 0 })

// Image
const imageFile      = ref<File | null>(null)
const imagePreview   = ref<string>('')
const uploadingImage = ref(false)

function onImagePick(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  if (imagePreview.value?.startsWith('blob:')) URL.revokeObjectURL(imagePreview.value)
  imageFile.value    = file
  imagePreview.value = URL.createObjectURL(file)
}

function revokePreview() {
  if (imagePreview.value?.startsWith('blob:')) URL.revokeObjectURL(imagePreview.value)
  imagePreview.value = ''
}

async function uploadComboImage(comboId: number) {
  if (!imageFile.value) return
  const fd = new FormData()
  fd.append('uploadComboImage', 'ok')
  fd.append('id_combo', String(comboId))
  fd.append('comboImage', imageFile.value)
  const res = await api.ajaxForm(fd)
  if (res?.status !== 200) {
    toast.add({ title: 'Error al subir imagen', description: res?.message, color: 'warning' })
  }
}

function clampItemQty(item: { qty: number }) {
  if (!item.qty || item.qty < 1) item.qty = 1
}

// ─── Computed ─────────────────────────────────────────────────────────────────
const totalPrice = computed(() =>
  formItems.value.reduce((s, i) => s + i.qty * i.price, 0)
)

const productOptions = computed(() =>
  products.value
    .filter(p => !formItems.value.some(fi => fi.id_product === p.id_product))
    .map(p => ({ label: decodeText(p.title_product), value: String(p.id_product) }))
)

const categoryOptions = computed(() => [
  { label: 'Sin categoría', value: '0' },
  ...categories.value.map(c => ({
    label: decodeText(c.name_category || c.title_category || ''),
    value: String(c.id_category)
  }))
])

// ─── API calls ────────────────────────────────────────────────────────────────
async function fetchAll() {
  loading.value = true
  const [combosRes, productsRes, catsRes] = await Promise.all([
    api.ajax({ getCombos: 'ok', id_office: auth.officeId ?? 0 }),
    api.rest<any>('/api/products?linkTo=status_product&equalTo=1'),
    api.rest<any>('/api/categories?linkTo=status_category&equalTo=1')
  ])
  combos.value   = combosRes?.results  ?? []
  products.value = productsRes?.results ?? productsRes ?? []
  categories.value = catsRes?.results  ?? catsRes ?? []
  loading.value  = false
}

// ─── Open create modal ────────────────────────────────────────────────────────
function openCreate() {
  isEditing.value    = false
  form.value         = emptyForm()
  formItems.value    = []
  addRow.value       = { id_product: '', qty: 1, price: 0 }
  revokePreview()
  imageFile.value      = null
  uploadingImage.value = false
  formErrors.value     = { title: '', items: '' }
  showModal.value    = true
}

// ─── Open edit modal ──────────────────────────────────────────────────────────
function openEdit(combo: any) {
  isEditing.value     = true
  form.value          = {
    id_product: combo.id_product,
    title:      decodeText(combo.title_product),
    sku:        combo.sku_product || '',
    id_category: combo.id_category_product ? String(combo.id_category_product) : '0',
    discount:   parseFloat(combo.discount_product ?? 0)
  }
  formItems.value = (combo.items || []).map((i: any) => ({
    id_product: i.id_product_ci,
    title:      decodeText(i.title_product),
    qty:        parseFloat(i.qty_ci),
    price:      parseFloat(i.price_ci)
  }))
  addRow.value       = { id_product: '', qty: 1, price: 0 }
  revokePreview()
  imageFile.value      = null
  imagePreview.value   = combo.img_product ? getImageUrl(combo.img_product) : ''
  uploadingImage.value = false
  formErrors.value     = { title: '', items: '' }
  showModal.value    = true
}

// ─── Add item to form list ────────────────────────────────────────────────────
function addItemToForm() {
  const pid = parseInt(String(addRow.value.id_product))
  if (!pid) { toast.add({ title: 'Selecciona un producto', color: 'warning' }); return }
  if (addRow.value.qty <= 0) { toast.add({ title: 'Cantidad inválida', color: 'warning' }); return }
  if (addRow.value.price < 0) { toast.add({ title: 'Precio inválido', color: 'warning' }); return }
  const p = products.value.find(x => x.id_product === pid)
  if (!p) return
  formItems.value.push({
    id_product: pid,
    title:      decodeText(p.title_product),
    qty:        addRow.value.qty,
    price:      addRow.value.price
  })
  addRow.value = { id_product: '', qty: 1, price: 0 }

}

function removeItem(index: number) {
  formItems.value.splice(index, 1)
}

// ─── Save combo ───────────────────────────────────────────────────────────────
async function saveCombo() {
  formErrors.value = { title: '', items: '' }
  let hasError = false
  if (!form.value.title.trim()) {
    formErrors.value.title = 'El nombre del combo es requerido'
    hasError = true
  }
  if (formItems.value.length < 2) {
    formErrors.value.items = 'Agrega al menos 2 componentes'
    hasError = true
  }
  if (hasError) return

  saving.value = true

  const itemsJson = JSON.stringify(formItems.value.map(i => ({
    id_product: i.id_product,
    qty:        i.qty,
    price:      i.price
  })))

  const payload: Record<string, string | number | boolean> = {
    title:       form.value.title.trim(),
    sku:         form.value.sku.trim(),
    id_category: form.value.id_category ?? 0,
    discount:    form.value.discount,
    items:       itemsJson
  }

  let res
  if (isEditing.value && form.value.id_product) {
    res = await api.ajax({ updateCombo: 'ok', id_combo: form.value.id_product, ...payload })
  } else {
    res = await api.ajax({ createCombo: 'ok', ...payload })
  }

  if (res?.status === 200) {
    const savedId = isEditing.value ? form.value.id_product! : (res.id as number)
    if (imageFile.value && savedId) await uploadComboImage(savedId)
    saving.value = false
    toast.add({ title: isEditing.value ? 'Combo actualizado' : 'Combo creado', color: 'success' })
    revokePreview()
    showModal.value = false
    await fetchAll()
  } else {
    saving.value = false
    toast.add({ title: 'Error', description: res?.message || 'No se pudo guardar', color: 'error' })
  }
}

// ─── Delete combo ─────────────────────────────────────────────────────────────
function confirmDeleteCombo(id: number, name: string) {
  pendingDeleteId.value   = id
  pendingDeleteName.value = name
  showDeleteModal.value   = true
}

async function doDeleteCombo() {
  if (!pendingDeleteId.value) return
  showDeleteModal.value = false
  deletingId.value = pendingDeleteId.value
  const res = await api.ajax({ deleteCombo: 'ok', id_combo: pendingDeleteId.value })
  deletingId.value      = null
  pendingDeleteId.value = null
  if (res?.status === 200) {
    toast.add({ title: 'Combo eliminado', color: 'success' })
    await fetchAll()
  } else {
    toast.add({ title: 'Error', description: res?.message || 'No se pudo eliminar', color: 'error' })
  }
}

// ─── Stock badge helper ────────────────────────────────────────────────────────
function stockColor(n: number) {
  if (n <= 0) return 'error'
  if (n <= 5) return 'warning'
  return 'success'
}

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-lg font-bold text-slate-800">Combos</h2>
        <p class="text-sm text-slate-500 mt-0.5">
          Productos compuestos — el stock de cada componente se descuenta al vender.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <UButton icon="i-lucide-refresh-cw" variant="ghost" color="neutral" size="sm" :loading="loading" @click="fetchAll">
          Actualizar
        </UButton>
        <UButton icon="i-lucide-plus" color="primary" size="sm" @click="openCreate">
          Nuevo Combo
        </UButton>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-24">
      <UIcon name="i-lucide-loader-2" class="w-10 h-10 animate-spin text-emerald-500" />
    </div>

    <!-- Empty -->
    <div v-else-if="combos.length === 0" class="text-center py-20">
      <div class="w-20 h-20 rounded-2xl bg-purple-50 border border-purple-200 flex items-center justify-center mx-auto mb-4">
        <UIcon name="i-lucide-layers" class="w-10 h-10 text-purple-400" />
      </div>
      <h3 class="font-semibold text-slate-700">Sin combos registrados</h3>
      <p class="text-sm text-slate-400 mt-1 max-w-xs mx-auto">
        Crea tu primer combo para empezar a vender productos compuestos.
      </p>
      <UButton class="mt-5" color="primary" icon="i-lucide-plus" @click="openCreate">Crear Combo</UButton>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div
        v-for="combo in combos"
        :key="combo.id_product"
        class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex flex-col gap-4 hover:shadow-md hover:border-purple-300 transition-all duration-200"
      >
        <!-- Combo header -->
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-12 h-12 rounded-xl overflow-hidden bg-purple-50 border border-purple-200 flex items-center justify-center shrink-0">
              <img
                v-if="combo.img_product"
                :src="getImageUrl(combo.img_product)"
                class="w-full h-full object-cover"
                :alt="decodeText(combo.title_product)"
                @error="(e) => (e.target as HTMLImageElement).style.display='none'"
              />
              <UIcon v-else name="i-lucide-layers" class="w-6 h-6 text-purple-600" />
            </div>
            <div class="min-w-0">
              <p class="font-semibold text-slate-800 truncate">{{ decodeText(combo.title_product) }}</p>
              <p v-if="combo.sku_product" class="text-xs text-slate-400 font-mono">{{ combo.sku_product }}</p>
            </div>
          </div>
          <div class="flex items-center gap-1 shrink-0">
            <UButton
              size="xs" variant="ghost" color="neutral" icon="i-lucide-pencil"
              @click="openEdit(combo)"
            />
            <UButton
              size="xs" variant="ghost" color="error" icon="i-lucide-trash-2"
              :loading="deletingId === combo.id_product"
              @click="confirmDeleteCombo(combo.id_product, decodeText(combo.title_product))"
            />
          </div>
        </div>

        <!-- Components list -->
        <div class="space-y-1.5">
          <template v-if="combo.items && combo.items.length">
            <div
              v-for="item in combo.items.slice(0, 4)"
              :key="item.id_combo_item"
              class="flex items-center justify-between text-xs"
            >
              <span class="text-slate-600 truncate max-w-[60%]">
                {{ decodeText(item.title_product) }} × {{ item.qty_ci }}
              </span>
              <span class="text-slate-500 font-mono">Bs. {{ Number(item.price_ci).toFixed(2) }}</span>
            </div>
            <p v-if="combo.items.length > 4" class="text-xs text-slate-400 italic">
              +{{ combo.items.length - 4 }} más...
            </p>
          </template>
          <p v-else class="text-xs text-slate-400 italic">Sin componentes</p>
        </div>

        <!-- Footer: total + stock -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100 mt-auto">
          <div>
            <p class="text-xs text-slate-400">Precio total</p>
            <p class="text-base font-bold text-emerald-600">Bs. {{ Number(combo.total_price).toFixed(2) }}</p>
          </div>
          <UBadge
            :color="stockColor(combo.stock_combo)"
            variant="soft"
            size="sm"
            :label="`Stock: ${combo.stock_combo} combos`"
          />
        </div>
      </div>
    </div>

    <!-- ── Delete Confirmation Modal ────────────────────────────────────────── -->
    <UModal
      v-model:open="showDeleteModal"
      title="Eliminar combo"
      :description="`¿Estás seguro de que deseas eliminar el combo &quot;${pendingDeleteName}&quot;? Esta acción no se puede deshacer.`"
    >
      <template #footer>
        <div class="flex justify-end gap-2 w-full">
          <UButton variant="ghost" color="neutral" @click="showDeleteModal = false">Cancelar</UButton>
          <UButton color="error" icon="i-lucide-trash-2" @click="doDeleteCombo">Eliminar</UButton>
        </div>
      </template>
    </UModal>

    <!-- ── Create / Edit Modal ─────────────────────────────────────────────── -->
    <UModal
      v-model:open="showModal"
      :title="isEditing ? 'Editar Combo' : 'Nuevo Combo'"
      :description="isEditing ? 'Modifica los componentes y precios del combo.' : 'Define los productos que componen este combo y sus precios individuales.'"
      :ui="{ content: 'max-w-2xl' }"
    >
      <template #body>
        <div class="space-y-6 p-1">

          <!-- Basic fields -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Nombre del combo *" class="col-span-2 sm:col-span-1" :error="formErrors.title">
              <UInput
                v-model="form.title"
                placeholder="Ej: Combo Desayuno"
                autofocus
                :color="formErrors.title ? 'error' : undefined"
              />
            </UFormField>
            <UFormField label="SKU / Código">
              <UInput v-model="form.sku" placeholder="Ej: COMBO-001" />
            </UFormField>
            <UFormField label="Categoría">
              <USelect
                v-model="form.id_category"
                :items="categoryOptions"
                placeholder="Seleccionar..."
              />
            </UFormField>
            <UFormField label="Descuento %">
              <UInput v-model.number="form.discount" type="number" min="0" max="100" placeholder="0" />
            </UFormField>
          </div>

          <!-- Image -->
          <div>
            <p class="text-xs font-medium text-slate-500 mb-2">Imagen del combo</p>
            <div class="flex items-center gap-4">
              <div class="w-20 h-20 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0">
                <img
                  v-if="imagePreview"
                  :src="imagePreview"
                  class="w-full h-full object-cover"
                  alt="Preview"
                />
                <UIcon v-else name="i-lucide-image" class="w-8 h-8 text-slate-300" />
              </div>
              <div class="flex-1">
                <label :class="saving ? 'pointer-events-none opacity-50' : 'cursor-pointer'">
                  <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    :disabled="saving"
                    @change="onImagePick"
                  />
                  <UButton
                    as="span"
                    variant="soft"
                    color="neutral"
                    size="sm"
                    icon="i-lucide-upload"
                    :loading="saving && !!imageFile"
                  >
                    {{ imagePreview ? 'Cambiar imagen' : 'Subir imagen' }}
                  </UButton>
                </label>
                <p class="text-xs text-slate-400 mt-1">JPG, PNG o WEBP · Máx 5MB</p>
              </div>
            </div>
          </div>

          <!-- Items table -->
          <div>
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-semibold text-slate-700">
                Componentes
                <UBadge v-if="formItems.length > 0" :label="String(formItems.length)" color="primary" size="xs" class="ml-2" />
              </h4>
              <p v-if="formErrors.items" class="text-xs text-red-500 font-medium">{{ formErrors.items }}</p>
              <p v-else-if="formItems.length < 2" class="text-xs text-amber-500">Mínimo 2 productos</p>
            </div>

            <!-- Existing items -->
            <div v-if="formItems.length > 0" class="rounded-xl border border-slate-200 overflow-x-auto mb-3">
              <table class="w-full text-sm min-w-[480px]">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="text-left text-xs font-medium text-slate-500 px-4 py-2">Producto</th>
                    <th class="text-center text-xs font-medium text-slate-500 px-2 py-2 w-20 shrink-0">Cant.</th>
                    <th class="text-center text-xs font-medium text-slate-500 px-2 py-2 w-28 shrink-0">Precio Bs.</th>
                    <th class="text-center text-xs font-medium text-slate-500 px-2 py-2 w-24 shrink-0">Subtotal</th>
                    <th class="w-10 py-2 shrink-0" />
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(item, idx) in formItems" :key="idx" class="group">
                    <td class="px-4 py-2.5 text-slate-700 font-medium truncate max-w-[160px]">
                      {{ item.title }}
                    </td>
                    <td class="px-2 py-2.5">
                      <UInput
                        v-model.number="item.qty"
                        type="number"
                        min="1"
                        size="sm"
                        class="w-20 text-center"
                        @change="clampItemQty(item)"
                      />
                    </td>
                    <td class="px-2 py-2.5">
                      <UInput
                        v-model.number="item.price"
                        type="number"
                        min="0"
                        step="0.01"
                        size="sm"
                        class="w-28 text-center"
                      />
                    </td>
                    <td class="px-2 py-2.5 text-center font-mono text-emerald-600 text-sm whitespace-nowrap">
                      {{ (item.qty * item.price).toFixed(2) }}
                    </td>
                    <td class="px-2 py-2.5 text-center">
                      <UButton
                        size="xs" variant="ghost" color="error" icon="i-lucide-x"
                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                        @click="removeItem(idx)"
                      />
                    </td>
                  </tr>
                </tbody>
                <tfoot class="bg-emerald-50">
                  <tr>
                    <td colspan="3" class="px-4 py-2.5 text-sm font-semibold text-slate-600 text-right">
                      Total del combo:
                    </td>
                    <td class="px-2 py-2.5 text-center font-bold font-mono text-emerald-700">
                      {{ totalPrice.toFixed(2) }}
                    </td>
                    <td />
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Add product row -->
            <div class="bg-slate-50 rounded-xl border border-dashed border-slate-300 p-4">
              <p class="text-xs font-medium text-slate-500 mb-3">Agregar componente</p>
              <div class="flex flex-wrap gap-2 items-end">
                <UFormField label="Producto" class="flex-1 min-w-[160px]">
                  <USelectMenu
                    v-model="addRow.id_product"
                    :items="productOptions"
                    value-key="value"
                    label-key="label"
                    placeholder="Buscar producto..."
                    searchable
                    searchable-placeholder="Buscar..."
                  />
                </UFormField>
                <UFormField label="Cantidad">
                  <UInput
                    v-model.number="addRow.qty"
                    type="number"
                    min="1"
                    class="w-24"
                    placeholder="1"
                  />
                </UFormField>
                <UFormField label="Precio Bs.">
                  <UInput
                    v-model.number="addRow.price"
                    type="number"
                    min="0"
                    step="0.01"
                    class="w-28"
                    placeholder="0.00"
                  />
                </UFormField>
                <UButton
                  icon="i-lucide-plus"
                  color="primary"
                  variant="soft"
                  :disabled="!addRow.id_product"
                  @click="addItemToForm"
                >
                  Agregar
                </UButton>
              </div>
            </div>
          </div>

        </div>
      </template>

      <template #footer>
        <div class="flex items-center justify-between w-full gap-3">
          <div class="text-sm text-slate-500">
            <span v-if="formItems.length > 0">
              {{ formItems.length }} componente{{ formItems.length !== 1 ? 's' : '' }} ·
              <span class="font-semibold text-emerald-600">Total: Bs. {{ totalPrice.toFixed(2) }}</span>
            </span>
          </div>
          <div class="flex gap-2">
            <UButton variant="ghost" color="neutral" @click="showModal = false">Cancelar</UButton>
            <UButton
              color="primary"
              :loading="saving"
              :icon="isEditing ? 'i-lucide-save' : 'i-lucide-check'"
              @click="saveCombo"
            >
              {{ isEditing ? 'Guardar cambios' : 'Crear Combo' }}
            </UButton>
          </div>
        </div>
      </template>
    </UModal>

  </div>
</template>
