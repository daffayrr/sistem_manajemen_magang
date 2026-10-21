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

// ==========================================================
// LOGIKA PROSES DATA (UPDATE KUOTA GLOBAL & DEADMAN SYSTEM)
// ==========================================================
if (isset($_POST['update_kuota'])) {
    $kuota_baru = (int) $_POST['jumlah_maks'];
    
    if ($kuota_baru < 1) {
        $msg_type = "danger";
        $msg_text = "Jumlah kuota maksimal minimal 1 orang.";
    } else {
        // Ambil kuota sebelumnya untuk audit
        $q_old = mysqli_query($koneksi, "SELECT jumlah_maks FROM kuota WHERE id_kuota = 1 LIMIT 1");
        $d_old = mysqli_fetch_assoc($q_old);
        $old_val = (int) ($d_old['jumlah_maks'] ?? 0);

        // Update data kuota di tabel kuota dengan Prepared Statement
        $stmt_kuota = mysqli_prepare($koneksi, "UPDATE kuota SET jumlah_maks = ? WHERE id_kuota = 1");
        mysqli_stmt_bind_param($stmt_kuota, "i", $kuota_baru);
        
        if (mysqli_stmt_execute($stmt_kuota)) {
            catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], 1, 'kuota', 'UPDATE_KUOTA', (string)$old_val, (string)$kuota_baru, "Mengubah kuota global dari $old_val menjadi $kuota_baru");
            $msg_type = "success"; 
            $msg_text = "Pengaturan Kuota Global berhasil diperbarui menjadi $kuota_baru orang.";
        } else {
            $msg_type = "danger"; 
            $msg_text = "Gagal memperbarui kuota: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt_kuota);
    }
} elseif (isset($_POST['update_deadman'])) {
    $deadman_enabled = isset($_POST['deadman_enabled']) ? 1 : 0;
    $deadman_threshold_days = (int) ($_POST['deadman_threshold_days'] ?? 7);
    $deadman_action = $_POST['deadman_action'] ?? 'AUDIT_ONLY';

    $allowed_actions = ['AUDIT_ONLY', 'AUTO_REJECT_INVALID_ONLY', 'AUTO_ACCEPT_ELIGIBLE'];

    if ($deadman_threshold_days < 1 || $deadman_threshold_days > 90) {
        $msg_type = "danger";
        $msg_text = "Batas waktu tenggat harus bernilai antara 1 sampai 90 hari.";
    } elseif (!in_array($deadman_action, $allowed_actions)) {
        $msg_type = "danger";
        $msg_text = "Pilihan kebijakan tindakan otomatis tidak valid.";
    } else {
        $stmt_dm = mysqli_prepare($koneksi, "UPDATE kuota SET deadman_enabled = ?, deadman_threshold_days = ?, deadman_action = ?, deadman_updated_at = NOW(3) WHERE id_kuota = 1");
        mysqli_stmt_bind_param($stmt_dm, "iis", $deadman_enabled, $deadman_threshold_days, $deadman_action);

        if (mysqli_stmt_execute($stmt_dm)) {
            $status_str = ($deadman_enabled == 1) ? "Aktif" : "Nonaktif";
            catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], 1, 'kuota', 'UPDATE_DEADMAN', null, $status_str, "Memperbarui Deadman System: Status $status_str, Tenggat $deadman_threshold_days hari, Tindakan: $deadman_action");
            $msg_type = "success";
            $msg_text = "Pengaturan Deadman System berhasil diperbarui ($status_str, $deadman_threshold_days hari).";
        } else {
            $msg_type = "danger";
            $msg_text = "Gagal memperbarui Deadman System: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt_dm);
    }
}

// ==========================================================
// AMBIL DATA SAAT INI UNTUK DITAMPILKAN
// ==========================================================

// 1. Ambil nilai kuota maksimal dan pengaturan Deadman
$query_kuota = mysqli_query($koneksi, "SELECT jumlah_maks, deadman_enabled, deadman_threshold_days, deadman_action, deadman_updated_at FROM kuota WHERE id_kuota = 1 LIMIT 1");
$data_kuota = mysqli_fetch_assoc($query_kuota);
$kuota_sekarang = (int) ($data_kuota['jumlah_maks'] ?? 0);
$deadman_enabled = (int) ($data_kuota['deadman_enabled'] ?? 0);
$deadman_threshold_days = (int) ($data_kuota['deadman_threshold_days'] ?? 7);
$deadman_action = $data_kuota['deadman_action'] ?? 'AUDIT_ONLY';
$deadman_updated_at = $data_kuota['deadman_updated_at'] ?? null;

