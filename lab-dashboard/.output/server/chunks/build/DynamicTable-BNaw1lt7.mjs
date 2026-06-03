import { K as useAuthStore, h as _sfc_main$6, i as _sfc_main$c, g as _sfc_main$h, j as _sfc_main$f, Q as useComponentProps, a7 as useLocale, J as useAppConfig, a1 as useForwardProps, I as tv, a0 as useForwardExpose, f as Primitive, m as createContext } from './server.mjs';
import { _ as _sfc_main$4 } from './Badge-BLusyd6V.mjs';
import { defineComponent, computed, ref, watch, mergeProps, unref, withCtx, createTextVNode, toDisplayString, createVNode, useSlots, renderSlot, openBlock, createBlock, createCommentVNode, Fragment, renderList, toRefs, normalizeProps, guardReactiveProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList, ssrRenderSlot, ssrRenderAttr } from 'vue/server-renderer';
import { reactivePick, useVModel } from '@vueuse/core';
import { _ as _sfc_main$5 } from './Slideover-CcB_tEFt.mjs';
import { _ as _sfc_main$7 } from './Switch-BSq9jMma.mjs';
import { _ as _sfc_main$8 } from './SelectMenu-jDjllcVC.mjs';
import { _ as _sfc_main$9 } from './Select-1euaQPd0.mjs';
import { _ as _sfc_main$a } from './Textarea-C_8t1vyc.mjs';
import { _ as __nuxt_component_7$1 } from './OrderReceiptModal-DsvU9H_7.mjs';
import { _ as _sfc_main$b } from './Modal-DVs2bKsP.mjs';
import { _ as _sfc_main$d } from './Card-Dj8zIcA3.mjs';

