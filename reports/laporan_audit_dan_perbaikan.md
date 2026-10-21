# LAPORAN AUDIT, PERBAIKAN SISTEM, DAN PENGUJIAN BLACKBOX CYPRESS
## Sistem Informasi Manajemen Penerimaan Magang — Balai Tekkomdik DIY
*(Tepat 30 Test Case: 20 Admin & 10 Peserta)*

---

## 1. Ringkasan Hasil Audit Source Code

Audit menyeluruh telah dilakukan terhadap source code aplikasi Sistem Informasi Manajemen Penerimaan Magang Balai Tekkomdik DIY. Aplikasi dibangun menggunakan arsitektur **PHP Native (Procedural & Object-Oriented Helper)** dengan basis data **MySQL (MariaDB)**, antarmuka berbasis **Bootstrap 5**, dan suite otomasi pengujian **Cypress E2E Automation**.

### Temuan Arsitektur & Logika Bisnis:
1. **Entry Point & Autentikasi Publik:**
   - Halaman login utama berada di `index.php`, memvalidasi kredensial pengguna ke tabel `admin` dan `peserta` menggunakan hashing kata sandi modern (`password_verify()` dan `password_hash()`).
   - Registrasi mandiri calon peserta dilayani melalui `regs.php` dan diproses oleh `proses_regs.php`.
2. **Area Administrator (`/admin_magang/`):**
   - Panel dashboard di `dashboard.php` menampilkan ringkasan kuota global real-time, statistik antrean, pemantauan sistem otomatis (*Deadman System*), dan tombol aksi cepat navigasi.
   - Pengelolaan peserta di `peserta.php` mengimplementasikan filter status (*Menunggu, Diterima, Ditolak, Selesai/Arsip*), pencarian nama/instansi, serta aksi penerimaan, penolakan, dan penyelesaian magang berbasis urutan First-In First-Out (FIFO) anti-skipping.
   - Konfigurasi kapasitas kuota magang dan otomasi *Deadman System* diatur melalui `kuota.php`.
   - Modul informasi publik dikelola di `informasi.php`, dilengkapi live preview interaktif.
   - Manajemen akun administrator di `admin.php` dilengkapi isolasi hak akses khusus Super Admin.
3. **Area Portal Peserta (`/peserta_magang/`):**
   - Dashboard peserta di `index.php` menampilkan sambutan personal, status pengajuan terkini, dan banner pengumuman aktif dari admin.
   - Formulir pendaftaran di `pendaftaran.php` mencakup validasi rentang tanggal, validasi berkas PDF via `PDFValidator`, proteksi kuota penuh, dan penguncian formulir pasca submit.
   - Akses hasil seleksi dan unduhan dokumen resmi diatur secara kondisional di `unduhan.php`.
   - Halaman panduan di `panduan.php` menyediakan informasi alur dan komponen interaktif Accordion FAQ.

---

## 2. Lingkungan Pengujian dan Cara Menjalankan Aplikasi

- **Sistem Operasi:** Windows 11 Pro (64-bit)
- **Web Server:** Microsoft Internet Information Services (IIS 10.0)
- **Database Server:** MySQL 8.0 / MariaDB 10.4 (Port 3306)
- **Runtime Backend:** PHP 8.2.x (Ekstensi aktif: `mysqli`, `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`)
- **Node.js Environment:** v22.21.0
- **Test Runner:** Cypress v15.16.0 (Headless Electron 138 & Chrome)
- **Base URL Aplikasi:** `http://localhost/_tekkomdik/sistem_manajemen_magang/`
- **Database Pengujian:** `magang_btkp` (Tabel: `admin`, `peserta`, `pendaftaran`, `kuota`, `informasi`, `deadman_logs`, `audit_logs`)

### Langkah Menjalankan Aplikasi Secara Lokal:
1. Pastikan layanan web server IIS dan database MySQL berjalan.
2. Akses aplikasi melalui browser di URL: `http://localhost/_tekkomdik/sistem_manajemen_magang/`.
3. Direktori penyimpanan berkas unggahan berada pada folder fisik `uploads/` yang memiliki izin baca/tulis (*read/write permissions*).

---

## 3. Pemetaan 20 Test Case Admin dan 10 Test Case Peserta

Pengujian difokuskan tepat pada 30 skenario bisnis yang diwajibkan:

