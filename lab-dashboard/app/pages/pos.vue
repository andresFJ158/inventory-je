<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

// State
const categories = ref<any[]>([])
const activeCategory = ref<string>('all')
const search = ref('')
const products = ref<any[]>([])
const inventory = ref<Record<string, number>>({})
const pricesMap = ref<Record<string, any>>({})
const clients = ref<any[]>([])
const selectedClient = ref<string>('')
const isWholesaleGlobal = ref(false)
const isCashRegisterOpen = ref(true)

// Active Order State
const orderId = ref<string | null>(null)
const transactionOrder = ref<string | null>(null)
const cartItems = ref<any[]>([])
const isPaying = ref(false)
const payMethod = ref<'efectivo' | 'transferencia' | 'tarjeta'>('efectivo')
const cashReceived = ref<number | null>(null)
const transferId = ref('')
const wantInvoice = ref(false)
const checkoutSuccess = ref(false)
const lastOrderReceipt = ref<any>(null)

// Client Modal
const isClientModalOpen = ref(false)
const newClient = ref({
  name: '',
  surname: '',
  dni: '',
  email: '',
  phone: '',
  address: ''
})

const apiHeaders = {
  Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
}

// Fetch categories
async function fetchCategories() {
  try {
    const data = await $fetch<any>('/api/categories?linkTo=status_category&equalTo=1', {
      headers: apiHeaders
    })
    if (data.status === 200) {
      categories.value = data.results || []
    }
  } catch (e) {
    console.error('Error fetching categories:', e)
  }
}

// Fetch products & inventory & prices
async function fetchCatalog() {
  try {
    // 1. Fetch products
    const prodData = await $fetch<any>('/api/products?linkTo=status_product&equalTo=1', {
      headers: apiHeaders
    })
    if (prodData.status === 200) {
      products.value = prodData.results || []
    }

    // 2. Fetch inventory for current office
    const officeId = auth.officeId || 3 // fallback to office 3
    const invData = await $fetch<any>(`/api/product_inventory?linkTo=id_office_inventory&equalTo=${officeId}`, {
      headers: apiHeaders
    })
    if (invData.status === 200 && invData.results) {
      const inv: Record<string, number> = {}
      invData.results.forEach((i: any) => {
        inv[i.id_product_inventory] = parseFloat(i.stock_inventory) || 0
      })
      inventory.value = inv
    }

    // 3. Fetch latest purchases to get prices
    const purchaseData = await $fetch<any>('/api/purchases?orderBy=date_created_purchase&orderMode=DESC', {
      headers: apiHeaders
    })
    if (purchaseData.status === 200 && purchaseData.results) {
      const prices: Record<string, any> = {}
      purchaseData.results.forEach((p: any) => {
        // Only set if not already set (since we sort by date DESC, the first one we see is the newest)
        if (!prices[p.id_product_purchase]) {
          prices[p.id_product_purchase] = {
            price: parseFloat(p.cost_purchase) || 0,
            wholesalePrice: parseFloat(p.may_product) || 0,
            wholesaleQty: parseInt(p.wholesale_quantity) || 0
          }
        }
      })
      pricesMap.value = prices
    }
  } catch (e) {
    console.error('Error fetching catalog data:', e)
  }
}

// Fetch clients
async function fetchClients() {
  try {
    const data = await $fetch<any>('/api/clients', {
      headers: apiHeaders
    })
    if (data.status === 200) {
      clients.value = data.results || []
    }
  } catch (e) {
    console.error('Error fetching clients:', e)
  }
}

// Load draft sales under this order
async function fetchCart() {
  if (!orderId.value) {
    cartItems.value = []
    return
  }
  try {
    const data = await $fetch<any>(`/api/sales?linkTo=id_order_sale&equalTo=${orderId.value}`, {
      headers: apiHeaders
    })
    if (data.status === 200 && data.results) {
      cartItems.value = data.results
    } else {
      cartItems.value = []
    }
  } catch (e) {
    console.error('Error fetching cart:', e)
    cartItems.value = []
  }
}

// Computed Catalog filtered
const filteredProducts = computed(() => {
  return products.value.filter(p => {
    const matchesCategory = activeCategory.value === 'all' || String(p.id_category_product) === String(activeCategory.value)
    const matchesSearch = !search.value ||
      p.title_product.toLowerCase().includes(search.value.toLowerCase()) ||
      p.sku_product.toLowerCase().includes(search.value.toLowerCase()) ||
      (p.code_product && p.code_product.toLowerCase().includes(search.value.toLowerCase()))
    return matchesCategory && matchesSearch
  })
})

