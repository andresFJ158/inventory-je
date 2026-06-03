<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()
const ajaxBase = '/ajax/pos.ajax.php'

const packagings = ref<any[]>([])
const loading = ref(true)
const search = ref('')

const slideOpen = ref(false)
const editing = ref<any>(null)
const saving = ref(false)

const form = ref({
  id_packaging: 0,
  name_packaging: '',
  price_packaging: 0,
  unit_packaging: 'unidades',
  status_packaging: 1
})

const unitOptions = [
  { value: 'unidades', label: 'Unidades (u.)' },
  { value: 'cajas', label: 'Cajas (caj.)' },
  { value: 'bolsas', label: 'Bolsas (bols.)' },
  { value: 'paquetes', label: 'Paquetes (paq.)' },
  { value: 'metros', label: 'Metros (m.)' }
]

function decode(s: string) { return s ? decodeURIComponent(s).replace(/\+/g, ' ') : '' }

const filtered = computed(() => {
  let list = packagings.value
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(p => (p.name_packaging || '').toLowerCase().includes(q) || (p.unit_packaging || '').toLowerCase().includes(q))
  }
  return list
})

async function fetchPackagings() {
  loading.value = true
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ getPackagings: 'ok' }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  packagings.value = d?.status === 200 ? d.results : []
  loading.value = false
}

function openCreate() {
  editing.value = null
  form.value = { id_packaging: 0, name_packaging: '', price_packaging: 0, unit_packaging: 'unidades', status_packaging: 1 }
  slideOpen.value = true
}

function openEdit(p: any) {
  editing.value = p
  form.value = {
    id_packaging: p.id_packaging,
    name_packaging: decode(p.name_packaging),
    price_packaging: parseFloat(p.price_packaging || 0),
    unit_packaging: p.unit_packaging || 'unidades',
    status_packaging: parseInt(p.status_packaging ?? 1)
  }
  slideOpen.value = true
}

async function savePackaging() {
  if (!form.value.name_packaging.trim()) { toast.add({ title: 'El nombre es requerido', color: 'error' }); return }
  if (form.value.price_packaging < 0) { toast.add({ title: 'El precio no puede ser negativo', color: 'error' }); return }
  
  saving.value = true
  const body = new URLSearchParams({
    savePackaging: 'ok',
    id_packaging: String(form.value.id_packaging),
    name_packaging: form.value.name_packaging,
    price_packaging: String(form.value.price_packaging),
    unit_packaging: form.value.unit_packaging,
    status_packaging: String(form.value.status_packaging)
  })
  
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST', 
    body: body.toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  
  const d = typeof res === 'string' ? JSON.parse(res) : res
  if (d?.status === 200) {
    toast.add({ title: form.value.id_packaging ? 'Empaque actualizado' : 'Empaque creado', color: 'success' })
    slideOpen.value = false
    await fetchPackagings()
  } else {
    toast.add({ title: 'Error al guardar', color: 'error' })
  }
  saving.value = false
}

async function deletePackaging(p: any) {
  if (!confirm(`¿Desactivar empaque "${decode(p.name_packaging)}"?`)) return
  await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ deletePackaging: 'ok', id_packaging: String(p.id_packaging) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  toast.add({ title: 'Empaque desactivado', color: 'success' })
  await fetchPackagings()
}

onMounted(fetchPackagings)
</script>

