import { I as useAuthStore, g as _sfc_main$h, i as _sfc_main$c, h as _sfc_main$6 } from './server.mjs';
import { _ as _sfc_main$1 } from './Modal-ulV1aY0B.mjs';
import { _ as _sfc_main$2 } from './Select-Bk-d3PfC.mjs';
import { _ as _sfc_main$3 } from './Textarea-DVGiVqM_.mjs';
import { defineComponent, ref, computed, mergeProps, unref, withCtx, createTextVNode, isRef, createVNode, toDisplayString, withModifiers, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass } from 'vue/server-renderer';
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

const apiBase = "/ajax/pos.ajax.php";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "insumos",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    const items = ref([]);
    const loading = ref(true);
    const searchQuery = ref("");
    const filteredItems = computed(() => {
      if (!searchQuery.value) return items.value;
      const q = searchQuery.value.toLowerCase();
      return items.value.filter(
        (i) => i.name.toLowerCase().includes(q) || i.unit.toLowerCase().includes(q) || i.desc && i.desc.toLowerCase().includes(q)
      );
    });
    async function fetchInsumos() {
      loading.value = true;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({
            getLabMaterials: "ok",
            id_office: String(auth.officeId || 6),
            is_insumo: "1"
          }).toString(),
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        if (data.status === 200) {
          items.value = data.results.map((m) => ({
            id: m.id_raw_material,
            name: m.name_raw_material,
            type: m.measure_type || "unit",
            stock: parseFloat(m.stock_raw_material) || 0,
            unit: m.unit_raw_material,
            desc: m.description_raw_material || ""
          }));
        } else {
          items.value = [];
        }
      } catch (error) {
        console.error("Error fetching insumos:", error);
        items.value = [];
      } finally {
        loading.value = false;
      }
    }
    const isModalOpen = ref(false);
    const modalTitle = ref("Registrar Insumo");
    const form = ref({
      id: null,
      name: "",
      type: "unit",
      unit: "und",
      desc: ""
    });
    const unitOptions = {
      weight: ["kg", "g"],
      volume: ["L", "ml"],
      unit: ["und"]
    };
    function handleMeasureTypeChange(type) {
      form.value.type = type;
      form.value.unit = unitOptions[type][0];
    }
    function openCreateModal() {
      form.value = { id: null, name: "", type: "unit", unit: "und", desc: "" };
      modalTitle.value = "Registrar Insumo";
      isModalOpen.value = true;
    }
    function openEditModal(item) {
      form.value = { ...item, type: item.type };
      modalTitle.value = "Editar Insumo";
      isModalOpen.value = true;
    }
    async function saveInsumo() {
      if (!form.value.name) return;
      try {
        let response;
        if (form.value.id !== null) {
          response = await $fetch(apiBase, {
            method: "POST",
            body: new URLSearchParams({
              editRawMaterial: "ok",
              id_raw_material: String(form.value.id),
              name_raw_material: form.value.name,
              measure_type: form.value.type,
              unit_raw_material: form.value.unit,
              description_raw_material: form.value.desc
            }).toString(),
            headers: { "Content-Type": "application/x-www-form-urlencoded" }
          });
        } else {
          response = await $fetch(apiBase, {
            method: "POST",
            body: new URLSearchParams({
              saveLabMaterial: "ok",
              name_raw_material: form.value.name,
              measure_type: form.value.type,
              unit_raw_material: form.value.unit,
              description_raw_material: form.value.desc,
              id_office_raw_material: String(auth.officeId || 6),
              id_admin_raw_material: String(auth.user?.id_admin || 1),
              is_insumo: "1"
            }).toString(),
            headers: { "Content-Type": "application/x-www-form-urlencoded" }
          });
        }
        const resText = typeof response === "string" ? response.trim() : JSON.stringify(response);
        if (resText.startsWith("error")) {
          alert("Error del servidor: " + resText.split("|")[1]);
          return;
        }
        isModalOpen.value = false;
        await fetchInsumos();
      } catch (error) {
        console.error("Error saving insumo:", error);
        alert("Error al guardar el insumo: " + (error.message || error));
        isModalOpen.value = false;
      }
    }
    async function deleteInsumo(item) {
      if (!confirm(`¿Eliminar el insumo "${item.name}"?

Solo se puede eliminar si no tiene stock, no está en recetas ni en historial de producción.`)) return;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({
            deleteRawMaterial: "ok",
            id_raw_material: String(item.id)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const resText = typeof response === "string" ? response.trim() : JSON.stringify(response);
        if (resText.startsWith("error")) {
          alert("No se puede eliminar: " + (resText.split("|")[1] || resText));
          return;
        }
        await fetchInsumos();
      } catch (error) {
        console.error("Error deleting insumo:", error);
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      const _component_UButton = _sfc_main$c;
      const _component_UInput = _sfc_main$6;
      const _component_UModal = _sfc_main$1;
      const _component_USelect = _sfc_main$2;
      const _component_UTextarea = _sfc_main$3;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm"><div><h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">`);
      _push(ssrRenderComponent(_component_UIcon, {
        name: "i-lucide-boxes",
        class: "text-green-500"
      }, null, _parent));
      _push(` Catálogo de Insumos </h1><p class="text-slate-500 dark:text-slate-400 text-sm mt-1"> Gestión y registro de envases, tapas, etiquetas y embalaje para la fase de producción y envasado. </p></div>`);
      if (unref(auth).role === "lab_admin") {
        _push(ssrRenderComponent(_component_UButton, {
          icon: "i-lucide-plus",
          color: "green",
          size: "md",
          class: "font-bold!",
          onClick: openCreateModal
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Agregar Insumo `);
            } else {
              return [
                createTextVNode(" Agregar Insumo ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm"><div class="p-5 border-b border-slate-200 dark:border-slate-800/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"><h3 class="font-bold text-slate-800 dark:text-white tracking-wide"> Insumos en Catálogo (${ssrInterpolate(unref(filteredItems).length)}`);
      if (unref(searchQuery)) {
        _push(`<span class="text-xs font-normal text-slate-400"> de ${ssrInterpolate(unref(items).length)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`) </h3>`);
      _push(ssrRenderComponent(_component_UInput, {
        modelValue: unref(searchQuery),
        "onUpdate:modelValue": ($event) => isRef(searchQuery) ? searchQuery.value = $event : null,
        icon: "i-lucide-search",
        placeholder: "Buscar insumo...",
        size: "sm",
        class: "w-full sm:w-64"
      }, null, _parent));
      _push(`</div><div class="overflow-x-auto">`);
      if (unref(loading)) {
        _push(`<div class="p-8 text-center text-slate-500 dark:text-slate-400">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-loader-2",
          class: "w-8 h-8 animate-spin mx-auto text-green-500 mb-2"
        }, null, _parent));
        _push(` Cargando catálogo de insumos... </div>`);
      } else {
        _push(`<table class="w-full text-left text-sm text-slate-600 dark:text-slate-300"><thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase tracking-wider text-slate-550 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800/80"><tr><th class="px-6 py-4"> ID </th><th class="px-6 py-4"> Nombre </th><th class="px-6 py-4"> Tipo </th><th class="px-6 py-4"> Stock Actual </th><th class="px-6 py-4"> Unidad </th><th class="px-6 py-4"> Descripción </th>`);
        if (unref(auth).role === "lab_admin") {
          _push(`<th class="px-6 py-4 text-center"> Acciones </th>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</tr></thead><tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">`);
        if (unref(filteredItems).length === 0) {
          _push(`<tr><td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">No se encontraron insumos registrados.</td></tr>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--[-->`);
        ssrRenderList(unref(filteredItems), (item, i) => {
          _push(`<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-all duration-150"><td class="px-6 py-4 font-mono text-slate-400 dark:text-slate-500">${ssrInterpolate(i + 1)}</td><td class="px-6 py-4 font-bold text-slate-800 dark:text-white uppercase tracking-wide">${ssrInterpolate(item.name)}</td><td class="px-6 py-4">`);
          if (item.type === "weight") {
            _push(`<span class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-amber-400/10 text-amber-600 dark:text-amber-300 border border-amber-400/20">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-weight",
              class: "w-3.5 h-3.5"
            }, null, _parent));
            _push(` Peso </span>`);
          } else if (item.type === "volume") {
            _push(`<span class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-cyan-400/10 text-cyan-600 dark:text-cyan-300 border border-cyan-400/20">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-droplet",
              class: "w-3.5 h-3.5"
            }, null, _parent));
            _push(` Volumen </span>`);
          } else {
            _push(`<span class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-green-400/10 text-green-600 dark:text-green-300 border border-green-400/20">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-box",
              class: "w-3.5 h-3.5"
            }, null, _parent));
            _push(` Unidad </span>`);
          }
          _push(`</td><td class="px-6 py-4 font-bold font-mono text-slate-800 dark:text-slate-100">${ssrInterpolate(item.stock.toFixed(2))}</td><td class="px-6 py-4"><span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs">${ssrInterpolate(item.unit)}</span></td><td class="px-6 py-4 text-xs text-slate-550 dark:text-slate-400 max-w-xs truncate">${ssrInterpolate(item.desc)}</td>`);
          if (unref(auth).role === "lab_admin") {
            _push(`<td class="px-6 py-4 text-center flex items-center justify-center gap-2">`);
            _push(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-edit-2",
              color: "warning",
              variant: "subtle",
              size: "xs",
              onClick: ($event) => openEditModal(item)
            }, null, _parent));
            _push(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-trash-2",
              color: "rose",
              variant: "subtle",
              size: "xs",
              onClick: ($event) => deleteInsumo(item)
            }, null, _parent));
            _push(`</td>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</tr>`);
        });
        _push(`<!--]--></tbody></table>`);
      }
      _push(`</div></div>`);
      _push(ssrRenderComponent(_component_UModal, {
        open: unref(isModalOpen),
        "onUpdate:open": ($event) => isRef(isModalOpen) ? isModalOpen.value = $event : null
      }, {
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-full p-6 space-y-4 text-slate-900 dark:text-white bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800"${_scopeId}><div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3"${_scopeId}><h3 class="text-lg font-bold text-slate-800 dark:text-white tracking-wide"${_scopeId}>${ssrInterpolate(unref(modalTitle))}</h3>`);
            _push2(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-x",
              color: "neutral",
              variant: "ghost",
              size: "sm",
              onClick: ($event) => isModalOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(`</div><form class="space-y-4"${_scopeId}><div class="space-y-1.5"${_scopeId}><label class="text-xs font-bold uppercase tracking-wider text-slate-400"${_scopeId}>Nombre de Insumo</label>`);
            _push2(ssrRenderComponent(_component_UInput, {
              modelValue: unref(form).name,
              "onUpdate:modelValue": ($event) => unref(form).name = $event,
              placeholder: "Ej. Envase PET de 250ml con Dosificador",
              size: "md",
              required: ""
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="space-y-1.5"${_scopeId}><label class="text-xs font-bold uppercase tracking-wider text-slate-400 block"${_scopeId}>Tipo de Medida</label><div class="grid grid-cols-3 gap-2 mt-2"${_scopeId}><button type="button" class="${ssrRenderClass([unref(form).type === "weight" ? "border-amber-400/80 bg-amber-500/10 text-amber-600 dark:text-amber-300 shadow-lg shadow-amber-500/5" : "border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400", "flex flex-col items-center justify-center py-3 px-2 rounded-xl border-2 transition-all duration-150"])}"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-weight",
              class: "w-8 h-8 mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<span class="font-bold text-sm"${_scopeId}>Peso</span></button><button type="button" class="${ssrRenderClass([unref(form).type === "volume" ? "border-cyan-400/80 bg-cyan-500/10 text-cyan-600 dark:text-cyan-300 shadow-lg shadow-cyan-500/5" : "border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400", "flex flex-col items-center justify-center py-3 px-2 rounded-xl border-2 transition-all duration-150"])}"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-droplet",
              class: "w-8 h-8 mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<span class="font-bold text-sm"${_scopeId}>Volumen</span></button><button type="button" class="${ssrRenderClass([unref(form).type === "unit" ? "border-green-400/80 bg-green-500/10 text-green-600 dark:text-green-300 shadow-lg shadow-green-500/5" : "border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400", "flex flex-col items-center justify-center py-3 px-2 rounded-xl border-2 transition-all duration-150"])}"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-box",
              class: "w-8 h-8 mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<span class="font-bold text-sm"${_scopeId}>Unidad</span></button></div></div><div class="space-y-1.5"${_scopeId}><label class="text-xs font-bold uppercase tracking-wider text-slate-400"${_scopeId}>Unidad de Medida</label>`);
            _push2(ssrRenderComponent(_component_USelect, {
              modelValue: unref(form).unit,
              "onUpdate:modelValue": ($event) => unref(form).unit = $event,
              items: unitOptions[unref(form).type],
              size: "md",
              class: "w-full",
              required: ""
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="space-y-1.5"${_scopeId}><label class="text-xs font-bold uppercase tracking-wider text-slate-400"${_scopeId}>Descripción (Opcional)</label>`);
            _push2(ssrRenderComponent(_component_UTextarea, {
              modelValue: unref(form).desc,
              "onUpdate:modelValue": ($event) => unref(form).desc = $event,
              placeholder: "Especificaciones, material del envase o detalles de embalaje...",
              rows: "3"
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-6"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              label: "Cancelar",
              variant: "ghost",
              color: "neutral",
              onClick: ($event) => isModalOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UButton, {
              type: "submit",
              label: "Guardar Insumo",
              color: "green",
              class: "font-bold!"
            }, null, _parent2, _scopeId));
            _push2(`</div></form></div>`);
          } else {
            return [
              createVNode("div", { class: "w-full p-6 space-y-4 text-slate-900 dark:text-white bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800" }, [
                createVNode("div", { class: "flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3" }, [
                  createVNode("h3", { class: "text-lg font-bold text-slate-800 dark:text-white tracking-wide" }, toDisplayString(unref(modalTitle)), 1),
                  createVNode(_component_UButton, {
                    icon: "i-lucide-x",
                    color: "neutral",
                    variant: "ghost",
                    size: "sm",
                    onClick: ($event) => isModalOpen.value = false
                  }, null, 8, ["onClick"])
                ]),
                createVNode("form", {
                  class: "space-y-4",
                  onSubmit: withModifiers(saveInsumo, ["prevent"])
                }, [
                  createVNode("div", { class: "space-y-1.5" }, [
                    createVNode("label", { class: "text-xs font-bold uppercase tracking-wider text-slate-400" }, "Nombre de Insumo"),
                    createVNode(_component_UInput, {
                      modelValue: unref(form).name,
                      "onUpdate:modelValue": ($event) => unref(form).name = $event,
                      placeholder: "Ej. Envase PET de 250ml con Dosificador",
                      size: "md",
                      required: ""
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  createVNode("div", { class: "space-y-1.5" }, [
                    createVNode("label", { class: "text-xs font-bold uppercase tracking-wider text-slate-400 block" }, "Tipo de Medida"),
                    createVNode("div", { class: "grid grid-cols-3 gap-2 mt-2" }, [
                      createVNode("button", {
                        type: "button",
                        class: ["flex flex-col items-center justify-center py-3 px-2 rounded-xl border-2 transition-all duration-150", unref(form).type === "weight" ? "border-amber-400/80 bg-amber-500/10 text-amber-600 dark:text-amber-300 shadow-lg shadow-amber-500/5" : "border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400"],
                        onClick: ($event) => handleMeasureTypeChange("weight")
                      }, [
                        createVNode(_component_UIcon, {
                          name: "i-lucide-weight",
                          class: "w-8 h-8 mb-2"
                        }),
                        createVNode("span", { class: "font-bold text-sm" }, "Peso")
                      ], 10, ["onClick"]),
                      createVNode("button", {
                        type: "button",
                        class: ["flex flex-col items-center justify-center py-3 px-2 rounded-xl border-2 transition-all duration-150", unref(form).type === "volume" ? "border-cyan-400/80 bg-cyan-500/10 text-cyan-600 dark:text-cyan-300 shadow-lg shadow-cyan-500/5" : "border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400"],
                        onClick: ($event) => handleMeasureTypeChange("volume")
                      }, [
                        createVNode(_component_UIcon, {
                          name: "i-lucide-droplet",
                          class: "w-8 h-8 mb-2"
                        }),
                        createVNode("span", { class: "font-bold text-sm" }, "Volumen")
                      ], 10, ["onClick"]),
                      createVNode("button", {
                        type: "button",
                        class: ["flex flex-col items-center justify-center py-3 px-2 rounded-xl border-2 transition-all duration-150", unref(form).type === "unit" ? "border-green-400/80 bg-green-500/10 text-green-600 dark:text-green-300 shadow-lg shadow-green-500/5" : "border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400"],
                        onClick: ($event) => handleMeasureTypeChange("unit")
                      }, [
                        createVNode(_component_UIcon, {
                          name: "i-lucide-box",
                          class: "w-8 h-8 mb-2"
                        }),
                        createVNode("span", { class: "font-bold text-sm" }, "Unidad")
                      ], 10, ["onClick"])
                    ])
                  ]),
                  createVNode("div", { class: "space-y-1.5" }, [
                    createVNode("label", { class: "text-xs font-bold uppercase tracking-wider text-slate-400" }, "Unidad de Medida"),
                    createVNode(_component_USelect, {
                      modelValue: unref(form).unit,
                      "onUpdate:modelValue": ($event) => unref(form).unit = $event,
                      items: unitOptions[unref(form).type],
                      size: "md",
                      class: "w-full",
                      required: ""
                    }, null, 8, ["modelValue", "onUpdate:modelValue", "items"])
                  ]),
                  createVNode("div", { class: "space-y-1.5" }, [
                    createVNode("label", { class: "text-xs font-bold uppercase tracking-wider text-slate-400" }, "Descripción (Opcional)"),
                    createVNode(_component_UTextarea, {
                      modelValue: unref(form).desc,
                      "onUpdate:modelValue": ($event) => unref(form).desc = $event,
                      placeholder: "Especificaciones, material del envase o detalles de embalaje...",
                      rows: "3"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  createVNode("div", { class: "flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-6" }, [
                    createVNode(_component_UButton, {
                      label: "Cancelar",
                      variant: "ghost",
                      color: "neutral",
                      onClick: ($event) => isModalOpen.value = false
                    }, null, 8, ["onClick"]),
                    createVNode(_component_UButton, {
                      type: "submit",
                      label: "Guardar Insumo",
                      color: "green",
                      class: "font-bold!"
                    })
                  ])
                ], 32)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/insumos.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=insumos-D2Rg5x89.mjs.map
