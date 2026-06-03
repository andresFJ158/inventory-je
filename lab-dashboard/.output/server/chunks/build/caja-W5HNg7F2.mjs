import { I as useAuthStore, a6 as useToast, g as _sfc_main$h, i as _sfc_main$c } from './server.mjs';
import { _ as _sfc_main$1 } from './Card-BV4DIQLA.mjs';
import { _ as __nuxt_component_0 } from './DynamicTable-Bh0txYmn.mjs';
import { _ as _sfc_main$2 } from './Badge-LaytOPGg.mjs';
import { _ as _sfc_main$3 } from './Modal-ulV1aY0B.mjs';
import { _ as _sfc_main$4 } from './FormField-H4QVgNpC.mjs';
import { defineComponent, ref, computed, mergeProps, withCtx, unref, createVNode, toDisplayString, createTextVNode, openBlock, createBlock, createCommentVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr } from 'vue/server-renderer';
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
import './Slideover-CbDvT2J_.mjs';
import './overlay-6I-jXWFz.mjs';
import './Switch-CVLe9LZj.mjs';
import './Select-Bk-d3PfC.mjs';
import './Textarea-DVGiVqM_.mjs';
import './OrderReceiptModal-BFUV5YHg.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "caja",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    function blockNegative(e) {
      if (e.key === "-" || e.key === "e" || e.key === "E") e.preventDefault();
    }
    function onOpenAmountInput(e) {
      const input = e.target;
      let raw = input.value.replace(/[^\d,]/g, "").replace(",", ".");
      const parts = raw.split(".");
      const intVal = parseInt(parts[0] || "0", 10) || 0;
      const decStr = parts[1] !== void 0 ? parts[1].slice(0, 2) : void 0;
      const n = decStr !== void 0 ? parseFloat(`${intVal}.${decStr}`) : intVal;
      openAmount.value = n < 0 ? 0 : n;
      const formatted = decStr !== void 0 ? intVal.toLocaleString("de-DE") + "," + decStr : intVal > 0 ? intVal.toLocaleString("de-DE") : "";
      input.value = formatted;
    }
    const toast = useToast();
    const apiHeaders = { Authorization: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy" };
    function getLocalDate() {
      const now = /* @__PURE__ */ new Date();
      return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
    }
    const todayStr = getLocalDate();
    const loading = ref(true);
    const todaysCash = ref(null);
    const allCashs = ref([]);
    const offices = ref([]);
    const openModal = ref(false);
    const openAmount = ref(null);
    const opening = ref(false);
    const closeModal = ref(false);
    const closing = ref(false);
    const cashDetails = ref(null);
    const loadingDetails = ref(false);
    const isSuperAdmin = computed(() => auth.role === "superadmin" || auth.role === "admin");
    function formatCurrency(val) {
      return new Intl.NumberFormat("es-BO", { style: "currency", currency: "BOB" }).format(val);
    }
    async function fetchCashStatus() {
      loading.value = true;
      try {
        if (isSuperAdmin.value) {
          const data = await $fetch(`/api/cashs?linkTo=date_created_cash&equalTo=${todayStr}&orderBy=id_cash&orderMode=DESC`, {
            headers: apiHeaders
          });
          if (data.status === 200) allCashs.value = data.results || [];
        } else {
          const data = await $fetch(`/api/cashs?linkTo=id_office_cash,date_created_cash&equalTo=${auth.officeId},${todayStr}&orderBy=id_cash&orderMode=DESC`, {
            headers: apiHeaders
          });
          if (data.status === 200 && data.results?.length > 0) {
            todaysCash.value = data.results[0];
          } else {
            todaysCash.value = null;
          }
        }
      } catch {
      }
      loading.value = false;
    }
    async function openCash() {
      if (!openAmount.value || openAmount.value <= 0) {
        toast.add({ title: "Ingresa un monto inicial válido", color: "error" });
        return;
      }
      opening.value = true;
      try {
        const body = new URLSearchParams({
          date_created_cash: todayStr,
          id_office_cash: String(auth.officeId),
          id_admin_cash: String(auth.user?.id_admin),
          start_cash: String(openAmount.value),
          status_cash: "1",
          bills_cash: "0",
          money_cash: "0",
          diff_cash: "0"
        });
        const res = await $fetch("/api/cashs?token=no&except=date_end_cash", {
          method: "POST",
          body: body.toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded", ...apiHeaders }
        });
        const data = typeof res === "string" ? JSON.parse(res) : res;
        if (data.status === 200) {
          toast.add({ title: "Caja abierta correctamente", color: "success" });
          openModal.value = false;
          openAmount.value = null;
          await fetchCashStatus();
        } else {
          toast.add({ title: data.message || "Error al abrir caja", color: "error" });
        }
      } catch {
        toast.add({ title: "Error de conexión", color: "error" });
      }
      opening.value = false;
    }
    async function loadCashDetails(cashRow) {
      loadingDetails.value = true;
      const cashId = cashRow?.id_cash || todaysCash.value?.id_cash;
      try {
        const body = new URLSearchParams({
          getCashDetails: "ok",
          id_cash: String(cashId)
        });
        const res = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body,
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof res === "string" ? JSON.parse(res) : res;
        if (data.status === 200) cashDetails.value = data.results;
      } catch {
      }
      loadingDetails.value = false;
    }
    async function closeCash() {
      closing.value = true;
      try {
        const body = new URLSearchParams({
          closeCashRegister: "ok",
          id_cash: String(todaysCash.value?.id_cash)
        });
        const res = await $fetch("/ajax/pos.ajax.php", {
          method: "POST",
          body,
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof res === "string" ? JSON.parse(res) : res;
        if (data.status === 200) {
          toast.add({ title: "Caja cerrada correctamente", color: "success" });
          closeModal.value = false;
          cashDetails.value = null;
          await fetchCashStatus();
        } else {
          toast.add({ title: data.message || "Error al cerrar caja", color: "error" });
        }
      } catch {
        toast.add({ title: "Error de conexión", color: "error" });
      }
      closing.value = false;
    }
    function getOfficeName(id) {
      const office = offices.value.find((o) => String(o.id_office) === String(id));
      return office ? decodeURIComponent(office.title_office || "").replace(/\+/g, " ") : `Sucursal ${id}`;
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      const _component_UCard = _sfc_main$1;
      const _component_DynamicTable = __nuxt_component_0;
      const _component_UButton = _sfc_main$c;
      const _component_UBadge = _sfc_main$2;
      const _component_UModal = _sfc_main$3;
      const _component_UFormField = _sfc_main$4;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}>`);
      if (!isSuperAdmin.value) {
        _push(`<!--[-->`);
        if (loading.value) {
          _push(`<div class="flex justify-center py-20">`);
          _push(ssrRenderComponent(_component_UIcon, {
            name: "i-lucide-loader-2",
            class: "animate-spin w-8 h-8 text-green-500"
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!--[-->`);
          if (todaysCash.value && todaysCash.value.status_cash == 1) {
            _push(`<!--[--><div class="grid grid-cols-1 md:grid-cols-3 gap-4">`);
            _push(ssrRenderComponent(_component_UCard, { class: "bg-gradient-to-br from-emerald-500 to-green-600 border-0 shadow-lg" }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`<div class="flex justify-between items-center text-white"${_scopeId}><div${_scopeId}><p class="text-emerald-100 text-xs font-bold uppercase tracking-wider"${_scopeId}>Estado de Caja</p><h2 class="text-3xl font-black mt-1"${_scopeId}>ABIERTA</h2><p class="text-sm mt-1 text-emerald-100"${_scopeId}>Desde ${ssrInterpolate(unref(todayStr))}</p></div>`);
                  _push2(ssrRenderComponent(_component_UIcon, {
                    name: "i-lucide-wallet",
                    class: "w-14 h-14 text-white/30"
                  }, null, _parent2, _scopeId));
                  _push2(`</div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex justify-between items-center text-white" }, [
                      createVNode("div", null, [
                        createVNode("p", { class: "text-emerald-100 text-xs font-bold uppercase tracking-wider" }, "Estado de Caja"),
                        createVNode("h2", { class: "text-3xl font-black mt-1" }, "ABIERTA"),
                        createVNode("p", { class: "text-sm mt-1 text-emerald-100" }, "Desde " + toDisplayString(unref(todayStr)), 1)
                      ]),
                      createVNode(_component_UIcon, {
                        name: "i-lucide-wallet",
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
                  _push2(`<p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider"${_scopeId}>Monto Inicial</p><h2 class="text-2xl font-black text-slate-800 dark:text-white mt-1"${_scopeId}>${ssrInterpolate(formatCurrency(parseFloat(todaysCash.value.start_cash || 0)))}</h2><p class="text-xs text-slate-400 mt-1"${_scopeId}>Capital de apertura</p>`);
                } else {
                  return [
                    createVNode("p", { class: "text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider" }, "Monto Inicial"),
                    createVNode("h2", { class: "text-2xl font-black text-slate-800 dark:text-white mt-1" }, toDisplayString(formatCurrency(parseFloat(todaysCash.value.start_cash || 0))), 1),
                    createVNode("p", { class: "text-xs text-slate-400 mt-1" }, "Capital de apertura")
                  ];
                }
              }),
              _: 1
            }, _parent));
            _push(ssrRenderComponent(_component_UCard, {
              class: "flex items-center justify-center gap-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors",
              onClick: () => {
                closeModal.value = true;
                loadCashDetails();
              }
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(_component_UIcon, {
                    name: "i-lucide-x-circle",
                    class: "w-10 h-10 text-rose-400"
                  }, null, _parent2, _scopeId));
                  _push2(`<div${_scopeId}><p class="font-bold text-slate-700 dark:text-slate-200"${_scopeId}>Cerrar Caja</p><p class="text-xs text-slate-500"${_scopeId}>Finalizar jornada del día</p></div>`);
                } else {
                  return [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-x-circle",
                      class: "w-10 h-10 text-rose-400"
                    }),
                    createVNode("div", null, [
                      createVNode("p", { class: "font-bold text-slate-700 dark:text-slate-200" }, "Cerrar Caja"),
                      createVNode("p", { class: "text-xs text-slate-500" }, "Finalizar jornada del día")
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
                  _push2(`<div class="flex items-center gap-2"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UIcon, {
                    name: "i-lucide-receipt",
                    class: "text-green-500"
                  }, null, _parent2, _scopeId));
                  _push2(`<h3 class="font-bold"${_scopeId}>Historial de Caja — Hoy</h3></div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex items-center gap-2" }, [
                      createVNode(_component_UIcon, {
                        name: "i-lucide-receipt",
                        class: "text-green-500"
                      }),
                      createVNode("h3", { class: "font-bold" }, "Historial de Caja — Hoy")
                    ])
                  ];
                }
              }),
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(_component_DynamicTable, { "module-name": "caja" }, null, _parent2, _scopeId));
                } else {
                  return [
                    createVNode(_component_DynamicTable, { "module-name": "caja" })
                  ];
                }
              }),
              _: 1
            }, _parent));
            _push(`<!--]-->`);
          } else {
            _push(`<div class="flex flex-col items-center justify-center py-20 space-y-6"><div class="w-24 h-24 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-wallet",
              class: "w-12 h-12 text-slate-400"
            }, null, _parent));
            _push(`</div><div class="text-center"><h2 class="text-2xl font-bold text-slate-700 dark:text-slate-200">Caja Cerrada</h2><p class="text-slate-500 dark:text-slate-400 mt-1">No hay caja abierta para hoy. Abre la caja para comenzar a operar.</p></div>`);
            _push(ssrRenderComponent(_component_UButton, {
              size: "lg",
              color: "primary",
              icon: "i-lucide-plus-circle",
              onClick: ($event) => openModal.value = true
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(` Abrir Caja `);
                } else {
                  return [
                    createTextVNode(" Abrir Caja ")
                  ];
                }
              }),
              _: 1
            }, _parent));
            _push(`</div>`);
          }
          _push(`<!--]-->`);
        }
        _push(`<!--]-->`);
      } else {
        _push(`<!--[--><div class="flex items-center justify-between"><div><h2 class="text-lg font-bold text-slate-800 dark:text-white">Cajas por Sucursal — Hoy</h2><p class="text-xs text-slate-500 dark:text-slate-400">${ssrInterpolate(unref(todayStr))}</p></div>`);
        _push(ssrRenderComponent(_component_UButton, {
          icon: "i-lucide-refresh-cw",
          variant: "ghost",
          color: "neutral",
          onClick: fetchCashStatus
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Actualizar`);
            } else {
              return [
                createTextVNode("Actualizar")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
        if (loading.value) {
          _push(`<div class="flex justify-center py-20">`);
          _push(ssrRenderComponent(_component_UIcon, {
            name: "i-lucide-loader-2",
            class: "animate-spin w-8 h-8 text-green-500"
          }, null, _parent));
          _push(`</div>`);
        } else if (allCashs.value.length === 0) {
          _push(`<div class="text-center py-16 text-slate-400"> No hay cajas registradas para hoy. </div>`);
        } else {
          _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
          ssrRenderList(allCashs.value, (cash) => {
            _push(ssrRenderComponent(_component_UCard, {
              key: cash.id_cash
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`<div class="flex items-start justify-between mb-3"${_scopeId}><div${_scopeId}><h3 class="font-bold text-slate-800 dark:text-white"${_scopeId}>${ssrInterpolate(getOfficeName(cash.id_office_cash))}</h3><p class="text-xs text-slate-500 dark:text-slate-400"${_scopeId}>Caja #${ssrInterpolate(cash.id_cash)}</p></div>`);
                  _push2(ssrRenderComponent(_component_UBadge, {
                    color: cash.status_cash == 1 ? "success" : "neutral",
                    variant: "subtle"
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(cash.status_cash == 1 ? "Abierta" : "Cerrada")}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(cash.status_cash == 1 ? "Abierta" : "Cerrada"), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</div><div class="space-y-1 text-sm"${_scopeId}><div class="flex justify-between"${_scopeId}><span class="text-slate-500 dark:text-slate-400"${_scopeId}>Apertura:</span><span class="font-semibold"${_scopeId}>${ssrInterpolate(formatCurrency(parseFloat(cash.start_cash || 0)))}</span></div>`);
                  if (cash.final_cash) {
                    _push2(`<div class="flex justify-between"${_scopeId}><span class="text-slate-500 dark:text-slate-400"${_scopeId}>Cierre:</span><span class="font-semibold text-rose-500"${_scopeId}>${ssrInterpolate(formatCurrency(parseFloat(cash.final_cash || 0)))}</span></div>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div><div class="mt-3"${_scopeId}>`);
                  _push2(ssrRenderComponent(_component_UButton, {
                    size: "xs",
                    variant: "soft",
                    color: "primary",
                    icon: "i-lucide-receipt",
                    onClick: () => {
                      loadCashDetails(cash);
                      closeModal.value = true;
                    }
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(` Ver Detalles `);
                      } else {
                        return [
                          createTextVNode(" Ver Detalles ")
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(`</div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex items-start justify-between mb-3" }, [
                      createVNode("div", null, [
                        createVNode("h3", { class: "font-bold text-slate-800 dark:text-white" }, toDisplayString(getOfficeName(cash.id_office_cash)), 1),
                        createVNode("p", { class: "text-xs text-slate-500 dark:text-slate-400" }, "Caja #" + toDisplayString(cash.id_cash), 1)
                      ]),
                      createVNode(_component_UBadge, {
                        color: cash.status_cash == 1 ? "success" : "neutral",
                        variant: "subtle"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(toDisplayString(cash.status_cash == 1 ? "Abierta" : "Cerrada"), 1)
                        ]),
                        _: 2
                      }, 1032, ["color"])
                    ]),
                    createVNode("div", { class: "space-y-1 text-sm" }, [
                      createVNode("div", { class: "flex justify-between" }, [
                        createVNode("span", { class: "text-slate-500 dark:text-slate-400" }, "Apertura:"),
                        createVNode("span", { class: "font-semibold" }, toDisplayString(formatCurrency(parseFloat(cash.start_cash || 0))), 1)
                      ]),
                      cash.final_cash ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "flex justify-between"
                      }, [
                        createVNode("span", { class: "text-slate-500 dark:text-slate-400" }, "Cierre:"),
                        createVNode("span", { class: "font-semibold text-rose-500" }, toDisplayString(formatCurrency(parseFloat(cash.final_cash || 0))), 1)
                      ])) : createCommentVNode("", true)
                    ]),
                    createVNode("div", { class: "mt-3" }, [
                      createVNode(_component_UButton, {
                        size: "xs",
                        variant: "soft",
                        color: "primary",
                        icon: "i-lucide-receipt",
                        onClick: () => {
                          loadCashDetails(cash);
                          closeModal.value = true;
                        }
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Ver Detalles ")
                        ]),
                        _: 1
                      }, 8, ["onClick"])
                    ])
                  ];
                }
              }),
              _: 2
            }, _parent));
          });
          _push(`<!--]--></div>`);
        }
        _push(ssrRenderComponent(_component_UCard, null, {
          header: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<h3 class="font-bold"${_scopeId}>Histórico de Cajas</h3>`);
            } else {
              return [
                createVNode("h3", { class: "font-bold" }, "Histórico de Cajas")
              ];
            }
          }),
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(_component_DynamicTable, { "module-name": "caja" }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(_component_DynamicTable, { "module-name": "caja" })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<!--]-->`);
      }
      _push(ssrRenderComponent(_component_UModal, {
        open: openModal.value,
        "onUpdate:open": ($event) => openModal.value = $event,
        title: "Abrir Caja del Día"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4 p-1"${_scopeId}><p class="text-sm text-slate-600 dark:text-slate-400"${_scopeId}> Ingresa el monto inicial con el que abres la caja para hoy <strong${_scopeId}>(${ssrInterpolate(unref(todayStr))})</strong>. </p>`);
            _push2(ssrRenderComponent(_component_UFormField, { label: "Monto Inicial (Bs.)" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<input${ssrRenderAttr("value", openAmount.value !== null && openAmount.value > 0 ? openAmount.value.toLocaleString("de-DE", { minimumFractionDigits: 0, maximumFractionDigits: 2 }) : "")} type="text" inputmode="decimal" placeholder="0,00" class="block w-full py-3 px-4 text-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId2}>`);
                } else {
                  return [
                    createVNode("input", {
                      value: openAmount.value !== null && openAmount.value > 0 ? openAmount.value.toLocaleString("de-DE", { minimumFractionDigits: 0, maximumFractionDigits: 2 }) : "",
                      type: "text",
                      inputmode: "decimal",
                      placeholder: "0,00",
                      class: "block w-full py-3 px-4 text-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500/50",
                      onInput: ($event) => onOpenAmountInput($event),
                      onKeydown: ($event) => blockNegative($event)
                    }, null, 40, ["value", "onInput", "onKeydown"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4 p-1" }, [
                createVNode("p", { class: "text-sm text-slate-600 dark:text-slate-400" }, [
                  createTextVNode(" Ingresa el monto inicial con el que abres la caja para hoy "),
                  createVNode("strong", null, "(" + toDisplayString(unref(todayStr)) + ")", 1),
                  createTextVNode(". ")
                ]),
                createVNode(_component_UFormField, { label: "Monto Inicial (Bs.)" }, {
                  default: withCtx(() => [
                    createVNode("input", {
                      value: openAmount.value !== null && openAmount.value > 0 ? openAmount.value.toLocaleString("de-DE", { minimumFractionDigits: 0, maximumFractionDigits: 2 }) : "",
                      type: "text",
                      inputmode: "decimal",
                      placeholder: "0,00",
                      class: "block w-full py-3 px-4 text-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500/50",
                      onInput: ($event) => onOpenAmountInput($event),
                      onKeydown: ($event) => blockNegative($event)
                    }, null, 40, ["value", "onInput", "onKeydown"])
                  ]),
                  _: 2
                }, 1024)
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
              onClick: ($event) => openModal.value = false
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
              icon: "i-lucide-wallet",
              loading: opening.value,
              onClick: openCash
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Confirmar Apertura `);
                } else {
                  return [
                    createTextVNode(" Confirmar Apertura ")
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
                  onClick: ($event) => openModal.value = false
                }, {
                  default: withCtx(() => [
                    createTextVNode("Cancelar")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(_component_UButton, {
                  color: "primary",
                  icon: "i-lucide-wallet",
                  loading: opening.value,
                  onClick: openCash
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Confirmar Apertura ")
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
        open: closeModal.value,
        "onUpdate:open": ($event) => closeModal.value = $event,
        title: "Detalles y Cierre de Caja",
        ui: { body: "max-h-[70vh] overflow-y-auto" }
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4 p-1"${_scopeId}>`);
            if (loadingDetails.value) {
              _push2(`<div class="flex justify-center py-8"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-loader-2",
                class: "animate-spin w-6 h-6 text-green-500"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else if (cashDetails.value) {
              _push2(`<div class="grid grid-cols-2 gap-3 text-sm"${_scopeId}><div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-3"${_scopeId}><p class="text-slate-500 text-xs"${_scopeId}>Apertura</p><p class="font-bold text-lg"${_scopeId}>${ssrInterpolate(formatCurrency(parseFloat(cashDetails.value.start_cash || 0)))}</p></div><div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-3"${_scopeId}><p class="text-slate-500 text-xs"${_scopeId}>Total Ventas</p><p class="font-bold text-lg text-green-600"${_scopeId}>${ssrInterpolate(formatCurrency(parseFloat(cashDetails.value.total_sales || 0)))}</p></div><div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-3"${_scopeId}><p class="text-slate-500 text-xs"${_scopeId}>Gastos</p><p class="font-bold text-lg text-rose-500"${_scopeId}>${ssrInterpolate(formatCurrency(parseFloat(cashDetails.value.total_bills || 0)))}</p></div><div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-200 dark:border-green-800"${_scopeId}><p class="text-green-600 text-xs font-semibold"${_scopeId}>Saldo Final</p><p class="font-black text-xl text-green-600"${_scopeId}>${ssrInterpolate(formatCurrency(parseFloat(cashDetails.value.final_cash || 0)))}</p></div></div>`);
            } else {
              _push2(`<div class="text-center py-4 text-slate-400 text-sm"${_scopeId}>No hay detalles disponibles.</div>`);
            }
            if (!isSuperAdmin.value && todaysCash.value?.status_cash == 1) {
              _push2(`<div class="pt-2 border-t border-slate-200 dark:border-slate-700"${_scopeId}><p class="text-xs text-slate-500 dark:text-slate-400 mb-3"${_scopeId}> Al cerrar la caja se finalizará la jornada. Esta acción no se puede deshacer. </p></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4 p-1" }, [
                loadingDetails.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "flex justify-center py-8"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "animate-spin w-6 h-6 text-green-500"
                  })
                ])) : cashDetails.value ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "grid grid-cols-2 gap-3 text-sm"
                }, [
                  createVNode("div", { class: "bg-slate-50 dark:bg-slate-800 rounded-lg p-3" }, [
                    createVNode("p", { class: "text-slate-500 text-xs" }, "Apertura"),
                    createVNode("p", { class: "font-bold text-lg" }, toDisplayString(formatCurrency(parseFloat(cashDetails.value.start_cash || 0))), 1)
                  ]),
                  createVNode("div", { class: "bg-slate-50 dark:bg-slate-800 rounded-lg p-3" }, [
                    createVNode("p", { class: "text-slate-500 text-xs" }, "Total Ventas"),
                    createVNode("p", { class: "font-bold text-lg text-green-600" }, toDisplayString(formatCurrency(parseFloat(cashDetails.value.total_sales || 0))), 1)
                  ]),
                  createVNode("div", { class: "bg-slate-50 dark:bg-slate-800 rounded-lg p-3" }, [
                    createVNode("p", { class: "text-slate-500 text-xs" }, "Gastos"),
                    createVNode("p", { class: "font-bold text-lg text-rose-500" }, toDisplayString(formatCurrency(parseFloat(cashDetails.value.total_bills || 0))), 1)
                  ]),
                  createVNode("div", { class: "bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-200 dark:border-green-800" }, [
                    createVNode("p", { class: "text-green-600 text-xs font-semibold" }, "Saldo Final"),
                    createVNode("p", { class: "font-black text-xl text-green-600" }, toDisplayString(formatCurrency(parseFloat(cashDetails.value.final_cash || 0))), 1)
                  ])
                ])) : (openBlock(), createBlock("div", {
                  key: 2,
                  class: "text-center py-4 text-slate-400 text-sm"
                }, "No hay detalles disponibles.")),
                !isSuperAdmin.value && todaysCash.value?.status_cash == 1 ? (openBlock(), createBlock("div", {
                  key: 3,
                  class: "pt-2 border-t border-slate-200 dark:border-slate-700"
                }, [
                  createVNode("p", { class: "text-xs text-slate-500 dark:text-slate-400 mb-3" }, " Al cerrar la caja se finalizará la jornada. Esta acción no se puede deshacer. ")
                ])) : createCommentVNode("", true)
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
              onClick: () => {
                closeModal.value = false;
                cashDetails.value = null;
              }
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
            if (!isSuperAdmin.value && todaysCash.value?.status_cash == 1) {
              _push2(ssrRenderComponent(_component_UButton, {
                color: "error",
                icon: "i-lucide-x-circle",
                loading: closing.value,
                onClick: closeCash
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Cerrar Caja `);
                  } else {
                    return [
                      createTextVNode(" Cerrar Caja ")
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
            return [
              createVNode("div", { class: "flex justify-end gap-3" }, [
                createVNode(_component_UButton, {
                  color: "neutral",
                  variant: "ghost",
                  onClick: () => {
                    closeModal.value = false;
                    cashDetails.value = null;
                  }
                }, {
                  default: withCtx(() => [
                    createTextVNode("Cerrar")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                !isSuperAdmin.value && todaysCash.value?.status_cash == 1 ? (openBlock(), createBlock(_component_UButton, {
                  key: 0,
                  color: "error",
                  icon: "i-lucide-x-circle",
                  loading: closing.value,
                  onClick: closeCash
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Cerrar Caja ")
                  ]),
                  _: 1
                }, 8, ["loading"])) : createCommentVNode("", true)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/caja.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=caja-W5HNg7F2.mjs.map
