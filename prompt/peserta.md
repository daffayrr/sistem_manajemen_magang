# TASK: IMPLEMENTASI FITUR PESERTA — INFORMASI KUOTA DAN CEK POSISI MAGANG

## ROLE
Bertindak sebagai Senior Full-Stack Developer dan QA Engineer yang bertanggung jawab mengembangkan fitur peserta pada aplikasi Sistem Manajemen Penerimaan Magang BTKP DIY.

Aplikasi sudah tersedia dan memiliki autentikasi peserta, sidebar, formulir pendaftaran, database peserta, dan sistem kuota. Tugasmu adalah memperbaiki dan menambahkan fitur pada aplikasi yang sudah ada, bukan membangun aplikasi baru.

## 1. AUDIT SEBELUM IMPLEMENTASI

Sebelum mengubah kode:

1. Periksa struktur proyek, framework, versi PHP, routing, autentikasi peserta, sidebar peserta, dan pola UI yang digunakan.
2. Identifikasi tabel serta kolom aktual untuk peserta, pendaftaran, periode magang, kuota, status pendaftaran, dan timestamp pendaftaran.
3. Telusuri bagaimana kuota dihitung saat ini, termasuk status peserta mana yang mengonsumsi kuota.
4. Periksa hubungan akun peserta dengan data pendaftaran.
5. Gunakan kembali komponen, query, dan layout yang sudah tersedia apabila sesuai.
6. Jangan mengarang nama tabel atau kolom.
7. Jangan menghapus data, mengubah struktur database secara destruktif, atau merombak desain aplikasi tanpa kebutuhan yang jelas.
8. Setelah audit, lanjutkan implementasi dan pengujian. Jangan berhenti pada rencana atau contoh kode.

## 2. INFORMASI JUMLAH KUOTA TERSEDIA

Tambahkan informasi kuota magang yang jelas pada halaman peserta, terutama di halaman Formulir Pendaftaran.

Informasi minimum yang ditampilkan:
- Kuota maksimum.
- Jumlah kuota yang sudah terpakai.
- Jumlah kuota yang masih tersedia.
- Status kuota: Tersedia atau Penuh.

Gunakan data aktual dari database, bukan angka statis atau dummy.

Rumus dasar:
Kuota tersedia = Kuota maksimum - Jumlah kuota terpakai.

Namun, cara menghitung jumlah kuota terpakai harus mengikuti aturan bisnis dan implementasi kuota yang sudah digunakan aplikasi. Jangan mengubah definisi peserta yang mengonsumsi kuota tanpa alasan dan dokumentasi.

Jika kuota dihitung berdasarkan periode magang, informasi yang ditampilkan harus sesuai dengan periode yang dipilih peserta. Jika aplikasi menggunakan kuota global, pertahankan aturan tersebut sampai ada keputusan bisnis yang berbeda.

### Ketentuan tampilan
- Tampilkan informasi kuota sebelum peserta mengisi atau mengirim formulir.
- Gunakan tampilan ringkas, profesional, konsisten dengan desain aplikasi saat ini, dan responsif untuk desktop maupun perangkat seluler.
- Perbarui informasi kuota setelah pendaftaran berhasil.
- Tampilkan pesan yang jelas ketika kuota penuh.
- Jangan menampilkan angka kuota yang sudah kedaluwarsa akibat data database yang berubah.
- Jika data kuota gagal dimuat, tampilkan pesan bahwa informasi belum dapat diambil dan jangan mengasumsikan kuota tersedia.

## 3. BLOKIR PENDAFTARAN KETIKA KUOTA PENUH

Jika kuota penuh, peserta tidak boleh melakukan pendaftaran baru melalui Formulir Pendaftaran.

Implementasikan kontrol pada frontend dan backend.

### Frontend
- Jika kuota tersedia, formulir dapat digunakan sesuai aturan yang berlaku.
- Jika kuota penuh, tampilkan status Penuh dan nonaktifkan kontrol pengiriman formulir.
- Berikan penjelasan singkat bahwa pendaftaran belum dapat dilakukan karena kuota telah terpenuhi.
- Pastikan tombol tidak sekadar terlihat aktif tanpa fungsi yang benar.
- Jangan menghilangkan informasi formulir yang sudah diisi jika kuota berubah sebelum pengiriman; berikan pesan yang sesuai dan hindari kehilangan input secara tidak perlu.

