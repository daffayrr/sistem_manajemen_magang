describe('Testing Fungsionalitas Halaman Informasi Umum (informasi.php)', () => {
  
  beforeEach(() => {
    // Skenario bypass login untuk masuk ke dashboard admin
    cy.visit('/index.php');
    cy.get('input[name="username"]').type('admin'); // Sesuaikan kredensial di DB
    cy.get('input[name="password"]').type('admin123'); 
    cy.get('button[type="submit"]').click();

    // Masuk ke halaman Informasi Umum
    cy.visit('/admin_magang/informasi.php');
    cy.url().should('include', '/admin_magang/informasi.php');
  });

  it('TC-01: Memvalidasi seluruh elemen Form dan Widget Preview tampil di layar', () => {
    // Memvalidasi Form Edit
    cy.get('input[name="judul"]').should('be.visible');
    cy.get('textarea[name="deskripsi"]').should('be.visible');
    cy.get('button[name="simpan_info"]').should('be.visible').and('contain', 'Simpan Perubahan');

    // Memvalidasi Widget Preview Peserta (Simulasi Layar HP)
    cy.contains('Preview Peserta').should('be.visible');
    cy.contains('Pengumuman Terbaru').should('be.visible');
  });

  it('TC-02: Memperbarui isi pengumuman dan memvalidasi notifikasi sukses', () => {
    // Membuat teks dinamis menggunakan waktu agar selalu unik saat testing diulang
    const judulBaru = 'Pengumuman Penting E2E - ' + Date.now();
    const deskripsiBaru = 'Ini adalah deskripsi pengumuman magang yang diupdate secara otomatis oleh sistem testing Cypress untuk keperluan pengujian Blackbox skripsi.';

    // Menghapus isi input lama dan menggantinya dengan teks baru
    cy.get('input[name="judul"]').clear().type(judulBaru);
    cy.get('textarea[name="deskripsi"]').clear().type(deskripsiBaru);

    // Menyimpan perubahan
    cy.get('button[name="simpan_info"]').click();

    // Validasi 1: Tetap berada di halaman informasi.php
    cy.url().should('include', '/admin_magang/informasi.php');

    // Validasi 2: Muncul alert sukses hijau
    cy.get('.alert-success')
      .should('be.visible')
      .and('contain', 'Informasi dan Pengumuman berhasil diperbarui!');

    // Validasi 3: Memastikan input box sekarang berisi data terbaru (tersimpan ke database)
    cy.get('input[name="judul"]').should('have.value', judulBaru);
    cy.get('textarea[name="deskripsi"]').should('have.value', deskripsiBaru);
  });

  it('TC-03: Memastikan Widget Preview merender data judul secara real-time', () => {
    // Kita mengambil nilai (value) judul dari dalam form input
    cy.get('input[name="judul"]').invoke('val').then((judulTersimpan) => {
      
      // Lalu kita cocokkan apakah judul di dalam card "Preview Peserta" 
      // sama dengan judul yang ada di dalam form input
      cy.get('.card-header h6.text-truncate').should('contain', judulTersimpan);
    });
  });

});