<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: false
})

const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')

const auth = useAuthStore()

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
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    const data = typeof response === 'string' ? JSON.parse(response) : response

    if (data.status === 200) {
      auth.setAuth(data)
      navigateTo('/')
    } else {
      errorMessage.value = data.message || 'Credenciales incorrectas.'
    }
  } catch (error: any) {
    console.error('Error de login:', error)
    errorMessage.value = 'Ocurrió un error al intentar iniciar sesión. Verifica la conexión con el servidor.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-green-950 px-4 py-12 relative overflow-hidden">
    <!-- Círculos de fondo con desenfoque para dar un aspecto estético premium -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-green-500/20 rounded-full blur-3xl" />
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl" />

    <div class="w-full max-w-md space-y-8 relative z-10 animate-fade-in-up">
      <!-- Tarjeta principal con efecto Glassmorphism -->
      <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 rounded-2xl p-8 shadow-2xl space-y-6">
        <!-- Logo y Cabecera -->
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-500/10 border border-green-500/20 mb-4">
            <UIcon
              name="i-lucide-flask-conical"
              class="w-8 h-8 text-green-500 animate-pulse"
            />
          </div>
          <h2 class="text-3xl font-extrabold text-white tracking-tight bg-gradient-to-r from-green-400 to-emerald-300 bg-clip-text text-transparent">
            UniTech LAB
          </h2>
          <p class="mt-2 text-sm text-slate-400">
            Módulo de Administración de Laboratorio
          </p>
        </div>

        <!-- Formulario -->
        <form
          class="mt-8 space-y-6"
          @submit.prevent="handleLogin"
        >
          <!-- Alerta de Error -->
          <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
          >
            <div
              v-if="errorMessage"
              class="flex items-center gap-3 p-4 text-sm text-red-200 bg-red-950/40 border border-red-800/60 rounded-xl"
            >
              <UIcon
                name="i-lucide-alert-triangle"
                class="w-5 h-5 text-red-400 shrink-0"
              />
              <span class="font-medium">{{ errorMessage }}</span>
            </div>
          </Transition>

          <div class="space-y-4">
            <!-- Input de Email -->
            <div>
              <label
                for="email"
                class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2"
              >
                Correo Electrónico
              </label>
              <div class="relative rounded-lg shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <UIcon
                    name="i-lucide-mail"
                    class="h-5 w-5 text-slate-500"
                  />
                </div>
                <input
                  id="email"
                  v-model="email"
                  name="email"
                  type="email"
                  autocomplete="email"
                  required
                  placeholder="ejemplo@unitech.com"
                  class="block w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-800 rounded-lg text-white placeholder-slate-550 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:border-green-500 transition-all duration-200 text-sm"
                >
              </div>
            </div>

            <!-- Input de Contraseña -->
            <div>
              <label
                for="password"
                class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2"
              >
                Contraseña
              </label>
              <div class="relative rounded-lg shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <UIcon
                    name="i-lucide-lock"
                    class="h-5 w-5 text-slate-500"
                  />
                </div>
                <input
                  id="password"
                  v-model="password"
                  name="password"
                  type="password"
                  autocomplete="current-password"
                  required
                  placeholder="••••••••"
                  class="block w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-800 rounded-lg text-white placeholder-slate-550 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:border-green-500 transition-all duration-200 text-sm"
                >
              </div>
            </div>
          </div>

          <!-- Botón de Envío -->
          <div>
            <button
              type="submit"
              :disabled="loading"
              class="group relative w-full flex justify-center py-3.5 px-4 border border-transparent rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 focus:ring-offset-slate-900 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-green-900/30 transition-all duration-200"
            >
              <span
                v-if="loading"
                class="absolute left-0 inset-y-0 flex items-center pl-3"
              >
                <UIcon
                  name="i-lucide-loader-2"
                  class="animate-spin h-5 w-5 text-green-200"
                />
              </span>
              <span>{{ loading ? 'Iniciando Sesión...' : 'Ingresar al Laboratorio' }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Footer/Info de la App -->
      <p class="text-center text-xs text-slate-500">
        UniTech Lab Dashboard &copy; 2026. Todos los derechos reservados.
      </p>
    </div>
  </div>
</template>

<style scoped>
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
