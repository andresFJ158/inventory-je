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
    
    const [orderRes, salesRes] = await Promise.all([
      $fetch<any>(`/api/relations?rel=orders,clients,admins,offices&type=order,client,admin,office&linkTo=id_order&equalTo=${id}`, { headers: apiHeaders }),
      $fetch<any>(`/api/relations?rel=sales,products&type=sale,product&linkTo=id_order_sale&equalTo=${id}`, { headers: apiHeaders })
    ])
    
    if (orderRes && orderRes.status === 200 && orderRes.results && orderRes.results.length > 0) {
      orderData.value = orderRes.results[0]
    }
    
    if (salesRes && salesRes.status === 200 && salesRes.results) {
      productsData.value = salesRes.results
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
  <UModal v-model:open="isOpenModel" :ui="{ width: 'max-w-md' }">
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

      <!-- Receipt Content (Matches POS format) -->
      <div v-else-if="orderData" class="print-container bg-white text-black p-4 text-xs font-mono w-72 mx-auto">
        <div class="space-y-2">
          <div class="text-center font-bold text-sm">JE INVENTARIO & VENTAS</div>
          <div class="text-center">Sucursal: {{ decodeStr(orderData.title_office) }}</div>
          <div class="text-center">NIT: {{ orderData.dni_office || '0000000' }}</div>
          <hr class="border-dashed border-black">
          <div><strong>Orden:</strong> {{ orderData.transaction_order }}</div>
          <div><strong>Fecha:</strong> {{ orderData.date_order }}</div>
          <div><strong>Cliente:</strong> {{ decodeStr(orderData.name_client) }} {{ decodeStr(orderData.surname_client) }} ({{ orderData.dni_client }})</div>
          <div><strong>Vendedor:</strong> {{ decodeStr(orderData.name_admin) }}</div>
          <hr class="border-dashed border-black">
          
          <table class="w-full text-left">
            <thead>
              <tr>
                <th>Cant</th>
                <th>Descripción</th>
                <th class="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(prod, i) in productsData" :key="i">
                <td>{{ prod.qty_sale }}</td>
                <td>{{ decodeStr(prod.title_product) }} @ {{ formatCurrency((parseFloat(prod.subtotal_sale) / parseInt(prod.qty_sale)).toFixed(2)) }}</td>
                <td class="text-right">{{ formatCurrency(prod.subtotal_sale) }}</td>
              </tr>
            </tbody>
          </table>
          
          <hr class="border-dashed border-black">
          <div class="flex justify-between">
            <span>Subtotal:</span>
            <span>{{ formatCurrency(orderData.subtotal_order) }}</span>
          </div>
          <div v-if="parseFloat(orderData.discount_order) > 0" class="flex justify-between">
            <span>Descuento:</span>
            <span>{{ formatCurrency(orderData.discount_order) }}</span>
          </div>
          <div class="flex justify-between font-bold text-sm">
            <span>TOTAL:</span>
            <span>{{ formatCurrency(orderData.total_order) }}</span>
          </div>
          <div class="flex justify-between text-capitalize">
            <span>Pago:</span>
            <span class="capitalize">{{ orderData.method_order }}</span>
          </div>
          <hr class="border-dashed border-black">
          <div class="text-center font-bold">¡GRACIAS POR SU COMPRA!</div>
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
</template>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  .print-container, .print-container * {
    visibility: visible;
  }
  .print-container {
    position: fixed;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 0;
    background-color: white !important;
    color: black !important;
  }
  
  .print-hide {
    display: none !important;
  }
  
  .u-modal-overlay { display: none !important; }
}
</style>
