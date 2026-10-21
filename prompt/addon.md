# MASTER TASK: IMPLEMENTASI FIFO BERDASARKAN PERIODE MAGANG, PRESISI TIMESTAMP, DEADMAN SYSTEM, DAN VALIDASI SURAT PENGANTAR PDF

## ROLE

Bertindak sebagai Senior Software Engineer, Database Engineer, Backend Security Engineer, dan QA Engineer.

Proyek yang sedang dikerjakan adalah Sistem Manajemen Penerimaan Magang dengan Metode First In First Out (FIFO), studi kasus Balai Teknologi Komunikasi Pendidikan DIY (BTKP DIY).

Aplikasi sudah memiliki modul pendaftaran peserta, pengelolaan kuota, administrasi penerimaan, status magang, upload dokumen, dan autentikasi pengguna.

Tugasmu adalah mengaudit dan mengembangkan fitur yang sudah ada. Jangan membangun ulang aplikasi dari nol dan jangan mengubah fitur lain yang tidak berkaitan tanpa alasan teknis yang jelas.

Target akhir:
1. FIFO menggunakan periode magang sebagai dasar prioritas.
2. Pencatatan waktu database memiliki presisi hingga milidetik jika didukung oleh database.
3. Deadman system menangani pendaftaran yang tidak diproses admin melewati batas waktu.
4. Sistem memvalidasi apakah PDF yang diunggah benar-benar merupakan surat pengantar magang.
5. Proposal magang tetap bersifat opsional.
6. Seluruh perubahan aman, dapat diuji, konsisten dengan kuota, dan memiliki audit trail.

---

# BAGIAN 1 — AUDIT IMPLEMENTASI AKTUAL

Sebelum mengubah kode, lakukan audit menyeluruh terhadap:

1. Struktur direktori dan framework aplikasi.
2. Versi PHP atau runtime yang digunakan.
3. Database, versi server, storage engine, dan skema tabel aktual.
4. Tabel peserta, pendaftaran, periode magang, kuota, status penerimaan, dokumen, dan akun admin.
5. Query pengurutan FIFO yang sudah berjalan.
6. Fungsi penerimaan dan penolakan peserta.
7. Mekanisme validasi kuota.
8. Timestamp yang dicatat ketika pendaftaran dibuat dan ketika status berubah.
9. Proses upload, penyimpanan, pengunduhan, dan penghapusan dokumen.
10. Cron job, scheduler, atau mekanisme pemrosesan latar belakang yang tersedia.
11. Mekanisme notifikasi yang sudah ada.
12. Pengujian otomatis dan prosedur deployment yang digunakan.

Buat peta alur berikut sebelum implementasi:

- Peserta mengisi formulir.
- Sistem memvalidasi dokumen.
- Sistem menyimpan pendaftaran.
- Sistem menghitung prioritas antrean.
- Admin menerima atau menolak peserta.
- Sistem mengelola kuota.
- Sistem memantau batas waktu keputusan.
- Sistem menjalankan tindakan otomatis yang diizinkan.
- Sistem mencatat seluruh perubahan status.

Aturan pelaksanaan:
- Gunakan nama tabel dan kolom yang ditemukan dalam source code.
- Jangan mengasumsikan struktur database tanpa memeriksanya.
- Jangan menghapus atau mereset data lama.
- Buat checkpoint Git atau backup yang aman sebelum perubahan.
- Gunakan migrasi database yang mempertahankan data.
- Jangan menampilkan password, token, secret, atau isi dokumen sensitif di log.
- Jangan hanya menghasilkan dokumentasi atau contoh kode; implementasikan dan uji perubahan pada proyek aktual.
- Jika ada keputusan bisnis yang belum ditentukan, buat konfigurasi atau titik keputusan yang jelas dan jangan mengarang kebijakan.

---

# BAGIAN 2 — FIFO BERDASARKAN PERIODE MAGANG

## 2.1 Tujuan

Ubah implementasi FIFO yang sebelumnya berpotensi menggunakan waktu klik Submit sebagai prioritas utama menjadi FIFO berdasarkan periode magang yang telah ditentukan.

Prioritas harus mencerminkan periode magang peserta, bukan sekadar siapa yang lebih dahulu menekan tombol pendaftaran.

Jangan menggunakan timestamp pendaftaran sebagai kunci prioritas utama apabila aturan bisnis telah menetapkan periode magang sebagai dasar FIFO.

## 2.2 Definisi data periode

