import { e as defineEventHandler, z as proxyRequest } from '../../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import '@iconify/utils';
import 'consola';

const ____path_ = defineEventHandler(async (event) => {
  const url = event.node.req.url || "";
  if (url.startsWith("/api/_nuxt_icon")) {
    return;
  }
  const targetHost = process.env.PROXY_API_URL || "http://localhost:8081/**";
  const baseHost = targetHost.replace(/\/\*\*$/, "");
  const cleanPath = url.replace(/^\/api/, "");
  const targetUrl = `${baseHost}${cleanPath}`;
  return proxyRequest(event, targetUrl);
});

export { ____path_ as default };
//# sourceMappingURL=_...path_.mjs.map
