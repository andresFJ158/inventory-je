<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

// State
const products = ref<any[]>([])
const admins = ref<any[]>([])
const officesMap = ref<Record<string, string>>({})
const assignedMap = ref<Record<string, number>>({})
const subWarehouses = ref<any[]>([])
const movements = ref<any[]>([])

const loadingStock = ref(true)
const loadingSubs = ref(true)
const loadingMoves = ref(true)
const activeTab = ref(0)

// Assign Dialog
const isAssignOpen = ref(false)
const selectedProduct = ref<any>(null)
const assignUser = ref('')
const assignQty = ref(1)
const assignNotes = ref('')
const processingAction = ref(false)

const apiHeaders = {
  Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
}

// Fetch general resources
async function fetchAdmins() {
  try {
    const data = await $fetch<any>('/api/admins?linkTo=status_admin&equalTo=1', {
      headers: apiHeaders
    })
    if (data.status === 200) {
      // Filter out superadmins/dispatchers to only show sellers/cajeros/editors for sub-warehouse assignments
      admins.value = (data.results || []).filter((a: any) => a.rol_admin !== 'superadmin')
    }
  } catch (e) {
    console.error('Error fetching admins:', e)
  }
}

async function fetchOffices() {
  try {
    const data = await $fetch<any>('/api/offices', {
      headers: apiHeaders
    })
    if (data.status === 200 && data.results) {
      const map: Record<string, string> = {}
      data.results.forEach((o: any) => {
        map[o.id_office] = decodeURIComponent(o.title_office).replace(/\+/g, ' ')
      })
      officesMap.value = map
    }
  } catch (e) {
    console.error('Error fetching offices:', e)
  }
}

