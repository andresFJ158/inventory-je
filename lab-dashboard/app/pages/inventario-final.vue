<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

const items = ref<any[]>([])
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

async function fetchWarehouse() {
  loading.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabWarehouse: 'ok',
        id_office: String(auth.officeId || 6)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      items.value = data.results.map((w: any) => ({
        id: w.id_warehouse,
        name: w.name_product || 'Producto Compuesto',
        stock: parseFloat(w.qty_warehouse) || 0,
        cost: parseFloat(w.cost_warehouse) || 0.00
      }))
    } else {
      items.value = []
    }
  } catch (error) {
    console.error('Error fetching warehouse:', error)
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchWarehouse()
})
</script>

<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm">
      <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
        <UIcon
          name="i-lucide-boxes"
          class="text-green-500"
        />
        Inventario Final
      </h1>
      <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
        Visualización de stock de productos finales compuestos y costos de manufactura.
      </p>
    </div>

    <div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm">
      <div class="p-5 border-b border-slate-200 dark:border-slate-800/80 flex justify-between items-center">
        <h3 class="font-bold text-slate-800 dark:text-white tracking-wide">
          Productos Terminados Disponibles
        </h3>
        <UButton icon="i-lucide-refresh-cw" variant="ghost" color="neutral" size="xs" @click="fetchWarehouse" />
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="p-8 text-center text-slate-500 dark:text-slate-400">
          <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2" />
          Cargando inventario final desde la base de datos...
        </div>
        <div v-else-if="items.length === 0" class="text-center p-8 text-slate-500">
          No hay productos finalizados disponibles en el Almacén Central.
        </div>
        <table v-else class="w-full text-left text-sm text-slate-650 dark:text-slate-350">
          <thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800/80">
            <tr>
              <th class="px-6 py-4">
                ID Producto
              </th>
              <th class="px-6 py-4">
                Producto Compuesto
              </th>
              <th class="px-6 py-4 text-right">
                Stock Central Disponible
              </th>
              <th class="px-6 py-4 text-right">
                Costo Real Unitario
              </th>
              <th class="px-6 py-4 text-right">
                Valoración Total
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 font-mono">
            <tr
              v-for="item in items"
              :key="item.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-all duration-150"
            >
              <td class="px-6 py-4 font-bold text-slate-500">
                #{{ item.id }}
              </td>
              <td class="px-6 py-4 font-bold text-slate-800 dark:text-white uppercase font-sans">
                {{ item.name }}
              </td>
              <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">
                {{ item.stock.toLocaleString() }} <span class="text-xs text-slate-500 font-normal">und</span>
              </td>
              <td class="px-6 py-4 text-right text-slate-700 dark:text-slate-300">
                Bs {{ item.cost.toFixed(2) }}
              </td>
              <td class="px-6 py-4 text-right font-bold text-slate-900 dark:text-white">
                Bs {{ (item.stock * item.cost).toFixed(2) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
