<script setup lang="ts">
import { ref, watch, computed } from 'vue'

const props = defineProps<{
  isOpen: boolean
  cash: any
}>()

const emit = defineEmits(['update:isOpen', 'close'])

const loading = ref(false)
const cashData = ref<any>(null)
const expenses = ref<any[]>([])
const sales = ref<any[]>([])
const incomes = ref<any[]>([])

const salesEfectivo = computed(() => sales.value.filter((s: any) => s.method_order !== 'QR'))
const salesQR = computed(() => sales.value.filter((s: any) => s.method_order === 'QR'))
const isOpenModel = computed({
  get: () => props.isOpen,
  set: (val) => {
    emit('update:isOpen', val)
    if (!val) emit('close')
  }
})

// Formats Date to YYYY-MM-DD HH:mm:ss for MySQL
function formatToMySQLDate(dateInput: string | Date | null): string {
  if (!dateInput) return ''
  const d = new Date(dateInput)
  if (isNaN(d.getTime())) return ''
  return d.toISOString().replace('T', ' ').split('.')[0] || ''
}

// Fetch full cash details
async function fetchCashDetails(cashId: string | number) {
  loading.value = true
  cashData.value = null
  expenses.value = []
  sales.value = []
  incomes.value = []
  
  try {
    const apiHeaders = { Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }
    
    // First get exactly this cash row to know the time window.
    const cashRes = await $fetch<any>(`/api/relations?rel=cashs,offices&type=cash,office&linkTo=id_cash&equalTo=${cashId}`, { headers: apiHeaders })
    if (cashRes && cashRes.status === 200 && cashRes.results && cashRes.results.length > 0) {
      cashData.value = cashRes.results[0]
      const rawStart = cashData.value.date_start_cash
      const rawEnd = cashData.value.date_end_cash && cashData.value.date_end_cash !== '0000-00-00 00:00:00' ? cashData.value.date_end_cash : ''
      
      const startDate = rawStart
      const endDate = rawEnd || formatToMySQLDate(new Date())
      
      const officeId = cashData.value.id_office_cash
      
      // Update with exact computed totals from PHP backend
      const detailsBody = new URLSearchParams({ getCashDetails: 'ok', id_cash: String(cashId) })
      const detailsRes = await $fetch<any>('/ajax/pos.ajax.php', { method: 'POST', body: detailsBody })
      const parsedDetails = typeof detailsRes === 'string' ? JSON.parse(detailsRes) : detailsRes
      if (parsedDetails.status === 200) {
        Object.assign(cashData.value, parsedDetails.results) // merge accurate totals into cashData
      }

      // Get Expenses for this cash register session
      const expRes = await $fetch<any>(`/api/bills?linkTo=id_cash_bill&equalTo=${cashId}`, { headers: apiHeaders })
      if (expRes && expRes.status === 200 && expRes.results) {
        expenses.value = expRes.results
      }
      
      // Get Incomes Extra for this cash register session
      const incRes = await $fetch<any>(`/api/incomes?linkTo=id_cash_income&equalTo=${cashId}`, { headers: apiHeaders })
      if (incRes && incRes.status === 200 && incRes.results) {
        incomes.value = incRes.results
      }

      // Get Sales inside these dates for this office
      const salesRes = await $fetch<any>(`/api/relations?rel=orders,clients&type=order,client&linkTo=date_order&between1=${startDate}&between2=${endDate}&filterTo=id_office_order&inTo=${officeId}&select=transaction_order,date_order,method_order,total_order,status_order,name_client`, { headers: apiHeaders })
      if (salesRes && salesRes.status === 200 && salesRes.results) {
        // filter only completed ones
        sales.value = salesRes.results.filter((s: any) => s.status_order === 'Completada' || s.status_order === 'Venta Confirmada')
      }
    }
  } catch (e) {
    console.error('Error fetching cash details:', e)
  } finally {
    loading.value = false
  }
}

watch(() => props.isOpen, (newVal) => {
  if (newVal && props.cash?.id_cash) {
    fetchCashDetails(props.cash.id_cash)
  }
})

function formatCurrency(val: number | string) {
  return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(Number(val))
}

