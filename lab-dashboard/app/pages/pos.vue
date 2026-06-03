<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()

// Carrito móvil drawer
const cartMobileOpen = ref(false)

// ── Constantes ──
const apiHeaders = { Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }
const ajaxBase = '/ajax/pos.ajax.php'

// ── Catálogo ──
const categories = ref<any[]>([])
const products = ref<any[]>([])
const inventory = ref<Record<string, number>>({})
const pricesMap = ref<Record<string, any>>({})
const clients = ref<any[]>([])

// ── Filtros ──
const activeCategory = ref<string>('all')
const search = ref('')

// ── Estado de caja ──
const isCashRegisterOpen = ref<boolean | null>(null)
const isOpeningCash = ref(false)
const openingAmount = ref<number>(0)
const cashModalLoading = ref(false)

// ── Órdenes activas ──
const pendingOrders = ref<any[]>([])

// ── Orden activa ──
const orderId = ref<string | null>(null)
const transactionOrder = ref<string | null>(null)
const selectedClient = ref<string>('')
const deliveryAddress = ref<string>('')
const orderNotes = ref<string>('')
const isWholesale = ref(false)
const cartItems = ref<any[]>([])
const orderStatus = ref<string>('Pendiente Despacho')
const checkoutSuccess = ref(false)
const lastReceipt = ref<any>(null)

// ── Pago ──
const payModal = ref(false)
const payMethod = ref<'efectivo' | 'qr' | 'transferencia' | 'credito' | 'consignacion'>('efectivo')
const payAmount = ref<number>(0)
const payReference = ref<string>('')
const proofFile = ref<File | null>(null)
const wantInvoice = ref(false)
const clientNit = ref<string>('')
const payLoading = ref(false)

// ── Gastos de despacho ──
const expensesModal = ref(false)
const orderExpenses = ref<any[]>([])
const newExpense = ref({ concept: '', amount: '' })
const expLoading = ref(false)
const packagingsList = ref<any[]>([])
const selectedPkgId = ref<string | null>(null)
const pkgQty = ref<number>(1)
const selectedPkg = computed(() => {
  if (!selectedPkgId.value) return null
  return packagingsList.value.find(p => String(p.id_packaging) === String(selectedPkgId.value))
})

// ── Nuevo cliente ──
const clientModal = ref(false)
const newClient = ref({ name: '', surname: '', dni: '', nit: '', email: '', phone: '', address: '' })

// ── Helpers ──
function decode(s: string) { return s ? decodeURIComponent(s).replace(/\+/g, ' ') : '' }
function fmt(val: number) { return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(val) }
function getDate(): string {
  const n = new Date()
  return `${n.getFullYear()}-${String(n.getMonth()+1).padStart(2,'0')}-${String(n.getDate()).padStart(2,'0')}`
}

// ── Roles ──
const isCashier = computed(() => auth.role === 'cajero' || auth.role === 'caja')
const isVendedor = computed(() => auth.role === 'vendedor' || auth.role === 'seller')
const isSuperAdmin = computed(() => auth.role === 'superadmin' || auth.role === 'admin')

// El cajero hace venta directa (sin flujo de despacho ni gastos logísticos).
// Vendedor y admin manejan el flujo completo: despacho, gastos, crédito, consignación.
const usesDispatchFlow = computed(() => !isCashier.value)

// Métodos de pago disponibles según rol
const payMethods = computed(() => {
  const base = [
    { value: 'efectivo', label: 'Efectivo', icon: 'i-lucide-banknote' },
    { value: 'qr', label: 'QR', icon: 'i-lucide-qr-code' },
    { value: 'transferencia', label: 'Transferencia', icon: 'i-lucide-arrow-right-left' }
  ]
  // Crédito y consignación: exclusivos de vendedores (y admin). NUNCA cajero.
  if (!isCashier.value) {
    base.push(
      { value: 'credito', label: 'Crédito', icon: 'i-lucide-credit-card' },
      { value: 'consignacion', label: 'Consignación', icon: 'i-lucide-package' }
    )
  }
  return base
})

// ── Carga inicial ──
async function fetchCategories() {
  const d = await $fetch<any>('/api/categories?linkTo=status_category&equalTo=1', { headers: apiHeaders }).catch(() => null)
  if (d?.status === 200) categories.value = d.results || []
}

async function fetchCatalog() {
  const [prodD, invD, purchD] = await Promise.all([
    $fetch<any>('/api/products?linkTo=status_product&equalTo=1', { headers: apiHeaders }).catch(() => null),
    $fetch<any>(`/api/product_inventory?linkTo=id_office_inventory&equalTo=${auth.officeId || 3}`, { headers: apiHeaders }).catch(() => null),
    $fetch<any>('/api/purchases?orderBy=date_created_purchase&orderMode=DESC', { headers: apiHeaders }).catch(() => null)
  ])
  if (prodD?.status === 200) products.value = prodD.results || []
  if (invD?.status === 200 && invD.results) {
    const inv: Record<string, number> = {}
    invD.results.forEach((i: any) => { inv[i.id_product_inventory] = parseFloat(i.stock_inventory) || 0 })
    inventory.value = inv
  }
  if (purchD?.status === 200 && purchD.results) {
    const prices: Record<string, any> = {}
    purchD.results.forEach((p: any) => {
      if (!prices[p.id_product_purchase]) {
        prices[p.id_product_purchase] = { price: parseFloat(p.price_purchase) || 0, wholesalePrice: parseFloat(p.may_product) || 0, wholesaleQty: parseInt(p.wholesale_quantity) || 0 }
      }
    })
    pricesMap.value = prices
  }
}

async function fetchClients() {
  const d = await $fetch<any>('/api/clients', { headers: apiHeaders }).catch(() => null)
  if (d?.status === 200) clients.value = d.results || []
}

