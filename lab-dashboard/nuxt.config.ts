// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: [
    '@nuxt/eslint',
    '@nuxt/ui',
    '@pinia/nuxt'
  ],

  devtools: {
    enabled: true
  },

  fonts: {
    providers: {
      google: false
    }
  },

  css: ['~/assets/css/main.css'],

  routeRules: {
    '/ajax/**': { proxy: 'http://api/ajax/**' },
    '/api/**': { proxy: 'http://api/**' }
  },

  runtimeConfig: {
    public: {
      // Base de la API REST y del endpoint AJAX del POS.
      apiBase: process.env.NUXT_PUBLIC_API_BASE || '',
      ajaxBase: process.env.NUXT_PUBLIC_AJAX_BASE || '/ajax/pos.ajax.php',
      // Token de la API. Configurable por entorno (no hardcodear en cada página).
      apiToken: process.env.NUXT_PUBLIC_API_TOKEN || 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
    }
  },

  compatibilityDate: '2025-01-15',

  typescript: {
    typeCheck: false
  }
})
