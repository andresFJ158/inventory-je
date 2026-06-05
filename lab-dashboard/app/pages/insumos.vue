<script setup lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

// State del formulario y catálogo reactivo desde la API del backend
const items = ref<any[]>([])
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
const apiBase = '/ajax/pos.ajax.php'

async function fetchInsumos() {
  loading.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabMaterials: 'ok',
        id_office: String(auth.officeId || 6),
        is_insumo: '1'
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
        desc: m.description_raw_material || ''
      }))
    } else {
      items.value = []
    }
  } catch (error) {
    console.error('Error fetching insumos:', error)
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchInsumos()
})

const isModalOpen = ref(false)
const modalTitle = ref('Registrar Insumo')

const form = ref({
  id: null as number | null,
  name: '',
  type: 'unit' as 'unit' | 'weight' | 'volume',
  unit: 'und',
  desc: ''
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
  form.value = { id: null, name: '', type: 'unit', unit: 'und', desc: '' }
  modalTitle.value = 'Registrar Insumo'
  isModalOpen.value = true
}

function openEditModal(item: any) {
  form.value = { ...item, type: item.type as 'unit' | 'weight' | 'volume' }
  modalTitle.value = 'Editar Insumo'
  isModalOpen.value = true
}

async function saveInsumo() {
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
          description_raw_material: form.value.desc
        }).toString(),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      })
    } else {
      // Registrar nuevo (con is_insumo = 1)
      response = await $fetch<any>(apiBase, {
        method: 'POST',
        body: new URLSearchParams({
          saveLabMaterial: 'ok',
          name_raw_material: form.value.name,
          measure_type: form.value.type,
          unit_raw_material: form.value.unit,
          description_raw_material: form.value.desc,
          id_office_raw_material: String(auth.officeId || 6),
          id_admin_raw_material: String(auth.user?.id_admin || 1),
          is_insumo: '1'
        }).toString(),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      })
    }

    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText.startsWith('error')) {
      alert('Error del servidor: ' + resText.split('|')[1])
      return
    }

    isModalOpen.value = false
    await fetchInsumos()
  } catch (error: any) {
    console.error('Error saving insumo:', error)
    alert('Error al guardar el insumo: ' + (error.message || error))
    isModalOpen.value = false
  }
}

async function deleteInsumo(item: any) {
  if (!confirm(`¿Eliminar el insumo "${item.name}"?\n\nSolo se puede eliminar si no tiene stock, no está en recetas ni en historial de producción.`)) return
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
      alert('No se puede eliminar: ' + (resText.split('|')[1] || resText))
      return
    }
    await fetchInsumos()
  } catch (error) {
    console.error('Error deleting insumo:', error)
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
          <UIcon
            name="i-lucide-boxes"
            class="text-green-500"
          />
          Catálogo de Insumos
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
          Gestión y registro de envases, tapas, etiquetas y embalaje para la fase de producción y envasado.
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
        Agregar Insumo
      </UButton>
    </div>

    <!-- Tabla -->
    <div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm">
      <div class="p-5 border-b border-slate-200 dark:border-slate-800/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="font-bold text-slate-800 dark:text-white tracking-wide">
          Insumos en Catálogo ({{ filteredItems.length }}<span v-if="searchQuery" class="text-xs font-normal text-slate-400"> de {{ items.length }}</span>)
        </h3>
        <UInput
          v-model="searchQuery"
          icon="i-lucide-search"
          placeholder="Buscar insumo..."
          size="sm"
          class="w-full sm:w-64"
        />
      </div>

      <div class="overflow-x-auto">
        <div
          v-if="loading"
          class="p-8 text-center text-slate-500 dark:text-slate-400"
        >
          <UIcon
            name="i-lucide-loader-2"
            class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2"
          />
          Cargando catálogo de insumos...
        </div>
        <table
          v-else
          class="w-full text-left text-sm text-slate-600 dark:text-slate-300"
        >
          <thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase tracking-wider text-slate-550 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800/80">
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
              <th
                v-if="auth.role === 'lab_admin'"
                class="px-6 py-4 text-center"
              >
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
            <tr v-if="filteredItems.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">No se encontraron insumos registrados.</td>
            </tr>
            <tr
              v-for="(item, i) in filteredItems"
              :key="item.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-all duration-150"
            >
              <td class="px-6 py-4 font-mono text-slate-400 dark:text-slate-500">
                {{ i + 1 }}
              </td>
              <td class="px-6 py-4 font-bold text-slate-800 dark:text-white uppercase tracking-wide">
                {{ item.name }}
              </td>
              <td class="px-6 py-4">
                <span
                  v-if="item.type === 'weight'"
                  class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-amber-400/10 text-amber-600 dark:text-amber-300 border border-amber-400/20"
                >
                  <UIcon
                    name="i-lucide-weight"
                    class="w-3.5 h-3.5"
                  /> Peso
                </span>
                <span
                  v-else-if="item.type === 'volume'"
                  class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-cyan-400/10 text-cyan-600 dark:text-cyan-300 border border-cyan-400/20"
                >
                  <UIcon
                    name="i-lucide-droplet"
                    class="w-3.5 h-3.5"
                  /> Volumen
                </span>
                <span
                  v-else
                  class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-green-400/10 text-green-600 dark:text-green-300 border border-green-400/20"
                >
                  <UIcon
                    name="i-lucide-box"
                    class="w-3.5 h-3.5"
                  /> Unidad
                </span>
              </td>
              <td class="px-6 py-4 font-bold font-mono text-slate-800 dark:text-slate-100">
                {{ item.stock.toFixed(2) }}
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs">{{ item.unit }}</span>
              </td>
              <td class="px-6 py-4 text-xs text-slate-550 dark:text-slate-400 max-w-xs truncate">
                {{ item.desc }}
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
                  @click="deleteInsumo(item)"
                />
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
            <h3 class="text-lg font-bold text-slate-800 dark:text-white tracking-wide">
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
            @submit.prevent="saveInsumo"
          >
            <!-- Nombre -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Nombre de Insumo</label>
              <UInput
                v-model="form.name"
                placeholder="Ej. Envase PET de 250ml con Dosificador"
                size="md"
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
                  :class="form.type === 'weight' ? 'border-amber-400/80 bg-amber-500/10 text-amber-600 dark:text-amber-300 shadow-lg shadow-amber-500/5' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400'"
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
                  :class="form.type === 'volume' ? 'border-cyan-400/80 bg-cyan-500/10 text-cyan-600 dark:text-cyan-300 shadow-lg shadow-cyan-500/5' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400'"
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
                  :class="form.type === 'unit' ? 'border-green-400/80 bg-green-500/10 text-green-600 dark:text-green-300 shadow-lg shadow-green-500/5' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400'"
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
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Unidad de Medida</label>
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
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Descripción (Opcional)</label>
              <UTextarea
                v-model="form.desc"
                placeholder="Especificaciones, material del envase o detalles de embalaje..."
                :rows="3"
              />
            </div>

            <!-- Footer Buttons -->
            <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-6">
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
  </div>
</template>