async function fetchPendingOrders() {
  const officeId = auth.officeId || 3
  const fetchByStatus = async (st: string) => {
    let url = `/api/orders?linkTo=id_office_order,status_order&equalTo=${officeId},${st}&orderBy=id_order&orderMode=ASC`
    if (auth.role === 'superadmin' || auth.role === 'admin') {
      url = `/api/orders?linkTo=status_order&equalTo=${st}&orderBy=id_order&orderMode=ASC`
    }
    const d = await $fetch<any>(url, { headers: apiHeaders }).catch(() => null)
    return d?.status === 200 && Array.isArray(d.results) ? d.results : []
  }

  let list = await fetchByStatus('Pendiente Despacho')
  if (usesDispatchFlow.value) {
    const dList = await fetchByStatus('Despachado')
    list = [...list, ...dList]
  }
  
  pendingOrders.value = list
  if (list.length > 0 && !orderId.value) {
    await selectOrder(list[0])
  }
}

async function checkCashRegister() {
  if (isVendedor.value) { isCashRegisterOpen.value = true; return }
  const d = await $fetch<any>(`/api/cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=${getDate()},1,${auth.officeId || 3}`, { headers: apiHeaders }).catch(() => null)
  isCashRegisterOpen.value = d?.status === 200 && (d.results?.length || 0) > 0
}

onMounted(async () => {
  await checkCashRegister()
  await Promise.all([fetchCategories(), fetchCatalog(), fetchClients(), fetchPackagings()])
  if (isCashRegisterOpen.value || isVendedor.value) await fetchPendingOrders()
})

// ── Filtrado de productos ──
const filteredProducts = computed(() => products.value.filter(p => {
  const inStock = (inventory.value[p.id_product] || 0) > 0
  const byCategory = activeCategory.value === 'all' || String(p.id_category_product) === String(activeCategory.value)
  const bySearch = !search.value || decode(p.title_product || '').toLowerCase().includes(search.value.toLowerCase()) || (p.sku_product || '').toLowerCase().includes(search.value.toLowerCase())
  return inStock && byCategory && bySearch
}))

// ── Seleccionar orden ──
async function selectOrder(order: any) {
  orderId.value = String(order.id_order)
  transactionOrder.value = order.transaction_order
  orderStatus.value = order.status_order || 'Pendiente Despacho'
  selectedClient.value = order.id_client_order > 0 ? String(order.id_client_order) : ''
  deliveryAddress.value = order.delivery_address_order || ''
  orderNotes.value = order.notes_order || ''
  checkoutSuccess.value = false
  await fetchCart()
}

// ── Crear orden ──
async function handleNewOrder() {
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ order: 'new', idOffice: String(auth.officeId || 3), seller: String(auth.user?.id_admin || 1) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  let data: any
  if (typeof res === 'string') {
    const t = res.trim()
    if (t === 'current cash error') { toast.add({ title: 'No hay caja abierta hoy', color: 'error' }); return }
    if (t === 'yesterday cash error') { toast.add({ title: 'Hay cajas anteriores sin cerrar', color: 'error' }); return }
    try { data = JSON.parse(t) } catch { toast.add({ title: 'Error al crear orden', color: 'error' }); return }
  } else { data = res }
  if (data?.transaction_order) {
    orderId.value = String(data.id_order)
    transactionOrder.value = data.transaction_order
    orderStatus.value = 'Pendiente Despacho'
    selectedClient.value = ''
    deliveryAddress.value = ''
    orderNotes.value = ''
    cartItems.value = []
    checkoutSuccess.value = false
    await fetchPendingOrders()
  } else {
    toast.add({ title: data?.message || 'Error al crear orden', color: 'error' })
  }
}

// ── Carrito ──
async function fetchCart() {
  if (!orderId.value) { cartItems.value = []; return }
  const d = await $fetch<any>(`/api/sales?linkTo=id_order_sale&equalTo=${orderId.value}`, { headers: apiHeaders }).catch(() => null)
  cartItems.value = d?.status === 200 && d.results ? d.results : []
}

async function addToCart(product: any) {
  if (!orderId.value) { toast.add({ title: 'Genera una orden primero', color: 'warning' }); return }
  if (!selectedClient.value) { toast.add({ title: 'Selecciona un cliente', color: 'warning' }); return }
  const stock = inventory.value[product.id_product] || 0
  const inCart = cartItems.value.find(i => String(i.id_product_sale) === String(product.id_product))
  if ((inCart ? parseInt(inCart.qty_sale) : 0) + 1 > stock) { toast.add({ title: 'Sin stock suficiente', color: 'error' }); return }
  await $fetch(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ idProduct: String(product.id_product), idOrder: orderId.value!, idClient: selectedClient.value, seller: String(auth.user?.id_admin || 1), idOffice: String(auth.officeId || 3), isWholesale: isWholesale.value ? '1' : '0' }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  await fetchCart()
}

