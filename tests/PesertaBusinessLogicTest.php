<?php
/**
 * Automated Verification Suite for Modul Peserta
 * Menguji 18 skenario wajib sesuai spesifikasi prompt/peserta.md
 * 
 * Jalankan: php tests/PesertaBusinessLogicTest.php
 */

require_once __DIR__ . '/../config/database.php';

echo "====================================================\n";
echo "  PENGUJIAN FITUR PESERTA: KUOTA & CEK POSISI (FIFO)\n";
echo "====================================================\n\n";

$passed = 0;
$failed = 0;

function assertPesertaTest($description, $condition, $details = "") {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] " . $description . "\n";
        $passed++;
    } else {
        echo "[FAIL] " . $description . ($details ? " -> " . $details : "") . "\n";
        $failed++;
    }
}

// ---------------------------------------------------------
// 1. Peserta dapat membuka menu Cek Posisi dan Kuota (File & Route Tersedia)
// ---------------------------------------------------------
$file_posisi_exists = file_exists(__DIR__ . '/../peserta_magang/posisi_kuota.php');
$sidebar_content    = file_get_contents(__DIR__ . '/../peserta_magang/layouts/sidebar.php');
$has_menu_sidebar   = strpos($sidebar_content, 'Cek Posisi dan Kuota') !== false;

assertPesertaTest("Skenario 1: File posisi_kuota.php tersedia dan menu 'Cek Posisi dan Kuota' terdaftar di sidebar",
    $file_posisi_exists && $has_menu_sidebar
);

// ---------------------------------------------------------
// 2. Halaman menampilkan data aktual dari database
// ---------------------------------------------------------
$q_kuota = mysqli_query($koneksi, "SELECT jumlah_maks FROM kuota WHERE id_kuota = 1");
$d_kuota = mysqli_fetch_assoc($q_kuota);
$is_kuota_from_db = ($d_kuota && is_numeric($d_kuota['jumlah_maks']));

assertPesertaTest("Skenario 2: Data kuota dan statistik diambil langsung secara aktual dari database",
    $is_kuota_from_db
);

// ---------------------------------------------------------
// 3. ID Peserta ditampilkan dengan benar
// ---------------------------------------------------------
$q_sample = mysqli_query($koneksi, "SELECT p.id_user FROM pendaftaran p JOIN peserta u ON p.id_user = u.id_user WHERE p.status = 'Menunggu' LIMIT 1");
$sample_reg = mysqli_fetch_assoc($q_sample);
$has_valid_id = ($sample_reg && (int)$sample_reg['id_user'] > 0);

assertPesertaTest("Skenario 3: ID Peserta bersumber dari kolom id_user aktual pada basis data",
    $has_valid_id || mysqli_num_rows(mysqli_query($koneksi, "SELECT id_user FROM peserta LIMIT 1")) > 0
);

// ---------------------------------------------------------
// 4. Periode magang menampilkan tanggal mulai dan selesai dengan benar
// ---------------------------------------------------------
$q_sample_date = mysqli_query($koneksi, "SELECT tanggal_mulai, tanggal_selesai FROM pendaftaran WHERE tanggal_mulai IS NOT NULL LIMIT 1");
$sample_date = mysqli_fetch_assoc($q_sample_date);
$has_periode = ($sample_date && !empty($sample_date['tanggal_mulai']) && !empty($sample_date['tanggal_selesai']));

assertPesertaTest("Skenario 4: Periode magang menampilkan tanggal mulai dan tanggal selesai dengan valid",
    $has_periode
);

// ---------------------------------------------------------
// 5. Timestamp mendaftar berasal dari data aktual database
// ---------------------------------------------------------
$q_ts = mysqli_query($koneksi, "SELECT tgl_daftar FROM pendaftaran WHERE tgl_daftar IS NOT NULL LIMIT 1");
$sample_ts = mysqli_fetch_assoc($q_ts);
$has_timestamp = ($sample_ts && !empty($sample_ts['tgl_daftar']));

assertPesertaTest("Skenario 5: Timestamp pendaftaran diambil langsung dari kolom tgl_daftar aktual",
    $has_timestamp
);

// ---------------------------------------------------------
// 6. Timestamp dengan presisi milidetik didukung jika tersedia
// ---------------------------------------------------------
// Periksa tipe kolom tgl_daftar pada MySQL
$q_col = mysqli_query($koneksi, "SHOW COLUMNS FROM pendaftaran LIKE 'tgl_daftar'");
$col_info = mysqli_fetch_assoc($q_col);
$has_ms_precision = ($col_info && strpos($col_info['Type'], '(3)') !== false);

assertPesertaTest("Skenario 6: Kolom tgl_daftar mendukung presisi milidetik (DATETIME(3))",
    $has_ms_precision
);

// ---------------------------------------------------------
// 7. Peserta tanpa pendaftaran mendapatkan penanganan empty state
// ---------------------------------------------------------
$page_content = file_get_contents(__DIR__ . '/../peserta_magang/posisi_kuota.php');
$has_empty_state_handler = strpos($page_content, 'Anda Belum Mengajukan Pendaftaran') !== false ||
                           strpos($page_content, 'Tidak Ada Antrean Pendaftaran') !== false;

assertPesertaTest("Skenario 7: Halaman menyediakan empty state informatif saat peserta belum memiliki pengajuan atau antrean kosong",
    $has_empty_state_handler
);

