import { I as useAuthStore, g as _sfc_main$h, _ as __nuxt_component_0$1, i as _sfc_main$c } from './server.mjs';
import { _ as _sfc_main$1 } from './Card-BV4DIQLA.mjs';
import { _ as _sfc_main$2 } from './Table-EJSLuWs0.mjs';
import { _ as _sfc_main$3 } from './Badge-LaytOPGg.mjs';
import { defineComponent, ref, computed, mergeProps, withCtx, createVNode, toDisplayString, createTextVNode, openBlock, createBlock, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderClass } from 'vue/server-renderer';
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
    function getLocalDate() {
      const now = /* @__PURE__ */ new Date();
      return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
    }
    const auth = useAuthStore();
    const loading = ref(true);
    getLocalDate();
    const role = computed(() => auth.role);
    const isSuperAdmin = computed(() => role.value === "superadmin" || role.value === "admin");
    const isCashier = computed(() => role.value === "cajero" || role.value === "vendedor" || role.value === "caja");
    const isDispatcher = computed(() => role.value === "despachador");
    const isLab = computed(() => !isSuperAdmin.value && !isCashier.value && !isDispatcher.value);
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
    const labKpis = computed(() => [
      { label: "Materias Primas", val: String(totalMaterials.value), icon: "i-lucide-droplet", color: "text-blue-500 bg-blue-50 dark:bg-blue-500/10" },
      { label: "En Proceso", val: String(totalInProcess.value), icon: "i-lucide-cog", color: "text-amber-500 bg-amber-50 dark:bg-amber-500/10" },
      { label: "Ensayos Calidad", val: String(qualityChecks.value), icon: "i-lucide-shield-check", color: "text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10" },
      { label: "Productos Finales", val: `${finalProductsStock.value} u.`, icon: "i-lucide-boxes", color: "text-indigo-500 bg-indigo-50 dark:bg-indigo-500/10" }
    ]);
    const activityStatusColor = {
      success: "bg-emerald-500",
      warning: "bg-amber-500",
      info: "bg-blue-500"
    };
    function formatCurrency(val) {
      return new Intl.NumberFormat("es-BO", { style: "currency", currency: "BOB" }).format(val);
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      const _component_NuxtLink = __nuxt_component_0$1;
      const _component_UCard = _sfc_main$1;
      const _component_UButton = _sfc_main$c;
      const _component_UTable = _sfc_main$2;
      const _component_UBadge = _sfc_main$3;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}>`);
      if (loading.value) {
        _push(`<div class="flex justify-center py-20">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-loader-2",
          class: "animate-spin w-8 h-8 text-green-500"
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!--[-->`);
        if (isLab.value) {
          _push(`<!--[--><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"><!--[-->`);
          ssrRenderList(labKpis.value, (kpi) => {
            _push(`<div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 p-5 rounded-xl flex items-center justify-between shadow-sm"><div><span class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">${ssrInterpolate(kpi.label)}</span><h4 class="text-2xl font-black mt-1 text-slate-800 dark:text-white">${ssrInterpolate(kpi.val)}</h4></div><div class="${ssrRenderClass(["w-12 h-12 rounded-xl flex items-center justify-center", kpi.color])}">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: kpi.icon,
              class: "w-6 h-6"
            }, null, _parent));
            _push(`</div></div>`);
          });
          _push(`<!--]--></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-4"><div class="grid grid-cols-2 gap-3"><!--[-->`);
          ssrRenderList([
            { to: "/ingreso-egreso", icon: "i-lucide-arrow-left-right", label: "Ingreso/Egreso", color: "text-amber-600 bg-amber-50 dark:bg-amber-500/10" },
            { to: "/produccion", icon: "i-lucide-cog", label: "Producción", color: "text-blue-600 bg-blue-50 dark:bg-blue-500/10" },
            { to: "/calidad", icon: "i-lucide-shield-check", label: "Control Calidad", color: "text-rose-600 bg-rose-50 dark:bg-rose-500/10" },
            { to: "/inventario", icon: "i-lucide-package", label: "Inventario M.P.", color: "text-indigo-600 bg-indigo-50 dark:bg-indigo-500/10" }
          ], (link) => {
            _push(ssrRenderComponent(_component_NuxtLink, {
              key: link.to,
              to: link.to,
              class: "bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex flex-col items-center justify-center gap-2 hover:border-green-400 dark:hover:border-green-600 transition-colors shadow-sm text-center group"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`<div class="${ssrRenderClass(["w-10 h-10 rounded-lg flex items-center justify-center", link.color])}"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UIcon, {
                    name: link.icon,
                    class: "w-5 h-5"
                  }, null, _parent2, _scopeId));
                  _push2(`</div><span class="text-xs font-semibold text-slate-600 dark:text-slate-300 group-hover:text-green-600 dark:group-hover:text-green-400"${_scopeId}>${ssrInterpolate(link.label)}</span>`);
                } else {
                  return [
                    createVNode("div", {
                      class: ["w-10 h-10 rounded-lg flex items-center justify-center", link.color]
                    }, [
                      createVNode(_component_UIcon, {
                        name: link.icon,
                        class: "w-5 h-5"
                      }, null, 8, ["name"])
                    ], 2),
                    createVNode("span", { class: "text-xs font-semibold text-slate-600 dark:text-slate-300 group-hover:text-green-600 dark:group-hover:text-green-400" }, toDisplayString(link.label), 1)
                  ];
                }
              }),
              _: 2
            }, _parent));
          });
          _push(`<!--]--></div><div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 p-5 rounded-xl shadow-sm"><h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2 mb-4">`);
          _push(ssrRenderComponent(_component_UIcon, {
            name: "i-lucide-activity",
            class: "text-green-500"
          }, null, _parent));
          _push(` Actividad Reciente </h3>`);
          if (recentActivities.value.length === 0) {
            _push(`<div class="text-sm text-center py-4 text-slate-400">Sin actividad reciente</div>`);
          } else {
            _push(`<div class="space-y-3"><!--[-->`);
            ssrRenderList(recentActivities.value, (act, i) => {
              _push(`<div class="flex items-start gap-3"><div class="${ssrRenderClass(["w-2 h-2 rounded-full mt-1.5 shrink-0", activityStatusColor[act.status] || "bg-slate-400"])}"></div><div class="min-w-0"><p class="text-sm text-slate-700 dark:text-slate-300 truncate">${ssrInterpolate(act.action)}</p><p class="text-xs text-slate-400 dark:text-slate-500">${ssrInterpolate(act.user)} · ${ssrInterpolate(act.time)}</p></div></div>`);
            });
            _push(`<!--]--></div>`);
          }
          _push(`</div></div><!--]-->`);
        } else if (isCashier.value) {
          _push(`<!--[--><div class="grid grid-cols-1 md:grid-cols-3 gap-4">`);
          _push(ssrRenderComponent(_component_UCard, { class: "bg-gradient-to-br from-green-500 to-green-600 border-0 shadow-lg" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="flex justify-between items-center text-white"${_scopeId}><div${_scopeId}><p class="text-green-100 text-xs font-bold uppercase tracking-wider"${_scopeId}>Ventas de Hoy</p><h2 class="text-3xl font-black mt-1"${_scopeId}>${ssrInterpolate(formatCurrency(todaysSalesAmount.value))}</h2><p class="text-sm mt-1 text-green-100"${_scopeId}>${ssrInterpolate(todaysOrders.value.length)} órdenes procesadas</p></div>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-banknote",
                  class: "w-14 h-14 text-white/30"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "flex justify-between items-center text-white" }, [
                    createVNode("div", null, [
                      createVNode("p", { class: "text-green-100 text-xs font-bold uppercase tracking-wider" }, "Ventas de Hoy"),
                      createVNode("h2", { class: "text-3xl font-black mt-1" }, toDisplayString(formatCurrency(todaysSalesAmount.value)), 1),
                      createVNode("p", { class: "text-sm mt-1 text-green-100" }, toDisplayString(todaysOrders.value.length) + " órdenes procesadas", 1)
                    ]),
                    createVNode(_component_UIcon, {
                      name: "i-lucide-banknote",
                      class: "w-14 h-14 text-white/30"
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
                _push2(`<div class="flex justify-between items-start"${_scopeId}><div${_scopeId}><p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider"${_scopeId}>Estado de Caja</p>`);
                if (!todaysCashStatus.value || todaysCashStatus.value.status_cash == 0) {
                  _push2(`<div class="mt-2"${_scopeId}><span class="text-2xl font-black text-rose-500"${_scopeId}>CERRADA</span><p class="text-xs text-slate-400 mt-1"${_scopeId}>Abre la caja para operar</p></div>`);
                } else {
                  _push2(`<div class="mt-2"${_scopeId}><span class="text-2xl font-black text-emerald-500"${_scopeId}>ABIERTA</span><p class="text-xs text-slate-400 mt-1"${_scopeId}>Apertura: ${ssrInterpolate(formatCurrency(parseFloat(todaysCashStatus.value.start_cash || 0)))}</p></div>`);
                }
                _push2(`</div>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-wallet",
                  class: !todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? "w-10 h-10 text-rose-300" : "w-10 h-10 text-emerald-300"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
                _push2(ssrRenderComponent(_component_UButton, {
                  to: "/caja",
                  size: "sm",
                  color: !todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? "error" : "success",
                  variant: "soft",
                  class: "mt-3 w-full justify-center"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`${ssrInterpolate(!todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? "Abrir Caja" : "Ver Caja")}`);
                    } else {
                      return [
                        createTextVNode(toDisplayString(!todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? "Abrir Caja" : "Ver Caja"), 1)
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                return [
                  createVNode("div", { class: "flex justify-between items-start" }, [
                    createVNode("div", null, [
                      createVNode("p", { class: "text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider" }, "Estado de Caja"),
                      !todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mt-2"
                      }, [
                        createVNode("span", { class: "text-2xl font-black text-rose-500" }, "CERRADA"),
                        createVNode("p", { class: "text-xs text-slate-400 mt-1" }, "Abre la caja para operar")
                      ])) : (openBlock(), createBlock("div", {
                        key: 1,
                        class: "mt-2"
                      }, [
                        createVNode("span", { class: "text-2xl font-black text-emerald-500" }, "ABIERTA"),
                        createVNode("p", { class: "text-xs text-slate-400 mt-1" }, "Apertura: " + toDisplayString(formatCurrency(parseFloat(todaysCashStatus.value.start_cash || 0))), 1)
                      ]))
                    ]),
                    createVNode(_component_UIcon, {
                      name: "i-lucide-wallet",
                      class: !todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? "w-10 h-10 text-rose-300" : "w-10 h-10 text-emerald-300"
                    }, null, 8, ["class"])
                  ]),
                  createVNode(_component_UButton, {
                    to: "/caja",
                    size: "sm",
                    color: !todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? "error" : "success",
                    variant: "soft",
                    class: "mt-3 w-full justify-center"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(!todaysCashStatus.value || todaysCashStatus.value.status_cash == 0 ? "Abrir Caja" : "Ver Caja"), 1)
                    ]),
                    _: 1
                  }, 8, ["color"])
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: "/pos",
            class: "block"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(_component_UCard, { class: "h-full flex items-center justify-center cursor-pointer hover:border-green-400 dark:hover:border-green-600 transition-colors" }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<div class="text-center py-2"${_scopeId2}><div class="w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center mx-auto mb-3"${_scopeId2}>`);
                      _push3(ssrRenderComponent(_component_UIcon, {
                        name: "i-lucide-monitor-smartphone",
                        class: "w-8 h-8 text-blue-500"
                      }, null, _parent3, _scopeId2));
                      _push3(`</div><span class="font-bold text-slate-800 dark:text-white"${_scopeId2}>Ir al Punto de Venta</span><p class="text-xs text-slate-500 mt-1"${_scopeId2}>Abrir POS</p></div>`);
                    } else {
                      return [
                        createVNode("div", { class: "text-center py-2" }, [
                          createVNode("div", { class: "w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center mx-auto mb-3" }, [
                            createVNode(_component_UIcon, {
                              name: "i-lucide-monitor-smartphone",
                              class: "w-8 h-8 text-blue-500"
                            })
                          ]),
                          createVNode("span", { class: "font-bold text-slate-800 dark:text-white" }, "Ir al Punto de Venta"),
                          createVNode("p", { class: "text-xs text-slate-500 mt-1" }, "Abrir POS")
                        ])
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                return [
                  createVNode(_component_UCard, { class: "h-full flex items-center justify-center cursor-pointer hover:border-green-400 dark:hover:border-green-600 transition-colors" }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "text-center py-2" }, [
                        createVNode("div", { class: "w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center mx-auto mb-3" }, [
                          createVNode(_component_UIcon, {
                            name: "i-lucide-monitor-smartphone",
                            class: "w-8 h-8 text-blue-500"
                          })
                        ]),
                        createVNode("span", { class: "font-bold text-slate-800 dark:text-white" }, "Ir al Punto de Venta"),
                        createVNode("p", { class: "text-xs text-slate-500 mt-1" }, "Abrir POS")
                      ])
                    ]),
                    _: 1
                  })
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div>`);
          _push(ssrRenderComponent(_component_UCard, null, {
            header: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="flex justify-between items-center"${_scopeId}><h3 class="font-bold"${_scopeId}>Últimas Ventas (Hoy)</h3>`);
                _push2(ssrRenderComponent(_component_UButton, {
                  to: "/ordenes",
                  size: "xs",
                  color: "neutral",
                  variant: "ghost",
                  icon: "i-lucide-external-link"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`Ver todas`);
                    } else {
                      return [
                        createTextVNode("Ver todas")
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "flex justify-between items-center" }, [
                    createVNode("h3", { class: "font-bold" }, "Últimas Ventas (Hoy)"),
                    createVNode(_component_UButton, {
                      to: "/ordenes",
                      size: "xs",
                      color: "neutral",
                      variant: "ghost",
                      icon: "i-lucide-external-link"
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Ver todas")
                      ]),
                      _: 1
                    })
                  ])
                ];
              }
            }),
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                if (todaysOrders.value.length === 0) {
                  _push2(`<div class="text-center text-slate-400 dark:text-slate-500 py-6"${_scopeId}>No hay ventas hoy aún.</div>`);
                } else {
                  _push2(ssrRenderComponent(_component_UTable, {
                    data: todaysOrders.value.slice(0, 8),
                    columns: [
                      { accessorKey: "transaction_order", header: "Transacción" },
                      { accessorKey: "client", header: "Cliente" },
                      { accessorKey: "method_order", header: "Método" },
                      { accessorKey: "total_order", header: "Total" }
                    ]
                  }, {
                    "client-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(decodeURIComponent(row.original.name_client || "").replace(/\+/g, " "))}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(decodeURIComponent(row.original.name_client || "").replace(/\+/g, " ")), 1)
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
                    class: "text-center text-slate-400 dark:text-slate-500 py-6"
                  }, "No hay ventas hoy aún.")) : (openBlock(), createBlock(_component_UTable, {
                    key: 1,
                    data: todaysOrders.value.slice(0, 8),
                    columns: [
                      { accessorKey: "transaction_order", header: "Transacción" },
                      { accessorKey: "client", header: "Cliente" },
                      { accessorKey: "method_order", header: "Método" },
                      { accessorKey: "total_order", header: "Total" }
                    ]
                  }, {
                    "client-cell": withCtx(({ row }) => [
                      createTextVNode(toDisplayString(decodeURIComponent(row.original.name_client || "").replace(/\+/g, " ")), 1)
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
        } else if (isSuperAdmin.value) {
          _push(`<!--[--><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">`);
          _push(ssrRenderComponent(_component_UCard, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider"${_scopeId}>Ventas Globales Hoy</p><h2 class="text-2xl font-black text-green-600 mt-1"${_scopeId}>${ssrInterpolate(formatCurrency(todaysSalesAmount.value))}</h2><p class="text-xs text-slate-400 mt-1"${_scopeId}>${ssrInterpolate(todaysOrders.value.length)} transacciones</p>`);
              } else {
                return [
                  createVNode("p", { class: "text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider" }, "Ventas Globales Hoy"),
                  createVNode("h2", { class: "text-2xl font-black text-green-600 mt-1" }, toDisplayString(formatCurrency(todaysSalesAmount.value)), 1),
                  createVNode("p", { class: "text-xs text-slate-400 mt-1" }, toDisplayString(todaysOrders.value.length) + " transacciones", 1)
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(ssrRenderComponent(_component_UCard, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider"${_scopeId}>Cajas Abiertas</p><h2 class="text-2xl font-black text-blue-600 mt-1"${_scopeId}>${ssrInterpolate(openCashRegisters.value.length)}</h2><p class="text-xs text-slate-400 mt-1"${_scopeId}>sucursales activas</p>`);
              } else {
                return [
                  createVNode("p", { class: "text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider" }, "Cajas Abiertas"),
                  createVNode("h2", { class: "text-2xl font-black text-blue-600 mt-1" }, toDisplayString(openCashRegisters.value.length), 1),
                  createVNode("p", { class: "text-xs text-slate-400 mt-1" }, "sucursales activas")
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(ssrRenderComponent(_component_UCard, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider"${_scopeId}>Ticket Promedio</p><h2 class="text-2xl font-black text-amber-600 mt-1"${_scopeId}>${ssrInterpolate(formatCurrency(todaysOrders.value.length > 0 ? todaysSalesAmount.value / todaysOrders.value.length : 0))}</h2><p class="text-xs text-slate-400 mt-1"${_scopeId}>por orden</p>`);
              } else {
                return [
                  createVNode("p", { class: "text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider" }, "Ticket Promedio"),
                  createVNode("h2", { class: "text-2xl font-black text-amber-600 mt-1" }, toDisplayString(formatCurrency(todaysOrders.value.length > 0 ? todaysSalesAmount.value / todaysOrders.value.length : 0)), 1),
                  createVNode("p", { class: "text-xs text-slate-400 mt-1" }, "por orden")
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(ssrRenderComponent(_component_NuxtLink, { to: "/reportes" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(_component_UCard, { class: "cursor-pointer hover:border-green-400 dark:hover:border-green-600 transition-colors h-full flex items-center justify-center" }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<div class="text-center"${_scopeId2}>`);
                      _push3(ssrRenderComponent(_component_UIcon, {
                        name: "i-lucide-bar-chart-2",
                        class: "w-8 h-8 text-green-500 mx-auto mb-1"
                      }, null, _parent3, _scopeId2));
                      _push3(`<p class="text-sm font-semibold text-slate-700 dark:text-slate-200"${_scopeId2}>Ver Reportes</p></div>`);
                    } else {
                      return [
                        createVNode("div", { class: "text-center" }, [
                          createVNode(_component_UIcon, {
                            name: "i-lucide-bar-chart-2",
                            class: "w-8 h-8 text-green-500 mx-auto mb-1"
                          }),
                          createVNode("p", { class: "text-sm font-semibold text-slate-700 dark:text-slate-200" }, "Ver Reportes")
                        ])
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                return [
                  createVNode(_component_UCard, { class: "cursor-pointer hover:border-green-400 dark:hover:border-green-600 transition-colors h-full flex items-center justify-center" }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "text-center" }, [
                        createVNode(_component_UIcon, {
                          name: "i-lucide-bar-chart-2",
                          class: "w-8 h-8 text-green-500 mx-auto mb-1"
                        }),
                        createVNode("p", { class: "text-sm font-semibold text-slate-700 dark:text-slate-200" }, "Ver Reportes")
                      ])
                    ]),
                    _: 1
                  })
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div><div class="grid grid-cols-1 lg:grid-cols-2 gap-4">`);
          _push(ssrRenderComponent(_component_UCard, null, {
            header: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="flex justify-between items-center"${_scopeId}><h3 class="font-bold"${_scopeId}>Sucursales con Caja Abierta</h3>`);
                _push2(ssrRenderComponent(_component_UButton, {
                  to: "/caja",
                  size: "xs",
                  color: "neutral",
                  variant: "ghost",
                  icon: "i-lucide-external-link"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`Ver Cajas`);
                    } else {
                      return [
                        createTextVNode("Ver Cajas")
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "flex justify-between items-center" }, [
                    createVNode("h3", { class: "font-bold" }, "Sucursales con Caja Abierta"),
                    createVNode(_component_UButton, {
                      to: "/caja",
                      size: "xs",
                      color: "neutral",
                      variant: "ghost",
                      icon: "i-lucide-external-link"
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Ver Cajas")
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
                  _push2(`<div class="text-center text-slate-400 dark:text-slate-500 py-6"${_scopeId}>No hay cajas abiertas.</div>`);
                } else {
                  _push2(ssrRenderComponent(_component_UTable, {
                    data: openCashRegisters.value,
                    columns: [{ accessorKey: "title_office", header: "Sucursal" }, { accessorKey: "start_cash", header: "Apertura" }]
                  }, {
                    "title_office-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`<span class="font-semibold"${_scopeId2}>${ssrInterpolate(decodeURIComponent(row.original.title_office || "").replace(/\+/g, " "))}</span>`);
                      } else {
                        return [
                          createVNode("span", { class: "font-semibold" }, toDisplayString(decodeURIComponent(row.original.title_office || "").replace(/\+/g, " ")), 1)
                        ];
                      }
                    }),
                    "start_cash-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(formatCurrency(parseFloat(row.original.start_cash || 0)))}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(formatCurrency(parseFloat(row.original.start_cash || 0))), 1)
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
                    class: "text-center text-slate-400 dark:text-slate-500 py-6"
                  }, "No hay cajas abiertas.")) : (openBlock(), createBlock(_component_UTable, {
                    key: 1,
                    data: openCashRegisters.value,
                    columns: [{ accessorKey: "title_office", header: "Sucursal" }, { accessorKey: "start_cash", header: "Apertura" }]
                  }, {
                    "title_office-cell": withCtx(({ row }) => [
                      createVNode("span", { class: "font-semibold" }, toDisplayString(decodeURIComponent(row.original.title_office || "").replace(/\+/g, " ")), 1)
                    ]),
                    "start_cash-cell": withCtx(({ row }) => [
                      createTextVNode(toDisplayString(formatCurrency(parseFloat(row.original.start_cash || 0))), 1)
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
                _push2(`<div class="flex justify-between items-center"${_scopeId}><h3 class="font-bold"${_scopeId}>Últimas Transacciones</h3>`);
                _push2(ssrRenderComponent(_component_UButton, {
                  to: "/ordenes",
                  size: "xs",
                  color: "neutral",
                  variant: "ghost",
                  icon: "i-lucide-external-link"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`Ver Órdenes`);
                    } else {
                      return [
                        createTextVNode("Ver Órdenes")
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "flex justify-between items-center" }, [
                    createVNode("h3", { class: "font-bold" }, "Últimas Transacciones"),
                    createVNode(_component_UButton, {
                      to: "/ordenes",
                      size: "xs",
                      color: "neutral",
                      variant: "ghost",
                      icon: "i-lucide-external-link"
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Ver Órdenes")
                      ]),
                      _: 1
                    })
                  ])
                ];
              }
            }),
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                if (todaysOrders.value.length === 0) {
                  _push2(`<div class="text-center text-slate-400 dark:text-slate-500 py-6"${_scopeId}>Sin transacciones hoy.</div>`);
                } else {
                  _push2(ssrRenderComponent(_component_UTable, {
                    data: todaysOrders.value.slice(0, 6),
                    columns: [{ accessorKey: "transaction_order", header: "Transacción" }, { accessorKey: "total_order", header: "Monto" }]
                  }, {
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
                    class: "text-center text-slate-400 dark:text-slate-500 py-6"
                  }, "Sin transacciones hoy.")) : (openBlock(), createBlock(_component_UTable, {
                    key: 1,
                    data: todaysOrders.value.slice(0, 6),
                    columns: [{ accessorKey: "transaction_order", header: "Transacción" }, { accessorKey: "total_order", header: "Monto" }]
                  }, {
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
        } else if (isDispatcher.value) {
          _push(`<!--[--><div class="grid grid-cols-1 md:grid-cols-3 gap-4">`);
          _push(ssrRenderComponent(_component_UCard, { class: "bg-gradient-to-br from-amber-500 to-orange-500 border-0 shadow-lg" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="flex justify-between items-center text-white"${_scopeId}><div${_scopeId}><p class="text-amber-100 text-xs font-bold uppercase tracking-wider"${_scopeId}>Solicitudes Pendientes</p><h2 class="text-4xl font-black mt-1"${_scopeId}>${ssrInterpolate(pendingRequests.value.length)}</h2><p class="text-sm mt-1 text-amber-100"${_scopeId}>requieren atención</p></div>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-clipboard-copy",
                  class: "w-14 h-14 text-white/30"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "flex justify-between items-center text-white" }, [
                    createVNode("div", null, [
                      createVNode("p", { class: "text-amber-100 text-xs font-bold uppercase tracking-wider" }, "Solicitudes Pendientes"),
                      createVNode("h2", { class: "text-4xl font-black mt-1" }, toDisplayString(pendingRequests.value.length), 1),
                      createVNode("p", { class: "text-sm mt-1 text-amber-100" }, "requieren atención")
                    ]),
                    createVNode(_component_UIcon, {
                      name: "i-lucide-clipboard-copy",
                      class: "w-14 h-14 text-white/30"
                    })
                  ])
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: "/despachos",
            class: "block"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(_component_UCard, { class: "h-full flex items-center justify-center cursor-pointer hover:border-amber-400 transition-colors" }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<div class="text-center py-2"${_scopeId2}><div class="w-14 h-14 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center mx-auto mb-3"${_scopeId2}>`);
                      _push3(ssrRenderComponent(_component_UIcon, {
                        name: "i-lucide-truck",
                        class: "w-8 h-8 text-amber-500"
                      }, null, _parent3, _scopeId2));
                      _push3(`</div><span class="font-bold text-slate-800 dark:text-white"${_scopeId2}>Centro de Despachos</span><p class="text-xs text-slate-500 mt-1"${_scopeId2}>Atender solicitudes</p></div>`);
                    } else {
                      return [
                        createVNode("div", { class: "text-center py-2" }, [
                          createVNode("div", { class: "w-14 h-14 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center mx-auto mb-3" }, [
                            createVNode(_component_UIcon, {
                              name: "i-lucide-truck",
                              class: "w-8 h-8 text-amber-500"
                            })
                          ]),
                          createVNode("span", { class: "font-bold text-slate-800 dark:text-white" }, "Centro de Despachos"),
                          createVNode("p", { class: "text-xs text-slate-500 mt-1" }, "Atender solicitudes")
                        ])
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                return [
                  createVNode(_component_UCard, { class: "h-full flex items-center justify-center cursor-pointer hover:border-amber-400 transition-colors" }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "text-center py-2" }, [
                        createVNode("div", { class: "w-14 h-14 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center mx-auto mb-3" }, [
                          createVNode(_component_UIcon, {
                            name: "i-lucide-truck",
                            class: "w-8 h-8 text-amber-500"
                          })
                        ]),
                        createVNode("span", { class: "font-bold text-slate-800 dark:text-white" }, "Centro de Despachos"),
                        createVNode("p", { class: "text-xs text-slate-500 mt-1" }, "Atender solicitudes")
                      ])
                    ]),
                    _: 1
                  })
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: "/almacen",
            class: "block"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(_component_UCard, { class: "h-full flex items-center justify-center cursor-pointer hover:border-blue-400 transition-colors" }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<div class="text-center py-2"${_scopeId2}><div class="w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center mx-auto mb-3"${_scopeId2}>`);
                      _push3(ssrRenderComponent(_component_UIcon, {
                        name: "i-lucide-warehouse",
                        class: "w-8 h-8 text-blue-500"
                      }, null, _parent3, _scopeId2));
                      _push3(`</div><span class="font-bold text-slate-800 dark:text-white"${_scopeId2}>Almacén</span><p class="text-xs text-slate-500 mt-1"${_scopeId2}>Ver inventario</p></div>`);
                    } else {
                      return [
                        createVNode("div", { class: "text-center py-2" }, [
                          createVNode("div", { class: "w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center mx-auto mb-3" }, [
                            createVNode(_component_UIcon, {
                              name: "i-lucide-warehouse",
                              class: "w-8 h-8 text-blue-500"
                            })
                          ]),
                          createVNode("span", { class: "font-bold text-slate-800 dark:text-white" }, "Almacén"),
                          createVNode("p", { class: "text-xs text-slate-500 mt-1" }, "Ver inventario")
                        ])
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                return [
                  createVNode(_component_UCard, { class: "h-full flex items-center justify-center cursor-pointer hover:border-blue-400 transition-colors" }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "text-center py-2" }, [
                        createVNode("div", { class: "w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center mx-auto mb-3" }, [
                          createVNode(_component_UIcon, {
                            name: "i-lucide-warehouse",
                            class: "w-8 h-8 text-blue-500"
                          })
                        ]),
                        createVNode("span", { class: "font-bold text-slate-800 dark:text-white" }, "Almacén"),
                        createVNode("p", { class: "text-xs text-slate-500 mt-1" }, "Ver inventario")
                      ])
                    ]),
                    _: 1
                  })
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div>`);
          if (pendingRequests.value.length > 0) {
            _push(ssrRenderComponent(_component_UCard, null, {
              header: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`<div class="flex justify-between items-center"${_scopeId}><h3 class="font-bold"${_scopeId}>Solicitudes Pendientes</h3>`);
                  _push2(ssrRenderComponent(_component_UButton, {
                    to: "/despachos",
                    size: "xs",
                    color: "warning",
                    variant: "soft",
                    icon: "i-lucide-external-link"
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`Atender`);
                      } else {
                        return [
                          createTextVNode("Atender")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent2, _scopeId));
                  _push2(`</div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex justify-between items-center" }, [
                      createVNode("h3", { class: "font-bold" }, "Solicitudes Pendientes"),
                      createVNode(_component_UButton, {
                        to: "/despachos",
                        size: "xs",
                        color: "warning",
                        variant: "soft",
                        icon: "i-lucide-external-link"
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Atender")
                        ]),
                        _: 1
                      })
                    ])
                  ];
                }
              }),
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`<div class="space-y-2"${_scopeId}><!--[-->`);
                  ssrRenderList(pendingRequests.value.slice(0, 5), (req) => {
                    _push2(`<div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-500/10 rounded-lg border border-amber-200 dark:border-amber-500/20"${_scopeId}><div${_scopeId}><p class="text-sm font-semibold text-slate-700 dark:text-slate-200"${_scopeId}>${ssrInterpolate(decodeURIComponent(req.name_product || req.product || "").replace(/\+/g, " ") || "Solicitud #" + (req.id_request || req.id))}</p><p class="text-xs text-slate-500 dark:text-slate-400"${_scopeId}>${ssrInterpolate(req.date_created_request || req.date || "")}</p></div>`);
                    _push2(ssrRenderComponent(_component_UBadge, {
                      color: "warning",
                      variant: "subtle"
                    }, {
                      default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                        if (_push3) {
                          _push3(`Pendiente`);
                        } else {
                          return [
                            createTextVNode("Pendiente")
                          ];
                        }
                      }),
                      _: 2
                    }, _parent2, _scopeId));
                    _push2(`</div>`);
                  });
                  _push2(`<!--]--></div>`);
                } else {
                  return [
                    createVNode("div", { class: "space-y-2" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(pendingRequests.value.slice(0, 5), (req) => {
                        return openBlock(), createBlock("div", {
                          key: req.id_request || req.id,
                          class: "flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-500/10 rounded-lg border border-amber-200 dark:border-amber-500/20"
                        }, [
                          createVNode("div", null, [
                            createVNode("p", { class: "text-sm font-semibold text-slate-700 dark:text-slate-200" }, toDisplayString(decodeURIComponent(req.name_product || req.product || "").replace(/\+/g, " ") || "Solicitud #" + (req.id_request || req.id)), 1),
                            createVNode("p", { class: "text-xs text-slate-500 dark:text-slate-400" }, toDisplayString(req.date_created_request || req.date || ""), 1)
                          ]),
                          createVNode(_component_UBadge, {
                            color: "warning",
                            variant: "subtle"
                          }, {
                            default: withCtx(() => [
                              createTextVNode("Pendiente")
                            ]),
                            _: 1
                          })
                        ]);
                      }), 128))
                    ])
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
        _push(`<!--]-->`);
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
//# sourceMappingURL=index-TYACRAje.mjs.map
