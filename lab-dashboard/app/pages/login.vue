<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({ layout: false })

const auth = useAuthStore()

// Step 1: elegir módulo. Step 2: formulario login
const step = ref<'select' | 'form'>('select')
const selectedMode = ref<'lab' | 'pos'>('lab')

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

function chooseMode(m: 'lab' | 'pos') {
  selectedMode.value = m
  step.value = 'form'
}

function goBack() {
  step.value = 'select'
  errorMessage.value = ''
}

const modeConfig = {
  lab: {
    icon: 'i-lucide-flask-conical',
    title: 'Laboratorio',
    subtitle: 'Control de producción, materias primas y calidad',
    gradient: 'from-green-600 to-emerald-500',
    ring: 'ring-green-500/30',
    bg: 'bg-green-500/10',
    border: 'border-green-500/20',
    iconColor: 'text-green-400',
    redirectTo: '/'
  },
  pos: {
    icon: 'i-lucide-monitor-smartphone',
    title: 'Sistema POS',
    subtitle: 'Ventas, caja, inventario y despachos',
    gradient: 'from-blue-600 to-indigo-500',
    ring: 'ring-blue-500/30',
    bg: 'bg-blue-500/10',
    border: 'border-blue-500/20',
    iconColor: 'text-blue-400',
    redirectTo: '/pos'
  }
}

async function handleLogin() {
  if (!email.value || !password.value) {
    errorMessage.value = 'Por favor, completa todos los campos.'
    return
  }
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        loginLabUser: 'ok',
        email: email.value,
        password: password.value
      }).toString(),
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      credentials: 'include'
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response

    if (data.status === 200) {
      auth.setAuth(data)
      auth.setMode(selectedMode.value)
      
      let redirectUrl = modeConfig[selectedMode.value].redirectTo
      if (selectedMode.value === 'pos') {
        const role = data.user?.rol_admin || 'lab_worker'
        const perms = data.user?.permissions_admin ? (typeof data.user.permissions_admin === 'string' ? JSON.parse(decodeURIComponent(data.user.permissions_admin)) : data.user.permissions_admin) : {}
        
        const hasPerm = (pageUrl: string) => {
          if (role === 'superadmin' || role === 'admin') return true
          return perms[pageUrl] === 'on'
        }

        if (!hasPerm('pos')) {
          if (role === 'despachador' || hasPerm('almacen')) {
            redirectUrl = '/almacen'
          } else {
            // Find first permitted module
            const possibleRoutes = [
              { key: 'pos', path: '/pos' },
              { key: 'sucursales', path: '/sucursales' },
              { key: 'admins', path: '/admins' },
              { key: 'clientes', path: '/clientes' },
              { key: 'categorias', path: '/categorias' },
              { key: 'productos', path: '/productos' },
              { key: 'compras', path: '/compras' },
              { key: 'ordenes', path: '/ordenes' },
              { key: 'ventas', path: '/ventas' },
              { key: 'caja', path: '/caja' },
              { key: 'gastos', path: '/gastos' },
              { key: 'proveedores', path: '/proveedores' },
              { key: 'almacenes', path: '/almacenes' }
            ]
            const matched = possibleRoutes.find(r => hasPerm(r.key))
            if (matched) {
              redirectUrl = matched.path
            } else if (role === 'despachador' || hasPerm('despachos')) {
              redirectUrl = '/despachos'
            } else if (hasPerm('mi_inventario')) {
              redirectUrl = '/mi-inventario'
            } else {
              redirectUrl = '/'
            }
          }
        }
      }
      navigateTo(redirectUrl)
    } else {
      errorMessage.value = data.message || 'Credenciales incorrectas.'
    }
  } catch (error: any) {
    console.error('Fetch error:', error)
    errorMessage.value = 'Error: ' + (error.message || JSON.stringify(error))
  } finally {
    loading.value = false
  }
}

