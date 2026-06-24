/**
 * Middleware de permisos por ruta.
 * Uso: definePageMeta({ middleware: ['auth', 'permission'], permission: 'admins' })
 */
export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore()
  const perm = to.meta.permission as string | undefined

  if (!perm) {
    return
  }

  const role = auth.role
  if (role === 'superadmin') {
    return
  }

  const perms = auth.permissions || {}
  
  // 1. If explicit permission exists in DB (on or off), it overrides everything (except superadmin)
  if (perms[perm] !== undefined) {
    if (perms[perm] === 'on') return
    if (perm === 'reportes' && perms['reports'] === 'on') return
    return navigateTo('/')
  }

  // 2. Fallbacks if no explicit permission is found (e.g. old users)
  if (role === 'admin' && perm !== 'reportes-empresa') {
    return
  }
  if (role === 'lab_admin' && ['productos', 'compras', 'proveedores', 'categorias'].includes(perm)) {
    return
  }

  return navigateTo('/')
})
