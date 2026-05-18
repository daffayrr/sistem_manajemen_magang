<?php
// Set Timezone agar jam benar
date_default_timezone_set('Asia/Jakarta');

// Ambil data dari SESSION yang diset saat Login
// Gunakan operator '??' (Null Coalescing) untuk mencegah error jika session kosong
$nama_admin = $_SESSION['admin_nama'] ?? 'User';
$role_admin = $_SESSION['admin_role'] ?? 'Admin';

// Ambil Huruf Depan untuk Avatar
$huruf_depan = substr($nama_admin, 0, 1);
?>

<div class="top-header">
    <div class="d-flex align-items-center">
        <button type="button" class="btn btn-link d-lg-none me-3 p-0 text-decoration-none" onclick="toggleSidebar()" style="color: var(--text-main);">
            <i class="fa-solid fa-bars fs-4"></i>
        </button>

        <div>
            <h5 class="fw-bold mb-0" style="color: var(--text-main);">Dashboard Admin</h5>
            <small class="text-muted">Sistem Manajemen Penerimaan Magang BTKP DIY</small>
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
                <div class="bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; background-color: #0d2141 !important;">
                    <?php echo strtoupper($huruf_depan); ?>
                </div>
                
                <div class="d-none d-lg-block text-start lh-sm">
                    <div class="fw-bold small" style="color: var(--text-main);">
                        <?php echo $nama_admin; ?>
                    </div>
                    <small class="text-muted" style="font-size: 11px;">
                        <?php echo $role_admin; ?>
                    </small>
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