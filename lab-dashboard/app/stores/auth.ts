import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<any>(null)
  const token = ref<string | null>(null)
  const office = ref<any>(null)

  const isLogged = computed(() => !!token.value)
  const role = computed(() => user.value?.rol_admin || 'lab_worker')
  const officeId = computed(() => office.value?.id_office || null)

  function setAuth(data: any) {
    user.value = data.user
    token.value = data.token
    office.value = data.office
  }

  function logout() {
    user.value = null
    token.value = null
    office.value = null
  }

  return {
    user,
    token,
    office,
    isLogged,
    role,
    officeId,
    setAuth,
    logout
  }
})