Audit data tanggal mulai dan tanggal selesai magang yang sudah digunakan aplikasi.

Pastikan setiap pendaftaran yang valid memiliki informasi periode magang yang dapat digunakan untuk menentukan prioritas.

Gunakan tanggal mulai dan tanggal selesai sesuai skema aktual.

Jika periode berasal dari pilihan periode yang telah ditentukan oleh admin, gunakan relasi periode tersebut jika tersedia. Jangan membuat periode baru secara otomatis apabila aplikasi sudah memiliki mekanisme pengelolaan periode.

Validasi:
- Tanggal mulai tidak boleh lebih akhir daripada tanggal selesai.
- Format tanggal harus valid.
- Periode yang belum ditentukan tidak boleh diam-diam ditempatkan pada posisi prioritas tertinggi.
- Data periode yang tidak valid harus ditandai untuk koreksi atau verifikasi.
- Perubahan periode setelah pendaftaran harus mengikuti aturan otorisasi dan audit trail yang sesuai.

## 2.3 Aturan pengurutan FIFO

Implementasikan aturan pengurutan yang konsisten di backend, halaman admin, halaman peserta, perhitungan posisi, dan proses otomatis.

Gunakan urutan prioritas berikut sebagai rancangan awal:

1. Tanggal mulai magang paling awal.
2. Tanggal selesai magang paling awal jika tanggal mulai sama.
3. Timestamp pendaftaran paling awal jika seluruh informasi periode sama.
4. ID pendaftaran unik sebagai tie-breaker terakhir jika semua nilai sebelumnya sama.

Aturan tersebut harus menjadi konfigurasi atau aturan bersama yang digunakan seluruh modul, bukan diimplementasikan secara terpisah dengan hasil berbeda.

PENTING:
Sebelum mengaktifkan pengurutan final, verifikasi bahwa tanggal mulai paling awal dan tanggal selesai paling awal memang sesuai dengan keputusan bisnis BTKP DIY. Jika aturan ini belum disetujui, dokumentasikan sebagai keputusan yang membutuhkan konfirmasi dan jangan mengaktifkannya diam-diam.

Jangan menggunakan:
- Urutan default database.
- Urutan ID saja sebagai prioritas utama.
- Timestamp Submit sebagai prioritas utama ketika periode magang menjadi dasar FIFO.
- Pengurutan frontend sebagai satu-satunya implementasi FIFO.

## 2.4 Status peserta dan kelayakan pemrosesan

Pisahkan dua konsep berikut:

A. Prioritas antrean: posisi peserta berdasarkan aturan periode magang.

B. Kelayakan tindakan: apakah peserta memenuhi persyaratan untuk diterima atau ditolak berdasarkan proses administrasi yang berlaku.

Peserta dengan periode paling awal tidak otomatis diterima jika dokumen wajib belum valid.

Audit bagaimana status berikut direpresentasikan dalam aplikasi:
- Menunggu verifikasi.
- Menunggu keputusan admin.
- Diterima.
- Ditolak.
- Sedang magang.
- Selesai.
- Diarsipkan.
- Status lain yang benar-benar digunakan aplikasi.

Jangan membuat status baru tanpa kebutuhan dan pemetaan yang jelas.

Tetapkan status mana yang masuk dalam antrean pemrosesan keputusan. Peserta yang sudah diputuskan tidak boleh terus-menerus menghalangi antrean yang masih aktif.

Kebijakan tentang dokumen tidak valid juga harus eksplisit: apakah peserta menunggu perbaikan, dianggap belum memenuhi syarat, atau diproses melalui jalur pengecualian. Jangan membiarkan peserta yang dokumennya tidak valid mengunci antrean tanpa batas waktu secara tidak sengaja.

## 2.5 Pencegahan antrean dilompati

Pada setiap aksi penerimaan atau penolakan:

1. Validasi autentikasi dan hak akses admin.
2. Ambil data antrean terbaru dari database.
3. Hitung ulang prioritas menggunakan aturan FIFO yang sama.
4. Verifikasi bahwa peserta yang dipilih boleh diproses.
5. Periksa status terkini dan kelengkapan dokumen.
6. Periksa kuota jika tindakan membutuhkan alokasi kuota.
7. Jalankan perubahan dalam transaksi database jika didukung.
8. Gunakan locking atau mekanisme konkurensi yang sesuai dengan database aktual.
9. Catat perubahan status dan audit trail.
10. Commit hanya jika seluruh persyaratan terpenuhi.

