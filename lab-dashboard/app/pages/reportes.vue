<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { decodeText } from '~/utils/format'

definePageMeta({ middleware: ['auth', 'permission'], permission: 'reportes' })

const api = useApi()

// Chart.js registration
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, LineElement, PointElement } from 'chart.js'
import { Bar, Doughnut, Line } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, LineElement, PointElement)

const auth = useAuthStore()
const toast = useToast()

// Data state
const orders = ref<any[]>([])
const sales = ref<any[]>([])
const productions = ref<any[]>([])
const offices = ref<any[]>([])
const admins = ref<any[]>([])
const statsByOffice = ref<any[]>([])
const creditsList = ref<any[]>([])

const loading = ref(true)

// Date Range Filters (Default: Start of last month to end of last month)
const today = new Date()
const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1)
const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0)

const startDate = ref(firstDayLastMonth.toISOString().split('T')[0] || '')
const endDate = ref(lastDayLastMonth.toISOString().split('T')[0] || '')

// Tabs
const items = computed(() => {
  const tabs = [
    { label: 'Órdenes', icon: 'i-lucide-file-text', slot: 'ordenes' },
    { label: 'Ventas (Productos)', icon: 'i-lucide-box', slot: 'ventas' }
  ]
  if (auth.role === 'superadmin' || auth.role === 'admin' || auth.role === 'lab_admin') {
    tabs.push({ label: 'Laboratorio (Producción)', icon: 'i-lucide-flask-conical', slot: 'laboratorio' })
  }
  if (auth.role === 'superadmin' || auth.role === 'admin' || auth.role === 'despachador') {
    tabs.push({ label: 'Vendedores / Comisiones', icon: 'i-lucide-users', slot: 'vendedores' })
  }
  return tabs
})

async function fetchOffices() {
  try {
    const data = await api.rest<any>('/api/offices')
    if (data.status === 200 && data.results) {
      offices.value = data.results
    }
  } catch (e) {
    console.error('Error fetching offices:', e)
  }
}

