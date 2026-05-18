<div class="sidebar d-flex flex-column">
    <a href="dashboard.php" class="text-decoration-none mb-4 mt-2">
        <div class="d-flex align-items-center px-2 gap-3 text-center">
        <img src="../assets/logo-btkp.png" alt="Logo BTKP DIY" width="150" class="img-fluid mb-3 " style="filter: drop-shadow(0px 4px 8px rgba(0,0,0,0.15));">
        </div>
    </a>
    
    <ul class="nav flex-column mb-auto">
        <li class="px-4 mb-2 text-uppercase small text-muted fw-bold" style="font-size: 0.7rem;">Main Menu</li>
        
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </li>
        
        <li class="px-4 mt-4 mb-2 text-uppercase small text-muted fw-bold" style="font-size: 0.7rem;">Manajemen Magang</li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'peserta.php' ? 'active' : ''; ?>" href="peserta.php">
                <i class="fa-solid fa-users-viewfinder"></i> Verifikasi Peserta
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kuota.php' ? 'active' : ''; ?>" href="kuota.php">
                <i class="fa-solid fa-chart-pie"></i> Kuota & Posisi
            </a>
        </li>

        <li class="px-4 mt-4 mb-2 text-uppercase small text-muted fw-bold" style="font-size: 0.7rem;">Konten & Sistem</li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'informasi.php' ? 'active' : ''; ?>" href="informasi.php">
                <i class="fa-solid fa-bullhorn"></i> Informasi Umum
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>" href="admin.php">
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