Jika dua admin memproses peserta secara bersamaan, sistem harus mencegah terjadinya perubahan status yang saling bertentangan atau pelompatan antrean.

Jangan menganggap bahwa transaksi database saja otomatis menyelesaikan seluruh masalah konkurensi. Pilih strategi locking dan isolation level berdasarkan database serta query aktual.

Jika urutan berubah sebelum admin menyelesaikan tindakan, sistem harus memeriksa ulang prioritas sebelum menyimpan keputusan.

---

# BAGIAN 3 — TIMESTAMP DENGAN PRESISI MILIDETIK

## 3.1 Tujuan

Audit dan perbaiki pencatatan waktu agar mendukung presisi hingga milidetik untuk kebutuhan audit dan pemecahan seri pengurutan FIFO.

Presisi milidetik harus diterapkan pada kolom yang memang diperlukan, bukan secara membabi buta pada seluruh kolom waktu aplikasi.

## 3.2 Kolom yang perlu diperiksa

Identifikasi kolom aktual yang merekam:
- Waktu pendaftaran dibuat.
- Waktu dokumen diunggah atau diperbarui.
- Waktu keputusan admin.
- Waktu tindakan otomatis.
- Waktu peserta selesai magang.
- Waktu perubahan status.
- Waktu audit trail dibuat.

Tentukan kolom mana yang membutuhkan presisi milidetik berdasarkan kebutuhan aplikasi.

## 3.3 Implementasi database

Jika database yang digunakan adalah MySQL dan mendukung tipe temporal dengan fractional seconds, pertimbangkan tipe seperti DATETIME(3) atau TIMESTAMP(3), sesuai kebutuhan dan zona waktu aplikasi.

Jika menggunakan database lain, gunakan tipe waktu yang setara dan didukung oleh database tersebut.

Persyaratan:
- Periksa tipe kolom dan presisi yang digunakan saat ini.
- Pastikan presisi tidak hilang ketika data dikirim dari aplikasi ke database.
- Pastikan driver database dan fungsi aplikasi mendukung penyimpanan pecahan detik.
- Pastikan format tampilan dapat menunjukkan milidetik jika diperlukan.
- Gunakan prepared statements atau ORM yang sesuai.
- Pertahankan kompatibilitas data lama.
- Hindari migrasi destruktif.
- Uji pembacaan dan penulisan timestamp melalui aplikasi, bukan hanya query SQL manual.

Jangan mengubah seluruh kolom waktu menjadi UTC atau zona waktu lain tanpa memeriksa konvensi yang sudah digunakan aplikasi. Tetapkan satu konvensi penyimpanan dan konversi secara konsisten.

## 3.4 Timestamp identik dan urutan deterministik

Presisi milidetik tidak menjamin semua pendaftaran memiliki timestamp berbeda.

Karena itu:
- Pertahankan timestamp sebagai data waktu aktual.
- Jangan menambahkan milidetik palsu untuk memaksa timestamp menjadi unik.
- Jangan memodifikasi timestamp historis agar urutan terlihat rapi.
- Gunakan ID unik atau tie-breaker lain yang stabil untuk menentukan urutan ketika timestamp sama.
- Pastikan aturan tie-breaker konsisten pada halaman admin, halaman peserta, dan proses backend.

Jika sistem harus mencatat urutan transaksi yang benar-benar terjadi ketika beberapa request masuk bersamaan, audit kebutuhan sequence atau mekanisme penetapan urutan atomik. Jangan menyimpulkan bahwa presisi jam sistem membuktikan urutan kejadian secara mutlak.

## 3.5 Backfill data lama

Jika kolom baru diperlukan:
- Buat migrasi non-destruktif.
- Pertahankan nilai timestamp historis yang tersedia.
- Jangan mengarang pecahan milidetik untuk data lama.
- Jika presisi historis tidak tersedia, pertahankan data tersebut dengan presisi aslinya.
- Dokumentasikan keterbatasan pengurutan untuk data lama.
- Uji migrasi pada salinan database atau lingkungan pengujian sebelum diterapkan pada data produksi.

---

# BAGIAN 4 — DEADMAN SYSTEM UNTUK PENDAFTARAN YANG BELUM DIPROSES

## 4.1 Tujuan

Bangun mekanisme otomatis untuk mendeteksi pendaftaran yang belum menerima keputusan admin setelah melewati batas waktu yang telah ditentukan.

