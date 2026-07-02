<?php
session_start();
// Hubungkan ke database
include __DIR__ . '/config/database.php';

/** @var mysqli $koneksi */

// Tangkap input dan amankan dari SQL Injection
$username = mysqli_real_escape_string($koneksi, $_POST['username']);
$password = md5($_POST['password']); // Enkripsi password dengan MD5

// ==========================================================
// 1. CEK KE TABEL ADMIN (Login pakai Username)
// ==========================================================
$query_admin = mysqli_query($koneksi, "SELECT * FROM admin WHERE username='$username' AND password='$password'");
$cek_admin   = mysqli_num_rows($query_admin);

if ($cek_admin > 0) {
    $data = mysqli_fetch_assoc($query_admin);

    // Set Session untuk Admin
    $_SESSION['status']      = "login";
    $_SESSION['role_akses']  = "Admin"; // KUNCI UTAMA (Penting untuk dashboard)
    
    // Data Spesifik Admin
    $_SESSION['admin_id']    = $data['id_admin'];
    $_SESSION['admin_nama']  = $data['nama_lengkap']; 
    $_SESSION['admin_role']  = $data['role']; // Isinya 'Admin'

    // Redirect ke Dashboard Admin
    header("Location: admin_magang/dashboard.php");
    exit();
}

// ==========================================================
// 2. CEK KE TABEL PESERTA (Login pakai Email)
// ==========================================================
// Jika tidak ketemu di tabel admin, cek ke tabel peserta
$query_peserta = mysqli_query($koneksi, "SELECT * FROM peserta WHERE email='$username' AND password='$password'");
$cek_peserta   = mysqli_num_rows($query_peserta);

if ($cek_peserta > 0) {
    $data = mysqli_fetch_assoc($query_peserta);

    // Set Session untuk Peserta
    $_SESSION['status']      = "login";
    $_SESSION['role_akses']  = "Peserta"; // KUNCI UTAMA
    
    // Data Spesifik Peserta
    $_SESSION['peserta_id']    = $data['id_user'];
    $_SESSION['peserta_nama']  = $data['nama'];
    $_SESSION['peserta_email'] = $data['email'];
    $_SESSION['peserta_foto']  = $data['foto_profil'];

    // Redirect ke Halaman Utama Peserta
    // (Pastikan folder peserta_magang dan file index.php sudah ada)
    header("Location: peserta_magang/index.php"); 
    exit();
}

// ==========================================================
// 3. JIKA GAGAL LOGIN (Username/Password Salah)
// ==========================================================
header("Location: index.php?pesan=gagal");
exit();
?>