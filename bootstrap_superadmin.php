<?php
/**
 * Command Bootstrap Super Admin
 * Sistem Manajemen Penerimaan Magang BTKP DIY
 * 
 * Penggunaan via CLI:
 * php bootstrap_superadmin.php
 */

require_once __DIR__ . '/config/database.php';

echo "=== BOOTSTRAP SUPER ADMIN BTKP ===\n";

$target_user = 'admin_btkp';
$initial_pwd = 'abc_123';
$full_name   = 'Super Admin BTKP';
$role_name   = 'Super Admin';

// 1. Cek apakah akun sudah ada
$stmt_check = mysqli_prepare($koneksi, "SELECT id_admin, username, role FROM `admin` WHERE username = ?");
mysqli_stmt_bind_param($stmt_check, "s", $target_user);
mysqli_stmt_execute($stmt_check);
$res = mysqli_stmt_get_result($stmt_check);

if ($row = mysqli_fetch_assoc($res)) {
    mysqli_stmt_close($stmt_check);
    echo "[INFO] Akun '$target_user' sudah terdaftar (ID: {$row['id_admin']}, Role: {$row['role']}).\n";
    echo "[INFO] Password akun yang sudah ada TIDAK direset secara otomatis untuk menjaga keamanan.\n";
    if ($row['role'] !== 'Super Admin') {
        mysqli_query($koneksi, "UPDATE `admin` SET role = 'Super Admin' WHERE id_admin = {$row['id_admin']}");
        echo "[UPDATE] Role akun '$target_user' berhasil dipromosikan ke 'Super Admin'.\n";
    }
} else {
    mysqli_stmt_close($stmt_check);
    // 2. Buat akun baru dengan password_hash()
    $hash = password_hash($initial_pwd, PASSWORD_DEFAULT);
    $stmt_ins = mysqli_prepare($koneksi, "INSERT INTO `admin` (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt_ins, "ssss", $target_user, $hash, $full_name, $role_name);

    if (mysqli_stmt_execute($stmt_ins)) {
        $new_id = mysqli_insert_id($koneksi);
        catat_audit($koneksi, $new_id, $full_name, $new_id, 'admin', 'BOOTSTRAP', null, 'Super Admin', "Inisialisasi akun bootstrap super admin: $target_user");
        echo "[SUKSES] Akun Super Admin berhasil dibuat!\n";
        echo "Username: $target_user\n";
        echo "Role    : Super Admin\n";
        echo "Status  : Silakan segera ganti password awal setelah login pertama kali.\n";
    } else {
        echo "[ERROR] Gagal membuat akun: " . mysqli_error($koneksi) . "\n";
    }
    mysqli_stmt_close($stmt_ins);
}

echo "=== SELESAI ===\n";
