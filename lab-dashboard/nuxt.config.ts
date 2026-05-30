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

  css: ['~/assets/css/main.css'],

  routeRules: {
    '/': { prerender: true },
    '/ajax/**': { proxy: 'http://localhost:8080/ajax/**' }
  },

  compatibilityDate: '2025-01-15',

  typescript: {
    typeCheck: false
  }
})
