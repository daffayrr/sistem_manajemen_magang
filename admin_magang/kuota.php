<?php
session_start();

// 1. CEK KEAMANAN
$status_login = $_SESSION['status'] ?? '';
$role_akses   = $_SESSION['role_akses'] ?? '';

if ($status_login != 'login' || $role_akses != 'Admin') {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

include '../config/database.php';

$msg_type = "";
$msg_text = "";

// ==========================================================
// LOGIKA PROSES DATA (UPDATE KUOTA GLOBAL)
// ==========================================================

if (isset($_POST['update_kuota'])) {
    $kuota_baru = (int) $_POST['kuota_maksimal'];
    
    // Update data kuota di tabel pengaturan_sistem
    $query = "UPDATE pengaturan_sistem SET kuota_maksimal = '$kuota_baru'";
    
    if (mysqli_query($koneksi, $query)) {
        $msg_type = "success"; 
        $msg_text = "Pengaturan Kuota Global berhasil diperbarui menjadi $kuota_baru orang.";
    } else {
        $msg_type = "danger"; 
        $msg_text = "Gagal memperbarui kuota: " . mysqli_error($koneksi);
    }
}

// ==========================================================
// AMBIL DATA SAAT INI UNTUK DITAMPILKAN
// ==========================================================

// 1. Ambil nilai kuota maksimal
$query_kuota = mysqli_query($koneksi, "SELECT kuota_maksimal FROM pengaturan_sistem LIMIT 1");
$data_kuota = mysqli_fetch_assoc($query_kuota);
$kuota_sekarang = $data_kuota['kuota_maksimal'] ?? 0;

// 2. Ambil jumlah peserta yang saat ini sedang aktif (Diterima)
$query_aktif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pendaftaran WHERE status_verifikasi = 'Diterima'");
$data_aktif  = mysqli_fetch_assoc($query_aktif);
$jumlah_aktif = $data_aktif['total'];

// 3. Hitung sisa
$sisa_kuota = $kuota_sekarang - $jumlah_aktif;

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Pengaturan Kuota Magang</h4>
            <p class="text-muted small mb-0">Atur batas maksimal penerimaan peserta magang secara global.</p>
        </div>
    </div>

    <?php if (!empty($msg_type)): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid <?php echo ($msg_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2"></i>
            <?php echo $msg_text; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold m-0 text-dark"><i class="fa-solid fa-gear me-2 text-primary"></i> Konfigurasi Sistem</h6>
                </div>
                <div class="card-body p-4">
                    
                    <div class="alert alert-info border-0 small mb-4 bg-opacity-10 text-primary">
                        <i class="fa-solid fa-circle-info me-1"></i> 
                        Sistem saat ini menggunakan metode <b>Penerimaan Berkelanjutan</b>. Peserta antrean (FIFO) hanya bisa diterima jika kuota maksimal belum terpenuhi oleh peserta yang saat ini sedang aktif.
                    </div>

                    <form action="" method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Kapasitas Maksimal Magang (Orang)</label>
                            <input type="number" name="kuota_maksimal" class="form-control form-control-lg" value="<?php echo $kuota_sekarang; ?>" min="1" required>
                            
                            <div class="form-text mt-3 text-muted bg-light p-3 rounded border">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Peserta Aktif Saat Ini:</span>
                                    <span class="fw-bold text-dark"><?php echo $jumlah_aktif; ?> Orang</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Sisa Kuota Tersedia:</span>
                                    <span class="fw-bold <?php echo ($sisa_kuota > 0) ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $sisa_kuota; ?> Orang
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_kuota" class="btn btn-primary fw-bold px-4 py-2 w-100">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Pengaturan
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'layouts/footer.php'; ?>