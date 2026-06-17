<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()
const moduleName = computed(() => {
  // Capture dynamic module parameter (e.g. "sucursales")
  const m = route.params.module
  return (Array.isArray(m) ? m[0] : m) || ''
})
</script>

<template>
  <div class="flex-1 flex flex-col min-h-0">
    <DynamicTable :module-name="moduleName" />
  </div>
</template>
