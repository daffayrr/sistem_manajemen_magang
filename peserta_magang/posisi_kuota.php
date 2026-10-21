<?php
session_start();

// 1. CEK KEAMANAN AKSES (HANYA PESERTA)
if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login' || $_SESSION['role_akses'] != 'Peserta') {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

require_once '../config/database.php';
require_once '../config/fifo_helper.php';

/** @var mysqli $koneksi */
$id_user = (int) $_SESSION['peserta_id'];

// Helper Format Tanggal Indonesia
function format_tgl_indo($tgl) {
    if (empty($tgl)) return "-";
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $ts = strtotime($tgl);
    if (!$ts) return htmlspecialchars($tgl);
    $d = date('d', $ts);
    $m = (int) date('m', $ts);
    $y = date('Y', $ts);
    return "$d " . ($bulan[$m] ?? date('M', $ts)) . " $y";
}

// Helper Format Timestamp Presisi Milidetik Indonesia
function format_ts_indo($dt) {
    if (empty($dt)) return "-";
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $parts = explode(' ', $dt);
    $date_part = $parts[0] ?? '';
    $time_part = $parts[1] ?? '';

    $ts = strtotime($date_part);
    if (!$ts) return htmlspecialchars($dt);

    $d = date('d', $ts);
    $m = (int) date('m', $ts);
    $y = date('Y', $ts);
    $nama_bulan = $bulan[$m] ?? date('M', $ts);

    $ms = "";
    if (strpos($time_part, '.') !== false) {
        $sub = explode('.', $time_part);
        $time_only = $sub[0];
        $ms = "." . substr($sub[1], 0, 3);
    } else {
        $time_only = $time_part;
    }

    return "$d $nama_bulan $y $time_only$ms WIB";
}

// ==========================================================
// 2. AMBIL DATA KUOTA GLOBAL DARI DATABASE
// ==========================================================
$db_error = false;
$kuota_maksimal = 0;
$total_aktif    = 0;
$sisa_kuota     = 0;
$status_kuota   = "Memuat...";

$q_kuota = mysqli_query($koneksi, "SELECT jumlah_maks FROM kuota WHERE id_kuota = 1 LIMIT 1");
if ($q_kuota && $d_kuota = mysqli_fetch_assoc($q_kuota)) {
    $kuota_maksimal = (int) ($d_kuota['jumlah_maks'] ?? 0);
    
    $q_aktif = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM pendaftaran WHERE status = 'Diterima'");
    if ($q_aktif && $d_aktif = mysqli_fetch_assoc($q_aktif)) {
        $total_aktif = (int) ($d_aktif['tot'] ?? 0);
        $sisa_kuota  = $kuota_maksimal - $total_aktif;
        $status_kuota = ($sisa_kuota > 0) ? "Tersedia" : "Penuh";
    } else {
        $db_error = true;
    }
} else {
    $db_error = true;
}

// ==========================================================
// 3. AMBIL DATA PENDAFTARAN AKTIF MILIK PESERTA YANG LOGIN
// ==========================================================
$stmt_my = mysqli_prepare($koneksi, "SELECT * FROM pendaftaran WHERE id_user = ? ORDER BY id_daftar DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_my, "i", $id_user);
mysqli_stmt_execute($stmt_my);
$my_reg = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_my));
mysqli_stmt_close($stmt_my);

// ==========================================================
// 4. AMBIL ANTREAN PENDAFTARAN (FIFO PERIODE MAGANG)
//    Prioritas: 1. Tanggal Mulai ASC, 2. Tanggal Selesai ASC, 3. Timestamp Daftar ASC, 4. ID Unik ASC
//    Menerapkan Prinsip Privasi: Hanya ID Peserta, Periode, & Timestamp
// ==========================================================
$order_fifo_clause = get_fifo_order_clause('p');
$query_queue = "SELECT p.id_daftar, p.id_user, p.tgl_daftar, p.tanggal_mulai, p.tanggal_selesai, p.status, p.status_berkas 
                FROM pendaftaran p
                WHERE p.status = 'Menunggu'
                $order_fifo_clause";
$result_queue = mysqli_query($koneksi, $query_queue);

// Hitung posisi antrean peserta yang sedang login (jika statusnya Menunggu)
$my_position = null;
$total_in_queue = ($result_queue) ? mysqli_num_rows($result_queue) : 0;

