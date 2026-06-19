/**
 * Proxy REST con token solo en servidor para AJAX (no expuesto al bundle del cliente).
 */
export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const base = (config.apiInternalUrl as string).replace(/\/$/, '')
  // Mantener la ruta original (ej. /ajax/pos.ajax.php)
  const query = getRequestURL(event).search || ''
  
  // Limpiar query string duplicada si event.path ya la incluye
  const path = event.path.split('?')[0]
  const target = `${base}${path}${query}`

  const method = event.method
  const clientAuth = getRequestHeader(event, 'authorization')
  const cookie = getRequestHeader(event, 'cookie')
  const contentType = getRequestHeader(event, 'content-type')

  const headers: Record<string, string> = {}
  if (clientAuth) headers['Authorization'] = clientAuth
  else if (config.apiToken) headers['Authorization'] = config.apiToken as string
  
  if (cookie) headers['cookie'] = cookie
  if (contentType) headers['content-type'] = contentType

  console.log(`[Proxy AJAX] ${method} ${event.path} -> ${target}`)

  try {
    return await proxyRequest(event, target, {
      headers,
      fetchOptions: {
        method
      }
    })
  } catch (err: any) {
    if (err?.response) {
      setResponseStatus(event, err.response.status)
      return err.response._data || { status: err.response.status, results: 'Proxy error' }
    }
    console.error(`[Proxy AJAX ERROR] Failed to proxy to ${target}:`, err)
    throw err
  }
})
