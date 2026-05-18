<?php
session_start();
// Cek jika sudah login, langsung arahkan sesuai role
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    if ($_SESSION['role'] == "Super Admin") {
        header("Location: admin_magang/dashboard.php");
    } else if ($_SESSION['role'] == "Peserta") {
        header("Location: peserta_magang/index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem Magang BTKP DIY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f6f9; 
            font-family: 'Inter', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
        }
        .card-login { 
            width: 100%; 
            max-width: 400px; 
            border: none; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
            border-radius: 12px; 
        }
        .btn-navy { background-color: #0d2141; color: white; transition: 0.3s; }
        .btn-navy:hover { background-color: #1a3a6c; color: white; }
        .form-control:focus { border-color: #0d2141; box-shadow: 0 0 0 0.2rem rgba(13, 33, 65, 0.25); }
    </style>
</head>
<body>

<div class="card card-login bg-white p-4">
    <div class="text-center mb-4">
        <div class="bg-primary text-white rounded p-2 d-inline-flex align-items-center justify-content-center mb-3" 
             style="width:55px; height:55px; background-color: #0d2141 !important;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-building-fill" viewBox="0 0 16 16">
              <path d="M3 0a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h3v-3.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V16h3a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1H3Zm1 2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm3.5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5ZM4 5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1ZM7.5 5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5Zm2.5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1ZM4.5 8h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5Zm2.5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm3.5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5Z"/>
            </svg>
        </div>
        <h4 class="fw-bold text-dark mb-1">Sistem Magang</h4>
        <p class="text-muted small">Balai Teknologi Komunikasi Pendidikan DIY</p>
    </div>

    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] == 'gagal'): ?>
            <div class="alert alert-danger py-2 small text-center mb-3 border-0 bg-danger bg-opacity-10 text-danger">
                Login Gagal! Akun tidak ditemukan.
            </div>
        <?php elseif ($_GET['pesan'] == 'logout'): ?>
            <div class="alert alert-success py-2 small text-center mb-3 border-0 bg-success bg-opacity-10 text-success">
                Anda telah berhasil logout.
            </div>
        <?php elseif ($_GET['pesan'] == 'belum_login'): ?>
            <div class="alert alert-warning py-2 small text-center mb-3 border-0 bg-warning bg-opacity-10 text-warning">
                Silakan login terlebih dahulu.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form action="cek_login.php" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Username / Email</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                <input type="text" name="username" class="form-control border-start-0 ps-0 bg-light" placeholder="Username (Admin) atau Email (Peserta)" required autofocus>
            </div>
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label class="form-label small fw-bold text-muted">Password</label>
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                <input type="password" name="password" class="form-control border-start-0 ps-0 bg-light" placeholder="Masukkan password" required>
            </div>
        </div>
        
        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-navy py-2 fw-bold shadow-sm">MASUK SEKARANG</button>
        </div>
    </form>
    
    <div class="text-center mt-4 pt-3 border-top">
        <small class="text-muted">Belum punya akun peserta?</small><br>
        <a href="regs.php" class="text-decoration-none fw-bold" style="color: #0d2141;">Daftar Akun Baru</a>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>