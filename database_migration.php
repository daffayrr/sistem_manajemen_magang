<?php
/**
 * Skrip Migrasi Non-Destruktif & Bootstrap Super Admin
 * Sistem Manajemen Penerimaan Magang BTKP DIY
 */
require_once __DIR__ . '/config/database.php';

echo "=== MEMULAI MIGRASI NON-DESTRUKTIF ===\n";

// 1. Perbarui kolom role pada tabel admin jika belum mendukung 'Super Admin'
$check_admin_role = mysqli_query($koneksi, "SHOW COLUMNS FROM `admin` LIKE 'role'");
$col_role = mysqli_fetch_assoc($check_admin_role);
if ($col_role && strpos($col_role['Type'], 'Super Admin') === false) {
    echo "Memperbarui kolom 'role' pada tabel admin...\n";
    $sql = "ALTER TABLE `admin` MODIFY COLUMN `role` ENUM('Admin', 'Super Admin') NOT NULL DEFAULT 'Admin'";
    if (mysqli_query($koneksi, $sql)) {
        echo "[OK] Kolom role tabel admin berhasil diupdate ke ENUM('Admin', 'Super Admin').\n";
    } else {
        echo "[ERROR] Gagal alter role admin: " . mysqli_error($koneksi) . "\n";
    }
} else {
    echo "[INFO] Kolom role tabel admin sudah mendukung Super Admin.\n";
}

// 2. Tingkatkan presisi tgl_daftar pada pendaftaran ke DATETIME(3)
$check_tgl_daftar = mysqli_query($koneksi, "SHOW COLUMNS FROM `pendaftaran` LIKE 'tgl_daftar'");
$col_tgl = mysqli_fetch_assoc($check_tgl_daftar);
if ($col_tgl && strpos($col_tgl['Type'], '(3)') === false) {
    echo "Memperbarui presisi tgl_daftar pada tabel pendaftaran...\n";
    $sql = "ALTER TABLE `pendaftaran` MODIFY COLUMN `tgl_daftar` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)";
    if (mysqli_query($koneksi, $sql)) {
        echo "[OK] Kolom tgl_daftar tabel pendaftaran berhasil diupdate ke DATETIME(3).\n";
    } else {
        echo "[ERROR] Gagal alter tgl_daftar: " . mysqli_error($koneksi) . "\n";
    }
} else {
    echo "[INFO] Kolom tgl_daftar tabel pendaftaran sudah berpresisi milidetik.\n";
}

// 3. Tambahkan kolom completed_at pada pendaftaran jika belum ada
$check_completed_at = mysqli_query($koneksi, "SHOW COLUMNS FROM `pendaftaran` LIKE 'completed_at'");
if (mysqli_num_rows($check_completed_at) == 0) {
    echo "Menambahkan kolom completed_at pada tabel pendaftaran...\n";
    $sql = "ALTER TABLE `pendaftaran` ADD COLUMN `completed_at` DATETIME(3) NULL AFTER `tgl_verifikasi`";
    if (mysqli_query($koneksi, $sql)) {
        echo "[OK] Kolom completed_at berhasil ditambahkan ke tabel pendaftaran.\n";
    } else {
        echo "[ERROR] Gagal menambahkan completed_at: " . mysqli_error($koneksi) . "\n";
    }
} else {
    echo "[INFO] Kolom completed_at sudah ada di tabel pendaftaran.\n";
}

// 4. Buat tabel audit_trail jika belum ada
echo "Memeriksa tabel audit_trail...\n";
$sql_audit = "CREATE TABLE IF NOT EXISTS `audit_trail` (
    `id_audit` INT AUTO_INCREMENT PRIMARY KEY,
    `id_admin` INT NULL,
    `nama_admin` VARCHAR(100) NULL,
    `id_target` INT NULL,
    `tipe_target` VARCHAR(50) NOT NULL,
    `aksi` VARCHAR(50) NOT NULL,
    `status_sebelum` VARCHAR(50) NULL,
    `status_sesudah` VARCHAR(50) NULL,
    `keterangan` TEXT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    INDEX `idx_admin` (`id_admin`),
    INDEX `idx_aksi` (`aksi`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (mysqli_query($koneksi, $sql_audit)) {
    echo "[OK] Tabel audit_trail siap digunakan.\n";
} else {
    echo "[ERROR] Gagal membuat tabel audit_trail: " . mysqli_error($koneksi) . "\n";
}

// 5. Bootstrap Super Admin admin_btkp / abc_123 jika belum ada
echo "Memeriksa akun bootstrap Super Admin...\n";
$check_super = mysqli_query($koneksi, "SELECT id_admin, role FROM `admin` WHERE username = 'admin_btkp'");
if (mysqli_num_rows($check_super) == 0) {
    $hashed_pass = password_hash('abc_123', PASSWORD_DEFAULT);
    $nama_super  = 'Super Admin BTKP';
    $role_super  = 'Super Admin';
    $stmt = mysqli_prepare($koneksi, "INSERT INTO `admin` (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $u, $p, $n, $r);
    $u = 'admin_btkp';
    $p = $hashed_pass;
    $n = $nama_super;
    $r = $role_super;
    if (mysqli_stmt_execute($stmt)) {
        echo "[OK] Akun Super Admin 'admin_btkp' berhasil dibuat dengan aman.\n";
    } else {
        echo "[ERROR] Gagal membuat akun Super Admin: " . mysqli_error($koneksi) . "\n";
    }
    mysqli_stmt_close($stmt);
} else {
    // Pastikan role-nya adalah Super Admin
    mysqli_query($koneksi, "UPDATE `admin` SET role = 'Super Admin' WHERE username = 'admin_btkp'");
    echo "[INFO] Akun 'admin_btkp' sudah ada. Role diverifikasi sebagai Super Admin.\n";
}

echo "=== MIGRASI & BOOTSTRAP SELESAI ===\n";
