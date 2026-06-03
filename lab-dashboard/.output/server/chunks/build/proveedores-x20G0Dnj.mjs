import { I as useAuthStore, a6 as useToast, h as _sfc_main$6, i as _sfc_main$c, g as _sfc_main$h } from './server.mjs';
import { _ as _sfc_main$1 } from './Select-Bk-d3PfC.mjs';
import { _ as _sfc_main$2 } from './Badge-LaytOPGg.mjs';
import { _ as _sfc_main$3 } from './Slideover-CbDvT2J_.mjs';
import { _ as _sfc_main$4 } from './FormField-H4QVgNpC.mjs';
import { _ as _sfc_main$5 } from './Switch-CVLe9LZj.mjs';
import { defineComponent, ref, computed, mergeProps, withCtx, createTextVNode, toDisplayString, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList } from 'vue/server-renderer';
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

const ajaxBase = "/ajax/pos.ajax.php";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "proveedores",
  __ssrInlineRender: true,
  setup(__props) {
    useAuthStore();
    const toast = useToast();
    const suppliers = ref([]);
    const loading = ref(true);
    const search = ref("");
    const filterType = ref("todos");
    const slideOpen = ref(false);
    const editing = ref(null);
    const saving = ref(false);
    const form = ref({
      id_supplier: 0,
      supplier_name: "",
      supplier_contact: "",
      email_supplier: "",
      ruc_supplier: "",
      type_supplier: "ambos",
      status_supplier: 1
    });
    const typeOptions = [
      { value: "ambos", label: "Productos y Materias Primas" },
      { value: "productos", label: "Solo Productos POS" },
      { value: "materias_primas", label: "Solo Materias Primas / Lab" }
    ];
    const typeColors = {
      productos: "blue",
      materias_primas: "green",
      ambos: "purple"
    };
    const typeLabels = {
      productos: "POS",
      materias_primas: "Lab",
      ambos: "Ambos"
    };
    function decode(s) {
      return s ? decodeURIComponent(s).replace(/\+/g, " ") : "";
    }
    const filtered = computed(() => {
      let list = suppliers.value;
      if (filterType.value !== "todos") list = list.filter((s) => s.type_supplier === filterType.value || s.type_supplier === "ambos");
      if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter((s) => (s.supplier_name || "").toLowerCase().includes(q) || (s.supplier_contact || "").includes(q) || (s.ruc_supplier || "").includes(q));
      }
      return list;
    });
    async function fetchSuppliers() {
      loading.value = true;
      const res = await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ getSuppliers: "ok", type: "todos" }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      const d = typeof res === "string" ? JSON.parse(res) : res;
      suppliers.value = d?.status === 200 ? d.results : [];
      loading.value = false;
    }
    function openCreate() {
      editing.value = null;
      form.value = { id_supplier: 0, supplier_name: "", supplier_contact: "", email_supplier: "", ruc_supplier: "", type_supplier: "ambos", status_supplier: 1 };
      slideOpen.value = true;
    }
    function openEdit(s) {
      editing.value = s;
      form.value = {
        id_supplier: s.id_supplier,
        supplier_name: decode(s.supplier_name),
        supplier_contact: s.supplier_contact || "",
        email_supplier: s.email_supplier || "",
        ruc_supplier: s.ruc_supplier || "",
        type_supplier: s.type_supplier || "ambos",
        status_supplier: parseInt(s.status_supplier ?? 1)
      };
      slideOpen.value = true;
    }
    async function saveSupplier() {
      if (!form.value.supplier_name.trim()) {
        toast.add({ title: "El nombre es requerido", color: "error" });
        return;
      }
      saving.value = true;
      const body = new URLSearchParams({
        saveSupplier: "ok",
        id_supplier: String(form.value.id_supplier),
        supplier_name: form.value.supplier_name,
        supplier_contact: form.value.supplier_contact,
        email_supplier: form.value.email_supplier,
        ruc_supplier: form.value.ruc_supplier,
        type_supplier: form.value.type_supplier,
        status_supplier: String(form.value.status_supplier)
      });
      const res = await $fetch(ajaxBase, {
        method: "POST",
        body: body.toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      const d = typeof res === "string" ? JSON.parse(res) : res;
      if (d?.status === 200) {
        toast.add({ title: form.value.id_supplier ? "Proveedor actualizado" : "Proveedor creado", color: "success" });
        slideOpen.value = false;
        await fetchSuppliers();
      } else {
        toast.add({ title: "Error al guardar", color: "error" });
      }
      saving.value = false;
    }
    async function deleteSupplier(s) {
      if (!confirm(`¿Desactivar a "${decode(s.supplier_name)}"?`)) return;
      await $fetch(ajaxBase, {
        method: "POST",
        body: new URLSearchParams({ deleteSupplier: "ok", id_supplier: String(s.id_supplier) }).toString(),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      }).catch(() => null);
      toast.add({ title: "Proveedor desactivado", color: "success" });
      await fetchSuppliers();
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UInput = _sfc_main$6;
      const _component_USelect = _sfc_main$1;
      const _component_UButton = _sfc_main$c;
      const _component_UIcon = _sfc_main$h;
      const _component_UBadge = _sfc_main$2;
      const _component_USlideover = _sfc_main$3;
      const _component_UFormField = _sfc_main$4;
      const _component_USwitch = _sfc_main$5;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-4" }, _attrs))}><div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm"><div><p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"> Proveedores unificados para el sistema POS y el Laboratorio. Un solo registro para ambos módulos. </p></div><div class="flex flex-wrap gap-2 w-full sm:w-auto">`);
      _push(ssrRenderComponent(_component_UInput, {
        modelValue: search.value,
        "onUpdate:modelValue": ($event) => search.value = $event,
        icon: "i-lucide-search",
        placeholder: "Buscar...",
        size: "sm",
        class: "flex-1 sm:w-52"
      }, null, _parent));
      _push(ssrRenderComponent(_component_USelect, {
        modelValue: filterType.value,
        "onUpdate:modelValue": ($event) => filterType.value = $event,
        items: [{ value: "todos", label: "Todos" }, { value: "productos", label: "POS" }, { value: "materias_primas", label: "Lab" }, { value: "ambos", label: "Ambos" }],
        size: "sm",
        class: "w-32"
      }, null, _parent));
      _push(ssrRenderComponent(_component_UButton, {
        color: "primary",
        icon: "i-lucide-plus",
        size: "sm",
        onClick: openCreate
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Nuevo`);
          } else {
            return [
              createTextVNode("Nuevo")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="grid grid-cols-3 gap-3"><div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center"><p class="text-2xl font-black text-slate-800 dark:text-white">${ssrInterpolate(suppliers.value.length)}</p><p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total</p></div><div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center"><p class="text-2xl font-black text-blue-600">${ssrInterpolate(suppliers.value.filter((s) => s.type_supplier === "productos").length)}</p><p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Solo POS</p></div><div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center"><p class="text-2xl font-black text-green-600">${ssrInterpolate(suppliers.value.filter((s) => s.type_supplier === "materias_primas" || s.type_supplier === "ambos").length)}</p><p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Incluyen Lab</p></div></div>`);
      if (loading.value) {
        _push(`<div class="flex justify-center py-16">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-loader-2",
          class: "w-8 h-8 animate-spin text-green-500"
        }, null, _parent));
        _push(`</div>`);
      } else if (filtered.value.length === 0) {
        _push(`<div class="text-center py-16">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-truck",
          class: "w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-3"
        }, null, _parent));
        _push(`<p class="text-slate-500 dark:text-slate-400">No hay proveedores registrados</p>`);
        _push(ssrRenderComponent(_component_UButton, {
          color: "primary",
          icon: "i-lucide-plus",
          class: "mt-4",
          onClick: openCreate
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Agregar primer proveedor`);
            } else {
              return [
                createTextVNode("Agregar primer proveedor")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
        ssrRenderList(filtered.value, (s) => {
          _push(`<div class="bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition-all"><div class="flex items-start justify-between gap-2 mb-3"><div class="flex items-center gap-3 min-w-0"><div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">`);
          _push(ssrRenderComponent(_component_UIcon, {
            name: "i-lucide-building-2",
            class: "w-5 h-5 text-slate-500"
          }, null, _parent));
          _push(`</div><div class="min-w-0"><h3 class="font-bold text-slate-800 dark:text-white truncate text-sm">${ssrInterpolate(decode(s.supplier_name))}</h3>`);
          if (s.ruc_supplier) {
            _push(`<p class="text-xs text-slate-400 font-mono">RUC: ${ssrInterpolate(s.ruc_supplier)}</p>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div>`);
          _push(ssrRenderComponent(_component_UBadge, {
            color: typeColors[s.type_supplier] || "neutral",
            variant: "subtle",
            size: "xs",
            class: "shrink-0"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`${ssrInterpolate(typeLabels[s.type_supplier] || s.type_supplier)}`);
              } else {
                return [
                  createTextVNode(toDisplayString(typeLabels[s.type_supplier] || s.type_supplier), 1)
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`</div><div class="space-y-1.5 text-xs text-slate-500 dark:text-slate-400">`);
          if (s.supplier_contact) {
            _push(`<div class="flex items-center gap-1.5">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-phone",
              class: "w-3.5 h-3.5 shrink-0"
            }, null, _parent));
            _push(`<span>${ssrInterpolate(s.supplier_contact)}</span></div>`);
          } else {
            _push(`<!---->`);
          }
          if (s.email_supplier) {
            _push(`<div class="flex items-center gap-1.5">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-mail",
              class: "w-3.5 h-3.5 shrink-0"
            }, null, _parent));
            _push(`<span class="truncate">${ssrInterpolate(s.email_supplier)}</span></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="flex gap-2 mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">`);
          _push(ssrRenderComponent(_component_UButton, {
            size: "xs",
            variant: "ghost",
            color: "neutral",
            icon: "i-lucide-edit",
            class: "flex-1 justify-center",
            onClick: ($event) => openEdit(s)
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`Editar`);
              } else {
                return [
                  createTextVNode("Editar")
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(ssrRenderComponent(_component_UButton, {
            size: "xs",
            variant: "ghost",
            color: "error",
            icon: "i-lucide-trash",
            onClick: ($event) => deleteSupplier(s)
          }, null, _parent));
          _push(`</div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(ssrRenderComponent(_component_USlideover, {
        open: slideOpen.value,
        "onUpdate:open": ($event) => slideOpen.value = $event,
        title: editing.value ? "Editar Proveedor" : "Nuevo Proveedor"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4 p-1"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UFormField, { label: "Nombre del Proveedor *" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: form.value.supplier_name,
                    "onUpdate:modelValue": ($event) => form.value.supplier_name = $event,
                    placeholder: "Ej: J.E Bolivia SRL",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: form.value.supplier_name,
                      "onUpdate:modelValue": ($event) => form.value.supplier_name = $event,
                      placeholder: "Ej: J.E Bolivia SRL",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, { label: "RUC / NIT" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: form.value.ruc_supplier,
                    "onUpdate:modelValue": ($event) => form.value.ruc_supplier = $event,
                    placeholder: "Número de registro tributario",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: form.value.ruc_supplier,
                      "onUpdate:modelValue": ($event) => form.value.ruc_supplier = $event,
                      placeholder: "Número de registro tributario",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, { label: "Teléfono / WhatsApp" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: form.value.supplier_contact,
                    "onUpdate:modelValue": ($event) => form.value.supplier_contact = $event,
                    placeholder: "79000000",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: form.value.supplier_contact,
                      "onUpdate:modelValue": ($event) => form.value.supplier_contact = $event,
                      placeholder: "79000000",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, { label: "Correo Electrónico" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: form.value.email_supplier,
                    "onUpdate:modelValue": ($event) => form.value.email_supplier = $event,
                    type: "email",
                    placeholder: "proveedor@empresa.com",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: form.value.email_supplier,
                      "onUpdate:modelValue": ($event) => form.value.email_supplier = $event,
                      type: "email",
                      placeholder: "proveedor@empresa.com",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, { label: "Tipo de Proveedor" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_USelect, {
                    modelValue: form.value.type_supplier,
                    "onUpdate:modelValue": ($event) => form.value.type_supplier = $event,
                    items: typeOptions,
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_USelect, {
                      modelValue: form.value.type_supplier,
                      "onUpdate:modelValue": ($event) => form.value.type_supplier = $event,
                      items: typeOptions,
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="flex items-center gap-3 pt-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_USwitch, {
              modelValue: form.value.status_supplier,
              "onUpdate:modelValue": [($event) => form.value.status_supplier = $event, (v) => form.value.status_supplier = v ? 1 : 0],
              "model-value": form.value.status_supplier === 1
            }, null, _parent2, _scopeId));
            _push2(`<span class="text-sm text-slate-600 dark:text-slate-400"${_scopeId}>${ssrInterpolate(form.value.status_supplier ? "Activo" : "Inactivo")}</span></div><div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-xs text-blue-700 dark:text-blue-300"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-info",
              class: "w-3.5 h-3.5 inline mr-1"
            }, null, _parent2, _scopeId));
            _push2(` Seleccionar <strong${_scopeId}>&quot;Productos y Materias Primas&quot;</strong> hace que este proveedor esté disponible tanto en compras del POS como en entradas del Laboratorio. </div></div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4 p-1" }, [
                createVNode(_component_UFormField, { label: "Nombre del Proveedor *" }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: form.value.supplier_name,
                      "onUpdate:modelValue": ($event) => form.value.supplier_name = $event,
                      placeholder: "Ej: J.E Bolivia SRL",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, { label: "RUC / NIT" }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: form.value.ruc_supplier,
                      "onUpdate:modelValue": ($event) => form.value.ruc_supplier = $event,
                      placeholder: "Número de registro tributario",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, { label: "Teléfono / WhatsApp" }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: form.value.supplier_contact,
                      "onUpdate:modelValue": ($event) => form.value.supplier_contact = $event,
                      placeholder: "79000000",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, { label: "Correo Electrónico" }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: form.value.email_supplier,
                      "onUpdate:modelValue": ($event) => form.value.email_supplier = $event,
                      type: "email",
                      placeholder: "proveedor@empresa.com",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, { label: "Tipo de Proveedor" }, {
                  default: withCtx(() => [
                    createVNode(_component_USelect, {
                      modelValue: form.value.type_supplier,
                      "onUpdate:modelValue": ($event) => form.value.type_supplier = $event,
                      items: typeOptions,
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode("div", { class: "flex items-center gap-3 pt-2" }, [
                  createVNode(_component_USwitch, {
                    modelValue: form.value.status_supplier,
                    "onUpdate:modelValue": [($event) => form.value.status_supplier = $event, (v) => form.value.status_supplier = v ? 1 : 0],
                    "model-value": form.value.status_supplier === 1
                  }, null, 8, ["modelValue", "onUpdate:modelValue", "model-value"]),
                  createVNode("span", { class: "text-sm text-slate-600 dark:text-slate-400" }, toDisplayString(form.value.status_supplier ? "Activo" : "Inactivo"), 1)
                ]),
                createVNode("div", { class: "bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-xs text-blue-700 dark:text-blue-300" }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-info",
                    class: "w-3.5 h-3.5 inline mr-1"
                  }),
                  createTextVNode(" Seleccionar "),
                  createVNode("strong", null, '"Productos y Materias Primas"'),
                  createTextVNode(" hace que este proveedor esté disponible tanto en compras del POS como en entradas del Laboratorio. ")
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
              onClick: ($event) => slideOpen.value = false
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
              loading: saving.value,
              icon: "i-lucide-check",
              onClick: saveSupplier
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Guardar`);
                } else {
                  return [
                    createTextVNode("Guardar")
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
                  onClick: ($event) => slideOpen.value = false
                }, {
                  default: withCtx(() => [
                    createTextVNode("Cancelar")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(_component_UButton, {
                  color: "primary",
                  loading: saving.value,
                  icon: "i-lucide-check",
                  onClick: saveSupplier
                }, {
                  default: withCtx(() => [
                    createTextVNode("Guardar")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/proveedores.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=proveedores-x20G0Dnj.mjs.map
