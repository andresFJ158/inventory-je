/**
 * Proxy REST con token solo en servidor (no expuesto al bundle del cliente).
 */
export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const base = (config.apiInternalUrl as string).replace(/\/$/, '')
  const suffix = event.path.replace(/^\/api\/?/, '')
  const query = getRequestURL(event).search || ''
  const target = `${base}/${suffix}${query}`

  const method = event.method
  const headers: Record<string, string> = {
    Authorization: config.apiToken as string
  }

  if (method === 'GET' || method === 'HEAD') {
    return await proxyRequest(event, target, { headers })
  }

  const contentType = getRequestHeader(event, 'content-type') || ''
  headers['Content-Type'] = contentType

  const body = await readRawBody(event)
  return await proxyRequest(event, target, {
    method,
    headers,
    body: body ?? undefined
  })
})
