<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

const items = ref<any[]>([])
const insumos = ref<any[]>([])
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

async function fetchInventory() {
  loading.value = true
  try {
    const officeId = String(auth.effectiveOfficeId ?? auth.officeId ?? 0)
    
    const [resMp, resInsumos] = await Promise.all([
      $fetch<any>(apiBase, {
        method: 'POST',
        body: new URLSearchParams({
          getLabMaterials: 'ok',
          id_office: officeId,
          is_insumo: '0'
        }),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      }),
      $fetch<any>(`/api/lab_supplies?linkTo=id_office_supply&equalTo=${officeId}`, {
        headers: { 'Authorization': 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }
      })
    ])

    const dataMp = typeof resMp === 'string' ? JSON.parse(resMp) : resMp
    if (dataMp.status === 200) {
      items.value = dataMp.results.map((m: any) => ({
        id: m.id_raw_material,
        name: m.name_raw_material,
        stock: parseFloat(m.stock_raw_material) || 0,
        unit: m.unit_raw_material,
        is_insumo: m.is_insumo || 0
      }))
    } else {
      items.value = []
    }

    if (resInsumos.status === 200) {
      insumos.value = resInsumos.results.filter((s: any) => parseInt(s.status_supply) === 1).map((m: any) => ({
        id: m.id_supply,
        name: decodeURIComponent(m.name_supply || '').replace(/\+/g, ' '),
        stock: parseFloat(m.stock_supply) || 0,
        unit: m.unit_supply,
      }))
    } else {
      insumos.value = []
    }
    
  } catch (error) {
    console.error('Error fetching inventory stock:', error)
    items.value = []
    insumos.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchInventory()
})

const activeTab = ref('mp')
</script>

<template>
  <div class="space-y-6">
    <div class="bg-white border border-slate-200 p-6 rounded-2xl shadow-sm">
      <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
        <UIcon
          name="i-lucide-package"
          class="text-indigo-500"
        />
        Inventario MP e Insumos
      </h1>
      <p class="text-slate-500 text-sm mt-1">
        Existencias físicas de materias primas e insumos registrados en el laboratorio.
      </p>
    </div>

    <div class="flex gap-4 border-b border-slate-200 pb-2">
      <button
        @click="activeTab = 'mp'"
        :class="activeTab === 'mp' ? 'text-indigo-600 border-indigo-600 font-bold' : 'text-slate-500 border-transparent hover:text-slate-700'"
        class="pb-2 px-1 border-b-2 transition-colors duration-150"
      >
        Materia Prima
      </button>
      <button
        @click="activeTab = 'insumos'"
        :class="activeTab === 'insumos' ? 'text-indigo-600 border-indigo-600 font-bold' : 'text-slate-500 border-transparent hover:text-slate-700'"
        class="pb-2 px-1 border-b-2 transition-colors duration-150"
      >
        Insumos
      </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div class="p-5 border-b border-slate-200">
        <h3 class="font-bold text-slate-800 tracking-wide">
          Lista de Existencias - {{ activeTab === 'mp' ? 'Materia Prima' : 'Insumos' }}
        </h3>
      </div>
      
      <div class="overflow-x-auto">
        <div v-if="loading" class="p-8 text-center text-slate-500">
          <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-indigo-500 mb-2" />
          Cargando existencias desde la base de datos...
        </div>
        
        <div v-else-if="activeTab === 'mp'">
          <div v-if="items.length === 0" class="text-center p-8 text-slate-500">
            No hay materias primas registradas en el inventario.
          </div>
          <table v-else class="w-full text-left text-sm text-slate-700">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
              <tr>
                <th class="px-6 py-4">Materia Prima</th>
                <th class="px-6 py-4 text-right">Cantidad Disponible</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50/55 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                    {{ item.name }}
                  </div>
                </td>
                <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 text-base">
                  {{ item.stock.toFixed(2) }} <span class="text-xs text-slate-500 font-sans ml-1">{{ item.unit }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else-if="activeTab === 'insumos'">
          <div v-if="insumos.length === 0" class="text-center p-8 text-slate-500">
            No hay insumos registrados en el inventario.
          </div>
          <table v-else class="w-full text-left text-sm text-slate-700">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
              <tr>
                <th class="px-6 py-4">Insumo</th>
                <th class="px-6 py-4 text-right">Cantidad Disponible</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in insumos" :key="item.id" class="hover:bg-slate-50/55 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                    {{ item.name }}
                  </div>
                </td>
                <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 text-base">
                  {{ item.stock.toFixed(2) }} <span class="text-xs text-slate-500 font-sans ml-1">{{ item.unit }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</template>
