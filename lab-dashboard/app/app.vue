<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

const auth = useAuthStore()
const colorMode = useColorMode()
const route = useRoute()
const todayLabel = ref('')

function toggleColorMode() {
  colorMode.preference = colorMode.value === 'dark' ? 'light' : 'dark'
}

const apiBase = '/ajax/pos.ajax.php'

async function checkSession() {
  if (route.path === '/login') return
  try {
    const response = await $fetch<any>(apiBase, {
      method: 'POST',
      body: new URLSearchParams({ getLoggedUser: 'ok' }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    const data = typeof response === 'string' ? JSON.parse(response) : response
    if (data.status === 200) {
      auth.setAuth(data)
    } else {
      navigateTo('/login')
    }
  } catch {
    navigateTo('/login')
  }
}

async function handleLogout() {
  try {
    await $fetch(apiBase, {
      method: 'POST',
      body: new URLSearchParams({ logoutLabUser: 'ok' }),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
  } catch {}
  auth.logout()
  navigateTo('/login')
}

// ---- NOTIFICACIONES ----
const pendingEntries = ref<any[]>([])
const activeProductions = ref<any[]>([])
const pendingQCTests = ref<any[]>([])
const lowStockMaterials = ref<any[]>([])

async function fetchNotifications() {
  if (route.path === '/login') return
  const officeId = String(auth.officeId || 6)

  const calls = [
    { key: 'getLabEntries', target: pendingEntries, filter: (e: any) => e.status_entry === 'pendiente' },
    { key: 'getLabProductions', target: activeProductions, filter: (p: any) => p.status_production === 'en_proceso' || p.status_production === 'pendiente' },
    { key: 'getLabQCTests', target: pendingQCTests, filter: (q: any) => q.status_qc === 'pendiente' },
    { key: 'getLabMaterials', target: lowStockMaterials, filter: (m: any) => (parseFloat(m.stock_raw_material) || 0) <= 0 }
  ]

  await Promise.allSettled(calls.map(async ({ key, target, filter }) => {
    try {
      const response = await $fetch<any>(apiBase, {
        method: 'POST',
        body: new URLSearchParams({ [key]: 'ok', id_office: officeId }),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      })
      const data = typeof response === 'string' ? JSON.parse(response) : response
      target.value = data?.status === 200 ? (data.results || []).filter(filter) : []
    } catch {
      target.value = []
    }
  }))
}

const totalNotificationsCount = computed(() =>
  pendingEntries.value.length + activeProductions.value.length + pendingQCTests.value.length + lowStockMaterials.value.length
)

const notificationItems = computed(() => {
  const items: any[] = []

  if (pendingEntries.value.length > 0) {
    items.push({ label: `Ingresos Pendientes (${pendingEntries.value.length})`, icon: 'i-lucide-truck', to: '/ingreso-egreso', class: 'text-amber-500 font-bold' })
    pendingEntries.value.slice(0, 3).forEach((e: any) => {
      items.push({ label: `${e.lot_number_entry || 'ENT-' + e.id_entry}: ${e.name_raw_material || 'M.P.'}`, icon: 'i-lucide-arrow-right', to: '/ingreso-egreso' })
    })
  }

  if (activeProductions.value.length > 0) {
    items.push({ label: `Producciones en Curso (${activeProductions.value.length})`, icon: 'i-lucide-cog', to: '/produccion', class: 'text-blue-500 font-bold' })
    activeProductions.value.slice(0, 3).forEach((p: any) => {
      items.push({ label: `${p.name_product || 'Lote'} (${parseFloat(p.total_qty_production)} u)`, icon: 'i-lucide-arrow-right', to: '/produccion' })
    })
  }

  if (pendingQCTests.value.length > 0) {
    items.push({ label: `Controles Pendientes (${pendingQCTests.value.length})`, icon: 'i-lucide-shield-alert', to: '/calidad', class: 'text-rose-500 font-bold' })
    pendingQCTests.value.slice(0, 3).forEach((q: any) => {
      items.push({ label: `Lote: ${q.batch_code || 'LOT-' + q.id}`, icon: 'i-lucide-arrow-right', to: '/calidad' })
    })
  }

  if (lowStockMaterials.value.length > 0) {
    items.push({ label: `Sin Stock (${lowStockMaterials.value.length})`, icon: 'i-lucide-alert-triangle', to: '/inventario', class: 'text-red-500 font-bold' })
    lowStockMaterials.value.slice(0, 3).forEach((m: any) => {
      items.push({ label: m.name_raw_material, icon: 'i-lucide-arrow-right', to: '/inventario' })
    })
  }

  if (items.length === 0) {
    return [{ label: 'Sin tareas pendientes', icon: 'i-lucide-check-circle', disabled: true }]
  }

  return items
})

// ---- SIDEBAR ----
const sidebarOpen = ref(false)

const sidebarSections = computed(() => {
  const role = auth.role
  const perms = auth.permissions || {}

  const hasPerm = (pageUrl: string) => {
    if (role === 'superadmin' || role === 'admin') return true
    return perms[pageUrl] === 'on'
  }

  const isAdmin = role === 'superadmin' || role === 'admin'
  const isCajero = role === 'cajero' || role === 'caja'
  const isVendedor = role === 'vendedor'
  const isCashier = isCajero || isVendedor
  const isDispatcher = role === 'despachador'
  const isLab = role === 'lab_admin' || role === 'lab_worker' || role === 'lab_calidad'
  const isPosUser = isAdmin || isCashier || isDispatcher

  const sections: { title: string, items: any[] }[] = []

  // Dynamic strict menu filtering for Caja role
  if (isCajero && auth.mode === 'pos') {
    const posItems: any[] = []
    posItems.push({ label: 'Punto de Venta', to: '/pos', icon: 'i-lucide-monitor-smartphone' })
    posItems.push({ label: 'Ventas', to: '/ventas', icon: 'i-lucide-banknote' })
    posItems.push({ label: 'Caja', to: '/caja', icon: 'i-lucide-wallet' })
    posItems.push({ label: 'Gastos', to: '/gastos', icon: 'i-lucide-receipt' })
    sections.push({ title: 'Punto de Venta', items: posItems })

    sections.push({
      title: 'Inventario',
      items: [
        { label: 'Mi Inventario', to: '/mi-inventario', icon: 'i-lucide-package' }
      ]
    })

    return sections
  }

  // POS / Ventas
  if (isPosUser && auth.mode === 'pos') {
    const posItems: any[] = []
    if (hasPerm('pos')) posItems.push({ label: 'Punto de Venta', to: '/pos', icon: 'i-lucide-monitor-smartphone' })
    if (hasPerm('ordenes')) posItems.push({ label: 'Órdenes', to: '/ordenes', icon: 'i-lucide-file-text' })
    if (hasPerm('ventas')) posItems.push({ label: 'Ventas', to: '/ventas', icon: 'i-lucide-banknote' })
    if (hasPerm('caja')) posItems.push({ label: 'Caja', to: '/caja', icon: 'i-lucide-wallet' })
    if (hasPerm('gastos')) posItems.push({ label: 'Gastos', to: '/gastos', icon: 'i-lucide-receipt' })
    if (posItems.length > 0) sections.push({ title: 'Punto de Venta', items: posItems })
  }

  // Inventario / Distribución
  if (isPosUser && auth.mode === 'pos') {
    const invItems: any[] = []
    if (hasPerm('productos')) invItems.push({ label: 'Productos', to: '/productos', icon: 'i-lucide-box' })
    if (hasPerm('categorias')) invItems.push({ label: 'Categorías', to: '/categorias', icon: 'i-lucide-tags' })
    if (hasPerm('compras')) invItems.push({ label: 'Compras', to: '/compras', icon: 'i-lucide-shopping-bag' })
    if (hasPerm('proveedores')) invItems.push({ label: 'Proveedores', to: '/proveedores', icon: 'i-lucide-truck' })
    if (isAdmin || isDispatcher || hasPerm('almacen')) invItems.push({ label: 'Almacén', to: '/almacen', icon: 'i-lucide-warehouse' })
    // Despachos: solo admin y despachador (NO cajero)
    if (isAdmin || isDispatcher) invItems.push({ label: 'Despachos', to: '/despachos', icon: 'i-lucide-truck' })
    if (isAdmin || isDispatcher) invItems.push({ label: 'Empaques', to: '/empaques', icon: 'i-lucide-package-open' })
    if (isCashier || hasPerm('mi_inventario')) invItems.push({ label: 'Mi Inventario', to: '/mi-inventario', icon: 'i-lucide-package' })
    if (isCashier || hasPerm('solicitar_inventario')) invItems.push({ label: 'Solicitar Inventario', to: '/solicitar-inventario', icon: 'i-lucide-clipboard-list' })
    if (invItems.length > 0) sections.push({ title: 'Inventario', items: invItems })
  }

  // Ventas avanzadas: SOLO vendedor y admin (NO cajero)
  if ((isVendedor || isAdmin) && auth.mode === 'pos') {
    const advItems: any[] = []
    advItems.push({ label: 'Crédito / Cobros', to: '/credito', icon: 'i-lucide-credit-card' })
    advItems.push({ label: 'Consignaciones', to: '/consignacion', icon: 'i-lucide-package' })
    if (isAdmin) advItems.push({ label: 'Combos', to: '/combos', icon: 'i-lucide-layers' })
    sections.push({ title: 'Ventas Avanzadas', items: advItems })
  }

  // Facturación: Admin y Vendedor
  if ((isVendedor || isAdmin) && auth.mode === 'pos') {
    sections.push({ title: 'Facturación', items: [{ label: 'Facturas', to: '/facturacion', icon: 'i-lucide-file-text' }] })
  }

  // Proveedores unificados: Admin y Lab (si está en mode lab)
  if (isAdmin || (isLab && auth.mode === 'lab')) {
    const suppItems: any[] = []
    suppItems.push({ label: 'Proveedores', to: '/proveedores', icon: 'i-lucide-building-2' })
    if (auth.mode === 'pos' && isAdmin) sections.push({ title: 'Compras', items: suppItems })
    if (auth.mode === 'lab') sections.push({ title: 'Materias Primas', items: suppItems })
  }

  // Gestión / Admin
  if (isAdmin && auth.mode === 'pos') {
    const adminItems: any[] = []
    adminItems.push({ label: 'Sucursales', to: '/sucursales', icon: 'i-lucide-store' })
    adminItems.push({ label: 'Administradores', to: '/admins', icon: 'i-lucide-user-cog' })
    adminItems.push({ label: 'Clientes', to: '/clientes', icon: 'i-lucide-users' })
    adminItems.push({ label: 'Reportes', to: '/reportes', icon: 'i-lucide-bar-chart-2' })
    sections.push({ title: 'Administración', items: adminItems })
  } else if (isCashier && hasPerm('reportes') && auth.mode === 'pos') {
    sections.push({ title: 'Administración', items: [{ label: 'Reportes', to: '/reportes', icon: 'i-lucide-bar-chart-2' }] })
  }

  // Laboratorio
  if ((isAdmin || isLab) && auth.mode === 'lab') {
    const labItems: any[] = []
    labItems.push({ label: 'Catálogo M.P.', to: '/materiales', icon: 'i-lucide-droplet' })
    labItems.push({ label: 'Catálogo Insumos', to: '/insumos', icon: 'i-lucide-boxes' })
    labItems.push({ label: 'Inventario M.P.', to: '/inventario', icon: 'i-lucide-package' })
    labItems.push({ label: 'Ingreso/Egreso', to: '/ingreso-egreso', icon: 'i-lucide-arrow-left-right' })
    labItems.push({ label: 'Recetas', to: '/recetas', icon: 'i-lucide-scroll' })
    labItems.push({ label: 'Producción', to: '/produccion', icon: 'i-lucide-cog' })
    if (role !== 'lab_worker') labItems.push({ label: 'Control Calidad', to: '/calidad', icon: 'i-lucide-shield-check' })
    labItems.push({ label: 'Inventario Final', to: '/inventario-final', icon: 'i-lucide-boxes' })
    sections.push({ title: 'Laboratorio', items: labItems })
  }

  return sections
})

const roleLabel = computed(() => {
  const map: Record<string, string> = {
    superadmin: 'Superadmin',
    admin: 'Administrador',
    cajero: 'Cajero',
    caja: 'Caja',
    vendedor: 'Vendedor',
    despachador: 'Despachador',
    lab_admin: 'Admin Lab',
    lab_worker: 'Operador Lab',
    lab_calidad: 'Control Calidad'
  }
  return map[auth.role] || auth.role
})

const pageTitle = computed(() => {
  const titles: Record<string, string> = {
    '/': 'Dashboard',
    '/pos': 'Punto de Venta',
    '/ordenes': 'Órdenes',
    '/ventas': 'Ventas',
    '/caja': 'Caja',
    '/gastos': 'Gastos',
    '/productos': 'Productos',
    '/categorias': 'Categorías',
    '/compras': 'Compras',
    '/proveedores': 'Proveedores',
    '/almacen': 'Almacén Principal',
    '/despachos': 'Centro de Despachos',
    '/empaques': 'Catálogo de Empaques y Envases',
    '/mi-inventario': 'Mi Inventario',
    '/solicitar-inventario': 'Solicitar Inventario',
    '/sucursales': 'Sucursales',
    '/admins': 'Administradores',
    '/clientes': 'Clientes',
    '/reportes': 'Reportes',
    '/facturacion': 'Facturación y Comprobantes',
    '/credito': 'Crédito y Cuentas por Cobrar',
    '/consignacion': 'Consignaciones',
    '/combos': 'Combos / Productos Compuestos',
    '/materiales': 'Catálogo de M.P.',
    '/insumos': 'Catálogo de Insumos',
    '/inventario': 'Inventario M.P.',
    '/ingreso-egreso': 'Ingreso/Egreso de Stock',
    '/recetas': 'Recetas',
    '/produccion': 'Producción',
    '/calidad': 'Control de Calidad',
    '/inventario-final': 'Inventario Final'
  }
  return titles[route.path] || 'Sistema de Gestión'
})

let intervalId: any = null

const handleGlobalNumericInputs = (e: KeyboardEvent) => {
  const target = e.target as HTMLInputElement
  if (!target) return
  const isNumberInput = target.getAttribute('type') === 'number' || target.classList.contains('format-numeric') || target.getAttribute('inputmode') === 'decimal' || target.getAttribute('inputmode') === 'numeric'
  if (isNumberInput) {
    if (e.key === '-' || e.key === 'e' || e.key === 'E') {
      e.preventDefault()
    }
  }
}

const handleGlobalPaste = (e: ClipboardEvent) => {
  const target = e.target as HTMLInputElement
  if (!target) return
  const isNumberInput = target.getAttribute('type') === 'number' || target.classList.contains('format-numeric') || target.getAttribute('inputmode') === 'decimal' || target.getAttribute('inputmode') === 'numeric'
  if (isNumberInput) {
    const clipboardData = e.clipboardData || (window as any).clipboardData
    const pastedData = clipboardData?.getData('Text') || ''
    if (pastedData.includes('-')) {
      e.preventDefault()
      const cleanVal = pastedData.replace(/-/g, '')
      if (cleanVal) {
        target.value = cleanVal
        target.dispatchEvent(new Event('input'))
      }
    }
  }
}

const handleGlobalInput = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target) return
  if (target.getAttribute('type') === 'number') {
    const val = parseFloat(target.value)
    if (val < 0) {
      target.value = String(Math.abs(val))
      target.dispatchEvent(new Event('input'))
    }
  }
}

const handleNumericFormatting = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target) return
  
  const isFormatted = target.classList.contains('format-numeric') || target.getAttribute('data-format-numeric') === 'true'
  if (isFormatted) {
    const val = target.value
    const selectionStart = target.selectionStart || 0
    const originalLen = val.length

    // Clean value: allow only digits and one comma for decimal separation
    const hasComma = val.includes(',')
    let clean = val.replace(/[^\d]/g, '')
    if (!clean) {
      if (target.value !== '') {
        target.value = ''
        target.dispatchEvent(new Event('input'))
      }
      return
    }

    // Format thousands with dots
    let formatted = clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    
    // Add decimal part back if present
    if (hasComma) {
      const parts = val.split(',')
      const decimal = parts[1] ? parts[1].replace(/[^\d]/g, '') : ''
      formatted = formatted + ',' + decimal
    }

    if (target.value !== formatted) {
      target.value = formatted
      target.dispatchEvent(new Event('input'))
      
      const newLen = formatted.length
      const cursorPosition = selectionStart + (newLen - originalLen)
      target.setSelectionRange(cursorPosition, cursorPosition)
    }
  }
}

onMounted(() => {
  todayLabel.value = new Date().toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' })
  auth.restoreMode()
  checkSession()
  fetchNotifications()
  intervalId = setInterval(fetchNotifications, 15000)

  window.addEventListener('keydown', handleGlobalNumericInputs, true)
  window.addEventListener('paste', handleGlobalPaste, true)
  window.addEventListener('input', handleGlobalInput, true)
  window.addEventListener('input', handleNumericFormatting, true)
})

onBeforeUnmount(() => {
  if (intervalId) clearInterval(intervalId)
  window.removeEventListener('keydown', handleGlobalNumericInputs, true)
  window.removeEventListener('paste', handleGlobalPaste, true)
  window.removeEventListener('input', handleGlobalInput, true)
  window.removeEventListener('input', handleNumericFormatting, true)
})

watch(() => route.path, () => {
  checkSession()
  fetchNotifications()
  sidebarOpen.value = false
})

watch(() => auth.officeId, () => {
  fetchNotifications()
})
</script>

<template>
  <UApp>
    <template v-if="route.path === '/login'">
      <NuxtPage />
    </template>

    <template v-else>
      <div class="flex h-dvh bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-sans overflow-hidden">

        <!-- ── Backdrop móvil ── -->
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
          <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebarOpen = false" />
        </Transition>

        <!-- ── Sidebar ── -->
        <aside
          :class="[
            'fixed inset-y-0 left-0 z-40 flex flex-col w-72 bg-white dark:bg-slate-950 border-r border-slate-200 dark:border-slate-800 transition-transform duration-300 lg:relative lg:translate-x-0 lg:z-auto lg:w-64 lg:shrink-0',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full'
          ]"
        >
          <!-- Logo -->
          <div class="h-16 px-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5 min-w-0">
              <div :class="['w-8 h-8 rounded-lg flex items-center justify-center shrink-0', auth.mode === 'pos' ? 'bg-blue-600' : 'bg-green-600']">
                <UIcon :name="auth.mode === 'pos' ? 'i-lucide-monitor-smartphone' : 'i-lucide-flask-conical'" class="w-4 h-4 text-white" />
              </div>
              <div class="min-w-0">
                <span class="font-bold text-sm text-slate-800 dark:text-white block leading-tight">UniTech ERP</span>
                <span :class="['text-[10px] font-semibold', auth.mode === 'pos' ? 'text-blue-500' : 'text-green-500']">
                  {{ auth.mode === 'pos' ? 'Sistema POS' : 'Laboratorio' }}
                </span>
              </div>
            </div>
            <div class="flex items-center gap-1">
              <UButton :icon="colorMode.value === 'dark' ? 'i-lucide-sun' : 'i-lucide-moon'" variant="ghost" color="neutral" size="xs" @click="toggleColorMode" />
              <UButton icon="i-lucide-x" variant="ghost" color="neutral" size="xs" class="lg:hidden" @click="sidebarOpen = false" />
            </div>
          </div>

          <!-- Nav -->
          <nav class="flex-1 overflow-y-auto py-2 px-2 space-y-3">
            <NuxtLink
              to="/"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-green-700 dark:hover:text-white hover:bg-green-50 dark:hover:bg-slate-800/60 transition-colors"
              active-class="bg-green-600 !text-white font-semibold shadow-sm hover:!text-slate-600 dark:hover:!text-slate-300"
              :exact="true"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-layout-dashboard" class="w-4 h-4 shrink-0" />
              <span>Dashboard</span>
            </NuxtLink>

            <template v-for="section in sidebarSections" :key="section.title">
              <div>
                <p class="px-3 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">{{ section.title }}</p>
                <div class="space-y-0.5">
                  <NuxtLink
                    v-for="item in section.items"
                    :key="item.to"
                    :to="item.to"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-green-700 dark:hover:text-white hover:bg-green-50 dark:hover:bg-slate-800/60 transition-colors"
                    active-class="bg-green-600 !text-white font-semibold shadow-sm hover:!text-slate-600 dark:hover:!text-slate-300"
                    @click="sidebarOpen = false"
                  >
                    <UIcon :name="item.icon" class="w-4 h-4 shrink-0" />
                    <span class="truncate">{{ item.label }}</span>
                  </NuxtLink>
                </div>
              </div>
            </template>
          </nav>

          <!-- Switch módulo -->
          <div class="px-3 pb-2 shrink-0">
            <UButton
              block variant="soft" size="xs"
              :color="auth.mode === 'pos' ? 'success' : 'primary'"
              :icon="auth.mode === 'pos' ? 'i-lucide-flask-conical' : 'i-lucide-monitor-smartphone'"
              @click="() => { auth.setMode(auth.mode === 'pos' ? 'lab' : 'pos'); navigateTo(auth.mode === 'pos' ? '/pos' : '/') }"
            >
              Ir a {{ auth.mode === 'pos' ? 'Laboratorio' : 'Sistema POS' }}
            </UButton>
          </div>

          <!-- User -->
          <div class="p-3 border-t border-slate-200 dark:border-slate-800 flex items-center gap-3 shrink-0">
            <div :class="['w-9 h-9 rounded-full flex items-center justify-center shrink-0', auth.mode === 'pos' ? 'bg-blue-100 dark:bg-blue-900/40' : 'bg-green-100 dark:bg-green-900/40']">
              <UIcon name="i-lucide-user" :class="['w-5 h-5', auth.mode === 'pos' ? 'text-blue-600' : 'text-green-600']" />
            </div>
            <div class="truncate flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">{{ auth.user?.name_admin || 'Usuario' }}</p>
              <span class="text-xs text-slate-500 dark:text-slate-400 block truncate">{{ roleLabel }}</span>
            </div>
            <UButton icon="i-lucide-log-out" variant="ghost" color="neutral" size="xs" title="Cerrar sesión" @click="handleLogout" />
          </div>
        </aside>

        <!-- ── Contenido principal ── -->
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
          <!-- Topbar -->
          <header class="h-14 lg:h-16 border-b border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-950/60 backdrop-blur-sm px-4 lg:px-6 flex items-center justify-between shrink-0 gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <!-- Hamburger (móvil) -->
              <UButton icon="i-lucide-menu" variant="ghost" color="neutral" size="sm" class="lg:hidden shrink-0" @click="sidebarOpen = true" />
              <div class="min-w-0">
                <h1 class="text-sm lg:text-base font-bold text-slate-800 dark:text-white truncate">{{ pageTitle }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block truncate">
                  Sucursal: <span class="text-green-600 font-medium">{{ auth.office?.title_office ? decodeURIComponent(auth.office.title_office).replace(/\+/g, ' ') : 'Principal' }}</span>
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <!-- Notificaciones -->
              <UDropdownMenu
                v-if="['lab_admin','lab_worker','lab_calidad','superadmin','admin'].includes(auth.role)"
                :items="notificationItems"
                :content="{ align: 'end' }"
                :ui="{ content: 'w-72 max-h-96 overflow-y-auto z-50' }"
              >
                <UButton icon="i-lucide-bell" variant="ghost" color="neutral" size="sm" class="relative">
                  <span v-if="totalNotificationsCount > 0" class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white animate-pulse">
                    {{ totalNotificationsCount }}
                  </span>
                </UButton>
              </UDropdownMenu>

              <div class="text-right hidden md:block">
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-none">Hoy</p>
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 mt-0.5">{{ todayLabel }}</p>
              </div>
            </div>
          </header>

          <!-- Contenido -->
          <section class="erp-content flex-1 overflow-y-auto p-3 sm:p-4 lg:p-5 bg-slate-50 dark:bg-slate-900">
            <NuxtPage />
          </section>
        </main>
      </div>
    </template>
  </UApp>
</template>
