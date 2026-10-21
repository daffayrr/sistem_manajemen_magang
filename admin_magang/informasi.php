<?php
session_start();

// 1. CEK KEAMANAN
$status_login = $_SESSION['status'] ?? '';
$role_akses   = $_SESSION['role_akses'] ?? '';

if ($status_login != 'login' || !in_array($role_akses, ['Admin', 'Super Admin'])) {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

require_once '../config/database.php';

/** @var mysqli $koneksi */

$msg_type = "";
$msg_text = "";

// 2. PROSES SIMPAN DATA DENGAN PREPARED STATEMENT
if (isset($_POST['simpan_info'])) {
    $judul     = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    
    if (empty($judul) || empty($deskripsi)) {
        $msg_type = "danger";
        $msg_text = "Judul dan deskripsi informasi tidak boleh kosong.";
    } else {
        $stmt_info = mysqli_prepare($koneksi, "UPDATE informasi SET judul = ?, deskripsi = ?, tgl_update = NOW() WHERE id = 1");
        mysqli_stmt_bind_param($stmt_info, "ss", $judul, $deskripsi);

        if (mysqli_stmt_execute($stmt_info)) {
            catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], 1, 'informasi', 'UPDATE_INFO', null, null, "Memperbarui pengumuman umum: $judul");
            $msg_type = "success";
            $msg_text = "Informasi dan Pengumuman berhasil diperbarui!";
        } else {
            $msg_type = "danger";
            $msg_text = "Gagal menyimpan: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt_info);
    }
}

// 3. AMBIL DATA SAAT INI
$query = mysqli_query($koneksi, "SELECT * FROM informasi WHERE id = 1");
$data  = mysqli_fetch_assoc($query);

// Jika tabel masih kosong, buat data default
if (!$data) {
    mysqli_query($koneksi, "INSERT INTO informasi (id, judul, deskripsi) VALUES (1, 'Pengumuman Penting', 'Silakan update informasi ini.')");
    header("Refresh:0");
    exit();
}

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Informasi Umum</h4>
            <p class="text-muted small mb-0">Kelola pengumuman yang tampil di dashboard peserta.</p>
        </div>
    </div>

    <?php if (!empty($msg_type)): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid <?php echo ($msg_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2 fa-lg"></i>
                <div>
                    <strong><?php echo ($msg_type == 'success') ? 'Berhasil!' : 'Gagal!'; ?></strong> <?php echo htmlspecialchars($msg_text, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold m-0 text-primary"><i class="fa-solid fa-bullhorn me-2"></i> Edit Konten Pengumuman</h6>
                </div>
                <div class="card-body p-4">
                    
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Judul Pengumuman</label>
                            <input type="text" name="judul" class="form-control" 
                                   value="<?php echo htmlspecialchars($data['judul']); ?>" 
                                   placeholder="Contoh: Pembukaan Magang Periode 1" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-dark">Isi Informasi / Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="8" 
                                      placeholder="Tuliskan informasi lengkap di sini..." required><?php echo htmlspecialchars($data['deskripsi']); ?></textarea>
                            <div class="form-text text-muted small mt-2">
                                <i class="fa-solid fa-circle-info me-1"></i> 
                                Teks ini akan muncul di halaman utama peserta setelah login.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fst-italic">
                                Terakhir diupdate: <?php echo !empty($data['tgl_update']) ? date('d M Y, H:i', strtotime($data['tgl_update'])) . ' WIB' : '-'; ?>
                            </small>
                            <button type="submit" name="simpan_info" class="btn btn-primary fw-bold px-4">
                                <i class="fa-solid fa-save me-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold m-0 text-dark"><i class="fa-solid fa-mobile-screen me-2"></i> Preview Peserta</h6>
                </div>
                <div class="card-body bg-light p-3">
                    
                    <div class="card border shadow-sm mx-auto" style="max-width: 320px;">
                        <div class="card-header bg-primary text-white py-3">
                            <small class="opacity-75 d-block mb-1">Pengumuman Terbaru</small>
                            <h6 class="fw-bold m-0 text-truncate"><?php echo htmlspecialchars($data['judul']); ?></h6>
                        </div>
                        <div class="card-body bg-white p-3">
                            <p class="card-text small text-secondary" style="white-space: pre-line;">
                                <?php 
                                $teks = $data['deskripsi'];
                                echo (strlen($teks) > 150) ? substr($teks, 0, 150) . '...' : $teks; 
                                ?>
                            </p>
                            <div class="mt-3">
                                <span class="btn btn-sm btn-outline-primary w-100 disabled">Baca Selengkapnya</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <small class="text-muted">Ini adalah simulasi tampilan pada Dashboard Peserta.</small>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'layouts/footer.php'; ?>
