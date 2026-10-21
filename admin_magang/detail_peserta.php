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

$id_daftar = (int) ($_GET['id'] ?? 0);
$msg_type = "";
$msg_text = "";

// ==========================================================
// LOGIKA UPLOAD DOKUMEN (SURAT BALASAN & DOKUMEN KELULUSAN)
// ==========================================================
$allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
$upload_dir  = __DIR__ . '/../uploads/';

// A. Upload Surat Balasan (Fase Diterima / Selesai)
if (isset($_POST['upload_balasan'])) {
    $id_user_doc = (int) $_POST['id_user'];
    
    if (!empty($_FILES['file_balasan']['name']) && $_FILES['file_balasan']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['file_balasan']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['file_balasan']['size'];

        if (!in_array($ext, $allowed_ext)) {
            $msg_type = "danger"; 
            $msg_text = "Format file tidak valid! Gunakan PDF, JPG, atau PNG.";
        } elseif ($file_size > 5 * 1024 * 1024) {
            $msg_type = "danger"; 
            $msg_text = "Ukuran file terlalu besar! Maksimal 5 MB.";
        } else {
            $safe_name = $id_user_doc . "_BALASAN_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            if (move_uploaded_file($_FILES['file_balasan']['tmp_name'], $upload_dir . $safe_name)) {
                $stmt_up = mysqli_prepare($koneksi, "UPDATE pendaftaran SET file_balasan=? WHERE id_daftar=?");
                mysqli_stmt_bind_param($stmt_up, "si", $safe_name, $id_daftar);
                mysqli_stmt_execute($stmt_up);
                mysqli_stmt_close($stmt_up);

                catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], $id_daftar, 'pendaftaran', 'UPLOAD_BALASAN', null, null, "Mengunggah Surat Balasan: $safe_name");
                $msg_type = "success"; 
                $msg_text = "Surat Balasan resmi berhasil diunggah.";
            } else {
                $msg_type = "danger"; 
                $msg_text = "Gagal memindahkan file ke direktori uploads.";
            }
        }
    } else {
        $msg_type = "danger"; 
        $msg_text = "Silakan pilih file surat balasan yang valid.";
    }
}

// B. Upload Dokumen Akhir (Sertifikat + Nilai & Surat Keterangan)
// Dapat diunggah saat status 'Diterima' (sebagai syarat Selesai) atau saat sudah 'Selesai'
if (isset($_POST['upload_akhir'])) {
    $id_user_doc = (int) $_POST['id_user'];
    $uploaded_any = false;

    // 1. Upload Sertifikat (+Nilai)
    if (!empty($_FILES['file_sertifikat']['name']) && $_FILES['file_sertifikat']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['file_sertifikat']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['file_sertifikat']['size'];

        if (in_array($ext, $allowed_ext) && $file_size <= 5 * 1024 * 1024) {
            $safe_sertif = $id_user_doc . "_SERTIFIKAT_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            if (move_uploaded_file($_FILES['file_sertifikat']['tmp_name'], $upload_dir . $safe_sertif)) {
                $stmt_up = mysqli_prepare($koneksi, "UPDATE pendaftaran SET file_sertifikat=? WHERE id_daftar=?");
                mysqli_stmt_bind_param($stmt_up, "si", $safe_sertif, $id_daftar);
                mysqli_stmt_execute($stmt_up);
                mysqli_stmt_close($stmt_up);
                $uploaded_any = true;
                catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], $id_daftar, 'pendaftaran', 'UPLOAD_SERTIFIKAT', null, null, "Mengunggah Sertifikat: $safe_sertif");
            }
        } else {
            $msg_type = "danger"; 
            $msg_text = "Format file Sertifikat harus PDF/JPG/PNG dan maksimal 5MB!";
        }
    }

    // 2. Upload Surat Keterangan
    if (!empty($_FILES['file_keterangan']['name']) && $_FILES['file_keterangan']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['file_keterangan']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['file_keterangan']['size'];

        if (in_array($ext, $allowed_ext) && $file_size <= 5 * 1024 * 1024) {
            $safe_ket = $id_user_doc . "_KETERANGAN_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            if (move_uploaded_file($_FILES['file_keterangan']['tmp_name'], $upload_dir . $safe_ket)) {
                $stmt_up = mysqli_prepare($koneksi, "UPDATE pendaftaran SET file_keterangan=? WHERE id_daftar=?");
                mysqli_stmt_bind_param($stmt_up, "si", $safe_ket, $id_daftar);
                mysqli_stmt_execute($stmt_up);
                mysqli_stmt_close($stmt_up);
                $uploaded_any = true;
                catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], $id_daftar, 'pendaftaran', 'UPLOAD_KETERANGAN', null, null, "Mengunggah Surat Keterangan: $safe_ket");
            }
        } else {
            $msg_type = "danger"; 
            $msg_text = "Format file Surat Keterangan harus PDF/JPG/PNG dan maksimal 5MB!";
        }
    }

    if ($uploaded_any && empty($msg_type)) {
        $msg_type = "success"; 
        $msg_text = "Dokumen Akhir kelulusan berhasil diperbarui dan tervalidasi.";
    }
}

