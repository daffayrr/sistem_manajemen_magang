<?php
// Hubungkan ke database
include 'config/database.php';

// Tangkap data dari form
$nama_lengkap  = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
$asal_instansi = mysqli_real_escape_string($koneksi, $_POST['asal_instansi']);
$no_hp         = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
$email         = mysqli_real_escape_string($koneksi, $_POST['email']);
$password_raw  = $_POST['password'];

// 1. Cek Apakah Email Sudah Ada?
$cek_email = mysqli_query($koneksi, "SELECT email FROM peserta WHERE email = '$email'");
if (mysqli_num_rows($cek_email) > 0) {
    // Jika email sudah ada, kembalikan ke form dengan pesan error
    header("Location: regs.php?pesan=email_ada");
    exit();
}

// 2. Enkripsi Password (MD5 sesuai db lama)
$password_hash = md5($password_raw);

// 3. Query Simpan Data
// Kita set role default = 'Peserta' dan foto_profil = 'default.jpg'
$query_simpan = "INSERT INTO peserta (nama, email, password, no_hp, asal_instansi, role, foto_profil) 
                 VALUES ('$nama_lengkap', '$email', '$password_hash', '$no_hp', '$asal_instansi', 'Peserta', 'default.jpg')";

if (mysqli_query($koneksi, $query_simpan)) {
    // Jika Berhasil, arahkan ke Login dengan pesan sukses
    // Kita buat pesan khusus agar user tahu registrasi berhasil
    echo "<script>
            alert('Registrasi Berhasil! Silakan Login.');
            window.location.href='index.php';
          </script>";
} else {
    // Jika Gagal Query
    header("Location: regs.php?pesan=gagal");
}
?>