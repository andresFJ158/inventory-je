<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { ref, computed, onMounted } from 'vue'

function getLocalDate(): string {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
}

const auth = useAuthStore()
const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'
const apiHeaders = { Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }
const todayStr = getLocalDate()

// Role flags
const role = computed(() => auth.role)
const isSuperAdmin = computed(() => role.value === 'superadmin' || role.value === 'admin')
const isCashier = computed(() => role.value === 'cajero' || role.value === 'vendedor' || role.value === 'caja')
const isDispatcher = computed(() => role.value === 'despachador')
const isLab = computed(() => !isSuperAdmin.value && !isCashier.value && !isDispatcher.value)

// ---- LAB STATE ----
const totalMaterials = ref(0)
const totalInProcess = ref(0)
const qualityChecks = ref(0)
const finalProductsStock = ref(0)
const recentActivities = ref<any[]>([])

// ---- CAJERO / ADMIN STATE ----
const todaysOrders = ref<any[]>([])
const todaysSalesAmount = ref(0)
const todaysCashStatus = ref<any>(null)
const openCashRegisters = ref<any[]>([])

// ---- DISPATCHER STATE ----
const pendingRequests = ref<any[]>([])

async function fetchLabMetrics() {
  const response = await $fetch<any>(apiBase, {
    method: 'POST',
    body: new URLSearchParams({ getLabDashboardMetrics: 'ok', id_office: String(auth.officeId || 6) }),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  })
  const data = typeof response === 'string' ? JSON.parse(response) : response
  if (data.status === 200) {
    totalMaterials.value = parseInt(data.results.totalMaterials) || 0
    totalInProcess.value = parseInt(data.results.totalInProcess) || 0
    qualityChecks.value = parseInt(data.results.qualityChecks) || 0
    finalProductsStock.value = parseFloat(data.results.finalProductsStock) || 0
    if (data.results.recentActivity) {
      recentActivities.value = data.results.recentActivity.slice(0, 5).map((p: any) => {
        if (p.status_production === 'completado') {
          return { user: p.name_admin || 'Operador', action: `Completó producción lote #${p.id_production} (${p.name_product})`, time: p.date_updated_production, status: 'success' }
        } else if (p.status_production === 'proceso' || p.status_production === 'en_proceso') {
          return { user: p.name_admin || 'Operador', action: `Inició producción lote #${p.id_production}`, time: p.date_updated_production, status: 'warning' }
        }
        return { user: p.name_admin || 'Operador', action: `Lote #${p.id_production} registrado como pendiente`, time: p.date_updated_production, status: 'info' }
      })
    }
  }
}

async function fetchSalesMetrics() {
  let orderUrl = `/api/relations?rel=orders,clients&type=order,client&linkTo=date_created_order&equalTo=${todayStr}&orderBy=id_order&orderMode=DESC`
  if (!isSuperAdmin.value) {
    orderUrl = `/api/relations?rel=orders,clients&type=order,client&linkTo=id_office_order,date_created_order&equalTo=${auth.officeId},${todayStr}&orderBy=id_order&orderMode=DESC`
  }
  const orderRes = await $fetch<any>(orderUrl, { headers: apiHeaders }).catch(() => null)
  if (orderRes?.status === 200 && orderRes.results) {
    todaysOrders.value = orderRes.results
    todaysSalesAmount.value = todaysOrders.value.reduce((acc, o) => acc + parseFloat(o.total_order || 0), 0)
  }

  if (!isSuperAdmin.value) {
    const cashUrl = `/api/cashs?linkTo=id_office_cash,date_created_cash&equalTo=${auth.officeId},${todayStr}&orderBy=id_cash&orderMode=DESC`
    const cashRes = await $fetch<any>(cashUrl, { headers: apiHeaders }).catch(() => null)
    if (cashRes?.status === 200 && cashRes.results?.length > 0) {
      todaysCashStatus.value = cashRes.results[0]
    }
  } else {
    const allCashUrl = `/api/relations?rel=cashs,offices&type=cash,office&linkTo=status_cash,date_created_cash&equalTo=1,${todayStr}`
    const allCashRes = await $fetch<any>(allCashUrl, { headers: apiHeaders }).catch(() => null)
    if (allCashRes?.status === 200 && allCashRes.results) {
      openCashRegisters.value = allCashRes.results
    }
  }
}

