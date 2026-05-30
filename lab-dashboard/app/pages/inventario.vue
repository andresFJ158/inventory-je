<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

const items = ref<any[]>([])
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

async function fetchInventory() {
  loading.value = true
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
      items.value = data.results.map((m: any) => ({
        id: m.id_raw_material,
        name: m.name_raw_material,
        stock: parseFloat(m.stock_raw_material) || 0,
        unit: m.unit_raw_material
      }))
    } else {
      items.value = []
    }
  } catch (error) {
    console.error('Error fetching inventory stock:', error)
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchInventory()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm">
      <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
        <UIcon
          name="i-lucide-package"
          class="text-green-500"
        />
        Inventario de Materia Prima
      </h1>
      <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
        Existencias físicas de insumos químicos registradas en el laboratorio.
      </p>
    </div>

    <!-- Lista Minimalista -->
    <div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm">
      <div class="p-5 border-b border-slate-200 dark:border-slate-800/80">
        <h3 class="font-bold text-slate-800 dark:text-white tracking-wide">
          Lista de Existencias
        </h3>
      </div>
      
      <div class="overflow-x-auto">
        <div v-if="loading" class="p-8 text-center text-slate-500 dark:text-slate-400">
          <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2" />
          Cargando existencias desde la base de datos...
        </div>
        <div v-else-if="items.length === 0" class="text-center p-8 text-slate-500">
          No hay insumos registrados en el inventario.
        </div>
        
        <table v-else class="w-full text-left text-sm text-slate-650 dark:text-slate-350">
          <thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase tracking-wider text-slate-550 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800/80">
            <tr>
              <th class="px-6 py-4">Insumo / Materia Prima</th>
              <th class="px-6 py-4 text-right">Cantidad Disponible</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
            <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50/55 dark:hover:bg-slate-800/10 transition-colors duration-150">
              <td class="px-6 py-4 font-bold text-slate-800 dark:text-white uppercase tracking-wide">
                {{ item.name }}
              </td>
              <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-slate-100 text-base">
                {{ item.stock.toFixed(2) }} <span class="text-xs text-slate-500 dark:text-slate-400 font-sans ml-1">{{ item.unit }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
