import { _ as _sfc_main$1 } from './Modal-DVs2bKsP.mjs';
import { g as _sfc_main$h, i as _sfc_main$c } from './server.mjs';
import { defineComponent, ref, computed, watch, mergeProps, withCtx, createTextVNode, createVNode, openBlock, createBlock, Fragment, toDisplayString, renderList, createCommentVNode, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderList } from 'vue/server-renderer';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "OrderReceiptModal",
  __ssrInlineRender: true,
  props: {
    isOpen: { type: Boolean },
    orderId: {}
  },
  emits: ["update:isOpen", "close"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const loading = ref(false);
    const orderData = ref(null);
    const productsData = ref([]);
    const isOpenModel = computed({
      get: () => props.isOpen,
      set: (val) => {
        emit("update:isOpen", val);
        if (!val) emit("close");
      }
    });
    async function fetchOrderDetails(id) {
      loading.value = true;
      orderData.value = null;
      productsData.value = [];
      try {
        const apiHeaders = { Authorization: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy" };
        const [orderRes, salesRes] = await Promise.all([
          $fetch(`/api/relations?rel=orders,clients,admins,offices&type=order,client,admin,office&linkTo=id_order&equalTo=${id}`, { headers: apiHeaders }),
          $fetch(`/api/relations?rel=sales,products&type=sale,product&linkTo=id_order_sale&equalTo=${id}`, { headers: apiHeaders })
        ]);
        if (orderRes && orderRes.status === 200 && orderRes.results && orderRes.results.length > 0) {
          orderData.value = orderRes.results[0];
        }
        if (salesRes && salesRes.status === 200 && salesRes.results) {
          productsData.value = salesRes.results;
        }
      } catch (e) {
        console.error("Error fetching order receipt data:", e);
      } finally {
        loading.value = false;
      }
    }
    watch(() => props.isOpen, (newVal) => {
      if (newVal && props.orderId) {
        fetchOrderDetails(props.orderId);
      }
    });
    function handlePrint() {
      (void 0).print();
    }
    function formatCurrency(val) {
      return new Intl.NumberFormat("es-BO", { style: "currency", currency: "BOB" }).format(Number(val));
    }
    function decodeStr(str) {
      if (!str) return "";
      return decodeURIComponent(str).replace(/\+/g, " ");
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UModal = _sfc_main$1;
      const _component_UIcon = _sfc_main$h;
      const _component_UButton = _sfc_main$c;
      _push(ssrRenderComponent(_component_UModal, mergeProps({
        modelValue: isOpenModel.value,
        "onUpdate:modelValue": ($event) => isOpenModel.value = $event,
        ui: { width: "sm:max-w-2xl" }
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="print-container bg-white text-black p-8 relative"${_scopeId}>`);
            if (loading.value) {
              _push2(`<div class="flex flex-col items-center justify-center py-12"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-loader-2",
                class: "w-10 h-10 animate-spin text-green-600 mb-2"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-500"${_scopeId}>Cargando comprobante...</p></div>`);
            } else if (orderData.value) {
              _push2(`<!--[--><div class="print-hide absolute top-4 right-4 flex gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UButton, {
                color: "primary",
                class: "bg-blue-600",
                icon: "i-lucide-printer",
                onClick: handlePrint
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Imprimir`);
                  } else {
                    return [
                      createTextVNode("Imprimir")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(ssrRenderComponent(_component_UButton, {
                color: "gray",
                variant: "ghost",
                icon: "i-lucide-x",
                onClick: ($event) => isOpenModel.value = false
              }, null, _parent2, _scopeId));
              _push2(`</div><div class="text-center mb-6"${_scopeId}><h1 class="text-3xl font-black text-blue-800 tracking-tight"${_scopeId}>Comprobante de Compra</h1><p class="text-gray-500 text-lg"${_scopeId}>Transacción #${ssrInterpolate(orderData.value.transaction_order)}</p></div><div class="grid grid-cols-2 gap-8 mb-6 text-sm"${_scopeId}><div${_scopeId}><h4 class="font-bold text-gray-700 uppercase border-b pb-1 mb-2"${_scopeId}>Datos de Sucursal</h4><p${_scopeId}><strong${_scopeId}>Sucursal:</strong> ${ssrInterpolate(decodeStr(orderData.value.title_office))}</p><p${_scopeId}><strong${_scopeId}>Dirección:</strong> ${ssrInterpolate(decodeStr(orderData.value.address_office))}</p><p${_scopeId}><strong${_scopeId}>Teléfono:</strong> ${ssrInterpolate(orderData.value.phone_office)}</p><p${_scopeId}><strong${_scopeId}>NIT:</strong> ${ssrInterpolate(orderData.value.dni_office)}</p></div><div${_scopeId}><h4 class="font-bold text-gray-700 uppercase border-b pb-1 mb-2"${_scopeId}>Datos del Cliente</h4><p${_scopeId}><strong${_scopeId}>Nombre:</strong> ${ssrInterpolate(decodeStr(orderData.value.name_client))} ${ssrInterpolate(decodeStr(orderData.value.surname_client))}</p><p${_scopeId}><strong${_scopeId}>Teléfono:</strong> ${ssrInterpolate(orderData.value.phone_client)}</p><p${_scopeId}><strong${_scopeId}>Email:</strong> ${ssrInterpolate(orderData.value.email_client || "No especificado")}</p><p${_scopeId}><strong${_scopeId}>Dirección:</strong> ${ssrInterpolate(decodeStr(orderData.value.address_client))}</p></div></div><div class="bg-gray-50 p-4 rounded-lg mb-6 text-sm flex justify-between"${_scopeId}><div${_scopeId}><strong${_scopeId}>Fecha:</strong> ${ssrInterpolate(orderData.value.date_order)}</div><div${_scopeId}><strong${_scopeId}>Método de pago:</strong> ${ssrInterpolate(orderData.value.method_order)}</div><div${_scopeId}><strong${_scopeId}>Estado:</strong><span class="${ssrRenderClass(orderData.value.status_order === "Completada" ? "text-green-600 font-bold" : "text-amber-600 font-bold")}"${_scopeId}>${ssrInterpolate(orderData.value.status_order)}</span></div></div><table class="w-full text-sm mb-6 border-collapse"${_scopeId}><thead${_scopeId}><tr class="bg-gray-100 border-b-2 border-gray-300"${_scopeId}><th class="py-2 px-3 text-left font-bold w-1/2"${_scopeId}>Producto</th><th class="py-2 px-3 text-center font-bold"${_scopeId}>Cant.</th><th class="py-2 px-3 text-right font-bold"${_scopeId}>Precio U.</th><th class="py-2 px-3 text-center font-bold"${_scopeId}>Dscto / IVA</th><th class="py-2 px-3 text-right font-bold"${_scopeId}>Subtotal</th></tr></thead><tbody${_scopeId}><!--[-->`);
              ssrRenderList(productsData.value, (prod, i) => {
                _push2(`<tr class="border-b border-gray-200"${_scopeId}><td class="py-3 px-3"${_scopeId}>${ssrInterpolate(decodeStr(prod.title_product))}</td><td class="py-3 px-3 text-center"${_scopeId}>${ssrInterpolate(prod.qty_sale)}</td><td class="py-3 px-3 text-right"${_scopeId}>${ssrInterpolate(formatCurrency(prod.price_sale))}</td><td class="py-3 px-3 text-center text-xs text-gray-500"${_scopeId}>`);
                if (parseFloat(prod.discount_sale) > 0) {
                  _push2(`<span${_scopeId}>D: ${ssrInterpolate(prod.discount_sale)}%</span>`);
                } else {
                  _push2(`<!---->`);
                }
                if (parseFloat(prod.discount_sale) > 0) {
                  _push2(`<br${_scopeId}>`);
                } else {
                  _push2(`<!---->`);
                }
                if (parseFloat(prod.tax_sale) > 0) {
                  _push2(`<span${_scopeId}>IVA: ${ssrInterpolate(prod.tax_sale)}%</span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</td><td class="py-3 px-3 text-right font-semibold"${_scopeId}>${ssrInterpolate(formatCurrency(prod.subtotal_sale))}</td></tr>`);
              });
              _push2(`<!--]-->`);
              if (productsData.value.length === 0) {
                _push2(`<tr${_scopeId}><td colspan="5" class="py-4 text-center text-gray-500"${_scopeId}>No hay productos en esta orden.</td></tr>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</tbody></table><div class="flex justify-end mt-6"${_scopeId}><div class="w-64 text-sm"${_scopeId}><div class="flex justify-between py-1"${_scopeId}><span class="text-gray-600"${_scopeId}>Subtotal:</span><span class="font-medium"${_scopeId}>${ssrInterpolate(formatCurrency(orderData.value.subtotal_order))}</span></div><div class="flex justify-between py-1 text-red-600"${_scopeId}><span${_scopeId}>Descuento total (-):</span><span class="font-medium"${_scopeId}>${ssrInterpolate(formatCurrency(orderData.value.discount_order))}</span></div><div class="flex justify-between py-1 text-gray-600 border-b border-gray-200 pb-2"${_scopeId}><span${_scopeId}>Impuestos (+):</span><span class="font-medium"${_scopeId}>${ssrInterpolate(formatCurrency(orderData.value.tax_order))}</span></div><div class="flex justify-between py-2 mt-1 text-lg font-black text-gray-900 border-t-2 border-gray-900"${_scopeId}><span${_scopeId}>TOTAL A PAGAR:</span><span${_scopeId}>${ssrInterpolate(formatCurrency(orderData.value.total_order))}</span></div></div></div><div class="mt-12 text-center text-xs text-gray-400 border-t pt-4 print-footer"${_scopeId}> Gracias por su compra. Conserve este comprobante. </div><!--]-->`);
            } else {
              _push2(`<div class="py-12 text-center"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-alert-triangle",
                class: "w-12 h-12 text-red-400 mx-auto mb-3"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-lg font-medium text-gray-700"${_scopeId}>Comprobante no encontrado</p>`);
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
              createVNode("div", { class: "print-container bg-white text-black p-8 relative" }, [
                loading.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "flex flex-col items-center justify-center py-12"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "w-10 h-10 animate-spin text-green-600 mb-2"
                  }),
                  createVNode("p", { class: "text-gray-500" }, "Cargando comprobante...")
                ])) : orderData.value ? (openBlock(), createBlock(Fragment, { key: 1 }, [
                  createVNode("div", { class: "print-hide absolute top-4 right-4 flex gap-2" }, [
                    createVNode(_component_UButton, {
                      color: "primary",
                      class: "bg-blue-600",
                      icon: "i-lucide-printer",
                      onClick: handlePrint
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Imprimir")
                      ]),
                      _: 1
                    }),
                    createVNode(_component_UButton, {
                      color: "gray",
                      variant: "ghost",
                      icon: "i-lucide-x",
                      onClick: ($event) => isOpenModel.value = false
                    }, null, 8, ["onClick"])
                  ]),
                  createVNode("div", { class: "text-center mb-6" }, [
                    createVNode("h1", { class: "text-3xl font-black text-blue-800 tracking-tight" }, "Comprobante de Compra"),
                    createVNode("p", { class: "text-gray-500 text-lg" }, "Transacción #" + toDisplayString(orderData.value.transaction_order), 1)
                  ]),
                  createVNode("div", { class: "grid grid-cols-2 gap-8 mb-6 text-sm" }, [
                    createVNode("div", null, [
                      createVNode("h4", { class: "font-bold text-gray-700 uppercase border-b pb-1 mb-2" }, "Datos de Sucursal"),
                      createVNode("p", null, [
                        createVNode("strong", null, "Sucursal:"),
                        createTextVNode(" " + toDisplayString(decodeStr(orderData.value.title_office)), 1)
                      ]),
                      createVNode("p", null, [
                        createVNode("strong", null, "Dirección:"),
                        createTextVNode(" " + toDisplayString(decodeStr(orderData.value.address_office)), 1)
                      ]),
                      createVNode("p", null, [
                        createVNode("strong", null, "Teléfono:"),
                        createTextVNode(" " + toDisplayString(orderData.value.phone_office), 1)
                      ]),
                      createVNode("p", null, [
                        createVNode("strong", null, "NIT:"),
                        createTextVNode(" " + toDisplayString(orderData.value.dni_office), 1)
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("h4", { class: "font-bold text-gray-700 uppercase border-b pb-1 mb-2" }, "Datos del Cliente"),
                      createVNode("p", null, [
                        createVNode("strong", null, "Nombre:"),
                        createTextVNode(" " + toDisplayString(decodeStr(orderData.value.name_client)) + " " + toDisplayString(decodeStr(orderData.value.surname_client)), 1)
                      ]),
                      createVNode("p", null, [
                        createVNode("strong", null, "Teléfono:"),
                        createTextVNode(" " + toDisplayString(orderData.value.phone_client), 1)
                      ]),
                      createVNode("p", null, [
                        createVNode("strong", null, "Email:"),
                        createTextVNode(" " + toDisplayString(orderData.value.email_client || "No especificado"), 1)
                      ]),
                      createVNode("p", null, [
                        createVNode("strong", null, "Dirección:"),
                        createTextVNode(" " + toDisplayString(decodeStr(orderData.value.address_client)), 1)
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "bg-gray-50 p-4 rounded-lg mb-6 text-sm flex justify-between" }, [
                    createVNode("div", null, [
                      createVNode("strong", null, "Fecha:"),
                      createTextVNode(" " + toDisplayString(orderData.value.date_order), 1)
                    ]),
                    createVNode("div", null, [
                      createVNode("strong", null, "Método de pago:"),
                      createTextVNode(" " + toDisplayString(orderData.value.method_order), 1)
                    ]),
                    createVNode("div", null, [
                      createVNode("strong", null, "Estado:"),
                      createVNode("span", {
                        class: orderData.value.status_order === "Completada" ? "text-green-600 font-bold" : "text-amber-600 font-bold"
                      }, toDisplayString(orderData.value.status_order), 3)
                    ])
                  ]),
                  createVNode("table", { class: "w-full text-sm mb-6 border-collapse" }, [
                    createVNode("thead", null, [
                      createVNode("tr", { class: "bg-gray-100 border-b-2 border-gray-300" }, [
                        createVNode("th", { class: "py-2 px-3 text-left font-bold w-1/2" }, "Producto"),
                        createVNode("th", { class: "py-2 px-3 text-center font-bold" }, "Cant."),
                        createVNode("th", { class: "py-2 px-3 text-right font-bold" }, "Precio U."),
                        createVNode("th", { class: "py-2 px-3 text-center font-bold" }, "Dscto / IVA"),
                        createVNode("th", { class: "py-2 px-3 text-right font-bold" }, "Subtotal")
                      ])
                    ]),
                    createVNode("tbody", null, [
                      (openBlock(true), createBlock(Fragment, null, renderList(productsData.value, (prod, i) => {
                        return openBlock(), createBlock("tr", {
                          key: i,
                          class: "border-b border-gray-200"
                        }, [
                          createVNode("td", { class: "py-3 px-3" }, toDisplayString(decodeStr(prod.title_product)), 1),
                          createVNode("td", { class: "py-3 px-3 text-center" }, toDisplayString(prod.qty_sale), 1),
                          createVNode("td", { class: "py-3 px-3 text-right" }, toDisplayString(formatCurrency(prod.price_sale)), 1),
                          createVNode("td", { class: "py-3 px-3 text-center text-xs text-gray-500" }, [
                            parseFloat(prod.discount_sale) > 0 ? (openBlock(), createBlock("span", { key: 0 }, "D: " + toDisplayString(prod.discount_sale) + "%", 1)) : createCommentVNode("", true),
                            parseFloat(prod.discount_sale) > 0 ? (openBlock(), createBlock("br", { key: 1 })) : createCommentVNode("", true),
                            parseFloat(prod.tax_sale) > 0 ? (openBlock(), createBlock("span", { key: 2 }, "IVA: " + toDisplayString(prod.tax_sale) + "%", 1)) : createCommentVNode("", true)
                          ]),
                          createVNode("td", { class: "py-3 px-3 text-right font-semibold" }, toDisplayString(formatCurrency(prod.subtotal_sale)), 1)
                        ]);
                      }), 128)),
                      productsData.value.length === 0 ? (openBlock(), createBlock("tr", { key: 0 }, [
                        createVNode("td", {
                          colspan: "5",
                          class: "py-4 text-center text-gray-500"
                        }, "No hay productos en esta orden.")
                      ])) : createCommentVNode("", true)
                    ])
                  ]),
                  createVNode("div", { class: "flex justify-end mt-6" }, [
                    createVNode("div", { class: "w-64 text-sm" }, [
                      createVNode("div", { class: "flex justify-between py-1" }, [
                        createVNode("span", { class: "text-gray-600" }, "Subtotal:"),
                        createVNode("span", { class: "font-medium" }, toDisplayString(formatCurrency(orderData.value.subtotal_order)), 1)
                      ]),
                      createVNode("div", { class: "flex justify-between py-1 text-red-600" }, [
                        createVNode("span", null, "Descuento total (-):"),
                        createVNode("span", { class: "font-medium" }, toDisplayString(formatCurrency(orderData.value.discount_order)), 1)
                      ]),
                      createVNode("div", { class: "flex justify-between py-1 text-gray-600 border-b border-gray-200 pb-2" }, [
                        createVNode("span", null, "Impuestos (+):"),
                        createVNode("span", { class: "font-medium" }, toDisplayString(formatCurrency(orderData.value.tax_order)), 1)
                      ]),
                      createVNode("div", { class: "flex justify-between py-2 mt-1 text-lg font-black text-gray-900 border-t-2 border-gray-900" }, [
                        createVNode("span", null, "TOTAL A PAGAR:"),
                        createVNode("span", null, toDisplayString(formatCurrency(orderData.value.total_order)), 1)
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "mt-12 text-center text-xs text-gray-400 border-t pt-4 print-footer" }, " Gracias por su compra. Conserve este comprobante. ")
                ], 64)) : (openBlock(), createBlock("div", {
                  key: 2,
                  class: "py-12 text-center"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-alert-triangle",
                    class: "w-12 h-12 text-red-400 mx-auto mb-3"
                  }),
                  createVNode("p", { class: "text-lg font-medium text-gray-700" }, "Comprobante no encontrado"),
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
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/OrderReceiptModal.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const __nuxt_component_7 = Object.assign(_sfc_main, { __name: "OrderReceiptModal" });

export { __nuxt_component_7 as _ };
//# sourceMappingURL=OrderReceiptModal-DsvU9H_7.mjs.map
