import { _ as _sfc_main$1 } from './Card-BV4DIQLA.mjs';
import { I as useAuthStore, a6 as useToast, g as _sfc_main$h, h as _sfc_main$6, i as _sfc_main$c, j as _sfc_main$f } from './server.mjs';
import { _ as _sfc_main$2 } from './Badge-LaytOPGg.mjs';
import { _ as _sfc_main$3 } from './Slideover-CbDvT2J_.mjs';
import { _ as _sfc_main$4 } from './FormField-H4QVgNpC.mjs';
import { _ as _sfc_main$5 } from './Modal-ulV1aY0B.mjs';
import { defineComponent, ref, computed, watch, resolveComponent, mergeProps, withCtx, createVNode, toDisplayString, createTextVNode, withDirectives, vModelSelect, openBlock, createBlock, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderAttr } from 'vue/server-renderer';
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

const itemsPerPage = 10;
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "admins",
  __ssrInlineRender: true,
  setup(__props) {
    useAuthStore();
    const toast = useToast();
    const apiHeaders = {
      Authorization: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy"
    };
    const admins = ref([]);
    const offices = ref([]);
    const loading = ref(true);
    const search = ref("");
    const page = ref(1);
    const isSlideoverOpen = ref(false);
    const selectedAdmin = ref(null);
    const isResetPasswordOpen = ref(false);
    const resetPasswordAdminId = ref(null);
    const newPassword = ref("");
    const resettingPassword = ref(false);
    ref(false);
    ref(null);
    const permForm = ref({
      pos: false,
      ordenes: false,
      ventas: false,
      caja: false,
      gastos: false,
      productos: false,
      categorias: false,
      compras: false,
      proveedores: false,
      almacen: false,
      mi_inventario: false,
      solicitar_inventario: false,
      reportes: false
    });
    ref(false);
    const ignoreRoleWatch = ref(false);
    const formModel = ref({
      name_admin: "",
      surname_admin: "",
      email_admin: "",
      password_admin: "",
      rol_admin: "cajero",
      id_office_admin: "",
      status_admin: true
    });
    const savingAdmin = ref(false);
    async function fetchAdmins() {
      loading.value = true;
      try {
        const data = await $fetch("/api/admins?orderBy=id_admin&orderMode=DESC", {
          headers: apiHeaders
        });
        if (data.status === 200) {
          admins.value = data.results || [];
        }
      } catch (e) {
        console.error("Error fetching admins:", e);
      } finally {
        loading.value = false;
      }
    }
    function getOfficeName(id) {
      if (!id || id == 0) return "Todas (Super)";
      const o = offices.value.find((off) => String(off.id_office) === String(id));
      return o ? decodeURIComponent(o.title_office || "").replace(/\+/g, " ") : `Sucursal ${id}`;
    }
    const totalAdmins = computed(() => admins.value.length);
    const activeAdmins = computed(() => admins.value.filter((a) => a.status_admin == 1).length);
    const roleStats = computed(() => {
      const stats = {};
      admins.value.forEach((a) => {
        stats[a.rol_admin] = (stats[a.rol_admin] || 0) + 1;
      });
      return stats;
    });
    const filteredAdmins = computed(() => {
      return admins.value.filter((a) => {
        const name = decode(a.name_admin || "") + " " + decode(a.surname_admin || "");
        const email = decode(a.email_admin || "");
        const query = search.value.toLowerCase();
        return name.toLowerCase().includes(query) || email.toLowerCase().includes(query) || (a.rol_admin || "").toLowerCase().includes(query);
      });
    });
    const paginatedAdmins = computed(() => {
      const start = (page.value - 1) * itemsPerPage;
      return filteredAdmins.value.slice(start, start + itemsPerPage);
    });
    function decode(s) {
      if (!s) return "-";
      return decodeURIComponent(s).replace(/\+/g, " ");
    }
    function openResetPassword(admin) {
      resetPasswordAdminId.value = admin.id_admin;
      newPassword.value = "";
      isResetPasswordOpen.value = true;
    }
    async function handleResetPassword() {
      if (!newPassword.value || newPassword.value.length < 4) {
        alert("Ingresa una contraseña de al menos 4 caracteres");
        return;
      }
      resettingPassword.value = true;
      try {
        const body = new URLSearchParams();
        body.append("password_admin", newPassword.value);
        const res = await $fetch("/api/admins", {
          method: "PUT",
          headers: { ...apiHeaders, "Content-Type": "application/x-www-form-urlencoded" },
          query: { id: resetPasswordAdminId.value, nameId: "id_admin", token: "no", except: "id_admin" },
          body: body.toString()
        });
        if (res.status === 200) {
          toast.add({ title: "Contraseña actualizada correctamente", color: "success" });
          isResetPasswordOpen.value = false;
        } else {
          toast.add({ title: "Error al actualizar contraseña", color: "error" });
        }
      } catch {
        toast.add({ title: "Error de red al actualizar contraseña", color: "error" });
      } finally {
        resettingPassword.value = false;
      }
    }
    watch(() => formModel.value.rol_admin, (newRole) => {
      if (ignoreRoleWatch.value) return;
      Object.keys(permForm.value).forEach((k) => {
        permForm.value[k] = false;
      });
      if (newRole === "superadmin" || newRole === "admin") {
        Object.keys(permForm.value).forEach((k) => {
          permForm.value[k] = true;
        });
      } else if (newRole === "cajero") {
        permForm.value.pos = true;
        permForm.value.caja = true;
        permForm.value.mi_inventario = true;
      } else if (newRole === "vendedor") {
        permForm.value.pos = true;
        permForm.value.ordenes = true;
        permForm.value.ventas = true;
        permForm.value.caja = true;
        permForm.value.gastos = true;
        permForm.value.mi_inventario = true;
        permForm.value.solicitar_inventario = true;
        permForm.value.reportes = true;
      } else if (newRole === "despachador") {
        permForm.value.almacen = true;
        permForm.value.mi_inventario = true;
      } else if (newRole.startsWith("lab_")) {
        permForm.value.almacen = true;
        permForm.value.mi_inventario = true;
      }
    });
    function openCreate() {
      ignoreRoleWatch.value = true;
      selectedAdmin.value = null;
      formModel.value = {
        name_admin: "",
        surname_admin: "",
        email_admin: "",
        password_admin: "",
        rol_admin: "cajero",
        id_office_admin: "",
        status_admin: true
      };
      Object.keys(permForm.value).forEach((k) => {
        permForm.value[k] = false;
      });
      permForm.value.pos = true;
      permForm.value.caja = true;
      permForm.value.mi_inventario = true;
      isSlideoverOpen.value = true;
      setTimeout(() => {
        ignoreRoleWatch.value = false;
      }, 100);
    }
    function openEdit(admin) {
      ignoreRoleWatch.value = true;
      selectedAdmin.value = admin;
      formModel.value = {
        name_admin: decode(admin.name_admin),
        surname_admin: decode(admin.surname_admin),
        email_admin: decode(admin.email_admin),
        password_admin: "",
        // Keep blank
        rol_admin: admin.rol_admin || "cajero",
        id_office_admin: admin.id_office_admin ? String(admin.id_office_admin) : "",
        status_admin: admin.status_admin == 1
      };
      let perms = {};
      try {
        perms = typeof admin.permissions_admin === "string" ? JSON.parse(decodeURIComponent(admin.permissions_admin)) : admin.permissions_admin || {};
      } catch {
        perms = {};
      }
      Object.keys(permForm.value).forEach((k) => {
        permForm.value[k] = perms[k] === "on";
      });
      isSlideoverOpen.value = true;
      setTimeout(() => {
        ignoreRoleWatch.value = false;
      }, 100);
    }
    async function handleSaveAdmin() {
      if (!formModel.value.name_admin || !formModel.value.email_admin) {
        alert("Por favor ingresa nombre y correo");
        return;
      }
      savingAdmin.value = true;
      try {
        const isEdit = !!selectedAdmin.value;
        const body = new URLSearchParams();
        body.append("name_admin", formModel.value.name_admin);
        body.append("surname_admin", formModel.value.surname_admin);
        body.append("email_admin", formModel.value.email_admin);
        if (formModel.value.password_admin) {
          body.append("password_admin", formModel.value.password_admin);
        }
        body.append("rol_admin", formModel.value.rol_admin);
        body.append("id_office_admin", formModel.value.id_office_admin || "0");
        body.append("status_admin", formModel.value.status_admin ? "1" : "0");
        const resultObj = {};
        Object.entries(permForm.value).forEach(([key, val]) => {
          resultObj[key] = val ? "on" : "off";
        });
        body.append("permissions_admin", JSON.stringify(resultObj));
        let url = "/api/admins";
        let method = "POST";
        const queryParams = {
          token: "no",
          except: "id_admin"
        };
        if (isEdit) {
          method = "PUT";
          queryParams.id = selectedAdmin.value.id_admin;
          queryParams.nameId = "id_admin";
        }
        const res = await $fetch(url, {
          method,
          headers: { ...apiHeaders, "Content-Type": "application/x-www-form-urlencoded" },
          query: queryParams,
          body: body.toString()
        });
        if (res.status === 200) {
          toast.add({ title: isEdit ? "Administrador actualizado" : "Administrador creado", color: "success" });
          isSlideoverOpen.value = false;
          await fetchAdmins();
        } else {
          toast.add({ title: res.results || "Error al guardar", color: "error" });
        }
      } catch {
        toast.add({ title: "Error de red", color: "error" });
      } finally {
        savingAdmin.value = false;
      }
    }
    async function handleDelete(admin) {
      if (!confirm(`¿Eliminar al administrador ${decode(admin.name_admin)}?`)) return;
      try {
        const res = await $fetch("/api/admins", {
          method: "DELETE",
          headers: apiHeaders,
          query: { id: admin.id_admin, nameId: "id_admin", token: "no", except: "id_admin" }
        });
        if (res.status === 200) {
          toast.add({ title: "Eliminado correctamente", color: "success" });
          await fetchAdmins();
        } else {
          toast.add({ title: res.results || "Error al eliminar", color: "error" });
        }
      } catch {
        toast.add({ title: "Error de conexión", color: "error" });
      }
    }
    function handleExportCSV() {
      if (admins.value.length === 0) return;
      const headers = ["ID", "Nombre", "Apellido", "Email", "Rol", "Sucursal", "Estado"];
      const rows = admins.value.map((a) => [
        a.id_admin,
        decode(a.name_admin),
        decode(a.surname_admin),
        decode(a.email_admin),
        a.rol_admin,
        getOfficeName(a.id_office_admin),
        a.status_admin == 1 ? "Activo" : "Inactivo"
      ]);
      const csvContent = "\uFEFFsep=;\n" + [headers.join(";"), ...rows.map((r) => r.map((v) => `"${String(v).replace(/"/g, '""')}"`).join(";"))].join("\n");
      const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const link = (void 0).createElement("a");
      link.setAttribute("href", url);
      link.setAttribute("download", `export_admins_${(/* @__PURE__ */ new Date()).toISOString().split("T")[0]}.csv`);
      (void 0).body.appendChild(link);
      link.click();
      (void 0).body.removeChild(link);
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_UCard = _sfc_main$1;
      const _component_UIcon = _sfc_main$h;
      const _component_UInput = _sfc_main$6;
      const _component_UButton = _sfc_main$c;
      const _component_UAvatar = _sfc_main$f;
      const _component_UBadge = _sfc_main$2;
      const _component_UButtonGroup = resolveComponent("UButtonGroup");
      const _component_USlideover = _sfc_main$3;
      const _component_UFormField = _sfc_main$4;
      const _component_UModal = _sfc_main$5;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">`);
      _push(ssrRenderComponent(_component_UCard, { class: "bg-gradient-to-br from-indigo-500 to-indigo-600 border-0 shadow-md" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex justify-between items-center text-white"${_scopeId}><div${_scopeId}><p class="text-indigo-100 text-[10px] font-bold uppercase tracking-wider"${_scopeId}>Total Administradores</p><h2 class="text-3xl font-black mt-1"${_scopeId}>${ssrInterpolate(totalAdmins.value)}</h2></div>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-users",
              class: "w-10 h-10 text-white/30"
            }, null, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "flex justify-between items-center text-white" }, [
                createVNode("div", null, [
                  createVNode("p", { class: "text-indigo-100 text-[10px] font-bold uppercase tracking-wider" }, "Total Administradores"),
                  createVNode("h2", { class: "text-3xl font-black mt-1" }, toDisplayString(totalAdmins.value), 1)
                ]),
                createVNode(_component_UIcon, {
                  name: "i-lucide-users",
                  class: "w-10 h-10 text-white/30"
                })
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UCard, { class: "bg-gradient-to-br from-emerald-500 to-emerald-600 border-0 shadow-md" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex justify-between items-center text-white"${_scopeId}><div${_scopeId}><p class="text-emerald-100 text-[10px] font-bold uppercase tracking-wider"${_scopeId}>Activos en el Sistema</p><h2 class="text-3xl font-black mt-1"${_scopeId}>${ssrInterpolate(activeAdmins.value)}</h2></div>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-user-check",
              class: "w-10 h-10 text-white/30"
            }, null, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "flex justify-between items-center text-white" }, [
                createVNode("div", null, [
                  createVNode("p", { class: "text-emerald-100 text-[10px] font-bold uppercase tracking-wider" }, "Activos en el Sistema"),
                  createVNode("h2", { class: "text-3xl font-black mt-1" }, toDisplayString(activeAdmins.value), 1)
                ]),
                createVNode(_component_UIcon, {
                  name: "i-lucide-user-check",
                  class: "w-10 h-10 text-white/30"
                })
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UCard, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider"${_scopeId}>Superadmins / Admins</p><h2 class="text-2xl font-black text-slate-800 dark:text-white mt-1"${_scopeId}>${ssrInterpolate((roleStats.value["superadmin"] || 0) + (roleStats.value["admin"] || 0))}</h2><p class="text-[10px] text-slate-400 mt-1"${_scopeId}>Personal de control central</p>`);
          } else {
            return [
              createVNode("p", { class: "text-slate-500 text-[10px] font-bold uppercase tracking-wider" }, "Superadmins / Admins"),
              createVNode("h2", { class: "text-2xl font-black text-slate-800 dark:text-white mt-1" }, toDisplayString((roleStats.value["superadmin"] || 0) + (roleStats.value["admin"] || 0)), 1),
              createVNode("p", { class: "text-[10px] text-slate-400 mt-1" }, "Personal de control central")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UCard, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider"${_scopeId}>Operadores / Cajeros</p><h2 class="text-2xl font-black text-slate-800 dark:text-white mt-1"${_scopeId}>${ssrInterpolate((roleStats.value["cajero"] || 0) + (roleStats.value["vendedor"] || 0) + (roleStats.value["lab_worker"] || 0))}</h2><p class="text-[10px] text-slate-400 mt-1"${_scopeId}>Personal operativo</p>`);
          } else {
            return [
              createVNode("p", { class: "text-slate-500 text-[10px] font-bold uppercase tracking-wider" }, "Operadores / Cajeros"),
              createVNode("h2", { class: "text-2xl font-black text-slate-800 dark:text-white mt-1" }, toDisplayString((roleStats.value["cajero"] || 0) + (roleStats.value["vendedor"] || 0) + (roleStats.value["lab_worker"] || 0)), 1),
              createVNode("p", { class: "text-[10px] text-slate-400 mt-1" }, "Personal operativo")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-4 rounded-xl shadow-sm"><div><h1 class="text-base font-extrabold text-slate-800 dark:text-white">Gestión Avanzada de Administradores</h1><p class="text-[11px] text-slate-400 mt-0.5">Control de cuentas de acceso, roles y asignación de permisos interactivos.</p></div><div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">`);
      _push(ssrRenderComponent(_component_UInput, {
        modelValue: search.value,
        "onUpdate:modelValue": ($event) => search.value = $event,
        icon: "i-lucide-search",
        placeholder: "Buscar administrador...",
        size: "sm",
        class: "w-full sm:w-48"
      }, null, _parent));
      _push(ssrRenderComponent(_component_UButton, {
        icon: "i-lucide-download",
        color: "neutral",
        variant: "outline",
        size: "sm",
        onClick: handleExportCSV
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Exportar`);
          } else {
            return [
              createTextVNode("Exportar")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UButton, {
        icon: "i-lucide-user-plus",
        color: "primary",
        size: "sm",
        class: "active:scale-95 duration-100 transition-transform font-bold",
        onClick: openCreate
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Crear Admin`);
          } else {
            return [
              createTextVNode("Crear Admin")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">`);
      if (loading.value) {
        _push(`<div class="flex justify-center py-12">`);
        _push(ssrRenderComponent(_component_UIcon, {
          name: "i-lucide-loader-2",
          class: "animate-spin w-8 h-8 text-indigo-500"
        }, null, _parent));
        _push(`</div>`);
      } else if (filteredAdmins.value.length === 0) {
        _push(`<div class="text-center py-12 text-slate-400"> No se encontraron cuentas de administradores. </div>`);
      } else {
        _push(`<div class="overflow-x-auto"><table class="w-full text-left border-collapse text-sm"><thead><tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-500 text-xs font-bold uppercase"><th class="px-4 py-3">Administrador</th><th class="px-4 py-3">Contacto (Email)</th><th class="px-4 py-3">Rol del Sistema</th><th class="px-4 py-3">Sucursal Asignada</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Acciones</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300"><!--[-->`);
        ssrRenderList(paginatedAdmins.value, (a) => {
          _push(`<tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"><td class="px-4 py-3 flex items-center gap-3">`);
          _push(ssrRenderComponent(_component_UAvatar, {
            src: a.img_admin ? decode(a.img_admin) : "",
            alt: decode(a.name_admin),
            size: "md",
            class: "border shadow-xs shrink-0"
          }, null, _parent));
          _push(`<div class="min-w-0"><p class="font-bold text-slate-950 dark:text-white text-sm">${ssrInterpolate(decode(a.name_admin))} ${ssrInterpolate(decode(a.surname_admin))}</p><p class="text-xs text-slate-400 font-mono">ID: ${ssrInterpolate(a.id_admin)}</p></div></td><td class="px-4 py-3"><p class="text-sm font-medium text-slate-700 dark:text-slate-300">${ssrInterpolate(decode(a.email_admin))}</p></td><td class="px-4 py-3">`);
          _push(ssrRenderComponent(_component_UBadge, {
            color: ["superadmin", "admin"].includes(a.rol_admin) ? "rose" : "indigo",
            variant: "subtle",
            size: "xs",
            class: "uppercase font-bold"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`${ssrInterpolate(a.rol_admin)}`);
              } else {
                return [
                  createTextVNode(toDisplayString(a.rol_admin), 1)
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`</td><td class="px-4 py-3"><span class="text-sm font-semibold text-slate-500 dark:text-slate-400">${ssrInterpolate(getOfficeName(a.id_office_admin))}</span></td><td class="px-4 py-3"><button type="button" class="${ssrRenderClass([
            a.status_admin == 1 ? "bg-emerald-500" : "bg-slate-300 dark:bg-slate-800",
            "relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
          ])}"><span class="${ssrRenderClass([
            a.status_admin == 1 ? "translate-x-5" : "translate-x-0",
            "pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
          ])}"></span></button></td><td class="px-4 py-3 text-right"><div class="flex items-center justify-end gap-1.5">`);
          _push(ssrRenderComponent(_component_UButtonGroup, { size: "xs" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(_component_UButton, {
                  icon: "i-lucide-key-round",
                  color: "neutral",
                  variant: "soft",
                  title: "Cambiar Contraseña",
                  onClick: ($event) => openResetPassword(a)
                }, null, _parent2, _scopeId));
                _push2(ssrRenderComponent(_component_UButton, {
                  icon: "i-lucide-edit",
                  color: "neutral",
                  variant: "ghost",
                  title: "Editar cuenta y permisos",
                  onClick: ($event) => openEdit(a)
                }, null, _parent2, _scopeId));
                _push2(ssrRenderComponent(_component_UButton, {
                  icon: "i-lucide-trash",
                  color: "rose",
                  variant: "ghost",
                  title: "Eliminar",
                  onClick: ($event) => handleDelete(a)
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode(_component_UButton, {
                    icon: "i-lucide-key-round",
                    color: "neutral",
                    variant: "soft",
                    title: "Cambiar Contraseña",
                    onClick: ($event) => openResetPassword(a)
                  }, null, 8, ["onClick"]),
                  createVNode(_component_UButton, {
                    icon: "i-lucide-edit",
                    color: "neutral",
                    variant: "ghost",
                    title: "Editar cuenta y permisos",
                    onClick: ($event) => openEdit(a)
                  }, null, 8, ["onClick"]),
                  createVNode(_component_UButton, {
                    icon: "i-lucide-trash",
                    color: "rose",
                    variant: "ghost",
                    title: "Eliminar",
                    onClick: ($event) => handleDelete(a)
                  }, null, 8, ["onClick"])
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`</div></td></tr>`);
        });
        _push(`<!--]--></tbody></table></div>`);
      }
      _push(`</div>`);
      _push(ssrRenderComponent(_component_USlideover, {
        open: isSlideoverOpen.value,
        "onUpdate:open": ($event) => isSlideoverOpen.value = $event,
        title: selectedAdmin.value ? "Editar Administrador y Permisos" : "Nuevo Administrador",
        class: "z-50"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4 p-1"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UFormField, { label: "Nombre(s) *" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: formModel.value.name_admin,
                    "onUpdate:modelValue": ($event) => formModel.value.name_admin = $event,
                    placeholder: "Ej. Juan",
                    class: "w-full text-sm"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: formModel.value.name_admin,
                      "onUpdate:modelValue": ($event) => formModel.value.name_admin = $event,
                      placeholder: "Ej. Juan",
                      class: "w-full text-sm"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, { label: "Apellido(s)" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: formModel.value.surname_admin,
                    "onUpdate:modelValue": ($event) => formModel.value.surname_admin = $event,
                    placeholder: "Ej. Pérez",
                    class: "w-full text-sm"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: formModel.value.surname_admin,
                      "onUpdate:modelValue": ($event) => formModel.value.surname_admin = $event,
                      placeholder: "Ej. Pérez",
                      class: "w-full text-sm"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, { label: "Correo Electrónico *" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: formModel.value.email_admin,
                    "onUpdate:modelValue": ($event) => formModel.value.email_admin = $event,
                    type: "email",
                    placeholder: "Ej. juan@unitech.com",
                    class: "w-full text-sm"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: formModel.value.email_admin,
                      "onUpdate:modelValue": ($event) => formModel.value.email_admin = $event,
                      type: "email",
                      placeholder: "Ej. juan@unitech.com",
                      class: "w-full text-sm"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, {
              label: selectedAdmin.value ? "Contraseña (Dejar en blanco para mantener)" : "Contraseña *"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: formModel.value.password_admin,
                    "onUpdate:modelValue": ($event) => formModel.value.password_admin = $event,
                    type: "password",
                    placeholder: "••••••••",
                    class: "w-full text-sm"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: formModel.value.password_admin,
                      "onUpdate:modelValue": ($event) => formModel.value.password_admin = $event,
                      type: "password",
                      placeholder: "••••••••",
                      class: "w-full text-sm"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, { label: "Rol del Sistema" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<select class="block w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1.5 focus:outline-none focus:border-indigo-500"${_scopeId2}><option value="superadmin"${ssrIncludeBooleanAttr(Array.isArray(formModel.value.rol_admin) ? ssrLooseContain(formModel.value.rol_admin, "superadmin") : ssrLooseEqual(formModel.value.rol_admin, "superadmin")) ? " selected" : ""}${_scopeId2}>Super Administrador</option><option value="admin"${ssrIncludeBooleanAttr(Array.isArray(formModel.value.rol_admin) ? ssrLooseContain(formModel.value.rol_admin, "admin") : ssrLooseEqual(formModel.value.rol_admin, "admin")) ? " selected" : ""}${_scopeId2}>Administrador</option><option value="cajero"${ssrIncludeBooleanAttr(Array.isArray(formModel.value.rol_admin) ? ssrLooseContain(formModel.value.rol_admin, "cajero") : ssrLooseEqual(formModel.value.rol_admin, "cajero")) ? " selected" : ""}${_scopeId2}>Cajero / Caja</option><option value="vendedor"${ssrIncludeBooleanAttr(Array.isArray(formModel.value.rol_admin) ? ssrLooseContain(formModel.value.rol_admin, "vendedor") : ssrLooseEqual(formModel.value.rol_admin, "vendedor")) ? " selected" : ""}${_scopeId2}>Vendedor / Ventas</option><option value="despachador"${ssrIncludeBooleanAttr(Array.isArray(formModel.value.rol_admin) ? ssrLooseContain(formModel.value.rol_admin, "despachador") : ssrLooseEqual(formModel.value.rol_admin, "despachador")) ? " selected" : ""}${_scopeId2}>Despachador</option><option value="lab_admin"${ssrIncludeBooleanAttr(Array.isArray(formModel.value.rol_admin) ? ssrLooseContain(formModel.value.rol_admin, "lab_admin") : ssrLooseEqual(formModel.value.rol_admin, "lab_admin")) ? " selected" : ""}${_scopeId2}>Admin Laboratorio</option><option value="lab_worker"${ssrIncludeBooleanAttr(Array.isArray(formModel.value.rol_admin) ? ssrLooseContain(formModel.value.rol_admin, "lab_worker") : ssrLooseEqual(formModel.value.rol_admin, "lab_worker")) ? " selected" : ""}${_scopeId2}>Operador Laboratorio</option><option value="lab_calidad"${ssrIncludeBooleanAttr(Array.isArray(formModel.value.rol_admin) ? ssrLooseContain(formModel.value.rol_admin, "lab_calidad") : ssrLooseEqual(formModel.value.rol_admin, "lab_calidad")) ? " selected" : ""}${_scopeId2}>Control Calidad</option></select>`);
                } else {
                  return [
                    withDirectives(createVNode("select", {
                      "onUpdate:modelValue": ($event) => formModel.value.rol_admin = $event,
                      class: "block w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1.5 focus:outline-none focus:border-indigo-500"
                    }, [
                      createVNode("option", { value: "superadmin" }, "Super Administrador"),
                      createVNode("option", { value: "admin" }, "Administrador"),
                      createVNode("option", { value: "cajero" }, "Cajero / Caja"),
                      createVNode("option", { value: "vendedor" }, "Vendedor / Ventas"),
                      createVNode("option", { value: "despachador" }, "Despachador"),
                      createVNode("option", { value: "lab_admin" }, "Admin Laboratorio"),
                      createVNode("option", { value: "lab_worker" }, "Operador Laboratorio"),
                      createVNode("option", { value: "lab_calidad" }, "Control Calidad")
                    ], 8, ["onUpdate:modelValue"]), [
                      [vModelSelect, formModel.value.rol_admin]
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_UFormField, { label: "Sucursal Asignada" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<select class="block w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1.5 focus:outline-none focus:border-indigo-500"${_scopeId2}><option value=""${ssrIncludeBooleanAttr(Array.isArray(formModel.value.id_office_admin) ? ssrLooseContain(formModel.value.id_office_admin, "") : ssrLooseEqual(formModel.value.id_office_admin, "")) ? " selected" : ""}${_scopeId2}>Todas (Super)</option><!--[-->`);
                  ssrRenderList(offices.value, (o) => {
                    _push3(`<option${ssrRenderAttr("value", String(o.id_office))}${ssrIncludeBooleanAttr(Array.isArray(formModel.value.id_office_admin) ? ssrLooseContain(formModel.value.id_office_admin, String(o.id_office)) : ssrLooseEqual(formModel.value.id_office_admin, String(o.id_office))) ? " selected" : ""}${_scopeId2}>${ssrInterpolate(decodeURIComponent(o.title_office || "").replace(/\+/g, " "))}</option>`);
                  });
                  _push3(`<!--]--></select>`);
                } else {
                  return [
                    withDirectives(createVNode("select", {
                      "onUpdate:modelValue": ($event) => formModel.value.id_office_admin = $event,
                      class: "block w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1.5 focus:outline-none focus:border-indigo-500"
                    }, [
                      createVNode("option", { value: "" }, "Todas (Super)"),
                      (openBlock(true), createBlock(Fragment, null, renderList(offices.value, (o) => {
                        return openBlock(), createBlock("option", {
                          key: o.id_office,
                          value: String(o.id_office)
                        }, toDisplayString(decodeURIComponent(o.title_office || "").replace(/\+/g, " ")), 9, ["value"]);
                      }), 128))
                    ], 8, ["onUpdate:modelValue"]), [
                      [vModelSelect, formModel.value.id_office_admin]
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900 rounded-lg"${_scopeId}><span class="text-sm font-bold text-slate-500 uppercase"${_scopeId}>Estado Cuenta</span><button type="button" class="${ssrRenderClass([
              formModel.value.status_admin ? "bg-emerald-500" : "bg-slate-300 dark:bg-slate-800",
              "relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
            ])}"${_scopeId}><span class="${ssrRenderClass([
              formModel.value.status_admin ? "translate-x-5" : "translate-x-0",
              "pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
            ])}"${_scopeId}></span></button></div><div class="border-t border-slate-200 dark:border-slate-800 pt-4 mt-4 space-y-3"${_scopeId}><div class="flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UIcon, {
              name: "i-lucide-shield-check",
              class: "w-4.5 h-4.5"
            }, null, _parent2, _scopeId));
            _push2(`<h3 class="text-sm font-black uppercase tracking-wider"${_scopeId}>Asignación Directa de Permisos</h3></div><p class="text-xs text-slate-500 dark:text-slate-400"${_scopeId}>Selecciona los módulos o pantallas que esta cuenta tendrá permitido visualizar e interactuar.</p><div class="grid grid-cols-1 gap-1 max-h-60 overflow-y-auto pr-1 border border-slate-100 dark:border-slate-800/60 rounded-lg p-2 bg-slate-50/50 dark:bg-slate-900/40"${_scopeId}><!--[-->`);
            ssrRenderList(permForm.value, (val, key) => {
              _push2(`<div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/40 last:border-b-0"${_scopeId}><span class="text-sm font-bold text-slate-700 dark:text-slate-300 capitalize"${_scopeId}>${ssrInterpolate(key.replace("_", " "))}</span><button type="button" class="${ssrRenderClass([
                permForm.value[key] ? "bg-emerald-500" : "bg-slate-300 dark:bg-slate-800",
                "relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
              ])}"${_scopeId}><span class="${ssrRenderClass([
                permForm.value[key] ? "translate-x-5" : "translate-x-0",
                "pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
              ])}"${_scopeId}></span></button></div>`);
            });
            _push2(`<!--]--></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4 p-1" }, [
                createVNode(_component_UFormField, { label: "Nombre(s) *" }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: formModel.value.name_admin,
                      "onUpdate:modelValue": ($event) => formModel.value.name_admin = $event,
                      placeholder: "Ej. Juan",
                      class: "w-full text-sm"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, { label: "Apellido(s)" }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: formModel.value.surname_admin,
                      "onUpdate:modelValue": ($event) => formModel.value.surname_admin = $event,
                      placeholder: "Ej. Pérez",
                      class: "w-full text-sm"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, { label: "Correo Electrónico *" }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: formModel.value.email_admin,
                      "onUpdate:modelValue": ($event) => formModel.value.email_admin = $event,
                      type: "email",
                      placeholder: "Ej. juan@unitech.com",
                      class: "w-full text-sm"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, {
                  label: selectedAdmin.value ? "Contraseña (Dejar en blanco para mantener)" : "Contraseña *"
                }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: formModel.value.password_admin,
                      "onUpdate:modelValue": ($event) => formModel.value.password_admin = $event,
                      type: "password",
                      placeholder: "••••••••",
                      class: "w-full text-sm"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                }, 8, ["label"]),
                createVNode(_component_UFormField, { label: "Rol del Sistema" }, {
                  default: withCtx(() => [
                    withDirectives(createVNode("select", {
                      "onUpdate:modelValue": ($event) => formModel.value.rol_admin = $event,
                      class: "block w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1.5 focus:outline-none focus:border-indigo-500"
                    }, [
                      createVNode("option", { value: "superadmin" }, "Super Administrador"),
                      createVNode("option", { value: "admin" }, "Administrador"),
                      createVNode("option", { value: "cajero" }, "Cajero / Caja"),
                      createVNode("option", { value: "vendedor" }, "Vendedor / Ventas"),
                      createVNode("option", { value: "despachador" }, "Despachador"),
                      createVNode("option", { value: "lab_admin" }, "Admin Laboratorio"),
                      createVNode("option", { value: "lab_worker" }, "Operador Laboratorio"),
                      createVNode("option", { value: "lab_calidad" }, "Control Calidad")
                    ], 8, ["onUpdate:modelValue"]), [
                      [vModelSelect, formModel.value.rol_admin]
                    ])
                  ]),
                  _: 1
                }),
                createVNode(_component_UFormField, { label: "Sucursal Asignada" }, {
                  default: withCtx(() => [
                    withDirectives(createVNode("select", {
                      "onUpdate:modelValue": ($event) => formModel.value.id_office_admin = $event,
                      class: "block w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1.5 focus:outline-none focus:border-indigo-500"
                    }, [
                      createVNode("option", { value: "" }, "Todas (Super)"),
                      (openBlock(true), createBlock(Fragment, null, renderList(offices.value, (o) => {
                        return openBlock(), createBlock("option", {
                          key: o.id_office,
                          value: String(o.id_office)
                        }, toDisplayString(decodeURIComponent(o.title_office || "").replace(/\+/g, " ")), 9, ["value"]);
                      }), 128))
                    ], 8, ["onUpdate:modelValue"]), [
                      [vModelSelect, formModel.value.id_office_admin]
                    ])
                  ]),
                  _: 1
                }),
                createVNode("div", { class: "flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900 rounded-lg" }, [
                  createVNode("span", { class: "text-sm font-bold text-slate-500 uppercase" }, "Estado Cuenta"),
                  createVNode("button", {
                    type: "button",
                    onClick: ($event) => formModel.value.status_admin = !formModel.value.status_admin,
                    class: [
                      formModel.value.status_admin ? "bg-emerald-500" : "bg-slate-300 dark:bg-slate-800",
                      "relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                    ]
                  }, [
                    createVNode("span", {
                      class: [
                        formModel.value.status_admin ? "translate-x-5" : "translate-x-0",
                        "pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                      ]
                    }, null, 2)
                  ], 10, ["onClick"])
                ]),
                createVNode("div", { class: "border-t border-slate-200 dark:border-slate-800 pt-4 mt-4 space-y-3" }, [
                  createVNode("div", { class: "flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400" }, [
                    createVNode(_component_UIcon, {
                      name: "i-lucide-shield-check",
                      class: "w-4.5 h-4.5"
                    }),
                    createVNode("h3", { class: "text-sm font-black uppercase tracking-wider" }, "Asignación Directa de Permisos")
                  ]),
                  createVNode("p", { class: "text-xs text-slate-500 dark:text-slate-400" }, "Selecciona los módulos o pantallas que esta cuenta tendrá permitido visualizar e interactuar."),
                  createVNode("div", { class: "grid grid-cols-1 gap-1 max-h-60 overflow-y-auto pr-1 border border-slate-100 dark:border-slate-800/60 rounded-lg p-2 bg-slate-50/50 dark:bg-slate-900/40" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(permForm.value, (val, key) => {
                      return openBlock(), createBlock("div", {
                        key,
                        class: "flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/40 last:border-b-0"
                      }, [
                        createVNode("span", { class: "text-sm font-bold text-slate-700 dark:text-slate-300 capitalize" }, toDisplayString(key.replace("_", " ")), 1),
                        createVNode("button", {
                          type: "button",
                          onClick: ($event) => permForm.value[key] = !permForm.value[key],
                          class: [
                            permForm.value[key] ? "bg-emerald-500" : "bg-slate-300 dark:bg-slate-800",
                            "relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                          ]
                        }, [
                          createVNode("span", {
                            class: [
                              permForm.value[key] ? "translate-x-5" : "translate-x-0",
                              "pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            ]
                          }, null, 2)
                        ], 10, ["onClick"])
                      ]);
                    }), 128))
                  ])
                ])
              ])
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex justify-end gap-2 p-4 border-t w-full bg-slate-50 dark:bg-slate-900"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              color: "neutral",
              variant: "ghost",
              size: "sm",
              onClick: ($event) => isSlideoverOpen.value = false
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
              size: "sm",
              loading: savingAdmin.value,
              onClick: handleSaveAdmin
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Guardar Administrador`);
                } else {
                  return [
                    createTextVNode("Guardar Administrador")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "flex justify-end gap-2 p-4 border-t w-full bg-slate-50 dark:bg-slate-900" }, [
                createVNode(_component_UButton, {
                  color: "neutral",
                  variant: "ghost",
                  size: "sm",
                  onClick: ($event) => isSlideoverOpen.value = false
                }, {
                  default: withCtx(() => [
                    createTextVNode("Cancelar")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(_component_UButton, {
                  color: "primary",
                  size: "sm",
                  loading: savingAdmin.value,
                  onClick: handleSaveAdmin
                }, {
                  default: withCtx(() => [
                    createTextVNode("Guardar Administrador")
                  ]),
                  _: 1
                }, 8, ["loading"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_UModal, {
        open: isResetPasswordOpen.value,
        "onUpdate:open": ($event) => isResetPasswordOpen.value = $event,
        title: "Establecer Nueva Contraseña"
      }, {
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4 p-1"${_scopeId}><p class="text-xs text-slate-500"${_scopeId}>Ingresa la nueva clave secreta para esta cuenta. Se guardará de forma encriptada en la base de datos.</p>`);
            _push2(ssrRenderComponent(_component_UFormField, { label: "Nueva Contraseña" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_component_UInput, {
                    modelValue: newPassword.value,
                    "onUpdate:modelValue": ($event) => newPassword.value = $event,
                    type: "password",
                    placeholder: "Mínimo 4 caracteres...",
                    class: "w-full"
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_component_UInput, {
                      modelValue: newPassword.value,
                      "onUpdate:modelValue": ($event) => newPassword.value = $event,
                      type: "password",
                      placeholder: "Mínimo 4 caracteres...",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4 p-1" }, [
                createVNode("p", { class: "text-xs text-slate-500" }, "Ingresa la nueva clave secreta para esta cuenta. Se guardará de forma encriptada en la base de datos."),
                createVNode(_component_UFormField, { label: "Nueva Contraseña" }, {
                  default: withCtx(() => [
                    createVNode(_component_UInput, {
                      modelValue: newPassword.value,
                      "onUpdate:modelValue": ($event) => newPassword.value = $event,
                      type: "password",
                      placeholder: "Mínimo 4 caracteres...",
                      class: "w-full"
                    }, null, 8, ["modelValue", "onUpdate:modelValue"])
                  ]),
                  _: 1
                })
              ])
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex justify-end gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_UButton, {
              color: "neutral",
              variant: "ghost",
              size: "sm",
              onClick: ($event) => isResetPasswordOpen.value = false
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
              size: "sm",
              loading: resettingPassword.value,
              onClick: handleResetPassword
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Confirmar Clave`);
                } else {
                  return [
                    createTextVNode("Confirmar Clave")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "flex justify-end gap-2" }, [
                createVNode(_component_UButton, {
                  color: "neutral",
                  variant: "ghost",
                  size: "sm",
                  onClick: ($event) => isResetPasswordOpen.value = false
                }, {
                  default: withCtx(() => [
                    createTextVNode("Cancelar")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(_component_UButton, {
                  color: "primary",
                  size: "sm",
                  loading: resettingPassword.value,
                  onClick: handleResetPassword
                }, {
                  default: withCtx(() => [
                    createTextVNode("Confirmar Clave")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/admins.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=admins-B8MaAm2i.mjs.map
