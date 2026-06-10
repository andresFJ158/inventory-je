<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

const auth = useAuthStore()
const colorMode = useColorMode()
const route = useRoute()

function toggleColorMode() {
  colorMode.preference = colorMode.value === 'dark' ? 'light' : 'dark'
}

const apiBase = '/ajax/pos.ajax.php'

async function checkSession() {
  // Si ya estamos en la página de login, no verificamos ni redirigimos
  if (route.path === '/login') return

  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLoggedUser: 'ok'
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      credentials: 'include'
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      auth.setAuth(data)
    } else {
      navigateTo('/login')
    }
  } catch (error) {
    console.error('Session error:', error)
    navigateTo('/login')
  }
}

async function handleLogout() {
  try {
    await $fetch(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        logoutLabUser: 'ok'
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      credentials: 'include'
    })
  } catch (e) {
    console.error('Logout error:', e)
  }
  auth.logout()
  navigateTo('/login')
}

// ---- SISTEMA DE NOTIFICACIONES ----
const pendingEntries = ref<any[]>([])
const activeProductions = ref<any[]>([])
const pendingQCTests = ref<any[]>([])
const lowStockMaterials = ref<any[]>([])

async function fetchNotifications() {
  if (route.path === '/login') return
  const officeId = String(auth.officeId || 6)

  try {
    // 1. Ingresos Pendientes
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabEntries: 'ok',
        id_office: officeId
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data && data.status === 200) {
      pendingEntries.value = (data.results || []).filter((e: any) => e.status_entry === 'pendiente')
    } else {
      pendingEntries.value = []
    }
  } catch (e) {
    console.error('Error fetching entries for notifications:', e)
  }

  try {
    // 2. Producciones Activas / En Proceso
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabProductions: 'ok',
        id_office: officeId
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data && data.status === 200) {
      activeProductions.value = (data.results || []).filter((p: any) => p.status_production === 'en_proceso' || p.status_production === 'pendiente')
    } else {
      activeProductions.value = []
    }
  } catch (e) {
    console.error('Error fetching productions for notifications:', e)
  }

  try {
    // 3. Controles de Calidad Pendientes
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabQCTests: 'ok',
        id_office: officeId
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data && data.status === 200) {
      pendingQCTests.value = (data.results || []).filter((q: any) => q.status_qc === 'pendiente')
    } else {
      pendingQCTests.value = []
    }
  } catch (e) {
    console.error('Error fetching QC tests for notifications:', e)
  }

  try {
    // 4. Insumos sin Stock o Stock Crítico (Adicional)
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({
        getLabMaterials: 'ok',
        id_office: officeId
      }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data && data.status === 200) {
      lowStockMaterials.value = (data.results || []).filter((m: any) => (parseFloat(m.stock_raw_material) || 0) <= 0)
    } else {
      lowStockMaterials.value = []
    }
  } catch (e) {
    console.error('Error fetching inventory status for notifications:', e)
  }
}

const totalNotificationsCount = computed(() => {
  return pendingEntries.value.length + activeProductions.value.length + pendingQCTests.value.length + lowStockMaterials.value.length
})

