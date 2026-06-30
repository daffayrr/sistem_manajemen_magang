<?php
session_start();

// 1. CEK KEAMANAN
$status_login = $_SESSION['status'] ?? '';
$role_akses   = $_SESSION['role_akses'] ?? '';

// Pastikan hanya Admin yang bisa akses
if ($status_login != 'login' || $role_akses != 'Admin') {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

include '../config/database.php';

/** @var mysqli $koneksi */

// Variabel Notifikasi
$msg_type = "";
$msg_text = "";

// 2. PROSES TAMBAH ADMIN
if (isset($_POST['tambah_admin'])) {
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password     = md5($_POST['password']); // Enkripsi MD5 sesuai standar database Anda

    // Cek apakah username sudah ada?
    $cek_user = mysqli_query($koneksi, "SELECT username FROM admin WHERE username='$username'");
    if (mysqli_num_rows($cek_user) > 0) {
        $msg_type = "gagal";
        $msg_text = "Username '$username' sudah terdaftar! Gunakan yang lain.";
    } else {
        // Simpan ke Database (Role otomatis 'Admin' sesuai struktur DB baru)
        $simpan = mysqli_query($koneksi, "INSERT INTO admin (username, password, nama_lengkap, role) VALUES ('$username', '$password', '$nama_lengkap', 'Admin')");
        
        if ($simpan) {
            $msg_type = "sukses";
            $msg_text = "Admin baru berhasil ditambahkan!";
        } else {
            $msg_type = "gagal";
            $msg_text = "Terjadi kesalahan: " . mysqli_error($koneksi);
        }
    }
}

// 3. PROSES HAPUS ADMIN
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    // Cegah menghapus akun sendiri yang sedang login
    if ($id_hapus == $_SESSION['admin_id']) {
        $msg_type = "gagal";
        $msg_text = "Anda tidak dapat menghapus akun Anda sendiri!";
    } else {
        $hapus = mysqli_query($koneksi, "DELETE FROM admin WHERE id_admin='$id_hapus'");
        if ($hapus) {
            $msg_type = "sukses";
            $msg_text = "Akun admin berhasil dihapus.";
        }
    }
}

// 4. AMBIL DATA ADMIN
$query_admin = mysqli_query($koneksi, "SELECT * FROM admin ORDER BY created_at DESC");

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
            <?php echo $msg_text; ?>
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

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="******" required>
                            <div class="form-text small">Password akan dienkripsi (MD5).</div>
                        </div>

                        <div class="d-grid">
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
                                    <th>Terdaftar Sejak</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($query_admin)) { 
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo $no++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                                                <?php echo substr($row['nama_lengkap'], 0, 1); ?>
                                            </div>
                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($row['nama_lengkap']); ?></span>
                                            
                                            <?php if($row['id_admin'] == $_SESSION['admin_id']): ?>
                                                <span class="badge bg-success ms-2" style="font-size: 0.65rem;">YOU</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td class="small text-muted"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td class="text-end pe-4">
                                        <?php if($row['id_admin'] != $_SESSION['admin_id']): ?>
                                            <button onclick="konfirmasiHapus('<?php echo $row['id_admin']; ?>', '<?php echo htmlspecialchars($row['nama_lengkap']); ?>')" 
                                                    class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="Hapus Akun">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Sedang Login</span>
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
        // PERBAIKAN: Tambahkan addslashes() di bawah ini
        text: '<?php echo addslashes($msg_text); ?>', 
        confirmButtonColor: '#0d2141'
    });
</script>
<?php endif; ?>

<?php include 'layouts/footer.php'; ?>