async function fetchReportData() {
  loading.value = true
  orders.value = []
  sales.value = []
  productions.value = []
  statsByOffice.value = []
  creditsList.value = []
  
  try {
    const isSuperAdmin = auth.officeId === 0 || auth.role === 'superadmin'
    const myOfficeId = auth.officeId
    if (!isSuperAdmin && !myOfficeId) {
      toast.add({ title: 'Error', description: 'No tiene sucursal asignada', color: 'error' })
      loading.value = false
      return
    }

    // Params for Orders
    const orderParams = new URLSearchParams({
      rel: 'orders,clients,offices',
      type: 'order,client,office',
      orderBy: 'id_order',
      orderMode: 'DESC'
    })

    // Params for Sales
    const saleParams = new URLSearchParams({
      rel: 'sales,products',
      type: 'sale,product',
      orderBy: 'id_sale',
      orderMode: 'DESC'
    })

    // Params for Productions
    const prodParams = new URLSearchParams({
      rel: 'productions,recipes',
      type: 'production,recipe',
      orderBy: 'id_production',
      orderMode: 'ASC'
    })

    if (startDate.value === endDate.value) {
      if (isSuperAdmin) {
        orderParams.set('linkTo', 'date_created_order')
        orderParams.set('equalTo', startDate.value)
        saleParams.set('linkTo', 'date_created_sale')
        saleParams.set('equalTo', startDate.value)
        prodParams.set('linkTo', 'date_created_production')
        prodParams.set('equalTo', startDate.value)
      } else {
        orderParams.set('linkTo', 'id_office_order,date_created_order')
        orderParams.set('equalTo', `${myOfficeId},${startDate.value}`)
        saleParams.set('linkTo', 'id_office_sale,date_created_sale')
        saleParams.set('equalTo', `${myOfficeId},${startDate.value}`)
        prodParams.set('linkTo', 'id_office_production,date_created_production')
        prodParams.set('equalTo', `${myOfficeId},${startDate.value}`)
      }
    } else {
      if (isSuperAdmin) {
        orderParams.set('between1', 'date_created_order')
        orderParams.set('between2', `${startDate.value},${endDate.value}`)
        saleParams.set('between1', 'date_created_sale')
        saleParams.set('between2', `${startDate.value},${endDate.value}`)
        prodParams.set('between1', 'date_created_production')
        prodParams.set('between2', `${startDate.value},${endDate.value}`)
      } else {
        orderParams.set('linkTo', 'id_office_order')
        orderParams.set('equalTo', String(myOfficeId))
        orderParams.set('between1', 'date_created_order')
        orderParams.set('between2', `${startDate.value},${endDate.value}`)
        
        saleParams.set('linkTo', 'id_office_sale')
        saleParams.set('equalTo', String(myOfficeId))
        saleParams.set('between1', 'date_created_sale')
        saleParams.set('between2', `${startDate.value},${endDate.value}`)

        prodParams.set('linkTo', 'id_office_production')
        prodParams.set('equalTo', String(myOfficeId))
        prodParams.set('between1', 'date_created_production')
        prodParams.set('between2', `${startDate.value},${endDate.value}`)
      }
    }

    // Fetch in parallel
    const adminFilter = isSuperAdmin ? '' : `?linkTo=id_office_admin&equalTo=${myOfficeId}`
    const [ordersData, salesData, prodsData, adminsRes, creditsRes] = await Promise.all([
      api.rest<any>(`/api/relations?${orderParams.toString()}`),
      api.rest<any>(`/api/relations?${saleParams.toString()}`),
      api.rest<any>(`/api/relations?${prodParams.toString()}`),
      api.rest<any>(`/api/admins${adminFilter}`),
      api.ajax({ getCredits: 'ok', id_office: isSuperAdmin ? 0 : myOfficeId })
    ])

    if (ordersData && ordersData.status === 200 && ordersData.results) {
      let fOrders = ordersData.results
      if (auth.role === 'vendedor' || auth.role === 'cajero') {
         fOrders = fOrders.filter((o: any) => String(o.id_admin_order) === String(auth.user?.id_admin))
      }
      orders.value = fOrders
    }
    
    if (salesData && salesData.status === 200 && salesData.results) {
      let fSales = salesData.results
      if (auth.role === 'vendedor' || auth.role === 'cajero') {
         fSales = fSales.filter((s: any) => String(s.id_admin_sale) === String(auth.user?.id_admin))
      }
      sales.value = fSales
    }

    if (prodsData && prodsData.status === 200 && prodsData.results) {
      productions.value = prodsData.results
    }

    if (adminsRes && adminsRes.status === 200 && adminsRes.results) {
      admins.value = adminsRes.results
    }

    if (creditsRes && creditsRes.status === 200 && creditsRes.results) {
      creditsList.value = creditsRes.results
    }

    // If superadmin, calculate stats by office
    if (isSuperAdmin) {
      await fetchOffices()
      
      const officeMap: Record<string, any> = {}
      offices.value.forEach(o => {
        officeMap[o.id_office] = {
          name: decodeURIComponent(o.title_office).replace(/\+/g, ' '),
          id: o.id_office,
          total_orders: 0,
          total_amount: 0
        }
      })

      orders.value.forEach(o => {
        const offId = String(o.id_office_order)
        if (officeMap[offId]) {
          officeMap[offId].total_orders++
          officeMap[offId].total_amount += parseFloat(o.total_order || 0)
        }
      })

      statsByOffice.value = Object.values(officeMap)
        .filter(st => st.total_orders > 0)
        .sort((a, b) => b.total_amount - a.total_amount)
    }

  } catch (e) {
    console.error('Error fetching reports:', e)
    toast.add({ title: 'Error', description: 'No se pudieron cargar los reportes', color: 'error' })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchReportData()
})

