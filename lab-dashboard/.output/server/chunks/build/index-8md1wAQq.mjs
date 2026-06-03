import { K as useAuthStore, g as _sfc_main$h, H as navigateTo, i as _sfc_main$c } from './server.mjs';
import { _ as _sfc_main$1 } from './Card-Dj8zIcA3.mjs';
import { _ as _sfc_main$2 } from './Table-BGh52cFP.mjs';
import { defineComponent, ref, computed, mergeProps, unref, withCtx, createVNode, toDisplayString, openBlock, createBlock, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderList, ssrInterpolate, ssrRenderClass, ssrRenderComponent } from 'vue/server-renderer';
import '../_/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:url';
import '@iconify/utils';
import 'node:crypto';
import 'consola';
import 'node:path';
import 'pinia';
import 'vue-router';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    const loading = ref(true);
    const totalMaterials = ref(0);
    const totalInProcess = ref(0);
    const qualityChecks = ref(0);
    const finalProductsStock = ref(0);
    const recentActivities = ref([]);
    const todaysOrders = ref([]);
    const todaysSalesAmount = ref(0);
    const todaysCashStatus = ref(null);
    const openCashRegisters = ref([]);
    const pendingRequests = ref([]);
    (/* @__PURE__ */ new Date()).toISOString().split("T")[0];
    const role = auth.role;
    const isSuperAdmin = role === "superadmin" || role === "admin";
    const isCashier = role === "cajero" || role === "vendedor";
    const isDispatcher = role === "despachador";
    const isLab = role === "lab_admin" || role === "lab_worker" || !isSuperAdmin && !isCashier && !isDispatcher;
    const kpis = computed(() => [
      { label: "Materias Primas", val: String(totalMaterials.value), icon: "i-lucide-droplet", color: "text-blue-500 bg-blue-500/10" },
      { label: "En Proceso", val: String(totalInProcess.value), icon: "i-lucide-cog", color: "text-amber-500 bg-amber-500/10" },
      { label: "Ensayos Calidad", val: String(qualityChecks.value), icon: "i-lucide-shield-check", color: "text-emerald-500 bg-emerald-500/10" },
      { label: "Productos Finales", val: `${finalProductsStock.value} u.`, icon: "i-lucide-boxes", color: "text-indigo-500 bg-indigo-500/10" }
    ]);
    function formatCurrency(val) {
      return new Intl.NumberFormat("es-BO", { style: "currency", currency: "BOB" }).format(val);
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      const _component_UCard = _sfc_main$1;
      const _component_UTable = _sfc_main$2;
      const _component_UButton = _sfc_main$c;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}>`);
      if (unref(isLab)) {
        _push(`<!--[--><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"><!--[-->`);
        ssrRenderList(kpis.value, (kpi) => {
          _push(`<div class="bg-white dark:bg-slate-950/40 backdrop-blur border border-slate-200 dark:border-slate-800/80 p-5 rounded-xl flex items-center justify-between shadow-sm"><div><span class="text-xs text-slate-500 uppercase tracking-wider font-bold">${ssrInterpolate(kpi.label)}</span><h4 class="text-2xl font-black mt-1">${ssrInterpolate(kpi.val)}</h4></div><div class="${ssrRenderClass(["w-12 h-12 rounded-xl flex items-center justify-center", kpi.color])}">`);
          _push(ssrRenderComponent(_component_UIcon, {
            name: kpi.icon,
            class: "w-6 h-6"
          }, null, _parent));
          _push(`</div></div>`);
        });
        _push(`<!--]--></div><div class="bg-white dark:bg-slate-950/40 border p-6 rounded-xl space-y-4 shadow-sm"><h3 class="font-bold flex items-center gap-2">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-activity",
          class: "text-green-500"
        }, null, _parent));
        _push(` Actividad Reciente en Planta </h3>`);
        if (recentActivities.value.length === 0) {
          _push(`<div class="text-sm text-center py-4">No hay datos</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><!--]-->`);
      } else if (unref(isCashier)) {
        _push(`<!--[--><div class="grid grid-cols-1 md:grid-cols-3 gap-4">`);
        _push(ssrRenderComponent(_component_UCard, { class: "bg-gradient-to-r from-green-500 to-green-600 text-white border-0 shadow-lg" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex justify-between items-center"${_scopeId}><div${_scopeId}><p class="text-green-100 text-sm font-semibold uppercase"${_scopeId}>Ventas de Hoy</p><h2 class="text-3xl font-black mt-1"${_scopeId}>${ssrInterpolate(formatCurrency(todaysSalesAmount.value))}</h2><p class="text-sm mt-1"${_scopeId}>${ssrInterpolate(todaysOrders.value.length)} órdenes procesadas</p></div>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-banknote",
                class: "w-12 h-12 text-white/50"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "flex justify-between items-center" }, [
                  createVNode("div", null, [
                    createVNode("p", { class: "text-green-100 text-sm font-semibold uppercase" }, "Ventas de Hoy"),
                    createVNode("h2", { class: "text-3xl font-black mt-1" }, toDisplayString(formatCurrency(todaysSalesAmount.value)), 1),
                    createVNode("p", { class: "text-sm mt-1" }, toDisplayString(todaysOrders.value.length) + " órdenes procesadas", 1)
                  ]),
                  createVNode(_component_UIcon, {
                    name: "i-lucide-banknote",
                    class: "w-12 h-12 text-white/50"
                  })
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_UCard, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex justify-between items-center h-full"${_scopeId}><div${_scopeId}><p class="text-slate-500 text-sm font-semibold uppercase"${_scopeId}>Estado de Caja</p>`);
              if (loading.value) {
                _push2(`<div class="mt-2 text-slate-400"${_scopeId}>Verificando...</div>`);
              } else if (!todaysCashStatus.value || todaysCashStatus.value.status_cash == 0) {
                _push2(`<div class="mt-1"${_scopeId}><span class="text-2xl font-black text-rose-500"${_scopeId}>CERRADA</span></div>`);
              } else {
                _push2(`<div class="mt-1"${_scopeId}><span class="text-2xl font-black text-emerald-500"${_scopeId}>ABIERTA</span><p class="text-xs mt-1 text-slate-500"${_scopeId}>Apertura: ${ssrInterpolate(formatCurrency(parseFloat(todaysCashStatus.value.opening_balance_cash)))}</p></div>`);
              }
              _push2(`</div>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-wallet",
                class: !todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? "w-10 h-10 text-rose-200" : "w-10 h-10 text-emerald-200"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "flex justify-between items-center h-full" }, [
                  createVNode("div", null, [
                    createVNode("p", { class: "text-slate-500 text-sm font-semibold uppercase" }, "Estado de Caja"),
                    loading.value ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "mt-2 text-slate-400"
                    }, "Verificando...")) : !todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? (openBlock(), createBlock("div", {
                      key: 1,
                      class: "mt-1"
                    }, [
                      createVNode("span", { class: "text-2xl font-black text-rose-500" }, "CERRADA")
                    ])) : (openBlock(), createBlock("div", {
                      key: 2,
                      class: "mt-1"
                    }, [
                      createVNode("span", { class: "text-2xl font-black text-emerald-500" }, "ABIERTA"),
                      createVNode("p", { class: "text-xs mt-1 text-slate-500" }, "Apertura: " + toDisplayString(formatCurrency(parseFloat(todaysCashStatus.value.opening_balance_cash))), 1)
                    ]))
                  ]),
                  createVNode(_component_UIcon, {
                    name: "i-lucide-wallet",
                    class: !todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? "w-10 h-10 text-rose-200" : "w-10 h-10 text-emerald-200"
                  }, null, 8, ["class"])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_UCard, {
          class: "flex items-center justify-center cursor-pointer hover:bg-slate-50 transition-colors",
          onClick: ($event) => ("navigateTo" in _ctx ? _ctx.navigateTo : unref(navigateTo))("/pos")
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="text-center"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-monitor-smartphone",
                class: "w-10 h-10 text-blue-500 mx-auto mb-2"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-bold text-lg text-slate-800"${_scopeId}>Ir al Punto de Venta (POS)</span></div>`);
            } else {
              return [
                createVNode("div", { class: "text-center" }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-monitor-smartphone",
                    class: "w-10 h-10 text-blue-500 mx-auto mb-2"
                  }),
                  createVNode("span", { class: "font-bold text-lg text-slate-800" }, "Ir al Punto de Venta (POS)")
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
        _push(ssrRenderComponent(_component_UCard, null, {
          header: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<h3 class="font-bold border-b pb-2"${_scopeId}>Últimas Ventas (Hoy)</h3>`);
            } else {
              return [
                createVNode("h3", { class: "font-bold border-b pb-2" }, "Últimas Ventas (Hoy)")
              ];
            }
          }),
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              if (todaysOrders.value.length === 0) {
                _push2(`<div class="text-center text-slate-500 py-4"${_scopeId}>No se han registrado ventas hoy.</div>`);
              } else {
                _push2(ssrRenderComponent(_component_UTable, {
                  data: todaysOrders.value.slice(0, 5),
                  columns: [{ accessorKey: "transaction_order", header: "ID Transacción" }, { accessorKey: "client", header: "Cliente" }, { accessorKey: "total_order", header: "Total" }]
                }, {
                  "client-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`${ssrInterpolate(decodeURIComponent(row.original.name_client).replace(/\+/g, " "))}`);
                    } else {
                      return [
                        createTextVNode(toDisplayString(decodeURIComponent(row.original.name_client).replace(/\+/g, " ")), 1)
                      ];
                    }
                  }),
                  "total_order-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<span class="font-bold text-green-600"${_scopeId2}>${ssrInterpolate(formatCurrency(parseFloat(row.original.total_order)))}</span>`);
                    } else {
                      return [
                        createVNode("span", { class: "font-bold text-green-600" }, toDisplayString(formatCurrency(parseFloat(row.original.total_order))), 1)
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              }
            } else {
              return [
                todaysOrders.value.length === 0 ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "text-center text-slate-500 py-4"
                }, "No se han registrado ventas hoy.")) : (openBlock(), createBlock(_component_UTable, {
                  key: 1,
                  data: todaysOrders.value.slice(0, 5),
                  columns: [{ accessorKey: "transaction_order", header: "ID Transacción" }, { accessorKey: "client", header: "Cliente" }, { accessorKey: "total_order", header: "Total" }]
                }, {
                  "client-cell": withCtx(({ row }) => [
                    createTextVNode(toDisplayString(decodeURIComponent(row.original.name_client).replace(/\+/g, " ")), 1)
                  ]),
                  "total_order-cell": withCtx(({ row }) => [
                    createVNode("span", { class: "font-bold text-green-600" }, toDisplayString(formatCurrency(parseFloat(row.original.total_order))), 1)
                  ]),
                  _: 1
                }, 8, ["data"]))
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<!--]-->`);
      } else if (unref(isSuperAdmin)) {
        _push(`<!--[--><div class="grid grid-cols-1 md:grid-cols-4 gap-4">`);
        _push(ssrRenderComponent(_component_UCard, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<p class="text-slate-500 text-sm font-bold uppercase"${_scopeId}>Ventas Globales (Hoy)</p><h2 class="text-2xl font-black text-green-600 mt-1"${_scopeId}>${ssrInterpolate(formatCurrency(todaysSalesAmount.value))}</h2><p class="text-xs text-slate-400 mt-1"${_scopeId}>${ssrInterpolate(todaysOrders.value.length)} transacciones en toda la red</p>`);
            } else {
              return [
                createVNode("p", { class: "text-slate-500 text-sm font-bold uppercase" }, "Ventas Globales (Hoy)"),
                createVNode("h2", { class: "text-2xl font-black text-green-600 mt-1" }, toDisplayString(formatCurrency(todaysSalesAmount.value)), 1),
                createVNode("p", { class: "text-xs text-slate-400 mt-1" }, toDisplayString(todaysOrders.value.length) + " transacciones en toda la red", 1)
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_UCard, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<p class="text-slate-500 text-sm font-bold uppercase"${_scopeId}>Cajas Abiertas</p><h2 class="text-2xl font-black text-blue-600 mt-1"${_scopeId}>${ssrInterpolate(openCashRegisters.value.length)}</h2><p class="text-xs text-slate-400 mt-1"${_scopeId}>sucursales operando activamente</p>`);
            } else {
              return [
                createVNode("p", { class: "text-slate-500 text-sm font-bold uppercase" }, "Cajas Abiertas"),
                createVNode("h2", { class: "text-2xl font-black text-blue-600 mt-1" }, toDisplayString(openCashRegisters.value.length), 1),
                createVNode("p", { class: "text-xs text-slate-400 mt-1" }, "sucursales operando activamente")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div><div class="grid grid-cols-1 lg:grid-cols-2 gap-4">`);
        _push(ssrRenderComponent(_component_UCard, null, {
          header: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex justify-between items-center"${_scopeId}><h3 class="font-bold"${_scopeId}>Sucursales Activas</h3>`);
              _push2(ssrRenderComponent(_component_UButton, {
                to: "/reportes",
                size: "xs",
                color: "gray",
                variant: "ghost",
                icon: "i-lucide-external-link"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Ver Reportes`);
                  } else {
                    return [
                      createTextVNode("Ver Reportes")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "flex justify-between items-center" }, [
                  createVNode("h3", { class: "font-bold" }, "Sucursales Activas"),
                  createVNode(_component_UButton, {
                    to: "/reportes",
                    size: "xs",
                    color: "gray",
                    variant: "ghost",
                    icon: "i-lucide-external-link"
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Ver Reportes")
                    ]),
                    _: 1
                  })
                ])
              ];
            }
          }),
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              if (openCashRegisters.value.length === 0) {
                _push2(`<div class="text-slate-500 text-center py-4"${_scopeId}>No hay cajas abiertas.</div>`);
              } else {
                _push2(ssrRenderComponent(_component_UTable, {
                  data: openCashRegisters.value,
                  columns: [{ accessorKey: "title_office", header: "Sucursal" }, { accessorKey: "opening_balance_cash", header: "Saldo Inicial" }]
                }, {
                  "title_office-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<span class="font-semibold"${_scopeId2}>${ssrInterpolate(decodeURIComponent(row.original.title_office).replace(/\+/g, " "))}</span>`);
                    } else {
                      return [
                        createVNode("span", { class: "font-semibold" }, toDisplayString(decodeURIComponent(row.original.title_office).replace(/\+/g, " ")), 1)
                      ];
                    }
                  }),
                  "opening_balance_cash-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`${ssrInterpolate(formatCurrency(parseFloat(row.original.opening_balance_cash)))}`);
                    } else {
                      return [
                        createTextVNode(toDisplayString(formatCurrency(parseFloat(row.original.opening_balance_cash))), 1)
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              }
            } else {
              return [
                openCashRegisters.value.length === 0 ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "text-slate-500 text-center py-4"
                }, "No hay cajas abiertas.")) : (openBlock(), createBlock(_component_UTable, {
                  key: 1,
                  data: openCashRegisters.value,
                  columns: [{ accessorKey: "title_office", header: "Sucursal" }, { accessorKey: "opening_balance_cash", header: "Saldo Inicial" }]
                }, {
                  "title_office-cell": withCtx(({ row }) => [
                    createVNode("span", { class: "font-semibold" }, toDisplayString(decodeURIComponent(row.original.title_office).replace(/\+/g, " ")), 1)
                  ]),
                  "opening_balance_cash-cell": withCtx(({ row }) => [
                    createTextVNode(toDisplayString(formatCurrency(parseFloat(row.original.opening_balance_cash))), 1)
                  ]),
                  _: 1
                }, 8, ["data"]))
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_UCard, null, {
          header: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<h3 class="font-bold"${_scopeId}>Últimas Transacciones Globales</h3>`);
            } else {
              return [
                createVNode("h3", { class: "font-bold" }, "Últimas Transacciones Globales")
              ];
            }
          }),
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              if (todaysOrders.value.length === 0) {
                _push2(`<div class="text-slate-500 text-center py-4"${_scopeId}>Sin datos de ventas.</div>`);
              } else {
                _push2(ssrRenderComponent(_component_UTable, {
                  data: todaysOrders.value.slice(0, 5),
                  columns: [{ accessorKey: "date_order", header: "Fecha/Hora" }, { accessorKey: "total_order", header: "Monto" }]
                }, {
                  "date_order-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<span class="text-xs text-slate-500"${_scopeId2}>${ssrInterpolate(row.original.date_order.split(" ")[1])}</span>`);
                    } else {
                      return [
                        createVNode("span", { class: "text-xs text-slate-500" }, toDisplayString(row.original.date_order.split(" ")[1]), 1)
                      ];
                    }
                  }),
                  "total_order-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<span class="font-semibold text-green-600"${_scopeId2}>${ssrInterpolate(formatCurrency(parseFloat(row.original.total_order)))}</span>`);
                    } else {
                      return [
                        createVNode("span", { class: "font-semibold text-green-600" }, toDisplayString(formatCurrency(parseFloat(row.original.total_order))), 1)
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              }
            } else {
              return [
                todaysOrders.value.length === 0 ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "text-slate-500 text-center py-4"
                }, "Sin datos de ventas.")) : (openBlock(), createBlock(_component_UTable, {
                  key: 1,
                  data: todaysOrders.value.slice(0, 5),
                  columns: [{ accessorKey: "date_order", header: "Fecha/Hora" }, { accessorKey: "total_order", header: "Monto" }]
                }, {
                  "date_order-cell": withCtx(({ row }) => [
                    createVNode("span", { class: "text-xs text-slate-500" }, toDisplayString(row.original.date_order.split(" ")[1]), 1)
                  ]),
                  "total_order-cell": withCtx(({ row }) => [
                    createVNode("span", { class: "font-semibold text-green-600" }, toDisplayString(formatCurrency(parseFloat(row.original.total_order))), 1)
                  ]),
                  _: 1
                }, 8, ["data"]))
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div><!--]-->`);
      } else if (isDispatcher) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-3 gap-4">`);
        _push(ssrRenderComponent(_component_UCard, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex justify-between items-center"${_scopeId}><div${_scopeId}><p class="text-amber-600 text-sm font-bold uppercase"${_scopeId}>Solicitudes Pendientes</p><h2 class="text-4xl font-black mt-1"${_scopeId}>${ssrInterpolate(pendingRequests.value.length)}</h2></div>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-clipboard-copy",
                class: "w-12 h-12 text-amber-100"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
              _push2(ssrRenderComponent(_component_UButton, {
                to: "/despachos",
                class: "w-full mt-4 justify-center",
                color: "amber"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Atender Solicitudes`);
                  } else {
                    return [
                      createTextVNode("Atender Solicitudes")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode("div", { class: "flex justify-between items-center" }, [
                  createVNode("div", null, [
                    createVNode("p", { class: "text-amber-600 text-sm font-bold uppercase" }, "Solicitudes Pendientes"),
                    createVNode("h2", { class: "text-4xl font-black mt-1" }, toDisplayString(pendingRequests.value.length), 1)
                  ]),
                  createVNode(_component_UIcon, {
                    name: "i-lucide-clipboard-copy",
                    class: "w-12 h-12 text-amber-100"
                  })
                ]),
                createVNode(_component_UButton, {
                  to: "/despachos",
                  class: "w-full mt-4 justify-center",
                  color: "amber"
                }, {
                  default: withCtx(() => [
                    createTextVNode("Atender Solicitudes")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-8md1wAQq.mjs.map