const notificationItems = computed(() => {
  const items: any[] = []

  // 1. Ingresos
  if (pendingEntries.value.length > 0) {
    items.push({
      label: `Ingresos Pendientes (${pendingEntries.value.length})`,
      icon: 'i-lucide-truck',
      to: '/ingreso-egreso',
      class: 'text-amber-500 font-bold bg-amber-50/50 dark:bg-amber-950/20'
    })
    pendingEntries.value.slice(0, 3).forEach((e: any) => {
      items.push({
        label: `Lote ${e.lot_number_entry || ('ENT-' + e.id_entry)}: ${e.name_raw_material || 'M.P.'}`,
        icon: 'i-lucide-arrow-right',
        to: '/ingreso-egreso'
      })
    })
  }

  // 2. Producciones
  if (activeProductions.value.length > 0) {
    items.push({
      label: `Producciones en Curso (${activeProductions.value.length})`,
      icon: 'i-lucide-cog',
      to: '/produccion',
      class: 'text-blue-500 font-bold bg-blue-50/50 dark:bg-blue-950/20'
    })
    activeProductions.value.slice(0, 3).forEach((p: any) => {
      items.push({
        label: `${p.name_product || 'Lote Producción'} (${parseFloat(p.total_qty_production)} u)`,
        icon: 'i-lucide-arrow-right',
        to: '/produccion'
      })
    })
  }

  // 3. Control Calidad
  if (pendingQCTests.value.length > 0) {
    items.push({
      label: `Controles Pendientes (${pendingQCTests.value.length})`,
      icon: 'i-lucide-shield-alert',
      to: '/calidad',
      class: 'text-rose-500 font-bold bg-rose-50/50 dark:bg-rose-950/20'
    })
    pendingQCTests.value.slice(0, 3).forEach((q: any) => {
      items.push({
        label: `Lote: ${q.batch_code || ('LOT-' + q.id)}`,
        icon: 'i-lucide-arrow-right',
        to: '/calidad'
      })
    })
  }

  // 4. Stock Crítico
  if (lowStockMaterials.value.length > 0) {
    items.push({
      label: `Sin Stock / Stock Agotado (${lowStockMaterials.value.length})`,
      icon: 'i-lucide-alert-triangle',
      to: '/inventario',
      class: 'text-red-500 font-bold bg-red-50/50 dark:bg-red-950/20'
    })
    lowStockMaterials.value.slice(0, 3).forEach((m: any) => {
      items.push({
        label: `${m.name_raw_material}`,
        icon: 'i-lucide-arrow-right',
        to: '/inventario'
      })
    })
  }

  if (items.length === 0) {
    return [{
      label: 'No hay tareas pendientes ✨',
      icon: 'i-lucide-check-circle',
      disabled: true
    }]
  }

  return items
})

let intervalId: any = null

onMounted(() => {
  checkSession()
  fetchNotifications()
  intervalId = setInterval(fetchNotifications, 15000) // update every 15s
})

onBeforeUnmount(() => {
  if (intervalId) clearInterval(intervalId)
})

watch(() => auth.officeId, () => {
  fetchNotifications()
})

watch(() => route.path, () => {
  checkSession()
  fetchNotifications()
})

