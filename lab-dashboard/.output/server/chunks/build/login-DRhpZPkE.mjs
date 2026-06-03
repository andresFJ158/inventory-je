import { I as useAuthStore, g as _sfc_main$h } from './server.mjs';
import { defineComponent, ref, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { _ as _export_sfc } from './_plugin-vue_export-helper-1tPrXgE0.mjs';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "login",
  __ssrInlineRender: true,
  setup(__props) {
    useAuthStore();
    const step = ref("select");
    const selectedMode = ref("lab");
    const email = ref("");
    const password = ref("");
    const loading = ref(false);
    const errorMessage = ref("");
    const modeConfig = {
      lab: {
        icon: "i-lucide-flask-conical",
        title: "Laboratorio",
        subtitle: "Control de producción, materias primas y calidad",
        gradient: "from-green-600 to-emerald-500",
        ring: "ring-green-500/30",
        bg: "bg-green-500/10",
        border: "border-green-500/20",
        iconColor: "text-green-400",
        redirectTo: "/"
      },
      pos: {
        icon: "i-lucide-monitor-smartphone",
        title: "Sistema POS",
        subtitle: "Ventas, caja, inventario y despachos",
        gradient: "from-blue-600 to-indigo-500",
        ring: "ring-blue-500/30",
        bg: "bg-blue-500/10",
        border: "border-blue-500/20",
        iconColor: "text-blue-400",
        redirectTo: "/pos"
      }
    };
    const cfg = computed(() => modeConfig[selectedMode.value]);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 px-4 py-12 relative overflow-hidden" }, _attrs))} data-v-730aa4f7><div class="absolute -top-40 -left-40 w-96 h-96 bg-green-500/10 rounded-full blur-3xl pointer-events-none" data-v-730aa4f7></div><div class="absolute -bottom-40 -right-40 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none" data-v-730aa4f7></div><div class="w-full max-w-md relative z-10" data-v-730aa4f7>`);
      if (step.value === "select") {
        _push(`<div class="space-y-6" data-v-730aa4f7><div class="text-center space-y-2" data-v-730aa4f7><div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/5 border border-white/10 mb-2" data-v-730aa4f7>`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-layers",
          class: "w-9 h-9 text-white"
        }, null, _parent));
        _push(`</div><h1 class="text-3xl font-extrabold text-white tracking-tight" data-v-730aa4f7>UniTech ERP</h1><p class="text-slate-400 text-sm" data-v-730aa4f7>Selecciona el módulo al que deseas acceder</p></div><div class="grid grid-cols-1 gap-4" data-v-730aa4f7><button class="group w-full text-left bg-slate-900/60 backdrop-blur border border-slate-800 hover:border-green-500/50 rounded-2xl p-6 transition-all duration-200 hover:shadow-lg hover:shadow-green-900/20 focus:outline-none focus:ring-2 focus:ring-green-500/40" data-v-730aa4f7><div class="flex items-center gap-4" data-v-730aa4f7><div class="w-14 h-14 rounded-xl bg-green-500/10 border border-green-500/20 flex items-center justify-center shrink-0 group-hover:bg-green-500/20 transition-colors" data-v-730aa4f7>`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-flask-conical",
          class: "w-7 h-7 text-green-400"
        }, null, _parent));
        _push(`</div><div class="flex-1 min-w-0" data-v-730aa4f7><h2 class="text-lg font-bold text-white group-hover:text-green-400 transition-colors" data-v-730aa4f7>Laboratorio</h2><p class="text-xs text-slate-400 mt-0.5" data-v-730aa4f7>Producción, materias primas, calidad</p></div>`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-arrow-right",
          class: "w-5 h-5 text-slate-600 group-hover:text-green-400 group-hover:translate-x-1 transition-all"
        }, null, _parent));
        _push(`</div><div class="mt-4 flex flex-wrap gap-2" data-v-730aa4f7><!--[-->`);
        ssrRenderList(["Entradas M.P.", "Producción", "Control Calidad", "Inventario"], (tag) => {
          _push(`<span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-green-500/10 text-green-400 border border-green-500/20" data-v-730aa4f7>${ssrInterpolate(tag)}</span>`);
        });
        _push(`<!--]--></div></button><button class="group w-full text-left bg-slate-900/60 backdrop-blur border border-slate-800 hover:border-blue-500/50 rounded-2xl p-6 transition-all duration-200 hover:shadow-lg hover:shadow-blue-900/20 focus:outline-none focus:ring-2 focus:ring-blue-500/40" data-v-730aa4f7><div class="flex items-center gap-4" data-v-730aa4f7><div class="w-14 h-14 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center shrink-0 group-hover:bg-blue-500/20 transition-colors" data-v-730aa4f7>`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-monitor-smartphone",
          class: "w-7 h-7 text-blue-400"
        }, null, _parent));
        _push(`</div><div class="flex-1 min-w-0" data-v-730aa4f7><h2 class="text-lg font-bold text-white group-hover:text-blue-400 transition-colors" data-v-730aa4f7>Sistema POS</h2><p class="text-xs text-slate-400 mt-0.5" data-v-730aa4f7>Ventas, caja, inventario y despachos</p></div>`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-arrow-right",
          class: "w-5 h-5 text-slate-600 group-hover:text-blue-400 group-hover:translate-x-1 transition-all"
        }, null, _parent));
        _push(`</div><div class="mt-4 flex flex-wrap gap-2" data-v-730aa4f7><!--[-->`);
        ssrRenderList(["Punto de Venta", "Caja", "Órdenes", "Despachos", "Reportes"], (tag) => {
          _push(`<span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20" data-v-730aa4f7>${ssrInterpolate(tag)}</span>`);
        });
        _push(`<!--]--></div></button></div><p class="text-center text-xs text-slate-600" data-v-730aa4f7>UniTech ERP © 2026</p></div>`);
      } else {
        _push(`<div class="space-y-6" data-v-730aa4f7><div class="text-center space-y-3" data-v-730aa4f7><button class="inline-flex items-center gap-2 text-xs text-slate-500 hover:text-slate-300 transition-colors mb-1" data-v-730aa4f7>`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-arrow-left",
          class: "w-3.5 h-3.5"
        }, null, _parent));
        _push(` Cambiar módulo </button><div class="${ssrRenderClass(["inline-flex items-center justify-center w-16 h-16 rounded-2xl border mb-1", unref(cfg).bg, unref(cfg).border])}" data-v-730aa4f7>`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: unref(cfg).icon,
          class: ["w-9 h-9", unref(cfg).iconColor]
        }, null, _parent));
        _push(`</div><div data-v-730aa4f7><h2 class="${ssrRenderClass(["text-2xl font-extrabold bg-gradient-to-r bg-clip-text text-transparent", unref(cfg).gradient])}" data-v-730aa4f7>${ssrInterpolate(unref(cfg).title)}</h2><p class="text-slate-400 text-xs mt-1" data-v-730aa4f7>${ssrInterpolate(unref(cfg).subtitle)}</p></div></div><div class="bg-slate-900/60 backdrop-blur border border-slate-800/80 rounded-2xl p-8 space-y-5 shadow-2xl" data-v-730aa4f7>`);
        if (errorMessage.value) {
          _push(`<div class="flex items-center gap-3 p-3 text-sm text-red-300 bg-red-950/40 border border-red-800/50 rounded-xl" data-v-730aa4f7>`);
          _push(ssrRenderComponent(_component_UIcon, {
            name: "i-lucide-alert-triangle",
            class: "w-4 h-4 text-red-400 shrink-0"
          }, null, _parent));
          _push(`<span data-v-730aa4f7>${ssrInterpolate(errorMessage.value)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<form class="space-y-4" data-v-730aa4f7><div data-v-730aa4f7><label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5" data-v-730aa4f7> Correo Electrónico </label><div class="relative" data-v-730aa4f7>`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-mail",
          class: "absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"
        }, null, _parent));
        _push(`<input${ssrRenderAttr("value", email.value)} type="email" autocomplete="email" required placeholder="ejemplo@unitech.com" class="${ssrRenderClass([selectedMode.value === "lab" ? "focus:ring-green-500/50" : "focus:ring-blue-500/50", "w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:border-transparent transition-all text-sm"])}" data-v-730aa4f7></div></div><div data-v-730aa4f7><label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5" data-v-730aa4f7> Contraseña </label><div class="relative" data-v-730aa4f7>`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-lock",
          class: "absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"
        }, null, _parent));
        _push(`<input${ssrRenderAttr("value", password.value)} type="password" autocomplete="current-password" required placeholder="••••••••" class="${ssrRenderClass([selectedMode.value === "lab" ? "focus:ring-green-500/50" : "focus:ring-blue-500/50", "w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:border-transparent transition-all text-sm"])}" data-v-730aa4f7></div></div><button type="submit"${ssrIncludeBooleanAttr(loading.value) ? " disabled" : ""} class="${ssrRenderClass([selectedMode.value === "lab" ? "from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 shadow-green-900/30" : "from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 shadow-blue-900/30", "w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl text-sm font-bold text-white bg-gradient-to-r transition-all duration-200 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed mt-2"])}" data-v-730aa4f7>`);
        if (loading.value) {
          _push(ssrRenderComponent(_component_UIcon, {
            name: "i-lucide-loader-2",
            class: "w-4 h-4 animate-spin"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(_component_UIcon, {
            name: unref(cfg).icon,
            class: "w-4 h-4"
          }, null, _parent));
        }
        _push(` ${ssrInterpolate(loading.value ? "Iniciando sesión..." : `Ingresar a ${unref(cfg).title}`)}</button></form></div><p class="text-center text-xs text-slate-600" data-v-730aa4f7>UniTech ERP © 2026</p></div>`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/login.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const login = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-730aa4f7"]]);

export { login as default };
//# sourceMappingURL=login-DRhpZPkE.mjs.map
