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
  return d.toISOString().replace('T', ' ').split('.')[0]
}

// Fetch full cash details
async function fetchCashDetails(cashId: string | number) {
  loading.value = true
  cashData.value = null
  expenses.value = []
  sales.value = []
  
  try {
    const data = await $fetch<any>(`/ajax/cash-details.ajax.php?id_cash=${cashId}`)
    if (data && data.status === 200) {
      cashData.value = {
        ...data.cash,
        money_cash: data.totalSales,
        bills_cash: data.totalBills,
        title_office: props.cash?.title_office || `Sucursal ${data.cash.id_office_cash}`
      }
      
      // Map bills to match template expectations
      expenses.value = (data.bills || []).map((exp: any) => ({
        id_bill: exp.id_bill,
        description_bill: exp.concept_bill,
        amount_bill: exp.cost_bill,
        date_created_bill: exp.date_bill
      }))
      
      // Map orders to match template expectations
      sales.value = (data.orders || []).map((sale: any) => ({
        id_order: sale.id_order,
        transaction_order: sale.transaction_order,
        name_client: sale.name_client || 'Cliente General',
        total_order: sale.total_order,
        date_order: sale.date_order
      }))
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
  <UModal v-model:open="isOpenModel" title="Detalles de Caja" :ui="{ content: 'w-full sm:max-w-7xl lg:max-w-[90vw] xl:max-w-[85vw]', body: 'max-h-[85vh] overflow-y-auto' }">
    <template #body>
      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-10 h-10 animate-spin text-primary mb-2" />
        <p class="text-gray-500">Cargando detalles de caja...</p>
      </div>

      <!-- Content -->
      <template v-else-if="cashData">
        <div class="mb-6 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg border border-slate-100 dark:border-slate-800">
          <p class="text-gray-500 dark:text-gray-400 text-sm">
            Caja #{{ cashData.id_cash }} · Sucursal: <strong>{{ decodeStr(cashData.title_office) }}</strong>
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <!-- Monto Inicial -->
          <div class="bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 p-4 rounded-xl flex items-center justify-between gap-4">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Monto Inicial</p>
              <p class="text-lg sm:text-xl xl:text-2xl font-extrabold text-blue-950 dark:text-blue-50 tracking-tight mt-1">{{ formatCurrency(cashData.start_cash) }}</p>
            </div>
            <div class="p-3 bg-blue-500/10 dark:bg-blue-500/20 rounded-xl text-blue-600 dark:text-blue-400 shrink-0">
              <UIcon name="i-lucide-wallet" class="w-6 h-6" />
            </div>
          </div>

          <!-- Ingresos -->
          <div class="bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40 p-4 rounded-xl flex items-center justify-between gap-4">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Ingresos</p>
              <p class="text-lg sm:text-xl xl:text-2xl font-extrabold text-emerald-950 dark:text-emerald-50 tracking-tight mt-1">{{ formatCurrency(cashData.money_cash) }}</p>
            </div>
            <div class="p-3 bg-emerald-500/10 dark:bg-emerald-500/20 rounded-xl text-emerald-600 dark:text-emerald-400 shrink-0">
              <UIcon name="i-lucide-arrow-up-right" class="w-6 h-6" />
            </div>
          </div>

          <!-- Gastos -->
          <div class="bg-rose-50/50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/40 p-4 rounded-xl flex items-center justify-between gap-4">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Gastos</p>
              <p class="text-lg sm:text-xl xl:text-2xl font-extrabold text-rose-950 dark:text-rose-50 tracking-tight mt-1">{{ formatCurrency(cashData.bills_cash) }}</p>
            </div>
            <div class="p-3 bg-rose-500/10 dark:bg-rose-500/20 rounded-xl text-rose-600 dark:text-rose-400 shrink-0">
              <UIcon name="i-lucide-arrow-down-right" class="w-6 h-6" />
            </div>
          </div>

          <!-- Total en Caja -->
          <div class="bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 p-4 rounded-xl flex items-center justify-between gap-4">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Total en Caja</p>
              <p class="text-lg sm:text-xl xl:text-2xl font-extrabold text-indigo-950 dark:text-indigo-50 tracking-tight mt-1">{{ formatCurrency(Number(cashData.start_cash) + Number(cashData.money_cash) - Number(cashData.bills_cash)) }}</p>
            </div>
            <div class="p-3 bg-indigo-500/10 dark:bg-indigo-500/20 rounded-xl text-indigo-600 dark:text-indigo-400 shrink-0">
              <UIcon name="i-lucide-banknote" class="w-6 h-6" />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Ingresos -->
          <div>
            <h4 class="font-bold text-gray-700 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-800 pb-2 mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-arrow-up-right" class="text-green-500" /> Ingresos Registrados
            </h4>
            <div v-if="sales.length === 0" class="text-sm text-gray-500 italic">No hay ventas registradas en esta sesión.</div>
            <div v-else class="space-y-2 max-h-[500px] overflow-y-auto pr-2">
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
            <div v-else class="space-y-2 max-h-[500px] overflow-y-auto pr-2">
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
      </div>
    </template>
    <template #footer>
      <div class="flex justify-end gap-2 w-full">
        <UButton color="neutral" variant="ghost" size="sm" @click="isOpenModel = false">Cerrar</UButton>
      </div>
    </template>
  </UModal>
</template>
