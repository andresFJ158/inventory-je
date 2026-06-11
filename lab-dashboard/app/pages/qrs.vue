<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const toast = useToast()
const loading = ref(true)
const saving = ref(false)
const currentQR = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

// Usamos la sucursal asignada al usuario
const officeId = auth.officeId || 3

async function fetchQR() {
  loading.value = true
  try {
    const data = await $fetch<any>(`/api/qrs?linkTo=id_office_qr&equalTo=${officeId}`, {
      headers: { Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }
    })
    if (data && data.status === 200 && data.results && data.results.length > 0) {
      currentQR.value = data.results[0].image_qr || ''
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchQR()
})

function getImageUrl(imgStr: string) {
  if (!imgStr) return ''
  const decoded = decodeURIComponent(imgStr).replace(/\+/g, ' ')
  const viewsIndex = decoded.indexOf('views/')
  if (viewsIndex !== -1) {
    return '/' + decoded.substring(viewsIndex)
  }
  if (decoded.startsWith('http') || decoded.startsWith('/')) return decoded
  return '/' + decoded
}

async function uploadImage() {
  if (!fileInput.value?.files?.length) {
    toast.add({ title: 'Selecciona una imagen', color: 'error' })
    return
  }
  
  const file = fileInput.value.files[0]
  if (file.size > 5 * 1024 * 1024) {
    toast.add({ title: 'La imagen es muy grande (máximo 5MB)', color: 'error' })
    return
  }

  saving.value = true
  try {
    const formData = new FormData()
    formData.append('uploadQR', 'ok')
    formData.append('id_office_qr', String(officeId))
    formData.append('qrImage', file)
    
    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: formData
    })
    
    const data = typeof res === 'string' ? JSON.parse(res) : res
    if (data.status === 200) {
      toast.add({ title: 'Código QR actualizado correctamente', color: 'success' })
      currentQR.value = data.path
      if (fileInput.value) fileInput.value.value = ''
    } else {
      toast.add({ title: data.message || 'Error al guardar el QR', color: 'error' })
    }
  } catch (e) {
    console.error(e)
    toast.add({ title: 'Error de conexión', color: 'error' })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-3xl mx-auto py-8 px-4">
    <div class="mb-8">
      <h1 class="text-3xl font-extrabold text-slate-800 dark:text-white flex items-center gap-3">
        <UIcon name="i-lucide-qr-code" class="text-primary" />
        Código QR de la Sucursal
      </h1>
      <p class="text-slate-500 mt-2">Sube la imagen del código QR de esta sucursal para recibir pagos. Solo puede haber un código QR activo; subir uno nuevo reemplazará al anterior automáticamente.</p>
    </div>

    <UCard class="backdrop-blur-sm bg-white/50 dark:bg-slate-900/50 shadow-xl border border-slate-100 dark:border-slate-800">
      <div v-if="loading" class="py-12 flex justify-center">
        <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-primary" />
      </div>
      
      <div v-else class="space-y-8">
        <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-8 text-center shadow-inner">
          <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 mb-6 uppercase tracking-widest">Código QR Actual</h3>
          
          <div v-if="currentQR" class="flex flex-col items-center">
            <div class="p-3 bg-white rounded-2xl shadow-md border-2 border-slate-100 dark:border-slate-600 inline-block transform transition-transform hover:scale-105 duration-300">
              <img :src="getImageUrl(currentQR)" alt="QR Actual" class="max-w-[250px] sm:max-w-[300px] rounded-xl" />
            </div>
            <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400 mt-5 flex items-center gap-1.5">
              <UIcon name="i-lucide-check-circle-2" class="w-4 h-4" />
              Este código se mostrará a los clientes al cobrar en el POS.
            </p>
          </div>
          
          <div v-else class="py-8">
            <div class="w-20 h-20 bg-slate-200 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
              <UIcon name="i-lucide-image-off" class="w-10 h-10 text-slate-400" />
            </div>
            <p class="text-slate-600 dark:text-slate-300 font-medium text-lg">No hay ningún código QR configurado</p>
            <p class="text-slate-400 text-sm mt-1">Los clientes no podrán escanear un QR al momento del cobro.</p>
          </div>
        </div>

        <div class="border-t border-slate-100 dark:border-slate-800 pt-8">
          <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-5 flex items-center gap-2">
            <UIcon :name="currentQR ? 'i-lucide-refresh-cw' : 'i-lucide-upload-cloud'" class="w-5 h-5 text-primary" />
            {{ currentQR ? 'Reemplazar Código QR' : 'Subir nuevo Código QR' }}
          </h3>
          
          <div class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-2 transition-all focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
              <label class="sr-only">Seleccionar nueva imagen</label>
              <input 
                ref="fileInput" 
                type="file" 
                accept="image/jpeg, image/png, image/webp" 
                class="block w-full text-sm text-slate-500 file:cursor-pointer file:mr-4 file:py-2.5 file:px-5 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-400 dark:text-slate-400 transition-colors"
              />
            </div>
            <UButton 
              size="xl" 
              color="primary" 
              :icon="currentQR ? 'i-lucide-refresh-ccw' : 'i-lucide-upload'" 
              :loading="saving" 
              @click="uploadImage"
              class="w-full md:w-auto px-8 font-bold shadow-lg shadow-primary/20"
            >
              {{ currentQR ? 'Actualizar' : 'Guardar' }}
            </UButton>
          </div>
          <p class="text-xs font-medium text-slate-400 mt-3 flex items-center gap-1.5">
            <UIcon name="i-lucide-info" class="w-3.5 h-3.5" />
            Formatos permitidos: JPG, PNG, WEBP. Tamaño máximo: 5MB.
          </p>
        </div>
      </div>
    </UCard>
  </div>
</template>
