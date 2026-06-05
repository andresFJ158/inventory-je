<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()
const ajaxBase = '/ajax/pos.ajax.php'

const suppliers = ref<any[]>([])
const loading = ref(true)
const search = ref('')
const filterType = ref<'todos' | 'productos' | 'materias_primas' | 'ambos'>('todos')

const slideOpen = ref(false)
const editing = ref<any>(null)
const saving = ref(false)

const form = ref({
  id_supplier: 0,
  supplier_name: '',
  supplier_contact: '',
  email_supplier: '',
  ruc_supplier: '',
  type_supplier: 'ambos' as 'productos' | 'materias_primas' | 'ambos',
  status_supplier: 1
})

const typeOptions = [
  { value: 'ambos', label: 'Productos y Materias Primas' },
  { value: 'productos', label: 'Solo Productos POS' },
  { value: 'materias_primas', label: 'Solo Materias Primas / Lab' }
]

const typeColors: Record<string, any> = {
  productos: 'info',
  materias_primas: 'success',
  ambos: 'secondary'
}

const typeLabels: Record<string, string> = {
  productos: 'POS',
  materias_primas: 'Lab',
  ambos: 'Ambos'
}

function decode(s: string) { return s ? decodeURIComponent(s).replace(/\+/g, ' ') : '' }

const filtered = computed(() => {
  let list = suppliers.value
  if (filterType.value !== 'todos') list = list.filter(s => s.type_supplier === filterType.value || s.type_supplier === 'ambos')
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(s => (s.supplier_name || '').toLowerCase().includes(q) || (s.supplier_contact || '').includes(q) || (s.ruc_supplier || '').includes(q))
  }
  return list
})

async function fetchSuppliers() {
  loading.value = true
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ getSuppliers: 'ok', type: 'todos' }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  suppliers.value = d?.status === 200 ? d.results : []
  loading.value = false
}

function openCreate() {
  editing.value = null
  form.value = { id_supplier: 0, supplier_name: '', supplier_contact: '', email_supplier: '', ruc_supplier: '', type_supplier: 'ambos', status_supplier: 1 }
  slideOpen.value = true
}

function openEdit(s: any) {
  editing.value = s
  form.value = {
    id_supplier: s.id_supplier,
    supplier_name: decode(s.supplier_name),
    supplier_contact: s.supplier_contact || '',
    email_supplier: s.email_supplier || '',
    ruc_supplier: s.ruc_supplier || '',
    type_supplier: s.type_supplier || 'ambos',
    status_supplier: parseInt(s.status_supplier ?? 1)
  }
  slideOpen.value = true
}

async function saveSupplier() {
  if (!form.value.supplier_name.trim()) { toast.add({ title: 'El nombre es requerido', color: 'error' }); return }
  saving.value = true
  const body = new URLSearchParams({
    saveSupplier: 'ok',
    id_supplier: String(form.value.id_supplier),
    supplier_name: form.value.supplier_name,
    supplier_contact: form.value.supplier_contact,
    email_supplier: form.value.email_supplier,
    ruc_supplier: form.value.ruc_supplier,
    type_supplier: form.value.type_supplier,
    status_supplier: String(form.value.status_supplier)
  })
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST', body: body.toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  if (d?.status === 200) {
    toast.add({ title: form.value.id_supplier ? 'Proveedor actualizado' : 'Proveedor creado', color: 'success' })
    slideOpen.value = false
    await fetchSuppliers()
  } else {
    toast.add({ title: 'Error al guardar', color: 'error' })
  }
  saving.value = false
}

async function deleteSupplier(s: any) {
  if (!confirm(`¿Desactivar a "${decode(s.supplier_name)}"?`)) return
  await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ deleteSupplier: 'ok', id_supplier: String(s.id_supplier) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  toast.add({ title: 'Proveedor desactivado', color: 'success' })
  await fetchSuppliers()
}

onMounted(() => {
  if (['lab_admin', 'lab_worker', 'lab_calidad'].includes(auth.role || '')) {
    filterType.value = 'materias_primas'
  }
  fetchSuppliers()
})
</script>

