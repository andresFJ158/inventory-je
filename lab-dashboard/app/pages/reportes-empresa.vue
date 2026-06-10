<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, LineElement, PointElement } from 'chart.js'
import { Bar, Doughnut } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, LineElement, PointElement)

definePageMeta({ middleware: ['auth'] })

const auth = useAuthStore()
const api = useApi()
const toast = useToast()

const orders = ref<any[]>([])
const sales = ref<any[]>([])
const offices = ref<any[]>([])
const loading = ref(true)

const today = new Date()
const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1)
const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0)

const startDate = ref(firstDayLastMonth.toISOString().split('T')[0] || '')
const endDate = ref(lastDayLastMonth.toISOString().split('T')[0] || '')

async function fetchOffices() {
  const data = await api.rest<any>('/api/offices')
  if (data?.status === 200) offices.value = data.results || []
}

async function fetchReportData() {
  if (startDate.value > endDate.value) {
    toast.add({ title: 'Rango inválido', description: 'La fecha inicial no puede ser posterior a la final', color: 'warning' })
    return
  }
  loading.value = true
  try {
    const orderParams = new URLSearchParams({ rel: 'orders', type: 'order', orderBy: 'id_order', orderMode: 'DESC' })
    const saleParams = new URLSearchParams({ rel: 'sales,products', type: 'sale,product', orderBy: 'id_sale', orderMode: 'DESC' })

    if (startDate.value === endDate.value) {
      orderParams.set('linkTo', 'date_created_order')
      orderParams.set('equalTo', startDate.value)
      saleParams.set('linkTo', 'date_created_sale')
      saleParams.set('equalTo', startDate.value)
    } else {
      orderParams.set('between1', 'date_created_order')
      orderParams.set('between2', `${startDate.value},${endDate.value}`)
      saleParams.set('between1', 'date_created_sale')
      saleParams.set('between2', `${startDate.value},${endDate.value}`)
    }

    const [oData, sData] = await Promise.all([
      api.rest<any>(`/api/relations?${orderParams.toString()}`),
      api.rest<any>(`/api/relations?${saleParams.toString()}`)
    ])

    orders.value = oData?.status === 200 ? (oData.results || []) : []
    sales.value = sData?.status === 200 ? (sData.results || []) : []
    
    if (offices.value.length === 0) await fetchOffices()
  } catch (e) {
    console.error(e)
    toast.add({ title: 'Error al cargar datos globales', color: 'error' })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (auth.role !== 'superadmin' && !(auth.role === 'admin' && auth.officeId === 0)) {
    return
  }
  fetchReportData()
})

function applyFilter() {
  fetchReportData()
}

// Global KPIs
const totalIncome = computed(() => orders.value.reduce((acc, o) => acc + parseFloat(o.total_order || 0), 0))
const totalDiscount = computed(() => orders.value.reduce((acc, o) => acc + parseFloat(o.discount_order || 0), 0))
const totalProductsSold = computed(() => sales.value.reduce((acc, s) => acc + parseInt(s.qty_sale || 0), 0))
const ticketAvg = computed(() => orders.value.length > 0 ? totalIncome.value / orders.value.length : 0)

