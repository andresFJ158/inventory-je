<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { ref, computed, onMounted } from 'vue'

const auth = useAuthStore()

// State del Dashboard compartidos
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

// ----------------------------------------------------
// STATE: LAB
// ----------------------------------------------------
const totalMaterials = ref(0)
const totalInProcess = ref(0)
const qualityChecks = ref(0)
const finalProductsStock = ref(0)
const recentActivities = ref<any[]>([])

// ----------------------------------------------------
// STATE: CAJERO / ADMIN / SUPERADMIN
// ----------------------------------------------------
const todaysOrders = ref<any[]>([])
const todaysSalesAmount = ref(0)
const todaysCashStatus = ref<any>(null)
const openCashRegisters = ref<any[]>([]) // For Superadmin

// ----------------------------------------------------
// STATE: DESPACHADOR
// ----------------------------------------------------
const pendingRequests = ref<any[]>([])

const todayStr = new Date().toISOString().split('T')[0]
const apiHeaders = { Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }

// Computed Role Flags
const role = auth.role
const isSuperAdmin = role === 'superadmin' || role === 'admin'
const isCashier = role === 'cajero' || role === 'vendedor'
const isDispatcher = role === 'despachador'
const isLab = role === 'lab_admin' || role === 'lab_worker' || (!isSuperAdmin && !isCashier && !isDispatcher)

async function fetchDashboardMetrics() {
  loading.value = true
  try {
    if (isLab) {
      await fetchLabMetrics()
    }
    
    if (isSuperAdmin || isCashier) {
      await fetchSalesMetrics()
    }
    
    if (isDispatcher) {
      await fetchDispatcherMetrics()
    }

  } catch (error) {
    console.error('Error fetching dashboard metrics:', error)
  } finally {
    loading.value = false
  }
}

async function fetchLabMetrics() {
  const response = await $fetch<any>(apiBase, {
    method: 'POST',
    body: new URLSearchParams({
      getLabDashboardMetrics: 'ok',
      id_office: String(auth.officeId || 6)
    }),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  })

  const data = typeof response === 'string' ? JSON.parse(response) : response
  if (data.status === 200) {
    totalMaterials.value = parseInt(data.results.totalMaterials) || 0
    totalInProcess.value = parseInt(data.results.totalInProcess) || 0
    qualityChecks.value = parseInt(data.results.qualityChecks) || 0
    finalProductsStock.value = parseFloat(data.results.finalProductsStock) || 0

    if (data.results.recentActivity) {
      recentActivities.value = data.results.recentActivity.slice(0, 4).map((p: any) => {
        let status = 'info'
        let actionMsg = ''
        if (p.status_production === 'completado') {
          status = 'success'
          actionMsg = 'Completó producción de lote #' + p.id_production + ' (' + p.name_product + ')'
        } else if (p.status_production === 'proceso' || p.status_production === 'en_proceso') {
          status = 'warning'
          actionMsg = 'Inició producción del lote #' + p.id_production
        } else {
          actionMsg = 'Lote #' + p.id_production + ' registrado como pendiente'
        }
        return {
          user: p.name_admin || 'Operador',
          action: actionMsg,
          time: p.date_updated_production,
          status
        }
      })
    }
  }
}

async function fetchSalesMetrics() {
  // Fetch orders for today
  let orderUrl = `/api/relations?rel=orders,clients&type=order,client&linkTo=date_created_order&equalTo=${todayStr}&orderBy=id_order&orderMode=DESC`
  if (!isSuperAdmin) {
    orderUrl = `/api/relations?rel=orders,clients&type=order,client&linkTo=id_office_order,date_created_order&equalTo=${auth.officeId},${todayStr}&orderBy=id_order&orderMode=DESC`
  }
  
  const orderRes = await $fetch<any>(orderUrl, { headers: apiHeaders }).catch(() => null)
  if (orderRes && orderRes.status === 200 && orderRes.results) {
    todaysOrders.value = orderRes.results
    todaysSalesAmount.value = todaysOrders.value.reduce((acc, o) => acc + parseFloat(o.total_order || 0), 0)
  }

  // Fetch cash register status today
  if (!isSuperAdmin) {
    const cashUrl = `/api/cashs?linkTo=id_office_cash,date_created_cash&equalTo=${auth.officeId},${todayStr}&orderBy=id_cash&orderMode=DESC`
    const cashRes = await $fetch<any>(cashUrl, { headers: apiHeaders }).catch(() => null)
    if (cashRes && cashRes.status === 200 && cashRes.results && cashRes.results.length > 0) {
      todaysCashStatus.value = cashRes.results[0]
    }
  } else {
    // Superadmin: open cash registers
    const allCashUrl = `/api/relations?rel=cashs,offices&type=cash,office&linkTo=status_cash,date_created_cash&equalTo=1,${todayStr}`
    const allCashRes = await $fetch<any>(allCashUrl, { headers: apiHeaders }).catch(() => null)
    if (allCashRes && allCashRes.status === 200 && allCashRes.results) {
      openCashRegisters.value = allCashRes.results
    }
  }
}