function applyFilter() {
  fetchReportData()
}

// ----------------------------------------
// KPIs & Calculated Stats
// ----------------------------------------

const totalVentasBs = computed(() => orders.value.reduce((acc, o) => {
  if (o.status_order === 'Cancelada' || o.status_order === 'anulada') {
    if (o.method_order === 'credito') {
      const credit = creditsList.value.find(c => String(c.id_order_credit) === String(o.id_order))
      if (credit) return acc + (parseFloat(credit.amount_credit || 0) - parseFloat(credit.balance_credit || 0))
    }
    return acc
  }
  return acc + parseFloat(o.total_order || 0)
}, 0))

const totalRecibidoBs = computed(() => {
  let total = 0
  orders.value.forEach(o => {
    if (o.status_order === 'Cancelada' || o.status_order === 'anulada') {
      if (o.method_order === 'credito') {
        const credit = creditsList.value.find(c => String(c.id_order_credit) === String(o.id_order))
        if (credit) total += (parseFloat(credit.amount_credit || 0) - parseFloat(credit.balance_credit || 0))
      }
    } else if (o.method_order === 'credito') {
      const credit = creditsList.value.find(c => String(c.id_order_credit) === String(o.id_order))
      if (credit) {
        total += (parseFloat(credit.amount_credit || 0) - parseFloat(credit.balance_credit || 0))
      }
    } else {
      total += parseFloat(o.total_order || 0)
    }
  })
  return total
})

const sumSubtotal = computed(() => orders.value.reduce((acc, o) => {
  if (o.status_order === 'Cancelada' || o.status_order === 'anulada') {
    if (o.method_order === 'credito') {
      const credit = creditsList.value.find(c => String(c.id_order_credit) === String(o.id_order))
      if (credit) return acc + (parseFloat(credit.amount_credit || 0) - parseFloat(credit.balance_credit || 0))
    }
    return acc
  }
  return acc + parseFloat(o.subtotal_order || 0)
}, 0))

const sumDiscount = computed(() => orders.value.reduce((acc, o) => {
  if (o.status_order === 'Cancelada' || o.status_order === 'anulada') return acc
  return acc + parseFloat(o.discount_order || 0)
}, 0))

const validOrdersList = computed(() => orders.value.filter(o => o.status_order !== 'Cancelada' && o.status_order !== 'anulada'))
const totalProductsQty = computed(() => sales.value.reduce((acc, s) => {
  const o = orders.value.find(x => String(x.id_order) === String(s.id_order_sale))
  if (o && (o.status_order === 'Cancelada' || o.status_order === 'anulada')) return acc
  return acc + parseInt(s.qty_sale || 0)
}, 0))

const avgOrder = computed(() => validOrdersList.value.length > 0 ? (totalVentasBs.value / validOrdersList.value.length) : 0)

const sellersStats = computed(() => {
  if (orders.value.length === 0) return []
  const map: Record<string, any> = {}
  orders.value.forEach(o => {
    const adminId = String(o.id_admin_order)
    if (!map[adminId]) {
      const admin = admins.value.find(a => String(a.id_admin) === adminId)
      map[adminId] = {
        name: admin ? `${decodeStr(admin.name_admin)} ${decodeStr(admin.surname_admin)}`.trim() : `Vendedor #${adminId}`,
        pct_commission: admin ? parseFloat(admin.pct_commission_admin || 0) : 0,
        total_sales: 0,
        orders_count: 0
      }
    }
    map[adminId].total_sales += parseFloat(o.total_order || 0)
    map[adminId].orders_count += 1
  })
  
  return Object.values(map).map(s => {
    s.commission_earned = s.total_sales * (s.pct_commission / 100)
    return s
  }).sort((a, b) => b.total_sales - a.total_sales)
})

// ----------------------------------------
// Charts Configuration
// ----------------------------------------

