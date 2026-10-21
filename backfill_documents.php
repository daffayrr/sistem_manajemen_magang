<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/pdf_validator.php';

echo "=== EVALUASI DAN BACKFILL DOKUMEN HISTORIS ===" . PHP_EOL;

$res = $koneksi->query("SELECT id_daftar, berkas, proposal FROM pendaftaran WHERE status_berkas = 'Menunggu Validasi'");
$total = 0;
$upload_dir = __DIR__ . '/uploads/';

while ($row = $res->fetch_assoc()) {
    $id = (int)$row['id_daftar'];
    $berkas = $row['berkas'];
    $proposal = $row['proposal'];

    $filepath = $upload_dir . $berkas;
    $status_berkas = 'Menunggu Validasi';
    $catatan_berkas = null;

    if (!empty($berkas) && file_exists($filepath)) {
        $val = PDFValidator::validateSuratPengantar($filepath, $berkas);
        $status_berkas = $val['status_berkas'];
        $catatan_berkas = $val['catatan'];
    } else {
        $status_berkas = 'Tidak Valid';
        $catatan_berkas = 'Berkas fisik tidak ditemukan di server.';
    }

    $status_prop = 'Tidak Diunggah';
    if (!empty($proposal) && file_exists($upload_dir . $proposal)) {
        $status_prop = 'Valid Otomatis';
    }

    $stmt = $koneksi->prepare("UPDATE pendaftaran SET status_berkas = ?, catatan_berkas = ?, status_proposal = ? WHERE id_daftar = ?");
    $stmt->bind_param("sssi", $status_berkas, $catatan_berkas, $status_prop, $id);
    $stmt->execute();
    $stmt->close();

    $total++;
    echo sprintf("ID: %-3d | Status Berkas: %-25s | Catatan: %s\n", $id, $status_berkas, substr($catatan_berkas, 0, 50));
}

echo "Total data historis diperbarui: $total" . PHP_EOL;