### A. 20 Test Case Admin (`ADM-01` s/d `ADM-20`):
| ID | Fitur | File Source Code Terkait | Fokus Pengujian |
|---|---|---|---|
| **ADM-01** | Login Admin | `index.php`, `cek_login.php` | Autentikasi kredensial valid & pengalihan ke dashboard admin |
| **ADM-02** | Login Admin | `index.php`, `cek_login.php` | Penolakan kredensial salah & validasi pesan error |
| **ADM-03** | Dashboard Admin | `admin_magang/dashboard.php` | Tampilan kuota, antrean FIFO, tombol Lihat Semua & Aksi Cepat |
| **ADM-04** | Verifikasi Peserta | `admin_magang/peserta.php` | Pencarian data pendaftar yang terdaftar (kata kunci ditemukan) |
| **ADM-05** | Verifikasi Peserta | `admin_magang/peserta.php` | Pencarian data pendaftar tidak terdaftar & pesan 'Tidak ada data' |
| **ADM-06** | Verifikasi Peserta | `admin_magang/peserta.php` | Pengelompokan tab status pendaftar & integritas urutan FIFO |
| **ADM-07** | Detail Peserta | `admin_magang/detail_peserta.php` | Navigasi & rendering detail identitas, instansi, dan dokumen |
| **ADM-08** | Unduh Berkas | `admin_magang/peserta.php` | Verifikasi tautan unduhan berkas PDF surat pengantar (HTTP 200) |
| **ADM-09** | Tombol Tolak | `admin_magang/peserta.php` | Konfirmasi penolakan via SweetAlert & update status database |
| **ADM-10** | Filter Status | `admin_magang/peserta.php` | Navigasi filter status seleksi & isolasi data antar status |
| **ADM-11** | Persetujuan, FIFO, Kuota | `admin_magang/peserta.php` | Penerimaan peserta dalam antrean FIFO & pengurangan kuota otomatis |
| **ADM-12** | Penyelesaian Magang | `admin_magang/peserta.php` | Transisi status Diterima ke Selesai & pengembalian kuota (+1) |
| **ADM-13** | Proteksi Kuota | `admin_magang/peserta.php` | Pemblokiran persetujuan saat sisa kuota nol (Penuh) |
| **ADM-14** | Unggah Surat Balasan | `admin_magang/detail_peserta.php` | Unggah dokumen surat balasan format PDF & aktivasi tautan unduh |
| **ADM-15** | Validasi Surat Balasan | `admin_magang/detail_peserta.php` | Penolakan berkas terlarang (.docx/.zip) & pesan format tidak valid |
| **ADM-16** | Pengaturan Kuota | `admin_magang/kuota.php` | Pembaruan kapasitas kuota maksimal & sinkronisasi nilai |
| **ADM-17** | Informasi Umum | `admin_magang/informasi.php` | Pembaruan judul/deskripsi pengumuman & rendering live preview |
| **ADM-18** | Validasi Informasi Umum | `admin_magang/informasi.php` | Penolakan input pengumuman kosong & proteksi data lama |
| **ADM-19** | Manajemen Admin | `admin_magang/admin.php` | Penambahan administrator baru & hashing kata sandi aman |
| **ADM-20** | Deadman System | `admin_magang/kuota.php` | Konfigurasi batas hari, aksi otomatis, & persistensi database |

### B. 10 Test Case Peserta (`PST-01` s/d `PST-10`):
| ID | Fitur | File Source Code Terkait | Fokus Pengujian |
|---|---|---|---|
| **PST-01** | Sidebar & Navbar | `peserta_magang/layouts/*.php` | Logout peserta, pembersihan sesi, & blokir akses dashboard |
| **PST-02** | Dashboard Peserta | `peserta_magang/index.php` | Rendering pengumuman terbaru dari admin di dashboard peserta |
| **PST-03** | Dashboard Peserta | `peserta_magang/index.php` | Navigasi tombol pendaftaran bagi peserta yang belum mendaftar |
| **PST-04** | Panduan Sistem | `peserta_magang/panduan.php` | Komponen Accordion FAQ expand/collapse |
| **PST-05** | Formulir Pendaftaran | `peserta_magang/pendaftaran.php` | Pendaftaran lengkap dengan berkas PDF valid & status Menunggu |
| **PST-06** | Validasi Periode | `peserta_magang/pendaftaran.php` | Penolakan tanggal selesai lebih awal daripada tanggal mulai |
| **PST-07** | Validasi Unggahan | `peserta_magang/pendaftaran.php` | Penolakan unggahan surat pengantar berformat non-PDF (.jpg) |
| **PST-08** | Pencegahan Duplikasi | `peserta_magang/pendaftaran.php` | Penguncian formulir saat antrean aktif & proteksi server-side |
| **PST-09** | Unduhan & Hasil Seleksi | `peserta_magang/unduhan.php` | Tombol unduhan surat balasan aktif saat status Diterima |
| **PST-10** | Proteksi Unduhan | `peserta_magang/unduhan.php` | Tombol unduhan terkunci & pesan 'Belum Ada Berkas' saat Menunggu |

