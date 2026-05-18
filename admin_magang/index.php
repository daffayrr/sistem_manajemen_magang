<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Magang BTKP DIY</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* =========================================================
           STYLE NAVY BLUE - SESUAI REFERENSI VISUAL CBT
           ========================================================= */
        :root {
            --sidebar-width: 260px;
            --header-height: 70px;
            --footer-height: 60px;
            
            /* Warna Utama: Navy Blue */
            --primary-navy: #0d2141; 
            --active-blue: #e3f2fd;
            --bg-body: #f4f6f9;
            --bg-card: #ffffff;
            --bg-sidebar: #ffffff;
            --text-main: #343a40;
            --text-muted: #6c757d;
            --border-color: #e9ecef;
            --input-bg: #ffffff;
            --input-border: #dee2e6;
            --table-hover: #f8f9fa;
            --shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        /* Global Reset */
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-body); 
            color: var(--text-main);
            font-size: 0.9rem;
            overflow-x: hidden;
        }
        a { text-decoration: none; }

        /* Sidebar Style */
        .sidebar { 
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background-color: var(--bg-sidebar); 
            border-right: 1px solid var(--border-color); 
            z-index: 1040;
            padding: 20px 15px;
            display: flex; flex-direction: column;
            overflow-y: auto;
        }

        .sidebar-brand { 
            font-weight: 800; font-size: 1.25rem; color: var(--primary-navy); 
            margin-bottom: 30px; padding-left: 10px; 
            display: flex; align-items: center; gap: 10px; 
        }
        
        .nav-link { 
            color: var(--text-muted); font-weight: 500; 
            padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; 
            transition: all 0.2s; display: flex; align-items: center; 
        }
        .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .nav-link:hover { background-color: var(--table-hover); color: var(--primary-navy); }
        .nav-link.active { background-color: #0d2141; color: white !important; box-shadow: 0 4px 6px rgba(13, 33, 65, 0.2); }

        /* Header Style */
        .top-header { 
            position: fixed; top: 0; left: var(--sidebar-width); 
            width: calc(100% - var(--sidebar-width)); height: var(--header-height);
            background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); 
            display: flex; align-items: center; justify-content: space-between; padding: 0 30px;
            z-index: 1030;
        }

        /* Main Content Style */
        .main-content {
            margin-top: var(--header-height); margin-left: var(--sidebar-width); 
            padding: 30px; min-height: calc(100vh - var(--header-height));
            padding-bottom: calc(var(--footer-height) + 30px);
        }

        /* Cards & Widgets */
        .card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; box-shadow: var(--shadow); }
        .card-header { background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); color: var(--text-main); font-weight: 600;}
        
        .card-stat { background-color: var(--bg-card); border: none; border-radius: 10px; box-shadow: var(--shadow); height: 100%; transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-3px); }
        .icon-box { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

        /* Action Buttons */
        .btn-quick { 
            background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); 
            padding: 15px; border-radius: 8px; text-align: left; transition: all 0.2s ease; display: block; font-weight: 500; 
        }
        .btn-quick:hover { border-color: var(--primary-navy); background-color: var(--table-hover); color: var(--primary-navy); transform: translateX(5px); }

        /* Footer */
        .app-footer {
            position: fixed; bottom: 0; left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width)); height: var(--footer-height);
            background-color: var(--bg-card); border-top: 1px solid var(--border-color);
            display: flex; align-items: center; justify-content: center;
            z-index: 1030; color: var(--text-muted); font-size: 0.85rem;
        }
        
        /* Mobile Responsive */
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .top-header { left: 0; width: 100%; }
            .app-footer { left: 0; width: 100%; }
            .main-content { margin-left: 0; }
            .sidebar.show { transform: translateX(0); box-shadow: 0 0 15px rgba(0,0,0,0.5); }
        }
    </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
    <a href="dashboard.php" class="text-decoration-none">
        <div class="sidebar-brand">
            <div class="bg-primary text-white rounded p-1 d-flex align-items-center justify-content-center" style="width:35px; height:35px; background-color: #0d2141 !important;">
                <i class="fa-solid fa-building-columns"></i>
            </div>
            <span>Magang <span class="text-primary">BTKP</span></span>
        </div>
    </a>
    
    <ul class="nav flex-column mb-auto">
        <li class="px-4 mt-2 mb-2 text-uppercase small text-muted fw-bold" style="font-size: 0.7rem;">Main Menu</li>
        
        <li class="nav-item">
            <a class="nav-link active" href="dashboard.php">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </li>
        
        <li class="px-4 mt-4 mb-2 text-uppercase small text-muted fw-bold" style="font-size: 0.7rem;">Manajemen Magang</li>

        <li class="nav-item">
            <a class="nav-link" href="peserta.php">
                <i class="fa-solid fa-users-viewfinder"></i> Verifikasi Peserta
                <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 0.7rem;">3 Baru</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="kuota.php">
                <i class="fa-solid fa-chart-pie"></i> Kuota & Posisi
            </a>
        </li>

        <li class="px-4 mt-4 mb-2 text-uppercase small text-muted fw-bold" style="font-size: 0.7rem;">Konten & Sistem</li>

        <li class="nav-item">
            <a class="nav-link" href="informasi.php">
                <i class="fa-solid fa-bullhorn"></i> Informasi Umum
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="admin.php">
                <i class="fa-solid fa-user-shield"></i> Manajemen Admin
            </a>
        </li>
    </ul>

    <div class="px-3 pb-4 mt-4">
        <a href="logout.php" class="nav-link text-danger fw-bold border border-danger rounded-3 justify-content-center">
            <i class="fa-solid fa-right-from-bracket"></i> Keluar
        </a>
    </div>
