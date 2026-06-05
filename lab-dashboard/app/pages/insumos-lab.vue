<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { useAuthStore } from '~/stores/auth'
import { ref, computed, onMounted } from 'vue'

const auth = useAuthStore()

// State
const items = ref<any[]>([])
const suppliers = ref<any[]>([])
const loading = ref(true)
const searchQuery = ref('')

const filteredItems = computed(() => {
  if (!searchQuery.value) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(i =>
    (i.name_supply || '').toLowerCase().includes(q) ||
    (i.unit_supply || '').toLowerCase().includes(q)
  )
})

// Enpoints usando API generic
const fetchSuppliers = async () => {
  try {
    const res = await $fetch<any>('/api/suppliers', {
      headers: { 'Authorization': auth.token || '' }
    })
    if (res.status === 200) {
      suppliers.value = res.results.filter((s: any) => s.type_supplier === 'materias_primas' || s.type_supplier === 'ambos')
    }
  } catch (e) {
    console.error('Error fetching suppliers:', e)
  }
}

const fetchInsumosLab = async () => {
  loading.value = true
  try {
    const res = await $fetch<any>('/api/lab_supplies', {
      headers: { 'Authorization': auth.token || '' }
    })
    if (res.status === 200) {
      items.value = res.results.filter((s: any) => parseInt(s.status_supply) === 1)
    } else {
      items.value = []
    }
  } catch (e) {
    console.error('Error fetching lab supplies:', e)
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchSuppliers()
  fetchInsumosLab()
})

const isModalOpen = ref(false)
const modalTitle = ref('Registrar Insumo de Laboratorio')

const form = ref({
  id: null as number | null,
  name: '',
  unit: 'unidad',
  stock: 0,
  price: 0,
  id_supplier: ''
})

const unitOptions = [
  { value: 'unidad', label: 'Unidad' },
  { value: 'kg', label: 'Kilogramos (kg)' },
  { value: 'litro', label: 'Litros (L)' }
]

function openCreateModal() {
  form.value = { id: null, name: '', unit: 'unidad', stock: 0, price: 0, id_supplier: '' }
  modalTitle.value = 'Registrar Insumo de Laboratorio'
  isModalOpen.value = true
}

function openEditModal(item: any) {
  form.value = { 
    id: item.id_supply, 
    name: decodeURIComponent(item.name_supply || '').replace(/\+/g, ' '), 
    unit: item.unit_supply, 
    stock: parseFloat(item.stock_supply) || 0, 
    price: parseFloat(item.price_supply) || 0,
    id_supplier: item.id_supplier_supply ? String(item.id_supplier_supply) : ''
  }
  modalTitle.value = 'Editar Insumo de Laboratorio'
  isModalOpen.value = true
}

async function saveInsumoLab() {
  if (!form.value.name.trim()) return

  try {
    const body = new URLSearchParams({
      name_supply: form.value.name,
      unit_supply: form.value.unit,
      stock_supply: String(form.value.stock),
      price_supply: String(form.value.price),
      id_supplier_supply: form.value.id_supplier || '0',
      id_office_supply: String(auth.officeId || 6),
      status_supply: '1'
    })

    if (form.value.id !== null) {
      // Editar
      await $fetch(`/api/lab_supplies?id=${form.value.id}&nameId=id_supply`, {
        method: 'PUT',
        body: body.toString(),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Authorization': auth.token || '' }
      })
    } else {
      // Crear
      await $fetch('/api/lab_supplies', {
        method: 'POST',
        body: body.toString(),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Authorization': auth.token || '' }
      })
    }

    isModalOpen.value = false
    await fetchInsumosLab()
  } catch (error: any) {
    console.error('Error saving lab supply:', error)
    alert('Error al guardar el insumo')
  }
}

async function deleteInsumoLab(item: any) {
  if (!confirm(`¿Eliminar el insumo de laboratorio "${decodeURIComponent(item.name_supply || '').replace(/\+/g, ' ')}"?`)) return
  try {
    const body = new URLSearchParams({ status_supply: '0' })
    await $fetch(`/api/lab_supplies?id=${item.id_supply}&nameId=id_supply`, {
      method: 'PUT',
      body: body.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Authorization': auth.token || '' }
    })
    await fetchInsumosLab()
  } catch (error) {
    console.error('Error deleting lab supply:', error)
  }
}

