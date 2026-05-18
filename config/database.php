<?php
// File: config/database.php

$server   = "localhost";
$username = "root";     // Sesuaikan dengan user database lokal Anda
$password = "abc_123";         // Sesuaikan dengan password database lokal Anda
$database = "db_magang_btkp";

$koneksi = mysqli_connect($server, $username, $password, $database);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// Fungsi Helper untuk URL Absolute (PENTING untuk folder includes)
function base_url($path = "") {
    // Ubah URL di bawah sesuai alamat project Anda di browser
    // Jika pakai IIS biasanya: http://localhost/nama_folder/
    $root_url = "http://localhost/_Tekkomdik/sistem_manajemen_magang/"; 
    return $root_url . $path;
}
?>