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

// ==========================================================
// 2. LOGIKA AKSI VERIFIKASI & ARSIP
// ==========================================================
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_daftar = mysqli_real_escape_string($koneksi, $_GET['id']);
    $aksi      = $_GET['aksi'];

    if ($aksi == 'terima') {
        // Cek Kuota Global vs Peserta Aktif
        $q_maks = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT kuota_maksimal FROM pengaturan_sistem LIMIT 1"));
        $q_aktf = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM pendaftaran WHERE status_verifikasi='Diterima'"));
        
        $sisa = $q_maks['kuota_maksimal'] - $q_aktf['tot'];

        if ($sisa > 0) {
            mysqli_query($koneksi, "UPDATE pendaftaran SET status_verifikasi='Diterima', tgl_verifikasi=NOW() WHERE id_pendaftaran='$id_daftar'");
            $msg_type = "sukses"; $msg_text = "Peserta Diterima. Kuota magang berkurang 1.";
        } else {
            $msg_type = "gagal"; $msg_text = "Gagal! Kuota Magang saat ini Penuh. Tunggu ada peserta yang selesai.";
        }
    } elseif ($aksi == 'tolak') {
        mysqli_query($koneksi, "UPDATE pendaftaran SET status_verifikasi='Ditolak', tgl_verifikasi=NOW() WHERE id_pendaftaran='$id_daftar'");
        $msg_type = "sukses"; $msg_text = "Pengajuan Magang Ditolak.";
    } elseif ($aksi == 'batal') {
        mysqli_query($koneksi, "UPDATE pendaftaran SET status_verifikasi='Menunggu', tgl_verifikasi=NULL WHERE id_pendaftaran='$id_daftar'");
        $msg_type = "sukses"; $msg_text = "Status dikembalikan ke Menunggu.";
    } elseif ($aksi == 'selesai') {
        // FITUR BARU: Masuk Arsip
        mysqli_query($koneksi, "UPDATE pendaftaran SET status_verifikasi='Selesai' WHERE id_pendaftaran='$id_daftar'");
        $msg_type = "sukses"; $msg_text = "Magang Selesai! Data dipindah ke Arsip, Kuota kembali kosong (+1).";
    }
}

// ==========================================================
// 3. PENGAMBILAN DATA (TABEL); FIFO DI SINI
// ==========================================================
$filter_status = $_GET['filter'] ?? 'Menunggu';
$keyword       = $_GET['q'] ?? '';
$search_sql    = !empty($keyword) ? "AND (u.nama_lengkap LIKE '%$keyword%' OR u.asal_instansi LIKE '%$keyword%')" : "";

$query = "SELECT p.*, u.nama_lengkap, u.asal_instansi, u.foto_profil, u.email 
          FROM pendaftaran p
          JOIN peserta u ON p.id_peserta = u.id_peserta
          WHERE p.status_verifikasi = '$filter_status' $search_sql
          ORDER BY p.tgl_daftar ASC"; // Ini fifo ygy

$result = mysqli_query($koneksi, $query);

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<style>
    .table-custom thead th { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6c757d; background-color: #f8f9fa; border-bottom: 2px solid #eaecf4; padding: 1rem; }
    .table-custom tbody td { vertical-align: middle; font-size: 0.9rem; padding: 1rem; border-bottom: 1px solid #f1f3f9; }
    .btn-icon-action { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; transition: 0.2s; cursor: pointer; }
    .btn-icon-action:hover { transform: scale(1.1); }
    /* Mempercantik tombol filter */
    .filter-btn { transition: all 0.2s ease-in-out; }
    .filter-btn:hover { transform: translateY(-2px); }
</style>

<div class="main-content">
    
    <?php if (isset($msg_type)): ?>
        <div class="alert alert-<?php echo ($msg_type=='sukses') ? 'success' : 'danger'; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fa-solid <?php echo ($msg_type=='sukses') ? 'fa-check-circle' : 'fa-circle-exclamation'; ?> me-2"></i>
            <?php echo $msg_text; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <h4 class="fw-bold text-dark mb-4">Manajemen & Verifikasi Peserta</h4>

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
                            <th>Rencana Magang</th>
                            <th class="text-center">Berkas</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0) { while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?php echo str_pad($row['id_pendaftaran'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                                            <?php echo substr($row['nama_lengkap'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <span class="d-block fw-bold text-dark"><?php echo htmlspecialchars($row['nama_lengkap']); ?></span>
                                            <small class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-clock me-1"></i> Daftar: <?php echo date('d/m/Y H:i', strtotime($row['tgl_daftar'])); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['asal_instansi']); ?></td>
                                <td>
                                    <span class="text-dark fw-bold d-block small">Mulai: <?php echo !empty($row['tanggal_mulai']) ? date('d M Y', strtotime($row['tanggal_mulai'])) : '-'; ?></span>
                                    <span class="text-muted small">Selesai: <?php echo !empty($row['tanggal_selesai']) ? date('d M Y', strtotime($row['tanggal_selesai'])) : '-'; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($row['surat_pengantar'])): ?>
                                        <a href="../uploads/<?php echo $row['surat_pengantar']; ?>" target="_blank" class="text-danger" title="Lihat Surat Pengantar"><i class="fa-solid fa-file-pdf fa-lg"></i></a>
                                    <?php else: ?> - <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="detail_peserta.php?id=<?php echo $row['id_pendaftaran']; ?>" class="btn btn-sm btn-outline-primary fw-bold px-3 me-1 rounded-pill">Detail</a>

                                    <?php if ($filter_status == 'Menunggu'): ?>
                                        <button onclick="konfirmasiAksi('terima', '<?php echo $row['id_pendaftaran']; ?>', '<?php echo htmlspecialchars($row['nama_lengkap']); ?>')" 
                                                class="btn-icon-action bg-success bg-opacity-10 text-success border-0 ms-1" title="Terima">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        
                                        <button onclick="konfirmasiAksi('tolak', '<?php echo $row['id_pendaftaran']; ?>', '<?php echo htmlspecialchars($row['nama_lengkap']); ?>')" 
                                                class="btn-icon-action bg-danger bg-opacity-10 text-danger border-0 ms-1" title="Tolak">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($filter_status == 'Diterima'): ?>
                                        <button onclick="konfirmasiAksi('selesai', '<?php echo $row['id_pendaftaran']; ?>', '<?php echo htmlspecialchars($row['nama_lengkap']); ?>')" 
                                                class="btn btn-sm btn-info text-white fw-bold ms-1 rounded-pill shadow-sm" title="Selesaikan Magang (Masuk Arsip)">
                                            <i class="fa-solid fa-flag-checkered me-1"></i> Selesaikan
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php }} else { ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-light"></i><br>
                                    Tidak ada data untuk status <b><?php echo $filter_status; ?></b> saat ini.
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
    let url = `peserta.php?aksi=${aksi}&id=${id}&filter=<?php echo $filter_status; ?>`;

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
        teks = `Apakah <b>${nama}</b> sudah selesai magang? <br>Data akan dipindah ke <b>Arsip</b> dan Kuota Magang akan bertambah (+1).`;
        ikon = 'info';
        warnaTombol = '#0dcaf0'; // Info cyan
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
</script>

<?php include 'layouts/footer.php'; ?>