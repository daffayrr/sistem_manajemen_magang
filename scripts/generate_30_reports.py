import csv
import os
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

# Definisi tepat 30 test case: 20 Admin (ADM-01 s/d ADM-20) dan 10 Peserta (PST-01 s/d PST-10)
test_cases_30 = [
    # ==========================================
    # 20 TEST CASE ADMIN (ADM-01 s/d ADM-20)
    # ==========================================
    {
        "No": 1,
        "Fitur": "Login Admin",
        "Pengujian": "Masukkan username dan password yang valid.",
        "Input": "Kredensial akun Admin pada database pengujian (username: admin, password valid).",
        "Hasil yang diharapkan": "Login berhasil dan pengguna diarahkan ke dashboard sesuai role.",
        "Status": "Berhasil"
    },
    {
        "No": 2,
        "Fitur": "Login Admin",
        "Pengujian": "Masukkan username atau password yang salah.",
        "Input": "Kredensial tidak valid (username: admin_ngawur_99, password salah).",
        "Hasil yang diharapkan": "Login ditolak dan pesan Login Gagal! Akun tidak ditemukan. ditampilkan.",
        "Status": "Berhasil"
    },
    {
        "No": 3,
        "Fitur": "Dashboard Admin",
        "Pengujian": "Periksa informasi sisa kuota dan gunakan tombol Lihat Semua, Verifikasi, serta menu pada bagian Aksi Cepat Admin.",
        "Input": "Admin yang sudah login aktif.",
        "Hasil yang diharapkan": "Informasi kuota tampil dan setiap navigasi membawa pengguna ke halaman yang sesuai.",
        "Status": "Berhasil"
    },
    {
        "No": 4,
        "Fitur": "Verifikasi Peserta",
        "Pengujian": "Cari peserta berdasarkan nama atau instansi yang benar-benar terdaftar.",
        "Input": "Kata kunci instansi terdaftar: 'Alma Ata'.",
        "Hasil yang diharapkan": "Data peserta yang sesuai ditemukan dan ditampilkan.",
        "Status": "Berhasil"
    },
    {
        "No": 5,
        "Fitur": "Verifikasi Peserta",
        "Pengujian": "Cari nama atau instansi yang tidak terdaftar.",
        "Input": "Kata pencarian unik yang tidak terdapat pada data pengujian: 'XYZ_NAMA_TIDAK_ADA_DI_SISTEM_999'.",
        "Hasil yang diharapkan": "Tidak ada hasil dan antarmuka menampilkan pesan Tidak ada data.",
        "Status": "Berhasil"
    },
    {
        "No": 6,
        "Fitur": "Verifikasi Peserta",
        "Pengujian": "Buka tab Menunggu, Diterima (Aktif), Ditolak, dan Arsip atau Selesai sesuai label aplikasi.",
        "Input": "Data fixture yang mencakup masing-masing status.",
        "Hasil yang diharapkan": "Peserta dikelompokkan sesuai status masing-masing dan urutan antrean FIFO konsisten.",
        "Status": "Berhasil"
    },
    {
        "No": 7,
        "Fitur": "Detail Peserta",
        "Pengujian": "Klik tombol Detail pada peserta yang tersedia.",
        "Input": "Satu peserta terdaftar di tabel verifikasi.",
        "Hasil yang diharapkan": "Sistem membuka halaman detail peserta yang benar dan menampilkan informasi sesuai data yang tersimpan.",
        "Status": "Berhasil"
    },
    {
        "No": 8,
        "Fitur": "Unduh Berkas",
        "Pengujian": "Gunakan tombol berkas yang diunggah peserta.",
        "Input": "Peserta dengan berkas surat pengantar PDF uji yang valid.",
        "Hasil yang diharapkan": "Berkas yang benar dapat diunduh dan respons tidak menghasilkan error.",
        "Status": "Berhasil"
    },
    {
        "No": 9,
        "Fitur": "Tombol Tolak",
        "Pengujian": "Klik tombol Tolak pada peserta berstatus Menunggu.",
        "Input": "Peserta berstatus Menunggu yang belum diverifikasi.",
        "Hasil yang diharapkan": "Status peserta berubah menjadi Ditolak dan perubahan tersimpan di database tanpa memengaruhi kuota.",
        "Status": "Berhasil"
    },
    {
        "No": 10,
        "Fitur": "Filter Status",
        "Pengujian": "Pilih setiap filter status yang tersedia.",
        "Input": "Dataset peserta dengan status Menunggu, Diterima, Ditolak, dan Selesai.",
        "Hasil yang diharapkan": "Daftar diperbarui sesuai filter yang dipilih dan hanya menampilkan data yang sesuai.",
        "Status": "Berhasil"
    },
    {
        "No": 11,
        "Fitur": "Persetujuan, FIFO, dan Kuota",
        "Pengujian": "Setujui peserta berstatus Menunggu ketika kuota masih tersedia.",
        "Input": "Peserta valid dalam antrean teratas FIFO dan kuota lebih besar dari nol.",
        "Hasil yang diharapkan": "Status berubah menjadi Diterima, timestamp verifikasi tersimpan, dan sisa kuota berkurang satu.",
        "Status": "Berhasil"
    },
    {
        "No": 12,
        "Fitur": "Penyelesaian Magang dan Kuota",
        "Pengujian": "Gunakan tombol Selesaikan pada peserta aktif dan konfirmasikan tindakan.",
        "Input": "Peserta dengan status Diterima (aktif magang) yang berkas kelulusannya lengkap.",
        "Hasil yang diharapkan": "Status menjadi Selesai, peserta masuk arsip, dan sisa kuota aktif bertambah satu.",
        "Status": "Berhasil"
    },
    {
        "No": 13,
        "Fitur": "Proteksi Kuota",
        "Pengujian": "Coba menyetujui peserta berstatus Menunggu ketika sisa kuota nol.",
        "Input": "Kuota nol (sisa kuota 0) dan peserta berstatus Menunggu.",
        "Hasil yang diharapkan": "Sistem memblokir persetujuan dan menampilkan pesan Gagal! Kuota Magang saat ini Penuh. Tunggu ada peserta yang selesai.",
        "Status": "Berhasil"
    },
    {
        "No": 14,
        "Fitur": "Detail Peserta dan Unggah Surat Balasan",
        "Pengujian": "Unggah dokumen berformat PDF, JPG, atau PNG untuk peserta yang diterima.",
        "Input": "Berkas surat balasan PDF resmi yang valid.",
        "Hasil yang diharapkan": "Berkas tersimpan, metadata tercatat di database, dan tautan untuk membuka dokumen menjadi aktif.",
        "Status": "Berhasil"
    },
    {
        "No": 15,
        "Fitur": "Validasi Unggahan Surat Balasan",
        "Pengujian": "Coba unggah berkas dengan ekstensi yang tidak diizinkan, misalnya DOCX atau ZIP.",
        "Input": "Berkas uji dengan ekstensi terlarang (.docx).",
        "Hasil yang diharapkan": "Sistem menolak unggahan dan menampilkan pesan Format file tidak valid! Gunakan PDF/JPG/PNG.",
        "Status": "Berhasil"
    },
    {
        "No": 16,
        "Fitur": "Pengaturan Kuota",
        "Pengujian": "Ubah kapasitas maksimal melalui halaman konfigurasi kuota.",
        "Input": "Angka kapasitas valid (misalnya 10).",
        "Hasil yang diharapkan": "Nilai kapasitas tersimpan, sisa kuota dihitung ulang secara konsisten, dan notifikasi sukses ditampilkan.",
        "Status": "Berhasil"
    },
    {
        "No": 17,
        "Fitur": "Informasi Umum",
        "Pengujian": "Ubah judul dan deskripsi pengumuman, lalu simpan.",
        "Input": "Judul dan deskripsi baru yang valid.",
        "Hasil yang diharapkan": "Perubahan tersimpan, tanggal modifikasi diperbarui, dan preview menampilkan konten terbaru.",
        "Status": "Berhasil"
    },
    {
        "No": 18,
        "Fitur": "Validasi Informasi Umum",
        "Pengujian": "Kosongkan judul dan isi informasi, lalu coba simpan.",
        "Input": "Field judul dan deskripsi wajib kosong.",
        "Hasil yang diharapkan": "Sistem menampilkan notifikasi validasi bahwa field wajib diisi dan data lama tidak tertimpa.",
        "Status": "Berhasil"
    },
    {
        "No": 19,
        "Fitur": "Manajemen Admin",
        "Pengujian": "Daftarkan akun Admin baru.",
        "Input": "Nama lengkap, username unik, dan password yang memenuhi aturan aplikasi.",
        "Hasil yang diharapkan": "Akun baru berhasil ditambahkan, muncul pada tabel daftar Admin, dan password tersimpan dalam bentuk hash.",
        "Status": "Berhasil"
    },
    {
        "No": 20,
        "Fitur": "Pengaturan Kuota dan Deadman",
        "Pengujian": "Audit dan uji kontrol konfigurasi Deadman pada halaman kuota.php.",
        "Input": "Pengaturan Deadman aktif, ambang batas 7 hari, kebijakan AUDIT_ONLY.",
        "Hasil yang diharapkan": "Pengaturan Deadman dapat dikonfigurasi dari halaman kuota.php, tersimpan dengan benar, dan nilai yang disimpan terbaca kembali setelah halaman dimuat ulang.",
        "Status": "Berhasil"
    },

    # ==========================================
    # 10 TEST CASE PESERTA (PST-01 s/d PST-10)
    # ==========================================
    {
        "No": 21,
        "Fitur": "Sidebar dan Navbar",
        "Pengujian": "Klik menu Keluar atau Logout.",
        "Input": "Peserta yang sudah login aktif.",
        "Hasil yang diharapkan": "Session dihancurkan, pengguna diarahkan ke halaman login, notifikasi logout ditampilkan, dan akses privat ditolak.",
        "Status": "Berhasil"
    },
    {
        "No": 22,
        "Fitur": "Dashboard Peserta",
        "Pengujian": "Buka dashboard setelah login.",
        "Input": "Pengumuman yang telah disimpan oleh Admin pada database.",
        "Hasil yang diharapkan": "Dashboard menampilkan judul dan deskripsi pengumuman terbaru yang berasal dari database.",
        "Status": "Berhasil"
    },
    {
        "No": 23,
        "Fitur": "Dashboard Peserta",
        "Pengujian": "Klik Isi Formulir Pendaftaran ketika peserta belum mendaftar.",
        "Input": "Akun peserta dengan status belum mendaftar.",
        "Hasil yang diharapkan": "Pengguna diarahkan ke halaman formulir pendaftaran (pendaftaran.php).",
        "Status": "Berhasil"
    },
    {
        "No": 24,
        "Fitur": "Panduan Sistem",
        "Pengujian": "Klik salah satu judul pertanyaan pada bagian FAQ.",
        "Input": "Interaksi klik pada komponen accordion FAQ.",
        "Hasil yang diharapkan": "Jawaban terbuka ketika dipilih dan perilaku collapse/expand berjalan sesuai desain komponen.",
        "Status": "Berhasil"
    },
    {
        "No": 25,
        "Fitur": "Formulir Pendaftaran",
        "Pengujian": "Isi data diri, kontak, domisili, instansi, pendamping, periode magang, dan unggah dokumen yang diwajibkan.",
        "Input": "Data lengkap valid dan berkas Surat Pengantar PDF sesuai batasan sistem.",
        "Hasil yang diharapkan": "Data tersimpan di database, sistem memberikan notifikasi sukses, dan status berubah menjadi Menunggu Verifikasi Admin.",
        "Status": "Berhasil"
    },
    {
        "No": 26,
        "Fitur": "Validasi Periode Pendaftaran",
        "Pengujian": "Masukkan tanggal mulai yang lebih besar daripada tanggal selesai.",
        "Input": "Tanggal mulai 1 Desember 2026 dan tanggal selesai 1 November 2026.",
        "Hasil yang diharapkan": "Sistem menolak pengajuan, menampilkan pesan kesalahan tanggal, dan tidak menyimpan pendaftaran yang tidak valid.",
        "Status": "Berhasil"
    },
    {
        "No": 27,
        "Fitur": "Validasi Unggahan Formulir",
        "Pengujian": "Coba unggah berkas yang tidak diizinkan pada field Surat Pengantar.",
        "Input": "Berkas berkstensi gambar (.jpg) alih-alih berkas PDF.",
        "Hasil yang diharapkan": "Sistem menolak unggahan dan menampilkan pesan Format Surat Pengantar harus PDF! serta pendaftaran tidak tersimpan.",
        "Status": "Berhasil"
    },
    {
        "No": 28,
        "Fitur": "Pencegahan Pendaftaran Duplikat",
        "Pengujian": "Buka kembali formulir setelah pendaftaran pertama berstatus Menunggu.",
        "Input": "Akun peserta yang sudah memiliki pengajuan aktif dalam antrean.",
        "Hasil yang diharapkan": "Formulir pendaftaran baru tidak dapat digunakan untuk membuat pengajuan duplikat, antarmuka menampilkan status pengajuan, dan pengiriman langsung ditolak server.",
        "Status": "Berhasil"
    },
    {
        "No": 29,
        "Fitur": "Unduhan dan Hasil Seleksi",
        "Pengujian": "Login sebagai peserta yang statusnya sudah Diterima dan buka halaman unduhan.",
        "Input": "Akun peserta berstatus Diterima dengan surat balasan yang tersedia.",
        "Hasil yang diharapkan": "Tombol unduhan menjadi aktif dan dokumen surat balasan resmi yang sesuai dapat diunduh.",
        "Status": "Berhasil"
    },
    {
        "No": 30,
        "Fitur": "Proteksi Unduhan",
        "Pengujian": "Buka halaman unduhan sebagai peserta yang masih berstatus Menunggu atau belum diterima.",
        "Input": "Akun peserta yang belum memperoleh persetujuan admin.",
        "Hasil yang diharapkan": "Tombol unduhan terkunci, dokumen tidak tersedia, dan antarmuka menampilkan informasi Belum Ada Berkas.",
        "Status": "Berhasil"
    }
]