// ==========================================================
// AMBIL DATA DETAIL PENDAFTAR
// ==========================================================
$stmt_get = mysqli_prepare($koneksi, "SELECT p.*, u.nama, u.email, u.no_hp, u.asal_instansi, u.alamat_instansi, u.no_hp_pendamping, u.alamat, u.foto_profil, u.id_user 
          FROM pendaftaran p
          JOIN peserta u ON p.id_user = u.id_user
          WHERE p.id_daftar = ?");
mysqli_stmt_bind_param($stmt_get, "i", $id_daftar);
mysqli_stmt_execute($stmt_get);
$result = mysqli_stmt_get_result($stmt_get);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_get);

if (!$data) {
    echo "<script>alert('Data pendaftar tidak ditemukan!'); window.location='peserta.php';</script>";
    exit();
}

$st = $data['status'];

// Cek keberadaan fisik file dokumen kelulusan
$sertif_file_exists = (!empty($data['file_sertifikat']) && file_exists($upload_dir . $data['file_sertifikat']));
$ket_file_exists    = (!empty($data['file_keterangan']) && file_exists($upload_dir . $data['file_keterangan']));
$balasan_file_exists = (!empty($data['file_balasan']) && file_exists($upload_dir . $data['file_balasan']));
$syarat_selesai_lengkap = ($sertif_file_exists && $ket_file_exists);

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">
    <div class="d-flex align-items-center mb-4">
        <a href="peserta.php" class="btn btn-light border shadow-sm me-3 rounded-pill px-3"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
        <div>
            <h4 class="fw-bold text-dark mb-1">Detail Pendaftar Magang</h4>
            <p class="text-muted small mb-0">Tinjau profil peserta, validasi dokumen, dan kelola kelulusan.</p>
        </div>
    </div>

    <?php if (!empty($msg_type)): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid <?php echo ($msg_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2"></i>
            <?php echo htmlspecialchars($msg_text, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 text-center p-4 h-100">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem; font-weight: bold;">
                    <?php echo htmlspecialchars(substr($data['nama'], 0, 1)); ?>
                </div>
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($data['nama']); ?></h5>
                <p class="text-muted small mb-3"><?php echo htmlspecialchars($data['email']); ?></p>

                <?php 
                if ($st == 'Menunggu') echo '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-clock me-1"></i> Menunggu Verifikasi</span>';
                elseif ($st == 'Diterima') echo '<span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> Diterima (Aktif)</span>';
                elseif ($st == 'Selesai') echo '<span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="fa-solid fa-flag-checkered me-1"></i> Selesai (Arsip)</span>';
                else echo '<span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-circle-xmark me-1"></i> Ditolak</span>';
                ?>
                
                <hr class="my-4">
                
                <div class="text-start bg-light p-3 rounded-3 border">
                    <p class="small text-muted mb-1">Rencana Magang:</p>
                    <h6 class="fw-bold text-dark mb-3">
                        <?php echo !empty($data['tanggal_mulai']) ? date('d M Y', strtotime($data['tanggal_mulai'])) . " - " . date('d M Y', strtotime($data['tanggal_selesai'])) : '-'; ?>
                    </h6>
                    <p class="small text-muted mb-1">Waktu Pendaftaran (FIFO):</p>
                    <h6 class="fw-bold text-dark mb-3"><?php echo date('d M Y, H:i:s', strtotime($data['tgl_daftar'])); ?> WIB</h6>

                    <?php if (!empty($data['completed_at'])): ?>
                        <p class="small text-muted mb-1">Waktu Selesai Magang:</p>
                        <h6 class="fw-bold text-success mb-0"><?php echo date('d M Y, H:i:s', strtotime($data['completed_at'])); ?> WIB</h6>
                    <?php endif; ?>
                </div>

                <?php if ($st == 'Diterima'): ?>
                    <div class="mt-4">
                        <?php if ($syarat_selesai_lengkap): ?>
                            <a href="peserta.php?aksi=selesai&id=<?php echo $id_daftar; ?>" 
                               class="btn btn-info text-white fw-bold w-100 rounded-pill shadow-sm"
                               onclick="return confirm('Apakah Anda yakin ingin menyelesaikan status magang peserta ini? Data akan dipindah ke Arsip dan kuota akan bertambah.')">
                                <i class="fa-solid fa-flag-checkered me-2"></i> Selesaikan Status Magang
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary fw-bold w-100 rounded-pill opacity-75" disabled title="Unggah Sertifikat & Surat Keterangan terlebih dahulu">
                                <i class="fa-solid fa-lock me-2"></i> Selesaikan Magang (Berkas Belum Lengkap)
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-address-card me-2"></i> Identitas & Kontak</h6>
                    <table class="table table-borderless table-sm mb-4">
                        <tr><td width="35%" class="text-muted">No. HP (WA)</td><td class="fw-bold"><?php echo htmlspecialchars($data['no_hp'] ?? '-'); ?></td></tr>
                        <tr><td class="text-muted">Alamat Domisili</td><td class="fw-bold"><?php echo nl2br(htmlspecialchars($data['alamat'] ?? '-')); ?></td></tr>
                    </table>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-building me-2"></i> Data Instansi Pendidikan</h6>
                    <table class="table table-borderless table-sm mb-4">
                        <tr><td width="35%" class="text-muted">Asal Sekolah/Kampus</td><td class="fw-bold"><?php echo htmlspecialchars($data['asal_instansi'] ?? '-'); ?></td></tr>
                        <tr><td class="text-muted">Alamat Instansi</td><td class="fw-bold"><?php echo nl2br(htmlspecialchars($data['alamat_instansi'] ?? '-')); ?></td></tr>
                        <tr><td class="text-muted">No. HP Pembimbing</td><td class="fw-bold"><?php echo htmlspecialchars($data['no_hp_pendamping'] ?? '-'); ?></td></tr>
                    </table>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-folder-open me-2"></i> Dokumen Pengajuan (Dari Peserta)</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if(!empty($data['berkas'])): ?>
                            <a href="../uploads/<?php echo htmlspecialchars($data['berkas']); ?>" target="_blank" class="btn btn-outline-danger fw-bold rounded-pill px-4">
                                <i class="fa-solid fa-file-pdf me-2"></i> Surat Pengantar
                            </a>
                        <?php else: ?>
                            <span class="badge bg-secondary p-2">Surat Pengantar Kosong</span>
                        <?php endif; ?>

                        <?php if(!empty($data['proposal'])): ?>
                            <a href="../uploads/<?php echo htmlspecialchars($data['proposal']); ?>" target="_blank" class="btn btn-outline-primary fw-bold rounded-pill px-4">
                                <i class="fa-solid fa-file-pdf me-2"></i> Proposal Magang
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($st == 'Diterima' || $st == 'Selesai'): ?>
            <!-- SEKSI SURAT BALASAN -->
            <div class="card border-0 shadow-sm rounded-3 border-top border-4 border-primary mb-4">
                <div class="card-header bg-white p-4 border-bottom-0">
                    <h6 class="fw-bold text-primary m-0"><i class="fa-solid fa-reply me-2"></i> Surat Balasan Magang Resmi</h6>
                    <p class="text-muted small mb-0 mt-1">Unggah Surat Balasan resmi dari BTKP DIY untuk diberikan kepada pihak instansi peserta.</p>
                </div>
                <div class="card-body p-4 pt-0">
                    <form action="" method="POST" enctype="multipart/form-data" class="bg-light p-3 rounded-3 border mb-3 d-flex flex-column flex-md-row align-items-md-end gap-3">
                        <input type="hidden" name="id_user" value="<?php echo $data['id_user']; ?>">
                        <div class="flex-grow-1">
                            <label class="form-label small fw-bold">Pilih File Surat Balasan (PDF/JPG/PNG, Max 5MB)</label>
                            <input type="file" name="file_balasan" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <button type="submit" name="upload_balasan" class="btn btn-primary btn-sm fw-bold px-4 rounded-pill">
                            <i class="fa-solid fa-upload me-1"></i> Simpan Surat Balasan
                        </button>
                    </form>

                    <?php if ($balasan_file_exists): ?>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Terunggah</span>
                            <a href="../uploads/<?php echo htmlspecialchars($data['file_balasan']); ?>" target="_blank" class="btn btn-primary btn-sm fw-bold rounded-pill px-3">
                                <i class="fa-solid fa-eye me-2"></i> Buka Surat Balasan Terunggah
                            </a>
                        </div>
                    <?php else: ?>
                        <span class="text-muted small"><i class="fa-solid fa-circle-exclamation text-warning me-1"></i> Belum ada file surat balasan resmi.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SEKSI DOKUMEN KELULUSAN (SERTIFIKAT + NILAI & SURAT KETERANGAN) -->
            <!-- Harus diunggah SEBELUM tombol Selesai dapat diklik -->
            <div class="card border-0 shadow-sm rounded-3 border-top border-4 <?php echo ($syarat_selesai_lengkap) ? 'border-success' : 'border-warning'; ?> mb-4">
                <div class="card-header bg-white p-4 border-bottom-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold <?php echo ($syarat_selesai_lengkap) ? 'text-success' : 'text-warning'; ?> m-0">
                                <i class="fa-solid fa-graduation-cap me-2"></i> Dokumen Kelulusan Magang (Wajib Sebelum Status Selesai)
                            </h6>
                            <p class="text-muted small mb-0 mt-1">Unggah Sertifikat (+ Nilai) dan Surat Keterangan Selesai Magang.</p>
                        </div>
                        <?php if ($syarat_selesai_lengkap): ?>
                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-circle-check me-1"></i> Syarat Selesai Terpenuhi</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Wajib Dilengkapi</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-4 pt-0">
                    
                    <form action="" method="POST" enctype="multipart/form-data" class="bg-light p-3 rounded-3 border mb-3">
                        <input type="hidden" name="id_user" value="<?php echo $data['id_user']; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Sertifikat + Nilai (PDF/JPG/PNG)</label>
                                <input type="file" name="file_sertifikat" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                <div class="form-text small">
                                    Status saat ini: 
                                    <?php if ($sertif_file_exists): ?>
                                        <span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Sudah Terunggah</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold"><i class="fa-solid fa-xmark"></i> Belum Diunggah</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Surat Keterangan Selesai (PDF/JPG/PNG)</label>
                                <input type="file" name="file_keterangan" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                <div class="form-text small">
                                    Status saat ini: 
                                    <?php if ($ket_file_exists): ?>
                                        <span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Sudah Terunggah</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold"><i class="fa-solid fa-xmark"></i> Belum Diunggah</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" name="upload_akhir" class="btn btn-success btn-sm fw-bold px-4 rounded-pill">
                                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Dokumen Kelulusan
                            </button>
                        </div>
                    </form>

                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <?php if ($sertif_file_exists): ?>
                            <a href="../uploads/<?php echo htmlspecialchars($data['file_sertifikat']); ?>" target="_blank" class="btn btn-warning text-dark fw-bold border-0 rounded-pill px-3 shadow-sm">
                                <i class="fa-solid fa-certificate me-2"></i> Buka Sertifikat & Nilai
                            </a>
                        <?php else: ?>
                            <span class="btn btn-light text-muted border border-dashed rounded-pill px-3" style="cursor:not-allowed;">
                                <i class="fa-solid fa-file-circle-xmark me-2 text-danger"></i> Sertifikat Belum Siap
                            </span>
                        <?php endif; ?>

                        <?php if ($ket_file_exists): ?>
                            <a href="../uploads/<?php echo htmlspecialchars($data['file_keterangan']); ?>" target="_blank" class="btn btn-success text-white fw-bold border-0 rounded-pill px-3 shadow-sm">
                                <i class="fa-solid fa-file-signature me-2"></i> Buka Surat Keterangan Selesai
                            </a>
                        <?php else: ?>
                            <span class="btn btn-light text-muted border border-dashed rounded-pill px-3" style="cursor:not-allowed;">
                                <i class="fa-solid fa-file-circle-xmark me-2 text-danger"></i> Surat Keterangan Belum Siap
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
