import { H as useAuthStore, h as _sfc_main$h, g as _sfc_main$c } from './server.mjs';
import { defineComponent, ref, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate } from 'vue/server-renderer';
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

const apiBase = "/ajax/pos.ajax.php";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "inventario-final",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    const items = ref([]);
    const loading = ref(true);
    async function fetchWarehouse() {
      loading.value = true;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({
            getLabWarehouse: "ok",
            id_office: String(auth.officeId || 6)
          }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        if (data.status === 200) {
          items.value = data.results.map((w) => ({
            id: w.id_warehouse,
            name: w.name_product || "Producto Compuesto",
            stock: parseFloat(w.qty_warehouse) || 0,
            cost: parseFloat(w.cost_warehouse) || 0
          }));
        } else {
          items.value = [];
        }
      } catch (error) {
        console.error("Error fetching warehouse:", error);
        items.value = [];
      } finally {
        loading.value = false;
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      const _component_UButton = _sfc_main$c;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm"><h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">`);
      _push(ssrRenderComponent(_component_UIcon, {
        name: "i-lucide-boxes",
        class: "text-green-500"
      }, null, _parent));
      _push(` Inventario Final </h1><p class="text-slate-500 dark:text-slate-400 text-sm mt-1"> Visualización de stock de productos finales compuestos y costos de manufactura. </p></div><div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm"><div class="p-5 border-b border-slate-200 dark:border-slate-800/80 flex justify-between items-center"><h3 class="font-bold text-slate-800 dark:text-white tracking-wide"> Productos Terminados Disponibles </h3>`);
      _push(ssrRenderComponent(_component_UButton, {
        icon: "i-lucide-refresh-cw",
        variant: "ghost",
        color: "neutral",
        size: "xs",
        onClick: fetchWarehouse
      }, null, _parent));
      _push(`</div><div class="overflow-x-auto">`);
      if (loading.value) {
        _push(`<div class="p-8 text-center text-slate-500 dark:text-slate-400">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-loader-2",
          class: "w-8 h-8 animate-spin mx-auto text-green-500 mb-2"
        }, null, _parent));
        _push(` Cargando inventario final desde la base de datos... </div>`);
      } else if (items.value.length === 0) {
        _push(`<div class="text-center p-8 text-slate-500"> No hay productos finalizados disponibles en el Almacén Central. </div>`);
      } else {
        _push(`<table class="w-full text-left text-sm text-slate-650 dark:text-slate-350"><thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800/80"><tr><th class="px-6 py-4"> ID Producto </th><th class="px-6 py-4"> Producto Compuesto </th><th class="px-6 py-4 text-right"> Stock Central Disponible </th><th class="px-6 py-4 text-right"> Costo Real Unitario </th><th class="px-6 py-4 text-right"> Valoración Total </th></tr></thead><tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 font-mono"><!--[-->`);
        ssrRenderList(items.value, (item) => {
          _push(`<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-all duration-150"><td class="px-6 py-4 font-bold text-slate-500"> #${ssrInterpolate(item.id)}</td><td class="px-6 py-4 font-bold text-slate-800 dark:text-white uppercase font-sans">${ssrInterpolate(item.name)}</td><td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">${ssrInterpolate(item.stock.toLocaleString())} <span class="text-xs text-slate-500 font-normal">und</span></td><td class="px-6 py-4 text-right text-slate-700 dark:text-slate-300"> Bs ${ssrInterpolate(item.cost.toFixed(2))}</td><td class="px-6 py-4 text-right font-bold text-slate-900 dark:text-white"> Bs ${ssrInterpolate((item.stock * item.cost).toFixed(2))}</td></tr>`);
        });
        _push(`<!--]--></tbody></table>`);
      }
      _push(`</div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/inventario-final.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=inventario-final-BAujoi3K.mjs.map