function decodeStr(str: string) {
  if (!str) return ''
  return decodeURIComponent(str).replace(/\+/g, ' ')
}
</script>

<template>
  <USlideover
    v-model:open="isOpenModel"
    title="Detalles de Caja"
    class="z-50"
    :ui="{ content: 'sm:max-w-4xl w-full' }"
  >
    <template #body>
      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-10 h-10 animate-spin text-primary mb-2" />
        <p class="text-gray-500">Cargando detalles de caja...</p>
      </div>

      <!-- Content -->
      <template v-else-if="cashData">
        <div class="mb-6">
          <p class="text-gray-500 text-sm mt-1">Caja #{{ cashData.id_cash }} - Sucursal: {{ decodeStr(cashData.title_office) }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
          <div class="bg-blue-50/50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/50 rounded-xl p-4 flex flex-col justify-between transition-all hover:scale-[1.02] duration-200">
            <div class="text-xs sm:text-lg font-semibold text-blue-600 dark:text-blue-400 mb-2">Monto Inicial</div>
            <div class="text-3xl sm:text-4xl font-black text-blue-900 dark:text-blue-200 break-words leading-tight">
              {{ formatCurrency(cashData.start_cash) }}
            </div>
          </div>
          <div class="bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50 rounded-xl p-4 flex flex-col justify-between transition-all hover:scale-[1.02] duration-200">
            <div class="text-xs sm:text-lg font-semibold text-emerald-600 dark:text-emerald-400 mb-2">Ingresos y Ventas</div>
            <div class="text-3xl sm:text-4xl font-black text-emerald-900 dark:text-emerald-200 break-words leading-tight">
              {{ formatCurrency(cashData.money_cash) }}
            </div>
            <div class="mt-2 text-[10px] sm:text-xs text-emerald-700 dark:text-emerald-500 font-semibold flex flex-col gap-0.5">
              <span>Efectivo: {{ formatCurrency(cashData.cash_efectivo || 0) }}</span>
              <span>QR: {{ formatCurrency(cashData.cash_qr || 0) }}</span>
            </div>
          </div>
          <div class="bg-rose-50/50 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50 rounded-xl p-4 flex flex-col justify-between transition-all hover:scale-[1.02] duration-200">
            <div class="text-xs sm:text-lg font-semibold text-rose-600 dark:text-rose-400 mb-2">Gastos</div>
            <div class="text-3xl sm:text-4xl font-black text-rose-900 dark:text-rose-200 break-words leading-tight">
              {{ formatCurrency(cashData.bills_cash) }}
            </div>
          </div>
          <div class="bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 rounded-xl p-4 flex flex-col justify-between transition-all hover:scale-[1.02] duration-200">
            <div class="text-xs sm:text-lg font-semibold text-indigo-600 dark:text-indigo-400 mb-2">Total Esperado</div>
            <div class="text-3xl sm:text-4xl font-black text-indigo-900 dark:text-indigo-200 break-words leading-tight">
              {{ formatCurrency(cashData.end_cash) }}
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Ingresos Efectivo -->
          <div>
            <h4 class="font-bold text-gray-700 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-800 pb-2 mb-3 flex items-center gap-2 text-sm">
              <UIcon name="i-lucide-banknote" class="text-emerald-500" /> Efectivo Registrado
            </h4>
            
            <div v-if="salesEfectivo.length === 0 && incomes.length === 0" class="text-center py-6 text-gray-400 text-sm italic">
              No hay pagos en efectivo.
            </div>
            <div v-else class="space-y-3">
              <div v-for="inc in incomes" :key="'inc-'+inc.id_income" class="flex justify-between items-center p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/50">
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate">{{ decodeStr(inc.concept_income) }}</div>
                  <div class="text-[10px] text-gray-500 font-mono mt-0.5 flex items-center gap-2">
                    {{ new Date(inc.date_income).toLocaleString('es-ES') }}
                    <UBadge color="emerald" size="xs" variant="subtle">EFECTIVO</UBadge>
                  </div>
                </div>
                <div class="font-bold text-emerald-600 dark:text-emerald-400 font-mono text-sm shrink-0 ml-3">
                  +{{ formatCurrency(inc.amount_income) }}
                </div>
              </div>
              <div v-for="sale in salesEfectivo" :key="'sale-ef-'+sale.id_order" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm border border-gray-100 dark:border-slate-700">
                <div>
                  <div class="font-bold text-slate-800 dark:text-slate-200">{{ sale.transaction_order }}</div>
                  <div class="text-xs text-gray-500 flex items-center gap-2 mt-1">
                    <span>{{ decodeStr(sale.name_client) }}</span>
                    <UBadge color="emerald" size="xs" variant="subtle" class="uppercase">EFECTIVO</UBadge>
                  </div>
                </div>
                <div class="text-right">
                  <div class="font-bold text-emerald-600">+ {{ formatCurrency(sale.total_order) }}</div>
                  <div class="text-[10px] text-gray-400 font-mono mt-0.5">{{ new Date(sale.date_order).toLocaleString('es-ES') }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Ingresos QR -->
          <div>
            <h4 class="font-bold text-gray-700 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-800 pb-2 mb-3 flex items-center gap-2 text-sm">
              <UIcon name="i-lucide-qr-code" class="text-purple-500" /> QR Registrado
            </h4>
            
            <div v-if="salesQR.length === 0" class="text-center py-6 text-gray-400 text-sm italic">
              No hay pagos con QR.
            </div>
            <div v-else class="space-y-3">
              <div v-for="sale in salesQR" :key="'sale-qr-'+sale.id_order" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm border border-gray-100 dark:border-slate-700">
                <div>
                  <div class="font-bold text-slate-800 dark:text-slate-200">{{ sale.transaction_order }}</div>
                  <div class="text-xs text-gray-500 flex items-center gap-2 mt-1">
                    <span>{{ decodeStr(sale.name_client) }}</span>
                    <UBadge color="purple" size="xs" variant="subtle" class="uppercase">QR</UBadge>
                  </div>
                </div>
                <div class="text-right">
                  <div class="font-bold text-purple-600">+ {{ formatCurrency(sale.total_order) }}</div>
                  <div class="text-[10px] text-gray-400 font-mono mt-0.5">{{ new Date(sale.date_order).toLocaleString('es-ES') }}</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Gastos -->
          <div>
            <h4 class="font-bold text-gray-700 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-800 pb-2 mb-3 flex items-center gap-2 text-sm">
              <UIcon name="i-lucide-arrow-down-right" class="text-red-500" /> Gastos Registrados
            </h4>
            <div v-if="expenses.length === 0" class="text-sm text-gray-500 italic">No hay gastos registrados en esta sesión.</div>
            <div v-else class="space-y-2 max-h-64 overflow-y-auto pr-2">
              <div v-for="exp in expenses" :key="exp.id_bill" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm border border-gray-100 dark:border-slate-700">
                <div>
                  <div class="font-bold">{{ decodeStr(exp.concept_bill) }}</div>
                </div>
                <div class="text-right">
                  <div class="font-bold text-red-500">- {{ formatCurrency(exp.cost_bill) }}</div>
                  <div class="text-xs text-gray-400">{{ exp.date_created_bill }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mt-6 flex justify-between items-center text-sm text-gray-500 border-t border-gray-100 dark:border-slate-800 pt-4">
          <div>Apertura: <strong>{{ cashData.date_created_cash }}</strong></div>
          <div v-if="cashData.date_end_cash">Cierre: <strong>{{ cashData.date_end_cash }}</strong></div>
          <div v-else class="text-emerald-500 font-medium flex items-center gap-1">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            Sesión Activa
          </div>
        </div>
      </template>

      <!-- Error State -->
      <div v-else class="py-12 text-center">
        <UIcon name="i-lucide-alert-triangle" class="w-12 h-12 text-red-400 mx-auto mb-3" />
        <p class="text-lg font-medium text-gray-700 dark:text-gray-300">No se pudieron cargar los detalles</p>
      </div>
    </template>
    <template #footer>
      <div class="flex justify-end gap-3 w-full">
        <UButton color="neutral" variant="ghost" @click="isOpenModel = false">Cerrar</UButton>
      </div>
    </template>
  </USlideover>
</template>