import { I as useAuthStore, g as _sfc_main$h, i as _sfc_main$c } from './server.mjs';
import { _ as _sfc_main$1 } from './Modal-ulV1aY0B.mjs';
import { defineComponent, computed, ref, watch, mergeProps, unref, withCtx, createTextVNode, createVNode, withModifiers, withDirectives, openBlock, createBlock, Fragment, renderList, toDisplayString, vModelSelect, createCommentVNode, vModelRadio, vModelText, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderClass, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderAttr } from 'vue/server-renderer';
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
  __name: "produccion",
  __ssrInlineRender: true,
  setup(__props) {
    const auth = useAuthStore();
    const isAdmin = computed(() => ["superadmin", "admin", "lab_admin"].includes(auth.role || ""));
    const cleanFloat = (val) => {
      if (val === void 0 || val === null || val === "") return 0;
      return parseFloat(String(val).replace(/\./g, "").replace(/,/g, ".")) || 0;
    };
    const productions = ref([]);
    const loading = ref(true);
    const isCreateOpen = ref(false);
    const recipes = ref([]);
    const loadingRecipes = ref(false);
    const totalQtyInput = useNumericInput("", { decimals: 3, min: 0 });
    const cifInput = useNumericInput("0", { decimals: 2, min: 0 });
    const moInput = useNumericInput("0", { decimals: 2, min: 0 });
    const newProduction = ref({
      id_recipe: ""
    });
    const selectedRecipe = computed(() => {
      return recipes.value.find((r) => String(r.id) === String(newProduction.value.id_recipe));
    });
    const scaleFactor = computed(() => {
      if (!selectedRecipe.value || !totalQtyInput.raw.value) return 0;
      const base = parseFloat(selectedRecipe.value.batch_size) || 1;
      return totalQtyInput.raw.value / base;
    });
    async function fetchRecipes() {
      loadingRecipes.value = true;
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
            id_product: r.id_product_recipe,
            name: r.title_product,
            batch_size: parseFloat(r.batch_size_recipe) || 1,
            unit: r.unit_batch_recipe || "u",
            // Costo por unidad: real si existe QC, si no el estimado por ingredientes
            cost_unit: parseFloat(r.cost_real) > 0 ? parseFloat(r.cost_real) : parseFloat(r.cost_estimated) || 0,
            cost_batch: r.ingredients ? r.ingredients.reduce((acc, ing) => acc + (parseFloat(ing.qty_ingredient) || 0) * (parseFloat(ing.unit_price_mp) || 0), 0) : 0,
            has_prices: r.ingredients ? r.ingredients.some((ing) => parseFloat(ing.unit_price_mp) > 0) : false
          }));
        }
      } catch (error) {
        console.error("Error fetching recipes:", error);
      } finally {
        loadingRecipes.value = false;
      }
    }
    watch([() => newProduction.value.id_recipe, totalQtyInput.raw], () => {
      if (!selectedRecipe.value) {
        cifInput.setValue(0);
        return;
      }
      const batchSize = selectedRecipe.value.batch_size || 1;
      const costBatch = selectedRecipe.value.cost_batch || 0;
      const qty = totalQtyInput.raw.value;
      if (qty > 0 && costBatch > 0) {
        const batches = qty / batchSize;
        const totalMpCost = batches * costBatch;
        cifInput.setValue(totalMpCost * 0.1);
      } else {
        cifInput.setValue(0);
      }
    });
    watch(() => newProduction.value.id_recipe, (newVal) => {
      if (newVal) {
        const recipe = recipes.value.find((r) => String(r.id) === String(newVal));
        if (recipe) {
          totalQtyInput.setValue(recipe.batch_size);
        }
      } else {
        totalQtyInput.setValue(0);
      }
    });
    async function fetchProductions() {
      loading.value = true;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({
            getLabProductions: "ok",
            id_office: String(auth.officeId || 6)
          }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        if (data.status === 200) {
          productions.value = data.results.map((p) => ({
            id: p.id_production,
            recipe: p.name_product || "Fórmula Compuesta",
            qty: parseFloat(p.total_qty_production) || 0,
            date: p.start_date_production || p.date_created_production,
            status: p.status_production || "pendiente",
            id_recipe: p.id_recipe_production,
            batches: p.batches_production,
            id_product: p.id_product_production,
            unit: p.unit_batch_recipe || "L",
            qty_packaged: parseFloat(p.qty_packaged_production) || 0,
            qty_approved: parseFloat(p.qty_approved_production) || 0,
            qty_rejected: parseFloat(p.qty_rejected_production) || 0,
            result_qc: p.result_qc_production || "",
            notes_qc: p.notes_qc_production || ""
          }));
        } else {
          productions.value = [];
        }
      } catch (error) {
        console.error("Error fetching productions:", error);
        productions.value = [];
      } finally {
        loading.value = false;
      }
    }
    const isPkgOpen = ref(false);
    const materials = ref([]);
    const loadingMaterials = ref(false);
    const pkgRealBulkInput = useNumericInput("", { decimals: 3, min: 0 });
    const pkgVolumeInput = useNumericInput("", { decimals: 0, min: 0 });
    const pkgExtraMoInput = useNumericInput("0", { decimals: 2, min: 0 });
    const pkgExtraCifInput = useNumericInput("0", { decimals: 2, min: 0 });
    function blockNegative(e) {
      if (e.key === "-" || e.key === "e" || e.key === "E") e.preventDefault();
    }
    function onPkgMatQtyInput(e, idx) {
      const input = e.target;
      const raw = input.value.replace(/[^\d]/g, "");
      const n = parseInt(raw, 10) || 0;
      pkgForm.value.extra_mats[idx].qty = String(n);
      input.value = n > 0 ? n.toLocaleString("de-DE") : "";
    }
    const pkgForm = ref({
      id_production: "",
      id_recipe: "",
      batches: "",
      id_product: "",
      recipe_name: "",
      total_qty: 0,
      bulk_unit: "L",
      yield_type: "same",
      envase_type: "botellas",
      unit: "ml",
      final_name: "",
      extra_mats: []
    });
    async function fetchMaterials() {
      loadingMaterials.value = true;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({
            getLabMaterials: "ok",
            id_office: String(auth.officeId || 6),
            is_insumo: "1"
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        if (data.status === 200) {
          materials.value = data.results;
        }
      } catch (error) {
        console.error("Error fetching packaging materials:", error);
      } finally {
        loadingMaterials.value = false;
      }
    }
    const calculatedEnvases = computed(() => {
      const qty = pkgForm.value.yield_type === "diff" ? pkgRealBulkInput.raw.value : cleanFloat(pkgForm.value.total_qty);
      const vol = pkgVolumeInput.raw.value;
      if (vol <= 0) return 0;
      let volInBase = vol;
      const baseUnit = pkgForm.value.bulk_unit;
      const envUnit = pkgForm.value.unit;
      if (baseUnit === "L" && envUnit === "ml") {
        volInBase = vol / 1e3;
      } else if (baseUnit === "kg" && envUnit === "g") {
        volInBase = vol / 1e3;
      } else if (baseUnit === "ml" && envUnit === "L") {
        volInBase = vol * 1e3;
      } else if (baseUnit === "g" && envUnit === "kg") {
        volInBase = vol * 1e3;
      }
      return Math.floor(qty / volInBase);
    });
    watch([pkgVolumeInput.raw, () => pkgForm.value.unit, () => pkgForm.value.recipe_name], () => {
      if (pkgVolumeInput.raw.value > 0 && pkgForm.value.recipe_name) {
        const base = pkgForm.value.recipe_name.replace(/a granel/ig, "").trim();
        pkgForm.value.final_name = `${base} ${pkgVolumeInput.raw.value}${pkgForm.value.unit}`;
      }
    });
    watch(() => pkgForm.value.bulk_unit, (newUnit) => {
      pkgForm.value.unit = newUnit === "L" ? "ml" : newUnit === "kg" ? "g" : "und";
    });
    async function openPkgModal(prod) {
      await fetchMaterials();
      pkgForm.value = {
        id_production: String(prod.id),
        id_recipe: String(prod.id_recipe),
        batches: String(prod.batches),
        id_product: String(prod.id_product),
        recipe_name: prod.recipe,
        total_qty: prod.qty,
        bulk_unit: prod.unit || "L",
        yield_type: "same",
        envase_type: "botellas",
        unit: prod.unit === "L" ? "ml" : prod.unit === "kg" ? "g" : "und",
        final_name: "",
        extra_mats: []
      };
      pkgRealBulkInput.reset();
      pkgVolumeInput.reset();
      pkgExtraMoInput.setValue(0);
      pkgExtraCifInput.setValue(0);
      isPkgOpen.value = true;
    }
    function addPkgMaterial() {
      pkgForm.value.extra_mats.push({
        id_raw: "",
        qty: String(calculatedEnvases.value || 0)
      });
    }
    function removePkgMaterial(index) {
      pkgForm.value.extra_mats.splice(index, 1);
    }
    async function submitPackaging() {
      const final_name = pkgForm.value.final_name.trim();
      const final_qty = calculatedEnvases.value;
      if (!final_name || final_qty <= 0) {
        alert("Ingresa el volumen por envase (mayor a 0) y el nombre del producto final.");
        return;
      }
      const extra_mats = pkgForm.value.extra_mats.filter((m) => m.id_raw && parseInt(m.qty) > 0).map((m) => ({ id_raw: m.id_raw, qty: String(parseInt(m.qty)) }));
      try {
        const isYieldDiff = pkgForm.value.yield_type === "diff";
        const real_bulk_qty = isYieldDiff ? pkgRealBulkInput.raw.value : null;
        const payload = new URLSearchParams();
        payload.append("completeProduction", "ok");
        payload.append("id_production", pkgForm.value.id_production);
        payload.append("id_recipe", pkgForm.value.id_recipe);
        payload.append("batches", pkgForm.value.batches);
        payload.append("id_product", pkgForm.value.id_product);
        payload.append("extra_mats", JSON.stringify(extra_mats));
        payload.append("extra_mo", String(pkgExtraMoInput.raw.value));
        payload.append("extra_cif", String(pkgExtraCifInput.raw.value));
        payload.append("pkg_final_qty", String(final_qty));
        payload.append("pkg_final_name", final_name);
        payload.append("pkg_envase_type", pkgForm.value.envase_type);
        payload.append("id_office", String(auth.officeId || 6));
        payload.append("real_bulk_qty", real_bulk_qty !== null ? String(real_bulk_qty) : "");
        payload.append("original_bulk_qty", String(cleanFloat(pkgForm.value.total_qty)));
        const response = await $fetch(apiBase, {
          method: "POST",
          body: payload.toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const resText = typeof response === "string" ? response.trim() : JSON.stringify(response);
        if (resText === "ok") {
          isPkgOpen.value = false;
          await fetchProductions();
        } else if (resText.includes("stock_insuficiente")) {
          const parts = resText.split("|");
          const itemName = parts[1] || "Materia Prima";
          alert(`Stock Insuficiente: No hay suficiente inventario de envases/materiales: ${itemName}`);
        } else {
          alert("Error al completar producción: " + resText);
        }
      } catch (error) {
        console.error("Error completing production:", error);
        alert("Error de red al completar producción: " + (error.message || error));
      }
    }
    const isDetailsOpen = ref(false);
    const detailsLoading = ref(false);
    const detailsData = ref(null);
    async function openDetailsModal(prodId) {
      detailsLoading.value = true;
      isDetailsOpen.value = true;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({
            getProductionDetails: "ok",
            id_production: String(prodId)
          }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const data = typeof response === "string" ? JSON.parse(response) : response;
        detailsData.value = data;
      } catch (error) {
        console.error("Error fetching production details:", error);
        detailsData.value = null;
      } finally {
        detailsLoading.value = false;
      }
    }
    async function handleCancelProduction(prod) {
      if (!confirm(`¿Cancelar la orden de producción #${prod.id} de "${prod.recipe}"?

Solo se pueden cancelar producciones en estado Pendiente.`)) return;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({ cancelProduction: "ok", id_production: String(prod.id) }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const resText = typeof response === "string" ? response.trim() : JSON.stringify(response);
        if (resText === "ok") {
          await fetchProductions();
        } else {
          alert("No se puede cancelar: " + (resText.startsWith("error|") ? resText.split("|")[1] : resText));
        }
      } catch (error) {
        console.error("Error cancelling production:", error);
        alert("Error de conexión con el servidor.");
      }
    }
    async function handleStartProduction(prodId) {
      if (!confirm("¿Desea iniciar la fabricación de este lote? Se descontarán las materias primas del inventario.")) return;
      try {
        const response = await $fetch(apiBase, {
          method: "POST",
          body: new URLSearchParams({
            startProduction: "ok",
            id_production: String(prodId)
          }).toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const resText = typeof response === "string" ? response.trim() : JSON.stringify(response);
        if (resText === "ok") {
          await fetchProductions();
        } else if (resText.startsWith("stock_insuficiente")) {
          const parts = resText.split("|");
          alert(`Stock insuficiente: No hay suficiente stock de "${parts[1] || "materia prima"}" para iniciar esta producción.`);
        } else {
          alert("Error al iniciar producción: " + resText);
        }
      } catch (error) {
        console.error("Error starting production:", error);
        alert("Error al conectar con el servidor.");
      }
    }
    async function handleOpenCreateModal() {
      await fetchRecipes();
      newProduction.value = { id_recipe: "" };
      totalQtyInput.reset();
      cifInput.setValue(0);
      moInput.setValue(0);
      isCreateOpen.value = true;
    }
    async function handleSaveProduction() {
      if (!newProduction.value.id_recipe || totalQtyInput.raw.value <= 0) {
        alert("Selecciona una receta y define la cantidad a producir (mayor a 0).");
        return;
      }
      try {
        const body = new URLSearchParams();
        body.append("saveProduction", "ok");
        body.append("id_recipe", newProduction.value.id_recipe);
        body.append("id_product", String(selectedRecipe.value?.id_product || ""));
        body.append("batches", String(scaleFactor.value));
        body.append("total_qty", String(totalQtyInput.raw.value));
        body.append("cif", String(cifInput.raw.value));
        body.append("mo", String(moInput.raw.value));
        body.append("id_office", String(auth.officeId || 6));
        body.append("id_admin", String(auth.user?.id_admin || 1));
        const response = await $fetch(apiBase, {
          method: "POST",
          body: body.toString(),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });
        const resText = typeof response === "string" ? response.trim() : JSON.stringify(response);
        if (resText === "ok") {
          isCreateOpen.value = false;
          await fetchProductions();
        } else if (resText.startsWith("stock_insuficiente")) {
          const parts = resText.split("|");
          alert(`Stock insuficiente: No hay suficiente stock de "${parts[1] || "materia prima"}" para esta producción.`);
        } else {
          alert("Error al iniciar producción: " + resText);
        }
      } catch (error) {
        console.error("Error creating production:", error);
        alert("Error al conectar con el servidor.");
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UIcon = _sfc_main$h;
      const _component_UButton = _sfc_main$c;
      const _component_UModal = _sfc_main$1;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm"><div><h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">`);
      _push(ssrRenderComponent(_component_UIcon, {
        name: "i-lucide-cog",
        class: "text-green-500"
      }, null, _parent));
      _push(` Producción de Laboratorio </h1><p class="text-slate-500 dark:text-slate-400 text-sm mt-1"> Monitoreo y ejecución de producción de compuestos del laboratorio. </p></div>`);
      if (unref(auth).role !== "lab_calidad") {
        _push(ssrRenderComponent(_component_UButton, {
          icon: "i-lucide-plus",
          color: "green",
          size: "md",
          class: "font-bold!",
          onClick: handleOpenCreateModal
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Nueva Producción `);
            } else {
              return [
                createTextVNode(" Nueva Producción ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="bg-white dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm"><div class="p-5 border-b border-slate-200 dark:border-slate-800/80"><h3 class="font-bold text-slate-800 dark:text-white tracking-wide"> Órdenes de Producción y Estado </h3></div><div class="overflow-x-auto">`);
      if (loading.value) {
        _push(`<div class="p-8 text-center text-slate-500 dark:text-slate-400">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-loader-2",
          class: "w-8 h-8 animate-spin mx-auto text-green-500 mb-2"
        }, null, _parent));
        _push(` Cargando producciones desde la base de datos... </div>`);
      } else if (productions.value.length === 0) {
        _push(`<div class="text-center p-8 text-slate-500"> No hay órdenes de producción en curso. </div>`);
      } else {
        _push(`<table class="w-full text-left text-sm text-slate-600 dark:text-slate-300"><thead class="bg-slate-50 dark:bg-slate-900/60 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800/80"><tr><th class="px-6 py-4">ID Producción</th><th class="px-6 py-4">Producto</th><th class="px-6 py-4">Cantidad</th><th class="px-6 py-4">Fecha</th><th class="px-6 py-4">Estado</th>`);
        if (unref(auth).role !== "lab_calidad") {
          _push(`<th class="px-6 py-4 text-center">Acciones</th>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</tr></thead><tbody class="divide-y divide-slate-200 dark:divide-slate-800/60"><!--[-->`);
        ssrRenderList(productions.value, (prod) => {
          _push(`<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-all duration-150"><td class="px-6 py-4 font-mono font-bold text-slate-600 dark:text-slate-300">#${ssrInterpolate(prod.id)}</td><td class="px-6 py-4 font-bold text-slate-800 dark:text-white uppercase">${ssrInterpolate(prod.recipe)}</td><td class="px-6 py-4 font-mono text-xs"><div class="font-bold text-sm text-slate-700 dark:text-slate-200">${ssrInterpolate(prod.qty)} <span class="text-xs text-slate-500 font-normal">${ssrInterpolate(prod.unit)}</span></div>`);
          if (prod.qty_packaged > 0) {
            _push(`<div class="mt-1 space-y-0.5 border-t border-slate-100 dark:border-slate-800/40 pt-1 text-slate-400"><div>Envasado: <strong class="text-slate-650 dark:text-slate-350">${ssrInterpolate(prod.qty_packaged)} und</strong></div>`);
            if (prod.qty_approved > 0) {
              _push(`<div class="text-emerald-600 dark:text-emerald-400 font-medium"> Aprobadas: <strong>${ssrInterpolate(prod.qty_approved)} und</strong></div>`);
            } else {
              _push(`<!---->`);
            }
            if (prod.qty_rejected > 0) {
              _push(`<div class="text-rose-500 dark:text-rose-400 font-semibold flex items-center gap-0.5">`);
              _push(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-alert-circle",
                class: "w-3 h-3"
              }, null, _parent));
              _push(` Merma (QC): <strong>${ssrInterpolate(prod.qty_rejected)} und</strong></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</td><td class="px-6 py-4 text-slate-500 dark:text-slate-400">${ssrInterpolate(prod.date)}</td><td class="px-6 py-4">`);
          if (prod.status === "completado") {
            _push(`<span class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 border border-emerald-500/20">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-check-circle",
              class: "w-3.5 h-3.5"
            }, null, _parent));
            _push(` Completado (Listo en Almacén) </span>`);
          } else if (prod.status === "pendiente_qc") {
            _push(`<span class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-shield-alert",
              class: "w-3.5 h-3.5"
            }, null, _parent));
            _push(` Envasado (En Control Calidad) </span>`);
          } else if (prod.status === "rechazado") {
            _push(`<span class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-x-circle",
              class: "w-3.5 h-3.5"
            }, null, _parent));
            _push(` Rechazado por QC (Merma) </span>`);
          } else if (prod.status === "en_proceso" || prod.status === "proceso") {
            _push(`<span class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-blue-500/10 text-blue-600 dark:text-blue-300 border border-blue-500/20 animate-pulse">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-loader-2",
              class: "w-3.5 h-3.5 animate-spin"
            }, null, _parent));
            _push(` Fabricación en Proceso </span>`);
          } else {
            _push(`<span class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1 bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/20">`);
            _push(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-hourglass",
              class: "w-3.5 h-3.5"
            }, null, _parent));
            _push(` Pendiente de Envasar </span>`);
          }
          _push(`</td>`);
          if (unref(auth).role !== "lab_calidad") {
            _push(`<td class="px-6 py-4 text-center flex justify-center items-center gap-2 flex-wrap">`);
            if (prod.status === "pendiente") {
              _push(`<!--[-->`);
              _push(ssrRenderComponent(_component_UButton, {
                label: "Iniciar Fabricación",
                color: "primary",
                size: "xs",
                class: "font-bold!",
                onClick: ($event) => handleStartProduction(prod.id)
              }, null, _parent));
              if (isAdmin.value) {
                _push(ssrRenderComponent(_component_UButton, {
                  icon: "i-lucide-x-circle",
                  color: "rose",
                  size: "xs",
                  variant: "ghost",
                  title: "Cancelar orden",
                  onClick: ($event) => handleCancelProduction(prod)
                }, null, _parent));
              } else {
                _push(`<!---->`);
              }
              _push(`<!--]-->`);
            } else if (prod.status === "proceso" || prod.status === "en_proceso") {
              _push(ssrRenderComponent(_component_UButton, {
                label: "Producción Finalizada",
                color: "success",
                size: "xs",
                class: "font-bold!",
                onClick: ($event) => openPkgModal(prod)
              }, null, _parent));
            } else {
              _push(`<span class="${ssrRenderClass([prod.status === "pendiente_qc" ? "text-indigo-500" : prod.status === "rechazado" ? "text-rose-500" : "text-slate-400 dark:text-slate-500", "text-xs font-bold uppercase"])}">${ssrInterpolate(prod.status === "pendiente_qc" ? "En QC" : prod.status === "rechazado" ? "Rechazado" : "Listo")}</span>`);
            }
            _push(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-eye",
              color: "blue",
              size: "xs",
              variant: "ghost",
              class: "font-bold!",
              onClick: ($event) => openDetailsModal(prod.id),
              title: "Ver Historial y Detalles"
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
        open: isCreateOpen.value,
        "onUpdate:open": ($event) => isCreateOpen.value = $event
      }, {
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-full max-w-xl md:max-w-2xl p-6 space-y-4 text-slate-900 dark:text-white bg-white dark:bg-slate-950 border border-slate-205 dark:border-slate-800 rounded-xl shadow-2xl"${_scopeId}><div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-green-600 dark:text-green-400"${_scopeId}><h3 class="text-lg font-bold tracking-wide flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-plus",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` Nueva Orden de Producción </h3>`);
            _push2(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-x",
              variant: "ghost",
              color: "neutral",
              size: "sm",
              onClick: ($event) => isCreateOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(`</div><form class="space-y-4"${_scopeId}><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Producto a Producir</label><select class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}><option value=""${ssrIncludeBooleanAttr(Array.isArray(newProduction.value.id_recipe) ? ssrLooseContain(newProduction.value.id_recipe, "") : ssrLooseEqual(newProduction.value.id_recipe, "")) ? " selected" : ""}${_scopeId}>Seleccione...</option><!--[-->`);
            ssrRenderList(recipes.value, (r) => {
              _push2(`<option${ssrRenderAttr("value", r.id)}${ssrIncludeBooleanAttr(Array.isArray(newProduction.value.id_recipe) ? ssrLooseContain(newProduction.value.id_recipe, r.id) : ssrLooseEqual(newProduction.value.id_recipe, r.id)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(r.name)}</option>`);
            });
            _push2(`<!--]--></select></div><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Cantidad Total a Producir (A Granel)</label><div class="relative rounded-lg shadow-sm"${_scopeId}><input${ssrRenderAttr("value", unref(totalQtyInput).display.value)} type="text" inputmode="decimal"${ssrIncludeBooleanAttr(!newProduction.value.id_recipe) ? " disabled" : ""}${ssrRenderAttr("placeholder", newProduction.value.id_recipe ? "0,000" : "Elige una receta primero")} class="block w-full py-2.5 px-3 pr-12 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-50"${_scopeId}><div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500 text-sm font-bold"${_scopeId}>${ssrInterpolate(selectedRecipe.value?.unit || "--")}</div></div>`);
            if (scaleFactor.value > 0) {
              _push2(`<p class="mt-2 text-xs text-green-600 dark:text-green-400 font-semibold"${_scopeId}> Factor de Escala: ${ssrInterpolate(scaleFactor.value.toFixed(3))}x — Los ingredientes se multiplicarán adecuadamente. </p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
            if (isAdmin.value && selectedRecipe.value && selectedRecipe.value.cost_batch > 0 && scaleFactor.value > 0) {
              _push2(`<div class="flex items-center justify-between px-3 py-2 bg-blue-500/10 border border-blue-500/20 rounded-lg text-xs"${_scopeId}><span class="text-blue-700 dark:text-blue-300 font-medium"${_scopeId}>Costo MP estimado del lote:</span><span class="font-mono font-bold text-blue-700 dark:text-blue-300"${_scopeId}> Bs. ${ssrInterpolate((scaleFactor.value * selectedRecipe.value.cost_batch).toFixed(2))}</span></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (unref(auth).role === "lab_admin" || unref(auth).role === "admin" || unref(auth).role === "superadmin") {
              _push2(`<div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-settings-2",
                class: "w-3.5 h-3.5"
              }, null, _parent2, _scopeId));
              _push2(` CIF — Costo Indirecto de Fabricación (Bs) <span class="text-[10px] font-normal text-slate-400 ml-auto"${_scopeId}>Calculado automáticamente (editable)</span></label><div class="relative"${_scopeId}><span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none"${_scopeId}>Bs.</span><input${ssrRenderAttr("value", unref(cifInput).display.value)} type="text" inputmode="decimal" placeholder="0,00" class="block w-full py-2.5 pl-10 pr-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}></div><p class="mt-1 text-[10px] text-slate-400"${_scopeId}>≈ 10% del costo total de materias primas del lote.</p></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (unref(auth).role === "lab_admin" || unref(auth).role === "admin" || unref(auth).role === "superadmin") {
              _push2(`<div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-user-round",
                class: "w-3.5 h-3.5"
              }, null, _parent2, _scopeId));
              _push2(` Mano de Obra Estimada (Bs) <span class="ml-auto text-[10px] font-normal text-slate-400"${_scopeId}>Opcional</span></label><div class="relative"${_scopeId}><span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none"${_scopeId}>Bs.</span><input${ssrRenderAttr("value", unref(moInput).display.value)} type="text" inputmode="decimal" placeholder="0,00 (opcional)" class="block w-full py-2.5 pl-10 pr-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              label: "Cancelar",
              variant: "ghost",
              color: "neutral",
              onClick: ($event) => isCreateOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UButton, {
              type: "submit",
              label: "Programar Producción",
              color: "green",
              class: "font-bold!"
            }, null, _parent2, _scopeId));
            _push2(`</div></form></div>`);
          } else {
            return [
              createVNode("div", { class: "w-full max-w-xl md:max-w-2xl p-6 space-y-4 text-slate-900 dark:text-white bg-white dark:bg-slate-950 border border-slate-205 dark:border-slate-800 rounded-xl shadow-2xl" }, [
                createVNode("div", { class: "flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-green-600 dark:text-green-400" }, [
                  createVNode("h3", { class: "text-lg font-bold tracking-wide flex items-center gap-2" }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-plus",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" Nueva Orden de Producción ")
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
                  onSubmit: withModifiers(handleSaveProduction, ["prevent"])
                }, [
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Producto a Producir"),
                    withDirectives(createVNode("select", {
                      "onUpdate:modelValue": ($event) => newProduction.value.id_recipe = $event,
                      class: "block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                    }, [
                      createVNode("option", { value: "" }, "Seleccione..."),
                      (openBlock(true), createBlock(Fragment, null, renderList(recipes.value, (r) => {
                        return openBlock(), createBlock("option", {
                          key: r.id,
                          value: r.id
                        }, toDisplayString(r.name), 9, ["value"]);
                      }), 128))
                    ], 8, ["onUpdate:modelValue"]), [
                      [vModelSelect, newProduction.value.id_recipe]
                    ])
                  ]),
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Cantidad Total a Producir (A Granel)"),
                    createVNode("div", { class: "relative rounded-lg shadow-sm" }, [
                      createVNode("input", {
                        value: unref(totalQtyInput).display.value,
                        type: "text",
                        inputmode: "decimal",
                        disabled: !newProduction.value.id_recipe,
                        placeholder: newProduction.value.id_recipe ? "0,000" : "Elige una receta primero",
                        class: "block w-full py-2.5 px-3 pr-12 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-50",
                        onInput: ($event) => unref(totalQtyInput).onInput($event),
                        onKeydown: ($event) => unref(totalQtyInput).onKeydown($event)
                      }, null, 40, ["value", "disabled", "placeholder", "onInput", "onKeydown"]),
                      createVNode("div", { class: "absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500 text-sm font-bold" }, toDisplayString(selectedRecipe.value?.unit || "--"), 1)
                    ]),
                    scaleFactor.value > 0 ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "mt-2 text-xs text-green-600 dark:text-green-400 font-semibold"
                    }, " Factor de Escala: " + toDisplayString(scaleFactor.value.toFixed(3)) + "x — Los ingredientes se multiplicarán adecuadamente. ", 1)) : createCommentVNode("", true)
                  ]),
                  isAdmin.value && selectedRecipe.value && selectedRecipe.value.cost_batch > 0 && scaleFactor.value > 0 ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "flex items-center justify-between px-3 py-2 bg-blue-500/10 border border-blue-500/20 rounded-lg text-xs"
                  }, [
                    createVNode("span", { class: "text-blue-700 dark:text-blue-300 font-medium" }, "Costo MP estimado del lote:"),
                    createVNode("span", { class: "font-mono font-bold text-blue-700 dark:text-blue-300" }, " Bs. " + toDisplayString((scaleFactor.value * selectedRecipe.value.cost_batch).toFixed(2)), 1)
                  ])) : createCommentVNode("", true),
                  unref(auth).role === "lab_admin" || unref(auth).role === "admin" || unref(auth).role === "superadmin" ? (openBlock(), createBlock("div", { key: 1 }, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5" }, [
                      createVNode(_component_UIcon, {
                        name: "i-lucide-settings-2",
                        class: "w-3.5 h-3.5"
                      }),
                      createTextVNode(" CIF — Costo Indirecto de Fabricación (Bs) "),
                      createVNode("span", { class: "text-[10px] font-normal text-slate-400 ml-auto" }, "Calculado automáticamente (editable)")
                    ]),
                    createVNode("div", { class: "relative" }, [
                      createVNode("span", { class: "absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none" }, "Bs."),
                      createVNode("input", {
                        value: unref(cifInput).display.value,
                        type: "text",
                        inputmode: "decimal",
                        placeholder: "0,00",
                        class: "block w-full py-2.5 pl-10 pr-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50",
                        onInput: ($event) => unref(cifInput).onInput($event),
                        onKeydown: ($event) => unref(cifInput).onKeydown($event)
                      }, null, 40, ["value", "onInput", "onKeydown"])
                    ]),
                    createVNode("p", { class: "mt-1 text-[10px] text-slate-400" }, "≈ 10% del costo total de materias primas del lote.")
                  ])) : createCommentVNode("", true),
                  unref(auth).role === "lab_admin" || unref(auth).role === "admin" || unref(auth).role === "superadmin" ? (openBlock(), createBlock("div", { key: 2 }, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5" }, [
                      createVNode(_component_UIcon, {
                        name: "i-lucide-user-round",
                        class: "w-3.5 h-3.5"
                      }),
                      createTextVNode(" Mano de Obra Estimada (Bs) "),
                      createVNode("span", { class: "ml-auto text-[10px] font-normal text-slate-400" }, "Opcional")
                    ]),
                    createVNode("div", { class: "relative" }, [
                      createVNode("span", { class: "absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none" }, "Bs."),
                      createVNode("input", {
                        value: unref(moInput).display.value,
                        type: "text",
                        inputmode: "decimal",
                        placeholder: "0,00 (opcional)",
                        class: "block w-full py-2.5 pl-10 pr-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50",
                        onInput: ($event) => unref(moInput).onInput($event),
                        onKeydown: ($event) => unref(moInput).onKeydown($event)
                      }, null, 40, ["value", "onInput", "onKeydown"])
                    ])
                  ])) : createCommentVNode("", true),
                  createVNode("div", { class: "flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6" }, [
                    createVNode(_component_UButton, {
                      label: "Cancelar",
                      variant: "ghost",
                      color: "neutral",
                      onClick: ($event) => isCreateOpen.value = false
                    }, null, 8, ["onClick"]),
                    createVNode(_component_UButton, {
                      type: "submit",
                      label: "Programar Producción",
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
      _push(ssrRenderComponent(_component_UModal, {
        open: isPkgOpen.value,
        "onUpdate:open": ($event) => isPkgOpen.value = $event
      }, {
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-full max-w-2xl lg:max-w-3xl p-6 bg-white dark:bg-slate-900 text-slate-900 dark:text-white rounded-xl shadow-2xl space-y-5 border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto"${_scopeId}><div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-green-600 dark:text-green-400"${_scopeId}><h3 class="text-lg font-bold tracking-wide flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-boxes",
              class: "w-6 h-6 animate-pulse"
            }, null, _parent2, _scopeId));
            _push2(` Fase de Envasado y Finalización </h3>`);
            _push2(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-x",
              variant: "ghost",
              color: "neutral",
              size: "sm",
              onClick: ($event) => isPkgOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-200 dark:border-slate-850"${_scopeId}><div${_scopeId}><p class="text-xs text-slate-400 uppercase font-bold"${_scopeId}>Total Producido (A Granel)</p><p class="text-lg font-black text-green-600 dark:text-green-400 mt-1"${_scopeId}>${ssrInterpolate(pkgForm.value.total_qty)} ${ssrInterpolate(pkgForm.value.bulk_unit)}</p></div><div class="text-right"${_scopeId}><p class="text-xs text-slate-400 uppercase font-bold"${_scopeId}>Envases Calculados</p><p class="text-lg font-black text-blue-600 dark:text-blue-400 mt-1"${_scopeId}>${ssrInterpolate(calculatedEnvases.value)} <span class="text-xs font-normal text-slate-500"${_scopeId}>Unidades</span></p></div></div><div class="border border-slate-200 dark:border-slate-800 rounded-xl p-4 bg-slate-50/50 dark:bg-slate-950/20 space-y-3"${_scopeId}><h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-scale",
              class: "w-4 h-4 text-green-500"
            }, null, _parent2, _scopeId));
            _push2(` Resultado del Proceso de Elaboración </h4><p class="text-xs text-slate-400"${_scopeId}>Rendimiento esperado: <strong class="text-green-600 dark:text-green-400"${_scopeId}>${ssrInterpolate(pkgForm.value.total_qty)} ${ssrInterpolate(pkgForm.value.bulk_unit)}</strong></p><div class="flex flex-col sm:flex-row sm:items-center gap-4 text-sm"${_scopeId}><label class="flex items-center gap-2 cursor-pointer font-medium"${_scopeId}><input type="radio"${ssrIncludeBooleanAttr(ssrLooseEqual(pkgForm.value.yield_type, "same")) ? " checked" : ""} value="same" class="accent-green-600"${_scopeId}> Rendimiento óptimo (Obtenido exactamente lo esperado) </label><label class="flex items-center gap-2 cursor-pointer font-medium"${_scopeId}><input type="radio"${ssrIncludeBooleanAttr(ssrLooseEqual(pkgForm.value.yield_type, "diff")) ? " checked" : ""} value="diff" class="accent-green-600"${_scopeId}> Rendimiento variable (Obtenido cantidad diferente) </label></div>`);
            if (pkgForm.value.yield_type === "diff") {
              _push2(`<div class="pt-2 border-t border-slate-200 dark:border-slate-800"${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Cantidad Real Obtenida</label><div class="relative rounded-lg shadow-sm w-48"${_scopeId}><input${ssrRenderAttr("value", unref(pkgRealBulkInput).display.value)} type="text" inputmode="decimal" placeholder="0,000" class="block w-full py-2 px-3 pr-10 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}><div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-xs font-bold"${_scopeId}>${ssrInterpolate(pkgForm.value.bulk_unit)}</div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="grid grid-cols-1 md:grid-cols-3 gap-4"${_scopeId}><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Tipo de Empaque</label><select class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}><option value="botellas"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.envase_type) ? ssrLooseContain(pkgForm.value.envase_type, "botellas") : ssrLooseEqual(pkgForm.value.envase_type, "botellas")) ? " selected" : ""}${_scopeId}>Botellas</option><option value="frascos"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.envase_type) ? ssrLooseContain(pkgForm.value.envase_type, "frascos") : ssrLooseEqual(pkgForm.value.envase_type, "frascos")) ? " selected" : ""}${_scopeId}>Frascos</option><option value="bolsas"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.envase_type) ? ssrLooseContain(pkgForm.value.envase_type, "bolsas") : ssrLooseEqual(pkgForm.value.envase_type, "bolsas")) ? " selected" : ""}${_scopeId}>Bolsas</option><option value="cajas"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.envase_type) ? ssrLooseContain(pkgForm.value.envase_type, "cajas") : ssrLooseEqual(pkgForm.value.envase_type, "cajas")) ? " selected" : ""}${_scopeId}>Cajas</option><option value="galones"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.envase_type) ? ssrLooseContain(pkgForm.value.envase_type, "galones") : ssrLooseEqual(pkgForm.value.envase_type, "galones")) ? " selected" : ""}${_scopeId}>Galones</option><option value="unidades"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.envase_type) ? ssrLooseContain(pkgForm.value.envase_type, "unidades") : ssrLooseEqual(pkgForm.value.envase_type, "unidades")) ? " selected" : ""}${_scopeId}>Unidades Sueltas</option></select></div><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Capacidad del Envase</label><div class="flex"${_scopeId}><input${ssrRenderAttr("value", unref(pkgVolumeInput).display.value)} type="text" inputmode="decimal" placeholder="500" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-l-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}><select class="py-2.5 px-2 bg-slate-100 dark:bg-slate-900 border-y border-r border-slate-200 dark:border-slate-800 rounded-r-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 w-24"${_scopeId}>`);
            if (pkgForm.value.bulk_unit === "L") {
              _push2(`<option value="ml"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.unit) ? ssrLooseContain(pkgForm.value.unit, "ml") : ssrLooseEqual(pkgForm.value.unit, "ml")) ? " selected" : ""}${_scopeId}>ml</option>`);
            } else {
              _push2(`<!---->`);
            }
            if (pkgForm.value.bulk_unit === "L") {
              _push2(`<option value="L"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.unit) ? ssrLooseContain(pkgForm.value.unit, "L") : ssrLooseEqual(pkgForm.value.unit, "L")) ? " selected" : ""}${_scopeId}>L</option>`);
            } else {
              _push2(`<!---->`);
            }
            if (pkgForm.value.bulk_unit === "kg") {
              _push2(`<option value="g"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.unit) ? ssrLooseContain(pkgForm.value.unit, "g") : ssrLooseEqual(pkgForm.value.unit, "g")) ? " selected" : ""}${_scopeId}>g</option>`);
            } else {
              _push2(`<!---->`);
            }
            if (pkgForm.value.bulk_unit === "kg") {
              _push2(`<option value="kg"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.unit) ? ssrLooseContain(pkgForm.value.unit, "kg") : ssrLooseEqual(pkgForm.value.unit, "kg")) ? " selected" : ""}${_scopeId}>kg</option>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<option value="und"${ssrIncludeBooleanAttr(Array.isArray(pkgForm.value.unit) ? ssrLooseContain(pkgForm.value.unit, "und") : ssrLooseEqual(pkgForm.value.unit, "und")) ? " selected" : ""}${_scopeId}>und</option></select></div></div><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"${_scopeId}>Nombre a Inventario</label><input${ssrRenderAttr("value", pkgForm.value.final_name)} type="text" placeholder="Ej: Jabón Líquido 500ml" class="block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"${_scopeId}></div></div><div class="space-y-3"${_scopeId}><div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-2"${_scopeId}><h4 class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider"${_scopeId}>Insumos/Materiales de Envasado</h4>`);
            _push2(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-plus",
              label: "Añadir Insumo",
              color: "green",
              variant: "ghost",
              size: "xs",
              onClick: addPkgMaterial
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="space-y-3 max-h-[25vh] overflow-y-auto pr-1"${_scopeId}>`);
            if (pkgForm.value.extra_mats.length === 0) {
              _push2(`<div class="text-xs text-slate-550 dark:text-slate-400 py-4 text-center"${_scopeId}> Ningún material de envasado seleccionado. Puedes añadir botellas, cajas o etiquetas desde el catálogo. </div>`);
            } else {
              _push2(`<!--[-->`);
              ssrRenderList(pkgForm.value.extra_mats, (mat, idx) => {
                _push2(`<div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-lg border border-slate-200 dark:border-slate-850"${_scopeId}><select class="flex-1 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500"${_scopeId}><option value=""${ssrIncludeBooleanAttr(Array.isArray(mat.id_raw) ? ssrLooseContain(mat.id_raw, "") : ssrLooseEqual(mat.id_raw, "")) ? " selected" : ""}${_scopeId}>Seleccione Insumo...</option><!--[-->`);
                ssrRenderList(materials.value, (m) => {
                  _push2(`<option${ssrRenderAttr("value", m.id_raw_material)}${ssrIncludeBooleanAttr(Array.isArray(mat.id_raw) ? ssrLooseContain(mat.id_raw, m.id_raw_material) : ssrLooseEqual(mat.id_raw, m.id_raw_material)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(m.name_raw_material)} (${ssrInterpolate(m.unit_raw_material)}) </option>`);
                });
                _push2(`<!--]--></select><input${ssrRenderAttr("value", parseInt(mat.qty) > 0 ? parseInt(mat.qty).toLocaleString("de-DE") : "")} type="text" inputmode="numeric" placeholder="0" class="w-24 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UButton, {
                  icon: "i-lucide-trash",
                  color: "red",
                  variant: "ghost",
                  size: "xs",
                  onClick: ($event) => removePkgMaterial(idx)
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              });
              _push2(`<!--]-->`);
            }
            _push2(`</div></div>`);
            if (isAdmin.value) {
              _push2(`<div class="row grid grid-cols-2 gap-4 border-t border-slate-200 dark:border-slate-800 pt-4"${_scopeId}><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1"${_scopeId}> Mano de Obra Extra (Envasado) `);
              if (unref(auth).role !== "lab_admin") {
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-lock",
                  class: "text-amber-500 w-3 h-3 animate-bounce"
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(`</label><div class="relative"${_scopeId}><span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none"${_scopeId}>Bs.</span><input${ssrRenderAttr("value", unref(pkgExtraMoInput).display.value)} type="text" inputmode="decimal" placeholder="0,00"${ssrIncludeBooleanAttr(unref(auth).role !== "lab_admin") ? " disabled" : ""} class="block w-full py-2.5 pl-10 pr-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-60 disabled:cursor-not-allowed"${_scopeId}></div></div><div${_scopeId}><label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1"${_scopeId}> Costos Indirectos Extra (Energía, etc.) `);
              if (unref(auth).role !== "lab_admin") {
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-lock",
                  class: "text-amber-500 w-3 h-3 animate-bounce"
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(`</label><div class="relative"${_scopeId}><span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none"${_scopeId}>Bs.</span><input${ssrRenderAttr("value", unref(pkgExtraCifInput).display.value)} type="text" inputmode="decimal" placeholder="0,00"${ssrIncludeBooleanAttr(unref(auth).role !== "lab_admin") ? " disabled" : ""} class="block w-full py-2.5 pl-10 pr-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-60 disabled:cursor-not-allowed"${_scopeId}></div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              label: "Cancelar",
              variant: "ghost",
              color: "neutral",
              onClick: ($event) => isPkgOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UButton, {
              label: "Confirmar Envasado y Enviar a QC",
              color: "green",
              class: "font-bold!",
              onClick: submitPackaging
            }, null, _parent2, _scopeId));
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "w-full max-w-2xl lg:max-w-3xl p-6 bg-white dark:bg-slate-900 text-slate-900 dark:text-white rounded-xl shadow-2xl space-y-5 border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto" }, [
                createVNode("div", { class: "flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-green-600 dark:text-green-400" }, [
                  createVNode("h3", { class: "text-lg font-bold tracking-wide flex items-center gap-2" }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-boxes",
                      class: "w-6 h-6 animate-pulse"
                    }),
                    createTextVNode(" Fase de Envasado y Finalización ")
                  ]),
                  createVNode(_component_UButton, {
                    icon: "i-lucide-x",
                    variant: "ghost",
                    color: "neutral",
                    size: "sm",
                    onClick: ($event) => isPkgOpen.value = false
                  }, null, 8, ["onClick"])
                ]),
                createVNode("div", { class: "grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-200 dark:border-slate-850" }, [
                  createVNode("div", null, [
                    createVNode("p", { class: "text-xs text-slate-400 uppercase font-bold" }, "Total Producido (A Granel)"),
                    createVNode("p", { class: "text-lg font-black text-green-600 dark:text-green-400 mt-1" }, toDisplayString(pkgForm.value.total_qty) + " " + toDisplayString(pkgForm.value.bulk_unit), 1)
                  ]),
                  createVNode("div", { class: "text-right" }, [
                    createVNode("p", { class: "text-xs text-slate-400 uppercase font-bold" }, "Envases Calculados"),
                    createVNode("p", { class: "text-lg font-black text-blue-600 dark:text-blue-400 mt-1" }, [
                      createTextVNode(toDisplayString(calculatedEnvases.value) + " ", 1),
                      createVNode("span", { class: "text-xs font-normal text-slate-500" }, "Unidades")
                    ])
                  ])
                ]),
                createVNode("div", { class: "border border-slate-200 dark:border-slate-800 rounded-xl p-4 bg-slate-50/50 dark:bg-slate-950/20 space-y-3" }, [
                  createVNode("h4", { class: "text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5" }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-scale",
                      class: "w-4 h-4 text-green-500"
                    }),
                    createTextVNode(" Resultado del Proceso de Elaboración ")
                  ]),
                  createVNode("p", { class: "text-xs text-slate-400" }, [
                    createTextVNode("Rendimiento esperado: "),
                    createVNode("strong", { class: "text-green-600 dark:text-green-400" }, toDisplayString(pkgForm.value.total_qty) + " " + toDisplayString(pkgForm.value.bulk_unit), 1)
                  ]),
                  createVNode("div", { class: "flex flex-col sm:flex-row sm:items-center gap-4 text-sm" }, [
                    createVNode("label", { class: "flex items-center gap-2 cursor-pointer font-medium" }, [
                      withDirectives(createVNode("input", {
                        type: "radio",
                        "onUpdate:modelValue": ($event) => pkgForm.value.yield_type = $event,
                        value: "same",
                        class: "accent-green-600"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelRadio, pkgForm.value.yield_type]
                      ]),
                      createTextVNode(" Rendimiento óptimo (Obtenido exactamente lo esperado) ")
                    ]),
                    createVNode("label", { class: "flex items-center gap-2 cursor-pointer font-medium" }, [
                      withDirectives(createVNode("input", {
                        type: "radio",
                        "onUpdate:modelValue": ($event) => pkgForm.value.yield_type = $event,
                        value: "diff",
                        class: "accent-green-600"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelRadio, pkgForm.value.yield_type]
                      ]),
                      createTextVNode(" Rendimiento variable (Obtenido cantidad diferente) ")
                    ])
                  ]),
                  pkgForm.value.yield_type === "diff" ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "pt-2 border-t border-slate-200 dark:border-slate-800"
                  }, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Cantidad Real Obtenida"),
                    createVNode("div", { class: "relative rounded-lg shadow-sm w-48" }, [
                      createVNode("input", {
                        value: unref(pkgRealBulkInput).display.value,
                        type: "text",
                        inputmode: "decimal",
                        placeholder: "0,000",
                        class: "block w-full py-2 px-3 pr-10 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-green-500/50",
                        onInput: ($event) => unref(pkgRealBulkInput).onInput($event),
                        onKeydown: ($event) => unref(pkgRealBulkInput).onKeydown($event)
                      }, null, 40, ["value", "onInput", "onKeydown"]),
                      createVNode("div", { class: "absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-xs font-bold" }, toDisplayString(pkgForm.value.bulk_unit), 1)
                    ])
                  ])) : createCommentVNode("", true)
                ]),
                createVNode("div", { class: "grid grid-cols-1 md:grid-cols-3 gap-4" }, [
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Tipo de Empaque"),
                    withDirectives(createVNode("select", {
                      "onUpdate:modelValue": ($event) => pkgForm.value.envase_type = $event,
                      class: "block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                    }, [
                      createVNode("option", { value: "botellas" }, "Botellas"),
                      createVNode("option", { value: "frascos" }, "Frascos"),
                      createVNode("option", { value: "bolsas" }, "Bolsas"),
                      createVNode("option", { value: "cajas" }, "Cajas"),
                      createVNode("option", { value: "galones" }, "Galones"),
                      createVNode("option", { value: "unidades" }, "Unidades Sueltas")
                    ], 8, ["onUpdate:modelValue"]), [
                      [vModelSelect, pkgForm.value.envase_type]
                    ])
                  ]),
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Capacidad del Envase"),
                    createVNode("div", { class: "flex" }, [
                      createVNode("input", {
                        value: unref(pkgVolumeInput).display.value,
                        type: "text",
                        inputmode: "decimal",
                        placeholder: "500",
                        class: "block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-l-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50",
                        onInput: ($event) => unref(pkgVolumeInput).onInput($event),
                        onKeydown: ($event) => unref(pkgVolumeInput).onKeydown($event)
                      }, null, 40, ["value", "onInput", "onKeydown"]),
                      withDirectives(createVNode("select", {
                        "onUpdate:modelValue": ($event) => pkgForm.value.unit = $event,
                        class: "py-2.5 px-2 bg-slate-100 dark:bg-slate-900 border-y border-r border-slate-200 dark:border-slate-800 rounded-r-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 w-24"
                      }, [
                        pkgForm.value.bulk_unit === "L" ? (openBlock(), createBlock("option", {
                          key: 0,
                          value: "ml"
                        }, "ml")) : createCommentVNode("", true),
                        pkgForm.value.bulk_unit === "L" ? (openBlock(), createBlock("option", {
                          key: 1,
                          value: "L"
                        }, "L")) : createCommentVNode("", true),
                        pkgForm.value.bulk_unit === "kg" ? (openBlock(), createBlock("option", {
                          key: 2,
                          value: "g"
                        }, "g")) : createCommentVNode("", true),
                        pkgForm.value.bulk_unit === "kg" ? (openBlock(), createBlock("option", {
                          key: 3,
                          value: "kg"
                        }, "kg")) : createCommentVNode("", true),
                        createVNode("option", { value: "und" }, "und")
                      ], 8, ["onUpdate:modelValue"]), [
                        [vModelSelect, pkgForm.value.unit]
                      ])
                    ])
                  ]),
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2" }, "Nombre a Inventario"),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => pkgForm.value.final_name = $event,
                      type: "text",
                      placeholder: "Ej: Jabón Líquido 500ml",
                      class: "block w-full py-2.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, pkgForm.value.final_name]
                    ])
                  ])
                ]),
                createVNode("div", { class: "space-y-3" }, [
                  createVNode("div", { class: "flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-2" }, [
                    createVNode("h4", { class: "text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider" }, "Insumos/Materiales de Envasado"),
                    createVNode(_component_UButton, {
                      icon: "i-lucide-plus",
                      label: "Añadir Insumo",
                      color: "green",
                      variant: "ghost",
                      size: "xs",
                      onClick: addPkgMaterial
                    })
                  ]),
                  createVNode("div", { class: "space-y-3 max-h-[25vh] overflow-y-auto pr-1" }, [
                    pkgForm.value.extra_mats.length === 0 ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "text-xs text-slate-550 dark:text-slate-400 py-4 text-center"
                    }, " Ningún material de envasado seleccionado. Puedes añadir botellas, cajas o etiquetas desde el catálogo. ")) : (openBlock(true), createBlock(Fragment, { key: 1 }, renderList(pkgForm.value.extra_mats, (mat, idx) => {
                      return openBlock(), createBlock("div", {
                        key: idx,
                        class: "flex items-center gap-3 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-lg border border-slate-200 dark:border-slate-850"
                      }, [
                        withDirectives(createVNode("select", {
                          "onUpdate:modelValue": ($event) => mat.id_raw = $event,
                          onChange: ($event) => mat.qty = String(calculatedEnvases.value || 0),
                          class: "flex-1 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500"
                        }, [
                          createVNode("option", { value: "" }, "Seleccione Insumo..."),
                          (openBlock(true), createBlock(Fragment, null, renderList(materials.value, (m) => {
                            return openBlock(), createBlock("option", {
                              key: m.id_raw_material,
                              value: m.id_raw_material
                            }, toDisplayString(m.name_raw_material) + " (" + toDisplayString(m.unit_raw_material) + ") ", 9, ["value"]);
                          }), 128))
                        ], 40, ["onUpdate:modelValue", "onChange"]), [
                          [vModelSelect, mat.id_raw]
                        ]),
                        createVNode("input", {
                          value: parseInt(mat.qty) > 0 ? parseInt(mat.qty).toLocaleString("de-DE") : "",
                          type: "text",
                          inputmode: "numeric",
                          placeholder: "0",
                          class: "w-24 py-1.5 px-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-green-500",
                          onInput: ($event) => onPkgMatQtyInput($event, idx),
                          onKeydown: ($event) => blockNegative($event)
                        }, null, 40, ["value", "onInput", "onKeydown"]),
                        createVNode(_component_UButton, {
                          icon: "i-lucide-trash",
                          color: "red",
                          variant: "ghost",
                          size: "xs",
                          onClick: ($event) => removePkgMaterial(idx)
                        }, null, 8, ["onClick"])
                      ]);
                    }), 128))
                  ])
                ]),
                isAdmin.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "row grid grid-cols-2 gap-4 border-t border-slate-200 dark:border-slate-800 pt-4"
                }, [
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1" }, [
                      createTextVNode(" Mano de Obra Extra (Envasado) "),
                      unref(auth).role !== "lab_admin" ? (openBlock(), createBlock(_component_UIcon, {
                        key: 0,
                        name: "i-lucide-lock",
                        class: "text-amber-500 w-3 h-3 animate-bounce"
                      })) : createCommentVNode("", true)
                    ]),
                    createVNode("div", { class: "relative" }, [
                      createVNode("span", { class: "absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none" }, "Bs."),
                      createVNode("input", {
                        value: unref(pkgExtraMoInput).display.value,
                        type: "text",
                        inputmode: "decimal",
                        placeholder: "0,00",
                        disabled: unref(auth).role !== "lab_admin",
                        class: "block w-full py-2.5 pl-10 pr-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-60 disabled:cursor-not-allowed",
                        onInput: ($event) => unref(pkgExtraMoInput).onInput($event),
                        onKeydown: ($event) => unref(pkgExtraMoInput).onKeydown($event)
                      }, null, 40, ["value", "disabled", "onInput", "onKeydown"])
                    ])
                  ]),
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1" }, [
                      createTextVNode(" Costos Indirectos Extra (Energía, etc.) "),
                      unref(auth).role !== "lab_admin" ? (openBlock(), createBlock(_component_UIcon, {
                        key: 0,
                        name: "i-lucide-lock",
                        class: "text-amber-500 w-3 h-3 animate-bounce"
                      })) : createCommentVNode("", true)
                    ]),
                    createVNode("div", { class: "relative" }, [
                      createVNode("span", { class: "absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm font-bold pointer-events-none" }, "Bs."),
                      createVNode("input", {
                        value: unref(pkgExtraCifInput).display.value,
                        type: "text",
                        inputmode: "decimal",
                        placeholder: "0,00",
                        disabled: unref(auth).role !== "lab_admin",
                        class: "block w-full py-2.5 pl-10 pr-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 disabled:opacity-60 disabled:cursor-not-allowed",
                        onInput: ($event) => unref(pkgExtraCifInput).onInput($event),
                        onKeydown: ($event) => unref(pkgExtraCifInput).onKeydown($event)
                      }, null, 40, ["value", "disabled", "onInput", "onKeydown"])
                    ])
                  ])
                ])) : createCommentVNode("", true),
                createVNode("div", { class: "flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-2" }, [
                  createVNode(_component_UButton, {
                    label: "Cancelar",
                    variant: "ghost",
                    color: "neutral",
                    onClick: ($event) => isPkgOpen.value = false
                  }, null, 8, ["onClick"]),
                  createVNode(_component_UButton, {
                    label: "Confirmar Envasado y Enviar a QC",
                    color: "green",
                    class: "font-bold!",
                    onClick: submitPackaging
                  })
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UModal, {
        open: isDetailsOpen.value,
        "onUpdate:open": ($event) => isDetailsOpen.value = $event
      }, {
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-full max-w-2xl lg:max-w-3xl p-6 bg-white dark:bg-slate-900 text-slate-900 dark:text-white rounded-xl shadow-2xl space-y-6 border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto"${_scopeId}><div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-blue-600 dark:text-blue-400"${_scopeId}><h3 class="text-lg font-bold tracking-wide flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-file-text",
              class: "w-6 h-6"
            }, null, _parent2, _scopeId));
            _push2(` Detalle Histórico de Producción #${ssrInterpolate(detailsData.value?.production?.id_production)}</h3>`);
            _push2(ssrRenderComponent(_component_UButton, {
              icon: "i-lucide-x",
              variant: "ghost",
              color: "neutral",
              size: "sm",
              onClick: ($event) => isDetailsOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(`</div>`);
            if (detailsLoading.value) {
              _push2(`<div class="p-12 text-center text-slate-500"${_scopeId}>`);
              _push2(ssrRenderComponent(_component_UIcon, {
                name: "i-lucide-loader-2",
                class: "w-8 h-8 animate-spin mx-auto text-blue-500 mb-2"
              }, null, _parent2, _scopeId));
              _push2(` Cargando historial completo de producción... </div>`);
            } else if (detailsData.value) {
              _push2(`<div class="space-y-6"${_scopeId}><div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-200 dark:border-slate-850"${_scopeId}><div class="space-y-1.5 text-sm"${_scopeId}><div${_scopeId}><span class="text-slate-400 font-bold text-xs uppercase tracking-wider"${_scopeId}>Producto a Granel:</span><strong class="text-slate-800 dark:text-white uppercase ms-1"${_scopeId}>${ssrInterpolate(detailsData.value.production?.title_product)}</strong></div>`);
              if (detailsData.value.production?.pkg_name_production) {
                _push2(`<div${_scopeId}><span class="text-slate-400 font-bold text-xs uppercase tracking-wider"${_scopeId}>Producto Final Envasado:</span><strong class="text-indigo-600 dark:text-indigo-400 ms-1"${_scopeId}>${ssrInterpolate(detailsData.value.production?.pkg_name_production)}</strong></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div${_scopeId}><span class="text-slate-400 font-bold text-xs uppercase tracking-wider"${_scopeId}>Estado:</span><span class="${ssrRenderClass([{
                "text-emerald-600 dark:text-emerald-400": detailsData.value.production?.status_production === "completado",
                "text-indigo-600 dark:text-indigo-400": detailsData.value.production?.status_production === "pendiente_qc",
                "text-blue-600 dark:text-blue-400": detailsData.value.production?.status_production === "en_proceso",
                "text-rose-500": detailsData.value.production?.status_production === "rechazado",
                "text-amber-600 dark:text-amber-400": detailsData.value.production?.status_production === "pendiente"
              }, "uppercase font-bold text-xs ms-1"])}"${_scopeId}>${ssrInterpolate(detailsData.value.production?.status_production?.replace(/_/g, " "))}</span></div><div${_scopeId}><span class="text-slate-400 font-bold text-xs uppercase tracking-wider"${_scopeId}>Fecha Inicio:</span><span class="text-slate-600 dark:text-slate-350 ms-1"${_scopeId}>${ssrInterpolate(detailsData.value.production?.start_date_production || detailsData.value.production?.date_created_production)}</span></div></div><div class="space-y-1.5 text-sm md:text-right"${_scopeId}><div${_scopeId}><span class="text-slate-400 font-bold text-xs uppercase tracking-wider"${_scopeId}>Factor de Escala:</span><strong class="text-slate-800 dark:text-white ms-1"${_scopeId}>${ssrInterpolate(parseFloat(detailsData.value.production?.batches_production || 0).toFixed(3))}x</strong></div><div${_scopeId}><span class="text-slate-400 font-bold text-xs uppercase tracking-wider"${_scopeId}>Planificado a Granel:</span><strong class="text-indigo-600 dark:text-indigo-400 ms-1"${_scopeId}>${ssrInterpolate(parseFloat(detailsData.value.production?.total_qty_production || 0).toLocaleString("de-DE", { maximumFractionDigits: 3 }))} ${ssrInterpolate(detailsData.value.production?.unit_product)}</strong></div>`);
              if (detailsData.value.production?.real_bulk_qty) {
                _push2(`<div${_scopeId}><span class="text-slate-400 font-bold text-xs uppercase tracking-wider"${_scopeId}>Real Obtenido:</span><strong class="text-emerald-600 dark:text-emerald-400 ms-1"${_scopeId}>${ssrInterpolate(parseFloat(detailsData.value.production?.real_bulk_qty || 0).toLocaleString("de-DE", { maximumFractionDigits: 3 }))} ${ssrInterpolate(detailsData.value.production?.unit_product)}</strong></div>`);
              } else {
                _push2(`<!---->`);
              }
              if (detailsData.value.production?.qty_packaged_production) {
                _push2(`<div${_scopeId}><span class="text-slate-400 font-bold text-xs uppercase tracking-wider"${_scopeId}>Unidades Envasadas:</span><strong class="text-blue-600 dark:text-blue-400 ms-1"${_scopeId}>${ssrInterpolate(parseFloat(detailsData.value.production?.qty_packaged_production || 0).toLocaleString())} und</strong></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div></div>`);
              if (detailsData.value.production?.status_production === "pendiente" || detailsData.value.production?.status_production === "en_proceso") {
                _push2(`<div class="flex items-center gap-2 px-4 py-3 bg-amber-500/10 border border-amber-500/20 rounded-lg text-xs text-amber-700 dark:text-amber-300 font-medium"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-clock",
                  class: "w-4 h-4 shrink-0"
                }, null, _parent2, _scopeId));
                _push2(` Los costos de insumos y el desglose financiero estarán disponibles cuando la producción sea envasada y aprobada por QC. </div>`);
              } else {
                _push2(`<!---->`);
              }
              if (detailsData.value.production?.real_bulk_qty) {
                _push2(`<div class="p-3 bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-300 rounded-lg text-xs font-bold flex justify-between"${_scopeId}><span${_scopeId}>Variación de Proceso Bulk (Fabricación):</span><span${_scopeId}>${ssrInterpolate(detailsData.value.production?.yield_variance >= 0 ? "+" : "")}${ssrInterpolate(parseFloat(detailsData.value.production?.yield_variance).toFixed(2))} ${ssrInterpolate(detailsData.value.production?.unit_product)} (${ssrInterpolate(detailsData.value.production?.yield_variance >= 0 ? "+" : "")}${ssrInterpolate(parseFloat(detailsData.value.production?.yield_variance_pct).toFixed(1))}%) </span></div>`);
              } else {
                _push2(`<!---->`);
              }
              if (detailsData.value.production?.result_qc) {
                _push2(`<div class="border border-slate-200 dark:border-slate-800 rounded-xl p-4 bg-slate-50/50 dark:bg-slate-950/20 space-y-3"${_scopeId}><h4 class="text-xs font-black text-slate-550 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 dark:border-slate-800 pb-2"${_scopeId}>`);
                _push2(ssrRenderComponent(_component_UIcon, {
                  name: "i-lucide-shield-check",
                  class: "text-green-500 w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(` Control de Calidad &amp; Liberación Final </h4><div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs font-mono"${_scopeId}><div${_scopeId}><p class="text-slate-400 font-sans font-bold"${_scopeId}>Unidades Evaluadas</p><p class="text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5"${_scopeId}>${ssrInterpolate(parseFloat(detailsData.value.production?.qty_packaged_production || detailsData.value.production?.total_qty_production).toLocaleString())} und</p></div><div${_scopeId}><p class="text-emerald-600 font-sans font-bold"${_scopeId}>✅ Aprobadas</p><p class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mt-0.5"${_scopeId}>${ssrInterpolate(parseFloat(detailsData.value.production?.qty_approved_qc).toLocaleString())} und</p></div><div${_scopeId}><p class="text-rose-500 font-sans font-bold"${_scopeId}>❌ Rechazadas (Merma)</p><p class="text-sm font-bold text-rose-500 dark:text-rose-400 mt-0.5"${_scopeId}>${ssrInterpolate(parseFloat(detailsData.value.production?.qty_rejected_qc).toLocaleString())} und</p></div><div${_scopeId}><p class="text-slate-400 font-sans font-bold"${_scopeId}>Resultado / Inspector</p><p class="text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5 uppercase"${_scopeId}>${ssrInterpolate(detailsData.value.production?.result_qc)}</p><p class="text-xxs text-slate-400 font-sans mt-0.5"${_scopeId}>${ssrInterpolate(detailsData.value.production?.qc_inspector_name)}</p></div></div>`);
                if (detailsData.value.production?.qc_notes) {
                  _push2(`<div class="mt-3 p-3 bg-slate-100 dark:bg-slate-950 border-l-4 border-l-amber-500 rounded text-xs text-slate-600 dark:text-slate-400 italic"${_scopeId}><strong${_scopeId}>Observaciones inspector:</strong> &quot;${ssrInterpolate(detailsData.value.production?.qc_notes)}&quot; </div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="space-y-2"${_scopeId}><h4 class="text-xs font-black text-slate-550 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-2"${_scopeId}> Insumos &amp; Materiales Consumidos de Inventario </h4><div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-lg"${_scopeId}><table class="w-full text-left text-xs text-slate-650 dark:text-slate-350"${_scopeId}><thead class="bg-slate-50 dark:bg-slate-900/60 font-bold uppercase tracking-wider text-slate-500 dark:text-slate-450 border-b border-slate-200 dark:border-slate-800/80"${_scopeId}><tr${_scopeId}><th class="px-4 py-2.5"${_scopeId}>Insumo / Materia Prima</th><th class="px-4 py-2.5 text-right"${_scopeId}>Cantidad Usada</th>`);
              if (isAdmin.value) {
                _push2(`<th class="px-4 py-2.5 text-right"${_scopeId}>Costo Unitario</th>`);
              } else {
                _push2(`<!---->`);
              }
              if (isAdmin.value) {
                _push2(`<th class="px-4 py-2.5 text-right"${_scopeId}>Subtotal</th>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</tr></thead><tbody class="divide-y divide-slate-150 dark:divide-slate-800 font-mono"${_scopeId}><!--[-->`);
              ssrRenderList(detailsData.value.materials, (m) => {
                _push2(`<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/10"${_scopeId}><td class="px-4 py-2.5 font-sans font-semibold text-slate-800 dark:text-white uppercase"${_scopeId}>${ssrInterpolate(m.name_raw_material)}</td><td class="px-4 py-2.5 text-right font-bold text-slate-700 dark:text-slate-300"${_scopeId}>${ssrInterpolate(parseFloat(m.qty_used_mat_cost).toLocaleString())} <span class="text-xxs text-slate-500 font-normal"${_scopeId}>${ssrInterpolate(m.unit_raw_material)}</span></td>`);
                if (isAdmin.value) {
                  _push2(`<td class="px-4 py-2.5 text-right text-slate-500"${_scopeId}>Bs ${ssrInterpolate(parseFloat(m.unit_price_at_production).toFixed(2))}</td>`);
                } else {
                  _push2(`<!---->`);
                }
                if (isAdmin.value) {
                  _push2(`<td class="px-4 py-2.5 text-right font-bold text-slate-800 dark:text-white"${_scopeId}>Bs ${ssrInterpolate(parseFloat(m.total_cost_mat_cost).toFixed(2))}</td>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</tr>`);
              });
              _push2(`<!--]-->`);
              if (!detailsData.value.materials || detailsData.value.materials.length === 0) {
                _push2(`<tr${_scopeId}><td${ssrRenderAttr("colspan", isAdmin.value ? 4 : 2)} class="px-4 py-6 text-center text-slate-500 font-sans italic"${_scopeId}> No hay insumos consumidos registrados para esta orden. </td></tr>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</tbody></table></div></div>`);
              if (isAdmin.value && detailsData.value.production?.real_total_cost > 0) {
                _push2(`<div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-200 dark:border-slate-800 pt-4"${_scopeId}><div class="bg-slate-50 dark:bg-slate-950/20 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2 text-xs font-mono"${_scopeId}><h5 class="font-sans font-bold text-slate-700 dark:text-slate-300 border-b pb-1.5 mb-2"${_scopeId}>Desglose de Costos de Manufactura</h5><div class="flex justify-between text-slate-600 dark:text-slate-400"${_scopeId}><span${_scopeId}>MP Consumidas (total):</span><strong class="text-slate-800 dark:text-slate-200"${_scopeId}> Bs. ${ssrInterpolate(detailsData.value.materials?.reduce((a, m) => a + parseFloat(m.total_cost_mat_cost || 0), 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}</strong></div><div class="flex justify-between"${_scopeId}><span${_scopeId}>MO Elaboración:</span><strong${_scopeId}>Bs. ${ssrInterpolate(parseFloat(detailsData.value.production?.real_labor_cost || detailsData.value.production?.proj_labor_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}</strong></div>`);
                if (parseFloat(detailsData.value.production?.pkg_labor_cost) > 0) {
                  _push2(`<div class="flex justify-between text-blue-600 dark:text-blue-400"${_scopeId}><span${_scopeId}>MO Envasado:</span><strong${_scopeId}>Bs. ${ssrInterpolate(parseFloat(detailsData.value.production?.pkg_labor_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}</strong></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<div class="flex justify-between"${_scopeId}><span${_scopeId}>CIF Elaboración:</span><strong${_scopeId}>Bs. ${ssrInterpolate(parseFloat(detailsData.value.production?.real_indirect_cost || detailsData.value.production?.proj_indirect_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}</strong></div>`);
                if (parseFloat(detailsData.value.production?.pkg_indirect_cost) > 0) {
                  _push2(`<div class="flex justify-between text-blue-600 dark:text-blue-400"${_scopeId}><span${_scopeId}>CIF Envasado:</span><strong${_scopeId}>Bs. ${ssrInterpolate(parseFloat(detailsData.value.production?.pkg_indirect_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}</strong></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div class="bg-slate-50 dark:bg-slate-950/20 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2 text-xs font-mono flex flex-col justify-between"${_scopeId}><div${_scopeId}><h5 class="font-sans font-bold text-slate-700 dark:text-slate-300 border-b pb-1.5 mb-2"${_scopeId}>Resumen Financiero del Lote</h5><div class="flex justify-between"${_scopeId}><span${_scopeId}>Costo Total Lote:</span><strong class="text-indigo-600 dark:text-indigo-400 text-sm"${_scopeId}> Bs. ${ssrInterpolate(parseFloat(detailsData.value.production?.real_total_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}</strong></div></div><div class="border-t pt-2 mt-2"${_scopeId}><div class="flex justify-between items-end"${_scopeId}><span class="font-sans font-bold text-slate-700 dark:text-slate-300"${_scopeId}>Costo Unitario Real:</span><strong class="text-emerald-600 dark:text-emerald-400 text-base"${_scopeId}> Bs. ${ssrInterpolate(parseFloat(detailsData.value.production?.real_unit_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 4, maximumFractionDigits: 4 }))}</strong></div><p class="text-[10px] text-slate-400 font-sans mt-1"${_scopeId}> Calculado sobre ${ssrInterpolate(parseFloat(detailsData.value.production?.qty_approved_qc || detailsData.value.production?.qty_packaged_production || 0).toLocaleString())} unidades aprobadas por QC. </p></div></div></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              label: "Cerrar Detalles",
              color: "neutral",
              onClick: ($event) => isDetailsOpen.value = false
            }, null, _parent2, _scopeId));
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "w-full max-w-2xl lg:max-w-3xl p-6 bg-white dark:bg-slate-900 text-slate-900 dark:text-white rounded-xl shadow-2xl space-y-6 border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto" }, [
                createVNode("div", { class: "flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-3 text-blue-600 dark:text-blue-400" }, [
                  createVNode("h3", { class: "text-lg font-bold tracking-wide flex items-center gap-2" }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-file-text",
                      class: "w-6 h-6"
                    }),
                    createTextVNode(" Detalle Histórico de Producción #" + toDisplayString(detailsData.value?.production?.id_production), 1)
                  ]),
                  createVNode(_component_UButton, {
                    icon: "i-lucide-x",
                    variant: "ghost",
                    color: "neutral",
                    size: "sm",
                    onClick: ($event) => isDetailsOpen.value = false
                  }, null, 8, ["onClick"])
                ]),
                detailsLoading.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "p-12 text-center text-slate-500"
                }, [
                  createVNode(_component_UIcon, {
                    name: "i-lucide-loader-2",
                    class: "w-8 h-8 animate-spin mx-auto text-blue-500 mb-2"
                  }),
                  createTextVNode(" Cargando historial completo de producción... ")
                ])) : detailsData.value ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "space-y-6"
                }, [
                  createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-200 dark:border-slate-850" }, [
                    createVNode("div", { class: "space-y-1.5 text-sm" }, [
                      createVNode("div", null, [
                        createVNode("span", { class: "text-slate-400 font-bold text-xs uppercase tracking-wider" }, "Producto a Granel:"),
                        createVNode("strong", { class: "text-slate-800 dark:text-white uppercase ms-1" }, toDisplayString(detailsData.value.production?.title_product), 1)
                      ]),
                      detailsData.value.production?.pkg_name_production ? (openBlock(), createBlock("div", { key: 0 }, [
                        createVNode("span", { class: "text-slate-400 font-bold text-xs uppercase tracking-wider" }, "Producto Final Envasado:"),
                        createVNode("strong", { class: "text-indigo-600 dark:text-indigo-400 ms-1" }, toDisplayString(detailsData.value.production?.pkg_name_production), 1)
                      ])) : createCommentVNode("", true),
                      createVNode("div", null, [
                        createVNode("span", { class: "text-slate-400 font-bold text-xs uppercase tracking-wider" }, "Estado:"),
                        createVNode("span", {
                          class: ["uppercase font-bold text-xs ms-1", {
                            "text-emerald-600 dark:text-emerald-400": detailsData.value.production?.status_production === "completado",
                            "text-indigo-600 dark:text-indigo-400": detailsData.value.production?.status_production === "pendiente_qc",
                            "text-blue-600 dark:text-blue-400": detailsData.value.production?.status_production === "en_proceso",
                            "text-rose-500": detailsData.value.production?.status_production === "rechazado",
                            "text-amber-600 dark:text-amber-400": detailsData.value.production?.status_production === "pendiente"
                          }]
                        }, toDisplayString(detailsData.value.production?.status_production?.replace(/_/g, " ")), 3)
                      ]),
                      createVNode("div", null, [
                        createVNode("span", { class: "text-slate-400 font-bold text-xs uppercase tracking-wider" }, "Fecha Inicio:"),
                        createVNode("span", { class: "text-slate-600 dark:text-slate-350 ms-1" }, toDisplayString(detailsData.value.production?.start_date_production || detailsData.value.production?.date_created_production), 1)
                      ])
                    ]),
                    createVNode("div", { class: "space-y-1.5 text-sm md:text-right" }, [
                      createVNode("div", null, [
                        createVNode("span", { class: "text-slate-400 font-bold text-xs uppercase tracking-wider" }, "Factor de Escala:"),
                        createVNode("strong", { class: "text-slate-800 dark:text-white ms-1" }, toDisplayString(parseFloat(detailsData.value.production?.batches_production || 0).toFixed(3)) + "x", 1)
                      ]),
                      createVNode("div", null, [
                        createVNode("span", { class: "text-slate-400 font-bold text-xs uppercase tracking-wider" }, "Planificado a Granel:"),
                        createVNode("strong", { class: "text-indigo-600 dark:text-indigo-400 ms-1" }, toDisplayString(parseFloat(detailsData.value.production?.total_qty_production || 0).toLocaleString("de-DE", { maximumFractionDigits: 3 })) + " " + toDisplayString(detailsData.value.production?.unit_product), 1)
                      ]),
                      detailsData.value.production?.real_bulk_qty ? (openBlock(), createBlock("div", { key: 0 }, [
                        createVNode("span", { class: "text-slate-400 font-bold text-xs uppercase tracking-wider" }, "Real Obtenido:"),
                        createVNode("strong", { class: "text-emerald-600 dark:text-emerald-400 ms-1" }, toDisplayString(parseFloat(detailsData.value.production?.real_bulk_qty || 0).toLocaleString("de-DE", { maximumFractionDigits: 3 })) + " " + toDisplayString(detailsData.value.production?.unit_product), 1)
                      ])) : createCommentVNode("", true),
                      detailsData.value.production?.qty_packaged_production ? (openBlock(), createBlock("div", { key: 1 }, [
                        createVNode("span", { class: "text-slate-400 font-bold text-xs uppercase tracking-wider" }, "Unidades Envasadas:"),
                        createVNode("strong", { class: "text-blue-600 dark:text-blue-400 ms-1" }, toDisplayString(parseFloat(detailsData.value.production?.qty_packaged_production || 0).toLocaleString()) + " und", 1)
                      ])) : createCommentVNode("", true)
                    ])
                  ]),
                  detailsData.value.production?.status_production === "pendiente" || detailsData.value.production?.status_production === "en_proceso" ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "flex items-center gap-2 px-4 py-3 bg-amber-500/10 border border-amber-500/20 rounded-lg text-xs text-amber-700 dark:text-amber-300 font-medium"
                  }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-clock",
                      class: "w-4 h-4 shrink-0"
                    }),
                    createTextVNode(" Los costos de insumos y el desglose financiero estarán disponibles cuando la producción sea envasada y aprobada por QC. ")
                  ])) : createCommentVNode("", true),
                  detailsData.value.production?.real_bulk_qty ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "p-3 bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-300 rounded-lg text-xs font-bold flex justify-between"
                  }, [
                    createVNode("span", null, "Variación de Proceso Bulk (Fabricación):"),
                    createVNode("span", null, toDisplayString(detailsData.value.production?.yield_variance >= 0 ? "+" : "") + toDisplayString(parseFloat(detailsData.value.production?.yield_variance).toFixed(2)) + " " + toDisplayString(detailsData.value.production?.unit_product) + " (" + toDisplayString(detailsData.value.production?.yield_variance >= 0 ? "+" : "") + toDisplayString(parseFloat(detailsData.value.production?.yield_variance_pct).toFixed(1)) + "%) ", 1)
                  ])) : createCommentVNode("", true),
                  detailsData.value.production?.result_qc ? (openBlock(), createBlock("div", {
                    key: 2,
                    class: "border border-slate-200 dark:border-slate-800 rounded-xl p-4 bg-slate-50/50 dark:bg-slate-950/20 space-y-3"
                  }, [
                    createVNode("h4", { class: "text-xs font-black text-slate-550 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 dark:border-slate-800 pb-2" }, [
                      createVNode(_component_UIcon, {
                        name: "i-lucide-shield-check",
                        class: "text-green-500 w-4 h-4"
                      }),
                      createTextVNode(" Control de Calidad & Liberación Final ")
                    ]),
                    createVNode("div", { class: "grid grid-cols-2 md:grid-cols-4 gap-4 text-xs font-mono" }, [
                      createVNode("div", null, [
                        createVNode("p", { class: "text-slate-400 font-sans font-bold" }, "Unidades Evaluadas"),
                        createVNode("p", { class: "text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5" }, toDisplayString(parseFloat(detailsData.value.production?.qty_packaged_production || detailsData.value.production?.total_qty_production).toLocaleString()) + " und", 1)
                      ]),
                      createVNode("div", null, [
                        createVNode("p", { class: "text-emerald-600 font-sans font-bold" }, "✅ Aprobadas"),
                        createVNode("p", { class: "text-sm font-bold text-emerald-600 dark:text-emerald-400 mt-0.5" }, toDisplayString(parseFloat(detailsData.value.production?.qty_approved_qc).toLocaleString()) + " und", 1)
                      ]),
                      createVNode("div", null, [
                        createVNode("p", { class: "text-rose-500 font-sans font-bold" }, "❌ Rechazadas (Merma)"),
                        createVNode("p", { class: "text-sm font-bold text-rose-500 dark:text-rose-400 mt-0.5" }, toDisplayString(parseFloat(detailsData.value.production?.qty_rejected_qc).toLocaleString()) + " und", 1)
                      ]),
                      createVNode("div", null, [
                        createVNode("p", { class: "text-slate-400 font-sans font-bold" }, "Resultado / Inspector"),
                        createVNode("p", { class: "text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5 uppercase" }, toDisplayString(detailsData.value.production?.result_qc), 1),
                        createVNode("p", { class: "text-xxs text-slate-400 font-sans mt-0.5" }, toDisplayString(detailsData.value.production?.qc_inspector_name), 1)
                      ])
                    ]),
                    detailsData.value.production?.qc_notes ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "mt-3 p-3 bg-slate-100 dark:bg-slate-950 border-l-4 border-l-amber-500 rounded text-xs text-slate-600 dark:text-slate-400 italic"
                    }, [
                      createVNode("strong", null, "Observaciones inspector:"),
                      createTextVNode(' "' + toDisplayString(detailsData.value.production?.qc_notes) + '" ', 1)
                    ])) : createCommentVNode("", true)
                  ])) : createCommentVNode("", true),
                  createVNode("div", { class: "space-y-2" }, [
                    createVNode("h4", { class: "text-xs font-black text-slate-550 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-2" }, " Insumos & Materiales Consumidos de Inventario "),
                    createVNode("div", { class: "overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-lg" }, [
                      createVNode("table", { class: "w-full text-left text-xs text-slate-650 dark:text-slate-350" }, [
                        createVNode("thead", { class: "bg-slate-50 dark:bg-slate-900/60 font-bold uppercase tracking-wider text-slate-500 dark:text-slate-450 border-b border-slate-200 dark:border-slate-800/80" }, [
                          createVNode("tr", null, [
                            createVNode("th", { class: "px-4 py-2.5" }, "Insumo / Materia Prima"),
                            createVNode("th", { class: "px-4 py-2.5 text-right" }, "Cantidad Usada"),
                            isAdmin.value ? (openBlock(), createBlock("th", {
                              key: 0,
                              class: "px-4 py-2.5 text-right"
                            }, "Costo Unitario")) : createCommentVNode("", true),
                            isAdmin.value ? (openBlock(), createBlock("th", {
                              key: 1,
                              class: "px-4 py-2.5 text-right"
                            }, "Subtotal")) : createCommentVNode("", true)
                          ])
                        ]),
                        createVNode("tbody", { class: "divide-y divide-slate-150 dark:divide-slate-800 font-mono" }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(detailsData.value.materials, (m) => {
                            return openBlock(), createBlock("tr", {
                              key: m.id_production_mat_cost,
                              class: "hover:bg-slate-50 dark:hover:bg-slate-800/10"
                            }, [
                              createVNode("td", { class: "px-4 py-2.5 font-sans font-semibold text-slate-800 dark:text-white uppercase" }, toDisplayString(m.name_raw_material), 1),
                              createVNode("td", { class: "px-4 py-2.5 text-right font-bold text-slate-700 dark:text-slate-300" }, [
                                createTextVNode(toDisplayString(parseFloat(m.qty_used_mat_cost).toLocaleString()) + " ", 1),
                                createVNode("span", { class: "text-xxs text-slate-500 font-normal" }, toDisplayString(m.unit_raw_material), 1)
                              ]),
                              isAdmin.value ? (openBlock(), createBlock("td", {
                                key: 0,
                                class: "px-4 py-2.5 text-right text-slate-500"
                              }, "Bs " + toDisplayString(parseFloat(m.unit_price_at_production).toFixed(2)), 1)) : createCommentVNode("", true),
                              isAdmin.value ? (openBlock(), createBlock("td", {
                                key: 1,
                                class: "px-4 py-2.5 text-right font-bold text-slate-800 dark:text-white"
                              }, "Bs " + toDisplayString(parseFloat(m.total_cost_mat_cost).toFixed(2)), 1)) : createCommentVNode("", true)
                            ]);
                          }), 128)),
                          !detailsData.value.materials || detailsData.value.materials.length === 0 ? (openBlock(), createBlock("tr", { key: 0 }, [
                            createVNode("td", {
                              colspan: isAdmin.value ? 4 : 2,
                              class: "px-4 py-6 text-center text-slate-500 font-sans italic"
                            }, " No hay insumos consumidos registrados para esta orden. ", 8, ["colspan"])
                          ])) : createCommentVNode("", true)
                        ])
                      ])
                    ])
                  ]),
                  isAdmin.value && detailsData.value.production?.real_total_cost > 0 ? (openBlock(), createBlock("div", {
                    key: 3,
                    class: "grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-200 dark:border-slate-800 pt-4"
                  }, [
                    createVNode("div", { class: "bg-slate-50 dark:bg-slate-950/20 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2 text-xs font-mono" }, [
                      createVNode("h5", { class: "font-sans font-bold text-slate-700 dark:text-slate-300 border-b pb-1.5 mb-2" }, "Desglose de Costos de Manufactura"),
                      createVNode("div", { class: "flex justify-between text-slate-600 dark:text-slate-400" }, [
                        createVNode("span", null, "MP Consumidas (total):"),
                        createVNode("strong", { class: "text-slate-800 dark:text-slate-200" }, " Bs. " + toDisplayString(detailsData.value.materials?.reduce((a, m) => a + parseFloat(m.total_cost_mat_cost || 0), 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })), 1)
                      ]),
                      createVNode("div", { class: "flex justify-between" }, [
                        createVNode("span", null, "MO Elaboración:"),
                        createVNode("strong", null, "Bs. " + toDisplayString(parseFloat(detailsData.value.production?.real_labor_cost || detailsData.value.production?.proj_labor_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })), 1)
                      ]),
                      parseFloat(detailsData.value.production?.pkg_labor_cost) > 0 ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "flex justify-between text-blue-600 dark:text-blue-400"
                      }, [
                        createVNode("span", null, "MO Envasado:"),
                        createVNode("strong", null, "Bs. " + toDisplayString(parseFloat(detailsData.value.production?.pkg_labor_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })), 1)
                      ])) : createCommentVNode("", true),
                      createVNode("div", { class: "flex justify-between" }, [
                        createVNode("span", null, "CIF Elaboración:"),
                        createVNode("strong", null, "Bs. " + toDisplayString(parseFloat(detailsData.value.production?.real_indirect_cost || detailsData.value.production?.proj_indirect_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })), 1)
                      ]),
                      parseFloat(detailsData.value.production?.pkg_indirect_cost) > 0 ? (openBlock(), createBlock("div", {
                        key: 1,
                        class: "flex justify-between text-blue-600 dark:text-blue-400"
                      }, [
                        createVNode("span", null, "CIF Envasado:"),
                        createVNode("strong", null, "Bs. " + toDisplayString(parseFloat(detailsData.value.production?.pkg_indirect_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })), 1)
                      ])) : createCommentVNode("", true)
                    ]),
                    createVNode("div", { class: "bg-slate-50 dark:bg-slate-950/20 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2 text-xs font-mono flex flex-col justify-between" }, [
                      createVNode("div", null, [
                        createVNode("h5", { class: "font-sans font-bold text-slate-700 dark:text-slate-300 border-b pb-1.5 mb-2" }, "Resumen Financiero del Lote"),
                        createVNode("div", { class: "flex justify-between" }, [
                          createVNode("span", null, "Costo Total Lote:"),
                          createVNode("strong", { class: "text-indigo-600 dark:text-indigo-400 text-sm" }, " Bs. " + toDisplayString(parseFloat(detailsData.value.production?.real_total_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })), 1)
                        ])
                      ]),
                      createVNode("div", { class: "border-t pt-2 mt-2" }, [
                        createVNode("div", { class: "flex justify-between items-end" }, [
                          createVNode("span", { class: "font-sans font-bold text-slate-700 dark:text-slate-300" }, "Costo Unitario Real:"),
                          createVNode("strong", { class: "text-emerald-600 dark:text-emerald-400 text-base" }, " Bs. " + toDisplayString(parseFloat(detailsData.value.production?.real_unit_cost || 0).toLocaleString("de-DE", { minimumFractionDigits: 4, maximumFractionDigits: 4 })), 1)
                        ]),
                        createVNode("p", { class: "text-[10px] text-slate-400 font-sans mt-1" }, " Calculado sobre " + toDisplayString(parseFloat(detailsData.value.production?.qty_approved_qc || detailsData.value.production?.qty_packaged_production || 0).toLocaleString()) + " unidades aprobadas por QC. ", 1)
                      ])
                    ])
                  ])) : createCommentVNode("", true)
                ])) : createCommentVNode("", true),
                createVNode("div", { class: "flex justify-end gap-2 border-t border-slate-200 dark:border-slate-850 pt-4 mt-6" }, [
                  createVNode(_component_UButton, {
                    label: "Cerrar Detalles",
                    color: "neutral",
                    onClick: ($event) => isDetailsOpen.value = false
                  }, null, 8, ["onClick"])
                ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/produccion.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=produccion-CZOCgyOT.mjs.map
