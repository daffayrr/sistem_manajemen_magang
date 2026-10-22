# LAPORAN HASIL PENGUJIAN DAN QUALITY ASSURANCE (QA)
## Sistem Informasi Manajemen Penerimaan Magang — Balai Tekkomdik DIY

---

## 1. Tujuan Pengujian

Pengujian perangkat lunak ini bertujuan untuk:
1. **Verifikasi Fungsional & Aturan Bisnis:** Memastikan seluruh fitur pada modul Administrator, Super Admin, dan Peserta bekerja sesuai dengan spesifikasi teknis dan aturan bisnis yang terimplementasi dalam source code.
2. **Penegakan Logika Seleksi FIFO (First-In, First-Out):** Memvalidasi bahwa antrean seleksi diproses secara adil dan deterministik berdasarkan multi-kriteria periode magang (`tanggal_mulai`, `tanggal_selesai`, `tgl_daftar`, `id_daftar`) tanpa toleransi terhadap aksi skipping.
3. **Integritas Kapasitas Kuota Dinamis:** Menguji mekanisme transaksi atomik dan penguncian baris basis data (*pessimistic locking* `FOR UPDATE`) guna mencegah kondisi balapan (*race condition*) atau kelebihan kuota (*overbooking*).
4. **Validasi Dokumen Otomatis & Keamanan Unggahan:** Menguji ketahanan pustaka `PDFValidator` dalam memeriksa integritas teknis (*magic bytes* `%PDF-`, MIME type `application/pdf`, batas ukuran 5MB) dan klasifikasi konten heuristik (membedakan surat permohonan resmi dari modul kuliah atau sertifikat).
5. **Keandalan Otomasi Deadman System:** Memastikan skrip worker terjadwal (`cron/deadman_worker.php`) dapat mengidentifikasi pengajuan kedaluwarsa secara aman, menerapkan mode *Dry-Run*, mencegah eksekusi ganda serentak via *process lock* (`flock`), serta mencatat log audit.
6. **Autentikasi & Otorisasi:** Memverifikasi keamanan kata sandi (`password_hash` & `password_verify`), isolasi hak akses role (*Super Admin*, *Admin*, *Peserta*), penanganan sesi, dan pencegahan duplikasi pendaftaran di sisi server.

---

## 2. Ruang Lingkup dan Environment Pengujian

### A. Ruang Lingkup Pengujian
Pengujian mencakup pengujian fungsional *End-to-End* (E2E) antarmuka peramban berbasis Cypress, serta pengujian logika bisnis backend dan basis data berbasis script PHP testing mandiri:
- **E2E Browser Automation:** 30 test case bisnis wajib (20 skenario Admin dan 10 skenario Peserta).
- **Backend Business Logic & Security Assertions:** 83 skenario pengujian backend mencakup logika kuota, FIFO, Deadman, validasi berkas PDF, serta manajemen hak akses.

### B. Lingkungan Pengujian (Test Environment)
- **Sistem Operasi:** Windows 11 Pro 64-bit
- **Web Server:** Microsoft Internet Information Services (IIS 10.0)
- **Database Server:** MySQL 8.0 / MariaDB 10.4 (Port 3306)
- **Runtime Backend:** PHP 8.2.x (Ekstensi aktif: `mysqli`, `fileinfo`, `mbstring`, `openssl`)
- **Node.js Environment:** v22.21.0
- **E2E Test Runner:** Cypress v15.16.0 (Headless Electron 138 & Chrome)
- **Reporter:** Cypress Mochawesome Reporter v4.0.2
- **Base URL Aplikasi:** `http://localhost/_tekkomdik/sistem_manajemen_magang/`
- **Database Pengujian:** `db_magang_btkp`

---

## 3. Daftar Test Suite dan Perintah yang Dijalankan

| No | Test Suite | File Pengujian | Tipe Pengujian | Jumlah Test |
|---|---|---|---|---|
| 1 | **Admin E2E Suite** | `cypress/e2e/admin_cases.cy.js` | Cypress Browser E2E | 20 Skenario |
| 2 | **Peserta E2E Suite** | `cypress/e2e/peserta_cases.cy.js` | Cypress Browser E2E | 10 Skenario |
| 3 | **Admin Business Logic** | `tests/AdminBusinessLogicTest.php` | PHP Backend / Integration | 20 Skenario |
| 4 | **Peserta Business Logic** | `tests/PesertaBusinessLogicTest.php` | PHP Backend / Integration | 18 Skenario |
| 5 | **Addon & Master Logic** | `tests/AddonBusinessLogicTest.php` | PHP Backend / Algoritmik | 45 Skenario |
| **TOTAL** | | | | **113 Skenario** |

