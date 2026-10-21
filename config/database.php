<?php
// File: config/database.php

$server   = "localhost";
$username = "root";     // Sesuaikan dengan user database lokal Anda
$password = "abc_123";  // Sesuaikan dengan password database lokal Anda
$database = "db_magang_btkp";

$koneksi = mysqli_connect($server, $username, $password, $database);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// Fungsi Helper untuk URL Absolute (PENTING untuk folder includes)
function base_url($path = "") {
    // Ubah URL di bawah sesuai alamat project Anda di browser
    $root_url = "http://localhost/_Tekkomdik/sistem_manajemen_magang/"; 
    return $root_url . $path;
}

// CSRF Protection Helpers
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Audit Trail Helper
function catat_audit($koneksi, $id_admin, $nama_admin, $id_target, $tipe_target, $aksi, $status_sebelum = null, $status_sesudah = null, $keterangan = null) {
    $stmt = mysqli_prepare($koneksi, "INSERT INTO audit_trail (id_admin, nama_admin, id_target, tipe_target, aksi, status_sebelum, status_sesudah, keterangan, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(3))");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isisssss", $id_admin, $nama_admin, $id_target, $tipe_target, $aksi, $status_sebelum, $status_sesudah, $keterangan);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
