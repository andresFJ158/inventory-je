import { H as useAuthStore, a5 as useToast, j as _sfc_main$6, g as _sfc_main$c, h as _sfc_main$h } from './server.mjs';
import { _ as _sfc_main$1 } from './Card-DphZz1jr.mjs';
import { _ as _sfc_main$2 } from './Tabs-BpL_2piA.mjs';
import { _ as _sfc_main$3 } from './Table-D5KmoofB.mjs';
import { _ as _sfc_main$4 } from './Badge-P0JOv5sI.mjs';
import { _ as __nuxt_component_6 } from './OrderReceiptModal-B0P38A7N.mjs';
import { _ as _sfc_main$5 } from './Modal-CoprpFuw.mjs';
import { _ as _sfc_main$7 } from './FormField-DEd2VTbu.mjs';
import { defineComponent, ref, computed, mergeProps, withCtx, createTextVNode, createVNode, toDisplayString, unref, openBlock, createBlock, Fragment, renderList, createCommentVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr } from 'vue/server-renderer';
import { Chart, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
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
import '@tanstack/vue-table';
import '@tanstack/vue-virtual';
import './overlay-DuqFFmJC.mjs';
import './Label-CXontjPM.mjs';

const ajaxBase = "/ajax/pos.ajax.php";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "reportes",
  __ssrInlineRender: true,
  setup(__props) {
    Chart.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);
    const auth = useAuthStore();
    const toast = useToast();
    const orders = ref([]);
    const sales = ref([]);
    const offices = ref([]);
    const statsByOffice = ref([]);
    const loading = ref(true);
    const today = /* @__PURE__ */ new Date();
    const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
    const startDate = ref(firstDayLastMonth.toISOString().split("T")[0]);
    const endDate = ref(lastDayLastMonth.toISOString().split("T")[0]);
    const items = [{
      label: "Órdenes",
      icon: "i-lucide-file-text",
      slot: "ordenes"
    }, {
      label: "Ventas (Productos)",
      icon: "i-lucide-box",
      slot: "ventas"
    }];
    const apiHeaders = {
      Authorization: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy"
    };
    async function fetchOffices() {
      try {
        const data = await $fetch("/api/offices", { headers: apiHeaders });
        if (data.status === 200 && data.results) {
          offices.value = data.results;
        }
      } catch (e) {
        console.error("Error fetching offices:", e);
      }
    }
    async function fetchReportData() {
      loading.value = true;
      orders.value = [];
      sales.value = [];
      statsByOffice.value = [];
      try {
        const isSuperAdmin = auth.officeId === 0 || auth.role === "superadmin" || auth.role === "admin";
        const myOfficeId = auth.officeId || 3;
        const orderParams = new URLSearchParams({
          rel: "orders,clients,offices",
          type: "order,client,office",
          orderBy: "id_order",
          orderMode: "DESC"
        });
        const saleParams = new URLSearchParams({
          rel: "sales,products",
          type: "sale,product",
          orderBy: "id_sale",
          orderMode: "DESC"
        });
        const sDate = startDate.value || "";
        const eDate = endDate.value || "";
        if (sDate === eDate) {
          if (isSuperAdmin) {
            orderParams.set("linkTo", "date_created_order");
            orderParams.set("equalTo", sDate);
            saleParams.set("linkTo", "date_created_sale");
            saleParams.set("equalTo", sDate);
          } else {
            orderParams.set("linkTo", "id_office_order,date_created_order");
            orderParams.set("equalTo", `${myOfficeId},${sDate}`);
            saleParams.set("linkTo", "id_office_sale,date_created_sale");
            saleParams.set("equalTo", `${myOfficeId},${sDate}`);
          }
        } else {
          if (isSuperAdmin) {
            orderParams.set("between1", "date_created_order");
            orderParams.set("between2", `${sDate},${eDate}`);
            saleParams.set("between1", "date_created_sale");
            saleParams.set("between2", `${sDate},${eDate}`);
          } else {
            orderParams.set("linkTo", "id_office_order");
            orderParams.set("equalTo", String(myOfficeId));
            orderParams.set("between1", "date_created_order");
            orderParams.set("between2", `${sDate},${eDate}`);
            saleParams.set("linkTo", "id_office_sale");
            saleParams.set("equalTo", String(myOfficeId));
            saleParams.set("between1", "date_created_sale");
            saleParams.set("between2", `${sDate},${eDate}`);
          }
        }
        const [ordersData, salesData] = await Promise.all([
          $fetch(`/api/relations?${orderParams.toString()}`, { headers: apiHeaders }).catch(() => null),
          $fetch(`/api/relations?${saleParams.toString()}`, { headers: apiHeaders }).catch(() => null)
        ]);
        if (ordersData && ordersData.status === 200 && ordersData.results) {
          orders.value = ordersData.results;
        }
        if (salesData && salesData.status === 200 && salesData.results) {
          sales.value = salesData.results;
        }
        if (isSuperAdmin) {
          await fetchOffices();
          const officeMap = {};
          offices.value.forEach((o) => {
            officeMap[o.id_office] = {
              name: decodeURIComponent(o.title_office).replace(/\+/g, " "),
              id: o.id_office,
              total_orders: 0,
              total_amount: 0
            };
          });
          orders.value.forEach((o) => {
            const offId = String(o.id_office_order);
            if (officeMap[offId]) {
              officeMap[offId].total_orders++;
              officeMap[offId].total_amount += parseFloat(o.total_order || 0);
            }
          });
          statsByOffice.value = Object.values(officeMap).filter((st) => st.total_orders > 0).sort((a, b) => b.total_amount - a.total_amount);
        }
      } catch (e) {
        console.error("Error fetching reports:", e);
      } finally {
        loading.value = false;
      }
    }
    function applyFilter() {
      fetchReportData();
    }
    const totalVentasBs = computed(() => orders.value.reduce((acc, o) => acc + parseFloat(o.total_order || 0), 0));
    const sumSubtotal = computed(() => orders.value.reduce((acc, o) => acc + parseFloat(o.subtotal_order || 0), 0));
    const sumDiscount = computed(() => orders.value.reduce((acc, o) => acc + parseFloat(o.discount_order || 0), 0));
    const totalProductsQty = computed(() => sales.value.reduce((acc, s) => acc + parseInt(s.qty_sale || 0), 0));
    const avgOrder = computed(() => orders.value.length > 0 ? totalVentasBs.value / orders.value.length : 0);
    const salesByDayChartData = computed(() => {
      const byDay = {};
      orders.value.forEach((o) => {
        const d = o.date_created_order || "N/A";
        byDay[d] = (byDay[d] || 0) + parseFloat(o.total_order || 0);
      });
      const sortedKeys = Object.keys(byDay).sort();
      return {
        labels: sortedKeys,
        datasets: [{
          label: "Ventas por Día (Bs)",
          data: sortedKeys.map((k) => byDay[k] || 0),
          backgroundColor: "#16a34a",
          borderRadius: 4
        }]
      };
    });
    const topProductsChartData = computed(() => {
      const byProd = {};
      sales.value.forEach((s) => {
        const pName = decodeURIComponent(s.title_product || "").replace(/\+/g, " ");
        byProd[pName] = (byProd[pName] || 0) + parseFloat(s.subtotal_sale || 0);
      });
      const sortedProds = Object.entries(byProd).sort((a, b) => b[1] - a[1]).slice(0, 10);
      return {
        labels: sortedProds.map((p) => p[0]),
        datasets: [{
          data: sortedProds.map((p) => p[1]),
          backgroundColor: [
            "#16a34a",
            "#2563eb",
            "#d97706",
            "#dc2626",
            "#7c3aed",
            "#059669",
            "#4f46e5",
            "#db2777",
            "#c026d3",
            "#0891b2"
          ]
        }]
      };
    });
    const salesByOfficeChartData = computed(() => {
      return {
        labels: statsByOffice.value.map((st) => st.name),
        datasets: [{
          label: "Total Ventas por Sucursal (Bs)",
          data: statsByOffice.value.map((st) => st.total_amount),
          backgroundColor: "#2563eb",
          borderRadius: 4
        }]
      };
    });
    function formatCurrency(val) {
      return new Intl.NumberFormat("es-BO", { style: "currency", currency: "BOB" }).format(val);
    }
    function decodeStr(str) {
      if (!str) return "";
      return decodeURIComponent(str).replace(/\+/g, " ");
    }
    function handleExportCSV() {
      if (orders.value.length === 0) return;
      const headers = ["Transaccion", "Cliente", "Sucursal", "Fecha", "Metodo", "Estado", "Subtotal", "Descuento", "Impuesto", "Total"];
      const rows = orders.value.map((o) => [
        o.transaction_order,
        decodeStr(o.name_client) + " " + decodeStr(o.surname_client),
        decodeStr(o.title_office),
        o.date_order,
        o.method_order,
        o.status_order,
        o.subtotal_order,
        o.discount_order,
        o.tax_order,
        o.total_order
      ]);
      const csvContent = "data:text/csv;charset=utf-8," + headers.join(",") + "\n" + rows.map((r) => r.join(",")).join("\n");
      const encodedUri = encodeURI(csvContent);
      const link = (void 0).createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", `reporte_ventas_${startDate.value}_${endDate.value}.csv`);
      (void 0).body.appendChild(link);
      link.click();
      (void 0).body.removeChild(link);
    }
    const isReceiptModalOpen = ref(false);
    const selectedOrderId = ref(null);
    function viewPdf(idOrder) {
      selectedOrderId.value = idOrder;
      isReceiptModalOpen.value = true;
    }
    const proofModal = ref(false);
    const proofOrder = ref(null);
    const proofPayments = ref([]);
    const proofLoading = ref(false);
    const proofUploading = ref(false);
    const newProofFile = ref(null);
    const newProofRef = ref("");
    async function openProof(order) {
      proofOrder.value = order;
      proofModal.value = true;
      newProofFile.value = null;
      newProofRef.value = "";
      await fetchProofs();
    }
    async function fetchProofs() {
      if (!proofOrder.value) return;
      proofLoading.value = true;
      const res = await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ getSalePayments: "ok", id_order: String(proofOrder.value.id_order) }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      const d = typeof res === "string" ? JSON.parse(res) : res;
      proofPayments.value = d?.status === 200 ? d.results : [];
      proofLoading.value = false;
    }
    function onNewProofChange(e) {
      const files = e.target.files;
      newProofFile.value = files && files.length ? files[0] : null;
    }
    async function uploadProof(idSalePayment) {
      if (!proofOrder.value) return;
      if (!newProofFile.value && !newProofRef.value && !idSalePayment) {
        toast.add({ title: "Adjunte un archivo o una referencia", color: "warning" });
        return;
      }
      proofUploading.value = true;
      try {
        const fd = new FormData();
        fd.append("uploadSalePayment", "ok");
        fd.append("id_order", String(proofOrder.value.id_order));
        fd.append("id_admin", String(auth.user?.id_admin || 0));
        if (idSalePayment) fd.append("id_sale_payment", String(idSalePayment));
        if (newProofRef.value) fd.append("reference", newProofRef.value);
        if (newProofFile.value) fd.append("proof", newProofFile.value);
        const res = await $fetch(ajaxBase, { method: "POST", body: fd });
        const d = typeof res === "string" ? JSON.parse(res) : res;
        if (d?.status === 200) {
          toast.add({ title: d.message || "Comprobante guardado", color: "success" });
          newProofFile.value = null;
          newProofRef.value = "";
          await fetchProofs();
        } else {
          toast.add({ title: d?.message || "Error al guardar", color: "error" });
        }
      } catch {
        toast.add({ title: "Error de conexión", color: "error" });
      }
      proofUploading.value = false;
    }
    async function deleteProof(idSalePayment) {
      await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ deleteSalePayment: "ok", id_sale_payment: String(idSalePayment) }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      await fetchProofs();
    }
    const orderCols = [
      { accessorKey: "transaction_order", header: "Transacción" },
      { accessorKey: "client", header: "Cliente" },
      { accessorKey: "date_order", header: "Fecha" },
      { accessorKey: "method_order", header: "Método" },
      { accessorKey: "subtotal_order", header: "Subtotal" },
      { accessorKey: "discount_order", header: "Dscto" },
      { accessorKey: "total_order", header: "Total" },
      { accessorKey: "status_order", header: "Estado" },
      { accessorKey: "actions", header: "" }
    ];
    const salesCols = [
      { accessorKey: "title_product", header: "Producto" },
      { accessorKey: "qty_sale", header: "Cant" },
      { accessorKey: "price_sale", header: "Precio" },
      { accessorKey: "tax_sale", header: "IVA%" },
      { accessorKey: "discount_sale", header: "Dscto%" },
      { accessorKey: "subtotal_sale", header: "Subtotal" }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UInput = _sfc_main$6;
      const _component_UButton = _sfc_main$c;
      const _component_UCard = _sfc_main$1;
      const _component_UTabs = _sfc_main$2;
      const _component_UTable = _sfc_main$3;
      const _component_UBadge = _sfc_main$4;
      const _component_OrderReceiptModal = __nuxt_component_6;
      const _component_UModal = _sfc_main$5;
      const _component_UIcon = _sfc_main$h;
      const _component_UFormField = _sfc_main$7;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex items-center justify-between flex-wrap gap-4"><div><p class="text-sm text-slate-500 dark:text-slate-400">Análisis y estadísticas de órdenes y facturación.</p></div><div class="flex flex-wrap items-center gap-2">`);
      _push(ssrRenderComponent(_component_UInput, {
        type: "date",
        modelValue: startDate.value,
        "onUpdate:modelValue": ($event) => startDate.value = $event,
        size: "sm",
        class: "w-36"
      }, null, _parent));
      _push(`<span class="text-slate-500 text-xs">hasta</span>`);
      _push(ssrRenderComponent(_component_UInput, {
        type: "date",
        modelValue: endDate.value,
        "onUpdate:modelValue": ($event) => endDate.value = $event,
        size: "sm",
        class: "w-36"
      }, null, _parent));
      _push(ssrRenderComponent(_component_UButton, {
        color: "neutral",
        variant: "solid",
        icon: "i-lucide-filter",
        size: "sm",
        onClick: applyFilter
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Filtrar`);
          } else {
            return [
              createTextVNode("Filtrar")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UButton, {
        color: "primary",
        icon: "i-lucide-download",
        size: "sm",
        onClick: handleExportCSV
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<span class="hidden sm:inline"${_scopeId}>Exportar Excel</span><span class="sm:hidden"${_scopeId}>Excel</span>`);
          } else {
            return [
              createVNode("span", { class: "hidden sm:inline" }, "Exportar Excel"),
              createVNode("span", { class: "sm:hidden" }, "Excel")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">`);
      _push(ssrRenderComponent(_component_UCard, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="text-slate-500 text-sm font-semibold"${_scopeId}>Total Ventas (Bs)</div><div class="text-2xl font-bold text-green-600"${_scopeId}>${ssrInterpolate(formatCurrency(totalVentasBs.value))}</div>`);
          } else {
            return [
              createVNode("div", { class: "text-slate-500 text-sm font-semibold" }, "Total Ventas (Bs)"),
              createVNode("div", { class: "text-2xl font-bold text-green-600" }, toDisplayString(formatCurrency(totalVentasBs.value)), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UCard, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="text-slate-500 text-sm font-semibold"${_scopeId}>Órdenes</div><div class="text-2xl font-bold text-blue-600"${_scopeId}>${ssrInterpolate(orders.value.length)}</div>`);
          } else {
            return [
              createVNode("div", { class: "text-slate-500 text-sm font-semibold" }, "Órdenes"),
              createVNode("div", { class: "text-2xl font-bold text-blue-600" }, toDisplayString(orders.value.length), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UCard, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="text-slate-500 text-sm font-semibold"${_scopeId}>Ticket Promedio</div><div class="text-2xl font-bold text-amber-600"${_scopeId}>${ssrInterpolate(formatCurrency(avgOrder.value))}</div>`);
          } else {
            return [
              createVNode("div", { class: "text-slate-500 text-sm font-semibold" }, "Ticket Promedio"),
              createVNode("div", { class: "text-2xl font-bold text-amber-600" }, toDisplayString(formatCurrency(avgOrder.value)), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UCard, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="text-slate-500 text-sm font-semibold"${_scopeId}>Productos Vendidos</div><div class="text-2xl font-bold text-purple-600"${_scopeId}>${ssrInterpolate(totalProductsQty.value)}</div>`);
          } else {
            return [
              createVNode("div", { class: "text-slate-500 text-sm font-semibold" }, "Productos Vendidos"),
              createVNode("div", { class: "text-2xl font-bold text-purple-600" }, toDisplayString(totalProductsQty.value), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="grid grid-cols-1 md:grid-cols-2 gap-4">`);
      _push(ssrRenderComponent(_component_UCard, null, {
        header: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h3 class="font-semibold text-lg"${_scopeId}>Resumen Financiero</h3>`);
          } else {
            return [
              createVNode("h3", { class: "font-semibold text-lg" }, "Resumen Financiero")
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-3"${_scopeId}><div class="flex justify-between"${_scopeId}><span class="text-slate-600"${_scopeId}>Subtotal:</span><span class="font-medium"${_scopeId}>${ssrInterpolate(formatCurrency(sumSubtotal.value))}</span></div><div class="flex justify-between"${_scopeId}><span class="text-slate-600"${_scopeId}>Descuentos (-):</span><span class="font-medium text-red-500"${_scopeId}>${ssrInterpolate(formatCurrency(sumDiscount.value))}</span></div><div class="flex justify-between text-lg font-bold border-t pt-2 mt-2"${_scopeId}><span${_scopeId}>Total General:</span><span${_scopeId}>${ssrInterpolate(formatCurrency(totalVentasBs.value))}</span></div></div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-3" }, [
                createVNode("div", { class: "flex justify-between" }, [
                  createVNode("span", { class: "text-slate-600" }, "Subtotal:"),
                  createVNode("span", { class: "font-medium" }, toDisplayString(formatCurrency(sumSubtotal.value)), 1)
                ]),
                createVNode("div", { class: "flex justify-between" }, [
                  createVNode("span", { class: "text-slate-600" }, "Descuentos (-):"),
                  createVNode("span", { class: "font-medium text-red-500" }, toDisplayString(formatCurrency(sumDiscount.value)), 1)
                ]),
                createVNode("div", { class: "flex justify-between text-lg font-bold border-t pt-2 mt-2" }, [
                  createVNode("span", null, "Total General:"),
                  createVNode("span", null, toDisplayString(formatCurrency(totalVentasBs.value)), 1)
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UCard, null, {
        header: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h3 class="font-semibold text-lg"${_scopeId}>Evolución Diaria</h3>`);
          } else {
            return [
              createVNode("h3", { class: "font-semibold text-lg" }, "Evolución Diaria")
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="h-48"${_scopeId}>`);
            if (orders.value.length > 0) {
              _push2(ssrRenderComponent(unref(Bar), {
                data: salesByDayChartData.value,
                options: { responsive: true, maintainAspectRatio: false }
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<div class="h-full flex items-center justify-center text-gray-400"${_scopeId}>Sin datos para graficar</div>`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "h-48" }, [
                orders.value.length > 0 ? (openBlock(), createBlock(unref(Bar), {
                  key: 0,
                  data: salesByDayChartData.value,
                  options: { responsive: true, maintainAspectRatio: false }
                }, null, 8, ["data"])) : (openBlock(), createBlock("div", {
                  key: 1,
                  class: "h-full flex items-center justify-center text-gray-400"
                }, "Sin datos para graficar"))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="grid grid-cols-1 md:grid-cols-2 gap-4">`);
      _push(ssrRenderComponent(_component_UCard, null, {
        header: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h3 class="font-semibold text-lg"${_scopeId}>Top 10 Productos</h3>`);
          } else {
            return [
              createVNode("h3", { class: "font-semibold text-lg" }, "Top 10 Productos")
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="h-64 flex justify-center"${_scopeId}>`);
            if (sales.value.length > 0) {
              _push2(ssrRenderComponent(unref(Doughnut), {
                data: topProductsChartData.value,
                options: { responsive: true, maintainAspectRatio: false }
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<div class="h-full flex items-center justify-center text-gray-400"${_scopeId}>Sin datos</div>`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "h-64 flex justify-center" }, [
                sales.value.length > 0 ? (openBlock(), createBlock(unref(Doughnut), {
                  key: 0,
                  data: topProductsChartData.value,
                  options: { responsive: true, maintainAspectRatio: false }
                }, null, 8, ["data"])) : (openBlock(), createBlock("div", {
                  key: 1,
                  class: "h-full flex items-center justify-center text-gray-400"
                }, "Sin datos"))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (statsByOffice.value.length > 0) {
        _push(ssrRenderComponent(_component_UCard, null, {
          header: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<h3 class="font-semibold text-lg"${_scopeId}>Rendimiento por Sucursal</h3>`);
            } else {
              return [
                createVNode("h3", { class: "font-semibold text-lg" }, "Rendimiento por Sucursal")
              ];
            }
          }),
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="h-64"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Bar), {
                data: salesByOfficeChartData.value,
                options: { responsive: true, maintainAspectRatio: false, indexAxis: "x" }
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "h-64" }, [
                  createVNode(unref(Bar), {
                    data: salesByOfficeChartData.value,
                    options: { responsive: true, maintainAspectRatio: false, indexAxis: "x" }
                  }, null, 8, ["data"])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      _push(ssrRenderComponent(_component_UTabs, {
        items,
        class: "w-full mt-4"
      }, {
        ordenes: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_UCard, { class: "mt-4" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UTable, {
                    data: orders.value,
                    columns: orderCols,
                    loading: loading.value
                  }, {
                    "client-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`${ssrInterpolate(decodeStr(row.original.name_client))} ${ssrInterpolate(decodeStr(row.original.surname_client))}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(decodeStr(row.original.name_client)) + " " + toDisplayString(decodeStr(row.original.surname_client)), 1)
                        ];
                      }
                    }),
                    "subtotal_order-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`${ssrInterpolate(formatCurrency(parseFloat(row.original.subtotal_order)))}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(formatCurrency(parseFloat(row.original.subtotal_order))), 1)
                        ];
                      }
                    }),
                    "discount_order-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`-${ssrInterpolate(formatCurrency(parseFloat(row.original.discount_order)))}`);
                      } else {
                        return [
                          createTextVNode("-" + toDisplayString(formatCurrency(parseFloat(row.original.discount_order))), 1)
                        ];
                      }
                    }),
                    "total_order-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`<span class="font-bold text-green-600"${_scopeId3}>${ssrInterpolate(formatCurrency(parseFloat(row.original.total_order)))}</span>`);
                      } else {
                        return [
                          createVNode("span", { class: "font-bold text-green-600" }, toDisplayString(formatCurrency(parseFloat(row.original.total_order))), 1)
                        ];
                      }
                    }),
                    "status_order-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(ssrRenderComponent(_component_UBadge, {
                          color: row.original.status_order === "Completada" ? "success" : "warning",
                          variant: "subtle"
                        }, {
                          default: withCtx((_3, _push5, _parent5, _scopeId4) => {
                            if (_push5) {
                              _push5(`${ssrInterpolate(row.original.status_order)}`);
                            } else {
                              return [
                                createTextVNode(toDisplayString(row.original.status_order), 1)
                              ];
                            }
                          }),
                          _: 2
                        }, _parent4, _scopeId3));
                      } else {
                        return [
                          createVNode(_component_UBadge, {
                            color: row.original.status_order === "Completada" ? "success" : "warning",
                            variant: "subtle"
                          }, {
                            default: withCtx(() => [
                              createTextVNode(toDisplayString(row.original.status_order), 1)
                            ]),
                            _: 2
                          }, 1032, ["color"])
                        ];
                      }
                    }),
                    "actions-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`<div class="flex items-center gap-1 justify-end"${_scopeId3}>`);
                        _push4(ssrRenderComponent(_component_UButton, {
                          color: "neutral",
                          variant: "ghost",
                          icon: "i-lucide-file-text",
                          size: "sm",
                          onClick: ($event) => viewPdf(row.original.id_order)
                        }, {
                          default: withCtx((_3, _push5, _parent5, _scopeId4) => {
                            if (_push5) {
                              _push5(`PDF`);
                            } else {
                              return [
                                createTextVNode("PDF")
                              ];
                            }
                          }),
                          _: 2
                        }, _parent4, _scopeId3));
                        _push4(ssrRenderComponent(_component_UButton, {
                          color: "primary",
                          variant: "ghost",
                          icon: "i-lucide-receipt",
                          size: "sm",
                          title: "Comprobante de pago",
                          onClick: ($event) => openProof(row.original)
                        }, {
                          default: withCtx((_3, _push5, _parent5, _scopeId4) => {
                            if (_push5) {
                              _push5(`Comprobante`);
                            } else {
                              return [
                                createTextVNode("Comprobante")
                              ];
                            }
                          }),
                          _: 2
                        }, _parent4, _scopeId3));
                        _push4(`</div>`);
                      } else {
                        return [
                          createVNode("div", { class: "flex items-center gap-1 justify-end" }, [
                            createVNode(_component_UButton, {
                              color: "neutral",
                              variant: "ghost",
                              icon: "i-lucide-file-text",
                              size: "sm",
                              onClick: ($event) => viewPdf(row.original.id_order)
                            }, {
                              default: withCtx(() => [
                                createTextVNode("PDF")
                              ]),
                              _: 1
                            }, 8, ["onClick"]),
                            createVNode(_component_UButton, {
                              color: "primary",
                              variant: "ghost",
                              icon: "i-lucide-receipt",
                              size: "sm",
                              title: "Comprobante de pago",
                              onClick: ($event) => openProof(row.original)
                            }, {
                              default: withCtx(() => [
                                createTextVNode("Comprobante")
                              ]),
                              _: 1
                            }, 8, ["onClick"])
                          ])
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UTable, {
                      data: orders.value,
                      columns: orderCols,
                      loading: loading.value
                    }, {
                      "client-cell": withCtx(({ row }) => [
                        createTextVNode(toDisplayString(decodeStr(row.original.name_client)) + " " + toDisplayString(decodeStr(row.original.surname_client)), 1)
                      ]),
                      "subtotal_order-cell": withCtx(({ row }) => [
                        createTextVNode(toDisplayString(formatCurrency(parseFloat(row.original.subtotal_order))), 1)
                      ]),
                      "discount_order-cell": withCtx(({ row }) => [
                        createTextVNode("-" + toDisplayString(formatCurrency(parseFloat(row.original.discount_order))), 1)
                      ]),
                      "total_order-cell": withCtx(({ row }) => [
                        createVNode("span", { class: "font-bold text-green-600" }, toDisplayString(formatCurrency(parseFloat(row.original.total_order))), 1)
                      ]),
                      "status_order-cell": withCtx(({ row }) => [
                        createVNode(_component_UBadge, {
                          color: row.original.status_order === "Completada" ? "success" : "warning",
                          variant: "subtle"
                        }, {
                          default: withCtx(() => [
                            createTextVNode(toDisplayString(row.original.status_order), 1)
                          ]),
                          _: 2
                        }, 1032, ["color"])
                      ]),
                      "actions-cell": withCtx(({ row }) => [
                        createVNode("div", { class: "flex items-center gap-1 justify-end" }, [
                          createVNode(_component_UButton, {
                            color: "neutral",
                            variant: "ghost",
                            icon: "i-lucide-file-text",
                            size: "sm",
                            onClick: ($event) => viewPdf(row.original.id_order)
                          }, {
                            default: withCtx(() => [
                              createTextVNode("PDF")
                            ]),
                            _: 1
                          }, 8, ["onClick"]),
                          createVNode(_component_UButton, {
                            color: "primary",
                            variant: "ghost",
                            icon: "i-lucide-receipt",
                            size: "sm",
                            title: "Comprobante de pago",
                            onClick: ($event) => openProof(row.original)
                          }, {
                            default: withCtx(() => [
                              createTextVNode("Comprobante")
                            ]),
                            _: 1
                          }, 8, ["onClick"])
                        ])
                      ]),
                      _: 1
                    }, 8, ["data", "loading"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(_component_UCard, { class: "mt-4" }, {
                default: withCtx(() => [
                  createVNode(_component_UTable, {
                    data: orders.value,
                    columns: orderCols,
                    loading: loading.value
                  }, {
                    "client-cell": withCtx(({ row }) => [
                      createTextVNode(toDisplayString(decodeStr(row.original.name_client)) + " " + toDisplayString(decodeStr(row.original.surname_client)), 1)
                    ]),
                    "subtotal_order-cell": withCtx(({ row }) => [
                      createTextVNode(toDisplayString(formatCurrency(parseFloat(row.original.subtotal_order))), 1)
                    ]),
                    "discount_order-cell": withCtx(({ row }) => [
                      createTextVNode("-" + toDisplayString(formatCurrency(parseFloat(row.original.discount_order))), 1)
                    ]),
                    "total_order-cell": withCtx(({ row }) => [
                      createVNode("span", { class: "font-bold text-green-600" }, toDisplayString(formatCurrency(parseFloat(row.original.total_order))), 1)
                    ]),
                    "status_order-cell": withCtx(({ row }) => [
                      createVNode(_component_UBadge, {
                        color: row.original.status_order === "Completada" ? "success" : "warning",
                        variant: "subtle"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(toDisplayString(row.original.status_order), 1)
                        ]),
                        _: 2
                      }, 1032, ["color"])
                    ]),
                    "actions-cell": withCtx(({ row }) => [
                      createVNode("div", { class: "flex items-center gap-1 justify-end" }, [
                        createVNode(_component_UButton, {
                          color: "neutral",
                          variant: "ghost",
                          icon: "i-lucide-file-text",
                          size: "sm",
                          onClick: ($event) => viewPdf(row.original.id_order)
                        }, {
                          default: withCtx(() => [
                            createTextVNode("PDF")
                          ]),
                          _: 1
                        }, 8, ["onClick"]),
                        createVNode(_component_UButton, {
                          color: "primary",
                          variant: "ghost",
                          icon: "i-lucide-receipt",
                          size: "sm",
                          title: "Comprobante de pago",
                          onClick: ($event) => openProof(row.original)
                        }, {
                          default: withCtx(() => [
                            createTextVNode("Comprobante")
                          ]),
                          _: 1
                        }, 8, ["onClick"])
                      ])
                    ]),
                    _: 1
                  }, 8, ["data", "loading"])
                ]),
                _: 1
              })
            ];
          }
        }),
        ventas: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_UCard, { class: "mt-4" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UTable, {
                    data: sales.value,
                    columns: salesCols,
                    loading: loading.value
                  }, {
                    "title_product-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`<span class="font-medium"${_scopeId3}>${ssrInterpolate(decodeStr(row.original.title_product))}</span>`);
                      } else {
                        return [
                          createVNode("span", { class: "font-medium" }, toDisplayString(decodeStr(row.original.title_product)), 1)
                        ];
                      }
                    }),
                    "price_sale-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`${ssrInterpolate(formatCurrency(parseFloat(row.original.price_sale)))}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(formatCurrency(parseFloat(row.original.price_sale))), 1)
                        ];
                      }
                    }),
                    "tax_sale-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`${ssrInterpolate(row.original.tax_sale)}%`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(row.original.tax_sale) + "%", 1)
                        ];
                      }
                    }),
                    "discount_sale-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`${ssrInterpolate(row.original.discount_sale)}%`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(row.original.discount_sale) + "%", 1)
                        ];
                      }
                    }),
                    "subtotal_sale-cell": withCtx(({ row }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`<span class="font-bold"${_scopeId3}>${ssrInterpolate(formatCurrency(parseFloat(row.original.subtotal_sale)))}</span>`);
                      } else {
                        return [
                          createVNode("span", { class: "font-bold" }, toDisplayString(formatCurrency(parseFloat(row.original.subtotal_sale))), 1)
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UTable, {
                      data: sales.value,
                      columns: salesCols,
                      loading: loading.value
                    }, {
                      "title_product-cell": withCtx(({ row }) => [
                        createVNode("span", { class: "font-medium" }, toDisplayString(decodeStr(row.original.title_product)), 1)
                      ]),
                      "price_sale-cell": withCtx(({ row }) => [
                        createTextVNode(toDisplayString(formatCurrency(parseFloat(row.original.price_sale))), 1)
                      ]),
                      "tax_sale-cell": withCtx(({ row }) => [
                        createTextVNode(toDisplayString(row.original.tax_sale) + "%", 1)
                      ]),
                      "discount_sale-cell": withCtx(({ row }) => [
                        createTextVNode(toDisplayString(row.original.discount_sale) + "%", 1)
                      ]),
                      "subtotal_sale-cell": withCtx(({ row }) => [
                        createVNode("span", { class: "font-bold" }, toDisplayString(formatCurrency(parseFloat(row.original.subtotal_sale))), 1)
                      ]),
                      _: 1
                    }, 8, ["data", "loading"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(_component_UCard, { class: "mt-4" }, {
                default: withCtx(() => [
                  createVNode(_component_UTable, {
                    data: sales.value,
                    columns: salesCols,
                    loading: loading.value
                  }, {
                    "title_product-cell": withCtx(({ row }) => [
                      createVNode("span", { class: "font-medium" }, toDisplayString(decodeStr(row.original.title_product)), 1)
                    ]),
                    "price_sale-cell": withCtx(({ row }) => [
                      createTextVNode(toDisplayString(formatCurrency(parseFloat(row.original.price_sale))), 1)
                    ]),
                    "tax_sale-cell": withCtx(({ row }) => [
                      createTextVNode(toDisplayString(row.original.tax_sale) + "%", 1)
                    ]),
                    "discount_sale-cell": withCtx(({ row }) => [
                      createTextVNode(toDisplayString(row.original.discount_sale) + "%", 1)
                    ]),
                    "subtotal_sale-cell": withCtx(({ row }) => [
                      createVNode("span", { class: "font-bold" }, toDisplayString(formatCurrency(parseFloat(row.original.subtotal_sale))), 1)
                    ]),
                    _: 1
                  }, 8, ["data", "loading"])
                ]),
                _: 1
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_OrderReceiptModal, {
        isOpen: isReceiptModalOpen.value,
        "onUpdate:isOpen": ($event) => isReceiptModalOpen.value = $event,
        "order-id": selectedOrderId.value,
        onClose: ($event) => selectedOrderId.value = null
      }, null, _parent));
      _push(ssrRenderComponent(_component_UModal, {
        open: proofModal.value,
        "onUpdate:open": ($event) => proofModal.value = $event,
        title: `Comprobante de pago · Orden ${proofOrder.value?.transaction_order || ""}`
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4"${_scopeId}>`);
            if (proofLoading.value) {
              _push2(`<div class="py-6 flex justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-loader-2",
                class: "w-5 h-5 animate-spin text-slate-400"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else if (proofPayments.value.length) {
              _push2(`<div class="space-y-2"${_scopeId}><!--[-->`);
              ssrRenderList(proofPayments.value, (p) => {
                _push2(`<div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 rounded-lg p-3"${_scopeId}><div class="flex-1 min-w-0"${_scopeId}><p class="text-sm font-medium text-slate-700 dark:text-white capitalize"${_scopeId}>${ssrInterpolate(p.method_payment || "Pago")} `);
                if (p.amount_payment) {
                  _push2(`<span class="text-slate-400 font-normal"${_scopeId}>· Bs.${ssrInterpolate(parseFloat(p.amount_payment).toFixed(2))}</span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</p>`);
                if (p.reference_payment) {
                  _push2(`<p class="text-xs text-slate-500 truncate"${_scopeId}>Ref: ${ssrInterpolate(p.reference_payment)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<p class="text-xs text-slate-400"${_scopeId}>${ssrInterpolate(p.date_created_payment)}`);
                if (p.name_admin) {
                  _push2(`<span${_scopeId}> · ${ssrInterpolate(p.name_admin)}</span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</p></div>`);
                if (p.file_payment) {
                  _push2(`<a${ssrRenderAttr("href", `/${p.file_payment}`)} target="_blank" class="text-primary-600 hover:text-primary-700 text-xs font-medium flex items-center gap-1"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UIcon, {
                    name: "i-lucide-eye",
                    class: "w-4 h-4"
                  }, null, _parent2, _scopeId));
                  _push2(` Ver </a>`);
                } else {
                  _push2(`<span class="text-xs text-amber-500 flex items-center gap-1"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UIcon, {
                    name: "i-lucide-alert-triangle",
                    class: "w-3.5 h-3.5"
                  }, null, _parent2, _scopeId));
                  _push2(` Sin archivo </span>`);
                }
                _push2(ssrRenderComponent(_component_UButton, {
                  color: "neutral",
                  variant: "ghost",
                  size: "xs",
                  icon: "i-lucide-upload",
                  title: "Reemplazar archivo",
                  disabled: !newProofFile.value,
                  onClick: ($event) => uploadProof(p.id_sale_payment)
                }, null, _parent2, _scopeId));
                _push2(ssrRenderComponent(_component_UButton, {
                  color: "error",
                  variant: "ghost",
                  size: "xs",
                  icon: "i-lucide-trash-2",
                  title: "Eliminar",
                  onClick: ($event) => deleteProof(p.id_sale_payment)
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<p class="text-sm text-slate-500 text-center py-4"${_scopeId}>Esta venta aún no tiene comprobante de respaldo.</p>`);
            }
            _push2(`<div class="border-t border-slate-200 dark:border-slate-700 pt-3 space-y-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UFormField, {
              label: "Adjuntar comprobante (imagen/PDF)",
              help: "Máx 5MB. Use el botón ↑ de una fila para reemplazar el archivo de ese pago."
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
                      onChange: onNewProofChange
                    }, null, 32)
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, { label: "Referencia (opcional)" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: newProofRef.value,
                    "onUpdate:modelValue": ($event) => newProofRef.value = $event,
                    placeholder: "N° de transacción / nota",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: newProofRef.value,
                      "onUpdate:modelValue": ($event) => newProofRef.value = $event,
                      placeholder: "N° de transacción / nota",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            if (newProofFile.value) {
              _push2(`<p class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-paperclip",
                class: "w-3.5 h-3.5"
              }, null, _parent2, _scopeId));
              _push2(` ${ssrInterpolate(newProofFile.value.name)}</p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4" }, [
                proofLoading.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "py-6 flex justify-center"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "w-5 h-5 animate-spin text-slate-400"
                  })
                ])) : proofPayments.value.length ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "space-y-2"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(proofPayments.value, (p) => {
                    return openBlock(), createBlock("div", {
                      key: p.id_sale_payment,
                      class: "flex items-center gap-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 rounded-lg p-3"
                    }, [
                      createVNode("div", { class: "flex-1 min-w-0" }, [
                        createVNode("p", { class: "text-sm font-medium text-slate-700 dark:text-white capitalize" }, [
                          createTextVNode(toDisplayString(p.method_payment || "Pago") + " ", 1),
                          p.amount_payment ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "text-slate-400 font-normal"
                          }, "· Bs." + toDisplayString(parseFloat(p.amount_payment).toFixed(2)), 1)) : createCommentVNode("", true)
                        ]),
                        p.reference_payment ? (openBlock(), createBlock("p", {
                          key: 0,
                          class: "text-xs text-slate-500 truncate"
                        }, "Ref: " + toDisplayString(p.reference_payment), 1)) : createCommentVNode("", true),
                        createVNode("p", { class: "text-xs text-slate-400" }, [
                          createTextVNode(toDisplayString(p.date_created_payment), 1),
                          p.name_admin ? (openBlock(), createBlock("span", { key: 0 }, " · " + toDisplayString(p.name_admin), 1)) : createCommentVNode("", true)
                        ])
                      ]),
                      p.file_payment ? (openBlock(), createBlock("a", {
                        key: 0,
                        href: `/${p.file_payment}`,
                        target: "_blank",
                        class: "text-primary-600 hover:text-primary-700 text-xs font-medium flex items-center gap-1"
                      }, [
                        createVNode(_component_UIcon, {
                          name: "i-lucide-eye",
                          class: "w-4 h-4"
                        }),
                        createTextVNode(" Ver ")
                      ], 8, ["href"])) : (openBlock(), createBlock("span", {
                        key: 1,
                        class: "text-xs text-amber-500 flex items-center gap-1"
                      }, [
                        createVNode(_component_UIcon, {
                          name: "i-lucide-alert-triangle",
                          class: "w-3.5 h-3.5"
                        }),
                        createTextVNode(" Sin archivo ")
                      ])),
                      createVNode(_component_UButton, {
                        color: "neutral",
                        variant: "ghost",
                        size: "xs",
                        icon: "i-lucide-upload",
                        title: "Reemplazar archivo",
                        disabled: !newProofFile.value,
                        onClick: ($event) => uploadProof(p.id_sale_payment)
                      }, null, 8, ["disabled", "onClick"]),
                      createVNode(_component_UButton, {
                        color: "error",
                        variant: "ghost",
                        size: "xs",
                        icon: "i-lucide-trash-2",
                        title: "Eliminar",
                        onClick: ($event) => deleteProof(p.id_sale_payment)
                      }, null, 8, ["onClick"])
                    ]);
                  }), 128))
                ])) : (openBlock(), createBlock("p", {
                  key: 2,
                  class: "text-sm text-slate-500 text-center py-4"
                }, "Esta venta aún no tiene comprobante de respaldo.")),
                createVNode("div", { class: "border-t border-slate-200 dark:border-slate-700 pt-3 space-y-2" }, [
                  createVNode(_component_UFormField, {
                    label: "Adjuntar comprobante (imagen/PDF)",
                    help: "Máx 5MB. Use el botón ↑ de una fila para reemplazar el archivo de ese pago."
                  }, {
                    default: withCtx(() => [
                      createVNode("input", {
                        type: "file",
                        accept: "image/jpeg,image/png,image/webp,application/pdf",
                        class: "block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-300",
                        onChange: onNewProofChange
                      }, null, 32)
                    ]),
                    _: 1
                  }),
                  createVNode(_component_UFormField, { label: "Referencia (opcional)" }, {
                    default: withCtx(() => [
                      createVNode(_component_UInput, {
                        modelValue: newProofRef.value,
                        "onUpdate:modelValue": ($event) => newProofRef.value = $event,
                        placeholder: "N° de transacción / nota",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"])
                    ]),
                    _: 1
                  }),
                  newProofFile.value ? (openBlock(), createBlock("p", {
                    key: 0,
                    class: "text-xs text-green-600 dark:text-green-400 flex items-center gap-1"
                  }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-paperclip",
                      class: "w-3.5 h-3.5"
                    }),
                    createTextVNode(" " + toDisplayString(newProofFile.value.name), 1)
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
              onClick: ($event) => proofModal.value = false
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
            _push2(ssrRenderComponent(_component_UButton, {
              color: "primary",
              icon: "i-lucide-plus",
              loading: proofUploading.value,
              onClick: ($event) => uploadProof()
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Adjuntar comprobante`);
                } else {
                  return [
                    createTextVNode("Adjuntar comprobante")
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
                  onClick: ($event) => proofModal.value = false
                }, {
                  default: withCtx(() => [
                    createTextVNode("Cerrar")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(_component_UButton, {
                  color: "primary",
                  icon: "i-lucide-plus",
                  loading: proofUploading.value,
                  onClick: ($event) => uploadProof()
                }, {
                  default: withCtx(() => [
                    createTextVNode("Adjuntar comprobante")
                  ]),
                  _: 1
                }, 8, ["loading", "onClick"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/reportes.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=reportes-39whbxKY.mjs.map
