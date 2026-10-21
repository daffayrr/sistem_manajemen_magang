describe('Testing Dinamis Halaman Unduhan & Hasil Seleksi', () => {
  
    // Akun dinamis khusus untuk menguji perubahan status halaman unduhan
    const uniqueEmail = `unduhan_tester_${Date.now()}@almaata.ac.id`;
    const passwordPeserta = '123456';
  
    before(() => {
      // Registrasi akun baru (Status Default: Belum Daftar)
      cy.visit('/regs.php');
      cy.get('input[name="nama_lengkap"]').type('Tester Unduhan');
      cy.get('input[name="asal_instansi"]').type('Universitas Alma Ata');
      cy.get('input[name="no_hp"]').type('08111111111');
      cy.get('input[name="email"]').type(uniqueEmail);
      cy.get('input[name="password"]').type(passwordPeserta);
      cy.on('window:alert', () => true);
      cy.get('button[type="submit"]').click();
    });
  
    beforeEach(() => {
      // Login sebelum setiap test case dijalankan
      cy.visit('/index.php');
      cy.get('input[name="username"]').type(uniqueEmail); 
      cy.get('input[name="password"]').type(passwordPeserta);
      cy.get('button[type="submit"]').click();
    });
  
    it('TC-01: Memvalidasi tampilan Unduhan saat status "Belum Daftar"', () => {
      cy.visit('/peserta_magang/unduhan.php');
  
      // Memvalidasi Card peringatan belum ada data
      cy.contains('Belum Ada Data').should('be.visible');
      cy.contains('Anda belum melakukan pengajuan magang.').should('be.visible');
  
      // Menguji tombol navigasi "Daftar Sekarang" dan ditarik kembali
      cy.contains('Daftar Sekarang').click();
      
      // Pastikan tombol mengarah ke form pendaftaran
      cy.url().should('include', 'pendaftaran.php');
      
      // Putar balik ke halaman unduhan
      cy.go('back');
      cy.url().should('include', '/peserta_magang/unduhan.php');
    });
  
    it('TC-02: Melakukan Pendaftaran agar status berubah menjadi "Menunggu"', () => {
      // Skenario ini bertugas mendaftarkan user agar statusnya naik level
      cy.visit('/peserta_magang/pendaftaran.php');
      
      // Mengisi form pendaftaran
      cy.get('input[name="no_hp"]').clear().type('0899999999');
      cy.get('textarea[name="alamat"]').type('Jl. Ringroad Selatan');
      cy.get('input[name="asal_instansi"]').clear().type('Universitas Alma Ata');
      cy.get('textarea[name="alamat_instansi"]').type('Bantul');
      cy.get('input[name="no_hp_pendamping"]').type('0855555555');
      cy.get('input[name="tanggal_mulai"]').type('2026-08-01');
      cy.get('input[name="tanggal_selesai"]').type('2026-11-01');
      
      cy.get('input[name="berkas"]').selectFile('cypress/fixtures/surat_dummy.pdf');
  
      // Submit form pendaftaran
      cy.on('window:confirm', () => true);
      cy.get('button[name="kirim_pendaftaran"]').click();
      
      // Pastikan sukses
      cy.get('.alert-success').should('be.visible');
    });
  
    it('TC-03: Memvalidasi tampilan Unduhan setelah status berubah menjadi "Menunggu"', () => {
      // Karena di TC-02 user sudah daftar, halaman ini harusnya berubah total
      cy.visit('/peserta_magang/unduhan.php');
  
      // 1. Validasi Widget Status Kiri
      cy.contains('Status Saat Ini').should('be.visible');
      cy.contains('Menunggu Verifikasi').should('be.visible');
      cy.contains('Berkas sedang dalam antrean pemeriksaan admin.').should('be.visible');
      
      // Pastikan tanggal pelaksanaan yang tadi diinput muncul di layar
      cy.contains('01 Aug 26 - 01 Nov 26').should('be.visible');
  
      // 2. Validasi Widget Unduhan Kanan
      cy.contains('Berkas Unduhan Resmi').should('be.visible');
      
      // Karena status masih Menunggu, file belum ada
      cy.contains('Belum Ada Berkas').should('be.visible');
      cy.contains('Berkas unduhan akan muncul setelah status Anda berubah menjadi Aktif Magang').should('be.visible');
    });
  
  });