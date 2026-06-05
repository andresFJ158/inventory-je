<script setup lang="ts">
import { ref, computed } from 'vue'

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

watch(() => props.isOpen, (newVal) => {
  if (newVal && props.orderId) {
    fetchOrderDetails(props.orderId)
  }
})

function handlePrint() {
  window.print()
}

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
    <div class="print-container bg-white text-black p-8 relative">
      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-10 h-10 animate-spin text-green-600 mb-2" />
        <p class="text-gray-500">Cargando comprobante...</p>
      </div>

      <!-- Receipt Content -->
      <template v-else-if="orderData">
        
        <!-- Action Buttons (Hidden in print) -->
        <div class="print-hide absolute top-4 right-4 flex gap-2">
          <UButton color="primary" class="bg-blue-600" icon="i-lucide-printer" @click="handlePrint">Imprimir</UButton>
          <UButton color="neutral" variant="ghost" icon="i-lucide-x" @click="isOpenModel = false" />
        </div>

        <div class="text-center mb-6">
          <h1 class="text-3xl font-black text-blue-800 tracking-tight">Comprobante de Compra</h1>
          <p class="text-gray-500 text-lg">Transacción #{{ orderData.transaction_order }}</p>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-6 text-sm">
          <div>
            <h4 class="font-bold text-gray-700 uppercase border-b pb-1 mb-2">Datos de Sucursal</h4>
            <p><strong>Sucursal:</strong> {{ decodeStr(orderData.title_office) }}</p>
            <p><strong>Dirección:</strong> {{ decodeStr(orderData.address_office) }}</p>
            <p><strong>Teléfono:</strong> {{ orderData.phone_office }}</p>
            <p><strong>NIT:</strong> {{ orderData.dni_office }}</p>
          </div>
          <div>
            <h4 class="font-bold text-gray-700 uppercase border-b pb-1 mb-2">Datos del Cliente</h4>
            <p><strong>Nombre:</strong> {{ decodeStr(orderData.name_client) }} {{ decodeStr(orderData.surname_client) }}</p>
            <p><strong>Teléfono:</strong> {{ orderData.phone_client }}</p>
            <p><strong>Email:</strong> {{ orderData.email_client || 'No especificado' }}</p>
            <p><strong>Dirección:</strong> {{ decodeStr(orderData.address_client) }}</p>
          </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg mb-6 text-sm flex justify-between">
          <div><strong>Fecha:</strong> {{ orderData.date_order }}</div>
          <div><strong>Método de pago:</strong> {{ orderData.method_order }}</div>
          <div>
            <strong>Estado:</strong> 
            <span :class="orderData.status_order === 'Completada' ? 'text-green-600 font-bold' : 'text-amber-600 font-bold'">
              {{ orderData.status_order }}
            </span>
          </div>
        </div>

        <table class="w-full text-sm mb-6 border-collapse">
          <thead>
            <tr class="bg-gray-100 border-b-2 border-gray-300">
              <th class="py-2 px-3 text-left font-bold w-1/2">Producto</th>
              <th class="py-2 px-3 text-center font-bold">Cant.</th>
              <th class="py-2 px-3 text-right font-bold">Precio U.</th>
              <th class="py-2 px-3 text-center font-bold">Dscto / IVA</th>
              <th class="py-2 px-3 text-right font-bold">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(prod, i) in productsData" :key="i" class="border-b border-gray-200">
              <td class="py-3 px-3">{{ decodeStr(prod.title_product) }}</td>
              <td class="py-3 px-3 text-center">{{ prod.qty_sale }}</td>
              <td class="py-3 px-3 text-right">{{ formatCurrency(prod.price_sale) }}</td>
              <td class="py-3 px-3 text-center text-xs text-gray-500">
                <span v-if="parseFloat(prod.discount_sale) > 0">D: {{ prod.discount_sale }}%</span><br v-if="parseFloat(prod.discount_sale) > 0">
                <span v-if="parseFloat(prod.tax_sale) > 0">IVA: {{ prod.tax_sale }}%</span>
              </td>
              <td class="py-3 px-3 text-right font-semibold">{{ formatCurrency(prod.subtotal_sale) }}</td>
            </tr>
            <tr v-if="productsData.length === 0">
              <td colspan="5" class="py-4 text-center text-gray-500">No hay productos en esta orden.</td>
            </tr>
          </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-end mt-6">
          <div class="w-64 text-sm">
            <div class="flex justify-between py-1">
              <span class="text-gray-600">Subtotal:</span>
              <span class="font-medium">{{ formatCurrency(orderData.subtotal_order) }}</span>
            </div>
            <div class="flex justify-between py-1 text-red-600">
              <span>Descuento total (-):</span>
              <span class="font-medium">{{ formatCurrency(orderData.discount_order) }}</span>
            </div>
            <div class="flex justify-between py-1 text-gray-600 border-b border-gray-200 pb-2">
              <span>Impuestos (+):</span>
              <span class="font-medium">{{ formatCurrency(orderData.tax_order) }}</span>
            </div>
            <div class="flex justify-between py-2 mt-1 text-lg font-black text-gray-900 border-t-2 border-gray-900">
              <span>TOTAL A PAGAR:</span>
              <span>{{ formatCurrency(orderData.total_order) }}</span>
            </div>
          </div>
        </div>

        <div class="mt-12 text-center text-xs text-gray-400 border-t pt-4 print-footer">
          Gracias por su compra. Conserve este comprobante.
        </div>

      </template>

      <!-- Error State -->
      <div v-else class="py-12 text-center">
        <UIcon name="i-lucide-alert-triangle" class="w-12 h-12 text-red-400 mx-auto mb-3" />
        <p class="text-lg font-medium text-gray-700">Comprobante no encontrado</p>
        <UButton color="neutral" class="mt-4" @click="isOpenModel = false">Cerrar</UButton>
      </div>

    </div>
  </UModal>
</template>

<style>
@media print {
  /* Hide everything outside of print-container */
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
    padding: 20px;
    background-color: white !important;
    color: black !important;
  }
  
  /* Hide elements we don't want printed */
  .print-hide {
    display: none !important;
  }
  
  /* Reset some shadcn/nuxt ui modal styles for printing */
  .u-modal-overlay { display: none !important; }
}
</style>
