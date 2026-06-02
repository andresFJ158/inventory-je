<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()
const moduleName = computed(() => {
  // Capture dynamic module parameter (e.g. "sucursales")
  const m = route.params.module
  return Array.isArray(m) ? m[0] : m
})
</script>

<template>
  <div class="container-fluid">
    <DynamicTable :module-name="moduleName" />
  </div>
</template>
