<?php
/**
 * FIFO Helper Library
 * Sistem Manajemen Penerimaan Magang BTKP DIY
 * 
 * Mengimplementasikan urutan prioritas FIFO berdasarkan Periode Magang:
 * 1. Tanggal mulai magang paling awal (tanggal_mulai ASC). Periode NULL ditaruh di akhir.
 * 2. Tanggal selesai magang paling awal jika tanggal mulai sama (tanggal_selesai ASC).
 * 3. Timestamp pendaftaran paling awal jika periode sama (tgl_daftar ASC).
 * 4. ID pendaftaran unik sebagai tie-breaker penentu (id_daftar ASC).
 */

if (!function_exists('get_fifo_order_clause')) {
    /**
     * Menghasilkan klausa SQL ORDER BY untuk pengurutan FIFO berbasis periode magang
     * 
     * @param string $alias Alias tabel pendaftaran dalam query (default 'p')
     * @return string Klausa SQL ORDER BY
     */
    function get_fifo_order_clause($alias = 'p') {
        $prefix = !empty($alias) ? rtrim($alias, '.') . '.' : '';
        return "ORDER BY ISNULL({$prefix}tanggal_mulai) ASC, {$prefix}tanggal_mulai ASC, 
                         ISNULL({$prefix}tanggal_selesai) ASC, {$prefix}tanggal_selesai ASC, 
                         {$prefix}tgl_daftar ASC, 
                         {$prefix}id_daftar ASC";
    }
}

if (!function_exists('get_top_fifo_candidate')) {
    /**
     * Mengambil ID pendaftaran pertama dalam antrean FIFO aktif
     * 
     * @param mysqli $koneksi Koneksi database aktif
     * @param string $status Filter status antrean (default 'Menunggu')
     * @return array|null Data baris kandidat pertama atau null jika antrean kosong
     */
    function get_top_fifo_candidate($koneksi, $status = 'Menunggu') {
        $safe_status = mysqli_real_escape_string($koneksi, $status);
        $order = get_fifo_order_clause('p');
        $sql = "SELECT p.*, u.nama, u.email, u.asal_instansi 
                FROM pendaftaran p
                JOIN peserta u ON p.id_user = u.id_user
                WHERE p.status = '$safe_status'
                $order
                LIMIT 1";
        
        $res = mysqli_query($koneksi, $sql);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            return $row;
        }
        return null;
    }
}

if (!function_exists('is_fifo_top')) {
    /**
     * Memverifikasi apakah ID pendaftaran tertentu merupakan urutan pertama dalam antrean FIFO
     * 
     * @param mysqli $koneksi Koneksi database aktif
     * @param int $id_daftar ID pendaftaran yang diperiksa
     * @param string $status Filter status antrean (default 'Menunggu')
     * @return bool True jika merupakan pendaftar paling berhak diproses
     */
    function is_fifo_top($koneksi, $id_daftar, $status = 'Menunggu') {
        $top = get_top_fifo_candidate($koneksi, $status);
        if (!$top) {
            return true; // Antrean kosong atau tidak ada kandidat lain
        }
        return ((int) $top['id_daftar'] === (int) $id_daftar);
    }
}

if (!function_exists('get_participant_queue_position')) {
    /**
     * Menghitung nomor urut antrean FIFO (1-based) untuk pendaftaran tertentu
     * 
     * @param mysqli $koneksi Koneksi database aktif
     * @param int $id_daftar ID pendaftaran yang dihitung posisinya
     * @param string $status Filter status antrean (default 'Menunggu')
     * @return int Nomor antrean (1, 2, 3...) atau 0 jika tidak ada dalam antrean
     */
    function get_participant_queue_position($koneksi, $id_daftar, $status = 'Menunggu') {
        $safe_status = mysqli_real_escape_string($koneksi, $status);
        $order = get_fifo_order_clause('p');
        
        $sql = "SELECT p.id_daftar 
                FROM pendaftaran p
                WHERE p.status = '$safe_status'
                $order";
        
        $res = mysqli_query($koneksi, $sql);
        if (!$res) {
            return 0;
        }
        
        $pos = 1;
        while ($row = mysqli_fetch_assoc($res)) {
            if ((int) $row['id_daftar'] === (int) $id_daftar) {
                return $pos;
            }
            $pos++;
        }
        return 0;
    }
}