Sistem harus mampu:
- Menentukan waktu mulai penghitungan tenggat.
- Menghitung apakah tenggat telah terlampaui.
- Menjalankan tindakan otomatis yang telah disetujui.
- Menjaga FIFO dan kuota tetap konsisten.
- Mencatat alasan dan hasil tindakan otomatis.
- Mencegah tindakan yang sama dijalankan berulang kali.

Jangan menganggap deadman system sebagai mekanisme yang otomatis menerima semua peserta setelah beberapa hari.

## 4.2 Konfigurasi kebijakan

Buat konfigurasi yang dapat diatur untuk:
- Batas waktu pemrosesan.
- Satuan waktu: jam atau hari.
- Definisi awal penghitungan tenggat.
- Tindakan setelah tenggat.
- Zona waktu operasional.
- Status yang memenuhi syarat untuk dipantau.
- Status yang dikecualikan dari pemrosesan otomatis.
- Kebijakan pengingat atau eskalasi jika tersedia.

Jangan menetapkan angka tenggat atau keputusan otomatis secara permanen berdasarkan asumsi sendiri.

Jika kebijakan resmi belum ditentukan:
1. Implementasikan fondasi teknis dan konfigurasi.
2. Buat mode dry-run atau simulasi.
3. Tampilkan daftar pendaftaran yang sudah melewati tenggat beserta tindakan yang diusulkan.
4. Jangan menjalankan penerimaan atau penolakan otomatis secara nyata sebelum kebijakan disetujui.
5. Dokumentasikan konfigurasi yang wajib ditentukan oleh pemilik aplikasi.

Jangan mengaktifkan mode otomatis hanya karena nilai konfigurasi belum tersedia.

## 4.3 Awal penghitungan tenggat

Gunakan timestamp yang benar-benar merepresentasikan awal proses menunggu keputusan.

Periksa apakah aplikasi sudah mempunyai kolom untuk:
- Waktu pendaftaran.
- Waktu dokumen dinyatakan lengkap.
- Waktu mulai menunggu keputusan.
- Waktu terakhir status berubah.

Jika dokumen wajib harus lengkap sebelum pendaftaran dapat diproses, tentukan apakah tenggat dimulai pada saat pendaftaran masuk atau ketika dokumen wajib dinyatakan lengkap.

Jangan menggunakan timestamp yang salah hanya karena kolom tersebut paling mudah ditemukan.

Jika tenggat harus dihitung berdasarkan waktu pendaftaran, gunakan waktu pendaftaran yang tersimpan di database. Jika aturan bisnis menetapkan waktu setelah dokumen lengkap, gunakan timestamp dokumen lengkap yang valid.

## 4.4 Mekanisme scheduler

Audit apakah aplikasi menggunakan cron, task scheduler, atau worker.

Implementasikan mekanisme sesuai lingkungan aplikasi:
- Jika tersedia scheduler, gunakan pola scheduler yang sudah ada.
- Jika belum ada, sediakan command atau script terpisah yang dapat dipanggil oleh cron atau mekanisme penjadwalan yang sesuai.
- Jangan mengandalkan pengunjung membuka halaman web sebagai pemicu utama.
- Jangan menjalankan tugas otomatis berat pada setiap request HTTP.
- Dokumentasikan cara mengaktifkan dan menjadwalkan proses pada server.
- Gunakan logging yang aman dan tidak mencatat isi dokumen atau kredensial.

Pemrosesan harus aman jika command dijalankan lebih dari satu kali.

## 4.5 Alur kerja deadman system

Untuk setiap kandidat yang melewati tenggat:

1. Ambil kandidat berdasarkan status dan waktu yang valid.
2. Periksa ulang apakah tenggat benar-benar terlampaui.
3. Periksa apakah admin sudah membuat keputusan.
4. Periksa kelengkapan dan validitas dokumen wajib.
5. Periksa prioritas FIFO dan status antrean.
6. Periksa kuota apabila tindakan yang direncanakan memerlukan kuota.
7. Periksa apakah peserta masih memenuhi syarat tindakan otomatis.
8. Periksa apakah kandidat sudah pernah diproses oleh deadman system.
9. Terapkan kebijakan yang telah disetujui.
10. Simpan perubahan status dan audit trail secara atomik.
11. Catat hasil, alasan, dan waktu eksekusi.

Jika kondisi berubah di antara proses pengambilan kandidat dan penyimpanan hasil, batalkan tindakan dan lakukan evaluasi ulang.

