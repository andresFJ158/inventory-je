import { a4 as useRuntimeConfig } from './server.mjs';

function useApi() {
  const config = useRuntimeConfig();
  const ajaxBase = config.public.ajaxBase;
  const apiToken = config.public.apiToken;
  const apiHeaders = { Authorization: apiToken };
  function parse(res) {
    if (res == null) return { status: 0 };
    return typeof res === "string" ? safeJson(res) : res;
  }
  function safeJson(s) {
    try {
      return JSON.parse(s);
    } catch {
      return { status: 0, message: s };
    }
  }
  async function ajax(params) {
    const body = new URLSearchParams(
      Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))
    ).toString();
    const res = await $fetch(ajaxBase, {
      method: "POST",
      body,
      headers: { "Content-Type": "application/x-www-form-urlencoded" }
    }).catch(() => null);
    return parse(res);
  }
  async function ajaxForm(fd) {
    const res = await $fetch(ajaxBase, { method: "POST", body: fd }).catch(() => null);
    return parse(res);
  }
  async function rest(url, opts = {}) {
    return await $fetch(url, {
      ...opts,
      headers: { ...apiHeaders, ...opts.headers || {} }
    }).catch(() => null);
  }
  return { ajaxBase, apiHeaders, ajax, ajaxForm, rest, parse };
}
function decodeText(s) {
  if (!s) return "";
  try {
    return decodeURIComponent(String(s)).replace(/\+/g, " ");
  } catch {
    return String(s).replace(/\+/g, " ");
  }
}
function formatBob(v) {
  const n = typeof v === "string" ? parseFloat(v) : v ?? 0;
  return new Intl.NumberFormat("es-BO", { style: "currency", currency: "BOB" }).format(Number.isFinite(n) ? n : 0);
}

export { decodeText as d, formatBob as f, useApi as u };
//# sourceMappingURL=format-B65qMLDZ.mjs.map