// Fetch Inventory Stock (Tab 1)
async function fetchStock() {
  loadingStock.value = true
  try {
    const officeId = auth.officeId || 3
    
    // 1. Fetch products inventory of this office
    const prodData = await $fetch<any>(`/api/relations?rel=products,product_inventory&type=product,inventory&linkTo=id_office_inventory,status_inventory&equalTo=${officeId},1`, {
      headers: apiHeaders
    })
    
    products.value = (prodData.status === 200 && prodData.results) ? prodData.results : []

    // 2. Fetch assigned quantities (to sub-warehouses)
    const adminId = auth.user?.id_admin || 1
    const assignData = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getAssignedByOffice: 'true',
        id_office: String(officeId),
        id_dispatcher: String(adminId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const assignResults = typeof assignData === 'string' ? JSON.parse(assignData) : assignData
    const map: Record<string, number> = {}
    if (Array.isArray(assignResults)) {
      assignResults.forEach((item: any) => {
        map[item.id_product] = parseInt(item.total_assigned) || 0
      })
    }
    assignedMap.value = map
  } catch (e) {
    console.error('Error fetching warehouse stock:', e)
    products.value = []
  } finally {
    loadingStock.value = false
  }
}

// Fetch Sub-Warehouses (Tab 2)
async function fetchSubWarehouses() {
  loadingSubs.value = true
  try {
    const officeId = auth.officeId || 3
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getSubWarehousesDetail: 'true',
        id_office: String(officeId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    subWarehouses.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Error fetching sub-warehouses:', e)
    subWarehouses.value = []
  } finally {
    loadingSubs.value = false
  }
}

// Fetch Movements (Tab 3)
async function fetchMovements() {
  loadingMoves.value = true
  try {
    const officeId = auth.officeId || 3
    const adminId = auth.user?.id_admin || 1
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getWarehouseMovements: 'true',
        id_office: String(officeId),
        id_dispatcher: String(adminId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    movements.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Error fetching movements:', e)
    movements.value = []
  } finally {
    loadingMoves.value = false
  }
}

onMounted(async () => {
  await fetchOffices()
  await fetchAdmins()
  await fetchStock()
  await fetchSubWarehouses()
  await fetchMovements()
})

// Action: Open Assign Modal
function startAssign(prod: any) {
  selectedProduct.value = prod
  assignQty.value = 1
  assignUser.value = ''
  assignNotes.value = ''
  isAssignOpen.value = true
}

// Action: Confirm Assign
async function confirmAssign() {
  if (!selectedProduct.value || !assignUser.value) {
    alert('Por favor completa todos los campos requeridos.')
    return
  }
  
  const availableStock = parseFloat(selectedProduct.value.stock_inventory) || 0
  if (assignQty.value <= 0 || assignQty.value > availableStock) {
    alert('La cantidad ingresada no es válida o supera el stock disponible.')
    return
  }

  processingAction.value = true
  try {
    const officeId = auth.officeId || 3
    const adminId = auth.user?.id_admin || 1

    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        assignToSubWarehouse: 'true',
        id_product: String(selectedProduct.value.id_product),
        id_admin_dest: assignUser.value,
        qty: String(assignQty.value),
        notes: assignNotes.value,
        id_office: String(officeId),
        id_dispatched_by: String(adminId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    if (String(res).trim() === 'ok') {
      isAssignOpen.value = false
      await fetchStock()
      await fetchSubWarehouses()
      await fetchMovements()
    } else {
      alert(res || 'Error al asignar stock.')
    }
  } catch (e) {
    console.error('Assign error:', e)
    alert('Error al conectar con la API de almacenes.')
  } finally {
    processingAction.value = false
  }
}

const tabsItems = [
  { label: 'Inventario Principal', icon: 'i-lucide-boxes' },
  { label: 'Sub-Almacenes', icon: 'i-lucide-users' },
  { label: 'Movimientos', icon: 'i-lucide-arrow-right-left' }
]
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-slate-900/60 border border-slate-800 p-6 rounded-xl flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight bg-gradient-to-r from-teal-400 to-emerald-300 bg-clip-text text-transparent">
          Almacén Principal
        </h1>
        <p class="text-xs text-slate-400 mt-1">
          Controla las existencias en almacén y distribuye a los sub-almacenes de tus vendedores.
        </p>
      </div>

      <UButton
        icon="i-lucide-refresh-cw"
        color="neutral"
        variant="soft"
        size="xs"
        @click="activeTab === 0 ? fetchStock() : activeTab === 1 ? fetchSubWarehouses() : fetchMovements()"
      >
        Refrescar
      </UButton>
    </div>

    <!-- Tabs Layout -->
    <UTabs :items="tabsItems" v-model="activeTab" class="w-full">
      <template #item="{ index }">
        <!-- TAB 0: Main Stock Inventory -->
        <div v-if="index === 0" class="mt-4">
          <div v-if="loadingStock" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="products.length === 0" class="text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500">
            No se encontraron productos en el almacén principal.
          </div>

          <div v-else class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse text-sm text-slate-300">
              <thead>
                <tr class="bg-slate-950 text-slate-400 border-b border-slate-800">
                  <th class="p-4">Imagen</th>
                  <th class="p-4">SKU</th>
                  <th class="p-4">Producto</th>
                  <th class="p-4">Stock Total</th>
                  <th class="p-4">Asignado</th>
                  <th class="p-4">Disponible (Almacén)</th>
                  <th class="p-4 text-right">Distribución</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="prod in products" :key="prod.id_product" class="border-b border-slate-850 hover:bg-slate-900/20">
                  <td class="p-4">
                    <UAvatar
                      :src="prod.img_product ? decodeURIComponent(prod.img_product).replace(/\+/g, ' ') : '/views/assets/img/multimedia.png'"
                      size="sm"
                      class="bg-slate-800"
                    />
                  </td>
                  <td class="p-4 font-mono text-xs">{{ prod.sku_product }}</td>
                  <td class="p-4 font-semibold text-white">{{ decodeURIComponent(prod.title_product || '').replace(/\+/g, ' ') }}</td>
                  
                  <!-- Total Stock = Main + Assigned -->
                  <td class="p-4">
                    <UBadge color="success" variant="soft" class="font-mono text-xs">
                      {{ (parseFloat(prod.stock_inventory) || 0) + (assignedMap[prod.id_product] || 0) }}
                    </UBadge>
                  </td>

                  <!-- Assigned Stock -->
                  <td class="p-4">
                    <UBadge color="info" variant="soft" class="font-mono text-xs">
                      {{ assignedMap[prod.id_product] || 0 }}
                    </UBadge>
                  </td>

                  <!-- Available Stock (In main warehouse office) -->
                  <td class="p-4">
                    <UBadge color="primary" variant="solid" class="font-mono text-xs">
                      {{ parseFloat(prod.stock_inventory) || 0 }}
                    </UBadge>
                  </td>

                  <!-- Manual Stock Assignment Button -->
                  <td class="p-4 text-right">
                    <UButton
                      color="teal"
                      icon="i-lucide-share-2"
                      size="xs"
                      :disabled="(parseFloat(prod.stock_inventory) || 0) <= 0"
                      @click="startAssign(prod)"
                    >
                      Asignar Stock
                    </UButton>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 1: Sub-Warehouses Detailed -->
        <div v-if="index === 1" class="mt-4">
          <div v-if="loadingSubs" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="subWarehouses.length === 0" class="text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500">
            No se encontraron sub-almacenes asociados.
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="sw in subWarehouses"
              :key="sw.id_sub_warehouse"
              class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden flex flex-col justify-between"
            >
              <!-- Card Header -->
              <div class="p-4 bg-slate-950/60 border-b border-slate-850 flex justify-between items-center">
                <div>
                  <h3 class="font-bold text-white text-sm flex items-center gap-1.5">
                    <UIcon name="i-lucide-user" class="text-teal-400" />
                    {{ sw.name_admin }}
                  </h3>
                  <span class="text-[10px] text-slate-400 block mt-0.5">
                    Sucursal: {{ sw.title_office ? decodeURIComponent(sw.title_office).replace(/\+/g, ' ') : 'Sin Sucursal' }}
                  </span>
                </div>
                <UBadge color="emerald" size="xs">{{ sw.name_sub_warehouse }}</UBadge>
              </div>

              <!-- Card Body (Table of products in this sub-warehouse) -->
              <div class="p-0">
                <table v-if="sw.products && sw.products.length > 0" class="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr class="bg-slate-950/30 text-slate-400 border-b border-slate-850">
                      <th class="p-2.5">Producto</th>
                      <th class="p-2.5 text-right">Stock Asignado</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="p in sw.products" :key="p.title_product" class="border-b border-slate-850/40 hover:bg-slate-900/10">
                      <td class="p-2.5 text-slate-300">
                        {{ decodeURIComponent(p.title_product || '').replace(/\+/g, ' ') }}
                      </td>
                      <td class="p-2.5 text-right font-mono font-bold text-teal-400">
                        {{ p.stock }}
                      </td>
                    </tr>
                  </tbody>
                </table>
                <div v-else class="text-center py-6 text-slate-500 text-xs">
                  Sin productos asignados a este sub-almacén.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: Warehouse Movements Log -->
        <div v-if="index === 2" class="mt-4">
          <div v-if="loadingMoves" class="flex justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-teal-500" />
          </div>

          <div v-else-if="movements.length === 0" class="text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500">
            No se encontraron movimientos registrados en la bitácora.
          </div>

          <div v-else class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse text-sm text-slate-300">
              <thead>
                <tr class="bg-slate-950 text-slate-400 border-b border-slate-800">
                  <th class="p-4">Fecha</th>
                  <th class="p-4">Tipo</th>
                  <th class="p-4">Producto</th>
                  <th class="p-4 text-center">Cant.</th>
                  <th class="p-4">Destinatario</th>
                  <th class="p-4">Sucursal Destino</th>
                  <th class="p-4">Despachador</th>
                  <th class="p-4">Notas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="m in movements" :key="m.date_created_assignment" class="border-b border-slate-850 hover:bg-slate-900/20">
                  <td class="p-4 font-mono text-xs">{{ m.date_created_assignment }}</td>
                  <td class="p-4">
                    <UBadge
                      :color="m.type_assignment === 'despacho' ? 'indigo' : m.type_assignment === 'devolucion' ? 'warning' : 'rose'"
                      variant="subtle"
                      class="capitalize font-semibold"
                    >
                      {{ m.type_assignment }}
                    </UBadge>
                  </td>
                  <td class="p-4 font-semibold text-white">{{ decodeURIComponent(m.title_product || '').replace(/\+/g, ' ') }}</td>
                  <td class="p-4 text-center font-mono font-bold">{{ m.qty_assignment }}</td>
                  <td class="p-4">{{ m.name_admin }}</td>
                  <td class="p-4">{{ m.office_name ? decodeURIComponent(m.office_name).replace(/\+/g, ' ') : '-' }}</td>
                  <td class="p-4 text-xs">{{ m.dispatcher_name }}</td>
                  <td class="p-4 text-xs italic">{{ m.notes_assignment || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </UTabs>

    <!-- Assign Modal -->
    <UModal v-model:open="isAssignOpen" title="Asignar Stock a Vendedor">
      <template #body>
        <div v-if="selectedProduct" class="space-y-4">
          <div class="bg-slate-950 p-3 rounded-lg border border-slate-850 flex justify-between items-center">
            <div>
              <span class="text-[10px] text-slate-500 block">Producto:</span>
              <span class="text-xs font-bold text-white">{{ decodeURIComponent(selectedProduct.title_product).replace(/\+/g, ' ') }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 block text-right">Disponible en Almacén:</span>
              <span class="text-sm font-bold text-teal-400 font-mono block text-right">
                {{ parseFloat(selectedProduct.stock_inventory) || 0 }} u
              </span>
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1">Destinatario (Vendedor/Cajero) *</label>
            <USelect
              v-model="assignUser"
              :options="admins.map(a => ({ value: String(a.id_admin), label: `${a.name_admin} (${officesMap[a.id_office_admin] || 'Sin Sucursal'})` }))"
              placeholder="Seleccionar destinatario..."
              class="w-full"
              required
            />
          </div>

          <div>
            <label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1">Cantidad a Asignar *</label>
            <UInput
              v-model.number="assignQty"
              type="number"
              min="1"
              :max="parseFloat(selectedProduct.stock_inventory) || 0"
              class="w-full"
              required
            />
          </div>

          <div>
            <label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1">Notas de Asignación</label>
            <UTextarea v-model="assignNotes" rows="2" placeholder="Opcional..." class="w-full" />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <UButton color="neutral" variant="ghost" @click="isAssignOpen = false">Cancelar</UButton>
            <UButton color="primary" :loading="processingAction" @click="confirmAssign">Realizar Asignación</UButton>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>
