import { I as useAuthStore, a6 as useToast, g as _sfc_main$h, i as _sfc_main$c } from './server.mjs';
import { _ as _sfc_main$1 } from './Card-BV4DIQLA.mjs';
import { _ as _sfc_main$2 } from './Select-Bk-d3PfC.mjs';
import { _ as _sfc_main$3 } from './Textarea-DVGiVqM_.mjs';
import { _ as _sfc_main$4 } from './Table-EJSLuWs0.mjs';
import { _ as _sfc_main$5 } from './Badge-LaytOPGg.mjs';
import { defineComponent, ref, computed, resolveComponent, mergeProps, withCtx, createVNode, openBlock, createBlock, createTextVNode, toDisplayString, createCommentVNode, withModifiers, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderClass } from 'vue/server-renderer';
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
  __name: "solicitar-inventario",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    function blockNegative(e) {
      if (e.key === "-" || e.key === "e" || e.key === "E") e.preventDefault();
    }
    function onQtyInput(e) {
      const input = e.target;
      const raw = input.value.replace(/[^\d]/g, "");
      const n = parseInt(raw, 10) || 0;
      form.value.qty = Math.max(0, n);
      input.value = n > 0 ? n.toLocaleString("de-DE") : "";
    }
    const warehouses = ref([]);
    const products = ref([]);
    const myRequests = ref([]);
    const loadingWarehouses = ref(true);
    const loadingProducts = ref(false);
    const loadingRequests = ref(true);
    const submitting = ref(false);
    const form = ref({
      warehouseId: "",
      productId: "",
      qty: 1,
      notes: ""
    });
    const toast = useToast();
    async function fetchWarehouseProducts(warehouseId) {
      if (!warehouseId) {
        products.value = [];
        return;
      }
      loadingProducts.value = true;
      try {
        const response = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            getWarehouseProducts: "true",
            id_warehouse: warehouseId
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        products.value = Array.isArray(data) ? data : [];
        if (products.value.length === 0) {
          toast.add({
            title: "Atención",
            description: "Este almacén no tiene productos con stock disponible.",
            color: "warning"
          });
        }
      } catch (e) {
        console.error("Error fetching warehouse products:", e);
        products.value = [];
      } finally {
        loadingProducts.value = false;
      }
    }
    function onWarehouseChange() {
      form.value.productId = "";
      form.value.qty = 1;
      fetchWarehouseProducts(form.value.warehouseId);
    }
    const selectedProductData = computed(() => {
      if (!form.value.productId) return null;
      return products.value.find((p) => String(p.id_product) === String(form.value.productId)) || null;
    });
    const maxStock = computed(() => {
      return selectedProductData.value ? parseFloat(selectedProductData.value.stock) : 0;
    });
    async function fetchMyRequests() {
      loadingRequests.value = true;
      try {
        const response = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            getMyRequests: "true",
            id_admin: String(auth.user?.id_admin || 1)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        myRequests.value = Array.isArray(data) ? data : [];
      } catch (e) {
        console.error("Error fetching my requests:", e);
        myRequests.value = [];
      } finally {
        loadingRequests.value = false;
      }
    }
    async function submitRequest() {
      if (!form.value.warehouseId) return toast.add({ title: "Error", description: "Selecciona un almacén", color: "error" });
      if (!form.value.productId) return toast.add({ title: "Error", description: "Selecciona un producto", color: "error" });
      if (!form.value.qty || form.value.qty <= 0) return toast.add({ title: "Error", description: "Ingresa una cantidad válida", color: "error" });
      if (form.value.qty > maxStock.value) return toast.add({ title: "Error", description: `La cantidad supera el stock disponible (${maxStock.value})`, color: "error" });
      submitting.value = true;
      try {
        const response = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            createInventoryRequest: "true",
            id_product: form.value.productId,
            qty: String(form.value.qty),
            notes: form.value.notes,
            id_admin: String(auth.user?.id_admin || 1),
            id_office: String(auth.officeId || 3),
            id_warehouse: form.value.warehouseId
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        if (typeof response === "string" && response.trim() === "ok") {
          toast.add({ title: "Éxito", description: "Solicitud enviada correctamente", color: "success" });
          form.value.warehouseId = "";
          form.value.productId = "";
          form.value.qty = 1;
          form.value.notes = "";
          products.value = [];
          await fetchMyRequests();
        } else {
          toast.add({ title: "Error", description: response || "No se pudo enviar la solicitud", color: "error" });
        }
      } catch (e) {
        console.error("Error submitting request:", e);
        toast.add({ title: "Error", description: "Ocurrió un error inesperado.", color: "error" });
      } finally {
        submitting.value = false;
      }
    }
    const columns = [
      { accessorKey: "date_created_request", header: "Fecha" },
      { accessorKey: "title_warehouse", header: "Almacén" },
      { accessorKey: "title_product", header: "Producto" },
      { accessorKey: "qty_request", header: "Solicitado" },
      { accessorKey: "qty_dispatched", header: "Despachado" },
      { accessorKey: "status_request", header: "Estado" },
      { accessorKey: "notes_request", header: "Notas" }
    ];
    function formatText(t) {
      if (!t) return "";
      return decodeURIComponent(t).replace(/\+/g, " ");
    }
    function getStatusColor(status) {
      switch (status) {
        case "pendiente":
          return "warning";
        case "despachada":
          return "success";
        case "rechazada":
          return "error";
        default:
          return "neutral";
      }
    }
    function getStatusLabel(status) {
      switch (status) {
        case "pendiente":
          return "Pendiente";
        case "despachada":
          return "Despachada";
        case "rechazada":
          return "Rechazada";
        default:
          return status || "Desconocido";
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      const _component_UCard = _sfc_main$1;
      const _component_UFormGroup = resolveComponent("UFormGroup");
      const _component_USelect = _sfc_main$2;
      const _component_UTextarea = _sfc_main$3;
      const _component_UButton = _sfc_main$c;
      const _component_UTable = _sfc_main$4;
      const _component_UBadge = _sfc_main$5;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "h-full flex flex-col p-6 overflow-y-auto w-full" }, _attrs))}><div class="mb-6"><h1 class="text-2xl font-bold flex items-center gap-2">`);
      _push(ssrRenderComponent(_component_UIcon, {
        name: "i-lucide-clipboard-list",
        class: "w-6 h-6 text-green-600"
      }, null, _parent));
      _push(` Solicitar Inventario </h1><p class="text-sm text-slate-500 mt-1"> Crea solicitudes de mercadería al almacén central o centros de distribución. </p></div><div class="grid grid-cols-1 lg:grid-cols-12 gap-6"><div class="lg:col-span-5">`);
      _push(ssrRenderComponent(_component_UCard, null, {
        header: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h3 class="font-semibold text-lg flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-plus-circle",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent2, _scopeId));
            _push2(` Nueva Solicitud </h3>`);
          } else {
            return [
              createVNode("h3", { class: "font-semibold text-lg flex items-center gap-2" }, [
                createVNode(_component_UIcon, {
                  name: "i-lucide-plus-circle",
                  class: "w-5 h-5 text-gray-500"
                }),
                createTextVNode(" Nueva Solicitud ")
              ])
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<form class="space-y-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UFormGroup, { label: "Almacén *" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_USelect, {
                    modelValue: form.value.warehouseId,
                    "onUpdate:modelValue": [($event) => form.value.warehouseId = $event, onWarehouseChange],
                    items: warehouses.value.map((w) => ({ value: String(w.id_warehouse), label: formatText(w.title_warehouse) })),
                    placeholder: "-- Seleccionar almacén --",
                    loading: loadingWarehouses.value,
                    required: ""
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_USelect, {
                      modelValue: form.value.warehouseId,
                      "onUpdate:modelValue": [($event) => form.value.warehouseId = $event, onWarehouseChange],
                      items: warehouses.value.map((w) => ({ value: String(w.id_warehouse), label: formatText(w.title_warehouse) })),
                      placeholder: "-- Seleccionar almacén --",
                      loading: loadingWarehouses.value,
                      required: ""
                    }, null, 8, ["modelValue", "onUpdate:modelValue", "items", "loading"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormGroup, { label: "Producto *" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_USelect, {
                    modelValue: form.value.productId,
                    "onUpdate:modelValue": ($event) => form.value.productId = $event,
                    items: products.value.map((p) => ({ value: String(p.id_product), label: `${formatText(p.title_product)} (Stock: ${p.stock})` })),
                    placeholder: "-- Seleccionar producto --",
                    disabled: !form.value.warehouseId,
                    loading: loadingProducts.value,
                    required: ""
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_USelect, {
                      modelValue: form.value.productId,
                      "onUpdate:modelValue": ($event) => form.value.productId = $event,
                      items: products.value.map((p) => ({ value: String(p.id_product), label: `${formatText(p.title_product)} (Stock: ${p.stock})` })),
                      placeholder: "-- Seleccionar producto --",
                      disabled: !form.value.warehouseId,
                      loading: loadingProducts.value,
                      required: ""
                    }, null, 8, ["modelValue", "onUpdate:modelValue", "items", "disabled", "loading"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormGroup, { label: "Cantidad *" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<input${ssrRenderAttr("value", form.value.qty > 0 ? form.value.qty.toLocaleString("de-DE") : "")} type="text" inputmode="numeric" placeholder="0" class="block w-full py-2.5 px-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId2}>`);
                  if (selectedProductData.value) {
                    _push3(`<p class="text-xs text-slate-500 mt-1"${_scopeId2}> Max disponible: <span class="font-bold text-green-600"${_scopeId2}>${ssrInterpolate(maxStock.value.toLocaleString("de-DE"))}</span></p>`);
                  } else {
                    _push3(`<!---->`);
                  }
                } else {
                  return [
                    createVNode("input", {
                      value: form.value.qty > 0 ? form.value.qty.toLocaleString("de-DE") : "",
                      type: "text",
                      inputmode: "numeric",
                      placeholder: "0",
                      class: "block w-full py-2.5 px-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50",
                      onInput: ($event) => onQtyInput($event),
                      onKeydown: ($event) => blockNegative($event)
                    }, null, 40, ["value", "onInput", "onKeydown"]),
                    selectedProductData.value ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "text-xs text-slate-500 mt-1"
                    }, [
                      createTextVNode(" Max disponible: "),
                      createVNode("span", { class: "font-bold text-green-600" }, toDisplayString(maxStock.value.toLocaleString("de-DE")), 1)
                    ])) : createCommentVNode("", true)
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormGroup, { label: "Notas (opcional)" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UTextarea, {
                    modelValue: form.value.notes,
                    "onUpdate:modelValue": ($event) => form.value.notes = $event,
                    placeholder: "Justificación de la solicitud...",
                    rows: 2
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UTextarea, {
                      modelValue: form.value.notes,
                      "onUpdate:modelValue": ($event) => form.value.notes = $event,
                      placeholder: "Justificación de la solicitud...",
                      rows: 2
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UButton, {
              type: "submit",
              color: "primary",
              class: "w-full justify-center mt-4 bg-green-600 hover:bg-green-700",
              icon: "i-lucide-send",
              loading: submitting.value
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Enviar Solicitud `);
                } else {
                  return [
                    createTextVNode(" Enviar Solicitud ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</form>`);
          } else {
            return [
              createVNode("form", {
                onSubmit: withModifiers(submitRequest, ["prevent"]),
                class: "space-y-4"
              }, [
                createVNode(_component_UFormGroup, { label: "Almacén *" }, {
                  default: withCtx(() => [
                    createVNode(_component_USelect, {
                      modelValue: form.value.warehouseId,
                      "onUpdate:modelValue": [($event) => form.value.warehouseId = $event, onWarehouseChange],
                      items: warehouses.value.map((w) => ({ value: String(w.id_warehouse), label: formatText(w.title_warehouse) })),
                      placeholder: "-- Seleccionar almacén --",
                      loading: loadingWarehouses.value,
                      required: ""
                    }, null, 8, ["modelValue", "onUpdate:modelValue", "items", "loading"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormGroup, { label: "Producto *" }, {
                  default: withCtx(() => [
                    createVNode(_component_USelect, {
                      modelValue: form.value.productId,
                      "onUpdate:modelValue": ($event) => form.value.productId = $event,
                      items: products.value.map((p) => ({ value: String(p.id_product), label: `${formatText(p.title_product)} (Stock: ${p.stock})` })),
                      placeholder: "-- Seleccionar producto --",
                      disabled: !form.value.warehouseId,
                      loading: loadingProducts.value,
                      required: ""
                    }, null, 8, ["modelValue", "onUpdate:modelValue", "items", "disabled", "loading"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormGroup, { label: "Cantidad *" }, {
                  default: withCtx(() => [
                    createVNode("input", {
                      value: form.value.qty > 0 ? form.value.qty.toLocaleString("de-DE") : "",
                      type: "text",
                      inputmode: "numeric",
                      placeholder: "0",
                      class: "block w-full py-2.5 px-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50",
                      onInput: ($event) => onQtyInput($event),
                      onKeydown: ($event) => blockNegative($event)
                    }, null, 40, ["value", "onInput", "onKeydown"]),
                    selectedProductData.value ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "text-xs text-slate-500 mt-1"
                    }, [
                      createTextVNode(" Max disponible: "),
                      createVNode("span", { class: "font-bold text-green-600" }, toDisplayString(maxStock.value.toLocaleString("de-DE")), 1)
                    ])) : createCommentVNode("", true)
                  ]),
                  _: 2
                }, 1024),
                createVNode(_component_UFormGroup, { label: "Notas (opcional)" }, {
                  default: withCtx(() => [
                    createVNode(_component_UTextarea, {
                      modelValue: form.value.notes,
                      "onUpdate:modelValue": ($event) => form.value.notes = $event,
                      placeholder: "Justificación de la solicitud...",
                      rows: 2
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UButton, {
                  type: "submit",
                  color: "primary",
                  class: "w-full justify-center mt-4 bg-green-600 hover:bg-green-700",
                  icon: "i-lucide-send",
                  loading: submitting.value
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Enviar Solicitud ")
                  ]),
                  _: 1
                }, 8, ["loading"])
              ], 32)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="lg:col-span-7">`);
      _push(ssrRenderComponent(_component_UCard, null, {
        header: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h3 class="font-semibold text-lg flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-list",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent2, _scopeId));
            _push2(` Mis Solicitudes </h3>`);
          } else {
            return [
              createVNode("h3", { class: "font-semibold text-lg flex items-center gap-2" }, [
                createVNode(_component_UIcon, {
                  name: "i-lucide-list",
                  class: "w-5 h-5 text-gray-500"
                }),
                createTextVNode(" Mis Solicitudes ")
              ])
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (loadingRequests.value) {
              _push2(`<div class="py-8 flex justify-center text-green-600"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-loader-2",
                class: "w-8 h-8 animate-spin"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else if (myRequests.value.length === 0) {
              _push2(`<div class="py-10 flex flex-col items-center"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-inbox",
                class: "w-12 h-12 text-slate-300 mb-3"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-slate-500 font-medium"${_scopeId}>No tienes solicitudes registradas</p></div>`);
            } else {
              _push2(ssrRenderComponent(_component_UTable, {
                columns,
                data: myRequests.value,
                class: "w-full"
              }, {
                "title_warehouse-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(formatText(row.original.title_warehouse))}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(formatText(row.original.title_warehouse)), 1)
                    ];
                  }
                }),
                "title_product-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<span class="font-semibold"${_scopeId2}>${ssrInterpolate(formatText(row.original.title_product))}</span>`);
                  } else {
                    return [
                      createVNode("span", { class: "font-semibold" }, toDisplayString(formatText(row.original.title_product)), 1)
                    ];
                  }
                }),
                "qty_request-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(row.original.qty_request)}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(row.original.qty_request), 1)
                    ];
                  }
                }),
                "qty_dispatched-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<span class="${ssrRenderClass(row.original.qty_dispatched > 0 ? "text-green-600 font-bold" : "text-slate-500")}"${_scopeId2}>${ssrInterpolate(row.original.qty_dispatched || "0")}</span>`);
                  } else {
                    return [
                      createVNode("span", {
                        class: row.original.qty_dispatched > 0 ? "text-green-600 font-bold" : "text-slate-500"
                      }, toDisplayString(row.original.qty_dispatched || "0"), 3)
                    ];
                  }
                }),
                "status_request-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(_component_UBadge, {
                      color: getStatusColor(row.original.status_request),
                      variant: "soft"
                    }, {
                      default: withCtx((_2, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(`${ssrInterpolate(getStatusLabel(row.original.status_request))}`);
                        } else {
                          return [
                            createTextVNode(toDisplayString(getStatusLabel(row.original.status_request)), 1)
                          ];
                        }
                      }),
                      _: 2
                    }, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_component_UBadge, {
                        color: getStatusColor(row.original.status_request),
                        variant: "soft"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(toDisplayString(getStatusLabel(row.original.status_request)), 1)
                        ]),
                        _: 2
                      }, 1032, ["color"])
                    ];
                  }
                }),
                "notes_request-cell": withCtx(({ row }, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<span class="text-sm text-slate-500"${ssrRenderAttr("title", row.original.notes_request)}${_scopeId2}>${ssrInterpolate(row.original.notes_request ? row.original.notes_request.length > 20 ? row.original.notes_request.substring(0, 20) + "..." : row.original.notes_request : "-")}</span>`);
                  } else {
                    return [
                      createVNode("span", {
                        class: "text-sm text-slate-500",
                        title: row.original.notes_request
                      }, toDisplayString(row.original.notes_request ? row.original.notes_request.length > 20 ? row.original.notes_request.substring(0, 20) + "..." : row.original.notes_request : "-"), 9, ["title"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            }
          } else {
            return [
              loadingRequests.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "py-8 flex justify-center text-green-600"
              }, [
                createVNode(_component_UIcon, {
                  name: "i-lucide-loader-2",
                  class: "w-8 h-8 animate-spin"
                })
              ])) : myRequests.value.length === 0 ? (openBlock(), createBlock("div", {
                key: 1,
                class: "py-10 flex flex-col items-center"
              }, [
                createVNode(_component_UIcon, {
                  name: "i-lucide-inbox",
                  class: "w-12 h-12 text-slate-300 mb-3"
                }),
                createVNode("p", { class: "text-slate-500 font-medium" }, "No tienes solicitudes registradas")
              ])) : (openBlock(), createBlock(_component_UTable, {
                key: 2,
                columns,
                data: myRequests.value,
                class: "w-full"
              }, {
                "title_warehouse-cell": withCtx(({ row }) => [
                  createTextVNode(toDisplayString(formatText(row.original.title_warehouse)), 1)
                ]),
                "title_product-cell": withCtx(({ row }) => [
                  createVNode("span", { class: "font-semibold" }, toDisplayString(formatText(row.original.title_product)), 1)
                ]),
                "qty_request-cell": withCtx(({ row }) => [
                  createTextVNode(toDisplayString(row.original.qty_request), 1)
                ]),
                "qty_dispatched-cell": withCtx(({ row }) => [
                  createVNode("span", {
                    class: row.original.qty_dispatched > 0 ? "text-green-600 font-bold" : "text-slate-500"
                  }, toDisplayString(row.original.qty_dispatched || "0"), 3)
                ]),
                "status_request-cell": withCtx(({ row }) => [
                  createVNode(_component_UBadge, {
                    color: getStatusColor(row.original.status_request),
                    variant: "soft"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(getStatusLabel(row.original.status_request)), 1)
                    ]),
                    _: 2
                  }, 1032, ["color"])
                ]),
                "notes_request-cell": withCtx(({ row }) => [
                  createVNode("span", {
                    class: "text-sm text-slate-500",
                    title: row.original.notes_request
                  }, toDisplayString(row.original.notes_request ? row.original.notes_request.length > 20 ? row.original.notes_request.substring(0, 20) + "..." : row.original.notes_request : "-"), 9, ["title"])
                ]),
                _: 1
              }, 8, ["data"]))
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/solicitar-inventario.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=solicitar-inventario-COdbGWoJ.mjs.map