## 4.6 Kebijakan penerimaan dan penolakan otomatis

Pisahkan kebijakan menjadi:
- Otomatis menolak setelah tenggat.
- Otomatis menerima setelah tenggat.
- Mengirim pengingat atau eskalasi kepada admin.
- Membiarkan status tetap menunggu keputusan.

Jangan mengimplementasikan pilihan tersebut sebagai tindakan yang saling dapat dipertukarkan tanpa aturan.

Jika kebijakan resmi memilih penerimaan otomatis:
- Peserta harus memenuhi seluruh persyaratan wajib.
- Dokumen surat pengantar harus dinyatakan valid.
- Kuota harus tersedia sesuai aturan yang berlaku.
- FIFO dan prioritas periode harus dihormati.
- Tindakan harus memiliki dasar kebijakan yang jelas.
- Jika salah satu syarat gagal, jangan menerima otomatis; tandai untuk penanganan sesuai kebijakan.

Jika kebijakan memilih penolakan otomatis:
- Pastikan batas waktu dan dasar penolakan telah disetujui.
- Catat alasan penolakan otomatis.
- Berikan informasi status yang sesuai kepada peserta.
- Jangan menghapus data pendaftaran atau dokumen.
- Jangan menolak peserta yang sudah memperoleh keputusan sebelumnya.

Jika kebijakan belum ditentukan, tetap gunakan dry-run dan jangan membuat keputusan final secara otomatis.

## 4.7 Idempotensi dan audit trail

Sistem tidak boleh memproses kandidat yang sama berulang kali secara tidak terkendali.

Implementasikan:
- Pemeriksaan status terkini sebelum perubahan.
- Conditional update atau mekanisme konkurensi yang sesuai.
- Identitas eksekusi atau pencatatan kejadian yang dapat digunakan untuk mencegah pemrosesan ganda.
- Audit trail untuk tindakan otomatis.

Catat sekurang-kurangnya:
- ID pendaftaran.
- Status sebelumnya.
- Status sesudahnya.
- Waktu tindakan.
- Jenis tindakan.
- Alasan dan kebijakan yang digunakan.
- Sumber tindakan: manual atau otomatis.
- Identitas proses atau job jika tersedia.

Jangan mencatat password, token, atau konten PDF sensitif.

## 4.8 Notifikasi

Jika aplikasi sudah memiliki mekanisme notifikasi, gunakan kembali mekanisme tersebut untuk:
- Mengingatkan admin tentang pendaftaran yang mendekati tenggat.
- Memberitahu admin tentang pendaftaran yang telah melewati tenggat.
- Memberitahu peserta jika status berubah otomatis.

Jangan menganggap email atau layanan eksternal tersedia jika belum dikonfigurasi. Jika notifikasi belum tersedia, dokumentasikan kebutuhan dan sediakan pencatatan status yang dapat diperiksa admin.

---

# BAGIAN 5 — VALIDASI PDF SURAT PENGANTAR

## 5.1 Tujuan

Tambahkan mekanisme untuk memeriksa apakah file PDF yang diunggah sebagai surat pengantar benar-benar berisi dokumen surat pengantar magang, bukan sekadar file PDF dengan nama yang sesuai.

Surat pengantar adalah dokumen wajib.

Proposal magang adalah dokumen opsional.

Validasi harus mempertimbangkan struktur file dan isi dokumen, bukan hanya nama file atau ekstensi.

## 5.2 Validasi file dasar

Ketika peserta mengunggah surat pengantar:

1. Pastikan file benar-benar ada.
2. Validasi ekstensi file sesuai kebijakan aplikasi.
3. Periksa MIME type.
4. Periksa signature atau magic bytes PDF.
5. Tolak file kosong, rusak, atau tidak dapat diproses.
6. Terapkan batas ukuran file yang dapat dikonfigurasi.
7. Gunakan nama file penyimpanan yang aman dan tidak bergantung pada nama dari pengguna.
8. Simpan file di lokasi yang sesuai dengan kebijakan keamanan aplikasi.
9. Cegah path traversal dan akses langsung tanpa otorisasi.
10. Jangan menjalankan file yang diunggah.
11. Jangan mempercayai Content-Type yang dikirim browser sebagai satu-satunya bukti jenis file.

Jika file tidak lolos validasi dasar, tolak upload dan tampilkan pesan yang dapat dipahami peserta.

## 5.3 Ekstraksi teks PDF

Periksa dependency dan library yang sudah tersedia.

