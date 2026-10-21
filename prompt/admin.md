# ROLE: SENIOR FULL-STACK ENGINEER & SOFTWARE QA

Kamu bertugas memperbaiki aplikasi Sistem Manajemen Penerimaan Magang dengan Metode First In First Out (FIFO), studi kasus Balai Teknologi Komunikasi Pendidikan DIY (BTKP DIY).

Aplikasi ini sudah memiliki fitur administrasi. Tugasmu adalah melakukan audit source code, memperbaiki bug, dan mengimplementasikan revisi berdasarkan spesifikasi di bawah. Ini adalah proyek perbaikan aplikasi yang sudah berjalan, BUKAN proyek untuk membangun ulang dari nol.

## 1. ATURAN WAJIB SEBELUM MEMULAI

1. Periksa struktur direktori, framework, versi PHP, konfigurasi database, routing, autentikasi, middleware jika ada, skema tabel, dan pola coding yang digunakan.
2. Identifikasi modul admin, modul pendaftaran peserta, pengelolaan kuota, pengelolaan status magang, arsip, upload dokumen, dan pengelolaan akun.
3. Baca implementasi aktual sebelum mengubah kode. Jangan berasumsi bahwa nama file, nama tabel, atau kolom tertentu sudah pasti ada.
4. Pertahankan framework, arsitektur, nama tabel, dan pola coding yang sudah digunakan kecuali perubahan memang diperlukan dan dapat dijustifikasi.
5. Jangan menghapus fitur yang sudah berjalan, mengubah desain keseluruhan secara tidak perlu, atau melakukan refactor besar tanpa alasan teknis yang jelas.
6. Buat backup atau gunakan Git checkpoint sebelum melakukan perubahan. Jangan menimpa backup sebelumnya.
7. Jangan menghapus data produksi, melakukan reset database secara destruktif, atau menjalankan migrasi yang menghilangkan data.
8. Jangan menampilkan password, secret, session token, atau kredensial database dalam log maupun laporan.
9. Jika menemukan ketidaksesuaian antara implementasi dan kebutuhan, dokumentasikan kondisi aktualnya sebelum memilih solusi.
10. Setelah audit, buat rencana perubahan singkat dan langsung lanjutkan implementasi. Jangan berhenti pada analisis atau hanya memberikan contoh kode.

## 2. PERBAIKAN LOGIN ADMIN

Temukan penyebab bug yang membuat admin tidak bisa login.

Periksa:
- Validasi username dan password.
- Mekanisme password hashing dan verifikasi.
- Query database dan struktur tabel pengguna.
- Session initialization, session regeneration, dan status login.
- Redirect setelah login.
- Pemeriksaan role dan middleware.
- Ketidaksesuaian nama kolom, perubahan skema, atau konfigurasi.
- Error PHP, exception, dan kegagalan koneksi database.

Persyaratan:
- Perbaiki akar masalah, bukan hanya menyembunyikan error.
- Jangan menonaktifkan autentikasi atau pemeriksaan role demi membuat login berhasil.
- Jangan mengizinkan login menggunakan password kosong atau melewati verifikasi password.
- Gunakan mekanisme hashing password yang sesuai dengan stack aplikasi, misalnya password_hash() dan password_verify() pada PHP Native.
- Regenerasikan session ID setelah login berhasil.
- Gunakan pesan error yang aman dan tidak membocorkan informasi sensitif.
- Pastikan admin yang valid bisa login, logout, dan tidak bisa mengakses halaman admin setelah logout.
- Pastikan peserta tidak memperoleh akses admin hanya dengan memodifikasi URL.
- Tambahkan atau perbaiki pengujian login yang relevan.

## 3. SISTEM SUPERADMIN

Implementasikan role superadmin yang terintegrasi dengan sistem autentikasi dan pengelolaan admin yang sudah ada.

Akun bootstrap yang diminta:
- Username: admin_btkp
- Password awal: abc_123

Ketentuan keamanan:
- Kredensial tersebut hanya boleh digunakan sebagai password awal pada lingkungan yang memang diotorisasi.
- Simpan password menggunakan password hashing, bukan plaintext.
- Jangan menuliskan password ke source code produksi, log, atau dokumentasi publik.
- Gunakan seeder atau command bootstrap yang aman, dapat dijalankan ulang tanpa membuat akun duplikat, dan tidak mereset password akun yang sudah ada secara diam-diam.
- Jika aplikasi sudah berada di lingkungan produksi, jangan membuat akun administrator dengan password lemah secara otomatis tanpa mekanisme provisioning yang terotorisasi. Sediakan proses bootstrap satu kali dan wajibkan penggantian password awal.
- Jangan mengungkapkan kredensial dalam respons error atau log.

