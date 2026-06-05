<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useNumericInput } from '~/composables/useNumericInput'

const auth = useAuthStore()

// Helper para bloquear negativos en inputs numéricos de arrays
function blockNegative(e: KeyboardEvent) {
  if (e.key === '-' || e.key === 'e' || e.key === 'E') e.preventDefault()
}

// Formatea un número para mostrar en input
function numDisplay(val: string | number): string {
  if (val === '' || val === null || val === undefined) return ''
  const n = parseFloat(String(val))
  if (isNaN(n) || n < 0) return ''
  return n.toLocaleString('de-DE', { maximumFractionDigits: 3 })
}

// Parsea el valor formateado de vuelta a número real
function numParse(formatted: string): string {
  const clean = formatted.replace(/\./g, '').replace(',', '.')
  const n = parseFloat(clean)
  return isNaN(n) || n < 0 ? '' : String(n)
}

function onNumInput(e: Event, obj: any, key: string) {
  const input = e.target as HTMLInputElement
  let raw = input.value.replace(/[^\d,]/g, '')
  raw = raw.replace(',', '.')
  const parts = raw.split('.')
  const intStr = (parts[0] || '').replace(/^0+(?=\d)/, '') || '0'
  const intVal = parseInt(intStr, 10) || 0
  const decStr = parts[1] !== undefined ? parts[1].slice(0, 3) : undefined

  const formatted = decStr !== undefined
    ? intVal.toLocaleString('de-DE') + ',' + decStr
    : intVal > 0 ? intVal.toLocaleString('de-DE') : ''

  obj[key] = numParse(formatted) || numParse(input.value)
  input.value = formatted
}

// Inputs para campos únicos (batch_size)
const newBatchInput = useNumericInput('1', { decimals: 3, min: 0 })
const editBatchInput = useNumericInput('1', { decimals: 3, min: 0 })

const recipes = ref<any[]>([])
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

// Estado para el creador de receta
const isCreateOpen = ref(false)
const materials = ref<any[]>([])
const loadingMaterials = ref(false)

const newRecipe = ref({
  name_product: '',
  unit_batch: 'L',
  ingredients: [] as Array<{ id_raw: string; qty: string }>
})

async function fetchMaterials() {
  loadingMaterials.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabMaterials: 'ok',
        id_office: String(auth.officeId || 6)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      materials.value = data.results
    }
  } catch (error) {
    console.error('Error fetching materials:', error)
  } finally {
    loadingMaterials.value = false
  }
}

async function fetchRecipes() {
  loading.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabRecipes: 'ok',
        id_office: String(auth.officeId || 6)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      recipes.value = data.results.map((r: any) => ({
        id: r.id_recipe,
        name: r.title_product,
        batch_size: parseFloat(r.batch_size_recipe) || 1,
        unit_batch: r.unit_batch_recipe || 'u',
        components: r.ingredients ? r.ingredients.map((ing: any) => ({
          name: ing.name_raw_material,
          qty: parseFloat(ing.qty_ingredient) || 0,
          unit: ing.unit_raw_material || '',
          unit_price: parseFloat(ing.unit_price_mp) || 0,
          subtotal: (parseFloat(ing.qty_ingredient) || 0) * (parseFloat(ing.unit_price_mp) || 0)
        })) : [],
        cost_estimated: parseFloat(r.cost_estimated) || 0,
        cost_real: parseFloat(r.cost_real) || 0,
        has_real_cost: r.has_real_cost === true || r.has_real_cost == 1,
        avg_variance: parseFloat(r.avg_variance) || 0,
        worst_variance: parseFloat(r.worst_variance) || 0,
        best_variance: parseFloat(r.best_variance) || 0,
        variance_history: r.variance_history || []
      }))
    } else {
      recipes.value = []
    }
  } catch (error) {
    console.error('Error fetching recipes:', error)
    recipes.value = []
  } finally {
    loading.value = false
  }
}

function addIngredient() {
  newRecipe.value.ingredients.push({ id_raw: '', qty: '' })
}

function removeIngredient(index: number) {
  newRecipe.value.ingredients.splice(index, 1)
}

async function handleOpenCreateModal() {
  await fetchMaterials()
  newRecipe.value = {
    name_product: '',
    unit_batch: 'L',
    ingredients: [{ id_raw: '', qty: '' }]
  }
  newBatchInput.setValue(1)
  isCreateOpen.value = true
}

