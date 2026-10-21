<?php
/**
 * Deadman System Engine
 * Sistem Manajemen Penerimaan Magang BTKP DIY
 * 
 * Bertanggung jawab mendeteksi pendaftaran yang tertahan di status 'Menunggu'
 * tanpa keputusan admin melewati batas waktu toleransi (threshold).
 * 
 * KEBIJAKAN DEFAULT:
 * Mode DRY-RUN (Audit/Simulasi) secara default aktif demi keamanan.
 * Tindakan otomatis (Auto-Accept / Auto-Reject) HANYA dapat dieksekusi jika:
 * 1. Mode Dry-Run dinonaktifkan secara eksplisit dalam konfigurasi.
 * 2. Kebijakan bisnis resmi disetujui oleh manajemen BTKP DIY.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/fifo_helper.php';
require_once __DIR__ . '/pdf_validator.php';

class DeadmanSystem {

    // Konfigurasi Default Operasional
    const DEFAULT_THRESHOLD_DAYS = 7;
    const TIMEZONE               = 'Asia/Jakarta';
    const LOCK_FILE              = __DIR__ . '/../deadman_worker.lock';

    private $koneksi;
    private $dryRun;
    private $thresholdDays;
    private $actionPolicy; // 'AUDIT_ONLY', 'AUTO_REJECT_INVALID_ONLY', 'AUTO_ACCEPT_ELIGIBLE'

    public function __construct($koneksi, $dryRun = true, $thresholdDays = self::DEFAULT_THRESHOLD_DAYS, $actionPolicy = 'AUDIT_ONLY') {
        $this->koneksi       = $koneksi;
        $this->dryRun        = (bool) $dryRun;
        $this->thresholdDays = (int) $thresholdDays;
        $this->actionPolicy  = $actionPolicy;
        
        date_default_timezone_set(self::TIMEZONE);
    }

    /**
     * Memuat konfigurasi Deadman System langsung dari tabel kuota di database
     * 
     * @param mysqli $koneksi
     * @return DeadmanSystem
     */
    public static function fromDatabase($koneksi) {
        $q = mysqli_query($koneksi, "SELECT deadman_enabled, deadman_threshold_days, deadman_action FROM kuota WHERE id_kuota = 1 LIMIT 1");
        $d = mysqli_fetch_assoc($q);
        
        $enabled   = (int) ($d['deadman_enabled'] ?? 0);
        $threshold = (int) ($d['deadman_threshold_days'] ?? self::DEFAULT_THRESHOLD_DAYS);
        $action    = $d['deadman_action'] ?? 'AUDIT_ONLY';

        // Jika fitur dinonaktifkan oleh admin (toggle off) atau kebijakannya AUDIT_ONLY,
        // sistem selalu berjalan dalam mode DRY-RUN (simulasi aman tanpa perubahan status)
        $dryRun = ($enabled === 0 || $action === 'AUDIT_ONLY');

        return new self($koneksi, $dryRun, $threshold, $action);
    }

    public function isDryRun() {
        return $this->dryRun;
    }

    public function getThresholdDays() {
        return $this->thresholdDays;
    }

    public function getActionPolicy() {
        return $this->actionPolicy;
    }

    /**
     * Mengambil daftar pendaftaran yang telah melewati tenggat waktu
     * Pengurutan konsisten menggunakan FIFO Periode Magang
     */
    public function getPendingOverdueCandidates() {
        $order = get_fifo_order_clause('p');
        $sql = "SELECT p.*, u.nama, u.email, u.asal_instansi,
                       TIMESTAMPDIFF(DAY, p.tgl_daftar, NOW()) as idle_days,
                       TIMESTAMPDIFF(HOUR, p.tgl_daftar, NOW()) as idle_hours
                FROM pendaftaran p
                JOIN peserta u ON p.id_user = u.id_user
                WHERE p.status = 'Menunggu'
                  AND p.tgl_daftar < NOW() - INTERVAL ? DAY
                $order";

        $stmt = mysqli_prepare($this->koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "i", $this->thresholdDays);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $candidates = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $candidates[] = $row;
        }
        mysqli_stmt_close($stmt);

        return $candidates;
    }

    /**
     * Menjalankan siklus evaluasi dan audit Deadman
     * @return array Hasil evaluasi untuk setiap kandidat
     */
    public function runAudit() {
        $candidates = $this->getPendingOverdueCandidates();
        $results = [];

        // Periksa kuota terkini
        $q_kuota = mysqli_query($this->koneksi, "SELECT jumlah_maks FROM kuota WHERE id_kuota = 1 LIMIT 1");
        $d_kuota = mysqli_fetch_assoc($q_kuota);
        $maks = (int) ($d_kuota['jumlah_maks'] ?? 0);

        $q_aktf = mysqli_query($this->koneksi, "SELECT COUNT(*) as tot FROM pendaftaran WHERE status = 'Diterima'");
        $d_aktf = mysqli_fetch_assoc($q_aktf);
        $aktif = (int) ($d_aktf['tot'] ?? 0);
        $sisa_kuota = $maks - $aktif;

        $upload_dir = __DIR__ . '/../uploads/';

        foreach ($candidates as $cand) {
            $id_daftar = (int) $cand['id_daftar'];
            $filepath  = $upload_dir . $cand['berkas'];

            // 1. Evaluasi Kelengkapan dan Validitas Berkas
            $berkas_exists = (!empty($cand['berkas']) && file_exists($filepath));
            $status_berkas = $cand['status_berkas'] ?? 'Menunggu Validasi';
            
            if ($berkas_exists && ($status_berkas == 'Menunggu Validasi' || empty($status_berkas))) {
                $valRes = PDFValidator::validateSuratPengantar($filepath, $cand['berkas']);
                $status_berkas = $valRes['status_berkas'];
            }

            // 2. Evaluasi Posisi FIFO
            $is_top_queue = is_fifo_top($this->koneksi, $id_daftar, 'Menunggu');
            $queue_pos    = get_participant_queue_position($this->koneksi, $id_daftar, 'Menunggu');

            // 3. Tentukan Status Kelayakan & Usulan Tindakan
            $kelayakan = "LAYAK_DITINJAU";
            $usulan_tindakan = "NOTIFIKASI_ESKALASI_ADMIN";

            if (!$berkas_exists || $status_berkas === 'Tidak Valid') {
                $kelayakan = "BERKAS_TIDAK_VALID";
                $usulan_tindakan = "REKOMENDASI_TOLAK_BERKAS_RUSAK";
            } elseif ($status_berkas === 'Perlu Pemeriksaan Manual') {
                $kelayakan = "PERLU_VERIFIKASI_MANUAL";
                $usulan_tindakan = "ESKALASI_VERIFIKASI_PETUGAS";
            } elseif ($sisa_kuota <= 0) {
                $kelayakan = "KUOTA_PENUH";
                $usulan_tindakan = "TETAP_DALAM_WAITING_LIST";
            } elseif ($status_berkas === 'Valid Otomatis' && $is_top_queue && $sisa_kuota > 0) {
                $kelayakan = "MEMENUHI_SYARAT_OTOMATIS";
                $usulan_tindakan = "REKOMENDASI_TERIMA_OTOMATIS";
            }

            // 4. Eksekusi Tindakan jika Aktif (Non Dry-Run) dan Kebijakan Menyetujui
            $eksekusi_status = "SKIPPED_DRY_RUN";
            if (!$this->dryRun) {
                $eksekusi_status = $this->executeApprovedPolicy($id_daftar, $cand, $kelayakan, $usulan_tindakan, $sisa_kuota);
            }

            $results[] = [
                'id_daftar'       => $id_daftar,
                'id_user'         => $cand['id_user'],
                'nama'            => $cand['nama'],
                'asal_instansi'   => $cand['asal_instansi'],
                'tgl_daftar'      => $cand['tgl_daftar'],
                'tanggal_mulai'   => $cand['tanggal_mulai'],
                'tanggal_selesai' => $cand['tanggal_selesai'],
                'idle_days'       => $cand['idle_days'],
                'idle_hours'      => $cand['idle_hours'],
                'queue_pos'       => $queue_pos,
                'status_berkas'   => $status_berkas,
                'kelayakan'       => $kelayakan,
                'usulan_tindakan' => $usulan_tindakan,
                'mode'            => $this->dryRun ? 'DRY_RUN (Simulasi)' : 'ACTIVE',
                'hasil_eksekusi'  => $eksekusi_status
            ];
        }

        return $results;
    }

    /**
     * Mengeksekusi kebijakan yang telah disetujui (idempotent, atomic, audit log)
     */
    private function executeApprovedPolicy($id_daftar, $cand, $kelayakan, $usulan_tindakan, &$sisa_kuota) {
        // Cek idempotensi: Jangan proses jika status di DB sudah bukan 'Menunggu'
        mysqli_begin_transaction($this->koneksi);

        $q_check = mysqli_query($this->koneksi, "SELECT status FROM pendaftaran WHERE id_daftar = $id_daftar FOR UPDATE");
        $d_check = mysqli_fetch_assoc($q_check);
        if (!$d_check || $d_check['status'] !== 'Menunggu') {
            mysqli_rollback($this->koneksi);
            return 'SKIPPED_ALREADY_PROCESSED';
        }

        if ($this->actionPolicy === 'AUTO_REJECT_INVALID_ONLY' && $kelayakan === 'BERKAS_TIDAK_VALID') {
            $stmt = mysqli_prepare($this->koneksi, "UPDATE pendaftaran SET status='Ditolak', tgl_verifikasi=NOW(3), catatan_admin='Otomatis ditolak Deadman: Melewati tenggat dan berkas tidak valid' WHERE id_daftar=? AND status='Menunggu'");
            mysqli_stmt_bind_param($stmt, "i", $id_daftar);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            catat_audit($this->koneksi, null, 'DEADMAN_SYSTEM', $id_daftar, 'pendaftaran', 'AUTO_REJECT', 'Menunggu', 'Ditolak', "Deadman System menolak otomatis setelah idle {$cand['idle_days']} hari karena berkas tidak valid");
            mysqli_commit($this->koneksi);
            return 'EXECUTED_AUTO_REJECT';
        }

        if ($this->actionPolicy === 'AUTO_ACCEPT_ELIGIBLE' && $kelayakan === 'MEMENUHI_SYARAT_OTOMATIS' && $sisa_kuota > 0) {
            $stmt = mysqli_prepare($this->koneksi, "UPDATE pendaftaran SET status='Diterima', tgl_verifikasi=NOW(3), catatan_admin='Otomatis diterima Deadman: Melewati tenggat, berkas valid, kuota tersedia' WHERE id_daftar=? AND status='Menunggu'");
            mysqli_stmt_bind_param($stmt, "i", $id_daftar);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            catat_audit($this->koneksi, null, 'DEADMAN_SYSTEM', $id_daftar, 'pendaftaran', 'AUTO_ACCEPT', 'Menunggu', 'Diterima', "Deadman System menerima otomatis setelah idle {$cand['idle_days']} hari, berkas valid & FIFO teratas");
            $sisa_kuota--;
            mysqli_commit($this->koneksi);
            return 'EXECUTED_AUTO_ACCEPT';
        }

        mysqli_rollback($this->koneksi);
        return 'SKIPPED_POLICY_AUDIT_ONLY';
    }

    /**
     * Mengunci proses menggunakan lockfile untuk mencegah eksekusi ganda serentak
     */
    public static function acquireProcessLock() {
        $lockFile = self::LOCK_FILE;
        $fp = @fopen($lockFile, 'c+');
        if (!$fp) {
            return false;
        }
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return false; // Proses lain sedang berjalan
        }
        return $fp;
    }

    public static function releaseProcessLock($fp) {
        if ($fp) {
            flock($fp, LOCK_UN);
            fclose($fp);
            @unlink(self::LOCK_FILE);
        }
    }
}
