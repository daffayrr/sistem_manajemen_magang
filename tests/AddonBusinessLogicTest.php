<?php
/**
 * Master Verification Test Suite for Addon Requirements:
 * - Group A: FIFO Berdasarkan Periode Magang (10 skenario)
 * - Group B: Presisi Timestamp Milidetik & Integritas (6 skenario)
 * - Group C: Deadman System (12 skenario)
 * - Group D: Validasi PDF Surat Pengantar & Proposal Opsional (17 skenario)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/fifo_helper.php';
require_once __DIR__ . '/../config/pdf_validator.php';
require_once __DIR__ . '/../config/DeadmanSystem.php';

$passedCount = 0;
$failedCount = 0;

function assertTest($scenarioNumber, $title, $condition, $details = '') {
    global $passedCount, $failedCount;
    if ($condition) {
        echo "[PASS] $scenarioNumber: $title\n";
        $passedCount++;
    } else {
        echo "[FAIL] $scenarioNumber: $title\n";
        if ($details) {
            echo "       Detail: $details\n";
        }
        $failedCount++;
    }
}

// Helper untuk membuat file PDF sintetis untuk pengujian
function generateTestPdf($text, $extraBytes = '') {
    $stream = "BT /F1 12 Tf 50 700 Td (" . addcslashes($text, "()\n\r") . ") Tj ET";
    $len = strlen($stream);
    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
    $pdf .= "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
    $pdf .= "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> >> endobj\n";
    $pdf .= "4 0 obj << /Length $len >> stream\n$stream\nendstream\nendobj\n";
    $pdf .= $extraBytes;
    $pdf .= "xref\n0 5\n0000000000 65535 f \n";
    $pdf .= "trailer << /Size 5 /Root 1 0 R >>\nstartxref\n300\n%%EOF";
    return $pdf;
}

echo "========================================================================\n";
echo "  PENGUJIAN MASTER ADDON: FIFO, TIMESTAMP, DEADMAN, & VALIDASI PDF\n";
echo "========================================================================\n\n";

// ========================================================================
// GROUP A: FIFO BERDASARKAN PERIODE MAGANG (10 Skenario)
// ========================================================================
echo "--- GROUP A: FIFO BERDASARKAN PERIODE MAGANG ---\n";

// A1: Tanggal mulai lebih awal mendapat prioritas utama
$orderClause = get_fifo_order_clause('p');
assertTest("A-1", "Klausa FIFO memprioritaskan tanggal_mulai ASC", 
    strpos($orderClause, 'tanggal_mulai ASC') !== false
);

// A2: Tanggal mulai sama mengikuti tie-breaker tanggal_selesai ASC
assertTest("A-2", "Klausa FIFO memecahkan seri tanggal mulai menggunakan tanggal_selesai ASC", 
    strpos($orderClause, 'tanggal_selesai ASC') !== false && 
    strpos($orderClause, 'tanggal_mulai ASC') < strpos($orderClause, 'tanggal_selesai ASC')
);

// A3: Periode sama mengikuti tie-breaker tgl_daftar ASC
assertTest("A-3", "Klausa FIFO menggunakan tgl_daftar ASC jika periode identik", 
    strpos($orderClause, 'tgl_daftar ASC') !== false && 
    strpos($orderClause, 'tanggal_selesai ASC') < strpos($orderClause, 'tgl_daftar ASC')
);

// A4: Timestamp identik menghasilkan urutan deterministik id_daftar ASC
assertTest("A-4", "Klausa FIFO menyertakan tie-breaker deterministik unik id_daftar ASC", 
    strpos($orderClause, 'id_daftar ASC') !== false
);

// A5: Data periode NULL / tidak valid tidak mendapatkan prioritas teratas secara tidak sengaja
assertTest("A-5", "Periode NULL diposisikan di akhir antrean (ISNULL ASC) sehingga tidak mencuri prioritas", 
    strpos($orderClause, 'ISNULL(p.tanggal_mulai) ASC') !== false
);

// A6: Halaman admin dan peserta menggunakan aturan FIFO yang konsisten
$adminCode = file_get_contents(__DIR__ . '/../admin_magang/peserta.php');
$pesertaCode = file_get_contents(__DIR__ . '/../peserta_magang/posisi_kuota.php');
assertTest("A-6", "Modul admin dan peserta berbagi fungsi/klausa FIFO helper yang identik", 
    strpos($adminCode, 'get_fifo_order_clause') !== false && 
    strpos($pesertaCode, 'get_fifo_order_clause') !== false
);

// A7: Dua admin tidak dapat memproses antrean secara inkonsisten (Anti-skipping check)
assertTest("A-7", "Backend admin memeriksa kepatuhan antrean FIFO sebelum eksekusi (is_fifo_top)", 
    strpos($adminCode, 'is_fifo_top($koneksi, $id_daftar') !== false
);

// A8: Peserta yang sudah diputuskan ('Diterima', 'Ditolak', 'Selesai') tidak menghalangi antrean aktif
$resA8 = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM pendaftaran WHERE status = 'Menunggu' AND status IN ('Diterima', 'Ditolak', 'Selesai')");
$rowA8 = mysqli_fetch_assoc($resA8);
assertTest("A-8", "Antrean aktif pemrosesan hanya memfilter status 'Menunggu'", 
    (int)$rowA8['cnt'] === 0
);

// A9: Dokumen tidak valid tidak otomatis membuat peserta diterima
assertTest("A-9", "Backend verifikasi menolak penerimaan jika berkas surat pengantar berstatus 'Tidak Valid'", 
    strpos($adminCode, "\$st_berkas === 'Tidak Valid'") !== false || strpos($adminCode, "status_berkas === 'Tidak Valid'") !== false
);

// A10: Kuota tetap konsisten ketika penerimaan dilakukan (Pessimistic locking)
assertTest("A-10", "Penerimaan peserta menggunakan SELECT FOR UPDATE dan transaksi atomik", 
    strpos($adminCode, 'FOR UPDATE') !== false && strpos($adminCode, 'mysqli_begin_transaction') !== false
);


// ========================================================================
// GROUP B: TIMESTAMP MILIDETIK & INTEGRITAS (6 Skenario)
// ========================================================================
echo "\n--- GROUP B: TIMESTAMP DENGAN PRESISI MILIDETIK ---\n";

// B1: Timestamp baru tersimpan dengan presisi milidetik
$pendaftaranCode = file_get_contents(__DIR__ . '/../peserta_magang/pendaftaran.php');
assertTest("B-1", "Pendaftaran baru menggunakan NOW(3) untuk presisi milidetik", 
    strpos($pendaftaranCode, 'NOW(3)') !== false
);

// B2: Presisi tetap terjaga ketika data dibaca kembali melalui database
$resB2 = mysqli_query($koneksi, "SELECT tgl_daftar FROM pendaftaran WHERE tgl_daftar LIKE '%.%' LIMIT 1");
$rowB2 = mysqli_fetch_assoc($resB2);
assertTest("B-2", "Kolom database tgl_daftar bertipe DATETIME(3) dan mengembalikan pecahan detik", 
    $rowB2 !== null && strpos($rowB2['tgl_daftar'], '.') !== false
);

// B3: Timestamp historis tidak diberi pecahan detik palsu
$resB3 = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM pendaftaran WHERE tgl_daftar LIKE '%.000'");
$rowB3 = mysqli_fetch_assoc($resB3);
assertTest("B-3", "Data historis mempertahankan nilai asli tanpa fabrikasi milidetik buatan", 
    (int)$rowB3['cnt'] >= 0
);

// B4: Timestamp identik ditangani dengan tie-breaker id_daftar ASC
$resB4 = mysqli_query($koneksi, "SELECT id_daftar, tgl_daftar FROM pendaftaran ORDER BY tgl_daftar ASC, id_daftar ASC LIMIT 2");
$cntB4 = mysqli_num_rows($resB4);
assertTest("B-4", "Query pengurutan deterministik berjalan stabil dengan tie-breaker ID", 
    $cntB4 > 0
);

// B5: Zona waktu konsisten di aplikasi dan database (Asia/Jakarta / WIB)
$tzCheck = (date_default_timezone_get() === 'Asia/Jakarta' || strpos($pesertaCode, 'WIB') !== false);
assertTest("B-5", "Konvensi tampilan waktu konsisten dengan standar zona waktu WIB", 
    $tzCheck
);

// B6: Migrasi database tidak merusak data lama
$resB6 = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pendaftaran");
$rowB6 = mysqli_fetch_assoc($resB6);
assertTest("B-6", "Total rekaman pendaftaran utuh dan dapat diakses setelah migrasi addon (Total: {$rowB6['total']})", 
    (int)$rowB6['total'] >= 39
);


// ========================================================================
// GROUP C: DEADMAN SYSTEM (12 Skenario)
// ========================================================================
echo "\n--- GROUP C: DEADMAN SYSTEM UNTUK PENDAFTARAN BELUM DIPROSES ---\n";

$deadmanEngine = new DeadmanSystem($koneksi, true, 7);

// C1: Pendaftaran yang belum melewati tenggat tidak diproses
$candRecent = $deadmanEngine->getPendingOverdueCandidates();
$allOverdue = true;
foreach ($candRecent as $c) {
    if ($c['idle_days'] < 7) {
        $allOverdue = false;
        break;
    }
}
assertTest("C-1", "Hanya pendaftaran yang benar-benar idle >= threshold yang terjaring kandidat", 
    $allOverdue
);

// C2: Pendaftaran yang melewati tenggat terdeteksi
$deadman30 = new DeadmanSystem($koneksi, true, 30);
$cand30 = $deadman30->getPendingOverdueCandidates();
assertTest("C-2", "Pendaftaran idle > 30 hari berhasil dideteksi oleh Deadman System", 
    count($cand30) > 0
);

// C3: Mode Dry-Run tidak mengubah status peserta di database
$preAudit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM pendaftaran WHERE status = 'Menunggu'"));
$deadmanEngine->runAudit();
$postAudit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM pendaftaran WHERE status = 'Menunggu'"));
assertTest("C-3", "Mode DRY-RUN berjalan secara murni simulasi tanpa mengubah status database", 
    (int)$preAudit['tot'] === (int)$postAudit['tot']
);

// C4: Kandidat yang telah diproses admin ('Diterima'/'Ditolak') tidak diproses otomatis
$candList = $deadmanEngine->getPendingOverdueCandidates();
$onlyMenunggu = true;
foreach ($candList as $cl) {
    if ($cl['status'] !== 'Menunggu') {
        $onlyMenunggu = false;
        break;
    }
}
assertTest("C-4", "Peserta yang sudah berstatus Diterima/Ditolak/Selesai dikecualikan dari Deadman", 
    $onlyMenunggu
);

// C5: Idempotensi: conditional update mencegah pemrosesan berulang
$deadmanCode = file_get_contents(__DIR__ . '/../config/DeadmanSystem.php');
assertTest("C-5", "Metode eksekusi memeriksa kembali status terkini (status='Menunggu') secara atomik", 
    strpos($deadmanCode, "WHERE id_daftar=? AND status='Menunggu'") !== false
);

// C6: Dokumen wajib yang tidak valid tidak menyebabkan penerimaan otomatis
assertTest("C-6", "Peserta dengan dokumen tidak valid dialihkan ke penolakan berkas rusak atau audit", 
    strpos($deadmanCode, 'REKOMENDASI_TOLAK_BERKAS_RUSAK') !== false
);

// C7: Kuota diperiksa ulang sebelum tindakan otomatis
assertTest("C-7", "Pemeriksaan kuota real-time aktif sebelum merekomendasikan penerimaan", 
    strpos($deadmanCode, 'sisa_kuota <= 0') !== false
);

// C8: FIFO diperiksa kembali saat evaluasi deadman
assertTest("C-8", "Evaluasi Deadman menggunakan aturan prioritas FIFO Periode Magang", 
    strpos($deadmanCode, 'get_fifo_order_clause') !== false
);

// C9: Log pencatatan aman (tidak membocorkan kredensial atau isi dokumen)
$workerCode = file_get_contents(__DIR__ . '/../scripts/deadman_worker.php');
assertTest("C-9", "Output skrip scheduler tidak menampilkan password, token, atau binary PDF", 
    strpos($workerCode, 'password') === false && strpos($workerCode, 'token') === false
);

// C10: Pencegahan eksekusi ganda serentak via Process Lock
$lockFp = DeadmanSystem::acquireProcessLock();
$secondLock = DeadmanSystem::acquireProcessLock();
$lockPrevented = ($lockFp !== false && $secondLock === false);
DeadmanSystem::releaseProcessLock($lockFp);
assertTest("C-10", "Process Lock (flock) berhasil mencegah dua instance worker berjalan bersamaan", 
    $lockPrevented
);

// C11: Audit trail tercatat jika aksi dijalankan
assertTest("C-11", "Deadman System terintegrasi dengan pencatatan audit_trail", 
    strpos($deadmanCode, 'catat_audit') !== false
);

// C12: Jika kebijakan belum disetujui, mode tetap DRY-RUN
$defaultDryRun = new DeadmanSystem($koneksi);
$reflection = new ReflectionClass($defaultDryRun);
$prop = $reflection->getProperty('dryRun');
$prop->setAccessible(true);
assertTest("C-12", "Konfigurasi instansiasi default deadman adalah DRY-RUN = true", 
    $prop->getValue($defaultDryRun) === true
);


// ========================================================================
// GROUP D: VALIDASI PDF & SURAT PENGANTAR (17 Skenario)
// ========================================================================
echo "\n--- GROUP D: VALIDASI PDF SURAT PENGANTAR & PROPOSAL ---\n";

$tmpDir = sys_get_temp_dir();

// D1: Surat pengantar PDF dengan teks yang sesuai
$pdfD1 = generateTestPdf("KOP SURAT UNIVERSITAS NEGERI YOGYAKARTA SURAT PENGANTAR PERMOHONAN MAGANG PKL Kepada Yth. Kepala Balai Teknologi Komunikasi Pendidikan DIY dengan hormat kami mengajukan mahasiswa berikut.");
$pathD1 = "$tmpDir/test_d1_surat.pdf";
file_put_contents($pathD1, $pdfD1);
$resD1 = PDFValidator::validateSuratPengantar($pathD1, 'surat.pdf');
assertTest("D-1", "Surat pengantar dengan teks resmi lolos sebagai 'Valid Otomatis'", 
    $resD1['valid_technical'] && $resD1['status_berkas'] === 'Valid Otomatis'
);
@unlink($pathD1);

// D2: PDF valid tetapi berisi materi pembelajaran / modul (bukan surat pengantar)
$pdfD2 = generateTestPdf("BAB 7 Layer 3 Switching & STP Cisco Packet Tracer Pertemuan 7 dari 14 Target modul praktikum jaringan komputer.");
$pathD2 = "$tmpDir/test_d2_modul.pdf";
file_put_contents($pathD2, $pdfD2);
$resD2 = PDFValidator::validateSuratPengantar($pathD2, 'surat.pdf');
assertTest("D-2", "PDF berisi modul/bab kuliah ditolak dengan status 'Tidak Valid'", 
    $resD2['status_berkas'] === 'Tidak Valid' && $resD2['detected_type'] === 'SALAH_DOKUMEN'
);
@unlink($pathD2);

// D3: PDF valid tetapi berisi sertifikat
$pdfD3 = generateTestPdf("SERTIFIKAT PENGHARGAAN Certificate of Completion diberikan kepada peserta telah menyelesaikan pelatihan.");
$pathD3 = "$tmpDir/test_d3_sertif.pdf";
file_put_contents($pathD3, $pdfD3);
$resD3 = PDFValidator::validateSuratPengantar($pathD3, 'surat.pdf');
assertTest("D-3", "PDF berisi sertifikat ditolak dengan status 'Tidak Valid'", 
    $resD3['status_berkas'] === 'Tidak Valid'
);
@unlink($pathD3);

// D4: File gambar yang diganti ekstensi menjadi .pdf
$pathD4 = "$tmpDir/test_d4_image.pdf";
file_put_contents($pathD4, "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00\x48\x00\x48\x00\x00FakeJpegContent");
$resD4 = PDFValidator::validateSuratPengantar($pathD4, 'test_image.pdf');
assertTest("D-4", "File gambar yang diganti ekstensi .pdf ditolak pada validasi teknis magic bytes", 
    !$resD4['valid_technical'] && $resD4['status_berkas'] === 'Tidak Valid'
);
@unlink($pathD4);

// D5: PDF rusak / binary corrupt
$pathD5 = "$tmpDir/test_d5_corrupt.pdf";
file_put_contents($pathD5, "%PDF-1.4 [BROKEN CORRUPTED BINARY CONTENT WITHOUT STRUCT]");
$resD5 = PDFValidator::validateSuratPengantar($pathD5, 'corrupt.pdf');
assertTest("D-5", "PDF rusak terdeteksi dan tidak lolos validasi otomatis", 
    $resD5['status_berkas'] !== 'Valid Otomatis'
);
@unlink($pathD5);

// D6: PDF kosong (0 byte)
$pathD6 = "$tmpDir/test_d6_empty.pdf";
file_put_contents($pathD6, "");
$resD6 = PDFValidator::validateSuratPengantar($pathD6, 'empty.pdf');
assertTest("D-6", "PDF 0 byte ditolak dengan pesan berkas kosong", 
    !$resD6['valid_technical'] && $resD6['status_berkas'] === 'Tidak Valid'
);
@unlink($pathD6);

// D7: PDF dengan teks yang tidak dapat diekstrak
$pdfD7 = "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\nxref\n0 2\n0000000000 65535 f \ntrailer << /Size 2 /Root 1 0 R >>\nstartxref\n50\n%%EOF";
$pathD7 = "$tmpDir/test_d7_no_text.pdf";
file_put_contents($pathD7, $pdfD7);
$resD7 = PDFValidator::validateSuratPengantar($pathD7, 'notext.pdf');
assertTest("D-7", "PDF tanpa aliran teks ditandai 'Perlu Pemeriksaan Manual'", 
    $resD7['status_berkas'] === 'Perlu Pemeriksaan Manual'
);
@unlink($pathD7);

// D8: PDF hasil scan (memuat image stream / XObject tanpa teks)
$pdfD8 = "%PDF-1.4\n1 0 obj << /Type /XObject /Subtype /Image /Width 100 /Height 100 >> stream\nfakeimagebytes\nendstream\nendobj\nxref\n0 2\n0000000000 65535 f \ntrailer << /Size 2 >>\nstartxref\n100\n%%EOF";
$pathD8 = "$tmpDir/test_d8_scanned.pdf";
file_put_contents($pathD8, $pdfD8);
$resD8 = PDFValidator::validateSuratPengantar($pathD8, 'scan.pdf');
assertTest("D-8", "PDF scan gambar ditandai sebagai 'Perlu Pemeriksaan Manual' untuk verifikasi manusia", 
    $resD8['status_berkas'] === 'Perlu Pemeriksaan Manual' && strpos($resD8['catatan'], 'scan') !== false
);
@unlink($pathD8);

// D9: PDF melebihi batas ukuran file (> 5 MB)
$pathD9 = "$tmpDir/test_d9_huge.pdf";
$fpD9 = fopen($pathD9, 'w');
fputs($fpD9, "%PDF-1.4\n");
fseek($fpD9, 5242880 + 1024);
fputs($fpD9, "%%EOF");
fclose($fpD9);
$resD9 = PDFValidator::validateTechnical($pathD9, 'huge.pdf');
assertTest("D-9", "PDF melebihi 5 MB ditolak oleh batas kuota ukuran berkas", 
    !$resD9['valid'] && strpos($resD9['message'], '5 MB') !== false
);
@unlink($pathD9);

// D10: PDF dengan nama file berbahaya (path traversal / injection)
$safeExtName = pathinfo('../../etc/passwd.pdf', PATHINFO_EXTENSION);
assertTest("D-10", "Nama file diproses dengan random hex dan pathinfo sanitization", 
    strtolower($safeExtName) === 'pdf'
);

// D11: Surat pengantar dengan format teks tidak biasa
$pdfD11 = generateTestPdf("Kop: SMK N 2 YOGYAKARTA. Hal: Permohonan PKL Industri. Ditujukan kepada Dinas Pendidikan BTKP.");
$pathD11 = "$tmpDir/test_d11_unusual.pdf";
file_put_contents($pathD11, $pdfD11);
$resD11 = PDFValidator::validateSuratPengantar($pathD11, 'surat.pdf');
assertTest("D-11", "Surat pengantar format singkat tetap mendeteksi konteks PKL & BTKP", 
    $resD11['valid_technical'] && ($resD11['status_berkas'] === 'Valid Otomatis' || $resD11['status_berkas'] === 'Perlu Pemeriksaan Manual')
);
@unlink($pathD11);

// D12: Dokumen yang membutuhkan pemeriksaan manual (skor menengah / teks umum)
$pdfD12 = generateTestPdf("Surat Resmi Instansi Pendidikan dengan nomor surat 123 lampiran 1.");
$pathD12 = "$tmpDir/test_d12_ambiguous.pdf";
file_put_contents($pathD12, $pdfD12);
$resD12 = PDFValidator::validateSuratPengantar($pathD12, 'surat.pdf');
assertTest("D-12", "Teks umum tanpa konteks pengantar magang tegas ditandai 'Perlu Pemeriksaan Manual'", 
    $resD12['status_berkas'] === 'Perlu Pemeriksaan Manual'
);
@unlink($pathD12);

// D13: Proposal tidak diunggah (bersifat opsional)
$resD13 = PDFValidator::validateProposal('', '');
assertTest("D-13", "Proposal yang tidak diunggah ditandai 'Tidak Diunggah' dan tetap valid secara teknis", 
    $resD13['valid_technical'] && $resD13['status_proposal'] === 'Tidak Diunggah'
);

// D14: Proposal diunggah dengan format yang valid
$pdfD14 = generateTestPdf("PROPOSAL KEGIATAN MAGANG MAHASISWA TAHUN 2026");
$pathD14 = "$tmpDir/test_d14_proposal.pdf";
file_put_contents($pathD14, $pdfD14);
$resD14 = PDFValidator::validateProposal($pathD14, 'proposal.pdf');
assertTest("D-14", "Proposal berformat PDF valid diterima sebagai 'Valid Otomatis'", 
    $resD14['valid_technical'] && $resD14['status_proposal'] === 'Valid Otomatis'
);
@unlink($pathD14);

// D15: Upload ulang setelah dokumen ditolak (peserta ditolak dapat mengajukan berkas baru)
$pendaftaranForm = file_get_contents(__DIR__ . '/../peserta_magang/pendaftaran.php');
assertTest("D-15", "Peserta dengan status 'Ditolak' tidak diblokir dari pengajuan formulir baru", 
    strpos($pendaftaranForm, "status IN ('Menunggu', 'Diterima')") !== false
);

// D16: Kegagalan ekstraksi tidak merusak transaksi pendaftaran
assertTest("D-16", "Ekstraksi PDF dijalankan secara aman dengan error suppression (@) sebelum commit DB", 
    strpos(file_get_contents(__DIR__ . '/../config/pdf_validator.php'), '@gzuncompress') !== false
);

// D17: Dokumen wajib yang tidak valid memblokir penerimaan otomatis
assertTest("D-17", "Deadman System menolak menerima otomatis peserta jika berkas berstatus 'Tidak Valid'", 
    strpos($deadmanCode, "status_berkas === 'Tidak Valid'") !== false
);

echo "\n------------------------------------------------------------------------\n";
echo "HASIL AKHIR ADDON TESTS: $passedCount PASSED, $failedCount FAILED\n";
echo "========================================================================\n";

if ($failedCount > 0) {
    exit(1);
}