### Perintah Eksekusi Pengujian:
```bash
# 1. Reset dan Seeding Data Uji Deterministik
php scripts/prepare_test_data.php

# 2. Menjalankan Seluruh Pengujian Cypress E2E (Headless)
npx.cmd cypress run --spec "cypress/e2e/admin_cases.cy.js,cypress/e2e/peserta_cases.cy.js"

# 3. Menjalankan Pengujian Logika Bisnis PHP Backend
php tests/AdminBusinessLogicTest.php
php tests/PesertaBusinessLogicTest.php
php tests/AddonBusinessLogicTest.php

# 4. Verifikasi Deadman System CLI Worker (Mode Simulasi Dry-Run)
php scripts/deadman_worker.php --dry-run
```

---

## 4. Hasil Aktual Pengujian

### Kategori Status:
- `PASS`: Pengujian benar-benar dijalankan dan seluruh asersi berhasil memenuhi *expected result*.
- `FAIL`: Pengujian dijalankan namun hasil aktual tidak sesuai *expected result*.
- `BLOCKED`: Pengujian terhambat oleh keterbatasan lingkungan atau dependensi.
- `NOT RUN`: Pengujian dirancang tetapi belum dieksekusi.

---

### Bagian 1: Automated E2E Cypress Testing — Admin Cases (ADM-01 s/d ADM-20)

| ID | Area | Pengujian | Status | Bukti / Keterangan |
|---|---|---|---|---|
| **ADM-01** | Autentikasi Admin | Login dengan kredensial benar | **PASS** | Berhasil login (`admin`/`admin123`), sesi terbentuk, dialihkan ke `dashboard.php`, widget FIFO tampil. |
| **ADM-02** | Autentikasi Admin | Login dengan kredensial salah | **PASS** | Kredensial salah ditolak, pesan error `Login Gagal! Akun tidak ditemukan.` tampil, akses dashboard diblokir. |
| **ADM-03** | Dashboard Admin | Dashboard dan navigasi Admin | **PASS** | Informasi sisa kuota tampil; tombol `Lihat Semua`, navigasi `Aksi Cepat` (`kuota.php`, `informasi.php`, `admin.php`) berfungsi normal. |
| **ADM-04** | Verifikasi Peserta | Pencarian peserta yang ditemukan | **PASS** | Pencarian instansi `Alma Ata` berhasil memfilter tabel dan menampilkan baris data yang relevan. |
| **ADM-05** | Verifikasi Peserta | Pencarian peserta yang tidak ditemukan | **PASS** | Pencarian kata kunci unik `XYZ_NAMA_TIDAK_ADA_DI_SISTEM_999` menghasilkan pesan informatif `Tidak ada data`. |
| **ADM-06** | Verifikasi Peserta | Pengelompokan peserta berdasarkan status | **PASS** | Tab `Menunggu`, `Diterima`, `Ditolak`, dan `Arsip` memfilter data sesuai status; urutan antrean FIFO terpelihara. |
| **ADM-07** | Detail Peserta | Membuka detail peserta | **PASS** | Tombol `Detail` membuka `detail_peserta.php?id=...`, merender seksi data diri, instansi, dan dokumen pengajuan. |
| **ADM-08** | Unduh Berkas | Mengunduh berkas peserta | **PASS** | Tautan unduhan berkas PDF surat pengantar tervalidasi aktif (`target="_blank"`) dan merespons HTTP 200. |
| **ADM-09** | Verifikasi Peserta | Menolak peserta | **PASS** | Konfirmasi penolakan melalui modal SweetAlert berhasil mengubah status peserta menjadi `Ditolak` di basis data. |
| **ADM-10** | Filter Status | Filter status seleksi | **PASS** | Seluruh tombol filter status seleksi (`.filter-btn`) berhasil memfilter data secara eksklusif. |
| **ADM-11** | FIFO & Kuota | Menyetujui peserta ketika kuota tersedia | **PASS** | Peserta antrean teratas disetujui, status menjadi `Diterima`, timestamp verifikasi tersimpan, sisa kuota berkurang 1. |
| **ADM-12** | Penyelesaian Magang | Menyelesaikan periode magang peserta | **PASS** | Peserta aktif diselesaikan melalui tombol `Selesaikan`, data dipindah ke Arsip, dan kapasitas kuota bertambah (+1). |
| **ADM-13** | Proteksi Kuota | Mencoba menyetujui peserta ketika kuota penuh | **PASS** | Saat sisa kuota 0, sistem memblokir persetujuan dan menampilkan notifikasi `Gagal! Kuota Magang saat ini Penuh. Tunggu ada peserta yang selesai.`. |
| **ADM-14** | Dokumen Balasan | Mengunggah Surat Balasan format valid (PDF) | **PASS** | Dokumen PDF berhasil disimpan ke `uploads/`, metadata tercatat, dan tautan `Buka Surat Balasan Terunggah` aktif. |
| **ADM-15** | Validasi Dokumen | Menolak unggahan Surat Balasan format terlarang | **PASS** | Pengunggahan file non-PDF (.docx) ditolak di sisi server dengan pesan `Format file tidak valid! Gunakan PDF/JPG/PNG.`. |
| **ADM-16** | Pengaturan Kuota | Memperbarui kapasitas kuota maksimal | **PASS** | Nilai kapasitas kuota berhasil diubah menjadi 10 orang, tersimpan ke tabel `kuota`, dan perhitungan sisa kuota sinkron. |
| **ADM-17** | Informasi Umum | Memperbarui judul dan deskripsi pengumuman | **PASS** | Konten pengumuman berhasil diperbarui di `informasi.php`, dan komponen live preview langsung merender perubahan. |
| **ADM-18** | Validasi Pengumuman | Menolak simpan pengumuman dengan field kosong | **PASS** | Validasi server-side memblokir input kosong dan menampilkan alert `Judul dan deskripsi informasi tidak boleh kosong.`. |
| **ADM-19** | Manajemen Admin | Menambahkan akun Administrator baru | **PASS** | Admin baru berhasil ditambahkan, kata sandi tersimpan dalam bentuk hash (`password_hash`), dan akun muncul di tabel. |
| **ADM-20** | Deadman System | Konfigurasi fitur Deadman pada `kuota.php` | **PASS** | Toggle status sistem, ambang toleransi hari (7 hari), dan kebijakan `AUDIT_ONLY` berhasil disimpan dan persisten paska reload. |

