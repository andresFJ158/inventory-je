<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()

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

// State del formulario y catálogo reactivo desde la API del backend
const items = ref<any[]>([])
const suppliers = ref<any[]>([])
const loading = ref(true)
const searchQuery = ref('')

const filteredItems = computed(() => {
  if (!searchQuery.value) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(i =>
    i.name.toLowerCase().includes(q) ||
    i.unit.toLowerCase().includes(q) ||
    (i.desc && i.desc.toLowerCase().includes(q))
  )
})

// Configuración de API
const config = useRuntimeConfig()
const apiBase = '/ajax/pos.ajax.php'

async function fetchSuppliers() {
  try {
    const res = await $fetch<any>('/api/suppliers', {
      headers: { 'Authorization': 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' } // same API_KEY as insumos
    })
    if (res.status === 200) {
      suppliers.value = res.results.filter((s: any) => s.type_supplier === 'materias_primas')
    }
  } catch (e) {
    console.error('Error fetching suppliers:', e)
  }
}

async function fetchMaterials() {
  loading.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabMaterials: 'ok',
        id_office: String(auth.effectiveOfficeId ?? auth.officeId ?? 0)
      }).toString(),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      items.value = data.results.map((m: any) => ({
        id: m.id_raw_material,
        name: m.name_raw_material,
        type: m.measure_type || 'unit',
        stock: parseFloat(m.stock_raw_material) || 0,
        unit: m.unit_raw_material,
        desc: m.description_raw_material || '',
        no_stock: parseInt(m.no_stock_raw_material) === 1,
        price: parseFloat(m.price_raw_material) || 0,
        id_supplier: m.id_supplier_raw_material ? String(m.id_supplier_raw_material) : ''
      }))
    } else {
      items.value = []
    }
  } catch (error) {
    console.error('Error fetching materials:', error)
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchSuppliers()
  fetchMaterials()
})

const isModalOpen = ref(false)
const modalTitle = ref('Registrar Materia Prima')

const form = ref({
  id: null as number | null,
  name: '',
  type: 'unit' as 'unit' | 'weight' | 'volume',
  unit: 'und',
  desc: '',
  no_stock: false,
  price: 0,
  id_supplier: ''
})

const unitOptions = {
  weight: ['kg', 'g'],
  volume: ['L', 'ml'],
  unit: ['und']
}

function handleMeasureTypeChange(type: 'unit' | 'weight' | 'volume') {
  form.value.type = type
  form.value.unit = unitOptions[type][0] || 'und'
}

function openCreateModal() {
  form.value = { id: null, name: '', type: 'unit', unit: 'und', desc: '', no_stock: false, price: 0, id_supplier: '' }
  modalTitle.value = 'Registrar Materia Prima'
  isModalOpen.value = true
}

function openEditModal(item: any) {
  form.value = { ...item, type: item.type as 'unit' | 'weight' | 'volume', id_supplier: item.id_supplier || '' }
  modalTitle.value = 'Editar Materia Prima'
  isModalOpen.value = true
}

async function saveMaterial() {
  if (!form.value.name) return

  try {
    let response: any
    if (form.value.id !== null) {
      // Editar vía pos.ajax.php
      response = await $fetch<any>(apiBase, {
        method: 'POST',
        body: new URLSearchParams({
          editRawMaterial: 'ok',
          id_raw_material: String(form.value.id),
          name_raw_material: form.value.name,
          measure_type: form.value.type,
          unit_raw_material: form.value.unit,
          description_raw_material: form.value.desc,
          no_stock_raw_material: form.value.no_stock ? '1' : '0',
          price_raw_material: String(form.value.price),
          id_supplier_raw_material: form.value.id_supplier || '0'
        }).toString(),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      })
    } else {
      // Registrar nuevo
      response = await $fetch<any>(apiBase, {
        method: 'POST',
        body: new URLSearchParams({
          saveLabMaterial: 'ok',
          name_raw_material: form.value.name,
          measure_type: form.value.type,
          unit_raw_material: form.value.unit,
          description_raw_material: form.value.desc,
          no_stock_raw_material: form.value.no_stock ? '1' : '0',
          price_raw_material: String(form.value.price),
          id_office_raw_material: String(auth.effectiveOfficeId ?? auth.officeId ?? 0),
          id_admin_raw_material: String(auth.user?.id_admin || 1),
          id_supplier_raw_material: form.value.id_supplier || '0'
        }).toString(),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      })
    }

    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText.startsWith('error')) {
      toast.add({ title: 'Error del servidor', description: resText.split('|')[1], color: 'error' })
      return
    }

    isModalOpen.value = false
    await fetchMaterials()
  } catch (error: any) {
    console.error('Error saving material:', error)
    toast.add({ title: 'Error al guardar la materia prima', description: error.message || String(error), color: 'error' })
    isModalOpen.value = false
  }
}

