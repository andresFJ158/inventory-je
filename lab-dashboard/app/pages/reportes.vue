<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'

// Chart.js registration
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js'
import { Bar, Doughnut } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const auth = useAuthStore()
const toast = useToast()

// Data state
const orders = ref<any[]>([])
const sales = ref<any[]>([])
const offices = ref<any[]>([])
const statsByOffice = ref<any[]>([])

const loading = ref(true)

// Date Range Filters (Default: Start of last month to end of last month)
const today = new Date()
const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1)
const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0)

const startDate = ref(firstDayLastMonth.toISOString().split('T')[0])
const endDate = ref(lastDayLastMonth.toISOString().split('T')[0])

// Tabs
const items = [{
  label: 'Órdenes',
  icon: 'i-lucide-file-text',
  slot: 'ordenes'
}, {
  label: 'Ventas (Productos)',
  icon: 'i-lucide-box',
  slot: 'ventas'
}]

const apiHeaders = {
  Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
}

async function fetchOffices() {
  try {
    const data = await $fetch<any>('/api/offices', { headers: apiHeaders })
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
  statsByOffice.value = []
  
  try {
    const isSuperAdmin = auth.officeId === 0 || auth.role === 'superadmin' || auth.role === 'admin'
    const myOfficeId = auth.officeId || 3

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

    if (startDate.value === endDate.value) {
      if (isSuperAdmin) {
        orderParams.set('linkTo', 'date_created_order')
        orderParams.set('equalTo', startDate.value)
        saleParams.set('linkTo', 'date_created_sale')
        saleParams.set('equalTo', startDate.value)
      } else {
        orderParams.set('linkTo', 'id_office_order,date_created_order')
        orderParams.set('equalTo', `${myOfficeId},${startDate.value}`)
        saleParams.set('linkTo', 'id_office_sale,date_created_sale')
        saleParams.set('equalTo', `${myOfficeId},${startDate.value}`)
      }
    } else {
      if (isSuperAdmin) {
        orderParams.set('between1', 'date_created_order')
        orderParams.set('between2', `${startDate.value},${endDate.value}`)
        saleParams.set('between1', 'date_created_sale')
        saleParams.set('between2', `${startDate.value},${endDate.value}`)
      } else {
        orderParams.set('linkTo', 'id_office_order')
        orderParams.set('equalTo', String(myOfficeId))
        orderParams.set('between1', 'date_created_order')
        orderParams.set('between2', `${startDate.value},${endDate.value}`)
        
        saleParams.set('linkTo', 'id_office_sale')
        saleParams.set('equalTo', String(myOfficeId))
        saleParams.set('between1', 'date_created_sale')
        saleParams.set('between2', `${startDate.value},${endDate.value}`)
      }
    }

    // Fetch in parallel
    const [ordersData, salesData] = await Promise.all([
      $fetch<any>(`/api/relations?${orderParams.toString()}`, { headers: apiHeaders }).catch(() => null),
      $fetch<any>(`/api/relations?${saleParams.toString()}`, { headers: apiHeaders }).catch(() => null)
    ])

    if (ordersData && ordersData.status === 200 && ordersData.results) {
      orders.value = ordersData.results
    }
    
    if (salesData && salesData.status === 200 && salesData.results) {
      sales.value = salesData.results
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

const totalVentasBs = computed(() => orders.value.reduce((acc, o) => acc + parseFloat(o.total_order || 0), 0))
const sumSubtotal = computed(() => orders.value.reduce((acc, o) => acc + parseFloat(o.subtotal_order || 0), 0))
const sumDiscount = computed(() => orders.value.reduce((acc, o) => acc + parseFloat(o.discount_order || 0), 0))
const totalProductsQty = computed(() => sales.value.reduce((acc, s) => acc + parseInt(s.qty_sale || 0), 0))
const avgOrder = computed(() => orders.value.length > 0 ? (totalVentasBs.value / orders.value.length) : 0)

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
      data: sortedKeys.map(k => byDay[k]),
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
      data: sortedProds.map(p => p[1]),
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

function formatCurrency(val: number) {
  return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(val)
}

function decodeStr(str: string) {
  if (!str) return ''
  return decodeURIComponent(str).replace(/\+/g, ' ')
}

function handleExportCSV() {
  if (orders.value.length === 0) return
  
  const headers = ['Transaccion', 'Cliente', 'Sucursal', 'Fecha', 'Metodo', 'Estado', 'Subtotal', 'Descuento', 'Impuesto', 'Total']
  const rows = orders.value.map(o => [
    o.transaction_order,
    decodeStr(o.name_client) + ' ' + decodeStr(o.surname_client),
    decodeStr(o.title_office),
    o.date_order,
    o.method_order,
    o.status_order,
    o.subtotal_order,
    o.discount_order,
    o.tax_order,
    o.total_order
  ])
  
  const csvContent = "data:text/csv;charset=utf-8," 
    + headers.join(',') + "\n"
    + rows.map(r => r.join(',')).join("\n")
    
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement("a")
  link.setAttribute("href", encodedUri)
  link.setAttribute("download", `reporte_ventas_${startDate.value}_${endDate.value}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
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
        <UButton color="white" variant="solid" icon="i-lucide-filter" @click="applyFilter">Filtrar</UButton>
        <UButton color="primary" class="bg-green-600" icon="i-lucide-download" @click="handleExportCSV">Exportar Excel</UButton>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <UCard>
        <div class="text-slate-500 text-sm font-semibold">Total Ventas (Bs)</div>
        <div class="text-2xl font-bold text-green-600">{{ formatCurrency(totalVentasBs) }}</div>
      </UCard>
      <UCard>
        <div class="text-slate-500 text-sm font-semibold">Órdenes</div>
        <div class="text-2xl font-bold text-blue-600">{{ orders.length }}</div>
      </UCard>
      <UCard>
        <div class="text-slate-500 text-sm font-semibold">Ticket Promedio</div>
        <div class="text-2xl font-bold text-amber-600">{{ formatCurrency(avgOrder) }}</div>
      </UCard>
      <UCard>
        <div class="text-slate-500 text-sm font-semibold">Productos Vendidos</div>
        <div class="text-2xl font-bold text-purple-600">{{ totalProductsQty }}</div>
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
            <span>Total General:</span>
            <span>{{ formatCurrency(totalVentasBs) }}</span>
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
      <template #ordenes>
        <UCard class="mt-4">
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
               <UBadge :color="row.original.status_order === 'Completada' ? 'green' : 'amber'" variant="subtle">
                 {{ row.original.status_order }}
               </UBadge>
             </template>
             <template #actions-cell="{ row }">
               <UButton color="gray" variant="ghost" icon="i-lucide-file-text" size="sm" @click="viewPdf(row.original.id_order)">PDF</UButton>
             </template>
           </UTable>
        </UCard>
      </template>

      <template #ventas>
        <UCard class="mt-4">
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
      </template>
    </UTabs>

    <OrderReceiptModal 
      v-model:isOpen="isReceiptModalOpen"
      :order-id="selectedOrderId"
      @close="selectedOrderId = null"
    />
  </div>
</template>