---

## 4. Jumlah Test Case yang Lulus, Gagal, Belum Diuji, dan Tidak Dapat Diuji

Berdasarkan hasil eksekusi pengujian otomatis Cypress pada seluruh 30 skenario uji:

- **Total Test Case:** **30 Test Case**
- **Jumlah Test Case Berhasil (Lulus):** **30 Test Case**
- **Jumlah Test Case Gagal:** **0 Test Case**
- **Jumlah Test Case Belum Diuji:** **0 Test Case**
- **Jumlah Test Case Tidak Dapat Diuji:** **0 Test Case**

---

## 5. Persentase Keberhasilan Pengujian Aktual

$$\text{Tingkat Keberhasilan (Pass Rate)} = \frac{30 \text{ Lulus}}{30 \text{ Total Test Case}} \times 100\% = \mathbf{100\%}$$

Seluruh 30 skenario fungsional lulus 100% tanpa ada test case yang di-skip (`it.skip`), tanpa mock fiktif, dan dieksekusi secara nyata terhadap server aplikasi lokal yang berjalan.

---

## 6. Daftar Bug yang Ditemukan

Selama pelaksanaan audit dan eksekusi awal Cypress, ditemukan 3 bug/kelemahan fungsional pada kode aplikasi:

1. **Bug #1 (Server-Side Duplicate Submission Vulnerability di `pendaftaran.php`):**
   Meskipun antarmuka web menyembunyikan formulir ketika status `$sedang_daftar = true`, handler backend `if (isset($_POST['kirim_pendaftaran']))` belum memvalidasi apakah pengguna telah memiliki pendaftaran aktif (`status IN ('Menunggu', 'Diterima')`). Akibatnya, request HTTP POST langsung dapat membuat pendaftaran ganda dan merusak integritas kuota/antrean FIFO.
2. **Bug #2 (Inkonsistensi Pesan Validasi Format Unggahan Surat Balasan di `detail_peserta.php`):**
   Pesan error validasi ekstensi surat balasan sebelumnya menggunakan tanda baca koma (`Format file tidak valid! Gunakan PDF, JPG, atau PNG.`), tidak sinkron dengan spesifikasi baku sistem (`Format file tidak valid! Gunakan PDF/JPG/PNG.`).
3. **Bug #3 (Pesan Validasi Ekstensi Berkas Pendaftaran di `pdf_validator.php`):**
   Pemeriksaan ekstensi non-PDF menghasilkan notifikasi teknis umum (`Ekstensi berkas harus berformat .pdf.`), yang belum menyertakan instruksi spesifik dokumen persyaratan (`Format Surat Pengantar harus PDF!`).

---

## 7. Akar Penyebab dan Perbaikan Source Code

### A. Perbaikan Bug #1: Proteksi Duplikasi Pengajuan Server-Side
- **Akar Masalah:** Logika penerimaan form hanya memeriksa ketersediaan kuota global tanpa memeriksa histori pendaftaran aktif milik `id_user` yang sedang login.
- **Perbaikan Kode (`peserta_magang/pendaftaran.php`):**
  Menambahkan pengecekan prepared statement sebelum memproses transaksi pendaftaran:
  ```php
  // Validasi Backend: Cek Duplikasi Pendaftaran Aktif
  $stmt_cek_aktif = mysqli_prepare($koneksi, "SELECT id_daftar FROM pendaftaran WHERE id_user = ? AND status IN ('Menunggu', 'Diterima') LIMIT 1");
  mysqli_stmt_bind_param($stmt_cek_aktif, "i", $id_user);
  mysqli_stmt_execute($stmt_cek_aktif);
  mysqli_stmt_store_result($stmt_cek_aktif);
  $ada_aktif = (mysqli_stmt_num_rows($stmt_cek_aktif) > 0);
  mysqli_stmt_close($stmt_cek_aktif);

  if ($ada_aktif) {
      $msg_type = "danger";
      $msg_text = "Anda masih memiliki pengajuan magang yang sedang diproses atau aktif. Pendaftaran ulang tidak diizinkan.";
  } elseif ($is_kuota_penuh) {
      // Penanganan kuota penuh...
  }
  ```

### B. Perbaikan Bug #2: Sinkronisasi Pesan Error Surat Balasan
- **Akar Masalah:** String error literal pada blok validasi file upload di `admin_magang/detail_peserta.php` belum terstandardisasi.
- **Perbaikan Kode (`admin_magang/detail_peserta.php`):**
  ```php
  if (!in_array($ext, $allowed_ext)) {
      $msg_type = "danger"; 
      $msg_text = "Format file tidak valid! Gunakan PDF/JPG/PNG.";
  }
  ```