---

### Bagian 2: Automated E2E Cypress Testing — Peserta Cases (PST-01 s/d PST-10)

| ID | Area | Pengujian | Status | Bukti / Keterangan |
|---|---|---|---|---|
| **PST-01** | Autentikasi Peserta | Logout peserta dari portal | **PASS** | Sesi dihancurkan, pengguna dialihkan ke `index.php`, notifikasi logout tampil, direct URL ke dashboard ditolak. |
| **PST-02** | Dashboard Peserta | Dashboard menampilkan pengumuman admin | **PASS** | Komponen banner `.alert-info` berhasil merender judul dan isi pengumuman terkini dari basis data. |
| **PST-03** | Navigasi Peserta | Navigasi ke formulir pendaftaran | **PASS** | Peserta baru diarahkan ke formulir `pendaftaran.php` saat mengklik tombol `Isi Formulir Pendaftaran`. |
| **PST-04** | Panduan Sistem | Interaktivitas Accordion FAQ | **PASS** | Klik pertanyaan FAQ berhasil membuka jawaban (`#faq1`, `#faq2`) dengan animasi expand Bootstrap yang mulus. |
| **PST-05** | Formulir Pendaftaran | Pengajuan pendaftaran dengan data valid | **PASS** | Pengisian biodata lengkap + upload berkas PDF berhasil disubmit, status pendaftaran menjadi `Menunggu Verifikasi Admin`. |
| **PST-06** | Validasi Periode | Menolak periode tanggal magang tidak valid | **PASS** | Tanggal selesai < tanggal mulai ditolak server dengan pesan `Tanggal selesai magang harus lebih besar dari tanggal mulai!`. |
| **PST-07** | Validasi Unggahan | Menolak dokumen berformat dilarang | **PASS** | Pengunggahan file gambar (.jpg) pada field Surat Pengantar ditolak dengan pesan `Format Surat Pengantar harus PDF!`. |
| **PST-08** | Proteksi Duplikasi | Mencegah pendaftaran ulang saat antrean aktif | **PASS** | Formulir otomatis terkunci dan digantikan kartu status antrean; submission langsung via HTTP POST diblokir server. |
| **PST-09** | Unduhan & Hasil | Unduhan aktif setelah peserta diterima | **PASS** | Peserta berstatus `Diterima` mendapati status `Aktif Magang` dan tombol `Unduh` surat balasan resmi aktif. |
| **PST-10** | Proteksi Unduhan | Unduhan terkunci saat status Menunggu | **PASS** | Peserta antrean `Menunggu` mendapati status `Menunggu Verifikasi`, tombol unduhan nonaktif, dan pesan `Belum Ada Berkas`. |

---

### Bagian 3: Backend Business Logic Test — Admin (AdminBusinessLogicTest.php)