### Backend
- Periksa kembali kuota saat request pendaftaran diterima.
- Jangan mengandalkan validasi JavaScript atau tombol disabled sebagai pengaman.
- Jika kuota penuh, tolak request dengan respons atau pesan yang sesuai.
- Pastikan request langsung melalui URL atau HTTP client tidak dapat melewati pemeriksaan.
- Gunakan transaksi dan locking yang sesuai dengan database untuk mencegah dua pendaftaran bersamaan melampaui kapasitas.
- Jika pendaftaran berhasil, simpan data dan status sesuai alur yang sudah ada.
- Jika pendaftaran gagal, jangan meninggalkan data parsial atau mengubah status peserta secara tidak konsisten.

Jangan mengubah aturan penerimaan peserta menjadi otomatis hanya karena kuota tersedia. Kuota tersedia tidak sama dengan peserta otomatis diterima.

## 4. MENU SIDEBAR PESERTA: CEK POSISI DAN KUOTA

Tambahkan menu sidebar peserta dengan label persis:

"Cek Posisi dan Kuota"

Ketentuan:
- Menu hanya tampil untuk akun peserta yang sudah login.
- Menu menggunakan route yang mengikuti pola routing aplikasi.
- Pastikan peserta dapat membuka halaman melalui sidebar dan URL yang sesuai.
- Pastikan admin tidak memperoleh akses ke halaman khusus peserta kecuali memang diizinkan oleh aturan aplikasi.
- Pertahankan desain sidebar yang sudah ada.
- Pastikan navigasi tetap responsif dan tidak mengganggu menu peserta lainnya.

## 5. HALAMAN CEK POSISI DAN KUOTA

Buat halaman yang menampilkan data pendaftaran peserta dalam bentuk tabel.

Kolom wajib:
1. ID Peserta.
2. Periode Magang.
3. Timestamp Mendaftar.

### Format data
- ID Peserta: gunakan ID aktual dari database.
- Periode Magang: tampilkan tanggal mulai sampai tanggal selesai dengan format yang konsisten dan mudah dibaca.
- Timestamp Mendaftar: tampilkan waktu pendaftaran dari database dengan tanggal, jam, dan milidetik jika tersedia.

Contoh format periode:
01 November 2026 – 30 November 2026

Contoh format timestamp:
21 Oktober 2026 14:32:15.123

Contoh di atas hanya format tampilan, bukan data yang boleh dimasukkan sebagai dummy.

### Sumber data
- Ambil data dari database aktual.
- Jangan membuat data peserta contoh atau mengisi tabel dengan data hardcoded.
- Gunakan relasi database yang benar berdasarkan skema proyek.
- Pastikan ID peserta ditampilkan sesuai identitas yang digunakan aplikasi, bukan menggantinya dengan nomor urut tampilan.
- Gunakan prepared statements atau ORM sesuai framework yang digunakan.
- Tangani kondisi ketika belum ada pendaftaran dengan empty state yang informatif.

## 6. DEFINISI POSISI FIFO

Halaman harus menampilkan posisi antrean peserta berdasarkan aturan FIFO yang berlaku pada aplikasi.

PENTING:
Aturan FIFO pada revisi ini menggunakan periode magang sebagai dasar prioritas, bukan otomatis berdasarkan timestamp submit pendaftaran.

Sebelum mengimplementasikan posisi antrean:

1. Periksa aturan FIFO yang telah diimplementasikan pada modul admin.
2. Gunakan aturan prioritas periode magang yang sama dengan modul admin.
3. Jangan membuat algoritma FIFO kedua yang berbeda untuk peserta.
4. Jika aturan periode magang belum diputuskan secara lengkap, dokumentasikan kebutuhan klarifikasi dan jangan mengarang aturan prioritas.
5. Jika dibutuhkan nomor posisi, hitung berdasarkan urutan antrean aktual dari database dan aturan prioritas yang sama dengan backend admin.
6. Jika timestamp identik, gunakan tie-breaker deterministik yang konsisten dengan modul admin.
7. Jangan menyimpulkan bahwa peserta diterima hanya karena berada di posisi pertama.
8. Jangan menjanjikan bahwa posisi antrean merupakan jaminan penerimaan.

Tambahkan informasi posisi antrean hanya jika posisi tersebut dapat dihitung secara valid. Jika sistem belum dapat menentukan posisi dengan benar, tampilkan data pendaftaran dan informasi status yang memang tersedia tanpa membuat posisi palsu.

## 7. PRIVASI DAN OTORISASI DATA PESERTA

Halaman ini harus menampilkan tabel data pendaftar sesuai kebutuhan transparansi antrean, tetapi jangan membocorkan data pribadi yang tidak diperlukan.

