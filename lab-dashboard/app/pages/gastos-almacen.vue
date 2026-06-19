<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  middleware: ['auth']
})

const auth = useAuthStore()
const api = useApi()
const toast = useToast()

// Role authorization check
const allowedRoles = ['admin', 'superadmin', 'cajero', 'despachador', 'despachador_laboratorio']
const hasAccess = computed(() => {
  return allowedRoles.includes(auth.role || '')
})

// State
const templates = ref<any[]>([])
const loading = ref(true)
const saving = ref(false)

// Modal States
const showFormModal = ref(false)
const isEditing = ref(false)
const showDeleteModal = ref(false)
const pendingDeleteId = ref<number | null>(null)
const pendingDeleteConcept = ref('')

// Form State
const formErrors = ref({ concept: '' })
const emptyForm = () => ({
  id_template: null as number | null,
  concept: '',
  amount: 0,
  charge_to_client: false
})
const form = ref(emptyForm())

// API Actions
async function fetchTemplates() {
  if (!hasAccess.value) return
  loading.value = true
  try {
    const res = await api.ajax({ getWarehouseExpenseTemplates: 'ok' })
    if (res?.status === 200) {
      templates.value = res.results || []
    } else {
      toast.add({ title: 'Error al obtener plantillas', description: res?.message || 'Error desconocido', color: 'warning' })
    }
  } catch (error) {
    console.error('Error fetching templates:', error)
    toast.add({ title: 'Error de servidor', description: 'No se pudo conectar con el servidor', color: 'error' })
  } finally {
    loading.value = false
  }
}

function openCreate() {
  isEditing.value = false
  form.value = emptyForm()
  formErrors.value = { concept: '' }
  showFormModal.value = true
}

function openEdit(template: any) {
  isEditing.value = true
  form.value = {
    id_template: parseInt(template.id_template),
    concept: template.concept_template || '',
    amount: parseFloat(template.amount_template) || 0,
    charge_to_client: parseInt(template.charge_to_client_template) === 1
  }
  formErrors.value = { concept: '' }
  showFormModal.value = true
}

async function saveTemplate() {
  formErrors.value = { concept: '' }
  if (!form.value.concept.trim()) {
    formErrors.value.concept = 'El concepto de gasto es requerido'
    return
  }

  saving.value = true
  try {
    const res = await api.ajax({
      saveWarehouseExpenseTemplate: 'ok',
      id_template: form.value.id_template || 0,
      concept: form.value.concept.trim(),
      amount: form.value.amount,
      charge_to_client: form.value.charge_to_client ? 1 : 0
    })

    if (res?.status === 200) {
      toast.add({
        title: isEditing.value ? 'Plantilla actualizada' : 'Plantilla creada',
        description: res.message || 'La operación se realizó con éxito',
        color: 'success'
      })
      showFormModal.value = false
      await fetchTemplates()
    } else {
      toast.add({ title: 'Error al guardar', description: res?.message || 'No se pudo completar la operación', color: 'error' })
    }
  } catch (error) {
    console.error('Error saving template:', error)
    toast.add({ title: 'Error de servidor', description: 'No se pudo guardar la plantilla', color: 'error' })
  } finally {
    saving.value = false
  }
}

function confirmDelete(template: any) {
  pendingDeleteId.value = parseInt(template.id_template)
  pendingDeleteConcept.value = template.concept_template
  showDeleteModal.value = true
}

async function doDelete() {
  if (!pendingDeleteId.value) return
  try {
    const res = await api.ajax({
      deleteWarehouseExpenseTemplate: 'ok',
      id_template: pendingDeleteId.value
    })

    if (res?.status === 200) {
      toast.add({ title: 'Plantilla eliminada', description: 'La plantilla se eliminó de forma permanente', color: 'success' })
      showDeleteModal.value = false
      await fetchTemplates()
    } else {
      toast.add({ title: 'Error al eliminar', description: res?.message || 'No se pudo eliminar la plantilla', color: 'error' })
    }
  } catch (error) {
    console.error('Error deleting template:', error)
    toast.add({ title: 'Error de servidor', description: 'No se pudo eliminar la plantilla', color: 'error' })
  } finally {
    pendingDeleteId.value = null
    pendingDeleteConcept.value = ''
  }
}