const salesByDayChartData = computed(() => {
  const byDay: Record<string, number> = {}
  orders.value.forEach(o => {
    const d = o.date_created_order || 'N/A'
    byDay[d] = (byDay[d] || 0) + parseFloat(o.total_order || 0)
  })
  
  const sortedKeys = Object.keys(byDay).sort()
  return {
    labels: sortedKeys,
    datasets: [{
      label: 'Ventas por Día (Bs)',
      data: sortedKeys.map(k => byDay[k] || 0),
      backgroundColor: '#16a34a',
      borderRadius: 4
    }]
  }
})

const topProductsChartData = computed(() => {
  const byProd: Record<string, number> = {}
  sales.value.forEach(s => {
    const pName = decodeURIComponent(s.title_product || '').replace(/\+/g, ' ')
    byProd[pName] = (byProd[pName] || 0) + parseFloat(s.subtotal_sale || 0)
  })
  
  const sortedProds = Object.entries(byProd)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10)
    
  return {
    labels: sortedProds.map(p => p[0]),
    datasets: [{
      data: sortedProds.map(p => p[1] || 0),
      backgroundColor: [
        '#16a34a', '#2563eb', '#d97706', '#dc2626', '#7c3aed',
        '#059669', '#4f46e5', '#db2777', '#c026d3', '#0891b2'
      ]
    }]
  }
})

const salesByOfficeChartData = computed(() => {
  return {
    labels: statsByOffice.value.map(st => st.name),
    datasets: [{
      label: 'Total Ventas por Sucursal (Bs)',
      data: statsByOffice.value.map(st => st.total_amount),
      backgroundColor: '#2563eb',
      borderRadius: 4
    }]
  }
})

function decodeStr(str: string) {
  if (!str) return ''
  return decodeURIComponent(str).replace(/\+/g, ' ')
}

const completedProds = computed(() => productions.value.filter(p => p.status_production === 'completado'))

const labWasteChartData = computed(() => {
  const dates = completedProds.value.map(p => p.date_created_production || '')
  const variances = completedProds.value.map(p => parseFloat(p.yield_variance_pct || 0))
  return {
    labels: dates.length ? dates : ['Sin Datos'],
    datasets: [{
      label: 'Merma (%)',
      data: variances.length ? variances : [0],
      backgroundColor: '#ef4444',
      borderColor: '#ef4444',
      fill: false
    }]
  }
})

const labCostChartData = computed(() => {
  const labels = completedProds.value.map(p => `#${p.id_production} ${decodeStr(p.title_product || '')}`)
  const estimated = completedProds.value.map(p => parseFloat(p.proj_unit_cost || 0))
  const real = completedProds.value.map(p => parseFloat(p.real_unit_cost || 0))
  
  return {
    labels: labels.length ? labels : ['Sin Datos'],
    datasets: [
      { label: 'Costo Estimado', data: estimated.length ? estimated : [0], borderColor: '#3b82f6', backgroundColor: '#3b82f6', fill: false },
      { label: 'Costo Real', data: real.length ? real : [0], borderColor: '#10b981', backgroundColor: '#10b981', fill: false }
    ]
  }
})

const labCountChartData = computed(() => {
  const byRecipe: Record<string, number> = {}
  completedProds.value.forEach(p => {
    const name = decodeStr(p.title_product || '')
    byRecipe[name] = (byRecipe[name] || 0) + 1
  })
  
  const labels = Object.keys(byRecipe)
  return {
    labels: labels.length ? labels : ['Sin Datos'],
    datasets: [{
      label: 'Producciones Completadas',
      data: labels.map(l => byRecipe[l] || 0),
      backgroundColor: '#8b5cf6',
      borderRadius: 4
    }]
  }
})

function formatCurrency(val: number) {
  return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(val)
}

