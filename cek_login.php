<?php
session_start();
// Hubungkan ke database
require_once __DIR__ . '/config/database.php';

/** @var mysqli $koneksi */

// Validasi keberadaan input
if (empty($_POST['username']) || empty($_POST['password'])) {
    header("Location: index.php?pesan=gagal");
    exit();
}

$username       = trim($_POST['username']);
$password_input = (string) $_POST['password'];

// ==========================================================
// 1. CEK KE TABEL ADMIN (Login pakai Username, Prepared Statement)
// ==========================================================
$stmt_admin = mysqli_prepare($koneksi, "SELECT id_admin, username, password, nama_lengkap, role FROM `admin` WHERE username = ?");
if ($stmt_admin) {
    mysqli_stmt_bind_param($stmt_admin, "s", $username);
    mysqli_stmt_execute($stmt_admin);
    $result_admin = mysqli_stmt_get_result($stmt_admin);
    
    if ($result_admin && mysqli_num_rows($result_admin) > 0) {
        $data = mysqli_fetch_assoc($result_admin);
        mysqli_stmt_close($stmt_admin);

        $db_pass = $data['password'];
        $is_valid = false;

        // Verifikasi password (password_verify atau fallback legacy MD5)
        if (password_verify($password_input, $db_pass)) {
            $is_valid = true;
        } elseif (strlen($db_pass) === 32 && md5($password_input) === $db_pass) {
            $is_valid = true;
            // Migrasi otomatis ke modern password_hash
            $new_hash = password_hash($password_input, PASSWORD_DEFAULT);
            $rehash_stmt = mysqli_prepare($koneksi, "UPDATE `admin` SET password = ? WHERE id_admin = ?");
            if ($rehash_stmt) {
                mysqli_stmt_bind_param($rehash_stmt, "si", $new_hash, $data['id_admin']);
                mysqli_stmt_execute($rehash_stmt);
                mysqli_stmt_close($rehash_stmt);
            }
        }

        if ($is_valid) {
            // Mencegah Session Fixation
            session_regenerate_id(true);

            $_SESSION['status']     = "login";
            $_SESSION['role_akses'] = "Admin"; // Konsisten untuk seluruh halaman admin
            $_SESSION['admin_id']   = (int) $data['id_admin'];
            $_SESSION['admin_nama'] = $data['nama_lengkap']; 
            $_SESSION['admin_role'] = $data['role']; // 'Admin' atau 'Super Admin'
            $_SESSION['role']       = $data['role']; // 'Admin' atau 'Super Admin'

            // Catat audit login
            catat_audit($koneksi, $data['id_admin'], $data['nama_lengkap'], $data['id_admin'], 'admin', 'LOGIN', null, 'login', 'Admin login ke sistem');

            header("Location: admin_magang/dashboard.php");
            exit();
        }
    } else {
        mysqli_stmt_close($stmt_admin);
    }
}

// ==========================================================
// 2. CEK KE TABEL PESERTA (Login pakai Email, Prepared Statement)
// ==========================================================
$stmt_peserta = mysqli_prepare($koneksi, "SELECT id_user, nama, email, password, foto_profil, role FROM `peserta` WHERE email = ?");
if ($stmt_peserta) {
    mysqli_stmt_bind_param($stmt_peserta, "s", $username);
    mysqli_stmt_execute($stmt_peserta);
    $result_peserta = mysqli_stmt_get_result($stmt_peserta);

    if ($result_peserta && mysqli_num_rows($result_peserta) > 0) {
        $data = mysqli_fetch_assoc($result_peserta);
        mysqli_stmt_close($stmt_peserta);

        $db_pass = $data['password'];
        $is_valid = false;

        if (password_verify($password_input, $db_pass)) {
            $is_valid = true;
        } elseif (strlen($db_pass) === 32 && md5($password_input) === $db_pass) {
            $is_valid = true;
            $new_hash = password_hash($password_input, PASSWORD_DEFAULT);
            $rehash_stmt = mysqli_prepare($koneksi, "UPDATE `peserta` SET password = ? WHERE id_user = ?");
            if ($rehash_stmt) {
                mysqli_stmt_bind_param($rehash_stmt, "si", $new_hash, $data['id_user']);
                mysqli_stmt_execute($rehash_stmt);
                mysqli_stmt_close($rehash_stmt);
            }
        }

        if ($is_valid) {
            session_regenerate_id(true);

            $_SESSION['status']        = "login";
            $_SESSION['role_akses']    = "Peserta";
            $_SESSION['role']          = "Peserta";
            $_SESSION['peserta_id']    = (int) $data['id_user'];
            $_SESSION['peserta_nama']  = $data['nama'];
            $_SESSION['peserta_email'] = $data['email'];
            $_SESSION['peserta_foto']  = $data['foto_profil'];

            header("Location: peserta_magang/index.php"); 
            exit();
        }
    } else {
        mysqli_stmt_close($stmt_peserta);
    }
}

// ==========================================================
// 3. JIKA GAGAL LOGIN (Username/Email atau Password Salah)
// ==========================================================
header("Location: index.php?pesan=gagal");
exit();
