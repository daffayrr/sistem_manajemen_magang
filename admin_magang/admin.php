<?php
session_start();

// 1. CEK KEAMANAN
$status_login = $_SESSION['status'] ?? '';
$role_akses   = $_SESSION['role_akses'] ?? '';
$admin_role   = $_SESSION['admin_role'] ?? 'Admin';

// Pastikan hanya Admin atau Super Admin yang bisa akses
if ($status_login != 'login' || !in_array($role_akses, ['Admin', 'Super Admin'])) {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

require_once '../config/database.php';

/** @var mysqli $koneksi */

// Variabel Notifikasi
$msg_type = "";
$msg_text = "";

// 2. PROSES TAMBAH ADMIN
if (isset($_POST['tambah_admin'])) {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password_raw = (string) ($_POST['password'] ?? '');

    // Penentuan role: Hanya Super Admin yang berhak menunjuk Super Admin baru
    $role_pilihan = 'Admin';
    if ($admin_role === 'Super Admin' && isset($_POST['role']) && $_POST['role'] === 'Super Admin') {
        $role_pilihan = 'Super Admin';
    }

    if (empty($nama_lengkap) || empty($username) || empty($password_raw)) {
        $msg_type = "gagal";
        $msg_text = "Semua kolom wajib diisi!";
    } else {
        // Cek apakah username sudah ada dengan Prepared Statement
        $stmt_cek = mysqli_prepare($koneksi, "SELECT id_admin FROM `admin` WHERE username = ?");
        mysqli_stmt_bind_param($stmt_cek, "s", $username);
        mysqli_stmt_execute($stmt_cek);
        mysqli_stmt_store_result($stmt_cek);

        if (mysqli_stmt_num_rows($stmt_cek) > 0) {
            $msg_type = "gagal";
            $msg_text = "Username '$username' sudah terdaftar! Gunakan yang lain.";
            mysqli_stmt_close($stmt_cek);
        } else {
            mysqli_stmt_close($stmt_cek);

            // Enkripsi modern dengan password_hash()
            $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);

            $stmt_simpan = mysqli_prepare($koneksi, "INSERT INTO `admin` (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_simpan, "ssss", $username, $password_hash, $nama_lengkap, $role_pilihan);

            if (mysqli_stmt_execute($stmt_simpan)) {
                $new_id = mysqli_insert_id($koneksi);
                catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], $new_id, 'admin', 'TAMBAH_ADMIN', null, $role_pilihan, "Menambahkan admin baru: $username ($role_pilihan)");
                $msg_type = "sukses";
                $msg_text = "Admin baru berhasil ditambahkan!";
            } else {
                $msg_type = "gagal";
                $msg_text = "Terjadi kesalahan: " . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt_simpan);
        }
    }
}

// 3. PROSES HAPUS ADMIN (DENGAN PERLINDUNGAN SUPERADMIN & BACKEND VALIDATION)
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];

    // Ambil data admin target
    $stmt_target = mysqli_prepare($koneksi, "SELECT id_admin, username, nama_lengkap, role FROM `admin` WHERE id_admin = ?");
    mysqli_stmt_bind_param($stmt_target, "i", $id_hapus);
    mysqli_stmt_execute($stmt_target);
    $res_target = mysqli_stmt_get_result($stmt_target);
    $target = mysqli_fetch_assoc($res_target);
    mysqli_stmt_close($stmt_target);

    if (!$target) {
        $msg_type = "gagal";
        $msg_text = "Akun admin tidak ditemukan.";
    } elseif ($target['id_admin'] == $_SESSION['admin_id']) {
        // Cegah menghapus akun sendiri
        $msg_type = "gagal";
        $msg_text = "Anda tidak dapat menghapus akun Anda sendiri!";
    } elseif ($target['role'] === 'Super Admin') {
        // Perlindungan Akun Superadmin: Tidak dapat dihapus oleh admin biasa maupun superadmin lain
        $msg_type = "gagal";
        $msg_text = "Kebijakan Keamanan: Akun Super Admin dilindungi dan tidak dapat dihapus!";
    } else {
        // Hapus akun admin biasa
        $stmt_hapus = mysqli_prepare($koneksi, "DELETE FROM `admin` WHERE id_admin = ?");
        mysqli_stmt_bind_param($stmt_hapus, "i", $id_hapus);

        if (mysqli_stmt_execute($stmt_hapus)) {
            catat_audit($koneksi, $_SESSION['admin_id'], $_SESSION['admin_nama'], $id_hapus, 'admin', 'HAPUS_ADMIN', $target['role'], null, "Menghapus akun admin: " . $target['username']);
            $msg_type = "sukses";
            $msg_text = "Akun admin berhasil dihapus.";
        } else {
            $msg_type = "gagal";
            $msg_text = "Gagal menghapus admin: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt_hapus);
    }
}