Jika belum tersedia, pilih library ekstraksi teks PDF yang kompatibel dengan stack aktual dan mudah dipelihara.

Tujuan ekstraksi:
- Membaca teks dari PDF.
- Memeriksa indikator isi surat pengantar.
- Mengidentifikasi informasi yang relevan dengan dokumen magang.
- Menentukan apakah file dapat dilanjutkan ke proses verifikasi.

Jangan mengasumsikan bahwa semua PDF memiliki teks yang dapat diekstrak.

Untuk PDF hasil scan:
- Deteksi apakah hasil ekstraksi kosong atau tidak memadai.
- Jika diperlukan, gunakan OCR yang sesuai dengan infrastruktur aplikasi.
- Jika OCR belum tersedia, tandai dokumen sebagai perlu pemeriksaan manual.
- Jangan menyatakan dokumen valid hanya karena OCR gagal menghasilkan teks.

Jangan mengirim dokumen peserta ke layanan eksternal tanpa otorisasi, kebijakan privasi, dan konfigurasi yang jelas.

## 5.4 Pemeriksaan isi surat pengantar

Buat lapisan klasifikasi dokumen yang dapat mengidentifikasi apakah isi PDF kemungkinan merupakan surat pengantar magang.

Gunakan indikator yang dapat dikonfigurasi dan disesuaikan dengan dokumen resmi BTKP DIY, misalnya:
- Frasa surat pengantar atau pengantar magang.
- Identitas instansi penerbit.
- Nomor surat jika memang diwajibkan.
- Tanggal surat jika memang diwajibkan.
- Identitas pihak atau instansi tujuan.
- Konteks permohonan atau pengajuan magang.
- Isi dokumen yang relevan dengan permohonan magang.

Daftar indikator di atas adalah kandidat pemeriksaan, bukan persyaratan resmi yang boleh diasumsikan berlaku seluruhnya.

Jangan hanya menggunakan nama file seperti surat_pengantar.pdf sebagai bukti validitas.

Jangan hanya menggunakan satu kata kunci sebagai bukti validitas.

Jika dokumen berisi materi yang tidak sesuai, misalnya proposal kegiatan, laporan, sertifikat, atau dokumen lain yang bukan surat pengantar, sistem harus dapat menandainya sebagai kemungkinan salah dokumen.

Jangan membuat klasifikasi yang mengklaim kepastian 100 persen tanpa evaluasi.

## 5.5 Status validasi dokumen

Gunakan status yang sesuai dengan model data aplikasi. Jika belum ada, rancang status terpisah yang jelas, misalnya:

- Belum diunggah.
- Menunggu validasi.
- Valid secara otomatis.
- Perlu pemeriksaan manual.
- Tidak valid.
- Valid setelah pemeriksaan admin.

Nama status dapat disesuaikan dengan pola aplikasi, tetapi makna setiap status harus terdokumentasi.

Perbedaan penting:
- File valid secara teknis tidak otomatis merupakan surat pengantar yang benar.
- Hasil klasifikasi otomatis tidak otomatis menggantikan keputusan admin jika kebijakan mengharuskan verifikasi manusia.
- Dokumen yang belum terverifikasi tidak boleh diam-diam dianggap valid.

Jika confidence score digunakan, tentukan threshold berdasarkan evaluasi pada sampel dokumen nyata yang telah diotorisasi untuk pengujian. Jangan mengarang angka akurasi atau mengklaim tingkat keberhasilan tanpa hasil pengukuran.

## 5.6 Perilaku jika validasi gagal

Jika PDF rusak, tidak terbaca, atau tidak memenuhi indikator surat pengantar:
- Tampilkan alasan yang relevan kepada peserta.
- Izinkan peserta mengunggah ulang sesuai kebijakan aplikasi.
- Jangan menghapus file sebelumnya sebelum file pengganti berhasil disimpan.
- Jangan membuat pendaftaran dianggap lengkap jika dokumen wajib belum valid.
- Jangan mengizinkan deadman system menerima peserta dengan dokumen wajib yang tidak valid.
- Catat status validasi tanpa menyimpan seluruh isi dokumen ke log.

Jika sistem tidak dapat memastikan jenis dokumen, tandai untuk pemeriksaan manual daripada membuat keputusan kelayakan secara keliru.

## 5.7 Proposal magang bersifat opsional

Pertahankan proposal sebagai dokumen opsional.

