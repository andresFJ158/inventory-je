<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

const entries = ref<any[]>([])
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

// Estado para el modal de Crear Entrada
const isCreateOpen = ref(false)
const materials = ref<any[]>([])
const loadingMaterials = ref(false)

const newEntry = ref({
  id_raw_material: '',
  qty: '',
  lot_number: '',
  supplier: '',
  date: new Date().toISOString().split('T')[0]
})

const selectedMaterial = computed(() => {
  return materials.value.find(m => String(m.id_raw_material) === String(newEntry.value.id_raw_material))
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

async function fetchEntries() {
  loading.value = true
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabEntries: 'ok',
        id_office: String(auth.officeId || 6)
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      entries.value = data.results.map((e: any) => ({
        id: e.id_entry,
        doc: e.lot_number_entry || ('ENT-' + e.id_entry),
        item: e.name_raw_material || 'Materia Prima ID: ' + e.id_raw_material_entry,
        qty: parseFloat(e.qty_entry) || 0,
        unit: e.unit_raw_material || 'u',
        date: e.date_created_entry || e.date_entry,
        status: e.status_entry || 'pendiente',
        id_raw_material: e.id_raw_material_entry
      }))
    } else {
      entries.value = []
    }
  } catch (error) {
    console.error('Error fetching entries:', error)
    entries.value = []
  } finally {
    loading.value = false
  }
}

const isPriceModalOpen = ref(false)
const selectedEntryForApproval = ref<any>(null)
const approvalPrice = ref(0)

const priceLabel = computed(() => {
  const unit = selectedEntryForApproval.value?.unit
  if (!unit) return 'Precio de Compra (Bs)'
  const uLower = unit.toLowerCase()
  if (uLower === 'kg') return 'Precio por Kilogramo (Bs/kg)'
  if (uLower === 'g') return 'Precio por Gramo (Bs/g)'
  if (uLower === 'l') return 'Precio por Litro (Bs/L)'
  if (uLower === 'ml') return 'Precio por Mililitro (Bs/ml)'
  if (uLower === 'und' || uLower === 'u') return 'Precio por Unidad (Bs/und)'
  return `Precio por ${unit} (Bs/${unit})`
})

function confirmEntry(entry: any) {
  if (auth.role !== 'lab_admin') {
    alert('Solo el administrador puede aprobar y asignar precios.')
    return
  }
  selectedEntryForApproval.value = entry
  approvalPrice.value = 0
  isPriceModalOpen.value = true
}

async function submitApprovalPrice() {
  if (!selectedEntryForApproval.value) return
  const entry = selectedEntryForApproval.value
  const price = approvalPrice.value || 0
  const qty = parseFloat(entry.qty) || 0
  const total = qty * price

  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        approveRawMaterialEntry: 'ok',
        id_entry: String(entry.id),
        id_raw_material: String(entry.id_raw_material),
        qty: String(qty),
        price: String(price),
        total: String(total),
        id_admin: String(auth.user?.id_admin || 1)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    
    const resText = typeof response === 'string' ? response.trim() : JSON.stringify(response)
    if (resText === 'ok') {
      isPriceModalOpen.value = false
      await fetchEntries()
    } else {
      alert('Error al confirmar ingreso: ' + resText)
    }
  } catch (error: any) {
    console.error('Error confirming entry:', error)
    alert('Error al conectar con el servidor: ' + (error.message || error))
  }
}

async function handleOpenCreateModal() {
  await fetchMaterials()
  newEntry.value = {
    id_raw_material: '',
    qty: '',
    lot_number: '',
    supplier: '',
    date: new Date().toISOString().split('T')[0]
  }
  isCreateOpen.value = true
}

async function handleSaveEntry() {
  if (!newEntry.value.id_raw_material || !newEntry.value.qty || !newEntry.value.date) {
    alert('Completa los campos obligatorios.')
    return
  }

  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        saveLabEntry: 'ok',
        id_raw_material_entry: String(newEntry.value.id_raw_material),
        qty_entry: String(newEntry.value.qty),
        lot_number_entry: String(newEntry.value.lot_number),
        supplier_entry: String(newEntry.value.supplier),
        date_entry: String(newEntry.value.date),
        id_admin_entry: String(auth.user?.id_admin || 1)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      isCreateOpen.value = false
      await fetchEntries()
    } else {
      alert('Error al guardar la entrada de materia prima: ' + (data.results || data.message || JSON.stringify(data)))
    }
  } catch (error: any) {
    console.error('Error saving entry:', error)
    alert('Error al conectar con el servidor: ' + (error.message || error))
    isCreateOpen.value = false
  }
}