const cfg = computed(() => modeConfig[selectedMode.value])
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 via-white to-gray-50 px-4 py-12 relative overflow-hidden">
    <!-- Blur orbs -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none" />
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none" />

    <div class="w-full max-w-md relative z-10">

      <!-- ── PASO 1: Selector de módulo ── -->
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-4"
        mode="out-in"
      >
        <div v-if="step === 'select'" key="select" class="space-y-6">
          <!-- Header -->
          <div class="text-center space-y-2">
            <img :src="'/logo.png'" alt="J.E Bolivia ERP" class="w-20 h-20 object-contain mx-auto mb-2 rounded-xl" />
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">J.E Bolivia ERP</h1>
            <p class="text-slate-600 text-sm">Selecciona el módulo al que deseas acceder</p>
          </div>

          <!-- Cards de módulo -->
          <div class="grid grid-cols-1 gap-4">
            <!-- LAB -->
            <button
              class="group w-full text-left bg-white border border-emerald-200 hover:border-emerald-500/50 rounded-2xl p-6 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-100/30 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
              @click="chooseMode('lab')"
            >
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center shrink-0 group-hover:bg-emerald-100 transition-colors">
                  <UIcon name="i-lucide-flask-conical" class="w-7 h-7 text-emerald-600" />
                </div>
                <div class="flex-1 min-w-0">
                  <h2 class="text-lg font-bold text-slate-900 group-hover:text-emerald-600 transition-colors">Laboratorio</h2>
                  <p class="text-xs text-slate-600 mt-0.5">Producción, materias primas, calidad</p>
                </div>
                <UIcon name="i-lucide-arrow-right" class="w-5 h-5 text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-1 transition-all" />
              </div>
              <div class="mt-4 flex flex-wrap gap-2">
                <span v-for="tag in ['Entradas M.P.', 'Producción', 'Control Calidad', 'Inventario']" :key="tag"
                  class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                  {{ tag }}
                </span>
              </div>
            </button>

            <!-- POS -->
            <button
              class="group w-full text-left bg-white border border-emerald-200 hover:border-emerald-500/50 rounded-2xl p-6 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-100/30 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
              @click="chooseMode('pos')"
            >
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center shrink-0 group-hover:bg-emerald-100 transition-colors">
                  <UIcon name="i-lucide-monitor-smartphone" class="w-7 h-7 text-emerald-600" />
                </div>
                <div class="flex-1 min-w-0">
                  <h2 class="text-lg font-bold text-slate-900 group-hover:text-emerald-600 transition-colors">Sistema POS</h2>
                  <p class="text-xs text-slate-600 mt-0.5">Ventas, caja, inventario y despachos</p>
                </div>
                <UIcon name="i-lucide-arrow-right" class="w-5 h-5 text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-1 transition-all" />
              </div>
              <div class="mt-4 flex flex-wrap gap-2">
                <span v-for="tag in ['Punto de Venta', 'Caja', 'Órdenes', 'Despachos', 'Reportes']" :key="tag"
                  class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                  {{ tag }}
                </span>
              </div>
            </button>
          </div>

          <p class="text-center text-xs text-slate-500">J.E Bolivia ERP &copy; 2026</p>
        </div>

        <!-- ── PASO 2: Formulario de login ── -->
        <div v-else key="form" class="space-y-6">
          <!-- Header con modo seleccionado -->
          <div class="text-center space-y-3">
            <button
              class="inline-flex items-center gap-2 text-xs text-slate-600 hover:text-slate-800 transition-colors mb-1"
              @click="goBack"
            >
              <UIcon name="i-lucide-arrow-left" class="w-3.5 h-3.5" />
              Cambiar módulo
            </button>

            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl border border-emerald-200 bg-emerald-50 mb-1">
              <UIcon :name="cfg.icon" class="w-9 h-9 text-emerald-600" />
            </div>

            <div>
              <h2 class="text-2xl font-extrabold text-emerald-600">
                {{ cfg.title }}
              </h2>
              <p class="text-slate-600 text-xs mt-1">{{ cfg.subtitle }}</p>
            </div>
          </div>

          <!-- Card de login -->
          <div class="bg-white border border-emerald-200 rounded-2xl p-8 space-y-5 shadow-md">
            <!-- Error -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
            >
              <div v-if="errorMessage" class="flex items-center gap-3 p-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded-xl">
                <UIcon name="i-lucide-alert-triangle" class="w-4 h-4 text-red-600 shrink-0" />
                <span>{{ errorMessage }}</span>
              </div>
            </Transition>

            <form class="space-y-4" @submit.prevent="handleLogin">
              <!-- Email -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                  Correo Electrónico
                </label>
                <div class="relative">
                  <UIcon name="i-lucide-mail" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                  <input
                    v-model="email"
                    type="email"
                    autocomplete="email"
                    required
                    placeholder="ejemplo@unitech.com"
                    class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-xl text-slate-900 placeholder-slate-500 focus:outline-none focus:ring-2 focus:border-transparent transition-all text-sm focus:ring-emerald-500/50"
                  />
                </div>
              </div>

              <!-- Password -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                  Contraseña
                </label>
                <div class="relative">
                  <UIcon name="i-lucide-lock" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                  <input
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="current-password"
                    required
                    placeholder="••••••••"
                    class="w-full pl-10 pr-11 py-3 bg-gray-50 border border-gray-300 rounded-xl text-slate-900 placeholder-slate-500 focus:outline-none focus:ring-2 focus:border-transparent transition-all text-sm focus:ring-emerald-500/50"
                  />
                  <button
                    type="button"
                    :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
                    @click="showPassword = !showPassword"
                  >
                    <UIcon :name="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'" class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Submit -->
              <button
                type="submit"
                :disabled="loading"
                class="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 transition-all duration-200 shadow-md shadow-emerald-500/20 disabled:opacity-50 disabled:cursor-not-allowed mt-2"
              >
                <UIcon v-if="loading" name="i-lucide-loader-2" class="w-4 h-4 animate-spin" />
                <UIcon v-else :name="cfg.icon" class="w-4 h-4" />
                {{ loading ? 'Iniciando sesión...' : `Ingresar a ${cfg.title}` }}
              </button>
            </form>
          </div>

          <p class="text-center text-xs text-slate-500">J.E Bolivia ERP &copy; 2026</p>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style scoped>
/* Asegurar que ambas tarjetas en paso 1 tengan el mismo cursor interactivo */
button { cursor: pointer; }
</style>