// Initialize order on mount if there is a pending order for this user
async function checkActiveOrder() {
  try {
    const officeId = auth.officeId || 3
    const adminId = auth.user?.id_admin || 1
    const today = new Date().toISOString().split('T')[0]
    
    // Check if user has a pending draft order today
    const data = await $fetch<any>(`/api/orders?linkTo=id_admin_order,status_order,id_office_order,date_created_order&equalTo=${adminId},Pendiente Racing,${officeId},${today}`, {
      headers: apiHeaders
    })
    
    // Fallback: Check general pending draft orders for this user
    const dataGeneral = await $fetch<any>(`/api/orders?linkTo=id_admin_order,status_order&equalTo=${adminId},Pendiente`, {
      headers: apiHeaders
    })

    const orderData = (data.status === 200 && data.results?.[0]) || (dataGeneral.status === 200 && dataGeneral.results?.[0])

    if (orderData) {
      orderId.value = String(orderData.id_order)
      transactionOrder.value = orderData.transaction_order
      if (orderData.id_client_order > 0) {
        selectedClient.value = String(orderData.id_client_order)
      }
      await fetchCart()
    }
  } catch (e) {
    console.error('Error checking active order:', e)
  }
}

async function checkCashRegister() {
  if (auth.role === 'vendedor') {
    isCashRegisterOpen.value = true
    return
  }
  try {
    const officeId = auth.officeId || 3
    const today = new Date().toISOString().split('T')[0]
    
    const data = await $fetch<any>(`/api/cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=${today},1,${officeId}&select=status_cash`, {
      headers: apiHeaders
    })
    
    if (data.status === 200 && data.results && data.results.length > 0) {
      isCashRegisterOpen.value = true
    } else {
      isCashRegisterOpen.value = false
    }
  } catch (e: any) {
    isCashRegisterOpen.value = false
  }
}

// Cash Register Opening logic
const isOpeningCash = ref(false)
const openingCashAmount = ref(0)
const cashModalLoading = ref(false)

async function submitCashOpen() {
  if (openingCashAmount.value < 0) {
    alert('El monto inicial no puede ser negativo.')
    return
  }
  cashModalLoading.value = true
  try {
    const today = new Date().toISOString().split('T')[0]
    const officeId = auth.officeId || 3

    const payload = new URLSearchParams()
    payload.append('date_created_cash', today)
    payload.append('id_office_cash', String(officeId))
    payload.append('initial_cash', String(openingCashAmount.value))
    payload.append('status_cash', '1') // 1 means open
    payload.append('bills_cash', '0')
    payload.append('money_cash', '0')
    payload.append('diff_cash', '0')

    const res = await $fetch<any>(`/api/cashs?token=no&except=date_end_cash`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        ...apiHeaders
      },
      body: payload.toString()
    })

    if (res.status === 200) {
      isCashRegisterOpen.value = true
      isOpeningCash.value = false
      alert('Caja abierta exitosamente.')
      await checkActiveOrder()
    } else {
      alert(`Error al abrir caja: ${res.results || 'Intenta de nuevo'}`)
    }
  } catch (e) {
    console.error('Error opening cash:', e)
    alert('Error de conexión al abrir caja.')
  } finally {
    cashModalLoading.value = false
  }
}

onMounted(async () => {
  await checkCashRegister()
  await fetchCategories()
  await fetchCatalog()
  await fetchClients()
  if (isCashRegisterOpen.value) {
    await checkActiveOrder()
  }
})

