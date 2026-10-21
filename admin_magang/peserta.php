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

$filter_status = $_GET['filter'] ?? 'Menunggu';
$keyword       = trim($_GET['q'] ?? '');

// ==========================================================
// 2. LOGIKA AKSI VERIFIKASI & ARSIP (FIFO & VALIDASI DOKUMEN)
// ==========================================================
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_daftar = (int) $_GET['id'];
    $aksi      = $_GET['aksi'];

    if ($aksi == 'terima' || $aksi == 'tolak') {
        // ENFORCE FIFO AT BACKEND: Cek apakah pendaftar ini adalah giliran pertama di antrean Menunggu
        $q_cur = mysqli_query($koneksi, "SELECT status FROM pendaftaran WHERE id_daftar = '$id_daftar'");
        $d_cur = mysqli_fetch_assoc($q_cur);

        if ($d_cur && $d_cur['status'] === 'Menunggu') {
            $q_fifo_first = mysqli_query($koneksi, "SELECT id_daftar FROM pendaftaran WHERE status = 'Menunggu' ORDER BY tgl_daftar ASC, id_daftar ASC LIMIT 1");
            $first_in_queue = mysqli_fetch_assoc($q_fifo_first);

            if ($first_in_queue && (int) $first_in_queue['id_daftar'] !== $id_daftar) {
                $msg_type = "gagal";
                $msg_text = "Pelanggaran Antrean FIFO! Pendaftar urutan pertama harus diproses terlebih dahulu sebelum memproses pendaftar berikutnya.";
                goto end_aksi;
            }
        }

        if ($aksi == 'terima') {
            // Transaksi Database & Pessimistic Locking untuk Kuota
            mysqli_begin_transaction($koneksi);
            $q_maks = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT jumlah_maks FROM kuota WHERE id_kuota = 1 FOR UPDATE"));
            $q_aktf = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM pendaftaran WHERE status='Diterima' FOR UPDATE"));
            
            $maksimal = (int) ($q_maks['jumlah_maks'] ?? 0);
            $aktif    = (int) ($q_aktf['tot'] ?? 0);
            $sisa     = $maksimal - $aktif;

            if ($sisa > 0) {
                $admin_id = $_SESSION['admin_id'] ?? null;
                $stmt_acc = mysqli_prepare($koneksi, "UPDATE pendaftaran SET status='Diterima', tgl_verifikasi=NOW(3), id_admin=? WHERE id_daftar=?");
                mysqli_stmt_bind_param($stmt_acc, "ii", $admin_id, $id_daftar);
                mysqli_stmt_execute($stmt_acc);
                mysqli_stmt_close($stmt_acc);

                mysqli_commit($koneksi);

                catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], $id_daftar, 'pendaftaran', 'TERIMA_PESERTA', 'Menunggu', 'Diterima', 'Peserta diterima magang');
                $msg_type = "sukses"; 
                $msg_text = "Peserta Diterima. Kuota magang berkurang 1.";
            } else {
                mysqli_rollback($koneksi);
                $msg_type = "gagal"; 
                $msg_text = "Gagal! Kuota Magang saat ini Penuh. Tunggu ada peserta yang selesai.";
            }
        } elseif ($aksi == 'tolak') {
            $admin_id = $_SESSION['admin_id'] ?? null;
            $stmt_rej = mysqli_prepare($koneksi, "UPDATE pendaftaran SET status='Ditolak', tgl_verifikasi=NOW(3), id_admin=? WHERE id_daftar=?");
            mysqli_stmt_bind_param($stmt_rej, "ii", $admin_id, $id_daftar);
            mysqli_stmt_execute($stmt_rej);
            mysqli_stmt_close($stmt_rej);

            catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], $id_daftar, 'pendaftaran', 'TOLAK_PESERTA', 'Menunggu', 'Ditolak', 'Pengajuan magang ditolak');
            $msg_type = "sukses"; 
            $msg_text = "Pengajuan Magang Ditolak.";
        }
    } elseif ($aksi == 'batal') {
        mysqli_query($koneksi, "UPDATE pendaftaran SET status='Menunggu', tgl_verifikasi=NULL WHERE id_daftar='$id_daftar'");
        catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], $id_daftar, 'pendaftaran', 'BATAL_STATUS', null, 'Menunggu', 'Status dikembalikan ke antrean Menunggu');
        $msg_type = "sukses"; 
        $msg_text = "Status dikembalikan ke Menunggu.";
    } elseif ($aksi == 'selesai') {
        // VALIDASI DOKUMEN WAJIB (NILAI/SERTIFIKAT & KETERANGAN) SEBELUM SELESAI
        $stmt_check = mysqli_prepare($koneksi, "SELECT file_sertifikat, file_keterangan FROM pendaftaran WHERE id_daftar = ?");
        mysqli_stmt_bind_param($stmt_check, "i", $id_daftar);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        $doc_data = mysqli_fetch_assoc($res_check);
        mysqli_stmt_close($stmt_check);

        $f_sertif = $doc_data['file_sertifikat'] ?? '';
        $f_ket    = $doc_data['file_keterangan'] ?? '';

        $path_sertif = __DIR__ . '/../uploads/' . $f_sertif;
        $path_ket    = __DIR__ . '/../uploads/' . $f_ket;

        $has_sertif = (!empty($f_sertif) && file_exists($path_sertif) && is_file($path_sertif));
        $has_ket    = (!empty($f_ket) && file_exists($path_ket) && is_file($path_ket));

        if (!$has_sertif || !$has_ket) {
            $msg_type = "gagal";
            $msg_text = "Gagal menyelesaikan magang! Berkas Sertifikat (+Nilai) dan Surat Keterangan Selesai wajib diunggah dan terverifikasi terlebih dahulu sebelum status diselesaikan.";
        } else {
            // Update status menjadi Selesai dan catat completed_at dengan presisi milidetik
            $stmt_selesai = mysqli_prepare($koneksi, "UPDATE pendaftaran SET status='Selesai', completed_at=NOW(3) WHERE id_daftar=?");
            mysqli_stmt_bind_param($stmt_selesai, "i", $id_daftar);
            mysqli_stmt_execute($stmt_selesai);
            mysqli_stmt_close($stmt_selesai);

            catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], $id_daftar, 'pendaftaran', 'SELESAI_MAGANG', 'Diterima', 'Selesai', 'Magang selesai, dokumen lengkap tervalidasi');
            $msg_type = "sukses"; 
            $msg_text = "Magang Selesai! Data dipindah ke Arsip, Kuota kembali kosong (+1).";
        }
    }
}
end_aksi:

