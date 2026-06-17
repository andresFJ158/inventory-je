// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  app: {
    head: {
      title: 'J.E Bolivia ERP',
      link: [{ rel: 'icon', type: 'image/png', href: '/favicon.png' }]
    }
  },

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
    '/ajax/**': { proxy: 'http://localhost:8081/ajax/**' },
    '/views/**': { proxy: 'http://localhost:8081/views/**' }
  },

  runtimeConfig: {
    apiInternalUrl: process.env.API_INTERNAL_URL || 'http://localhost:8081',
    apiToken: process.env.API_TOKEN || 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy',
    public: {
      apiBase: '/api',
      ajaxBase: '/ajax/pos.ajax.php'
    }
  },

  colorMode: {
    preference: 'light',
    fallback: 'light',
    classSuffix: ''
  },

  compatibilityDate: '2025-01-15',

  typescript: {
    typeCheck: false
  }
})
