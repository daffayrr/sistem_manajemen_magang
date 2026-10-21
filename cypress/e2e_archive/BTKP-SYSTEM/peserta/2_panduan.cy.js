describe('Testing Fungsionalitas Halaman Panduan Magang', () => {
  
    beforeEach(() => {
      // Proses Autentikasi Peserta
      cy.visit('/index.php');
      
      // Gunakan akun peserta yang valid di database Anda
      cy.get('input[name="username"]').type('farras.yassarramadhan@toscaflow.id'); 
      cy.get('input[name="password"]').type('abc_123');
      cy.get('button[type="submit"]').click();
  
      // Masuk ke halaman Panduan secara langsung
      cy.visit('/peserta_magang/panduan.php');
      cy.url().should('include', '/peserta_magang/panduan.php');
    });
  
    it('TC-01: Memvalidasi seluruh teks statis dan langkah pendaftaran tampil di layar', () => {
      // Memastikan Judul Halaman
      cy.contains('Panduan Pendaftaran').should('be.visible');
      
      // Memastikan 5 Langkah Pendaftaran ter-render
      cy.contains('Registrasi & Login Akun').should('be.visible');
      cy.contains('Lengkapi Formulir Pendaftaran').should('be.visible');
      cy.contains('Unggah Berkas Persyaratan').should('be.visible');
      cy.contains('Tunggu Verifikasi (Sistem FIFO)').should('be.visible');
      cy.contains('Pengumuman & Unduhan').should('be.visible');
  
      // Memastikan Widget Info Penting
      cy.contains('Info Penting').should('be.visible');
    });
  
    it('TC-02: Menguji fungsionalitas komponen Accordion pada Pertanyaan Umum (FAQ)', () => {
      // 1. Menguji FAQ Pertama
      cy.contains('Berapa lama proses verifikasi?').click();
      // Memastikan jawaban muncul di layar (menunggu animasi slide down Bootstrap)
      cy.get('#faq1').should('be.visible').and('contain', '3-5 hari kerja');
  
      // 2. Menguji FAQ Kedua
      cy.contains('Apa itu metode FIFO?').click();
      cy.get('#faq2').should('be.visible').and('contain', 'First In First Out');
  
      // 3. Menguji FAQ Ketiga
      cy.contains('Bagaimana jika ditolak?').click();
      cy.get('#faq3').should('be.visible').and('contain', 'mencoba mendaftar lagi di periode berikutnya');
    });
  
    it('TC-03: Memvalidasi tautan bantuan Eksternal (WA) dan kembali ke Dashboard', () => {
      // Memastikan tombol "Hubungi Admin" mengarah ke URL WA yang benar
      // dan memiliki atribut target="_blank" agar terbuka di tab baru
      cy.get('a[href^="https://wa.me/"]')
        .should('have.attr', 'href', 'https://wa.me/6281234567890')
        .and('have.attr', 'target', '_blank');
  
      // Karena tidak ada tombol "Back" internal di halaman ini, 
      // kita akan menguji tautan 'Dashboard' pada Sidebar untuk kembali ke menu utama
      cy.get('.sidebar').contains('Panduan Sistem').click();
      
      // Memastikan berhasil kembali ke halaman Dashboard
      cy.url().should('include', '/peserta_magang/panduan.php');
    });
  
  });