Ketentuan:
- Pendaftaran tidak boleh ditolak hanya karena proposal tidak diunggah.
- Validasi surat pengantar dan validasi proposal harus dipisahkan.
- Proposal tidak boleh digunakan sebagai pengganti surat pengantar.
- Jika proposal diunggah, lakukan validasi file dasar yang sama sesuai kebutuhan.
- Jangan menerapkan aturan mandatory pada proposal.
- Jangan memblokir pendaftaran hanya karena hasil klasifikasi proposal tidak tersedia, kecuali terdapat kebijakan terpisah yang memang mengharuskannya.

## 5.8 Integrasi dengan FIFO dan deadman system

Hubungkan status dokumen dengan proses administrasi.

Pastikan:
- Status dokumen tidak mengubah urutan prioritas FIFO secara diam-diam.
- Dokumen yang tidak valid tidak membuat peserta otomatis diterima.
- Tenggat dokumen dan tenggat keputusan admin memiliki definisi yang jelas.
- Jika peserta perlu memperbaiki dokumen, sistem mengikuti kebijakan tenggat yang telah ditentukan.
- Proses otomatis memeriksa status dokumen terkini sebelum melakukan perubahan status.
- Admin dapat mengetahui alasan dokumen perlu pemeriksaan manual.

---

# BAGIAN 6 — ARSITEKTUR DAN INTEGRITAS DATA

Implementasikan perubahan secara modular mengikuti struktur aplikasi aktual.

Pisahkan tanggung jawab secara jelas apabila sesuai dengan arsitektur yang sudah digunakan:
- Penentuan urutan FIFO.
- Penghitungan kuota.
- Validasi dokumen.
- Pemrosesan tenggat.
- Perubahan status pendaftaran.
- Audit trail.
- Notifikasi.

Hindari menggandakan logika yang sama pada beberapa halaman.

Gunakan transaksi database untuk perubahan yang saling bergantung, seperti:
- Penerimaan peserta dan alokasi kuota.
- Perubahan status oleh deadman system.
- Penyelesaian verifikasi dokumen dan perubahan status.
- Pencatatan keputusan serta audit trail jika keduanya harus konsisten.

Pastikan:
- Perubahan status tidak dapat terjadi dua kali secara bertentangan.
- Job otomatis tidak memproses peserta yang sudah diproses admin.
- Pengunggahan file dan perubahan metadata tidak meninggalkan data parsial.
- Kegagalan dependency ekstraksi PDF tidak merusak pendaftaran yang sudah ada.
- Data lama tetap dapat dibaca setelah migrasi.
- Hak akses tetap diterapkan di backend.

Jangan menambahkan dependency besar jika kebutuhan dapat dipenuhi dengan dependency yang lebih ringan dan kompatibel.

---

# BAGIAN 7 — PENGUJIAN WAJIB

Buat dan jalankan pengujian otomatis sesuai framework aplikasi. Tambahkan pengujian integrasi jika memungkinkan.

## A. FIFO

1. Peserta dengan tanggal mulai lebih awal mendapat prioritas sesuai aturan yang disetujui.
2. Peserta dengan tanggal mulai sama mengikuti tie-breaker tanggal selesai.
3. Peserta dengan periode sama mengikuti tie-breaker timestamp.
4. Timestamp identik menghasilkan urutan deterministik.
5. Data periode tidak valid tidak mendapatkan prioritas yang tidak semestinya.
6. Halaman admin dan peserta menampilkan urutan yang konsisten.
7. Dua admin tidak dapat memproses antrean secara inkonsisten.
8. Peserta yang sudah diputuskan tidak terus menghalangi antrean aktif.
9. Dokumen tidak valid tidak otomatis membuat peserta diterima.
10. Kuota tetap konsisten ketika penerimaan dilakukan.

## B. Timestamp

1. Timestamp baru tersimpan dengan presisi milidetik jika didukung.
2. Presisi tetap terjaga ketika data dibaca kembali melalui aplikasi.
3. Timestamp historis tidak diberi pecahan detik palsu.
4. Timestamp identik ditangani dengan tie-breaker.
5. Zona waktu konsisten di database, aplikasi, dan tampilan.
6. Migrasi tidak merusak data lama.

## C. Deadman system

