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
    '/api/**': { proxy: 'http://api/**' },
    '/views/**': { proxy: 'http://api/views/**' }
  },

  runtimeConfig: {
    public: {
      // Base de la API REST y del endpoint AJAX del POS.
      apiBase: '',
      ajaxBase: '/ajax/pos.ajax.php',
      // Token de la API. Configurable por entorno (no hardcodear en cada página).
      apiToken: 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
    }
  },

  compatibilityDate: '2025-01-15',

  typescript: {
    typeCheck: false
  }
})