async function updateQty(item: any, delta: number) {
  const newQty = parseInt(item.qty_sale) + delta
  if (newQty < 1) return
  if (delta > 0 && newQty > (inventory.value[item.id_product_sale] || 0)) { toast.add({ title: 'Sin stock', color: 'error' }); return }
  const pm = pricesMap.value[item.id_product_sale] || { price: 0, wholesalePrice: 0, wholesaleQty: 0 }
  let price = pm.price
  if (isWholesale.value || (pm.wholesaleQty > 0 && newQty >= pm.wholesaleQty)) if (pm.wholesalePrice > 0) price = pm.wholesalePrice
  const disc = parseFloat(item.discount_sale) || 0
  if (disc > 0) price = price * (1 - disc / 100)
  await $fetch(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ idSaleUpdate: String(item.id_sale), qtySale: String(newQty), subtotalSale: String(price * newQty) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  await fetchCart()
}

async function deleteItem(item: any) {
  await $fetch(ajaxBase, { method: 'POST', body: new URLSearchParams({ idSaleDelete: String(item.id_sale) }).toString(), headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }).catch(() => null)
  await fetchCart()
}

async function cancelOrder() {
  if (!orderId.value || !confirm('¿Cancelar esta orden?')) return
  await $fetch(ajaxBase, { method: 'POST', body: new URLSearchParams({ idOrderDelete: orderId.value }).toString(), headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }).catch(() => null)
  orderId.value = null; transactionOrder.value = null; cartItems.value = []
  await fetchPendingOrders()
}

// ── Avanzar estado de orden ──
async function advanceOrderStatus(newStatus: string) {
  if (!orderId.value) return
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ updateOrderStatus: 'ok', id_order: orderId.value, status: newStatus }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  if (d?.status === 200) {
    orderStatus.value = newStatus
    toast.add({ title: `Orden → ${newStatus}`, color: 'success' })
    if (newStatus === 'Venta Confirmada') {
      lastReceipt.value = {
        transaction: transactionOrder.value,
        date: new Date().toLocaleString('es-ES'),
        client: clients.value.find(c => String(c.id_client) === selectedClient.value),
        items: [...cartItems.value],
        subtotal: subtotal.value, discount: totalDiscount.value,
        expenses: expensesTotal.value, total: total.value,
        method: payMethod.value, reference: payReference.value,
        invoice: wantInvoice.value, nit: clientNit.value,
        vendedor: auth.user?.name_admin
      }
      checkoutSuccess.value = true
      await fetchCatalog()
      await fetchPendingOrders()
    } else if (newStatus === 'Despachado') {
      await fetchPendingOrders()
    }
  }
}

// ── Sincronizar cliente y dirección ──
watch(selectedClient, async (val) => {
  if (!orderId.value || !val) return
  await $fetch(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ idOrderUpdate: orderId.value, idClient: val, subtotalOrder: String(subtotal.value), discountOrder: String(totalDiscount.value), taxOrder: '0', totalOrder: String(total.value) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  // Cargar NIT del cliente
  const c = clients.value.find(c => String(c.id_client) === val)
  clientNit.value = c?.nit_client || ''
})

// ── Totales ──
const subtotal = computed(() => cartItems.value.reduce((a, i) => a + (parseFloat(i.subtotal_sale) || 0), 0))
const totalDiscount = computed(() => cartItems.value.reduce((a, i) => a + (parseFloat(i.subtotal_sale) || 0) * ((parseFloat(i.discount_sale) || 0) / 100), 0))
const expensesTotal = computed(() => orderExpenses.value.reduce((a, e) => a + parseFloat(e.amount_expense || 0), 0))
const total = computed(() => subtotal.value - totalDiscount.value + expensesTotal.value)
const cashChange = computed(() => Math.max(0, payAmount.value - total.value))

// ── Gastos de despacho ──
async function fetchPackagings() {
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ getPackagings: 'ok' }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  packagingsList.value = d?.status === 200 ? d.results : []
}

async function openExpenses() {
  if (!orderId.value) return
  expLoading.value = true
  expensesModal.value = true
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ getOrderExpenses: 'ok', id_order: orderId.value }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  const d = typeof res === 'string' ? JSON.parse(res) : res
  orderExpenses.value = d?.status === 200 ? d.results : []
  expLoading.value = false
}

async function addExpense() {
  if (!newExpense.value.concept || !newExpense.value.amount) return
  await $fetch(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ addOrderExpense: 'ok', id_order: orderId.value!, concept: newExpense.value.concept, amount: String(newExpense.value.amount), id_admin: String(auth.user?.id_admin || 0) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  newExpense.value = { concept: '', amount: '' }
  await openExpenses()
}

async function addPkgExpense() {
  if (!selectedPkg.value || pkgQty.value <= 0) return
  const pkg = selectedPkg.value
  const concept = `${decode(pkg.name_packaging)} (${pkgQty.value} ${pkg.unit_packaging || 'u.'})`
  const amount = parseFloat(pkg.price_packaging) * pkgQty.value
  
  expLoading.value = true
  await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ 
      addOrderExpense: 'ok', 
      id_order: orderId.value!, 
      concept, 
      amount: String(amount), 
      id_admin: String(auth.user?.id_admin || 0) 
    }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  
  selectedPkgId.value = null
  pkgQty.value = 1
  await openExpenses()
}

async function deleteExpense(id: number) {
  await $fetch(ajaxBase, { method: 'POST', body: new URLSearchParams({ deleteOrderExpense: 'ok', id_expense: String(id) }).toString(), headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }).catch(() => null)
  await openExpenses()
}

watch(total, (val) => {
  if (orderStatus.value === 'Pendiente Despacho') payAmount.value = val
})

function onProofChange(e: Event) {
  const files = (e.target as HTMLInputElement).files
  proofFile.value = files && files.length ? files[0]! : null
}

