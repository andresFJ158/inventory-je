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
    '/views/**': { proxy: 'http://api/views/**' }
  },

  runtimeConfig: {
    apiInternalUrl: process.env.API_INTERNAL_URL || 'http://localhost:8081',
    apiToken: process.env.API_TOKEN || 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy',
    public: {
      apiBase: '/api',
      ajaxBase: '/ajax/pos.ajax.php'
    }
  },

  compatibilityDate: '2025-01-15',

  typescript: {
    typeCheck: false
  }
})
