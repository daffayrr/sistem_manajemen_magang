describe('Testing Fungsionalitas dan Navigasi Halaman Kelola Peserta', () => {
  
    beforeEach(() => {
      // Skenario bypass login
      cy.visit('/index.php');
      cy.get('input[name="username"]').type('admin'); // Sesuaikan kredensial admin
      cy.get('input[name="password"]').type('admin123'); 
      cy.get('button[type="submit"]').click();
  
      // Memastikan berada di halaman Peserta (default: tab Menunggu)
      cy.visit('/admin_magang/peserta.php');
      cy.url().should('include', '/admin_magang/peserta.php');
    });
  
    it('TC-01: Menguji navigasi pada Filter Status Peserta', () => {
      // 1. Klik filter Diterima menggunakan target URL href (Paling Aman)
      cy.get('a[href="peserta.php?filter=Diterima"]').click();
      cy.url().should('include', 'filter=Diterima');
      cy.go('back'); // Kembali ke halaman peserta awal
  
      // 2. Klik filter Ditolak
      cy.get('a[href="peserta.php?filter=Ditolak"]').click();
      cy.url().should('include', 'filter=Ditolak');
      cy.go('back');
  
      // 3. Klik filter Arsip (Selesai)
      cy.get('a[href="peserta.php?filter=Selesai"]').click();
      cy.url().should('include', 'filter=Selesai');
      cy.go('back');
  
      // Memastikan berhasil ditarik kembali ke halaman utama peserta
      cy.url().should('include', '/admin_magang/peserta.php');
    });
  
    it('TC-02: Menguji fungsionalitas kotak Pencarian (Search)', () => {
      // Mengetikkan kata kunci di input pencarian
      cy.get('input[name="q"]').type('Alma Ata');
      
      // Klik tombol submit pencarian (ikon kaca pembesar)
      cy.get('button[type="submit"]').click();
  
      // Memastikan URL termodifikasi dengan query string 'q'
      cy.url().should('include', 'q=Alma+Ata');
  
      // Menggunakan cy.go('back') untuk menghapus pencarian dan kembali ke tabel utuh
      cy.go('back');
      cy.get('input[name="q"]').should('have.value', '');
    });
  
    it('TC-03: Menguji navigasi tombol Detail ke halaman Data Bucket', () => {
      // Mengambil baris tabel pendaftar (jika tabel tidak kosong)
      cy.get('table tbody').then(($tbody) => {
        // Cek apakah ada tombol "Detail" (menghindari error jika db kosong)
        if ($tbody.find('a.btn-outline-primary').length > 0) {
          
          // Klik tombol Detail pada baris pertama
          cy.get('table tbody a.btn-outline-primary').first().click();
          
          // Memastikan sistem masuk ke halaman detail peserta
          cy.url().should('include', 'detail_peserta.php?id=');
          
          // Putar balik ke halaman Kelola Peserta
          cy.go('back');
          cy.url().should('include', '/admin_magang/peserta.php');
        } else {
          cy.log('Tabel peserta kosong, pengujian tombol Detail dilewati.');
        }
      });
    });
  
    it('TC-04: Menguji pemicu (trigger) SweetAlert pada tombol Aksi', () => {
      // Test ini memastikan tombol aksi memunculkan popup konfirmasi,
      // namun kita menekan "Batal/Cancel" agar tidak mengubah data di database saat testing navigasi.
      
      cy.get('table tbody').then(($tbody) => {
        // Mengecek apakah ada tombol Terima/Tolak (status Menunggu)
        if ($tbody.find('.btn-icon-action').length > 0) {
          
          // 1. Uji klik tombol Tolak (X merah)
          cy.get('button[title="Tolak"]').first().click();
          
          // Memastikan SweetAlert muncul
          cy.get('.swal2-popup').should('be.visible');
          cy.contains('Tolak Peserta?').should('be.visible'); // Memvalidasi judul alert
          
          // Klik tombol batal pada SweetAlert (tidak mengeksekusi PHP-nya)
          cy.get('.swal2-cancel').click(); 
          
          // Memastikan popup tertutup dan tetap di halaman peserta
          cy.get('.swal2-popup').should('not.exist');
          cy.url().should('include', '/admin_magang/peserta.php');
        } else {
          cy.log('Tidak ada peserta berstatus Menunggu, melewati uji klik tombol Aksi.');
        }
      });
    });
  
  });