// ==========================================================
// 3. PENGAMBILAN DATA (TABEL) & PENGURUTAN FIFO / ARSIP
// ==========================================================
$search_sql = "";
if (!empty($keyword)) {
    $safe_q = mysqli_real_escape_string($koneksi, $keyword);
    $search_sql = "AND (u.nama LIKE '%$safe_q%' OR u.asal_instansi LIKE '%$safe_q%')";
}

// Menentukan ID Pertama antrean FIFO Menunggu (untuk penguncian tombol pada UI)
$top_fifo_id = null;
if ($filter_status == 'Menunggu') {
    $q_top = mysqli_query($koneksi, "SELECT id_daftar FROM pendaftaran WHERE status = 'Menunggu' ORDER BY tgl_daftar ASC, id_daftar ASC LIMIT 1");
    if ($row_top = mysqli_fetch_assoc($q_top)) {
        $top_fifo_id = (int) $row_top['id_daftar'];
    }
}

// Logika Pengurutan:
// - Status 'Menunggu': FIFO ketat (tgl_daftar ASC, id_daftar ASC)
// - Status 'Selesai' (Arsip): Berdasarkan waktu penyelesaian terbaru (completed_at DESC, id_daftar DESC)
// - Status lainnya: Waktu verifikasi terbaru (tgl_verifikasi DESC, id_daftar DESC)
if ($filter_status == 'Selesai') {
    $order_clause = "ORDER BY COALESCE(p.completed_at, p.tgl_verifikasi, p.tgl_daftar) DESC, p.id_daftar DESC";
} elseif ($filter_status == 'Menunggu') {
    $order_clause = "ORDER BY p.tgl_daftar ASC, p.id_daftar ASC";
} else {
    $order_clause = "ORDER BY COALESCE(p.tgl_verifikasi, p.tgl_daftar) DESC, p.id_daftar DESC";
}

