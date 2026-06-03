import { I as useAuthStore, g as _sfc_main$h, i as _sfc_main$c } from './server.mjs';
import { _ as _sfc_main$1 } from './Card-BV4DIQLA.mjs';
import { _ as _sfc_main$2 } from './Table-EJSLuWs0.mjs';
import { _ as _sfc_main$3 } from './Badge-LaytOPGg.mjs';
import { defineComponent, ref, computed, mergeProps, withCtx, createTextVNode, toDisplayString, createVNode, openBlock, createBlock, createCommentVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass } from 'vue/server-renderer';
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
  __name: "mi-inventario",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    const hasSubWarehouse = ref(false);
    const inventory = ref([]);
    const movements = ref([]);
    const loadingInventory = ref(true);
    const loadingMovements = ref(true);
    const isCaja = computed(() => auth.role === "cajero" || auth.role === "caja");
    const invColumns = [
      { accessorKey: "title_product", header: "Producto" },
      { accessorKey: "sku_product", header: "SKU" },
      { accessorKey: "unit_product", header: "Unidad" },
      { accessorKey: "stock", header: "Cantidad Disponible" },
      { accessorKey: "status", header: "Estado" }
    ];
    const moveColumns = [
      { accessorKey: "date_created_assignment", header: "Fecha" },
      { accessorKey: "type", header: "Tipo" },
      { accessorKey: "title_product", header: "Producto" },
      { accessorKey: "qty_assignment", header: "Cantidad" },
      { accessorKey: "notes_assignment", header: "Notas" }
    ];
    function formatText(t) {
      if (!t) return "";
      return decodeURIComponent(t).replace(/\+/g, " ");
    }
    function getTypeColor(type) {
      if (type === "despacho") return "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300";
      if (type === "venta") return "bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300";
      return "bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300";
    }
    function getTypeLabel(type) {
      if (type === "despacho") return "Recibido";
      if (type === "venta") return "Venta";
      return "Devolución";
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      const _component_UButton = _sfc_main$c;
      const _component_UCard = _sfc_main$1;
      const _component_UTable = _sfc_main$2;
      const _component_UBadge = _sfc_main$3;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "h-full flex flex-col p-6 space-y-6 overflow-y-auto w-full" }, _attrs))}><div class="flex items-center justify-between"><div><h1 class="text-2xl font-bold flex items-center gap-2">`);
      _push(ssrRenderComponent(_component_UIcon, {
        name: "i-lucide-box",
        class: "w-6 h-6 text-green-600"
      }, null, _parent));
      _push(` ${ssrInterpolate(!hasSubWarehouse.value ? "Inventario Almacén" : "Mi Inventario")}</h1><p class="text-sm text-slate-500 mt-1">${ssrInterpolate(!hasSubWarehouse.value ? "Consulta de stock general de sucursal" : "Tus productos asignados")}</p></div>`);
      if (hasSubWarehouse.value && !isCaja.value) {
        _push(ssrRenderComponent(_component_UButton, {
          to: "/solicitar-inventario",
          icon: "i-lucide-plus-circle",
          color: "primary",
          class: "bg-green-600 hover:bg-green-700"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Solicitar Inventario `);
            } else {
              return [
                createTextVNode(" Solicitar Inventario ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      _push(ssrRenderComponent(_component_UCard, null, {
        header: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h3 class="font-semibold text-lg flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-boxes",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent2, _scopeId));
            _push2(` ${ssrInterpolate(!hasSubWarehouse.value ? "Productos en Almacén" : "Productos en mi Sub-Almacén")}</h3>`);
          } else {
            return [
              createVNode("h3", { class: "font-semibold text-lg flex items-center gap-2" }, [
                createVNode(_component_UIcon, {
                  name: "i-lucide-boxes",
                  class: "w-5 h-5 text-gray-500"
                }),
                createTextVNode(" " + toDisplayString(!hasSubWarehouse.value ? "Productos en Almacén" : "Productos en mi Sub-Almacén"), 1)
              ])
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (loadingInventory.value) {
              _push2(`<div class="py-8 flex justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-loader-2",
                class: "w-8 h-8 animate-spin text-green-600"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else if (inventory.value.length === 0) {
              _push2(`<div class="py-12 text-center flex flex-col items-center"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-box-select",
                class: "w-12 h-12 text-gray-300 mb-3"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-500 font-medium"${_scopeId}>No tienes productos asignados.</p>`);
              if (hasSubWarehouse.value && !isCaja.value) {
                _push2(ssrRenderComponent(_component_UButton, {
                  to: "/solicitar-inventario",
                  color: "neutral",
                  variant: "soft",
                  class: "mt-4"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(` Ir a solicitar inventario `);
                    } else {
                      return [
                        createTextVNode(" Ir a solicitar inventario ")
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            } else {
              _push2(ssrRenderComponent(_component_UTable, {
                columns: invColumns,
                data: inventory.value
              }, {
                "title_product-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<span class="font-medium"${_scopeId2}>${ssrInterpolate(formatText(row.original.title_product))}</span>`);
                  } else {
                    return [
                      createVNode("span", { class: "font-medium" }, toDisplayString(formatText(row.original.title_product)), 1)
                    ];
                  }
                }),
                "sku_product-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_UBadge, {
                      color: "neutral",
                      variant: "soft"
                    }, {
                      default: withCtx((_2, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(`${ssrInterpolate(row.original.sku_product || "-")}`);
                        } else {
                          return [
                            createTextVNode(toDisplayString(row.original.sku_product || "-"), 1)
                          ];
                        }
                      }),
                      _: 2
                    }, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_component_UBadge, {
                        color: "neutral",
                        variant: "soft"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(toDisplayString(row.original.sku_product || "-"), 1)
                        ]),
                        _: 2
                      }, 1024)
                    ];
                  }
                }),
                "unit_product-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(row.original.unit_product || "-")}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(row.original.unit_product || "-"), 1)
                    ];
                  }
                }),
                "stock-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<span class="${ssrRenderClass([
                      "font-bold px-2 py-1 rounded text-sm",
                      parseFloat(row.original.stock) > 0 ? "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300" : "bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300"
                    ])}"${_scopeId2}>${ssrInterpolate(row.original.stock)}</span>`);
                  } else {
                    return [
                      createVNode("span", {
                        class: [
                          "font-bold px-2 py-1 rounded text-sm",
                          parseFloat(row.original.stock) > 0 ? "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300" : "bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300"
                        ]
                      }, toDisplayString(row.original.stock), 3)
                    ];
                  }
                }),
                "status-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_UBadge, {
                      color: parseFloat(row.original.stock) > 0 ? "success" : "error"
                    }, {
                      default: withCtx((_2, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(`${ssrInterpolate(parseFloat(row.original.stock) > 0 ? "Disponible" : "Agotado")}`);
                        } else {
                          return [
                            createTextVNode(toDisplayString(parseFloat(row.original.stock) > 0 ? "Disponible" : "Agotado"), 1)
                          ];
                        }
                      }),
                      _: 2
                    }, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_component_UBadge, {
                        color: parseFloat(row.original.stock) > 0 ? "success" : "error"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(toDisplayString(parseFloat(row.original.stock) > 0 ? "Disponible" : "Agotado"), 1)
                        ]),
                        _: 2
                      }, 1032, ["color"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            }
          } else {
            return [
              loadingInventory.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "py-8 flex justify-center"
              }, [
                createVNode(_component_UIcon, {
                  name: "i-lucide-loader-2",
                  class: "w-8 h-8 animate-spin text-green-600"
                })
              ])) : inventory.value.length === 0 ? (openBlock(), createBlock("div", {
                key: 1,
                class: "py-12 text-center flex flex-col items-center"
              }, [
                createVNode(_component_UIcon, {
                  name: "i-lucide-box-select",
                  class: "w-12 h-12 text-gray-300 mb-3"
                }),
                createVNode("p", { class: "text-gray-500 font-medium" }, "No tienes productos asignados."),
                hasSubWarehouse.value && !isCaja.value ? (openBlock(), createBlock(_component_UButton, {
                  key: 0,
                  to: "/solicitar-inventario",
                  color: "neutral",
                  variant: "soft",
                  class: "mt-4"
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Ir a solicitar inventario ")
                  ]),
                  _: 1
                })) : createCommentVNode("", true)
              ])) : (openBlock(), createBlock(_component_UTable, {
                key: 2,
                columns: invColumns,
                data: inventory.value
              }, {
                "title_product-cell": withCtx(({ row }) => [
                  createVNode("span", { class: "font-medium" }, toDisplayString(formatText(row.original.title_product)), 1)
                ]),
                "sku_product-cell": withCtx(({ row }) => [
                  createVNode(_component_UBadge, {
                    color: "neutral",
                    variant: "soft"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(row.original.sku_product || "-"), 1)
                    ]),
                    _: 2
                  }, 1024)
                ]),
                "unit_product-cell": withCtx(({ row }) => [
                  createTextVNode(toDisplayString(row.original.unit_product || "-"), 1)
                ]),
                "stock-cell": withCtx(({ row }) => [
                  createVNode("span", {
                    class: [
                      "font-bold px-2 py-1 rounded text-sm",
                      parseFloat(row.original.stock) > 0 ? "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300" : "bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300"
                    ]
                  }, toDisplayString(row.original.stock), 3)
                ]),
                "status-cell": withCtx(({ row }) => [
                  createVNode(_component_UBadge, {
                    color: parseFloat(row.original.stock) > 0 ? "success" : "error"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(parseFloat(row.original.stock) > 0 ? "Disponible" : "Agotado"), 1)
                    ]),
                    _: 2
                  }, 1032, ["color"])
                ]),
                _: 1
              }, 8, ["data"]))
            ];
          }
        }),
        _: 1
      }, _parent));
      if (hasSubWarehouse.value) {
        _push(ssrRenderComponent(_component_UCard, null, {
          header: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<h3 class="font-semibold text-lg flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-history",
                class: "w-5 h-5 text-gray-500"
              }, null, _parent2, _scopeId));
              _push2(` Últimos Movimientos </h3>`);
            } else {
              return [
                createVNode("h3", { class: "font-semibold text-lg flex items-center gap-2" }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-history",
                    class: "w-5 h-5 text-gray-500"
                  }),
                  createTextVNode(" Últimos Movimientos ")
                ])
              ];
            }
          }),
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              if (loadingMovements.value) {
                _push2(`<div class="py-8 flex justify-center"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-loader-2",
                  class: "w-8 h-8 animate-spin text-green-600"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else if (movements.value.length === 0) {
                _push2(`<div class="py-8 text-center text-gray-500"${_scopeId}> Sin movimientos registrados. </div>`);
              } else {
                _push2(ssrRenderComponent(_component_UTable, {
                  columns: moveColumns,
                  data: movements.value
                }, {
                  "title_product-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<strong${_scopeId2}>${ssrInterpolate(formatText(row.original.title_product))}</strong>`);
                    } else {
                      return [
                        createVNode("strong", null, toDisplayString(formatText(row.original.title_product)), 1)
                      ];
                    }
                  }),
                  "type-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<span class="${ssrRenderClass(["px-2 py-1 text-xs font-semibold rounded", getTypeColor(row.original.type_assignment)])}"${_scopeId2}>${ssrInterpolate(getTypeLabel(row.original.type_assignment))}</span>`);
                    } else {
                      return [
                        createVNode("span", {
                          class: ["px-2 py-1 text-xs font-semibold rounded", getTypeColor(row.original.type_assignment)]
                        }, toDisplayString(getTypeLabel(row.original.type_assignment)), 3)
                      ];
                    }
                  }),
                  "notes_assignment-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<span class="text-sm text-gray-500"${_scopeId2}>${ssrInterpolate(row.original.notes_assignment || "-")}</span>`);
                    } else {
                      return [
                        createVNode("span", { class: "text-sm text-gray-500" }, toDisplayString(row.original.notes_assignment || "-"), 1)
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              }
            } else {
              return [
                loadingMovements.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "py-8 flex justify-center"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "w-8 h-8 animate-spin text-green-600"
                  })
                ])) : movements.value.length === 0 ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "py-8 text-center text-gray-500"
                }, " Sin movimientos registrados. ")) : (openBlock(), createBlock(_component_UTable, {
                  key: 2,
                  columns: moveColumns,
                  data: movements.value
                }, {
                  "title_product-cell": withCtx(({ row }) => [
                    createVNode("strong", null, toDisplayString(formatText(row.original.title_product)), 1)
                  ]),
                  "type-cell": withCtx(({ row }) => [
                    createVNode("span", {
                      class: ["px-2 py-1 text-xs font-semibold rounded", getTypeColor(row.original.type_assignment)]
                    }, toDisplayString(getTypeLabel(row.original.type_assignment)), 3)
                  ]),
                  "notes_assignment-cell": withCtx(({ row }) => [
                    createVNode("span", { class: "text-sm text-gray-500" }, toDisplayString(row.original.notes_assignment || "-"), 1)
                  ]),
                  _: 1
                }, 8, ["data"]))
              ];
            }
          }),
          _: 1
        }, _parent));
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/mi-inventario.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=mi-inventario-Duir0kaR.mjs.map
