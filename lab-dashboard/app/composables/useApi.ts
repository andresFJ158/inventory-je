/**
 * Composable central para llamadas a la API del POS.
 *
 * Elimina la duplicación de `ajaxBase`, el token hardcodeado y el parseo
 * manual de respuestas que había en cada página. Toda la configuración
 * (base AJAX, base REST, token) viene de runtimeConfig.
 *
 * Uso:
 *   const api = useApi()
 *   const d = await api.ajax({ getCredits: 'ok', id_office: '3' })        // x-www-form-urlencoded
 *   const r = await api.ajaxForm(formData)                                // multipart (archivos)
 *   const o = await api.rest('/api/offices')                              // GET REST con token
 */

interface AjaxResult {
  status?: number
  message?: string
  results?: any
  [key: string]: any
}

export function useApi() {
  const config = useRuntimeConfig()
  const ajaxBase = config.public.ajaxBase as string
  const apiToken = config.public.apiToken as string

  const apiHeaders = { Authorization: apiToken }

  /** Normaliza la respuesta del backend (a veces devuelve string JSON). */
  function parse(res: any): AjaxResult {
    if (res == null) return { status: 0 }
    return typeof res === 'string' ? safeJson(res) : res
  }

  function safeJson(s: string): AjaxResult {
    try { return JSON.parse(s) } catch { return { status: 0, message: s } }
  }

  /** POST al endpoint AJAX como x-www-form-urlencoded. */
  async function ajax(params: Record<string, string | number | boolean>): Promise<AjaxResult> {
    const body = new URLSearchParams(
      Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))
    ).toString()
    const res = await $fetch<any>(ajaxBase, {
      method: 'POST',
      body,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    }).catch(() => null)
    return parse(res)
  }

  /** POST al endpoint AJAX como multipart/form-data (para subir archivos). */
  async function ajaxForm(fd: FormData): Promise<AjaxResult> {
    const res = await $fetch<any>(ajaxBase, { method: 'POST', body: fd }).catch(() => null)
    return parse(res)
  }

  /** Petición a la API REST con el token de autorización. */
  async function rest<T = any>(url: string, opts: Record<string, any> = {}): Promise<T | null> {
    return await $fetch<T>(url, {
      ...opts,
      headers: { ...apiHeaders, ...(opts.headers || {}) }
    }).catch(() => null)
  }

  return { ajaxBase, apiHeaders, ajax, ajaxForm, rest, parse }
}
