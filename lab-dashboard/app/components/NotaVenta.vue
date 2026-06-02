<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

const props = defineProps<{
  order: any
  items: any[]
  client: any
  subtotal: number
  discount: number
  expenses: number
  total: number
  method: string
  vendedor: string
}>()

const company = ref({
  name: 'JE INVENTARIO & VENTAS',
  address: 'La Paz, Bolivia',
  phone: '+591 2 XXXXXX',
  website: 'www.jeinventario.com'
})

function decode(s: string) { return s ? decodeURIComponent(String(s)).replace(/\+/g, ' ') : '' }
function fmt(v: number) { return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(v) }

const today = computed(() => new Date().toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' }))
const time = computed(() => new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }))

onMounted(() => {
  window.print()
})
</script>

<template>
  <div id="nota-venta-print" class="w-full max-w-4xl mx-auto bg-white p-0">
    <!-- Header profesional -->
    <div class="border-b-4 border-green-600 pb-6 mb-6">
      <div class="flex justify-between items-start gap-4">
        <!-- Logo / Branding -->
        <div>
          <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center text-white font-black text-xl mb-3">
            JE
          </div>
          <h1 class="text-2xl font-black text-slate-900">{{ company.name }}</h1>
          <p class="text-sm text-slate-600 mt-1">{{ company.address }}</p>
          <p class="text-xs text-slate-500">{{ company.phone }}</p>
        </div>

        <!-- Documento -->
        <div class="text-right">
          <p class="text-3xl font-black text-green-600">NOTA DE VENTA</p>
          <p class="text-sm text-slate-600 mt-2">#{{ order.transaction_order?.slice(-8) }}</p>
          <div class="mt-3 text-xs text-slate-700 space-y-1">
            <p><strong>{{ today }}</strong> - {{ time }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Cliente y vendedor -->
    <div class="grid grid-cols-2 gap-8 mb-8 pb-8 border-b border-slate-200">
      <div>
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Cliente</p>
        <p class="text-lg font-bold text-slate-900">{{ decode(client?.name_client || 'Cliente General') }} {{ decode(client?.surname_client || '') }}</p>
        <p class="text-sm text-slate-600 mt-1">DNI: {{ client?.dni_client || '—' }}</p>
        <p v-if="client?.nit_client" class="text-sm text-slate-600">NIT: {{ client.nit_client }}</p>
        <p v-if="client?.phone_client" class="text-sm text-slate-600">Teléfono: {{ client.phone_client }}</p>
      </div>
      <div class="text-right">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Vendedor</p>
        <p class="text-lg font-bold text-slate-900">{{ vendedor || 'Sistema' }}</p>
        <p class="text-sm text-slate-600 mt-1">Sucursal: Principal</p>
      </div>
    </div>

    <!-- Tabla de items -->
    <div class="mb-8">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-slate-100 border-t-2 border-b-2 border-slate-300">
            <th class="text-left px-4 py-3 font-bold text-slate-900">Descripción</th>
            <th class="text-center px-4 py-3 font-bold text-slate-900 w-24">Cantidad</th>
            <th class="text-right px-4 py-3 font-bold text-slate-900 w-28">P. Unitario</th>
            <th class="text-right px-4 py-3 font-bold text-slate-900 w-32">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id_sale" class="border-b border-slate-100 hover:bg-slate-50">
            <td class="px-4 py-3 text-slate-900 font-semibold">
              <p>{{ decode(item.title_product || 'Producto') }}</p>
              <p v-if="item.sku_product" class="text-xs text-slate-500">SKU: {{ item.sku_product }}</p>
            </td>
            <td class="text-center px-4 py-3 text-slate-700">{{ item.qty_sale }}</td>
            <td class="text-right px-4 py-3 text-slate-700 font-mono">{{ fmt(parseFloat(item.subtotal_sale) / parseInt(item.qty_sale)) }}</td>
            <td class="text-right px-4 py-3 text-slate-900 font-bold font-mono">{{ fmt(parseFloat(item.subtotal_sale)) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Totales -->
    <div class="flex justify-end mb-8">
      <div class="w-full max-w-md">
        <div class="space-y-2 text-sm border-t-2 border-b-2 border-slate-300 py-4">
          <div class="flex justify-between text-slate-700">
            <span>Subtotal:</span>
            <span class="font-mono font-semibold">{{ fmt(subtotal) }}</span>
          </div>
          <div v-if="discount > 0" class="flex justify-between text-slate-700">
            <span>Descuento:</span>
            <span class="font-mono font-semibold text-rose-600">-{{ fmt(discount) }}</span>
          </div>
          <div v-if="expenses > 0" class="flex justify-between text-slate-700">
            <span>Gastos de Despacho:</span>
            <span class="font-mono font-semibold text-blue-600">+{{ fmt(expenses) }}</span>
          </div>
        </div>
        <div class="flex justify-between items-center mt-4 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border-2 border-green-200">
          <span class="text-xl font-bold text-slate-900">TOTAL</span>
          <span class="text-3xl font-black text-green-600 font-mono">{{ fmt(total) }}</span>
        </div>
      </div>
    </div>

    <!-- Método de pago -->
    <div class="grid grid-cols-2 gap-8 mb-8 pb-8 border-b border-slate-200 text-sm">
      <div>
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Método de Pago</p>
        <p class="text-lg font-bold text-slate-900 capitalize">{{ method || '—' }}</p>
      </div>
      <div class="text-right">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Estado</p>
        <p class="text-lg font-bold text-emerald-600">VENTA CONFIRMADA</p>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-xs text-slate-600 space-y-2 pt-4">
      <p class="font-semibold">Gracias por su compra</p>
      <p>{{ company.website }}</p>
      <p class="text-slate-500 mt-4">Documento generado automáticamente por el sistema</p>
      <p class="text-slate-400">{{ new Date().toLocaleString('es-ES') }}</p>
    </div>

    <!-- Marca de agua -->
    <div class="fixed inset-0 pointer-events-none flex items-center justify-center opacity-5 z-0" style="font-size: 120px; font-weight: 900; color: #000;">
      COPIA
    </div>
  </div>
</template>

<style scoped>
@media print {
  ::-webkit-scrollbar {
    display: none;
  }
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  body {
    background: white;
  }
  #nota-venta-print {
    width: 21cm;
    height: auto;
    margin: 0;
    padding: 1.5cm;
    box-shadow: none;
    page-break-after: always;
  }
  @page {
    size: A4;
    margin: 0.5cm;
  }
}
</style>
