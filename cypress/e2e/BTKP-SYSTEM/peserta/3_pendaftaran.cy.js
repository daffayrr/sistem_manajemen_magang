describe('Testing Fungsionalitas Form Pendaftaran Magang', () => {
  
    // Membuat email dinamis berdasarkan waktu (timestamp)
    const uniqueEmail = `pendaftar_${Date.now()}@almaata.ac.id`;
    const passwordPeserta = '123456';
  
    // Blok 'before' ini hanya dijalankan SEKALI di awal untuk membuat akun baru
    before(() => {
      cy.visit('/regs.php');
      cy.get('input[name="nama_lengkap"]').type('Peserta Uji Form');
      cy.get('input[name="asal_instansi"]').type('Universitas Alma Ata');
      cy.get('input[name="no_hp"]').type('08123456789');
      cy.get('input[name="email"]').type(uniqueEmail);
      cy.get('input[name="password"]').type(passwordPeserta);
      
      cy.on('window:alert', () => true); // Menyetujui alert registrasi
      cy.get('button[type="submit"]').click();
    });
  
    // Blok 'beforeEach' untuk login menggunakan akun baru yang barusan dibuat
    beforeEach(() => {
      // Gunakan path relatif dari baseUrl
      cy.visit('/index.php'); 
      cy.get('input[name="username"]').type(uniqueEmail); 
      cy.get('input[name="password"]').type(passwordPeserta);
      cy.get('button[type="submit"]').click();
      
      // Pastikan diarahkan ke folder peserta_magang
      cy.visit('/peserta_magang/pendaftaran.php');
    });
  
    it('TC-01: Memvalidasi penolakan jika tanggal selesai lebih awal dari tanggal mulai', () => {
      // Mengisi form dengan data dasar
      cy.get('input[name="no_hp"]').clear().type('0899999999');
      cy.get('textarea[name="alamat"]').type('Jl. Ringroad Selatan');
      cy.get('input[name="asal_instansi"]').clear().type('Universitas Alma Ata');
      cy.get('textarea[name="alamat_instansi"]').type('Bantul');
      cy.get('input[name="no_hp_pendamping"]').type('0855555555');
  
      // MENGATUR TANGGAL SALAH (Selesai lebih dulu dari Mulai)
      cy.get('input[name="tanggal_mulai"]').type('2026-12-01');
      cy.get('input[name="tanggal_selesai"]').type('2026-11-01'); // Mundur ke bulan 11
  
      // Upload file dummy (Pastikan file surat_dummy.pdf ada di cypress/fixtures/)
      cy.get('input[name="berkas"]').selectFile('cypress/fixtures/surat_dummy.pdf');
  
      // Menangani Pop-up Konfirmasi JS (return confirm)
      // Cypress secara default menekan 'Cancel', kita paksa tekan 'OK' (true)
      cy.on('window:confirm', () => true);
      cy.get('button[name="kirim_pendaftaran"]').click();
  
      // Memvalidasi error alert dari PHP muncul
      cy.get('.alert-danger')
        .should('be.visible')
        .and('contain', 'Tanggal selesai magang harus lebih besar dari tanggal mulai');
    });
  
    it('TC-02: Berhasil mengirim formulir pendaftaran dan unggah dokumen', () => {
      // Mengisi ulang form dengan data yang valid
      cy.get('input[name="no_hp"]').clear().type('0899999999');
      cy.get('textarea[name="alamat"]').clear().type('Jl. Ringroad Selatan');
      cy.get('input[name="asal_instansi"]').clear().type('Universitas Alma Ata');
      cy.get('textarea[name="alamat_instansi"]').clear().type('Bantul');
      cy.get('input[name="no_hp_pendamping"]').clear().type('0855555555');
  
      // MENGATUR TANGGAL BENAR
      cy.get('input[name="tanggal_mulai"]').type('2026-08-01');
      cy.get('input[name="tanggal_selesai"]').type('2026-11-01');
  
      cy.get('input[name="berkas"]').selectFile('cypress/fixtures/surat_dummy.pdf');
  
      cy.on('window:confirm', () => true);
      cy.get('button[name="kirim_pendaftaran"]').click();
  
      // Memvalidasi notifikasi sukses
      cy.get('.alert-success')
        .should('be.visible')
        .and('contain', 'Pendaftaran Berhasil');
    });
  
    it('TC-03: Memastikan Form terkunci (Status Menunggu) setelah data disubmit', () => {
      // Karena di TC-02 pendaftaran sudah berhasil, saat kita mengunjungi halaman ini lagi,
      // kode PHP Anda ($sedang_daftar = true) harusnya menghilangkan form dan menampilkan Card Status.
  
      // Memastikan Form Input sudah TIDAK ADA di halaman
      cy.get('input[name="tanggal_mulai"]').should('not.exist');
      cy.get('button[name="kirim_pendaftaran"]').should('not.exist');
  
      // Memastikan Card Status "Menunggu Verifikasi" MUNCUL di layar
      cy.contains('Menunggu Verifikasi Admin').should('be.visible');
      cy.get('.fa-clock').should('be.visible'); // Memvalidasi ikon jam (warning) muncul
      
      // Memastikan Rencana Magang tercetak dengan benar di dalam Card
      cy.contains('Rencana Magang').should('be.visible');
      cy.contains('Waktu Pengajuan (FIFO)').should('be.visible');
    });
  
  });