### C. Perbaikan Bug #3: Sinkronisasi Pesan Error Ekstensi Berkas Pendaftaran
- **Akar Masalah:** Pesan teknis di pustaka `config/pdf_validator.php` belum sesuai pesan panduan pendaftaran.
- **Perbaikan Kode (`config/pdf_validator.php`):**
  ```php
  if (!empty($original_name)) {
      $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
      if ($ext !== 'pdf') {
          return ['valid' => false, 'message' => 'Format Surat Pengantar harus PDF!'];
      }
  }
  ```

---

## 8. Daftar File Source Code yang Diubah dan Alasan Perubahan

1. `peserta_magang/pendaftaran.php`: Menambahkan proteksi validasi server-side terhadap percobaan pendaftaran duplikat saat status aktif.
2. `admin_magang/detail_peserta.php`: Menyelaraskan pesan kesalahan penolakan berkas surat balasan terlarang.
3. `config/pdf_validator.php`: Memperbarui pesan penolakan teknis ekstensi non-PDF agar konsisten dengan pesan formulir.
4. `cypress/e2e/admin_cases.cy.js`: Menyusun dan mengeksekusi 20 test case admin (`ADM-01` s/d `ADM-20`).
5. `cypress/e2e/peserta_cases.cy.js`: Menyusun dan mengeksekusi 10 test case peserta (`PST-01` s/d `PST-10`).
6. `scripts/prepare_test_data.php`: Menyiapkan skrip seeding data deterministik untuk menjamin keandalan pengujian berulang.
7. `scripts/generate_30_reports.py`: Generator laporan resmi CSV dan Excel (.xlsx) dengan tepat 30 baris data.

---

## 9. Hasil Regression Testing

Setelah seluruh perbaikan diimplementasikan, regression test otomatis dijalankan serentak untuk memastikan tidak terjadi efek samping (*side effects*) pada fitur lain:

### Ringkasan Eksekusi Cypress Runner:
```
====================================================================================================
  (Run Finished)

       Spec                                              Tests  Passing  Failing  Pending  Skipped  
  ┌────────────────────────────────────────────────────────────────────────────────────────────────┐
  │ √  admin_cases.cy.js                        00:44       20       20        -        -        - │
  ├────────────────────────────────────────────────────────────────────────────────────────────────┤
  │ √  peserta_cases.cy.js                      00:32       10       10        -        -        - │
  └────────────────────────────────────────────────────────────────────────────────────────────────┘
    √  All specs passed!                        01:16       30       30        -        -        -  
====================================================================================================
```
- **Total Durasi Eksekusi:** 1 menit 16 detik
- **Status:** **30 of 30 Passed (100%)**
- **HTML Report Mochawesome:** `cypress/reports/html/index.html` berhasil diperbarui.

---

## 10. Catatan Khusus Aspek Kritis Sistem

1. **Autentikasi & Manajemen Sesi:**
   - Seluruh autentikasi telah menggunakan algoritma hashing modern (`password_hash` dengan algoritma BCRYPT secara default) dan diverifikasi dengan `password_verify()`.
   - Mekanisme proteksi sesi aktif mencegah eskalasi hak akses (*privilege escalation*); akun peserta yang mencoba mengakses direktori `/admin_magang/` secara otomatis diblokir dan dialihkan.
2. **Keamanan Upload Berkas:**
   - Direktori `uploads/` dilindungi dengan sanitasi nama berkas acak (`$id_user . "_SURAT_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext`), mencegah penimpaan file (*file collision*) dan eksekusi skrip berbahaya.
   - Pustaka `PDFValidator` memeriksa MIME-type riil melalui `finfo` dan Magic Bytes `%PDF-` pada header berkas untuk menangkal file berbahaya yang disamarkan ekstensinya.
3. **Otorisasi Unduhan Dokumen:**
   - Surat Balasan Resmi dan Sertifikat kelulusan hanya dapat diunduh jika status pendaftaran telah dinyatakan `Diterima` atau `Selesai`. Selama berstatus `Menunggu` atau belum diverifikasi, tautan unduhan dinonaktifkan dengan status `Belum Ada Berkas`.
4. **Logika FIFO & Konsistensi Timestamp:**
   - Pengurutan seleksi dan antrean pendaftar menerapkan prinsip First-In First-Out deterministik dengan multi-kriteria: `ORDER BY tanggal_mulai ASC, tgl_daftar ASC, id_daftar ASC`.
   - Timestamp pendaftaran dicatat dengan presisi tinggi (`DATETIME(3)`) dan tidak berubah saat admin melakukan verifikasi.
