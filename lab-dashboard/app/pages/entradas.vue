<script setup lang="ts">
/** Entradas de materia prima — redirige al módulo de inventario M.P. */
definePageMeta({ middleware: ['auth'] })
await navigateTo('/inventario', { replace: true })
</script>

<template>
  <div class="flex justify-center py-20">
    <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-green-500" />
  </div>
</template>