async function fetchDispatcherMetrics() {
  const reqRes = await $fetch<any>('/ajax/pos.ajax.php', {
    method: 'POST',
    body: new URLSearchParams({
      getPendingRequests: 'true',
      id_admin: String(auth.user?.id_admin || 1)
    }),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  
  const parsed = typeof reqRes === 'string' ? JSON.parse(reqRes) : reqRes
  if (Array.isArray(parsed)) {
    pendingRequests.value = parsed
  }
}

onMounted(() => {
  fetchDashboardMetrics()
})

const kpis = computed(() => [
  { label: 'Materias Primas', val: String(totalMaterials.value), icon: 'i-lucide-droplet', color: 'text-blue-500 bg-blue-500/10' },
  { label: 'En Proceso', val: String(totalInProcess.value), icon: 'i-lucide-cog', color: 'text-amber-500 bg-amber-500/10' },
  { label: 'Ensayos Calidad', val: String(qualityChecks.value), icon: 'i-lucide-shield-check', color: 'text-emerald-500 bg-emerald-500/10' },
  { label: 'Productos Finales', val: `${finalProductsStock.value} u.`, icon: 'i-lucide-boxes', color: 'text-indigo-500 bg-indigo-500/10' }
])

function formatCurrency(val: number) {
  return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(val)
}
</script>

<template>
  <div class="space-y-6">

    <!-- Vista: LAB -->
    <template v-if="isLab">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div 
          v-for="kpi in kpis" 
          :key="kpi.label" 
          class="bg-white dark:bg-slate-950/40 backdrop-blur border border-slate-200 dark:border-slate-800/80 p-5 rounded-xl flex items-center justify-between shadow-sm"
        >
          <div>
            <span class="text-xs text-slate-500 uppercase tracking-wider font-bold">{{ kpi.label }}</span>
            <h4 class="text-2xl font-black mt-1">{{ kpi.val }}</h4>
          </div>
          <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', kpi.color]">
            <UIcon :name="kpi.icon" class="w-6 h-6" />
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-950/40 border p-6 rounded-xl space-y-4 shadow-sm">
        <h3 class="font-bold flex items-center gap-2">
          <UIcon name="i-lucide-activity" class="text-green-500" /> Actividad Reciente en Planta
        </h3>
        <div v-if="recentActivities.length === 0" class="text-sm text-center py-4">No hay datos</div>
        <!-- (omitted for brevity, list existing activities) -->
      </div>
    </template>

    <!-- Vista: CAJERO / VENDEDOR -->
    <template v-else-if="isCashier">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <UCard class="bg-gradient-to-r from-green-500 to-green-600 text-white border-0 shadow-lg">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-green-100 text-sm font-semibold uppercase">Ventas de Hoy</p>
              <h2 class="text-3xl font-black mt-1">{{ formatCurrency(todaysSalesAmount) }}</h2>
              <p class="text-sm mt-1">{{ todaysOrders.length }} órdenes procesadas</p>
            </div>
            <UIcon name="i-lucide-banknote" class="w-12 h-12 text-white/50" />
          </div>
        </UCard>

        <UCard>
          <div class="flex justify-between items-center h-full">
            <div>
              <p class="text-slate-500 text-sm font-semibold uppercase">Estado de Caja</p>
              <div v-if="loading" class="mt-2 text-slate-400">Verificando...</div>
              <div v-else-if="!todaysCashStatus || todaysCashStatus.status_cash == 0" class="mt-1">
                <span class="text-2xl font-black text-rose-500">CERRADA</span>
              </div>
              <div v-else class="mt-1">
                <span class="text-2xl font-black text-emerald-500">ABIERTA</span>
                <p class="text-xs mt-1 text-slate-500">Apertura: {{ formatCurrency(parseFloat(todaysCashStatus.opening_balance_cash)) }}</p>
              </div>
            </div>
            <UIcon name="i-lucide-wallet" :class="(!todaysCashStatus || todaysCashStatus.status_cash == 0) ? 'w-10 h-10 text-rose-200' : 'w-10 h-10 text-emerald-200'" />
          </div>
        </UCard>

        <UCard class="flex items-center justify-center cursor-pointer hover:bg-slate-50 transition-colors" @click="navigateTo('/pos')">
          <div class="text-center">
            <UIcon name="i-lucide-monitor-smartphone" class="w-10 h-10 text-blue-500 mx-auto mb-2" />
            <span class="font-bold text-lg text-slate-800">Ir al Punto de Venta (POS)</span>
          </div>
        </UCard>
      </div>

      <UCard>
        <template #header><h3 class="font-bold border-b pb-2">Últimas Ventas (Hoy)</h3></template>
        <div v-if="todaysOrders.length === 0" class="text-center text-slate-500 py-4">No se han registrado ventas hoy.</div>
        <UTable v-else :data="todaysOrders.slice(0, 5)" :columns="[{accessorKey: 'transaction_order', header: 'ID Transacción'}, {accessorKey: 'client', header: 'Cliente'}, {accessorKey: 'total_order', header: 'Total'}]">
          <template #client-cell="{ row }">{{ decodeURIComponent(row.original.name_client).replace(/\+/g, ' ') }}</template>
          <template #total_order-cell="{ row }"><span class="font-bold text-green-600">{{ formatCurrency(parseFloat(row.original.total_order)) }}</span></template>
        </UTable>
      </UCard>
    </template>

    <!-- Vista: SUPERADMIN / ADMIN -->
    <template v-else-if="isSuperAdmin">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <UCard>
          <p class="text-slate-500 text-sm font-bold uppercase">Ventas Globales (Hoy)</p>
          <h2 class="text-2xl font-black text-green-600 mt-1">{{ formatCurrency(todaysSalesAmount) }}</h2>
          <p class="text-xs text-slate-400 mt-1">{{ todaysOrders.length }} transacciones en toda la red</p>
        </UCard>
        <UCard>
          <p class="text-slate-500 text-sm font-bold uppercase">Cajas Abiertas</p>
          <h2 class="text-2xl font-black text-blue-600 mt-1">{{ openCashRegisters.length }}</h2>
          <p class="text-xs text-slate-400 mt-1">sucursales operando activamente</p>
        </UCard>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <UCard>
          <template #header>
            <div class="flex justify-between items-center">
              <h3 class="font-bold">Sucursales Activas</h3>
              <UButton to="/reportes" size="xs" color="neutral" variant="ghost" icon="i-lucide-external-link">Ver Reportes</UButton>
            </div>
          </template>
          <div v-if="openCashRegisters.length === 0" class="text-slate-500 text-center py-4">No hay cajas abiertas.</div>
          <UTable v-else :data="openCashRegisters" :columns="[{accessorKey: 'title_office', header: 'Sucursal'}, {accessorKey: 'opening_balance_cash', header: 'Saldo Inicial'}]">
            <template #title_office-cell="{ row }"><span class="font-semibold">{{ decodeURIComponent(row.original.title_office).replace(/\+/g, ' ') }}</span></template>
            <template #opening_balance_cash-cell="{ row }">{{ formatCurrency(parseFloat(row.original.opening_balance_cash)) }}</template>
          </UTable>
        </UCard>
        
        <UCard>
          <template #header><h3 class="font-bold">Últimas Transacciones Globales</h3></template>
          <div v-if="todaysOrders.length === 0" class="text-slate-500 text-center py-4">Sin datos de ventas.</div>
          <UTable v-else :data="todaysOrders.slice(0, 5)" :columns="[{accessorKey: 'date_order', header: 'Fecha/Hora'}, {accessorKey: 'total_order', header: 'Monto'}]">
            <template #date_order-cell="{ row }"><span class="text-xs text-slate-500">{{ row.original.date_order.split(' ')[1] }}</span></template>
            <template #total_order-cell="{ row }"><span class="font-semibold text-green-600">{{ formatCurrency(parseFloat(row.original.total_order)) }}</span></template>
          </UTable>
        </UCard>
      </div>
    </template>

    <!-- Vista: DESPACHADOR -->
    <template v-else-if="isDispatcher">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <UCard>
          <div class="flex justify-between items-center">
            <div>
              <p class="text-amber-600 text-sm font-bold uppercase">Solicitudes Pendientes</p>
              <h2 class="text-4xl font-black mt-1">{{ pendingRequests.length }}</h2>
            </div>
            <UIcon name="i-lucide-clipboard-copy" class="w-12 h-12 text-amber-100" />
          </div>
          <UButton to="/despachos" class="w-full mt-4 justify-center" color="warning">Atender Solicitudes</UButton>
        </UCard>
      </div>
    </template>

  </div>
</template>