<template>
  <div class="space-y-4">

    <!-- Header + filtros -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
      <div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
          Proveedores unificados para el sistema POS y el Laboratorio. Un solo registro para ambos módulos.
        </p>
      </div>
      <div class="flex flex-wrap gap-2 w-full sm:w-auto">
        <UInput v-model="search" icon="i-lucide-search" placeholder="Buscar..." size="sm" class="flex-1 sm:w-52" />
        <USelect
          v-model="filterType"
          :items="[{ value: 'todos', label: 'Todos' }, { value: 'productos', label: 'POS' }, { value: 'materias_primas', label: 'Lab' }, { value: 'ambos', label: 'Ambos' }]"
          size="sm" class="w-32"
        />
        <UButton color="primary" icon="i-lucide-plus" size="sm" @click="openCreate">Nuevo</UButton>
      </div>
    </div>

    <!-- KPIs rápidos -->
    <div class="grid grid-cols-3 gap-3">
      <div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-slate-800 dark:text-white">{{ suppliers.length }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total</p>
      </div>
      <div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-blue-600">{{ suppliers.filter(s => s.type_supplier === 'productos').length }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Solo POS</p>
      </div>
      <div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-green-600">{{ suppliers.filter(s => s.type_supplier === 'materias_primas' || s.type_supplier === 'ambos').length }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Incluyen Lab</p>
      </div>
    </div>

    <!-- Lista -->
    <div v-if="loading" class="flex justify-center py-16">
      <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-green-500" />
    </div>

    <div v-else-if="filtered.length === 0" class="text-center py-16">
      <UIcon name="i-lucide-truck" class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-3" />
      <p class="text-slate-500 dark:text-slate-400">No hay proveedores registrados</p>
      <UButton color="primary" icon="i-lucide-plus" class="mt-4" @click="openCreate">Agregar primer proveedor</UButton>
    </div>

    <!-- Cards de proveedores -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="s in filtered" :key="s.id_supplier"
        class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition-all"
      >
        <div class="flex items-start justify-between gap-2 mb-3">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
              <UIcon name="i-lucide-building-2" class="w-5 h-5 text-slate-500" />
            </div>
            <div class="min-w-0">
              <h3 class="font-bold text-slate-800 dark:text-white truncate text-sm">{{ decode(s.supplier_name) }}</h3>
              <p v-if="s.ruc_supplier" class="text-xs text-slate-400 font-mono">RUC: {{ s.ruc_supplier }}</p>
            </div>
          </div>
          <UBadge :color="typeColors[s.type_supplier] || 'neutral'" variant="subtle" size="xs" class="shrink-0">
            {{ typeLabels[s.type_supplier] || s.type_supplier }}
          </UBadge>
        </div>

        <div class="space-y-1.5 text-xs text-slate-500 dark:text-slate-400">
          <div v-if="s.supplier_contact" class="flex items-center gap-1.5">
            <UIcon name="i-lucide-phone" class="w-3.5 h-3.5 shrink-0" />
            <span>{{ s.supplier_contact }}</span>
          </div>
          <div v-if="s.email_supplier" class="flex items-center gap-1.5">
            <UIcon name="i-lucide-mail" class="w-3.5 h-3.5 shrink-0" />
            <span class="truncate">{{ s.email_supplier }}</span>
          </div>
        </div>

        <div class="flex gap-2 mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
          <UButton size="xs" variant="ghost" color="neutral" icon="i-lucide-edit" class="flex-1 justify-center" @click="openEdit(s)">Editar</UButton>
          <UButton size="xs" variant="ghost" color="error" icon="i-lucide-trash" @click="deleteSupplier(s)" />
        </div>
      </div>
    </div>

    <!-- Slideover: Crear / Editar -->
    <USlideover v-model:open="slideOpen" :title="editing ? 'Editar Proveedor' : 'Nuevo Proveedor'">
      <template #body>
        <div class="space-y-4 p-1">
          <UFormField label="Nombre del Proveedor *">
            <UInput v-model="form.supplier_name" placeholder="Ej: J.E Bolivia SRL" class="w-full" />
          </UFormField>
          <UFormField label="RUC / NIT">
            <UInput v-model="form.ruc_supplier" placeholder="Número de registro tributario" class="w-full" />
          </UFormField>
          <UFormField label="Teléfono / WhatsApp">
            <UInput v-model="form.supplier_contact" placeholder="79000000" class="w-full" />
          </UFormField>
          <UFormField label="Correo Electrónico">
            <UInput v-model="form.email_supplier" type="email" placeholder="proveedor@empresa.com" class="w-full" />
          </UFormField>
          <UFormField label="Tipo de Proveedor">
            <USelect v-model="form.type_supplier" :items="typeOptions" class="w-full" />
          </UFormField>
          <div class="flex items-center gap-3 pt-2">
            <USwitch :model-value="form.status_supplier === 1" @update:model-value="(v: boolean) => form.status_supplier = v ? 1 : 0" />
            <span class="text-sm text-slate-600 dark:text-slate-400">{{ form.status_supplier ? 'Activo' : 'Inactivo' }}</span>
          </div>

          <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-xs text-blue-700 dark:text-blue-300">
            <UIcon name="i-lucide-info" class="w-3.5 h-3.5 inline mr-1" />
            Seleccionar <strong>"Productos y Materias Primas"</strong> hace que este proveedor esté disponible tanto en compras del POS como en entradas del Laboratorio.
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="slideOpen = false">Cancelar</UButton>
          <UButton color="primary" :loading="saving" icon="i-lucide-check" @click="saveSupplier">Guardar</UButton>
        </div>
      </template>
    </USlideover>
  </div>
</template>
