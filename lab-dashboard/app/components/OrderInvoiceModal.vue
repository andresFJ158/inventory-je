<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps<{
  isOpen: boolean
  orderId: number | string | null
}>()

const emit = defineEmits(['update:isOpen', 'close'])

const loading = ref(false)
const orderData = ref<any>(null)
const productsData = ref<any[]>([])
const orderExpenses = ref<any[]>([])

const detailsClientExpensesTotal = computed(() => {
  return orderExpenses.value
    .filter((e: any) => parseInt(e.charge_to_client_expense) === 1)
    .reduce((sum: number, e: any) => sum + parseFloat(e.amount_expense || 0), 0)
})

const detailsCompanyExpensesTotal = computed(() => {
  return orderExpenses.value
    .filter((e: any) => parseInt(e.charge_to_client_expense) === 0)
    .reduce((sum: number, e: any) => sum + parseFloat(e.amount_expense || 0), 0)
})

const isOpenModel = computed({
  get: () => props.isOpen,
  set: (val) => {
    emit('update:isOpen', val)
    if (!val) emit('close')
  }
})

// Fetch full order + products details
async function fetchOrderDetails(id: string | number) {
  loading.value = true
  orderData.value = null
  productsData.value = []
  
  try {
    const apiHeaders = { Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }
    
    let orderRes = await $fetch<any>(`/api/relations?rel=orders,clients,admins,offices&type=order,client,admin,office&linkTo=id_order&equalTo=${id}`, { headers: apiHeaders }).catch(() => null)
    const salesRes = await $fetch<any>(`/api/relations?rel=sales,products&type=sale,product&linkTo=id_order_sale&equalTo=${id}`, { headers: apiHeaders }).catch(() => null)
    
    if (!orderRes || !orderRes.results || orderRes.results.length === 0) {
      // Fallback: order might not have a client (id_client_order = 0)
      orderRes = await $fetch<any>(`/api/relations?rel=orders,admins,offices&type=order,admin,office&linkTo=id_order&equalTo=${id}`, { headers: apiHeaders }).catch(() => null)
    }

    if (orderRes && orderRes.status === 200 && orderRes.results && orderRes.results.length > 0) {
      orderData.value = orderRes.results[0]
    }
    
    if (salesRes && salesRes.status === 200 && salesRes.results) {
      productsData.value = salesRes.results
    }

    // Fetch Expenses Info
    try {
      const expRes = await $fetch<any>('/ajax/pos.ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ getOrderExpenses: 'ok', id_order: String(id) }).toString()
      }).catch(() => null)
      const parsedExp = typeof expRes === 'string' ? JSON.parse(expRes) : expRes
      if (parsedExp && parsedExp.status === 200) {
        orderExpenses.value = parsedExp.results || []
      } else {
        orderExpenses.value = []
      }
    } catch (e) {
      console.error('Error fetching order expenses for receipt:', e)
      orderExpenses.value = []
    }
  } catch (e) {
    console.error('Error fetching order receipt data:', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (props.isOpen && props.orderId) {
    fetchOrderDetails(props.orderId)
  }
})

watch(() => props.isOpen, (newVal) => {
  if (newVal && props.orderId) {
    fetchOrderDetails(props.orderId)
  }
})

function handlePrint() {
  window.print()
}

// Helpers for string decoding
function decodeStr(str: any) {
  if (!str) return ''
  return decodeURIComponent(String(str)).replace(/\+/g, ' ')
}

function formatCurrency(val: any) {
  const num = parseFloat(val) || 0
  return `Bs. ${num.toFixed(2)}`
}
</script>

