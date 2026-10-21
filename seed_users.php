<?php
// Hubungkan ke database
require_once __DIR__ . '/config/database.php';

echo "<h3>Memulai Pembuatan Akun Tester...</h3>";

// ==========================================
// 1. BUAT AKUN ADMIN
// ==========================================
$admin_seeds = [
    [
        'username' => 'farrasdy',
        'password' => 'abc_123',
        'nama'     => 'Farras Daffa Tester',
        'role'     => 'Admin'
    ],
    [
        'username' => 'administrator',
        'password' => 'abc_123',
        'nama'     => 'Admin Tester Dev',
        'role'     => 'Admin'
    ]
];

foreach ($admin_seeds as $adm) {
    $u = $adm['username'];
    $stmt_c = mysqli_prepare($koneksi, "SELECT username FROM admin WHERE username = ?");
    mysqli_stmt_bind_param($stmt_c, "s", $u);
    mysqli_stmt_execute($stmt_c);
    $res_c = mysqli_stmt_get_result($stmt_c);

    if (mysqli_num_rows($res_c) == 0) {
        $p_hash = password_hash($adm['password'], PASSWORD_DEFAULT);
        $stmt_i = mysqli_prepare($koneksi, "INSERT INTO admin (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_i, "ssss", $u, $p_hash, $adm['nama'], $adm['role']);
        if (mysqli_stmt_execute($stmt_i)) {
            echo "<div style='color:green;'>[SUKSES] Akun Admin '{$u}' berhasil dibuat!</div>";
        } else {
            echo "<div style='color:red;'>[GAGAL] Admin '{$u}': " . mysqli_error($koneksi) . "</div>";
        }
        mysqli_stmt_close($stmt_i);
    } else {
        echo "<div style='color:orange;'>[INFO] Username Admin '{$u}' sudah ada. Lewati.</div>";
    }
    mysqli_stmt_close($stmt_c);
}

echo "<hr>";

// ==========================================
// 2. BUAT AKUN PESERTA
// ==========================================
$peserta_seeds = [
    [
        'nama'     => 'Peserta Tester 2',
        'email'    => 'peserta2@test.com',
        'password' => 'peserta2',
        'hp'       => '08123456789',
        'instansi' => 'Universitas Contoh'
    ]
];

foreach ($peserta_seeds as $pes) {
    $e = $pes['email'];
    $stmt_cp = mysqli_prepare($koneksi, "SELECT email FROM peserta WHERE email = ?");
    mysqli_stmt_bind_param($stmt_cp, "s", $e);
    mysqli_stmt_execute($stmt_cp);
    $res_cp = mysqli_stmt_get_result($stmt_cp);

    if (mysqli_num_rows($res_cp) == 0) {
        $p_hash = password_hash($pes['password'], PASSWORD_DEFAULT);
        $role_p = 'Peserta';
        $foto_p = 'default.jpg';
        $stmt_ip = mysqli_prepare($koneksi, "INSERT INTO peserta (nama, email, password, no_hp, asal_instansi, role, foto_profil) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_ip, "sssssss", $pes['nama'], $e, $p_hash, $pes['hp'], $pes['instansi'], $role_p, $foto_p);
        if (mysqli_stmt_execute($stmt_ip)) {
            echo "<div style='color:green;'>[SUKSES] Akun Peserta '{$e}' berhasil dibuat!</div>";
        } else {
            echo "<div style='color:red;'>[GAGAL] Peserta '{$e}': " . mysqli_error($koneksi) . "</div>";
        }
        mysqli_stmt_close($stmt_ip);
    } else {
        echo "<div style='color:orange;'>[INFO] Email Peserta '{$e}' sudah ada. Lewati.</div>";
    }
    mysqli_stmt_close($stmt_cp);
}

echo "<hr>";
echo "<a href='index.php'>Kembali ke Login</a>";
