<div class="sidebar d-flex flex-column">
    <a href="index.php" class="text-decoration-none mb-4 mt-2">
        <div class="d-flex align-items-center px-2 gap-3 text-center">
            <img src="../assets/logo-btkp.png" alt="Logo BTKP DIY" width="150" class="img-fluid mb-3" style="filter: drop-shadow(0px 4px 8px rgba(0,0,0,0.15));">
        </div>
    </a>
    
    <ul class="nav flex-column mb-auto">
        <li class="px-3 mb-2 text-uppercase small text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Menu Utama</li>
        
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="fa-solid fa-house"></i> 
                <span>Beranda & Info</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'panduan.php' ? 'active' : ''; ?>" href="panduan.php">
                <i class="fa-solid fa-book-open"></i> 
                <span>Panduan Sistem</span>
            </a>
        </li>
        
        <li class="px-3 mt-4 mb-2 text-uppercase small text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Kegiatan Magang</li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pendaftaran.php' ? 'active' : ''; ?>" href="pendaftaran.php">
                <i class="fa-solid fa-file-pen"></i> 
                <span>Formulir Pendaftaran</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'posisi_kuota.php' ? 'active' : ''; ?>" href="posisi_kuota.php">
                <i class="fa-solid fa-ranking-star"></i> 
                <span>Cek Posisi dan Kuota</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'unduhan.php' ? 'active' : ''; ?>" href="unduhan.php">
                <i class="fa-solid fa-download"></i> 
                <span>Unduhan & Hasil</span>
            </a>
        </li>
    </ul>

    <div class="px-3 pb-4 mt-4">
        <a href="logout.php" class="nav-link text-danger fw-bold border border-danger rounded-3 justify-content-center">
            <i class="fa-solid fa-right-from-bracket"></i> 
            <span>Keluar</span>
        </a>
    </div>
</div>