Hak akses:
1. Superadmin mempunyai hak akses operasional yang sama dengan admin biasa, termasuk seluruh fitur administrasi yang tersedia untuk admin.
2. Superadmin dapat menghapus akun admin biasa.
3. Admin biasa tidak dapat menghapus akun superadmin.
4. Superadmin tidak dapat menghapus akun superadmin lain jika kebijakan perlindungan akun inti mensyaratkan akun superadmin tetap terlindungi.
5. Superadmin tidak dapat menghapus dirinya sendiri.
6. Admin biasa tidak dapat mengubah role dirinya sendiri menjadi superadmin.
7. Admin biasa tidak dapat meningkatkan hak akses akun lain menjadi superadmin.
8. Perubahan role, penghapusan akun, dan tindakan sensitif harus diperiksa di backend, bukan hanya dengan menyembunyikan tombol pada UI.
9. Pastikan sistem tidak secara tidak sengaja menghapus atau menurunkan role akun superadmin utama.
10. Pastikan penghapusan akun tidak menghapus riwayat pendaftaran, arsip, atau audit trail yang terkait.

Antarmuka:
- Tampilkan label role yang jelas pada halaman pengelolaan admin.
- Tampilkan tombol hapus sesuai hak akses.
- Berikan konfirmasi sebelum menghapus akun admin.
- Berikan notifikasi sukses atau gagal yang informatif tanpa membocorkan informasi sensitif.

Jangan membuat duplikasi sistem role jika aplikasi sudah memiliki implementasi role yang dapat diperluas.

## 4. MENU SIDEBAR ADMIN

Ubah label menu "Kuota dan Posisi" menjadi "Kuota Magang".

Ketentuan:
- Pertahankan route dan fungsi yang sudah ada jika memungkinkan.
- Perbarui label di semua tempat yang relevan, termasuk judul halaman, breadcrumb, dan navigasi terkait.
- Jangan merusak tautan menu, hak akses, atau tampilan responsif.
- Pastikan halaman Kuota Magang tetap dapat digunakan untuk mengelola kapasitas sesuai struktur aplikasi saat ini.

## 5. FIFO DAN KETERTIBAN PEMROSESAN PESERTA

Implementasikan aturan FIFO sesuai kebutuhan bisnis yang ditentukan, bukan hanya pengurutan visual tabel.

Ketentuan:
- Admin tidak boleh memproses peserta di urutan berikutnya jika masih ada peserta terdahulu yang wajib diproses.
- Pastikan aturan tersebut berlaku di backend pada setiap aksi penerimaan atau penolakan.
- Peserta yang belum diverifikasi dokumennya tidak boleh otomatis dianggap layak diterima hanya karena berada di posisi pertama.
- Urutan harus konsisten pada daftar, detail peserta, dan endpoint aksi administrasi.
- Jangan mengandalkan JavaScript atau urutan tampilan sebagai satu-satunya pengaman.
- Jika terdapat dua atau lebih peserta yang memiliki nilai prioritas sama, gunakan aturan tie-breaker yang deterministik.
- Cegah dua admin memproses urutan antrean yang sama secara bersamaan sehingga urutan FIFO dapat dilewati.
- Gunakan transaksi database dan locking yang sesuai dengan database serta pola akses aplikasi.
- Berikan pesan yang jelas ketika admin mencoba memproses peserta yang belum mendapat giliran.

PENTING:
Kebutuhan FIFO berdasarkan periode magang akan diimplementasikan sesuai keputusan bisnis yang eksplisit. Jangan mengasumsikan timestamp pendaftaran tetap menjadi prioritas utama. Jika definisi prioritas periode belum tersedia, dokumentasikan konflik tersebut dan minta keputusan sebelum menetapkan urutan final.

## 6. PRESISI WAKTU DAN URUTAN DATA

Audit semua kolom waktu yang digunakan untuk menentukan urutan antrean dan pencatatan aktivitas.

Persyaratan:
- Pertahankan timestamp pendaftaran untuk kebutuhan audit.
- Gunakan presisi milidetik jika didukung oleh database dan tipe kolom yang sesuai, misalnya DATETIME(3) pada MySQL.
- Periksa presisi aktual database, format timestamp dari aplikasi, zona waktu, dan konsistensi penyimpanan.
- Jangan mengklaim bahwa semua permintaan memiliki timestamp unik hanya karena presisi milidetik digunakan.
- Tambahkan tie-breaker yang deterministik untuk timestamp identik.
- Perubahan skema harus menggunakan migrasi non-destruktif dan mempertahankan data lama.
- Jika migrasi membutuhkan downtime atau berpotensi mengubah data lama, dokumentasikan dampaknya sebelum menjalankan migrasi.

Jangan mengubah seluruh kolom waktu aplikasi tanpa kebutuhan. Fokus pada kolom yang relevan dengan FIFO dan audit trail.

