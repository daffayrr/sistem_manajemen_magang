import os
import csv
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

# Pastikan folder reports ada
os.makedirs('reports', exist_ok=True)

csv_path = 'reports/hasil_pengujian_blackbox.csv'
xlsx_path = 'reports/hasil_pengujian_blackbox.xlsx'

headers = ['No', 'Fitur', 'Pengujian', 'Input', 'Hasil yang diharapkan', 'Status']

test_cases = [
    # 1. Autentikasi Admin
    (
        "Autentikasi Admin",
        "Memasukkan username dan password yang tidak terdaftar pada sistem.",
        "Username: admin_salah, Password: password_hahahaha",
        "Sistem menolak autentikasi, tetap berada di index.php, dan menampilkan alert 'Login Gagal! Akun tidak ditemukan.'",
        "Berhasil"
    ),
    (
        "Autentikasi Admin",
        "Memasukkan kredensial login admin yang sah dan terdaftar.",
        "Username: admin, Password: password sah (admin123)",
        "Sistem mengautentikasi akun dan mengarahkan pengguna ke halaman /admin_magang/dashboard.php.",
        "Berhasil"
    ),
    (
        "Autentikasi Admin",
        "Memasukkan username admin benar tetapi password salah.",
        "Username: admin, Password: salah",
        "Sistem menolak login, sesi tidak dibuat, dan menampilkan pesan kesalahan.",
        "Berhasil"
    ),
    (
        "Autentikasi Admin",
        "Mengakses dashboard admin langsung via URL tanpa sesi login aktif.",
        "Akses langsung URL: /admin_magang/dashboard.php tanpa sesi login",
        "Sistem memblokir akses dan mengalihkan pengguna kembali ke index.php?pesan=belum_login.",
        "Berhasil"
    ),
    (
        "Autentikasi Admin",
        "Melakukan logout dari dashboard admin.",
        "Klik tombol logout pada header/navbar admin",
        "Sesi dihancurkan, pengguna diarahkan ke index.php, dan tampil alert 'Anda telah berhasil logout.'",
        "Berhasil"
    ),

    # 2. Dashboard Administrator
    (
        "Dashboard Admin",
        "Memvalidasi tampilan seluruh widget statistik dan indikator sistem.",
        "Membuka halaman /admin_magang/dashboard.php setelah login",
        "Seluruh widget tampil: Banner Metode FIFO, Sisa Kuota Magang, Peserta Menunggu Verifikasi, dan Status Sistem.",
        "Berhasil"
    ),
    (
        "Dashboard Admin",
        "Menguji tautan navigasi pada widget Aksi Cepat Admin.",
        "Klik tombol Aksi Cepat: Update Kuota, Edit Pengumuman, Tambah Admin Baru",
        "Sistem mengarahkan admin ke halaman kuota.php, informasi.php, dan admin.php dengan tepat.",
        "Berhasil"
    ),
    (
        "Dashboard Admin",
        "Menguji tautan pada Tabel Antrean FIFO di Dashboard.",
        "Klik tombol Verifikasi pada baris pendaftar teratas di tabel dashboard",
        "Sistem mengarahkan admin ke halaman detail_peserta.php sesuai ID pendaftar yang dipilih.",
        "Berhasil"
    ),
    (
        "Dashboard Admin",
        "Memvalidasi widget pemantauan Deadman System pada dashboard.",
        "Melihat status widget Deadman System di kolom kanan dashboard",
        "Sistem menampilkan jumlah pendaftaran tertahan > 7 hari dan mode eksekusi aktif (DRY-RUN).",
        "Berhasil"
    ),

    # 3. Manajemen dan Verifikasi Peserta
    (
        "Verifikasi Peserta",
        "Menguji navigasi filter status pendaftar (Menunggu, Diterima, Ditolak, Arsip).",
        "Klik tombol filter status pada halaman peserta.php",
        "Tabel memperbarui daftar peserta sesuai parameter status yang dipilih dalam URL (filter=...).",
        "Berhasil"
    ),
    (
        "Verifikasi Peserta",
        "Menguji fitur pencarian data peserta berdasarkan nama atau instansi.",
        "Ketik kata kunci 'Alma Ata' pada kolom pencarian dan submit",
        "Tabel hanya menampilkan daftar peserta yang memuat kata kunci pencarian pada nama atau instansi.",
        "Berhasil"
    ),
    (
        "Verifikasi Peserta",
        "Membuka halaman detail pendaftar dari tabel verifikasi.",
        "Klik tombol Detail pada salah satu baris peserta",
        "Sistem mengarahkan ke detail_peserta.php?id=... dan merender profil lengkap, tanggal, dan berkas pengajuan.",
        "Berhasil"
    ),
    (
        "Verifikasi Peserta",
        "Membatalkan aksi penolakan peserta pada konfirmasi modal SweetAlert.",
        "Klik tombol Tolak (X) lalu klik tombol Batal pada dialog konfirmasi",
        "Dialog konfirmasi tertutup, tidak ada eksekusi penghapusan/penolakan, dan data pendaftar tetap utuh.",
        "Berhasil"
    ),
    (
        "Verifikasi Peserta",
        "Memverifikasi aturan anti-skipping antrean FIFO saat admin memproses penerimaan.",
        "Mencoba menerima peserta antrean urutan kedua sebelum peserta urutan pertama diproses",
        "Sistem menolak tindakan dengan pesan peringatan: Pendaftar urutan pertama harus diproses terlebih dahulu.",
        "Berhasil"
    ),
    (
        "Verifikasi Peserta",
        "Mencegah penerimaan peserta jika berkas surat pengantar terdeteksi 'Tidak Valid'.",
        "Mencoba menerima pendaftar dengan status_berkas = 'Tidak Valid'",
        "Backend memblokir aksi penerimaan dan menampilkan notifikasi berkas tidak valid.",
        "Berhasil"
    ),
    (
        "Verifikasi Peserta",
        "Melakukan validasi manual terhadap dokumen pendaftar yang berstatus 'Perlu Pemeriksaan Manual'.",
        "Klik aksi 'Setujui Manual' pada berkas hasil scan",
        "Status berkas berhasil diubah menjadi 'Valid Admin' dan tercatat dalam audit trail.",
        "Berhasil"
    ),

    # 4. Penyelesaian Magang dan Arsip
    (
        "Penyelesaian Magang",
        "Mencoba menyelesaikan magang peserta ketika file sertifikat atau surat keterangan belum diunggah.",
        "Klik tombol Selesaikan saat berkas kelulusan belum diunggah di detail_peserta.php",
        "Sistem memblokir aksi dan menampilkan peringatan bahwa dokumen kelulusan wajib dilengkapi terlebih dahulu.",
        "Berhasil"
    ),
    (
        "Penyelesaian Magang",
        "Mengunggah dokumen kelulusan resmi (Sertifikat Nilai dan Surat Keterangan) format PDF.",
        "Unggah file sertifikat dan surat keterangan yang sah (PDF <= 5MB) di detail_peserta.php",
        "File berhasil disimpan di uploads/, status kelulusan terpenuhi, dan admin dapat menyelesaikan magang.",
        "Berhasil"
    ),
    (
        "Penyelesaian Magang",
        "Menyelesaikan magang peserta aktif dan memvalidasi pelepasan kuota ke kapasitas tersedia.",
        "Klik tombol Selesaikan pada peserta dengan dokumen lengkap",
        "Status pendaftaran berubah menjadi 'Selesai', berpindah ke tab Arsip, dan kuota tersedia bertambah (+1).",
        "Berhasil"
    ),

    # 5. Pengaturan Kuota Magang
    (
        "Pengaturan Kuota",
        "Melakukan pengujian perubahan kuota berulang (Stress Test) dengan nilai input dinamis.",
        "Input nilai kuota: 10, 25, 8, 15 secara berurutan pada kuota.php",
        "Sistem berhasil memperbarui kuota pada setiap iterasi dan menampilkan notifikasi pembaruan kuota.",
        "Berhasil"
    ),
    (
        "Pengaturan Kuota",
        "Mengunci kuota maksimal magang pada angka 4 sesuai dengan skenario skripsi.",
        "Input kuota maksimal: 4 pada formulir kuota.php",
        "Sistem mengunci kuota pada angka 4, status tersimpan di database, dan nilai input bernilai 4.",
        "Berhasil"
    ),
    (
        "Pengaturan Kuota",
        "Memasukkan nilai kuota tidak valid (0 atau angka negatif).",
        "Input kuota: 0 atau -5",
        "Sistem menolak pembaruan dengan alert: 'Jumlah kuota maksimal minimal 1 orang.'",
        "Berhasil"
    ),
    (
        "Pengaturan Kuota",
        "Menolak pendaftaran baru dan penerimaan peserta saat sisa kuota bernilai 0 (Penuh).",
        "Simulasi sisa kuota = 0 dan mengajukan pendaftaran baru",
        "Form pendaftaran diblokir di frontend dan backend menolak transaksi penerimaan karena kuota penuh.",
        "Berhasil"
    ),

    # 6. Deadman System di Kuota.php
    (
        "Deadman System",
        "Memvalidasi keberadaan komponen pengaturan Deadman System pada halaman kuota.php.",
        "Buka halaman /admin_magang/kuota.php",
        "Komponen switch toggle, input hari toleransi, dropdown kebijakan tindakan, dan tombol simpan tampil lengkap.",
        "Berhasil"
    ),
    (
        "Deadman System",
        "Menyimpan konfigurasi Deadman System (Aktifkan toggle, ubah hari, pilih kebijakan).",
        "Toggle ON, Tenggat: 7 hari, Kebijakan: AUDIT_ONLY",
        "Sistem menyimpan konfigurasi secara persisten ke database tabel kuota dan memunculkan alert sukses.",
        "Berhasil"
    ),
    (
        "Deadman System",
        "Memvalidasi input tenggat toleransi di luar batas yang diizinkan (< 1 atau > 90 hari).",
        "Input hari tenggat: 0 atau 150 hari",
        "Sistem menolak konfigurasi dengan pesan: 'Batas waktu tenggat harus bernilai antara 1 sampai 90 hari.'",
        "Berhasil"
    ),
    (
        "Deadman System",
        "Mengeksekusi skrip background worker Deadman System melalui CLI.",
        "Eksekusi CLI: php scripts/deadman_worker.php",
        "Skrip membaca konfigurasi database, mendeteksi pendaftar tertahan > 7 hari, dan mencatat hasil evaluasi secara aman.",
        "Berhasil"
    ),
    (
        "Deadman System",
        "Memvalidasi proteksi process lock (flock) untuk mencegah eksekusi ganda serentak.",
        "Menjalankan dua instance worker deadman secara bersamaan",
        "Instance kedua mendeteksi lockfile aktif dan keluar dengan aman tanpa eksekusi ganda.",
        "Berhasil"
    ),

    # 7. Informasi dan Pengumuman
    (
        "Informasi & Pengumuman",
        "Memvalidasi form pengumuman dan widget preview pada informasi.php.",
        "Membuka halaman /admin_magang/informasi.php",
        "Form judul, textarea deskripsi, dan card live preview tampil sempurna.",
        "Berhasil"
    ),
    (
        "Informasi & Pengumuman",
        "Memperbarui isi judul dan deskripsi pengumuman penerimaan magang.",
        "Judul: 'Pengumuman Seleksi Magang', Deskripsi: 'Periode pendaftaran dibuka'",
        "Data pengumuman tersimpan di database dan muncul notifikasi sukses pembaruan.",
        "Berhasil"
    ),
    (
        "Informasi & Pengumuman",
        "Memvalidasi live preview merender perubahan judul secara real-time saat diketik.",
        "Ketik teks pada input judul pengumuman",
        "Widget preview di sebelah kanan langsung mencerminkan judul yang diketikkan.",
        "Berhasil"
    ),

    # 8. Manajemen Akun Admin & Super Admin
    (
        "Manajemen Admin",
        "Menambahkan akun administrator baru dengan data valid.",
        "Username: admin_baru, Nama: Admin Uji, Password: password123, Role: Admin",
        "Akun baru tersimpan di tabel admin dan dapat digunakan untuk login ke sistem.",
        "Berhasil"
    ),
    (
        "Manajemen Admin",
        "Mencoba mendaftarkan admin baru dengan username yang sudah terdaftar.",
        "Username: admin (duplikat), Password: password123",
        "Sistem menolak pendaftaran akun dengan pesan 'Username sudah digunakan!'",
        "Berhasil"
    ),
    (
        "Manajemen Admin",
        "Menghapus akun admin biasa yang telah dibuat.",
        "Klik tombol Hapus pada akun admin biasa yang dibuat",
        "Akun berhasil dihapus dari sistem dan baris tabel terhapus.",
        "Berhasil"
    ),
    (
        "Manajemen Admin",
        "Memvalidasi proteksi Super Admin: Mencegah penghapusan akun Super Admin.",
        "Mencoba menghapus akun role 'Super Admin' (admin_btkp)",
        "Backend memblokir aksi penghapusan dan tombol hapus tidak tersedia untuk akun Super Admin.",
        "Berhasil"
    ),
    (
        "Manajemen Admin",
        "Mencegah admin menghapus akun dirinya sendiri yang sedang login.",
        "Mencoba menghapus ID admin yang sedang aktif dalam sesi",
        "Backend menolak penghapusan dengan notifikasi tidak diizinkan menghapus akun sendiri.",
        "Berhasil"
    ),

    # 9. Autentikasi Peserta
    (
        "Autentikasi Peserta",
        "Peserta memasukkan email atau password yang salah pada form login.",
        "Email: peserta_salah@email.com, Password: password_salah",
        "Sistem menolak login, tidak membuat sesi peserta, dan memunculkan alert 'Login Gagal!'",
        "Berhasil"
    ),
    (
        "Autentikasi Peserta",
        "Peserta memasukkan email dan password yang sah.",
        "Email: farras.yassarramadhan@toscaflow.id, Password: valid (abc_123)",
        "Sistem mengarahkan peserta ke portal dashboard peserta (/peserta_magang/index.php).",
        "Berhasil"
    ),
    (
        "Autentikasi Peserta",
        "Peserta melakukan logout dari portal magang.",
        "Klik tautan Logout pada sidebar peserta",
        "Sesi dihancurkan dan pengguna diarahkan ke index.php?pesan=logout.",
        "Berhasil"
    ),
    (
        "Autentikasi Peserta",
        "Mencegah akun peserta mengakses area administrator (/admin_magang/*).",
        "Peserta login mencoba membuka URL /admin_magang/peserta.php",
        "Middleware otorisasi memblokir akses dan mengalihkan pengguna ke index.php?pesan=belum_login.",
        "Berhasil"
    ),

    # 10. Dashboard Peserta & Panduan
    (
        "Dashboard Peserta",
        "Memvalidasi tampilan elemen UI, sambutan, dan widget status pada dashboard peserta.",
        "Buka /peserta_magang/index.php saat login sebagai peserta",
        "Banner sambutan, widget status pendaftaran, dan tombol akses cepat tampil lengkap.",
        "Berhasil"
    ),
    (
        "Dashboard Peserta",
        "Menguji navigasi tombol Akses Cepat pada dashboard peserta.",
        "Klik tautan: Cek Hasil & Unduhan, Buku Panduan, Profil & Form Pendaftaran",
        "Sistem mengarahkan ke unduhan.php, panduan.php, dan pendaftaran.php dengan tepat.",
        "Berhasil"
    ),
    (
        "Buku Panduan",
        "Memvalidasi tampilan alur magang dan komponen Accordion FAQ pada panduan.php.",
        "Buka /peserta_magang/panduan.php dan klik item pertanyaan accordion",
        "Item accordion membuka jawaban pertanyaan secara interaktif dan tautan kembali berfungsi normal.",
        "Berhasil"
    ),

    # 11. Formulir Pendaftaran & Validasi Dokumen PDF
    (
        "Formulir Pendaftaran",
        "Memasukkan tanggal selesai magang lebih awal daripada tanggal mulai magang.",
        "Tanggal Mulai: 2026-12-01, Tanggal Selesai: 2026-11-01",
        "Sistem menolak formulir dengan alert 'Tanggal selesai magang harus lebih besar dari tanggal mulai!'",
        "Berhasil"
    ),
    (
        "Formulir Pendaftaran",
        "Mengunggah berkas surat pengantar berformat non-PDF (misal .jpg atau .docx diubah nama ke .pdf).",
        "File gambar yang diganti nama menjadi surat.pdf",
        "Sistem menolak berkas karena signature magic bytes bukan dokumen PDF valid (%PDF-).",
        "Berhasil"
    ),
    (
        "Formulir Pendaftaran",
        "Mengunggah berkas PDF yang berisi materi kuliah/modul (bukan surat pengantar resmi).",
        "File PDF valid berisi modul Cisco Packet Tracer / bab praktikum",
        "Sistem mendeteksi dokumen salah sasaran dan menolak upload dengan pesan berkas bukan surat pengantar.",
        "Berhasil"
    ),
    (
        "Formulir Pendaftaran",
        "Mengirim formulir pendaftaran lengkap dengan surat pengantar resmi yang valid.",
        "Data diri lengkap, tanggal valid, berkas PDF surat pengantar resmi bertanda tangan",
        "Data pendaftaran tersimpan dengan presisi waktu NOW(3), status 'Menunggu', dan berkas 'Valid Otomatis'.",
        "Berhasil"
    ),
    (
        "Formulir Pendaftaran",
        "Mengajukan pendaftaran tanpa mengunggah proposal magang (Proposal bersifat opsional).",
        "Surat pengantar diunggah, kolom proposal dikosongkan",
        "Pendaftaran tetap berhasil dikirim dan kolom status_proposal tersimpan sebagai 'Tidak Diunggah'.",
        "Berhasil"
    ),
    (
        "Formulir Pendaftaran",
        "Memastikan formulir pendaftaran terkunci setelah pengajuan berhasil disubmit.",
        "Buka kembali halaman /peserta_magang/pendaftaran.php setelah mendaftar",
        "Formulir input tidak ditampilkan lagi, digantikan oleh Card Status Pendaftaran Aktif (Menunggu).",
        "Berhasil"
    ),

    # 12. Cek Posisi Antrean FIFO & Unduhan
    (
        "Cek Posisi & Kuota",
        "Memvalidasi menu navigasi 'Cek Posisi dan Kuota' pada sidebar peserta.",
        "Buka sidebar peserta",
        "Menu dengan label persis 'Cek Posisi dan Kuota' tampil dan terhubung ke posisi_kuota.php.",
        "Berhasil"
    ),
    (
        "Cek Posisi & Kuota",
        "Memvalidasi 4 widget kuota real-time dan kartu posisi antrean peserta.",
        "Buka halaman /peserta_magang/posisi_kuota.php",
        "Widget Kapasitas Maksimal, Kuota Terpakai, Kuota Tersedia, Status Kuota, dan Nomor Urut Peserta tampil akurat.",
        "Berhasil"
    ),
    (
        "Cek Posisi & Kuota",
        "Memvalidasi transparansi antrean FIFO dan kepatuhan privasi data (Data Minimization).",
        "Periksa kolom tabel transparansi antrean pada posisi_kuota.php",
        "Tabel hanya memuat ID Peserta (PST-XXXX), Periode Magang, dan Timestamp; nomor HP, email, dan alamat disembunyikan.",
        "Berhasil"
    ),
    (
        "Unduhan & Hasil",
        "Memvalidasi tampilan halaman unduhan saat peserta belum mendaftar magang.",
        "Akun peserta baru membuka /peserta_magang/unduhan.php",
        "Sistem menampilkan Card 'Belum Ada Data' dengan tombol ajakan 'Daftar Sekarang'.",
        "Berhasil"
    ),
    (
        "Unduhan & Hasil",
        "Memvalidasi tampilan halaman unduhan saat pendaftaran berstatus 'Menunggu'.",
        "Buka unduhan.php saat status peserta masih Menunggu",
        "Status menampilkan 'Menunggu Verifikasi', rentang tanggal pelaksanaan, dan berkas unduhan belum terbuka.",
        "Berhasil"
    ),
    (
        "Unduhan & Hasil",
        "Mengunduh surat balasan resmi saat status peserta telah 'Diterima' (Aktif Magang).",
        "Klik tombol 'Unduh Surat Balasan' pada peserta berstatus Diterima",
        "Sistem menyajikan berkas Surat Balasan resmi untuk diunduh oleh peserta yang bersangkutan.",
        "Berhasil"
    )
]