// Generate new Order
async function handleNewOrder() {
  try {
    const officeId = auth.officeId || 3
    const adminId = auth.user?.id_admin || 1

    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        order: 'new',
        idOffice: String(officeId),
        seller: String(adminId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    let data;
    if (typeof response === 'string') {
      const trimmed = response.trim();
      if (trimmed === 'current cash error') {
        alert('Error: No se encuentra ninguna caja del día de hoy abierta para esta sucursal. Por favor, abre caja.');
        return;
      }
      if (trimmed === 'yesterday cash error') {
        alert('Error: Tienes cajas de días anteriores pendientes de cerrar. Por favor ciérralas primero.');
        return;
      }
      try {
        data = JSON.parse(trimmed);
      } catch (err) {
        alert('Error al iniciar la orden: ' + trimmed);
        return;
      }
    } else {
      data = response;
    }

    if (data && data.transaction_order) {
      orderId.value = String(data.id_order)
      transactionOrder.value = data.transaction_order
      selectedClient.value = ''
      cartItems.value = []
      checkoutSuccess.value = false
    } else {
      alert((data && data.message) || 'Error al iniciar la orden. Asegúrate de tener la caja abierta.');
    }
  } catch (e) {
    console.error('Error creating order:', e)
    alert('Error al conectar con el servidor para iniciar la orden.')
  }
}

// Add Product to Cart
async function addToCart(product: any) {
  if (!orderId.value) {
    alert('Por favor, genera una nueva orden antes de agregar productos.')
    return
  }
  if (!selectedClient.value) {
    alert('Por favor, selecciona un cliente para la orden.')
    return
  }

  const stock = inventory.value[product.id_product] || 0
  const inCart = cartItems.value.find(item => String(item.id_product_sale) === String(product.id_product))
  const currentQty = inCart ? parseInt(inCart.qty_sale) : 0

  if (currentQty + 1 > stock) {
    alert('La cantidad excede el stock disponible en la sucursal.')
    return
  }

  try {
    const officeId = auth.officeId || 3
    const adminId = auth.user?.id_admin || 1

    // Call POS ajax to create/add
    await $fetch('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        idProduct: String(product.id_product),
        idOrder: orderId.value,
        idClient: selectedClient.value,
        seller: String(adminId),
        idOffice: String(officeId),
        isWholesale: isWholesaleGlobal.value ? '1' : '0'
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    // Refresh cart
    await fetchCart()
  } catch (e) {
    console.error('Error adding product to cart:', e)
  }
}

// Update quantity of cart item
async function updateQty(item: any, change: number) {
  const newQty = parseInt(item.qty_sale) + change
  const stock = inventory.value[item.id_product_sale] || 0

  if (newQty < 1) return
  if (newQty > stock) {
    alert('La cantidad excede el stock disponible.')
    return
  }

  // Calculate pricing based on wholesale thresholds
  const priceMeta = pricesMap.value[item.id_product_sale] || { price: 0, wholesalePrice: 0, wholesaleQty: 0 }
  let unitPrice = priceMeta.price

  if (isWholesaleGlobal.value || (priceMeta.wholesaleQty > 0 && newQty >= priceMeta.wholesaleQty)) {
    if (priceMeta.wholesalePrice > 0) {
      unitPrice = priceMeta.wholesalePrice
    }
  }

  // Discount
  const discountVal = parseFloat(item.discount_sale) || 0
  if (discountVal > 0) {
    unitPrice = unitPrice - (unitPrice * (discountVal / 100))
  }

  const newSubtotal = unitPrice * newQty

  try {
    await $fetch('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        idSaleUpdate: String(item.id_sale),
        qtySale: String(newQty),
        subtotalSale: String(newSubtotal)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    // Refresh
    await fetchCart()
  } catch (e) {
    console.error('Error updating quantity:', e)
  }
}

// Delete cart item
async function deleteItem(item: any) {
  if (!confirm('¿Deseas remover este producto del carrito?')) return
  try {
    await $fetch('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        idSaleDelete: String(item.id_sale)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    await fetchCart()
  } catch (e) {
    console.error('Error deleting cart item:', e)
  }
}

// Clear all items in cart
async function clearCart() {
  if (!orderId.value) return
  if (!confirm('¿Deseas vaciar todos los productos de esta orden?')) return
  try {
    await $fetch('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        idOrderSale: orderId.value
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    await fetchCart()
  } catch (e) {
    console.error('Error clearing cart:', e)
  }
}

// Remove order completely
async function cancelOrder() {
  if (!orderId.value) return
  if (!confirm('¿Deseas cancelar y eliminar esta orden?')) return
  try {
    await $fetch('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        idOrderDelete: orderId.value
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    orderId.value = null
    transactionOrder.value = null
    selectedClient.value = ''
    cartItems.value = []
  } catch (e) {
    console.error('Error deleting order:', e)
  }
}

// Sync client changes to the order
watch(selectedClient, async (newVal) => {
  if (!orderId.value || !newVal) return
  try {
    await $fetch('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        idOrderUpdate: orderId.value,
        idClient: newVal,
        subtotalOrder: String(subtotal.value),
        discountOrder: String(totalDiscount.value),
        taxOrder: '0',
        totalOrder: String(total.value)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
  } catch (e) {
    console.error('Error updating order client:', e)
  }
})

// Trigger wholesale switch recalculation
watch(isWholesaleGlobal, async () => {
  if (!orderId.value) return
  // Force update each item in the cart to trigger price recalculation
  for (const item of cartItems.value) {
    await updateQty(item, 0)
  }
})

// Client registration
async function handleRegisterClient() {
  if (!newClient.value.name || !newClient.value.dni) return
  try {
    const officeId = auth.officeId || 3
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        name_client: newClient.value.name,
        surname_client: newClient.value.surname,
        dni_client: newClient.value.dni,
        email_client: newClient.value.email || '',
        phone_client: newClient.value.phone || '',
        address_client: newClient.value.address || '',
        idOffice: String(officeId)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    if (response && response !== 'logout' && response !== 'error') {
      await fetchClients()
      selectedClient.value = String(response) // backend returns client ID
      isClientModalOpen.value = false
      newClient.value = { name: '', surname: '', dni: '', email: '', phone: '', address: '' }
    } else {
      alert('Error al registrar cliente.')
    }
  } catch (e) {
    console.error('Error creating client:', e)
  }
}

// Subtotal & Totals calculations
const subtotal = computed(() => {
  return cartItems.value.reduce((acc, item) => acc + (parseFloat(item.subtotal_sale) || 0), 0)
})

const totalDiscount = computed(() => {
  return cartItems.value.reduce((acc, item) => {
    const price = parseFloat(item.subtotal_sale) || 0
    const disc = parseFloat(item.discount_sale) || 0
    return acc + (price * (disc / 100))
  }, 0)
})

const total = computed(() => {
  return subtotal.value - totalDiscount.value
})

// Payment Modal Open
function openPayment() {
  if (cartItems.value.length === 0) {
    alert('No hay productos en el carrito.')
    return
  }
  cashReceived.value = total.value
  transferId.value = ''
  payMethod.value = 'efectivo'
  wantInvoice.value = false
  isPaying.value = true
}

// Return cash change calculation
const cashChange = computed(() => {
  if (cashReceived.value === null) return 0
  return Math.max(0, cashReceived.value - total.value)
})

// Finalize Checkout
async function handleCheckout() {
  if (payMethod.value === 'transferencia' && !transferId.value) {
    alert('Ingresa el ID de la transferencia para confirmar.')
    return
  }
  if (payMethod.value === 'efectivo' && (cashReceived.value || 0) < total.value) {
    alert('El efectivo recibido es menor al total a pagar.')
    return
  }

  try {
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        payPosOrder: 'ok',
        idOrder: orderId.value!,
        method: payMethod.value,
        transfer: transferId.value,
        invoice: wantInvoice.value ? 'yes' : 'no',
        sellerId: String(auth.user?.id_admin || 1)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response

    if (data.status === 200) {
      // Save details for PDF receipt printing
      const clientObj = clients.value.find(c => String(c.id_client) === String(selectedClient.value))
      lastOrderReceipt.value = {
        transaction: transactionOrder.value,
        date: new Date().toLocaleString('es-ES'),
        client: clientObj ? `${clientObj.name_client} ${clientObj.surname_client || ''} (${clientObj.dni_client})` : 'Consumidor Final',
        items: cartItems.value.map(item => {
          const product = products.value.find(p => String(p.id_product) === String(item.id_product_sale))
          return {
            name: product ? product.title_product : 'Producto',
            qty: item.qty_sale,
            price: (parseFloat(item.subtotal_sale) / parseInt(item.qty_sale)).toFixed(2),
            subtotal: parseFloat(item.subtotal_sale).toFixed(2)
          }
        }),
        subtotal: subtotal.value.toFixed(2),
        discount: totalDiscount.value.toFixed(2),
        total: total.value.toFixed(2),
        payment: payMethod.value,
        vendedor: auth.user?.name_admin || 'Vendedor'
      }

      isPaying.value = false
      checkoutSuccess.value = true
      
      // Reset POS cart variables
      orderId.value = null
      transactionOrder.value = null
      selectedClient.value = ''
      cartItems.value = []
      
      // Update inventory stock catalog
      await fetchCatalog()
    } else {
      alert(data.message || 'Error al procesar el pago.')
    }
  } catch (e) {
    console.error('Checkout error:', e)
    alert('Error al conectar con la API de facturación y cobros.')
  }
}

// Print Receipt trigger (PDF)
function printReceipt() {
  window.print()
}
</script>

<template>
  <div class="h-full flex flex-col space-y-6 relative">
    <!-- Printable POS Receipt sheet (Hidden except during printing) -->
    <div id="print-area" class="hidden print:block bg-white text-black p-4 text-xs font-mono w-72 mx-auto">
      <div v-if="lastOrderReceipt" class="space-y-2">
        <div class="text-center font-bold text-sm">JE INVENTARIO & VENTAS</div>
        <div class="text-center">Sucursal: {{ auth.office?.title_office || 'General' }}</div>
        <div class="text-center">NIT: {{ auth.office?.dni_office || '0000000' }}</div>
        <hr class="border-dashed border-black">
        <div><strong>Orden:</strong> {{ lastOrderReceipt.transaction }}</div>
        <div><strong>Fecha:</strong> {{ lastOrderReceipt.date }}</div>
        <div><strong>Cliente:</strong> {{ lastOrderReceipt.client }}</div>
        <div><strong>Vendedor:</strong> {{ lastOrderReceipt.vendedor }}</div>
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
            <tr v-for="item in lastOrderReceipt.items" :key="item.name">
              <td>{{ item.qty }}</td>
              <td>{{ item.name }} @ Bs. {{ item.price }}</td>
              <td class="text-right">Bs. {{ item.subtotal }}</td>
            </tr>
          </tbody>
        </table>
        <hr class="border-dashed border-black">
        <div class="flex justify-between">
          <span>Subtotal:</span>
          <span>Bs. {{ lastOrderReceipt.subtotal }}</span>
        </div>
        <div v-if="parseFloat(lastOrderReceipt.discount) > 0" class="flex justify-between">
          <span>Descuento:</span>
          <span>Bs. {{ lastOrderReceipt.discount }}</span>
        </div>
        <div class="flex justify-between font-bold text-sm">
          <span>TOTAL:</span>
          <span>Bs. {{ lastOrderReceipt.total }}</span>
        </div>
        <div class="flex justify-between text-capitalize">
          <span>Pago:</span>
          <span>{{ lastOrderReceipt.payment }}</span>
        </div>
        <hr class="border-dashed border-black">
        <div class="text-center font-bold">¡GRACIAS POR SU COMPRA!</div>
      </div>
    </div>

    <!-- Main View Grid layout (Products left, cart right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1 overflow-hidden min-h-0 print:hidden">
      <!-- Left: Products Catalog & Filtering (8 cols) -->
      <div class="lg:col-span-8 flex flex-col space-y-4 min-h-0">
        <!-- Search and Category slider -->
        <div class="bg-slate-900/60 border border-slate-800 p-4 rounded-xl space-y-4">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-bold text-white">Catálogo de Productos</h2>
            <UInput
              v-model="search"
              icon="i-lucide-search"
              placeholder="Buscar producto por nombre o SKU..."
              class="w-64"
            />
          </div>

          <!-- Category Slider Pills -->
          <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
            <UButton
              :color="activeCategory === 'all' ? 'primary' : 'neutral'"
              variant="soft"
              size="xs"
              @click="activeCategory = 'all'"
            >
              Todos
            </UButton>
            <UButton
              v-for="cat in categories"
              :key="cat.id_category"
              :color="activeCategory === String(cat.id_category) ? 'primary' : 'neutral'"
              variant="soft"
              size="xs"
              @click="activeCategory = String(cat.id_category)"
            >
              {{ decodeURIComponent(cat.title_category).replace(/\+/g, ' ') }}
            </UButton>
          </div>
        </div>

        <!-- Product Cards Grid -->
        <div class="flex-1 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pb-6 scrollbar-thin">
          <div
            v-for="prod in filteredProducts"
            :key="prod.id_product"
            class="bg-slate-900/60 border border-slate-800/80 rounded-xl overflow-hidden shadow hover:border-slate-700/80 hover:shadow-lg transition duration-200 cursor-pointer flex flex-col justify-between"
            @click="addToCart(prod)"
          >
            <!-- Product Image -->
            <div class="h-40 bg-slate-950 flex items-center justify-center p-4 relative">
              <span class="absolute top-2 right-2 text-[10px] font-mono font-bold bg-slate-900 border border-slate-700 px-1.5 py-0.5 rounded text-slate-300">
                SKU: {{ prod.sku_product }}
              </span>
              <img
                :src="prod.img_product ? decodeURIComponent(prod.img_product).replace(/\+/g, ' ') : '/views/assets/img/multimedia.png'"
                class="max-h-full max-w-full object-contain"
              />
            </div>

            <!-- Product Details -->
            <div class="p-4 space-y-3 flex-1 flex flex-col justify-between">
              <div>
                <h3 class="font-bold text-white line-clamp-2">{{ decodeURIComponent(prod.title_product).replace(/\+/g, ' ') }}</h3>
                <span class="text-xs text-slate-500 block mt-1">U.M: {{ prod.unit_product }}</span>
              </div>

              <div class="flex justify-between items-center">
                <!-- Price info lookup -->
                <div>
                  <span class="text-xs text-slate-400 block">Precio:</span>
                  <span class="font-bold text-teal-400 font-mono">
                    Bs. {{ (pricesMap[prod.id_product]?.price || 0).toFixed(2) }}
                  </span>
                </div>

                <!-- Stock info lookup -->
                <div class="text-right">
                  <span class="text-[10px] text-slate-400 block">Stock:</span>
                  <UBadge
                    :color="(inventory[prod.id_product] || 0) > 0 ? 'emerald' : 'rose'"
                    variant="soft"
                    size="xs"
                  >
                    {{ inventory[prod.id_product] || 0 }}
                  </UBadge>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Cart & Order Panel (4 cols) -->
      <div class="lg:col-span-4 bg-slate-900/60 border border-slate-800 rounded-xl flex flex-col justify-between overflow-hidden shadow-2xl">
        <!-- Cart Header -->
        <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-slate-950/60">
          <div>
            <h2 class="font-extrabold text-white text-base">Carrito</h2>
            <span class="text-xs font-semibold font-mono text-slate-400">
              {{ transactionOrder ? `Orden #${transactionOrder}` : 'Sin Orden Activa' }}
            </span>
          </div>
          
          <UButton
            v-if="!orderId"
            color="primary"
            icon="i-lucide-plus-circle"
            size="xs"
            @click="handleNewOrder"
          >
            Nueva Orden
          </UButton>
          <div v-else class="flex gap-2">
            <UButton
              color="rose"
              variant="ghost"
              icon="i-lucide-trash-2"
              size="xs"
              @click="cancelOrder"
            />
            <UButton
              color="neutral"
              variant="ghost"
              icon="i-lucide-rotate-ccw"
              size="xs"
              @click="clearCart"
            />
          </div>
        </div>

        <!-- Cart Body (Client lookup, items list) -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 scrollbar-thin">
          <!-- Client select & add client -->
          <div v-if="orderId" class="flex gap-2 items-center">
            <USelect
              v-model="selectedClient"
              :options="clients.map(c => ({ value: String(c.id_client), label: `${c.name_client} ${c.surname_client || ''} (${c.dni_client})` }))"
              placeholder="Seleccionar cliente *"
              class="flex-1"
            />
            <UButton
              icon="i-lucide-user-plus"
              color="neutral"
              variant="soft"
              @click="isClientModalOpen = true"
            />
          </div>

          <!-- Wholesale global switch toggle -->
          <div v-if="orderId" class="flex items-center justify-between bg-slate-950/40 p-2 border border-slate-800 rounded-lg">
            <span class="text-xs text-slate-300 font-semibold">Habilitar precio mayorista</span>
            <USwitch v-model="isWholesaleGlobal" />
          </div>

          <!-- Items list in order -->
          <div v-if="cartItems.length > 0" class="space-y-3">
            <div
              v-for="item in cartItems"
              :key="item.id_sale"
              class="flex justify-between items-start gap-2 bg-slate-950/30 p-2.5 rounded-lg border border-slate-800/60"
            >
              <div class="flex-1 min-w-0">
                <span class="text-xs font-bold text-white block truncate">
                  {{ decodeURIComponent(products.find(p => String(p.id_product) === String(item.id_product_sale))?.title_product || 'Producto').replace(/\+/g, ' ') }}
                </span>
                <span class="text-[10px] text-teal-400 font-semibold font-mono block mt-0.5">
                  Bs. {{ (parseFloat(item.subtotal_sale) / parseInt(item.qty_sale)).toFixed(2) }} x {{ item.qty_sale }}
                </span>
              </div>

              <!-- Controls -->
              <div class="flex items-center gap-1.5 shrink-0">
                <UButton
                  icon="i-lucide-minus"
                  color="neutral"
                  variant="ghost"
                  size="xs"
                  class="p-1"
                  @click="updateQty(item, -1)"
                />
                <span class="text-xs font-mono font-bold text-white px-1.5 bg-slate-950 border border-slate-850 rounded">
                  {{ item.qty_sale }}
                </span>
                <UButton
                  icon="i-lucide-plus"
                  color="neutral"
                  variant="ghost"
                  size="xs"
                  class="p-1"
                  @click="updateQty(item, 1)"
                />
                <UButton
                  icon="i-lucide-trash"
                  color="rose"
                  variant="ghost"
                  size="xs"
                  class="p-1 ml-1"
                  @click="deleteItem(item)"
                />
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-12 text-slate-500 text-xs">
            <UIcon name="i-lucide-shopping-cart" class="w-8 h-8 mx-auto mb-2 text-slate-650" />
            El carrito está vacío. Agrega productos.
          </div>
        </div>

        <!-- Checkout success panel -->
        <div v-if="checkoutSuccess" class="m-4 p-3 bg-emerald-950/40 border border-emerald-800 rounded-xl text-center space-y-2">
          <UIcon name="i-lucide-check-circle" class="w-8 h-8 text-emerald-400 mx-auto" />
          <h3 class="text-xs font-bold text-white">Venta Procesada con Éxito</h3>
          <UButton
            color="emerald"
            size="xs"
            block
            icon="i-lucide-printer"
            @click="printReceipt"
          >
            Imprimir Recibo (PDF)
          </UButton>
        </div>

        <!-- Cart Footer Totals and Checkout -->
        <div class="p-4 border-t border-slate-800 bg-slate-950 space-y-3">
          <div class="space-y-1.5">
            <div class="flex justify-between text-xs text-slate-400">
              <span>Subtotal:</span>
              <span class="font-mono">Bs. {{ subtotal.toFixed(2) }}</span>
            </div>
            <div v-if="totalDiscount > 0" class="flex justify-between text-xs text-rose-400">
              <span>Descuento:</span>
              <span class="font-mono">-Bs. {{ totalDiscount.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between font-extrabold text-white text-sm pt-1 border-t border-slate-900">
              <span>Total:</span>
              <span class="font-mono text-teal-400 text-base">Bs. {{ total.toFixed(2) }}</span>
            </div>
          </div>

          <UButton
            v-if="orderId"
            color="primary"
            block
            icon="i-lucide-credit-card"
            @click="openPayment"
          >
            Cobrar Orden
          </UButton>
        </div>
      </div>
    </div>

    <!-- Client Modal Form -->
    <UModal v-model:open="isClientModalOpen" title="Registrar Nuevo Cliente">
      <template #body>
        <form class="space-y-4" @submit.prevent="handleRegisterClient">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-1">Nombre *</label>
              <UInput v-model="newClient.name" placeholder="Ej: Juan" required />
            </div>
            <div>
              <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-1">Apellido</label>
              <UInput v-model="newClient.surname" placeholder="Ej: Perez" />
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-1">DNI / Documento *</label>
            <UInput v-model="newClient.dni" placeholder="Ej: 1234567" required />
          </div>
          <div>
            <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-1">Correo Electrónico</label>
            <UInput v-model="newClient.email" type="email" placeholder="Ej: juan@perez.com" />
          </div>
          <div>
            <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-1">Teléfono</label>
            <UInput v-model="newClient.phone" placeholder="Ej: 79008000" />
          </div>
          <div>
            <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-1">Dirección</label>
            <UInput v-model="newClient.address" placeholder="Ej: Zona Central Calle Sucre #123" />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <UButton color="neutral" variant="ghost" @click="isClientModalOpen = false">Cancelar</UButton>
            <UButton color="primary" type="submit">Crear Cliente</UButton>
          </div>
        </form>
      </template>
    </UModal>

    <!-- Payment details Modal dialog -->
    <UModal v-model:open="isPaying" title="Finalizar Cobro de Orden">
      <template #body>
        <div class="space-y-4">
          <div class="bg-slate-950 p-4 border border-slate-850 rounded-xl text-center">
            <span class="text-xs text-slate-400 block mb-1">Monto a Cobrar</span>
            <span class="text-2xl font-extrabold text-teal-400 font-mono">Bs. {{ total.toFixed(2) }}</span>
          </div>

          <!-- Payment methods toggle -->
          <div>
            <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-2">Método de Pago</label>
            <div class="grid grid-cols-3 gap-2">
              <UButton
                :color="payMethod === 'efectivo' ? 'primary' : 'neutral'"
                variant="soft"
                icon="i-lucide-banknote"
                size="sm"
                class="flex-col py-3.5"
                @click="payMethod = 'efectivo'"
              >
                Efectivo
              </UButton>
              <UButton
                :color="payMethod === 'transferencia' ? 'primary' : 'neutral'"
                variant="soft"
                icon="i-lucide-arrow-right-left"
                size="sm"
                class="flex-col py-3.5"
                @click="payMethod = 'transferencia'"
              >
                Transfer
              </UButton>
              <UButton
                :color="payMethod === 'tarjeta' ? 'primary' : 'neutral'"
                variant="soft"
                icon="i-lucide-credit-card"
                size="sm"
                class="flex-col py-3.5"
                @click="payMethod = 'tarjeta'"
              >
                Tarjeta
              </UButton>
            </div>
          </div>

          <!-- Cash details form -->
          <div v-if="payMethod === 'efectivo'" class="space-y-3">
            <div>
              <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-1">Efectivo Recibido</label>
              <UInput
                v-model.number="cashReceived"
                type="number"
                step="any"
                placeholder="Bs. 0.00"
              />
            </div>
            <div class="flex justify-between items-center text-xs text-slate-400 pt-1 font-mono">
              <span>Cambio a devolver:</span>
              <span class="text-sm font-bold text-white">Bs. {{ cashChange.toFixed(2) }}</span>
            </div>
          </div>

          <!-- Transfer details form -->
          <div v-if="payMethod === 'transferencia'">
            <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-1">ID / Código de Transferencia *</label>
            <UInput
              v-model="transferId"
              placeholder="Ingresa el número de referencia"
            />
          </div>

          <!-- Card message hint -->
          <div v-if="payMethod === 'tarjeta'" class="text-center py-2 text-xs text-slate-400">
            Asegúrate de pasar la tarjeta por el POS físico antes de completar.
          </div>

          <!-- Invoice switch checkbox -->
          <div class="flex items-center justify-between border-t border-slate-800 pt-3">
            <span class="text-xs text-slate-300 font-semibold">¿Emitir Factura Electrónica?</span>
            <USwitch v-model="wantInvoice" />
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <UButton color="neutral" variant="ghost" @click="isPaying = false">Cancelar</UButton>
            <UButton color="primary" @click="handleCheckout">Confirmar Cobro</UButton>
          </div>
        </div>
      </template>
    </UModal>

    <!-- Overlay Caja Cerrada (Glassmorphism + Blur) -->
    <div v-if="!isCashRegisterOpen" class="absolute inset-0 z-40 backdrop-blur-md bg-slate-950/60 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 rounded-2xl max-w-sm w-full shadow-2xl text-center space-y-6 transform scale-100 transition-all duration-300">
        <!-- Circular Wallet Icon in Red -->
        <div class="mx-auto w-16 h-16 bg-rose-50 dark:bg-rose-950/30 rounded-full flex items-center justify-center text-rose-500 border border-rose-100 dark:border-rose-900/50">
          <UIcon name="i-lucide-wallet" class="w-8 h-8" />
        </div>
        
        <div class="space-y-2">
          <h2 class="text-xl font-extrabold text-slate-800 dark:text-white tracking-tight">
            Caja Cerrada
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed px-2">
            Para poder realizar ventas y utilizar el módulo POS, primero debe abrir la caja del día.
          </p>
        </div>

        <UButton
          size="lg"
          color="emerald"
          icon="i-lucide-lock-keyhole-open"
          block
          class="font-bold py-3 text-base shadow-lg shadow-emerald-500/20"
          @click="isOpeningCash = true"
        >
          Abrir Caja
        </UButton>
      </div>
    </div>

    <!-- Modal to open cash -->
    <UModal v-model="isOpeningCash">
      <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-100 dark:divide-slate-800' }">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold">Apertura de Caja</h3>
            <UButton color="gray" variant="ghost" icon="i-lucide-x" class="-my-1" @click="isOpeningCash = false" />
          </div>
        </template>

        <div class="space-y-4">
          <p class="text-sm text-gray-500">Ingrese el monto inicial con el que comienza el día.</p>
          <UFormGroup label="Dinero Inicial (Bs.)" :error="openingCashAmount < 0 ? 'Monto no puede ser negativo' : ''">
            <UInput v-model.number="openingCashAmount" type="number" step="0.10" icon="i-lucide-coins" size="lg" />
          </UFormGroup>
        </div>

        <template #footer>
          <div class="flex justify-end gap-3">
            <UButton color="gray" variant="soft" @click="isOpeningCash = false">Cancelar</UButton>
            <UButton color="emerald" :loading="cashModalLoading" @click="submitCashOpen">Confirmar y Abrir</UButton>
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>

<style>
/* CSS Page layout hiding everything except print-area during printing */
@media print {
  body * {
    visibility: hidden;
  }
  #print-area, #print-area * {
    visibility: visible;
  }
  #print-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>
