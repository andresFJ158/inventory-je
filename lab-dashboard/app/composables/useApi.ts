/**
 * Composable central para llamadas a la API del POS.
 * El token REST se inyecta en servidor vía server/routes/api/[...path].ts
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
  const apiBase = config.public.apiBase as string

  function parse(res: any): AjaxResult {
    if (res == null) return { status: 0, message: 'Sin respuesta del servidor' }
    if (Array.isArray(res)) return { status: 200, results: res }
    if (typeof res === 'string') return safeJson(res)
    return res
  }

  function safeJson(s: string): AjaxResult {
    const t = s.trim()
    if (t === 'ok') return { status: 200, message: 'ok' }
    if (t.startsWith('error')) return { status: 400, message: t }
    try {
      const parsed = JSON.parse(t)
      if (Array.isArray(parsed)) return { status: 200, results: parsed }
      return parsed
    } catch {
      return { status: 0, message: s }
    }
  }

  async function ajax(params: Record<string, string | number | boolean>): Promise<AjaxResult> {
    const body = new URLSearchParams(
      Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))
    ).toString()
    const res = await $fetch<any>(ajaxBase, {
      method: 'POST',
      body,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      credentials: 'include'
    }).catch((e: any) => ({ status: 0, message: e?.message || 'Error de red' }))
    return parse(res)
  }

  async function ajaxForm(fd: FormData): Promise<AjaxResult> {
    const res = await $fetch<any>(ajaxBase, {
      method: 'POST',
      body: fd,
      credentials: 'include'
    }).catch((e: any) => ({ status: 0, message: e?.message || 'Error de red' }))
    return parse(res)
  }

  /** Petición REST — token agregado en proxy server-side. */
  async function rest<T = any>(path: string, opts: Record<string, any> = {}): Promise<T | null> {
    const url = path.startsWith('/api') ? path : `${apiBase}/${path.replace(/^\//, '')}`
    const auth = useAuthStore()
    const query = { ...(opts.query || {}) }
    if (auth.token && !query.token) {
      query.token = auth.token
      query.table = query.table || 'admins'
      query.suffix = query.suffix || 'admin'
    }
    return await $fetch<T>(url, {
      ...opts,
      query,
      credentials: 'include'
    }).catch((e) => {
      console.error('API REST Fetch error:', e.data || e.message)
      return e.data || { status: 0, results: e.message }
    })
  }

  return { ajaxBase, apiBase, ajax, ajaxForm, rest, parse, safeJson }
}