onMounted(() => {
  fetchTemplates()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Authorization Guard UI -->
    <div v-if="!hasAccess" class="text-center py-20 bg-slate-50 rounded-2xl border border-slate-200 p-8">
      <div class="w-16 h-16 rounded-full bg-rose-50 border border-rose-200 flex items-center justify-center mx-auto mb-4">
        <UIcon name="i-lucide-alert-triangle" class="w-8 h-8 text-rose-500" />
      </div>
      <h3 class="font-bold text-slate-800 text-lg">Acceso Denegado</h3>
      <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
        No tienes permisos suficientes para gestionar las plantillas de gastos de almacén. Comunícate con el administrador si consideras que esto es un error.
      </p>
    </div>

    <!-- Main CRUD UI -->
    <template v-else>
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h2 class="text-lg font-bold text-slate-800">Gastos de Almacén</h2>
          <p class="text-sm text-slate-500 mt-0.5">
            Define conceptos y montos de gastos predeterminados (como cajas, empaques, etc.) para acelerar los despachos.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <UButton
            icon="i-lucide-refresh-cw"
            variant="ghost"
            color="neutral"
            size="sm"
            :loading="loading"
            @click="fetchTemplates"
          >
            Actualizar
          </UButton>
          <UButton
            icon="i-lucide-plus"
            color="primary"
            size="sm"
            @click="openCreate"
          >
            Nueva Plantilla
          </UButton>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-24">
        <div class="flex flex-col items-center gap-3">
          <UIcon name="i-lucide-loader-2" class="w-10 h-10 animate-spin text-emerald-500" />
          <span class="text-sm text-slate-400">Cargando plantillas...</span>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="templates.length === 0" class="text-center py-20 bg-slate-50/50 rounded-2xl border border-dashed border-slate-300">
        <div class="w-20 h-20 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto mb-4">
          <UIcon name="i-lucide-receipt" class="w-10 h-10 text-slate-400" />
        </div>
        <h3 class="font-semibold text-slate-700">Sin plantillas de gastos</h3>
        <p class="text-sm text-slate-400 mt-1 max-w-xs mx-auto">
          Crea plantillas predefinidas para automatizar la carga de costos en los despachos de almacén.
        </p>
        <UButton
          class="mt-5"
          color="primary"
          icon="i-lucide-plus"
          @click="openCreate"
        >
          Crear Plantilla
        </UButton>
      </div>

      <!-- Table List -->
      <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="text-left font-semibold text-slate-600 px-6 py-4">Concepto</th>
                <th class="text-right font-semibold text-slate-600 px-6 py-4">Monto Predeterminado</th>
                <th class="text-center font-semibold text-slate-600 px-6 py-4">Cobrar al Cliente</th>
                <th class="text-center font-semibold text-slate-600 px-6 py-4">Fecha Creación</th>
                <th class="text-right font-semibold text-slate-600 px-6 py-4 w-32">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="template in templates"
                :key="template.id_template"
                class="hover:bg-slate-50/50 transition-colors"
              >
                <td class="px-6 py-4 font-medium text-slate-800">
                  {{ template.concept_template }}
                </td>
                <td class="px-6 py-4 text-right font-mono text-slate-700">
                  Bs. {{ Number(template.amount_template || 0).toFixed(2) }}
                </td>
                <td class="px-6 py-4 text-center">
                  <UBadge
                    v-if="parseInt(template.charge_to_client_template) === 1"
                    color="success"
                    variant="soft"
                    size="sm"
                    label="Sí"
                  />
                  <UBadge
                    v-else
                    color="neutral"
                    variant="soft"
                    size="sm"
                    label="No (Asume empresa)"
                  />
                </td>
                <td class="px-6 py-4 text-center text-slate-500">
                  {{ template.date_created_template }}
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <UButton
                      size="xs"
                      variant="ghost"
                      color="neutral"
                      icon="i-lucide-pencil"
                      v-tooltip="'Editar plantilla'"
                      @click="openEdit(template)"
                    />
                    <UButton
                      size="xs"
                      variant="ghost"
                      color="error"
                      icon="i-lucide-trash-2"
                      v-tooltip="'Eliminar plantilla'"
                      @click="confirmDelete(template)"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <UModal
        v-model:open="showDeleteModal"
        title="Eliminar plantilla"
        :description="`¿Estás seguro de que deseas eliminar la plantilla &quot;${pendingDeleteConcept}&quot;? Esta acción no afectará los despachos o gastos ya registrados.`"
      >
        <template #footer>
          <div class="flex justify-end gap-2 w-full">
            <UButton variant="ghost" color="neutral" @click="showDeleteModal = false">Cancelar</UButton>
            <UButton color="error" icon="i-lucide-trash-2" @click="doDelete">Eliminar</UButton>
          </div>
        </template>
      </UModal>

      <!-- Create / Edit Modal -->
      <UModal
        v-model:open="showFormModal"
        :title="isEditing ? 'Editar Plantilla de Gasto' : 'Nueva Plantilla de Gasto'"
        :description="isEditing ? 'Modifica los valores predeterminados de la plantilla.' : 'Define un nuevo concepto y costo de gasto predeterminado.'"
        :ui="{ content: 'max-w-md' }"
      >
        <template #body>
          <div class="space-y-4 p-1">
            <UFormField label="Concepto de Gasto *" :error="formErrors.concept">
              <UInput
                v-model="form.concept"
                placeholder="Ej: Caja, Empaque Grande, Delivery Urbano"
                autofocus
                :color="formErrors.concept ? 'error' : undefined"
              />
            </UFormField>

            <UFormField label="Monto por defecto (Bs.)">
              <UInput
                v-model.number="form.amount"
                type="number"
                min="0"
                step="0.01"
                placeholder="0.00"
              />
            </UFormField>

            <div class="flex items-center justify-between py-2 border-t border-slate-100 mt-4">
              <div>
                <label class="text-sm font-medium text-slate-700 block">Cobrar al cliente por defecto</label>
                <span class="text-xs text-slate-500">Determina si este gasto se le carga al cliente final en la orden.</span>
              </div>
              <USwitch v-model="form.charge_to_client" color="success" />
            </div>
          </div>
        </template>

        <template #footer>
          <div class="flex justify-end gap-2 w-full">
            <UButton variant="ghost" color="neutral" @click="showFormModal = false">Cancelar</UButton>
            <UButton
              color="primary"
              :loading="saving"
              :icon="isEditing ? 'i-lucide-save' : 'i-lucide-check'"
              @click="saveTemplate"
            >
              {{ isEditing ? 'Guardar cambios' : 'Crear Plantilla' }}
            </UButton>
          </div>
        </template>
      </UModal>
    </template>
  </div>
</template>