| ID | Area | Pengujian | Status | Bukti / Keterangan |
|---|---|---|---|---|
| **TEST-ADM-001** | Keamanan | Verifikasi kredensial Super Admin `admin_btkp` | **PASS** | Password hash diverifikasi melalui fungsi bawaan PHP `password_verify('abc_123')`. |
| **TEST-ADM-002** | Keamanan | Penolakan kata sandi salah untuk akun admin | **PASS** | String sandi acak mengembalikan nilai false saat diverifikasi. |
| **TEST-ADM-003** | Sesi | Proteksi sesi pengguna logout | **PASS** | Variabel sesi `login` kosong diblokir dari akses administratif. |
| **TEST-ADM-004** | Otorisasi | Proteksi hak akses role Peserta ke area admin | **PASS** | Akun dengan role `Peserta` tidak diizinkan mengakses resource administratif. |
| **TEST-ADM-005** | Keamanan Superadmin | Proteksi pencegahan hapus akun Super Admin | **PASS** | Query penghapusan akun berstatus Super Admin ditolak oleh aturan backend. |
| **TEST-ADM-006** | Keamanan Superadmin | Pencegahan admin menghapus dirinya sendiri | **PASS** | Parameter ID hapus yang identik dengan ID sesi aktif diblokir. |
| **TEST-ADM-007** | Otorisasi Superadmin | Hak menghapus akun admin biasa | **PASS** | Super Admin memiliki kewenangan penuh menghapus administrator reguler. |
| **TEST-ADM-008** | Hak Akses | Penegakan role saat pembuatan admin baru | **PASS** | Akun yang dibuat oleh admin non-superadmin dipaksakan menjadi role `Admin`. |
| **TEST-ADM-009** | Navigasi UI | Validasi pembaruan label menu `Kuota Magang` | **PASS** | String label menu navigasi konsisten dengan fitur kuota terintegrasi. |
| **TEST-ADM-010** | Logika FIFO | Anti-skipping antrean verifikasi pendaftar | **PASS** | Sistem menolak memproses pendaftar urutan kedua sebelum pendaftar pertama ditindaklanjuti. |
| **TEST-ADM-011** | Integritas Kuota | Transaksi database & Pessimistic Locking | **PASS** | Klausa SQL `SELECT ... FOR UPDATE` aktif menjaga konsistensi kuota saat konkurensi. |
| **TEST-ADM-012** | Integritas Kuota | Penolakan penerimaan peserta saat sisa kuota 0 | **PASS** | Logika backend menghentikan transaksi jika kuota maksimal telah terpenuhi. |
| **TEST-ADM-013** | Kalkulasi Kuota | Hanya status `Diterima` yang mengonsumsi kuota | **PASS** | Pendaftar berstatus `Menunggu`, `Ditolak`, atau `Selesai` tidak mengurangi kuota aktif. |
| **TEST-ADM-014** | Kelulusan Magang | Penolakan Selesai jika dokumen nilai kosong | **PASS** | Tombol kelulusan nonaktif jika file sertifikat nilai belum diunggah. |
| **TEST-ADM-015** | Kelulusan Magang | Penolakan Selesai jika dokumen keterangan kosong | **PASS** | Tombol kelulusan nonaktif jika surat keterangan belum diunggah. |
| **TEST-ADM-016** | Storage Integrity | Pemeriksaan fisik keberadaan file pada disk | **PASS** | Verifikasi `file_exists()` pada direktori fisik `uploads/` sebelum mengubah status ke Selesai. |
| **TEST-ADM-017** | Kelulusan Magang | Transisi berhasil saat kedua dokumen valid di disk | **PASS** | Status peserta berhasil berubah menjadi `Selesai` jika kedua berkas fisik tersedia. |
| **TEST-ADM-018** | Arsip Magang | Pengurutan data arsip berdasarkan waktu selesai | **PASS** | Query arsip menggunakan kolom `completed_at DESC` secara deterministik. |
| **TEST-ADM-019** | Integritas Data | Pemeliharaan data pendaftaran historis | **PASS** | Seluruh baris data historis pendaftaran terpelihara utuh paska migrasi. |
| **TEST-ADM-020** | Audit Trail | Pencatatan log administratif presisi milidetik | **PASS** | Aksi verifikasi terekam pada tabel `audit_trail` dengan kolom `created_at` berpresisi `NOW(3)`. |

---

### Bagian 4: Backend Business Logic Test — Peserta (PesertaBusinessLogicTest.php)