// 4. AMBIL DATA ADMIN
$query_admin = mysqli_query($koneksi, "SELECT * FROM `admin` ORDER BY role DESC, created_at DESC");

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Manajemen Akun Admin</h4>
            <p class="text-muted small mb-0">Kelola akun administrator untuk akses sistem.</p>
        </div>
    </div>

    <?php if (!empty($msg_type)): ?>
        <div class="alert alert-<?php echo ($msg_type=='sukses')?'success':'danger'; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fa-solid <?php echo ($msg_type=='sukses')?'fa-check-circle':'fa-circle-exclamation'; ?> me-2"></i>
            <?php echo htmlspecialchars($msg_text, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold m-0 text-primary"><i class="fa-solid fa-user-plus me-2"></i> Tambah Admin Baru</h6>
                </div>
                <div class="card-body p-4">
                    
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Admin" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Buat Username Unik" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="******" required>
                            <div class="form-text small">Password dienkripsi dengan standar aman (password_hash).</div>
                        </div>

                        <?php if ($admin_role === 'Super Admin'): ?>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Hak Akses (Role)</label>
                            <select name="role" class="form-select">
                                <option value="Admin">Admin Biasa</option>
                                <option value="Super Admin">Super Admin</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="d-grid mt-4">
                            <button type="submit" name="tambah_admin" class="btn btn-primary fw-bold">
                                <i class="fa-solid fa-save me-2"></i> Simpan Akun
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0 text-dark"><i class="fa-solid fa-users-gear me-2"></i> Daftar Administrator</h6>
                    <span class="badge bg-light text-dark border">Total: <?php echo mysqli_num_rows($query_admin); ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-uppercase small text-muted">
                                <tr>
                                    <th class="ps-4" width="50">No</th>
                                    <th>Nama Admin</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Terdaftar Sejak</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($query_admin)) { 
                                    $is_super = ($row['role'] === 'Super Admin');
                                    $is_self  = ($row['id_admin'] == $_SESSION['admin_id']);
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo $no++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                                                <?php echo htmlspecialchars(substr($row['nama_lengkap'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($row['nama_lengkap']); ?></span>
                                                <?php if($is_self): ?>
                                                    <span class="badge bg-success ms-2" style="font-size: 0.65rem;">YOU</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td>
                                        <?php if ($is_super): ?>
                                            <span class="badge bg-primary px-2 py-1"><i class="fa-solid fa-crown me-1 text-warning"></i> Super Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-2 py-1">Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?php echo !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '-'; ?></td>
                                    <td class="text-end pe-4">
                                        <?php if ($is_self): ?>
                                            <span class="text-muted small fst-italic">Sedang Login</span>
                                        <?php elseif ($is_super): ?>
                                            <span class="badge bg-light text-muted border px-2 py-1" title="Super Admin dilindungi dan tidak dapat dihapus">
                                                <i class="fa-solid fa-shield-halved text-primary me-1"></i> Terlindungi
                                            </span>
                                        <?php else: ?>
                                            <button onclick="konfirmasiHapus('<?php echo $row['id_admin']; ?>', '<?php echo htmlspecialchars(addslashes($row['nama_lengkap'])); ?>')" 
                                                    class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="Hapus Akun">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function konfirmasiHapus(id, nama) {
    Swal.fire({
        title: 'Hapus Admin?',
        text: `Anda yakin ingin menghapus akun "${nama}"? Akses login mereka akan hilang.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `admin.php?hapus=${id}`;
        }
    });
}
</script>

<?php if (!empty($msg_type)): ?>
<script>
    Swal.fire({ 
        icon: '<?php echo ($msg_type=="sukses") ? "success" : "error"; ?>',
        title: '<?php echo ($msg_type=="sukses") ? "Berhasil!" : "Gagal!"; ?>',
        text: '<?php echo addslashes($msg_text); ?>', 
        confirmButtonColor: '#0d2141'
    });
</script>
<?php endif; ?>

<?php include 'layouts/footer.php'; ?>