onMounted(() => {
  fetchEntries()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
          <UIcon
            name="i-lucide-truck"
            class="text-green-500"
          />
          Entradas de Materia Prima
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
          Recepción, verificación de facturas e ingreso de insumos químicos al stock del laboratorio.
        </p>
      </div>
      <UButton
        icon="i-lucide-plus"
        color="green"
        size="md"
        class="font-bold!"
        @click="handleOpenCreateModal"
      >
        Registrar Entrada
      </UButton>
    </div>

    <!-- Tabla -->
    <div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm">
      <div class="p-5 border-b border-slate-200 dark:border-slate-800/80">
        <h3 class="font-bold text-slate-800 dark:text-white tracking-wide">
          Registro de Ingresos de Proveedores
        </h3>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="p-8 text-center text-slate-500 dark:text-slate-400">
          <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto text-green-500 mb-2" />
          Cargando registros de entradas reales...
        </div>
        <div v-else-if="entries.length === 0" class="text-center p-8 text-slate-500">
          No hay entradas registradas en este laboratorio.
        </div>
        <table v-else class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
          <thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800/80">
            <tr>
              <th class="px-6 py-4">Nº Lote / Ctrl</th>
              <th class="px-6 py-4">Materia Prima</th>
              <th class="px-6 py-4">Cantidad</th>
              <th class="px-6 py-4">Fecha</th>
              <th class="px-6 py-4">Estado</th>
              <th v-if="auth.role === 'lab_admin'" class="px-6 py-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
            <tr v-for="entry in entries" :key="entry.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-all duration-150">
              <td class="px-6 py-4 font-mono font-bold text-slate-700 dark:text-slate-300">{{ entry.doc }}</td>
              <td class="px-6 py-4 font-bold text-slate-800 dark:text-white uppercase">{{ entry.item }}</td>
              <td class="px-6 py-4 font-mono font-bold text-slate-800 dark:text-slate-200">
                {{ entry.qty }} <span class="text-xs text-slate-500 dark:text-slate-400">{{ entry.unit }}</span>
              </td>
              <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ entry.date }}</td>
              <td class="px-6 py-4">
                <span v-if="entry.status === 'recibido' || entry.status === 'confirmado' || entry.status === 'aprobado'" class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-green-500/10 text-green-600 dark:text-green-300 border border-green-500/20">
                  <UIcon name="i-lucide-check-circle-2" class="w-3.5 h-3.5" /> Recibido
                </span>
                <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/20 animate-pulse">
                  <UIcon name="i-lucide-clock" class="w-3.5 h-3.5" /> Pendiente
                </span>
              </td>
              <td v-if="auth.role === 'lab_admin'" class="px-6 py-4 text-center">
                <UButton v-if="entry.status === 'pendiente'" label="Confirmar Ingreso" color="green" size="xs" class="font-bold!" @click="confirmEntry(entry)" />
                <span v-else class="text-xs text-slate-400 dark:text-slate-550 font-bold uppercase">Aprobada</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Registrar Entrada (UModal v-model:open) -->
    <UModal v-model:open="isCreateOpen">
      <template #content>
        <div class="w-full p-6 space-y-4 text-slate-900 dark:text-white">
          <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-green-600 dark:text-green-400">
            <h3 class="text-lg font-bold tracking-wide flex items-center gap-2">
              <UIcon name="i-lucide-plus" class="w-5 h-5" /> Registrar Entrada MP
            </h3>
            <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="sm" @click="isCreateOpen = false" />
          </div>

          <form class="space-y-4" @submit.prevent="handleSaveEntry">
            <!-- Materia Prima -->
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Materia Prima</label>
              <select v-model="newEntry.id_raw_material" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
                <option value="">Seleccione...</option>
                <option v-for="m in materials" :key="m.id_raw_material" :value="m.id_raw_material">
                  {{ m.name_raw_material }} ({{ m.unit_raw_material }})
                </option>
              </select>
            </div>

            <!-- Cantidad -->
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Cantidad Recibida</label>
              <div class="relative rounded-lg shadow-sm">
                <input v-model="newEntry.qty" type="number" step="0.001" :disabled="!newEntry.id_raw_material" :placeholder="newEntry.id_raw_material ? '0.00' : 'Elige MP primero'" class="block w-full py-2.5 px-3 pr-12 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-50">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500 text-sm font-bold">
                  {{ selectedMaterial?.unit_raw_material || '--' }}
                </div>
              </div>
            </div>

            <!-- Lote de Proveedor -->
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Número de Lote (Proveedor)</label>
              <input v-model="newEntry.lot_number" type="text" placeholder="Lote / Factura" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
            </div>

            <!-- Proveedor -->
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Proveedor</label>
              <input v-model="newEntry.supplier" type="text" placeholder="Nombre Proveedor" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
            </div>

            <!-- Fecha de Llegada -->
            <div>
              <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Fecha de Llegada</label>
              <input v-model="newEntry.date" type="date" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50">
            </div>

            <!-- Footer integrado directamente sin márgenes negativos -->
            <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isCreateOpen = false" />
              <UButton type="submit" label="Registrar Entrada" color="green" class="font-bold!" />
            </div>
          </form>
        </div>
      </template>
    </UModal>

    <!-- Modal Confirmar Precio de Entrada (UModal v-model:open) -->
    <UModal v-model:open="isPriceModalOpen">
      <template #content>
        <div class="w-full max-w-sm p-6 bg-white dark:bg-slate-900 text-slate-900 dark:text-white rounded-xl shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800">
          <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white tracking-wide">
              Asignar Costo de Compra
            </h3>
            <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" @click="isPriceModalOpen = false" />
          </div>

          <div class="space-y-4">
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Lote: <span class="font-bold text-slate-700 dark:text-slate-200">{{ selectedEntryForApproval?.doc }}</span> | Insumo: <span class="font-bold text-slate-700 dark:text-slate-200 uppercase">{{ selectedEntryForApproval?.item }}</span>
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Cantidad Recibida: <span class="font-bold text-slate-700 dark:text-slate-200">{{ selectedEntryForApproval?.qty }} {{ selectedEntryForApproval?.unit }}</span>
            </p>

            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ priceLabel }}</label>
              <UInput v-model="approvalPrice" type="number" step="0.01" size="md" placeholder="0.00" required />
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-6">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="isPriceModalOpen = false" />
              <UButton label="Aprobar Ingreso" color="green" class="font-bold!" @click="submitApprovalPrice" />
            </div>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>