// 2. Ambil jumlah peserta yang saat ini sedang aktif (Diterima)
$query_aktif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pendaftaran WHERE status = 'Diterima'");
$data_aktif  = mysqli_fetch_assoc($query_aktif);
$jumlah_aktif = (int) ($data_aktif['total'] ?? 0);

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
            <?php echo htmlspecialchars($msg_text, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold m-0 text-dark"><i class="fa-solid fa-gear me-2 text-primary"></i> Konfigurasi Sistem Kuota</h6>
                </div>
                <div class="card-body p-4">
                    
                    <div class="alert alert-info border-0 small mb-4 bg-opacity-10 text-primary">
                        <i class="fa-solid fa-circle-info me-1"></i> 
                        Sistem saat ini menggunakan metode <b>Penerimaan Berkelanjutan</b>. Peserta antrean (FIFO) hanya bisa diterima jika kuota maksimal belum terpenuhi oleh peserta yang saat ini sedang aktif.
                    </div>

                    <form action="" method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Kapasitas Maksimal Magang (Orang)</label>
                            <input type="number" name="jumlah_maks" class="form-control form-control-lg" value="<?php echo $kuota_sekarang; ?>" min="1" required>
                            
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

        <!-- BAGIAN PENGATURAN DEADMAN SYSTEM (PENERIMAAN / PEMROSESAN OTOMATIS) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0 text-dark"><i class="fa-solid fa-stopwatch me-2 text-warning"></i> Pengaturan Deadman System</h6>
                    <?php if ($deadman_enabled == 1): ?>
                        <span class="badge bg-success rounded-pill px-3 py-1"><i class="fa-solid fa-bolt me-1"></i> Sistem Aktif</span>
                    <?php else: ?>
                        <span class="badge bg-secondary rounded-pill px-3 py-1"><i class="fa-solid fa-power-off me-1"></i> Nonaktif</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    
                    <div class="alert alert-warning border-0 small mb-4 bg-opacity-10 text-dark">
                        <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i>
                        <b>Deadman System</b> memantau pendaftaran 'Menunggu' yang belum diproses melewati batas toleransi untuk menjaga kelancaran antrean FIFO.
                    </div>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark d-block">Status Fitur Deadman</label>
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" name="deadman_enabled" id="deadmanSwitch" value="1" <?php echo ($deadman_enabled == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label fs-6 align-middle" for="deadmanSwitch">
                                    Aktifkan Pemantauan Tenggat Otomatis
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">Jika dinonaktifkan, sistem tidak akan mengeksekusi tindakan otomatis.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Batas Waktu Toleransi Pemrosesan (Hari)</label>
                            <div class="input-group">
                                <input type="number" name="deadman_threshold_days" class="form-control" min="1" max="90" value="<?php echo $deadman_threshold_days; ?>" required>
                                <span class="input-group-text bg-light text-muted">Hari</span>
                            </div>
                            <small class="text-muted">Pendaftaran idle lebih dari batas ini akan dievaluasi oleh sistem.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Kebijakan Tindakan Setelah Tenggat</label>
                            <select name="deadman_action" class="form-select">
                                <option value="AUDIT_ONLY" <?php echo ($deadman_action === 'AUDIT_ONLY') ? 'selected' : ''; ?>>
                                    Simulasi / Audit Saja (Pemberitahuan Admin)
                                </option>
                                <option value="AUTO_REJECT_INVALID_ONLY" <?php echo ($deadman_action === 'AUTO_REJECT_INVALID_ONLY') ? 'selected' : ''; ?>>
                                    Tolak Otomatis Berkas Rusak / Tidak Valid
                                </option>
                                <option value="AUTO_ACCEPT_ELIGIBLE" <?php echo ($deadman_action === 'AUTO_ACCEPT_ELIGIBLE') ? 'selected' : ''; ?>>
                                    Terima Otomatis Kandidat Teratas (Sesuai Kuota & Berkas Valid)
                                </option>
                            </select>
                            <small class="text-muted">Pilih perlakuan pendaftar yang melewati tenggat waktu.</small>
                        </div>

                        <div class="p-3 bg-light rounded border mb-4 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Status Konfigurasi:</span>
                                <span class="fw-bold <?php echo ($deadman_enabled == 1) ? 'text-success' : 'text-secondary'; ?>">
                                    <?php echo ($deadman_enabled == 1) ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Tenggat Toleransi:</span>
                                <span class="fw-bold text-dark"><?php echo $deadman_threshold_days; ?> Hari</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Terakhir Diperbarui:</span>
                                <span class="text-dark"><?php echo !empty($deadman_updated_at) ? date('d M Y, H:i', strtotime($deadman_updated_at)) . ' WIB' : 'Belum Pernah'; ?></span>
                            </div>
                        </div>

                        <button type="submit" name="update_deadman" class="btn btn-warning fw-bold text-dark px-4 py-2 w-100">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Pengaturan Deadman
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'layouts/footer.php'; ?>
