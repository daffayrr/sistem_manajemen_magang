const { defineConfig } = require("cypress");

module.exports = defineConfig({
  reporter: 'cypress-mochawesome-reporter', // Hasil untuk report
  reporterOptions: {
    charts: true,              // Mengaktifkan grafik Pie Chart
    reportPageTitle: 'Blackbox Test Sistem Magang BTKP', // Judul tab di browser
    embeddedScreenshots: true, // Memasukkan screenshot ke dalam report jika ada error
    inlineAssets: true,        // Menyatukan file aset (CSS/JS) agar HTML bisa dibuka secara offline
    saveAllAttempts: false,
  },
  projectId: "6t7fi1",

  e2e: {
    baseUrl: "http://localhost/_Tekkomdik/sistem_manajemen_magang",
    setupNodeEvents(on, config) {
      // implement node event listeners here
      require('cypress-mochawesome-reporter/plugin')(on); // Plugin report
    },
  },

  component: {
    devServer: {
      framework: "next",
      bundler: "webpack",
    },
  },
});