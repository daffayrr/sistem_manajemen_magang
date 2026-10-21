describe('Testing Fungsionalitas dan Navigasi Dashboard Admin', () => {
  
    beforeEach(() => {
      // Clear cookies and session to avoid auto-redirect if already logged in
      cy.clearCookies();
      cy.clearLocalStorage();

      // Skenario bypass login untuk masuk ke dashboard
      cy.visit('/index.php');
      cy.get('input[name="username"]').type('admin'); // Sesuaikan kredensial di DB Anda
      cy.get('input[name="password"]').type('admin123'); 
      cy.get('button[type="submit"]').click();
  
      // Memastikan sudah berada di halaman dashboard
      cy.url().should('include', '/admin_magang/dashboard.php');
    });
  
    it('TC-01: Memvalidasi seluruh Widget dan Elemen UI tampil dengan benar', () => {
      // Memastikan Alert Informasi FIFO
      cy.contains('Metode FIFO Aktif!').should('be.visible');
      
      // Memastikan Widget Statistik
      cy.contains('Sisa Kuota Magang').should('be.visible');
      cy.contains('Menunggu Verifikasi').should('be.visible');
      
      // Memastikan Widget Tabel dan Aksi Cepat
      cy.contains('Antrean Masuk (FIFO)').should('be.visible');
      cy.contains('Aksi Cepat Admin').should('be.visible');
      cy.contains('Status Sistem').should('be.visible');
    });
  
    it('TC-02: Menguji seluruh navigasi pada widget Aksi Cepat Admin', () => {
      // 1. Uji klik Update Kuota
      cy.contains('Update Kuota Magang').click();
      cy.url().should('include', 'kuota.php');
      cy.go('back'); // Kembali ke Dashboard
  
      // 2. Uji klik Edit Pengumuman
      cy.contains('Edit Pengumuman').click();
      cy.url().should('include', 'informasi.php');
      cy.go('back');
  
      // 3. Uji klik Tambah Admin Baru
      cy.contains('Tambah Admin Baru').click();
      cy.url().should('include', 'admin.php');
      cy.go('back');
      
      // Memastikan berhasil kembali dan masih di Dashboard
      cy.url().should('include', '/admin_magang/dashboard.php');
    });
  
    it('TC-03: Menguji navigasi pada Tabel Antrean FIFO', () => {
      // 1. Uji klik tombol "Lihat Semua" pada header tabel
      cy.contains('Lihat Semua').click();
      cy.url().should('include', 'peserta.php');
      cy.go('back');
  
      // 2. Uji klik tombol "Verifikasi" pada baris tabel (jika ada data pendaftar)
      cy.get('table tbody').then(($tbody) => {
        // Mengecek apakah ada tombol verifikasi (artinya tabel tidak kosong)
        if ($tbody.find('a.btn-primary').length > 0) {
          cy.get('table tbody a.btn-primary').first().click();
          
          // Memastikan sistem mengarah ke halaman detail_peserta dengan ID
          cy.url().should('include', 'detail_peserta.php?id=');
          
          cy.go('back');
          cy.url().should('include', '/admin_magang/dashboard.php');
        } else {
          // Jika tabel kosong, Cypress akan mencetak log ini tanpa membuat test gagal
          cy.log('Tabel FIFO kosong, melewati uji klik tombol Verifikasi.');
        }
      });
    });
  
    it('TC-04: Menguji tautan pada Sidebar Menu', () => {
      // Uji klik menu Verifikasi Peserta di Sidebar
      cy.get('.sidebar').contains('Verifikasi Peserta').click();
      cy.url().should('include', 'peserta.php');
      cy.go('back');
  
      // Uji klik menu Kuota Magang di Sidebar
      cy.get('.sidebar').contains('Kuota Magang').click();
      cy.url().should('include', 'kuota.php');
      cy.go('back');
    });
  
  });
