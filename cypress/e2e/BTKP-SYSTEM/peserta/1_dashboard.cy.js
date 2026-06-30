describe('Testing Fungsionalitas dan Navigasi Dashboard Peserta', () => {
  
    beforeEach(() => {
      // Skenario bypass login untuk masuk ke dashboard peserta
      cy.visit('/index.php');
      
      // Sesuaikan username (email) dan password dengan data tester di database Anda
      cy.get('input[name="username"]').type('farras.yassarramadhan@toscaflow.id'); 
      cy.get('input[name="password"]').type('abc_123');
      cy.get('button[type="submit"]').click();
  
      // Memastikan berhasil masuk ke dashboard
      cy.visit('/peserta_magang/index.php');
      cy.url().should('include', '/peserta_magang/index.php');
    });
  
    it('TC-01: Memvalidasi seluruh Elemen UI dan Widget tampil dengan benar', () => {
      // Memvalidasi Banner Sambutan (Gradient Blue)
      cy.contains('Portal Peserta').should('be.visible');
      cy.contains('Halo,').should('be.visible'); 
      cy.contains('Selamat datang di Sistem Informasi Penerimaan Magang').should('be.visible');
  
      // Memvalidasi Widget Status Pengajuan Magang
      cy.contains('Status Pengajuan Magang').should('be.visible');
  
      // Memvalidasi Widget Akses Cepat
      cy.contains('Akses Cepat').should('be.visible');
    });
  
    it('TC-02: Menguji navigasi pada widget Akses Cepat (Quick Links)', () => {
      // 1. Uji klik menu Unduhan
      cy.contains('Cek Hasil & Unduhan').click();
      cy.url().should('include', 'unduhan.php');
      cy.go('back'); // Tarik kembali ke Dashboard Peserta
  
      // 2. Uji klik menu Panduan
      cy.contains('Buku Panduan Magang').click();
      cy.url().should('include', 'panduan.php');
      cy.go('back');
  
      // 3. Uji klik menu Pendaftaran (Profil & Form)
      cy.contains('Profil & Form Pendaftaran').click();
      cy.url().should('include', 'pendaftaran.php');
      cy.go('back');
  
      // Memastikan berhasil kembali dengan selamat ke Dashboard
      cy.url().should('include', '/peserta_magang/index.php');
    });
  
    it('TC-03: Menguji tombol pada Widget Status (Kondisional Belum Daftar)', () => {
      // Sistem Anda dinamis. Jika user "Belum Daftar", maka muncul tombol "Isi Formulir Pendaftaran".
      // Kita gunakan teknik kondisional Cypress untuk mencegah test gagal jika user ini sudah daftar.
      
      cy.get('.card-body').then(($body) => {
        // Mengecek apakah tombol "Isi Formulir Pendaftaran" muncul di layar
        if ($body.find('a[href="pendaftaran.php"]').text().includes('Isi Formulir Pendaftaran')) {
          
          // Jika ada, klik tombol tersebut
          cy.contains('Isi Formulir Pendaftaran').click();
          
          // Validasi pindah ke halaman form
          cy.url().should('include', 'pendaftaran.php');
          
          // Putar balik ke dashboard
          cy.go('back');
          cy.url().should('include', '/peserta_magang/index.php');
          
        } else {
          // Jika tidak ada (artinya status user Menunggu/Diterima/Selesai), cetak log saja
          cy.log('User sudah memiliki status pengajuan magang, melewati uji tombol daftar awal.');
          cy.contains('Status:').should('be.visible');
        }
      });
    });
  
    it('TC-04: Memastikan fitur Logout berfungsi dari Sidebar Peserta', () => {
      // Menguji tombol "Keluar" yang ada di komponen sidebar/navbar
      cy.contains('Keluar').click(); 
  
      // Memastikan sistem menghancurkan sesi dan melempar user ke halaman login utama
      cy.url().should('include', '/index.php');
  
      // Memvalidasi alert sukses logout dari PHP
      cy.get('.alert-success').should('be.visible').and('contain', 'berhasil logout');
    });
  
  });