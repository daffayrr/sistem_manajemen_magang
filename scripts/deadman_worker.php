<?php
/**
 * CLI Worker for Deadman System Scheduler
 * Sistem Manajemen Penerimaan Magang BTKP DIY
 * 
 * Penggunaan:
 *   php scripts/deadman_worker.php [--dry-run=0|1] [--days=N] [--policy=AUDIT_ONLY|AUTO_REJECT_INVALID_ONLY|AUTO_ACCEPT_ELIGIBLE]
 * 
 * Contoh Cron (Setiap hari pk 01:00 WIB):
 *   0 1 * * * php /path/to/sistem_manajemen_magang/scripts/deadman_worker.php >> /path/to/logs/deadman.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    die("Akses ditolak. Skrip ini hanya dapat dijalankan melalui Command Line Interface (CLI).\n");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/DeadmanSystem.php';

// Parse CLI options
$options = getopt("", ["dry-run::", "days::", "policy::"]);

$dryRun = true;
if (isset($options['dry-run']) && ($options['dry-run'] === '0' || $options['dry-run'] === 'false')) {
    $dryRun = false;
}

$thresholdDays = isset($options['days']) ? (int) $options['days'] : DeadmanSystem::DEFAULT_THRESHOLD_DAYS;
$policy        = isset($options['policy']) ? strtoupper($options['policy']) : 'AUDIT_ONLY';

echo "========================================================\n";
echo " DEADMAN SYSTEM SCHEDULER — BTKP DIY\n";
echo " Tanggal Eksekusi: " . date('Y-m-d H:i:s T') . "\n";
echo " Mode: " . ($dryRun ? "DRY-RUN (Simulasi / Audit Saja)" : "ACTIVE (Eksekusi Nyata)") . "\n";
echo " Ambang Batas Waktu: $thresholdDays Hari\n";
echo " Kebijakan Tindakan: $policy\n";
echo "========================================================\n\n";

// 1. Lock Process untuk mencegah eksekusi ganda serentak
$lock = DeadmanSystem::acquireProcessLock();
if (!$lock) {
    echo "[PERINGATAN] Proses Deadman System lain sedang berjalan. Skrip dibatalkan demi keamanan idempotensi.\n";
    exit(0);
}

try {
    $deadman = new DeadmanSystem($koneksi, $dryRun, $thresholdDays, $policy);
    $results = $deadman->runAudit();

    if (empty($results)) {
        echo "[INFO] Tidak ditemukan pendaftaran tertahan melebihi ambang batas $thresholdDays hari.\n";
    } else {
        echo sprintf("%-5s | %-6s | %-20s | %-12s | %-10s | %-25s | %-20s\n",
            "ID", "Pos", "Nama Peserta", "Idle (Hari)", "Berkas", "Status Kelayakan", "Rekomendasi Tindakan"
        );
        echo str_repeat("-", 115) . "\n";

        foreach ($results as $r) {
            echo sprintf("%-5d | %-6d | %-20s | %-12d | %-10s | %-25s | %-20s\n",
                $r['id_daftar'],
                $r['queue_pos'],
                substr($r['nama'], 0, 20),
                $r['idle_days'],
                substr($r['status_berkas'], 0, 10),
                substr($r['kelayakan'], 0, 25),
                substr($r['usulan_tindakan'], 0, 20)
            );
        }

        echo "\n[INFO] Total pendaftaran tertahan yang dievaluasi: " . count($results) . "\n";
    }

} catch (Exception $e) {
    echo "[ERROR] Terjadi pengecualian saat mengeksekusi Deadman System: " . $e->getMessage() . "\n";
} finally {
    DeadmanSystem::releaseProcessLock($lock);
}

echo "\nEksekusi selesai dengan aman.\n";
