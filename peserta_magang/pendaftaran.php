<?php
session_start();

// 1. CEK KEAMANAN
if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login' || $_SESSION['role_akses'] != 'Peserta') {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

include '../config/database.php';

/** @var mysqli $koneksi */

$id_user = $_SESSION['peserta_id'];
$msg_type = "";
$msg_text = "";

// ==================================================================
// LOGIKA 1: PROSES PENDAFTARAN BARU
// ==================================================================
if (isset($_POST['kirim_pendaftaran'])) {
    
    // A. TANGKAP DATA LENGKAP
    $no_hp            = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $alamat           = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $asal_instansi    = mysqli_real_escape_string($koneksi, $_POST['asal_instansi']);
    $alamat_instansi  = mysqli_real_escape_string($koneksi, $_POST['alamat_instansi']);
    $no_hp_pendamping = mysqli_real_escape_string($koneksi, $_POST['no_hp_pendamping']);
    
    // Data Tanggal Magang (Input Mandiri)
    $tanggal_mulai    = $_POST['tanggal_mulai'];
    $tanggal_selesai  = $_POST['tanggal_selesai'];
    
    // B. VALIDASI FILE
    $allowed_ext = ['pdf'];
    $file_surat = $_FILES['berkas'];
    $ext_surat  = strtolower(pathinfo($file_surat['name'], PATHINFO_EXTENSION));
    
    $file_prop  = $_FILES['proposal'];
    $ext_prop   = strtolower(pathinfo($file_prop['name'], PATHINFO_EXTENSION));

    if (!in_array($ext_surat, $allowed_ext)) {
        $msg_type = "danger"; $msg_text = "Format Surat Pengantar harus PDF!";
    } elseif (!empty($file_prop['name']) && !in_array($ext_prop, $allowed_ext)) {
        $msg_type = "danger"; $msg_text = "Format Proposal harus PDF!";
    } elseif ($tanggal_mulai >= $tanggal_selesai) {
        $msg_type = "danger"; $msg_text = "Tanggal selesai magang harus lebih besar dari tanggal mulai!";
    } else {
        
        // C. UPDATE DATA DIRI LENGKAP
        $update_profil = "UPDATE peserta SET 
                            no_hp = '$no_hp', 
                            alamat = '$alamat', 
                            asal_instansi = '$asal_instansi',
                            alamat_instansi = '$alamat_instansi',
                            no_hp_pendamping = '$no_hp_pendamping'
                            WHERE id_user = '$id_user'";
        
        if (mysqli_query($koneksi, $update_profil)) {
            
            // D. UPLOAD FILE
            $nama_surat_baru = $id_user . "_SURAT_" . time() . "." . $ext_surat;
            $nama_prop_baru  = "";
            
            if (move_uploaded_file($file_surat['tmp_name'], "../uploads/" . $nama_surat_baru)) {
                
                if (!empty($file_prop['name'])) {
                    $nama_prop_baru = $id_user . "_PROPOSAL_" . time() . "." . $ext_prop;
                    move_uploaded_file($file_prop['tmp_name'], "../uploads/" . $nama_prop_baru);
                }

                // E. SIMPAN PENDAFTARAN (Logika Baru tanpa id_kuota)
                $query_simpan = "INSERT INTO pendaftaran (id_user, tgl_daftar, tanggal_mulai, tanggal_selesai, status, berkas, proposal) 
                                    VALUES ('$id_user', NOW(), '$tanggal_mulai', '$tanggal_selesai', 'Menunggu', '$nama_surat_baru', '$nama_prop_baru')";
                
                if (mysqli_query($koneksi, $query_simpan)) {
                    $msg_type = "success";
                    $msg_text = "Pendaftaran Berhasil! Silakan tunggu proses verifikasi oleh Admin.";
                    header("Refresh:2"); 
                } else {
                    $msg_type = "danger"; $msg_text = "Gagal menyimpan pendaftaran: " . mysqli_error($koneksi);
                }
            } else {
                $msg_type = "danger"; $msg_text = "Gagal Upload File.";
            }
        } else {
            $msg_type = "danger"; $msg_text = "Gagal Update Profil: " . mysqli_error($koneksi);
        }
    }
}

// ==================================================================
// LOGIKA 2: CEK APAKAH SEDANG DAFTAR / AKTIF MAGANG
// ==================================================================
// Peserta tidak boleh mendaftar lagi jika statusnya masih 'Menunggu' atau 'Diterima'
$sedang_daftar = false;
$data_pendaftaran_aktif = null;

