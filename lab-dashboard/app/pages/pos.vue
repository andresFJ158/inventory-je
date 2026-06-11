<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()

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
const activeCashId = ref<string | null>(null)
const imageErrors = ref<Record<string, boolean>>({})

// Active Order State
const orderId = ref<string | null>(null)
const transactionOrder = ref<string | null>(null)
const cartItems = ref<any[]>([])
const isPaying = ref(false)
const payMethod = ref<'efectivo' | 'transferencia' | 'credito' | 'tarjeta'>('efectivo')
const cashReceived = ref<number | null>(null)
const transferId = ref('')
const qrRefOrder = ref('')
const wantInvoice = ref(false)
const checkoutSuccess = ref(false)
const lastOrderReceipt = ref<any>(null)

// Expense Modal
const isExpenseModalOpen = ref(false)
const expenseModel = ref({ description: '', amount: 0 })
const savingExpense = ref(false)

async function handleSaveExpense() {
  if (!activeCashId.value) {
    toast.add({ title: 'Debes tener una caja abierta para poder registrar gastos.', color: 'error' })
    return
  }
  if (!expenseModel.value.description || expenseModel.value.amount <= 0) {
    toast.add({ title: 'Ingresa una descripción válida y monto mayor a 0.', color: 'error' })
    return
  }
  savingExpense.value = true
  try {
    const body = new URLSearchParams({
      concept_bill: 'Gasto POS: ' + expenseModel.value.description,
      cost_bill: String(expenseModel.value.amount),
      id_office_bill: String(auth.officeId ?? 3),
      id_admin_bill: String(auth.user?.id_admin || 1),
      id_cash_bill: String(activeCashId.value)
    })
    const res = await $fetch<any>(`/api/bills?token=${auth.token}&table=admins&suffix=admin&except=id_bill`, {
      method: 'POST',
      body: body.toString(),
      headers: { 
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
      }
    })
    if (res.status === 200) {
      toast.add({ title: 'Gasto registrado con éxito.', color: 'success' })
      isExpenseModalOpen.value = false
      expenseModel.value = { description: '', amount: 0 }
    } else {
      toast.add({ title: 'Error al registrar gasto.', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de conexión.', color: 'error' })
  }
  savingExpense.value = false
}

// Income Modal
const isIncomeModalOpen = ref(false)
const incomeModel = ref({ description: '', amount: 0 })
const savingIncome = ref(false)

async function handleSaveIncome() {
  if (!activeCashId.value) {
    toast.add({ title: 'Debes tener una caja abierta para poder registrar ingresos.', color: 'error' })
    return
  }
  if (!incomeModel.value.description || incomeModel.value.amount <= 0) {
    toast.add({ title: 'Ingresa una descripción válida y monto mayor a 0.', color: 'error' })
    return
  }
  savingIncome.value = true
  try {
    const body = new URLSearchParams({
      concept_income: 'Ingreso Extra POS: ' + incomeModel.value.description,
      amount_income: String(incomeModel.value.amount),
      id_office_income: String(auth.officeId ?? 3),
      id_admin_income: String(auth.user?.id_admin || 1),
      id_cash_income: String(activeCashId.value)
    })
    const res = await $fetch<any>(`/api/incomes?token=${auth.token}&table=admins&suffix=admin&except=id_income`, {
      method: 'POST',
      body: body.toString(),
      headers: { 
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
      }
    })
    if (res.status === 200) {
      toast.add({ title: 'Ingreso registrado con éxito.', color: 'success' })
      isIncomeModalOpen.value = false
      incomeModel.value = { description: '', amount: 0 }
    } else {
      toast.add({ title: 'Error al registrar ingreso.', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de conexión.', color: 'error' })
  }
  savingIncome.value = false
}

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
    const officeId = auth.officeId ?? 3 // fallback to office 3
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

// --- Price helpers (Fallback por SKU) ---
function getProductPriceMeta(productId: string | number) {
  const idStr = String(productId)
  if (pricesMap.value[idStr]) {
    return pricesMap.value[idStr]
  }
  // Fallback
  const prod = products.value.find(p => String(p.id_product) === idStr)
  if (prod && prod.sku_product) {
    const fallbackProd = products.value.find(p => p.sku_product === prod.sku_product && pricesMap.value[p.id_product])
    if (fallbackProd) {
      return pricesMap.value[fallbackProd.id_product]
    }
  }
  return { price: 0, wholesalePrice: 0, wholesaleQty: 0 }
}

function getProductPrice(prod: any) {
  const meta = getProductPriceMeta(prod.id_product)
  return meta.price || 0
}

function getProductDiscountedPrice(prod: any) {
  const base = getProductPrice(prod)
  const discount = parseFloat(prod.discount_product) || 0
  if (discount > 0) {
    return base - (base * (discount / 100))
  }
  return base
}

function getImageUrl(imgStr: string) {
  if (!imgStr) return ''
  const decoded = decodeURIComponent(imgStr).replace(/\+/g, ' ')
  
  // Si la ruta absoluta incluye nuestro propio directorio de views, la forzamos a ser relativa
  // Esto arregla el problema de dominios inactivos guardados en BD (ej. pos.desarrolloweb24siete.com)
  const viewsIndex = decoded.indexOf('views/')
  if (viewsIndex !== -1) {
    return '/' + decoded.substring(viewsIndex)
  }

  if (decoded.startsWith('http') || decoded.startsWith('/')) {
    return decoded
  }
  return '/' + decoded
}

// Fetch clients
async function fetchClients() {
  try {
    const data = await $fetch<any>('/api/clients', {
      headers: apiHeaders
    })
    if (data.status === 200 && data.results) {
      clients.value = data.results.sort((a: any, b: any) => {
        const nameA = (a.name_client || '').toLowerCase()
        const nameB = (b.name_client || '').toLowerCase()
        return nameA.localeCompare(nameB)
      })
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

// Initialize order on mount
async function checkActiveOrder() {
  try {
    await handleNewOrder()
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
    const officeId = auth.officeId ?? 3
    
    const data = await $fetch<any>(`/api/cashs?linkTo=id_office_cash,status_cash&equalTo=${officeId},1&select=id_cash,status_cash`, {
      headers: apiHeaders
    })
    
    if (data.status === 200 && data.results && data.results.length > 0) {
      isCashRegisterOpen.value = true
      activeCashId.value = String(data.results[0].id_cash)
    } else {
      isCashRegisterOpen.value = false
      activeCashId.value = null
    }
  } catch (e: any) {
    isCashRegisterOpen.value = false
    activeCashId.value = null
  }
}

// Cash Register Opening logic
const isOpeningCash = ref(false)
const openingCashAmount = ref(0)
const cashModalLoading = ref(false)

async function submitCashOpen() {
  if (openingCashAmount.value < 0) {
    toast.add({ title: 'El monto inicial no puede ser negativo.', color: 'error' })
    return
  }
  cashModalLoading.value = true
  try {
    const today = new Date().toISOString().split('T')[0]
    const officeId = auth.officeId ?? 3

    const localDateTime = new Date().toLocaleString('sv-SE').replace('T', ' ')

    const payload = new URLSearchParams()
    payload.append('date_created_cash', today || '')
    payload.append('id_office_cash', String(officeId))
    payload.append('id_admin_cash', String(auth.user?.id_admin || ''))
    payload.append('date_start_cash', localDateTime)
    payload.append('start_cash', String(openingCashAmount.value))
    payload.append('status_cash', '1') // 1 means open
    payload.append('bills_cash', '0')
    payload.append('money_cash', '0')
    payload.append('diff_cash', '0')

    const res = await ($fetch as any)(`/api/cashs?token=${auth.token}&table=admins&suffix=admin&except=date_end_cash,end_cash,gap_cash`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        ...apiHeaders
      },
      body: payload.toString()
    })

    if (res.status === 200) {
      await checkCashRegister()
      isOpeningCash.value = false
      toast.add({ title: 'Caja abierta exitosamente.', color: 'success' })
      await checkActiveOrder()
    } else {
      toast.add({ title: `Error al abrir caja: ${res.results || 'Intenta de nuevo'}`, color: 'error' })
    }
  } catch (e) {
    console.error('Error opening cash:', e)
    toast.add({ title: 'Error de conexión al abrir caja.', color: 'error' })
  } finally {
    cashModalLoading.value = false
  }
}

onMounted(async () => {
  await checkCashRegister()
  await fetchCategories()
  await fetchCatalog()
  await fetchClients()
  // Ya no creamos órdenes vacías automáticamente al entrar
  // if (isCashRegisterOpen.value) {
  //   await checkActiveOrder()
  // }
})

// Generate new Order
async function handleNewOrder() {
  try {
    const officeId = auth.officeId ?? 3
    const adminId = auth.user?.id_admin || 1

    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        order: 'new',
        idOffice: String(officeId),
        seller: String(adminId),
        token: auth.token || ''
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    let data;
    if (typeof response === 'string') {
      const trimmed = response.trim();
      if (trimmed === 'current cash error') {
        toast.add({ title: 'Error: No se encuentra ninguna caja del día de hoy abierta para esta sucursal. Por favor, abre caja.', color: 'error' });
        return;
      }
      if (trimmed === 'yesterday cash error') {
        toast.add({ title: 'Error: Tienes cajas de días anteriores pendientes de cerrar. Por favor ciérralas primero.', color: 'error' });
        return;
      }
      try {
        data = JSON.parse(trimmed);
      } catch (err) {
        toast.add({ title: 'Error al iniciar la orden: ' + trimmed, color: 'error' });
        return;
      }
    } else {
      data = response;
    }

    if (data && data.transaction_order) {
      orderId.value = String(data.id_order)
      transactionOrder.value = data.transaction_order
      // No reseteamos el cliente porque puede que ya haya seleccionado uno antes de la auto-creación
      if (!selectedClient.value) {
         selectedClient.value = ''
      }
      cartItems.value = []
      checkoutSuccess.value = false
    } else {
      toast.add({ title: (data && data.message) || 'Error al iniciar la orden. Asegúrate de tener la caja abierta.', color: 'error' });
    }
  } catch (e) {
    console.error('Error creating order:', e)
    toast.add({ title: 'Error al conectar con el servidor para iniciar la orden.', color: 'error' })
  }
}

// Add Product to Cart
async function addToCart(product: any) {
  if (!selectedClient.value) {
    toast.add({ title: 'Por favor, selecciona un cliente para la orden.', color: 'error' })
    return
  }

  if (!orderId.value) {
    await handleNewOrder()
    // Si aún después de intentar crearla, no hay orderId, no continuamos
    if (!orderId.value) return
  }

  const stock = inventory.value[product.id_product] || 0
  const inCart = cartItems.value.find(item => String(item.id_product_sale) === String(product.id_product))
  const currentQty = inCart ? parseInt(inCart.qty_sale) : 0

  if (currentQty + 1 > stock) {
    toast.add({ title: 'La cantidad excede el stock disponible en la sucursal.', color: 'error' })
    return
  }

  if (inCart) {
    await updateQty(inCart, 1)
    return
  }

  try {
    const officeId = auth.officeId ?? 3
    const adminId = auth.user?.id_admin || 1

    // Call POS ajax to create/add
    const ajaxRes = await $fetch('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        idProduct: String(product.id_product),
        idOrder: orderId.value,
        idClient: selectedClient.value,
        seller: String(adminId),
        idOffice: String(officeId),
        isWholesale: isWholesaleGlobal.value ? '1' : '0',
        token: auth.token || ''
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    if (typeof ajaxRes === 'string' && ajaxRes.trim() !== '') {
      if (ajaxRes.trim() === 'error stock') {
        toast.add({ title: 'No hay stock disponible.', color: 'error' })
        return
      } else if (ajaxRes.trim() === 'logout') {
        toast.add({ title: 'Sesión expirada o error de autorización.', color: 'error' })
        auth.logout()
        useRouter().push('/login')
        return
      } else if (ajaxRes.trim() !== 'product exist') {
        if (!ajaxRes.trim().includes('<tr')) { // If it's not returning HTML
           toast.add({ title: 'Error del servidor: ' + ajaxRes.trim(), color: 'error' })
           return
        }
      }
    }

    // Refresh cart
    await fetchCart()
  } catch (e: any) {
    console.error('Error adding product to cart:', e)
    toast.add({ title: 'Error al agregar producto: ' + (e.message || 'Desconocido'), color: 'error' })
  }
}

// Update quantity of cart item
async function updateQty(item: any, change: number) {
  const newQty = parseInt(item.qty_sale) + change
  const stock = inventory.value[item.id_product_sale] || 0

  if (newQty < 1) return
  if (newQty > stock) {
    toast.add({ title: 'La cantidad excede el stock disponible.', color: 'error' })
    return
  }

  // Calculate pricing based on wholesale thresholds
  const priceMeta = getProductPriceMeta(item.id_product_sale)
  let unitPrice = priceMeta.price

  if (isWholesaleGlobal.value || newQty >= 12 || (priceMeta.wholesaleQty > 0 && newQty >= priceMeta.wholesaleQty)) {
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
        subtotalSale: String(newSubtotal),
        token: auth.token || ''
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
        idSaleDelete: String(item.id_sale),
        token: auth.token || ''
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
        idOrderSale: orderId.value,
        token: auth.token || ''
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
        idOrderDelete: orderId.value,
        token: auth.token || ''
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
    const officeId = auth.officeId ?? 3
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        name_client: newClient.value.name,
        surname_client: newClient.value.surname,
        dni_client: newClient.value.dni,
        email_client: newClient.value.email || '',
        phone_client: newClient.value.phone || '',
        address_client: newClient.value.address || '',
        idOffice: String(officeId),
        token: auth.token || ''
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    if (response && response !== 'logout' && response !== 'error') {
      await fetchClients()
      selectedClient.value = String(response) // backend returns client ID
      isClientModalOpen.value = false
      newClient.value = { name: '', surname: '', dni: '', email: '', phone: '', address: '' }
    } else {
      toast.add({ title: 'Error al registrar cliente.', color: 'error' })
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

// Sync order totals to the backend when cart or client changes
watch([selectedClient, subtotal, totalDiscount, total], async ([newClientVal, newSub, newDisc, newTot]) => {
  if (!orderId.value || !newClientVal) return
  try {
    await $fetch('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        idOrderUpdate: orderId.value,
        idClient: String(newClientVal),
        subtotalOrder: String(newSub),
        discountOrder: String(newDisc),
        taxOrder: '0',
        totalOrder: String(newTot),
        token: auth.token || ''
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
  } catch (e) {
    console.error('Error updating order:', e)
  }
})

const qrImage = ref('')

async function fetchQRImage() {
  qrImage.value = ''
  try {
    const officeId = auth.officeId ?? 3
    const data = await $fetch<any>(`/api/qrs?linkTo=id_office_qr&equalTo=${officeId}`, {
      headers: apiHeaders
    })
    if (data && data.status === 200 && data.results && data.results.length > 0) {
      qrImage.value = data.results[0].image_qr || ''
    }
  } catch (e) {
    console.error('Error fetching QR image:', e)
  }
}

// Payment Modal Open
function openPayment() {
  if (cartItems.value.length === 0) {
    toast.add({ title: 'No hay productos en el carrito.', color: 'error' })
    return
  }
  cashReceived.value = total.value
  transferId.value = ''
  payMethod.value = 'efectivo'
  wantInvoice.value = false
  isPaying.value = true
  fetchQRImage()
}

// Return cash change calculation
const cashChange = computed(() => {
  if (cashReceived.value === null) return 0
  return Math.max(0, cashReceived.value - total.value)
})

// Finalize Checkout
async function handleCheckout() {
  if (payMethod.value === 'QR' && !transferId.value) {
    toast.add({ title: 'Ingresa el ID o código de referencia del pago QR para confirmar.', color: 'error' })
    return
  }
  if (payMethod.value === 'efectivo' && (cashReceived.value || 0) < total.value) {
    toast.add({ title: 'El efectivo recibido es menor al total a pagar.', color: 'error' })
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
        qrRefOrder: qrRefOrder.value,
        invoice: wantInvoice.value ? 'yes' : 'no',
        sellerId: String(auth.user?.id_admin || 1),
        subtotal: String(subtotal.value.toFixed(2)),
        discount: String(totalDiscount.value.toFixed(2)),
        tax: String(0),
        total: String(total.value.toFixed(2))
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
      toast.add({ title: data.message || 'Error al procesar el pago.', color: 'error' })
    }
  } catch (e) {
    console.error('Checkout error:', e)
    toast.add({ title: 'Error al conectar con la API de facturación y cobros.', color: 'error' })
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
            class="bg-slate-900/60 border border-slate-800/80 rounded-xl overflow-hidden shadow hover:border-slate-700/80 hover:shadow-lg transition duration-200 cursor-pointer flex flex-col justify-between h-[300px]"
            @click="addToCart(prod)"
          >
            <!-- Product Image -->
            <div class="h-36 bg-slate-950 flex items-center justify-center p-3 relative overflow-hidden shrink-0">
              <span class="absolute top-2 right-2 text-[10px] font-mono font-bold bg-slate-900 border border-slate-700 px-1.5 py-0.5 rounded text-slate-300 z-10">
                SKU: {{ prod.sku_product }}
              </span>
              <img
                v-if="prod.img_product && !imageErrors[prod.id_product]"
                :src="getImageUrl(prod.img_product)"
                @error="imageErrors[prod.id_product] = true"
                class="h-full w-full object-contain"
              />
              <UIcon
                v-else
                name="i-lucide-image"
                class="w-12 h-12 text-slate-600"
              />
            </div>

            <!-- Product Details -->
            <div class="p-3 flex-1 flex flex-col justify-between min-h-0">
              <div>
                <h3 class="font-bold text-white text-xs line-clamp-2 leading-snug">{{ decodeURIComponent(prod.title_product).replace(/\+/g, ' ') }}</h3>
                <span class="text-[10px] text-slate-500 block mt-0.5">U.M: {{ prod.unit_product }}</span>
              </div>

              <div class="flex justify-between items-center pt-2">
                <!-- Price info lookup -->
                <div>
                  <span class="text-[10px] text-slate-400 block">Precio:</span>
                  <div v-if="Number(prod.discount_product || 0) > 0">
                    <span class="text-[9px] text-red-400 line-through">Bs. {{ Number(getProductPrice(prod)).toFixed(2) }}</span>
                    <span class="font-bold text-teal-400 font-mono text-xs ml-1 font-bold">Bs. {{ Number(getProductDiscountedPrice(prod)).toFixed(2) }}</span>
                  </div>
                  <span v-else class="font-bold text-teal-400 font-mono text-xs font-bold">
                    Bs. {{ Number(getProductPrice(prod)).toFixed(2) }}
                  </span>
                </div>

                <!-- Stock info lookup -->
                <div class="text-right">
                  <span class="text-[9px] text-slate-400 block">Stock:</span>
                  <UBadge
                    :color="(inventory[prod.id_product] || 0) > 0 ? 'success' : 'error'"
                    variant="soft"
                    size="xs"
                    class="text-[9px] font-bold"
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
              color="success"
              variant="ghost"
              icon="i-lucide-arrow-up-circle"
              size="xs"
              title="Añadir Ingreso Extra"
              @click="isIncomeModalOpen = true"
            />
            <UButton
              color="warning"
              variant="ghost"
              icon="i-lucide-receipt"
              size="xs"
              title="Añadir Gasto"
              @click="isExpenseModalOpen = true"
            />
            <UButton
              color="error"
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
            <USelectMenu
              v-model="selectedClient"
              :items="clients.map(c => ({ value: String(c.id_client), label: `${c.name_client} ${c.surname_client || ''} (${c.dni_client})` }))"
              value-key="value"
              label-key="label"
              placeholder="Seleccionar cliente *"
              class="flex-1"
              searchable
              searchable-placeholder="Buscar cliente..."
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
                  color="error"
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
            v-if="auth.role === 'vendedor' || auth.user?.type_seller"
            color="success"
            size="xs"
            block
            icon="i-lucide-file-text"
            @click="printReceipt"
          >
            Imprimir Comprobante (PDF)
          </UButton>
          <UButton
            v-if="auth.role === 'cajero' || auth.role === 'admin' || auth.role === 'superadmin'"
            color="info"
            size="xs"
            block
            icon="i-lucide-printer"
            @click="printReceipt"
          >
            Imprimir Ticket (Caja)
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
                :color="payMethod === 'QR' ? 'primary' : 'neutral'"
                variant="soft"
                icon="i-lucide-qr-code"
                size="sm"
                class="flex-col py-3.5"
                @click="payMethod = 'QR'"
              >
                QR
              </UButton>
              <UButton
                v-if="auth.role === 'vendedor' || auth.user?.type_seller === 'vendedor' || auth.user?.type_seller === 'calle' || auth.user?.type_seller === 'tienda'"
                :color="payMethod === 'credito' ? 'primary' : 'neutral'"
                variant="soft"
                icon="i-lucide-credit-card"
                size="sm"
                class="flex-col py-3.5"
                @click="payMethod = 'credito'"
              >
                Crédito
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

          <!-- QR details form -->
          <div v-if="payMethod === 'QR'">
            
            <div v-if="qrImage" class="flex justify-center my-6">
              <img :src="getImageUrl(qrImage)" alt="Código QR" class="max-w-[350px] w-full border-4 border-white rounded-xl shadow-xl" />
            </div>
            <div v-else class="text-center text-xs text-slate-400 my-4 italic">
              No hay código QR configurado para esta sucursal.
            </div>

            <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-1">ID / Código de Referencia *</label>
            <UInput
              v-model="transferId"
              placeholder="Ingresa el número de referencia del pago"
            />
          </div>
          
          <div v-if="payMethod === 'QR' && (auth.role === 'vendedor' || auth.user?.type_seller)">
            <label class="block text-[10px] font-semibold text-slate-300 uppercase mb-1">Link Imagen / QR de Confirmación</label>
            <UInput
              v-model="qrRefOrder"
              placeholder="Ej: https://... / Comprobante"
            />
          </div>

          <!-- Credit message hint -->
          <div v-if="payMethod === 'credito'" class="text-center py-2 text-xs text-slate-400">
            Esta orden se registrará como crédito para el vendedor.
          </div>

          <!-- Invoice switch checkbox -->
          <div class="flex items-center justify-between border-t border-slate-800 pt-3">
            <span class="text-xs text-slate-300 font-semibold">¿Emitir Factura Electrónica?</span>
            <USwitch v-model="wantInvoice" />
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <UButton color="neutral" variant="ghost" @click="isPaying = false">Cancelar</UButton>
            <UButton color="primary" @click="handleCheckout">{{ wantInvoice ? 'Cobrar CON Factura' : 'Cobrar SIN Factura' }}</UButton>
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
          color="success"
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
    <UModal v-model:open="isOpeningCash" title="Apertura de Caja">
      <template #body>
        <div class="space-y-4 p-1">
          <p class="text-sm text-gray-500">Ingrese el monto inicial con el que comienza el día.</p>
          <UFormField label="Dinero Inicial (Bs.)">
            <UInput v-model.number="openingCashAmount" type="number" step="0.10" icon="i-lucide-coins" size="lg" />
          </UFormField>
        </div>
      </template>

      <template #footer>
        <div class="flex justify-end gap-3 w-full">
          <UButton color="neutral" variant="soft" @click="isOpeningCash = false">Cancelar</UButton>
          <UButton color="success" :loading="cashModalLoading" @click="submitCashOpen">Confirmar y Abrir</UButton>
        </div>
      </template>
    </UModal>

    <!-- Modal Añadir Gasto POS -->
    <UModal v-model:open="isExpenseModalOpen" title="Registrar Gasto Rápido">
      <template #body>
        <div class="space-y-4 p-1">
          <UFormField label="Descripción del Gasto *">
            <UInput v-model="expenseModel.description" placeholder="Ej: Pasajes, almuerzo, etc." />
          </UFormField>
          <UFormField label="Monto (Bs.) *">
            <UInput v-model.number="expenseModel.amount" type="number" step="0.10" />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3 w-full">
          <UButton color="neutral" variant="soft" @click="isExpenseModalOpen = false">Cancelar</UButton>
          <UButton color="warning" :loading="savingExpense" @click="handleSaveExpense">Guardar Gasto</UButton>
        </div>
      </template>
    </UModal>

    <!-- Modal Añadir Ingreso POS -->
    <UModal v-model:open="isIncomeModalOpen" title="Registrar Ingreso Extra Rápido">
      <template #body>
        <div class="space-y-4 p-1">
          <UFormField label="Descripción del Ingreso *">
            <UInput v-model="incomeModel.description" placeholder="Ej: Pago pendiente, ajuste de caja, etc." />
          </UFormField>
          <UFormField label="Monto (Bs.) *">
            <UInput v-model.number="incomeModel.amount" type="number" step="0.10" />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3 w-full">
          <UButton color="neutral" variant="soft" @click="isIncomeModalOpen = false">Cancelar</UButton>
          <UButton color="success" :loading="savingIncome" @click="handleSaveIncome">Guardar Ingreso</UButton>
        </div>
      </template>
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
