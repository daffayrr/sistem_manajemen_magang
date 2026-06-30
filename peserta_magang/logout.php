<?php
session_start();

// 1. Kosongkan semua variabel session
$_SESSION = [];

// 2. Hapus session dari memori
session_unset();

// 3. Hancurkan session sepenuhnya
session_destroy();

// 4. Arahkan kembali ke Halaman Login Utama (di folder luar/root)
// Menggunakan ../ karena posisi file ini ada di dalam folder admin_magang
header("Location: ../index.php?pesan=logout");
exit();
?>