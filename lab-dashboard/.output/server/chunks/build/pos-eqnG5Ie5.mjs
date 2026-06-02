import { H as useAuthStore, a5 as useToast, j as _sfc_main$6, g as _sfc_main$c, h as _sfc_main$h } from './server.mjs';
import { _ as _sfc_main$1 } from './Badge-P0JOv5sI.mjs';
import { _ as _sfc_main$2 } from './Select-Di6-gJCC.mjs';
import { _ as _sfc_main$3 } from './Switch-DkNSnXzc.mjs';
import { _ as _sfc_main$4 } from './Modal-CoprpFuw.mjs';
import { _ as _sfc_main$5 } from './FormField-DEd2VTbu.mjs';
import { defineComponent, ref, computed, watch, mergeProps, unref, withCtx, createTextVNode, toDisplayString, createVNode, openBlock, createBlock, Fragment, renderList, createCommentVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderComponent, ssrRenderAttr, ssrRenderClass, ssrRenderStyle } from 'vue/server-renderer';
import '../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import '@iconify/utils';
import 'consola';
import 'pinia';
import 'vue-router';
import 'perfect-debounce';
import '@vue/shared';
import '@iconify/vue';
import 'tailwindcss/colors';
import '@vueuse/core';
import '@vueuse/shared';
import 'tailwind-variants';
import '@iconify/utils/lib/css/icon';
import '@floating-ui/vue';
import 'aria-hidden';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/utils';
import './Label-CXontjPM.mjs';
import './overlay-DuqFFmJC.mjs';

