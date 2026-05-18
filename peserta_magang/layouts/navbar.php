<?php
// Set Timezone
date_default_timezone_set('Asia/Jakarta');

// Ambil Data Session Peserta (Sesuai cek_login.php)
$nama_peserta = $_SESSION['peserta_nama'] ?? 'Peserta Magang';
$foto_peserta = $_SESSION['peserta_foto'] ?? 'default.jpg'; 

// Path Foto (Pastikan folder uploads/foto ada)
$foto_url = "uploads/foto/" . $foto_peserta;
if (!file_exists($foto_url) || empty($foto_peserta)) {
    // Fallback ke UI Avatars jika foto tidak ada
    $foto_url = "https://ui-avatars.com/api/?name=" . urlencode($nama_peserta) . "&background=0d2141&color=fff";
}
?>

<div class="top-header">
    <div class="d-flex align-items-center">
        <button type="button" class="btn btn-link d-lg-none me-3 p-0 text-decoration-none" onclick="toggleSidebar()" style="color: var(--text-main);">
            <i class="fa-solid fa-bars fs-4"></i>
        </button>

        <div>
            <h5 class="fw-bold mb-0" style="color: var(--text-main);">Portal Peserta</h5>
            <small class="text-muted">Balai Teknologi Komunikasi Pendidikan DIY</small>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <div class="d-none d-md-block text-end">
            <div class="fw-bold small" style="color: var(--text-main);"><?php echo date('l, d F Y'); ?></div>
            <div id="live-clock" class="badge border rounded-pill px-3" style="color: var(--text-main); border-color: var(--border-color) !important;">
                <?php echo date('H:i:s'); ?> WIB
            </div>
        </div>

        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <img src="<?php echo $foto_url; ?>" alt="Profile" class="rounded-circle me-2 border" style="width: 38px; height: 38px; object-fit: cover;">
                
                <div class="d-none d-lg-block text-start lh-sm">
                    <div class="fw-bold small" style="color: var(--text-main);">
                        <?php echo htmlspecialchars($nama_peserta); ?>
                    </div>
                    <small class="text-muted" style="font-size: 11px;">Peserta Magang</small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow mt-2">
                <li><a class="dropdown-item" href="profil.php"><i class="fa-solid fa-user-gear me-2"></i> Edit Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</div>