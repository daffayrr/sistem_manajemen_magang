<?php
/**
 * Test Seed Script for 30 Blackbox Test Cases (BTKP Magang)
 */
require_once __DIR__ . '/../config/database.php';

// 1. Pastikan Admin Utama ada
$pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = mysqli_prepare($koneksi, "INSERT INTO admin (id_admin, username, password, nama_lengkap, role) VALUES (1, 'admin', ?, 'Administrator Utama', 'Admin') ON DUPLICATE KEY UPDATE password=?, role='Admin'");
mysqli_stmt_bind_param($stmt, "ss", $pass_admin, $pass_admin);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// 2. Pastikan Kuota awal diset = 10 (mencukupi untuk seluruh alur penerimaan dan pengajuan)
mysqli_query($koneksi, "UPDATE kuota SET jumlah_maks = 10, deadman_enabled = 1, deadman_threshold_days = 7, deadman_action = 'AUDIT_ONLY' WHERE id_kuota = 1");

$pass_pst = password_hash('abc_123', PASSWORD_DEFAULT);
$upload_dir = __DIR__ . '/../uploads/';
$dummy_balasan = "surat_balasan_dummy.pdf";
if (!file_exists($upload_dir . $dummy_balasan) && file_exists(__DIR__ . '/../cypress/fixtures/surat_dummy.pdf')) {
    copy(__DIR__ . '/../cypress/fixtures/surat_dummy.pdf', $upload_dir . $dummy_balasan);
}

// 3. Peserta Diterima (Khusus PST-09 & ADM-14)
$email_diterima = 'peserta.diterima@btkp.test';
$q_chk_diterima = mysqli_query($koneksi, "SELECT id_user FROM peserta WHERE email='$email_diterima'");
if (mysqli_num_rows($q_chk_diterima) == 0) {
    mysqli_query($koneksi, "INSERT INTO peserta (nama, email, password, no_hp, asal_instansi, role) VALUES ('Peserta Diterima Test', '$email_diterima', '$pass_pst', '081234567890', 'Universitas Gadjah Mada', 'Peserta')");
    $id_user_diterima = mysqli_insert_id($koneksi);
} else {
    $row_d = mysqli_fetch_assoc($q_chk_diterima);
    $id_user_diterima = $row_d['id_user'];
    mysqli_query($koneksi, "UPDATE peserta SET password='$pass_pst' WHERE id_user=$id_user_diterima");
}