const ajaxBase = "/ajax/pos.ajax.php";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "pos",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    const toast = useToast();
    const cartMobileOpen = ref(false);
    const apiHeaders = { Authorization: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy" };
    const categories = ref([]);
    const products = ref([]);
    const inventory = ref({});
    const pricesMap = ref({});
    const clients = ref([]);
    const activeCategory = ref("all");
    const search = ref("");
    const isCashRegisterOpen = ref(null);
    const isOpeningCash = ref(false);
    const openingAmount = ref(0);
    const cashModalLoading = ref(false);
    const pendingOrders = ref([]);
    const orderId = ref(null);
    const transactionOrder = ref(null);
    const selectedClient = ref("");
    const deliveryAddress = ref("");
    const orderNotes = ref("");
    const isWholesale = ref(false);
    const cartItems = ref([]);
    const orderStatus = ref("Pendiente Despacho");
    const checkoutSuccess = ref(false);
    const lastReceipt = ref(null);
    const payModal = ref(false);
    const payMethod = ref("efectivo");
    const payAmount = ref(0);
    const payReference = ref("");
    const proofFile = ref(null);
    const wantInvoice = ref(false);
    const clientNit = ref("");
    const payLoading = ref(false);
    const expensesModal = ref(false);
    const orderExpenses = ref([]);
    const newExpense = ref({ concept: "", amount: "" });
    const expLoading = ref(false);
    const clientModal = ref(false);
    const newClient = ref({ name: "", surname: "", dni: "", nit: "", email: "", phone: "", address: "" });
    function decode(s) {
      return s ? decodeURIComponent(s).replace(/\+/g, " ") : "";
    }
    function fmt(val) {
      return new Intl.NumberFormat("es-BO", { style: "currency", currency: "BOB" }).format(val);
    }
    function getDate() {
      const n = /* @__PURE__ */ new Date();
      return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, "0")}-${String(n.getDate()).padStart(2, "0")}`;
    }
    computed(() => auth.role === "cajero");
    const isVendedor = computed(() => auth.role === "vendedor");
    const isSuperAdmin = computed(() => auth.role === "superadmin" || auth.role === "admin");
    const usesDispatchFlow = computed(() => isVendedor.value || isSuperAdmin.value);
    const payMethods = computed(() => {
      const base = [
        { value: "efectivo", label: "Efectivo", icon: "i-lucide-banknote" },
        { value: "qr", label: "QR", icon: "i-lucide-qr-code" },
        { value: "transferencia", label: "Transferencia", icon: "i-lucide-arrow-right-left" }
      ];
      if (isVendedor.value || isSuperAdmin.value) {
        base.push(
          { value: "credito", label: "Crédito", icon: "i-lucide-credit-card" },
          { value: "consignacion", label: "Consignación", icon: "i-lucide-package" }
        );
      }
      return base;
    });
    async function fetchCatalog() {
      const [prodD, invD, purchD] = await Promise.all([
        $fetch("/api/products?linkTo=status_product&equalTo=1", { headers: apiHeaders }).catch(() => null),
        $fetch(`/api/product_inventory?linkTo=id_office_inventory&equalTo=${auth.officeId || 3}`, { headers: apiHeaders }).catch(() => null),
        $fetch("/api/purchases?orderBy=date_created_purchase&orderMode=DESC", { headers: apiHeaders }).catch(() => null)
      ]);
      if (prodD?.status === 200) products.value = prodD.results || [];
      if (invD?.status === 200 && invD.results) {
        const inv = {};
        invD.results.forEach((i) => {
          inv[i.id_product_inventory] = parseFloat(i.stock_inventory) || 0;
        });
        inventory.value = inv;
      }
      if (purchD?.status === 200 && purchD.results) {
        const prices = {};
        purchD.results.forEach((p) => {
          if (!prices[p.id_product_purchase]) {
            prices[p.id_product_purchase] = { price: parseFloat(p.price_purchase) || 0, wholesalePrice: parseFloat(p.may_product) || 0, wholesaleQty: parseInt(p.wholesale_quantity) || 0 };
          }
        });
        pricesMap.value = prices;
      }
    }
    async function fetchClients() {
      const d = await $fetch("/api/clients", { headers: apiHeaders }).catch(() => null);
      if (d?.status === 200) clients.value = d.results || [];
    }
    async function fetchPendingOrders() {
      const adminId = auth.user?.id_admin || 1;
      const d = await $fetch(`/api/orders?linkTo=id_admin_order,status_order&equalTo=${adminId},Pendiente Despacho&orderBy=id_order&orderMode=ASC`, { headers: apiHeaders }).catch(() => null);
      pendingOrders.value = d?.status === 200 ? d.results || [] : [];
      if (pendingOrders.value.length > 0 && !orderId.value) {
        await selectOrder(pendingOrders.value[0]);
      }
    }
    const filteredProducts = computed(() => products.value.filter((p) => {
      const inStock = (inventory.value[p.id_product] || 0) > 0;
      const byCategory = activeCategory.value === "all" || String(p.id_category_product) === String(activeCategory.value);
      const bySearch = !search.value || decode(p.title_product || "").toLowerCase().includes(search.value.toLowerCase()) || (p.sku_product || "").toLowerCase().includes(search.value.toLowerCase());
      return inStock && byCategory && bySearch;
    }));
    async function selectOrder(order) {
      orderId.value = String(order.id_order);
      transactionOrder.value = order.transaction_order;
      orderStatus.value = order.status_order || "Pendiente Despacho";
      selectedClient.value = order.id_client_order > 0 ? String(order.id_client_order) : "";
      deliveryAddress.value = order.delivery_address_order || "";
      orderNotes.value = order.notes_order || "";
      checkoutSuccess.value = false;
      await fetchCart();
    }
    async function handleNewOrder() {
      const res = await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ order: "new", idOffice: String(auth.officeId || 3), seller: String(auth.user?.id_admin || 1) }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      let data;
      if (typeof res === "string") {
        const t = res.trim();
        if (t === "current cash error") {
          toast.add({ title: "No hay caja abierta hoy", color: "error" });
          return;
        }
        if (t === "yesterday cash error") {
          toast.add({ title: "Hay cajas anteriores sin cerrar", color: "error" });
          return;
        }
        try {
          data = JSON.parse(t);
        } catch {
          toast.add({ title: "Error al crear orden", color: "error" });
          return;
        }
      } else {
        data = res;
      }
      if (data?.transaction_order) {
        orderId.value = String(data.id_order);
        transactionOrder.value = data.transaction_order;
        orderStatus.value = "Pendiente Despacho";
        selectedClient.value = "";
        deliveryAddress.value = "";
        orderNotes.value = "";
        cartItems.value = [];
        checkoutSuccess.value = false;
        await fetchPendingOrders();
      } else {
        toast.add({ title: data?.message || "Error al crear orden", color: "error" });
      }
    }
    async function fetchCart() {
      if (!orderId.value) {
        cartItems.value = [];
        return;
      }
      const d = await $fetch(`/api/sales?linkTo=id_order_sale&equalTo=${orderId.value}`, { headers: apiHeaders }).catch(() => null);
      cartItems.value = d?.status === 200 && d.results ? d.results : [];
    }
    async function updateQty(item, delta) {
      const newQty = parseInt(item.qty_sale) + delta;
      if (newQty < 1) return;
      if (delta > 0 && newQty > (inventory.value[item.id_product_sale] || 0)) {
        toast.add({ title: "Sin stock", color: "error" });
        return;
      }
      const pm = pricesMap.value[item.id_product_sale] || { price: 0, wholesalePrice: 0, wholesaleQty: 0 };
      let price = pm.price;
      if (isWholesale.value || pm.wholesaleQty > 0 && newQty >= pm.wholesaleQty) {
        if (pm.wholesalePrice > 0) price = pm.wholesalePrice;
      }
      const disc = parseFloat(item.discount_sale) || 0;
      if (disc > 0) price = price * (1 - disc / 100);
      await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ idSaleUpdate: String(item.id_sale), qtySale: String(newQty), subtotalSale: String(price * newQty) }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      await fetchCart();
    }
    async function deleteItem(item) {
      await $fetch(ajaxBase, { method: "POST", body: new URLSearchParams({ idSaleDelete: String(item.id_sale) }).toString(), headers: { "Content-Type": "application/x-www-form-urlencoded" } }).catch(() => null);
      await fetchCart();
    }
    async function cancelOrder() {
      if (!orderId.value || !confirm("¿Cancelar esta orden?")) return;
      await $fetch(ajaxBase, { method: "POST", body: new URLSearchParams({ idOrderDelete: orderId.value }).toString(), headers: { "Content-Type": "application/x-www-form-urlencoded" } }).catch(() => null);
      orderId.value = null;
      transactionOrder.value = null;
      cartItems.value = [];
      await fetchPendingOrders();
    }
    async function advanceOrderStatus(newStatus) {
      if (!orderId.value) return;
      const res = await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ updateOrderStatus: "ok", id_order: orderId.value, status: newStatus }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      const d = typeof res === "string" ? JSON.parse(res) : res;
      if (d?.status === 200) {
        orderStatus.value = newStatus;
        toast.add({ title: `Orden → ${newStatus}`, color: "success" });
        if (newStatus === "Venta Confirmada") {
          checkoutSuccess.value = true;
          await fetchCatalog();
          await fetchPendingOrders();
        }
      }
    }
    watch(selectedClient, async (val) => {
      if (!orderId.value || !val) return;
      await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ idOrderUpdate: orderId.value, idClient: val, subtotalOrder: String(subtotal.value), discountOrder: String(totalDiscount.value), taxOrder: "0", totalOrder: String(total.value) }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      const c = clients.value.find((c2) => String(c2.id_client) === val);
      clientNit.value = c?.nit_client || "";
    });
    const subtotal = computed(() => cartItems.value.reduce((a, i) => a + (parseFloat(i.subtotal_sale) || 0), 0));
    const totalDiscount = computed(() => cartItems.value.reduce((a, i) => a + (parseFloat(i.subtotal_sale) || 0) * ((parseFloat(i.discount_sale) || 0) / 100), 0));
    const expensesTotal = computed(() => orderExpenses.value.reduce((a, e) => a + parseFloat(e.amount_expense || 0), 0));
    const total = computed(() => subtotal.value - totalDiscount.value + expensesTotal.value);
    const cashChange = computed(() => Math.max(0, payAmount.value - total.value));
    async function openExpenses() {
      if (!orderId.value) return;
      expLoading.value = true;
      expensesModal.value = true;
      const res = await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ getOrderExpenses: "ok", id_order: orderId.value }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      const d = typeof res === "string" ? JSON.parse(res) : res;
      orderExpenses.value = d?.status === 200 ? d.results : [];
      expLoading.value = false;
    }
    async function addExpense() {
      if (!newExpense.value.concept || !newExpense.value.amount) return;
      await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ addOrderExpense: "ok", id_order: orderId.value, concept: newExpense.value.concept, amount: String(newExpense.value.amount), id_admin: String(auth.user?.id_admin || 0) }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      newExpense.value = { concept: "", amount: "" };
      await openExpenses();
    }
    async function deleteExpense(id) {
      await $fetch(ajaxBase, { method: "POST", body: new URLSearchParams({ deleteOrderExpense: "ok", id_expense: String(id) }).toString(), headers: { "Content-Type": "application/x-www-form-urlencoded" } }).catch(() => null);
      await openExpenses();
    }
    function openPayment() {
      if (cartItems.value.length === 0) {
        toast.add({ title: "El carrito está vacío", color: "warning" });
        return;
      }
      payAmount.value = total.value;
      payMethod.value = "efectivo";
      payReference.value = "";
      proofFile.value = null;
      wantInvoice.value = false;
      payModal.value = true;
    }
    function onProofChange(e) {
      const files = e.target.files;
      proofFile.value = files && files.length ? files[0] : null;
    }
    async function confirmPayment() {
      payLoading.value = true;
      try {
        const fd = new FormData();
        fd.append("confirmOrderPayment", "ok");
        fd.append("id_order", orderId.value);
        fd.append("method", payMethod.value);
        fd.append("reference", payReference.value);
        fd.append("invoice", wantInvoice.value ? "1" : "0");
        fd.append("id_admin", String(auth.user?.id_admin || 0));
        if (proofFile.value) fd.append("proof", proofFile.value);
        const res = await $fetch(ajaxBase, { method: "POST", body: fd });
        const d = typeof res === "string" ? JSON.parse(res) : res;
        if (d?.status === 200) {
          if (payMethod.value === "credito") {
            await $fetch(ajaxBase, {
              method: "POST",
              body: new URLSearchParams({ createCredit: "ok", id_client: selectedClient.value, id_office: String(auth.officeId || 3), id_admin: String(auth.user?.id_admin || 0), amount: String(total.value), due_date: payReference.value || "", notes: `Orden #${transactionOrder.value}` }).toString(),
              headers: { "Content-Type": "application/x-www-form-urlencoded" }
            }).catch(() => null);
          }
          payModal.value = false;
          lastReceipt.value = {
            transaction: transactionOrder.value,
            date: (/* @__PURE__ */ new Date()).toLocaleString("es-ES"),
            client: clients.value.find((c) => String(c.id_client) === selectedClient.value),
            items: [...cartItems.value],
            subtotal: subtotal.value,
            discount: totalDiscount.value,
            expenses: expensesTotal.value,
            total: total.value,
            method: payMethod.value,
            reference: payReference.value,
            invoice: wantInvoice.value,
            nit: clientNit.value,
            vendedor: auth.user?.name_admin
          };
          await advanceOrderStatus("Venta Confirmada");
          toast.add({ title: "Pago confirmado exitosamente", color: "success" });
          if (d.file_warning) toast.add({ title: "Comprobante no adjuntado", description: d.file_warning, color: "warning" });
        } else {
          toast.add({ title: d?.message || "Error al confirmar pago", color: "error" });
        }
      } catch {
        toast.add({ title: "Error de conexión", color: "error" });
      }
      payLoading.value = false;
    }
    async function registerClient() {
      if (!newClient.value.name || !newClient.value.dni) return;
      const res = await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ name_client: newClient.value.name, surname_client: newClient.value.surname, dni_client: newClient.value.dni, email_client: newClient.value.email || "", phone_client: newClient.value.phone || "", address_client: newClient.value.address || "", idOffice: String(auth.officeId || 3) }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      if (res && res !== "logout" && res !== "error") {
        if (newClient.value.nit) {
          await $fetch(`/api/clients`, { method: "PUT", headers: { "Content-Type": "application/x-www-form-urlencoded", ...apiHeaders }, query: { id: String(res), nameId: "id_client", token: "no", except: "id_client" }, body: `nit_client=${encodeURIComponent(newClient.value.nit)}` }).catch(() => null);
        }
        await fetchClients();
        selectedClient.value = String(res);
        clientModal.value = false;
        newClient.value = { name: "", surname: "", dni: "", nit: "", email: "", phone: "", address: "" };
        toast.add({ title: "Cliente registrado", color: "success" });
      } else {
        toast.add({ title: "Error al registrar cliente", color: "error" });
      }
    }
    async function submitCashOpen() {
      cashModalLoading.value = true;
      try {
        const body = new URLSearchParams({
          date_created_cash: getDate(),
          date_start_cash: (/* @__PURE__ */ new Date()).toISOString().slice(0, 19).replace("T", " "),
          id_office_cash: String(auth.officeId || 3),
          id_admin_cash: String(auth.user?.id_admin || 0),
          start_cash: String(openingAmount.value),
          status_cash: "1",
          bills_cash: "0",
          money_cash: "0",
          diff_cash: "0"
        });
        const res = await $fetch("/api/cashs?token=no&except=date_end_cash", { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded", ...apiHeaders }, body: body.toString() });
        if (res.status === 200) {
          isCashRegisterOpen.value = true;
          isOpeningCash.value = false;
          await fetchPendingOrders();
          toast.add({ title: "Caja abierta", color: "success" });
        }
      } catch {
      }
      cashModalLoading.value = false;
    }
    function printReceipt() {
      (void 0).print();
    }
    const statusConfig = {
      "Pendiente Despacho": { color: "text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-700", label: "Pendiente Despacho", icon: "i-lucide-clock" },
      "Despachado": { color: "text-blue-600 bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700", label: "Despachado", icon: "i-lucide-truck" },
      "Pago Pendiente": { color: "text-orange-600 bg-orange-50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-700", label: "Pago Pendiente", icon: "i-lucide-wallet" },
      "Venta Confirmada": { color: "text-emerald-600 bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-700", label: "Confirmada", icon: "i-lucide-check-circle" }
    };
    const currentStatus = computed(() => statusConfig[orderStatus.value] || statusConfig["Pendiente Despacho"]);
    const nextStatus = computed(() => {
      const flow = ["Pendiente Despacho", "Despachado", "Pago Pendiente", "Venta Confirmada"];
      const idx = flow.indexOf(orderStatus.value);
      return idx >= 0 && idx < flow.length - 1 ? flow[idx + 1] : null;
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UInput = _sfc_main$6;
      const _component_UButton = _sfc_main$c;
      const _component_UBadge = _sfc_main$1;
      const _component_UIcon = _sfc_main$h;
      const _component_USelect = _sfc_main$2;
      const _component_USwitch = _sfc_main$3;
      const _component_UModal = _sfc_main$4;
      const _component_UFormField = _sfc_main$5;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "h-full flex flex-col relative" }, _attrs))}><div id="print-area" class="hidden print:block bg-white text-black p-4 text-xs font-mono w-72 mx-auto">`);
      if (lastReceipt.value) {
        _push(`<div class="space-y-1.5"><div class="text-center font-bold text-sm">JE INVENTARIO &amp; VENTAS</div><div class="text-center text-xs">Sucursal: ${ssrInterpolate(unref(auth).office?.title_office || "")}</div>`);
        if (lastReceipt.value.invoice) {
          _push(`<div class="text-center text-xs">NIT Cliente: ${ssrInterpolate(lastReceipt.value.nit || "S/N")}</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<hr class="border-dashed border-black my-1"><div>Orden: ${ssrInterpolate(lastReceipt.value.transaction)}</div><div>Fecha: ${ssrInterpolate(lastReceipt.value.date)}</div>`);
        if (lastReceipt.value.client) {
          _push(`<div>Cliente: ${ssrInterpolate(decode(lastReceipt.value.client.name_client))} ${ssrInterpolate(decode(lastReceipt.value.client.surname_client || ""))}</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div>Vendedor: ${ssrInterpolate(lastReceipt.value.vendedor)}</div><hr class="border-dashed border-black my-1"><table class="w-full"><!--[-->`);
        ssrRenderList(lastReceipt.value.items, (item) => {
          _push(`<tr><td>${ssrInterpolate(item.qty_sale)}</td><td class="px-1">${ssrInterpolate(decode(products.value.find((p) => String(p.id_product) === String(item.id_product_sale))?.title_product || ""))}</td><td class="text-right">Bs.${ssrInterpolate(parseFloat(item.subtotal_sale).toFixed(2))}</td></tr>`);
        });
        _push(`<!--]--></table><hr class="border-dashed border-black my-1"><div class="flex justify-between"><span>Subtotal:</span><span>Bs.${ssrInterpolate(lastReceipt.value.subtotal.toFixed(2))}</span></div>`);
        if (lastReceipt.value.discount > 0) {
          _push(`<div class="flex justify-between"><span>Dto:</span><span>-Bs.${ssrInterpolate(lastReceipt.value.discount.toFixed(2))}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (lastReceipt.value.expenses > 0) {
          _push(`<div class="flex justify-between"><span>Gastos:</span><span>Bs.${ssrInterpolate(lastReceipt.value.expenses.toFixed(2))}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="flex justify-between font-bold"><span>TOTAL:</span><span>Bs.${ssrInterpolate(lastReceipt.value.total.toFixed(2))}</span></div><div class="flex justify-between"><span>Pago:</span><span>${ssrInterpolate(lastReceipt.value.method)}</span></div><hr class="border-dashed border-black my-1"><div class="text-center font-bold">¡GRACIAS!</div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="flex flex-col lg:grid lg:grid-cols-12 gap-4 flex-1 overflow-hidden min-h-0 print:hidden"><div class="lg:col-span-8 flex flex-col gap-3 min-h-0 flex-1 lg:flex-none overflow-hidden"><div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-sm shrink-0 space-y-2">`);
      _push(ssrRenderComponent(_component_UInput, {
        modelValue: search.value,
        "onUpdate:modelValue": ($event) => search.value = $event,
        icon: "i-lucide-search",
        placeholder: "Buscar producto..."
      }, null, _parent));
      _push(`<div class="flex gap-2 overflow-x-auto pb-1">`);
      _push(ssrRenderComponent(_component_UButton, {
        color: activeCategory.value === "all" ? "primary" : "neutral",
        variant: "soft",
        size: "xs",
        onClick: ($event) => activeCategory.value = "all"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Todos`);
          } else {
            return [
              createTextVNode("Todos")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<!--[-->`);
      ssrRenderList(categories.value, (cat) => {
        _push(ssrRenderComponent(_component_UButton, {
          key: cat.id_category,
          color: activeCategory.value === String(cat.id_category) ? "primary" : "neutral",
          variant: "soft",
          size: "xs",
          onClick: ($event) => activeCategory.value = String(cat.id_category)
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`${ssrInterpolate(decode(cat.title_category))}`);
            } else {
              return [
                createTextVNode(toDisplayString(decode(cat.title_category)), 1)
              ];
            }
          }),
          _: 2
        }, _parent));
      });
      _push(`<!--]--></div></div><div class="flex-1 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 content-start pb-4"><!--[-->`);
      ssrRenderList(filteredProducts.value, (prod) => {
        _push(`<div class="bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm hover:border-green-400 hover:shadow-md transition-all cursor-pointer flex flex-col"><div class="h-28 bg-slate-50 dark:bg-slate-950 flex items-center justify-center p-2 relative">`);
        if (prod.is_compound_product == 1) {
          _push(`<span class="absolute top-1 left-1 text-[9px] bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300 border border-purple-200 dark:border-purple-700 px-1.5 py-0.5 rounded font-bold">COMBO</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<span class="absolute top-1 right-1 text-[9px] font-mono bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-1.5 py-0.5 rounded text-slate-500">${ssrInterpolate(prod.sku_product)}</span><img${ssrRenderAttr("src", prod.img_product ? decode(prod.img_product) : "/views/assets/img/multimedia.png")} class="max-h-full max-w-full object-contain"></div><div class="p-2.5 flex flex-col gap-1.5 flex-1"><h3 class="text-xs font-semibold text-slate-800 dark:text-white line-clamp-2 leading-snug">${ssrInterpolate(decode(prod.title_product))}</h3><div class="flex justify-between items-end mt-auto"><div><span class="text-[10px] text-slate-400">Precio</span><span class="font-bold text-green-600 dark:text-green-400 font-mono text-sm block">Bs.${ssrInterpolate((pricesMap.value[prod.id_product]?.price || 0).toFixed(2))}</span></div>`);
        _push(ssrRenderComponent(_component_UBadge, {
          color: (inventory.value[prod.id_product] || 0) > 0 ? "success" : "error",
          variant: "subtle",
          size: "xs"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`${ssrInterpolate(inventory.value[prod.id_product] || 0)} ${ssrInterpolate(prod.unit_product)}`);
            } else {
              return [
                createTextVNode(toDisplayString(inventory.value[prod.id_product] || 0) + " " + toDisplayString(prod.unit_product), 1)
              ];
            }
          }),
          _: 2
        }, _parent));
        _push(`</div></div></div>`);
      });
      _push(`<!--]-->`);
      if (filteredProducts.value.length === 0) {
        _push(`<div class="col-span-full text-center py-16 text-slate-400">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-search-x",
          class: "w-8 h-8 mx-auto mb-2"
        }, null, _parent));
        _push(`<p class="text-sm">Sin productos disponibles</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="${ssrRenderClass([
        "lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden shadow-sm",
        "lg:relative lg:rounded-xl lg:translate-y-0",
        "fixed inset-x-0 bottom-0 z-30 rounded-t-2xl transition-transform duration-300 lg:transition-none",
        cartMobileOpen.value ? "translate-y-0" : "translate-y-full"
      ])}" style="${ssrRenderStyle({ "max-height": "88dvh" })}"><div class="lg:hidden flex justify-center pt-2 pb-1 shrink-0 cursor-pointer"><div class="w-10 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></div></div><div class="px-3 py-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 shrink-0"><div class="flex justify-between items-start"><div><p class="font-bold text-slate-800 dark:text-white text-sm">${ssrInterpolate(transactionOrder.value ? `#${transactionOrder.value}` : "Sin orden")}</p>`);
      if (orderId.value && usesDispatchFlow.value) {
        _push(`<div class="${ssrRenderClass(["inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full border mt-0.5", currentStatus.value.color])}">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: currentStatus.value.icon,
          class: "w-3 h-3"
        }, null, _parent));
        _push(` ${ssrInterpolate(currentStatus.value.label)}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="flex gap-1">`);
      if (!orderId.value) {
        _push(ssrRenderComponent(_component_UButton, {
          color: "primary",
          icon: "i-lucide-plus-circle",
          size: "xs",
          onClick: handleNewOrder
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Nueva`);
            } else {
              return [
                createTextVNode("Nueva")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!--[-->`);
        if (usesDispatchFlow.value) {
          _push(`<!--[-->`);
          if (nextStatus.value && orderStatus.value !== "Venta Confirmada") {
            _push(ssrRenderComponent(_component_UButton, {
              color: "success",
              size: "xs",
              icon: orderStatus.value === "Pendiente Despacho" ? "i-lucide-truck" : orderStatus.value === "Despachado" ? "i-lucide-wallet" : "i-lucide-check",
              onClick: ($event) => advanceOrderStatus(nextStatus.value)
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`${ssrInterpolate(nextStatus.value === "Despachado" ? "Despachar" : nextStatus.value === "Pago Pendiente" ? "Confirmar Salida" : "")}`);
                } else {
                  return [
                    createTextVNode(toDisplayString(nextStatus.value === "Despachado" ? "Despachar" : nextStatus.value === "Pago Pendiente" ? "Confirmar Salida" : ""), 1)
                  ];
                }
              }),
              _: 1
            }, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(ssrRenderComponent(_component_UButton, {
            icon: "i-lucide-package-open",
            color: "neutral",
            variant: "ghost",
            size: "xs",
            title: "Gastos de despacho",
            onClick: openExpenses
          }, null, _parent));
          _push(`<!--]-->`);
        } else {
          _push(`<!---->`);
        }
        _push(ssrRenderComponent(_component_UButton, {
          icon: "i-lucide-trash-2",
          color: "error",
          variant: "ghost",
          size: "xs",
          title: "Cancelar orden",
          onClick: cancelOrder
        }, null, _parent));
        _push(`<!--]-->`);
      }
      _push(`</div></div>`);
      if (pendingOrders.value.length > 1) {
        _push(`<div class="mt-2 flex gap-1 overflow-x-auto"><!--[-->`);
        ssrRenderList(pendingOrders.value, (ord) => {
          _push(`<button class="${ssrRenderClass(["text-[10px] px-2 py-0.5 rounded-full border font-mono transition-colors", String(ord.id_order) === orderId.value ? "bg-green-600 text-white border-green-600" : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700"])}"> #${ssrInterpolate(ord.transaction_order?.slice(-6))}</button>`);
        });
        _push(`<!--]--><button class="text-[10px] px-2 py-0.5 rounded-full border bg-green-50 dark:bg-green-900/20 text-green-600 border-green-200 dark:border-green-700 font-bold">+</button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="flex-1 overflow-y-auto p-3 space-y-3">`);
      if (orderId.value) {
        _push(`<div class="space-y-2"><div class="flex gap-2">`);
        _push(ssrRenderComponent(_component_USelect, {
          modelValue: selectedClient.value,
          "onUpdate:modelValue": ($event) => selectedClient.value = $event,
          options: clients.value.map((c) => ({ value: String(c.id_client), label: `${decode(c.name_client)} ${decode(c.surname_client || "")} · ${c.dni_client}` })),
          placeholder: "Cliente *",
          class: "flex-1 text-xs"
        }, null, _parent));
        _push(ssrRenderComponent(_component_UButton, {
          icon: "i-lucide-user-plus",
          color: "neutral",
          variant: "soft",
          size: "sm",
          onClick: ($event) => clientModal.value = true
        }, null, _parent));
        _push(`</div>`);
        if (selectedClient.value && usesDispatchFlow.value) {
          _push(ssrRenderComponent(_component_UInput, {
            modelValue: deliveryAddress.value,
            "onUpdate:modelValue": ($event) => deliveryAddress.value = $event,
            placeholder: "Dirección de entrega...",
            size: "sm",
            icon: "i-lucide-map-pin",
            class: "text-xs"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (orderId.value) {
        _push(`<div class="flex items-center justify-between bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2"><span class="text-xs font-medium text-slate-600 dark:text-slate-300">Precio mayorista</span>`);
        _push(ssrRenderComponent(_component_USwitch, {
          modelValue: isWholesale.value,
          "onUpdate:modelValue": ($event) => isWholesale.value = $event,
          size: "sm"
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (cartItems.value.length > 0) {
        _push(`<div class="space-y-2"><!--[-->`);
        ssrRenderList(cartItems.value, (item) => {
          _push(`<div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 rounded-lg p-2"><div class="flex-1 min-w-0"><p class="text-xs font-semibold text-slate-800 dark:text-white truncate">${ssrInterpolate(decode(products.value.find((p) => String(p.id_product) === String(item.id_product_sale))?.title_product || "Producto"))}</p><p class="text-[11px] text-green-600 dark:text-green-400 font-mono"> Bs.${ssrInterpolate((parseFloat(item.subtotal_sale) / parseInt(item.qty_sale)).toFixed(2))} × ${ssrInterpolate(item.qty_sale)}</p></div><div class="flex items-center gap-1 shrink-0">`);
          _push(ssrRenderComponent(_component_UButton, {
            icon: "i-lucide-minus",
            color: "neutral",
            variant: "ghost",
            size: "xs",
            class: "p-1",
            onClick: ($event) => updateQty(item, -1)
          }, null, _parent));
          _push(`<span class="text-xs font-mono w-5 text-center text-slate-700 dark:text-white">${ssrInterpolate(item.qty_sale)}</span>`);
          _push(ssrRenderComponent(_component_UButton, {
            icon: "i-lucide-plus",
            color: "neutral",
            variant: "ghost",
            size: "xs",
            class: "p-1",
            onClick: ($event) => updateQty(item, 1)
          }, null, _parent));
          _push(ssrRenderComponent(_component_UButton, {
            icon: "i-lucide-trash",
            color: "error",
            variant: "ghost",
            size: "xs",
            class: "p-1",
            onClick: ($event) => deleteItem(item)
          }, null, _parent));
          _push(`</div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (orderId.value) {
        _push(`<div class="text-center py-8 text-slate-400">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-shopping-cart",
          class: "w-7 h-7 mx-auto mb-1"
        }, null, _parent));
        _push(`<p class="text-xs">Carrito vacío</p></div>`);
      } else {
        _push(`<!---->`);
      }
      if (orderExpenses.value.length > 0) {
        _push(`<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-2"><p class="text-[11px] font-semibold text-blue-600 dark:text-blue-300 mb-1 flex items-center gap-1">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-truck",
          class: "w-3 h-3"
        }, null, _parent));
        _push(` Gastos de despacho </p><!--[-->`);
        ssrRenderList(orderExpenses.value, (exp) => {
          _push(`<div class="flex justify-between text-[11px] text-blue-700 dark:text-blue-300"><span>${ssrInterpolate(exp.concept_expense)}</span><span class="font-mono">Bs.${ssrInterpolate(parseFloat(exp.amount_expense).toFixed(2))}</span></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      if (checkoutSuccess.value) {
        _push(`<div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-3 text-center space-y-2">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-check-circle",
          class: "w-7 h-7 text-emerald-500 mx-auto"
        }, null, _parent));
        _push(`<p class="text-xs font-bold text-slate-700 dark:text-white">Venta confirmada</p>`);
        _push(ssrRenderComponent(_component_UButton, {
          color: "success",
          size: "xs",
          block: "",
          icon: "i-lucide-printer",
          onClick: printReceipt
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Imprimir Recibo`);
            } else {
              return [
                createTextVNode("Imprimir Recibo")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_UButton, {
          color: "neutral",
          variant: "ghost",
          size: "xs",
          block: "",
          onClick: () => {
            checkoutSuccess.value = false;
            orderId.value = null;
            transactionOrder.value = null;
            cartItems.value = [];
          }
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Nueva Orden`);
            } else {
              return [
                createTextVNode("Nueva Orden")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-2.5 shrink-0"><div class="space-y-1 text-xs"><div class="flex justify-between text-slate-500 dark:text-slate-400"><span>Subtotal</span><span class="font-mono">Bs.${ssrInterpolate(subtotal.value.toFixed(2))}</span></div>`);
      if (totalDiscount.value > 0) {
        _push(`<div class="flex justify-between text-rose-500"><span>Descuento</span><span class="font-mono">-Bs.${ssrInterpolate(totalDiscount.value.toFixed(2))}</span></div>`);
      } else {
        _push(`<!---->`);
      }
      if (expensesTotal.value > 0) {
        _push(`<div class="flex justify-between text-blue-500"><span>Gastos despacho</span><span class="font-mono">+Bs.${ssrInterpolate(expensesTotal.value.toFixed(2))}</span></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex justify-between font-bold text-slate-800 dark:text-white border-t border-slate-200 dark:border-slate-700 pt-1.5 text-sm"><span>Total</span><span class="font-mono text-green-600 dark:text-green-400">Bs.${ssrInterpolate(total.value.toFixed(2))}</span></div></div>`);
      if (!usesDispatchFlow.value && orderId.value && cartItems.value.length > 0 && !checkoutSuccess.value) {
        _push(ssrRenderComponent(_component_UButton, {
          color: "primary",
          block: "",
          icon: "i-lucide-credit-card",
          onClick: openPayment
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Cobrar Orden `);
            } else {
              return [
                createTextVNode(" Cobrar Orden ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else if (usesDispatchFlow.value && !checkoutSuccess.value) {
        _push(`<!--[-->`);
        if (orderId.value && orderStatus.value === "Pago Pendiente") {
          _push(ssrRenderComponent(_component_UButton, {
            color: "primary",
            block: "",
            icon: "i-lucide-credit-card",
            onClick: openPayment
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(` Registrar Pago `);
              } else {
                return [
                  createTextVNode(" Registrar Pago ")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else if (orderId.value && orderStatus.value === "Pendiente Despacho" && cartItems.value.length > 0) {
          _push(ssrRenderComponent(_component_UButton, {
            color: "warning",
            block: "",
            icon: "i-lucide-truck",
            variant: "soft",
            onClick: ($event) => advanceOrderStatus("Despachado")
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(` Enviar a Despacho `);
              } else {
                return [
                  createTextVNode(" Enviar a Despacho ")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div>`);
      if (cartMobileOpen.value) {
        _push(`<div class="fixed inset-0 z-20 bg-black/40 lg:hidden"></div>`);
      } else {
        _push(`<!---->`);
      }
      if (!cartMobileOpen.value) {
        _push(`<button class="fixed bottom-5 right-5 z-20 lg:hidden flex items-center gap-2 bg-green-600 hover:bg-green-700 active:scale-95 text-white font-bold px-4 py-3 rounded-full shadow-xl shadow-green-900/30 transition-all">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-shopping-cart",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span class="text-sm">Carrito</span>`);
        if (cartItems.value.length > 0) {
          _push(`<span class="bg-white text-green-700 text-xs font-black w-5 h-5 rounded-full flex items-center justify-center">${ssrInterpolate(cartItems.value.length)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button>`);
      } else {
        _push(`<!---->`);
      }
      if (isCashRegisterOpen.value === null) {
        _push(`<div class="absolute inset-0 z-40 backdrop-blur-sm bg-white/60 dark:bg-slate-900/60 flex items-center justify-center">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-loader-2",
          class: "w-9 h-9 animate-spin text-green-600"
        }, null, _parent));
        _push(`</div>`);
      } else if (!isCashRegisterOpen.value && !isVendedor.value) {
        _push(`<div class="absolute inset-0 z-40 backdrop-blur-sm bg-white/70 dark:bg-slate-900/70 flex items-center justify-center p-4"><div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 rounded-2xl max-w-sm w-full shadow-2xl text-center space-y-5"><div class="w-16 h-16 rounded-full bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 mx-auto flex items-center justify-center">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-wallet",
          class: "w-8 h-8 text-rose-500"
        }, null, _parent));
        _push(`</div><div><h2 class="text-xl font-extrabold text-slate-800 dark:text-white">Caja Cerrada</h2><p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Abre la caja del día para operar el POS.</p></div>`);
        _push(ssrRenderComponent(_component_UButton, {
          size: "lg",
          color: "success",
          icon: "i-lucide-lock-keyhole-open",
          block: "",
          onClick: ($event) => isOpeningCash.value = true
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Abrir Caja`);
            } else {
              return [
                createTextVNode("Abrir Caja")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(_component_UModal, {
        open: isOpeningCash.value,
        "onUpdate:open": ($event) => isOpeningCash.value = $event,
        title: "Apertura de Caja"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4 p-1"${_scopeId}><p class="text-sm text-slate-500 dark:text-slate-400"${_scopeId}>Monto inicial con el que comienzas el día.</p>`);
            _push2(ssrRenderComponent(_component_UFormField, { label: "Monto Inicial (Bs.)" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: openingAmount.value,
                    "onUpdate:modelValue": ($event) => openingAmount.value = $event,
                    modelModifiers: { number: true },
                    type: "number",
                    step: "0.10",
                    min: "0",
                    size: "lg",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: openingAmount.value,
                      "onUpdate:modelValue": ($event) => openingAmount.value = $event,
                      modelModifiers: { number: true },
                      type: "number",
                      step: "0.10",
                      min: "0",
                      size: "lg",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4 p-1" }, [
                createVNode("p", { class: "text-sm text-slate-500 dark:text-slate-400" }, "Monto inicial con el que comienzas el día."),
                createVNode(_component_UFormField, { label: "Monto Inicial (Bs.)" }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: openingAmount.value,
                      "onUpdate:modelValue": ($event) => openingAmount.value = $event,
                      modelModifiers: { number: true },
                      type: "number",
                      step: "0.10",
                      min: "0",
                      size: "lg",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                })
              ])
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex justify-end gap-3"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              color: "neutral",
              variant: "ghost",
              onClick: ($event) => isOpeningCash.value = false
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Cancelar`);
                } else {
                  return [
                    createTextVNode("Cancelar")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UButton, {
              color: "success",
              loading: cashModalLoading.value,
              onClick: submitCashOpen
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Confirmar y Abrir`);
                } else {
                  return [
                    createTextVNode("Confirmar y Abrir")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "flex justify-end gap-3" }, [
                createVNode(_component_UButton, {
                  color: "neutral",
                  variant: "ghost",
                  onClick: ($event) => isOpeningCash.value = false
                }, {
                  default: withCtx(() => [
                    createTextVNode("Cancelar")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(_component_UButton, {
                  color: "success",
                  loading: cashModalLoading.value,
                  onClick: submitCashOpen
                }, {
                  default: withCtx(() => [
                    createTextVNode("Confirmar y Abrir")
                  ]),
                  _: 1
                }, 8, ["loading"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UModal, {
        open: expensesModal.value,
        "onUpdate:open": ($event) => expensesModal.value = $event,
        title: "Gastos de Despacho"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4 p-1"${_scopeId}>`);
            if (expLoading.value) {
              _push2(`<div class="text-center py-4"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-loader-2",
                class: "w-6 h-6 animate-spin text-green-500 mx-auto"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<!--[--><div class="space-y-2"${_scopeId}><!--[-->`);
              ssrRenderList(orderExpenses.value, (exp) => {
                _push2(`<div class="flex justify-between items-center bg-slate-50 dark:bg-slate-800 rounded-lg px-3 py-2 text-sm"${_scopeId}><span class="text-slate-700 dark:text-slate-200"${_scopeId}>${ssrInterpolate(exp.concept_expense)}</span><div class="flex items-center gap-2"${_scopeId}><span class="font-mono font-bold text-blue-600"${_scopeId}>Bs.${ssrInterpolate(parseFloat(exp.amount_expense).toFixed(2))}</span>`);
                _push2(ssrRenderComponent(_component_UButton, {
                  icon: "i-lucide-trash",
                  color: "error",
                  variant: "ghost",
                  size: "xs",
                  onClick: ($event) => deleteExpense(exp.id_expense)
                }, null, _parent2, _scopeId));
                _push2(`</div></div>`);
              });
              _push2(`<!--]-->`);
              if (orderExpenses.value.length === 0) {
                _push2(`<div class="text-center py-4 text-slate-400 text-sm"${_scopeId}>Sin gastos registrados</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><div class="flex gap-2 pt-2 border-t border-slate-200 dark:border-slate-700"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UInput, {
                modelValue: newExpense.value.concept,
                "onUpdate:modelValue": ($event) => newExpense.value.concept = $event,
                placeholder: "Concepto (ej: Transporte)",
                class: "flex-1 text-sm"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_component_UInput, {
                modelValue: newExpense.value.amount,
                "onUpdate:modelValue": ($event) => newExpense.value.amount = $event,
                type: "number",
                step: "0.5",
                placeholder: "Bs.",
                class: "w-24 text-sm"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_component_UButton, {
                icon: "i-lucide-plus",
                color: "primary",
                size: "sm",
                onClick: addExpense
              }, null, _parent2, _scopeId));
              _push2(`</div><div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg px-3 py-2 flex justify-between items-center"${_scopeId}><span class="text-sm font-semibold text-blue-700 dark:text-blue-300"${_scopeId}>Total Gastos</span><span class="font-bold font-mono text-blue-700 dark:text-blue-300"${_scopeId}>${ssrInterpolate(fmt(expensesTotal.value))}</span></div><!--]-->`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4 p-1" }, [
                expLoading.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "text-center py-4"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "w-6 h-6 animate-spin text-green-500 mx-auto"
                  })
                ])) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                  createVNode("div", { class: "space-y-2" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(orderExpenses.value, (exp) => {
                      return openBlock(), createBlock("div", {
                        key: exp.id_expense,
                        class: "flex justify-between items-center bg-slate-50 dark:bg-slate-800 rounded-lg px-3 py-2 text-sm"
                      }, [
                        createVNode("span", { class: "text-slate-700 dark:text-slate-200" }, toDisplayString(exp.concept_expense), 1),
                        createVNode("div", { class: "flex items-center gap-2" }, [
                          createVNode("span", { class: "font-mono font-bold text-blue-600" }, "Bs." + toDisplayString(parseFloat(exp.amount_expense).toFixed(2)), 1),
                          createVNode(_component_UButton, {
                            icon: "i-lucide-trash",
                            color: "error",
                            variant: "ghost",
                            size: "xs",
                            onClick: ($event) => deleteExpense(exp.id_expense)
                          }, null, 8, ["onClick"])
                        ])
                      ]);
                    }), 128)),
                    orderExpenses.value.length === 0 ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "text-center py-4 text-slate-400 text-sm"
                    }, "Sin gastos registrados")) : createCommentVNode("", true)
                  ]),
                  createVNode("div", { class: "flex gap-2 pt-2 border-t border-slate-200 dark:border-slate-700" }, [
                    createVNode(_component_UInput, {
                      modelValue: newExpense.value.concept,
                      "onUpdate:modelValue": ($event) => newExpense.value.concept = $event,
                      placeholder: "Concepto (ej: Transporte)",
                      class: "flex-1 text-sm"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                    createVNode(_component_UInput, {
                      modelValue: newExpense.value.amount,
                      "onUpdate:modelValue": ($event) => newExpense.value.amount = $event,
                      type: "number",
                      step: "0.5",
                      placeholder: "Bs.",
                      class: "w-24 text-sm"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                    createVNode(_component_UButton, {
                      icon: "i-lucide-plus",
                      color: "primary",
                      size: "sm",
                      onClick: addExpense
                    })
                  ]),
                  createVNode("div", { class: "bg-blue-50 dark:bg-blue-900/20 rounded-lg px-3 py-2 flex justify-between items-center" }, [
                    createVNode("span", { class: "text-sm font-semibold text-blue-700 dark:text-blue-300" }, "Total Gastos"),
                    createVNode("span", { class: "font-bold font-mono text-blue-700 dark:text-blue-300" }, toDisplayString(fmt(expensesTotal.value)), 1)
                  ])
                ], 64))
              ])
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_UButton, {
              color: "neutral",
              variant: "ghost",
              onClick: ($event) => expensesModal.value = false
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Cerrar`);
                } else {
                  return [
                    createTextVNode("Cerrar")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(_component_UButton, {
                color: "neutral",
                variant: "ghost",
                onClick: ($event) => expensesModal.value = false
              }, {
                default: withCtx(() => [
                  createTextVNode("Cerrar")
                ]),
                _: 1
              }, 8, ["onClick"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UModal, {
        open: payModal.value,
        "onUpdate:open": ($event) => payModal.value = $event,
        title: "Registrar Pago"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4 p-1"${_scopeId}><div class="bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-xl p-4 text-center"${_scopeId}><span class="text-xs text-slate-500 dark:text-slate-400 block"${_scopeId}>Total a Cobrar</span><span class="text-3xl font-extrabold text-green-600 dark:text-green-400 font-mono"${_scopeId}>${ssrInterpolate(fmt(total.value))}</span></div><div${_scopeId}><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2"${_scopeId}>Método de Pago</p><div class="grid grid-cols-3 gap-2"${_scopeId}><!--[-->`);
            ssrRenderList(payMethods.value, (m) => {
              _push2(ssrRenderComponent(_component_UButton, {
                key: m.value,
                color: payMethod.value === m.value ? "primary" : "neutral",
                variant: "soft",
                size: "sm",
                icon: m.icon,
                class: "flex-col py-2.5 text-xs",
                onClick: ($event) => payMethod.value = m.value
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(m.label)}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(m.label), 1)
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
            });
            _push2(`<!--]--></div></div>`);
            if (payMethod.value === "efectivo" || payMethod.value === "qr") {
              _push2(`<div class="space-y-2"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UFormField, {
                label: `${payMethod.value === "efectivo" ? "Efectivo" : "Monto QR"} Recibido (Bs.)`
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_UInput, {
                      modelValue: payAmount.value,
                      "onUpdate:modelValue": ($event) => payAmount.value = $event,
                      modelModifiers: { number: true },
                      type: "number",
                      step: "any",
                      class: "w-full"
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_component_UInput, {
                        modelValue: payAmount.value,
                        "onUpdate:modelValue": ($event) => payAmount.value = $event,
                        modelModifiers: { number: true },
                        type: "number",
                        step: "any",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`<div class="flex justify-between text-sm font-semibold"${_scopeId}><span class="text-slate-500"${_scopeId}>Cambio:</span><span class="font-mono text-green-600"${_scopeId}>${ssrInterpolate(fmt(cashChange.value))}</span></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (payMethod.value === "transferencia") {
              _push2(`<div${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UFormField, { label: "N° de Referencia / Comprobante *" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_UInput, {
                      modelValue: payReference.value,
                      "onUpdate:modelValue": ($event) => payReference.value = $event,
                      placeholder: "Código de transferencia",
                      class: "w-full"
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_component_UInput, {
                        modelValue: payReference.value,
                        "onUpdate:modelValue": ($event) => payReference.value = $event,
                        placeholder: "Código de transferencia",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            if (payMethod.value === "credito") {
              _push2(`<div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 space-y-2"${_scopeId}><p class="text-xs font-semibold text-amber-700 dark:text-amber-300 flex items-center gap-1"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-info",
                class: "w-3.5 h-3.5"
              }, null, _parent2, _scopeId));
              _push2(` Se creará un crédito por ${ssrInterpolate(fmt(total.value))}</p>`);
              _push2(ssrRenderComponent(_component_UFormField, { label: "Fecha de Vencimiento" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_UInput, {
                      modelValue: payReference.value,
                      "onUpdate:modelValue": ($event) => payReference.value = $event,
                      type: "date",
                      class: "w-full"
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_component_UInput, {
                        modelValue: payReference.value,
                        "onUpdate:modelValue": ($event) => payReference.value = $event,
                        type: "date",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            if (payMethod.value === "consignacion") {
              _push2(`<div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3"${_scopeId}><p class="text-xs text-purple-700 dark:text-purple-300"${_scopeId}> Los productos se asignarán al inventario del vendedor para su liquidación posterior. </p></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (payMethod.value === "qr" || payMethod.value === "transferencia") {
              _push2(`<div class="border-t border-slate-200 dark:border-slate-700 pt-3"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UFormField, {
                label: "Comprobante de pago (imagen/PDF)",
                help: "Captura del QR/transferencia como respaldo de la venta. Máx 5MB."
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-300"${_scopeId2}>`);
                  } else {
                    return [
                      createVNode("input", {
                        type: "file",
                        accept: "image/jpeg,image/png,image/webp,application/pdf",
                        class: "block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-300",
                        onChange: onProofChange
                      }, null, 32)
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              if (proofFile.value) {
                _push2(`<p class="text-xs text-green-600 dark:text-green-400 mt-1 flex items-center gap-1"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-paperclip",
                  class: "w-3.5 h-3.5"
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(proofFile.value.name)}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="border-t border-slate-200 dark:border-slate-700 pt-3 space-y-2"${_scopeId}><div class="flex items-center justify-between"${_scopeId}><span class="text-sm text-slate-600 dark:text-slate-300"${_scopeId}>¿Emitir Factura?</span>`);
            _push2(ssrRenderComponent(_component_USwitch, {
              modelValue: wantInvoice.value,
              "onUpdate:modelValue": ($event) => wantInvoice.value = $event
            }, null, _parent2, _scopeId));
            _push2(`</div>`);
            if (wantInvoice.value) {
              _push2(`<div class="space-y-2"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UFormField, { label: "NIT del Cliente" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_UInput, {
                      modelValue: clientNit.value,
                      "onUpdate:modelValue": ($event) => clientNit.value = $event,
                      placeholder: "Ej: 1234567",
                      class: "w-full"
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_component_UInput, {
                        modelValue: clientNit.value,
                        "onUpdate:modelValue": ($event) => clientNit.value = $event,
                        placeholder: "Ej: 1234567",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4 p-1" }, [
                createVNode("div", { class: "bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-xl p-4 text-center" }, [
                  createVNode("span", { class: "text-xs text-slate-500 dark:text-slate-400 block" }, "Total a Cobrar"),
                  createVNode("span", { class: "text-3xl font-extrabold text-green-600 dark:text-green-400 font-mono" }, toDisplayString(fmt(total.value)), 1)
                ]),
                createVNode("div", null, [
                  createVNode("p", { class: "text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2" }, "Método de Pago"),
                  createVNode("div", { class: "grid grid-cols-3 gap-2" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(payMethods.value, (m) => {
                      return openBlock(), createBlock(_component_UButton, {
                        key: m.value,
                        color: payMethod.value === m.value ? "primary" : "neutral",
                        variant: "soft",
                        size: "sm",
                        icon: m.icon,
                        class: "flex-col py-2.5 text-xs",
                        onClick: ($event) => payMethod.value = m.value
                      }, {
                        default: withCtx(() => [
                          createTextVNode(toDisplayString(m.label), 1)
                        ]),
                        _: 2
                      }, 1032, ["color", "icon", "onClick"]);
                    }), 128))
                  ])
                ]),
                payMethod.value === "efectivo" || payMethod.value === "qr" ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "space-y-2"
                }, [
                  createVNode(_component_UFormField, {
                    label: `${payMethod.value === "efectivo" ? "Efectivo" : "Monto QR"} Recibido (Bs.)`
                  }, {
                    default: withCtx(() => [
                      createVNode(_component_UInput, {
                        modelValue: payAmount.value,
                        "onUpdate:modelValue": ($event) => payAmount.value = $event,
                        modelModifiers: { number: true },
                        type: "number",
                        step: "any",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"])
                    ]),
                    _: 1
                  }, 8, ["label"]),
                  createVNode("div", { class: "flex justify-between text-sm font-semibold" }, [
                    createVNode("span", { class: "text-slate-500" }, "Cambio:"),
                    createVNode("span", { class: "font-mono text-green-600" }, toDisplayString(fmt(cashChange.value)), 1)
                  ])
                ])) : createCommentVNode("", true),
                payMethod.value === "transferencia" ? (openBlock(), createBlock("div", { key: 1 }, [
                  createVNode(_component_UFormField, { label: "N° de Referencia / Comprobante *" }, {
                    default: withCtx(() => [
                      createVNode(_component_UInput, {
                        modelValue: payReference.value,
                        "onUpdate:modelValue": ($event) => payReference.value = $event,
                        placeholder: "Código de transferencia",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"])
                    ]),
                    _: 1
                  })
                ])) : createCommentVNode("", true),
                payMethod.value === "credito" ? (openBlock(), createBlock("div", {
                  key: 2,
                  class: "bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 space-y-2"
                }, [
                  createVNode("p", { class: "text-xs font-semibold text-amber-700 dark:text-amber-300 flex items-center gap-1" }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-info",
                      class: "w-3.5 h-3.5"
                    }),
                    createTextVNode(" Se creará un crédito por " + toDisplayString(fmt(total.value)), 1)
                  ]),
                  createVNode(_component_UFormField, { label: "Fecha de Vencimiento" }, {
                    default: withCtx(() => [
                      createVNode(_component_UInput, {
                        modelValue: payReference.value,
                        "onUpdate:modelValue": ($event) => payReference.value = $event,
                        type: "date",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"])
                    ]),
                    _: 1
                  })
                ])) : createCommentVNode("", true),
                payMethod.value === "consignacion" ? (openBlock(), createBlock("div", {
                  key: 3,
                  class: "bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3"
                }, [
                  createVNode("p", { class: "text-xs text-purple-700 dark:text-purple-300" }, " Los productos se asignarán al inventario del vendedor para su liquidación posterior. ")
                ])) : createCommentVNode("", true),
                payMethod.value === "qr" || payMethod.value === "transferencia" ? (openBlock(), createBlock("div", {
                  key: 4,
                  class: "border-t border-slate-200 dark:border-slate-700 pt-3"
                }, [
                  createVNode(_component_UFormField, {
                    label: "Comprobante de pago (imagen/PDF)",
                    help: "Captura del QR/transferencia como respaldo de la venta. Máx 5MB."
                  }, {
                    default: withCtx(() => [
                      createVNode("input", {
                        type: "file",
                        accept: "image/jpeg,image/png,image/webp,application/pdf",
                        class: "block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-300",
                        onChange: onProofChange
                      }, null, 32)
                    ]),
                    _: 1
                  }),
                  proofFile.value ? (openBlock(), createBlock("p", {
                    key: 0,
                    class: "text-xs text-green-600 dark:text-green-400 mt-1 flex items-center gap-1"
                  }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-paperclip",
                      class: "w-3.5 h-3.5"
                    }),
                    createTextVNode(" " + toDisplayString(proofFile.value.name), 1)
                  ])) : createCommentVNode("", true)
                ])) : createCommentVNode("", true),
                createVNode("div", { class: "border-t border-slate-200 dark:border-slate-700 pt-3 space-y-2" }, [
                  createVNode("div", { class: "flex items-center justify-between" }, [
                    createVNode("span", { class: "text-sm text-slate-600 dark:text-slate-300" }, "¿Emitir Factura?"),
                    createVNode(_component_USwitch, {
                      modelValue: wantInvoice.value,
                      "onUpdate:modelValue": ($event) => wantInvoice.value = $event
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  wantInvoice.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "space-y-2"
                  }, [
                    createVNode(_component_UFormField, { label: "NIT del Cliente" }, {
                      default: withCtx(() => [
                        createVNode(_component_UInput, {
                          modelValue: clientNit.value,
                          "onUpdate:modelValue": ($event) => clientNit.value = $event,
                          placeholder: "Ej: 1234567",
                          class: "w-full"
                        }, null, 8, ["modelValue", "onUpdate:modelValue"])
                      ]),
                      _: 1
                    })
                  ])) : createCommentVNode("", true)
                ])
              ])
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex justify-end gap-3"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              color: "neutral",
              variant: "ghost",
              onClick: ($event) => payModal.value = false
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Cancelar`);
                } else {
                  return [
                    createTextVNode("Cancelar")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UButton, {
              color: "primary",
              icon: "i-lucide-check",
              loading: payLoading.value,
              onClick: confirmPayment
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Confirmar Pago`);
                } else {
                  return [
                    createTextVNode("Confirmar Pago")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "flex justify-end gap-3" }, [
                createVNode(_component_UButton, {
                  color: "neutral",
                  variant: "ghost",
                  onClick: ($event) => payModal.value = false
                }, {
                  default: withCtx(() => [
                    createTextVNode("Cancelar")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(_component_UButton, {
                  color: "primary",
                  icon: "i-lucide-check",
                  loading: payLoading.value,
                  onClick: confirmPayment
                }, {
                  default: withCtx(() => [
                    createTextVNode("Confirmar Pago")
                  ]),
                  _: 1
                }, 8, ["loading"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UModal, {
        open: clientModal.value,
        "onUpdate:open": ($event) => clientModal.value = $event,
        title: "Registrar Cliente"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="grid grid-cols-2 gap-3 p-1"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UFormField, {
              label: "Nombre *",
              class: "col-span-1"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: newClient.value.name,
                    "onUpdate:modelValue": ($event) => newClient.value.name = $event,
                    placeholder: "Juan",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.name,
                      "onUpdate:modelValue": ($event) => newClient.value.name = $event,
                      placeholder: "Juan",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, {
              label: "Apellido",
              class: "col-span-1"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: newClient.value.surname,
                    "onUpdate:modelValue": ($event) => newClient.value.surname = $event,
                    placeholder: "Pérez",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.surname,
                      "onUpdate:modelValue": ($event) => newClient.value.surname = $event,
                      placeholder: "Pérez",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, {
              label: "DNI *",
              class: "col-span-1"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: newClient.value.dni,
                    "onUpdate:modelValue": ($event) => newClient.value.dni = $event,
                    placeholder: "1234567",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.dni,
                      "onUpdate:modelValue": ($event) => newClient.value.dni = $event,
                      placeholder: "1234567",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, {
              label: "NIT (Facturación)",
              class: "col-span-1"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: newClient.value.nit,
                    "onUpdate:modelValue": ($event) => newClient.value.nit = $event,
                    placeholder: "NIT empresa",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.nit,
                      "onUpdate:modelValue": ($event) => newClient.value.nit = $event,
                      placeholder: "NIT empresa",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, {
              label: "Teléfono",
              class: "col-span-1"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: newClient.value.phone,
                    "onUpdate:modelValue": ($event) => newClient.value.phone = $event,
                    placeholder: "79000000",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.phone,
                      "onUpdate:modelValue": ($event) => newClient.value.phone = $event,
                      placeholder: "79000000",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, {
              label: "Correo",
              class: "col-span-1"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: newClient.value.email,
                    "onUpdate:modelValue": ($event) => newClient.value.email = $event,
                    type: "email",
                    placeholder: "juan@mail.com",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.email,
                      "onUpdate:modelValue": ($event) => newClient.value.email = $event,
                      type: "email",
                      placeholder: "juan@mail.com",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, {
              label: "Dirección",
              class: "col-span-2"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: newClient.value.address,
                    "onUpdate:modelValue": ($event) => newClient.value.address = $event,
                    placeholder: "Zona Central...",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.address,
                      "onUpdate:modelValue": ($event) => newClient.value.address = $event,
                      placeholder: "Zona Central...",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "grid grid-cols-2 gap-3 p-1" }, [
                createVNode(_component_UFormField, {
                  label: "Nombre *",
                  class: "col-span-1"
                }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.name,
                      "onUpdate:modelValue": ($event) => newClient.value.name = $event,
                      placeholder: "Juan",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, {
                  label: "Apellido",
                  class: "col-span-1"
                }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.surname,
                      "onUpdate:modelValue": ($event) => newClient.value.surname = $event,
                      placeholder: "Pérez",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, {
                  label: "DNI *",
                  class: "col-span-1"
                }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.dni,
                      "onUpdate:modelValue": ($event) => newClient.value.dni = $event,
                      placeholder: "1234567",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, {
                  label: "NIT (Facturación)",
                  class: "col-span-1"
                }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.nit,
                      "onUpdate:modelValue": ($event) => newClient.value.nit = $event,
                      placeholder: "NIT empresa",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, {
                  label: "Teléfono",
                  class: "col-span-1"
                }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.phone,
                      "onUpdate:modelValue": ($event) => newClient.value.phone = $event,
                      placeholder: "79000000",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, {
                  label: "Correo",
                  class: "col-span-1"
                }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.email,
                      "onUpdate:modelValue": ($event) => newClient.value.email = $event,
                      type: "email",
                      placeholder: "juan@mail.com",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, {
                  label: "Dirección",
                  class: "col-span-2"
                }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: newClient.value.address,
                      "onUpdate:modelValue": ($event) => newClient.value.address = $event,
                      placeholder: "Zona Central...",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                })
              ])
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex justify-end gap-3"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              color: "neutral",
              variant: "ghost",
              onClick: ($event) => clientModal.value = false
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Cancelar`);
                } else {
                  return [
                    createTextVNode("Cancelar")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UButton, {
              color: "primary",
              onClick: registerClient
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Crear Cliente`);
                } else {
                  return [
                    createTextVNode("Crear Cliente")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "flex justify-end gap-3" }, [
                createVNode(_component_UButton, {
                  color: "neutral",
                  variant: "ghost",
                  onClick: ($event) => clientModal.value = false
                }, {
                  default: withCtx(() => [
                    createTextVNode("Cancelar")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(_component_UButton, {
                  color: "primary",
                  onClick: registerClient
                }, {
                  default: withCtx(() => [
                    createTextVNode("Crear Cliente")
                  ]),
                  _: 1
                })
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/pos.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=pos-eqnG5Ie5.mjs.map