async function fetchDispatcherMetrics() {
  const reqRes = await $fetch<any>(apiBase, {
    method: 'POST',
    body: new URLSearchParams({ getPendingRequests: 'true', id_admin: String(auth.user?.id_admin || 1) }),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const parsed = typeof reqRes === 'string' ? JSON.parse(reqRes) : reqRes
  if (Array.isArray(parsed)) pendingRequests.value = parsed
}

async function fetchDashboardMetrics() {
  loading.value = true
  try {
    if (isLab.value) await fetchLabMetrics()
    if (isSuperAdmin.value || isCashier.value) await fetchSalesMetrics()
    if (isDispatcher.value) await fetchDispatcherMetrics()
  } catch {}
  loading.value = false
}

onMounted(fetchDashboardMetrics)

const labKpis = computed(() => [
  { label: 'Materias Primas', val: String(totalMaterials.value), icon: 'i-lucide-droplet', color: 'text-blue-500 bg-blue-50 dark:bg-blue-500/10' },
  { label: 'En Proceso', val: String(totalInProcess.value), icon: 'i-lucide-cog', color: 'text-amber-500 bg-amber-50 dark:bg-amber-500/10' },
  { label: 'Ensayos Calidad', val: String(qualityChecks.value), icon: 'i-lucide-shield-check', color: 'text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10' },
  { label: 'Productos Finales', val: `${finalProductsStock.value} u.`, icon: 'i-lucide-boxes', color: 'text-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' }
])

const activityStatusColor: Record<string, string> = {
  success: 'bg-emerald-500',
  warning: 'bg-amber-500',
  info: 'bg-blue-500'
}

function formatCurrency(val: number) {
  return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(val)
}
</script>

<template>
  <div class="space-y-6">

    <div v-if="loading" class="flex justify-center py-20">
      <UIcon name="i-lucide-loader-2" class="animate-spin w-8 h-8 text-green-500" />
    </div>

    <template v-else>

      <!-- ================================ VISTA: LAB ================================ -->
      <template v-if="isLab">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            v-for="kpi in labKpis"
            :key="kpi.label"
            class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 p-5 rounded-xl flex items-center justify-between shadow-sm"
          >
            <div>
              <span class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">{{ kpi.label }}</span>
              <h4 class="text-2xl font-black mt-1 text-slate-800 dark:text-white">{{ kpi.val }}</h4>
            </div>
            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', kpi.color]">
              <UIcon :name="kpi.icon" class="w-6 h-6" />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <!-- Accesos rápidos -->
          <div class="grid grid-cols-2 gap-3">
            <NuxtLink
              v-for="link in [
                { to: '/ingreso-egreso', icon: 'i-lucide-arrow-left-right', label: 'Ingreso/Egreso', color: 'text-amber-600 bg-amber-50 dark:bg-amber-500/10' },
                { to: '/produccion', icon: 'i-lucide-cog', label: 'Producción', color: 'text-blue-600 bg-blue-50 dark:bg-blue-500/10' },
                { to: '/calidad', icon: 'i-lucide-shield-check', label: 'Control Calidad', color: 'text-rose-600 bg-rose-50 dark:bg-rose-500/10' },
                { to: '/inventario', icon: 'i-lucide-package', label: 'Inventario M.P.', color: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-500/10' },
              ]"
              :key="link.to"
              :to="link.to"
              class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex flex-col items-center justify-center gap-2 hover:border-green-400 dark:hover:border-green-600 transition-colors shadow-sm text-center group"
            >
              <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', link.color]">
                <UIcon :name="link.icon" class="w-5 h-5" />
              </div>
              <span class="text-xs font-semibold text-slate-600 dark:text-slate-300 group-hover:text-green-600 dark:group-hover:text-green-400">{{ link.label }}</span>
            </NuxtLink>
          </div>

          <!-- Actividad reciente -->
          <div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 p-5 rounded-xl shadow-sm">
            <h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2 mb-4">
              <UIcon name="i-lucide-activity" class="text-green-500" /> Actividad Reciente
            </h3>
            <div v-if="recentActivities.length === 0" class="text-sm text-center py-4 text-slate-400">Sin actividad reciente</div>
            <div v-else class="space-y-3">
              <div v-for="(act, i) in recentActivities" :key="i" class="flex items-start gap-3">
                <div :class="['w-2 h-2 rounded-full mt-1.5 shrink-0', activityStatusColor[act.status] || 'bg-slate-400']" />
                <div class="min-w-0">
                  <p class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ act.action }}</p>
                  <p class="text-xs text-slate-400 dark:text-slate-500">{{ act.user }} · {{ act.time }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ================================ VISTA: CAJERO ================================ -->
      <template v-else-if="isCashier">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <UCard class="bg-gradient-to-br from-green-500 to-green-600 border-0 shadow-lg">
            <div class="flex justify-between items-center text-white">
              <div>
                <p class="text-green-100 text-xs font-bold uppercase tracking-wider">Ventas de Hoy</p>
                <h2 class="text-3xl font-black mt-1">{{ formatCurrency(todaysSalesAmount) }}</h2>
                <p class="text-sm mt-1 text-green-100">{{ todaysOrders.length }} órdenes procesadas</p>
              </div>
              <UIcon name="i-lucide-banknote" class="w-14 h-14 text-white/30" />
            </div>
          </UCard>

          <UCard>
            <div class="flex justify-between items-start">
              <div>
                <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">Estado de Caja</p>
                <div v-if="!todaysCashStatus || todaysCashStatus.status_cash == 0" class="mt-2">
                  <span class="text-2xl font-black text-rose-500">CERRADA</span>
                  <p class="text-xs text-slate-400 mt-1">Abre la caja para operar</p>
                </div>
                <div v-else class="mt-2">
                  <span class="text-2xl font-black text-emerald-500">ABIERTA</span>
                  <p class="text-xs text-slate-400 mt-1">Apertura: {{ formatCurrency(parseFloat(todaysCashStatus.start_cash || 0)) }}</p>
                </div>
              </div>
              <UIcon
                name="i-lucide-wallet"
                :class="(!todaysCashStatus || todaysCashStatus.status_cash == 0) ? 'w-10 h-10 text-rose-300' : 'w-10 h-10 text-emerald-300'"
              />
            </div>
            <UButton
              :to="'/caja'"
              size="sm"
              :color="(!todaysCashStatus || todaysCashStatus.status_cash == 0) ? 'error' : 'success'"
              variant="soft"
              class="mt-3 w-full justify-center"
            >
              {{ (!todaysCashStatus || todaysCashStatus.status_cash == 0) ? 'Abrir Caja' : 'Ver Caja' }}
            </UButton>
          </UCard>

          <NuxtLink to="/pos" class="block">
            <UCard class="h-full flex items-center justify-center cursor-pointer hover:border-green-400 dark:hover:border-green-600 transition-colors">
              <div class="text-center py-2">
                <div class="w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center mx-auto mb-3">
                  <UIcon name="i-lucide-monitor-smartphone" class="w-8 h-8 text-blue-500" />
                </div>
                <span class="font-bold text-slate-800 dark:text-white">Ir al Punto de Venta</span>
                <p class="text-xs text-slate-500 mt-1">Abrir POS</p>
              </div>
            </UCard>
          </NuxtLink>
        </div>

        <UCard>
          <template #header>
            <div class="flex justify-between items-center">
              <h3 class="font-bold">Últimas Ventas (Hoy)</h3>
              <UButton to="/ordenes" size="xs" color="neutral" variant="ghost" icon="i-lucide-external-link">Ver todas</UButton>
            </div>
          </template>
          <div v-if="todaysOrders.length === 0" class="text-center text-slate-400 dark:text-slate-500 py-6">No hay ventas hoy aún.</div>
          <UTable
            v-else
            :data="todaysOrders.slice(0, 8)"
            :columns="([
              { accessorKey: 'transaction_order', header: 'Transacción' },
              { accessorKey: 'client', header: 'Cliente' },
              { accessorKey: 'method_order', header: 'Método' },
              { accessorKey: 'total_order', header: 'Total' }
            ] as any[])"
          >
            <template #client-cell="{ row }">{{ decodeURIComponent(row.original.name_client || '').replace(/\+/g, ' ') }}</template>
            <template #total_order-cell="{ row }">
              <span class="font-bold text-green-600">{{ formatCurrency(parseFloat(row.original.total_order)) }}</span>
            </template>
          </UTable>
        </UCard>
      </template>

      <!-- ================================ VISTA: SUPERADMIN / ADMIN ================================ -->
      <template v-else-if="isSuperAdmin">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <UCard>
            <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">Ventas Globales Hoy</p>
            <h2 class="text-2xl font-black text-green-600 mt-1">{{ formatCurrency(todaysSalesAmount) }}</h2>
            <p class="text-xs text-slate-400 mt-1">{{ todaysOrders.length }} transacciones</p>
          </UCard>
          <UCard>
            <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">Cajas Abiertas</p>
            <h2 class="text-2xl font-black text-blue-600 mt-1">{{ openCashRegisters.length }}</h2>
            <p class="text-xs text-slate-400 mt-1">sucursales activas</p>
          </UCard>
          <UCard>
            <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">Ticket Promedio</p>
            <h2 class="text-2xl font-black text-amber-600 mt-1">
              {{ formatCurrency(todaysOrders.length > 0 ? todaysSalesAmount / todaysOrders.length : 0) }}
            </h2>
            <p class="text-xs text-slate-400 mt-1">por orden</p>
          </UCard>
          <NuxtLink to="/reportes">
            <UCard class="cursor-pointer hover:border-green-400 dark:hover:border-green-600 transition-colors h-full flex items-center justify-center">
              <div class="text-center">
                <UIcon name="i-lucide-bar-chart-2" class="w-8 h-8 text-green-500 mx-auto mb-1" />
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Ver Reportes</p>
              </div>
            </UCard>
          </NuxtLink>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <UCard>
            <template #header>
              <div class="flex justify-between items-center">
                <h3 class="font-bold">Sucursales con Caja Abierta</h3>
                <UButton to="/caja" size="xs" color="neutral" variant="ghost" icon="i-lucide-external-link">Ver Cajas</UButton>
              </div>
            </template>
            <div v-if="openCashRegisters.length === 0" class="text-center text-slate-400 dark:text-slate-500 py-6">No hay cajas abiertas.</div>
            <UTable
              v-else
              :data="openCashRegisters"
              :columns="([{ accessorKey: 'title_office', header: 'Sucursal' }, { accessorKey: 'start_cash', header: 'Apertura' }] as any[])"
            >
              <template #title_office-cell="{ row }">
                <span class="font-semibold">{{ decodeURIComponent(row.original.title_office || '').replace(/\+/g, ' ') }}</span>
              </template>
              <template #start_cash-cell="{ row }">{{ formatCurrency(parseFloat(row.original.start_cash || 0)) }}</template>
            </UTable>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex justify-between items-center">
                <h3 class="font-bold">Últimas Transacciones</h3>
                <UButton to="/ordenes" size="xs" color="neutral" variant="ghost" icon="i-lucide-external-link">Ver Órdenes</UButton>
              </div>
            </template>
            <div v-if="todaysOrders.length === 0" class="text-center text-slate-400 dark:text-slate-500 py-6">Sin transacciones hoy.</div>
            <UTable
              v-else
              :data="todaysOrders.slice(0, 6)"
              :columns="([{ accessorKey: 'transaction_order', header: 'Transacción' }, { accessorKey: 'total_order', header: 'Monto' }] as any[])"
            >
              <template #total_order-cell="{ row }">
                <span class="font-semibold text-green-600">{{ formatCurrency(parseFloat(row.original.total_order)) }}</span>
              </template>
            </UTable>
          </UCard>
        </div>
      </template>

      <!-- ================================ VISTA: DESPACHADOR ================================ -->
      <template v-else-if="isDispatcher">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <UCard class="bg-gradient-to-br from-amber-500 to-orange-500 border-0 shadow-lg">
            <div class="flex justify-between items-center text-white">
              <div>
                <p class="text-amber-100 text-xs font-bold uppercase tracking-wider">Solicitudes Pendientes</p>
                <h2 class="text-4xl font-black mt-1">{{ pendingRequests.length }}</h2>
                <p class="text-sm mt-1 text-amber-100">requieren atención</p>
              </div>
              <UIcon name="i-lucide-clipboard-copy" class="w-14 h-14 text-white/30" />
            </div>
          </UCard>

          <NuxtLink to="/despachos" class="block">
            <UCard class="h-full flex items-center justify-center cursor-pointer hover:border-amber-400 transition-colors">
              <div class="text-center py-2">
                <div class="w-14 h-14 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center mx-auto mb-3">
                  <UIcon name="i-lucide-truck" class="w-8 h-8 text-amber-500" />
                </div>
                <span class="font-bold text-slate-800 dark:text-white">Centro de Despachos</span>
                <p class="text-xs text-slate-500 mt-1">Atender solicitudes</p>
              </div>
            </UCard>
          </NuxtLink>

          <NuxtLink to="/almacen" class="block">
            <UCard class="h-full flex items-center justify-center cursor-pointer hover:border-blue-400 transition-colors">
              <div class="text-center py-2">
                <div class="w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center mx-auto mb-3">
                  <UIcon name="i-lucide-warehouse" class="w-8 h-8 text-blue-500" />
                </div>
                <span class="font-bold text-slate-800 dark:text-white">Almacén</span>
                <p class="text-xs text-slate-500 mt-1">Ver inventario</p>
              </div>
            </UCard>
          </NuxtLink>
        </div>

        <UCard v-if="pendingRequests.length > 0">
          <template #header>
            <div class="flex justify-between items-center">
              <h3 class="font-bold">Solicitudes Pendientes</h3>
              <UButton to="/despachos" size="xs" color="warning" variant="soft" icon="i-lucide-external-link">Atender</UButton>
            </div>
          </template>
          <div class="space-y-2">
            <div
              v-for="req in pendingRequests.slice(0, 5)"
              :key="req.id_request || req.id"
              class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-500/10 rounded-lg border border-amber-200 dark:border-amber-500/20"
            >
              <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                  {{ decodeURIComponent(req.name_product || req.product || '').replace(/\+/g, ' ') || 'Solicitud #' + (req.id_request || req.id) }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ req.date_created_request || req.date || '' }}</p>
              </div>
              <UBadge color="warning" variant="subtle">Pendiente</UBadge>
            </div>
          </div>
        </UCard>
      </template>

    </template>
  </div>
</template>
