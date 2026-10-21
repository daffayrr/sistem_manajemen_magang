# IMPLEMENTATION TASK — DEADMAN SYSTEM DI KUOTA.PHP DAN PENYESUAIAN CYPRESS SESUAI BAB IV SKRIPSI

## ROLE

Bertindak sebagai Senior PHP Native Developer, MySQL Database Engineer, dan QA Engineer.

Proyek: Sistem Informasi Manajemen Penerimaan Magang BTKP DIY menggunakan PHP Native, MySQL, Bootstrap CDN, dan Cypress untuk pengujian Blackbox.

Lakukan perubahan langsung pada source code yang sudah ada. Pertahankan arsitektur, tampilan, alur kerja, istilah, dan ruang lingkup penelitian sesuai draft skripsi serta catatan koreksi dosen.

Jangan membangun ulang aplikasi. Jangan menambahkan fitur di luar kebutuhan revisi. Jangan mengubah metodologi penelitian atau memperluas ruang lingkup sistem tanpa instruksi.

---

# BAGIAN A — DEADMAN SYSTEM HARUS TERINTEGRASI DI KUOTA.PHP

## A1. Lokasi pengaturan

Seluruh konfigurasi deadman system harus menjadi bagian dari halaman pengaturan kuota magang yang sudah ada, yaitu `kuota.php`.

Sebelum mengubah kode:

1. Periksa implementasi aktual `kuota.php`.
2. Periksa bagaimana kapasitas maksimum, sisa kuota, jumlah peserta aktif, dan status penerimaan disimpan.
3. Periksa struktur tabel pendaftaran serta status peserta.
4. Periksa cara admin menyimpan pengaturan saat ini.
5. Gunakan komponen UI dan mekanisme penyimpanan yang sudah digunakan halaman tersebut.

Jangan membuat halaman konfigurasi deadman terpisah.

Jika struktur proyek menggunakan file pemrosesan terpisah untuk menyimpan pengaturan, pertahankan pola tersebut dan hubungkan ke `kuota.php`.

## A2. Antarmuka pengaturan

Tambahkan bagian pengaturan penerimaan otomatis pada halaman `kuota.php`, tanpa merombak desain halaman yang sudah ada.

Bagian pengaturan harus mencakup:

- Toggle untuk mengaktifkan atau menonaktifkan deadman system.
- Input jumlah hari tenggat pemrosesan.
- Pilihan tindakan setelah tenggat, sesuai kebijakan yang disetujui.
- Tombol simpan pengaturan.
- Informasi konfigurasi aktif.
- Notifikasi ketika pengaturan berhasil disimpan atau gagal disimpan.

Gunakan bahasa antarmuka Indonesia yang konsisten dengan aplikasi.

Nilai pengaturan harus tersimpan secara persisten di database. Jangan hanya menyimpannya dalam session atau variabel frontend.

Jika konfigurasi belum tersedia, deadman system harus berada dalam kondisi nonaktif sampai admin mengaturnya.

Jangan menentukan jumlah hari atau keputusan otomatis secara permanen di dalam source code.

## A3. Kebijakan penerimaan otomatis

Fitur ini harus tetap berada dalam ruang lingkup sistem penerimaan magang yang dibahas pada skripsi.

Jangan mengembangkan workflow persetujuan baru, sistem notifikasi eksternal, modul AI, atau sistem eskalasi kompleks yang tidak diminta.

Ketentuan:

1. Admin dapat mengatur tenggat dari `kuota.php`.
2. Sistem mengidentifikasi pendaftaran yang masih menunggu keputusan dan telah melewati tenggat.
3. Sistem memeriksa status aktual sebelum melakukan tindakan.
4. Pendaftaran yang sudah diterima atau ditolak tidak boleh diproses kembali.
5. Pendaftaran yang belum memenuhi persyaratan surat pengantar tidak boleh otomatis diterima.
6. Penerimaan otomatis harus mematuhi kuota dan aturan FIFO yang berlaku.
7. Setiap perubahan status harus dicatat.
8. Eksekusi berulang tidak boleh menghasilkan perubahan status ganda.
9. Jika tindakan otomatis belum memiliki kebijakan yang disetujui, jangan mengaktifkannya secara default.
10. Jangan membuat peserta otomatis diterima hanya karena admin belum memprosesnya, kecuali aturan tersebut memang dipilih dan disetujui.

Jika konfigurasi memungkinkan penerimaan maupun penolakan otomatis, implementasikan masing-masing hanya berdasarkan kebijakan yang jelas. Jangan membuat asumsi tentang keputusan bisnis BTKP DIY.

## A4. Pemicu otomatis

Periksa lingkungan proyek dan mekanisme yang sudah tersedia.

Jika belum terdapat scheduler:

- Sediakan mekanisme pemrosesan terjadwal yang kompatibel dengan PHP Native.
- Gunakan cron atau mekanisme penjadwalan server yang sesuai.
- Jangan mengandalkan pengunjung membuka `kuota.php` sebagai satu-satunya pemicu otomatis.
- Jangan menjalankan tindakan otomatis berat pada setiap request halaman.
- Dokumentasikan konfigurasi scheduler dan cara menjalankannya.

Proses harus:

- Menghitung tenggat berdasarkan timestamp yang benar.
- Memeriksa status terkini.
- Menghormati aturan FIFO.
- Memeriksa kuota sebelum penerimaan otomatis.
- Memeriksa dokumen wajib.
- Menggunakan transaksi dan kontrol konkurensi yang sesuai.
- Mencatat hasil tindakan.
- Aman dijalankan lebih dari satu kali.

Jika kebijakan belum disetujui, gunakan mode simulasi atau dry-run dan jangan mengubah status peserta secara nyata.

## A5. Integrasi kuota dan FIFO

Jangan membuat algoritma FIFO baru yang terpisah.

Gunakan aturan FIFO yang sama dengan modul admin dan peserta. Untuk revisi terbaru, prioritas didasarkan pada periode magang sesuai ketentuan yang telah ditetapkan, bukan sekadar waktu klik Submit.

Jika definisi pengurutan periode belum disepakati, jangan mengarang aturan prioritas.

Penerimaan otomatis tidak boleh:

- Melompati peserta yang masih lebih dahulu menurut aturan FIFO.
- Melampaui kuota maksimum.
- Menerima peserta yang sudah diproses.
- Mengubah riwayat waktu pendaftaran.
- Menghapus dokumen atau riwayat peserta.

Jika penerimaan otomatis gagal karena kuota penuh atau dokumen tidak valid, jangan mengubah status peserta menjadi diterima.

## A6. Penyimpanan pengaturan dan keamanan

- Gunakan validasi server-side.
- Pastikan hanya admin dengan hak akses yang sesuai yang dapat mengubah pengaturan.
- Gunakan prepared statements atau pola akses database yang sudah digunakan aplikasi.
- Jangan mempercayai nilai input dari frontend.
- Validasi jumlah hari sebagai nilai numerik yang masuk akal.
- Pastikan nilai toggle benar-benar disimpan dan dibaca kembali dari database.
- Jangan menampilkan error database atau secret kepada pengguna.
- Gunakan migrasi non-destruktif jika membutuhkan kolom atau tabel baru.
- Pertahankan data konfigurasi dan data pendaftaran yang sudah ada.

---

# BAGIAN B — PRESISI TIMESTAMP DAN VALIDASI SURAT PENGANTAR

Implementasikan kebutuhan ini hanya sejauh mendukung alur FIFO dan penerimaan magang yang sudah ada.

## B1. Timestamp

Audit kolom timestamp yang relevan dengan pendaftaran, perubahan status, dan pemrosesan otomatis.

Jika database menggunakan MySQL, gunakan presisi pecahan detik seperti DATETIME(3) atau tipe yang sesuai jika didukung dan dibutuhkan.

Ketentuan:

- Pertahankan timestamp pendaftaran sebagai data historis.
- Jangan mengganti timestamp pendaftaran dengan waktu keputusan admin.
- Jangan membuat milidetik palsu untuk data lama.
- Gunakan tie-breaker deterministik jika timestamp sama.
- Pastikan urutan FIFO tidak hanya bergantung pada presisi waktu.
- Jangan melakukan migrasi destruktif.
- Dokumentasikan perubahan skema dan keterbatasan data historis.

## B2. Validasi surat pengantar PDF

Surat pengantar merupakan dokumen wajib. Proposal magang bersifat opsional.

Lakukan validasi pada file yang diunggah:

- Validasi tipe file dan signature PDF.
- Periksa file kosong atau rusak.
- Ekstrak teks jika library yang sesuai tersedia.
- Gunakan indikator isi dokumen untuk menilai apakah file kemungkinan merupakan surat pengantar.
- Jangan menganggap nama file atau ekstensi PDF sebagai bukti bahwa dokumen benar.
- Jika PDF hasil scan tidak dapat dibaca, tandai untuk pemeriksaan manual.
- Jika klasifikasi tidak meyakinkan, jangan mengklaim dokumen valid secara otomatis.

Dokumen yang belum valid tidak boleh menjadi dasar penerimaan otomatis.

Jangan menambahkan dependency eksternal atau layanan AI berbayar tanpa kebutuhan dan persetujuan yang jelas.

---


# BAGIAN D — BATASAN RUANG LINGKUP

1. Jangan keluar dari ruang lingkup skripsi mengenai Sistem Informasi Manajemen Penerimaan Magang BTKP DIY.
2. Jangan membuat modul baru di luar kebutuhan revisi dosen.
3. Jangan membuat halaman pengaturan deadman terpisah dari `kuota.php`.
5. Jangan menambah fitur bisnis yang tidak diminta.
6. Jangan menghapus data atau menjalankan migrasi destruktif.
7. Jangan mengubah tampilan keseluruhan tanpa kebutuhan.
8. Jangan mengganti framework atau membangun ulang aplikasi.
9. Jangan menjalankan deployment produksi tanpa izin.
10. Jangan menyatakan pekerjaan selesai jika pengujian belum benar-benar dijalankan.

---

# BAGIAN E — LAPORAN AKHIR

Setelah implementasi, berikan laporan berisi:

1. File yang diubah.
2. Cara deadman system dikonfigurasi melalui `kuota.php`.
3. Cara tenggat dihitung dan bagaimana tindakan otomatis dipicu.
4. Cara sistem menjaga FIFO, kuota, dan validitas dokumen.
5. Perubahan database dan migrasi.
6. Perubahan terkait timestamp milidetik.
11. Risiko atau keputusan bisnis yang masih memerlukan persetujuan.
12. Cara menjalankan scheduler dan konfigurasi yang diperlukan.