async function confirmPayment() {
  if (cartItems.value.length === 0) { toast.add({ title: 'El carrito está vacío', color: 'warning' }); return }
  if (!selectedClient.value) { toast.add({ title: 'Selecciona un cliente', color: 'warning' }); return }
  payLoading.value = true
  try {
    const fd = new FormData()
    fd.append('confirmOrderPayment', 'ok')
    fd.append('id_order', orderId.value!)
    fd.append('method', payMethod.value)
    fd.append('reference', payReference.value)
    fd.append('invoice', wantInvoice.value ? '1' : '0')
    fd.append('id_admin', String(auth.user?.id_admin || 0))
    if (proofFile.value) fd.append('proof', proofFile.value)
    const res = await $fetch<any>(ajaxBase, { method: 'POST', body: fd })
    const d = typeof res === 'string' ? JSON.parse(res) : res
    if (d?.status === 200) {
      if (payMethod.value === 'credito') {
        await $fetch(ajaxBase, {
          method: 'POST',
          body: new URLSearchParams({ createCredit: 'ok', id_client: selectedClient.value, id_office: String(auth.officeId || 3), id_admin: String(auth.user?.id_admin || 0), amount: String(total.value), due_date: payReference.value || '', notes: `Orden #${transactionOrder.value}` }).toString(),
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).catch(() => null)
      }

      const nextStat = isCashier.value ? 'Venta Confirmada' : 'Despachado'
      await advanceOrderStatus(nextStat)
      if (d.file_warning) toast.add({ title: 'Comprobante no adjuntado', description: d.file_warning, color: 'warning' })
    } else {
      toast.add({ title: d?.message || 'Error al confirmar pago', color: 'error' })
    }
  } catch {
    toast.add({ title: 'Error de conexión', color: 'error' })
  }
  payLoading.value = false
}

// ── Registrar cliente ──
async function registerClient() {
  if (!newClient.value.name || !newClient.value.dni) return
  const res = await $fetch<any>(ajaxBase, {
    method: 'POST',
    body: new URLSearchParams({ name_client: newClient.value.name, surname_client: newClient.value.surname, dni_client: newClient.value.dni, email_client: newClient.value.email || '', phone_client: newClient.value.phone || '', address_client: newClient.value.address || '', idOffice: String(auth.officeId || 3) }).toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  }).catch(() => null)
  if (res && res !== 'logout' && res !== 'error') {
    // Actualizar NIT por separado
    if (newClient.value.nit) {
      await $fetch(`/api/clients`, { method: 'PUT', headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...apiHeaders }, query: { id: String(res), nameId: 'id_client', token: 'no', except: 'id_client' }, body: `nit_client=${encodeURIComponent(newClient.value.nit)}` }).catch(() => null)
    }
    await fetchClients()
    selectedClient.value = String(res)
    clientModal.value = false
    newClient.value = { name: '', surname: '', dni: '', nit: '', email: '', phone: '', address: '' }
    toast.add({ title: 'Cliente registrado', color: 'success' })
  } else {
    toast.add({ title: 'Error al registrar cliente', color: 'error' })
  }
}

// ── Abrir caja ──
async function submitCashOpen() {
  cashModalLoading.value = true
  try {
    const body = new URLSearchParams({
      date_created_cash: getDate(),
      date_start_cash: new Date().toISOString().slice(0, 19).replace('T', ' '),
      id_office_cash: String(auth.officeId || 3),
      id_admin_cash: String(auth.user?.id_admin || 0),
      start_cash: String(openingAmount.value),
      status_cash: '1',
      bills_cash: '0',
      money_cash: '0',
      diff_cash: '0'
    })
    const res = await $fetch<any>('/api/cashs?token=no&except=date_end_cash', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...apiHeaders }, body: body.toString() })
    if (res.status === 200) {
      isCashRegisterOpen.value = true; isOpeningCash.value = false
      await fetchPendingOrders()
      toast.add({ title: 'Caja abierta', color: 'success' })
    }
  } catch {}
  cashModalLoading.value = false
}

function printReceipt() { window.print() }

// ── Estado visual ──
const statusConfig: Record<string, { color: string, label: string, icon: string }> = {
  'Pendiente Despacho': { color: 'text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-700', label: 'Pendiente Despacho', icon: 'i-lucide-clock' },
  'Despachado':         { color: 'text-blue-600 bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700', label: 'Despachado', icon: 'i-lucide-truck' },
  'Pago Pendiente':     { color: 'text-orange-600 bg-orange-50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-700', label: 'Pago Pendiente', icon: 'i-lucide-wallet' },
  'Venta Confirmada':   { color: 'text-emerald-600 bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-700', label: 'Confirmada', icon: 'i-lucide-check-circle' }
}

const currentStatus = computed(() => statusConfig[orderStatus.value] || statusConfig['Pendiente Despacho'])

// Siguiente estado posible
const nextStatus = computed(() => {
  const flow = ['Pendiente Despacho', 'Despachado', 'Venta Confirmada']
  const idx = flow.indexOf(orderStatus.value)
  return idx >= 0 && idx < flow.length - 1 ? flow[idx + 1] : null
})
</script>