// Company Wide Charts
const salesByOfficeChartData = computed(() => {
  const map: Record<string, number> = {}
  orders.value.forEach(o => {
    const oId = String(o.id_office_order)
    map[oId] = (map[oId] || 0) + parseFloat(o.total_order || 0)
  })
  
  const labels: string[] = []
  const data: number[] = []
  offices.value.forEach(office => {
    const val = map[String(office.id_office)] || 0
    if (val > 0) {
      labels.push(decodeURIComponent(office.title_office).replace(/\+/g, ' '))
      data.push(val)
    }
  })

  return {
    labels,
    datasets: [{
      label: 'Ingresos por Sucursal (Bs)',
      data,
      backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4'],
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
  
  const sortedProds = Object.entries(byProd).sort((a, b) => b[1] - a[1]).slice(0, 10)
    
  return {
    labels: sortedProds.map(p => p[0]),
    datasets: [{
      data: sortedProds.map(p => p[1] || 0),
      backgroundColor: ['#16a34a', '#2563eb', '#d97706', '#dc2626', '#7c3aed', '#059669', '#4f46e5', '#db2777', '#c026d3', '#0891b2']
    }]
  }
})

function formatCurrency(val: number) {
  return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(val)
}
</script>

<template>
  <div class="w-full space-y-6 relative" v-if="auth.role === 'superadmin' || auth.role === 'admin' || auth.officeId === 0">
    <div
      v-if="loading"
      class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 dark:bg-slate-950/70 rounded-lg"
    >
      <UIcon name="i-lucide-loader-circle" class="w-10 h-10 animate-spin text-indigo-600" />
    </div>
    
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
          <UIcon name="i-lucide-building-2" class="w-6 h-6 text-indigo-600" />
          Reporte General de Empresa
        </h1>
        <p class="text-sm text-slate-500 mt-1">Visión global consolidada de ingresos y rendimiento corporativo.</p>
      </div>

      <div class="flex items-center gap-2">
        <UInput type="date" v-model="startDate" size="sm" />
        <span class="text-slate-500">hasta</span>
        <UInput type="date" v-model="endDate" size="sm" />
        <UButton color="indigo" variant="solid" icon="i-lucide-filter" :loading="loading" @click="applyFilter">Analizar</UButton>
      </div>
    </div>

    <!-- KPIs Globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <UCard class="border-t-4 border-indigo-500">
        <div class="text-slate-500 text-sm font-semibold">Ingresos Totales (Global)</div>
        <div class="text-3xl font-black text-indigo-600">{{ formatCurrency(totalIncome) }}</div>
      </UCard>
      <UCard>
        <div class="text-slate-500 text-sm font-semibold">Transacciones Exitosas</div>
        <div class="text-2xl font-bold text-slate-700">{{ orders.length }}</div>
      </UCard>
      <UCard>
        <div class="text-slate-500 text-sm font-semibold">Promedio de Venta</div>
        <div class="text-2xl font-bold text-emerald-600">{{ formatCurrency(ticketAvg) }}</div>
      </UCard>
      <UCard>
        <div class="text-slate-500 text-sm font-semibold">Descuentos Otorgados</div>
        <div class="text-2xl font-bold text-red-500">{{ formatCurrency(totalDiscount) }}</div>
      </UCard>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <UCard>
        <template #header><h3 class="font-semibold text-lg flex items-center gap-2"><UIcon name="i-lucide-store" class="w-5 h-5"/> Ingresos por Sucursal</h3></template>
        <div class="h-80">
          <Bar v-if="orders.length > 0" :data="salesByOfficeChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
          <div v-else class="h-full flex items-center justify-center text-slate-400">Sin datos en el periodo</div>
        </div>
      </UCard>

      <UCard>
        <template #header><h3 class="font-semibold text-lg flex items-center gap-2"><UIcon name="i-lucide-trophy" class="w-5 h-5"/> Top 10 Productos Más Vendidos</h3></template>
        <div class="h-80 flex justify-center pb-4">
          <Doughnut v-if="sales.length > 0" :data="topProductsChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
          <div v-else class="h-full flex items-center justify-center text-slate-400">Sin datos en el periodo</div>
        </div>
      </UCard>
    </div>

    <UCard class="bg-indigo-50 dark:bg-indigo-950/20 border-indigo-100 dark:border-indigo-900">
      <div class="flex items-start gap-4">
        <UIcon name="i-lucide-info" class="w-6 h-6 text-indigo-500 mt-1 shrink-0" />
        <div>
          <h4 class="font-bold text-indigo-900 dark:text-indigo-300">Nota Confidencial</h4>
          <p class="text-sm text-indigo-800 dark:text-indigo-400 mt-1">Este panel muestra datos consolidados de la compañía sin filtros de sucursal ni vendedor. Su acceso está restringido únicamente a usuarios con rol de Super Administrador.</p>
        </div>
      </div>
    </UCard>

  </div>
  
  <div v-else class="py-20 text-center">
    <UIcon name="i-lucide-shield-alert" class="w-16 h-16 mx-auto text-red-500 mb-4" />
    <h2 class="text-2xl font-bold text-slate-800">Acceso Denegado</h2>
    <p class="text-slate-500 mt-2">No tienes los permisos necesarios para visualizar el consolidado de la empresa.</p>
  </div>
</template>
