<?php
/**
 * Deadman System Engine (Audit & Foundation)
 * Sistem Manajemen Penerimaan Magang BTKP DIY
 * 
 * Fondasi teknis untuk mendeteksi pendaftaran yang tertahan di status 'Menunggu'
 * tanpa keputusan admin melebihi batas waktu toleransi.
 * 
 * CATATAN KEAMANAN:
 * Berdasarkan instruksi arsitektur, sistem ini beroperasi dalam mode DRY-RUN (Audit/Rekomendasi).
 * Keputusan otomatis (Auto-Reject / Auto-Escalate) TIDAK dieksekusi sebelum ada persetujuan
 * dan penetapan aturan bisnis tertulis dari pimpinan/manajemen BTKP DIY.
 */

require_once __DIR__ . '/database.php';

class DeadmanSystem {
    private $koneksi;
    private $dryRun;
    private $defaultThresholdDays;

    public function __construct($koneksi, $dryRun = true, $defaultThresholdDays = 7) {
        $this->koneksi = $koneksi;
        $this->dryRun  = $dryRun;
        $this->defaultThresholdDays = $defaultThresholdDays;
    }

    /**
     * Memeriksa antrean yang melewati tenggat waktu (threshold)
     * 
     * @param int|null $customThresholdDays Jumlah hari batas tenggat
     * @return array Hasil evaluasi antrean
     */
    public function auditPendingRegistrations($customThresholdDays = null) {
        $thresholdDays = $customThresholdDays ?? $this->defaultThresholdDays;
        
        $sql = "SELECT p.*, u.nama, u.email, u.asal_instansi,
                       TIMESTAMPDIFF(DAY, p.tgl_daftar, NOW()) as idle_days
                FROM pendaftaran p
                JOIN peserta u ON p.id_user = u.id_user
                WHERE p.status = 'Menunggu'
                  AND p.tgl_daftar < NOW() - INTERVAL ? DAY
                ORDER BY p.tgl_daftar ASC, p.id_daftar ASC";

        $stmt = mysqli_prepare($this->koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "i", $thresholdDays);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $evaluations = [];
        $upload_dir = __DIR__ . '/../uploads/';

        // Cek Kuota saat ini
        $q_kuota = mysqli_query($this->koneksi, "SELECT jumlah_maks FROM kuota WHERE id_kuota = 1 LIMIT 1");
        $d_kuota = mysqli_fetch_assoc($q_kuota);
        $maks = (int) ($d_kuota['jumlah_maks'] ?? 0);

        $q_aktf = mysqli_query($this->koneksi, "SELECT COUNT(*) as tot FROM pendaftaran WHERE status = 'Diterima'");
        $d_aktf = mysqli_fetch_assoc($q_aktf);
        $aktif = (int) ($d_aktf['tot'] ?? 0);
        $sisa_kuota = $maks - $aktif;

        while ($row = mysqli_fetch_assoc($res)) {
            $has_berkas = !empty($row['berkas']) && file_exists($upload_dir . $row['berkas']);
            
            $status_kelayakan = "LAYAK_DITINJAU";
            $rekomendasi_tindakan = "PEMBERITAHUAN_ADMIN"; // Mengingatkan admin

            if (!$has_berkas) {
                $status_kelayakan = "BERKAS_TIDAK_VALID";
                $rekomendasi_tindakan = "REKOMENDASI_TOLAK_BERKAS_RUSAK";
            } elseif ($sisa_kuota <= 0) {
                $status_kelayakan = "KUOTA_PENUH";
                $rekomendasi_tindakan = "TETAP_DALAM_WAITING_LIST";
            }

            $evaluations[] = [
                'id_daftar'            => $row['id_daftar'],
                'nama'                 => $row['nama'],
                'asal_instansi'        => $row['asal_instansi'],
                'tgl_daftar'           => $row['tgl_daftar'],
                'idle_days'            => $row['idle_days'],
                'has_berkas'           => $has_berkas,
                'status_kelayakan'     => $status_kelayakan,
                'rekomendasi_tindakan' => $rekomendasi_tindakan,
                'mode'                 => $this->dryRun ? 'DRY_RUN (Audit Only)' : 'ACTIVE'
            ];
        }

        mysqli_stmt_close($stmt);
        return $evaluations;
    }
}