var PaginationEllipsis_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
  __name: "PaginationEllipsis",
  props: {
    asChild: {
      type: Boolean,
      required: false
    },
    as: {
      type: null,
      required: false
    }
  },
  setup(__props) {
    const props = __props;
    useForwardExpose();
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(Primitive), mergeProps(props, { "data-type": "ellipsis" }), {
        default: withCtx(() => [renderSlot(_ctx.$slots, "default", {}, () => [_cache[0] || (_cache[0] = createTextVNode("…"))])]),
        _: 3
      }, 16);
    };
  }
});
var PaginationEllipsis_default = PaginationEllipsis_vue_vue_type_script_setup_true_lang_default;
const [injectPaginationRootContext, providePaginationRootContext] = /* @__PURE__ */ createContext("PaginationRoot");
var PaginationRoot_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
  __name: "PaginationRoot",
  props: {
    page: {
      type: Number,
      required: false
    },
    defaultPage: {
      type: Number,
      required: false,
      default: 1
    },
    itemsPerPage: {
      type: Number,
      required: true
    },
    total: {
      type: Number,
      required: false,
      default: 0
    },
    siblingCount: {
      type: Number,
      required: false,
      default: 2
    },
    disabled: {
      type: Boolean,
      required: false
    },
    showEdges: {
      type: Boolean,
      required: false,
      default: false
    },
    asChild: {
      type: Boolean,
      required: false
    },
    as: {
      type: null,
      required: false,
      default: "nav"
    }
  },
  emits: ["update:page"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emits = __emit;
    const { siblingCount, disabled, showEdges } = toRefs(props);
    useForwardExpose();
    const page = useVModel(props, "page", emits, {
      defaultValue: props.defaultPage,
      passive: props.page === void 0
    });
    const pageCount = computed(() => Math.max(1, Math.ceil(props.total / (props.itemsPerPage || 1))));
    providePaginationRootContext({
      page,
      onPageChange(value) {
        page.value = value;
      },
      pageCount,
      siblingCount,
      disabled,
      showEdges
    });
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(Primitive), {
        as: _ctx.as,
        "as-child": _ctx.asChild
      }, {
        default: withCtx(() => [renderSlot(_ctx.$slots, "default", {
          page: unref(page),
          pageCount: pageCount.value
        })]),
        _: 3
      }, 8, ["as", "as-child"]);
    };
  }
});
var PaginationRoot_default = PaginationRoot_vue_vue_type_script_setup_true_lang_default;
var PaginationFirst_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
  __name: "PaginationFirst",
  props: {
    asChild: {
      type: Boolean,
      required: false
    },
    as: {
      type: null,
      required: false,
      default: "button"
    }
  },
  setup(__props) {
    const props = __props;
    const rootContext = injectPaginationRootContext();
    useForwardExpose();
    const disabled = computed(() => rootContext.page.value === 1 || rootContext.disabled.value);
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
        "aria-label": "First Page",
        type: _ctx.as === "button" ? "button" : void 0,
        disabled: disabled.value,
        onClick: _cache[0] || (_cache[0] = ($event) => !disabled.value && unref(rootContext).onPageChange(1))
      }), {
        default: withCtx(() => [renderSlot(_ctx.$slots, "default", {}, () => [_cache[1] || (_cache[1] = createTextVNode("First page"))])]),
        _: 3
      }, 16, ["type", "disabled"]);
    };
  }
});
var PaginationFirst_default = PaginationFirst_vue_vue_type_script_setup_true_lang_default;
var PaginationLast_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
  __name: "PaginationLast",
  props: {
    asChild: {
      type: Boolean,
      required: false
    },
    as: {
      type: null,
      required: false,
      default: "button"
    }
  },
  setup(__props) {
    const props = __props;
    const rootContext = injectPaginationRootContext();
    useForwardExpose();
    const disabled = computed(() => rootContext.page.value === rootContext.pageCount.value || rootContext.disabled.value);
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
        "aria-label": "Last Page",
        type: _ctx.as === "button" ? "button" : void 0,
        disabled: disabled.value,
        onClick: _cache[0] || (_cache[0] = ($event) => !disabled.value && unref(rootContext).onPageChange(unref(rootContext).pageCount.value))
      }), {
        default: withCtx(() => [renderSlot(_ctx.$slots, "default", {}, () => [_cache[1] || (_cache[1] = createTextVNode("Last page"))])]),
        _: 3
      }, 16, ["type", "disabled"]);
    };
  }
});
var PaginationLast_default = PaginationLast_vue_vue_type_script_setup_true_lang_default;
function range(start, end) {
  const length = end - start + 1;
  return Array.from({ length }, (_, idx) => idx + start);
}
function transform(items) {
  return items.map((value) => {
    if (typeof value === "number") return {
      type: "page",
      value
    };
    return { type: "ellipsis" };
  });
}
const ELLIPSIS = "ellipsis";
function getRange(currentPage, pageCount, siblingCount, showEdges) {
  const firstPageIndex = 1;
  const lastPageIndex = pageCount;
  const leftSiblingIndex = Math.max(currentPage - siblingCount, firstPageIndex);
  const rightSiblingIndex = Math.min(currentPage + siblingCount, lastPageIndex);
  if (showEdges) {
    const totalPageNumbers = Math.min(2 * siblingCount + 5, pageCount);
    const itemCount = totalPageNumbers - 2;
    const showLeftEllipsis = leftSiblingIndex > firstPageIndex + 2 && Math.abs(lastPageIndex - itemCount - firstPageIndex + 1) > 2 && Math.abs(leftSiblingIndex - firstPageIndex) > 2;
    const showRightEllipsis = rightSiblingIndex < lastPageIndex - 2 && Math.abs(lastPageIndex - itemCount) > 2 && Math.abs(lastPageIndex - rightSiblingIndex) > 2;
    if (!showLeftEllipsis && showRightEllipsis) {
      const leftRange = range(1, itemCount);
      return [
        ...leftRange,
        ELLIPSIS,
        lastPageIndex
      ];
    }
    if (showLeftEllipsis && !showRightEllipsis) {
      const rightRange = range(lastPageIndex - itemCount + 1, lastPageIndex);
      return [
        firstPageIndex,
        ELLIPSIS,
        ...rightRange
      ];
    }
    if (showLeftEllipsis && showRightEllipsis) {
      const middleRange = range(leftSiblingIndex, rightSiblingIndex);
      return [
        firstPageIndex,
        ELLIPSIS,
        ...middleRange,
        ELLIPSIS,
        lastPageIndex
      ];
    }
    const fullRange = range(firstPageIndex, lastPageIndex);
    return fullRange;
  } else {
    const itemCount = siblingCount * 2 + 1;
    if (pageCount < itemCount) return range(1, lastPageIndex);
    else if (currentPage <= siblingCount + 1) return range(firstPageIndex, itemCount);
    else if (pageCount - currentPage <= siblingCount) return range(pageCount - itemCount + 1, lastPageIndex);
    else return range(leftSiblingIndex, rightSiblingIndex);
  }
}
var PaginationList_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
  __name: "PaginationList",
  props: {
    asChild: {
      type: Boolean,
      required: false
    },
    as: {
      type: null,
      required: false
    }
  },
  setup(__props) {
    const props = __props;
    useForwardExpose();
    const rootContext = injectPaginationRootContext();
    const transformedRange = computed(() => {
      return transform(getRange(rootContext.page.value, rootContext.pageCount.value, rootContext.siblingCount.value, rootContext.showEdges.value));
    });
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(Primitive), normalizeProps(guardReactiveProps(props)), {
        default: withCtx(() => [renderSlot(_ctx.$slots, "default", { items: transformedRange.value })]),
        _: 3
      }, 16);
    };
  }
});
var PaginationList_default = PaginationList_vue_vue_type_script_setup_true_lang_default;
var PaginationListItem_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
  __name: "PaginationListItem",
  props: {
    value: {
      type: Number,
      required: true
    },
    asChild: {
      type: Boolean,
      required: false
    },
    as: {
      type: null,
      required: false,
      default: "button"
    }
  },
  setup(__props) {
    const props = __props;
    useForwardExpose();
    const rootContext = injectPaginationRootContext();
    const isSelected = computed(() => rootContext.page.value === props.value);
    const disabled = computed(() => rootContext.disabled.value);
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
        "data-type": "page",
        "aria-label": `Page ${_ctx.value}`,
        "aria-current": isSelected.value ? "page" : void 0,
        "data-selected": isSelected.value ? "true" : void 0,
        disabled: disabled.value,
        type: _ctx.as === "button" ? "button" : void 0,
        onClick: _cache[0] || (_cache[0] = ($event) => !disabled.value && unref(rootContext).onPageChange(_ctx.value))
      }), {
        default: withCtx(() => [renderSlot(_ctx.$slots, "default", {}, () => [createTextVNode(toDisplayString(_ctx.value), 1)])]),
        _: 3
      }, 16, [
        "aria-label",
        "aria-current",
        "data-selected",
        "disabled",
        "type"
      ]);
    };
  }
});
var PaginationListItem_default = PaginationListItem_vue_vue_type_script_setup_true_lang_default;
var PaginationNext_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
  __name: "PaginationNext",
  props: {
    asChild: {
      type: Boolean,
      required: false
    },
    as: {
      type: null,
      required: false,
      default: "button"
    }
  },
  setup(__props) {
    const props = __props;
    useForwardExpose();
    const rootContext = injectPaginationRootContext();
    const disabled = computed(() => rootContext.page.value === rootContext.pageCount.value || rootContext.disabled.value);
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
        "aria-label": "Next Page",
        type: _ctx.as === "button" ? "button" : void 0,
        disabled: disabled.value,
        onClick: _cache[0] || (_cache[0] = ($event) => !disabled.value && unref(rootContext).onPageChange(unref(rootContext).page.value + 1))
      }), {
        default: withCtx(() => [renderSlot(_ctx.$slots, "default", {}, () => [_cache[1] || (_cache[1] = createTextVNode("Next page"))])]),
        _: 3
      }, 16, ["type", "disabled"]);
    };
  }
});
var PaginationNext_default = PaginationNext_vue_vue_type_script_setup_true_lang_default;
var PaginationPrev_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
  __name: "PaginationPrev",
  props: {
    asChild: {
      type: Boolean,
      required: false
    },
    as: {
      type: null,
      required: false,
      default: "button"
    }
  },
  setup(__props) {
    const props = __props;
    useForwardExpose();
    const rootContext = injectPaginationRootContext();
    const disabled = computed(() => rootContext.page.value === 1 || rootContext.disabled.value);
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
        "aria-label": "Previous Page",
        type: _ctx.as === "button" ? "button" : void 0,
        disabled: disabled.value,
        onClick: _cache[0] || (_cache[0] = ($event) => !disabled.value && unref(rootContext).onPageChange(unref(rootContext).page.value - 1))
      }), {
        default: withCtx(() => [renderSlot(_ctx.$slots, "default", {}, () => [_cache[1] || (_cache[1] = createTextVNode("Prev page"))])]),
        _: 3
      }, 16, ["type", "disabled"]);
    };
  }
});
var PaginationPrev_default = PaginationPrev_vue_vue_type_script_setup_true_lang_default;
const theme = {
  "slots": {
    "root": "",
    "list": "flex items-center gap-1",
    "ellipsis": "pointer-events-none",
    "label": "min-w-5 text-center",
    "first": "",
    "prev": "",
    "item": "",
    "next": "",
    "last": ""
  }
};
const _sfc_main$3 = {
  __name: "UPagination",
  __ssrInlineRender: true,
  props: {
    as: { type: null, required: false },
    firstIcon: { type: null, required: false },
    prevIcon: { type: null, required: false },
    nextIcon: { type: null, required: false },
    lastIcon: { type: null, required: false },
    ellipsisIcon: { type: null, required: false },
    color: { type: null, required: false, default: "neutral" },
    variant: { type: null, required: false, default: "outline" },
    activeColor: { type: null, required: false, default: "primary" },
    activeVariant: { type: null, required: false, default: "solid" },
    showControls: { type: Boolean, required: false, default: true },
    size: { type: null, required: false },
    to: { type: Function, required: false },
    class: { type: null, required: false },
    ui: { type: Object, required: false },
    defaultPage: { type: Number, required: false },
    disabled: { type: Boolean, required: false },
    itemsPerPage: { type: Number, required: false, default: 10 },
    page: { type: Number, required: false },
    showEdges: { type: Boolean, required: false, default: false },
    siblingCount: { type: Number, required: false, default: 2 },
    total: { type: Number, required: false, default: 0 }
  },
  emits: ["update:page"],
  setup(__props, { emit: __emit }) {
    const _props = __props;
    const emits = __emit;
    const slots = useSlots();
    const props = useComponentProps("pagination", _props);
    const { dir } = useLocale();
    const appConfig = useAppConfig();
    const rootProps = useForwardProps(reactivePick(props, "as", "defaultPage", "disabled", "itemsPerPage", "page", "showEdges", "siblingCount", "total"), emits);
    const firstIcon = computed(() => props.firstIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronDoubleRight : appConfig.ui.icons.chevronDoubleLeft));
    const prevIcon = computed(() => props.prevIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronRight : appConfig.ui.icons.chevronLeft));
    const nextIcon = computed(() => props.nextIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronLeft : appConfig.ui.icons.chevronRight));
    const lastIcon = computed(() => props.lastIcon || (dir.value === "rtl" ? appConfig.ui.icons.chevronDoubleLeft : appConfig.ui.icons.chevronDoubleRight));
    const ui = computed(() => tv({ extend: tv(theme), ...appConfig.ui?.pagination || {} })());
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(unref(PaginationRoot_default), mergeProps(unref(rootProps), {
        "data-slot": "root",
        class: ui.value.root({ class: [unref(props).ui?.root, unref(props).class] })
      }, _attrs), {
        default: withCtx(({ page, pageCount }, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(PaginationList_default), {
              "data-slot": "list",
              class: ui.value.list({ class: unref(props).ui?.list })
            }, {
              default: withCtx(({ items }, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  if (unref(props).showControls || !!slots.first) {
                    _push3(ssrRenderComponent(unref(PaginationFirst_default), {
                      "as-child": "",
                      "data-slot": "first",
                      class: ui.value.first({ class: unref(props).ui?.first })
                    }, {
                      default: withCtx((_, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          ssrRenderSlot(_ctx.$slots, "first", {}, () => {
                            _push4(ssrRenderComponent(_sfc_main$c, {
                              color: unref(props).color,
                              variant: unref(props).variant,
                              size: unref(props).size,
                              icon: firstIcon.value,
                              to: unref(props).to?.(1)
                            }, null, _parent4, _scopeId3));
                          }, _push4, _parent4, _scopeId3);
                        } else {
                          return [
                            renderSlot(_ctx.$slots, "first", {}, () => [
                              createVNode(_sfc_main$c, {
                                color: unref(props).color,
                                variant: unref(props).variant,
                                size: unref(props).size,
                                icon: firstIcon.value,
                                to: unref(props).to?.(1)
                              }, null, 8, ["color", "variant", "size", "icon", "to"])
                            ])
                          ];
                        }
                      }),
                      _: 2
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  if (unref(props).showControls || !!slots.prev) {
                    _push3(ssrRenderComponent(unref(PaginationPrev_default), {
                      "as-child": "",
                      "data-slot": "prev",
                      class: ui.value.prev({ class: unref(props).ui?.prev })
                    }, {
                      default: withCtx((_, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          ssrRenderSlot(_ctx.$slots, "prev", {}, () => {
                            _push4(ssrRenderComponent(_sfc_main$c, {
                              color: unref(props).color,
                              variant: unref(props).variant,
                              size: unref(props).size,
                              icon: prevIcon.value,
                              to: page > 1 ? unref(props).to?.(page - 1) : void 0
                            }, null, _parent4, _scopeId3));
                          }, _push4, _parent4, _scopeId3);
                        } else {
                          return [
                            renderSlot(_ctx.$slots, "prev", {}, () => [
                              createVNode(_sfc_main$c, {
                                color: unref(props).color,
                                variant: unref(props).variant,
                                size: unref(props).size,
                                icon: prevIcon.value,
                                to: page > 1 ? unref(props).to?.(page - 1) : void 0
                              }, null, 8, ["color", "variant", "size", "icon", "to"])
                            ])
                          ];
                        }
                      }),
                      _: 2
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  _push3(`<!--[-->`);
                  ssrRenderList(items, (item, index) => {
                    _push3(`<!--[-->`);
                    if (item.type === "page") {
                      _push3(ssrRenderComponent(unref(PaginationListItem_default), {
                        "as-child": "",
                        value: item.value,
                        "data-slot": "item",
                        class: ui.value.item({ class: unref(props).ui?.item })
                      }, {
                        default: withCtx((_, _push4, _parent4, _scopeId3) => {
                          if (_push4) {
                            ssrRenderSlot(_ctx.$slots, "item", mergeProps({ ref_for: true }, { item, index, page, pageCount }), () => {
                              _push4(ssrRenderComponent(_sfc_main$c, {
                                color: page === item.value ? unref(props).activeColor : unref(props).color,
                                variant: page === item.value ? unref(props).activeVariant : unref(props).variant,
                                size: unref(props).size,
                                label: String(item.value),
                                ui: { label: ui.value.label() },
                                to: unref(props).to?.(item.value),
                                square: ""
                              }, null, _parent4, _scopeId3));
                            }, _push4, _parent4, _scopeId3);
                          } else {
                            return [
                              renderSlot(_ctx.$slots, "item", mergeProps({ ref_for: true }, { item, index, page, pageCount }), () => [
                                createVNode(_sfc_main$c, {
                                  color: page === item.value ? unref(props).activeColor : unref(props).color,
                                  variant: page === item.value ? unref(props).activeVariant : unref(props).variant,
                                  size: unref(props).size,
                                  label: String(item.value),
                                  ui: { label: ui.value.label() },
                                  to: unref(props).to?.(item.value),
                                  square: ""
                                }, null, 8, ["color", "variant", "size", "label", "ui", "to"])
                              ])
                            ];
                          }
                        }),
                        _: 2
                      }, _parent3, _scopeId2));
                    } else {
                      _push3(ssrRenderComponent(unref(PaginationEllipsis_default), {
                        "as-child": "",
                        "data-slot": "ellipsis",
                        class: ui.value.ellipsis({ class: unref(props).ui?.ellipsis })
                      }, {
                        default: withCtx((_, _push4, _parent4, _scopeId3) => {
                          if (_push4) {
                            ssrRenderSlot(_ctx.$slots, "ellipsis", { ui: ui.value }, () => {
                              _push4(ssrRenderComponent(_sfc_main$c, {
                                as: "div",
                                color: unref(props).color,
                                variant: unref(props).variant,
                                size: unref(props).size,
                                icon: unref(props).ellipsisIcon || unref(appConfig).ui.icons.ellipsis
                              }, null, _parent4, _scopeId3));
                            }, _push4, _parent4, _scopeId3);
                          } else {
                            return [
                              renderSlot(_ctx.$slots, "ellipsis", { ui: ui.value }, () => [
                                createVNode(_sfc_main$c, {
                                  as: "div",
                                  color: unref(props).color,
                                  variant: unref(props).variant,
                                  size: unref(props).size,
                                  icon: unref(props).ellipsisIcon || unref(appConfig).ui.icons.ellipsis
                                }, null, 8, ["color", "variant", "size", "icon"])
                              ])
                            ];
                          }
                        }),
                        _: 2
                      }, _parent3, _scopeId2));
                    }
                    _push3(`<!--]-->`);
                  });
                  _push3(`<!--]-->`);
                  if (unref(props).showControls || !!slots.next) {
                    _push3(ssrRenderComponent(unref(PaginationNext_default), {
                      "as-child": "",
                      "data-slot": "next",
                      class: ui.value.next({ class: unref(props).ui?.next })
                    }, {
                      default: withCtx((_, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          ssrRenderSlot(_ctx.$slots, "next", {}, () => {
                            _push4(ssrRenderComponent(_sfc_main$c, {
                              color: unref(props).color,
                              variant: unref(props).variant,
                              size: unref(props).size,
                              icon: nextIcon.value,
                              to: page < pageCount ? unref(props).to?.(page + 1) : void 0
                            }, null, _parent4, _scopeId3));
                          }, _push4, _parent4, _scopeId3);
                        } else {
                          return [
                            renderSlot(_ctx.$slots, "next", {}, () => [
                              createVNode(_sfc_main$c, {
                                color: unref(props).color,
                                variant: unref(props).variant,
                                size: unref(props).size,
                                icon: nextIcon.value,
                                to: page < pageCount ? unref(props).to?.(page + 1) : void 0
                              }, null, 8, ["color", "variant", "size", "icon", "to"])
                            ])
                          ];
                        }
                      }),
                      _: 2
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  if (unref(props).showControls || !!slots.last) {
                    _push3(ssrRenderComponent(unref(PaginationLast_default), {
                      "as-child": "",
                      "data-slot": "last",
                      class: ui.value.last({ class: unref(props).ui?.last })
                    }, {
                      default: withCtx((_, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          ssrRenderSlot(_ctx.$slots, "last", {}, () => {
                            _push4(ssrRenderComponent(_sfc_main$c, {
                              color: unref(props).color,
                              variant: unref(props).variant,
                              size: unref(props).size,
                              icon: lastIcon.value,
                              to: unref(props).to?.(pageCount)
                            }, null, _parent4, _scopeId3));
                          }, _push4, _parent4, _scopeId3);
                        } else {
                          return [
                            renderSlot(_ctx.$slots, "last", {}, () => [
                              createVNode(_sfc_main$c, {
                                color: unref(props).color,
                                variant: unref(props).variant,
                                size: unref(props).size,
                                icon: lastIcon.value,
                                to: unref(props).to?.(pageCount)
                              }, null, 8, ["color", "variant", "size", "icon", "to"])
                            ])
                          ];
                        }
                      }),
                      _: 2
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                } else {
                  return [
                    unref(props).showControls || !!slots.first ? (openBlock(), createBlock(unref(PaginationFirst_default), {
                      key: 0,
                      "as-child": "",
                      "data-slot": "first",
                      class: ui.value.first({ class: unref(props).ui?.first })
                    }, {
                      default: withCtx(() => [
                        renderSlot(_ctx.$slots, "first", {}, () => [
                          createVNode(_sfc_main$c, {
                            color: unref(props).color,
                            variant: unref(props).variant,
                            size: unref(props).size,
                            icon: firstIcon.value,
                            to: unref(props).to?.(1)
                          }, null, 8, ["color", "variant", "size", "icon", "to"])
                        ])
                      ]),
                      _: 3
                    }, 8, ["class"])) : createCommentVNode("", true),
                    unref(props).showControls || !!slots.prev ? (openBlock(), createBlock(unref(PaginationPrev_default), {
                      key: 1,
                      "as-child": "",
                      "data-slot": "prev",
                      class: ui.value.prev({ class: unref(props).ui?.prev })
                    }, {
                      default: withCtx(() => [
                        renderSlot(_ctx.$slots, "prev", {}, () => [
                          createVNode(_sfc_main$c, {
                            color: unref(props).color,
                            variant: unref(props).variant,
                            size: unref(props).size,
                            icon: prevIcon.value,
                            to: page > 1 ? unref(props).to?.(page - 1) : void 0
                          }, null, 8, ["color", "variant", "size", "icon", "to"])
                        ])
                      ]),
                      _: 2
                    }, 1032, ["class"])) : createCommentVNode("", true),
                    (openBlock(true), createBlock(Fragment, null, renderList(items, (item, index) => {
                      return openBlock(), createBlock(Fragment, { key: index }, [
                        item.type === "page" ? (openBlock(), createBlock(unref(PaginationListItem_default), {
                          key: 0,
                          "as-child": "",
                          value: item.value,
                          "data-slot": "item",
                          class: ui.value.item({ class: unref(props).ui?.item })
                        }, {
                          default: withCtx(() => [
                            renderSlot(_ctx.$slots, "item", mergeProps({ ref_for: true }, { item, index, page, pageCount }), () => [
                              createVNode(_sfc_main$c, {
                                color: page === item.value ? unref(props).activeColor : unref(props).color,
                                variant: page === item.value ? unref(props).activeVariant : unref(props).variant,
                                size: unref(props).size,
                                label: String(item.value),
                                ui: { label: ui.value.label() },
                                to: unref(props).to?.(item.value),
                                square: ""
                              }, null, 8, ["color", "variant", "size", "label", "ui", "to"])
                            ])
                          ]),
                          _: 2
                        }, 1032, ["value", "class"])) : (openBlock(), createBlock(unref(PaginationEllipsis_default), {
                          key: 1,
                          "as-child": "",
                          "data-slot": "ellipsis",
                          class: ui.value.ellipsis({ class: unref(props).ui?.ellipsis })
                        }, {
                          default: withCtx(() => [
                            renderSlot(_ctx.$slots, "ellipsis", { ui: ui.value }, () => [
                              createVNode(_sfc_main$c, {
                                as: "div",
                                color: unref(props).color,
                                variant: unref(props).variant,
                                size: unref(props).size,
                                icon: unref(props).ellipsisIcon || unref(appConfig).ui.icons.ellipsis
                              }, null, 8, ["color", "variant", "size", "icon"])
                            ])
                          ]),
                          _: 3
                        }, 8, ["class"]))
                      ], 64);
                    }), 128)),
                    unref(props).showControls || !!slots.next ? (openBlock(), createBlock(unref(PaginationNext_default), {
                      key: 2,
                      "as-child": "",
                      "data-slot": "next",
                      class: ui.value.next({ class: unref(props).ui?.next })
                    }, {
                      default: withCtx(() => [
                        renderSlot(_ctx.$slots, "next", {}, () => [
                          createVNode(_sfc_main$c, {
                            color: unref(props).color,
                            variant: unref(props).variant,
                            size: unref(props).size,
                            icon: nextIcon.value,
                            to: page < pageCount ? unref(props).to?.(page + 1) : void 0
                          }, null, 8, ["color", "variant", "size", "icon", "to"])
                        ])
                      ]),
                      _: 2
                    }, 1032, ["class"])) : createCommentVNode("", true),
                    unref(props).showControls || !!slots.last ? (openBlock(), createBlock(unref(PaginationLast_default), {
                      key: 3,
                      "as-child": "",
                      "data-slot": "last",
                      class: ui.value.last({ class: unref(props).ui?.last })
                    }, {
                      default: withCtx(() => [
                        renderSlot(_ctx.$slots, "last", {}, () => [
                          createVNode(_sfc_main$c, {
                            color: unref(props).color,
                            variant: unref(props).variant,
                            size: unref(props).size,
                            icon: lastIcon.value,
                            to: unref(props).to?.(pageCount)
                          }, null, 8, ["color", "variant", "size", "icon", "to"])
                        ])
                      ]),
                      _: 2
                    }, 1032, ["class"])) : createCommentVNode("", true)
                  ];
                }
              }),
              _: 2
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(PaginationList_default), {
                "data-slot": "list",
                class: ui.value.list({ class: unref(props).ui?.list })
              }, {
                default: withCtx(({ items }) => [
                  unref(props).showControls || !!slots.first ? (openBlock(), createBlock(unref(PaginationFirst_default), {
                    key: 0,
                    "as-child": "",
                    "data-slot": "first",
                    class: ui.value.first({ class: unref(props).ui?.first })
                  }, {
                    default: withCtx(() => [
                      renderSlot(_ctx.$slots, "first", {}, () => [
                        createVNode(_sfc_main$c, {
                          color: unref(props).color,
                          variant: unref(props).variant,
                          size: unref(props).size,
                          icon: firstIcon.value,
                          to: unref(props).to?.(1)
                        }, null, 8, ["color", "variant", "size", "icon", "to"])
                      ])
                    ]),
                    _: 3
                  }, 8, ["class"])) : createCommentVNode("", true),
                  unref(props).showControls || !!slots.prev ? (openBlock(), createBlock(unref(PaginationPrev_default), {
                    key: 1,
                    "as-child": "",
                    "data-slot": "prev",
                    class: ui.value.prev({ class: unref(props).ui?.prev })
                  }, {
                    default: withCtx(() => [
                      renderSlot(_ctx.$slots, "prev", {}, () => [
                        createVNode(_sfc_main$c, {
                          color: unref(props).color,
                          variant: unref(props).variant,
                          size: unref(props).size,
                          icon: prevIcon.value,
                          to: page > 1 ? unref(props).to?.(page - 1) : void 0
                        }, null, 8, ["color", "variant", "size", "icon", "to"])
                      ])
                    ]),
                    _: 2
                  }, 1032, ["class"])) : createCommentVNode("", true),
                  (openBlock(true), createBlock(Fragment, null, renderList(items, (item, index) => {
                    return openBlock(), createBlock(Fragment, { key: index }, [
                      item.type === "page" ? (openBlock(), createBlock(unref(PaginationListItem_default), {
                        key: 0,
                        "as-child": "",
                        value: item.value,
                        "data-slot": "item",
                        class: ui.value.item({ class: unref(props).ui?.item })
                      }, {
                        default: withCtx(() => [
                          renderSlot(_ctx.$slots, "item", mergeProps({ ref_for: true }, { item, index, page, pageCount }), () => [
                            createVNode(_sfc_main$c, {
                              color: page === item.value ? unref(props).activeColor : unref(props).color,
                              variant: page === item.value ? unref(props).activeVariant : unref(props).variant,
                              size: unref(props).size,
                              label: String(item.value),
                              ui: { label: ui.value.label() },
                              to: unref(props).to?.(item.value),
                              square: ""
                            }, null, 8, ["color", "variant", "size", "label", "ui", "to"])
                          ])
                        ]),
                        _: 2
                      }, 1032, ["value", "class"])) : (openBlock(), createBlock(unref(PaginationEllipsis_default), {
                        key: 1,
                        "as-child": "",
                        "data-slot": "ellipsis",
                        class: ui.value.ellipsis({ class: unref(props).ui?.ellipsis })
                      }, {
                        default: withCtx(() => [
                          renderSlot(_ctx.$slots, "ellipsis", { ui: ui.value }, () => [
                            createVNode(_sfc_main$c, {
                              as: "div",
                              color: unref(props).color,
                              variant: unref(props).variant,
                              size: unref(props).size,
                              icon: unref(props).ellipsisIcon || unref(appConfig).ui.icons.ellipsis
                            }, null, 8, ["color", "variant", "size", "icon"])
                          ])
                        ]),
                        _: 3
                      }, 8, ["class"]))
                    ], 64);
                  }), 128)),
                  unref(props).showControls || !!slots.next ? (openBlock(), createBlock(unref(PaginationNext_default), {
                    key: 2,
                    "as-child": "",
                    "data-slot": "next",
                    class: ui.value.next({ class: unref(props).ui?.next })
                  }, {
                    default: withCtx(() => [
                      renderSlot(_ctx.$slots, "next", {}, () => [
                        createVNode(_sfc_main$c, {
                          color: unref(props).color,
                          variant: unref(props).variant,
                          size: unref(props).size,
                          icon: nextIcon.value,
                          to: page < pageCount ? unref(props).to?.(page + 1) : void 0
                        }, null, 8, ["color", "variant", "size", "icon", "to"])
                      ])
                    ]),
                    _: 2
                  }, 1032, ["class"])) : createCommentVNode("", true),
                  unref(props).showControls || !!slots.last ? (openBlock(), createBlock(unref(PaginationLast_default), {
                    key: 3,
                    "as-child": "",
                    "data-slot": "last",
                    class: ui.value.last({ class: unref(props).ui?.last })
                  }, {
                    default: withCtx(() => [
                      renderSlot(_ctx.$slots, "last", {}, () => [
                        createVNode(_sfc_main$c, {
                          color: unref(props).color,
                          variant: unref(props).variant,
                          size: unref(props).size,
                          icon: lastIcon.value,
                          to: unref(props).to?.(pageCount)
                        }, null, 8, ["color", "variant", "size", "icon", "to"])
                      ])
                    ]),
                    _: 2
                  }, 1032, ["class"])) : createCommentVNode("", true)
                ]),
                _: 2
              }, 1032, ["class"])
            ];
          }
        }),
        _: 3
      }, _parent));
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("../node_modules/@nuxt/ui/dist/runtime/components/Pagination.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "DynamicForm",
  __ssrInlineRender: true,
  props: {
    moduleName: {},
    initialData: {}
  },
  emits: ["saved", "cancel"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const auth = useAuthStore();
    const MODULE_MAPPING = {
      admins: { id_module: 2, title_module: "admins", suffix_module: "admin", title: "Administradores", editable_module: 0 },
      sucursales: { id_module: 4, title_module: "offices", suffix_module: "office", title: "Sucursales", editable_module: 1 },
      clientes: { id_module: 6, title_module: "clients", suffix_module: "client", title: "Clientes", editable_module: 1 },
      categorias: { id_module: 8, title_module: "categories", suffix_module: "category", title: "Categorías", editable_module: 1 },
      productos: { id_module: 10, title_module: "products", suffix_module: "product", title: "Productos", editable_module: 1 },
      compras: { id_module: 41, title_module: "purchases", suffix_module: "purchase", title: "Compras", editable_module: 1 },
      ordenes: { id_module: 14, title_module: "orders", suffix_module: "order", title: "Órdenes", editable_module: 0 },
      ventas: { id_module: 16, title_module: "sales", suffix_module: "sale", title: "Ventas", editable_module: 0 },
      caja: { id_module: 18, title_module: "cashs", suffix_module: "cash", title: "Caja", editable_module: 1 },
      gastos: { id_module: 20, title_module: "bills", suffix_module: "bill", title: "Gastos", editable_module: 1 },
      proveedores: { id_module: 40, title_module: "suppliers", suffix_module: "supplier", title: "Proveedores", editable_module: 1 },
      almacenes: { id_module: 42, title_module: "warehouses", suffix_module: "warehouse", title: "Almacenes", editable_module: 1 }
    };
    const moduleConfig = computed(() => MODULE_MAPPING[props.moduleName]);
    const columns = ref([]);
    const formModel = ref({});
    const selectOptions = ref({});
    const loading = ref(true);
    const saving = ref(false);
    const apiHeaders = {
      Authorization: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy"
    };
    async function loadFormMetadata() {
      if (!moduleConfig.value) return;
      loading.value = true;
      try {
        const data = await $fetch(`/api/columns?linkTo=id_module_column&equalTo=${moduleConfig.value.id_module}`, {
          headers: apiHeaders
        });
        if (data.status === 200) {
          const cols = data.results || [];
          columns.value = cols;
          const initial = props.initialData || {};
          const model = {};
          for (const col of cols) {
            if (col.title_column.startsWith("date_") || col.title_column === "token_admin" || col.title_column === "token_exp_admin") {
              continue;
            }
            const colName = col.title_column;
            const val = initial[colName];
            if (col.type_column === "boolean") {
              model[colName] = val !== void 0 ? Number(val) === 1 : true;
            } else if (col.type_column === "relations") {
              model[colName] = val !== void 0 && val !== "" ? String(val) : void 0;
              await loadRelationOptions(col.matrix_column);
            } else if (col.type_column === "select") {
              let options = col.matrix_column ? col.matrix_column.split(",") : [];
              if (colName === "rol_admin") {
                options = ["superadmin", "admin", "cajero", "vendedor", "despachador", "lab_admin", "lab_worker", "lab_calidad"];
              }
              model[colName] = val !== void 0 && val !== "" ? String(val) : options.length > 0 ? options[0].trim() : void 0;
              if (colName === "rol_admin") {
                const roleLabels = {
                  superadmin: "Super Administrador",
                  admin: "Administrador",
                  cajero: "Cajero / Caja",
                  vendedor: "Vendedor / Venta Despacho",
                  despachador: "Despachador de Envíos",
                  lab_admin: "Administrador de Laboratorio",
                  lab_worker: "Operador de Laboratorio",
                  lab_calidad: "Control de Calidad"
                };
                selectOptions.value = {
                  ...selectOptions.value,
                  [colName]: options.map((opt) => ({
                    value: opt.trim(),
                    label: roleLabels[opt.trim()] || opt.trim()
                  }))
                };
              } else {
                selectOptions.value = {
                  ...selectOptions.value,
                  [colName]: options.map((opt) => ({
                    value: opt.trim(),
                    label: opt.trim()
                  }))
                };
              }
            } else if (["money", "double", "int", "order"].includes(col.type_column)) {
              if (val !== void 0 && val !== null && val !== "") {
                const parsed = parseFloat(String(val));
                if (!isNaN(parsed)) {
                  const parts = String(parsed).split(".");
                  let intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                  if (parts[1]) {
                    model[colName] = intPart + "," + parts[1];
                  } else {
                    model[colName] = intPart;
                  }
                } else {
                  model[colName] = "";
                }
              } else {
                model[colName] = "";
              }
            } else {
              if (col.type_column === "password") {
                model[colName] = "";
              } else {
                model[colName] = val !== void 0 ? decodeURIComponent(String(val)).replace(/\+/g, " ") : "";
              }
            }
          }
          if (moduleConfig.value.title_module === "purchases" && auth.role !== "superadmin" && !props.initialData) {
            if (auth.role === "despachador") {
              model["id_office_purchase"] = String(auth.warehouseId || "0");
            } else if (auth.officeId) {
              try {
                const whData = await $fetch(`/api/warehouses?linkTo=id_office_warehouse&equalTo=${auth.officeId}`, {
                  headers: apiHeaders
                });
                if (whData.status === 200 && whData.results && whData.results.length > 0) {
                  model["id_office_purchase"] = String(whData.results[0].id_warehouse);
                }
              } catch (e) {
                console.error("Error fetching warehouse for purchases:", e);
              }
            }
          }
          formModel.value = model;
        }
      } catch (e) {
        console.error("Error loading form metadata:", e);
      } finally {
        loading.value = false;
      }
    }
    async function loadRelationOptions(matrixTable) {
      if (selectOptions.value[matrixTable]) return;
      try {
        const data = await $fetch(`/api/${matrixTable}`, {
          headers: apiHeaders
        });
        if (data.status === 200 && data.results) {
          const mapped = data.results.filter((r) => {
            const firstKey = Object.keys(r)[0];
            return r[firstKey] && String(r[firstKey]).trim() !== "";
          }).map((r) => {
            const firstKey = Object.keys(r)[0];
            const secondKey = Object.keys(r)[1];
            return {
              value: String(r[firstKey]).trim(),
              label: decodeURIComponent(r[secondKey] || "").replace(/\+/g, " ")
            };
          });
          selectOptions.value = {
            ...selectOptions.value,
            [matrixTable]: mapped
          };
        } else {
          selectOptions.value = {
            ...selectOptions.value,
            [matrixTable]: []
          };
        }
      } catch (e) {
        console.error(`Error loading relations for ${matrixTable}:`, e);
        selectOptions.value = {
          ...selectOptions.value,
          [matrixTable]: []
        };
      }
    }
    async function handleSubmit() {
      if (!moduleConfig.value) return;
      saving.value = true;
      if (props.moduleName === "compras") {
        if (!formModel.value.id_supplier_purchase) {
          alert("Por favor selecciona un proveedor.");
          saving.value = false;
          return;
        }
        if (!formModel.value.id_office_purchase) {
          alert("Por favor selecciona un almacén.");
          saving.value = false;
          return;
        }
        if (!formModel.value.id_product_purchase) {
          alert("Por favor selecciona un producto.");
          saving.value = false;
          return;
        }
        const qty = parseFormattedNumber(formModel.value.qty_purchase);
        if (qty <= 0) {
          alert("Por favor ingresa una cantidad válida mayor a 0.");
          saving.value = false;
          return;
        }
      }
      try {
        const config = moduleConfig.value;
        const isEdit = !!props.initialData;
        const idKey = `id_${config.suffix_module}`;
        const body = new URLSearchParams();
        Object.entries(formModel.value).forEach(([key, val]) => {
          let finalVal = val;
          const matchedCol = columns.value.find((c) => c.title_column === key);
          if (typeof val === "boolean") {
            finalVal = val ? "1" : "0";
          } else if (matchedCol && ["money", "double", "int", "order"].includes(matchedCol.type_column)) {
            let strVal = String(val).replace(/\./g, "").replace(",", ".");
            let numVal = parseFloat(strVal) || 0;
            if (numVal < 0) {
              numVal = Math.abs(numVal);
            }
            finalVal = String(numVal);
          }
          body.append(key, String(finalVal));
        });
        if (!isEdit) {
          const dateCreatedCol = `date_created_${config.suffix_module}`;
          const hasDateCreated = columns.value.some((c) => c.title_column === dateCreatedCol);
          if (hasDateCreated) {
            body.append(dateCreatedCol, (/* @__PURE__ */ new Date()).toISOString().split("T")[0]);
          }
        }
        let url = `/api/${config.title_module}`;
        let method = "POST";
        const queryParams = {
          token: "no",
          except: idKey
        };
        if (isEdit) {
          method = "PUT";
          queryParams.id = props.initialData[idKey];
          queryParams.nameId = idKey;
        }
        const res = await $fetch(url, {
          method,
          headers: {
            ...apiHeaders,
            "Content-Type": "application/x-www-form-urlencoded"
          },
          query: queryParams,
          body: body.toString()
        });
        if (res.status === 200) {
          emit("saved");
        } else {
          alert(`Error al guardar: ${res.results || "Verifica los campos e intenta de nuevo"}`);
        }
      } catch (e) {
        console.error("Error saving form:", e);
        alert("Error al enviar los datos del formulario.");
      } finally {
        saving.value = false;
      }
    }
    function parseFormattedNumber(val) {
      if (val === void 0 || val === null || val === "") return 0;
      const str = String(val).replace(/\./g, "").replace(",", ".");
      return parseFloat(str) || 0;
    }
    function formatNumber(num) {
      if (isNaN(num)) return "0";
      const parts = String(num).split(".");
      let intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
      if (parts[1]) {
        let decPart = parts[1].substring(0, 2);
        return intPart + "," + decPart;
      } else {
        return intPart;
      }
    }
    watch(
      () => [formModel.value.cost_purchase, formModel.value.qty_purchase],
      ([newCost, newQty]) => {
        if (props.moduleName === "compras") {
          const cost = parseFormattedNumber(newCost);
          const qty = parseFormattedNumber(newQty);
          formModel.value.invest_purchase = formatNumber(cost * qty);
        }
      }
    );
    watch(() => props.initialData, () => {
      loadFormMetadata();
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      const _component_USwitch = _sfc_main$7;
      const _component_USelectMenu = _sfc_main$8;
      const _component_USelect = _sfc_main$9;
      const _component_UInput = _sfc_main$6;
      const _component_UTextarea = _sfc_main$a;
      const _component_UButton = _sfc_main$c;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "h-full flex flex-col justify-between" }, _attrs))}><div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">`);
      if (loading.value) {
        _push(`<div class="flex justify-center items-center py-12">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-loader-2",
          class: "animate-spin w-8 h-8 text-green-500"
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<form class="space-y-4"><!--[-->`);
        ssrRenderList(columns.value, (col) => {
          _push(`<div>`);
          if (!col.title_column.startsWith("date_") && col.title_column !== "token_admin" && col.title_column !== "token_exp_admin" && col.title_column !== `id_${moduleConfig.value.suffix_module}` && !(col.title_column === "id_warehouse_admin" && formModel.value.rol_admin !== "despachador") && !(moduleConfig.value.title_module === "purchases" && col.title_column === "id_office_purchase" && unref(auth).role === "despachador")) {
            _push(`<div><label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">${ssrInterpolate(col.alias_column || col.title_column)}</label>`);
            if (col.type_column === "boolean") {
              _push(`<div class="flex items-center gap-3">`);
              _push(ssrRenderComponent(_component_USwitch, {
                modelValue: formModel.value[col.title_column],
                "onUpdate:modelValue": ($event) => formModel.value[col.title_column] = $event
              }, null, _parent));
              _push(`<span class="text-sm text-slate-600 dark:text-slate-400">${ssrInterpolate(formModel.value[col.title_column] ? "Activo (ON)" : "Inactivo (OFF)")}</span></div>`);
            } else if (col.type_column === "relations") {
              _push(`<div>`);
              _push(ssrRenderComponent(_component_USelectMenu, {
                modelValue: formModel.value[col.title_column],
                "onUpdate:modelValue": ($event) => formModel.value[col.title_column] = $event,
                items: selectOptions.value[col.matrix_column] || [],
                class: "w-full",
                placeholder: "Seleccionar opción...",
                ui: { content: "z-[100]" },
                "value-key": "value",
                "label-key": "label"
              }, null, _parent));
              _push(`</div>`);
            } else if (col.type_column === "select") {
              _push(`<div>`);
              _push(ssrRenderComponent(_component_USelect, {
                modelValue: formModel.value[col.title_column],
                "onUpdate:modelValue": ($event) => formModel.value[col.title_column] = $event,
                items: selectOptions.value[col.title_column] || [],
                class: "w-full capitalize",
                ui: { content: "z-[100]" }
              }, null, _parent));
              _push(`</div>`);
            } else if (["money", "double", "int", "order"].includes(col.type_column)) {
              _push(`<div>`);
              _push(ssrRenderComponent(_component_UInput, {
                modelValue: formModel.value[col.title_column],
                "onUpdate:modelValue": ($event) => formModel.value[col.title_column] = $event,
                type: "text",
                class: "w-full format-numeric",
                "data-format-numeric": "true",
                inputmode: "decimal",
                placeholder: "0,00",
                disabled: moduleConfig.value?.title_module === "purchases" && col.title_column === "invest_purchase"
              }, null, _parent));
              _push(`</div>`);
            } else if (col.type_column === "password") {
              _push(`<div>`);
              _push(ssrRenderComponent(_component_UInput, {
                modelValue: formModel.value[col.title_column],
                "onUpdate:modelValue": ($event) => formModel.value[col.title_column] = $event,
                type: "password",
                placeholder: "•••••••• (dejar en blanco para mantener)",
                class: "w-full"
              }, null, _parent));
              _push(`</div>`);
            } else if (col.type_column === "image") {
              _push(`<div class="space-y-2">`);
              _push(ssrRenderComponent(_component_UInput, {
                modelValue: formModel.value[col.title_column],
                "onUpdate:modelValue": ($event) => formModel.value[col.title_column] = $event,
                placeholder: "URL de la imagen...",
                class: "w-full"
              }, null, _parent));
              if (formModel.value[col.title_column]) {
                _push(`<div class="mt-2 flex items-center border border-slate-200 dark:border-slate-700 rounded-lg p-2 bg-slate-50 dark:bg-slate-900 w-max"><img${ssrRenderAttr("src", formModel.value[col.title_column])} class="w-16 h-16 rounded object-cover"></div>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div>`);
            } else if (col.title_column.includes("description") || col.title_column.includes("notes")) {
              _push(`<div>`);
              _push(ssrRenderComponent(_component_UTextarea, {
                modelValue: formModel.value[col.title_column],
                "onUpdate:modelValue": ($event) => formModel.value[col.title_column] = $event,
                rows: "3",
                class: "w-full"
              }, null, _parent));
              _push(`</div>`);
            } else {
              _push(`<div>`);
              _push(ssrRenderComponent(_component_UInput, {
                modelValue: formModel.value[col.title_column],
                "onUpdate:modelValue": ($event) => formModel.value[col.title_column] = $event,
                class: "w-full"
              }, null, _parent));
              _push(`</div>`);
            }
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        });
        _push(`<!--]--></form>`);
      }
      _push(`</div><div class="p-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3 bg-slate-50 dark:bg-slate-950">`);
      _push(ssrRenderComponent(_component_UButton, {
        color: "neutral",
        variant: "ghost",
        onClick: ($event) => emit("cancel")
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Cancelar `);
          } else {
            return [
              createTextVNode(" Cancelar ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UButton, {
        color: "primary",
        loading: saving.value,
        onClick: handleSubmit
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Guardar `);
          } else {
            return [
              createTextVNode(" Guardar ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/DynamicForm.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const __nuxt_component_7 = Object.assign(_sfc_main$2, { __name: "DynamicForm" });
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "CashDetailsModal",
  __ssrInlineRender: true,
  props: {
    isOpen: { type: Boolean },
    cash: {}
  },
  emits: ["update:isOpen", "close"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const loading = ref(false);
    const cashData = ref(null);
    const expenses = ref([]);
    const sales = ref([]);
    const isOpenModel = computed({
      get: () => props.isOpen,
      set: (val) => {
        emit("update:isOpen", val);
        if (!val) emit("close");
      }
    });
    function formatToMySQLDate(dateInput) {
      if (!dateInput) return "";
      const d = new Date(dateInput);
      if (isNaN(d.getTime())) return "";
      return d.toISOString().replace("T", " ").split(".")[0];
    }
    async function fetchCashDetails(cashId) {
      loading.value = true;
      cashData.value = null;
      expenses.value = [];
      sales.value = [];
      try {
        const apiHeaders = { Authorization: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy" };
        const cashRes = await $fetch(`/api/relations?rel=cashs,offices&type=cash,office&linkTo=id_cash&equalTo=${cashId}`, { headers: apiHeaders });
        if (cashRes && cashRes.status === 200 && cashRes.results && cashRes.results.length > 0) {
          cashData.value = cashRes.results[0];
          const rawStart = cashData.value.date_created_cash;
          const rawEnd = cashData.value.date_end_cash || /* @__PURE__ */ new Date();
          const startDate = formatToMySQLDate(rawStart);
          const endDate = formatToMySQLDate(rawEnd);
          const officeId = cashData.value.id_office_cash;
          const expRes = await $fetch(`/api/bills?linkTo=id_office_bill,date_created_bill&between1=${officeId},${startDate}&between2=${officeId},${endDate}`, { headers: apiHeaders });
          if (expRes && expRes.status === 200 && expRes.results) {
            expenses.value = expRes.results;
          }
          const salesRes = await $fetch(`/api/relations?rel=orders,clients&type=order,client&linkTo=id_office_order,date_order&between1=${officeId},${startDate}&between2=${officeId},${endDate}&select=transaction_order,date_order,method_order,total_order,name_client`, { headers: apiHeaders });
          if (salesRes && salesRes.status === 200 && salesRes.results) {
            sales.value = salesRes.results;
          }
        }
      } catch (e) {
        console.error("Error fetching cash details:", e);
      } finally {
        loading.value = false;
      }
    }
    watch(() => props.isOpen, (newVal) => {
      if (newVal && props.cash?.id_cash) {
        fetchCashDetails(props.cash.id_cash);
      }
    });
    function formatCurrency(val) {
      return new Intl.NumberFormat("es-BO", { style: "currency", currency: "BOB" }).format(Number(val));
    }
    function decodeStr(str) {
      if (!str) return "";
      return decodeURIComponent(str).replace(/\+/g, " ");
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UModal = _sfc_main$b;
      const _component_UIcon = _sfc_main$h;
      const _component_UButton = _sfc_main$c;
      const _component_UCard = _sfc_main$d;
      _push(ssrRenderComponent(_component_UModal, mergeProps({
        modelValue: isOpenModel.value,
        "onUpdate:modelValue": ($event) => isOpenModel.value = $event,
        ui: { width: "sm:max-w-4xl" }
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 relative rounded-xl h-[80vh] overflow-y-auto"${_scopeId}>`);
            if (loading.value) {
              _push2(`<div class="flex flex-col items-center justify-center py-12"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-loader-2",
                class: "w-10 h-10 animate-spin text-primary mb-2"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-500"${_scopeId}>Cargando detalles de caja...</p></div>`);
            } else if (cashData.value) {
              _push2(`<!--[--><div class="absolute top-4 right-4"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UButton, {
                color: "gray",
                variant: "ghost",
                icon: "i-lucide-x",
                onClick: ($event) => isOpenModel.value = false
              }, null, _parent2, _scopeId));
              _push2(`</div><div class="mb-6"${_scopeId}><h1 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, { name: "i-lucide-receipt" }, null, _parent2, _scopeId));
              _push2(` Detalles de Caja </h1><p class="text-gray-500 text-sm mt-1"${_scopeId}>Caja #${ssrInterpolate(cashData.value.id_cash)} - Sucursal: ${ssrInterpolate(decodeStr(cashData.value.title_office))}</p></div><div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UCard, { class: "bg-blue-50 dark:bg-blue-900/20 ring-blue-500/20" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="text-xs font-semibold text-blue-600 mb-1"${_scopeId2}>Monto Inicial</div><div class="text-xl font-bold"${_scopeId2}>${ssrInterpolate(formatCurrency(cashData.value.initial_cash))}</div>`);
                  } else {
                    return [
                      createVNode("div", { class: "text-xs font-semibold text-blue-600 mb-1" }, "Monto Inicial"),
                      createVNode("div", { class: "text-xl font-bold" }, toDisplayString(formatCurrency(cashData.value.initial_cash)), 1)
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(ssrRenderComponent(_component_UCard, { class: "bg-green-50 dark:bg-green-900/20 ring-green-500/20" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="text-xs font-semibold text-green-600 mb-1"${_scopeId2}>Ingresos (Ventas)</div><div class="text-xl font-bold"${_scopeId2}>${ssrInterpolate(formatCurrency(cashData.value.money_cash))}</div>`);
                  } else {
                    return [
                      createVNode("div", { class: "text-xs font-semibold text-green-600 mb-1" }, "Ingresos (Ventas)"),
                      createVNode("div", { class: "text-xl font-bold" }, toDisplayString(formatCurrency(cashData.value.money_cash)), 1)
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(ssrRenderComponent(_component_UCard, { class: "bg-red-50 dark:bg-red-900/20 ring-red-500/20" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="text-xs font-semibold text-red-600 mb-1"${_scopeId2}>Gastos</div><div class="text-xl font-bold"${_scopeId2}>${ssrInterpolate(formatCurrency(cashData.value.bills_cash))}</div>`);
                  } else {
                    return [
                      createVNode("div", { class: "text-xs font-semibold text-red-600 mb-1" }, "Gastos"),
                      createVNode("div", { class: "text-xl font-bold" }, toDisplayString(formatCurrency(cashData.value.bills_cash)), 1)
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(ssrRenderComponent(_component_UCard, { class: "bg-indigo-50 dark:bg-indigo-900/20 ring-indigo-500/20" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="text-xs font-semibold text-indigo-600 mb-1"${_scopeId2}>Total en Caja</div><div class="text-xl font-bold"${_scopeId2}>${ssrInterpolate(formatCurrency(Number(cashData.value.initial_cash) + Number(cashData.value.money_cash) - Number(cashData.value.bills_cash)))}</div>`);
                  } else {
                    return [
                      createVNode("div", { class: "text-xs font-semibold text-indigo-600 mb-1" }, "Total en Caja"),
                      createVNode("div", { class: "text-xl font-bold" }, toDisplayString(formatCurrency(Number(cashData.value.initial_cash) + Number(cashData.value.money_cash) - Number(cashData.value.bills_cash))), 1)
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div><div class="grid grid-cols-1 md:grid-cols-2 gap-8"${_scopeId}><div${_scopeId}><h4 class="font-bold text-gray-700 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-800 pb-2 mb-3 flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-arrow-up-right",
                class: "text-green-500"
              }, null, _parent2, _scopeId));
              _push2(` Ingresos Registrados </h4>`);
              if (sales.value.length === 0) {
                _push2(`<div class="text-sm text-gray-500 italic"${_scopeId}>No hay ventas registradas en esta sesión.</div>`);
              } else {
                _push2(`<div class="space-y-2 max-h-64 overflow-y-auto pr-2"${_scopeId}><!--[-->`);
                ssrRenderList(sales.value, (sale) => {
                  _push2(`<div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm border border-gray-100 dark:border-slate-700"${_scopeId}><div${_scopeId}><div class="font-bold"${_scopeId}>${ssrInterpolate(sale.transaction_order)}</div><div class="text-xs text-gray-500"${_scopeId}>${ssrInterpolate(decodeStr(sale.name_client))}</div></div><div class="text-right"${_scopeId}><div class="font-bold text-green-600"${_scopeId}>+ ${ssrInterpolate(formatCurrency(sale.total_order))}</div><div class="text-xs text-gray-400"${_scopeId}>${ssrInterpolate(sale.date_order)}</div></div></div>`);
                });
                _push2(`<!--]--></div>`);
              }
              _push2(`</div><div${_scopeId}><h4 class="font-bold text-gray-700 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-800 pb-2 mb-3 flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-arrow-down-right",
                class: "text-red-500"
              }, null, _parent2, _scopeId));
              _push2(` Gastos Registrados </h4>`);
              if (expenses.value.length === 0) {
                _push2(`<div class="text-sm text-gray-500 italic"${_scopeId}>No hay gastos registrados en esta sesión.</div>`);
              } else {
                _push2(`<div class="space-y-2 max-h-64 overflow-y-auto pr-2"${_scopeId}><!--[-->`);
                ssrRenderList(expenses.value, (exp) => {
                  _push2(`<div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm border border-gray-100 dark:border-slate-700"${_scopeId}><div${_scopeId}><div class="font-bold"${_scopeId}>${ssrInterpolate(decodeStr(exp.description_bill))}</div></div><div class="text-right"${_scopeId}><div class="font-bold text-red-500"${_scopeId}>- ${ssrInterpolate(formatCurrency(exp.amount_bill))}</div><div class="text-xs text-gray-400"${_scopeId}>${ssrInterpolate(exp.date_created_bill)}</div></div></div>`);
                });
                _push2(`<!--]--></div>`);
              }
              _push2(`</div></div><div class="mt-6 flex justify-between items-center text-sm text-gray-500 border-t border-gray-100 dark:border-slate-800 pt-4"${_scopeId}><div${_scopeId}>Apertura: <strong${_scopeId}>${ssrInterpolate(cashData.value.date_created_cash)}</strong></div>`);
              if (cashData.value.date_end_cash) {
                _push2(`<div${_scopeId}>Cierre: <strong${_scopeId}>${ssrInterpolate(cashData.value.date_end_cash)}</strong></div>`);
              } else {
                _push2(`<div class="text-emerald-500 font-medium flex items-center gap-1"${_scopeId}><span class="relative flex h-2 w-2"${_scopeId}><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"${_scopeId}></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"${_scopeId}></span></span> Sesión Activa </div>`);
              }
              _push2(`</div><!--]-->`);
            } else {
              _push2(`<div class="py-12 text-center"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-alert-triangle",
                class: "w-12 h-12 text-red-400 mx-auto mb-3"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-lg font-medium text-gray-700 dark:text-gray-300"${_scopeId}>No se pudieron cargar los detalles</p>`);
              _push2(ssrRenderComponent(_component_UButton, {
                color: "gray",
                class: "mt-4",
                onClick: ($event) => isOpenModel.value = false
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
              _push2(`</div>`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 relative rounded-xl h-[80vh] overflow-y-auto" }, [
                loading.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "flex flex-col items-center justify-center py-12"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "w-10 h-10 animate-spin text-primary mb-2"
                  }),
                  createVNode("p", { class: "text-gray-500" }, "Cargando detalles de caja...")
                ])) : cashData.value ? (openBlock(), createBlock(Fragment, { key: 1 }, [
                  createVNode("div", { class: "absolute top-4 right-4" }, [
                    createVNode(_component_UButton, {
                      color: "gray",
                      variant: "ghost",
                      icon: "i-lucide-x",
                      onClick: ($event) => isOpenModel.value = false
                    }, null, 8, ["onClick"])
                  ]),
                  createVNode("div", { class: "mb-6" }, [
                    createVNode("h1", { class: "text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2" }, [
                      createVNode(_component_UIcon, { name: "i-lucide-receipt" }),
                      createTextVNode(" Detalles de Caja ")
                    ]),
                    createVNode("p", { class: "text-gray-500 text-sm mt-1" }, "Caja #" + toDisplayString(cashData.value.id_cash) + " - Sucursal: " + toDisplayString(decodeStr(cashData.value.title_office)), 1)
                  ]),
                  createVNode("div", { class: "grid grid-cols-2 md:grid-cols-4 gap-4 mb-8" }, [
                    createVNode(_component_UCard, { class: "bg-blue-50 dark:bg-blue-900/20 ring-blue-500/20" }, {
                      default: withCtx(() => [
                        createVNode("div", { class: "text-xs font-semibold text-blue-600 mb-1" }, "Monto Inicial"),
                        createVNode("div", { class: "text-xl font-bold" }, toDisplayString(formatCurrency(cashData.value.initial_cash)), 1)
                      ]),
                      _: 1
                    }),
                    createVNode(_component_UCard, { class: "bg-green-50 dark:bg-green-900/20 ring-green-500/20" }, {
                      default: withCtx(() => [
                        createVNode("div", { class: "text-xs font-semibold text-green-600 mb-1" }, "Ingresos (Ventas)"),
                        createVNode("div", { class: "text-xl font-bold" }, toDisplayString(formatCurrency(cashData.value.money_cash)), 1)
                      ]),
                      _: 1
                    }),
                    createVNode(_component_UCard, { class: "bg-red-50 dark:bg-red-900/20 ring-red-500/20" }, {
                      default: withCtx(() => [
                        createVNode("div", { class: "text-xs font-semibold text-red-600 mb-1" }, "Gastos"),
                        createVNode("div", { class: "text-xl font-bold" }, toDisplayString(formatCurrency(cashData.value.bills_cash)), 1)
                      ]),
                      _: 1
                    }),
                    createVNode(_component_UCard, { class: "bg-indigo-50 dark:bg-indigo-900/20 ring-indigo-500/20" }, {
                      default: withCtx(() => [
                        createVNode("div", { class: "text-xs font-semibold text-indigo-600 mb-1" }, "Total en Caja"),
                        createVNode("div", { class: "text-xl font-bold" }, toDisplayString(formatCurrency(Number(cashData.value.initial_cash) + Number(cashData.value.money_cash) - Number(cashData.value.bills_cash))), 1)
                      ]),
                      _: 1
                    })
                  ]),
                  createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-8" }, [
                    createVNode("div", null, [
                      createVNode("h4", { class: "font-bold text-gray-700 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-800 pb-2 mb-3 flex items-center gap-2" }, [
                        createVNode(_component_UIcon, {
                          name: "i-lucide-arrow-up-right",
                          class: "text-green-500"
                        }),
                        createTextVNode(" Ingresos Registrados ")
                      ]),
                      sales.value.length === 0 ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "text-sm text-gray-500 italic"
                      }, "No hay ventas registradas en esta sesión.")) : (openBlock(), createBlock("div", {
                        key: 1,
                        class: "space-y-2 max-h-64 overflow-y-auto pr-2"
                      }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(sales.value, (sale) => {
                          return openBlock(), createBlock("div", {
                            key: sale.id_order,
                            class: "flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm border border-gray-100 dark:border-slate-700"
                          }, [
                            createVNode("div", null, [
                              createVNode("div", { class: "font-bold" }, toDisplayString(sale.transaction_order), 1),
                              createVNode("div", { class: "text-xs text-gray-500" }, toDisplayString(decodeStr(sale.name_client)), 1)
                            ]),
                            createVNode("div", { class: "text-right" }, [
                              createVNode("div", { class: "font-bold text-green-600" }, "+ " + toDisplayString(formatCurrency(sale.total_order)), 1),
                              createVNode("div", { class: "text-xs text-gray-400" }, toDisplayString(sale.date_order), 1)
                            ])
                          ]);
                        }), 128))
                      ]))
                    ]),
                    createVNode("div", null, [
                      createVNode("h4", { class: "font-bold text-gray-700 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-800 pb-2 mb-3 flex items-center gap-2" }, [
                        createVNode(_component_UIcon, {
                          name: "i-lucide-arrow-down-right",
                          class: "text-red-500"
                        }),
                        createTextVNode(" Gastos Registrados ")
                      ]),
                      expenses.value.length === 0 ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "text-sm text-gray-500 italic"
                      }, "No hay gastos registrados en esta sesión.")) : (openBlock(), createBlock("div", {
                        key: 1,
                        class: "space-y-2 max-h-64 overflow-y-auto pr-2"
                      }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(expenses.value, (exp) => {
                          return openBlock(), createBlock("div", {
                            key: exp.id_bill,
                            class: "flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm border border-gray-100 dark:border-slate-700"
                          }, [
                            createVNode("div", null, [
                              createVNode("div", { class: "font-bold" }, toDisplayString(decodeStr(exp.description_bill)), 1)
                            ]),
                            createVNode("div", { class: "text-right" }, [
                              createVNode("div", { class: "font-bold text-red-500" }, "- " + toDisplayString(formatCurrency(exp.amount_bill)), 1),
                              createVNode("div", { class: "text-xs text-gray-400" }, toDisplayString(exp.date_created_bill), 1)
                            ])
                          ]);
                        }), 128))
                      ]))
                    ])
                  ]),
                  createVNode("div", { class: "mt-6 flex justify-between items-center text-sm text-gray-500 border-t border-gray-100 dark:border-slate-800 pt-4" }, [
                    createVNode("div", null, [
                      createTextVNode("Apertura: "),
                      createVNode("strong", null, toDisplayString(cashData.value.date_created_cash), 1)
                    ]),
                    cashData.value.date_end_cash ? (openBlock(), createBlock("div", { key: 0 }, [
                      createTextVNode("Cierre: "),
                      createVNode("strong", null, toDisplayString(cashData.value.date_end_cash), 1)
                    ])) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "text-emerald-500 font-medium flex items-center gap-1"
                    }, [
                      createVNode("span", { class: "relative flex h-2 w-2" }, [
                        createVNode("span", { class: "animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" }),
                        createVNode("span", { class: "relative inline-flex rounded-full h-2 w-2 bg-emerald-500" })
                      ]),
                      createTextVNode(" Sesión Activa ")
                    ]))
                  ])
                ], 64)) : (openBlock(), createBlock("div", {
                  key: 2,
                  class: "py-12 text-center"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-alert-triangle",
                    class: "w-12 h-12 text-red-400 mx-auto mb-3"
                  }),
                  createVNode("p", { class: "text-lg font-medium text-gray-700 dark:text-gray-300" }, "No se pudieron cargar los detalles"),
                  createVNode(_component_UButton, {
                    color: "gray",
                    class: "mt-4",
                    onClick: ($event) => isOpenModel.value = false
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Cerrar")
                    ]),
                    _: 1
                  }, 8, ["onClick"])
                ]))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/CashDetailsModal.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const __nuxt_component_9 = Object.assign(_sfc_main$1, { __name: "CashDetailsModal" });
const itemsPerPage = 10;
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "DynamicTable",
  __ssrInlineRender: true,
  props: {
    moduleName: {}
  },
  setup(__props) {
    const props = __props;
    const auth = useAuthStore();
    const MODULE_MAPPING = {
      admins: { id_module: 2, title_module: "admins", suffix_module: "admin", title: "Administradores", editable_module: 0 },
      sucursales: { id_module: 4, title_module: "offices", suffix_module: "office", title: "Sucursales", editable_module: 1 },
      clientes: { id_module: 6, title_module: "clients", suffix_module: "client", title: "Clientes", editable_module: 1 },
      categorias: { id_module: 8, title_module: "categories", suffix_module: "category", title: "Categorías", editable_module: 1 },
      productos: { id_module: 10, title_module: "products", suffix_module: "product", title: "Productos", editable_module: 1 },
      compras: { id_module: 41, title_module: "purchases", suffix_module: "purchase", title: "Compras", editable_module: 1 },
      ordenes: { id_module: 14, title_module: "orders", suffix_module: "order", title: "Órdenes", editable_module: 0 },
      ventas: { id_module: 16, title_module: "sales", suffix_module: "sale", title: "Ventas", editable_module: 0 },
      caja: { id_module: 18, title_module: "cashs", suffix_module: "cash", title: "Caja", editable_module: 1 },
      gastos: { id_module: 20, title_module: "bills", suffix_module: "bill", title: "Gastos", editable_module: 1 },
      proveedores: { id_module: 40, title_module: "suppliers", suffix_module: "supplier", title: "Proveedores", editable_module: 1 },
      almacenes: { id_module: 42, title_module: "warehouses", suffix_module: "warehouse", title: "Almacenes", editable_module: 1 }
    };
    const moduleConfig = computed(() => MODULE_MAPPING[props.moduleName]);
    const columns = ref([]);
    const rows = ref([]);
    const loading = ref(true);
    const search = ref("");
    const page = ref(1);
    const totalItems = ref(0);
    const isSlideoverOpen = ref(false);
    const selectedItem = ref(null);
    const relationsCache = ref({});
    const apiHeaders = {
      Authorization: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy"
    };
    async function fetchMetadata() {
      if (!moduleConfig.value) return;
      try {
        const data = await $fetch(`/api/columns?linkTo=id_module_column&equalTo=${moduleConfig.value.id_module}`, {
          headers: apiHeaders
        });
        if (data.status === 200) {
          columns.value = data.results;
          for (const col of columns.value) {
            if (col.type_column === "relations" && col.matrix_column) {
              await fetchRelationCache(col.matrix_column);
            }
          }
        }
      } catch (e) {
        console.error("Error fetching table metadata:", e);
      }
    }
    async function fetchRelationCache(matrixTable) {
      if (relationsCache.value[matrixTable]) return;
      try {
        const data = await $fetch(`/api/${matrixTable}`, {
          headers: apiHeaders
        });
        if (data.status === 200) {
          relationsCache.value[matrixTable] = data.results || [];
        }
      } catch (e) {
        console.error(`Error loading relation cache for ${matrixTable}:`, e);
        relationsCache.value[matrixTable] = [];
      }
    }
    function getRelationLabel(matrixTable, id) {
      const tableData = relationsCache.value[matrixTable];
      if (!tableData || !tableData.length) return id;
      const match = tableData.find((r) => {
        const firstKey = Object.keys(r)[0];
        return String(r[firstKey]) === String(id);
      });
      if (!match) return id;
      const secondKey = Object.keys(match)[1];
      return decodeURIComponent(match[secondKey] || "").replace(/\+/g, " ");
    }
    async function fetchRows() {
      if (!moduleConfig.value) return;
      loading.value = true;
      try {
        const config = moduleConfig.value;
        const idKey = `id_${config.suffix_module}`;
        let url = `/api/${config.title_module}`;
        const params = {
          orderBy: idKey,
          orderMode: "DESC"
        };
        const hasOfficeCol = columns.value.some((c) => c.title_column === `id_office_${config.suffix_module}`);
        if (hasOfficeCol && config.title_module !== "clients") {
          let equalToVal = auth.officeId;
          let shouldFilter = auth.officeId && auth.officeId > 0;
          if (config.title_module === "purchases" && auth.role !== "superadmin") {
            shouldFilter = true;
            if (auth.role === "despachador") {
              equalToVal = auth.warehouseId;
            } else if (auth.officeId) {
              try {
                const whData = await $fetch(`/api/warehouses?linkTo=id_office_warehouse&equalTo=${auth.officeId}`, {
                  headers: apiHeaders
                });
                if (whData.status === 200 && whData.results && whData.results.length > 0) {
                  equalToVal = whData.results[0].id_warehouse;
                } else {
                  equalToVal = 0;
                }
              } catch (e) {
                console.error("Error fetching warehouse for purchases filter:", e);
                equalToVal = 0;
              }
            } else {
              equalToVal = 0;
            }
          }
          if (shouldFilter && equalToVal && equalToVal > 0) {
            params.linkTo = `id_office_${config.suffix_module}`;
            params.equalTo = equalToVal;
          }
        }
        if (search.value) {
          params.linkTo = columns.value.find((c) => c.type_column === "text")?.title_column || idKey;
          params.search = search.value;
        }
        console.log("[fetchRows]", {
          url,
          params,
          role: auth.role,
          officeId: auth.officeId,
          warehouseId: auth.warehouseId,
          columnsCount: columns.value.length
        });
        const data = await $fetch(url, {
          headers: apiHeaders,
          query: params
        });
        if (data.status === 200) {
          rows.value = data.results || [];
          totalItems.value = rows.value.length;
        } else {
          rows.value = [];
          totalItems.value = 0;
        }
      } catch (e) {
        console.error("Error fetching rows:", e);
        rows.value = [];
        totalItems.value = 0;
      } finally {
        loading.value = false;
      }
    }
    const showActions = computed(() => {
      if (!moduleConfig.value) return false;
      const config = moduleConfig.value;
      const isProducts = config.title_module === "products";
      const isSuperOrAdmin = auth.role === "superadmin" || auth.role === "admin";
      return !isProducts && (isSuperOrAdmin || config.editable_module === 1) || isProducts && isSuperOrAdmin;
    });
    const paginatedRows = computed(() => {
      const start = (page.value - 1) * itemsPerPage;
      const end = start + itemsPerPage;
      return rows.value.slice(start, end);
    });
    watch(() => props.moduleName, async () => {
      page.value = 1;
      search.value = "";
      await fetchMetadata();
      await fetchRows();
    });
    watch(search, () => {
      page.value = 1;
      fetchRows();
    });
    watch(() => auth.user, async (newVal, oldVal) => {
      if (newVal !== oldVal) {
        await fetchRows();
      }
    });
    function openCreate() {
      selectedItem.value = null;
      isSlideoverOpen.value = true;
    }
    function openEdit(item) {
      selectedItem.value = item;
      isSlideoverOpen.value = true;
    }
    async function handleDelete(item) {
      if (!moduleConfig.value) return;
      const idKey = `id_${moduleConfig.value.suffix_module}`;
      const idValue = item[idKey];
      if (!confirm("¿Estás seguro de que deseas eliminar este registro?")) return;
      try {
        const res = await $fetch(`/api/${moduleConfig.value.title_module}`, {
          method: "DELETE",
          headers: apiHeaders,
          query: {
            id: idValue,
            nameId: idKey,
            token: "no",
            except: idKey
          }
        });
        if (res.status === 200) {
          await fetchRows();
        } else {
          alert(`Error al eliminar: ${res.results || "Intenta de nuevo"}`);
        }
      } catch (e) {
        console.error("Error deleting item:", e);
        alert("Error de red al intentar eliminar el registro.");
      }
    }
    function onFormSaved() {
      isSlideoverOpen.value = false;
      fetchRows();
    }
    const isReceiptModalOpen = ref(false);
    const selectedOrderId = ref(null);
    function openReceipt(id) {
      selectedOrderId.value = id;
      isReceiptModalOpen.value = true;
    }
    const isCashDetailsModalOpen = ref(false);
    const selectedCash = ref(null);
    function openCashDetails(cashRow) {
      selectedCash.value = cashRow;
      isCashDetailsModalOpen.value = true;
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UInput = _sfc_main$6;
      const _component_UButton = _sfc_main$c;
      const _component_UIcon = _sfc_main$h;
      const _component_UAvatar = _sfc_main$f;
      const _component_UBadge = _sfc_main$4;
      const _component_UPagination = _sfc_main$3;
      const _component_USlideover = _sfc_main$5;
      const _component_DynamicForm = __nuxt_component_7;
      const _component_OrderReceiptModal = __nuxt_component_7$1;
      const _component_CashDetailsModal = __nuxt_component_9;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900/60 backdrop-blur border border-slate-800 p-4 rounded-xl"><div><h1 class="text-2xl font-extrabold text-white tracking-tight capitalize bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">${ssrInterpolate(moduleConfig.value?.title || "Administración")}</h1><p class="text-xs text-slate-400 mt-1"> Gestiona los registros de este módulo de forma dinámica y segura. </p></div><div class="flex items-center gap-3 w-full sm:w-auto">`);
      _push(ssrRenderComponent(_component_UInput, {
        modelValue: search.value,
        "onUpdate:modelValue": ($event) => search.value = $event,
        icon: "i-lucide-search",
        placeholder: "Buscar...",
        class: "w-full sm:w-64"
      }, null, _parent));
      if (unref(auth).role === "superadmin" || unref(auth).role === "admin" || moduleConfig.value?.editable_module === 1) {
        _push(ssrRenderComponent(_component_UButton, {
          icon: "i-lucide-plus",
          color: "primary",
          onClick: openCreate
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Agregar `);
            } else {
              return [
                createTextVNode(" Agregar ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden shadow-xl">`);
      if (loading.value) {
        _push(`<div class="flex justify-center py-12">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-loader-2",
          class: "animate-spin w-8 h-8 text-indigo-500"
        }, null, _parent));
        _push(`</div>`);
      } else if (rows.value.length === 0) {
        _push(`<div class="text-center py-12 text-slate-500 text-sm"> No se encontraron registros. </div>`);
      } else {
        _push(`<div class="overflow-x-auto"><table class="w-full text-left border-collapse text-sm text-slate-300"><thead><tr class="bg-slate-950 text-slate-400 border-b border-slate-800"><th class="p-4">#</th><!--[-->`);
        ssrRenderList(columns.value.filter((c) => c.visible_column === 1), (col) => {
          _push(`<th class="p-4">${ssrInterpolate(col.alias_column || col.title_column)}</th>`);
        });
        _push(`<!--]-->`);
        if (showActions.value) {
          _push(`<th class="p-4 text-right">Acciones</th>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</tr></thead><tbody><!--[-->`);
        ssrRenderList(paginatedRows.value, (row, idx) => {
          _push(`<tr class="border-b border-slate-850 hover:bg-slate-900/20"><td class="p-4 text-xs text-slate-500 font-mono">${ssrInterpolate((page.value - 1) * itemsPerPage + idx + 1)}</td><!--[-->`);
          ssrRenderList(columns.value.filter((c) => c.visible_column === 1), (col) => {
            _push(`<td class="p-4">`);
            if (col.type_column === "image") {
              _push(`<div class="flex items-center">`);
              _push(ssrRenderComponent(_component_UAvatar, {
                src: row[col.title_column] ? decodeURIComponent(row[col.title_column]).replace(/\+/g, " ") : "/views/assets/img/multimedia.png",
                size: "lg",
                class: "border border-slate-700 bg-slate-800"
              }, null, _parent));
              _push(`</div>`);
            } else if (col.type_column === "boolean") {
              _push(`<span>`);
              _push(ssrRenderComponent(_component_UBadge, {
                color: row[col.title_column] == 1 ? "emerald" : "rose",
                variant: "subtle",
                class: "capitalize"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`${ssrInterpolate(row[col.title_column] == 1 ? "ON" : "OFF")}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(row[col.title_column] == 1 ? "ON" : "OFF"), 1)
                    ];
                  }
                }),
                _: 2
              }, _parent));
              _push(`</span>`);
            } else if (col.type_column === "money") {
              _push(`<span class="font-semibold text-teal-400 font-mono"> Bs. ${ssrInterpolate(parseFloat(row[col.title_column] || 0).toFixed(2))}</span>`);
            } else if (col.type_column === "relations") {
              _push(`<span>`);
              _push(ssrRenderComponent(_component_UBadge, {
                color: "indigo",
                variant: "outline"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`${ssrInterpolate(getRelationLabel(col.matrix_column, row[col.title_column]))}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(getRelationLabel(col.matrix_column, row[col.title_column])), 1)
                    ];
                  }
                }),
                _: 2
              }, _parent));
              _push(`</span>`);
            } else if (col.type_column === "select") {
              _push(`<span>`);
              _push(ssrRenderComponent(_component_UBadge, {
                color: "neutral",
                variant: "solid",
                class: "capitalize"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`${ssrInterpolate(row[col.title_column])}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(row[col.title_column]), 1)
                    ];
                  }
                }),
                _: 2
              }, _parent));
              _push(`</span>`);
            } else {
              _push(`<span class="text-sm truncate max-w-xs block">${ssrInterpolate(row[col.title_column] !== null ? decodeURIComponent(String(row[col.title_column])).replace(/\+/g, " ") : "-")}</span>`);
            }
            _push(`</td>`);
          });
          _push(`<!--]-->`);
          if (showActions.value || moduleConfig.value?.title_module === "orders" || moduleConfig.value?.title_module === "cashs") {
            _push(`<td class="p-4 text-right"><div class="flex items-center justify-end gap-2">`);
            if (moduleConfig.value?.title_module === "orders") {
              _push(ssrRenderComponent(_component_UButton, {
                icon: "i-lucide-printer",
                color: "primary",
                variant: "soft",
                size: "xs",
                onClick: ($event) => openReceipt(row[`id_${moduleConfig.value.suffix_module}`]),
                title: "Imprimir Comprobante"
              }, null, _parent));
            } else {
              _push(`<!---->`);
            }
            if (moduleConfig.value?.title_module === "cashs") {
              _push(ssrRenderComponent(_component_UButton, {
                icon: "i-lucide-receipt",
                color: "primary",
                variant: "soft",
                size: "xs",
                onClick: ($event) => openCashDetails(row),
                title: "Ver Detalles de Caja"
              }, null, _parent));
            } else {
              _push(`<!---->`);
            }
            if (showActions.value) {
              _push(`<!--[-->`);
              _push(ssrRenderComponent(_component_UButton, {
                icon: "i-lucide-edit",
                color: "neutral",
                variant: "ghost",
                size: "xs",
                onClick: ($event) => openEdit(row)
              }, null, _parent));
              _push(ssrRenderComponent(_component_UButton, {
                icon: "i-lucide-trash",
                color: "rose",
                variant: "ghost",
                size: "xs",
                onClick: ($event) => handleDelete(row)
              }, null, _parent));
              _push(`<!--]-->`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></td>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</tr>`);
        });
        _push(`<!--]--></tbody></table></div>`);
      }
      if (totalItems.value > itemsPerPage) {
        _push(`<div class="p-4 border-t border-slate-800 flex justify-between items-center bg-slate-950/40"><span class="text-xs text-slate-400"> Mostrando ${ssrInterpolate((page.value - 1) * itemsPerPage + 1)} a ${ssrInterpolate(Math.min(page.value * itemsPerPage, totalItems.value))} de ${ssrInterpolate(totalItems.value)} registros </span>`);
        _push(ssrRenderComponent(_component_UPagination, {
          modelValue: page.value,
          "onUpdate:modelValue": ($event) => page.value = $event,
          total: totalItems.value,
          "page-count": itemsPerPage
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      _push(ssrRenderComponent(_component_USlideover, {
        open: isSlideoverOpen.value,
        "onUpdate:open": ($event) => isSlideoverOpen.value = $event,
        title: selectedItem.value ? "Editar Registro" : "Nuevo Registro",
        class: "z-50"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_DynamicForm, {
              "module-name": props.moduleName,
              "initial-data": selectedItem.value,
              onSaved: onFormSaved,
              onCancel: ($event) => isSlideoverOpen.value = false
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_component_DynamicForm, {
                "module-name": props.moduleName,
                "initial-data": selectedItem.value,
                onSaved: onFormSaved,
                onCancel: ($event) => isSlideoverOpen.value = false
              }, null, 8, ["module-name", "initial-data", "onCancel"])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (moduleConfig.value?.title_module === "orders") {
        _push(ssrRenderComponent(_component_OrderReceiptModal, {
          isOpen: isReceiptModalOpen.value,
          "onUpdate:isOpen": ($event) => isReceiptModalOpen.value = $event,
          "order-id": selectedOrderId.value,
          onClose: ($event) => selectedOrderId.value = null
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (moduleConfig.value?.title_module === "cashs") {
        _push(ssrRenderComponent(_component_CashDetailsModal, {
          isOpen: isCashDetailsModalOpen.value,
          "onUpdate:isOpen": ($event) => isCashDetailsModalOpen.value = $event,
          cash: selectedCash.value,
          onClose: ($event) => selectedCash.value = null
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/DynamicTable.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const __nuxt_component_0 = Object.assign(_sfc_main, { __name: "DynamicTable" });

export { __nuxt_component_0 as _ };
//# sourceMappingURL=DynamicTable-BNaw1lt7.mjs.map