function csvCell(val: unknown): string {
  const s = String(val ?? '')
  if (/[",\n\r]/.test(s)) return `"${s.replace(/"/g, '""')}"`
  return s
}

function handleExportCSV() {
  if (orders.value.length === 0) return

  const headers = ['Transaccion', 'Cliente', 'Sucursal', 'Fecha', 'Metodo', 'Estado', 'Subtotal', 'Descuento', 'Impuesto', 'Total']
  const rows = orders.value.map(o => [
    o.transaction_order,
    `${decodeStr(o.name_client)} ${decodeStr(o.surname_client)}`.trim(),
    decodeStr(o.title_office),
    o.date_order,
    o.method_order,
    o.status_order,
    o.subtotal_order,
    o.discount_order,
    o.tax_order,
    o.total_order
  ])

  const lines = [
    headers.map(csvCell).join(','),
    ...rows.map(r => r.map(csvCell).join(','))
  ]
  const blob = new Blob(['\uFEFF' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `reporte_ventas_${startDate.value}_${endDate.value}.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

const isReceiptModalOpen = ref(false)
const selectedOrderId = ref<number | string | null>(null)

function viewPdf(idOrder: string | number) {
  selectedOrderId.value = idOrder
  isReceiptModalOpen.value = true
}

// Data table config
const orderCols = [
  { accessorKey: 'transaction_order', header: 'Transacción' },
  { accessorKey: 'client', header: 'Cliente' },
  { accessorKey: 'date_order', header: 'Fecha' },
  { accessorKey: 'method_order', header: 'Método' },
  { accessorKey: 'subtotal_order', header: 'Subtotal' },
  { accessorKey: 'discount_order', header: 'Dscto' },
  { accessorKey: 'total_order', header: 'Total' },
  { accessorKey: 'status_order', header: 'Estado' },
  { accessorKey: 'actions', header: '' }
]

const salesCols = [
  { accessorKey: 'title_product', header: 'Producto' },
  { accessorKey: 'qty_sale', header: 'Cant' },
  { accessorKey: 'price_sale', header: 'Precio' },
  { accessorKey: 'tax_sale', header: 'IVA%' },
  { accessorKey: 'discount_sale', header: 'Dscto%' },
  { accessorKey: 'subtotal_sale', header: 'Subtotal' }
]

const sellerCols = [
  { accessorKey: 'name', header: 'Vendedor' },
  { accessorKey: 'orders_count', header: 'Ventas Realizadas' },
  { accessorKey: 'total_sales', header: 'Total Vendido (Bs)' },
  { accessorKey: 'pct_commission', header: '% Comisión' },
  { accessorKey: 'commission_earned', header: 'Comisión Ganada (Bs)' }
]

// Cancel Credit Order
const cancelingId = ref<number | null>(null)

async function cancelCreditOrder(idOrder: number) {
  if (!confirm('¿Estás seguro de que deseas cancelar esta venta a crédito? Se devolverá el stock, pero el dinero inicial se mantendrá como ingreso en caja.')) return
  cancelingId.value = idOrder
  const fd = new FormData()
  fd.append('cancelCreditOrder', String(idOrder))
  
  const d = await api.ajaxForm(fd, { endpoint: '/ajax/pos.ajax.php' })
  if (d === 'ok') {
    toast.add({ title: 'Venta a crédito cancelada y stock devuelto', color: 'success' })
    await fetchReportData() // recargar la tabla
  } else {
    toast.add({ title: 'Error al cancelar la orden', description: d || 'Error desconocido', color: 'error' })
  }
  cancelingId.value = null
}
</script>

<template>
  <div class="w-full space-y-6">
    
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
          <UIcon name="i-lucide-bar-chart-2" class="w-6 h-6 text-green-600" />
          Reportes de Ventas
        </h1>
        <p class="text-sm text-slate-500 mt-1">Análisis y estadísticas de órdenes y facturación.</p>
      </div>

      <div class="flex items-center gap-2">
        <UInput type="date" v-model="startDate" size="sm" />
        <span class="text-slate-500">hasta</span>
        <UInput type="date" v-model="endDate" size="sm" />
        <UButton color="neutral" variant="solid" icon="i-lucide-filter" :loading="loading" @click="applyFilter">Filtrar</UButton>
        <UButton color="primary" class="bg-green-600" icon="i-lucide-download" @click="handleExportCSV">Exportar Excel</UButton>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <UCard>
        <div class="text-slate-500 text-xs font-semibold">Total Vendido (Bs)</div>
        <div class="text-xl font-bold text-green-600">{{ formatCurrency(totalVentasBs) }}</div>
      </UCard>
      <UCard class="bg-emerald-50 border-emerald-200">
        <div class="text-emerald-700 text-xs font-bold">Total Recibido (Bs)</div>
        <div class="text-xl font-black text-emerald-600">{{ formatCurrency(totalRecibidoBs) }}</div>
      </UCard>
      <UCard>
        <div class="text-slate-500 text-xs font-semibold">Órdenes</div>
        <div class="text-xl font-bold text-blue-600">{{ validOrdersList.length }}</div>
      </UCard>
      <UCard>
        <div class="text-slate-500 text-xs font-semibold">Ticket Promedio</div>
        <div class="text-xl font-bold text-amber-600">{{ formatCurrency(avgOrder) }}</div>
      </UCard>
      <UCard>
        <div class="text-slate-500 text-xs font-semibold">Productos</div>
        <div class="text-xl font-bold text-purple-600">{{ totalProductsQty }}</div>
      </UCard>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Resumen Financiero -->
      <UCard>
        <template #header><h3 class="font-semibold text-lg">Resumen Financiero</h3></template>
        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-slate-600">Subtotal:</span>
            <span class="font-medium">{{ formatCurrency(sumSubtotal) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">Descuentos (-):</span>
            <span class="font-medium text-red-500">{{ formatCurrency(sumDiscount) }}</span>
          </div>
          <div class="flex justify-between text-lg font-bold border-t pt-2 mt-2">
            <span>Total Vendido:</span>
            <span>{{ formatCurrency(totalVentasBs) }}</span>
          </div>
          <div class="flex justify-between text-lg font-bold text-emerald-600 mt-1">
            <span>Total Recibido:</span>
            <span>{{ formatCurrency(totalRecibidoBs) }}</span>
          </div>
        </div>
      </UCard>

      <!-- Gráfico de Ventas Diarias -->
      <UCard>
        <template #header><h3 class="font-semibold text-lg">Evolución Diaria</h3></template>
        <div class="h-48">
          <Bar v-if="orders.length > 0" :data="salesByDayChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
          <div v-else class="h-full flex items-center justify-center text-gray-400">Sin datos para graficar</div>
        </div>
      </UCard>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Top Productos -->
      <UCard>
        <template #header><h3 class="font-semibold text-lg">Top 10 Productos</h3></template>
        <div class="h-64 flex justify-center">
          <Doughnut v-if="sales.length > 0" :data="topProductsChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
          <div v-else class="h-full flex items-center justify-center text-gray-400">Sin datos</div>
        </div>
      </UCard>

      <!-- Comparación Sucursales (Only if admin) -->
      <UCard v-if="statsByOffice.length > 0">
        <template #header><h3 class="font-semibold text-lg">Rendimiento por Sucursal</h3></template>
        <div class="h-64">
          <Bar :data="salesByOfficeChartData" :options="{ responsive: true, maintainAspectRatio: false, indexAxis: 'x' }" />
        </div>
      </UCard>
    </div>

    <!-- Data Tabs -->
    <UTabs :items="items" class="w-full mt-4">
      <template #content="{ item }">
        <UCard v-if="item.slot === 'ordenes'" class="mt-4">
           <UTable :data="orders" :columns="orderCols" :loading="loading">
             <template #client-cell="{ row }">
               {{ decodeStr(row.original.name_client) }} {{ decodeStr(row.original.surname_client) }}
             </template>
             <template #subtotal_order-cell="{ row }">{{ formatCurrency(parseFloat(row.original.subtotal_order)) }}</template>
             <template #discount_order-cell="{ row }">-{{ formatCurrency(parseFloat(row.original.discount_order)) }}</template>
             <template #total_order-cell="{ row }">
               <span class="font-bold text-green-600">{{ formatCurrency(parseFloat(row.original.total_order)) }}</span>
             </template>
             <template #status_order-cell="{ row }">
               <UBadge :color="row.original.status_order === 'Completada' ? 'success' : (row.original.status_order === 'Cancelada' ? 'error' : 'warning')" variant="subtle">
                 {{ row.original.status_order }}
               </UBadge>
             </template>
             <template #actions-cell="{ row }">
               <div class="flex items-center gap-1">
                 <UButton color="neutral" variant="ghost" icon="i-lucide-file-text" size="sm" @click="viewPdf(row.original.id_order)" title="Ver Factura/Recibo" />
                 <UButton 
                   v-if="row.original.method_order === 'credito' && row.original.status_order === 'Completada'" 
                   color="error" 
                   variant="ghost" 
                   icon="i-lucide-x-circle" 
                   size="sm" 
                   title="Cancelar Venta y Devolver Stock"
                   :loading="cancelingId === row.original.id_order"
                   @click="cancelCreditOrder(row.original.id_order)" 
                 />
               </div>
             </template>
           </UTable>
        </UCard>

        <UCard v-else-if="item.slot === 'ventas'" class="mt-4">
           <UTable :data="sales" :columns="salesCols" :loading="loading">
             <template #title_product-cell="{ row }">
               <span class="font-medium">{{ decodeStr(row.original.title_product) }}</span>
             </template>
             <template #price_sale-cell="{ row }">{{ formatCurrency(parseFloat(row.original.price_sale)) }}</template>
             <template #tax_sale-cell="{ row }">{{ row.original.tax_sale }}%</template>
             <template #discount_sale-cell="{ row }">{{ row.original.discount_sale }}%</template>
             <template #subtotal_sale-cell="{ row }">
               <span class="font-bold">{{ formatCurrency(parseFloat(row.original.subtotal_sale)) }}</span>
             </template>
           </UTable>
        </UCard>

        <div v-else-if="item.slot === 'laboratorio'" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <UCard>
            <template #header><h3 class="font-semibold text-lg">Merma Histórica (%)</h3></template>
            <div class="h-64">
              <Line :data="labWasteChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
            </div>
          </UCard>
          <UCard>
            <template #header><h3 class="font-semibold text-lg">Costo de Producción: Estimado vs Real</h3></template>
            <div class="h-64">
              <Line :data="labCostChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
            </div>
          </UCard>
          <UCard class="md:col-span-2">
            <template #header><h3 class="font-semibold text-lg">Producciones Completadas por Receta</h3></template>
            <div class="h-64">
              <Bar :data="labCountChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
            </div>
          </UCard>
        </div>

        <UCard v-else-if="item.slot === 'vendedores'" class="mt-4">
           <UTable :data="sellersStats" :columns="sellerCols" :loading="loading">
             <template #name-cell="{ row }">
               <span class="font-medium text-blue-600">{{ row.original.name }}</span>
             </template>
             <template #total_sales-cell="{ row }">
               <span class="font-bold">{{ formatCurrency(row.original.total_sales) }}</span>
             </template>
             <template #pct_commission-cell="{ row }">
               {{ row.original.pct_commission }}%
             </template>
             <template #commission_earned-cell="{ row }">
               <span class="font-bold text-green-600">{{ formatCurrency(row.original.commission_earned) }}</span>
             </template>
           </UTable>
        </UCard>
      </template>
    </UTabs>

    <OrderReceiptModal 
      v-model:isOpen="isReceiptModalOpen"
      :order-id="selectedOrderId"
      @close="selectedOrderId = null"
    />
  </div>
</template>