function getSupplierName(id: number | string) {
  if (!id || id == '0') return 'N/A'
  const s = suppliers.value.find(x => x.id_supplier == id)
  return s ? decodeURIComponent(s.supplier_name || '').replace(/\+/g, ' ') : 'Desconocido'
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
          <UIcon name="i-lucide-beaker" class="text-green-500" />
          Insumos de Laboratorio
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
          Gestión del catálogo de insumos específicos para el laboratorio (etiquetas, envases, etc). Separado de materias primas.
        </p>
      </div>
      <UButton
        v-if="auth.role === 'lab_admin' || auth.role === 'superadmin' || auth.role === 'admin'"
        icon="i-lucide-plus"
        color="success"
        size="md"
        class="font-bold!"
        @click="openCreateModal"
      >
        Agregar Insumo
      </UButton>
    </div>

    <!-- Tabla -->
    <div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm">
      <div class="p-5 border-b border-slate-200 dark:border-slate-800/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="font-bold text-slate-800 dark:text-white tracking-wide">
          Insumos de Laboratorio ({{ filteredItems.length }})
        </h3>
        <UInput
          v-model="searchQuery"
          icon="i-lucide-search"
          placeholder="Buscar..."
          size="sm"
          class="w-full sm:w-64"
        />
      </div>

      <div class="overflow-x-auto">
        <div v-if="loading" class="p-8 text-center text-slate-500">
          <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2" />
          Cargando catálogo...
        </div>
        <table v-else class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
          <thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase tracking-wider text-slate-550 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800/80">
            <tr>
              <th class="px-6 py-4">ID</th>
              <th class="px-6 py-4">Nombre</th>
              <th class="px-6 py-4">Stock</th>
              <th class="px-6 py-4">Precio (Bs.)</th>
              <th class="px-6 py-4">Unidad</th>
              <th class="px-6 py-4">Proveedor</th>
              <th v-if="auth.role === 'lab_admin' || auth.role === 'superadmin' || auth.role === 'admin'" class="px-6 py-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
            <tr v-if="filteredItems.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">No se encontraron registros.</td>
            </tr>
            <tr v-for="item in filteredItems" :key="item.id_supply" class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-all duration-150">
              <td class="px-6 py-4 font-mono text-slate-400 dark:text-slate-500">{{ item.id_supply }}</td>
              <td class="px-6 py-4 font-bold text-slate-800 dark:text-white tracking-wide uppercase">{{ decodeURIComponent(item.name_supply || '').replace(/\+/g, ' ') }}</td>
              <td class="px-6 py-4 font-bold font-mono text-slate-800 dark:text-slate-100">{{ parseFloat(item.stock_supply).toFixed(2) }}</td>
              <td class="px-6 py-4 font-mono text-green-600 dark:text-green-400">{{ parseFloat(item.price_supply).toFixed(2) }}</td>
              <td class="px-6 py-4"><span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold text-xs">{{ item.unit_supply }}</span></td>
              <td class="px-6 py-4">{{ getSupplierName(item.id_supplier_supply) }}</td>
              <td v-if="auth.role === 'lab_admin' || auth.role === 'superadmin' || auth.role === 'admin'" class="px-6 py-4 text-center flex justify-center gap-2">
                <UButton icon="i-lucide-edit-2" color="warning" variant="subtle" size="xs" @click="openEditModal(item)" />
                <UButton icon="i-lucide-trash-2" color="error" variant="subtle" size="xs" @click="deleteInsumoLab(item)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario -->
    <UModal v-model:open="isModalOpen">
      <template #content>
        <div class="w-full p-6 space-y-4 text-slate-900 dark:text-white bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
          <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white tracking-wide">{{ modalTitle }}</h3>
            <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" @click="isModalOpen = false" />
          </div>

          <form class="space-y-4" @submit.prevent="saveInsumoLab">
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Nombre</label>
              <UInput v-model="form.name" placeholder="Ej. Etiqueta" required />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Unidad de Medida</label>
                <USelect v-model="form.unit" :items="unitOptions" required />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Precio Referencial (Bs.)</label>
                <UInput v-model.number="form.price" type="number" step="0.01" min="0" required />
              </div>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Stock Inicial (Solo para ajuste)</label>
              <UInput v-model.number="form.stock" type="number" step="any" min="0" required />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Proveedor (Opcional)</label>
              <USelect
                v-model="form.id_supplier"
                :items="[{value: '', label: 'Seleccionar Proveedor'}, ...suppliers.map(s => ({value: String(s.id_supplier), label: decodeURIComponent(s.supplier_name || '').replace(/\+/g, ' ')}))]"
              />
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isModalOpen = false" />
              <UButton type="submit" label="Guardar" color="success" class="font-bold!" />
            </div>
          </form>
        </div>
      </template>
    </UModal>
  </div>
</template>