// Mapeo exacto de los títulos del dashboard adaptado por roles
const sidebarItems = computed(() => {
  const role = auth.role
  const perms = auth.permissions || {}

  // Helper to verify permissions
  const hasPerm = (pageUrl: string) => {
    if (role === 'superadmin' || role === 'admin') return true
    return perms[pageUrl] === 'on'
  }

  const items: any[] = []

  // Módulos de Administración y POS
  if (role === 'superadmin' || role === 'admin' || role === 'cajero' || role === 'vendedor' || role === 'editor' || role === 'despachador') {
    if (hasPerm('pos')) items.push({ label: 'Punto de Venta POS', to: '/pos', icon: 'i-lucide-shopping-cart' })
    if (hasPerm('sucursales')) items.push({ label: 'Sucursales', to: '/sucursales', icon: 'i-lucide-store' })
    if (hasPerm('admins')) items.push({ label: 'Administradores', to: '/admins', icon: 'i-lucide-user-cog' })
    if (hasPerm('clientes')) items.push({ label: 'Clientes', to: '/clientes', icon: 'i-lucide-users' })
    if (hasPerm('categorias')) items.push({ label: 'Categorías', to: '/categorias', icon: 'i-lucide-tags' })
    if (hasPerm('productos')) items.push({ label: 'Productos', to: '/productos', icon: 'i-lucide-box' })
    if (role === 'superadmin' || role === 'admin' || hasPerm('productos')) {
      items.push({ label: 'Combos', to: '/combos', icon: 'i-lucide-layers' })
    }
    if (hasPerm('compras')) items.push({ label: 'Compras', to: '/compras', icon: 'i-lucide-shopping-bag' })
    if (hasPerm('ordenes')) items.push({ label: 'Órdenes', to: '/ordenes', icon: 'i-lucide-file-text' })
    if (hasPerm('ventas')) items.push({ label: 'Ventas', to: '/ventas', icon: 'i-lucide-banknote' })
    if (hasPerm('caja')) items.push({ label: 'Caja', to: '/caja', icon: 'i-lucide-wallet' })
    if (hasPerm('gastos')) items.push({ label: 'Gastos', to: '/gastos', icon: 'i-lucide-receipt' })
    if (hasPerm('proveedores')) items.push({ label: 'Proveedores', to: '/proveedores', icon: 'i-lucide-truck' })
    if (hasPerm('almacenes')) items.push({ label: 'Almacenes', to: '/almacenes', icon: 'i-lucide-warehouse' })
    
    // Vistas de Almacén/Distribución
    if (role === 'despachador' || role === 'superadmin' || role === 'admin' || hasPerm('almacen')) {
      items.push({ label: 'Almacén Principal', to: '/almacen', icon: 'i-lucide-warehouse' })
    }
    if (role === 'despachador' || role === 'superadmin' || role === 'admin' || hasPerm('despachos')) {
      items.push({ label: 'Centro Despachos', to: '/despachos', icon: 'i-lucide-truck' })
    }
    if (hasPerm('mi_inventario') || role === 'cajero' || role === 'vendedor') {
      items.push({ label: 'Mi Inventario', to: '/mi-inventario', icon: 'i-lucide-box' })
    }
    if (hasPerm('solicitar_inventario') || role === 'cajero' || role === 'vendedor') {
      items.push({ label: 'Solicitar Inventario', to: '/solicitar-inventario', icon: 'i-lucide-clipboard-list' })
    }

    // Reportes
    if (role === 'superadmin' || role === 'admin' || role === 'cajero' || hasPerm('reports') || hasPerm('reportes')) {
      items.push({ label: 'Reportes', to: '/reportes', icon: 'i-lucide-bar-chart-2' })
    }
    if (role === 'superadmin' || (role === 'admin' && auth.officeId === 0)) {
      items.push({ label: 'Reporte Empresa', to: '/reportes-empresa', icon: 'i-lucide-building-2' })
    }
  }

  // Módulos de Laboratorio
  if (role === 'superadmin' || role === 'admin' || role === 'lab_admin' || role === 'lab_worker') {
    items.push({ label: 'Dashboard Lab', to: '/', icon: 'i-lucide-layout-dashboard' })
    items.push({ label: 'Catalogo M.P.', to: '/materiales', icon: 'i-lucide-droplet' })
    items.push({ label: 'Insumos Lab', to: '/insumos-lab', icon: 'i-lucide-beaker' })
    items.push({ label: 'Inventario M.P.', to: '/inventario', icon: 'i-lucide-package' })
    items.push({ label: 'Entradas M.P.', to: '/ingreso-egreso', icon: 'i-lucide-truck' })
    items.push({ label: 'Recetas', to: '/recetas', icon: 'i-lucide-scroll' })
    items.push({ label: 'Produccion', to: '/produccion', icon: 'i-lucide-cog' })
    if (role !== 'lab_worker') {
      items.push({ label: 'Control Calidad', to: '/calidad', icon: 'i-lucide-shield-check' })
    }
    items.push({ label: 'Inventario Final', to: '/inventario-final', icon: 'i-lucide-boxes' })
  }

  return items
})
</script>