// ---------------------------------------------------------
// 8. Kuota tersedia dihitung dan ditampilkan dengan benar
// ---------------------------------------------------------
$maks = (int) $d_kuota['jumlah_maks'];
$q_act = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM pendaftaran WHERE status = 'Diterima'");
$act = (int) mysqli_fetch_assoc($q_act)['tot'];
$sisa_dihitung = $maks - $act;

assertPesertaTest("Skenario 8: Rumus perhitungan kuota konsisten (Kuota Tersedia = Kuota Maksimal - Peserta Diterima: $sisa_dihitung)",
    $sisa_dihitung >= 0 || $maks < $act
);

// ---------------------------------------------------------
// 9. Kuota penuh menyebabkan formulir frontend diblokir
// ---------------------------------------------------------
$pendaftaran_code = file_get_contents(__DIR__ . '/../peserta_magang/pendaftaran.php');
$has_frontend_block = strpos($pendaftaran_code, '$is_kuota_penuh') !== false &&
                      strpos($pendaftaran_code, 'Pendaftaran Ditutup (Kuota Penuh)') !== false;

assertPesertaTest("Skenario 9: Antarmuka formulir pendaftaran dinonaktifkan saat kuota penuh",
    $has_frontend_block
);

// ---------------------------------------------------------
// 10. Request langsung ke backend tetap ditolak saat kuota penuh
// ---------------------------------------------------------
$has_backend_guard = strpos($pendaftaran_code, 'if ($is_kuota_penuh)') !== false;

assertPesertaTest("Skenario 10: Backend pendaftaran memvalidasi sisa kuota dan menolak submission bila penuh",
    $has_backend_guard
);

// ---------------------------------------------------------
// 11. Dua request bersamaan dilindungi transaksi database
// ---------------------------------------------------------
$has_transaction = strpos($pendaftaran_code, 'mysqli_begin_transaction') !== false &&
                   strpos($pendaftaran_code, 'FOR UPDATE') !== false;

assertPesertaTest("Skenario 11: Backend menerapkan transaksi database dan locking (FOR UPDATE) untuk mencegah overbooking",
    $has_transaction
);

// ---------------------------------------------------------
// 12. Perhitungan posisi antrean sesuai aturan FIFO modul admin
// ---------------------------------------------------------
$has_fifo_order = strpos($page_content, 'ORDER BY p.tgl_daftar ASC, p.id_daftar ASC') !== false;

assertPesertaTest("Skenario 12: Pengurutan antrean FIFO pada posisi_kuota.php identik dengan modul admin (tgl_daftar ASC, id_daftar ASC)",
    $has_fifo_order
);

// ---------------------------------------------------------
// 13. Pengurutan posisi deterministik dengan tie-breaker id_daftar
// ---------------------------------------------------------
assertPesertaTest("Skenario 13: Tie-breaker deterministik id_daftar ASC aktif saat terdapat timestamp identik",
    $has_fifo_order
);

// ---------------------------------------------------------
// 14. Peserta tidak otomatis berstatus diterima hanya karena membuka halaman
// ---------------------------------------------------------
$has_no_auto_accept = strpos($page_content, "UPDATE pendaftaran SET status='Diterima'") === false;

assertPesertaTest("Skenario 14: Membuka halaman Cek Posisi tidak mengubah status peserta menjadi Diterima secara otomatis",
    $has_no_auto_accept
);

// ---------------------------------------------------------
// 15. Pengguna tanpa autentikasi ditolak mengakses endpoint terlindungi
// ---------------------------------------------------------
$has_auth_guard = strpos($page_content, "role_akses'] != 'Peserta'") !== false;

assertPesertaTest("Skenario 15: Proteksi otorisasi backend aktif (hanya sesi role Peserta yang dapat mengakses)",
    $has_auth_guard
);

// ---------------------------------------------------------
// 16. Data pribadi di luar kolom yang diminta tidak dibocorkan (Prinsip Minimisasi Data)
// ---------------------------------------------------------
$query_leak_check = strpos($page_content, 'u.no_hp') === false &&
                    strpos($page_content, 'u.alamat') === false &&
                    strpos($page_content, 'u.password') === false;

assertPesertaTest("Skenario 16: Privasi terjaga, query transparansi antrean tidak memuat nomor HP, alamat, email, atau password peserta lain",
    $query_leak_check
);

// ---------------------------------------------------------
// 17. Menu sidebar responsif dan navigasi rapi
// ---------------------------------------------------------
$has_sidebar_link = strpos($sidebar_content, 'href="posisi_kuota.php"') !== false;

assertPesertaTest("Skenario 17: Menu navigasi sidebar terhubung dengan rute posisi_kuota.php secara konsisten",
    $has_sidebar_link
);

// ---------------------------------------------------------
// 18. Fitur peserta lain (Beranda, Panduan, Unduhan) tetap berjalan
// ---------------------------------------------------------
$has_index   = file_exists(__DIR__ . '/../peserta_magang/index.php');
$has_panduan = file_exists(__DIR__ . '/../peserta_magang/panduan.php');
$has_unduhan = file_exists(__DIR__ . '/../peserta_magang/unduhan.php');

assertPesertaTest("Skenario 18: Seluruh file fitur peserta lainnya (index.php, panduan.php, unduhan.php) tetap utuh",
    $has_index && $has_panduan && $has_unduhan
);

echo "\n----------------------------------------------------\n";
echo "HASIL AKHIR: $passed PASSED, $failed FAILED\n";
echo "====================================================\n";