| ID | Area | Pengujian | Status | Bukti / Keterangan |
|---|---|---|---|---|
| **TEST-PST-001** | Transparansi Kuota | Ketersediaan file `posisi_kuota.php` & menu sidebar | **PASS** | File controller rute tersedia dan tautan terhubung di sidebar layout peserta. |
| **TEST-PST-002** | Konsistensi Kuota | Data kuota diambil langsung secara real-time | **PASS** | Angka kapasitas, terpakai, dan sisa terikat langsung pada tabel `kuota` dan `pendaftaran`. |
| **TEST-PST-003** | Transparansi FIFO | ID Peserta bersumber dari basis data aktual | **PASS** | Nilai penanda pendaftar terikat pada primary key `id_user` yang valid. |
| **TEST-PST-004** | Validasi Periode | Format rendering tanggal mulai dan selesai | **PASS** | Komponen tanggal menampilkan representasi kalender yang valid. |
| **TEST-PST-005** | Presisi Waktu | Timestamp pendaftaran bersumber dari `tgl_daftar` | **PASS** | Waktu pengajuan antrean merujuk ke data historis asli pengajuan pendaftar. |
| **TEST-PST-006** | Presisi Waktu | Dukungan presisi milidetik pada `tgl_daftar` | **PASS** | Kolom basis data bertipe `DATETIME(3)` mengembalikan fraksi detik. |
| **TEST-PST-007** | Antarmuka UI | Empty state informatif saat antrean kosong | **PASS** | Komponen tabel menyediakan placeholder visual ketika data pendaftaran belum tersedia. |
| **TEST-PST-008** | Rumus Kuota | Konsistensi formula sisa kuota | **PASS** | Formula: `Sisa Kuota = Kapasitas Maksimal - Total Peserta Diterima` konsisten. |
| **TEST-PST-009** | Formulir | Nonaktivasi formulir pendaftaran saat kuota penuh | **PASS** | Antarmuka formulir menyembunyikan input form saat sisa kuota bernilai 0. |
| **TEST-PST-010** | Backend Form | Penolakan pendaftaran server-side saat kuota penuh | **PASS** | Eksekusi form pendaftaran langsung menolak request jika kapasitas terpenuhi. |
| **TEST-PST-011** | Konkurensi | Database locking `FOR UPDATE` pada pendaftaran | **PASS** | Mekanisme transaksi melindungi sisa kuota dari kondisi *overbooking*. |
| **TEST-PST-012** | Logika FIFO | Konsistensi pengurutan FIFO antar modul | **PASS** | Aturan pengurutan di portal peserta identik 100% dengan urutan pada dashboard admin. |
| **TEST-PST-013** | Logika FIFO | Tie-breaker deterministik `id_daftar ASC` | **PASS** | Bila terdapat timestamp identik, ID unik pendaftaran menjadi penentu mutlak urutan. |
| **TEST-PST-014** | Keamanan Data | Halaman Cek Posisi bersifat Read-Only | **PASS** | Membuka transparansi antrean tidak memicu perubahan status pada basis data. |
| **TEST-PST-015** | Otorisasi | Isolasi hak akses berbasis role `Peserta` | **PASS** | Pengguna tanpa sesi role Peserta dialihkan ke halaman login. |
| **TEST-PST-016** | Privasi (GDPR) | Penerapan prinsip *Data Minimization* pada antrean | **PASS** | Tabel publik tidak memaparkan nomor HP, alamat rumah, email, atau password peserta. |
| **TEST-PST-017** | Navigasi UI | Integritas rute navigasi sidebar | **PASS** | Seluruh tautan menu sidebar terhubung secara konsisten ke target modul yang tepat. |
| **TEST-PST-018** | Integritas Modul | Pemeliharaan file fungsional peserta lain | **PASS** | File `index.php`, `panduan.php`, `pendaftaran.php`, dan `unduhan.php` utuh. |

---

### Bagian 5: Master Addon & Advanced Logic Test (AddonBusinessLogicTest.php)

