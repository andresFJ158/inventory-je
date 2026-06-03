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

    const sDate = startDate.value || ''
    const eDate = endDate.value || ''

    if (sDate === eDate) {
      if (isSuperAdmin) {
        orderParams.set('linkTo', 'date_created_order')
        orderParams.set('equalTo', sDate)
        saleParams.set('linkTo', 'date_created_sale')
        saleParams.set('equalTo', sDate)
      } else {
        orderParams.set('linkTo', 'id_office_order,date_created_order')
        orderParams.set('equalTo', `${myOfficeId},${sDate}`)
        saleParams.set('linkTo', 'id_office_sale,date_created_sale')
        saleParams.set('equalTo', `${myOfficeId},${sDate}`)
      }
    } else {
      if (isSuperAdmin) {
        orderParams.set('between1', 'date_created_order')
        orderParams.set('between2', `${sDate},${eDate}`)
        saleParams.set('between1', 'date_created_sale')
        saleParams.set('between2', `${sDate},${eDate}`)
      } else {
        orderParams.set('linkTo', 'id_office_order')
        orderParams.set('equalTo', String(myOfficeId))
        orderParams.set('between1', 'date_created_order')
        orderParams.set('between2', `${sDate},${eDate}`)
        
        saleParams.set('linkTo', 'id_office_sale')
        saleParams.set('equalTo', String(myOfficeId))
        saleParams.set('between1', 'date_created_sale')
        saleParams.set('between2', `${sDate},${eDate}`)
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

// ── Comprobantes de pago (respaldo de la venta) ──
const ajaxBase = '/ajax/pos.ajax.php'
const proofModal = ref(false)
const proofOrder = ref<any>(null)
const proofPayments = ref<any[]>([])
const proofLoading = ref(false)
const proofUploading = ref(false)
const newProofFile = ref<File | null>(null)
const newProofRef = ref('')

async function openProof(order: any) {
  proofOrder.value = order
  proofModal.value = true
  newProofFile.value = null
  newProofRef.value = ''
  await fetchProofs()
}

async function fetchProofs() {
  if (!proofOrder.value) return
  proofLoading.value = true
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ getSalePayments: 'ok', id_order: String(proofOrder.value.id_order) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  proofPayments.value = d?.status === 200 ? d.results : []
  proofLoading.value = false
}

function onNewProofChange(e: Event) {
  const files = (e.target as HTMLInputElement).files
  newProofFile.value = files && files.length ? files[0]! : null
}

async function uploadProof(idSalePayment?: number) {
  if (!proofOrder.value) return
  if (!newProofFile.value && !newProofRef.value && !idSalePayment) {
    toast.add({ title: 'Adjunte un archivo o una referencia', color: 'warning' }); return
  }
  proofUploading.value = true
  try {
    const fd = new FormData()
    fd.append('uploadSalePayment', 'ok')
    fd.append('id_order', String(proofOrder.value.id_order))
    fd.append('id_admin', String(auth.user?.id_admin || 0))
    if (idSalePayment) fd.append('id_sale_payment', String(idSalePayment))
    if (newProofRef.value) fd.append('reference', newProofRef.value)
    if (newProofFile.value) fd.append('proof', newProofFile.value)
    const res = await $fetch<any>(ajaxBase, { method: 'POST', body: fd })
    const d = typeof res === 'string' ? JSON.parse(res) : res
    if (d?.status === 200) {
      toast.add({ title: d.message || 'Comprobante guardado', color: 'success' })
      newProofFile.value = null
      newProofRef.value = ''
      await fetchProofs()
    } else {
      toast.add({ title: d?.message || 'Error al guardar', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de conexión', color: 'error' })
  }
  proofUploading.value = false
}

async function deleteProof(idSalePayment: number) {
  await $fetch(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ deleteSalePayment: 'ok', id_sale_payment: String(idSalePayment) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  await fetchProofs()
}

// Data table config
const orderCols: any[] = [
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

const salesCols: any[] = [
  { accessorKey: 'title_product', header: 'Producto' },
  { accessorKey: 'qty_sale', header: 'Cant' },
  { accessorKey: 'price_sale', header: 'Precio' },
  { accessorKey: 'tax_sale', header: 'IVA%' },
  { accessorKey: 'discount_sale', header: 'Dscto%' },
  { accessorKey: 'subtotal_sale', header: 'Subtotal' }
]
</script>

<template>
  <div class="space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Análisis y estadísticas de órdenes y facturación.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <UInput type="date" v-model="startDate" size="sm" class="w-36" />
        <span class="text-slate-500 text-xs">hasta</span>
        <UInput type="date" v-model="endDate" size="sm" class="w-36" />
        <UButton color="neutral" variant="solid" icon="i-lucide-filter" size="sm" @click="applyFilter">Filtrar</UButton>
        <UButton color="primary" icon="i-lucide-download" size="sm" @click="handleExportCSV">
          <span class="hidden sm:inline">Exportar Excel</span>
          <span class="sm:hidden">Excel</span>
        </UButton>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-4 text-white shadow-md flex justify-between items-center transition-all hover:scale-[1.02] duration-200">
        <div>
          <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-wider">Total Facturado</p>
          <h2 class="text-3xl font-black mt-1.5 font-mono">{{ formatCurrency(totalVentasBs) }}</h2>
          <span class="text-[9px] bg-white/20 px-2 py-0.5 rounded-full inline-block mt-2 font-medium">✓ Ventas Netas</span>
        </div>
        <UIcon name="i-lucide-banknote" class="w-12 h-12 text-white/30 shrink-0" />
      </div>

      <div class="bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl p-4 text-white shadow-md flex justify-between items-center transition-all hover:scale-[1.02] duration-200">
        <div>
          <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-wider">Órdenes Procesadas</p>
          <h2 class="text-3xl font-black mt-1.5 font-mono">{{ orders.length }}</h2>
          <span class="text-[9px] bg-white/20 px-2 py-0.5 rounded-full inline-block mt-2 font-medium">↑ Transacciones</span>
        </div>
        <UIcon name="i-lucide-shopping-cart" class="w-12 h-12 text-white/30 shrink-0" />
      </div>

      <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-4 text-white shadow-md flex justify-between items-center transition-all hover:scale-[1.02] duration-200">
        <div>
          <p class="text-amber-100 text-[10px] font-bold uppercase tracking-wider">Ticket Promedio</p>
          <h2 class="text-3xl font-black mt-1.5 font-mono">{{ formatCurrency(avgOrder) }}</h2>
          <span class="text-[9px] bg-white/20 px-2 py-0.5 rounded-full inline-block mt-2 font-medium">⇆ Por Transacción</span>
        </div>
        <UIcon name="i-lucide-trending-up" class="w-12 h-12 text-white/30 shrink-0" />
      </div>

      <div class="bg-gradient-to-br from-fuchsia-500 to-purple-600 rounded-xl p-4 text-white shadow-md flex justify-between items-center transition-all hover:scale-[1.02] duration-200">
        <div>
          <p class="text-fuchsia-100 text-[10px] font-bold uppercase tracking-wider">Productos Vendidos</p>
          <h2 class="text-3xl font-black mt-1.5 font-mono">{{ totalProductsQty }} u.</h2>
          <span class="text-[9px] bg-white/20 px-2 py-0.5 rounded-full inline-block mt-2 font-medium">📦 Volumen total</span>
        </div>
        <UIcon name="i-lucide-package" class="w-12 h-12 text-white/30 shrink-0" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Resumen Financiero -->
      <UCard class="border border-slate-200 dark:border-slate-800 shadow-sm rounded-xl">
        <template #header>
          <div class="flex items-center gap-2">
            <UIcon name="i-lucide-bar-chart" class="text-emerald-500 w-5 h-5" />
            <h3 class="font-extrabold text-slate-800 dark:text-white">Resumen General</h3>
          </div>
        </template>
        <div class="space-y-4">
          <div class="flex justify-between items-center py-1">
            <span class="text-slate-500 font-semibold text-xs uppercase">Subtotal General:</span>
            <span class="font-bold text-slate-800 dark:text-white font-mono">{{ formatCurrency(sumSubtotal) }}</span>
          </div>
          <div class="flex justify-between items-center py-1 border-t border-slate-100 dark:border-slate-800">
            <span class="text-slate-500 font-semibold text-xs uppercase">Descuentos Aplicados (-):</span>
            <span class="font-bold text-rose-500 font-mono">{{ formatCurrency(sumDiscount) }}</span>
          </div>
          <div class="flex justify-between items-center pt-3 border-t border-slate-200 dark:border-slate-700 text-lg font-black">
            <span class="text-slate-800 dark:text-white">TOTAL FACTURADO:</span>
            <span class="text-emerald-600 dark:text-emerald-400 font-mono">{{ formatCurrency(totalVentasBs) }}</span>
          </div>
        </div>
      </UCard>

      <!-- Gráfico de Ventas Diarias -->
      <UCard>
        <template #header><h3 class="font-semibold text-lg">Evolución Diaria</h3></template>
        <div class="h-48">
          <Bar v-if="orders.length > 0" :data="salesByDayChartData" :items="{ responsive: true, maintainAspectRatio: false }" />
          <div v-else class="h-full flex items-center justify-center text-gray-400">Sin datos para graficar</div>
        </div>
      </UCard>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Top Productos -->
      <UCard>
        <template #header><h3 class="font-semibold text-lg">Top 10 Productos</h3></template>
        <div class="h-64 flex justify-center">
          <Doughnut v-if="sales.length > 0" :data="topProductsChartData" :items="{ responsive: true, maintainAspectRatio: false }" />
          <div v-else class="h-full flex items-center justify-center text-gray-400">Sin datos</div>
        </div>
      </UCard>

      <!-- Comparación Sucursales (Only if admin) -->
      <UCard v-if="statsByOffice.length > 0">
        <template #header><h3 class="font-semibold text-lg">Rendimiento por Sucursal</h3></template>
        <div class="h-64">
          <Bar :data="salesByOfficeChartData" :items="{ responsive: true, maintainAspectRatio: false, indexAxis: 'x' }" />
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
               <UBadge :color="row.original.status_order === 'Completada' ? 'success' : 'warning'" variant="subtle">
                 {{ row.original.status_order }}
               </UBadge>
             </template>
             <template #actions-cell="{ row }">
               <div class="flex items-center gap-1 justify-end">
                 <UButton color="neutral" variant="ghost" icon="i-lucide-file-text" size="sm" @click="viewPdf(row.original.id_order)">PDF</UButton>
                 <UButton color="primary" variant="ghost" icon="i-lucide-receipt" size="sm" title="Comprobante de pago" @click="openProof(row.original)">Comprobante</UButton>
               </div>
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

    <!-- Comprobantes de pago de la venta -->
    <UModal v-model:open="proofModal" :title="`Comprobante de pago · Orden ${proofOrder?.transaction_order || ''}`">
      <template #body>
        <div class="space-y-4">
          <!-- Comprobantes existentes -->
          <div v-if="proofLoading" class="py-6 flex justify-center">
            <UIcon name="i-lucide-loader-2" class="w-5 h-5 animate-spin text-slate-400" />
          </div>
          <div v-else-if="proofPayments.length" class="space-y-2">
            <div v-for="p in proofPayments" :key="p.id_sale_payment"
              class="flex items-center gap-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 rounded-lg p-3">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-700 dark:text-white capitalize">
                  {{ p.method_payment || 'Pago' }}
                  <span v-if="p.amount_payment" class="text-slate-400 font-normal">· Bs.{{ parseFloat(p.amount_payment).toFixed(2) }}</span>
                </p>
                <p v-if="p.reference_payment" class="text-xs text-slate-500 truncate">Ref: {{ p.reference_payment }}</p>
                <p class="text-xs text-slate-400">{{ p.date_created_payment }}<span v-if="p.name_admin"> · {{ p.name_admin }}</span></p>
              </div>
              <a v-if="p.file_payment" :href="`/${p.file_payment}`" target="_blank"
                class="text-primary-600 hover:text-primary-700 text-xs font-medium flex items-center gap-1">
                <UIcon name="i-lucide-eye" class="w-4 h-4" /> Ver
              </a>
              <span v-else class="text-xs text-amber-500 flex items-center gap-1">
                <UIcon name="i-lucide-alert-triangle" class="w-3.5 h-3.5" /> Sin archivo
              </span>
              <UButton color="neutral" variant="ghost" size="xs" icon="i-lucide-upload" title="Reemplazar archivo"
                :disabled="!newProofFile" @click="uploadProof(p.id_sale_payment)" />
              <UButton color="error" variant="ghost" size="xs" icon="i-lucide-trash-2" title="Eliminar"
                @click="deleteProof(p.id_sale_payment)" />
            </div>
          </div>
          <p v-else class="text-sm text-slate-500 text-center py-4">Esta venta aún no tiene comprobante de respaldo.</p>

          <!-- Adjuntar nuevo / archivo a reemplazar -->
          <div class="border-t border-slate-200 dark:border-slate-700 pt-3 space-y-2">
            <UFormField label="Adjuntar comprobante (imagen/PDF)" help="Máx 5MB. Use el botón ↑ de una fila para reemplazar el archivo de ese pago.">
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp,application/pdf"
                class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-300"
                @change="onNewProofChange"
              >
            </UFormField>
            <UFormField label="Referencia (opcional)">
              <UInput v-model="newProofRef" placeholder="N° de transacción / nota" class="w-full" />
            </UFormField>
            <p v-if="newProofFile" class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
              <UIcon name="i-lucide-paperclip" class="w-3.5 h-3.5" /> {{ newProofFile.name }}
            </p>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="proofModal = false">Cerrar</UButton>
          <UButton color="primary" icon="i-lucide-plus" :loading="proofUploading" @click="uploadProof()">Adjuntar comprobante</UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>
