<?php
// Hubungkan ke database
require_once __DIR__ . '/config/database.php';

// Validasi input form
$nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
$asal_instansi = trim($_POST['asal_instansi'] ?? '');
$no_hp         = trim($_POST['no_hp'] ?? '');
$email         = trim($_POST['email'] ?? '');
$password_raw  = (string) ($_POST['password'] ?? '');

if (empty($nama_lengkap) || empty($email) || empty($password_raw)) {
    header("Location: regs.php?pesan=gagal");
    exit();
}

// 1. Cek Apakah Email Sudah Ada dengan Prepared Statement
$stmt_cek = mysqli_prepare($koneksi, "SELECT id_user FROM peserta WHERE email = ?");
mysqli_stmt_bind_param($stmt_cek, "s", $email);
mysqli_stmt_execute($stmt_cek);
mysqli_stmt_store_result($stmt_cek);

if (mysqli_stmt_num_rows($stmt_cek) > 0) {
    mysqli_stmt_close($stmt_cek);
    header("Location: regs.php?pesan=email_ada");
    exit();
}
mysqli_stmt_close($stmt_cek);

// 2. Enkripsi Password Menggunakan password_hash() Standar Modern
$password_hash = password_hash($password_raw, PASSWORD_DEFAULT);

// 3. Query Simpan Data Peserta
$role_default = 'Peserta';
$foto_default = 'default.jpg';
$stmt_simpan  = mysqli_prepare($koneksi, "INSERT INTO peserta (nama, email, password, no_hp, asal_instansi, role, foto_profil) VALUES (?, ?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt_simpan, "sssssss", $nama_lengkap, $email, $password_hash, $no_hp, $asal_instansi, $role_default, $foto_default);

if (mysqli_stmt_execute($stmt_simpan)) {
    mysqli_stmt_close($stmt_simpan);
    echo "<script>
            alert('Registrasi Berhasil! Silakan Login.');
            window.location.href='index.php';
          </script>";
    exit();
} else {
    mysqli_stmt_close($stmt_simpan);
    header("Location: regs.php?pesan=gagal");
    exit();
}
