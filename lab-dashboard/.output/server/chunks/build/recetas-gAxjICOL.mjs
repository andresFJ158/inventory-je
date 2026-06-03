import { I as useAuthStore, g as _sfc_main$h, i as _sfc_main$c } from './server.mjs';
import { _ as _sfc_main$1 } from './Modal-ulV1aY0B.mjs';
import { defineComponent, ref, mergeProps, unref, withCtx, createTextVNode, createVNode, toDisplayString, withModifiers, withDirectives, vModelText, openBlock, createBlock, Fragment, renderList, vModelSelect, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { u as useNumericInput } from './useNumericInput-Bqjo07MU.mjs';
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
  __name: "recetas",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    function blockNegative(e) {
      if (e.key === "-" || e.key === "e" || e.key === "E") e.preventDefault();
    }
    function numDisplay(val) {
      if (val === "" || val === null || val === void 0) return "";
      const n = parseFloat(String(val));
      if (isNaN(n) || n < 0) return "";
      return n.toLocaleString("de-DE", { maximumFractionDigits: 3 });
    }
    function numParse(formatted) {
      const clean = formatted.replace(/\./g, "").replace(",", ".");
      const n = parseFloat(clean);
      return isNaN(n) || n < 0 ? "" : String(n);
    }
    function onNumInput(e, obj, key) {
      const input = e.target;
      let raw = input.value.replace(/[^\d,]/g, "");
      raw = raw.replace(",", ".");
      const parts = raw.split(".");
      const intStr = parts[0].replace(/^0+(?=\d)/, "") || "0";
      const intVal = parseInt(intStr, 10) || 0;
      const decStr = parts[1] !== void 0 ? parts[1].slice(0, 3) : void 0;
      const formatted = decStr !== void 0 ? intVal.toLocaleString("de-DE") + "," + decStr : intVal > 0 ? intVal.toLocaleString("de-DE") : "";
      obj[key] = numParse(formatted) || numParse(input.value);
      input.value = formatted;
    }
    const newBatchInput = useNumericInput("1", { decimals: 3, min: 0 });
    const editBatchInput = useNumericInput("1", { decimals: 3, min: 0 });
    const recipes = ref([]);
    const loading = ref(true);
    const isCreateOpen = ref(false);
    const materials = ref([]);
    const loadingMaterials = ref(false);
    const newRecipe = ref({
      name_product: "",
      unit_batch: "L",
      ingredients: []
    });
    async function fetchMaterials() {
      loadingMaterials.value = true;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({
            getLabMaterials: "ok",
            id_office: String(auth.officeId || 6)
          }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        if (data.status === 200) {
          materials.value = data.results;
        }
      } catch (error) {
        console.error("Error fetching materials:", error);
      } finally {
        loadingMaterials.value = false;
      }
    }
    async function fetchRecipes() {
      loading.value = true;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({
            getLabRecipes: "ok",
            id_office: String(auth.officeId || 6)
          }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        if (data.status === 200) {
          recipes.value = data.results.map((r) => ({
            id: r.id_recipe,
            name: r.title_product,
            batch_size: parseFloat(r.batch_size_recipe) || 1,
            unit_batch: r.unit_batch_recipe || "u",
            components: r.ingredients ? r.ingredients.map((ing) => ({
              name: ing.name_raw_material,
              qty: parseFloat(ing.qty_ingredient) || 0,
              unit: ing.unit_raw_material || "",
              unit_price: parseFloat(ing.unit_price_mp) || 0,
              subtotal: (parseFloat(ing.qty_ingredient) || 0) * (parseFloat(ing.unit_price_mp) || 0)
            })) : [],
            cost_estimated: parseFloat(r.cost_estimated) || 0,
            cost_real: parseFloat(r.cost_real) || 0,
            has_real_cost: r.has_real_cost === true || r.has_real_cost == 1
          }));
        } else {
          recipes.value = [];
        }
      } catch (error) {
        console.error("Error fetching recipes:", error);
        recipes.value = [];
      } finally {
        loading.value = false;
      }
    }
    function addIngredient() {
      newRecipe.value.ingredients.push({ id_raw: "", qty: "" });
    }
    function removeIngredient(index) {
      newRecipe.value.ingredients.splice(index, 1);
    }
    async function handleOpenCreateModal() {
      await fetchMaterials();
      newRecipe.value = {
        name_product: "",
        unit_batch: "L",
        ingredients: [{ id_raw: "", qty: "" }]
      };
      newBatchInput.setValue(1);
      isCreateOpen.value = true;
    }
    async function handleSaveRecipe() {
      if (!newRecipe.value.name_product || newBatchInput.raw.value <= 0) {
        alert("Por favor, ingresa el nombre de la fórmula y una cantidad base mayor a 0.");
        return;
      }
      const validIngredients = newRecipe.value.ingredients.filter((i) => i.id_raw && parseFloat(i.qty) > 0);
      if (validIngredients.length === 0) {
        alert("Debes agregar al menos un ingrediente válido con su cantidad.");
        return;
      }
      try {
        const body = new URLSearchParams();
        body.append("saveRecipe", "ok");
        body.append("name_product", newRecipe.value.name_product);
        body.append("batch_size", String(newBatchInput.raw.value));
        body.append("unit_batch", newRecipe.value.unit_batch);
        body.append("id_office", String(auth.officeId || 6));
        body.append("id_admin", String(auth.user?.id_admin || 1));
        body.append("ingredients", JSON.stringify(validIngredients));
        body.append("labor", JSON.stringify([]));
        const response = await $fetch(apiBase, {
          method: "POST",
          body: body.toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const resText = typeof response === "string" ? response.trim() : JSON.stringify(response);
        if (resText === "ok") {
          isCreateOpen.value = false;
          await fetchRecipes();
        } else {
          alert("Error al guardar la receta: " + (resText.startsWith("error|") ? resText.split("|")[1] : "El nombre puede ya existir en la sucursal."));
        }
      } catch (error) {
        console.error("Error saving recipe:", error);
        alert("Error de conexión con el servidor.");
      }
    }
    const isEditOpen = ref(false);
    const editingRecipe = ref(null);
    const editForm = ref({
      id_recipe: "",
      name_product: "",
      unit_batch: "L",
      ingredients: []
    });
    async function handleOpenEditModal(recipe) {
      await fetchMaterials();
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({ getRecipeDataForEdit: "ok", id_recipe: String(recipe.id) }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        editingRecipe.value = recipe;
        editForm.value = {
          id_recipe: String(recipe.id),
          name_product: data.recipe?.title_product || recipe.name,
          unit_batch: data.recipe?.unit_batch_recipe || "L",
          ingredients: (data.ingredients || []).map((ing) => ({
            id_raw: String(ing.id_raw_material_ingredient),
            qty: String(ing.qty_ingredient)
          }))
        };
        editBatchInput.setValue(parseFloat(data.recipe?.batch_size_recipe) || 1);
        if (editForm.value.ingredients.length === 0) {
          editForm.value.ingredients.push({ id_raw: "", qty: "" });
        }
        isEditOpen.value = true;
      } catch (error) {
        console.error("Error loading recipe for edit:", error);
        alert("Error al cargar los datos de la receta.");
      }
    }
    function addEditIngredient() {
      editForm.value.ingredients.push({ id_raw: "", qty: "" });
    }
    function removeEditIngredient(index) {
      editForm.value.ingredients.splice(index, 1);
    }
    async function handleUpdateRecipe() {
      if (!editForm.value.name_product || editBatchInput.raw.value <= 0) {
        alert("Por favor, ingresa el nombre de la fórmula y una cantidad base mayor a 0.");
        return;
      }
      const validIngredients = editForm.value.ingredients.filter((i) => i.id_raw && i.qty);
      if (validIngredients.length === 0) {
        alert("Debes agregar al menos un ingrediente válido con su cantidad.");
        return;
      }
      try {
        const body = new URLSearchParams();
        body.append("editRecipe", "ok");
        body.append("id_recipe", editForm.value.id_recipe);
        body.append("name_product", editForm.value.name_product);
        body.append("batch_size", String(editBatchInput.raw.value));
        body.append("unit_batch", editForm.value.unit_batch);
        body.append("ingredients", JSON.stringify(validIngredients));
        body.append("labor", JSON.stringify([]));
        const response = await $fetch(apiBase, {
          method: "POST",
          body: body.toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const resText = typeof response === "string" ? response.trim() : JSON.stringify(response);
        if (resText === "ok") {
          isEditOpen.value = false;
          await fetchRecipes();
        } else {
          alert("Error al actualizar la receta: " + (resText.startsWith("error|") ? resText.split("|")[1] : resText));
        }
      } catch (error) {
        console.error("Error updating recipe:", error);
        alert("Error de conexión con el servidor.");
      }
    }
    async function handleDeleteRecipe(recipe) {
      if (!confirm(`¿Eliminar la receta "${recipe.name}"?

Se eliminará el producto asociado. Esta acción no se puede deshacer.`)) return;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({ deleteRecipe: "ok", id_recipe: String(recipe.id) }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const resText = typeof response === "string" ? response.trim() : JSON.stringify(response);
        if (resText === "ok") {
          await fetchRecipes();
        } else {
          alert("Error al eliminar la receta. Puede tener producciones asociadas.");
        }
      } catch (error) {
        console.error("Error deleting recipe:", error);
        alert("Error de conexión con el servidor.");
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      const _component_UButton = _sfc_main$c;
      const _component_UModal = _sfc_main$1;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm"><div><h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">`);
      _push(ssrRenderComponent(_component_UIcon, {
        name: "i-lucide-scroll",
        class: "text-green-500"
      }, null, _parent));
      _push(` Recetas de Laboratorio </h1><p class="text-slate-500 dark:text-slate-400 text-sm mt-1"> Configuración técnica de componentes químicos y cálculo de costo promedio. </p></div>`);
      if (unref(auth).role !== "lab_worker") {
        _push(ssrRenderComponent(_component_UButton, {
          icon: "i-lucide-plus",
          color: "green",
          size: "md",
          class: "font-bold!",
          onClick: handleOpenCreateModal
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Crear Nueva Receta `);
            } else {
              return [
                createTextVNode(" Crear Nueva Receta ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (loading.value) {
        _push(`<div class="p-8 text-center text-slate-500 dark:text-slate-400">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-loader-2",
          class: "w-8 h-8 animate-spin mx-auto text-green-500 mb-2"
        }, null, _parent));
        _push(` Cargando recetas formuladas... </div>`);
      } else if (recipes.value.length === 0) {
        _push(`<div class="text-center p-8 text-slate-500"> No hay recetas formuladas registradas en el laboratorio. </div>`);
      } else {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-6"><!--[-->`);
        ssrRenderList(recipes.value, (recipe) => {
          _push(`<div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-xl space-y-4 shadow-sm"><div class="flex justify-between items-start gap-2"><div class="flex-1 min-w-0"><h3 class="text-base font-bold text-slate-800 dark:text-white uppercase tracking-wide truncate">${ssrInterpolate(recipe.name)}</h3><p class="text-xs text-slate-400 mt-0.5"> Lote base: <span class="font-bold text-slate-600 dark:text-slate-300">${ssrInterpolate(recipe.batch_size)} ${ssrInterpolate(recipe.unit_batch)}</span></p></div><div class="flex items-center gap-1 shrink-0">`);
          if (unref(auth).role !== "lab_worker") {
            _push(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-edit-2",
              color: "warning",
              variant: "ghost",
              size: "xs",
              title: "Editar receta",
              onClick: ($event) => handleOpenEditModal(recipe)
            }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          if (unref(auth).role === "lab_admin" || unref(auth).role === "superadmin") {
            _push(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-trash-2",
              color: "rose",
              variant: "ghost",
              size: "xs",
              title: "Eliminar receta",
              onClick: ($event) => handleDeleteRecipe(recipe)
            }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div><div class="space-y-1"><h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2"> Fórmula / Ingredientes </h4><div class="rounded-lg border border-slate-100 dark:border-slate-800/60 overflow-hidden"><div class="${ssrRenderClass([unref(auth).role !== "lab_worker" ? "grid-cols-4" : "grid-cols-2", "grid text-xs font-bold uppercase tracking-wider text-slate-400 bg-slate-50 dark:bg-slate-900/60 px-3 py-1.5"])}"><span class="col-span-2">Materia Prima</span>`);
          if (unref(auth).role !== "lab_worker") {
            _push(`<span class="text-right">Precio MP</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<span class="text-right">Cantidad</span></div><!--[-->`);
          ssrRenderList(recipe.components, (comp) => {
            _push(`<div class="${ssrRenderClass([unref(auth).role !== "lab_worker" ? "grid-cols-4" : "grid-cols-2", "grid px-3 py-2 border-t border-slate-100 dark:border-slate-800/40 hover:bg-slate-50 dark:hover:bg-slate-800/10 transition-colors"])}"><span class="col-span-2 text-slate-700 dark:text-slate-300 font-medium truncate">${ssrInterpolate(comp.name)}</span>`);
            if (unref(auth).role !== "lab_worker") {
              _push(`<span class="text-right font-mono text-slate-500 dark:text-slate-400 text-xs">`);
              if (comp.unit_price > 0) {
                _push(`<span class="text-slate-600 dark:text-slate-300">Bs. ${ssrInterpolate(comp.unit_price.toFixed(2))}/${ssrInterpolate(comp.unit)}</span>`);
              } else {
                _push(`<span class="text-amber-500 italic">Sin precio</span>`);
              }
              _push(`</span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`<span class="text-right font-mono font-bold text-slate-600 dark:text-slate-300">${ssrInterpolate(comp.qty)} <span class="text-xs font-normal text-slate-400">${ssrInterpolate(comp.unit)}</span></span></div>`);
          });
          _push(`<!--]--></div></div>`);
          if (unref(auth).role !== "lab_worker") {
            _push(`<div class="border-t border-slate-100 dark:border-slate-800/60 pt-3 space-y-1.5"><div class="flex justify-between items-center text-xs"><span class="text-slate-400 font-medium">Costo MP del lote (${ssrInterpolate(recipe.batch_size)} ${ssrInterpolate(recipe.unit_batch)}):</span><span class="font-mono font-bold text-slate-600 dark:text-slate-300"> Bs. ${ssrInterpolate(recipe.components.reduce((acc, c) => acc + c.subtotal, 0).toFixed(2))}</span></div><div class="flex justify-between items-center text-xs"><span class="text-slate-400 font-medium">Costo MP / unidad producida:</span><span class="${ssrRenderClass([recipe.cost_estimated > 0 ? "text-green-600 dark:text-green-400" : "text-amber-500", "font-mono font-bold"])}">`);
            if (recipe.cost_estimated > 0) {
              _push(`<span>Bs. ${ssrInterpolate(recipe.cost_estimated.toFixed(4))}</span>`);
            } else {
              _push(`<span class="italic text-amber-500">Sin precio de MP</span>`);
            }
            _push(`</span></div>`);
            if (recipe.has_real_cost) {
              _push(`<div class="flex justify-between items-center py-1.5 px-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg"><span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1">`);
              _push(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-check-circle",
                class: "w-3.5 h-3.5"
              }, null, _parent));
              _push(` Costo Promedio Real (QC): </span><span class="font-mono font-bold text-emerald-700 dark:text-emerald-300 text-sm"> Bs. ${ssrInterpolate(recipe.cost_real.toFixed(4))} / und </span></div>`);
            } else {
              _push(`<div class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400">`);
              _push(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-info",
                class: "w-3.5 h-3.5 shrink-0"
              }, null, _parent));
              _push(`<span>El costo real se calculará al completar la primera producción con QC aprobado.</span></div>`);
            }
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(ssrRenderComponent(_component_UModal, {
        open: isEditOpen.value,
        "onUpdate:open": ($event) => isEditOpen.value = $event
      }, {
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-full p-6 space-y-4 text-slate-900 dark:text-white"${_scopeId}><div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-amber-600 dark:text-amber-400"${_scopeId}><h3 class="text-lg font-bold tracking-wide flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-edit-2",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` Editar Fórmula: ${ssrInterpolate(editingRecipe.value?.name)}</h3>`);
            _push2(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-x",
              variant: "ghost",
              color: "neutral",
              size: "sm",
              onClick: ($event) => isEditOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(`</div><form class="space-y-4"${_scopeId}><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Nombre del Producto</label><input${ssrRenderAttr("value", editForm.value.name_product)} type="text" placeholder="Ej: Vinagre de Manzana 1L" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50"${_scopeId}></div><div class="grid grid-cols-2 gap-4"${_scopeId}><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Rendimiento Base</label><input${ssrRenderAttr("value", unref(editBatchInput).display.value)} type="text" inputmode="decimal" placeholder="1,000" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50"${_scopeId}></div><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Unidad de Medida</label><input${ssrRenderAttr("value", editForm.value.unit_batch)} type="text" placeholder="L, und, kg" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50"${_scopeId}></div></div><div class="space-y-3 pt-2"${_scopeId}><div class="flex justify-between items-center"${_scopeId}><label class="block text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider"${_scopeId}>Insumos (Fórmula)</label>`);
            _push2(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-plus",
              label: "Agregar Insumo",
              color: "warning",
              variant: "ghost",
              size: "xs",
              onClick: addEditIngredient
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="space-y-3 max-h-[40vh] overflow-y-auto pr-1"${_scopeId}><!--[-->`);
            ssrRenderList(editForm.value.ingredients, (ing, index) => {
              _push2(`<div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800"${_scopeId}><select class="flex-1 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500"${_scopeId}><option value=""${ssrIncludeBooleanAttr(Array.isArray(ing.id_raw) ? ssrLooseContain(ing.id_raw, "") : ssrLooseEqual(ing.id_raw, "")) ? " selected" : ""}${_scopeId}>Seleccione Insumo...</option><!--[-->`);
              ssrRenderList(materials.value, (m) => {
                _push2(`<option${ssrRenderAttr("value", m.id_raw_material)}${ssrIncludeBooleanAttr(Array.isArray(ing.id_raw) ? ssrLooseContain(ing.id_raw, m.id_raw_material) : ssrLooseEqual(ing.id_raw, m.id_raw_material)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(m.name_raw_material)} (${ssrInterpolate(m.unit_raw_material)}) </option>`);
              });
              _push2(`<!--]--></select><input${ssrRenderAttr("value", numDisplay(ing.qty))} type="text" inputmode="decimal" placeholder="0,000" class="w-24 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UButton, {
                icon: "i-lucide-trash",
                color: "red",
                variant: "ghost",
                size: "xs",
                onClick: ($event) => removeEditIngredient(index)
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            });
            _push2(`<!--]--></div></div><div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              label: "Cancelar",
              variant: "ghost",
              color: "neutral",
              onClick: ($event) => isEditOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UButton, {
              type: "submit",
              label: "Guardar Cambios",
              color: "warning",
              class: "font-bold!"
            }, null, _parent2, _scopeId));
            _push2(`</div></form></div>`);
          } else {
            return [
              createVNode("div", { class: "w-full p-6 space-y-4 text-slate-900 dark:text-white" }, [
                createVNode("div", { class: "flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-amber-600 dark:text-amber-400" }, [
                  createVNode("h3", { class: "text-lg font-bold tracking-wide flex items-center gap-2" }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-edit-2",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" Editar Fórmula: " + toDisplayString(editingRecipe.value?.name), 1)
                  ]),
                  createVNode(_component_UButton, {
                    icon: "i-lucide-x",
                    variant: "ghost",
                    color: "neutral",
                    size: "sm",
                    onClick: ($event) => isEditOpen.value = false
                  }, null, 8, ["onClick"])
                ]),
                createVNode("form", {
                  class: "space-y-4",
                  onSubmit: withModifiers(handleUpdateRecipe, ["prevent"])
                }, [
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Nombre del Producto"),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => editForm.value.name_product = $event,
                      type: "text",
                      placeholder: "Ej: Vinagre de Manzana 1L",
                      class: "block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, editForm.value.name_product]
                    ])
                  ]),
                  createVNode("div", { class: "grid grid-cols-2 gap-4" }, [
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Rendimiento Base"),
                      createVNode("input", {
                        value: unref(editBatchInput).display.value,
                        type: "text",
                        inputmode: "decimal",
                        placeholder: "1,000",
                        class: "block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50",
                        onInput: ($event) => unref(editBatchInput).onInput($event),
                        onKeydown: ($event) => unref(editBatchInput).onKeydown($event)
                      }, null, 40, ["value", "onInput", "onKeydown"])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Unidad de Medida"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => editForm.value.unit_batch = $event,
                        type: "text",
                        placeholder: "L, und, kg",
                        class: "block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, editForm.value.unit_batch]
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "space-y-3 pt-2" }, [
                    createVNode("div", { class: "flex justify-between items-center" }, [
                      createVNode("label", { class: "block text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider" }, "Insumos (Fórmula)"),
                      createVNode(_component_UButton, {
                        icon: "i-lucide-plus",
                        label: "Agregar Insumo",
                        color: "warning",
                        variant: "ghost",
                        size: "xs",
                        onClick: addEditIngredient
                      })
                    ]),
                    createVNode("div", { class: "space-y-3 max-h-[40vh] overflow-y-auto pr-1" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(editForm.value.ingredients, (ing, index) => {
                        return openBlock(), createBlock("div", {
                          key: index,
                          class: "flex items-center gap-3 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800"
                        }, [
                          withDirectives(createVNode("select", {
                            "onUpdate:modelValue": ($event) => ing.id_raw = $event,
                            class: "flex-1 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500"
                          }, [
                            createVNode("option", { value: "" }, "Seleccione Insumo..."),
                            (openBlock(true), createBlock(Fragment, null, renderList(materials.value, (m) => {
                              return openBlock(), createBlock("option", {
                                key: m.id_raw_material,
                                value: m.id_raw_material
                              }, toDisplayString(m.name_raw_material) + " (" + toDisplayString(m.unit_raw_material) + ") ", 9, ["value"]);
                            }), 128))
                          ], 8, ["onUpdate:modelValue"]), [
                            [vModelSelect, ing.id_raw]
                          ]),
                          createVNode("input", {
                            value: numDisplay(ing.qty),
                            type: "text",
                            inputmode: "decimal",
                            placeholder: "0,000",
                            class: "w-24 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500",
                            onInput: ($event) => onNumInput($event, ing, "qty"),
                            onKeydown: ($event) => blockNegative($event)
                          }, null, 40, ["value", "onInput", "onKeydown"]),
                          createVNode(_component_UButton, {
                            icon: "i-lucide-trash",
                            color: "red",
                            variant: "ghost",
                            size: "xs",
                            onClick: ($event) => removeEditIngredient(index)
                          }, null, 8, ["onClick"])
                        ]);
                      }), 128))
                    ])
                  ]),
                  createVNode("div", { class: "flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6" }, [
                    createVNode(_component_UButton, {
                      label: "Cancelar",
                      variant: "ghost",
                      color: "neutral",
                      onClick: ($event) => isEditOpen.value = false
                    }, null, 8, ["onClick"]),
                    createVNode(_component_UButton, {
                      type: "submit",
                      label: "Guardar Cambios",
                      color: "warning",
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
      _push(ssrRenderComponent(_component_UModal, {
        open: isCreateOpen.value,
        "onUpdate:open": ($event) => isCreateOpen.value = $event
      }, {
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-full p-6 space-y-4 text-slate-900 dark:text-white"${_scopeId}><div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-green-600 dark:text-green-400"${_scopeId}><h3 class="text-lg font-bold tracking-wide flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-plus",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` Diseñador de Fórmulas </h3>`);
            _push2(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-x",
              variant: "ghost",
              color: "neutral",
              size: "sm",
              onClick: ($event) => isCreateOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(`</div><form class="space-y-4"${_scopeId}><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Producto a Fabricar (Nombre)</label><input${ssrRenderAttr("value", newRecipe.value.name_product)} type="text" placeholder="Ej: Vinagre de Manzana 1L" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}></div><div class="grid grid-cols-2 gap-4"${_scopeId}><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Cantidad Esperada de Producción (Rendimiento)</label><input${ssrRenderAttr("value", unref(newBatchInput).display.value)} type="text" inputmode="decimal" placeholder="1,000" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}></div><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Unidad de Medida</label><input${ssrRenderAttr("value", newRecipe.value.unit_batch)} type="text" placeholder="Ej: L, und, kg" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}></div></div><div class="space-y-3 pt-2"${_scopeId}><div class="flex justify-between items-center"${_scopeId}><label class="block text-xs font-bold text-green-600 dark:text-green-400 uppercase tracking-wider"${_scopeId}>Insumos (Fórmula)</label>`);
            _push2(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-plus",
              label: "Agregar Insumo",
              color: "green",
              variant: "ghost",
              size: "xs",
              onClick: addIngredient
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="space-y-3 max-h-[40vh] overflow-y-auto pr-1"${_scopeId}><!--[-->`);
            ssrRenderList(newRecipe.value.ingredients, (ing, index) => {
              _push2(`<div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800"${_scopeId}><select class="flex-1 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500"${_scopeId}><option value=""${ssrIncludeBooleanAttr(Array.isArray(ing.id_raw) ? ssrLooseContain(ing.id_raw, "") : ssrLooseEqual(ing.id_raw, "")) ? " selected" : ""}${_scopeId}>Seleccione Insumo...</option><!--[-->`);
              ssrRenderList(materials.value, (m) => {
                _push2(`<option${ssrRenderAttr("value", m.id_raw_material)}${ssrIncludeBooleanAttr(Array.isArray(ing.id_raw) ? ssrLooseContain(ing.id_raw, m.id_raw_material) : ssrLooseEqual(ing.id_raw, m.id_raw_material)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(m.name_raw_material)} (${ssrInterpolate(m.unit_raw_material)}) </option>`);
              });
              _push2(`<!--]--></select><input${ssrRenderAttr("value", numDisplay(ing.qty))} type="text" inputmode="decimal" placeholder="0,000" class="w-24 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UButton, {
                icon: "i-lucide-trash",
                color: "red",
                variant: "ghost",
                size: "xs",
                onClick: ($event) => removeIngredient(index)
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            });
            _push2(`<!--]--></div></div><div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              label: "Cancelar",
              variant: "ghost",
              color: "neutral",
              onClick: ($event) => isCreateOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UButton, {
              type: "submit",
              label: "Guardar Receta",
              color: "green",
              class: "font-bold!"
            }, null, _parent2, _scopeId));
            _push2(`</div></form></div>`);
          } else {
            return [
              createVNode("div", { class: "w-full p-6 space-y-4 text-slate-900 dark:text-white" }, [
                createVNode("div", { class: "flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-green-600 dark:text-green-400" }, [
                  createVNode("h3", { class: "text-lg font-bold tracking-wide flex items-center gap-2" }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-plus",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" Diseñador de Fórmulas ")
                  ]),
                  createVNode(_component_UButton, {
                    icon: "i-lucide-x",
                    variant: "ghost",
                    color: "neutral",
                    size: "sm",
                    onClick: ($event) => isCreateOpen.value = false
                  }, null, 8, ["onClick"])
                ]),
                createVNode("form", {
                  class: "space-y-4",
                  onSubmit: withModifiers(handleSaveRecipe, ["prevent"])
                }, [
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Producto a Fabricar (Nombre)"),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => newRecipe.value.name_product = $event,
                      type: "text",
                      placeholder: "Ej: Vinagre de Manzana 1L",
                      class: "block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, newRecipe.value.name_product]
                    ])
                  ]),
                  createVNode("div", { class: "grid grid-cols-2 gap-4" }, [
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Cantidad Esperada de Producción (Rendimiento)"),
                      createVNode("input", {
                        value: unref(newBatchInput).display.value,
                        type: "text",
                        inputmode: "decimal",
                        placeholder: "1,000",
                        class: "block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50",
                        onInput: ($event) => unref(newBatchInput).onInput($event),
                        onKeydown: ($event) => unref(newBatchInput).onKeydown($event)
                      }, null, 40, ["value", "onInput", "onKeydown"])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Unidad de Medida"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => newRecipe.value.unit_batch = $event,
                        type: "text",
                        placeholder: "Ej: L, und, kg",
                        class: "block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, newRecipe.value.unit_batch]
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "space-y-3 pt-2" }, [
                    createVNode("div", { class: "flex justify-between items-center" }, [
                      createVNode("label", { class: "block text-xs font-bold text-green-600 dark:text-green-400 uppercase tracking-wider" }, "Insumos (Fórmula)"),
                      createVNode(_component_UButton, {
                        icon: "i-lucide-plus",
                        label: "Agregar Insumo",
                        color: "green",
                        variant: "ghost",
                        size: "xs",
                        onClick: addIngredient
                      })
                    ]),
                    createVNode("div", { class: "space-y-3 max-h-[40vh] overflow-y-auto pr-1" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(newRecipe.value.ingredients, (ing, index) => {
                        return openBlock(), createBlock("div", {
                          key: index,
                          class: "flex items-center gap-3 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800"
                        }, [
                          withDirectives(createVNode("select", {
                            "onUpdate:modelValue": ($event) => ing.id_raw = $event,
                            class: "flex-1 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500"
                          }, [
                            createVNode("option", { value: "" }, "Seleccione Insumo..."),
                            (openBlock(true), createBlock(Fragment, null, renderList(materials.value, (m) => {
                              return openBlock(), createBlock("option", {
                                key: m.id_raw_material,
                                value: m.id_raw_material
                              }, toDisplayString(m.name_raw_material) + " (" + toDisplayString(m.unit_raw_material) + ") ", 9, ["value"]);
                            }), 128))
                          ], 8, ["onUpdate:modelValue"]), [
                            [vModelSelect, ing.id_raw]
                          ]),
                          createVNode("input", {
                            value: numDisplay(ing.qty),
                            type: "text",
                            inputmode: "decimal",
                            placeholder: "0,000",
                            class: "w-24 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500",
                            onInput: ($event) => onNumInput($event, ing, "qty"),
                            onKeydown: ($event) => blockNegative($event)
                          }, null, 40, ["value", "onInput", "onKeydown"]),
                          createVNode(_component_UButton, {
                            icon: "i-lucide-trash",
                            color: "red",
                            variant: "ghost",
                            size: "xs",
                            onClick: ($event) => removeIngredient(index)
                          }, null, 8, ["onClick"])
                        ]);
                      }), 128))
                    ])
                  ]),
                  createVNode("div", { class: "flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6" }, [
                    createVNode(_component_UButton, {
                      label: "Cancelar",
                      variant: "ghost",
                      color: "neutral",
                      onClick: ($event) => isCreateOpen.value = false
                    }, null, 8, ["onClick"]),
                    createVNode(_component_UButton, {
                      type: "submit",
                      label: "Guardar Receta",
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/recetas.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=recetas-gAxjICOL.mjs.map
