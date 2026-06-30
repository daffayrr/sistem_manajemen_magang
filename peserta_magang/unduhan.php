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

// 2. AMBIL DATA PENDAFTARAN TERAKHIR (Tanpa JOIN tabel Kuota)
$query = "SELECT * FROM pendaftaran WHERE id_user = '$id_user' ORDER BY id_daftar DESC LIMIT 1";
$result = mysqli_query($koneksi, $query);
$data   = mysqli_fetch_assoc($result);

// Default state jika belum pernah daftar
$status = $data['status'] ?? 'Belum Daftar';
$file_balasan    = $data['file_balasan'] ?? null;
$file_sertifikat = $data['file_sertifikat'] ?? null;
$file_keterangan = $data['file_keterangan'] ?? null;

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Unduhan & Hasil Seleksi</h4>
            <p class="text-muted small mb-0">Pantau status magang dan unduh berkas resmi dari BTKP DIY di sini.</p>
        </div>
    </div>

    <?php if ($status == 'Belum Daftar'): ?>
        <div class="card border-0 shadow-sm rounded-3 bg-light">
            <div class="card-body p-5 text-center">
                <i class="fa-solid fa-folder-open fa-3x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-dark">Belum Ada Data</h5>
                <p class="text-muted mb-3">Anda belum melakukan pengajuan magang.</p>
                <a href="pendaftaran.php" class="btn btn-primary rounded-pill px-4">Daftar Sekarang</a>
            </div>
        </div>

    <?php else: ?>
        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-4 text-center">
                        <h6 class="fw-bold text-uppercase text-muted small mb-4">Status Saat Ini</h6>

                        <?php if ($status == 'Menunggu'): ?>
                            <div class="mb-3"><div class="spinner-border text-warning" style="width: 3rem; height: 3rem;" role="status"></div></div>
                            <h4 class="fw-bold text-warning">Menunggu Verifikasi</h4>
                            <p class="text-muted small">Berkas sedang dalam antrean pemeriksaan admin.</p>
                        
                        <?php elseif ($status == 'Diterima'): ?>
                            <div class="mb-3"><i class="fa-solid fa-circle-check text-success fa-4x"></i></div>
                            <h4 class="fw-bold text-success">Aktif Magang</h4>
                            <p class="text-muted small">Selamat! Anda berstatus sebagai peserta magang aktif.</p>

                        <?php elseif ($status == 'Selesai'): ?>
                            <div class="mb-3"><i class="fa-solid fa-graduation-cap text-primary fa-4x"></i></div>
                            <h4 class="fw-bold text-primary">Magang Selesai</h4>
                            <p class="text-muted small">Terima kasih telah melaksanakan magang di BTKP DIY.</p>

                        <?php else: ?>
                            <div class="mb-3"><i class="fa-solid fa-circle-xmark text-danger fa-4x"></i></div>
                            <h4 class="fw-bold text-danger">Mohon Maaf</h4>
                            <p class="text-muted small">Pengajuan magang Anda belum dapat diterima.</p>
                        <?php endif; ?>

                        <hr class="my-4">
                        <div class="text-start bg-light p-3 rounded-3 border">
                            <div class="row mb-2">
                                <div class="col-5 text-muted small">Waktu Daftar</div>
                                <div class="col-7 fw-bold small text-dark"><?php echo date('d M Y', strtotime($data['tgl_daftar'])); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-5 text-muted small">Pelaksanaan</div>
                                <div class="col-7 fw-bold small text-dark">
                                    <?php echo !empty($data['tanggal_mulai']) ? date('d M y', strtotime($data['tanggal_mulai'])) . " - " . date('d M y', strtotime($data['tanggal_selesai'])) : '-'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold m-0 text-primary"><i class="fa-solid fa-cloud-arrow-down me-2"></i> Berkas Unduhan Resmi</h6>
                    </div>
                    <div class="card-body p-0">
                        
                        <?php if ($status == 'Diterima' || $status == 'Selesai'): ?>
                            <div class="list-group list-group-flush">
                                
                                <div class="list-group-item p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3"><i class="fa-solid fa-reply fa-2x"></i></div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Surat Balasan Resmi</h6>
                                            <p class="text-muted small mb-0">Surat persetujuan penerimaan magang dari BTKP DIY.</p>
                                        </div>
                                    </div>
                                    <?php if (!empty($file_balasan)): ?>
                                        <a href="../uploads/<?php echo $file_balasan; ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">
                                            <i class="fa-solid fa-download me-2"></i> Unduh
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-light text-muted btn-sm rounded-pill px-4 border" disabled><i class="fa-solid fa-clock me-2"></i> Diproses Admin</button>
                                    <?php endif; ?>
                                </div>

                                <div class="list-group-item p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3"><i class="fa-solid fa-certificate fa-2x"></i></div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Sertifikat & Nilai</h6>
                                            <p class="text-muted small mb-0">Diberikan setelah menyelesaikan periode magang.</p>
                                        </div>
                                    </div>
                                    <?php if ($status == 'Selesai'): ?>
                                        <?php if (!empty($file_sertifikat)): ?>
                                            <a href="../uploads/<?php echo $file_sertifikat; ?>" target="_blank" class="btn btn-warning text-dark btn-sm rounded-pill px-4 fw-bold shadow-sm">
                                                <i class="fa-solid fa-download me-2"></i> Unduh
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-light text-muted btn-sm rounded-pill px-4 border" disabled><i class="fa-solid fa-clock me-2"></i> Diproses Admin</button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-light text-muted btn-sm rounded-pill px-4 border" disabled title="Hanya tersedia jika status Selesai"><i class="fa-solid fa-lock me-2"></i> Terkunci</button>
                                    <?php endif; ?>
                                </div>

                                <div class="list-group-item p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 me-3"><i class="fa-solid fa-file-signature fa-2x"></i></div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Surat Keterangan Selesai</h6>
                                            <p class="text-muted small mb-0">Dokumen bukti telah melaksanakan magang.</p>
                                        </div>
                                    </div>
                                    <?php if ($status == 'Selesai'): ?>
                                        <?php if (!empty($file_keterangan)): ?>
                                            <a href="../uploads/<?php echo $file_keterangan; ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-4 fw-bold shadow-sm">
                                                <i class="fa-solid fa-download me-2"></i> Unduh
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-light text-muted btn-sm rounded-pill px-4 border" disabled><i class="fa-solid fa-clock me-2"></i> Diproses Admin</button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-light text-muted btn-sm rounded-pill px-4 border" disabled title="Hanya tersedia jika status Selesai"><i class="fa-solid fa-lock me-2"></i> Terkunci</button>
                                    <?php endif; ?>
                                </div>

                            </div>

                            <?php if (empty($file_balasan) && $status == 'Diterima'): ?>
                                <div class="alert alert-info border-0 m-4 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-info fa-lg me-3"></i>
                                    <div class="small">
                                        <strong>Info:</strong> Berkas sedang diproses dan ditandatangani oleh pihak instansi. Silakan cek halaman ini secara berkala.
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php elseif ($status == 'Menunggu'): ?>
                            <div class="text-center py-5">
                                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="80" class="mb-3 opacity-50" alt="Waiting">
                                <h6 class="fw-bold text-muted">Belum Ada Berkas</h6>
                                <p class="small text-muted mb-0">Berkas unduhan akan muncul setelah status Anda berubah menjadi <b>Aktif Magang</b>.</p>
                            </div>

                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fa-solid fa-ban fa-3x text-muted mb-3 opacity-25"></i>
                                <h6 class="fw-bold text-muted">Akses Tertutup</h6>
                                <p class="small text-muted mb-0">Anda tidak memiliki berkas yang dapat diunduh pada periode ini.</p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include 'layouts/footer.php'; ?>