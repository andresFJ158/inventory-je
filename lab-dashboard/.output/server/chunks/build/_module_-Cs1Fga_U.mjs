import { _ as __nuxt_component_0 } from './DynamicTable-9zbkmh2W.mjs';
import { defineComponent, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';
import { useRoute } from 'vue-router';
import './server.mjs';
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
import './Badge-P0JOv5sI.mjs';
import './Slideover-DZ0IYow3.mjs';
import './overlay-DuqFFmJC.mjs';
import './Switch-DkNSnXzc.mjs';
import './Label-CXontjPM.mjs';
import './Select-Di6-gJCC.mjs';
import './Textarea-KHOF13zM.mjs';
import './OrderReceiptModal-B0P38A7N.mjs';
import './Modal-CoprpFuw.mjs';
import './Card-DphZz1jr.mjs';

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
//# sourceMappingURL=_module_-Cs1Fga_U.mjs.map