async function deleteMaterial(item: any) {
  if (!await confirmAction('Eliminar materia prima', `¿Eliminar la materia prima "${item.name}"? Solo se puede eliminar si no tiene stock, no está en recetas ni en historial de producción.`)) return
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        deleteRawMaterial: 'ok',
        id_raw_material: String(item.id)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText.startsWith('error')) {
      toast.add({ title: 'No se puede eliminar', description: resText.split('|')[1] || resText, color: 'error' })
      return
    }
    await fetchMaterials()
  } catch (error) {
    console.error('Error deleting material:', error)
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
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white border border-slate-200 p-6 rounded-2xl shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
          <UIcon
            name="i-lucide-droplet"
            class="text-green-500"
          />
          Catálogo de Materia Prima
        </h1>
        <p class="text-slate-500 text-sm mt-1">
          Gestión y registro de insumos base para formulaciones de laboratorio.
        </p>
      </div>
      <UButton
        v-if="auth.role === 'lab_admin'"
        icon="i-lucide-plus"
        color="success"
        size="md"
        class="font-bold!"
        @click="openCreateModal"
      >
        Agregar Materia Prima
      </UButton>
    </div>

    <!-- Tabla -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="font-bold text-slate-800 tracking-wide">
          Insumos en Catálogo ({{ filteredItems.length }}<span v-if="searchQuery" class="text-xs font-normal text-slate-400"> de {{ items.length }}</span>)
        </h3>
        <UInput
          v-model="searchQuery"
          icon="i-lucide-search"
          placeholder="Buscar materia prima..."
          size="sm"
          class="w-full sm:w-64"
        />
      </div>

      <div class="overflow-x-auto">
        <div
          v-if="loading"
          class="p-8 text-center text-slate-500"
        >
          <UIcon
            name="i-lucide-loader-2"
            class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2"
          />
          Cargando catálogo desde la Base de Datos...
        </div>
        <table
          v-else
          class="w-full text-left text-sm text-slate-600"
        >
          <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
            <tr>
              <th class="px-6 py-4">
                ID
              </th>
              <th class="px-6 py-4">
                Nombre
              </th>
              <th class="px-6 py-4">
                Tipo
              </th>
              <th class="px-6 py-4">
                Stock Actual
              </th>
              <th class="px-6 py-4">
                Unidad
              </th>
              <th class="px-6 py-4">
                Descripción
              </th>
              <th class="px-6 py-4">
                Proveedor
              </th>
              <th
                v-if="auth.role === 'lab_admin'"
                class="px-6 py-4 text-center"
              >
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-if="filteredItems.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">No se encontraron materias primas con ese criterio.</td>
            </tr>
            <tr
              v-for="(item, i) in filteredItems"
              :key="item.id"
              class="hover:bg-slate-50 transition-all duration-150"
            >
              <td class="px-6 py-4 font-mono text-slate-400">
                {{ i + 1 }}
              </td>
              <td class="px-6 py-4 font-bold text-slate-800 uppercase tracking-wide">
                {{ item.name }}
                <span v-if="item.no_stock" class="ml-2 text-[10px] bg-sky-100 text-sky-700 px-1.5 py-0.5 rounded uppercase font-bold">Fijo / Sin Stock</span>
              </td>
              <td class="px-6 py-4">
                <span
                  v-if="item.type === 'weight'"
                  class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-amber-400/10 text-amber-600 border border-amber-400/20"
                >
                  <UIcon
                    name="i-lucide-weight"
                    class="w-3.5 h-3.5"
                  /> Peso
                </span>
                <span
                  v-else-if="item.type === 'volume'"
                  class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-cyan-400/10 text-cyan-600 border border-cyan-400/20"
                >
                  <UIcon
                    name="i-lucide-droplet"
                    class="w-3.5 h-3.5"
                  /> Volumen
                </span>
                <span
                  v-else
                  class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-green-400/10 text-green-600 border border-green-400/20"
                >
                  <UIcon
                    name="i-lucide-box"
                    class="w-3.5 h-3.5"
                  /> Unidad
                </span>
              </td>
              <td class="px-6 py-4 font-bold font-mono text-slate-800">
                <span v-if="!item.no_stock">{{ item.stock.toFixed(2) }}</span>
                <span v-else class="text-slate-400">N/A</span>
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-bold text-xs">{{ item.unit }}</span>
              </td>
              <td class="px-6 py-4 text-xs text-slate-500 max-w-xs truncate">
                {{ item.desc }}
              </td>
              <td class="px-6 py-4">
                {{ getSupplierName(item.id_supplier) }}
              </td>
              <td
                v-if="auth.role === 'lab_admin'"
                class="px-6 py-4 text-center flex items-center justify-center gap-2"
              >
                <UButton
                  icon="i-lucide-edit-2"
                  color="warning"
                  variant="subtle"
                  size="xs"
                  @click="openEditModal(item)"
                />
                <UButton
                  icon="i-lucide-trash-2"
                  color="error"
                  variant="subtle"
                  size="xs"
                  @click="deleteMaterial(item)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <UModal v-model:open="isModalOpen">
      <template #content>
        <div class="w-full p-6 space-y-4 text-slate-900">
          <div class="flex justify-between items-center border-b border-slate-200 pb-3">
            <h3 class="text-lg font-bold text-slate-800 tracking-wide">
              {{ modalTitle }}
            </h3>
            <UButton
              icon="i-lucide-x"
              color="neutral"
              variant="ghost"
              size="sm"
              @click="isModalOpen = false"
            />
          </div>

          <form
            class="space-y-4"
            @submit.prevent="saveMaterial"
          >
            <!-- Nombre -->
            <div class="space-y-1.5">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Nombre de Insumo</label>
              <UInput
                v-model="form.name"
                placeholder="Ej. Alcohol Isopropílico"
                size="md"
                required
              />
            </div>

            <div class="flex items-center gap-3 bg-sky-50 p-3 rounded-lg border border-sky-100">
              <USwitch v-model="form.no_stock" color="info" />
              <div>
                <p class="text-sm font-bold text-sky-800">Insumo Especial (Sin Descuento de Stock)</p>
                <p class="text-xs text-sky-600">Ej: Agua, Energía. Se cobrará un costo fijo en recetas.</p>
              </div>
            </div>

            <div v-if="form.no_stock" class="space-y-1.5">
              <label class="block text-xs font-bold uppercase tracking-wider text-sky-600">Costo Fijo (Bs.)</label>
              <UInput
                v-model.number="form.price"
                type="number"
                step="0.01"
                min="0"
                required
              />
            </div>

            <!-- Selector de Tipo de Medida -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Tipo de Medida</label>
              <div class="grid grid-cols-3 gap-2 mt-2">
                <button
                  type="button"
                  class="flex flex-col items-center justify-center py-3 px-2 rounded-xl border-2 transition-all duration-150"
                  :class="form.type === 'weight' ? 'border-amber-400/80 bg-amber-500/10 text-amber-600 shadow-lg shadow-amber-500/5' : 'border-slate-200 hover:border-slate-300 bg-slate-50 text-slate-500'"
                  @click="handleMeasureTypeChange('weight')"
                >
                  <UIcon
                    name="i-lucide-weight"
                    class="w-8 h-8 mb-2"
                  />
                  <span class="font-bold text-sm">Peso</span>
                </button>

                <button
                  type="button"
                  class="flex flex-col items-center justify-center py-3 px-2 rounded-xl border-2 transition-all duration-150"
                  :class="form.type === 'volume' ? 'border-cyan-400/80 bg-cyan-500/10 text-cyan-600 shadow-lg shadow-cyan-500/5' : 'border-slate-200 hover:border-slate-300 bg-slate-50 text-slate-500'"
                  @click="handleMeasureTypeChange('volume')"
                >
                  <UIcon
                    name="i-lucide-droplet"
                    class="w-8 h-8 mb-2"
                  />
                  <span class="font-bold text-sm">Volumen</span>
                </button>

                <button
                  type="button"
                  class="flex flex-col items-center justify-center py-3 px-2 rounded-xl border-2 transition-all duration-150"
                  :class="form.type === 'unit' ? 'border-green-400/80 bg-green-500/10 text-green-600 shadow-lg shadow-green-500/5' : 'border-slate-200 hover:border-slate-300 bg-slate-50 text-slate-500'"
                  @click="handleMeasureTypeChange('unit')"
                >
                  <UIcon
                    name="i-lucide-box"
                    class="w-8 h-8 mb-2"
                  />
                  <span class="font-bold text-sm">Unidad</span>
                </button>
              </div>
            </div>

            <!-- Unidad de Medida -->
            <div class="space-y-1.5">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Unidad de Medida</label>
              <USelect
                v-model="form.unit"
                :items="unitOptions[form.type]"
                size="md"
                class="w-full"
                required
              />
            </div>

            <!-- Descripción -->
            <div class="space-y-1.5">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Descripción (Opcional)</label>
              <UTextarea
                v-model="form.desc"
                placeholder="Especificaciones, usos o detalles químicos..."
                :rows="3"
              />
            </div>

            <!-- Proveedor -->
            <div class="space-y-1.5">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Proveedor (Opcional)</label>
              <select v-model="form.id_supplier" class="block w-full text-sm bg-white border border-slate-200 rounded-md px-2 py-1.5 focus:outline-none focus:border-indigo-500">
                <option value="">Seleccionar Proveedor</option>
                <option v-for="s in suppliers" :key="s.id_supplier" :value="String(s.id_supplier)">
                  {{ decodeURIComponent(s.supplier_name || '').replace(/\+/g, ' ') }}
                </option>
              </select>
            </div>

            <!-- Footer Buttons -->
            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 mt-6">
              <UButton
                label="Cancelar"
                variant="ghost"
                color="neutral"
                @click="isModalOpen = false"
              />
              <UButton
                type="submit"
                label="Guardar Insumo"
                color="success"
                class="font-bold!"
              />
            </div>
          </form>
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
