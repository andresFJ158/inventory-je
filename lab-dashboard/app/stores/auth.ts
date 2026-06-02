import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<any>(null)
  const token = ref<string | null>(null)
  const office = ref<any>(null)
  // 'lab' | 'pos' — persisted in localStorage via plugin
  const mode = ref<'lab' | 'pos'>('lab')

  const isLogged = computed(() => !!token.value)
  const role = computed(() => user.value?.rol_admin || 'lab_worker')
  const officeId = computed(() => office.value?.id_office || user.value?.id_office_admin || null)
  const warehouseId = computed(() => user.value?.id_warehouse_admin || 0)
  const permissions = computed(() => user.value?.permissions_admin || {})

  function setAuth(data: any) {
    user.value = data.user
    token.value = data.token
    office.value = data.office
  }

  function setMode(m: 'lab' | 'pos') {
    mode.value = m
    if (import.meta.client) {
      localStorage.setItem('erp_mode', m)
    }
  }

  function restoreMode() {
    if (import.meta.client) {
      const saved = localStorage.getItem('erp_mode') as 'lab' | 'pos' | null
      if (saved === 'lab' || saved === 'pos') {
        mode.value = saved
      }
    }
  }

  function logout() {
    user.value = null
    token.value = null
    office.value = null
    // mode se mantiene para la próxima sesión
  }

  return {
    user,
    token,
    office,
    mode,
    isLogged,
    role,
    officeId,
    warehouseId,
    permissions,
    setAuth,
    setMode,
    restoreMode,
    logout
  }
})