<template>
  <div>
    <UModal v-model:open="isOpenModel" :ui="{ width: 'max-w-4xl' }">
    <template #body>
      <!-- Action Buttons (Hidden in print) -->
      <div class="print-hide flex justify-end gap-2 mb-4 border-b border-gray-800 pb-2">
        <UButton color="primary" class="bg-blue-600" icon="i-lucide-printer" @click="handlePrint">Imprimir</UButton>
        <UButton color="neutral" variant="ghost" icon="i-lucide-x" @click="isOpenModel = false" />
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-10 h-10 animate-spin text-teal-500 mb-2" />
        <p class="text-slate-400">Cargando comprobante...</p>
      </div>

      <!-- Modal Content (Screen View) -->
      <div v-else-if="orderData" class="screen-container bg-white text-black p-8 font-sans max-w-full mx-auto shadow-xl print-hide border border-slate-200">
        <div class="space-y-6">
          
          <!-- Header: Logo, Company Name, and Office -->
          <div class="flex justify-between items-start border-b-2 border-slate-200 pb-6">
            <div class="flex items-center gap-4">
              <img src="/Logo.png" alt="Logo" class="h-16 object-contain" />
              <h1 class="text-3xl font-black text-slate-800 tracking-tight">J.E Bolivia ERP</h1>
            </div>
            <div class="text-right">
              <h2 class="text-xl font-bold text-slate-700">FACTURA ELECTRÓNICA</h2>
              <p class="text-sm text-slate-500 mt-1">N° Orden: <span class="font-bold text-black">{{ orderData.transaction_order }}</span></p>
              <p class="text-sm text-slate-500">Fecha: <span class="font-bold text-black">{{ orderData.date_order }}</span></p>
            </div>
          </div>

          <!-- Details: Client and Seller -->
          <div class="grid grid-cols-2 gap-8 border-b-2 border-slate-200 pb-6">
            <div>
              <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Datos del Cliente</h3>
              <p class="font-bold text-lg">{{ decodeStr(orderData.name_client) }} {{ decodeStr(orderData.surname_client) }}</p>
              <p class="text-sm text-slate-600">Documento / NIT: {{ orderData.dni_client }}</p>
            </div>
            <div>
              <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Datos de Venta</h3>
              <p class="text-sm text-slate-600"><strong>Sucursal:</strong> {{ decodeStr(orderData.title_office) }}</p>
              <p class="text-sm text-slate-600"><strong>NIT Sucursal:</strong> {{ orderData.dni_office || '0000000' }}</p>
              <p class="text-sm text-slate-600"><strong>Vendedor:</strong> {{ decodeStr(orderData.name_admin) }}</p>
              <p class="text-sm text-slate-600"><strong>Método de Pago:</strong> <span class="capitalize">{{ orderData.method_order }}</span></p>
            </div>
          </div>

          <!-- Products Table -->
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-100">
                <th class="p-3 text-sm font-bold text-slate-700 border-b border-slate-200">Cant</th>
                <th class="p-3 text-sm font-bold text-slate-700 border-b border-slate-200">Descripción</th>
                <th class="p-3 text-sm font-bold text-slate-700 border-b border-slate-200 text-right">P. Unitario</th>
                <th class="p-3 text-sm font-bold text-slate-700 border-b border-slate-200 text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(prod, i) in productsData" :key="i" class="border-b border-slate-100 last:border-0">
                <td class="p-3 text-sm">{{ prod.qty_sale }}</td>
                <td class="p-3 text-sm font-medium">{{ decodeStr(prod.title_product) }}</td>
                <td class="p-3 text-sm text-right">{{ formatCurrency((parseFloat(prod.subtotal_sale) / parseInt(prod.qty_sale)).toFixed(2)) }}</td>
                <td class="p-3 text-sm text-right font-bold">{{ formatCurrency(prod.subtotal_sale) }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Expenses and Totals -->
          <div class="flex justify-end pt-6">
            <div class="w-1/2 space-y-3">
              <div v-if="orderExpenses.length > 0" class="mb-4">
                <h4 class="text-sm font-bold text-slate-600 mb-2 border-b border-slate-200 pb-1">Detalle de Gastos</h4>
                <div v-for="exp in orderExpenses" :key="exp.id_expense" class="flex justify-between text-sm">
                  <span class="text-slate-600">{{ exp.concept_expense }} (Cobrado: {{ exp.charge_to_client_expense == 1 ? 'Sí' : 'No' }}):</span>
                  <span>{{ formatCurrency(exp.amount_expense) }}</span>
                </div>
              </div>

              <div class="flex justify-between text-sm">
                <span class="text-slate-500">Subtotal:</span>
                <span class="font-medium">{{ formatCurrency(orderData.subtotal_order) }}</span>
              </div>
              <div v-if="parseFloat(orderData.discount_order) > 0" class="flex justify-between text-sm">
                <span class="text-slate-500">Descuento:</span>
                <span class="text-red-500">-{{ formatCurrency(orderData.discount_order) }}</span>
              </div>
              <div v-if="detailsClientExpensesTotal > 0" class="flex justify-between text-sm">
                <span class="text-slate-500">Gastos a cobrar al cliente:</span>
                <span>+{{ formatCurrency(detailsClientExpensesTotal) }}</span>
              </div>
              <div v-if="detailsCompanyExpensesTotal > 0" class="flex justify-between text-sm">
                <span class="text-slate-500">Gastos asumidos (Almacén):</span>
                <span>-{{ formatCurrency(detailsCompanyExpensesTotal) }}</span>
              </div>
              
              <div class="flex justify-between items-center border-t-2 border-slate-800 pt-3 mt-3">
                <span class="text-lg font-black">TOTAL A PAGAR:</span>
                <span class="text-2xl font-black text-emerald-600">{{ formatCurrency(Number(orderData.total_order || 0) + detailsClientExpensesTotal - detailsCompanyExpensesTotal) }}</span>
              </div>
            </div>
          </div>
          
          <div class="text-center text-slate-500 text-sm mt-12 pt-8 border-t border-slate-200">
            <p class="font-bold text-lg mb-1">¡Gracias por su compra, vuelva pronto!</p>
            <p>Este documento es una representación impresa de una factura electrónica.</p>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else class="py-12 text-center">
        <UIcon name="i-lucide-alert-triangle" class="w-12 h-12 text-rose-500 mx-auto mb-3" />
        <p class="text-lg font-medium text-slate-300">Comprobante no encontrado</p>
        <UButton color="neutral" class="mt-4" @click="isOpenModel = false">Cerrar</UButton>
      </div>
    </template>
  </UModal>

  <!-- Print Layout (Rendered OUTSIDE UModal so it prints properly) -->
  <Teleport to="body">
    <div v-if="orderData && isOpenModel" class="print-container bg-white text-black font-sans w-full hidden print:block">
      <div class="space-y-6">
        <!-- Header: Logo, Company Name, and Office -->
        <div class="flex justify-between items-start border-b-2 border-slate-200 pb-6">
          <div class="flex items-center gap-4">
            <img src="/Logo.png" alt="Logo" class="h-16 object-contain" />
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">J.E Bolivia ERP</h1>
          </div>
          <div class="text-right">
            <h2 class="text-xl font-bold text-slate-700">FACTURA ELECTRÓNICA</h2>
            <p class="text-sm text-slate-500 mt-1">N° Orden: <span class="font-bold text-black">{{ orderData.transaction_order }}</span></p>
            <p class="text-sm text-slate-500">Fecha: <span class="font-bold text-black">{{ orderData.date_order }}</span></p>
          </div>
        </div>

        <!-- Details: Client and Seller -->
        <div class="grid grid-cols-2 gap-8 border-b-2 border-slate-200 pb-6">
          <div>
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Datos del Cliente</h3>
            <p class="font-bold text-lg">{{ decodeStr(orderData.name_client) }} {{ decodeStr(orderData.surname_client) }}</p>
            <p class="text-sm text-slate-600">Documento / NIT: {{ orderData.dni_client }}</p>
          </div>
          <div>
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Datos de Venta</h3>
            <p class="text-sm text-slate-600"><strong>Sucursal:</strong> {{ decodeStr(orderData.title_office) }}</p>
            <p class="text-sm text-slate-600"><strong>NIT Sucursal:</strong> {{ orderData.dni_office || '0000000' }}</p>
            <p class="text-sm text-slate-600"><strong>Vendedor:</strong> {{ decodeStr(orderData.name_admin) }}</p>
            <p class="text-sm text-slate-600"><strong>Método de Pago:</strong> <span class="capitalize">{{ orderData.method_order }}</span></p>
          </div>
        </div>

        <!-- Products Table -->
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-100">
              <th class="p-3 text-sm font-bold text-slate-700 border-b border-slate-200">Cant</th>
              <th class="p-3 text-sm font-bold text-slate-700 border-b border-slate-200">Descripción</th>
              <th class="p-3 text-sm font-bold text-slate-700 border-b border-slate-200 text-right">P. Unitario</th>
              <th class="p-3 text-sm font-bold text-slate-700 border-b border-slate-200 text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(prod, i) in productsData" :key="i" class="border-b border-slate-100 last:border-0">
              <td class="p-3 text-sm">{{ prod.qty_sale }}</td>
              <td class="p-3 text-sm font-medium">{{ decodeStr(prod.title_product) }}</td>
              <td class="p-3 text-sm text-right">{{ formatCurrency((parseFloat(prod.subtotal_sale) / parseInt(prod.qty_sale)).toFixed(2)) }}</td>
              <td class="p-3 text-sm text-right font-bold">{{ formatCurrency(prod.subtotal_sale) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Expenses and Totals -->
        <div class="flex justify-end pt-6">
          <div class="w-1/2 space-y-3">
            <div v-if="orderExpenses.length > 0" class="mb-4">
              <h4 class="text-sm font-bold text-slate-600 mb-2 border-b border-slate-200 pb-1">Detalle de Gastos</h4>
              <div v-for="exp in orderExpenses" :key="exp.id_expense" class="flex justify-between text-sm">
                <span class="text-slate-600">{{ exp.concept_expense }} (Cobrado: {{ exp.charge_to_client_expense == 1 ? 'Sí' : 'No' }}):</span>
                <span>{{ formatCurrency(exp.amount_expense) }}</span>
              </div>
            </div>

            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Subtotal:</span>
              <span class="font-medium">{{ formatCurrency(orderData.subtotal_order) }}</span>
            </div>
            <div v-if="parseFloat(orderData.discount_order) > 0" class="flex justify-between text-sm">
              <span class="text-slate-500">Descuento:</span>
              <span class="text-red-500">-{{ formatCurrency(orderData.discount_order) }}</span>
            </div>
            <div v-if="detailsClientExpensesTotal > 0" class="flex justify-between text-sm">
              <span class="text-slate-500">Gastos a cobrar al cliente:</span>
              <span>+{{ formatCurrency(detailsClientExpensesTotal) }}</span>
            </div>
            <div v-if="detailsCompanyExpensesTotal > 0" class="flex justify-between text-sm">
              <span class="text-slate-500">Gastos asumidos (Almacén):</span>
              <span>-{{ formatCurrency(detailsCompanyExpensesTotal) }}</span>
            </div>
            
            <div class="flex justify-between items-center border-t-2 border-slate-800 pt-3 mt-3">
              <span class="text-lg font-black">TOTAL A PAGAR:</span>
              <span class="text-2xl font-black text-emerald-600">{{ formatCurrency(Number(orderData.total_order || 0) + detailsClientExpensesTotal - detailsCompanyExpensesTotal) }}</span>
            </div>
          </div>
        </div>
        
        <div class="text-center text-slate-500 text-sm mt-12 pt-8 border-t border-slate-200">
          <p class="font-bold text-lg mb-1">¡Gracias por su compra, vuelva pronto!</p>
          <p>Este documento es una representación impresa de una factura electrónica.</p>
        </div>
      </div>
    </div>
  </Teleport>
  </div>
</template>

<style>
@media print {
  /* Ocultar todo en body excepto print-container (que está teleportado) */
  body > :not(.print-container) {
    display: none !important;
  }
  
  body {
    background-color: white !important;
  }
  
  .print-container {
    display: block !important;
    position: static !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background-color: white !important;
    color: black !important;
  }
  
  .print-container * {
    visibility: visible !important;
  }
  
  @page {
    size: letter;
    margin: 0.5in;
  }
  
  .print-hide {
    display: none !important;
  }
}
</style>