<template>
  <div class="space-y-4">
    <!-- Header + filtros -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm animate-fade-in">
      <div>
        <h1 class="text-xl font-bold flex items-center gap-2">
          <UIcon name="i-lucide-package-open" class="text-blue-500 w-5 h-5" />
          Catálogo de Empaques y Envases
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
          Predefine bolsas, cajas y otros materiales de embalaje con sus respectivos precios unitarios para cargarlos ágilmente a los gastos de despacho.
        </p>
      </div>
      <div class="flex gap-2 w-full sm:w-auto">
        <UInput v-model="search" icon="i-lucide-search" placeholder="Buscar..." size="sm" class="flex-1 sm:w-52" />
        <UButton color="primary" icon="i-lucide-plus" size="sm" class="bg-blue-600 hover:bg-blue-700 text-white" @click="openCreate">Nuevo Empaque</UButton>
      </div>
    </div>

    <!-- KPIs rápidos -->
    <div class="grid grid-cols-3 gap-3">
      <div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-slate-800 dark:text-white">{{ packagings.length }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Empaques Definidos</p>
      </div>
      <div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-blue-600">
          Bs.{{ packagings.length > 0 ? (packagings.reduce((a, b) => a + parseFloat(b.price_packaging), 0) / packagings.length).toFixed(2) : '0.00' }}
        </p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Precio Promedio</p>
      </div>
      <div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-emerald-600">{{ packagings.filter(p => parseFloat(p.price_packaging) === 0).length }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Empaques Gratuitos (Bs.0)</p>
      </div>
    </div>

    <!-- Lista -->
    <div v-if="loading" class="flex justify-center py-16">
      <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-blue-500" />
    </div>

    <div v-else-if="filtered.length === 0" class="text-center py-16 bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-2xl">
      <UIcon name="i-lucide-box" class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-3 animate-pulse" />
      <p class="text-slate-500 dark:text-slate-400 font-semibold">No hay empaques o envases registrados</p>
      <UButton color="primary" icon="i-lucide-plus" class="mt-4 bg-blue-600 text-white" @click="openCreate">Agregar primer empaque</UButton>
    </div>

    <!-- Cards de empaques -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="p in filtered" :key="p.id_packaging"
        class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-blue-400/40 dark:hover:border-blue-500/40 transition-all group flex flex-col justify-between"
      >
        <div>
          <div class="flex items-start justify-between gap-2 mb-3">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center shrink-0">
                <UIcon name="i-lucide-package-open" class="w-5 h-5 text-blue-500" />
              </div>
              <div class="min-w-0">
                <h3 class="font-bold text-slate-800 dark:text-white truncate text-sm capitalize">{{ decode(p.name_packaging) }}</h3>
                <span class="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 px-2 py-0.5 rounded-full font-semibold uppercase">
                  {{ p.unit_packaging || 'unidades' }}
                </span>
              </div>
            </div>
          </div>

          <div class="mt-2 bg-slate-50 dark:bg-slate-900/60 rounded-lg p-2.5 flex justify-between items-center border border-slate-100 dark:border-slate-800/40">
            <span class="text-xs text-slate-500">Precio Unitario:</span>
            <span class="font-mono font-bold text-blue-600 dark:text-blue-400 text-sm">Bs.{{ parseFloat(p.price_packaging).toFixed(2) }}</span>
          </div>
        </div>

        <div class="flex gap-2 mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
          <UButton size="xs" variant="ghost" color="neutral" icon="i-lucide-edit" class="flex-1 justify-center hover:bg-slate-100 dark:hover:bg-slate-800" @click="openEdit(p)">Editar</UButton>
          <UButton size="xs" variant="ghost" color="error" icon="i-lucide-trash" class="hover:bg-red-50 dark:hover:bg-red-950/20" @click="deletePackaging(p)" />
        </div>
      </div>
    </div>

    <!-- Slideover: Crear / Editar -->
    <USlideover v-model:open="slideOpen" :title="editing ? 'Editar Empaque' : 'Nuevo Empaque'">
      <template #body>
        <div class="space-y-4 p-1">
          <UFormField label="Nombre del Empaque / Envase *">
            <UInput v-model="form.name_packaging" placeholder="Ej: Bolsa Mediana de Plástico, Caja de Cartón Grande" class="w-full" />
          </UFormField>
          <UFormField label="Precio Unitario (Bs.) *">
            <UInput v-model.number="form.price_packaging" type="number" step="0.05" min="0" placeholder="0.00" class="w-full font-mono" />
          </UFormField>
          <UFormField label="Unidad de Medida">
            <USelect v-model="form.unit_packaging" :items="unitOptions" class="w-full capitalize" />
          </UFormField>
          <div class="flex items-center gap-3 pt-2">
            <USwitch v-model="form.status_packaging" :model-value="form.status_packaging === 1" @update:model-value="(v: boolean) => form.status_packaging = v ? 1 : 0" />
            <span class="text-sm text-slate-600 dark:text-slate-400 font-medium">{{ form.status_packaging ? 'Activo' : 'Inactivo' }}</span>
          </div>

          <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-xs text-blue-700 dark:text-blue-300">
            <UIcon name="i-lucide-info" class="w-3.5 h-3.5 inline mr-1 text-blue-500" />
            Al predefinir estos empaques, los despachadores y administradores podrán agregarlos a los gastos de cualquier orden con solo seleccionarlos del catálogo e ingresar la cantidad utilizada.
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="slideOpen = false">Cancelar</UButton>
          <UButton color="primary" :loading="saving" class="bg-blue-600 text-white" icon="i-lucide-check" @click="savePackaging">Guardar</UButton>
        </div>
      </template>
    </USlideover>
  </div>
</template>