| ID | Area | Pengujian | Status | Bukti / Keterangan |
|---|---|---|---|---|
| **TEST-ADD-A01** | FIFO Multi-Periode | Prioritas utama berdasarkan `tanggal_mulai ASC` | **PASS** | Pendaftar dengan rencana mulai lebih awal diposisikan di urutan prioritas lebih tinggi. |
| **TEST-ADD-A02** | FIFO Multi-Periode | Pemecah seri periode via `tanggal_selesai ASC` | **PASS** | Jika tanggal mulai sama, tanggal selesai yang lebih cepat menjadi penentu urutan. |
| **TEST-ADD-A03** | FIFO Multi-Periode | Prioritas timestamp `tgl_daftar ASC` saat periode identik | **PASS** | Waktu pengajuan pendaftaran menjadi rujukan jika seluruh rentang tanggal sama persis. |
| **TEST-ADD-A04** | FIFO Multi-Periode | Tie-breaker deterministik unik `id_daftar ASC` | **PASS** | Primary key pendaftaran menjamin pengurutan stabil dan deterministik. |
| **TEST-ADD-A05** | FIFO Multi-Periode | Penanganan nilai periode NULL (`ISNULL ASC`) | **PASS** | Pendaftar tanpa data periode diletakkan di akhir antrean agar tidak mendahului peserta valid. |
| **TEST-ADD-A06** | Konsistensi FIFO | Integrasi helper bersama `config/fifo_helper.php` | **PASS** | Admin dan peserta mengeksekusi fungsi helper SQL query yang sama. |
| **TEST-ADD-A07** | Validasi Verifikasi | Verifikasi status antrean teratas (`is_fifo_top`) | **PASS** | Admin dilarang menyetujui peserta sebelum peserta pada posisi nomor 1 diproses. |
| **TEST-ADD-A08** | Filter Antrean | Filter hanya menyeleksi status `Menunggu` | **PASS** | Peserta yang telah Diterima/Ditolak tidak dimasukkan dalam evaluasi antrean aktif. |
| **TEST-ADD-A09** | Validasi Dokumen | Blokir persetujuan jika berkas `Tidak Valid` | **PASS** | Admin dicegah menyetujui pendaftar yang surat pengantarnya terdeteksi tidak valid. |
| **TEST-ADD-A10** | Konkurensi | Transaksi atomik dan locking pada seleksi | **PASS** | `mysqli_begin_transaction()` dan `COMMIT`/`ROLLBACK` terverifikasi aktif. |
| **TEST-ADD-B01** | Presisi Waktu | Pendaftaran baru memanfaatkan fungsi `NOW(3)` | **PASS** | Waktu pendaftaran terekam hingga satuan seperseribu detik. |
| **TEST-ADD-B02** | Presisi Waktu | Validasi tipe kolom MySQL `DATETIME(3)` | **PASS** | Kolom database secara presisi menyimpan dan mengembalikan pecahan milidetik. |
| **TEST-ADD-B03** | Integritas Data | Pemeliharaan data timestamp historis | **PASS** | Tidak ada modifikasi atau fabrikasi timestamp buatan pada pendaftaran lama. |
| **TEST-ADD-B04** | Pengurutan | Stabilitas query pengurutan waktu | **PASS** | Hasil query konsisten saat dieksekusi berulang kali. |
| **TEST-ADD-B05** | Zona Waktu | Konvensi tampilan waktu WIB (UTC+7) | **PASS** | Format waktu antarmuka sesuai standar Waktu Indonesia Barat. |
| **TEST-ADD-B06** | Integritas Migrasi | Integritas total catatan pendaftaran | **PASS** | Tidak ada kehilangan rekaman data selama proses migrasi skema tabel pendaftaran. |
| **TEST-ADD-C01** | Deadman System | Filter idle pendaftar sesuai ambang batas hari | **PASS** | Hanya pendaftaran dengan `DATEDIFF >= threshold` yang masuk kriteria kedaluwarsa. |
| **TEST-ADD-C02** | Deadman System | Deteksi pendaftaran terbengkalai (>30 hari) | **PASS** | Sistem berhasil mendeteksi berkas pendaftar yang tidak ditindaklanjuti lebih dari 30 hari. |
| **TEST-ADD-C03** | Deadman System | Integritas mode simulasi `DRY-RUN` | **PASS** | Mode dry-run mengevaluasi rekomendasi tanpa melakukan mutasi status pada basis data. |
| **TEST-ADD-C04** | Deadman System | Pengecualian status non-Menunggu dari worker | **PASS** | Peserta berstatus Diterima/Ditolak/Selesai diabaikan dari siklus pemrosesan otomatis. |
| **TEST-ADD-C05** | Deadman System | Pemeriksaan status atomik sebelum eksekusi | **PASS** | Status peserta divalidasi ulang saat worker mengeksekusi aksi otomatis. |
| **TEST-ADD-C06** | Deadman System | Penanganan berkas tidak valid oleh worker | **PASS** | Peserta dengan dokumen tidak valid diarahkan ke rekomendasi tolak otomatis/audit. |
| **TEST-ADD-C07** | Deadman System | Pengecekan kuota sebelum rekomendasi penerimaan | **PASS** | Rekomendasi terima otomatis ditolak jika kapasitas kuota magang penuh. |
| **TEST-ADD-C08** | Deadman System | Penerapan aturan prioritas FIFO pada Deadman | **PASS** | Urutan evaluasi otomatis mengikuti urutan prioritas FIFO multi-periode. |
| **TEST-ADD-C09** | Keamanan CLI | Sensor rahasia pada output scheduler | **PASS** | Log eksekusi worker bebas dari password, token sesi, atau dump berkas biner. |
| **TEST-ADD-C10** | Konkurensi Worker | Proteksi eksekusi ganda via *process lock* (`flock`) | **PASS** | Worker instance kedua secara otomatis keluar jika instance pertama sedang berjalan. |
| **TEST-ADD-C11** | Audit Trail | Integrasi Deadman System dengan tabel audit | **PASS** | Setiap rekomendasi/eksekusi terekam ke tabel `audit_trail` dengan keterangan jelas. |
| **TEST-ADD-C12** | Konfigurasi Default | Konfigurasi aman default worker (`DRY-RUN = true`) | **PASS** | Skrip worker secara default tidak melakukan eksekusi mutasi langsung tanpa flag `--execute`. |
| **TEST-ADD-D01** | Validasi Dokumen | Surat pengantar resmi tervalidasi `Valid Otomatis` | **PASS** | Dokumen dengan kata kunci resmi instansi lolos klasifikasi heuristik. |
| **TEST-ADD-D02** | Validasi Dokumen | Penolakan berkas modul/materi kuliah (`Tidak Valid`) | **PASS** | Algoritma heuristik berhasil mendeteksi dan menolak berkas modul/materi perkuliahan. |
| **TEST-ADD-D03** | Validasi Dokumen | Penolakan berkas sertifikat kelulusan (`Tidak Valid`) | **PASS** | Berkas sertifikat yang diunggah pada slot surat pengantar ditolak otomatis. |
| **TEST-ADD-D04** | Validasi Dokumen | Penolakan file gambar berekstensi palsu `.pdf` | **PASS** | Validasi byte header `%PDF-` mendeteksi berkas biner gambar yang diubah namanya. |
| **TEST-ADD-D05** | Validasi Dokumen | Deteksi dan penolakan berkas PDF rusak/korup | **PASS** | File PDF tanpa struktur objek dokumen valid gagal dalam tahap verifikasi teknis. |
| **TEST-ADD-D06** | Validasi Dokumen | Penolakan berkas kosong (0 byte) | **PASS** | Pemeriksaan ukuran file server-side mendeteksi dan menolak berkas 0 byte. |
| **TEST-ADD-D07** | Validasi Dokumen | Berkas tanpa teks dialihkan ke `Pemeriksaan Manual` | **PASS** | Dokumen hasil cetak digital tanpa lapisan teks ditandai untuk tinjauan verifikator. |
| **TEST-ADD-D08** | Validasi Dokumen | Berkas hasil scan gambar ditandai `Pemeriksaan Manual`| **PASS** | Dokumen pindaian gambar/XObject diarahkan ke antrean pemeriksaan manual admin. |
| **TEST-ADD-D09** | Validasi Dokumen | Penolakan berkas melebihi batas ukuran 5 MB | **PASS** | Berkas berukuran lebih dari 5.242.880 bytes diblokir oleh validator. |
| **TEST-ADD-D10** | Keamanan Upload | Sanitasi nama berkas acak kriptografis | **PASS** | Nama file disimpan dengan pola acak acak unik (`bin2hex(random_bytes(4))`). |
| **TEST-ADD-D11** | Validasi Dokumen | Pengenalan surat pengantar format ringkas | **PASS** | Kata kunci relevan seperti PKL, magang, dan Balai Tekkomdik dikenali secara akurat. |
| **TEST-ADD-D12** | Validasi Dokumen | Penanganan dokumen teks umum non-kontekstual | **PASS** | Teks bebas tanpa struktur surat pengantar diarahkan ke pemeriksaan manual. |
| **TEST-ADD-D13** | Dokumen Opsional | Proposal magang bersifat opsional | **PASS** | Formulir pendaftaran berhasil disubmit tanpa proposal (`Tidak Diunggah`). |
| **TEST-ADD-D14** | Dokumen Opsional | Penerimaan berkas proposal berformat PDF valid | **PASS** | Berkas proposal PDF valid terekam dengan status `Valid Otomatis`. |
| **TEST-ADD-D15** | Aturan Pendaftaran | Pendaftar status Ditolak dapat mendaftar ulang | **PASS** | Peserta yang sebelumnya ditolak diizinkan mengajukan pendaftaran baru. |
| **TEST-ADD-D16** | Stabilitas Ekstraksi| Penanganan aman ekstraksi teks tanpa fatal error | **PASS** | Pustaka ekstraktor PDF murni PHP berjalan aman dengan penanganan exception terisolasi. |
| **TEST-ADD-D17** | Deadman System | Deadman menolak penerimaan otomatis berkas tidak valid | **PASS** | Otomasi seleksi Deadman mematuhi kelayakan dokumen pendaftar. |

