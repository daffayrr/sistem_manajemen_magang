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

$id_pendaftaran = $_GET['id'] ?? 0;
$msg_type = "";
$msg_text = "";

// ==========================================================
// LOGIKA UPLOAD DOKUMEN (DIPISAH BERDASARKAN FASE)
// ==========================================================
$allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];

// A. Upload Surat Balasan (Fase Diterima)
if (isset($_POST['upload_balasan'])) {
    $id_peserta_doc = $_POST['id_peserta'];
    
    if (!empty($_FILES['file_balasan']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_balasan']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_ext)) {
            $nama_file = $id_peserta_doc . "_BALASAN_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['file_balasan']['tmp_name'], "../uploads/" . $nama_file)) {
                mysqli_query($koneksi, "UPDATE pendaftaran SET file_balasan='$nama_file' WHERE id_pendaftaran='$id_pendaftaran'");
                $msg_type = "success"; $msg_text = "Surat Balasan berhasil diunggah.";
            }
        } else {
            $msg_type = "danger"; $msg_text = "Format file tidak valid! Gunakan PDF/JPG/PNG.";
        }
    }
}

// B. Upload Dokumen Akhir (Fase Selesai)
if (isset($_POST['upload_akhir'])) {
    $id_peserta_doc = $_POST['id_peserta'];

    // 1. Upload Sertifikat (+Nilai)
    if (!empty($_FILES['file_sertifikat']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_sertifikat']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_ext)) {
            $nama_sertif = $id_peserta_doc . "_SERTIFIKAT_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['file_sertifikat']['tmp_name'], "../uploads/" . $nama_sertif)) {
                mysqli_query($koneksi, "UPDATE pendaftaran SET file_sertifikat='$nama_sertif' WHERE id_pendaftaran='$id_pendaftaran'");
                $msg_type = "success"; $msg_text = "Dokumen Akhir berhasil diperbarui.";
            }
        }
    }

    // 2. Upload Surat Keterangan
    if (!empty($_FILES['file_keterangan']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_keterangan']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_ext)) {
            $nama_ket = $id_peserta_doc . "_KETERANGAN_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['file_keterangan']['tmp_name'], "../uploads/" . $nama_ket)) {
                mysqli_query($koneksi, "UPDATE pendaftaran SET file_keterangan='$nama_ket' WHERE id_pendaftaran='$id_pendaftaran'");
                $msg_type = "success"; $msg_text = "Dokumen Akhir berhasil diperbarui.";
            }
        }
    }
}

// ==========================================================
// AMBIL DATA DETAIL
// ==========================================================
$query = "SELECT p.*, u.nama_lengkap, u.email, u.no_hp, u.asal_instansi, u.alamat_instansi, u.no_hp_pendamping, u.alamat, u.foto_profil, u.id_peserta 
          FROM pendaftaran p
          JOIN peserta u ON p.id_peserta = u.id_peserta
          WHERE p.id_pendaftaran = '$id_pendaftaran'";
$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='peserta.php';</script>";
    exit();
}

