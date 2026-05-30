<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

// State del Dashboard
const totalMaterials = ref(0)
const totalInProcess = ref(0)
const qualityChecks = ref(0)
const finalProductsStock = ref(0)
const recentActivities = ref<any[]>([])

const loading = ref(true)
const apiBase = '/ajax/pos.ajax.php'

async function fetchDashboardMetrics() {
  loading.value = true
  try {
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

      // Map recent activities from results
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
  } catch (error) {
    console.error('Error fetching dashboard metrics:', error)
  } finally {
    loading.value = false
  }
}

// Kept for backward compatibility and clean lifecycle
async function fetchRecentActivity() {}

onMounted(() => {
  fetchDashboardMetrics()
})

const kpis = computed(() => [
  { label: 'Materias Primas', val: String(totalMaterials.value), icon: 'i-lucide-droplet', color: 'text-blue-500 bg-blue-500/10' },
  { label: 'En Proceso', val: String(totalInProcess.value), icon: 'i-lucide-cog', color: 'text-amber-500 bg-amber-500/10' },
  { label: 'Ensayos Calidad', val: String(qualityChecks.value), icon: 'i-lucide-shield-check', color: 'text-emerald-500 bg-emerald-500/10' },
  { label: 'Productos Finales', val: `${finalProductsStock.value} u.`, icon: 'i-lucide-boxes', color: 'text-indigo-500 bg-indigo-500/10' }
])
</script>

<template>
  <div class="space-y-6">


    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div 
        v-for="kpi in kpis" 
        :key="kpi.label" 
        class="bg-white dark:bg-slate-950/40 backdrop-blur border border-slate-200 dark:border-slate-800/80 p-5 rounded-xl flex items-center justify-between hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-200 shadow-sm"
      >
        <div>
          <span class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-bold">{{ kpi.label }}</span>
          <h4 class="text-2xl font-black text-slate-800 dark:text-white mt-1">{{ kpi.val }}</h4>
        </div>
        <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', kpi.color]">
          <UIcon :name="kpi.icon" class="w-6 h-6" />
        </div>
      </div>
    </div>

    <!-- Widgets -->
    <div class="grid grid-cols-1 gap-6">
      <!-- Actividad Reciente Real -->
      <div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-xl space-y-4 shadow-sm">
        <h3 class="font-bold text-slate-800 dark:text-white tracking-wide flex items-center gap-2">
          <UIcon name="i-lucide-activity" class="text-green-500" />
          Actividad Reciente en Planta
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Últimos movimientos de producción de lotes registrados en tiempo real.</p>
        
        <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
          <div v-if="recentActivities.length === 0" class="text-sm text-slate-500 py-8 text-center">
            No se han registrado producciones recientes en este laboratorio.
          </div>
          <div v-else v-for="(act, i) in recentActivities" :key="i" class="flex gap-4 py-4 first:pt-0 last:pb-0 text-sm hover:bg-slate-50/50 dark:hover:bg-slate-800/10 px-2 rounded-lg transition-colors duration-150">
            <div class="mt-1">
              <span v-if="act.status === 'success'" class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
              </span>
              <span v-else-if="act.status === 'warning'" class="h-3 w-3 rounded-full bg-amber-500 block"></span>
              <span v-else class="h-3 w-3 rounded-full bg-blue-500 block"></span>
            </div>
            <div class="flex-1 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <div>
                <p class="text-slate-800 dark:text-slate-200 font-bold leading-none">{{ act.user }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1.5 leading-snug">{{ act.action }}</p>
              </div>
              <span class="text-xs text-slate-450 dark:text-slate-500 font-mono font-bold whitespace-nowrap bg-slate-100 dark:bg-slate-900 px-2.5 py-1 rounded">{{ act.time }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