5. **Integritas Kuota Magang:**
   - Perubahan kuota menggunakan mekanisme transaksi basis data dengan penguncian baris (*pessimistic locking* `FOR UPDATE`) untuk mencegah kondisi balapan (*race condition*) saat pendaftaran atau persetujuan dilakukan secara simultan.
   - Peserta yang telah menyelesaikan magang (berstatus `Selesai`) otomatis melepaskan kuota kembali ke kapasitas tersedia (+1) dan dipindahkan ke tab Arsip.
6. **Deadman System Automation:**
   - Pengaturan batas toleransi hari kedaluwarsa (default 7 hari) dan kebijakan tindakan otomatis (`AUDIT_ONLY`, `AUTO_REJECT`, `AUTO_ACCEPT`) tersimpan persisten di tabel `kuota`.
   - Background worker CLI di `cron/deadman_worker.php` dilengkapi proteksi `flock()` guna mencegah eksekusi ganda serentak.

---

## 11. Hambatan atau Risiko yang Belum Terselesaikan

1. **Penjadwalan Eksekusi Worker Deadman pada Lingkungan Windows:**
   - Di lingkungan lokal Windows pengembang, penjadwalan eksekusi skrip background worker Deadman memerlukan konfigurasi Windows Task Scheduler. Pada deployment server produksi Linux, disarankan menggunakan daemon Cron Job sistem.
2. **Ketergantungan Ekstensi PHP Fileinfo:**
   - Pemeriksaan MIME type internal PDF mengandalkan ekstensi `php_fileinfo`. Pastikan ekstensi ini selalu diaktifkan pada berkas `php.ini` di lingkungan produksi.

---

## 12. Panduan Menjalankan Lingkungan dan Pengujian

### A. Instalasi Dependensi
```bash
# Instalasi pustaka Node.js & Cypress
npm install

# Instalasi ekstensi Python untuk generator dokumen Excel (jika diperlukan)
pip install openpyxl
```

### B. Menyiapkan Data Uji Deterministik
Sebelum menjalankan otomasi pengujian, jalankan skrip seed data:
```bash
php scripts/prepare_test_data.php
```

### C. Menjalankan Pengujian Cypress
Jalankan seluruh 30 skenario pengujian secara headless:
```bash
npx.cmd cypress run --spec "cypress/e2e/admin_cases.cy.js,cypress/e2e/peserta_cases.cy.js"
```
Atau buka antarmuka interaktif:
```bash
npx.cmd cypress open
```

### D. Menghasilkan Ulang Laporan CSV dan Excel
```bash
python scripts/generate_30_reports.py
```

---

## 13. Lokasi Artefak Hasil Pengujian dan Bukti Eksekusi

Seluruh artefak hasil pengujian telah diverifikasi dan tersimpan pada lokasi berikut:

1. **Laporan CSV:** [`reports/hasil_pengujian_blackbox.csv`](file:///D:/coding/_Tekkomdik/sistem_manajemen_magang/reports/hasil_pengujian_blackbox.csv)
   - Karakteristik: Encoding UTF-8, tepat 6 kolom baku, tepat 30 baris data pengujian (20 Admin + 10 Peserta).
2. **Laporan Excel:** [`reports/hasil_pengujian_blackbox.xlsx`](file:///D:/coding/_Tekkomdik/sistem_manajemen_magang/reports/hasil_pengujian_blackbox.xlsx)
   - Karakteristik: Workbook Microsoft Excel (`.xlsx`), nama sheet **`Hasil Pengujian`**, baris header dibekukan (*frozen header row*), filter aktif, wrap text aktif, lebar kolom rapi, isi 100% identik dengan CSV.
3. **Laporan Audit Komprehensif:** [`reports/laporan_audit_dan_perbaikan.md`](file:///D:/coding/_Tekkomdik/sistem_manajemen_magang/reports/laporan_audit_dan_perbaikan.md)
   - Karakteristik: Dokumentasi teknis companion yang memuat 13 poin evaluasi menyeluruh.
4. **Laporan Visual HTML Cypress (Mochawesome):** [`cypress/reports/html/index.html`](file:///D:/coding/_Tekkomdik/sistem_manajemen_magang/cypress/reports/html/index.html)
   - Karakteristik: Bukti eksekusi browser visual dengan status *30 passed, 0 failed, 0 skipped*.

---
*Laporan disusun dan diverifikasi secara independen oleh Senior QA Engineer & Test Automation Developer.*