$cek_status = mysqli_query($koneksi, "SELECT * FROM pendaftaran WHERE id_user = '$id_user' AND status IN ('Menunggu', 'Diterima') ORDER BY id_daftar DESC LIMIT 1");

if (mysqli_num_rows($cek_status) > 0) {
    $sedang_daftar = true;
    $data_pendaftaran_aktif = mysqli_fetch_assoc($cek_status);
}

// Ambil Data Profil
$q_peserta = mysqli_query($koneksi, "SELECT * FROM peserta WHERE id_user = '$id_user'");
$d_peserta = mysqli_fetch_assoc($q_peserta);
if (!$d_peserta) $d_peserta = [];

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Formulir Pendaftaran Magang</h4>
            <p class="text-muted small mb-0">Lengkapi data diri, rencana waktu magang, dan unggah berkas pengajuan.</p>
        </div>
    </div>

    <?php if (!empty($msg_type)): ?>
        <div class="alert alert-<?php echo $msg_type; ?> border-0 shadow-sm mb-4">
            <i class="fa-solid <?php echo ($msg_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2"></i>
            <?php echo $msg_text; ?>
        </div>
    <?php endif; ?>

    <?php if ($sedang_daftar): ?>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-5 text-center">
                <?php 
                $st = $data_pendaftaran_aktif['status'];
                if ($st == 'Menunggu') { $icon = 'fa-clock'; $cls = 'warning'; $txt = 'Menunggu Verifikasi Admin'; }
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
                </div>
                <?php if ($st == 'Diterima'): ?>
                    <div class="mt-4"><a href="unduhan.php" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fa-solid fa-download me-2"></i> Unduh Surat Balasan</a></div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
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
                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($d_peserta['nama']??''); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold">Email</label>
                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($d_peserta['email']??''); ?>" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">No. HP / WhatsApp <span class="text-danger">*</span></label>
                                <input type="number" name="no_hp" class="form-control" value="<?php echo htmlspecialchars($d_peserta['no_hp']??''); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-dark">Alamat Rumah (Domisili) <span class="text-danger">*</span></label>
                                <textarea name="alamat" class="form-control" rows="2" required><?php echo htmlspecialchars($d_peserta['alamat']??''); ?></textarea>
                            </div>

                            <hr>

                            <h6 class="fw-bold text-dark mb-3 text-uppercase small" style="letter-spacing: 1px;">Data Instansi</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Nama Sekolah / Kampus <span class="text-danger">*</span></label>
                                <input type="text" name="asal_instansi" class="form-control" placeholder="Contoh: Universitas Gadjah Mada" value="<?php echo htmlspecialchars($d_peserta['asal_instansi']??''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Alamat Instansi <span class="text-danger">*</span></label>
                                <textarea name="alamat_instansi" class="form-control" rows="2" placeholder="Alamat lengkap sekolah/kampus..." required><?php echo htmlspecialchars($d_peserta['alamat_instansi']??''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">No. HP Guru/Dosen Pendamping <span class="text-danger">*</span></label>
                                <input type="number" name="no_hp_pendamping" class="form-control" placeholder="Nomor kontak pembimbing magang" value="<?php echo htmlspecialchars($d_peserta['no_hp_pendamping']??''); ?>" required>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="fw-bold m-0 text-primary"><i class="fa-solid fa-calendar-days me-2"></i> Rencana & Berkas</h6>
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

                            <h6 class="fw-bold text-dark mb-3 text-uppercase small" style="letter-spacing: 1px;">Unggah Berkas</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Surat Pengantar (Wajib) <span class="text-danger">*</span></label>
                                <input type="file" name="berkas" class="form-control" accept=".pdf" required>
                                <div class="form-text small text-muted">Format PDF, Max 2MB.</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-dark">Proposal (Opsional)</label>
                                <input type="file" name="proposal" class="form-control" accept=".pdf">
                            </div>
                            
                            <div class="d-grid mt-4">
                                <button type="submit" name="kirim_pendaftaran" class="btn btn-primary fw-bold py-2 shadow-sm" onclick="return confirm('Apakah data dan tanggal yang Anda masukkan sudah benar?')">
                                    <i class="fa-solid fa-paper-plane me-2"></i> KIRIM SEKARANG
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