import { _ as __nuxt_component_0 } from './DynamicTable-BNaw1lt7.mjs';
import { defineComponent, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';
import { useRoute } from 'vue-router';
import './server.mjs';
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
import './Badge-BLusyd6V.mjs';
import './Slideover-CcB_tEFt.mjs';
import './overlay-DzoQAbpj.mjs';
import './Switch-BSq9jMma.mjs';
import './Label-BRZJsVxu.mjs';
import './useFormControl-9B1GcqCr.mjs';
import './VisuallyHiddenInput-lcZAWR9l.mjs';
import './SelectMenu-jDjllcVC.mjs';
import '@tanstack/vue-virtual';
import './Select-1euaQPd0.mjs';
import './Textarea-C_8t1vyc.mjs';
import './OrderReceiptModal-DsvU9H_7.mjs';
import './Modal-DVs2bKsP.mjs';
import './Card-Dj8zIcA3.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "[module]",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    const moduleName = computed(() => {
      const m = route.params.module;
      return Array.isArray(m) ? m[0] : m;
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_DynamicTable = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container-fluid" }, _attrs))}>`);
      _push(ssrRenderComponent(_component_DynamicTable, { "module-name": unref(moduleName) }, null, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/[module].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_module_-DIZtglC0.mjs.map
