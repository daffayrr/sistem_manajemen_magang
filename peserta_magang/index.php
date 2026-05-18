<?php
session_start();

// 1. CEK KEAMANAN
if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login' || $_SESSION['role_akses'] != 'Peserta') {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

include '../config/database.php';
$id_peserta = $_SESSION['peserta_id'];

// 2. AMBIL DATA PESERTA
$q_peserta = mysqli_query($koneksi, "SELECT * FROM peserta WHERE id_peserta = '$id_peserta'");
$d_peserta = mysqli_fetch_assoc($q_peserta);

// 3. AMBIL DATA PENDAFTARAN TERAKHIR (Tanpa JOIN Kuota)
$q_daftar = mysqli_query($koneksi, "SELECT * FROM pendaftaran WHERE id_peserta = '$id_peserta' ORDER BY id_pendaftaran DESC LIMIT 1");
$d_daftar = mysqli_fetch_assoc($q_daftar);

$status_magang = $d_daftar ? $d_daftar['status_verifikasi'] : 'Belum Daftar';

// 4. AMBIL DATA INFORMASI / PENGUMUMAN DARI ADMIN
$q_info = mysqli_query($koneksi, "SELECT * FROM informasi ORDER BY id DESC LIMIT 1");
$d_info = mysqli_fetch_assoc($q_info);

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 position-relative" style="background: linear-gradient(135deg, #0d2141 0%, #1a4385 100%);">
        <div class="position-absolute opacity-25" style="top: -50px; right: -20px; width: 200px; height: 200px; border-radius: 50%; border: 30px solid rgba(255,255,255,0.1);"></div>
        <div class="position-absolute opacity-25" style="bottom: -80px; right: 100px; width: 150px; height: 150px; border-radius: 50%; border: 20px solid rgba(255,255,255,0.1);"></div>
        
        <div class="card-body p-4 p-lg-5 position-relative z-1 text-white">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge bg-white text-primary mb-3 px-3 py-2 rounded-pill fw-bold">Portal Peserta</span>
                    <h2 class="fw-bold mb-2">Halo, <?php echo htmlspecialchars($d_peserta['nama_lengkap']); ?>! 👋</h2>
                    <p class="mb-0 opacity-75 fs-6">Selamat datang di Sistem Informasi Penerimaan Magang Balai Teknologi Komunikasi Pendidikan (BTKP) DIY.</p>
                </div>
                <div class="col-md-4 text-md-end mt-4 mt-md-0 d-none d-md-block">
                    <i class="fa-solid fa-graduation-cap opacity-50" style="font-size: 6rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if ($d_info): ?>
    <div class="alert alert-info border-0 shadow-sm rounded-4 d-flex align-items-start p-4 mb-4" style="background-color: #f0f7ff;">
        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle me-4 d-none d-md-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
            <i class="fa-solid fa-bullhorn fa-2x"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($d_info['judul']); ?></h5>
                <span class="badge bg-info text-dark rounded-pill"><i class="fa-solid fa-circle-info me-1"></i> Info Terbaru</span>
            </div>
            <p class="text-secondary mb-3" style="line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($d_info['deskripsi'])); ?>
            </p>
            <div class="d-flex align-items-center text-muted small fw-bold">
                <i class="fa-solid fa-clock me-2"></i> Diperbarui: <?php echo date('d M Y, H:i', strtotime($d_info['tgl_update'])); ?> WIB
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-4 border-bottom-0">
                    <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-chart-pie text-primary me-2"></i> Status Pengajuan Magang</h6>
                </div>
                <div class="card-body px-4 pb-4 pt-0">
                    
                    <?php if ($status_magang == 'Belum Daftar'): ?>
                        <div class="text-center py-4 bg-light rounded-3 border border-dashed">
                            <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                <i class="fa-solid fa-folder-open fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-dark">Belum Ada Pengajuan</h5>
                            <p class="text-muted small mb-4">Anda belum mengajukan pendaftaran magang. Silakan klik tombol di bawah untuk mulai.</p>
                            <a href="pendaftaran.php" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm">
                                <i class="fa-solid fa-pen-to-square me-2"></i> Isi Formulir Pendaftaran
                            </a>
                        </div>
                    
                    <?php else: ?>
                        <div class="p-4 rounded-4 mb-4 text-center text-md-start <?php 
                            if($status_magang == 'Menunggu') echo 'bg-warning bg-opacity-10';
                            elseif($status_magang == 'Diterima') echo 'bg-success bg-opacity-10';
                            elseif($status_magang == 'Selesai') echo 'bg-primary bg-opacity-10';
                            else echo 'bg-danger bg-opacity-10';
                        ?>">
                            <div class="d-flex flex-column flex-md-row align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm text-white <?php 
                                    if($status_magang == 'Menunggu') echo 'bg-warning';
                                    elseif($status_magang == 'Diterima') echo 'bg-success';
                                    elseif($status_magang == 'Selesai') echo 'bg-primary';
                                    else echo 'bg-danger';
                                ?>" style="width: 60px; height: 60px;">
                                    <?php 
                                        if($status_magang == 'Menunggu') echo '<i class="fa-solid fa-hourglass-half fa-2x"></i>';
                                        elseif($status_magang == 'Diterima') echo '<i class="fa-solid fa-check fa-2x"></i>';
                                        elseif($status_magang == 'Selesai') echo '<i class="fa-solid fa-graduation-cap fa-2x"></i>';
                                        else echo '<i class="fa-solid fa-xmark fa-2x"></i>';
                                    ?>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1 <?php 
                                        if($status_magang == 'Menunggu') echo 'text-warning';
                                        elseif($status_magang == 'Diterima') echo 'text-success';
                                        elseif($status_magang == 'Selesai') echo 'text-primary';
                                        else echo 'text-danger';
                                    ?>">Status: <?php echo strtoupper($status_magang); ?></h5>
                                    <p class="text-dark opacity-75 mb-0 small">
                                        <?php 
                                            if($status_magang == 'Menunggu') echo 'Berkas Anda sedang dalam antrean verifikasi Admin (FIFO).';
                                            elseif($status_magang == 'Diterima') echo 'Selamat! Anda resmi menjadi peserta magang aktif. Cek menu Unduhan untuk surat balasan.';
                                            elseif($status_magang == 'Selesai') echo 'Terima kasih telah menyelesaikan magang. Silakan unduh Sertifikat Anda.';
                                            else echo 'Mohon maaf, pengajuan magang Anda belum dapat diterima saat ini.';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border p-3 rounded-3 bg-white">
                                    <p class="text-muted small mb-1">Rencana Pelaksanaan:</p>
                                    <h6 class="fw-bold text-dark mb-0">
                                        <?php echo !empty($d_daftar['tanggal_mulai']) ? date('d M Y', strtotime($d_daftar['tanggal_mulai'])) . " s.d <br>" . date('d M Y', strtotime($d_daftar['tanggal_selesai'])) : '-'; ?>
                                    </h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border p-3 rounded-3 bg-white">
                                    <p class="text-muted small mb-1">Waktu Pendaftaran Masuk:</p>
                                    <h6 class="fw-bold text-dark mb-0">
                                        <?php echo date('d M Y', strtotime($d_daftar['tgl_daftar'])); ?><br>
                                        <span class="text-primary"><?php echo date('H:i:s', strtotime($d_daftar['tgl_daftar'])); ?> WIB</span>
                                    </h6>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-4 border-bottom-0">
                    <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-bolt text-warning me-2"></i> Akses Cepat</h6>
                </div>
                <div class="card-body px-4 pb-4 pt-0">
                    <div class="d-grid gap-3">
                        
                        <a href="unduhan.php" class="btn btn-light border p-3 rounded-3 text-start d-flex align-items-center hover-shadow transition-all text-decoration-none">
                            <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3">
                                <i class="fa-solid fa-cloud-arrow-down fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Cek Hasil & Unduhan</h6>
                                <p class="text-muted small mb-0">Unduh Surat Balasan dan Sertifikat</p>
                            </div>
                        </a>

                        <a href="panduan.php" class="btn btn-light border p-3 rounded-3 text-start d-flex align-items-center hover-shadow transition-all text-decoration-none">
                            <div class="bg-info bg-opacity-10 text-info rounded p-3 me-3">
                                <i class="fa-solid fa-book-open-reader fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Buku Panduan Magang</h6>
                                <p class="text-muted small mb-0">Baca tata tertib dan aturan instansi</p>
                            </div>
                        </a>

                        <a href="pendaftaran.php" class="btn btn-light border p-3 rounded-3 text-start d-flex align-items-center hover-shadow transition-all text-decoration-none">
                            <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                                <i class="fa-solid fa-user-pen fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Profil & Form Pendaftaran</h6>
                                <p class="text-muted small mb-0">Lihat atau perbarui pengajuan magang</p>
                            </div>
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
    .hover-shadow:hover {
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        transform: translateY(-2px);
        background-color: #fff !important;
    }
    .transition-all {
        transition: all 0.3s ease;
    }
    .border-dashed {
        border-style: dashed !important;
        border-width: 2px !important;
    }
</style>

<?php include 'layouts/footer.php'; ?>