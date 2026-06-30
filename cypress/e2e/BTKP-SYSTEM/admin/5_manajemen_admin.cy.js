describe('Testing Fungsionalitas Manajemen Akun Admin', () => {
  
  // Menggunakan Date.now() agar selalu unik setiap test dijalankan
  const uniqueUsername = `admin_test_${Date.now()}`;
  const namaAdmin = 'Admin E2E Tester';

  beforeEach(() => {
    cy.visit('/index.php');
    cy.get('input[name="username"]').type('admin'); // Sesuaikan admin utama Anda
    cy.get('input[name="password"]').type('admin123'); 
    cy.get('button[type="submit"]').click();

    cy.visit('/admin_magang/admin.php');
  });

  it('TC-01: Berhasil menambahkan akun Admin baru', () => {
    cy.get('input[name="nama_lengkap"]').type(namaAdmin);
    cy.get('input[name="username"]').type(uniqueUsername);
    cy.get('input[name="password"]').type('password_aman123');
    cy.get('button[name="tambah_admin"]').click();

    // 1. Memvalidasi SweetAlert Sukses muncul dari PHP
    cy.get('.swal2-popup', { timeout: 10000 }).should('be.visible');
    cy.get('.swal2-title').should('contain', 'Berhasil!');
    
    // Klik tombol OK pada SweetAlert agar layar bersih
    cy.get('.swal2-confirm').click();

    // 2. Pastikan admin baru benar-benar terekam di tabel
    cy.get('table tbody').should('contain', uniqueUsername);
  });

  it('TC-02: Menguji Error Handling - Menolak username duplikat', () => {
    // 1. Input data dengan username duplikat yang sama dengan TC-01
    cy.get('input[name="nama_lengkap"]').type('Admin Duplikat');
    cy.get('input[name="username"]').type(uniqueUsername); 
    cy.get('input[name="password"]').type('123456');
    
    // 2. Klik tombol simpan
    cy.get('button[name="tambah_admin"]').click();

    // 3. PENGUJIAN INTI (Sesuai ide Anda):
    // Kita langsung mencari Alert Bootstrap Merah.
    // Jika alert muncul, mengandung username tersebut, dan memuat kata "sudah terdaftar", 
    // maka test OTOMATIS HIJAU (Passed).
    cy.get('.alert-danger', { timeout: 10000 })
      .should('be.visible')
      .and('contain', uniqueUsername)
      .and('contain', 'sudah terdaftar');

    // 4. (Opsional/Pengaman) Jika SweetAlert kebetulan ikut muncul, 
    // kita klik "OK" diam-diam agar tidak menutupi layar untuk TC-03.
    // Jika tidak muncul, Cypress akan mengabaikannya tanpa membuat error.
    cy.get('body').then(($body) => {
      if ($body.find('.swal2-confirm').length > 0) {
        cy.get('.swal2-confirm').click({ force: true });
      }
    });
  });
    
  it('TC-03: Berhasil menghapus akun Admin', () => {
    // Mencari baris yang spesifik mengandung username hasil tes
    cy.contains('tr', uniqueUsername).within(() => {
      // Klik ikon tempat sampah di baris tersebut
      cy.get('button[title="Hapus Akun"]').click();
    });

    // 1. Konfirmasi SweetAlert Hapus (Tanya: Ya, Hapus?)
    cy.get('.swal2-popup', { timeout: 10000 }).should('be.visible');
    cy.contains('Hapus Admin?').should('be.visible');
    cy.get('.swal2-confirm').click();

    // 2. Cypress MENUNGGU URL berubah menjadi '?hapus=...' yang menandakan halaman di-reload
    cy.url().should('include', 'hapus=');

    // 3. Setelah reload, PHP memunculkan SweetAlert "Berhasil!"
    cy.get('.swal2-popup', { timeout: 10000 }).should('be.visible');
    cy.get('.swal2-title').should('contain', 'Berhasil!');
    cy.get('.swal2-confirm').click();

    // 4. Memastikan data username tersebut sudah lenyap dari tabel
    cy.get('table tbody').should('not.contain', uniqueUsername);
  });

});