- Jangan menampilkan nama lengkap, alamat, nomor telepon, email, dokumen, atau data pribadi lain jika tidak diminta dalam spesifikasi.
- Jangan menampilkan password, token, atau data autentikasi.
- Terapkan autentikasi dan otorisasi di backend.
- Gunakan hanya data yang diperlukan untuk tabel.
- Jika halaman menampilkan data peserta lain, batasi informasi pada ID Peserta, Periode Magang, dan Timestamp Mendaftar.
- Jangan mengirim seluruh objek peserta ke frontend jika hanya tiga kolom tersebut yang diperlukan.
- Jangan mengizinkan manipulasi parameter URL untuk melihat data internal yang tidak diizinkan.

Jika terdapat perbedaan antara kebijakan privasi aplikasi dan kebutuhan tabel ini, dokumentasikan perbedaannya dan gunakan prinsip minimisasi data.

## 8. STATUS, EMPTY STATE, DAN ERROR HANDLING

Tangani kondisi berikut dengan baik:
- Peserta belum pernah mendaftar.
- Data pendaftaran sedang dimuat.
- Database gagal diakses.
- Kuota belum dikonfigurasi.
- Kuota penuh.
- Periode magang tidak valid atau belum tersedia.
- Timestamp pendaftaran lama tidak memiliki presisi milidetik.
- Request pendaftaran ditolak karena kuota baru saja penuh.
- Peserta belum login atau session telah berakhir.

Gunakan pesan yang mudah dipahami dan jangan menampilkan stack trace, query SQL, atau kredensial kepada pengguna.

## 9. PENGUJIAN WAJIB

Jalankan pengujian yang sesuai dengan framework dan struktur proyek.

Minimal verifikasi skenario berikut:

1. Peserta dapat membuka menu Cek Posisi dan Kuota.
2. Halaman menampilkan data aktual dari database.
3. ID Peserta ditampilkan dengan benar.
4. Periode magang menampilkan tanggal mulai dan selesai dengan benar.
5. Timestamp mendaftar berasal dari data aktual.
6. Timestamp dengan presisi milidetik ditampilkan dengan benar jika tersedia.
7. Peserta tanpa pendaftaran mendapatkan empty state.
8. Kuota tersedia ditampilkan dengan benar.
9. Kuota penuh menyebabkan formulir tidak dapat dikirim.
10. Request langsung ke backend tetap ditolak saat kuota penuh.
11. Dua request pendaftaran bersamaan tidak melampaui kuota.
12. Perhitungan posisi sesuai aturan FIFO modul admin.
13. Pengurutan posisi tidak bergantung pada urutan baris yang kebetulan dikembalikan database.
14. Peserta yang belum berada di posisi pertama tidak memperoleh status diterima hanya karena membuka halaman.
15. Pengguna tanpa autentikasi tidak dapat mengakses endpoint terlindungi.
16. Data pribadi di luar tiga kolom yang diminta tidak dibocorkan.
17. Menu sidebar tidak merusak navigasi peserta pada desktop maupun seluler.
18. Fitur peserta lain tetap berjalan setelah perubahan.

Jangan menyatakan tes berhasil jika tidak benar-benar dijalankan.

## 10. KRITERIA SELESAI

Implementasi dianggap selesai apabila:
- Informasi kuota bersumber dari database.
- Pendaftaran ditolak di frontend dan backend saat kuota penuh.
- Menu Cek Posisi dan Kuota tersedia di sidebar peserta.
- Tabel menampilkan ID Peserta, Periode Magang, dan Timestamp Mendaftar.
- Jika posisi FIFO ditampilkan, algoritmanya konsisten dengan modul admin.
- Hak akses dan privasi tetap terjaga.
- Pengujian dijalankan dan hasilnya dilaporkan.
- Perubahan database tidak merusak data lama.

## 11. LAPORAN AKHIR

Setelah selesai, berikan:
1. Ringkasan fitur yang ditambahkan.
2. File yang dibuat atau diubah.
3. Perubahan database atau migrasi jika ada.
4. Cara menghitung kuota.
5. Cara menghitung posisi antrean.
6. Hasil pengujian yang benar-benar dijalankan.
7. Bug atau risiko yang belum terselesaikan.
8. Keputusan bisnis yang masih membutuhkan konfirmasi.

Mulai dengan memeriksa source code aktual, lalu implementasikan perubahan secara bertahap tanpa merusak fitur yang sudah berjalan.