</div>

<div class="top-header">
    <div class="d-flex align-items-center">
        <button type="button" class="btn btn-link d-lg-none me-3 p-0 text-decoration-none" onclick="toggleSidebar()" style="color: var(--text-main);">
            <i class="fa-solid fa-bars fs-4"></i>
        </button>

        <div>
            <h5 class="fw-bold mb-0" style="color: var(--text-main);">Dashboard Admin</h5>
            <small class="text-muted">Balai Teknologi Komunikasi Pendidikan DIY</small>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <div class="d-none d-md-block text-end">
            <div class="fw-bold small" style="color: var(--text-main);">Sabtu, 27 Desember 2025</div>
            <div id="live-clock" class="badge border rounded-pill px-3" style="color: var(--text-main); border-color: var(--border-color) !important;">
                07:00:13 WIB
            </div>
        </div>

        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <div class="bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; background-color: #0d2141 !important;">
                    A
                </div>
                <div class="d-none d-lg-block text-start lh-sm">
                    <div class="fw-bold small" style="color: var(--text-main);">Admin TU</div>
                    <small class="text-muted" style="font-size: 11px;">Administrator</small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow mt-2">
                <li><a class="dropdown-item" href="admin.php">Pengaturan Akun</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="main-content">
    
    <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4 p-4" style="background-color: #e3f2fd; color: #0d2141; border-radius: 12px;">
        <i class="fa-solid fa-circle-info fa-2x me-3"></i>
        <div>
            <h5 class="fw-bold mb-1">Metode FIFO Aktif!</h5>
            <p class="mb-0 small opacity-75">Sistem akan mengurutkan pendaftar berdasarkan <b>Waktu Masuk (Timestamp)</b>. Harap verifikasi peserta dari urutan teratas.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card card-stat p-3 h-100 border-start border-4 border-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-bold text-uppercase">Sisa Kuota Magang</div>
                                <h2 class="fw-bold mb-0 text-dark">5 <span class="fs-6 text-muted">/ 10</span></h2>
                                <small class="text-success small"><i class="fa-solid fa-check"></i> Masih Tersedia</small>
                            </div>
                            <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-chart-pie"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card card-stat p-3 h-100 border-start border-4 border-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-bold text-uppercase">Menunggu Verifikasi</div>
                                <h2 class="fw-bold mb-0 text-dark">3</h2>
                                <small class="text-warning small fw-bold">Segera Proses!</small>
                            </div>
                            <div class="icon-box bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0 text-dark">
                        <i class="fa-solid fa-list-ol me-2 text-primary"></i> Antrean Masuk (FIFO)
                    </h6>
                    <a href="peserta.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Nama Peserta</th>
                                    <th>Instansi</th>
                                    <th>Waktu Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-4">1</td>
                                    <td class="fw-bold">Budi Santoso</td>
                                    <td>Univ. Alma Ata</td>
                                    <td>27-12-2025 <span class="badge bg-light text-dark border">08:00:01</span></td>
                                    <td><button class="btn btn-sm btn-primary">Verifikasi</button></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">2</td>
                                    <td>Siti Aminah</td>
                                    <td>SMK N 1 Yk</td>
                                    <td>27-12-2025 <span class="badge bg-light text-dark border">08:05:12</span></td>
                                    <td><button class="btn btn-sm btn-primary">Verifikasi</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold m-0 text-dark">Aksi Cepat Admin</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        <a href="kuota.php" class="btn-quick text-decoration-none">
                            <i class="fa-solid fa-pen-to-square text-primary me-2"></i> Update Kuota Magang
                        </a>
                        <a href="informasi.php" class="btn-quick text-decoration-none">
                            <i class="fa-solid fa-newspaper text-info me-2"></i> Edit Pengumuman
                        </a>
                        <a href="admin.php" class="btn-quick text-decoration-none">
                            <i class="fa-solid fa-user-plus text-success me-2"></i> Tambah Admin Baru
                        </a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold m-0 text-dark"><i class="fa-solid fa-server me-2"></i>Status Sistem</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <span class="text-muted">Database</span>
                            <span class="badge bg-success">Connected</span>
                        </li>
                        <li class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Algoritma</span>
                            <span class="fw-bold text-dark">FIFO (Active)</span>
                        </li>
                        <li class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Periode Magang</span>
                            <span class="text-primary">Januari - Maret 2026</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="app-footer">
    <div>
        &copy; 2025 <strong>Balai Teknologi Komunikasi Pendidikan DIY</strong>. 
        <span class="d-none d-sm-inline"> - Sistem Penerimaan Magang v1.0</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Sidebar Mobile
    window.toggleSidebar = function() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) sidebar.classList.toggle('show');
    };

    // Jam Realtime
    document.addEventListener('DOMContentLoaded', function() {
        // ... kode jam sama seperti sebelumnya ...
        // (Bisa dicopy dari kode sebelumnya jika diperlukan fungsi jam)
    });
</script>

</body>
</html>