assert len(test_cases_30) == 30, f"Error: Jumlah test case harus tepat 30, saat ini {len(test_cases_30)}"

output_dir = "reports"
os.makedirs(output_dir, exist_ok=True)
csv_path = os.path.join(output_dir, "hasil_pengujian_blackbox.csv")
xlsx_path = os.path.join(output_dir, "hasil_pengujian_blackbox.xlsx")

headers = ["No", "Fitur", "Pengujian", "Input", "Hasil yang diharapkan", "Status"]

# 1. GENERATE CSV (UTF-8)
with open(csv_path, mode="w", newline="", encoding="utf-8") as f:
    writer = csv.DictWriter(f, fieldnames=headers)
    writer.writeheader()
    for row in test_cases_30:
        writer.writerow(row)

print(f"[OK] File CSV berhasil dibuat di: {csv_path} (Tepat {len(test_cases_30)} baris data)")

# 2. GENERATE EXCEL (.xlsx)
wb = openpyxl.Workbook()
ws = wb.active
ws.title = "Hasil Pengujian"

# Header styling
header_fill = PatternFill(start_color="1F4E79", end_color="1F4E79", fill_type="solid")
header_font = Font(name="Segoe UI", size=11, bold=True, color="FFFFFF")
header_align = Alignment(horizontal="center", vertical="center", wrap_text=True)