$safe_filter = mysqli_real_escape_string($koneksi, $filter_status);
$query = "SELECT p.*, u.nama, u.asal_instansi, u.foto_profil, u.email 
          FROM pendaftaran p
          JOIN peserta u ON p.id_user = u.id_user
          WHERE p.status = '$safe_filter' $search_sql
          $order_clause";

$result = mysqli_query($koneksi, $query);

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<style>
    .table-custom thead th { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6c757d; background-color: #f8f9fa; border-bottom: 2px solid #eaecf4; padding: 1rem; }
    .table-custom tbody td { vertical-align: middle; font-size: 0.9rem; padding: 1rem; border-bottom: 1px solid #f1f3f9; }
    .btn-icon-action { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; transition: 0.2s; cursor: pointer; }
    .btn-icon-action:hover:not(:disabled) { transform: scale(1.1); }
    .filter-btn { transition: all 0.2s ease-in-out; }
    .filter-btn:hover { transform: translateY(-2px); }
</style>

<div class="main-content">
    
    <?php if (isset($msg_type)): ?>
        <div class="alert alert-<?php echo ($msg_type=='sukses') ? 'success' : 'danger'; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fa-solid <?php echo ($msg_type=='sukses') ? 'fa-check-circle' : 'fa-circle-exclamation'; ?> me-2"></i>
            <?php echo htmlspecialchars($msg_text, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Manajemen & Verifikasi Peserta</h4>
            <p class="text-muted small mb-0">Kelola seleksi pendaftar magang sesuai alur antrean FIFO.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        
        <div class="card-header bg-white p-4 border-bottom-0 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            
            <div class="d-flex flex-wrap gap-2">
                <a href="peserta.php?filter=Menunggu" class="filter-btn btn btn-sm rounded-pill px-4 <?php echo ($filter_status == 'Menunggu') ? 'btn-primary fw-bold shadow-sm' : 'btn-light text-muted border'; ?>">
                    <i class="fa-solid fa-clock me-1"></i> Menunggu
                </a>
                <a href="peserta.php?filter=Diterima" class="filter-btn btn btn-sm rounded-pill px-4 <?php echo ($filter_status == 'Diterima') ? 'btn-success fw-bold shadow-sm' : 'btn-light text-muted border'; ?>">
                    <i class="fa-solid fa-circle-check me-1"></i> Diterima (Aktif)
                </a>
                <a href="peserta.php?filter=Ditolak" class="filter-btn btn btn-sm rounded-pill px-4 <?php echo ($filter_status == 'Ditolak') ? 'btn-danger fw-bold shadow-sm' : 'btn-light text-muted border'; ?>">
                    <i class="fa-solid fa-circle-xmark me-1"></i> Ditolak
                </a>
                <a href="peserta.php?filter=Selesai" class="filter-btn btn btn-sm rounded-pill px-4 <?php echo ($filter_status == 'Selesai') ? 'btn-secondary fw-bold shadow-sm' : 'btn-light text-muted border'; ?>">
                    <i class="fa-solid fa-box-archive me-1"></i> Arsip (Selesai)
                </a>
            </div>

            <form action="" method="GET" class="d-flex w-100" style="max-width: 320px;">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter_status); ?>">
                <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden border">
                    <input type="text" name="q" class="form-control border-0 px-3" placeholder="Cari Nama / Instansi..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit" class="btn btn-white border-0 text-primary bg-white px-3"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>
        </div>
        <div class="card-body p-0 border-top">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Nama Peserta</th>
                            <th>Instansi</th>
                            <?php if ($filter_status == 'Selesai'): ?>
                                <th>Waktu Selesai (Arsip)</th>
                            <?php else: ?>
                                <th>Rencana Magang</th>
                            <?php endif; ?>
                            <th class="text-center">Berkas Pengajuan</th>
                            <?php if ($filter_status == 'Diterima'): ?>
                                <th class="text-center">Kelengkapan Akhir</th>
                            <?php endif; ?>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($result) > 0) { 
                            $queue_num = 1;
                            while ($row = mysqli_fetch_assoc($result)) { 
                                $is_first_fifo = ($filter_status == 'Menunggu' && (int)$row['id_daftar'] === $top_fifo_id);

                                // Cek kelengkapan dokumen akhir untuk status Diterima
                                $doc_sertif_ready = !empty($row['file_sertifikat']) && file_exists(__DIR__ . '/../uploads/' . $row['file_sertifikat']);
                                $doc_ket_ready    = !empty($row['file_keterangan']) && file_exists(__DIR__ . '/../uploads/' . $row['file_keterangan']);
                                $can_finish       = ($doc_sertif_ready && $doc_ket_ready);
                        ?>
                            <tr>
                                <td class="ps-4 text-muted">
                                    #<?php echo str_pad($row['id_daftar'], 4, '0', STR_PAD_LEFT); ?>
                                    <?php if ($filter_status == 'Menunggu'): ?>
                                        <div class="small fw-bold <?php echo $is_first_fifo ? 'text-primary' : 'text-muted'; ?>">
                                            <?php echo $is_first_fifo ? '<span class="badge bg-primary" style="font-size:0.65rem;">GILIRAN 1</span>' : '<span class="badge bg-light text-muted border" style="font-size:0.65rem;">Antrean #' . $queue_num . '</span>'; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                                            <?php echo htmlspecialchars(substr($row['nama'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <span class="d-block fw-bold text-dark"><?php echo htmlspecialchars($row['nama']); ?></span>
                                            <small class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-clock me-1"></i> Daftar: <?php echo date('d/m/Y H:i', strtotime($row['tgl_daftar'])); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['asal_instansi']); ?></td>
                                
                                <?php if ($filter_status == 'Selesai'): ?>
                                    <td>
                                        <span class="text-success fw-bold d-block small">
                                            <i class="fa-solid fa-flag-checkered me-1"></i>
                                            <?php echo !empty($row['completed_at']) ? date('d M Y, H:i', strtotime($row['completed_at'])) . ' WIB' : (!empty($row['tgl_verifikasi']) ? date('d M Y, H:i', strtotime($row['tgl_verifikasi'])) : '-'); ?>
                                        </span>
                                        <small class="text-muted">Periode: <?php echo !empty($row['tanggal_mulai']) ? date('d/m/y', strtotime($row['tanggal_mulai'])) . ' - ' . date('d/m/y', strtotime($row['tanggal_selesai'])) : '-'; ?></small>
                                    </td>
                                <?php else: ?>
                                    <td>
                                        <span class="text-dark fw-bold d-block small">Mulai: <?php echo !empty($row['tanggal_mulai']) ? date('d M Y', strtotime($row['tanggal_mulai'])) : '-'; ?></span>
                                        <span class="text-muted small">Selesai: <?php echo !empty($row['tanggal_selesai']) ? date('d M Y', strtotime($row['tanggal_selesai'])) : '-'; ?></span>
                                    </td>
                                <?php endif; ?>

                                <td class="text-center">
                                    <?php if (!empty($row['berkas'])): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($row['berkas']); ?>" target="_blank" class="text-danger" title="Lihat Surat Pengantar"><i class="fa-solid fa-file-pdf fa-lg"></i></a>
                                    <?php else: ?> - <?php endif; ?>
                                </td>

                                <?php if ($filter_status == 'Diterima'): ?>
                                    <td class="text-center">
                                        <?php if ($can_finish): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-2 py-1 small">
                                                <i class="fa-solid fa-check-double me-1"></i> Siap Selesai
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-2 py-1 small" title="Unggah sertifikat & surat keterangan di halaman detail">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Berkas Belum Lengkap
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>

                                <td class="text-end pe-4">
                                    <a href="detail_peserta.php?id=<?php echo $row['id_daftar']; ?>" class="btn btn-sm btn-outline-primary fw-bold px-3 me-1 rounded-pill">Detail</a>

                                    <?php if ($filter_status == 'Menunggu'): ?>
                                        <?php if ($is_first_fifo): ?>
                                            <button onclick="konfirmasiAksi('terima', '<?php echo $row['id_daftar']; ?>', '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>')" 
                                                    class="btn-icon-action bg-success bg-opacity-10 text-success border-0 ms-1" title="Terima">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            
                                            <button onclick="konfirmasiAksi('tolak', '<?php echo $row['id_daftar']; ?>', '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>')" 
                                                    class="btn-icon-action bg-danger bg-opacity-10 text-danger border-0 ms-1" title="Tolak">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        <?php else: ?>
                                            <button disabled class="btn-icon-action bg-secondary bg-opacity-10 text-muted border-0 ms-1" style="cursor:not-allowed;" title="Menunggu giliran antrean FIFO (Urutan #<?php echo $queue_num; ?>)">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($filter_status == 'Diterima'): ?>
                                        <?php if ($can_finish): ?>
                                            <button onclick="konfirmasiAksi('selesai', '<?php echo $row['id_daftar']; ?>', '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>')" 
                                                    class="btn btn-sm btn-info text-white fw-bold ms-1 rounded-pill shadow-sm" title="Selesaikan Magang (Masuk Arsip)">
                                                <i class="fa-solid fa-flag-checkered me-1"></i> Selesaikan
                                            </button>
                                        <?php else: ?>
                                            <button onclick="peringatanBelumLengkap('<?php echo $row['id_daftar']; ?>')" 
                                                    class="btn btn-sm btn-secondary fw-bold ms-1 rounded-pill shadow-sm opacity-75" title="Unggah Nilai & Sertifikat terlebih dahulu">
                                                <i class="fa-solid fa-lock me-1"></i> Selesaikan
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                                $queue_num++;
                            } 
                        } else { 
                        ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-light"></i><br>
                                    Tidak ada data untuk status <b><?php echo htmlspecialchars($filter_status); ?></b> saat ini.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function konfirmasiAksi(aksi, id, nama) {
    let judul, teks, ikon, warnaTombol;
    let url = `peserta.php?aksi=${aksi}&id=${id}&filter=<?php echo urlencode($filter_status); ?>`;

    if (aksi === 'terima') {
        judul = 'Terima Peserta?';
        teks = `Anda akan menerima <b>${nama}</b> sebagai peserta magang. <br>Sisa kuota global akan otomatis berkurang.`;
        ikon = 'question';
        warnaTombol = '#198754';
    } else if (aksi === 'tolak') {
        judul = 'Tolak Peserta?';
        teks = `Anda akan menolak pengajuan magang dari <b>${nama}</b>.`;
        ikon = 'warning';
        warnaTombol = '#dc3545';
    } else if (aksi === 'selesai') {
        judul = 'Selesaikan Magang?';
        teks = `Apakah <b>${nama}</b> sudah menyelesaikan magang? <br>Dokumen telah tervalidasi. Data akan dipindah ke <b>Arsip</b> dan Kuota Magang akan bertambah (+1).`;
        ikon = 'info';
        warnaTombol = '#0dcaf0';
    }

    Swal.fire({
        title: judul,
        html: teks,
        icon: ikon,
        showCancelButton: true,
        confirmButtonColor: warnaTombol,
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Lanjutkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}

function peringatanBelumLengkap(id) {
    Swal.fire({
        title: 'Dokumen Belum Lengkap!',
        text: 'Sesuai aturan, Sertifikat (+Nilai) dan Surat Keterangan Selesai wajib diunggah terlebih dahulu sebelum magang dapat diselesaikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d2141',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Buka Halaman Detail untuk Unggah',
        cancelButtonText: 'Tutup'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `detail_peserta.php?id=${id}`;
        }
    });
}
</script>

<?php include 'layouts/footer.php'; ?>
