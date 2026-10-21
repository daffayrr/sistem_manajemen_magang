const { defineConfig } = require('cypress');

module.exports = defineConfig({
  reporter: 'cypress-mochawesome-reporter',
  reporterOptions: {
    inlineAssets: true, 
  },
  e2e: {
    // 1. Tambahkan waktu tunggu lebih lama
    pageLoadTimeout: 120000, 
    // 2. Pastikan baseUrl mengarah ke folder sistem Anda
    baseUrl: 'http://localhost/_tekkomdik/sistem_manajemen_magang/', 
    setupNodeEvents(on, config) {
      require('cypress-mochawesome-reporter/plugin')(on);
    },
  },
});