$st = $data['status_verifikasi'];

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">
    <div class="d-flex align-items-center mb-4">
        <a href="peserta.php" class="btn btn-light border shadow-sm me-3 rounded-pill px-3"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
        <div>
            <h4 class="fw-bold text-dark mb-1">Detail Pendaftar</h4>
            <p class="text-muted small mb-0">Tinjau profil peserta dan kelola dokumen administrasi.</p>
        </div>
    </div>

    <?php if (!empty($msg_type)): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid <?php echo ($msg_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2"></i>
            <?php echo $msg_text; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 text-center p-4 h-100">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem; font-weight: bold;">
                    <?php echo substr($data['nama_lengkap'], 0, 1); ?>
                </div>
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($data['nama_lengkap']); ?></h5>
                <p class="text-muted small mb-3"><?php echo htmlspecialchars($data['email']); ?></p>

                <?php 
                if ($st == 'Menunggu') echo '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Menunggu Verifikasi</span>';
                elseif ($st == 'Diterima') echo '<span class="badge bg-success px-3 py-2 rounded-pill">Diterima (Aktif)</span>';
                elseif ($st == 'Selesai') echo '<span class="badge bg-secondary px-3 py-2 rounded-pill">Selesai (Arsip)</span>';
                else echo '<span class="badge bg-danger px-3 py-2 rounded-pill">Ditolak</span>';
                ?>
                
                <hr class="my-4">
                
                <div class="text-start bg-light p-3 rounded-3 border">
                    <p class="small text-muted mb-1">Rencana Magang:</p>
                    <h6 class="fw-bold text-dark mb-3">
                        <?php echo !empty($data['tanggal_mulai']) ? date('d M Y', strtotime($data['tanggal_mulai'])) . " - " . date('d M Y', strtotime($data['tanggal_selesai'])) : '-'; ?>
                    </h6>
                    <p class="small text-muted mb-1">Waktu Pendaftaran:</p>
                    <h6 class="fw-bold text-dark mb-0"><?php echo date('d M Y, H:i:s', strtotime($data['tgl_daftar'])); ?> WIB</h6>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-address-card me-2"></i> Identitas & Kontak</h6>
                    <table class="table table-borderless table-sm mb-4">
                        <tr><td width="35%" class="text-muted">No. HP (WA)</td><td class="fw-bold"><?php echo htmlspecialchars($data['no_hp']); ?></td></tr>
                        <tr><td class="text-muted">Alamat Domisili</td><td class="fw-bold"><?php echo nl2br(htmlspecialchars($data['alamat'])); ?></td></tr>
                    </table>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-building me-2"></i> Data Instansi</h6>
                    <table class="table table-borderless table-sm mb-4">
                        <tr><td width="35%" class="text-muted">Asal Sekolah/Kampus</td><td class="fw-bold"><?php echo htmlspecialchars($data['asal_instansi']); ?></td></tr>
                        <tr><td class="text-muted">Alamat Instansi</td><td class="fw-bold"><?php echo nl2br(htmlspecialchars($data['alamat_instansi'])); ?></td></tr>
                        <tr><td class="text-muted">No. HP Pembimbing</td><td class="fw-bold"><?php echo htmlspecialchars($data['no_hp_pendamping']); ?></td></tr>
                    </table>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-folder-open me-2"></i> Dokumen Pengajuan (Dari Peserta)</h6>
                    <div class="d-flex gap-3">
                        <?php if(!empty($data['surat_pengantar'])): ?>
                            <a href="../uploads/<?php echo $data['surat_pengantar']; ?>" target="_blank" class="btn btn-outline-danger fw-bold rounded-pill px-4"><i class="fa-solid fa-file-pdf me-2"></i> Surat Pengantar</a>
                        <?php endif; ?>
                        <?php if(!empty($data['proposal'])): ?>
                            <a href="../uploads/<?php echo $data['proposal']; ?>" target="_blank" class="btn btn-outline-primary fw-bold rounded-pill px-4"><i class="fa-solid fa-file-pdf me-2"></i> Proposal</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($st == 'Diterima' || $st == 'Selesai'): ?>
            <div class="card border-0 shadow-sm rounded-3 border-top border-4 border-primary mb-4">
                <div class="card-header bg-white p-4 border-bottom-0">
                    <h6 class="fw-bold text-primary m-0"><i class="fa-solid fa-reply me-2"></i> Surat Balasan Magang</h6>
                    <p class="text-muted small mb-0 mt-1">Unggah Surat Balasan resmi dari BTKP DIY untuk diberikan kepada instansi peserta.</p>
                </div>
                <div class="card-body p-4 pt-0">
                    <form action="" method="POST" enctype="multipart/form-data" class="bg-light p-3 rounded-3 border mb-3 d-flex align-items-end gap-3">
                        <input type="hidden" name="id_peserta" value="<?php echo $data['id_peserta']; ?>">
                        <div class="flex-grow-1">
                            <label class="form-label small fw-bold">Pilih File (PDF/JPG/PNG)</label>
                            <input type="file" name="file_balasan" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <button type="submit" name="upload_balasan" class="btn btn-primary btn-sm fw-bold px-4 rounded-pill">
                            <i class="fa-solid fa-upload me-1"></i> Simpan
                        </button>
                    </form>

                    <?php if (!empty($data['file_balasan'])): ?>
                        <a href="../uploads/<?php echo $data['file_balasan']; ?>" target="_blank" class="btn btn-primary bg-opacity-10 text-white fw-bold border-0 rounded-pill px-3">
                            <i class="fa-solid fa-eye me-2"></i> Cek File Terunggah
                        </a>
                    <?php else: ?>
                        <span class="btn btn-light text-muted border border-dashed rounded-pill px-3" style="cursor:not-allowed;"><i class="fa-solid fa-file-circle-xmark me-2"></i> Belum ada file</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($st == 'Selesai'): ?>
            <div class="card border-0 shadow-sm rounded-3 border-top border-4 border-success">
                <div class="card-header bg-white p-4 border-bottom-0">
                    <h6 class="fw-bold text-success m-0"><i class="fa-solid fa-graduation-cap me-2"></i> Dokumen Kelulusan Magang</h6>
                    <p class="text-muted small mb-0 mt-1">Unggah Sertifikat (termasuk Nilai) dan Surat Keterangan Selesai Magang.</p>
                </div>
                <div class="card-body p-4 pt-0">
                    <form action="" method="POST" enctype="multipart/form-data" class="bg-light p-3 rounded-3 border mb-3">
                        <input type="hidden" name="id_peserta" value="<?php echo $data['id_peserta']; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Sertifikat + Nilai</label>
                                <input type="file" name="file_sertifikat" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Surat Keterangan Selesai</label>
                                <input type="file" name="file_keterangan" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" name="upload_akhir" class="btn btn-success btn-sm fw-bold px-4 rounded-pill">
                                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Dokumen Akhir
                            </button>
                        </div>
                    </form>

                    <div class="d-flex gap-3 mt-4">
                        <?php if (!empty($data['file_sertifikat'])): ?>
                            <a href="../uploads/<?php echo $data['file_sertifikat']; ?>" target="_blank" class="btn btn-warning bg-opacity-10 text-white fw-bold border-0 rounded-pill px-3">
                                <i class="fa-solid fa-certificate me-2"></i> Cek Sertifikat
                            </a>
                        <?php else: ?>
                            <span class="btn btn-light text-muted border border-dashed rounded-pill px-3" style="cursor:not-allowed;"><i class="fa-solid fa-file-circle-xmark me-2"></i> Sertifikat Kosong</span>
                        <?php endif; ?>

                        <?php if (!empty($data['file_keterangan'])): ?>
                            <a href="../uploads/<?php echo $data['file_keterangan']; ?>" target="_blank" class="btn btn-success bg-opacity-10 text-white fw-bold border-0 rounded-pill px-3">
                                <i class="fa-solid fa-file-signature me-2"></i> Cek Surat Keterangan
                            </a>
                        <?php else: ?>
                            <span class="btn btn-light text-muted border border-dashed rounded-pill px-3" style="cursor:not-allowed;"><i class="fa-solid fa-file-circle-xmark me-2"></i> Keterangan Kosong</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>