print(f"Total Test Cases to generate: {len(test_cases)}")

# 1. TULIS KE CSV
with open(csv_path, 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow(headers)
    for idx, tc in enumerate(test_cases, start=1):
        writer.writerow([idx, tc[0], tc[1], tc[2], tc[3], tc[4]])

print(f"[OK] File CSV berhasil dibuat di: {csv_path}")

# 2. TULIS KE EXCEL (.XLSX)
wb = openpyxl.Workbook()
ws = wb.active
ws.title = "Hasil Pengujian"

# Header Font & Fill
header_font = Font(name="Segoe UI", size=11, bold=True, color="FFFFFF")
header_fill = PatternFill(start_color="1F4E78", end_color="1F4E78", fill_type="solid")
header_align = Alignment(horizontal="center", vertical="center", wrap_text=True)

# Data Font & Align
data_font = Font(name="Segoe UI", size=10)
pass_fill = PatternFill(start_color="E2EFDA", end_color="E2EFDA", fill_type="solid")
pass_font = Font(name="Segoe UI", size=10, bold=True, color="375623")

thin_border = Border(
    left=Side(style='thin', color='D9D9D9'),
    right=Side(style='thin', color='D9D9D9'),
    top=Side(style='thin', color='D9D9D9'),
    bottom=Side(style='thin', color='D9D9D9')
)

# Tulis Header
ws.append(headers)
for col_num in range(1, len(headers) + 1):
    cell = ws.cell(row=1, column=col_num)
    cell.font = header_font
    cell.fill = header_fill
    cell.alignment = header_align
    cell.border = thin_border
ws.row_dimensions[1].height = 28

# Tulis Data Rows
for idx, tc in enumerate(test_cases, start=1):
    row_data = [idx, tc[0], tc[1], tc[2], tc[3], tc[4]]
    ws.append(row_data)
    row_idx = idx + 1
    
    for col_idx in range(1, 7):
        cell = ws.cell(row=row_idx, column=col_idx)
        cell.font = data_font
        cell.border = thin_border
        
        # Alignment
        if col_idx == 1:
            cell.alignment = Alignment(horizontal="center", vertical="top")
        elif col_idx in [2, 6]:
            cell.alignment = Alignment(horizontal="center", vertical="top")
            if col_idx == 6 and cell.value == "Berhasil":
                cell.fill = pass_fill
                cell.font = pass_font
        else:
            cell.alignment = Alignment(horizontal="left", vertical="top", wrap_text=True)

# Auto Fit Column Widths
column_widths = {
    'A': 8,   # No
    'B': 24,  # Fitur
    'C': 42,  # Pengujian
    'D': 35,  # Input
    'E': 45,  # Hasil yang diharapkan
    'F': 14   # Status
}

for col_letter, width in column_widths.items():
    ws.column_dimensions[col_letter].width = width

# Bekukan header (Freeze Pane)
ws.freeze_panes = 'A2'

# Aktifkan Auto Filter pada header
ws.auto_filter.ref = f"A1:F{len(test_cases) + 1}"

wb.save(xlsx_path)
print(f"[OK] File Excel (.xlsx) berhasil dibuat di: {xlsx_path}")
