<?php
session_start();

// =================================================================
// 1. CEK KEAMANAN LOGIN
// =================================================================
$status_login = $_SESSION['status'] ?? '';
$role_akses   = $_SESSION['role_akses'] ?? '';

if ($status_login != 'login' || !in_array($role_akses, ['Admin', 'Super Admin'])) {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

require_once '../config/database.php';
require_once '../config/fifo_helper.php';
require_once '../config/DeadmanSystem.php';

/** @var mysqli $koneksi */

// =================================================================
// 3. LOGIKA PENGAMBILAN DATA (REAL-TIME) - REVISI KUOTA GLOBAL
// =================================================================

// A. Ambil Data KUOTA GLOBAL
$query_kuota = mysqli_query($koneksi, "SELECT jumlah_maks, sisa_kuota FROM kuota WHERE id_kuota = 1");
$data_kuota  = mysqli_fetch_assoc($query_kuota);
$jumlah_maksimal = (int) ($data_kuota['jumlah_maks'] ?? 0);

// B. Hitung Peserta Aktif (Status 'Diterima' dan belum 'Selesai')
$query_aktif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pendaftaran WHERE status = 'Diterima'");
$data_aktif  = mysqli_fetch_assoc($query_aktif);
$jumlah_aktif = (int) ($data_aktif['total'] ?? 0);

// C. Hitung Sisa Kuota Dinamis
$sisa_kuota = $jumlah_maksimal - $jumlah_aktif;

// D. Hitung Jumlah MENUNGGU VERIFIKASI
$query_waiting = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pendaftaran WHERE status = 'Menunggu'");
$data_waiting  = mysqli_fetch_assoc($query_waiting);
$jumlah_menunggu = (int) ($data_waiting['total'] ?? 0);

// E. Ambil Data Antrean FIFO Berdasarkan Periode Magang
// Urutan: 1. Tanggal Mulai ASC, 2. Tanggal Selesai ASC, 3. Timestamp Daftar ASC, 4. ID Unik ASC
$order_fifo_clause = get_fifo_order_clause('p');
$query_fifo = "SELECT p.id_daftar, p.tgl_daftar, p.tanggal_mulai, p.tanggal_selesai, p.status_berkas, u.nama, u.asal_instansi 
               FROM pendaftaran p
               JOIN peserta u ON p.id_user = u.id_user
               WHERE p.status = 'Menunggu'
               $order_fifo_clause 
               LIMIT 5"; 
$result_fifo = mysqli_query($koneksi, $query_fifo);

// =================================================================
// 4. TAMPILAN DASHBOARD
// =================================================================
include 'layouts/header.php';  
include 'layouts/sidebar.php'; 
include 'layouts/navbar.php';  
?>

<div class="main-content">
    
    <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4 p-4" style="background-color: #e3f2fd; color: #0d2141; border-radius: 12px;">
        <i class="fa-solid fa-circle-info fa-2x me-3"></i>
        <div>
            <h5 class="fw-bold mb-1">Metode FIFO Aktif!</h5>
            <p class="mb-0 small opacity-75">
                Sistem secara otomatis mengurutkan pendaftar berdasarkan <b>Waktu Masuk (Timestamp)</b>.<br>
                Sistem Magang Saat Ini: <strong>Penerimaan Berkelanjutan (Global Quota)</strong>
            </p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="row g-3 mb-4">
                
                <div class="col-md-6">
                    <div class="card card-stat p-3 h-100 border-start border-4 border-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-bold text-uppercase">Sisa Kuota Magang</div>
                                <h2 class="fw-bold mb-0 text-dark">
                                    <?php echo $sisa_kuota; ?> 
                                    <span class="fs-6 text-muted">/ <?php echo $jumlah_maksimal; ?></span>
                                </h2>
                                <small class="<?php echo ($sisa_kuota > 0) ? 'text-success' : 'text-danger'; ?> small">
                                    <?php echo ($sisa_kuota > 0) ? '<i class="fa-solid fa-check"></i> Masih Tersedia' : '<i class="fa-solid fa-xmark"></i> Penuh (Tunggu Peserta Selesai)'; ?>
                                </small>
                            </div>
                            <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-chart-pie"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card card-stat p-3 h-100 border-start border-4 border-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-bold text-uppercase">Menunggu Verifikasi</div>
                                <h2 class="fw-bold mb-0 text-dark"><?php echo $jumlah_menunggu; ?></h2>
                                <small class="text-warning small fw-bold">Segera Proses!</small>
                            </div>
                            <div class="icon-box bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0 text-dark">
                        <i class="fa-solid fa-list-ol me-2 text-primary"></i> Antrean Masuk (FIFO)
                    </h6>
                    <a href="peserta.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Nama Peserta</th>
                                    <th>Asal Instansi</th>
                                    <th>Rencana Magang</th>
                                    <th>Waktu Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (mysqli_num_rows($result_fifo) > 0) {
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($result_fifo)) { 
                                ?>
                                    <tr>
                                        <td class="ps-4"><?php echo $no++; ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($row['asal_instansi']); ?></td>
                                        <td>
                                            <small class="text-muted">Mulai: <?php echo !empty($row['tanggal_mulai']) ? date('d M Y', strtotime($row['tanggal_mulai'])) : '<i class="text-danger">Belum diatur</i>'; ?></small><br>
                                            <small class="text-muted">Selesai: <?php echo !empty($row['tanggal_selesai']) ? date('d M Y', strtotime($row['tanggal_selesai'])) : '<i class="text-danger">Belum diatur</i>'; ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('d-m-Y', strtotime($row['tgl_daftar'])); ?> 
                                            <span class="badge bg-light text-dark border ms-1">
                                                <?php echo date('H:i:s', strtotime($row['tgl_daftar'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="detail_peserta.php?id=<?php echo $row['id_daftar']; ?>" class="btn btn-sm btn-primary">
                                                Verifikasi
                                            </a>
                                        </td>
                                    </tr>
                                <?php 
                                    } 
                                } else { 
                                ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-box-open fa-2x mb-3 text-secondary"></i><br>
                                            Tidak ada antrean pendaftaran saat ini.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold m-0 text-dark">Aksi Cepat Admin</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        <a href="kuota.php" class="btn-quick text-decoration-none">
                            <i class="fa-solid fa-pen-to-square text-primary me-2"></i> Update Kuota Magang
                        </a>
                        <a href="informasi.php" class="btn-quick text-decoration-none">
                            <i class="fa-solid fa-newspaper text-info me-2"></i> Edit Pengumuman
                        </a>
                        <a href="admin.php" class="btn-quick text-decoration-none">
                            <i class="fa-solid fa-user-plus text-success me-2"></i> Tambah Admin Baru
                        </a>
                    </div>
                </div>
            </div>

            <?php 
            $deadman_dash = new DeadmanSystem($koneksi, true, 7);
            $overdue_dash = $deadman_dash->getPendingOverdueCandidates();
            $tot_overdue = count($overdue_dash);
            ?>
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0 text-dark"><i class="fa-solid fa-stopwatch text-warning me-2"></i>Deadman System</h6>
                    <span class="badge <?php echo ($tot_overdue > 0) ? 'bg-warning text-dark' : 'bg-success'; ?> rounded-pill">
                        <?php echo ($tot_overdue > 0) ? "$tot_overdue Tertahan" : "Normal"; ?>
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                        <span class="text-muted">Ambang Batas Toleransi:</span>
                        <span class="fw-bold">7 Hari</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                        <span class="text-muted">Mode Eksekusi:</span>
                        <span class="badge bg-light text-dark border">DRY-RUN (Simulasi)</span>
                    </div>
                    <?php if ($tot_overdue > 0): ?>
                        <div class="alert alert-warning py-2 px-3 small border-0 mb-2 mt-2">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Ada <?php echo $tot_overdue; ?> pendaftaran tertahan > 7 hari.
                        </div>
                        <a href="peserta.php?filter=Menunggu" class="btn btn-sm btn-outline-warning text-dark w-100 rounded-pill mt-1">
                            Periksa Antrean Menunggu
                        </a>
                    <?php else: ?>
                        <small class="text-success d-block mt-2"><i class="fa-solid fa-check me-1"></i> Tidak ada antrean yang melewati batas waktu.</small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold m-0 text-dark"><i class="fa-solid fa-server me-2"></i>Status Sistem</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <span class="text-muted">Database</span>
                            <span class="badge bg-success">Connected</span>
                        </li>
                        <li class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Algoritma</span>
                            <span class="fw-bold text-dark">FIFO (Date ASC + Deterministic)</span>
                        </li>
                        <li class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Versi PHP</span>
                            <span class="text-dark"><?php echo phpversion(); ?></span>
                        </li>
                        <li>
                            <span class="text-muted d-block mb-1">Server Time</span>
                            <code class="text-primary"><?php echo date('d-m-Y H:i:s'); ?> WIB</code>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
