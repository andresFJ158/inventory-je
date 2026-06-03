import { I as useAuthStore, i as _sfc_main$c, g as _sfc_main$h, j as _sfc_main$f } from './server.mjs';
import { _ as _sfc_main$1 } from './Tabs-fNkZ4Vjh.mjs';
import { _ as _sfc_main$2 } from './Badge-LaytOPGg.mjs';
import { _ as _sfc_main$3 } from './Modal-ulV1aY0B.mjs';
import { _ as _sfc_main$4 } from './Select-Bk-d3PfC.mjs';
import { _ as _sfc_main$5 } from './Textarea-DVGiVqM_.mjs';
import { defineComponent, ref, computed, mergeProps, withCtx, createTextVNode, toDisplayString, openBlock, createBlock, createVNode, Fragment, renderList, createCommentVNode, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderAttr } from 'vue/server-renderer';
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
import './overlay-6I-jXWFz.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "almacen",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    function onAssignQtyInput(e) {
      const input = e.target;
      const raw = input.value.replace(/[^\d]/g, "");
      const n = parseInt(raw, 10) || 0;
      assignQty.value = Math.max(0, n);
      input.value = n > 0 ? n.toLocaleString("de-DE") : "";
    }
    function blockNegative(e) {
      if (e.key === "-" || e.key === "e" || e.key === "E") e.preventDefault();
    }
    const products = ref([]);
    const admins = ref([]);
    const officesMap = ref({});
    const assignedMap = ref({});
    const subWarehouses = ref([]);
    const movements = ref([]);
    const loadingStock = ref(true);
    const loadingSubs = ref(true);
    const loadingMoves = ref(true);
    const activeTab = ref(0);
    const isAssignOpen = ref(false);
    const selectedProduct = ref(null);
    const assignUser = ref("");
    const assignQty = ref(1);
    const assignNotes = ref("");
    const processingAction = ref(false);
    const officesList = ref([]);
    const isTransferOpen = ref(false);
    const transferOffice = ref("");
    const transferQty = ref(1);
    const transferNotes = ref("");
    const apiHeaders = {
      Authorization: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy"
    };
    async function fetchStock() {
      loadingStock.value = true;
      try {
        const officeId = auth.officeId || 3;
        const prodData = await $fetch(`/api/relations?rel=products,product_inventory&type=product,inventory&linkTo=id_office_inventory,status_inventory&equalTo=${officeId},1`, {
          headers: apiHeaders
        });
        products.value = prodData.status === 200 && prodData.results ? prodData.results : [];
        const adminId = auth.user?.id_admin || 1;
        const assignData = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            getAssignedByOffice: "true",
            id_office: String(officeId),
            id_dispatcher: String(adminId)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const assignResults = typeof assignData === "string" ? JSON.parse(assignData) : assignData;
        const map = {};
        if (Array.isArray(assignResults)) {
          assignResults.forEach((item) => {
            map[item.id_product] = parseInt(item.total_assigned) || 0;
          });
        }
        assignedMap.value = map;
      } catch (e) {
        console.error("Error fetching warehouse stock:", e);
        products.value = [];
      } finally {
        loadingStock.value = false;
      }
    }
    async function fetchSubWarehouses() {
      loadingSubs.value = true;
      try {
        const officeId = auth.officeId || 3;
        const response = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            getSubWarehousesDetail: "true",
            id_office: String(officeId)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        subWarehouses.value = Array.isArray(data) ? data : [];
      } catch (e) {
        console.error("Error fetching sub-warehouses:", e);
        subWarehouses.value = [];
      } finally {
        loadingSubs.value = false;
      }
    }
    async function fetchMovements() {
      loadingMoves.value = true;
      try {
        const officeId = auth.officeId || 3;
        const adminId = auth.user?.id_admin || 1;
        const response = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            getWarehouseMovements: "true",
            id_office: String(officeId),
            id_dispatcher: String(adminId)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        movements.value = Array.isArray(data) ? data : [];
      } catch (e) {
        console.error("Error fetching movements:", e);
        movements.value = [];
      } finally {
        loadingMoves.value = false;
      }
    }
    function startAssign(prod) {
      selectedProduct.value = prod;
      assignQty.value = 1;
      assignUser.value = "";
      assignNotes.value = "";
      isAssignOpen.value = true;
    }
    async function confirmAssign() {
      if (!selectedProduct.value || !assignUser.value) {
        alert("Por favor completa todos los campos requeridos.");
        return;
      }
      const availableStock = parseFloat(selectedProduct.value.stock_inventory) || 0;
      if (assignQty.value <= 0 || assignQty.value > availableStock) {
        alert("La cantidad ingresada no es válida o supera el stock disponible.");
        return;
      }
      processingAction.value = true;
      try {
        const officeId = auth.officeId || 3;
        const adminId = auth.user?.id_admin || 1;
        const res = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            assignToSubWarehouse: "true",
            id_product: String(selectedProduct.value.id_product),
            id_admin_dest: assignUser.value,
            qty: String(assignQty.value),
            notes: assignNotes.value,
            id_office: String(officeId),
            id_dispatched_by: String(adminId)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        if (String(res).trim() === "ok") {
          isAssignOpen.value = false;
          await fetchStock();
          await fetchSubWarehouses();
          await fetchMovements();
        } else {
          alert(res || "Error al asignar stock.");
        }
      } catch (e) {
        console.error("Assign error:", e);
        alert("Error al conectar con la API de almacenes.");
      } finally {
        processingAction.value = false;
      }
    }
    const destinationOffices = computed(() => {
      const currentOfficeId = String(auth.officeId || 3);
      return officesList.value.filter((o) => String(o.id_office) !== currentOfficeId).map((o) => ({
        value: String(o.id_office),
        label: decodeURIComponent(o.title_office).replace(/\+/g, " ")
      }));
    });
    function onTransferQtyInput(e) {
      const input = e.target;
      const raw = input.value.replace(/[^\d]/g, "");
      const n = parseInt(raw, 10) || 0;
      transferQty.value = Math.max(0, n);
      input.value = n > 0 ? n.toLocaleString("de-DE") : "";
    }
    function startTransfer(prod) {
      selectedProduct.value = prod;
      transferQty.value = 1;
      transferOffice.value = "";
      transferNotes.value = "";
      isTransferOpen.value = true;
    }
    async function confirmTransfer() {
      if (!selectedProduct.value || !transferOffice.value) {
        alert("Por favor completa todos los campos requeridos.");
        return;
      }
      const availableStock = parseFloat(selectedProduct.value.stock_inventory) || 0;
      if (transferQty.value <= 0 || transferQty.value > availableStock) {
        alert("La cantidad ingresada no es válida o supera el stock disponible.");
        return;
      }
      processingAction.value = true;
      try {
        const officeId = auth.officeId || 3;
        const adminId = auth.user?.id_admin || 1;
        const res = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body: new URLSearchParams({
            transferStockBetweenOffices: "true",
            id_product: String(selectedProduct.value.id_product),
            id_office_source: String(officeId),
            id_office_dest: transferOffice.value,
            qty: String(transferQty.value),
            notes: transferNotes.value,
            id_dispatched_by: String(adminId)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        if (String(res).trim() === "ok") {
          isTransferOpen.value = false;
          await fetchStock();
          await fetchSubWarehouses();
          await fetchMovements();
        } else {
          alert(res || "Error al traspasar stock.");
        }
      } catch (e) {
        console.error("Transfer error:", e);
        alert("Error al conectar con la API de traspasos.");
      } finally {
        processingAction.value = false;
      }
    }
    const tabsItems = [
      { label: "Inventario Principal", icon: "i-lucide-boxes" },
      { label: "Sub-Almacenes", icon: "i-lucide-users" },
      { label: "Movimientos", icon: "i-lucide-arrow-right-left" }
    ];
    function exportCSV() {
      if (activeTab.value === 0) {
        if (products.value.length === 0) return;
        const headers = ["SKU", "Producto", "Stock Total", "Asignado", "Disponible"];
        const rows = products.value.map((p) => [
          p.sku_product,
          decodeURIComponent(p.title_product || "").replace(/\+/g, " "),
          (parseFloat(p.stock_inventory) || 0) + (assignedMap.value[p.id_product] || 0),
          assignedMap.value[p.id_product] || 0,
          parseFloat(p.stock_inventory) || 0
        ]);
        const csvContent = "\uFEFF" + [headers.join(","), ...rows.map((r) => r.map((v) => `"${v}"`).join(","))].join("\n");
        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const link = (void 0).createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `inventario_almacen_${(/* @__PURE__ */ new Date()).toISOString().split("T")[0]}.csv`);
        (void 0).body.appendChild(link);
        link.click();
        (void 0).body.removeChild(link);
      } else if (activeTab.value === 2) {
        if (movements.value.length === 0) return;
        const headers = ["Fecha", "Tipo", "Producto", "Cantidad", "Destinatario", "Sucursal Destino", "Despachador", "Notas"];
        const rows = movements.value.map((m) => [
          m.date_created_assignment,
          m.type_assignment,
          decodeURIComponent(m.title_product || "").replace(/\+/g, " "),
          m.qty_assignment,
          m.name_admin,
          m.office_name ? decodeURIComponent(m.office_name).replace(/\+/g, " ") : "",
          m.dispatcher_name,
          m.notes_assignment || ""
        ]);
        const csvContent = "\uFEFF" + [headers.join(","), ...rows.map((r) => r.map((v) => `"${v}"`).join(","))].join("\n");
        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const link = (void 0).createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `movimientos_almacen_${(/* @__PURE__ */ new Date()).toISOString().split("T")[0]}.csv`);
        (void 0).body.appendChild(link);
        link.click();
        (void 0).body.removeChild(link);
      } else {
        alert("Exportación disponible para inventario y movimientos.");
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UButton = _sfc_main$c;
      const _component_UTabs = _sfc_main$1;
      const _component_UIcon = _sfc_main$h;
      const _component_UAvatar = _sfc_main$f;
      const _component_UBadge = _sfc_main$2;
      const _component_UModal = _sfc_main$3;
      const _component_USelect = _sfc_main$4;
      const _component_UTextarea = _sfc_main$5;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="bg-slate-900/60 border border-slate-800 p-6 rounded-xl flex justify-between items-center"><div><h1 class="text-2xl font-extrabold text-white tracking-tight bg-gradient-to-r from-teal-400 to-emerald-300 bg-clip-text text-transparent"> Almacén Principal </h1><p class="text-xs text-slate-400 mt-1"> Controla las existencias en almacén y distribuye a los sub-almacenes de tus vendedores. </p></div><div class="flex items-center gap-2">`);
      if (activeTab.value === 0 || activeTab.value === 2) {
        _push(ssrRenderComponent(_component_UButton, {
          icon: "i-lucide-download",
          color: "neutral",
          variant: "outline",
          size: "xs",
          onClick: exportCSV
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Exportar CSV `);
            } else {
              return [
                createTextVNode(" Exportar CSV ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(_component_UButton, {
        icon: "i-lucide-refresh-cw",
        color: "neutral",
        variant: "soft",
        size: "xs",
        onClick: ($event) => activeTab.value === 0 ? fetchStock() : activeTab.value === 1 ? fetchSubWarehouses() : fetchMovements()
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
      _push(`</div></div>`);
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
              if (loadingStock.value) {
                _push2(`<div class="flex justify-center py-12"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-loader-2",
                  class: "animate-spin w-8 h-8 text-teal-500"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else if (products.value.length === 0) {
                _push2(`<div class="text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500"${_scopeId}> No se encontraron productos en el almacén principal. </div>`);
              } else {
                _push2(`<div class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden"${_scopeId}><table class="w-full text-left border-collapse text-sm text-slate-300"${_scopeId}><thead${_scopeId}><tr class="bg-slate-950 text-slate-400 border-b border-slate-800"${_scopeId}><th class="p-4"${_scopeId}>Imagen</th><th class="p-4"${_scopeId}>SKU</th><th class="p-4"${_scopeId}>Producto</th><th class="p-4"${_scopeId}>Stock Total</th><th class="p-4"${_scopeId}>Asignado</th><th class="p-4"${_scopeId}>Disponible (Almacén)</th><th class="p-4 text-right"${_scopeId}>Distribución</th></tr></thead><tbody${_scopeId}><!--[-->`);
                ssrRenderList(products.value, (prod) => {
                  _push2(`<tr class="border-b border-slate-850 hover:bg-slate-900/20"${_scopeId}><td class="p-4"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UAvatar, {
                    src: prod.img_product ? decodeURIComponent(prod.img_product).replace(/\+/g, " ") : "/views/assets/img/multimedia.png",
                    size: "sm",
                    class: "bg-slate-800"
                  }, null, _parent2, _scopeId));
                  _push2(`</td><td class="p-4 font-mono text-xs"${_scopeId}>${ssrInterpolate(prod.sku_product)}</td><td class="p-4 font-semibold text-white"${_scopeId}>${ssrInterpolate(decodeURIComponent(prod.title_product || "").replace(/\+/g, " "))}</td><td class="p-4"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UBadge, {
                    color: "success",
                    variant: "soft",
                    class: "font-mono text-xs"
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate((parseFloat(prod.stock_inventory) || 0) + (assignedMap.value[prod.id_product] || 0))}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString((parseFloat(prod.stock_inventory) || 0) + (assignedMap.value[prod.id_product] || 0)), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</td><td class="p-4"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UBadge, {
                    color: "info",
                    variant: "soft",
                    class: "font-mono text-xs"
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(assignedMap.value[prod.id_product] || 0)}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(assignedMap.value[prod.id_product] || 0), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</td><td class="p-4"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UBadge, {
                    color: "primary",
                    variant: "solid",
                    class: "font-mono text-xs"
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(parseFloat(prod.stock_inventory) || 0)}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(parseFloat(prod.stock_inventory) || 0), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</td><td class="p-4 text-right"${_scopeId}><div class="flex justify-end gap-2"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UButton, {
                    color: "teal",
                    icon: "i-lucide-share-2",
                    size: "xs",
                    disabled: (parseFloat(prod.stock_inventory) || 0) <= 0,
                    onClick: ($event) => startAssign(prod)
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(` Asignar `);
                      } else {
                        return [
                          createTextVNode(" Asignar ")
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(ssrRenderComponent(_component_UButton, {
                    color: "indigo",
                    icon: "i-lucide-arrow-right-left",
                    size: "xs",
                    disabled: (parseFloat(prod.stock_inventory) || 0) <= 0,
                    onClick: ($event) => startTransfer(prod)
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(` Traspasar `);
                      } else {
                        return [
                          createTextVNode(" Traspasar ")
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</div></td></tr>`);
                });
                _push2(`<!--]--></tbody></table></div>`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            if (index === 1) {
              _push2(`<div class="mt-4"${_scopeId}>`);
              if (loadingSubs.value) {
                _push2(`<div class="flex justify-center py-12"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-loader-2",
                  class: "animate-spin w-8 h-8 text-teal-500"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else if (subWarehouses.value.length === 0) {
                _push2(`<div class="text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500"${_scopeId}> No se encontraron sub-almacenes asociados. </div>`);
              } else {
                _push2(`<div class="grid grid-cols-1 md:grid-cols-2 gap-4"${_scopeId}><!--[-->`);
                ssrRenderList(subWarehouses.value, (sw) => {
                  _push2(`<div class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden flex flex-col justify-between"${_scopeId}><div class="p-4 bg-slate-950/60 border-b border-slate-850 flex justify-between items-center"${_scopeId}><div${_scopeId}><h3 class="font-bold text-white text-sm flex items-center gap-1.5"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UIcon, {
                    name: "i-lucide-user",
                    class: "text-teal-400"
                  }, null, _parent2, _scopeId));
                  _push2(` ${ssrInterpolate(sw.name_admin)}</h3><span class="text-[10px] text-slate-400 block mt-0.5"${_scopeId}> Sucursal: ${ssrInterpolate(sw.title_office ? decodeURIComponent(sw.title_office).replace(/\+/g, " ") : "Sin Sucursal")}</span></div>`);
                  _push2(ssrRenderComponent(_component_UBadge, {
                    color: "emerald",
                    size: "xs"
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(sw.name_sub_warehouse)}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(sw.name_sub_warehouse), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</div><div class="p-0"${_scopeId}>`);
                  if (sw.products && sw.products.length > 0) {
                    _push2(`<table class="w-full text-left text-xs border-collapse"${_scopeId}><thead${_scopeId}><tr class="bg-slate-950/30 text-slate-400 border-b border-slate-850"${_scopeId}><th class="p-2.5"${_scopeId}>Producto</th><th class="p-2.5 text-right"${_scopeId}>Stock Asignado</th></tr></thead><tbody${_scopeId}><!--[-->`);
                    ssrRenderList(sw.products, (p) => {
                      _push2(`<tr class="border-b border-slate-850/40 hover:bg-slate-900/10"${_scopeId}><td class="p-2.5 text-slate-300"${_scopeId}>${ssrInterpolate(decodeURIComponent(p.title_product || "").replace(/\+/g, " "))}</td><td class="p-2.5 text-right font-mono font-bold text-teal-400"${_scopeId}>${ssrInterpolate(p.stock)}</td></tr>`);
                    });
                    _push2(`<!--]--></tbody></table>`);
                  } else {
                    _push2(`<div class="text-center py-6 text-slate-500 text-xs"${_scopeId}> Sin productos asignados a este sub-almacén. </div>`);
                  }
                  _push2(`</div></div>`);
                });
                _push2(`<!--]--></div>`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            if (index === 2) {
              _push2(`<div class="mt-4"${_scopeId}>`);
              if (loadingMoves.value) {
                _push2(`<div class="flex justify-center py-12"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-loader-2",
                  class: "animate-spin w-8 h-8 text-teal-500"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else if (movements.value.length === 0) {
                _push2(`<div class="text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500"${_scopeId}> No se encontraron movimientos registrados en la bitácora. </div>`);
              } else {
                _push2(`<div class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden"${_scopeId}><table class="w-full text-left border-collapse text-sm text-slate-300"${_scopeId}><thead${_scopeId}><tr class="bg-slate-950 text-slate-400 border-b border-slate-800"${_scopeId}><th class="p-4"${_scopeId}>Fecha</th><th class="p-4"${_scopeId}>Tipo</th><th class="p-4"${_scopeId}>Producto</th><th class="p-4 text-center"${_scopeId}>Cant.</th><th class="p-4"${_scopeId}>Destinatario</th><th class="p-4"${_scopeId}>Sucursal Destino</th><th class="p-4"${_scopeId}>Despachador</th><th class="p-4"${_scopeId}>Notas</th></tr></thead><tbody${_scopeId}><!--[-->`);
                ssrRenderList(movements.value, (m) => {
                  _push2(`<tr class="border-b border-slate-850 hover:bg-slate-900/20"${_scopeId}><td class="p-4 font-mono text-xs"${_scopeId}>${ssrInterpolate(m.date_created_assignment)}</td><td class="p-4"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UBadge, {
                    color: m.type_assignment === "despacho" ? "indigo" : m.type_assignment === "devolucion" ? "warning" : m.type_assignment === "traspaso" ? "success" : "rose",
                    variant: "subtle",
                    class: "capitalize font-semibold"
                  }, {
                    default: withCtx((_, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(m.type_assignment)}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(m.type_assignment), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</td><td class="p-4 font-semibold text-white"${_scopeId}>${ssrInterpolate(decodeURIComponent(m.title_product || "").replace(/\+/g, " "))}</td><td class="p-4 text-center font-mono font-bold"${_scopeId}>${ssrInterpolate(m.qty_assignment)}</td><td class="p-4"${_scopeId}>${ssrInterpolate(m.name_admin)}</td><td class="p-4"${_scopeId}>${ssrInterpolate(m.office_name ? decodeURIComponent(m.office_name).replace(/\+/g, " ") : "-")}</td><td class="p-4 text-xs"${_scopeId}>${ssrInterpolate(m.dispatcher_name)}</td><td class="p-4 text-xs italic"${_scopeId}>${ssrInterpolate(m.notes_assignment || "-")}</td></tr>`);
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
                loadingStock.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "flex justify-center py-12"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "animate-spin w-8 h-8 text-teal-500"
                  })
                ])) : products.value.length === 0 ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500"
                }, " No se encontraron productos en el almacén principal. ")) : (openBlock(), createBlock("div", {
                  key: 2,
                  class: "bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden"
                }, [
                  createVNode("table", { class: "w-full text-left border-collapse text-sm text-slate-300" }, [
                    createVNode("thead", null, [
                      createVNode("tr", { class: "bg-slate-950 text-slate-400 border-b border-slate-800" }, [
                        createVNode("th", { class: "p-4" }, "Imagen"),
                        createVNode("th", { class: "p-4" }, "SKU"),
                        createVNode("th", { class: "p-4" }, "Producto"),
                        createVNode("th", { class: "p-4" }, "Stock Total"),
                        createVNode("th", { class: "p-4" }, "Asignado"),
                        createVNode("th", { class: "p-4" }, "Disponible (Almacén)"),
                        createVNode("th", { class: "p-4 text-right" }, "Distribución")
                      ])
                    ]),
                    createVNode("tbody", null, [
                      (openBlock(true), createBlock(Fragment, null, renderList(products.value, (prod) => {
                        return openBlock(), createBlock("tr", {
                          key: prod.id_product,
                          class: "border-b border-slate-850 hover:bg-slate-900/20"
                        }, [
                          createVNode("td", { class: "p-4" }, [
                            createVNode(_component_UAvatar, {
                              src: prod.img_product ? decodeURIComponent(prod.img_product).replace(/\+/g, " ") : "/views/assets/img/multimedia.png",
                              size: "sm",
                              class: "bg-slate-800"
                            }, null, 8, ["src"])
                          ]),
                          createVNode("td", { class: "p-4 font-mono text-xs" }, toDisplayString(prod.sku_product), 1),
                          createVNode("td", { class: "p-4 font-semibold text-white" }, toDisplayString(decodeURIComponent(prod.title_product || "").replace(/\+/g, " ")), 1),
                          createVNode("td", { class: "p-4" }, [
                            createVNode(_component_UBadge, {
                              color: "success",
                              variant: "soft",
                              class: "font-mono text-xs"
                            }, {
                              default: withCtx(() => [
                                createTextVNode(toDisplayString((parseFloat(prod.stock_inventory) || 0) + (assignedMap.value[prod.id_product] || 0)), 1)
                              ]),
                              _: 2
                            }, 1024)
                          ]),
                          createVNode("td", { class: "p-4" }, [
                            createVNode(_component_UBadge, {
                              color: "info",
                              variant: "soft",
                              class: "font-mono text-xs"
                            }, {
                              default: withCtx(() => [
                                createTextVNode(toDisplayString(assignedMap.value[prod.id_product] || 0), 1)
                              ]),
                              _: 2
                            }, 1024)
                          ]),
                          createVNode("td", { class: "p-4" }, [
                            createVNode(_component_UBadge, {
                              color: "primary",
                              variant: "solid",
                              class: "font-mono text-xs"
                            }, {
                              default: withCtx(() => [
                                createTextVNode(toDisplayString(parseFloat(prod.stock_inventory) || 0), 1)
                              ]),
                              _: 2
                            }, 1024)
                          ]),
                          createVNode("td", { class: "p-4 text-right" }, [
                            createVNode("div", { class: "flex justify-end gap-2" }, [
                              createVNode(_component_UButton, {
                                color: "teal",
                                icon: "i-lucide-share-2",
                                size: "xs",
                                disabled: (parseFloat(prod.stock_inventory) || 0) <= 0,
                                onClick: ($event) => startAssign(prod)
                              }, {
                                default: withCtx(() => [
                                  createTextVNode(" Asignar ")
                                ]),
                                _: 1
                              }, 8, ["disabled", "onClick"]),
                              createVNode(_component_UButton, {
                                color: "indigo",
                                icon: "i-lucide-arrow-right-left",
                                size: "xs",
                                disabled: (parseFloat(prod.stock_inventory) || 0) <= 0,
                                onClick: ($event) => startTransfer(prod)
                              }, {
                                default: withCtx(() => [
                                  createTextVNode(" Traspasar ")
                                ]),
                                _: 1
                              }, 8, ["disabled", "onClick"])
                            ])
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
                loadingSubs.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "flex justify-center py-12"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "animate-spin w-8 h-8 text-teal-500"
                  })
                ])) : subWarehouses.value.length === 0 ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500"
                }, " No se encontraron sub-almacenes asociados. ")) : (openBlock(), createBlock("div", {
                  key: 2,
                  class: "grid grid-cols-1 md:grid-cols-2 gap-4"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(subWarehouses.value, (sw) => {
                    return openBlock(), createBlock("div", {
                      key: sw.id_sub_warehouse,
                      class: "bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden flex flex-col justify-between"
                    }, [
                      createVNode("div", { class: "p-4 bg-slate-950/60 border-b border-slate-850 flex justify-between items-center" }, [
                        createVNode("div", null, [
                          createVNode("h3", { class: "font-bold text-white text-sm flex items-center gap-1.5" }, [
                            createVNode(_component_UIcon, {
                              name: "i-lucide-user",
                              class: "text-teal-400"
                            }),
                            createTextVNode(" " + toDisplayString(sw.name_admin), 1)
                          ]),
                          createVNode("span", { class: "text-[10px] text-slate-400 block mt-0.5" }, " Sucursal: " + toDisplayString(sw.title_office ? decodeURIComponent(sw.title_office).replace(/\+/g, " ") : "Sin Sucursal"), 1)
                        ]),
                        createVNode(_component_UBadge, {
                          color: "emerald",
                          size: "xs"
                        }, {
                          default: withCtx(() => [
                            createTextVNode(toDisplayString(sw.name_sub_warehouse), 1)
                          ]),
                          _: 2
                        }, 1024)
                      ]),
                      createVNode("div", { class: "p-0" }, [
                        sw.products && sw.products.length > 0 ? (openBlock(), createBlock("table", {
                          key: 0,
                          class: "w-full text-left text-xs border-collapse"
                        }, [
                          createVNode("thead", null, [
                            createVNode("tr", { class: "bg-slate-950/30 text-slate-400 border-b border-slate-850" }, [
                              createVNode("th", { class: "p-2.5" }, "Producto"),
                              createVNode("th", { class: "p-2.5 text-right" }, "Stock Asignado")
                            ])
                          ]),
                          createVNode("tbody", null, [
                            (openBlock(true), createBlock(Fragment, null, renderList(sw.products, (p) => {
                              return openBlock(), createBlock("tr", {
                                key: p.title_product,
                                class: "border-b border-slate-850/40 hover:bg-slate-900/10"
                              }, [
                                createVNode("td", { class: "p-2.5 text-slate-300" }, toDisplayString(decodeURIComponent(p.title_product || "").replace(/\+/g, " ")), 1),
                                createVNode("td", { class: "p-2.5 text-right font-mono font-bold text-teal-400" }, toDisplayString(p.stock), 1)
                              ]);
                            }), 128))
                          ])
                        ])) : (openBlock(), createBlock("div", {
                          key: 1,
                          class: "text-center py-6 text-slate-500 text-xs"
                        }, " Sin productos asignados a este sub-almacén. "))
                      ])
                    ]);
                  }), 128))
                ]))
              ])) : createCommentVNode("", true),
              index === 2 ? (openBlock(), createBlock("div", {
                key: 2,
                class: "mt-4"
              }, [
                loadingMoves.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "flex justify-center py-12"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "animate-spin w-8 h-8 text-teal-500"
                  })
                ])) : movements.value.length === 0 ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "text-center py-12 bg-slate-900/40 border border-slate-850 rounded-xl text-slate-500"
                }, " No se encontraron movimientos registrados en la bitácora. ")) : (openBlock(), createBlock("div", {
                  key: 2,
                  class: "bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden"
                }, [
                  createVNode("table", { class: "w-full text-left border-collapse text-sm text-slate-300" }, [
                    createVNode("thead", null, [
                      createVNode("tr", { class: "bg-slate-950 text-slate-400 border-b border-slate-800" }, [
                        createVNode("th", { class: "p-4" }, "Fecha"),
                        createVNode("th", { class: "p-4" }, "Tipo"),
                        createVNode("th", { class: "p-4" }, "Producto"),
                        createVNode("th", { class: "p-4 text-center" }, "Cant."),
                        createVNode("th", { class: "p-4" }, "Destinatario"),
                        createVNode("th", { class: "p-4" }, "Sucursal Destino"),
                        createVNode("th", { class: "p-4" }, "Despachador"),
                        createVNode("th", { class: "p-4" }, "Notas")
                      ])
                    ]),
                    createVNode("tbody", null, [
                      (openBlock(true), createBlock(Fragment, null, renderList(movements.value, (m) => {
                        return openBlock(), createBlock("tr", {
                          key: m.date_created_assignment,
                          class: "border-b border-slate-850 hover:bg-slate-900/20"
                        }, [
                          createVNode("td", { class: "p-4 font-mono text-xs" }, toDisplayString(m.date_created_assignment), 1),
                          createVNode("td", { class: "p-4" }, [
                            createVNode(_component_UBadge, {
                              color: m.type_assignment === "despacho" ? "indigo" : m.type_assignment === "devolucion" ? "warning" : m.type_assignment === "traspaso" ? "success" : "rose",
                              variant: "subtle",
                              class: "capitalize font-semibold"
                            }, {
                              default: withCtx(() => [
                                createTextVNode(toDisplayString(m.type_assignment), 1)
                              ]),
                              _: 2
                            }, 1032, ["color"])
                          ]),
                          createVNode("td", { class: "p-4 font-semibold text-white" }, toDisplayString(decodeURIComponent(m.title_product || "").replace(/\+/g, " ")), 1),
                          createVNode("td", { class: "p-4 text-center font-mono font-bold" }, toDisplayString(m.qty_assignment), 1),
                          createVNode("td", { class: "p-4" }, toDisplayString(m.name_admin), 1),
                          createVNode("td", { class: "p-4" }, toDisplayString(m.office_name ? decodeURIComponent(m.office_name).replace(/\+/g, " ") : "-"), 1),
                          createVNode("td", { class: "p-4 text-xs" }, toDisplayString(m.dispatcher_name), 1),
                          createVNode("td", { class: "p-4 text-xs italic" }, toDisplayString(m.notes_assignment || "-"), 1)
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
        open: isAssignOpen.value,
        "onUpdate:open": ($event) => isAssignOpen.value = $event,
        title: "Asignar Stock a Vendedor"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (selectedProduct.value) {
              _push2(`<div class="space-y-4"${_scopeId}><div class="bg-slate-950 p-3 rounded-lg border border-slate-850 flex justify-between items-center"${_scopeId}><div${_scopeId}><span class="text-[10px] text-slate-500 block"${_scopeId}>Producto:</span><span class="text-xs font-bold text-white"${_scopeId}>${ssrInterpolate(decodeURIComponent(selectedProduct.value.title_product).replace(/\+/g, " "))}</span></div><div${_scopeId}><span class="text-[10px] text-slate-500 block text-right"${_scopeId}>Disponible en Almacén:</span><span class="text-sm font-bold text-teal-400 font-mono block text-right"${_scopeId}>${ssrInterpolate(parseFloat(selectedProduct.value.stock_inventory) || 0)} u </span></div></div><div${_scopeId}><label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1"${_scopeId}>Destinatario (Vendedor/Cajero) *</label>`);
              _push2(ssrRenderComponent(_component_USelect, {
                modelValue: assignUser.value,
                "onUpdate:modelValue": ($event) => assignUser.value = $event,
                items: admins.value.map((a) => ({ value: String(a.id_admin), label: `${a.name_admin} (${officesMap.value[a.id_office_admin] || "Sin Sucursal"})` })),
                placeholder: "Seleccionar destinatario...",
                class: "w-full",
                required: ""
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1"${_scopeId}>Cantidad a Asignar *</label><input${ssrRenderAttr("value", assignQty.value > 0 ? assignQty.value.toLocaleString("de-DE") : "")} type="text" inputmode="numeric" placeholder="0" class="block w-full py-2.5 px-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}></div><div${_scopeId}><label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1"${_scopeId}>Notas de Asignación</label>`);
              _push2(ssrRenderComponent(_component_UTextarea, {
                modelValue: assignNotes.value,
                "onUpdate:modelValue": ($event) => assignNotes.value = $event,
                rows: "2",
                placeholder: "Opcional...",
                class: "w-full"
              }, null, _parent2, _scopeId));
              _push2(`</div><div class="flex justify-end gap-3 pt-4 border-t border-slate-800"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UButton, {
                color: "neutral",
                variant: "ghost",
                onClick: ($event) => isAssignOpen.value = false
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
                onClick: confirmAssign
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Realizar Asignación`);
                  } else {
                    return [
                      createTextVNode("Realizar Asignación")
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
                class: "space-y-4"
              }, [
                createVNode("div", { class: "bg-slate-950 p-3 rounded-lg border border-slate-850 flex justify-between items-center" }, [
                  createVNode("div", null, [
                    createVNode("span", { class: "text-[10px] text-slate-500 block" }, "Producto:"),
                    createVNode("span", { class: "text-xs font-bold text-white" }, toDisplayString(decodeURIComponent(selectedProduct.value.title_product).replace(/\+/g, " ")), 1)
                  ]),
                  createVNode("div", null, [
                    createVNode("span", { class: "text-[10px] text-slate-500 block text-right" }, "Disponible en Almacén:"),
                    createVNode("span", { class: "text-sm font-bold text-teal-400 font-mono block text-right" }, toDisplayString(parseFloat(selectedProduct.value.stock_inventory) || 0) + " u ", 1)
                  ])
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-[10px] font-semibold text-slate-350 uppercase mb-1" }, "Destinatario (Vendedor/Cajero) *"),
                  createVNode(_component_USelect, {
                    modelValue: assignUser.value,
                    "onUpdate:modelValue": ($event) => assignUser.value = $event,
                    items: admins.value.map((a) => ({ value: String(a.id_admin), label: `${a.name_admin} (${officesMap.value[a.id_office_admin] || "Sin Sucursal"})` })),
                    placeholder: "Seleccionar destinatario...",
                    class: "w-full",
                    required: ""
                  }, null, 8, ["modelValue", "onUpdate:modelValue", "items"])
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-[10px] font-semibold text-slate-350 uppercase mb-1" }, "Cantidad a Asignar *"),
                  createVNode("input", {
                    value: assignQty.value > 0 ? assignQty.value.toLocaleString("de-DE") : "",
                    type: "text",
                    inputmode: "numeric",
                    placeholder: "0",
                    class: "block w-full py-2.5 px-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50",
                    onInput: ($event) => onAssignQtyInput($event),
                    onKeydown: ($event) => blockNegative($event)
                  }, null, 40, ["value", "onInput", "onKeydown"])
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-[10px] font-semibold text-slate-350 uppercase mb-1" }, "Notas de Asignación"),
                  createVNode(_component_UTextarea, {
                    modelValue: assignNotes.value,
                    "onUpdate:modelValue": ($event) => assignNotes.value = $event,
                    rows: "2",
                    placeholder: "Opcional...",
                    class: "w-full"
                  }, null, 8, ["modelValue", "onUpdate:modelValue"])
                ]),
                createVNode("div", { class: "flex justify-end gap-3 pt-4 border-t border-slate-800" }, [
                  createVNode(_component_UButton, {
                    color: "neutral",
                    variant: "ghost",
                    onClick: ($event) => isAssignOpen.value = false
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Cancelar")
                    ]),
                    _: 1
                  }, 8, ["onClick"]),
                  createVNode(_component_UButton, {
                    color: "primary",
                    loading: processingAction.value,
                    onClick: confirmAssign
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Realizar Asignación")
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
        open: isTransferOpen.value,
        "onUpdate:open": ($event) => isTransferOpen.value = $event,
        title: "Traspasar Stock a Almacén / Sucursal"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (selectedProduct.value) {
              _push2(`<div class="space-y-4"${_scopeId}><div class="bg-slate-950 p-3 rounded-lg border border-slate-850 flex justify-between items-center"${_scopeId}><div${_scopeId}><span class="text-[10px] text-slate-500 block"${_scopeId}>Producto:</span><span class="text-xs font-bold text-white"${_scopeId}>${ssrInterpolate(decodeURIComponent(selectedProduct.value.title_product).replace(/\+/g, " "))}</span></div><div${_scopeId}><span class="text-[10px] text-slate-500 block text-right"${_scopeId}>Disponible en Almacén:</span><span class="text-sm font-bold text-teal-400 font-mono block text-right"${_scopeId}>${ssrInterpolate(parseFloat(selectedProduct.value.stock_inventory) || 0)} u </span></div></div><div${_scopeId}><span class="text-[10px] text-slate-500 block"${_scopeId}>Almacén de Origen:</span><span class="text-xs font-bold text-slate-350"${_scopeId}>${ssrInterpolate(officesMap.value[unref(auth).officeId || 3] || "Almacén Principal")}</span></div><div${_scopeId}><label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1"${_scopeId}>Almacén de Destino *</label>`);
              _push2(ssrRenderComponent(_component_USelect, {
                modelValue: transferOffice.value,
                "onUpdate:modelValue": ($event) => transferOffice.value = $event,
                items: destinationOffices.value,
                placeholder: "Seleccionar almacén de destino...",
                class: "w-full",
                required: ""
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1"${_scopeId}>Cantidad a Traspasar *</label><input${ssrRenderAttr("value", transferQty.value > 0 ? transferQty.value.toLocaleString("de-DE") : "")} type="text" inputmode="numeric" placeholder="0" class="block w-full py-2.5 px-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}></div><div${_scopeId}><label class="block text-[10px] font-semibold text-slate-350 uppercase mb-1"${_scopeId}>Notas de Traspaso</label>`);
              _push2(ssrRenderComponent(_component_UTextarea, {
                modelValue: transferNotes.value,
                "onUpdate:modelValue": ($event) => transferNotes.value = $event,
                rows: "2",
                placeholder: "Opcional...",
                class: "w-full"
              }, null, _parent2, _scopeId));
              _push2(`</div><div class="flex justify-end gap-3 pt-4 border-t border-slate-800"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UButton, {
                color: "neutral",
                variant: "ghost",
                onClick: ($event) => isTransferOpen.value = false
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
                onClick: confirmTransfer
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Confirmar Traspaso`);
                  } else {
                    return [
                      createTextVNode("Confirmar Traspaso")
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
                class: "space-y-4"
              }, [
                createVNode("div", { class: "bg-slate-950 p-3 rounded-lg border border-slate-850 flex justify-between items-center" }, [
                  createVNode("div", null, [
                    createVNode("span", { class: "text-[10px] text-slate-500 block" }, "Producto:"),
                    createVNode("span", { class: "text-xs font-bold text-white" }, toDisplayString(decodeURIComponent(selectedProduct.value.title_product).replace(/\+/g, " ")), 1)
                  ]),
                  createVNode("div", null, [
                    createVNode("span", { class: "text-[10px] text-slate-500 block text-right" }, "Disponible en Almacén:"),
                    createVNode("span", { class: "text-sm font-bold text-teal-400 font-mono block text-right" }, toDisplayString(parseFloat(selectedProduct.value.stock_inventory) || 0) + " u ", 1)
                  ])
                ]),
                createVNode("div", null, [
                  createVNode("span", { class: "text-[10px] text-slate-500 block" }, "Almacén de Origen:"),
                  createVNode("span", { class: "text-xs font-bold text-slate-350" }, toDisplayString(officesMap.value[unref(auth).officeId || 3] || "Almacén Principal"), 1)
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-[10px] font-semibold text-slate-350 uppercase mb-1" }, "Almacén de Destino *"),
                  createVNode(_component_USelect, {
                    modelValue: transferOffice.value,
                    "onUpdate:modelValue": ($event) => transferOffice.value = $event,
                    items: destinationOffices.value,
                    placeholder: "Seleccionar almacén de destino...",
                    class: "w-full",
                    required: ""
                  }, null, 8, ["modelValue", "onUpdate:modelValue", "items"])
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-[10px] font-semibold text-slate-350 uppercase mb-1" }, "Cantidad a Traspasar *"),
                  createVNode("input", {
                    value: transferQty.value > 0 ? transferQty.value.toLocaleString("de-DE") : "",
                    type: "text",
                    inputmode: "numeric",
                    placeholder: "0",
                    class: "block w-full py-2.5 px-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50",
                    onInput: ($event) => onTransferQtyInput($event),
                    onKeydown: ($event) => blockNegative($event)
                  }, null, 40, ["value", "onInput", "onKeydown"])
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-[10px] font-semibold text-slate-350 uppercase mb-1" }, "Notas de Traspaso"),
                  createVNode(_component_UTextarea, {
                    modelValue: transferNotes.value,
                    "onUpdate:modelValue": ($event) => transferNotes.value = $event,
                    rows: "2",
                    placeholder: "Opcional...",
                    class: "w-full"
                  }, null, 8, ["modelValue", "onUpdate:modelValue"])
                ]),
                createVNode("div", { class: "flex justify-end gap-3 pt-4 border-t border-slate-800" }, [
                  createVNode(_component_UButton, {
                    color: "neutral",
                    variant: "ghost",
                    onClick: ($event) => isTransferOpen.value = false
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Cancelar")
                    ]),
                    _: 1
                  }, 8, ["onClick"]),
                  createVNode(_component_UButton, {
                    color: "primary",
                    loading: processingAction.value,
                    onClick: confirmTransfer
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Confirmar Traspaso")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/almacen.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=almacen-7cZ7IY-S.mjs.map