# Data row styling
font_data = Font(name="Segoe UI", size=10)
align_left_wrap = Alignment(horizontal="left", vertical="center", wrap_text=True)
align_center = Alignment(horizontal="center", vertical="center")
thin_border = Border(
    left=Side(style='thin', color='D9D9D9'),
    right=Side(style='thin', color='D9D9D9'),
    top=Side(style='thin', color='D9D9D9'),
    bottom=Side(style='thin', color='D9D9D9')
)

fill_success = PatternFill(start_color="E2EFDA", end_color="E2EFDA", fill_type="solid")
font_success = Font(name="Segoe UI", size=10, bold=True, color="375623")

# Write Header
ws.append(headers)
for col_num in range(1, len(headers) + 1):
    cell = ws.cell(row=1, column=col_num)
    cell.fill = header_fill
    cell.font = header_font
    cell.alignment = header_align
    cell.border = thin_border
ws.row_dimensions[1].height = 28

# Write Data
for r_idx, row_data in enumerate(test_cases_30, start=2):
    ws.append([
        row_data["No"],
        row_data["Fitur"],
        row_data["Pengujian"],
        row_data["Input"],
        row_data["Hasil yang diharapkan"],
        row_data["Status"]
    ])
    ws.row_dimensions[r_idx].height = 42

    for c_idx in range(1, len(headers) + 1):
        cell = ws.cell(row=r_idx, column=c_idx)
        cell.font = font_data
        cell.border = thin_border

        if c_idx == 1:
            cell.alignment = align_center
        elif c_idx in [2, 6]:
            cell.alignment = align_center
            if c_idx == 6:
                cell.fill = fill_success
                cell.font = font_success
        else:
            cell.alignment = align_left_wrap

# Auto Column Widths
col_widths = {
    "A": 8,   # No
    "B": 24,  # Fitur
    "C": 42,  # Pengujian
    "D": 38,  # Input
    "E": 48,  # Hasil yang diharapkan
    "F": 16   # Status
}
for col_letter, width in col_widths.items():
    ws.column_dimensions[col_letter].width = width

# Enable Auto Filter on headers
ws.auto_filter.ref = f"A1:F{len(test_cases_30) + 1}"

# Freeze Header Row (Row 1)
ws.freeze_panes = "A2"

wb.save(xlsx_path)
print(f"[OK] File Excel (.xlsx) berhasil dibuat di: {xlsx_path} (Worksheet: 'Hasil Pengujian', Tepat {len(test_cases_30)} baris data)")
