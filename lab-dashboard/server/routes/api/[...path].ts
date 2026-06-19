/**
 * Proxy REST con token solo en servidor (no expuesto al bundle del cliente).
 */
export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const base = (config.apiInternalUrl as string).replace(/\/$/, '')
  const suffix = event.path.replace(/^\/api\/?/, '')
  const query = getRequestURL(event).search || ''
  const target = suffix.includes('?') ? `${base}/${suffix}` : `${base}/${suffix}${query}`

  const method = event.method
  const headers: Record<string, string> = {
    Authorization: config.apiToken as string
  }

  console.log(`[Proxy REST] ${method} ${event.path} -> ${target}`)

  try {
    if (method === 'GET' || method === 'HEAD') {
      return await proxyRequest(event, target, {
        headers,
        fetchOptions: {
          method
        }
      })
    }

    const body = await readRawBody(event)

    // For POST/PUT/DELETE: use $fetch to ensure headers (especially Authorization) are sent correctly.
    // proxyRequest re-sends the original client request and may not properly merge custom headers.
    const contentType = getRequestHeader(event, 'content-type') || 'application/x-www-form-urlencoded'
    headers['Content-Type'] = contentType

    const response = await $fetch.raw(target, {
      method: method as any,
      headers,
      body: body ?? undefined,
      // Don't parse response, just pass it through
      responseType: 'json'
    })

    // Forward status code
    setResponseStatus(event, response.status)
    return response._data
  } catch (err: any) {
    // If it's a FetchError with a response, forward the status and body
    if (err?.response) {
      setResponseStatus(event, err.response.status)
      return err.response._data || { status: err.response.status, results: 'Proxy error' }
    }
    console.error(`[Proxy REST ERROR] Failed to proxy to ${target}:`, err)
    throw err
  }
})
