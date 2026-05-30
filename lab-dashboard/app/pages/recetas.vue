<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

const recipes = ref<any[]>([])
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

// Estado para el creador de receta
const isCreateOpen = ref(false)
const materials = ref<any[]>([])
const loadingMaterials = ref(false)

const newRecipe = ref({
  name_product: '',
  batch_size: '1',
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
        components: r.ingredients ? r.ingredients.map((ing: any) => ({
          name: ing.name_raw_material,
          qty: parseFloat(ing.qty_ingredient) || 0,
          unit: ing.unit_raw_material || ''
        })) : [],
        cost: parseFloat(r.rte_product) || 0.00
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
    batch_size: '1',
    unit_batch: 'L',
    ingredients: [{ id_raw: '', qty: '' }]
  }
  isCreateOpen.value = true
}

async function handleSaveRecipe() {
  if (!newRecipe.value.name_product || !newRecipe.value.batch_size) {
    alert('Por favor, ingresa el nombre de la fórmula y su cantidad base.')
    return
  }

  // Filtrar ingredientes válidos
  const validIngredients = newRecipe.value.ingredients.filter(i => i.id_raw && i.qty)
  if (validIngredients.length === 0) {
    alert('Debes agregar al menos un ingrediente válido con su cantidad.')
    return
  }

  try {
    const body = new URLSearchParams()
    body.append('saveRecipe', 'ok')
    body.append('name_product', newRecipe.value.name_product)
    body.append('batch_size', newRecipe.value.batch_size)
    body.append('unit_batch', newRecipe.value.unit_batch)
    body.append('id_office', String(auth.officeId || 6))
    body.append('id_admin', String(auth.user?.id_admin || 1))
    body.append('ingredients', JSON.stringify(validIngredients))
    body.append('labor', JSON.stringify([])) // Por defecto vacío si no se requiere mano de obra
    body.append('token', auth.token || 'session-token')

    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: body.toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    if (response.trim() === 'ok') {
      isCreateOpen.value = false
      await fetchRecipes()
    } else {
      alert('Error al guardar la receta. Es posible que el nombre ya exista en la sucursal.')
    }
  } catch (error) {
    console.error('Error saving recipe:', error)
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
        color="green"
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
        <div class="flex justify-between items-start">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-wide">
            {{ recipe.name }}
          </h3>
          <span class="px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-850 text-xs font-bold text-slate-600 dark:text-slate-300">
            Costo Promedio: <span class="text-green-600 dark:text-green-400 font-mono font-bold">${{ recipe.cost.toFixed(2) }}</span>
          </span>
        </div>

        <div class="space-y-2">
          <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest">
            Componentes
          </h4>
          <div class="space-y-1">
            <div
              v-for="comp in recipe.components"
              :key="comp.name"
              class="flex justify-between text-sm py-2 border-b border-slate-100 dark:border-slate-800/40"
            >
              <span class="text-slate-700 dark:text-slate-300 font-medium">{{ comp.name }}</span>
              <span class="font-mono font-bold text-slate-500 dark:text-slate-400">{{ comp.qty }} <span class="text-xs">{{ comp.unit }}</span> (Ratio/u)</span>
            </div>
          </div>
        </div>
      </div>
    </div>

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
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Cantidad Esperada de Producción (Rendimiento)</label>
                <input v-model="newRecipe.batch_size" type="number" step="0.01" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Unidad de Medida</label>
                <input v-model="newRecipe.unit_batch" type="text" placeholder="Ej: L, und, kg" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
              </div>
            </div>

            <!-- Ingredientes Dinámicos -->
            <div class="space-y-3 pt-2">
              <div class="flex justify-between items-center">
                <label class="block text-xs font-bold text-green-600 dark:text-green-400 uppercase tracking-wider">Insumos (Fórmula)</label>
                <UButton icon="i-lucide-plus" label="Agregar Insumo" color="green" variant="ghost" size="xs" @click="addIngredient" />
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
                  <input v-model="ing.qty" type="number" step="0.001" placeholder="0.00" class="w-24 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500">

                  <!-- Botón eliminar -->
                  <UButton icon="i-lucide-trash" color="red" variant="ghost" size="xs" @click="removeIngredient(index)" />
                </div>
              </div>
            </div>

            <!-- Footer integrado directamente sin márgenes negativos -->
            <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isCreateOpen = false" />
              <UButton type="submit" label="Guardar Receta" color="green" class="font-bold!" />
            </div>
          </form>
        </div>
      </template>
    </UModal>
  </div>
</template>
