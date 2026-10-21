<?php
// Hubungkan ke database
include 'config/database.php';

echo "<h3>Memulai Pembuatan Akun Tester...</h3>";

// ==========================================
// 1. BUAT AKUN ADMIN
// ==========================================
$username_admin = "administrator";
$password_admin = md5("abc_123"); // Password: admin123
$nama_admin     = "Admin Tester Dev";
$role_admin     = "Admin";

// Cek dulu apakah username sudah ada agar tidak error duplikat
$cek_admin = mysqli_query($koneksi, "SELECT username FROM admin WHERE username = '$username_admin'");
if (mysqli_num_rows($cek_admin) == 0) {
    
    $query_admin = "INSERT INTO admin (username, password, nama_lengkap, role) 
                    VALUES ('$username_admin', '$password_admin', '$nama_admin', '$role_admin')";
    
    if (mysqli_query($koneksi, $query_admin)) {
        echo "<div style='color:green;'>[SUKSES] Akun Admin berhasil dibuat!</div>";
        echo "<ul>
                <li>Username: <b>$username_admin</b></li>
                <li>Password: <b>admin123</b></li>
              </ul>";
    } else {
        echo "<div style='color:red;'>[GAGAL] Admin: " . mysqli_error($koneksi) . "</div>";
    }

} else {
    echo "<div style='color:orange;'>[INFO] Username Admin '$username_admin' sudah ada. Lewati.</div>";
}

echo "<hr>";

// ==========================================
// 2. BUAT AKUN PESERTA
// ==========================================
$nama_peserta  = "Peserta Tester 2";
$email_peserta = "peserta2@test.com";
$pass_peserta  = md5("peserta2"); // Password: peserta123
$hp_peserta    = "08123456789";
$asal_peserta  = "Universitas Contoh";
$role_peserta  = "Peserta";

// Cek dulu apakah email sudah ada
$cek_peserta = mysqli_query($koneksi, "SELECT email FROM peserta WHERE email = '$email_peserta'");
if (mysqli_num_rows($cek_peserta) == 0) {

    $query_peserta = "INSERT INTO peserta (nama, email, password, no_hp, asal_instansi, role) 
                      VALUES ('$nama_peserta', '$email_peserta', '$pass_peserta', '$hp_peserta', '$asal_peserta', '$role_peserta')";

    if (mysqli_query($koneksi, $query_peserta)) {
        echo "<div style='color:green;'>[SUKSES] Akun Peserta berhasil dibuat!</div>";
        echo "<ul>
                <li>Email: <b>$email_peserta</b></li>
                <li>Password: <b>peserta123</b></li>
              </ul>";
    } else {
        echo "<div style='color:red;'>[GAGAL] Peserta: " . mysqli_error($koneksi) . "</div>";
    }

} else {
    echo "<div style='color:orange;'>[INFO] Email Peserta '$email_peserta' sudah ada. Lewati.</div>";
}

echo "<hr>";
echo "<a href='index.php'>Kembali ke Login</a>";
?>