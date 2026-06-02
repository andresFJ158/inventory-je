import { proxyRequest } from 'h3'

export default defineEventHandler(async (event) => {
  const url = event.node.req.url || ''
  
  const targetHost = process.env.PROXY_AJAX_URL || 'http://localhost:8080/ajax/**'
  const baseHost = targetHost.replace(/\/\*\*$/, '')
  const cleanPath = url.replace(/^\/ajax/, '')
  const targetUrl = `${baseHost}${cleanPath}`
  
  return proxyRequest(event, targetUrl)
})