async function handleSaveRecipe() {
  if (!newRecipe.value.name_product || newBatchInput.raw.value <= 0) {
    alert('Por favor, ingresa el nombre de la fórmula y una cantidad base mayor a 0.')
    return
  }

  const validIngredients = newRecipe.value.ingredients.filter(i => i.id_raw && parseFloat(i.qty) > 0)
  if (validIngredients.length === 0) {
    alert('Debes agregar al menos un ingrediente válido con su cantidad.')
    return
  }

  try {
    const body = new URLSearchParams()
    body.append('saveRecipe', 'ok')
    body.append('name_product', newRecipe.value.name_product)
    body.append('batch_size', String(newBatchInput.raw.value))
    body.append('unit_batch', newRecipe.value.unit_batch)
    body.append('id_office', String(auth.officeId || 6))
    body.append('id_admin', String(auth.user?.id_admin || 1))
    body.append('ingredients', JSON.stringify(validIngredients))
    body.append('labor', JSON.stringify([]))

    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: body.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText === 'ok') {
      isCreateOpen.value = false
      await fetchRecipes()
    } else {
      alert('Error al guardar la receta: ' + (resText.startsWith('error|') ? resText.split('|')[1] : 'El nombre puede ya existir en la sucursal.'))
    }
  } catch (error) {
    console.error('Error saving recipe:', error)
    alert('Error de conexión con el servidor.')
  }
}

// ---- EDITAR RECETA ----
const isEditOpen = ref(false)
const editingRecipe = ref<any>(null)
const editForm = ref({
  id_recipe: '',
  name_product: '',
  unit_batch: 'L',
  ingredients: [] as Array<{ id_raw: string; qty: string }>
})

async function handleOpenEditModal(recipe: any) {
  await fetchMaterials()
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({ getRecipeDataForEdit: 'ok', id_recipe: String(recipe.id) }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    editingRecipe.value = recipe
    editForm.value = {
      id_recipe: String(recipe.id),
      name_product: data.recipe?.title_product || recipe.name,
      unit_batch: data.recipe?.unit_batch_recipe || 'L',
      ingredients: (data.ingredients || []).map((ing: any) => ({
        id_raw: String(ing.id_raw_material_ingredient),
        qty: String(ing.qty_ingredient)
      }))
    }
    editBatchInput.setValue(parseFloat(data.recipe?.batch_size_recipe) || 1)
    if (editForm.value.ingredients.length === 0) {
      editForm.value.ingredients.push({ id_raw: '', qty: '' })
    }
    isEditOpen.value = true
  } catch (error) {
    console.error('Error loading recipe for edit:', error)
    alert('Error al cargar los datos de la receta.')
  }
}

function addEditIngredient() {
  editForm.value.ingredients.push({ id_raw: '', qty: '' })
}

function removeEditIngredient(index: number) {
  editForm.value.ingredients.splice(index, 1)
}

async function handleUpdateRecipe() {
  if (!editForm.value.name_product || editBatchInput.raw.value <= 0) {
    alert('Por favor, ingresa el nombre de la fórmula y una cantidad base mayor a 0.')
    return
  }

  const validIngredients = editForm.value.ingredients.filter(i => i.id_raw && i.qty)
  if (validIngredients.length === 0) {
    alert('Debes agregar al menos un ingrediente válido con su cantidad.')
    return
  }

  try {
    const body = new URLSearchParams()
    body.append('editRecipe', 'ok')
    body.append('id_recipe', editForm.value.id_recipe)
    body.append('name_product', editForm.value.name_product)
    body.append('batch_size', String(editBatchInput.raw.value))
    body.append('unit_batch', editForm.value.unit_batch)
    body.append('ingredients', JSON.stringify(validIngredients))
    body.append('labor', JSON.stringify([]))

    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: body.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText === 'ok') {
      isEditOpen.value = false
      await fetchRecipes()
    } else {
      alert('Error al actualizar la receta: ' + (resText.startsWith('error|') ? resText.split('|')[1] : resText))
    }
  } catch (error) {
    console.error('Error updating recipe:', error)
    alert('Error de conexión con el servidor.')
  }
}

// ---- ELIMINAR RECETA ----
async function handleDeleteRecipe(recipe: any) {
  if (!confirm(`¿Eliminar la receta "${recipe.name}"?\n\nSe eliminará el producto asociado. Esta acción no se puede deshacer.`)) return
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({ deleteRecipe: 'ok', id_recipe: String(recipe.id) }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText === 'ok') {
      await fetchRecipes()
    } else {
      alert('Error al eliminar la receta. Puede tener producciones asociadas.')
    }
  } catch (error) {
    console.error('Error deleting recipe:', error)
    alert('Error de conexión con el servidor.')
  }
}

