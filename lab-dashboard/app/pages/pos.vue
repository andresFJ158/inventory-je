<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { getImageUrl } from '~/utils/image'

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
const clientSelectError = ref(false)
const clientSelectEl = ref<HTMLElement | null>(null)
const isWholesaleGlobal = ref(false)
const isCashRegisterOpen = ref(true)
const activeCashId = ref<string | null>(null)
const imageErrors = ref<Record<string, boolean>>({})

// Active Order State
const pendingOrders = ref<any[]>([])
const activeOrderIndex = ref<number>(0)

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

// Credit variables
const creditInitialPayment = ref<number | null>(null)
const creditEndDate = ref<string>('')

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

// Order Expenses Modal (vendedor: gastos de la orden, no de caja)
const isOrderExpenseModalOpen = ref(false)
const orderExpenses = ref<any[]>([])
const loadingOrderExpenses = ref(false)
const orderExpenseModel = ref({ concept: '', amount: 0, chargeToClient: false })
const savingOrderExpense = ref(false)

async function fetchOrderExpenses() {
  if (!orderId.value) return
  loadingOrderExpenses.value = true
  try {
    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({ getOrderExpenses: 'ok', id_order: String(orderId.value) }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof res === 'string' ? JSON.parse(res) : res
    orderExpenses.value = data.results || []
  } catch {
    orderExpenses.value = []
  } finally {
    loadingOrderExpenses.value = false
  }
}

async function openOrderExpenseModal() {
  if (!orderId.value) {
    toast.add({ title: 'Primero crea una orden.', color: 'error' })
    return
  }
  orderExpenseModel.value = { concept: '', amount: 0, chargeToClient: false }
  isOrderExpenseModalOpen.value = true
  await fetchOrderExpenses()
}

async function handleSaveOrderExpense() {
  if (!orderId.value || !orderExpenseModel.value.concept.trim() || orderExpenseModel.value.amount <= 0) {
    toast.add({ title: 'Ingresa un concepto válido y monto mayor a 0.', color: 'error' })
    return
  }

  // Client-side validation: total cannot be negative for company expenses
  if (!orderExpenseModel.value.chargeToClient) {
    const expenseTotal = parseFloat(String(orderExpenseModel.value.amount)) || 0
    const currentMax = subtotal.value - totalDiscount.value + totalOrderExpensesClient.value - totalOrderExpensesCompany.value
    if (expenseTotal > currentMax) {
      toast.add({
        title: 'Gasto excedido',
        description: `El total de la orden no puede ser negativo. El saldo disponible es Bs. ${currentMax.toFixed(2)}.`,
        color: 'error'
      })
      return
    }
  }

  savingOrderExpense.value = true
  try {
    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        addOrderExpense: 'ok',
        id_order: String(orderId.value),
        concept: orderExpenseModel.value.concept.trim(),
        amount: String(orderExpenseModel.value.amount),
        charge_to_client: orderExpenseModel.value.chargeToClient ? '1' : '0',
        id_admin: String(auth.user?.id_admin ?? 0)
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof res === 'string' ? JSON.parse(res) : res
    if (data.status === 200) {
      toast.add({ title: 'Gasto registrado.', color: 'success' })
      orderExpenseModel.value = { concept: '', amount: 0, chargeToClient: false }
      await fetchOrderExpenses()
    } else {
      toast.add({ title: data.message || 'Error al registrar gasto.', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de conexión.', color: 'error' })
  } finally {
    savingOrderExpense.value = false
  }
}

async function deleteOrderExpense(expenseId: number) {
  try {
    await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({ deleteOrderExpense: 'ok', id_expense: String(expenseId) }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    await fetchOrderExpenses()
  } catch {
    toast.add({ title: 'Error al eliminar gasto.', color: 'error' })
  }
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
    const prodData = await $fetch<any>('/api/products?linkTo=status_product&equalTo=1&orderBy=is_compound_product&orderMode=DESC', {
      headers: apiHeaders
    })
    if (prodData.status === 200) {
      products.value = prodData.results || []
    }

    // 2. Fetch inventory for assigned warehouse office (falls back to sucursal if no warehouse)
    const officeId = auth.effectiveOfficeId ?? 3
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


  } catch (e) {
    console.error('Error fetching catalog data:', e)
  }
}

// --- Price helpers (Fallback por SKU) ---
function getProductPriceMeta(productId: string | number) {
  const idStr = String(productId)
  const prod = products.value.find(p => String(p.id_product) === idStr)
  if (prod) {
    return {
      price: parseFloat(prod.price_product) || 0,
      wholesalePrice: parseFloat(prod.wholesale_price_product) || 0,
      wholesaleQty: parseInt(prod.wholesale_qty_product) || 0
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

// Fetch clients
async function fetchClients() {
  try {
    const data = await $fetch<any>(`/api/clients?token=${auth.token}`, {
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

// Sort once when products change; combos always first
const sortedProducts = computed(() =>
  [...products.value].sort((a, b) =>
    parseInt(b.is_compound_product ?? 0) - parseInt(a.is_compound_product ?? 0)
  )
)

// Debounced search: filter runs on debounced value, not on every keystroke
const searchRaw = ref('')
let _searchTimer: ReturnType<typeof setTimeout> | null = null
watch(searchRaw, val => {
  if (_searchTimer) clearTimeout(_searchTimer)
  _searchTimer = setTimeout(() => { search.value = val }, 200)
})

// Filter from sorted list — sort is stable and not repeated on each keystroke
const filteredProducts = computed(() => {
  return sortedProducts.value.filter(p => {
    // Ocultar productos sin stock o desactivados
    const inStock = (inventory.value[p.id_product] || 0) > 0
    const isActive = String(p.status_product ?? 1) !== '0'
    if (!inStock || !isActive) return false

    const matchesCategory = activeCategory.value === 'all' || String(p.id_category_product) === String(activeCategory.value)
    const matchesSearch = !search.value ||
      p.title_product.toLowerCase().includes(search.value.toLowerCase()) ||
      p.sku_product.toLowerCase().includes(search.value.toLowerCase()) ||
      (p.code_product && p.code_product.toLowerCase().includes(search.value.toLowerCase()))
    return matchesCategory && matchesSearch
  })
})

// Initialize order on mount
async function fetchPendingOrders() {
  try {
    const officeId = auth.officeId ?? 3
    const adminId = auth.user?.id_admin || 1
    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        getPosPendingOrders: 'ok',
        id_office: String(officeId),
        id_admin: String(adminId),
        token: auth.token || ''
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof res === 'string' ? JSON.parse(res) : res
    if (data.status === 200 && data.results) {
      pendingOrders.value = data.results
    } else {
      pendingOrders.value = []
    }
  } catch (e) {
    console.error('Error fetching pending orders:', e)
    pendingOrders.value = []
  }
}

async function selectOrder(index: number) {
  activeOrderIndex.value = index
  const order = pendingOrders.value[index]
  checkoutSuccess.value = false
  if (order) {
    orderId.value = String(order.id_order)
    transactionOrder.value = order.transaction_order
    selectedClient.value = (order.id_client_order && String(order.id_client_order) !== '0') ? String(order.id_client_order) : ''
    await fetchCart()
  }
}

async function checkActiveOrder() {
  try {
    await fetchPendingOrders()
    if (pendingOrders.value.length > 0) {
      await selectOrder(0)
    } else {
      await handleNewOrder()
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
  
  if (isCashRegisterOpen.value) {
    await fetchPendingOrders()
    if (pendingOrders.value.length > 0) {
      await selectOrder(0)
    }
  }
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
      await fetchPendingOrders()
      const newIndex = pendingOrders.value.findIndex((o: any) => String(o.id_order) === String(data.id_order))
      if (newIndex !== -1) {
        await selectOrder(newIndex)
      } else {
        orderId.value = String(data.id_order)
        transactionOrder.value = data.transaction_order
        if (!selectedClient.value) {
           selectedClient.value = ''
        }
        cartItems.value = []
        checkoutSuccess.value = false
      }
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
    const officeId = auth.effectiveOfficeId ?? 3
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

    const res = typeof ajaxRes === 'string' ? ajaxRes.trim() : ''
    if (res === 'error stock') {
      toast.add({ title: 'No hay stock disponible.', color: 'error' })
      return
    } else if (res === 'logout') {
      toast.add({ title: 'Sesión expirada.', color: 'error' })
      auth.logout()
      useRouter().push('/login')
      return
    } else if (res.startsWith('error')) {
      toast.add({ title: 'Error: ' + res, color: 'error' })
      return
    }
    // res puede ser 'product exist' o 'ok|saleId|canOverride' — en ambos casos refrescamos el carrito

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

// Delete cart item — confirmed via UModal
const showDeleteItemModal = ref(false)
const pendingDeleteItem   = ref<any>(null)

function confirmDeleteItem(item: any) {
  pendingDeleteItem.value   = item
  showDeleteItemModal.value = true
}

async function doDeleteItem() {
  const item = pendingDeleteItem.value
  showDeleteItemModal.value = false
  pendingDeleteItem.value   = null
  if (!item) return
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

// Clear all items in cart — confirmed via UModal
const showClearCartModal = ref(false)

function confirmClearCart() {
  if (!orderId.value) return
  showClearCartModal.value = true
}

async function doClearCart() {
  showClearCartModal.value = false
  if (!orderId.value) return
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
const isCancelModalOpen = ref(false)

function cancelOrder() {
  if (!orderId.value) {
    toast.add({ title: 'No hay orden seleccionada', color: 'error' })
    return
  }
  isCancelModalOpen.value = true
}

async function confirmCancelOrder() {
  if (!orderId.value) {
    toast.add({ title: 'No hay orden seleccionada', color: 'error' })
    return
  }
  try {
    console.log('Deleting order:', orderId.value)
    const response = await $fetch('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        idOrderDelete: orderId.value,
        token: auth.token || ''
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    if (response && response.includes && response.includes('error')) {
      toast.add({ title: 'Error al eliminar la orden', description: String(response), color: 'error' })
      return
    }

    orderId.value = null
    transactionOrder.value = null
    selectedClient.value = ''
    cartItems.value = []
    isCancelModalOpen.value = false
    await fetchPendingOrders()
    if (pendingOrders.value.length > 0) {
      await selectOrder(0)
    } else {
      await handleNewOrder()
    }
  } catch (e: any) {
    console.error('Error deleting order:', e)
    toast.add({ title: 'Error al eliminar la orden', description: e.message, color: 'error' })
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

const totalOrderExpensesClient = computed(() => {
  return orderExpenses.value
    .filter(exp => exp.charge_to_client_expense == 1)
    .reduce((acc, exp) => acc + parseFloat(exp.amount_expense || 0), 0)
})

const totalOrderExpensesCompany = computed(() => {
  return orderExpenses.value
    .filter(exp => exp.charge_to_client_expense == 0)
    .reduce((acc, exp) => acc + parseFloat(exp.amount_expense || 0), 0)
})

const total = computed(() => {
  return subtotal.value - totalDiscount.value + totalOrderExpensesClient.value - totalOrderExpensesCompany.value
})

// Sync order totals to the backend when cart or client changes
watch([selectedClient, subtotal, totalDiscount, total], async ([newClientVal, newSub, newDisc, newTot]) => {
  if (!orderId.value || !newClientVal || newClientVal === '0') return
  try {
    const currentPending = pendingOrders.value[activeOrderIndex.value]
    if (currentPending && String(currentPending.id_order) === orderId.value) {
      currentPending.id_client_order = newClientVal
    }
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
  if (!selectedClient.value || selectedClient.value === '0') {
    clientSelectError.value = true
    nextTick(() => clientSelectEl.value?.scrollIntoView({ behavior: 'smooth', block: 'center' }))
    setTimeout(() => { clientSelectError.value = false }, 1500)
    return
  }
  cashReceived.value = total.value
  transferId.value = ''
  payMethod.value = 'efectivo'
  wantInvoice.value = false
  isPaying.value = true
  fetchQRImage()
}

const qrFile = ref<File | null>(null)
const qrFilePreview = ref<string | null>(null)

function handleFileSelect(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    qrFile.value = file
    
    // Create preview if it's an image
    if (file.type.startsWith('image/')) {
      if (qrFilePreview.value) URL.revokeObjectURL(qrFilePreview.value)
      qrFilePreview.value = URL.createObjectURL(file)
    } else {
      qrFilePreview.value = null
    }
  } else {
    qrFile.value = null
    qrFilePreview.value = null
  }
}

// Return cash change calculation
const cashChange = computed(() => {
  if (cashReceived.value === null) return 0
  return Math.max(0, cashReceived.value - total.value)
})

// Finalize Checkout
async function handleCheckout() {
  if (!selectedClient.value || selectedClient.value === '0') {
    toast.add({ title: 'Selecciona un cliente válido antes de generar el cobro.', color: 'error' })
    return
  }

  if (payMethod.value === 'efectivo' && (cashReceived.value || 0) < total.value) {
    toast.add({ title: 'El efectivo recibido es menor al total a pagar.', color: 'error' })
    return
  }

  try {
    const formData = new FormData()
    formData.append('payPosOrder', 'ok')
    formData.append('idOrder', orderId.value!)
    formData.append('method', payMethod.value)
    formData.append('transfer', transferId.value)
    formData.append('invoice', wantInvoice.value ? 'yes' : 'no')
    formData.append('sellerId', String(auth.user?.id_admin || 1))
    formData.append('subtotal', String(subtotal.value.toFixed(2)))
    formData.append('discount', String(totalDiscount.value.toFixed(2)))
    formData.append('tax', String(0))
    formData.append('total', String(total.value.toFixed(2)))
    formData.append('token', auth.token || '')
    
    if (payMethod.value === 'QR' && qrFile.value) {
      formData.append('proof', qrFile.value)
    }
    
    if (payMethod.value === 'credito') {
      if (!creditEndDate.value) {
        toast.add({ title: 'Ingresa la fecha de finalización del crédito.', color: 'error' })
        return
      }
      if (creditInitialPayment.value === null || creditInitialPayment.value < 0) {
        toast.add({ title: 'El dinero inicial no puede ser negativo.', color: 'error' })
        return
      }
      formData.append('creditInitialPayment', String(creditInitialPayment.value))
      formData.append('creditEndDate', creditEndDate.value)
    }

    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: formData
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

      // Update inventory stock catalog
      await fetchCatalog()
      await fetchPendingOrders()
      if (pendingOrders.value.length > 0) {
        await selectOrder(0)
      } else {
        // No crear orden automáticamente — dejar el carrito vacío
        orderId.value = null
        transactionOrder.value = null
        selectedClient.value = ''
        cartItems.value = []
      }
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

    <!-- Tab bar for pending orders -->
    <div v-if="isCashRegisterOpen" class="flex gap-1 items-center overflow-x-auto pb-2 border-b border-slate-200 shrink-0 print:hidden scrollbar-thin">
      <button
        v-for="(order, index) in pendingOrders"
        :key="order.id_order"
        :style="{
          backgroundColor: activeOrderIndex === index ? '#f1ee63' : '#f1ee6380'
        }"
        class="px-2 py-1 text-xs rounded font-mono transition-all hover:opacity-80 font-medium min-w-fit flex items-center gap-1"
        @click="selectOrder(index)"
      >
        <UIcon name="i-lucide-receipt" class="w-3 h-3" />
        <span :style="{ color: activeOrderIndex === index ? '#000000' : '#6b7280' }">
          Ord #{{ order.transaction_order }}
        </span>
      </button>
      
      <UButton
        icon="i-lucide-plus-circle"
        color="primary"
        size="xs"
        @click="handleNewOrder"
      >
        Nueva Orden
      </UButton>
    </div>

    <!-- Main View Grid layout (Products left, cart right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1 overflow-hidden min-h-0 print:hidden">
      <!-- Left: Products Catalog & Filtering (8 cols) -->
      <div class="lg:col-span-8 flex flex-col space-y-4 min-h-0">
        <!-- Search and Category slider -->
        <div class="bg-white border border-slate-200 p-4 rounded-xl space-y-4">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-bold text-slate-800">Catálogo de Productos</h2>
            <div class="relative w-64">
              <UInput
                v-model="searchRaw"
                icon="i-lucide-search"
                placeholder="Buscar por nombre o SKU..."
                class="w-full"
                :trailing-icon="searchRaw ? 'i-lucide-x' : undefined"
                @click:trailing="searchRaw = ''; search = ''"
                @keyup.enter="if (filteredProducts.length > 0) { addToCart(filteredProducts[0]); searchRaw = ''; search = '' }"
              />
            </div>
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
        <div class="flex-1 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 pb-6 scrollbar-thin">
          <!-- Estado vacío del catálogo -->
          <div v-if="filteredProducts.length === 0" class="col-span-full flex flex-col items-center justify-center text-center py-16">
            <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
              <UIcon :name="searchRaw || activeCategory !== 'all' ? 'i-lucide-search-x' : 'i-lucide-package-open'" class="w-7 h-7 text-slate-400" />
            </div>
            <p class="text-sm font-semibold text-slate-700">
              {{ searchRaw || activeCategory !== 'all' ? 'Sin coincidencias' : 'No hay productos' }}
            </p>
            <p class="text-xs text-slate-500 mt-1 max-w-xs">
              {{ searchRaw || activeCategory !== 'all' ? 'Prueba con otro término o cambia de categoría.' : 'Aún no hay productos disponibles en este catálogo.' }}
            </p>
            <UButton
              v-if="searchRaw || activeCategory !== 'all'"
              class="mt-4"
              color="neutral"
              variant="outline"
              size="xs"
              icon="i-lucide-x"
              @click="searchRaw = ''; search = ''; activeCategory = 'all'"
            >
              Limpiar filtros
            </UButton>
          </div>

          <div
            v-for="prod in filteredProducts"
            :key="prod.id_product"
            class="bg-white border border-slate-200/80 rounded-lg overflow-hidden shadow-sm hover:border-emerald-400 hover:shadow-md transition duration-200 cursor-pointer flex flex-col justify-between h-[188px]"
            @click="addToCart(prod)"
          >
            <!-- Product Image -->
            <div class="h-20 bg-slate-100 flex items-center justify-center p-2 relative overflow-hidden shrink-0">
              <span class="absolute top-1 right-1 text-[8px] font-mono font-bold bg-slate-100 border border-slate-300 px-1 py-0.5 rounded text-slate-600 z-10">
                {{ prod.sku_product }}
              </span>
              <span
                v-if="parseInt(prod.is_compound_product ?? 0) === 1"
                class="absolute top-1 left-1 text-[8px] font-bold bg-purple-600 text-white px-1 py-0.5 rounded z-10 tracking-wide"
              >
                COMBO
              </span>
              <img
                v-if="prod.img_product && !imageErrors[prod.id_product]"
                :src="getImageUrl(prod.img_product)"
                class="h-full w-full object-contain"
                @error="imageErrors[prod.id_product] = true"
              >
              <UIcon
                v-else
                name="i-lucide-image"
                class="w-7 h-7 text-slate-600"
              />
            </div>

            <!-- Product Details -->
            <div class="p-2 flex-1 flex flex-col justify-between min-h-0">
              <div>
                <h3 class="font-bold text-slate-800 text-[11px] line-clamp-2 leading-tight">{{ decodeURIComponent(prod.title_product).replace(/\+/g, ' ') }}</h3>
                <span class="text-[9px] text-slate-500 block mt-0.5">U.M: {{ prod.unit_product }}</span>
              </div>

              <div class="flex justify-between items-end pt-1.5">
                <!-- Price info lookup -->
                <div class="min-w-0">
                  <div v-if="Number(prod.discount_product || 0) > 0" class="leading-none">
                    <span class="text-[8px] text-red-400 line-through block">Bs. {{ Number(getProductPrice(prod)).toFixed(2) }}</span>
                    <span class="pos-price font-bold font-mono text-[11px]">Bs. {{ Number(getProductDiscountedPrice(prod)).toFixed(2) }}</span>
                  </div>
                  <span v-else class="pos-price font-bold font-mono text-[11px]">
                    Bs. {{ Number(getProductPrice(prod)).toFixed(2) }}
                  </span>
                </div>

                <!-- Stock info lookup -->
                <UBadge
                  :color="(inventory[prod.id_product] || 0) > 0 ? 'success' : 'error'"
                  variant="soft"
                  size="xs"
                  class="text-[8px] font-bold shrink-0"
                >
                  {{ inventory[prod.id_product] || 0 }}
                </UBadge>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Cart & Order Panel (4 cols) -->
      <div class="lg:col-span-4 bg-white border border-slate-200 rounded-xl flex flex-col justify-between overflow-hidden shadow-2xl">
        <!-- Cart Header -->
        <div class="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
          <div>
            <h2 class="font-extrabold text-slate-800 text-base">Carrito</h2>
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
              v-if="auth.role !== 'vendedor'"
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
              @click="auth.role === 'vendedor' ? openOrderExpenseModal() : (isExpenseModalOpen = true)"
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
              @click="confirmClearCart"
            />
          </div>
        </div>

        <!-- Cart Body (Client lookup, items list) -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 scrollbar-thin">
          <!-- Client select & add client -->
          <div v-if="orderId" ref="clientSelectEl" class="flex gap-2 items-center">
            <div class="flex-1 relative">
              <USelectMenu
                v-model="selectedClient"
                :items="clients.map(c => ({ value: String(c.id_client), label: `${c.name_client} ${c.surname_client || ''} (${c.dni_client})` }))"
                value-key="value"
                label-key="label"
                placeholder="Seleccionar cliente *"
                class="w-full"
                :class="clientSelectError ? 'ring-2 ring-red-500 rounded-lg animate-shake' : ''"
                searchable
                searchable-placeholder="Buscar cliente..."
              />
              <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <p v-if="clientSelectError" class="absolute -bottom-5 left-0 text-[10px] font-semibold text-red-500 flex items-center gap-1">
                  <UIcon name="i-lucide-alert-circle" class="w-3 h-3" />
                  Debes seleccionar un cliente para cobrar
                </p>
              </Transition>
            </div>
            <UButton
              icon="i-lucide-user-plus"
              color="neutral"
              variant="soft"
              @click="isClientModalOpen = true"
            />
          </div>

          <!-- Wholesale global switch toggle -->
          <div v-if="orderId" class="flex items-center justify-between bg-slate-50 p-2 border border-slate-200 rounded-lg">
            <span class="text-xs text-slate-600 font-semibold">Habilitar precio mayorista</span>
            <USwitch v-model="isWholesaleGlobal" />
          </div>

          <!-- Items list in order -->
          <div v-if="cartItems.length > 0" class="space-y-3">
            <div
              v-for="item in cartItems"
              :key="item.id_sale"
              class="flex justify-between items-start gap-2 bg-slate-100/60 p-2.5 rounded-lg border border-slate-200/60"
            >
              <div class="flex-1 min-w-0">
                <span class="text-xs font-bold text-slate-800 block truncate">
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
                <span class="text-xs font-mono font-bold text-slate-800 px-1.5 bg-white border border-slate-300 rounded">
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
                  @click="confirmDeleteItem(item)"
                />
              </div>
            </div>
          </div>
          
          <div v-else class="flex flex-col items-center justify-center text-center py-12 px-4">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
              <UIcon name="i-lucide-shopping-cart" class="w-6 h-6 text-slate-400" />
            </div>
            <p class="text-sm font-semibold text-slate-700">
              {{ orderId ? 'Carrito vacío' : 'Sin orden activa' }}
            </p>
            <p class="text-xs text-slate-500 mt-1">
              {{ orderId ? 'Toca un producto del catálogo para agregarlo.' : 'Crea una nueva orden para empezar a vender.' }}
            </p>
          </div>
        </div>

        <!-- Checkout success panel -->
        <div v-if="checkoutSuccess" class="m-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-center space-y-3 relative">
          <UButton
            icon="i-lucide-x"
            color="neutral"
            variant="ghost"
            class="absolute top-1 right-1 p-1"
            size="xs"
            @click="checkoutSuccess = false"
          />
          <UIcon name="i-lucide-check-circle" class="w-10 h-10 text-emerald-500 mx-auto" />
          <h3 class="text-sm font-bold text-emerald-700">¡Venta Procesada con Éxito!</h3>
          <p v-if="lastOrderReceipt" class="text-xs text-slate-600">Orden #{{ lastOrderReceipt.transaction }} · Bs. {{ lastOrderReceipt.total }}</p>
          <UButton
            color="primary"
            size="sm"
            block
            icon="i-lucide-printer"
            @click="printReceipt"
          >
            Imprimir Ticket
          </UButton>
          <UButton
            color="neutral"
            variant="soft"
            size="sm"
            block
            icon="i-lucide-plus-circle"
            @click="checkoutSuccess = false; handleNewOrder()"
          >
            Nueva Orden
          </UButton>
        </div>

        <!-- Cart Footer Totals and Checkout -->
        <div class="p-4 border-t border-slate-200 bg-white space-y-4">
          <div class="space-y-3">
            <div class="flex justify-between text-sm font-bold text-slate-600">
              <span>Subtotal:</span>
              <span class="font-mono text-slate-800">Bs. {{ subtotal.toFixed(2) }}</span>
            </div>
            <div v-if="totalDiscount > 0" class="flex justify-between text-sm font-bold text-rose-500">
              <span>Descuento:</span>
              <span class="font-mono">-Bs. {{ totalDiscount.toFixed(2) }}</span>
            </div>
            <div v-if="totalOrderExpensesClient > 0" class="flex justify-between text-sm font-bold text-amber-600">
              <span>Gastos (Al cliente):</span>
              <span class="font-mono">+Bs. {{ totalOrderExpensesClient.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between font-black text-slate-900 text-lg pt-3 border-t-2 border-slate-200">
              <span>TOTAL:</span>
              <span class="pos-price font-mono text-2xl tracking-tight text-emerald-600">Bs. {{ total.toFixed(2) }}</span>
            </div>
          </div>

          <!-- Status and Action Button -->
          <div v-if="orderId">
            <UButton
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
    </div>

    <!-- Client Modal Form -->
    <UModal v-model:open="isClientModalOpen" title="Registrar Nuevo Cliente">
      <template #body>
        <form class="space-y-4" @submit.prevent="handleRegisterClient">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Nombre *</label>
              <UInput v-model="newClient.name" placeholder="Ej: Juan" required />
            </div>
            <div>
              <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Apellido</label>
              <UInput v-model="newClient.surname" placeholder="Ej: Perez" />
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">DNI / Documento *</label>
            <UInput v-model="newClient.dni" placeholder="Ej: 1234567" required />
          </div>
          <div>
            <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Correo Electrónico</label>
            <UInput v-model="newClient.email" type="email" placeholder="Ej: juan@perez.com" />
          </div>
          <div>
            <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Teléfono</label>
            <UInput v-model="newClient.phone" placeholder="Ej: 79008000" />
          </div>
          <div>
            <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Dirección</label>
            <UInput v-model="newClient.address" placeholder="Ej: Zona Central Calle Sucre #123" />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
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
          <div class="bg-slate-100 p-4 border border-slate-200 rounded-xl text-center">
            <span class="text-xs text-slate-400 block mb-1">Monto a Cobrar</span>
            <span class="pos-price text-2xl font-extrabold font-mono">Bs. {{ total.toFixed(2) }}</span>
          </div>

          <!-- Payment methods toggle -->
          <div>
            <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-2">Método de Pago</label>
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
              <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Efectivo Recibido</label>
              <UInput
                v-model.number="cashReceived"
                type="number"
                step="any"
                placeholder="Bs. 0.00"
              />
            </div>
            <div class="flex justify-between items-center text-xs text-slate-400 pt-1 font-mono">
              <span>Cambio a devolver:</span>
              <span class="text-sm font-bold text-slate-800">Bs. {{ cashChange.toFixed(2) }}</span>
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
          </div>
          
          <div v-if="payMethod === 'QR' && (auth.role === 'vendedor' || auth.user?.type_seller)">
            <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Imagen / Comprobante (Opcional)</label>
            <input
              type="file"
              accept="image/*,.pdf"
              class="block w-full text-sm text-slate-500
                file:mr-4 file:py-2 file:px-4
                file:rounded-full file:border-0
                file:text-sm file:font-semibold
                file:bg-emerald-50 file:text-emerald-700
                hover:file:bg-emerald-100"
              @change="handleFileSelect"
            />
            <div v-if="qrFilePreview" class="mt-4 flex justify-center">
              <img :src="qrFilePreview" alt="Vista previa comprobante" class="max-h-48 rounded-lg shadow-md border border-slate-200" />
            </div>
          </div>

          <!-- Credit details form -->
          <div v-if="payMethod === 'credito'" class="space-y-3">
            <div>
              <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Dinero Inicial (Bs.) *</label>
              <UInput
                v-model.number="creditInitialPayment"
                type="number"
                step="any"
                placeholder="Bs. 0.00"
                min="0"
              />
            </div>
            <div>
              <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Fecha de Finalización *</label>
              <UInput
                v-model="creditEndDate"
                type="date"
                required
              />
            </div>
            <div class="text-center py-2 text-xs text-slate-400">
              Esta orden se registrará como crédito para el cliente. El monto inicial se registrará como el primer pago.
            </div>
          </div>

          <!-- Invoice switch checkbox -->
          <div class="flex items-center justify-between border-t border-slate-200 pt-3">
            <span class="text-xs text-slate-600 font-semibold">¿Emitir Factura Electrónica?</span>
            <USwitch v-model="wantInvoice" />
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
            <UButton color="neutral" variant="ghost" @click="isPaying = false">Cancelar</UButton>
            <UButton color="primary" @click="handleCheckout">{{ wantInvoice ? 'Cobrar CON Factura' : 'Cobrar SIN Factura' }}</UButton>
          </div>
        </div>
      </template>
    </UModal>

    <!-- Overlay Caja Cerrada (Glassmorphism + Blur) -->
    <div v-if="!isCashRegisterOpen" class="absolute inset-0 z-40 backdrop-blur-sm bg-white/80 flex items-center justify-center p-4">
      <div class="bg-white border border-slate-200 p-8 rounded-2xl max-w-sm w-full shadow-2xl text-center space-y-6 transform scale-100 transition-all duration-300">
        <!-- Circular Wallet Icon in Red -->
        <div class="mx-auto w-16 h-16 bg-rose-50 rounded-full flex items-center justify-center text-rose-500 border border-rose-100">
          <UIcon name="i-lucide-wallet" class="w-8 h-8" />
        </div>
        
        <div class="space-y-2">
          <h2 class="text-xl font-extrabold text-slate-800 tracking-tight">
            Caja Cerrada
          </h2>
          <p class="text-xs text-slate-500 leading-relaxed px-2">
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
    <UModal v-model:open="isExpenseModalOpen" :ui="{ width: 'sm:max-w-md' }">
      <template #header>
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center shrink-0">
            <UIcon name="i-lucide-receipt" class="w-4 h-4 text-amber-500" />
          </div>
          <div>
            <p class="text-sm font-bold text-slate-800">Registrar Gasto Rápido</p>
            <p class="text-xs text-slate-500">Se carga a la caja activa</p>
          </div>
        </div>
      </template>
      <template #body>
        <div class="space-y-5 px-1 pb-1">
          <!-- Categorías rápidas -->
          <div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Categoría rápida</p>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="cat in ['Pasajes', 'Almuerzo', 'Delivery', 'Limpieza', 'Material', 'Otro']"
                :key="cat"
                type="button"
                class="px-2.5 py-1 text-xs font-medium rounded-full border transition-all"
                :class="expenseModel.description === cat
 ? 'bg-amber-500 border-amber-500 text-white'
 : 'bg-white border-slate-200 text-slate-600 hover:border-amber-400 hover:text-amber-600'"
                @click="() => {
                  if (expenseModel.description === cat) {
                    expenseModel.description = ''
                    expenseModel.amount = 0
                  } else {
                    expenseModel.description = cat
                    if (cat === 'Pasajes') expenseModel.amount = 10
                    else if (cat === 'Almuerzo') expenseModel.amount = 20
                    else if (cat === 'Limpieza') expenseModel.amount = 15
                    else if (cat === 'Delivery') expenseModel.amount = 15
                  }
                }"
              >
                {{ cat }}
              </button>
            </div>
          </div>

          <!-- Descripción -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
              Descripción <span class="text-red-400">*</span>
            </label>
            <div class="relative">
              <UIcon name="i-lucide-file-text" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
              <input
                v-model="expenseModel.description"
                type="text"
                placeholder="Ej: Pasajes al mercado, etc."
                class="w-full pl-9 pr-4 py-2.5 text-sm bg-white border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400/50 focus:border-amber-400 transition-all"
              />
            </div>
          </div>

          <!-- Monto -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
              Monto <span class="text-red-400">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-500 pointer-events-none">Bs.</span>
              <input
                v-model.number="expenseModel.amount"
                type="number"
                step="0.10"
                min="0"
                placeholder="0.00"
                class="w-full pl-10 pr-4 py-2.5 text-sm bg-white border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400/50 focus:border-amber-400 transition-all font-mono"
              />
            </div>
          </div>

          <!-- Preview -->
          <div v-if="expenseModel.amount > 0 && expenseModel.description" class="flex items-center justify-between bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
            <div class="flex items-center gap-2">
              <UIcon name="i-lucide-check-circle" class="w-4 h-4 text-amber-500" />
              <span class="text-xs font-semibold text-slate-700 truncate max-w-[180px]">{{ expenseModel.description }}</span>
            </div>
            <span class="text-sm font-extrabold text-amber-600 font-mono">Bs. {{ Number(expenseModel.amount).toFixed(2) }}</span>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2 w-full">
          <UButton color="neutral" variant="ghost" @click="isExpenseModalOpen = false; expenseModel = { description: '', amount: 0 }">
            Cancelar
          </UButton>
          <UButton
            color="warning"
            :loading="savingExpense"
            :disabled="!expenseModel.description || expenseModel.amount <= 0"
            icon="i-lucide-save"
            @click="handleSaveExpense"
          >
            Registrar Gasto
          </UButton>
        </div>
      </template>
    </UModal>

    <!-- Modal Cancelar Orden -->
    <UModal v-model:open="isCancelModalOpen" title="Cancelar Orden">
      <template #body>
        <div class="space-y-4 p-1 text-center">
          <div class="mx-auto w-16 h-16 bg-rose-50 rounded-full flex items-center justify-center text-rose-500 border border-rose-100 mb-4">
            <UIcon name="i-lucide-trash-2" class="w-8 h-8" />
          </div>
          <p class="text-slate-400 text-sm">
            ¿Estás seguro que deseas cancelar y eliminar esta orden? Esta acción no se puede deshacer.
          </p>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-center gap-4 w-full">
          <UButton color="neutral" variant="soft" @click="isCancelModalOpen = false">No, mantener</UButton>
          <UButton color="error" @click="confirmCancelOrder">Sí, eliminar orden</UButton>
        </div>
      </template>
    </UModal>

    <!-- Modal Añadir Ingreso POS -->
    <UModal v-model:open="isIncomeModalOpen" :ui="{ width: 'sm:max-w-md' }">
      <template #header>
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center shrink-0">
            <UIcon name="i-lucide-arrow-up-circle" class="w-4 h-4 text-emerald-500" />
          </div>
          <div>
            <p class="text-sm font-bold text-slate-800">Registrar Ingreso Extra</p>
            <p class="text-xs text-slate-500">Se suma a la caja activa</p>
          </div>
        </div>
      </template>
      <template #body>
        <div class="space-y-5 px-1 pb-1">
          <!-- Categorías rápidas -->
          <div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Categoría rápida</p>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="cat in ['Pago pendiente', 'Ajuste de caja', 'Devolución', 'Anticipo', 'Depósito', 'Otro']"
                :key="cat"
                type="button"
                class="px-2.5 py-1 text-xs font-medium rounded-full border transition-all"
                :class="incomeModel.description === cat
 ? 'bg-emerald-500 border-emerald-500 text-white'
 : 'bg-white border-slate-200 text-slate-600 hover:border-emerald-400 hover:text-emerald-600'"
                @click="incomeModel.description = incomeModel.description === cat ? '' : cat"
              >
                {{ cat }}
              </button>
            </div>
          </div>

          <!-- Descripción -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
              Descripción <span class="text-red-400">*</span>
            </label>
            <div class="relative">
              <UIcon name="i-lucide-file-text" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
              <input
                v-model="incomeModel.description"
                type="text"
                placeholder="Ej: Pago pendiente cliente X, etc."
                class="w-full pl-9 pr-4 py-2.5 text-sm bg-white border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50 focus:border-emerald-400 transition-all"
              />
            </div>
          </div>

          <!-- Monto -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
              Monto <span class="text-red-400">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-500 pointer-events-none">Bs.</span>
              <input
                v-model.number="incomeModel.amount"
                type="number"
                step="0.10"
                min="0"
                placeholder="0.00"
                class="w-full pl-10 pr-4 py-2.5 text-sm bg-white border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50 focus:border-emerald-400 transition-all font-mono"
              />
            </div>
          </div>

          <!-- Preview -->
          <div v-if="incomeModel.amount > 0 && incomeModel.description" class="flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
            <div class="flex items-center gap-2">
              <UIcon name="i-lucide-check-circle" class="w-4 h-4 text-emerald-500" />
              <span class="text-xs font-semibold text-slate-700 truncate max-w-[180px]">{{ incomeModel.description }}</span>
            </div>
            <span class="text-sm font-extrabold text-emerald-600 font-mono">+ Bs. {{ Number(incomeModel.amount).toFixed(2) }}</span>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2 w-full">
          <UButton color="neutral" variant="ghost" @click="isIncomeModalOpen = false; incomeModel = { description: '', amount: 0 }">
            Cancelar
          </UButton>
          <UButton
            color="success"
            :loading="savingIncome"
            :disabled="!incomeModel.description || incomeModel.amount <= 0"
            icon="i-lucide-save"
            @click="handleSaveIncome"
          >
            Registrar Ingreso
          </UButton>
        </div>
      </template>
    </UModal>

    <!-- Modal Gastos de Orden (vendedor) -->
    <UModal v-model:open="isOrderExpenseModalOpen" title="Gastos de la Orden">
      <template #body>
        <div class="space-y-4 p-1">
          <!-- Lista de gastos actuales -->
          <div v-if="loadingOrderExpenses" class="text-center py-4 text-slate-500 text-sm">Cargando gastos...</div>
          <div v-else-if="orderExpenses.length === 0" class="text-center py-3 text-slate-500 text-sm">Sin gastos registrados para esta orden.</div>
          <div v-else class="max-h-40 overflow-y-auto border border-slate-200 rounded-lg divide-y divide-slate-100">
            <div v-for="exp in orderExpenses" :key="exp.id_expense" class="flex items-center justify-between px-3 py-2 text-sm">
              <div>
                <span class="font-medium text-slate-800">{{ exp.concept_expense }}</span>
                <UBadge :color="exp.charge_to_client_expense == 1 ? 'success' : 'neutral'" variant="subtle" size="xs" class="ml-2">
                  {{ exp.charge_to_client_expense == 1 ? 'Al cliente' : 'Descuenta utilidad' }}
                </UBadge>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-mono text-xs text-emerald-600">Bs. {{ Number(exp.amount_expense).toFixed(2) }}</span>
                <UButton icon="i-lucide-trash-2" color="error" variant="ghost" size="xs" @click="deleteOrderExpense(exp.id_expense)" />
              </div>
            </div>
          </div>

          <div class="border-t border-slate-200 pt-3 space-y-3">
            <p class="text-xs font-semibold text-slate-500 uppercase">Nuevo gasto</p>
            
            <div class="flex flex-wrap gap-1.5 mb-2">
              <button
                v-for="cat in ['Pasajes', 'Delivery', 'Empaque', 'Otro']"
                :key="cat"
                type="button"
                class="px-2 py-1 text-[10px] font-medium rounded-full border transition-all"
                :class="orderExpenseModel.concept === cat
 ? 'bg-amber-500 border-amber-500 text-white'
 : 'bg-white border-slate-200 text-slate-600 hover:border-amber-400 hover:text-amber-600'"
                @click="() => {
                  if (orderExpenseModel.concept === cat) {
                    orderExpenseModel.concept = ''
                    orderExpenseModel.amount = 0
                  } else {
                    orderExpenseModel.concept = cat
                    if (cat === 'Pasajes') orderExpenseModel.amount = 10
                    else if (cat === 'Delivery') orderExpenseModel.amount = 15
                    else if (cat === 'Empaque') orderExpenseModel.amount = 5
                  }
                }"
              >
                {{ cat }}
              </button>
            </div>

            <UFormField label="Concepto *">
              <UInput v-model="orderExpenseModel.concept" placeholder="Ej: Delivery, Empaque..." class="w-full" />
            </UFormField>
            <UFormField label="Monto (Bs.) *">
              <UInput v-model.number="orderExpenseModel.amount" type="number" step="0.01" min="0" placeholder="0.00" class="w-full" />
            </UFormField>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
              <div>
                <span class="text-sm font-medium text-slate-700 block">Cobrar al cliente</span>
                <span class="text-xs text-slate-500">Suma al total de la orden</span>
              </div>
              <button
                type="button"
                @click="orderExpenseModel.chargeToClient = !orderExpenseModel.chargeToClient"
                :class="[
 orderExpenseModel.chargeToClient ? 'bg-emerald-500' : 'bg-slate-300',
 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none'
 ]"
              >
                <span :class="[orderExpenseModel.chargeToClient ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition']" />
              </button>
            </div>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3 w-full">
          <UButton color="neutral" variant="soft" @click="isOrderExpenseModalOpen = false">Cerrar</UButton>
          <UButton color="warning" :loading="savingOrderExpense" @click="handleSaveOrderExpense">Agregar Gasto</UButton>
        </div>
      </template>
    </UModal>

    <!-- Delete item confirmation modal -->
    <UModal
      v-model:open="showDeleteItemModal"
      title="Quitar producto"
      description="¿Deseas remover este producto del carrito?"
    >
      <template #footer>
        <div class="flex justify-end gap-2 w-full">
          <UButton variant="ghost" color="neutral" @click="showDeleteItemModal = false">Cancelar</UButton>
          <UButton color="error" icon="i-lucide-trash" @click="doDeleteItem">Quitar</UButton>
        </div>
      </template>
    </UModal>

    <!-- Clear cart confirmation modal -->
    <UModal
      v-model:open="showClearCartModal"
      title="Vaciar carrito"
      description="¿Deseas vaciar todos los productos de esta orden? Esta acción no se puede deshacer."
    >
      <template #footer>
        <div class="flex justify-end gap-2 w-full">
          <UButton variant="ghost" color="neutral" @click="showClearCartModal = false">Cancelar</UButton>
          <UButton color="error" icon="i-lucide-rotate-ccw" @click="doClearCart">Vaciar</UButton>
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
