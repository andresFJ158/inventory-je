import { proxyRequest } from 'h3'

export default defineEventHandler(async (event) => {
  const url = event.node.req.url || ''
  
  // Bypass internal Nuxt Icon requests so Nuxt handles them locally
  if (url.startsWith('/api/_nuxt_icon')) {
    return
  }
  
  const targetHost = process.env.PROXY_API_URL || 'http://localhost:8081/**'
  const baseHost = targetHost.replace(/\/\*\*$/, '')
  const cleanPath = url.replace(/^\/api/, '')
  const targetUrl = `${baseHost}${cleanPath}`
  
  return proxyRequest(event, targetUrl)
})