onMounted(() => {
  fetchRecipes()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
          <UIcon
            name="i-lucide-scroll"
            class="text-green-500"
          />
          Recetas de Laboratorio
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
          Configuración técnica de componentes químicos y cálculo de costo promedio.
        </p>
      </div>
      <UButton
        v-if="auth.role !== 'lab_worker'"
        icon="i-lucide-plus"
        color="success"
        size="md"
        class="font-bold!"
        @click="handleOpenCreateModal"
      >
        Crear Nueva Receta
      </UButton>
    </div>

    <div v-if="loading" class="p-8 text-center text-slate-500 dark:text-slate-400">
      <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2" />
      Cargando recetas formuladas...
    </div>

    <div v-else-if="recipes.length === 0" class="text-center p-8 text-slate-500">
      No hay recetas formuladas registradas en el laboratorio.
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div
        v-for="recipe in recipes"
        :key="recipe.id"
        class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-xl space-y-4 shadow-sm"
      >
        <!-- Header: nombre + acciones -->
        <div class="flex justify-between items-start gap-2">
          <div class="flex-1 min-w-0">
            <h3 class="text-base font-bold text-slate-800 dark:text-white uppercase tracking-wide truncate">
              {{ recipe.name }}
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Lote base: <span class="font-bold text-slate-600 dark:text-slate-300">{{ recipe.batch_size }} {{ recipe.unit_batch }}</span>
            </p>
          </div>
          <div class="flex items-center gap-1 shrink-0">
            <UButton
              v-if="auth.role !== 'lab_worker'"
              icon="i-lucide-edit-2"
              color="warning"
              variant="ghost"
              size="xs"
              title="Editar receta"
              @click="handleOpenEditModal(recipe)"
            />
            <UButton
              v-if="auth.role === 'lab_admin' || auth.role === 'superadmin'"
              icon="i-lucide-trash-2"
              color="error"
              variant="ghost"
              size="xs"
              title="Eliminar receta"
              @click="handleDeleteRecipe(recipe)"
            />
          </div>
        </div>

        <!-- Tabla de ingredientes con costos -->
        <div class="space-y-1">
          <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
            Fórmula / Ingredientes
          </h4>
          <div class="rounded-lg border border-slate-100 dark:border-slate-800/60 overflow-hidden">
            <div class="grid text-xs font-bold uppercase tracking-wider text-slate-400 bg-slate-50 dark:bg-slate-900/60 px-3 py-1.5"
              :class="auth.role !== 'lab_worker' ? 'grid-cols-4' : 'grid-cols-2'">
              <span class="col-span-2">Materia Prima</span>
              <span v-if="auth.role !== 'lab_worker'" class="text-right">Precio MP</span>
              <span class="text-right">Cantidad</span>
            </div>
            <div
              v-for="comp in recipe.components"
              :key="comp.name"
              class="grid px-3 py-2 border-t border-slate-100 dark:border-slate-800/40 hover:bg-slate-50 dark:hover:bg-slate-800/10 transition-colors"
              :class="auth.role !== 'lab_worker' ? 'grid-cols-4' : 'grid-cols-2'"
            >
              <span class="col-span-2 text-slate-700 dark:text-slate-300 font-medium truncate">{{ comp.name }}</span>
              <span v-if="auth.role !== 'lab_worker'" class="text-right font-mono text-slate-500 dark:text-slate-400 text-xs">
                <span v-if="comp.unit_price > 0" class="text-slate-600 dark:text-slate-300">Bs. {{ comp.unit_price.toFixed(2) }}/{{ comp.unit }}</span>
                <span v-else class="text-amber-500 italic">Sin precio</span>
              </span>
              <span class="text-right font-mono font-bold text-slate-600 dark:text-slate-300">{{ comp.qty }} <span class="text-xs font-normal text-slate-400">{{ comp.unit }}</span></span>
            </div>
          </div>
        </div>

        <!-- Bloque de costos (solo visible para no-lab_worker) -->
        <div v-if="auth.role !== 'lab_worker'" class="border-t border-slate-100 dark:border-slate-800/60 pt-3 space-y-1.5">
          <!-- Costo estimado por lote de ingredientes -->
          <div class="flex justify-between items-center text-xs">
            <span class="text-slate-400 font-medium">Costo MP del lote ({{ recipe.batch_size }} {{ recipe.unit_batch }}):</span>
            <span class="font-mono font-bold text-slate-600 dark:text-slate-300">
              Bs. {{ recipe.components.reduce((acc: number, c: any) => acc + c.subtotal, 0).toFixed(2) }}
            </span>
          </div>
          <!-- Costo estimado por unidad -->
          <div class="flex justify-between items-center text-xs">
            <span class="text-slate-400 font-medium">Costo MP / unidad producida:</span>
            <span class="font-mono font-bold" :class="recipe.cost_estimated > 0 ? 'text-green-600 dark:text-green-400' : 'text-amber-500'">
              <span v-if="recipe.cost_estimated > 0">Bs. {{ recipe.cost_estimated.toFixed(4) }}</span>
              <span v-else class="italic text-amber-500">Sin precio de MP</span>
            </span>
          </div>
          <!-- Costo promedio real (de producciones completadas) -->
          <div v-if="recipe.has_real_cost" class="flex justify-between items-center py-1.5 px-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg">
            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
              <UIcon name="i-lucide-check-circle" class="w-3.5 h-3.5" /> Costo Promedio Real (QC):
            </span>
            <span class="font-mono font-bold text-emerald-700 dark:text-emerald-300 text-sm">
              Bs. {{ recipe.cost_real.toFixed(4) }} / und
            </span>
          </div>
          <div v-else class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400">
            <UIcon name="i-lucide-info" class="w-3.5 h-3.5 shrink-0" />
            <span>El costo real se calculará al completar la primera producción con QC aprobado.</span>
          </div>
        </div>

        <!-- Métricas de Merma -->
        <div v-if="recipe.variance_history.length > 0" class="border-t border-slate-100 dark:border-slate-800/60 pt-3 space-y-2">
          <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Métricas de Merma</h4>
          <div class="grid grid-cols-3 gap-2">
            <div class="bg-slate-50 dark:bg-slate-900 p-2 rounded text-center">
              <p class="text-[10px] text-slate-500 uppercase">Promedio</p>
              <p class="font-bold font-mono text-sm" :class="recipe.avg_variance < 0 ? 'text-rose-500' : 'text-green-500'">
                {{ recipe.avg_variance > 0 ? '+' : '' }}{{ recipe.avg_variance.toFixed(1) }}%
              </p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-900 p-2 rounded text-center">
              <p class="text-[10px] text-slate-500 uppercase">Peor</p>
              <p class="font-bold font-mono text-sm text-rose-500">
                {{ recipe.worst_variance > 0 ? '+' : '' }}{{ recipe.worst_variance.toFixed(1) }}%
              </p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-900 p-2 rounded text-center">
              <p class="text-[10px] text-slate-500 uppercase">Mejor</p>
              <p class="font-bold font-mono text-sm text-green-500">
                {{ recipe.best_variance > 0 ? '+' : '' }}{{ recipe.best_variance.toFixed(1) }}%
              </p>
            </div>
          </div>
          <div class="flex gap-1 items-end h-8 mt-2 px-1">
            <div v-for="(v, i) in recipe.variance_history" :key="i" class="flex-1 rounded-t" :class="v < 0 ? 'bg-rose-400' : 'bg-green-400'" :style="{ height: Math.max(10, Math.min(100, Math.abs(v) * 2)) + '%' }" :title="v + '%'"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Editar Receta -->
    <UModal v-model:open="isEditOpen">
      <template #content>
        <div class="w-full p-6 space-y-4 text-slate-900 dark:text-white">
          <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-amber-600 dark:text-amber-400">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-edit-2" class="w-5 h-5" /> Editar Fórmula: {{ editingRecipe?.name }}
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isEditOpen = false" />
          </div>

          <form class="space-y-4" @submit.prevent="handleUpdateRecipe">
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Nombre del Producto</label>
              <input v-model="editForm.name_product" type="text" placeholder="Ej: Vinagre de Manzana 1L" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50">
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Litros de agua base (Tamaño Lote)</label>
                <input
                  :value="editBatchInput.display.value"
                  type="text"
                  inputmode="decimal"
                  placeholder="1,000"
                  class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50"
                  @input="editBatchInput.onInput($event as InputEvent)"
                  @keydown="editBatchInput.onKeydown($event as KeyboardEvent)"
                >
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Unidad de Medida</label>
                <input v-model="editForm.unit_batch" type="text" disabled placeholder="L" class="block w-full py-2.5 px-3 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-500 dark:text-slate-400 text-sm cursor-not-allowed">
              </div>
            </div>

            <div class="space-y-3 pt-2">
              <div class="flex justify-between items-center">
                <label class="block text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Insumos (Fórmula)</label>
                <UButton icon="i-lucide-plus" label="Agregar Insumo" color="warning" variant="ghost" size="xs" @click="addEditIngredient" />
              </div>

              <div class="space-y-3 max-h-[40vh] overflow-y-auto pr-1">
                <div v-for="(ing, index) in editForm.ingredients" :key="index" class="flex items-center gap-3 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                  <select v-model="ing.id_raw" class="flex-1 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <option value="">Seleccione Insumo...</option>
                    <option v-for="m in materials" :key="m.id_raw_material" :value="m.id_raw_material">
                      {{ m.name_raw_material }} ({{ m.unit_raw_material }})
                    </option>
                  </select>
                  <input
                    :value="numDisplay(ing.qty)"
                    type="text"
                    inputmode="decimal"
                    placeholder="0,000"
                    class="w-24 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500"
                    @input="onNumInput($event, ing, 'qty')"
                    @keydown="blockNegative($event as KeyboardEvent)"
                  >
                  <UButton icon="i-lucide-trash" color="error" variant="ghost" size="xs" @click="removeEditIngredient(index)" />
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isEditOpen = false" />
              <UButton type="submit" label="Guardar Cambios" color="warning" class="font-bold!" />
            </div>
          </form>
        </div>
      </template>
    </UModal>

    <!-- Modal Crear Receta (UModal v-model:open) -->
    <UModal v-model:open="isCreateOpen">
      <template #content>
        <div class="w-full p-6 space-y-4 text-slate-900 dark:text-white">
          <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-green-600 dark:text-green-400">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-plus" class="w-5 h-5" /> Diseñador de Fórmulas
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isCreateOpen = false" />
          </div>

          <form class="space-y-4" @submit.prevent="handleSaveRecipe">
            <!-- Nombre del producto -->
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Producto a Fabricar (Nombre)</label>
              <input v-model="newRecipe.name_product" type="text" placeholder="Ej: Vinagre de Manzana 1L" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
            </div>

            <!-- Lote Base y Unidad -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Litros de agua base (Tamaño Lote)</label>
                <input
                  :value="newBatchInput.display.value"
                  type="text"
                  inputmode="decimal"
                  placeholder="1,000"
                  class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                  @input="newBatchInput.onInput($event as InputEvent)"
                  @keydown="newBatchInput.onKeydown($event as KeyboardEvent)"
                >
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Unidad de Medida</label>
                <input v-model="newRecipe.unit_batch" type="text" disabled placeholder="L" class="block w-full py-2.5 px-3 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-500 dark:text-slate-400 text-sm cursor-not-allowed">
              </div>
            </div>

            <!-- Ingredientes Dinámicos -->
            <div class="space-y-3 pt-2">
              <div class="flex justify-between items-center">
                <label class="block text-xs font-bold text-green-600 dark:text-green-400 uppercase tracking-wider">Insumos (Fórmula)</label>
                <UButton icon="i-lucide-plus" label="Agregar Insumo" color="success" variant="ghost" size="xs" @click="addIngredient" />
              </div>

              <div class="space-y-3 max-h-[40vh] overflow-y-auto pr-1">
                <div v-for="(ing, index) in newRecipe.ingredients" :key="index" class="flex items-center gap-3 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                  <!-- Select Insumo -->
                  <select v-model="ing.id_raw" class="flex-1 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500">
                    <option value="">Seleccione Insumo...</option>
                    <option v-for="m in materials" :key="m.id_raw_material" :value="m.id_raw_material">
                      {{ m.name_raw_material }} ({{ m.unit_raw_material }})
                    </option>
                  </select>

                  <!-- Cantidad -->
                  <input
                    :value="numDisplay(ing.qty)"
                    type="text"
                    inputmode="decimal"
                    placeholder="0,000"
                    class="w-24 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500"
                    @input="onNumInput($event, ing, 'qty')"
                    @keydown="blockNegative($event as KeyboardEvent)"
                  >

                  <!-- Botón eliminar -->
                  <UButton icon="i-lucide-trash" color="error" variant="ghost" size="xs" @click="removeIngredient(index)" />
                </div>
              </div>
            </div>

            <!-- Footer integrado directamente sin márgenes negativos -->
            <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isCreateOpen = false" />
              <UButton type="submit" label="Guardar Receta" color="success" class="font-bold!" />
            </div>
          </form>
        </div>
      </template>
    </UModal>
  </div>
</template>
