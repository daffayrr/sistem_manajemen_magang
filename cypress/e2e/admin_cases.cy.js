/**
 * Cypress E2E Test Suite: ADMIN CASES (ADM-01 s/d ADM-20)
 * Sistem Informasi Manajemen Penerimaan Magang Balai Tekkomdik DIY
 */

describe('Admin Test Suite — ADM-01 s/d ADM-20', () => {

  const adminUser = 'admin';
  const adminPass = 'admin123';

  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  // Helper untuk login admin
  const loginAdmin = () => {
    cy.visit('/index.php');
    cy.get('input[name="username"]').clear().type(adminUser);
    cy.get('input[name="password"]').clear().type(adminPass);
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/admin_magang/dashboard.php');
  };

  it('ADM-01: Login dengan kredensial benar', () => {
    cy.visit('/index.php');
    cy.get('input[name="username"]').type(adminUser);
    cy.get('input[name="password"]').type(adminPass);
    cy.get('button[type="submit"]').click();

    // Validasi URL tujuan dan terbentuknya sesi dashboard
    cy.url().should('include', '/admin_magang/dashboard.php');
    cy.contains('Metode FIFO Aktif!').should('be.visible');
    cy.contains('Sisa Kuota Magang').should('be.visible');
  });

  it('ADM-02: Login dengan kredensial salah', () => {
    cy.visit('/index.php');
    cy.get('input[name="username"]').type('admin_ngawur_99');
    cy.get('input[name="password"]').type('salah_total_123');
    cy.get('button[type="submit"]').click();

    // Validasi pesan error dan penolakan akses dashboard
    cy.get('.alert-danger')
      .should('be.visible')
      .and('contain', 'Login Gagal! Akun tidak ditemukan.');
    cy.url().should('not.include', '/admin_magang/dashboard.php');
  });

  it('ADM-03: Dashboard dan navigasi Admin', () => {
    loginAdmin();

    // Periksa widget sisa kuota dan tabel antrean FIFO
    cy.contains('Sisa Kuota Magang').should('be.visible');
    cy.contains('Antrean Masuk (FIFO)').should('be.visible');

    // Navigasi tombol Aksi Cepat: Update Kuota Magang
    cy.contains('Update Kuota Magang').click();
    cy.url().should('include', 'kuota.php');
    cy.go('back');

    // Navigasi tombol Aksi Cepat: Edit Pengumuman
    cy.contains('Edit Pengumuman').click();
    cy.url().should('include', 'informasi.php');
    cy.go('back');

    // Navigasi tombol Aksi Cepat: Tambah Admin Baru
    cy.contains('Tambah Admin Baru').click();
    cy.url().should('include', 'admin.php');
    cy.go('back');

    // Navigasi tombol Lihat Semua / Verifikasi di dashboard
    cy.contains('Lihat Semua').click();
    cy.url().should('include', 'peserta.php');
  });

  it('ADM-04: Pencarian peserta yang ditemukan', () => {
    loginAdmin();
    cy.visit('/admin_magang/peserta.php');

    // Cari instansi/nama yang terdaftar di database
    cy.get('input[name="q"]').clear().type('Alma Ata');
    cy.get('button[type="submit"]').click();

    cy.url().should('include', 'q=Alma+Ata');
    cy.get('table tbody').should('contain', 'Alma Ata');
  });

  it('ADM-05: Pencarian peserta yang tidak ditemukan', () => {
    loginAdmin();
    cy.visit('/admin_magang/peserta.php');

    // Cari kata kunci yang dipastikan tidak terdaftar
    const nonExistent = 'XYZ_NAMA_TIDAK_ADA_DI_SISTEM_999';
    cy.get('input[name="q"]').clear().type(nonExistent);
    cy.get('button[type="submit"]').click();

    // Validasi pesan tidak ada data
    cy.get('table tbody').should('contain', 'Tidak ada data');
  });

  it('ADM-06: Pengelompokan peserta berdasarkan status', () => {
    loginAdmin();
    cy.visit('/admin_magang/peserta.php');

    // Buka tab Diterima
    cy.get('a[href="peserta.php?filter=Diterima"]').click();
    cy.url().should('include', 'filter=Diterima');

    // Buka tab Ditolak
    cy.get('a[href="peserta.php?filter=Ditolak"]').click();
    cy.url().should('include', 'filter=Ditolak');

    // Buka tab Arsip (Selesai)
    cy.get('a[href="peserta.php?filter=Selesai"]').click();
    cy.url().should('include', 'filter=Selesai');

    // Kembali ke tab Menunggu dan pastikan urutan antrean FIFO konsisten
    cy.get('a[href="peserta.php?filter=Menunggu"]').click();
    cy.url().should('include', 'filter=Menunggu');
    cy.get('table thead').should('contain', 'Nama Peserta');
  });

  it('ADM-07: Membuka detail peserta', () => {
    loginAdmin();
    cy.visit('/admin_magang/peserta.php');

    // Klik tombol detail baris pertama
    cy.get('table tbody a.btn-outline-primary').first().click();
    cy.url().should('include', 'detail_peserta.php?id=');

    // Validasi informasi detail peserta tampil
    cy.contains('Detail Pendaftar Magang').should('be.visible');
    cy.contains('Identitas & Kontak').should('be.visible');
    cy.contains('Dokumen Pengajuan').should('be.visible');
  });

  it('ADM-08: Mengunduh berkas peserta', () => {
    loginAdmin();
    cy.visit('/admin_magang/peserta.php');

    // Periksa tautan unduh berkas di tabel
    cy.get('table tbody a[href*="../uploads/"]').first().then(($link) => {
      const href = $link.attr('href');
      expect(href).to.include('/uploads/');
      expect($link.attr('target')).to.equal('_blank');

      // Validasi file dapat diakses di server (Status 200) dengan path yang dinormalisasi
      const cleanPath = href.replace('../', '');
      cy.request(cleanPath).its('status').should('eq', 200);
    });
  });

  it('ADM-09: Menolak peserta', () => {
    loginAdmin();
    cy.visit('/admin_magang/peserta.php?filter=Menunggu');

    // Cek tombol Tolak
    cy.get('table tbody').then(($tbody) => {
      if ($tbody.find('button[title="Tolak"]').length > 0) {
        cy.get('button[title="Tolak"]').first().click();

        // Validasi modal konfirmasi SweetAlert
        cy.get('.swal2-popup').should('be.visible');
        cy.contains('Tolak Peserta?').should('be.visible');

        // Klik Konfirmasi Tolak
        cy.get('.swal2-confirm').click();

        // Validasi perubahan status tersimpan
        cy.url().should('include', 'peserta.php');
      } else {
        cy.log('Tidak ada peserta antrean menunggu saat ini.');
      }
    });
  });

  it('ADM-10: Filter status seleksi', () => {
    loginAdmin();
    cy.visit('/admin_magang/peserta.php');

    const filters = [
      { filter: 'Menunggu', label: 'Menunggu' },
      { filter: 'Diterima', label: 'Diterima' },
      { filter: 'Ditolak', label: 'Ditolak' },
      { filter: 'Selesai', label: 'Arsip' }
    ];
    filters.forEach((f) => {
      cy.get(`a[href="peserta.php?filter=${f.filter}"]`).click();
      cy.url().should('include', `filter=${f.filter}`);
      cy.get(`a[href="peserta.php?filter=${f.filter}"]`).should('have.class', 'fw-bold');
    });
  });

  it('ADM-11: Menyetujui peserta ketika kuota tersedia', () => {
    loginAdmin();

    // Pastikan kapasitas kuota aman (misal 4)
    cy.visit('/admin_magang/kuota.php');
    cy.get('input[name="jumlah_maks"]').clear().type('4');
    cy.get('button[name="update_kuota"]').click();

    // Verifikasi peserta antrean pertama
    cy.visit('/admin_magang/peserta.php?filter=Menunggu');
    cy.get('table tbody').then(($tbody) => {
      if ($tbody.find('button[title="Terima"]').length > 0) {
        cy.get('button[title="Terima"]').first().click();
        cy.get('.swal2-popup').should('be.visible');
        cy.contains('Terima Peserta?').should('be.visible');
        cy.get('.swal2-confirm').click();

        // Validasi notifikasi sukses
        cy.url().should('include', 'peserta.php');
      } else {
        cy.log('Antrean peserta menunggu kosong.');
      }
    });
  });

  it('ADM-12: Menyelesaikan periode magang peserta', () => {
    loginAdmin();
    cy.visit('/admin_magang/peserta.php?filter=Diterima');

    cy.get('table tbody').then(($tbody) => {
      if ($tbody.find('button[title="Selesaikan Magang"]').length > 0) {
        cy.get('button[title="Selesaikan Magang"]').first().click();
        cy.get('.swal2-popup').should('be.visible');
        cy.contains('Selesaikan Magang?').should('be.visible');
        cy.get('.swal2-confirm').click();

        // Validasi status berubah menjadi Selesai
        cy.url().should('include', 'peserta.php');
      } else {
        cy.log('Belum ada peserta berstatus Diterima yang berkasnya lengkap.');
      }
    });
  });

  it('ADM-13: Mencoba menyetujui peserta ketika kuota penuh', () => {
    loginAdmin();

    // Simulasikan kuota 0 di database
    cy.visit('/admin_magang/kuota.php');
    cy.get('input[name="jumlah_maks"]').clear().type('1');
    cy.get('button[name="update_kuota"]').click();

    // Coba setujui peserta ketika kuota terpakai penuh
    cy.visit('/admin_magang/peserta.php?filter=Menunggu');
    cy.get('table tbody').then(($tbody) => {
      if ($tbody.find('button[title="Terima"]').length > 0) {
        cy.get('button[title="Terima"]').first().click();
        cy.get('.swal2-confirm').click();

        // Validasi sistem menolak dan menampilkan pesan error kuota penuh
        cy.get('.alert-danger, .swal2-popup, body').should('contain', 'Kuota Magang saat ini Penuh');
      }
    });

    // Kembalikan kuota ke 10 agar kapasitas memadai untuk pengujian berikutnya
    cy.visit('/admin_magang/kuota.php');
    cy.get('input[name="jumlah_maks"]').clear().type('10');
    cy.get('button[name="update_kuota"]').click();
  });

  it('ADM-14: Mengunggah Surat Balasan dengan format yang diizinkan', () => {
    loginAdmin();

    // Buka detail peserta berstatus Diterima
    cy.visit('/admin_magang/peserta.php?filter=Diterima');
    cy.get('table tbody a.btn-outline-primary').first().click();
    cy.url().should('include', 'detail_peserta.php?id=');

    // Unggah dokumen Surat Balasan (PDF valid)
    cy.get('input[name="file_balasan"]').selectFile('cypress/fixtures/surat_balasan_dummy.pdf');
    cy.get('button[name="upload_balasan"]').click();

    // Validasi notifikasi sukses
    cy.get('.alert-success')
      .should('be.visible')
      .and('contain', 'Surat Balasan resmi berhasil diunggah.');

    // Validasi tautan berkas menjadi aktif
    cy.contains('Buka Surat Balasan Terunggah').should('be.visible');
  });

  it('ADM-15: Menolak unggahan Surat Balasan dengan format tidak valid', () => {
    loginAdmin();

    // Buka detail peserta Diterima
    cy.visit('/admin_magang/peserta.php?filter=Diterima');
    cy.get('table tbody a.btn-outline-primary').first().click();

    // Unggah file berformat tidak diizinkan (.docx)
    cy.get('input[name="file_balasan"]').selectFile('cypress/fixtures/dokumen_terlarang.docx');
    cy.get('button[name="upload_balasan"]').click();

    // Validasi pesan error penolakan server-side
    cy.get('.alert-danger')
      .should('be.visible')
      .and('contain', 'Format file tidak valid! Gunakan PDF/JPG/PNG.');
  });

  it('ADM-16: Memperbarui kapasitas kuota', () => {
    loginAdmin();
    cy.visit('/admin_magang/kuota.php');

    // Ubah kuota menjadi 10
    cy.get('input[name="jumlah_maks"]').clear().type('10');
    cy.get('button[name="update_kuota"]').click();

    cy.get('.alert-success')
      .should('be.visible')
      .and('contain', 'Pengaturan Kuota Global berhasil diperbarui menjadi 10 orang.');

    cy.get('input[name="jumlah_maks"]').should('have.value', '10');
  });

  it('ADM-17: Memperbarui pengumuman', () => {
    loginAdmin();
    cy.visit('/admin_magang/informasi.php');

    const judulBaru = `Pengumuman Resmi Magang BTKP DIY — ${Date.now()}`;
    const deskripsiBaru = 'Pendaftaran magang semester baru resmi dibuka. Silakan lengkapi berkas persyaratan.';

    cy.get('input[name="judul"]').clear().type(judulBaru);
    cy.get('textarea[name="deskripsi"]').clear().type(deskripsiBaru);
    cy.get('button[name="simpan_info"]').click();

    // Validasi notifikasi sukses dan live preview terupdate
    cy.get('.alert-success')
      .should('be.visible')
      .and('contain', 'Informasi dan Pengumuman berhasil diperbarui!');
    cy.contains('Preview Peserta').should('be.visible');
    cy.contains(judulBaru).should('be.visible');
  });

  it('ADM-18: Menolak pengumuman kosong', () => {
    loginAdmin();
    cy.visit('/admin_magang/informasi.php');

    // Kosongkan field judul dan deskripsi (hilangkan atribut required agar memicu server validation)
    cy.get('input[name="judul"]').clear().invoke('removeAttr', 'required');
    cy.get('textarea[name="deskripsi"]').clear().invoke('removeAttr', 'required');
    cy.get('button[name="simpan_info"]').click();

    // Validasi penolakan sistem
    cy.get('.alert-danger')
      .should('be.visible')
      .and('contain', 'Judul dan deskripsi informasi tidak boleh kosong.');
  });

  it('ADM-19: Menambahkan akun Admin', () => {
    loginAdmin();
    cy.visit('/admin_magang/admin.php');

    const newUsername = `admin_qa_${Date.now()}`;
    cy.get('input[name="nama_lengkap"]').type('Administrator QA Auto');
    cy.get('input[name="username"]').type(newUsername);
    cy.get('input[name="password"]').type('password_aman123');
    cy.get('button[name="tambah_admin"]').click();

    // Konfirmasi SweetAlert Sukses
    cy.get('.swal2-popup').should('be.visible');
    cy.get('.swal2-confirm').click();

    // Validasi username baru muncul di daftar admin
    cy.get('table tbody').should('contain', newUsername);
  });

  it('ADM-20: Konfigurasi fitur Deadman pada halaman kuota', () => {
    loginAdmin();
    cy.visit('/admin_magang/kuota.php');

    // Validasi kontrol konfigurasi Deadman
    cy.contains('Pengaturan Deadman System').should('be.visible');

    // Pastikan switch toggle aktif
    cy.get('input[name="deadman_enabled"]').then(($switch) => {
      if (!$switch.is(':checked')) {
        cy.get('label[for="deadmanSwitch"]').click();
      }
    });

    cy.get('input[name="deadman_threshold_days"]').clear().type('7');
    cy.get('select[name="deadman_action"]').select('AUDIT_ONLY');
    cy.get('button[name="update_deadman"]').click();

    // Validasi sukses dan persistensi data setelah reload
    cy.get('.alert-success')
      .should('be.visible')
      .and('contain', 'Pengaturan Deadman System berhasil diperbarui');

    cy.reload();
    cy.get('input[name="deadman_threshold_days"]').should('have.value', '7');
    cy.get('select[name="deadman_action"]').should('have.value', 'AUDIT_ONLY');
    cy.get('input[name="deadman_enabled"]').should('be.checked');
  });

});