<template>
  <div class="h-full flex flex-col relative">

    <!-- Recibo imprimible -->
    <div id="print-area" class="hidden print:block bg-white text-black p-4 text-xs font-mono w-72 mx-auto">
      <div v-if="lastReceipt" class="space-y-1.5">
        <div class="text-center font-bold text-sm">JE INVENTARIO & VENTAS</div>
        <div class="text-center text-xs">Sucursal: {{ auth.office?.title_office || '' }}</div>
        <div v-if="lastReceipt.invoice" class="text-center text-xs">NIT Cliente: {{ lastReceipt.nit || 'S/N' }}</div>
        <hr class="border-dashed border-black my-1">
        <div>Orden: {{ lastReceipt.transaction }}</div>
        <div>Fecha: {{ lastReceipt.date }}</div>
        <div v-if="lastReceipt.client">Cliente: {{ decode(lastReceipt.client.name_client) }} {{ decode(lastReceipt.client.surname_client || '') }}</div>
        <div>Vendedor: {{ lastReceipt.vendedor }}</div>
        <hr class="border-dashed border-black my-1">
        <table class="w-full">
          <tr v-for="item in lastReceipt.items" :key="item.id_sale">
            <td>{{ item.qty_sale }}</td>
            <td class="px-1">{{ decode(products.find(p => String(p.id_product) === String(item.id_product_sale))?.title_product || '') }}</td>
            <td class="text-right">Bs.{{ parseFloat(item.subtotal_sale).toFixed(2) }}</td>
          </tr>
        </table>
        <hr class="border-dashed border-black my-1">
        <div class="flex justify-between"><span>Subtotal:</span><span>Bs.{{ lastReceipt.subtotal.toFixed(2) }}</span></div>
        <div v-if="lastReceipt.discount > 0" class="flex justify-between"><span>Dto:</span><span>-Bs.{{ lastReceipt.discount.toFixed(2) }}</span></div>
        <div v-if="lastReceipt.expenses > 0" class="flex justify-between"><span>Gastos:</span><span>Bs.{{ lastReceipt.expenses.toFixed(2) }}</span></div>
        <div class="flex justify-between font-bold"><span>TOTAL:</span><span>Bs.{{ lastReceipt.total.toFixed(2) }}</span></div>
        <div class="flex justify-between"><span>Pago:</span><span>{{ lastReceipt.method }}</span></div>
        <hr class="border-dashed border-black my-1">
        <div class="text-center font-bold">¡GRACIAS!</div>
      </div>
    </div>

    <!-- Layout principal -->
    <div class="flex flex-col lg:grid lg:grid-cols-12 gap-4 flex-1 overflow-hidden min-h-0 print:hidden">

      <!-- ─── Catálogo ─── -->
      <div class="lg:col-span-8 flex flex-col gap-3 min-h-0 flex-1 lg:flex-none overflow-hidden">

        <!-- Selector de órdenes pendientes (siempre visible arriba) -->
        <div v-if="pendingOrders.length > 0 || orderId" class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-2.5 shadow-sm flex gap-2 overflow-x-auto shrink-0">
          <button
            v-for="ord in pendingOrders" :key="ord.id_order"
            :class="['text-[13px] px-3.5 py-1.5 rounded-full border font-mono transition-colors whitespace-nowrap', String(ord.id_order) === orderId ? 'bg-green-600 text-white border-green-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700']"
            @click="selectOrder(ord)"
          >
            Orden #{{ ord.transaction_order?.slice(-6) }}
          </button>
          <button class="text-[13px] px-3.5 py-1.5 rounded-full border bg-green-50 dark:bg-green-900/20 text-green-600 border-green-200 dark:border-green-700 font-bold whitespace-nowrap" @click="handleNewOrder">+ Nueva Orden</button>
        </div>

        <!-- Buscador + categorías -->
        <div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-sm shrink-0 space-y-2">
          <UInput v-model="search" icon="i-lucide-search" placeholder="Buscar producto..." />
          <div class="flex gap-2 overflow-x-auto pb-1">
            <UButton :color="activeCategory === 'all' ? 'primary' : 'neutral'" variant="soft" size="sm" @click="activeCategory = 'all'">Todos</UButton>
            <UButton
              v-for="cat in categories" :key="cat.id_category"
              :color="activeCategory === String(cat.id_category) ? 'primary' : 'neutral'"
              variant="soft" size="sm"
              @click="activeCategory = String(cat.id_category)"
            >
              {{ decode(cat.title_category) }}
            </UButton>
          </div>
        </div>

        <!-- Grid de productos -->
        <div class="flex-1 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 content-start pb-4">
          <div
            v-for="prod in filteredProducts" :key="prod.id_product"
            class="bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm hover:border-green-400 hover:shadow-md transition-all cursor-pointer flex flex-col"
            @click="addToCart(prod)"
          >
            <div class="h-28 bg-slate-50 dark:bg-slate-950 flex items-center justify-center p-2 relative">
              <span v-if="prod.is_compound_product == 1" class="absolute top-1.5 left-1.5 text-[11px] bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300 border border-purple-200 dark:border-purple-700 px-2 py-0.5 rounded font-bold">COMBO</span>
              <span class="absolute top-1.5 right-1.5 text-[11px] font-mono bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2 py-0.5 rounded text-slate-500">{{ prod.sku_product }}</span>
              <img :src="prod.img_product ? decode(prod.img_product) : '/views/assets/img/multimedia.png'" class="max-h-full max-w-full object-contain" @error="($event.target as HTMLImageElement).style.display='none'" />
            </div>
            <div class="p-3.5 flex flex-col gap-2 flex-1">
              <h3 class="text-sm font-semibold text-slate-800 dark:text-white line-clamp-2 leading-snug">{{ decode(prod.title_product) }}</h3>
              <div class="flex justify-between items-end mt-auto">
                <div>
                  <span class="text-xs text-slate-400">Precio</span>
                  <span class="font-bold text-green-600 dark:text-green-400 font-mono text-[15px] block">Bs.{{ (pricesMap[prod.id_product]?.price || 0).toFixed(2) }}</span>
                </div>
                <UBadge :color="(inventory[prod.id_product] || 0) > 0 ? 'success' : 'error'" variant="subtle" size="sm">
                  {{ inventory[prod.id_product] || 0 }} {{ prod.unit_product }}
                </UBadge>
              </div>
            </div>
          </div>
          <div v-if="filteredProducts.length === 0" class="col-span-full text-center py-16 text-slate-400">
            <UIcon name="i-lucide-search-x" class="w-8 h-8 mx-auto mb-2" />
            <p class="text-sm">Sin productos disponibles</p>
          </div>
        </div>
      </div>

      <!-- ─── Panel de orden — sidebar en desktop, drawer en móvil ─── -->
      <div
        :class="[
          'lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden shadow-sm',
          'lg:relative lg:rounded-xl lg:translate-y-0',
          'fixed inset-x-0 bottom-0 z-30 rounded-t-2xl transition-transform duration-300 lg:transition-none',
          cartMobileOpen ? 'translate-y-0' : 'translate-y-full'
        ]"
        style="max-height: 88dvh"
      >

        <!-- Handle móvil (drag indicator) -->
        <div class="lg:hidden flex justify-center pt-2 pb-1 shrink-0 cursor-pointer" @click="cartMobileOpen = false">
          <div class="w-10 h-1 rounded-full bg-slate-300 dark:bg-slate-600" />
        </div>

        <!-- Header -->
        <div class="px-3 py-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 shrink-0">
          <div class="flex justify-between items-start">
            <div>
              <p class="font-bold text-slate-800 dark:text-white text-[15px]">{{ transactionOrder ? `#${transactionOrder}` : 'Sin orden' }}</p>
              <!-- Badge de estado: solo en flujo de despacho (vendedor/admin) -->
              <div v-if="orderId && usesDispatchFlow" :class="['inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-0.5 rounded-full border mt-0.5', currentStatus.color]">
                <UIcon :name="currentStatus.icon" class="w-3.5 h-3.5" />
                {{ currentStatus.label }}
              </div>
            </div>
            <div class="flex gap-1.5">
              <UButton v-if="!orderId" color="primary" icon="i-lucide-plus-circle" size="sm" @click="handleNewOrder">Nueva</UButton>
              <template v-else>
                <template v-if="usesDispatchFlow && orderStatus === 'Despachado'">
                  <UButton icon="i-lucide-package-open" color="neutral" variant="ghost" size="sm" title="Gastos de despacho" @click="openExpenses" />
                </template>
                <UButton icon="i-lucide-trash-2" color="error" variant="ghost" size="sm" title="Cancelar orden" @click="cancelOrder" />
              </template>
            </div>
          </div>

          <!-- Selector de órdenes pendientes movido a la parte superior -->
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-3 space-y-3">

          <!-- Cliente -->
          <div v-if="orderId" class="space-y-2">
            <div class="flex gap-2">
              <select
                v-model="selectedClient"
                class="flex-1 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2.5 py-1.5 focus:outline-none focus:border-green-500"
              >
                <option value="" disabled selected>Cliente *</option>
                <option v-for="c in clients" :key="c.id_client" :value="String(c.id_client)">
                  {{ decode(c.name_client) }} {{ decode(c.surname_client||'') }} · {{ c.dni_client }}
                </option>
              </select>
              <UButton icon="i-lucide-user-plus" color="neutral" variant="soft" size="sm" @click="clientModal = true" />
            </div>
            <!-- Dirección de entrega: solo flujo de despacho -->
            <UInput v-if="selectedClient && usesDispatchFlow" v-model="deliveryAddress" placeholder="Dirección de entrega..." size="sm" icon="i-lucide-map-pin" class="text-sm" />
          </div>

          <!-- Toggle mayorista -->
          <div v-if="orderId" class="flex items-center justify-between bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Precio mayorista</span>
            <button
              type="button"
              @click="isWholesale = !isWholesale"
              :class="[
                isWholesale ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-800',
                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none'
              ]"
            >
              <span
                :class="[
                  isWholesale ? 'translate-x-5' : 'translate-x-0',
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                ]"
              />
            </button>
          </div>

          <!-- Ítems del carrito -->
          <div v-if="cartItems.length > 0" class="space-y-2">
            <div v-for="item in cartItems" :key="item.id_sale" class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 rounded-lg p-2.5">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                  {{ decode(products.find(p => String(p.id_product) === String(item.id_product_sale))?.title_product || 'Producto') }}
                </p>
                <p class="text-[12px] text-green-600 dark:text-green-400 font-mono">
                  Bs.{{ (parseFloat(item.subtotal_sale) / parseInt(item.qty_sale)).toFixed(2) }} × {{ item.qty_sale }}
                </p>
              </div>
              <div class="flex items-center gap-1.5 shrink-0">
                <UButton icon="i-lucide-minus" color="neutral" variant="ghost" size="sm" class="p-1" @click="updateQty(item, -1)" />
                <span class="text-sm font-mono w-6 text-center text-slate-700 dark:text-white">{{ item.qty_sale }}</span>
                <UButton icon="i-lucide-plus" color="neutral" variant="ghost" size="sm" class="p-1" @click="updateQty(item, 1)" />
                <UButton icon="i-lucide-trash" color="error" variant="ghost" size="sm" class="p-1" @click="deleteItem(item)" />
              </div>
            </div>
          </div>

          <div v-else-if="orderId" class="text-center py-8 text-slate-400">
            <UIcon name="i-lucide-shopping-cart" class="w-7 h-7 mx-auto mb-1" />
            <p class="text-xs">Carrito vacío</p>
          </div>

          <!-- Gastos de despacho (resumen) -->
          <div v-if="orderExpenses.length > 0" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-2.5">
            <p class="text-xs font-semibold text-blue-600 dark:text-blue-300 mb-1 flex items-center gap-1">
              <UIcon name="i-lucide-truck" class="w-3.5 h-3.5" /> Gastos de despacho
            </p>
            <div v-for="exp in orderExpenses" :key="exp.id_expense" class="flex justify-between text-xs text-blue-700 dark:text-blue-300">
              <span>{{ exp.concept_expense }}</span><span class="font-mono">Bs.{{ parseFloat(exp.amount_expense).toFixed(2) }}</span>
            </div>
          </div>

          <!-- Éxito -->
          <div v-if="checkoutSuccess" class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-3 text-center space-y-2">
            <UIcon name="i-lucide-check-circle" class="w-7 h-7 text-emerald-500 mx-auto" />
            <p class="text-xs font-bold text-slate-700 dark:text-white">Venta confirmada</p>
            <UButton color="success" size="xs" block icon="i-lucide-printer" @click="printReceipt">Imprimir Recibo</UButton>
            <UButton color="neutral" variant="ghost" size="xs" block @click="() => { checkoutSuccess = false; orderId = null; transactionOrder = null; cartItems = [] }">Nueva Orden</UButton>
          </div>
        </div>

        <!-- Footer totales -->
        <div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 flex flex-col gap-3 shrink-0">
          <div class="space-y-1.5 text-sm">
            <div class="flex justify-between text-slate-500 dark:text-slate-400"><span>Subtotal</span><span class="font-mono">Bs.{{ subtotal.toFixed(2) }}</span></div>
            <div v-if="totalDiscount > 0" class="flex justify-between text-rose-500"><span>Descuento</span><span class="font-mono">-Bs.{{ totalDiscount.toFixed(2) }}</span></div>
            <div v-if="expensesTotal > 0" class="flex justify-between text-blue-500"><span>Gastos despacho</span><span class="font-mono">+Bs.{{ expensesTotal.toFixed(2) }}</span></div>
            <div class="flex justify-between font-bold text-slate-800 dark:text-white border-t border-slate-200 dark:border-slate-700 pt-2 text-[15px]">
              <span>Total</span><span class="font-mono text-green-600 dark:text-green-400">Bs.{{ total.toFixed(2) }}</span>
            </div>
          </div>

          <!-- FORMAS DE PAGO (Solo si está en Pendiente Despacho y hay items) -->
          <div v-if="orderId && orderStatus === 'Pendiente Despacho' && cartItems.length > 0 && !checkoutSuccess" class="space-y-3.5 border-t border-slate-200 dark:border-slate-700 pt-3.5">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Método de Pago</p>
            <div class="grid grid-cols-3 gap-2">
              <UButton
                v-for="m in payMethods" :key="m.value"
                :color="payMethod === m.value ? 'primary' : 'neutral'"
                variant="soft" size="sm" :icon="m.icon"
                class="flex-col py-2 text-xs"
                @click="payMethod = (m.value as any); payAmount = total"
              >
                {{ m.label }}
              </UButton>
            </div>

            <!-- Detalles del pago -->
            <div v-if="payMethod === 'efectivo' || payMethod === 'qr'" class="flex items-center gap-2">
              <UInput v-model.number="payAmount" type="number" step="any" size="sm" :placeholder="payMethod === 'efectivo' ? 'Efectivo recibido' : 'Monto QR'" class="flex-1 text-sm" />
              <div v-if="payMethod === 'efectivo'" class="text-xs text-slate-500 font-semibold bg-slate-200 dark:bg-slate-800 px-2 py-1.5 rounded">
                Cambio: <span class="text-green-600 font-mono">{{ fmt(cashChange) }}</span>
              </div>
            </div>

            <div v-if="payMethod === 'transferencia'">
              <UInput v-model="payReference" size="sm" placeholder="N° de Referencia / Comprobante" class="w-full text-xs" />
            </div>

            <div v-if="payMethod === 'credito'" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded px-2.5 py-1.5">
               <div class="text-xs text-amber-700 mb-1">Fecha de vencimiento:</div>
               <UInput v-model="payReference" type="date" size="sm" class="w-full text-sm" />
            </div>

            <div v-if="payMethod === 'qr' || payMethod === 'transferencia'">
              <input
                type="file"
                accept="image/*,application/pdf"
                class="block w-full text-[10px] text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-primary-50 file:text-primary-700"
                @change="onProofChange"
              >
            </div>

            <div class="flex items-center justify-between bg-slate-100 dark:bg-slate-800 px-2.5 py-2 rounded">
              <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">¿Emitir Factura?</span>
              <button
                type="button"
                @click="wantInvoice = !wantInvoice"
                :class="[
                  wantInvoice ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-800',
                  'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none'
                ]"
              >
                <span
                  :class="[
                    wantInvoice ? 'translate-x-5' : 'translate-x-0',
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                  ]"
                />
              </button>
            </div>
            <UInput v-if="wantInvoice" v-model="clientNit" size="sm" placeholder="NIT del Cliente" class="w-full text-sm" />

            <UButton color="primary" block icon="i-lucide-check-circle" :loading="payLoading" @click="confirmPayment">
              Confirmar y {{ isCashier ? 'Finalizar' : 'Despachar' }}
            </UButton>
          </div>

          <!-- Si ya está pagado y está en despacho (para roles logísticos) -->
          <template v-else-if="usesDispatchFlow && orderId && orderStatus === 'Despachado' && !checkoutSuccess">
            <UButton color="success" block icon="i-lucide-check-check" @click="advanceOrderStatus('Venta Confirmada')">
              Completar Orden
            </UButton>
          </template>
        </div>
      </div>
    </div>

    <!-- ── Backdrop carrito móvil ── -->
    <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="cartMobileOpen" class="fixed inset-0 z-20 bg-black/40 lg:hidden" @click="cartMobileOpen = false" />
    </Transition>

    <!-- ── FAB: abrir carrito en móvil ── -->
    <button
      v-if="!cartMobileOpen"
      class="fixed bottom-5 right-5 z-20 lg:hidden flex items-center gap-2 bg-green-600 hover:bg-green-700 active:scale-95 text-white font-bold px-4 py-3 rounded-full shadow-xl shadow-green-900/30 transition-all"
      @click="cartMobileOpen = true"
    >
      <UIcon name="i-lucide-shopping-cart" class="w-5 h-5" />
      <span class="text-sm">Carrito</span>
      <span v-if="cartItems.length > 0" class="bg-white text-green-700 text-xs font-black w-5 h-5 rounded-full flex items-center justify-center">{{ cartItems.length }}</span>
    </button>

    <!-- ── Overlay: verificando caja ── -->
    <div v-if="isCashRegisterOpen === null" class="absolute inset-0 z-40 backdrop-blur-sm bg-white/60 dark:bg-slate-900/60 flex items-center justify-center">
      <UIcon name="i-lucide-loader-2" class="w-9 h-9 animate-spin text-green-600" />
    </div>

    <!-- ── Overlay: caja cerrada ── -->
    <div v-else-if="!isCashRegisterOpen && !isVendedor" class="absolute inset-0 z-40 backdrop-blur-sm bg-white/70 dark:bg-slate-900/70 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 rounded-2xl max-w-sm w-full shadow-2xl text-center space-y-5">
        <div class="w-16 h-16 rounded-full bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 mx-auto flex items-center justify-center">
          <UIcon name="i-lucide-wallet" class="w-8 h-8 text-rose-500" />
        </div>
        <div>
          <h2 class="text-xl font-extrabold text-slate-800 dark:text-white">Caja Cerrada</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Abre la caja del día para operar el POS.</p>
        </div>
        <UButton size="lg" color="success" icon="i-lucide-lock-keyhole-open" block @click="isOpeningCash = true">Abrir Caja</UButton>
      </div>
    </div>

    <!-- ── Modal: Apertura de caja ── -->
    <UModal v-model:open="isOpeningCash" title="Apertura de Caja">
      <template #body>
        <div class="space-y-4 p-1">
          <p class="text-sm text-slate-500 dark:text-slate-400">Monto inicial con el que comienzas el día.</p>
          <UFormField label="Monto Inicial (Bs.)">
            <UInput v-model.number="openingAmount" type="number" step="0.10" min="0" size="lg" class="w-full" />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="isOpeningCash = false">Cancelar</UButton>
          <UButton color="success" :loading="cashModalLoading" @click="submitCashOpen">Confirmar y Abrir</UButton>
        </div>
      </template>
    </UModal>

    <!-- ── Modal: Gastos de despacho ── -->
    <UModal v-model:open="expensesModal" title="Gastos de Despacho">
      <template #body>
        <div class="space-y-4 p-1">
          <div v-if="expLoading" class="text-center py-4"><UIcon name="i-lucide-loader-2" class="w-6 h-6 animate-spin text-green-500 mx-auto" /></div>
          <template v-else>
            <div class="space-y-2">
              <div v-for="exp in orderExpenses" :key="exp.id_expense" class="flex justify-between items-center bg-slate-50 dark:bg-slate-800 rounded-lg px-3 py-2 text-sm">
                <span class="text-slate-700 dark:text-slate-200">{{ exp.concept_expense }}</span>
                <div class="flex items-center gap-2">
                  <span class="font-mono font-bold text-blue-600">Bs.{{ parseFloat(exp.amount_expense).toFixed(2) }}</span>
                  <UButton icon="i-lucide-trash" color="error" variant="ghost" size="xs" @click="deleteExpense(exp.id_expense)" />
                </div>
              </div>
              <div v-if="orderExpenses.length === 0" class="text-center py-4 text-slate-400 text-sm">Sin gastos registrados</div>
            </div>

            <!-- Selección de Empaques Predefinidos -->
            <div v-if="packagingsList.length > 0" class="pt-3 border-t border-slate-200 dark:border-slate-700 space-y-2">
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cargar del Catálogo de Empaques</p>
              <div class="flex gap-2 items-center">
                <USelect
                  v-model="selectedPkgId"
                  :items="packagingsList.map(p => ({ value: String(p.id_packaging), label: decode(p.name_packaging) + ' - Bs.' + parseFloat(p.price_packaging).toFixed(2) }))"
                  placeholder="Seleccionar empaque..."
                  class="flex-1 text-xs"
                />
                <UInput v-model.number="pkgQty" type="number" min="1" class="w-20 text-xs text-center font-mono" placeholder="Cant." />
                <UButton icon="i-lucide-plus" color="success" size="sm" class="bg-green-600 hover:bg-green-700 text-white" :disabled="!selectedPkgId" @click="addPkgExpense" />
              </div>
              <p v-if="selectedPkg" class="text-[10px] text-slate-400">
                Total Empaque: <strong class="text-blue-600 font-mono">Bs.{{ (parseFloat(selectedPkg.price_packaging) * pkgQty).toFixed(2) }}</strong> ({{ selectedPkg.unit_packaging }})
              </p>
            </div>

            <!-- Registro Manual -->
            <div class="pt-3 border-t border-slate-200 dark:border-slate-700 space-y-2">
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Agregar Gasto Manual</p>
              <div class="flex gap-2">
                <UInput v-model="newExpense.concept" placeholder="Concepto (ej: Transporte)" class="flex-1 text-sm" />
                <UInput v-model="newExpense.amount" type="number" step="0.5" placeholder="Bs." class="w-24 text-sm" />
                <UButton icon="i-lucide-plus" color="primary" size="sm" @click="addExpense" />
              </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg px-3 py-2 flex justify-between items-center">
              <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">Total Gastos</span>
              <span class="font-bold font-mono text-blue-700 dark:text-blue-300">{{ fmt(expensesTotal) }}</span>
            </div>
          </template>
        </div>
      </template>
      <template #footer>
        <UButton color="neutral" variant="ghost" @click="expensesModal = false">Cerrar</UButton>
      </template>
    </UModal>



    <!-- ── Modal: Nuevo cliente ── -->
    <UModal v-model:open="clientModal" title="Registrar Cliente">
      <template #body>
        <div class="grid grid-cols-2 gap-3 p-1">
          <UFormField label="Nombre *" class="col-span-1">
            <UInput v-model="newClient.name" placeholder="Juan" class="w-full" />
          </UFormField>
          <UFormField label="Apellido" class="col-span-1">
            <UInput v-model="newClient.surname" placeholder="Pérez" class="w-full" />
          </UFormField>
          <UFormField label="DNI *" class="col-span-1">
            <UInput v-model="newClient.dni" placeholder="1234567" class="w-full" />
          </UFormField>
          <UFormField label="NIT (Facturación)" class="col-span-1">
            <UInput v-model="newClient.nit" placeholder="NIT empresa" class="w-full" />
          </UFormField>
          <UFormField label="Teléfono" class="col-span-1">
            <UInput v-model="newClient.phone" placeholder="79000000" class="w-full" />
          </UFormField>
          <UFormField label="Correo" class="col-span-1">
            <UInput v-model="newClient.email" type="email" placeholder="juan@mail.com" class="w-full" />
          </UFormField>
          <UFormField label="Dirección" class="col-span-2">
            <UInput v-model="newClient.address" placeholder="Zona Central..." class="w-full" />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton color="neutral" variant="ghost" @click="clientModal = false">Cancelar</UButton>
          <UButton color="primary" @click="registerClient">Crear Cliente</UButton>
        </div>
      </template>
    </UModal>

  </div>
</template>

<style>
@media print {
  body * { visibility: hidden; }
  #print-area, #print-area * { visibility: visible; }
  #print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
