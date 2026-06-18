<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{
  isOpen: boolean
  orderId: string | number | null
}>()

const emit = defineEmits(['update:isOpen', 'close', 'updated'])

const auth = useAuthStore()
const toast = useToast()

const loading = ref(false)
const orderData = ref<any>(null)
const salesData = ref<any[]>([])

const uploadingImage = ref(false)

const isOpenModel = computed({
  get: () => props.isOpen,
  set: (val) => {
    emit('update:isOpen', val)
    if (!val) emit('close')
  }
})

async function fetchOrderDetails(id: string | number) {
  loading.value = true
  orderData.value = null
  salesData.value = []
  
  try {
    const apiHeaders = { Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy' }
    
    // Fetch Order Info
    const orderRes = await $fetch<any>(`/api/relations?rel=orders,clients,offices,admins&type=order,client,office,admin&linkTo=id_order&equalTo=${id}`, { headers: apiHeaders })
    if (orderRes && orderRes.status === 200 && orderRes.results && orderRes.results.length > 0) {
      orderData.value = orderRes.results[0]
      
      // Fetch Sales Info (Products)
      const salesRes = await $fetch<any>(`/api/relations?rel=sales,products&type=sale,product&linkTo=id_order_sale&equalTo=${id}`, { headers: apiHeaders })
      if (salesRes && salesRes.status === 200 && salesRes.results) {
        salesData.value = salesRes.results
      }
    }
  } catch (e) {
    console.error('Error fetching order details:', e)
  } finally {
    loading.value = false
  }
}

watch(() => props.isOpen, (newVal) => {
  if (newVal && props.orderId) {
    fetchOrderDetails(props.orderId)
  }
})

function formatCurrency(val: number | string) {
  return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(Number(val))
}

function decodeStr(str: string) {
  if (!str) return ''
  return decodeURIComponent(str).replace(/\+/g, ' ')
}

const requiresVoucher = computed(() => {
  if (!orderData.value) return false
  const m = String(orderData.value.method_order).toLowerCase()
  return m.includes('qr') || m.includes('transferencia')
})

const hasVoucherImage = computed(() => {
  if (!orderData.value) return false
  const ref = orderData.value.qr_ref_order || orderData.value.transfer_order || ''
  return ref.includes('/img/') || ref.match(/\.(jpeg|jpg|gif|png|webp)$/i) !== null
})

const voucherText = computed(() => {
  if (!orderData.value) return ''
  const ref = orderData.value.qr_ref_order || orderData.value.transfer_order || ''
  if (!hasVoucherImage.value && ref) return ref
  return ''
})

async function handleImageUpload(event: Event) {
  const fileInput = event.target as HTMLInputElement
  const file = fileInput.files?.[0]
  if (!file) return

  uploadingImage.value = true
  const formData = new FormData()
  formData.append('imageFile', file)
  formData.append('uploadImage', 'ok')

  try {
    const res = await $fetch<any>('/ajax/pos.ajax.php', {
      method: 'POST',
      body: formData
    })
    
    const data = typeof res === 'string' ? JSON.parse(res) : res
    
    if (data.status === 200 && data.url) {
      // Image uploaded successfully, now update order in DB
      await updateOrderVoucher(data.url)
    } else {
      toast.add({ title: 'Error al subir la imagen', color: 'error' })
    }
  } catch (e) {
    console.error('Upload error:', e)
    toast.add({ title: 'Fallo la conexión al subir imagen', color: 'error' })
  } finally {
    uploadingImage.value = false
    fileInput.value = ''
  }
}

async function updateOrderVoucher(imageUrl: string) {
  if (!orderData.value) return
  
  try {
    // Determine which field to update based on payment method
    const field = orderData.value.method_order === 'QR' ? 'qr_ref_order' : 'transfer_order'
    
    const updateBody = new URLSearchParams()
    updateBody.append('id_order', orderData.value.id_order)
    updateBody.append(field, imageUrl)
    // Send PUT request
    const putBody = new URLSearchParams()
    putBody.append('data', updateBody.toString())

    const res = await $fetch<any>(`/api/orders?id=${orderData.value.id_order}&nameId=id_order`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        Authorization: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
      },
      body: updateBody.toString()
    })

    if (res.status === 200) {
      toast.add({ title: 'Comprobante guardado con éxito', color: 'success' })
      orderData.value[field] = imageUrl
      emit('updated')
    } else {
      toast.add({ title: 'Error al actualizar orden', color: 'error' })
    }
  } catch (error) {
    console.error('Update voucher error:', error)
    toast.add({ title: 'Error al actualizar el comprobante en la base de datos', color: 'error' })
  }
}

function getImageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `/${path.replace(/^\/+/, '')}` // prepend slash safely
}

</script>

<template>
  <USlideover
    v-model:open="isOpenModel"
    title="Detalles de la Orden"
    class="z-50"
    :ui="{ content: 'sm:max-w-2xl w-full' }"
  >
    <template #body>
      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="w-10 h-10 animate-spin text-primary mb-2" />
        <p class="text-gray-500">Cargando detalles de orden...</p>
      </div>

      <!-- Content -->
      <template v-else-if="orderData">
        <div class="mb-6 flex justify-between items-start">
          <div>
            <h3 class="text-xl font-bold text-slate-800">Orden {{ orderData.transaction_order }}</h3>
            <p class="text-slate-500 text-sm mt-1">Fecha: {{ new Date(orderData.date_order).toLocaleString('es-ES') }}</p>
          </div>
          <UBadge :color="orderData.status_order === 'Completada' ? 'success' : 'warning'" variant="subtle" size="lg">
            {{ orderData.status_order }}
          </UBadge>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
          <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
            <span class="block text-slate-500 text-xs font-semibold uppercase mb-1">Cliente</span>
            <span class="font-medium text-slate-800">{{ decodeStr(orderData.name_client) }} {{ decodeStr(orderData.surname_client) }}</span>
          </div>
          <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
            <span class="block text-slate-500 text-xs font-semibold uppercase mb-1">Vendedor</span>
            <span class="font-medium text-slate-800">{{ decodeStr(orderData.name_admin) }} {{ decodeStr(orderData.surname_admin) }}</span>
          </div>
          <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
            <span class="block text-slate-500 text-xs font-semibold uppercase mb-1">Sucursal</span>
            <span class="font-medium text-slate-800">{{ decodeStr(orderData.title_office) }}</span>
          </div>
          <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
            <span class="block text-slate-500 text-xs font-semibold uppercase mb-1">Método de Pago</span>
            <span class="font-medium text-slate-800 capitalize">{{ orderData.method_order }}</span>
          </div>
        </div>

        <!-- Products Table -->
        <h4 class="font-bold text-slate-700 uppercase border-b border-slate-200 pb-2 mb-3 text-sm flex items-center gap-2">
          <UIcon name="i-lucide-shopping-cart" class="text-primary" /> Productos Vendidos
        </h4>
        
        <div v-if="salesData.length === 0" class="text-center py-4 text-slate-400 italic text-sm">
          No hay productos registrados.
        </div>
        <div v-else class="space-y-2 mb-6">
          <div v-for="sale in salesData" :key="sale.id_sale" class="flex justify-between items-center p-3 border border-slate-100 rounded-lg hover:bg-slate-50 transition-colors">
            <div>
              <div class="font-medium text-slate-800 text-sm">{{ decodeStr(sale.title_product) }}</div>
              <div class="text-xs text-slate-500 mt-0.5">Cant: <span class="font-semibold">{{ sale.qty_sale }}</span> × {{ formatCurrency(sale.price_sale) }}</div>
            </div>
            <div class="font-bold text-slate-800">
              {{ formatCurrency(sale.subtotal_sale) }}
            </div>
          </div>
        </div>

        <!-- Order Totals -->
        <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100 mb-6">
          <div class="flex justify-between text-sm text-slate-600 mb-1">
            <span>Subtotal</span>
            <span>{{ formatCurrency(orderData.subtotal_order) }}</span>
          </div>
          <div v-if="parseFloat(orderData.discount_order) > 0" class="flex justify-between text-sm text-red-500 mb-1">
            <span>Descuento</span>
            <span>- {{ formatCurrency(orderData.discount_order) }}</span>
          </div>
          <div class="flex justify-between text-lg font-bold text-emerald-800 mt-2 pt-2 border-t border-emerald-200/60">
            <span>Total</span>
            <span>{{ formatCurrency(orderData.total_order) }}</span>
          </div>
        </div>

        <!-- Voucher / Comprobante Section -->
        <div v-if="requiresVoucher" class="mb-6">
          <h4 class="font-bold text-slate-700 uppercase border-b border-slate-200 pb-2 mb-3 text-sm flex items-center gap-2">
            <UIcon name="i-lucide-receipt" class="text-purple-500" /> Comprobante de Pago
          </h4>

          <div v-if="hasVoucherImage" class="mt-4 border border-slate-200 rounded-lg p-2 bg-slate-50 w-max mx-auto">
            <a :href="getImageUrl(orderData.qr_ref_order || orderData.transfer_order)" target="_blank" class="block group relative">
              <img :src="getImageUrl(orderData.qr_ref_order || orderData.transfer_order)" class="max-w-[200px] rounded object-cover shadow-sm transition-opacity group-hover:opacity-90" alt="Comprobante">
              <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40 rounded">
                <UIcon name="i-lucide-external-link" class="text-white w-6 h-6" />
              </div>
            </a>
            <div class="text-center mt-2 text-xs text-slate-500">Clic para ampliar</div>
          </div>
          
          <div v-else-if="voucherText" class="p-3 bg-purple-50 text-purple-800 border border-purple-100 rounded-lg text-sm font-mono mb-4 text-center">
            Ref: {{ voucherText }}
          </div>

          <div class="mt-4 border-2 border-dashed border-slate-200 rounded-lg p-6 text-center hover:bg-slate-50 transition-colors" v-if="!hasVoucherImage">
            <input
              type="file"
              id="voucher-upload"
              accept="image/*"
              class="hidden"
              @change="handleImageUpload"
              :disabled="uploadingImage"
            />
            <label for="voucher-upload" class="cursor-pointer flex flex-col items-center">
              <UIcon v-if="uploadingImage" name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-slate-400 mb-2" />
              <UIcon v-else name="i-lucide-upload-cloud" class="w-8 h-8 text-slate-400 mb-2" />
              <span class="text-sm font-medium text-slate-700">{{ uploadingImage ? 'Subiendo imagen...' : 'Subir Imagen del Comprobante' }}</span>
              <span class="text-xs text-slate-500 mt-1">Formatos soportados: JPG, PNG, WEBP</span>
            </label>
          </div>
          
          <div class="mt-4 text-center" v-if="hasVoucherImage">
            <input
              type="file"
              id="voucher-reupload"
              accept="image/*"
              class="hidden"
              @change="handleImageUpload"
              :disabled="uploadingImage"
            />
            <label for="voucher-reupload" class="cursor-pointer text-xs text-primary hover:underline flex items-center justify-center gap-1">
              <UIcon v-if="uploadingImage" name="i-heroicons-arrow-path" class="animate-spin w-3 h-3" />
              <UIcon v-else name="i-lucide-refresh-cw" class="w-3 h-3" />
              {{ uploadingImage ? 'Subiendo...' : 'Actualizar Comprobante' }}
            </label>
          </div>
        </div>

      </template>

      <!-- Error State -->
      <div v-else class="py-12 text-center">
        <UIcon name="i-lucide-alert-triangle" class="w-12 h-12 text-red-400 mx-auto mb-3" />
        <p class="text-lg font-medium text-slate-700">No se pudieron cargar los detalles</p>
      </div>
    </template>
    
    <template #footer>
      <div class="flex justify-end gap-3 w-full">
        <UButton color="neutral" variant="ghost" @click="isOpenModel = false">Cerrar</UButton>
      </div>
    </template>
  </USlideover>
</template>