$q_chk_reg_d = mysqli_query($koneksi, "SELECT id_daftar FROM pendaftaran WHERE id_user=$id_user_diterima");
if (mysqli_num_rows($q_chk_reg_d) == 0) {
    mysqli_query($koneksi, "INSERT INTO pendaftaran (id_user, status, tanggal_mulai, tanggal_selesai, berkas, file_balasan, file_sertifikat, file_keterangan, tgl_daftar, tgl_verifikasi) 
    VALUES ($id_user_diterima, 'Diterima', '2026-08-01', '2026-10-31', 'surat_dummy.pdf', '$dummy_balasan', 'surat_dummy.pdf', 'surat_dummy.pdf', NOW(3), NOW(3))");
} else {
    mysqli_query($koneksi, "UPDATE pendaftaran SET status='Diterima', file_balasan='$dummy_balasan', file_sertifikat='surat_dummy.pdf', file_keterangan='surat_dummy.pdf' WHERE id_user=$id_user_diterima");
}

// 4. Peserta Selesai Test (Khusus ADM-12 agar tidak mengubah peserta.diterima@btkp.test)
$email_selesai = 'peserta.selesai@btkp.test';
$q_chk_selesai = mysqli_query($koneksi, "SELECT id_user FROM peserta WHERE email='$email_selesai'");
if (mysqli_num_rows($q_chk_selesai) == 0) {
    mysqli_query($koneksi, "INSERT INTO peserta (nama, email, password, no_hp, asal_instansi, role) VALUES ('Peserta Siap Selesai', '$email_selesai', '$pass_pst', '081234567895', 'Institut Seni Indonesia', 'Peserta')");
    $id_user_selesai = mysqli_insert_id($koneksi);
} else {
    $row_s = mysqli_fetch_assoc($q_chk_selesai);
    $id_user_selesai = $row_s['id_user'];
    mysqli_query($koneksi, "UPDATE peserta SET password='$pass_pst' WHERE id_user=$id_user_selesai");
}

$q_chk_reg_s = mysqli_query($koneksi, "SELECT id_daftar FROM pendaftaran WHERE id_user=$id_user_selesai");
if (mysqli_num_rows($q_chk_reg_s) == 0) {
    mysqli_query($koneksi, "INSERT INTO pendaftaran (id_user, status, tanggal_mulai, tanggal_selesai, berkas, file_balasan, file_sertifikat, file_keterangan, tgl_daftar, tgl_verifikasi) 
    VALUES ($id_user_selesai, 'Diterima', '2026-07-01', '2026-09-30', 'surat_dummy.pdf', '$dummy_balasan', 'surat_dummy.pdf', 'surat_dummy.pdf', NOW(3), NOW(3))");
} else {
    mysqli_query($koneksi, "UPDATE pendaftaran SET status='Diterima', file_balasan='$dummy_balasan', file_sertifikat='surat_dummy.pdf', file_keterangan='surat_dummy.pdf' WHERE id_user=$id_user_selesai");
}

// 5. Peserta Menunggu (Untuk PST-08, PST-10, ADM-09, ADM-11)
$email_menunggu = 'peserta.menunggu@btkp.test';
$q_chk_menunggu = mysqli_query($koneksi, "SELECT id_user FROM peserta WHERE email='$email_menunggu'");
if (mysqli_num_rows($q_chk_menunggu) == 0) {
    mysqli_query($koneksi, "INSERT INTO peserta (nama, email, password, no_hp, asal_instansi, role) VALUES ('Peserta Menunggu Test', '$email_menunggu', '$pass_pst', '081234567891', 'Universitas Alma Ata', 'Peserta')");
    $id_user_menunggu = mysqli_insert_id($koneksi);
} else {
    $row_m = mysqli_fetch_assoc($q_chk_menunggu);
    $id_user_menunggu = $row_m['id_user'];
    mysqli_query($koneksi, "UPDATE peserta SET password='$pass_pst' WHERE id_user=$id_user_menunggu");
}

$q_chk_reg_m = mysqli_query($koneksi, "SELECT id_daftar FROM pendaftaran WHERE id_user=$id_user_menunggu");
if (mysqli_num_rows($q_chk_reg_m) == 0) {
    mysqli_query($koneksi, "INSERT INTO pendaftaran (id_user, status, tanggal_mulai, tanggal_selesai, berkas, file_balasan, tgl_daftar) 
    VALUES ($id_user_menunggu, 'Menunggu', '2026-09-01', '2026-11-30', 'surat_dummy.pdf', NULL, NOW(3))");
} else {
    mysqli_query($koneksi, "UPDATE pendaftaran SET status='Menunggu', file_balasan=NULL WHERE id_user=$id_user_menunggu");
}

// 6. Peserta Baru Belum Mendaftar (Untuk PST-03, PST-06, PST-07)
$email_baru = 'peserta.baru@btkp.test';
$q_chk_baru = mysqli_query($koneksi, "SELECT id_user FROM peserta WHERE email='$email_baru'");
if (mysqli_num_rows($q_chk_baru) == 0) {
    mysqli_query($koneksi, "INSERT INTO peserta (nama, email, password, no_hp, asal_instansi, role) VALUES ('Peserta Baru Test', '$email_baru', '$pass_pst', '081234567892', 'Universitas Negeri Yogyakarta', 'Peserta')");
} else {
    $row_b = mysqli_fetch_assoc($q_chk_baru);
    $id_b = $row_b['id_user'];
    mysqli_query($koneksi, "DELETE FROM pendaftaran WHERE id_user=$id_b");
    mysqli_query($koneksi, "UPDATE peserta SET password='$pass_pst' WHERE id_user=$id_b");
}

// 7. Pengumuman
$q_info = mysqli_query($koneksi, "SELECT id FROM informasi WHERE id=1");
if (mysqli_num_rows($q_info) == 0) {
    mysqli_query($koneksi, "INSERT INTO informasi (id, judul, deskripsi, tgl_update) VALUES (1, 'Pengumuman Penerimaan Magang 2026', 'Pendaftaran magang semester ganjil telah dibuka.', NOW())");
}

echo "[SUCCESS] Data uji deterministik berhasil disiapkan untuk 30 skenario testing.\n";
