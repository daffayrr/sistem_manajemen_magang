describe('Testing Fitur Peserta: Informasi Kuota dan Cek Posisi Magang (FIFO)', () => {

  const testEmail = 'farras.yassarramadhan@toscaflow.id';
  const testPassword = 'abc_123';

  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();

    // Login sebagai peserta
    cy.visit('/index.php');
    cy.get('input[name="username"]').type(testEmail);
    cy.get('input[name="password"]').type(testPassword);
    cy.get('button[type="submit"]').click();

    // Memastikan berhasil login ke portal peserta
    cy.url().should('include', '/peserta_magang/index.php');
  });

  it('TC-01: Memvalidasi keberadaan Menu "Cek Posisi dan Kuota" pada Sidebar Peserta', () => {
    cy.get('.sidebar').contains('Cek Posisi dan Kuota').should('be.visible');
  });

  it('TC-02: Membuka halaman Cek Posisi dan Kuota dan memvalidasi seluruh Widget Kuota', () => {
    cy.get('.sidebar').contains('Cek Posisi dan Kuota').click();
    cy.url().should('include', '/peserta_magang/posisi_kuota.php');

    // Validasi Header Halaman
    cy.contains('Cek Posisi dan Kuota Magang').should('be.visible');

    // Validasi 4 Kartu Statistik Kuota
    cy.contains('Kuota Maksimal').should('be.visible');
    cy.contains('Kuota Terpakai').should('be.visible');
    cy.contains('Sisa Kuota Tersedia').should('be.visible');
    cy.contains('Antrean Menunggu').should('be.visible');
  });

  it('TC-03: Memvalidasi Tabel Transparansi Antrean FIFO & Kolom Wajib (ID Peserta, Periode, Timestamp)', () => {
    cy.visit('/peserta_magang/posisi_kuota.php');

    cy.contains('Transparansi Antrean Pendaftar (Metode FIFO)').should('be.visible');

    // Validasi Kolom Wajib pada Header Tabel
    cy.get('table thead').within(() => {
      cy.contains('Posisi').should('be.visible');
      cy.contains('ID Peserta').should('be.visible');
      cy.contains('Periode Magang').should('be.visible');
      cy.contains('Timestamp Mendaftar').should('be.visible');
      cy.contains('Status Antrean').should('be.visible');
    });

    // Memastikan privasi data: data pribadi nomor HP, alamat, email tidak bocor di tabel antrean umum
    cy.get('table').should('not.contain', '0812');
    cy.get('table').should('not.contain', '@toscaflow.id');
  });

  it('TC-04: Memvalidasi Informasi Kuota Tersedia pada Halaman Formulir Pendaftaran', () => {
    cy.visit('/peserta_magang/pendaftaran.php');

    // Memvalidasi Card Informasi Kuota Muncul sebelum Formulir
    cy.contains('Informasi Kuota Magang Tersedia').should('be.visible');
    cy.contains('Kuota Maksimum').should('be.visible');
    cy.contains('Kuota Terpakai').should('be.visible');
    cy.contains('Sisa Kuota Tersedia').should('be.visible');
    cy.contains('Status Pendaftaran').should('be.visible');
  });

});
