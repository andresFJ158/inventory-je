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
  
  try {
    const apiHeaders = { Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }
    
    // First get exactly this cash row to know the time window.
    const cashRes = await $fetch<any>(`/api/relations?rel=cashs,offices&type=cash,office&linkTo=id_cash&equalTo=${cashId}`, { headers: apiHeaders })
    if (cashRes && cashRes.status === 200 && cashRes.results && cashRes.results.length > 0) {
      cashData.value = cashRes.results[0]
      const rawStart = cashData.value.date_created_cash
      const rawEnd = cashData.value.date_end_cash || new Date()
      
      const startDate = formatToMySQLDate(rawStart)
      const endDate = formatToMySQLDate(rawEnd)
      
      const officeId = cashData.value.id_office_cash
      
      // Get Expenses inside these dates for this office
      const expRes = await $fetch<any>(`/api/bills?linkTo=id_office_bill,date_created_bill&between1=${officeId},${startDate}&between2=${officeId},${endDate}`, { headers: apiHeaders })
      if (expRes && expRes.status === 200 && expRes.results) {
        expenses.value = expRes.results
      }
      
      // Get Sales inside these dates for this office
      const salesRes = await $fetch<any>(`/api/relations?rel=orders,clients&type=order,client&linkTo=id_office_order,date_order&between1=${officeId},${startDate}&between2=${officeId},${endDate}&select=transaction_order,date_order,method_order,total_order,name_client`, { headers: apiHeaders })
      if (salesRes && salesRes.status === 200 && salesRes.results) {
        // filter only completed ones if necessary
        sales.value = salesRes.results
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
  <UModal v-model="isOpenModel">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 relative rounded-xl h-[80vh] overflow-y-auto">
      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-10 h-10 animate-spin text-primary mb-2" />
        <p class="text-gray-500">Cargando detalles de caja...</p>
      </div>

      <!-- Content -->
      <template v-else-if="cashData">
        <div class="absolute top-4 right-4">
          <UButton color="neutral" variant="ghost" icon="i-lucide-x" @click="isOpenModel = false" />
        </div>

        <div class="mb-6">
          <h1 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
            <UIcon name="i-lucide-receipt" /> Detalles de Caja
          </h1>
          <p class="text-gray-500 text-sm mt-1">Caja #{{ cashData.id_cash }} - Sucursal: {{ decodeStr(cashData.title_office) }}</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
          <UCard class="bg-blue-50 dark:bg-blue-900/20 ring-blue-500/20">
            <div class="text-xs font-semibold text-blue-600 mb-1">Monto Inicial</div>
            <div class="text-xl font-bold">{{ formatCurrency(cashData.initial_cash) }}</div>
          </UCard>
          <UCard class="bg-green-50 dark:bg-green-900/20 ring-green-500/20">
            <div class="text-xs font-semibold text-green-600 mb-1">Ingresos (Ventas)</div>
            <div class="text-xl font-bold">{{ formatCurrency(cashData.money_cash) }}</div>
          </UCard>
          <UCard class="bg-red-50 dark:bg-red-900/20 ring-red-500/20">
            <div class="text-xs font-semibold text-red-600 mb-1">Gastos</div>
            <div class="text-xl font-bold">{{ formatCurrency(cashData.bills_cash) }}</div>
          </UCard>
          <UCard class="bg-indigo-50 dark:bg-indigo-900/20 ring-indigo-500/20">
            <div class="text-xs font-semibold text-indigo-600 mb-1">Total en Caja</div>
            <div class="text-xl font-bold">{{ formatCurrency(Number(cashData.initial_cash) + Number(cashData.money_cash) - Number(cashData.bills_cash)) }}</div>
          </UCard>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Ingresos -->
          <div>
            <h4 class="font-bold text-gray-700 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-800 pb-2 mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-arrow-up-right" class="text-green-500" /> Ingresos Registrados
            </h4>
            <div v-if="sales.length === 0" class="text-sm text-gray-500 italic">No hay ventas registradas en esta sesión.</div>
            <div v-else class="space-y-2 max-h-64 overflow-y-auto pr-2">
              <div v-for="sale in sales" :key="sale.id_order" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm border border-gray-100 dark:border-slate-700">
                <div>
                  <div class="font-bold">{{ sale.transaction_order }}</div>
                  <div class="text-xs text-gray-500">{{ decodeStr(sale.name_client) }}</div>
                </div>
                <div class="text-right">
                  <div class="font-bold text-green-600">+ {{ formatCurrency(sale.total_order) }}</div>
                  <div class="text-xs text-gray-400">{{ sale.date_order }}</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Gastos -->
          <div>
            <h4 class="font-bold text-gray-700 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-800 pb-2 mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-arrow-down-right" class="text-red-500" /> Gastos Registrados
            </h4>
            <div v-if="expenses.length === 0" class="text-sm text-gray-500 italic">No hay gastos registrados en esta sesión.</div>
            <div v-else class="space-y-2 max-h-64 overflow-y-auto pr-2">
              <div v-for="exp in expenses" :key="exp.id_bill" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm border border-gray-100 dark:border-slate-700">
                <div>
                  <div class="font-bold">{{ decodeStr(exp.description_bill) }}</div>
                </div>
                <div class="text-right">
                  <div class="font-bold text-red-500">- {{ formatCurrency(exp.amount_bill) }}</div>
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
        <UButton color="neutral" class="mt-4" @click="isOpenModel = false">Cerrar</UButton>
      </div>
    </div>
  </UModal>
</template>