if ($my_reg && $my_reg['status'] === 'Menunggu' && $result_queue) {
    $pos_counter = 1;
    mysqli_data_seek($result_queue, 0);
    while ($row = mysqli_fetch_assoc($result_queue)) {
        if ((int)$row['id_user'] === $id_user) {
            $my_position = $pos_counter;
            break;
        }
        $pos_counter++;
    }
    // Kembalikan pointer hasil query ke awal untuk render tabel
    mysqli_data_seek($result_queue, 0);
}

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">Cek Posisi dan Kuota Magang</h4>
            <p class="text-muted small mb-0">Informasi kapasitas kuota penerimaan dan transparansi antrean seleksi (FIFO).</p>
        </div>
        <div>
            <?php if ($status_kuota === "Tersedia"): ?>
                <span class="badge bg-success px-3 py-2 rounded-pill fs-6 shadow-sm">
                    <i class="fa-solid fa-circle-check me-1"></i> Kuota Tersedia (<?php echo $sisa_kuota; ?> Slot)
                </span>
            <?php elseif ($status_kuota === "Penuh"): ?>
                <span class="badge bg-danger px-3 py-2 rounded-pill fs-6 shadow-sm">
                    <i class="fa-solid fa-circle-xmark me-1"></i> Kuota Penuh (0 Slot)
                </span>
            <?php else: ?>
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Informasi Kuota Belum Tersedia
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($db_error): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            Informasi kuota sistem saat ini belum dapat diambil dari basis data. Silakan refresh halaman atau hubungi administrator.
        </div>
    <?php endif; ?>

    <!-- WIDGET STATISTIK KUOTA MAGANG -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Kuota Maksimal</div>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $kuota_maksimal; ?> <span class="fs-6 text-muted">Orang</span></h3>
                        <small class="text-muted">Kapasitas total penerimaan</small>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-users fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Kuota Terpakai</div>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $total_aktif; ?> <span class="fs-6 text-muted">Orang</span></h3>
                        <small class="text-info fw-bold">Peserta status Diterima (Aktif)</small>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-user-check fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 border-start border-4 <?php echo ($sisa_kuota > 0) ? 'border-success' : 'border-danger'; ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Sisa Kuota Tersedia</div>
                        <h3 class="fw-bold mb-0 <?php echo ($sisa_kuota > 0) ? 'text-success' : 'text-danger'; ?>">
                            <?php echo max(0, $sisa_kuota); ?> <span class="fs-6 text-muted">Orang</span>
                        </h3>
                        <small class="<?php echo ($sisa_kuota > 0) ? 'text-success' : 'text-danger'; ?> fw-bold">
                            <?php echo ($sisa_kuota > 0) ? '<i class="fa-solid fa-check"></i> Siap Menerima' : '<i class="fa-solid fa-lock"></i> Pendaftaran Tertutup'; ?>
                        </small>
                    </div>
                    <div class="rounded-circle <?php echo ($sisa_kuota > 0) ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger'; ?> d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-chart-pie fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Antrean Menunggu</div>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $total_in_queue; ?> <span class="fs-6 text-muted">Berkas</span></h3>
                        <small class="text-warning fw-bold">Dalam antrean verifikasi</small>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-hourglass-half fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STATUS PENDAFTARAN PESERTA YANG SEDANG LOGIN -->
    <?php if ($my_reg): ?>
        <?php $st_my = $my_reg['status']; ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4 <?php 
            if ($st_my === 'Menunggu') echo 'bg-warning bg-opacity-10 border-start border-4 border-warning';
            elseif ($st_my === 'Diterima') echo 'bg-success bg-opacity-10 border-start border-4 border-success';
            elseif ($st_my === 'Selesai') echo 'bg-primary bg-opacity-10 border-start border-4 border-primary';
            else echo 'bg-danger bg-opacity-10 border-start border-4 border-danger';
        ?>">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle p-3 text-white <?php 
                            if ($st_my === 'Menunggu') echo 'bg-warning text-dark';
                            elseif ($st_my === 'Diterima') echo 'bg-success';
                            elseif ($st_my === 'Selesai') echo 'bg-primary';
                            else echo 'bg-danger';
                        ?>">
                            <?php 
                            if ($st_my === 'Menunggu') echo '<i class="fa-solid fa-clock fa-2x"></i>';
                            elseif ($st_my === 'Diterima') echo '<i class="fa-solid fa-circle-check fa-2x"></i>';
                            elseif ($st_my === 'Selesai') echo '<i class="fa-solid fa-graduation-cap fa-2x"></i>';
                            else echo '<i class="fa-solid fa-circle-xmark fa-2x"></i>';
                            ?>
                        </div>
                        <div>
                            <span class="badge bg-dark bg-opacity-75 text-white mb-1">Status Pengajuan Anda</span>
                            <h5 class="fw-bold mb-1 text-dark">
                                Status: <span class="<?php 
                                    if ($st_my === 'Menunggu') echo 'text-warning';
                                    elseif ($st_my === 'Diterima') echo 'text-success';
                                    elseif ($st_my === 'Selesai') echo 'text-primary';
                                    else echo 'text-danger';
                                ?>"><?php echo strtoupper($st_my); ?></span>
                            </h5>
                            <p class="mb-0 text-muted small">
                                ID Peserta Anda: <b>PST-<?php echo str_pad($id_user, 4, '0', STR_PAD_LEFT); ?></b> &bull; 
                                Periode: <b><?php echo format_tgl_indo($my_reg['tanggal_mulai']); ?> – <?php echo format_tgl_indo($my_reg['tanggal_selesai']); ?></b>
                            </p>
                        </div>
                    </div>

                    <?php if ($st_my === 'Menunggu'): ?>
                        <div class="text-md-end bg-white p-3 rounded-3 shadow-sm border">
                            <small class="text-muted d-block">Posisi Antrean Anda (FIFO):</small>
                            <span class="fs-4 fw-bold <?php echo ($my_position === 1) ? 'text-primary' : 'text-dark'; ?>">
                                <?php echo ($my_position !== null) ? "Urutan #$my_position dari $total_in_queue" : "-"; ?>
                            </span>
                            <?php if ($my_position === 1): ?>
                                <span class="badge bg-primary ms-1"><i class="fa-solid fa-bolt me-1"></i> Prioritas 1</span>
                            <?php endif; ?>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">*Berdasarkan metode FIFO Periode Magang (Tanggal Mulai Terawal).</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-light border shadow-sm rounded-3 mb-4 p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-info-circle fa-2x text-primary"></i>
                <div>
                    <h6 class="fw-bold mb-1">Anda Belum Mengajukan Pendaftaran</h6>
                    <p class="mb-0 text-muted small">Silakan lengkapi formulir pendaftaran untuk mengajukan periode magang.</p>
                </div>
            </div>
            <div>
                <a href="pendaftaran.php" class="btn btn-primary btn-sm fw-bold rounded-pill px-4">
                    <i class="fa-solid fa-file-pen me-1"></i> Isi Formulir Sekarang
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- TABEL TRANSPARANSI ANTREAN FIFO (DATA MINIMIZATION) -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="fw-bold m-0 text-dark">
                    <i class="fa-solid fa-list-ol text-primary me-2"></i> Transparansi Antrean Pendaftar (Metode FIFO)
                </h6>
                <small class="text-muted">Urutan verifikasi diproses dari pendaftar paling awal.</small>
            </div>
            <div>
                <span class="badge bg-light text-dark border">Total Menunggu: <?php echo $total_in_queue; ?></span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4" width="90">Posisi</th>
                            <th>ID Peserta</th>
                            <th>Periode Magang</th>
                            <th>Timestamp Mendaftar</th>
                            <th class="text-center">Status Antrean</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($total_in_queue > 0) {
                            $posisi = 1;
                            while ($row = mysqli_fetch_assoc($result_queue)) {
                                $is_me = ((int)$row['id_user'] === $id_user);
                        ?>
                            <tr class="<?php echo $is_me ? 'table-primary bg-opacity-25' : ''; ?>">
                                <td class="ps-4 fw-bold">
                                    <?php if ($posisi === 1): ?>
                                        <span class="badge bg-primary rounded-pill px-2 py-1"><i class="fa-solid fa-crown me-1 text-warning"></i> #1</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border rounded-pill px-2 py-1">#<?php echo $posisi; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">
                                        PST-<?php echo str_pad($row['id_user'], 4, '0', STR_PAD_LEFT); ?>
                                    </span>
                                    <?php if ($is_me): ?>
                                        <span class="badge bg-primary ms-2"><i class="fa-solid fa-user me-1"></i> Anda</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-dark small fw-bold">
                                        <?php echo format_tgl_indo($row['tanggal_mulai']); ?> – <?php echo format_tgl_indo($row['tanggal_selesai']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <i class="fa-solid fa-clock me-1 text-secondary"></i>
                                        <?php echo format_ts_indo($row['tgl_daftar']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($posisi === 1): ?>
                                        <span class="badge bg-warning text-dark border border-warning px-3 py-1 rounded-pill small">
                                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Giliran Pertama
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-3 py-1 rounded-pill small">
                                            Dalam Antrean
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                                $posisi++;
                            } 
                        } else { 
                        ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="py-3">
                                        <i class="fa-solid fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                                        <h6 class="fw-bold text-dark">Tidak Ada Antrean Pendaftaran</h6>
                                        <p class="small text-muted mb-0">Saat ini seluruh berkas pendaftaran yang masuk telah selesai diverifikasi oleh Administrator.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'layouts/footer.php'; ?>
