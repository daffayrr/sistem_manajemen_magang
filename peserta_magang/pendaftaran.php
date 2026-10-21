<?php
session_start();

// 1. CEK KEAMANAN
if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login' || $_SESSION['role_akses'] != 'Peserta') {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

require_once '../config/database.php';
require_once '../config/fifo_helper.php';
require_once '../config/pdf_validator.php';

/** @var mysqli $koneksi */

$id_user = (int) $_SESSION['peserta_id'];
$msg_type = "";
$msg_text = "";

// ==================================================================
// LOGIKA KUOTA REAL-TIME
// ==================================================================
$db_kuota_error = false;
$kuota_maksimal = 0;
$total_terpakai = 0;
$sisa_kuota     = 0;
$status_kuota_teks = "Tidak Diketahui";

$q_kuota = mysqli_query($koneksi, "SELECT jumlah_maks FROM kuota WHERE id_kuota = 1 LIMIT 1");
if ($q_kuota && $d_kuota = mysqli_fetch_assoc($q_kuota)) {
    $kuota_maksimal = (int) ($d_kuota['jumlah_maks'] ?? 0);
    
    // Kuota terpakai dihitung dari peserta yang berstatus 'Diterima' (Aktif)
    $q_aktif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pendaftaran WHERE status = 'Diterima'");
    if ($q_aktif && $d_aktif = mysqli_fetch_assoc($q_aktif)) {
        $total_terpakai = (int) ($d_aktif['total'] ?? 0);
        $sisa_kuota     = $kuota_maksimal - $total_terpakai;
        $status_kuota_teks = ($sisa_kuota > 0) ? "Tersedia" : "Penuh";
    } else {
        $db_kuota_error = true;
    }
} else {
    $db_kuota_error = true;
}

$is_kuota_penuh = ($db_kuota_error || $sisa_kuota <= 0);