<template>
  <UApp>
    <!-- Si estamos en la página de login, renderizamos únicamente la página a pantalla completa -->
    <template v-if="route.path === '/login'">
      <NuxtPage />
    </template>

    <!-- Si estamos en cualquier otra página, mostramos el layout completo del Dashboard -->
    <template v-else>
      <div class="flex h-screen bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-sans overflow-hidden transition-colors duration-200">
        <!-- Sidebar de Navegación -->
        <aside class="w-64 border-r border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex flex-col shrink-0">
          <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <UIcon
                name="i-lucide-flask-conical"
                class="w-6 h-6 text-green-600 animate-pulse"
              />
              <span class="font-bold text-lg tracking-wider bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-transparent">
                UniTech LAB
              </span>
            </div>
            <UButton
              :icon="colorMode.value === 'dark' ? 'i-lucide-sun' : 'i-lucide-moon'"
              variant="ghost"
              color="neutral"
              @click="toggleColorMode"
            />
          </div>

          <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <NuxtLink
              v-for="item in sidebarItems"
              :key="item.to"
              :to="item.to"
              class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-650 dark:text-slate-300 hover:text-green-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 transition-all duration-150 group"
              active-class="bg-green-600 text-white dark:bg-green-600 dark:text-white font-semibold!"
            >
              <UIcon
                :name="item.icon"
                class="w-5 h-5"
              />
              <span>{{ item.label }}</span>
            </NuxtLink>
          </nav>

          <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 flex items-center gap-3">
            <UAvatar
              src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80"
              alt="Usuario"
              size="md"
            />
            <div class="truncate flex-1">
              <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                {{ auth.user?.name_admin || 'Usuario Lab' }}
              </p>
              <span class="text-xs text-slate-550 dark:text-slate-400 capitalize block">
                {{ auth.role === 'superadmin' ? 'Superadmin' : auth.role === 'admin' ? 'Administrador' : auth.role === 'despachador' ? 'Despachador' : auth.role === 'cajero' ? 'Cajero' : auth.role === 'vendedor' ? 'Vendedor' : auth.role === 'lab_admin' ? 'Admin Lab' : auth.role === 'lab_calidad' ? 'Control Calidad' : 'Operador' }}
              </span>
            </div>
            <UButton
              icon="i-lucide-log-out"
              variant="ghost"
              color="neutral"
              size="xs"
              @click="handleLogout"
            />
          </div>
        </aside>

        <!-- Panel de Contenido Principal -->
        <main class="flex-1 flex flex-col min-w-0 bg-white dark:bg-slate-900 overflow-hidden">
          <!-- Topbar -->
          <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-950/40 backdrop-blur px-6 flex items-center justify-between shrink-0">
            <div>
              <h2 class="text-lg font-bold text-slate-800 dark:text-white tracking-wide">
                Módulo de Laboratorio
              </h2>
              <p class="text-xs text-slate-550 dark:text-slate-400">
                Sucursal: <span class="text-green-600 font-medium">{{ auth.office?.title_office || 'Laboratorio Central' }}</span>
              </p>
            </div>
            <div class="flex items-center gap-4">
              <UDropdownMenu
                :items="notificationItems"
                :content="{ align: 'end' }"
                :ui="{ content: 'w-72 max-h-96 overflow-y-auto z-50' }"
              >
                <UButton
                  icon="i-lucide-bell"
                  variant="ghost"
                  color="neutral"
                  class="relative"
                >
                  <span
                    v-if="totalNotificationsCount > 0"
                    class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white ring-2 ring-white dark:ring-slate-900 animate-pulse"
                  >
                    {{ totalNotificationsCount }}
                  </span>
                </UButton>
              </UDropdownMenu>
              <div class="h-6 w-px bg-slate-200 dark:bg-slate-800" />
              <div class="text-right hidden sm:block">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  Fecha del Sistema
                </p>
                <p class="text-sm text-slate-700 dark:text-slate-200 font-semibold">
                  {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' }) }}
                </p>
              </div>
            </div>
          </header>

          <!-- Area de Vistas Nuxt -->
          <section class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 bg-slate-50/50 dark:bg-slate-900">
            <NuxtPage />
          </section>
        </main>
      </div>
    </template>
  </UApp>
</template>
