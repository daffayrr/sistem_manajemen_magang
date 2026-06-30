describe('Testing Fungsionalitas Pengaturan Kuota Admin', () => {
  
    beforeEach(() => {
      // Skenario bypass login
      cy.visit('/index.php');
      cy.get('input[name="username"]').type('admin'); // Sesuaikan kredensial
      cy.get('input[name="password"]').type('admin123'); 
      cy.get('button[type="submit"]').click();
  
      // Masuk ke halaman pengaturan kuota
      cy.visit('/admin_magang/kuota.php');
      cy.url().should('include', '/admin_magang/kuota.php');
    });
  
    it('TC-01: Mengubah kuota secara berulang (Stress Test) dan diakhiri dengan angka 4', () => {
      // Daftar angka kuota yang akan disimulasikan secara berurutan
      // Angka terakhir wajib 4 sesuai dengan skenario akhir skripsi
      const skenarioPerubahanKuota = [10, 25, 8, 15, 4];
  
      // Cypress akan melakukan looping untuk setiap angka di atas
      skenarioPerubahanKuota.forEach((kuotaBaru) => {
        
        // 1. Membersihkan kolom input dan mengetik angka baru
        cy.get('input[name="kuota_maksimal"]').clear().type(kuotaBaru.toString());
        
        // 2. Mengeklik tombol "Simpan Pengaturan"
        cy.get('button[name="update_kuota"]').click();
        
        // 3. Memvalidasi bahwa alert sukses muncul dengan angka yang benar
        cy.get('.alert-success')
          .should('be.visible')
          .and('contain', `Pengaturan Kuota Global berhasil diperbarui menjadi ${kuotaBaru} orang.`);
          
        // 4. Memvalidasi bahwa nilai di dalam kolom input benar-benar sudah berubah
        cy.get('input[name="kuota_maksimal"]').should('have.value', kuotaBaru.toString());
        
        // Karena form menggunakan action="" (reload di halaman yang sama),
        // kita pastikan URL tetap aman berada di kuota.php
        cy.url().should('include', 'kuota.php');
        
        // Memberikan jeda waktu 0.5 detik setiap iterasi agar perubahan UI terlihat jelas saat direkam
        cy.wait(500); 
      });
  
      // =========================================================
      // VALIDASI FINAL: Memastikan sisa akhir benar-benar angka 4
      // =========================================================
      cy.get('input[name="kuota_maksimal"]').should('have.value', '4');
      cy.log('Pengujian selesai, kuota sistem saat ini berhasil dikunci pada angka 4.');
    });
  
    it('TC-02: Navigasi kembali ke Dashboard setelah mengubah kuota', () => {
      // Menguji navigasi dari sidebar setelah selesai melakukan update
      cy.get('.sidebar').contains('Dashboard').click();
      
      // Memastikan berhasil kembali ke dashboard
      cy.url().should('include', '/admin_magang/dashboard.php');
    });
  
  });