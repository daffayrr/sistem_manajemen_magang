/**
 * Cypress E2E Test Suite: PESERTA CASES (PST-01 s/d PST-10)
 * Sistem Informasi Manajemen Penerimaan Magang Balai Tekkomdik DIY
 */

describe('Peserta Test Suite — PST-01 s/d PST-10', () => {

  const passPeserta = 'abc_123';
  const emailDiterima = 'peserta.diterima@btkp.test';
  const emailMenunggu = 'peserta.menunggu@btkp.test';
  const emailBaru     = 'peserta.baru@btkp.test';

  before(() => {
    cy.exec('php scripts/prepare_test_data.php');
  });

  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  const loginPeserta = (email) => {
    cy.visit('/index.php');
    cy.get('input[name="username"]').clear().type(email);
    cy.get('input[name="password"]').clear().type(passPeserta);
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/peserta_magang/index.php');
  };

  it('PST-01: Logout peserta', () => {
    loginPeserta(emailDiterima);

    // Klik tombol Keluar di sidebar
    cy.contains('Keluar').click();

    // Sistem mengarahkan ke halaman login dan menampilkan pesan logout
    cy.url().should('include', '/index.php');
    cy.get('.alert-success')
      .should('be.visible')
      .and('contain', 'berhasil logout');

    // Pastikan akses kembali ke dashboard tanpa login ditolak
    cy.visit('/peserta_magang/index.php');
    cy.url().should('not.include', '/peserta_magang/index.php');
  });

  it('PST-02: Dashboard menampilkan pengumuman Admin', () => {
    loginPeserta(emailDiterima);

    // Dashboard peserta harus menampilkan pengumuman terkini
    cy.get('.alert-info')
      .should('be.visible')
      .and('contain', 'Info Terbaru');
  });

  it('PST-03: Navigasi ke formulir pendaftaran', () => {
    loginPeserta(emailBaru);

    // Untuk peserta baru yang belum mendaftar, terdapat tombol pendaftaran
    cy.contains('Isi Formulir Pendaftaran').click();
    cy.url().should('include', 'pendaftaran.php');
    cy.contains('Formulir Pendaftaran Magang').should('be.visible');
  });

  it('PST-04: Accordion FAQ', () => {
    loginPeserta(emailDiterima);
    cy.visit('/peserta_magang/panduan.php');

    // Validasi interaksi pada komponen FAQ Accordion
    cy.contains('Berapa lama proses verifikasi?').click();
    cy.get('#faq1').should('be.visible').and('contain', '3-5 hari kerja');

    cy.contains('Apa itu metode FIFO?').click();
    cy.get('#faq2').should('be.visible').and('contain', 'First In First Out');
  });

  it('PST-05: Pendaftaran dengan data valid', () => {
    // Buat akun baru secara dinamis agar pengujian pendaftaran bersih
    const dynamicEmail = `peserta_pendaftaran_${Date.now()}@btkp.test`;
    
    // Registrasi via form register
    cy.visit('/regs.php');
    cy.get('input[name="nama_lengkap"]').type('Peserta Baru Sukses E2E');
    cy.get('input[name="asal_instansi"]').type('Universitas Gadjah Mada');
    cy.get('input[name="no_hp"]').type('081234567899');
    cy.get('input[name="email"]').type(dynamicEmail);
    cy.get('input[name="password"]').type('abc_123');
    cy.on('window:alert', () => true);
    cy.get('button[type="submit"]').click();

    // Login dengan akun baru
    cy.visit('/index.php');
    cy.get('input[name="username"]').type(dynamicEmail);
    cy.get('input[name="password"]').type('abc_123');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/peserta_magang/index.php');

    // Masuk ke formulir pendaftaran
    cy.visit('/peserta_magang/pendaftaran.php');
    cy.get('input[name="no_hp"]').clear().type('081234567899');
    cy.get('textarea[name="alamat"]').clear().type('Jl. Kenari No. 10 Yogyakarta');
    cy.get('input[name="asal_instansi"]').clear().type('Universitas Gadjah Mada');
    cy.get('textarea[name="alamat_instansi"]').clear().type('Bulaksumur, Yogyakarta');
    cy.get('input[name="no_hp_pendamping"]').clear().type('081987654321');

    cy.get('input[name="tanggal_mulai"]').type('2026-08-01');
    cy.get('input[name="tanggal_selesai"]').type('2026-11-01');
    cy.get('input[name="berkas"]').selectFile('cypress/fixtures/surat_dummy.pdf');

    cy.on('window:confirm', () => true);
    cy.get('button[name="kirim_pendaftaran"]').click();

    // Validasi notifikasi sukses
    cy.get('.alert-success')
      .should('be.visible')
      .and('contain', 'Pendaftaran Berhasil');
  });

  it('PST-06: Menolak periode magang yang tidak valid', () => {
    loginPeserta(emailBaru);
    cy.visit('/peserta_magang/pendaftaran.php');

    cy.get('input[name="no_hp"]').clear().type('081234567890');
    cy.get('textarea[name="alamat"]').clear().type('Jl. Kaliurang KM 5');
    cy.get('input[name="asal_instansi"]').clear().type('Universitas Negeri Yogyakarta');
    cy.get('textarea[name="alamat_instansi"]').clear().type('Colombo, Sleman');
    cy.get('input[name="no_hp_pendamping"]').clear().type('081234567890');

    // Tanggal mulai lebih besar daripada tanggal selesai
    cy.get('input[name="tanggal_mulai"]').type('2026-12-01');
    cy.get('input[name="tanggal_selesai"]').type('2026-11-01');
    cy.get('input[name="berkas"]').selectFile('cypress/fixtures/surat_dummy.pdf');

    cy.on('window:confirm', () => true);
    cy.get('button[name="kirim_pendaftaran"]').click();

    // Validasi pesan error penolakan tanggal
    cy.get('.alert-danger')
      .should('be.visible')
      .and('contain', 'Tanggal selesai magang harus lebih besar dari tanggal mulai');
  });

  it('PST-07: Menolak dokumen persyaratan dengan format yang dilarang', () => {
    loginPeserta(emailBaru);
    cy.visit('/peserta_magang/pendaftaran.php');

    cy.get('input[name="no_hp"]').clear().type('081234567890');
    cy.get('textarea[name="alamat"]').clear().type('Jl. Magelang');
    cy.get('input[name="asal_instansi"]').clear().type('Universitas Negeri Yogyakarta');
    cy.get('textarea[name="alamat_instansi"]').clear().type('Yogyakarta');
    cy.get('input[name="no_hp_pendamping"]').clear().type('081234567890');

    cy.get('input[name="tanggal_mulai"]').type('2026-08-01');
    cy.get('input[name="tanggal_selesai"]').type('2026-11-01');

    // Unggah file JPG terlarang
    cy.get('input[name="berkas"]').selectFile('cypress/fixtures/gambar_terlarang.jpg');

    cy.on('window:confirm', () => true);
    cy.get('button[name="kirim_pendaftaran"]').click();

    // Validasi penolakan sistem
    cy.get('.alert-danger')
      .should('be.visible')
      .and('contain', 'Format Surat Pengantar harus PDF!');
  });

  it('PST-08: Mencegah pendaftaran ulang ketika masih dalam antrean', () => {
    // Login akun yang sudah memiliki pendaftaran berstatus Menunggu
    loginPeserta(emailMenunggu);
    cy.visit('/peserta_magang/pendaftaran.php');

    // Formulir pendaftaran harus terkunci dan digantikan oleh status pendaftaran
    cy.get('input[name="tanggal_mulai"]').should('not.exist');
    cy.get('button[name="kirim_pendaftaran"]').should('not.exist');
    cy.contains('Menunggu Verifikasi Admin').should('be.visible');
    cy.contains('Waktu Pengajuan (FIFO)').should('be.visible');
  });

  it('PST-09: Unduhan aktif setelah peserta diterima', () => {
    // Login akun yang statusnya Diterima dan memiliki surat balasan
    loginPeserta(emailDiterima);
    cy.visit('/peserta_magang/unduhan.php');

    // Validasi badge status diterima
    cy.contains('Aktif Magang').should('be.visible');

    // Validasi tombol unduhan surat balasan aktif
    cy.contains('Surat Balasan Resmi').should('be.visible');
    cy.get('a[href*="../uploads/"]').first().then(($link) => {
      expect($link.attr('href')).to.include('/uploads/');
      expect($link.attr('target')).to.equal('_blank');
    });
  });

  it('PST-10: Unduhan terkunci ketika peserta belum diterima', () => {
    // Login akun yang masih berstatus Menunggu
    loginPeserta(emailMenunggu);
    cy.visit('/peserta_magang/unduhan.php');

    // Validasi status menunggu
    cy.contains('Menunggu Verifikasi').should('be.visible');

    // Tombol unduhan terkunci dan tampil teks Belum Ada Berkas
    cy.contains('Belum Ada Berkas').should('be.visible');
    cy.contains('Unduh Surat Balasan').should('not.exist');
  });

});
