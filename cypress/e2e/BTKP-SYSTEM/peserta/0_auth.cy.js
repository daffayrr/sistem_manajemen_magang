describe('Testing Autentikasi dan Dashboard Admin', () => {
  
    beforeEach(() => {
      // Memastikan setiap test dimulai dari halaman login utama
      cy.visit('/index.php');
    });
  
    it('TC-01: Gagal login karena username atau password salah', () => {
      // Menyimulasikan admin memasukkan data yang salah
      cy.get('input[name="username"]').type('admin_salah');
      cy.get('input[name="password"]').type('password_hahahaha');
      cy.get('button[type="submit"]').click();
  
      // Memvalidasi munculnya alert error dari PHP (pesan=gagal)
      cy.get('.alert-danger')
        .should('be.visible')
        .and('contain', 'Login Gagal! Akun tidak ditemukan.');
        
      // Memastikan user tidak tembus ke dashboard
      cy.url().should('not.include', 'peserta_magang/index.php');
    });
  
    it('TC-02: Berhasil login dan masuk ke Dashboard Admin', () => {
      // Sesuaikan username dan password dengan data di database (tabel admin)
      cy.get('input[name="username"]').type('farras.yassarramadhan@toscaflow.id'); 
      cy.get('input[name="password"]').type('abc_123');
      cy.get('button[type="submit"]').click();
  
      // Memastikan sistem mengarahkan ke URL dashboard yang benar
      cy.url().should('include', '/peserta_magang/index.php');
  
    });
  
    it('TC-03: Berhasil melakukan Logout dan sesi dihancurkan', () => {
      // Proses login terlebih dahulu
      cy.get('input[name="username"]').type('farras.yassarramadhan@toscaflow.id');
      cy.get('input[name="password"]').type('abc_123');
      cy.get('button[type="submit"]').click();
      
      // Pastikan sudah masuk dashboard
      cy.url().should('include', '/peserta_magang/index.php');

      // Menyimulasikan klik tombol logout (mencari link yang mengarah ke logout.php)
      cy.get('a[href="logout.php"]').first().click();
  
      // Memastikan sistem membuang user kembali ke halaman login
      cy.url().should('include', '/index.php');
  
      // Memvalidasi munculnya pesan sukses logout (pesan=logout)
      cy.get('.alert-success')
        .should('be.visible')
        .and('contain', 'Anda telah berhasil logout.');
    });
  
  });