## 7. KUOTA DAN VALIDASI STATUS MAGANG

Pastikan sistem kuota tidak dapat dilewati melalui pengiriman formulir langsung, request manual, atau dua pendaftaran bersamaan.

Ketentuan:
- Validasi kuota dilakukan di backend ketika pendaftaran diproses.
- Jangan hanya mengandalkan tombol disabled atau validasi JavaScript.
- Gunakan transaksi dan mekanisme konsistensi database yang sesuai untuk mencegah overbooking.
- Jangan menghitung peserta yang sama lebih dari satu kali.
- Tentukan status mana yang mengonsumsi kuota berdasarkan aturan bisnis yang sudah ada.
- Jangan mengubah aturan bahwa peserta selesai/diarsipkan melepaskan kuota tanpa terlebih dahulu memeriksa implementasi dan ketentuan aplikasi.
- Jika kuota dihitung per periode magang, hitung berdasarkan periode yang relevan, bukan sekadar jumlah seluruh peserta.
- Jika definisi kuota masih global, pertahankan perilaku yang ada sampai aturan bisnis dikonfirmasi.
- Berikan pesan yang jelas saat kuota penuh.
- Pastikan perubahan status peserta memperbarui perhitungan kuota secara konsisten.

## 8. UPLOAD NILAI DAN SERTIFIKAT SEBELUM SELESAI

Sebelum admin dapat menekan tombol "Selesai", sistem harus memastikan dokumen nilai dan sertifikat telah diunggah.

Persyaratan:
1. Validasi dilakukan di frontend dan backend.
2. Jika salah satu dokumen belum ada atau tidak valid, proses penyelesaian harus ditolak.
3. Jangan hanya memeriksa bahwa kolom database berisi string path; pastikan file memang tersedia dan dapat diakses oleh aplikasi.
4. Validasi tipe file, ukuran file, dan penamaan file dengan kebijakan yang sesuai.
5. Jangan menerima nama file atau path dari pengguna secara langsung tanpa validasi.
6. Cegah path traversal dan akses tidak sah ke file.
7. Jangan menghapus file lama sebelum penggantian file berhasil.
8. Tampilkan status dokumen dengan jelas, termasuk dokumen yang belum diunggah.
9. Setelah persyaratan terpenuhi, admin dapat menyelesaikan magang.
10. Catat waktu penyelesaian secara konsisten saat status berhasil berubah.

Jika file tidak ditemukan atau proses upload gagal, jangan ubah status peserta menjadi selesai.

## 9. ARSIP BERDASARKAN WAKTU SELESAI

Perbarui pencatatan dan pengurutan arsip.

Persyaratan:
- Gunakan kolom waktu penyelesaian yang sesuai, misalnya completed_at, jika belum tersedia dan sesuai dengan struktur data.
- Catat waktu saat transisi status menjadi selesai berhasil disimpan.
- Urutkan arsip berdasarkan completed_at, bukan ID peserta.
- Tentukan apakah arsip terbaru ditampilkan terlebih dahulu atau terlama terlebih dahulu, lalu gunakan arah pengurutan yang konsisten.
- Jika dua peserta selesai pada waktu yang sama, gunakan ID sebagai tie-breaker sekunder.
- Jangan mengganti timestamp pendaftaran dengan timestamp penyelesaian.
- Jangan mengubah waktu penyelesaian saat halaman arsip dibuka atau saat daftar di-refresh.
- Pastikan data arsip lama ditangani dengan aman jika kolom completed_at baru ditambahkan. Jangan membuat timestamp historis palsu seolah-olah waktu penyelesaian yang sebenarnya diketahui.

## 10. AUDIT TRAIL

Untuk tindakan penting, simpan informasi yang tersedia dan relevan:
- Aktor/admin yang melakukan tindakan.
- Peserta atau akun yang terdampak.
- Jenis tindakan.
- Waktu tindakan.
- Status sebelum dan sesudah jika relevan.
- Alasan atau sumber tindakan, misalnya tindakan manual atau otomatis.

Prioritaskan audit trail untuk:
- Penerimaan dan penolakan peserta.
- Penyelesaian magang.
- Penghapusan akun admin.
- Perubahan role.
- Perubahan kuota.
- Tindakan otomatis yang memengaruhi status peserta.

Jangan mencatat password, token autentikasi, atau isi dokumen sensitif dalam audit trail.

Jika aplikasi belum memiliki sistem audit trail, implementasikan secara minimal sesuai pola proyek dan hindari perubahan skema yang merusak data.

## 11. DEADMAN SYSTEM

Lakukan audit kesiapan sistem untuk proses otomatis atas pendaftaran yang belum diproses admin.

