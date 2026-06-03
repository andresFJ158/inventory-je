import { K as useAuthStore, ab as useToast, g as _sfc_main$h, i as _sfc_main$c, h as _sfc_main$6 } from './server.mjs';
import { _ as _sfc_main$1 } from './Card-Dj8zIcA3.mjs';
import { _ as _sfc_main$2 } from './Table-BGh52cFP.mjs';
import { _ as _sfc_main$3 } from './Badge-BLusyd6V.mjs';
import { _ as _sfc_main$4 } from './Modal-DVs2bKsP.mjs';
import { _ as _sfc_main$5 } from './FormField-IGNQl_uA.mjs';
import { _ as _sfc_main$7 } from './SelectMenu-jDjllcVC.mjs';
import { _ as _sfc_main$8 } from './Textarea-C_8t1vyc.mjs';
import { defineComponent, ref, computed, watch, mergeProps, withCtx, createTextVNode, toDisplayString, createVNode, unref, openBlock, createBlock, createCommentVNode, useSSRContext } from 'vue';
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
import './overlay-DzoQAbpj.mjs';
import './Label-BRZJsVxu.mjs';
import './useFormControl-9B1GcqCr.mjs';
import './VisuallyHiddenInput-lcZAWR9l.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "mi-inventario",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    const toast = useToast();
    const hasSubWarehouse = ref(false);
    const inventory = ref([]);
    const movements = ref([]);
    const offices = ref([]);
    const loadingInventory = ref(true);
    const loadingMovements = ref(true);
    const assignModalOpen = ref(false);
    const selectedProduct = ref(null);
    const selectedOfficeId = ref("");
    const assignQty = ref(null);
    const assignNotes = ref("");
    const assigning = ref(false);
    const apiHeaders = {
      Authorization: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy"
    };
    async function fetchOffices() {
      try {
        const data = await $fetch("/api/offices", { headers: apiHeaders });
        if (data.status === 200) {
          offices.value = data.results || [];
        }
      } catch (e) {
        console.error("Error fetching offices:", e);
      }
    }
    const officeOptions = computed(() => {
      return offices.value.filter((o) => String(o.id_office) !== String(auth.officeId)).map((o) => ({
        value: String(o.id_office),
        label: decodeURIComponent(o.title_office || "").replace(/\+/g, " ")
      }));
    });
    function openAssignModal(product) {
      selectedProduct.value = product;
      selectedOfficeId.value = "";
      assignQty.value = null;
      assignNotes.value = "";
      assignModalOpen.value = true;
    }
    async function confirmAssignment() {
      if (!selectedProduct.value) return;
      if (!selectedOfficeId.value) {
        toast.add({ title: "Por favor selecciona una sucursal de destino.", color: "error" });
        return;
      }
      if (!assignQty.value || assignQty.value <= 0) {
        toast.add({ title: "Por favor ingresa una cantidad válida mayor a 0.", color: "error" });
        return;
      }
      if (assignQty.value > parseFloat(selectedProduct.value.stock)) {
        toast.add({ title: "La cantidad ingresada supera el stock disponible en almacén.", color: "error" });
        return;
      }
      assigning.value = true;
      try {
        const response = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            transferStockBetweenOffices: "true",
            id_product: String(selectedProduct.value.id_product),
            id_office_source: String(auth.officeId),
            id_office_dest: String(selectedOfficeId.value),
            qty: String(assignQty.value),
            notes: assignNotes.value || `Asignación manual de stock desde Almacén`,
            id_dispatched_by: String(auth.user?.id_admin || 1)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        if (response.trim() === "ok") {
          toast.add({ title: "Inventario asignado correctamente.", color: "success" });
          assignModalOpen.value = false;
          await fetchInventory();
        } else {
          toast.add({ title: response.replace("error: ", "") || "Error al asignar inventario.", color: "error" });
        }
      } catch (e) {
        console.error("Error confirming assignment:", e);
        toast.add({ title: "Error de red al intentar asignar inventario.", color: "error" });
      } finally {
        assigning.value = false;
      }
    }
    async function checkHasSubWarehouse() {
      if (auth.role === "vendedor") {
        hasSubWarehouse.value = true;
        return;
      }
      try {
        const data = await $fetch(`/api/sub_warehouses?linkTo=id_office_sub_warehouse&equalTo=${auth.officeId}`, {
          headers: apiHeaders
        });
        hasSubWarehouse.value = data.status === 200 && data.results && data.results.length > 0;
      } catch (e) {
        hasSubWarehouse.value = false;
      }
    }
    async function fetchInventory() {
      loadingInventory.value = true;
      try {
        const response = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            getSubWarehouseStock: "true",
            id_admin: String(auth.user?.id_admin || 1),
            id_office: String(auth.officeId || 3),
            role: auth.role || "cajero"
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        inventory.value = Array.isArray(data) ? data : [];
      } catch (e) {
        console.error("Error fetching inventory:", e);
        inventory.value = [];
      } finally {
        loadingInventory.value = false;
      }
    }
    async function fetchMovements() {
      if (!hasSubWarehouse.value) return;
      loadingMovements.value = true;
      try {
        const response = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            getMyWarehouseMovements: "true",
            id_admin: String(auth.user?.id_admin || 1),
            id_office: String(auth.officeId || 3)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        movements.value = Array.isArray(data) ? data : [];
      } catch (e) {
        console.error("Error fetching movements:", e);
        movements.value = [];
      } finally {
        loadingMovements.value = false;
      }
    }
    watch(() => auth.user, async (newVal) => {
      if (newVal) {
        await checkHasSubWarehouse();
        await fetchInventory();
        await fetchMovements();
        if (auth.role === "despachador") {
          await fetchOffices();
        }
      }
    }, { immediate: true });
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
      const _component_UModal = _sfc_main$4;
      const _component_UFormField = _sfc_main$5;
      const _component_UInput = _sfc_main$6;
      const _component_USelectMenu = _sfc_main$7;
      const _component_UTextarea = _sfc_main$8;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "w-full space-y-6" }, _attrs))}><div class="flex items-center justify-between"><div><h1 class="text-2xl font-bold flex items-center gap-2">`);
      _push(ssrRenderComponent(_component_UIcon, {
        name: "i-lucide-box",
        class: "w-6 h-6 text-green-600"
      }, null, _parent));
      _push(` ${ssrInterpolate(!hasSubWarehouse.value ? "Inventario Almacén" : "Mi Inventario")}</h1><p class="text-sm text-slate-500 mt-1">${ssrInterpolate(!hasSubWarehouse.value ? "Consulta de stock general de sucursal" : "Tus productos asignados")}</p></div>`);
      if (hasSubWarehouse.value) {
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
              if (hasSubWarehouse.value) {
                _push2(ssrRenderComponent(_component_UButton, {
                  to: "/solicitar-inventario",
                  color: "gray",
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
                      color: "gray",
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
                        color: "gray",
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
                    _push3(`<div class="flex items-center gap-4 justify-between"${_scopeId2}><span class="${ssrRenderClass([
                      "font-bold px-2 py-1 rounded text-sm",
                      parseFloat(row.original.stock) > 0 ? "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300" : "bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300"
                    ])}"${_scopeId2}>${ssrInterpolate(row.original.stock)}</span>`);
                    if (unref(auth).role === "despachador" && parseFloat(row.original.stock) > 0) {
                      _push3(ssrRenderComponent(_component_UButton, {
                        size: "xs",
                        color: "primary",
                        variant: "soft",
                        icon: "i-lucide-send",
                        onClick: ($event) => openAssignModal(row.original)
                      }, {
                        default: withCtx((_2, _push4, _parent4, _scopeId3) => {
                          if (_push4) {
                            _push4(` Asignar `);
                          } else {
                            return [
                              createTextVNode(" Asignar ")
                            ];
                          }
                        }),
                        _: 2
                      }, _parent3, _scopeId2));
                    } else {
                      _push3(`<!---->`);
                    }
                    _push3(`</div>`);
                  } else {
                    return [
                      createVNode("div", { class: "flex items-center gap-4 justify-between" }, [
                        createVNode("span", {
                          class: [
                            "font-bold px-2 py-1 rounded text-sm",
                            parseFloat(row.original.stock) > 0 ? "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300" : "bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300"
                          ]
                        }, toDisplayString(row.original.stock), 3),
                        unref(auth).role === "despachador" && parseFloat(row.original.stock) > 0 ? (openBlock(), createBlock(_component_UButton, {
                          key: 0,
                          size: "xs",
                          color: "primary",
                          variant: "soft",
                          icon: "i-lucide-send",
                          onClick: ($event) => openAssignModal(row.original)
                        }, {
                          default: withCtx(() => [
                            createTextVNode(" Asignar ")
                          ]),
                          _: 1
                        }, 8, ["onClick"])) : createCommentVNode("", true)
                      ])
                    ];
                  }
                }),
                "status-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_UBadge, {
                      color: parseFloat(row.original.stock) > 0 ? "green" : "red"
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
                        color: parseFloat(row.original.stock) > 0 ? "green" : "red"
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
                hasSubWarehouse.value ? (openBlock(), createBlock(_component_UButton, {
                  key: 0,
                  to: "/solicitar-inventario",
                  color: "gray",
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
                    color: "gray",
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
                  createVNode("div", { class: "flex items-center gap-4 justify-between" }, [
                    createVNode("span", {
                      class: [
                        "font-bold px-2 py-1 rounded text-sm",
                        parseFloat(row.original.stock) > 0 ? "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300" : "bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300"
                      ]
                    }, toDisplayString(row.original.stock), 3),
                    unref(auth).role === "despachador" && parseFloat(row.original.stock) > 0 ? (openBlock(), createBlock(_component_UButton, {
                      key: 0,
                      size: "xs",
                      color: "primary",
                      variant: "soft",
                      icon: "i-lucide-send",
                      onClick: ($event) => openAssignModal(row.original)
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" Asignar ")
                      ]),
                      _: 1
                    }, 8, ["onClick"])) : createCommentVNode("", true)
                  ])
                ]),
                "status-cell": withCtx(({ row }) => [
                  createVNode(_component_UBadge, {
                    color: parseFloat(row.original.stock) > 0 ? "green" : "red"
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
      _push(ssrRenderComponent(_component_UModal, {
        open: assignModalOpen.value,
        "onUpdate:open": ($event) => assignModalOpen.value = $event,
        title: "Asignar Inventario a Sucursal"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (selectedProduct.value) {
              _push2(`<div class="space-y-4 p-1"${_scopeId}><div${_scopeId}><span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1"${_scopeId}>Producto</span><div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-800"${_scopeId}>${ssrInterpolate(formatText(selectedProduct.value.title_product))} (${ssrInterpolate(selectedProduct.value.unit_product || "Unidad")}) </div></div><div class="grid grid-cols-2 gap-4"${_scopeId}><div${_scopeId}><span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1"${_scopeId}>Stock Disponible</span><div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-lg text-sm font-bold text-teal-600 border border-slate-200 dark:border-slate-800"${_scopeId}>${ssrInterpolate(selectedProduct.value.stock)}</div></div><div${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UFormField, {
                label: "Cantidad a Asignar",
                required: ""
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_UInput, {
                      modelValue: assignQty.value,
                      "onUpdate:modelValue": ($event) => assignQty.value = $event,
                      type: "number",
                      min: "1",
                      max: selectedProduct.value.stock,
                      placeholder: "Ej. 5",
                      class: "w-full"
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_component_UInput, {
                        modelValue: assignQty.value,
                        "onUpdate:modelValue": ($event) => assignQty.value = $event,
                        type: "number",
                        min: "1",
                        max: selectedProduct.value.stock,
                        placeholder: "Ej. 5",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue", "max"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div></div><div${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UFormField, {
                label: "Sucursal de Destino",
                required: ""
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_USelectMenu, {
                      modelValue: selectedOfficeId.value,
                      "onUpdate:modelValue": ($event) => selectedOfficeId.value = $event,
                      items: officeOptions.value,
                      class: "w-full",
                      placeholder: "Seleccionar sucursal...",
                      "value-key": "value",
                      "label-key": "label",
                      ui: { content: "z-[100]" }
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_component_USelectMenu, {
                        modelValue: selectedOfficeId.value,
                        "onUpdate:modelValue": ($event) => selectedOfficeId.value = $event,
                        items: officeOptions.value,
                        class: "w-full",
                        placeholder: "Seleccionar sucursal...",
                        "value-key": "value",
                        "label-key": "label",
                        ui: { content: "z-[100]" }
                      }, null, 8, ["modelValue", "onUpdate:modelValue", "items"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UFormField, { label: "Notas / Justificación" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_UTextarea, {
                      modelValue: assignNotes.value,
                      "onUpdate:modelValue": ($event) => assignNotes.value = $event,
                      placeholder: "Ej. Traspaso de mercadería para venta en sucursal",
                      rows: "3",
                      class: "w-full"
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_component_UTextarea, {
                        modelValue: assignNotes.value,
                        "onUpdate:modelValue": ($event) => assignNotes.value = $event,
                        placeholder: "Ej. Traspaso de mercadería para venta en sucursal",
                        rows: "3",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div></div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              selectedProduct.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "space-y-4 p-1"
              }, [
                createVNode("div", null, [
                  createVNode("span", { class: "text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1" }, "Producto"),
                  createVNode("div", { class: "p-3 bg-slate-50 dark:bg-slate-900 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-800" }, toDisplayString(formatText(selectedProduct.value.title_product)) + " (" + toDisplayString(selectedProduct.value.unit_product || "Unidad") + ") ", 1)
                ]),
                createVNode("div", { class: "grid grid-cols-2 gap-4" }, [
                  createVNode("div", null, [
                    createVNode("span", { class: "text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1" }, "Stock Disponible"),
                    createVNode("div", { class: "p-3 bg-slate-50 dark:bg-slate-900 rounded-lg text-sm font-bold text-teal-600 border border-slate-200 dark:border-slate-800" }, toDisplayString(selectedProduct.value.stock), 1)
                  ]),
                  createVNode("div", null, [
                    createVNode(_component_UFormField, {
                      label: "Cantidad a Asignar",
                      required: ""
                    }, {
                      default: withCtx(() => [
                        createVNode(_component_UInput, {
                          modelValue: assignQty.value,
                          "onUpdate:modelValue": ($event) => assignQty.value = $event,
                          type: "number",
                          min: "1",
                          max: selectedProduct.value.stock,
                          placeholder: "Ej. 5",
                          class: "w-full"
                        }, null, 8, ["modelValue", "onUpdate:modelValue", "max"])
                      ]),
                      _: 1
                    })
                  ])
                ]),
                createVNode("div", null, [
                  createVNode(_component_UFormField, {
                    label: "Sucursal de Destino",
                    required: ""
                  }, {
                    default: withCtx(() => [
                      createVNode(_component_USelectMenu, {
                        modelValue: selectedOfficeId.value,
                        "onUpdate:modelValue": ($event) => selectedOfficeId.value = $event,
                        items: officeOptions.value,
                        class: "w-full",
                        placeholder: "Seleccionar sucursal...",
                        "value-key": "value",
                        "label-key": "label",
                        ui: { content: "z-[100]" }
                      }, null, 8, ["modelValue", "onUpdate:modelValue", "items"])
                    ]),
                    _: 1
                  })
                ]),
                createVNode("div", null, [
                  createVNode(_component_UFormField, { label: "Notas / Justificación" }, {
                    default: withCtx(() => [
                      createVNode(_component_UTextarea, {
                        modelValue: assignNotes.value,
                        "onUpdate:modelValue": ($event) => assignNotes.value = $event,
                        placeholder: "Ej. Traspaso de mercadería para venta en sucursal",
                        rows: "3",
                        class: "w-full"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"])
                    ]),
                    _: 1
                  })
                ])
              ])) : createCommentVNode("", true)
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex justify-end gap-3"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              color: "neutral",
              variant: "ghost",
              onClick: ($event) => assignModalOpen.value = false
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Cancelar `);
                } else {
                  return [
                    createTextVNode(" Cancelar ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UButton, {
              color: "primary",
              loading: assigning.value,
              onClick: confirmAssignment
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Confirmar Asignación `);
                } else {
                  return [
                    createTextVNode(" Confirmar Asignación ")
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
                  onClick: ($event) => assignModalOpen.value = false
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Cancelar ")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(_component_UButton, {
                  color: "primary",
                  loading: assigning.value,
                  onClick: confirmAssignment
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Confirmar Asignación ")
                  ]),
                  _: 1
                }, 8, ["loading"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/mi-inventario.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=mi-inventario-C6Bl-_TH.mjs.map