---

## 5. Ringkasan Metrik Pengujian

```
====================================================================================================
                        REKAPITULASI HASIL TESTING KESELURUHAN SISTEM
====================================================================================================
Total Test Suite                  : 5 Test Suites
Total Test Cases Dijalankan       : 113 Test Cases
Total Test Cases PASS             : 113 (100.00%)
Total Test Cases FAIL             : 0 (0.00%)
Total Test Cases BLOCKED / NOT RUN: 0 (0.00%)
Durasi Eksekusi Cypress E2E       : 1 Menit 16 Detik
Durasi Eksekusi PHP Backend Logic : ~4 Detik
Hasil Akhir                       : SELURUH PENGUJIAN MEMENUHI CRITERIA KELULUSAN (100% PASS)
====================================================================================================
```

---

## 6. Bukti Log dan Referensi Artefak

1. **Laporan Visual Cypress HTML (Mochawesome):**
   - Jalur: [`cypress/reports/html/index.html`](file:///D:/coding/_Tekkomdik/sistem_manajemen_magang/cypress/reports/html/index.html)
   - Status: 30 passed, 0 failed, 0 skipped.
2. **Laporan Pengujian CSV Baku (6 Kolom Mandatori):**
   - Jalur: [`reports/hasil_pengujian_blackbox.csv`](file:///D:/coding/_Tekkomdik/sistem_manajemen_magang/reports/hasil_pengujian_blackbox.csv)
   - Jumlah baris: Tepat 30 baris data (baris 1–20 Admin, baris 21–30 Peserta).
3. **Laporan Pengujian Spreadsheet Excel (.xlsx):**
   - Jalur: [`reports/hasil_pengujian_blackbox.xlsx`](file:///D:/coding/_Tekkomdik/sistem_manajemen_magang/reports/hasil_pengujian_blackbox.xlsx)
   - Karakteristik: Worksheet `Hasil Pengujian`, frozen header row, auto-filter aktif, wrap text aktif, styling tabel profesional.
4. **Laporan Audit & Riwayat Perbaikan Sistem:**
   - Jalur: [`reports/laporan_audit_dan_perbaikan.md`](file:///D:/coding/_Tekkomdik/sistem_manajemen_magang/reports/laporan_audit_dan_perbaikan.md)

---

## 7. Analisis Bug, Perbaikan, dan Solusi Pencegahan

Selama siklus pengujian, ditemukan beberapa kelemahan fungsional yang telah berhasil diperbaiki dan diuji regresi:

1. **Kerentanan Pendaftaran Ganda Sisi Server (`peserta_magang/pendaftaran.php`):**
   - *Masalah:* Form disembunyikan di frontend saat mendaftar, namun belum ada pengecekan status aktif saat request HTTP POST langsung dikirimkan ke server.
   - *Solusi:* Ditambahkan validasi prepared statement untuk menolak pengajuan jika pemohon masih berstatus `Menunggu` atau `Diterima`.
2. **Inkonsistensi Pesan Validasi Unggahan Surat Balasan (`admin_magang/detail_peserta.php`):**
   - *Masalah:* Perbedaan string literal pada pesan error berkas non-PDF/JPG/PNG.
   - *Solusi:* Distandardisasi menjadi `Format file tidak valid! Gunakan PDF/JPG/PNG.`.
3. **Penyelarasan Notifikasi Ekstensi Berkas (`config/pdf_validator.php`):**
   - *Masalah:* Pesan penolakan teknis awal terlalu umum.
   - *Solusi:* Diperbarui menjadi `Format Surat Pengantar harus PDF!`.
4. **Isolasi Dataset Uji Cypress:**
   - *Masalah:* Aksi penyelesaian magang pada test case `ADM-12` berpotensi mengubah status peserta yang dibutuhkan oleh pengujian unduhan `PST-09`.
   - *Solusi:* Dibuat skrip seed data deterministik [`scripts/prepare_test_data.php`](file:///D:/coding/_Tekkomdik/sistem_manajemen_magang/scripts/prepare_test_data.php) yang memisahkan akun uji untuk setiap skenario secara terisolasi.

---

## 8. Pengujian Lanjutan yang Direkomendasikan

Pengujian berikut dicatat sebagai rekomendasi tindak lanjut untuk siklus rilis berikutnya:
1. **Load & Stress Testing Konkurensi Tinggi:** Simulasi 500+ pengguna simultan menggunakan k6 atau Apache JMeter saat jendela pembukaan pendaftaran dibuka untuk menguji beban maksimal koneksi MySQL pool.
2. **Penetration Testing OWASP Top 10 Mendalam:** Pengujian otomatis vulnerability scanner (OWASP ZAP) untuk mengevaluasi header HTTP (Content-Security-Policy, X-Frame-Options) secara berkala.
3. **Cross-Browser Automated Grid:** Perluasan pengujian E2E Cypress pada Firefox dan WebKit/Safari secara terdistribusi di pipeline CI/CD.

---

## 9. Risiko dan Rekomendasi Tindak Lanjut

1. **Penjadwalan Deadman System di Windows vs Linux:**
   - *Risiko:* Lingkungan pengembang lokal menggunakan Windows memerlukan Task Scheduler untuk memicu worker `scripts/deadman_worker.php`.
   - *Rekomendasi:* Pada server produksi berbasis Linux, konfigurasikan Cron Job sistem:
     ```bash
     0 2 * * * /usr/bin/php /var/www/html/scripts/deadman_worker.php >> /var/log/deadman_worker.log 2>&1
     ```
2. **Ekstensi PHP Fileinfo:**
   - *Risiko:* Jika ekstensi `php_fileinfo` dinonaktifkan di server hosting, validasi MIME type biner PDF akan gagal.
   - *Rekomendasi:* Pastikan direktif `extension=fileinfo` selalu aktif pada file konfigurasi `php.ini` produksi.
3. **Manajemen Rotasi Berkas Storage:**
   - *Risiko:* Akumulasi berkas PDF pada folder `uploads/` seiring berjalannya tahun akademik.
   - *Rekomendasi:* Terapkan mekanisme pengarsipan berkas magang lama (>2 tahun) ke cold storage atau penyimpanan cloud terisolasi.

---
*Laporan ini diverifikasi dan disahkan oleh Senior QA Automation Engineer & Technical Writer.*