// ==================================================================
// LOGIKA 1: PROSES PENDAFTARAN BARU
// ==================================================================
if (isset($_POST['kirim_pendaftaran'])) {
    
    // A. VALIDASI BACKEND: BLOKIR JIKA KUOTA PENUH ATAU DATABASE GAGAL
    if ($is_kuota_penuh) {
        $msg_type = "danger";
        $msg_text = "Mohon maaf, pendaftaran tidak dapat diproses karena Kuota Magang saat ini telah Penuh. Silakan tunggu hingga kuota tersedia kembali.";
    } else {
        // B. TANGKAP INPUT FORM
        $no_hp            = trim($_POST['no_hp'] ?? '');
        $alamat           = trim($_POST['alamat'] ?? '');
        $asal_instansi    = trim($_POST['asal_instansi'] ?? '');
        $alamat_instansi  = trim($_POST['alamat_instansi'] ?? '');
        $no_hp_pendamping = trim($_POST['no_hp_pendamping'] ?? '');
        
        $tanggal_mulai    = $_POST['tanggal_mulai'] ?? '';
        $tanggal_selesai  = $_POST['tanggal_selesai'] ?? '';
        
        // C. VALIDASI FILE PDF DENGAN PDF VALIDATOR & CONTENT CLASSIFIER
        $file_surat = $_FILES['berkas'] ?? null;
        $file_prop  = $_FILES['proposal'] ?? null;

        if (empty($file_surat['name']) || empty($file_surat['tmp_name'])) {
            $msg_type = "danger"; 
            $msg_text = "Berkas Surat Pengantar Magang wajib diunggah!";
        } elseif (empty($tanggal_mulai) || empty($tanggal_selesai)) {
            $msg_type = "danger"; 
            $msg_text = "Tanggal mulai dan selesai magang wajib diisi!";
        } elseif ($tanggal_mulai >= $tanggal_selesai) {
            $msg_type = "danger"; 
            $msg_text = "Tanggal selesai magang harus lebih besar dari tanggal mulai!";
        } else {
            // Evaluasi Surat Pengantar
            $val_surat = PDFValidator::validateSuratPengantar($file_surat['tmp_name'], $file_surat['name']);
            
            // Evaluasi Proposal (Opsional)
            $val_prop = null;
            if (!empty($file_prop['name']) && !empty($file_prop['tmp_name'])) {
                $val_prop = PDFValidator::validateProposal($file_prop['tmp_name'], $file_prop['name']);
            }

            if (!$val_surat['valid_technical']) {
                $msg_type = "danger";
                $msg_text = "Berkas Surat Pengantar tidak valid: " . $val_surat['catatan'];
            } elseif ($val_surat['status_berkas'] === 'Tidak Valid') {
                $msg_type = "danger";
                $msg_text = "Berkas Surat Pengantar ditolak: " . $val_surat['catatan'];
            } elseif ($val_prop !== null && !$val_prop['valid_technical']) {
                $msg_type = "danger";
                $msg_text = "Berkas Proposal tidak valid: " . $val_prop['catatan'];
            } else {
                
                // D. TRANSAKSI DATABASE UNTUK MENJAGA KONSISTENSI KUOTA
                mysqli_begin_transaction($koneksi);

                // Kunci baris kuota untuk memeriksa ketersediaan saat pengiriman serentak
                $q_lock_kuota = mysqli_query($koneksi, "SELECT jumlah_maks FROM kuota WHERE id_kuota = 1 FOR UPDATE");
                $q_lock_aktif = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM pendaftaran WHERE status = 'Diterima' FOR UPDATE");
                $d_lock_k = mysqli_fetch_assoc($q_lock_kuota);
                $d_lock_a = mysqli_fetch_assoc($q_lock_aktif);

                $curr_maks = (int) ($d_lock_k['jumlah_maks'] ?? 0);
                $curr_aktf = (int) ($d_lock_a['tot'] ?? 0);

                if (($curr_maks - $curr_aktf) <= 0) {
                    mysqli_rollback($koneksi);
                    $msg_type = "danger";
                    $msg_text = "Mohon maaf, pendaftaran gagal dikirim karena kuota baru saja terpenuhi. Silakan coba kembali nanti.";
                } else {
                    // E. UPDATE DATA DIRI LENGKAP
                    $stmt_profil = mysqli_prepare($koneksi, "UPDATE peserta SET no_hp=?, alamat=?, asal_instansi=?, alamat_instansi=?, no_hp_pendamping=? WHERE id_user=?");
                    mysqli_stmt_bind_param($stmt_profil, "sssssi", $no_hp, $alamat, $asal_instansi, $alamat_instansi, $no_hp_pendamping, $id_user);
                    
                    if (mysqli_stmt_execute($stmt_profil)) {
                        mysqli_stmt_close($stmt_profil);
                        
                        // F. PINDAHKAN FILE UPLOAD DENGAN NAMA AMAN & ACAK
                        $upload_dir = __DIR__ . '/../uploads/';
                        $ext_surat  = strtolower(pathinfo($file_surat['name'], PATHINFO_EXTENSION));
                        $nama_surat_baru = $id_user . "_SURAT_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext_surat;
                        
                        $nama_prop_baru   = "";
                        $status_prop_val  = "Tidak Diunggah";
                        
                        if (move_uploaded_file($file_surat['tmp_name'], $upload_dir . $nama_surat_baru)) {
                            
                            if (!empty($file_prop['name']) && !empty($file_prop['tmp_name'])) {
                                $ext_prop = strtolower(pathinfo($file_prop['name'], PATHINFO_EXTENSION));
                                $nama_prop_baru = $id_user . "_PROPOSAL_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext_prop;
                                move_uploaded_file($file_prop['tmp_name'], $upload_dir . $nama_prop_baru);
                                $status_prop_val = "Valid Otomatis";
                            }

                            $status_berkas_val  = $val_surat['status_berkas'];
                            $catatan_berkas_val = $val_surat['catatan'];

                            // G. SIMPAN DATA PENDAFTARAN DENGAN PRESISI MILIDETIK (NOW(3)) & METADATA DOKUMEN
                            $stmt_ins = mysqli_prepare($koneksi, "INSERT INTO pendaftaran 
                                (id_user, tgl_daftar, tanggal_mulai, tanggal_selesai, status, berkas, status_berkas, catatan_berkas, proposal, status_proposal) 
                                VALUES (?, NOW(3), ?, ?, 'Menunggu', ?, ?, ?, ?, ?)");
                            mysqli_stmt_bind_param($stmt_ins, "isssssss", $id_user, $tanggal_mulai, $tanggal_selesai, $nama_surat_baru, $status_berkas_val, $catatan_berkas_val, $nama_prop_baru, $status_prop_val);
                            
                            if (mysqli_stmt_execute($stmt_ins)) {
                                $new_id = mysqli_insert_id($koneksi);
                                mysqli_stmt_close($stmt_ins);

                                mysqli_commit($koneksi);

                                catat_audit($koneksi, null, 'Peserta (Self)', $new_id, 'pendaftaran', 'DAFTAR_BARU', null, 'Menunggu', "Peserta ID: $id_user mendaftar magang. Status Berkas: $status_berkas_val ($catatan_berkas_val)");
                                $msg_type = "success";
                                $msg_text = "Pendaftaran Berhasil Dikirim! " . 
                                            ($status_berkas_val === 'Valid Otomatis' ? "Berkas surat pengantar terverifikasi otomatis." : "Berkas menunggu verifikasi petugas.") . 
                                            " Silakan cek status dan posisi antrean Anda.";
                                header("Refresh:2"); 
                            } else {
                                mysqli_stmt_close($stmt_ins);
                                mysqli_rollback($koneksi);
                                $msg_type = "danger"; 
                                $msg_text = "Gagal menyimpan data pendaftaran: " . mysqli_error($koneksi);
                            }
                        } else {
                            mysqli_rollback($koneksi);
                            $msg_type = "danger"; 
                            $msg_text = "Gagal memproses berkas surat pengantar di server.";
                        }
                    } else {
                        mysqli_stmt_close($stmt_profil);
                        mysqli_rollback($koneksi);
                        $msg_type = "danger"; 
                        $msg_text = "Gagal memperbarui profil peserta: " . mysqli_error($koneksi);
                    }
                }
            }
        }
    }
}

// ==================================================================
// LOGIKA 2: CEK APAKAH SEDANG DAFTAR / AKTIF MAGANG
// ==================================================================
$sedang_daftar = false;
$data_pendaftaran_aktif = null;

$stmt_status = mysqli_prepare($koneksi, "SELECT * FROM pendaftaran WHERE id_user = ? AND status IN ('Menunggu', 'Diterima') ORDER BY id_daftar DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_status, "i", $id_user);
mysqli_stmt_execute($stmt_status);
$cek_status = mysqli_stmt_get_result($stmt_status);

if (mysqli_num_rows($cek_status) > 0) {
    $sedang_daftar = true;
    $data_pendaftaran_aktif = mysqli_fetch_assoc($cek_status);
}
mysqli_stmt_close($stmt_status);

// Ambil Data Profil
$stmt_peserta = mysqli_prepare($koneksi, "SELECT * FROM peserta WHERE id_user = ?");
mysqli_stmt_bind_param($stmt_peserta, "i", $id_user);
mysqli_stmt_execute($stmt_peserta);
$d_peserta = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_peserta)) ?? [];
mysqli_stmt_close($stmt_peserta);

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">Formulir Pendaftaran Magang</h4>
            <p class="text-muted small mb-0">Lengkapi data diri, rencana periode magang, dan unggah berkas pengajuan.</p>
        </div>
        <div>
            <a href="posisi_kuota.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-ranking-star me-1"></i> Cek Posisi & Antrean
            </a>
        </div>
    </div>

    <!-- WIDGET INFORMASI KUOTA MAGANG AKTUAL -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0 text-dark">
                <i class="fa-solid fa-chart-pie text-primary me-2"></i> Informasi Kuota Magang Tersedia
            </h6>
            <?php if ($status_kuota_teks === "Tersedia"): ?>
                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check me-1"></i> Kuota Tersedia</span>
            <?php elseif ($status_kuota_teks === "Penuh"): ?>
                <span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-lock me-1"></i> Kuota Penuh</span>
            <?php else: ?>
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-exclamation-triangle me-1"></i> Belum Dikonfigurasi</span>
            <?php endif; ?>
        </div>
        <div class="card-body p-4">
            <?php if ($db_kuota_error): ?>
                <div class="alert alert-warning border-0 small mb-0">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                    Informasi kuota belum dapat diambil dari sistem saat ini. Pendaftaran sementara tidak dapat diproses demi menjaga integritas data.
                </div>
            <?php else: ?>
                <div class="row g-3 text-center text-sm-start">
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted text-uppercase fw-bold" style="font-size:0.75rem;">Kuota Maksimum</small>
                            <h4 class="fw-bold text-dark mb-0 mt-1"><?php echo $kuota_maksimal; ?> <span class="fs-6 text-muted">Orang</span></h4>
                            <small class="text-muted">Kapasitas total</small>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted text-uppercase fw-bold" style="font-size:0.75rem;">Kuota Terpakai</small>
                            <h4 class="fw-bold text-dark mb-0 mt-1"><?php echo $total_terpakai; ?> <span class="fs-6 text-muted">Orang</span></h4>
                            <small class="text-muted">Peserta magang aktif</small>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 bg-light rounded-3 border border-start border-4 <?php echo ($sisa_kuota > 0) ? 'border-success' : 'border-danger'; ?>">
                            <small class="text-muted text-uppercase fw-bold" style="font-size:0.75rem;">Sisa Kuota Tersedia</small>
                            <h4 class="fw-bold mb-0 mt-1 <?php echo ($sisa_kuota > 0) ? 'text-success' : 'text-danger'; ?>">
                                <?php echo max(0, $sisa_kuota); ?> <span class="fs-6 text-muted">Orang</span>
                            </h4>
                            <small class="<?php echo ($sisa_kuota > 0) ? 'text-success' : 'text-danger'; ?> fw-bold">
                                <?php echo ($sisa_kuota > 0) ? 'Dapat mendaftar' : 'Tidak ada slot tersisa'; ?>
                            </small>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted text-uppercase fw-bold" style="font-size:0.75rem;">Status Pendaftaran</small>
                            <h4 class="fw-bold mb-0 mt-1 <?php echo ($status_kuota_teks === 'Tersedia') ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $status_kuota_teks; ?>
                            </h4>
                            <small class="text-muted"><?php echo ($status_kuota_teks === 'Tersedia') ? 'Formulir Aktif' : 'Pendaftaran Ditutup'; ?></small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($msg_type)): ?>
        <div class="alert alert-<?php echo $msg_type; ?> border-0 shadow-sm mb-4">
            <i class="fa-solid <?php echo ($msg_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2"></i>
            <?php echo htmlspecialchars($msg_text, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($sedang_daftar): ?>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-5 text-center">
                <?php 
                $st = $data_pendaftaran_aktif['status'];
                if ($st == 'Menunggu') { $icon = 'fa-clock'; $cls = 'warning'; $txt = 'Menunggu Verifikasi Admin (Antrean FIFO)'; }
                elseif ($st == 'Diterima') { $icon = 'fa-circle-check'; $cls = 'success'; $txt = 'Selamat! Anda Berstatus Magang Aktif'; }
                ?>
                <div class="mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-<?php echo $cls; ?> bg-opacity-10 text-<?php echo $cls; ?>" style="width: 80px; height: 80px;">
                        <i class="fa-solid <?php echo $icon; ?> fa-3x"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-<?php echo $cls; ?> mb-2"><?php echo $txt; ?></h4>
                <p class="text-muted mb-4">Ini adalah status pengajuan magang Anda saat ini.</p>
                
                <div class="card bg-light border-0 mx-auto text-start p-3" style="max-width: 500px;">
                    <div class="row mb-2">
                        <div class="col-6 text-muted small">Rencana Magang</div>
                        <div class="col-6 fw-bold small text-dark">
                            <?php echo date('d M Y', strtotime($data_pendaftaran_aktif['tanggal_mulai'])) . " s.d " . date('d M Y', strtotime($data_pendaftaran_aktif['tanggal_selesai'])); ?>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 text-muted small">Waktu Pengajuan (FIFO)</div>
                        <div class="col-6 fw-bold small text-dark"><?php echo date('d M Y, H:i:s', strtotime($data_pendaftaran_aktif['tgl_daftar'])); ?> WIB</div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-6 text-muted small">Status Verifikasi Berkas</div>
                        <div class="col-6 fw-bold small">
                            <?php 
                            $st_b = $data_pendaftaran_aktif['status_berkas'] ?? 'Menunggu Validasi';
                            if ($st_b == 'Valid Otomatis' || $st_b == 'Valid Admin') {
                                echo '<span class="text-success"><i class="fa-solid fa-check-circle me-1"></i> Tervalidasi</span>';
                            } elseif ($st_b == 'Perlu Pemeriksaan Manual') {
                                echo '<span class="text-warning text-dark"><i class="fa-solid fa-clock-rotate-left me-1"></i> Perlu Verifikasi Petugas</span>';
                            } else {
                                echo '<span class="text-danger"><i class="fa-solid fa-times-circle me-1"></i> ' . htmlspecialchars($st_b) . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex justify-content-center gap-2">
                    <a href="posisi_kuota.php" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">
                        <i class="fa-solid fa-ranking-star me-2"></i> Lihat Antrean FIFO
                    </a>
                    <?php if ($st == 'Diterima'): ?>
                        <a href="unduhan.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            <i class="fa-solid fa-download me-2"></i> Unduh Surat Balasan
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php elseif ($is_kuota_penuh): ?>
        <!-- TAMPILAN BLOKIR KETIKA KUOTA PENUH -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-5 text-center">
                <div class="mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 text-danger" style="width: 85px; height: 85px;">
                        <i class="fa-solid fa-ban fa-3x"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-danger mb-2">Pendaftaran Ditutup (Kuota Penuh)</h4>
                <p class="text-muted mb-4 mx-auto" style="max-width: 620px;">
                    Mohon maaf, saat ini kapasitas kuota magang di Balai Teknologi Komunikasi Pendidikan DIY telah terisi penuh 
                    (<b><?php echo $total_terpakai; ?> / <?php echo $kuota_maksimal; ?> peserta aktif</b>).<br>
                    Formulir pendaftaran dinonaktifkan sementara dan akan dibuka kembali secara otomatis begitu terdapat peserta magang yang menyelesaikan programnya.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="posisi_kuota.php" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="fa-solid fa-ranking-star me-1"></i> Pantau Antrean & Kuota
                    </a>
                    <a href="index.php" class="btn btn-light border rounded-pill px-4">
                        <i class="fa-solid fa-house me-1"></i> Beranda
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- FORMULIR PENDAFTARAN AKTIF -->
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="fw-bold m-0 text-primary"><i class="fa-solid fa-user-pen me-2"></i> Data Diri & Instansi</h6>
                        </div>
                        <div class="card-body p-4">
                            
                            <h6 class="fw-bold text-dark mb-3 text-uppercase small" style="letter-spacing: 1px;">Identitas Peserta</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold">Nama Lengkap</label>
                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($d_peserta['nama'] ?? ''); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold">Email</label>
                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($d_peserta['email'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">No. HP / WhatsApp <span class="text-danger">*</span></label>
                                <input type="number" name="no_hp" class="form-control" value="<?php echo htmlspecialchars($d_peserta['no_hp'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-dark">Alamat Rumah (Domisili) <span class="text-danger">*</span></label>
                                <textarea name="alamat" class="form-control" rows="2" required><?php echo htmlspecialchars($d_peserta['alamat'] ?? ''); ?></textarea>
                            </div>

                            <hr>

                            <h6 class="fw-bold text-dark mb-3 text-uppercase small" style="letter-spacing: 1px;">Data Instansi Pendidikan</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Nama Sekolah / Kampus <span class="text-danger">*</span></label>
                                <input type="text" name="asal_instansi" class="form-control" placeholder="Contoh: Universitas Gadjah Mada" value="<?php echo htmlspecialchars($d_peserta['asal_instansi'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Alamat Instansi <span class="text-danger">*</span></label>
                                <textarea name="alamat_instansi" class="form-control" rows="2" placeholder="Alamat lengkap sekolah/kampus..." required><?php echo htmlspecialchars($d_peserta['alamat_instansi'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">No. HP Guru/Dosen Pendamping <span class="text-danger">*</span></label>
                                <input type="number" name="no_hp_pendamping" class="form-control" placeholder="Nomor kontak pembimbing magang" value="<?php echo htmlspecialchars($d_peserta['no_hp_pendamping'] ?? ''); ?>" required>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="fw-bold m-0 text-primary"><i class="fa-solid fa-calendar-days me-2"></i> Rencana & Berkas Persyaratan</h6>
                        </div>
                        <div class="card-body p-4">
                            
                            <h6 class="fw-bold text-dark mb-3 text-uppercase small" style="letter-spacing: 1px;">Rencana Waktu Magang</h6>
                            <div class="alert alert-info small border-0 mb-3 py-2">
                                <i class="fa-solid fa-circle-info me-1"></i> Sesuaikan dengan durasi yang diminta oleh instansi pendidikan Anda.
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold text-dark">Mulai Magang <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_mulai" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label small fw-bold text-dark">Selesai Magang <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_selesai" class="form-control" required>
                                </div>
                            </div>

                            <hr>

                            <h6 class="fw-bold text-dark mb-3 text-uppercase small" style="letter-spacing: 1px;">Unggah Berkas Persyaratan</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Surat Pengantar (Wajib) <span class="text-danger">*</span></label>
                                <input type="file" name="berkas" class="form-control" accept=".pdf" required>
                                <div class="form-text small text-muted">Format PDF, Maksimal 2MB.</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-dark">Proposal (Opsional)</label>
                                <input type="file" name="proposal" class="form-control" accept=".pdf">
                                <div class="form-text small text-muted">Format PDF, Maksimal 2MB.</div>
                            </div>
                            
                            <div class="d-grid mt-4">
                                <button type="submit" name="kirim_pendaftaran" class="btn btn-primary fw-bold py-2 shadow-sm" onclick="return confirm('Apakah data dan tanggal yang Anda masukkan sudah benar?')">
                                    <i class="fa-solid fa-paper-plane me-2"></i> KIRIM PENDAFTARAN
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>

</div>

<?php include 'layouts/footer.php'; ?>
