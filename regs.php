<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Peserta Magang - BTKP DIY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f6f9; 
            font-family: 'Inter', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            padding: 20px;
        }
        .card-reg { 
            width: 100%; 
            max-width: 500px; 
            border: none; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
            border-radius: 12px; 
        }
        .btn-navy { background-color: #0d2141; color: white; transition: 0.3s; }
        .btn-navy:hover { background-color: #1a3a6c; color: white; }
        .form-label { font-size: 0.85rem; font-weight: 600; color: #6c757d; }
    </style>
</head>
<body>

<div class="card card-reg bg-white p-4">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-dark mb-1">Buat Akun Peserta</h4>
        <p class="text-muted small">Silakan lengkapi data diri Anda untuk mendaftar magang.</p>
    </div>

    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] == 'email_ada'): ?>
            <div class="alert alert-danger py-2 small text-center mb-3 border-0 bg-danger bg-opacity-10 text-danger">
                Email sudah terdaftar! Gunakan email lain.
            </div>
        <?php elseif ($_GET['pesan'] == 'gagal'): ?>
            <div class="alert alert-danger py-2 small text-center mb-3 border-0 bg-danger bg-opacity-10 text-danger">
                Terjadi kesalahan sistem. Coba lagi.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form action="proses_regs.php" method="POST">
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control bg-light" placeholder="Contoh: Budi Santoso" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Asal Instansi (Kampus/Sekolah)</label>
            <input type="text" name="asal_instansi" class="form-control bg-light" placeholder="Contoh: Universitas Alma Ata" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">No. Handphone (WA)</label>
                <input type="number" name="no_hp" class="form-control bg-light" placeholder="08xxxxxxxx" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email Aktif</label>
                <input type="email" name="email" class="form-control bg-light" placeholder="email@anda.com" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control bg-light" placeholder="Buat password yang aman" required>
            <div class="form-text small">Minimal 6 karakter.</div>
        </div>
        
        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-navy py-2 fw-bold shadow-sm">DAFTAR SEKARANG</button>
        </div>
    </form>
    
    <div class="text-center mt-4 pt-3 border-top">
        <small class="text-muted">Sudah punya akun?</small><br>
        <a href="index.php" class="text-decoration-none fw-bold" style="color: #0d2141;">Login di sini</a>
    </div>
</div>

</body>
</html>