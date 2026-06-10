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
  if (role === 'admin' && perm !== 'reportes-empresa') {
    return
  }

  const perms = auth.permissions || {}
  if (perms[perm] === 'on' || perms['reports'] === 'on' && perm === 'reportes') {
    return
  }

  return navigateTo('/')
})