Untuk tahap ini:
- Identifikasi status yang memenuhi syarat untuk pemrosesan otomatis.
- Identifikasi konfigurasi tenggat yang dapat diatur.
- Pastikan pendaftaran dengan dokumen wajib yang belum valid tidak otomatis diterima.
- Pastikan keputusan otomatis tidak melanggar FIFO atau kuota.
- Pastikan pemrosesan otomatis tidak berjalan dua kali untuk peserta yang sama.
- Catat alasan dan waktu setiap tindakan otomatis.
- Jika aplikasi menggunakan cron atau scheduler, dokumentasikan mekanisme yang sesuai dengan lingkungan proyek.
- Jangan membuat aturan otomatis menerima atau menolak berdasarkan jumlah hari yang dikarang sendiri.

Jika kebijakan tenggat dan hasil otomatis belum ditentukan, siapkan fondasi teknis dan dokumentasi keputusan yang dibutuhkan, tetapi jangan mengaktifkan keputusan otomatis yang belum disetujui.

## 12. KEAMANAN UMUM

Audit seluruh perubahan terhadap:
- SQL injection dan penggunaan prepared statements.
- CSRF pada aksi administratif yang relevan.
- Validasi server-side.
- Autentikasi dan otorisasi pada setiap endpoint.
- Upload file yang aman.
- Session handling.
- Kebocoran error dan informasi sensitif.
- Proteksi terhadap perubahan role tanpa izin.
- Konsistensi transaksi database.

Gunakan mekanisme yang sesuai dengan framework dan versi yang benar-benar digunakan. Jangan memasang dependency baru jika fitur yang dibutuhkan dapat diimplementasikan dengan aman menggunakan komponen yang sudah tersedia.

## 13. PENGUJIAN WAJIB

Jalankan test suite yang sudah ada. Tambahkan pengujian sesuai framework proyek.

Skenario minimum:
1. Admin valid berhasil login.
2. Password salah ditolak.
3. Pengguna yang logout tidak dapat membuka halaman admin.
4. Peserta tidak dapat membuka endpoint admin.
5. Admin biasa tidak dapat menghapus superadmin.
6. Superadmin tidak dapat menghapus dirinya sendiri.
7. Superadmin dapat menghapus admin biasa.
8. Admin biasa tidak dapat menaikkan role menjadi superadmin.
9. Label menu berubah menjadi "Kuota Magang".
10. Admin tidak dapat melewati urutan FIFO.
11. Dua permintaan bersamaan tidak menghasilkan pemrosesan antrean yang inkonsisten.
12. Kuota penuh menolak pendaftaran melalui backend.
13. Pendaftaran bersamaan tidak menyebabkan kuota terlampaui.
14. Tombol Selesai ditolak jika dokumen nilai kosong.
15. Tombol Selesai ditolak jika sertifikat kosong.
16. Tombol Selesai ditolak jika file wajib tidak ditemukan.
17. Peserta hanya dapat selesai setelah semua syarat terpenuhi.
18. Arsip diurutkan berdasarkan waktu penyelesaian.
19. Data lama tidak hilang akibat migrasi.
20. Aksi administratif yang penting tercatat sesuai implementasi audit trail.

Jangan mengklaim tes lulus jika tes tidak benar-benar dijalankan. Jangan menghapus atau menonaktifkan tes hanya untuk mendapatkan hasil hijau.

## 14. BATASAN EKSEKUSI

- Jangan menghapus database atau menjalankan DROP TABLE tanpa persetujuan eksplisit.
- Jangan mengubah kredensial admin produksi secara diam-diam.
- Jangan menjalankan deployment produksi tanpa izin.
- Jangan menampilkan file .env atau secret dalam laporan.
- Jika konfigurasi environment, migrasi, atau keputusan bisnis belum jelas, berhenti hanya pada langkah yang berisiko dan jelaskan apa yang dibutuhkan.
- Jangan meninggalkan TODO kritis tanpa dokumentasi.

## 15. LAPORAN AKHIR

Setelah implementasi, berikan laporan berisi:
1. Ringkasan struktur aplikasi yang ditemukan.
2. Penyebab bug login admin.
3. File yang dibuat atau diubah dan alasannya.
4. Perubahan database/migrasi.
5. Implementasi dan batasan superadmin.
6. Perbaikan FIFO, kuota, penyelesaian magang, dan arsip.
7. Hasil pengujian yang benar-benar dijalankan.
8. Pengujian yang belum dapat dilakukan beserta alasannya.
9. Risiko atau keputusan bisnis yang masih membutuhkan konfirmasi.
10. Cara menjalankan migrasi dan bootstrap superadmin dengan aman.

Mulai dari audit aktual, buat checkpoint, kemudian implementasikan perbaikan secara bertahap. Prioritaskan menjaga integritas data dan kompatibilitas fitur yang sudah berjalan.