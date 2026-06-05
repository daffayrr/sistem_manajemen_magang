const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost/_Tekkomdik/sistem_manajemen_magang',
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
});