1. Pendaftaran yang belum melewati tenggat tidak diproses.
2. Pendaftaran yang melewati tenggat terdeteksi.
3. Dry-run tidak mengubah status peserta.
4. Kandidat yang telah diproses admin tidak diproses otomatis.
5. Kandidat yang sudah ditangani job sebelumnya tidak diproses berulang kali.
6. Dokumen wajib yang tidak valid tidak menyebabkan penerimaan otomatis.
7. Kuota diperiksa ulang sebelum tindakan yang membutuhkan kapasitas.
8. FIFO diperiksa kembali saat tindakan otomatis dijalankan.
9. Kegagalan job dicatat dengan aman.
10. Dua eksekusi scheduler bersamaan tidak menimbulkan keputusan ganda.
11. Status dan audit trail konsisten.
12. Jika konfigurasi kebijakan belum tersedia, tindakan otomatis final tetap nonaktif.

## D. Validasi PDF

Gunakan dokumen uji yang diotorisasi dan tidak mengandung data pribadi yang tidak diperlukan.

Skenario:
1. Surat pengantar PDF dengan teks yang sesuai.
2. PDF valid tetapi berisi proposal, bukan surat pengantar.
3. PDF valid tetapi berisi sertifikat.
4. File gambar yang diganti ekstensi menjadi .pdf.
5. PDF rusak.
6. PDF kosong.
7. PDF dengan teks yang tidak dapat diekstrak.
8. PDF hasil scan yang membutuhkan OCR.
9. PDF dengan ukuran melebihi batas.
10. PDF dengan nama file berbahaya.
11. Surat pengantar dengan format teks yang tidak biasa.
12. Dokumen yang membutuhkan pemeriksaan manual.
13. Proposal tidak diunggah.
14. Proposal diunggah dengan format yang valid.
15. Upload ulang setelah dokumen ditolak.
16. Kegagalan ekstraksi tidak merusak pendaftaran.
17. Dokumen wajib yang tidak valid memblokir penerimaan otomatis.

Jangan mengklaim akurasi klasifikasi tertentu tanpa dataset evaluasi dan hasil pengujian yang nyata.

---

# BAGIAN 8 — PENERAPAN DAN OPERASIONAL

Sebelum deployment:

1. Tampilkan seluruh migrasi database yang diperlukan.
2. Jelaskan dampak migrasi pada data lama.
3. Dokumentasikan dependency PDF/OCR yang ditambahkan.
4. Dokumentasikan cara menjalankan scheduler.
5. Dokumentasikan konfigurasi tenggat.
6. Dokumentasikan cara mengaktifkan dan menonaktifkan deadman system.
7. Pastikan mode dry-run tersedia sebelum mode otomatis diaktifkan.
8. Pastikan kebijakan penerimaan atau penolakan otomatis sudah disetujui.
9. Pastikan mekanisme rollback tersedia untuk perubahan kode dan migrasi yang relevan.
10. Jangan menjalankan deployment produksi tanpa izin.

Jangan menyimpan dokumen peserta pada direktori publik tanpa kontrol akses yang sesuai.

---

# BAGIAN 9 — LAPORAN AKHIR

Setelah implementasi, buat laporan teknis yang memuat:

1. Kondisi awal sistem dan masalah yang ditemukan.
2. File yang dibuat atau diubah.
3. Perubahan skema database dan migrasi.
4. Algoritma FIFO yang diterapkan.
5. Aturan tie-breaker dan penanganan timestamp identik.
6. Hasil implementasi presisi timestamp.
7. Definisi awal tenggat deadman system.
8. Cara menjalankan scheduler.
9. Konfigurasi kebijakan penerimaan atau penolakan otomatis.
10. Mekanisme validasi PDF dan ekstraksi teks.
11. Cara menangani PDF hasil scan.
12. Perbedaan validasi teknis file dan validasi isi surat pengantar.
13. Hasil seluruh pengujian yang benar-benar dijalankan.
14. Tes yang gagal atau belum dapat dilakukan.
15. Risiko keamanan dan keterbatasan yang tersisa.
16. Keputusan bisnis yang masih membutuhkan persetujuan.
17. Langkah penerapan dan rollback yang aman.

Gunakan status PASS, FAIL, atau BLOCKED untuk setiap pengujian penting.

Jangan mengklaim bahwa fitur aman, akurat, atau siap produksi tanpa bukti pengujian yang sesuai.

MULAI SEKARANG:
Audit source code aktual, buat checkpoint, identifikasi konflik aturan bisnis, lalu implementasikan perubahan yang tidak membutuhkan keputusan bisnis tambahan. Untuk keputusan yang belum ditetapkan, siapkan konfigurasi dan dokumentasi tanpa mengaktifkan perilaku otomatis yang belum disetujui.