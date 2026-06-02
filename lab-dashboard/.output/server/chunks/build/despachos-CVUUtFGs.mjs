import { H as useAuthStore, g as _sfc_main$c, h as _sfc_main$h } from './server.mjs';
import { _ as _sfc_main$1 } from './Tabs-BpL_2piA.mjs';
import { _ as _sfc_main$2 } from './Badge-P0JOv5sI.mjs';
import { _ as _sfc_main$3 } from './Modal-CoprpFuw.mjs';
import { _ as _sfc_main$4 } from './Textarea-KHOF13zM.mjs';
import { defineComponent, ref, mergeProps, withCtx, createTextVNode, toDisplayString, openBlock, createBlock, createVNode, Fragment, renderList, createCommentVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderAttr } from 'vue/server-renderer';
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
import './overlay-DuqFFmJC.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "despachos",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    function blockNegative(e) {
      if (e.key === "-" || e.key === "e" || e.key === "E") e.preventDefault();
    }
    function onDispatchQtyInput(e) {
      const input = e.target;
      const raw = input.value.replace(/[^\d]/g, "");
      const n = parseInt(raw, 10) || 0;
      dispatchQty.value = Math.max(0, n);
      input.value = n > 0 ? n.toLocaleString("de-DE") : "";
    }
    const pendingRequests = ref([]);
    const historyRequests = ref([]);
    const loadingPending = ref(true);
    const loadingHistory = ref(true);
    const activeTab = ref(0);
    const isDispatchOpen = ref(false);
    const selectedRequest = ref(null);
    const dispatchQty = ref(1);
    const dispatchNotes = ref("");
    const processingAction = ref(false);
    const isRejectOpen = ref(false);
    const rejectNotes = ref("");
    async function fetchPending() {
      loadingPending.value = true;
      try {
        const officeId = auth.officeId || 3;
        const whId = auth.warehouseId || 0;
        const response = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            getPendingRequests: "true",
            id_office: String(officeId),
            id_warehouse: String(whId)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        pendingRequests.value = Array.isArray(data) ? data : [];
      } catch (e) {
        console.error("Error fetching pending requests:", e);
        pendingRequests.value = [];
      } finally {
        loadingPending.value = false;
      }
    }
    async function fetchHistory() {
      loadingHistory.value = true;
      try {
        const officeId = auth.officeId || 3;
        const whId = auth.warehouseId || 0;
        const response = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            getRequestHistory: "true",
            id_office: String(officeId),
            id_warehouse: String(whId)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        historyRequests.value = Array.isArray(data) ? data : [];
      } catch (e) {
        console.error("Error fetching history:", e);
        historyRequests.value = [];
      } finally {
        loadingHistory.value = false;
      }
    }
    function startDispatch(req) {
      selectedRequest.value = req;
      dispatchQty.value = parseInt(req.qty_request);
      dispatchNotes.value = "";
      isDispatchOpen.value = true;
    }
    async function confirmDispatch() {
      if (!selectedRequest.value) return;
      if (dispatchQty.value <= 0 || dispatchQty.value > selectedRequest.value.available_stock) {
        alert("Cantidad de despacho no válida o excede el stock disponible.");
        return;
      }
      processingAction.value = true;
      try {
        const officeId = auth.officeId || 3;
        const adminId = auth.user?.id_admin || 1;
        const res = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            dispatchRequest: "true",
            id_request: String(selectedRequest.value.id_request),
            qty_dispatch: String(dispatchQty.value),
            notes_dispatcher: dispatchNotes.value,
            id_dispatched_by: String(adminId),
            id_office: String(officeId)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        if (String(res).trim() === "ok") {
          isDispatchOpen.value = false;
          await fetchPending();
          await fetchHistory();
        } else {
          alert(res || "Error al despachar la solicitud.");
        }
      } catch (e) {
        console.error("Dispatch error:", e);
        alert("Error al conectar con la API de despacho.");
      } finally {
        processingAction.value = false;
      }
    }
    function startReject(req) {
      selectedRequest.value = req;
      rejectNotes.value = "";
      isRejectOpen.value = true;
    }
    async function confirmReject() {
      if (!selectedRequest.value) return;
      processingAction.value = true;
      try {
        const adminId = auth.user?.id_admin || 1;
        const res = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            rejectRequest: "true",
            id_request: String(selectedRequest.value.id_request),
            notes_dispatcher: rejectNotes.value,
            id_dispatched_by: String(adminId)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        if (String(res).trim() === "ok") {
          isRejectOpen.value = false;
          await fetchPending();
          await fetchHistory();
        } else {
          alert(res || "Error al rechazar la solicitud.");
        }
      } catch (e) {
        console.error("Reject error:", e);
        alert("Error al conectar con la API de despacho.");
      } finally {
        processingAction.value = false;
      }
    }
    const tabsItems = [
      { label: "Solicitudes Pendientes", icon: "i-lucide-clock" },
      { label: "Historial", icon: "i-lucide-history" }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UButton = _sfc_main$c;
      const _component_UTabs = _sfc_main$1;
      const _component_UIcon = _sfc_main$h;
      const _component_UBadge = _sfc_main$2;
      const _component_UModal = _sfc_main$3;
      const _component_UTextarea = _sfc_main$4;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="bg-slate-900/60 border border-slate-800 p-6 rounded-xl flex justify-between items-center"><div><h1 class="text-2xl font-extrabold text-white tracking-tight bg-gradient-to-r from-teal-400 to-emerald-300 bg-clip-text text-transparent"> Centro de Despachos </h1><p class="text-xs text-slate-400 mt-1"> Gestiona las solicitudes de despacho de sucursales del inventario principal. </p></div>`);
      _push(ssrRenderComponent(_component_UButton, {
        icon: "i-lucide-refresh-cw",
        color: "neutral",
        variant: "soft",
        size: "xs",
        onClick: ($event) => activeTab.value === 0 ? fetchPending() : fetchHistory()
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Refrescar `);
          } else {
            return [
              createTextVNode(" Refrescar ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      _push(ssrRenderComponent(_component_UTabs, {
        items: tabsItems,
        modelValue: activeTab.value,
        "onUpdate:modelValue": ($event) => activeTab.value = $event,
        class: "w-full"
      }, {
        item: withCtx(({ index }, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (index === 0) {
              _push2(`<div class="mt-4"${_scopeId}>`);
              if (loadingPending.value) {
                _push2(`<div class="flex justify-center py-12"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-loader-2",
                  class: "animate-spin w-8 h-8 text-teal-500"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else if (pendingRequests.value.length === 0) {
                _push2(`<div class="text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500 text-sm"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-check-circle",
                  class: "w-10 h-10 mx-auto mb-2 text-slate-650"
                }, null, _parent2, _scopeId));
                _push2(` No hay solicitudes de despacho pendientes. </div>`);
              } else {
                _push2(`<div class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden"${_scopeId}><table class="w-full text-left border-collapse text-sm text-slate-300"${_scopeId}><thead${_scopeId}><tr class="bg-slate-950 text-slate-400 border-b border-slate-800"${_scopeId}><th class="p-4"${_scopeId}>Fecha</th><th class="p-4"${_scopeId}>Solicitante</th><th class="p-4"${_scopeId}>Producto</th><th class="p-4"${_scopeId}>Cant. Solicitada</th><th class="p-4"${_scopeId}>Stock Disponible</th><th class="p-4"${_scopeId}>Notas</th><th class="p-4 text-right"${_scopeId}>Acciones</th></tr></thead><tbody${_scopeId}><!--[-->`);
                ssrRenderList(pendingRequests.value, (req) => {
                  _push2(`<tr class="border-b border-slate-850 hover:bg-slate-900/20"${_scopeId}><td class="p-4 font-mono text-xs"${_scopeId}>${ssrInterpolate(req.date_created_request)}</td><td class="p-4 font-semibold text-white"${_scopeId}>${ssrInterpolate(req.name_admin)}</td><td class="p-4"${_scopeId}>${ssrInterpolate(decodeURIComponent(req.title_product || "").replace(/\+/g, " "))}</td><td class="p-4"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UBadge, {
                    color: "info",
                    variant: "soft"
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(req.qty_request)}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(req.qty_request), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</td><td class="p-4"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UBadge, {
                    color: req.available_stock > 0 ? "emerald" : "rose",
                    variant: "subtle"
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(req.available_stock)}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(req.available_stock), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</td><td class="p-4 text-xs italic"${_scopeId}>${ssrInterpolate(req.notes_request || "-")}</td><td class="p-4 text-right flex gap-2 justify-end"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UButton, {
                    color: "emerald",
                    icon: "i-lucide-check",
                    size: "xs",
                    disabled: req.available_stock <= 0,
                    onClick: ($event) => startDispatch(req)
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(` Despachar `);
                      } else {
                        return [
                          createTextVNode(" Despachar ")
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(ssrRenderComponent(_component_UButton, {
                    color: "rose",
                    icon: "i-lucide-x",
                    size: "xs",
                    variant: "soft",
                    onClick: ($event) => startReject(req)
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(` Rechazar `);
                      } else {
                        return [
                          createTextVNode(" Rechazar ")
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</td></tr>`);
                });
                _push2(`<!--]--></tbody></table></div>`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            if (index === 1) {
              _push2(`<div class="mt-4"${_scopeId}>`);
              if (loadingHistory.value) {
                _push2(`<div class="flex justify-center py-12"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-loader-2",
                  class: "animate-spin w-8 h-8 text-teal-500"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else if (historyRequests.value.length === 0) {
                _push2(`<div class="text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500 text-sm"${_scopeId}> No hay historial de solicitudes registradas. </div>`);
              } else {
                _push2(`<div class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden"${_scopeId}><table class="w-full text-left border-collapse text-sm text-slate-300"${_scopeId}><thead${_scopeId}><tr class="bg-slate-950 text-slate-400 border-b border-slate-800"${_scopeId}><th class="p-4"${_scopeId}>Fecha</th><th class="p-4"${_scopeId}>Solicitante</th><th class="p-4"${_scopeId}>Producto</th><th class="p-4"${_scopeId}>Solicitado</th><th class="p-4"${_scopeId}>Despachado</th><th class="p-4"${_scopeId}>Estado</th><th class="p-4"${_scopeId}>Notas</th></tr></thead><tbody${_scopeId}><!--[-->`);
                ssrRenderList(historyRequests.value, (req) => {
                  _push2(`<tr class="border-b border-slate-850 hover:bg-slate-900/20"${_scopeId}><td class="p-4 font-mono text-xs"${_scopeId}>${ssrInterpolate(req.date_created_request)}</td><td class="p-4"${_scopeId}>${ssrInterpolate(req.name_admin)}</td><td class="p-4"${_scopeId}>${ssrInterpolate(decodeURIComponent(req.title_product || "").replace(/\+/g, " "))}</td><td class="p-4 font-mono"${_scopeId}>${ssrInterpolate(req.qty_request)}</td><td class="p-4 font-mono"${_scopeId}>${ssrInterpolate(req.qty_dispatched_request || "-")}</td><td class="p-4"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UBadge, {
                    color: req.status_request === "despachada" ? "emerald" : req.status_request === "rechazada" ? "rose" : "warning",
                    variant: "subtle",
                    class: "capitalize"
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(req.status_request)}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(req.status_request), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</td><td class="p-4 text-xs italic"${_scopeId}>${ssrInterpolate(req.notes_dispatcher_request || req.notes_request || "-")}</td></tr>`);
                });
                _push2(`<!--]--></tbody></table></div>`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              index === 0 ? (openBlock(), createBlock("div", {
                key: 0,
                class: "mt-4"
              }, [
                loadingPending.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "flex justify-center py-12"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "animate-spin w-8 h-8 text-teal-500"
                  })
                ])) : pendingRequests.value.length === 0 ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500 text-sm"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-check-circle",
                    class: "w-10 h-10 mx-auto mb-2 text-slate-650"
                  }),
                  createTextVNode(" No hay solicitudes de despacho pendientes. ")
                ])) : (openBlock(), createBlock("div", {
                  key: 2,
                  class: "bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden"
                }, [
                  createVNode("table", { class: "w-full text-left border-collapse text-sm text-slate-300" }, [
                    createVNode("thead", null, [
                      createVNode("tr", { class: "bg-slate-950 text-slate-400 border-b border-slate-800" }, [
                        createVNode("th", { class: "p-4" }, "Fecha"),
                        createVNode("th", { class: "p-4" }, "Solicitante"),
                        createVNode("th", { class: "p-4" }, "Producto"),
                        createVNode("th", { class: "p-4" }, "Cant. Solicitada"),
                        createVNode("th", { class: "p-4" }, "Stock Disponible"),
                        createVNode("th", { class: "p-4" }, "Notas"),
                        createVNode("th", { class: "p-4 text-right" }, "Acciones")
                      ])
                    ]),
                    createVNode("tbody", null, [
                      (openBlock(true), createBlock(Fragment, null, renderList(pendingRequests.value, (req) => {
                        return openBlock(), createBlock("tr", {
                          key: req.id_request,
                          class: "border-b border-slate-850 hover:bg-slate-900/20"
                        }, [
                          createVNode("td", { class: "p-4 font-mono text-xs" }, toDisplayString(req.date_created_request), 1),
                          createVNode("td", { class: "p-4 font-semibold text-white" }, toDisplayString(req.name_admin), 1),
                          createVNode("td", { class: "p-4" }, toDisplayString(decodeURIComponent(req.title_product || "").replace(/\+/g, " ")), 1),
                          createVNode("td", { class: "p-4" }, [
                            createVNode(_component_UBadge, {
                              color: "info",
                              variant: "soft"
                            }, {
                              default: withCtx(() => [
                                createTextVNode(toDisplayString(req.qty_request), 1)
                              ]),
                              _: 2
                            }, 1024)
                          ]),
                          createVNode("td", { class: "p-4" }, [
                            createVNode(_component_UBadge, {
                              color: req.available_stock > 0 ? "emerald" : "rose",
                              variant: "subtle"
                            }, {
                              default: withCtx(() => [
                                createTextVNode(toDisplayString(req.available_stock), 1)
                              ]),
                              _: 2
                            }, 1032, ["color"])
                          ]),
                          createVNode("td", { class: "p-4 text-xs italic" }, toDisplayString(req.notes_request || "-"), 1),
                          createVNode("td", { class: "p-4 text-right flex gap-2 justify-end" }, [
                            createVNode(_component_UButton, {
                              color: "emerald",
                              icon: "i-lucide-check",
                              size: "xs",
                              disabled: req.available_stock <= 0,
                              onClick: ($event) => startDispatch(req)
                            }, {
                              default: withCtx(() => [
                                createTextVNode(" Despachar ")
                              ]),
                              _: 1
                            }, 8, ["disabled", "onClick"]),
                            createVNode(_component_UButton, {
                              color: "rose",
                              icon: "i-lucide-x",
                              size: "xs",
                              variant: "soft",
                              onClick: ($event) => startReject(req)
                            }, {
                              default: withCtx(() => [
                                createTextVNode(" Rechazar ")
                              ]),
                              _: 1
                            }, 8, ["onClick"])
                          ])
                        ]);
                      }), 128))
                    ])
                  ])
                ]))
              ])) : createCommentVNode("", true),
              index === 1 ? (openBlock(), createBlock("div", {
                key: 1,
                class: "mt-4"
              }, [
                loadingHistory.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "flex justify-center py-12"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "animate-spin w-8 h-8 text-teal-500"
                  })
                ])) : historyRequests.value.length === 0 ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500 text-sm"
                }, " No hay historial de solicitudes registradas. ")) : (openBlock(), createBlock("div", {
                  key: 2,
                  class: "bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden"
                }, [
                  createVNode("table", { class: "w-full text-left border-collapse text-sm text-slate-300" }, [
                    createVNode("thead", null, [
                      createVNode("tr", { class: "bg-slate-950 text-slate-400 border-b border-slate-800" }, [
                        createVNode("th", { class: "p-4" }, "Fecha"),
                        createVNode("th", { class: "p-4" }, "Solicitante"),
                        createVNode("th", { class: "p-4" }, "Producto"),
                        createVNode("th", { class: "p-4" }, "Solicitado"),
                        createVNode("th", { class: "p-4" }, "Despachado"),
                        createVNode("th", { class: "p-4" }, "Estado"),
                        createVNode("th", { class: "p-4" }, "Notas")
                      ])
                    ]),
                    createVNode("tbody", null, [
                      (openBlock(true), createBlock(Fragment, null, renderList(historyRequests.value, (req) => {
                        return openBlock(), createBlock("tr", {
                          key: req.id_request,
                          class: "border-b border-slate-850 hover:bg-slate-900/20"
                        }, [
                          createVNode("td", { class: "p-4 font-mono text-xs" }, toDisplayString(req.date_created_request), 1),
                          createVNode("td", { class: "p-4" }, toDisplayString(req.name_admin), 1),
                          createVNode("td", { class: "p-4" }, toDisplayString(decodeURIComponent(req.title_product || "").replace(/\+/g, " ")), 1),
                          createVNode("td", { class: "p-4 font-mono" }, toDisplayString(req.qty_request), 1),
                          createVNode("td", { class: "p-4 font-mono" }, toDisplayString(req.qty_dispatched_request || "-"), 1),
                          createVNode("td", { class: "p-4" }, [
                            createVNode(_component_UBadge, {
                              color: req.status_request === "despachada" ? "emerald" : req.status_request === "rechazada" ? "rose" : "warning",
                              variant: "subtle",
                              class: "capitalize"
                            }, {
                              default: withCtx(() => [
                                createTextVNode(toDisplayString(req.status_request), 1)
                              ]),
                              _: 2
                            }, 1032, ["color"])
                          ]),
                          createVNode("td", { class: "p-4 text-xs italic" }, toDisplayString(req.notes_dispatcher_request || req.notes_request || "-"), 1)
                        ]);
                      }), 128))
                    ])
                  ])
                ]))
              ])) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UModal, {
        open: isDispatchOpen.value,
        "onUpdate:open": ($event) => isDispatchOpen.value = $event,
        title: "Confirmar Despacho"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (selectedRequest.value) {
              _push2(`<div class="space-y-4"${_scopeId}><div class="grid grid-cols-2 gap-4 bg-slate-950 p-3 rounded-lg border border-slate-850"${_scopeId}><div${_scopeId}><span class="text-[10px] text-slate-500 block"${_scopeId}>Solicitante:</span><span class="text-xs font-bold text-white"${_scopeId}>${ssrInterpolate(selectedRequest.value.name_admin)}</span></div><div${_scopeId}><span class="text-[10px] text-slate-500 block"${_scopeId}>Producto:</span><span class="text-xs font-bold text-white truncate block"${_scopeId}>${ssrInterpolate(decodeURIComponent(selectedRequest.value.title_product || "").replace(/\+/g, " "))}</span></div><div${_scopeId}><span class="text-[10px] text-slate-500 block"${_scopeId}>Cant. Solicitada:</span><span class="text-xs font-bold text-teal-400 font-mono"${_scopeId}>${ssrInterpolate(selectedRequest.value.qty_request)}</span></div><div${_scopeId}><span class="text-[10px] text-slate-500 block"${_scopeId}>Stock en Almacén:</span><span class="text-xs font-bold text-emerald-400 font-mono"${_scopeId}>${ssrInterpolate(selectedRequest.value.available_stock)}</span></div></div><div${_scopeId}><label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1"${_scopeId}>Cantidad a Despachar *</label><input${ssrRenderAttr("value", dispatchQty.value > 0 ? dispatchQty.value.toLocaleString("de-DE") : "")} type="text" inputmode="numeric" placeholder="0" class="block w-full py-2.5 px-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}></div><div${_scopeId}><label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1"${_scopeId}>Notas del Despachador</label>`);
              _push2(ssrRenderComponent(_component_UTextarea, {
                modelValue: dispatchNotes.value,
                "onUpdate:modelValue": ($event) => dispatchNotes.value = $event,
                rows: "2",
                placeholder: "Opcional...",
                class: "w-full"
              }, null, _parent2, _scopeId));
              _push2(`</div><div class="flex justify-end gap-3 pt-4 border-t border-slate-800"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UButton, {
                color: "neutral",
                variant: "ghost",
                onClick: ($event) => isDispatchOpen.value = false
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
                loading: processingAction.value,
                onClick: confirmDispatch
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Completar Despacho`);
                  } else {
                    return [
                      createTextVNode("Completar Despacho")
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
              selectedRequest.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "space-y-4"
              }, [
                createVNode("div", { class: "grid grid-cols-2 gap-4 bg-slate-950 p-3 rounded-lg border border-slate-850" }, [
                  createVNode("div", null, [
                    createVNode("span", { class: "text-[10px] text-slate-500 block" }, "Solicitante:"),
                    createVNode("span", { class: "text-xs font-bold text-white" }, toDisplayString(selectedRequest.value.name_admin), 1)
                  ]),
                  createVNode("div", null, [
                    createVNode("span", { class: "text-[10px] text-slate-500 block" }, "Producto:"),
                    createVNode("span", { class: "text-xs font-bold text-white truncate block" }, toDisplayString(decodeURIComponent(selectedRequest.value.title_product || "").replace(/\+/g, " ")), 1)
                  ]),
                  createVNode("div", null, [
                    createVNode("span", { class: "text-[10px] text-slate-500 block" }, "Cant. Solicitada:"),
                    createVNode("span", { class: "text-xs font-bold text-teal-400 font-mono" }, toDisplayString(selectedRequest.value.qty_request), 1)
                  ]),
                  createVNode("div", null, [
                    createVNode("span", { class: "text-[10px] text-slate-500 block" }, "Stock en Almacén:"),
                    createVNode("span", { class: "text-xs font-bold text-emerald-400 font-mono" }, toDisplayString(selectedRequest.value.available_stock), 1)
                  ])
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-[10px] font-semibold text-slate-350 uppercase mb-1" }, "Cantidad a Despachar *"),
                  createVNode("input", {
                    value: dispatchQty.value > 0 ? dispatchQty.value.toLocaleString("de-DE") : "",
                    type: "text",
                    inputmode: "numeric",
                    placeholder: "0",
                    class: "block w-full py-2.5 px-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50",
                    onInput: ($event) => onDispatchQtyInput($event),
                    onKeydown: ($event) => blockNegative($event)
                  }, null, 40, ["value", "onInput", "onKeydown"])
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-[10px] font-semibold text-slate-350 uppercase mb-1" }, "Notas del Despachador"),
                  createVNode(_component_UTextarea, {
                    modelValue: dispatchNotes.value,
                    "onUpdate:modelValue": ($event) => dispatchNotes.value = $event,
                    rows: "2",
                    placeholder: "Opcional...",
                    class: "w-full"
                  }, null, 8, ["modelValue", "onUpdate:modelValue"])
                ]),
                createVNode("div", { class: "flex justify-end gap-3 pt-4 border-t border-slate-800" }, [
                  createVNode(_component_UButton, {
                    color: "neutral",
                    variant: "ghost",
                    onClick: ($event) => isDispatchOpen.value = false
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Cancelar")
                    ]),
                    _: 1
                  }, 8, ["onClick"]),
                  createVNode(_component_UButton, {
                    color: "primary",
                    loading: processingAction.value,
                    onClick: confirmDispatch
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Completar Despacho")
                    ]),
                    _: 1
                  }, 8, ["loading"])
                ])
              ])) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UModal, {
        open: isRejectOpen.value,
        "onUpdate:open": ($event) => isRejectOpen.value = $event,
        title: "Rechazar Solicitud de Despacho"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (selectedRequest.value) {
              _push2(`<div class="space-y-4"${_scopeId}><p class="text-sm text-slate-300"${_scopeId}> ¿Confirmas el rechazo de la solicitud de ${ssrInterpolate(selectedRequest.value.qty_request)} u de <strong${_scopeId}>${ssrInterpolate(decodeURIComponent(selectedRequest.value.title_product || "").replace(/\+/g, " "))}</strong>? </p><div${_scopeId}><label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1"${_scopeId}>Motivo de Rechazo</label>`);
              _push2(ssrRenderComponent(_component_UTextarea, {
                modelValue: rejectNotes.value,
                "onUpdate:modelValue": ($event) => rejectNotes.value = $event,
                rows: "2",
                placeholder: "Escribe el motivo del rechazo aquí...",
                class: "w-full",
                required: ""
              }, null, _parent2, _scopeId));
              _push2(`</div><div class="flex justify-end gap-3 pt-4 border-t border-slate-800"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UButton, {
                color: "neutral",
                variant: "ghost",
                onClick: ($event) => isRejectOpen.value = false
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
                color: "rose",
                loading: processingAction.value,
                onClick: confirmReject
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Rechazar Solicitud`);
                  } else {
                    return [
                      createTextVNode("Rechazar Solicitud")
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
              selectedRequest.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "space-y-4"
              }, [
                createVNode("p", { class: "text-sm text-slate-300" }, [
                  createTextVNode(" ¿Confirmas el rechazo de la solicitud de " + toDisplayString(selectedRequest.value.qty_request) + " u de ", 1),
                  createVNode("strong", null, toDisplayString(decodeURIComponent(selectedRequest.value.title_product || "").replace(/\+/g, " ")), 1),
                  createTextVNode("? ")
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-[10px] font-semibold text-slate-350 uppercase mb-1" }, "Motivo de Rechazo"),
                  createVNode(_component_UTextarea, {
                    modelValue: rejectNotes.value,
                    "onUpdate:modelValue": ($event) => rejectNotes.value = $event,
                    rows: "2",
                    placeholder: "Escribe el motivo del rechazo aquí...",
                    class: "w-full",
                    required: ""
                  }, null, 8, ["modelValue", "onUpdate:modelValue"])
                ]),
                createVNode("div", { class: "flex justify-end gap-3 pt-4 border-t border-slate-800" }, [
                  createVNode(_component_UButton, {
                    color: "neutral",
                    variant: "ghost",
                    onClick: ($event) => isRejectOpen.value = false
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Cancelar")
                    ]),
                    _: 1
                  }, 8, ["onClick"]),
                  createVNode(_component_UButton, {
                    color: "rose",
                    loading: processingAction.value,
                    onClick: confirmReject
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Rechazar Solicitud")
                    ]),
                    _: 1
                  }, 8, ["loading"])
                ])
              ])) : createCommentVNode("", true)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/despachos.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=despachos-CVUUtFGs.mjs.map
