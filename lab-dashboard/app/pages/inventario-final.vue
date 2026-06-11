<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const api = useApi()
const toast = useToast()

const items = ref<any[]>([])
const offices = ref<any[]>([])
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

const isDispatchModalOpen = ref(false)
const submittingDispatch = ref(false)
const dispatchForm = ref({
  id_product: '',
  product_name: '',
  max_qty: 0,
  qty: 1,
  id_office_dest: ''
})

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

async function fetchOffices() {
  try {
    const data = await api.rest<any>('/api/offices')
    offices.value = data.status === 200 && data.results ? data.results : []
  } catch (e) {
    console.error('Error fetching offices:', e)
  }
}

const officeOptions = computed(() => {
  const defaultOption = { value: '', label: 'Seleccionar Sucursal' }
  if (!offices.value || !Array.isArray(offices.value)) return [defaultOption]

  const filtered = offices.value.filter((o: any) => {
    const currentId = auth.officeId || 6
    return String(o.id_office) !== String(currentId)
  })

  const mapped = filtered.map((o: any) => ({
    value: String(o.id_office),
    label: String(o.title_office || o.name_office || 'Sucursal ' + o.id_office)
  }))

  return [defaultOption, ...mapped]
})

function openDispatchModal(item: any) {
  dispatchForm.value = {
    id_product: String(item.id),
    product_name: item.name,
    max_qty: parseFloat(item.stock),
    qty: 1,
    id_office_dest: ''
  }
  isDispatchModalOpen.value = true
}

async function submitDispatch() {
  if (!dispatchForm.value.id_office_dest) {
    return toast.add({ title: 'Error', description: 'Seleccione una sucursal de destino.', color: 'error' })
  }
  if (dispatchForm.value.qty <= 0 || dispatchForm.value.qty > dispatchForm.value.max_qty) {
    return toast.add({ title: 'Error', description: 'Cantidad inválida o superior al stock.', color: 'error' })
  }

  submittingDispatch.value = true
  try {
    const response = await api.ajax({
      createStockTransfer: 'true',
      id_product: dispatchForm.value.id_product,
      qty: String(dispatchForm.value.qty),
      id_admin: String(auth.id || 1),
      id_origin_office: String(auth.officeId || 6),
      id_dest_office: String(dispatchForm.value.id_office_dest),
      notes: 'Despacho directo desde Laboratorio'
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200 || data.success) {
      toast.add({ title: 'Éxito', description: 'Traspaso completado correctamente.', color: 'success' })
      isDispatchModalOpen.value = false
      fetchWarehouse()
    } else {
      toast.add({ title: 'Error', description: data.message || 'Error al completar traspaso.', color: 'error' })
    }
  } catch (e) {
    console.error('Error despachando:', e)
    toast.add({ title: 'Error', description: 'Ocurrió un error inesperado.', color: 'error' })
  } finally {
    submittingDispatch.value = false
  }
}

onMounted(() => {
  fetchWarehouse()
  fetchOffices()
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
              <th class="px-6 py-4 text-right">
                Acciones
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
              <td class="px-6 py-4 text-right font-bold text-slate-800 dark:text-slate-200">
                Bs. {{ (item.stock * item.cost).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </td>
              <td class="px-6 py-4 text-right">
                <UButton
                  icon="i-lucide-arrow-right-left"
                  color="primary"
                  variant="soft"
                  size="xs"
                  @click="openDispatchModal(item)"
                >
                  Despachar
                </UButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal de Despacho Rápido -->
    <UModal v-model:open="isDispatchModalOpen">
      <template #content>
        <div class="w-full p-6 space-y-4 text-slate-900 dark:text-white bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
          <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white tracking-wide">
              Despacho Directo de Producto Terminado
            </h3>
            <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" @click="isDispatchModalOpen = false" />
          </div>

          <form class="space-y-4" @submit.prevent="submitDispatch">
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Producto a Despachar</label>
              <UInput :model-value="dispatchForm.product_name" readonly class="opacity-70 cursor-not-allowed bg-slate-100 dark:bg-slate-900" />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Sucursal Destino *</label>
              <USelectMenu
                v-model="dispatchForm.id_office_dest"
                :items="officeOptions"
                value-key="value"
                label-key="label"
                placeholder="Seleccionar Sucursal..."
                class="w-full"
                required
              />
            </div>

            <div class="space-y-1.5">
              <div class="flex justify-between">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Cantidad a Despachar *</label>
                <span class="text-xs font-mono font-bold text-green-500">Stock Max: {{ dispatchForm.max_qty }}</span>
              </div>
              <UInput
                v-model.number="dispatchForm.qty"
                type="number"
                step="any"
                min="0.1"
                :max="dispatchForm.max_qty"
                required
              />
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isDispatchModalOpen = false" />
              <UButton type="submit" label="Confirmar Despacho" color="primary" class="font-bold!" :loading="submittingDispatch" />
            </div>
          </form>
        </div>
      </template>
    </UModal>
  </div>
</template>
