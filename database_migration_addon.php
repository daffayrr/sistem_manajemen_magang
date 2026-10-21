<?php
/**
 * Database Migration Script for Addon Requirements:
 * - Document Validation Status & Notes
 * - Non-destructive alterations to `pendaftaran` table
 */

require_once __DIR__ . '/config/database.php';

echo "=== MEMULAI MIGRASI ADDON ===" . PHP_EOL;

// 1. Periksa kolom pada tabel pendaftaran
$existing_cols = [];
$res = $koneksi->query("SHOW COLUMNS FROM `pendaftaran`");
while ($row = $res->fetch_assoc()) {
    $existing_cols[] = $row['Field'];
}

// 2. Tambah kolom status_berkas jika belum ada
if (!in_array('status_berkas', $existing_cols)) {
    $sql = "ALTER TABLE `pendaftaran` 
            ADD COLUMN `status_berkas` ENUM('Belum Diunggah', 'Menunggu Validasi', 'Valid Otomatis', 'Perlu Pemeriksaan Manual', 'Tidak Valid', 'Valid Admin') 
            NOT NULL DEFAULT 'Menunggu Validasi' AFTER `berkas`";
    if ($koneksi->query($sql)) {
        echo "[MIGRASI] Kolom 'status_berkas' berhasil ditambahkan." . PHP_EOL;
    } else {
        echo "[ERROR] Gagal menambah 'status_berkas': " . $koneksi->error . PHP_EOL;
    }
} else {
    echo "[INFO] Kolom 'status_berkas' sudah ada." . PHP_EOL;
}

// 3. Tambah kolom catatan_berkas jika belum ada
if (!in_array('catatan_berkas', $existing_cols)) {
    $sql = "ALTER TABLE `pendaftaran` 
            ADD COLUMN `catatan_berkas` VARCHAR(255) NULL AFTER `status_berkas`";
    if ($koneksi->query($sql)) {
        echo "[MIGRASI] Kolom 'catatan_berkas' berhasil ditambahkan." . PHP_EOL;
    } else {
        echo "[ERROR] Gagal menambah 'catatan_berkas': " . $koneksi->error . PHP_EOL;
    }
} else {
    echo "[INFO] Kolom 'catatan_berkas' sudah ada." . PHP_EOL;
}

// 4. Tambah kolom status_proposal jika belum ada
if (!in_array('status_proposal', $existing_cols)) {
    $sql = "ALTER TABLE `pendaftaran` 
            ADD COLUMN `status_proposal` ENUM('Tidak Diunggah', 'Menunggu Validasi', 'Valid Otomatis', 'Perlu Pemeriksaan Manual', 'Tidak Valid') 
            NOT NULL DEFAULT 'Tidak Diunggah' AFTER `proposal`";
    if ($koneksi->query($sql)) {
        echo "[MIGRASI] Kolom 'status_proposal' berhasil ditambahkan." . PHP_EOL;
    } else {
        echo "[ERROR] Gagal menambah 'status_proposal': " . $koneksi->error . PHP_EOL;
    }
} else {
    echo "[INFO] Kolom 'status_proposal' sudah ada." . PHP_EOL;
}

// 5. Pastikan indeks performa untuk pengurutan FIFO periode
$indexes = [];
$res_idx = $koneksi->query("SHOW INDEX FROM `pendaftaran`");
while ($row = $res_idx->fetch_assoc()) {
    $indexes[] = $row['Key_name'];
}

if (!in_array('idx_fifo_periode', $indexes)) {
    $sql_idx = "ALTER TABLE `pendaftaran` ADD INDEX `idx_fifo_periode` (`status`, `tanggal_mulai`, `tanggal_selesai`, `tgl_daftar`, `id_daftar`)";
    if ($koneksi->query($sql_idx)) {
        echo "[MIGRASI] Index 'idx_fifo_periode' berhasil dibuat untuk optimasi FIFO." . PHP_EOL;
    } else {
        echo "[ERROR] Gagal membuat index: " . $koneksi->error . PHP_EOL;
    }
} else {
    echo "[INFO] Index 'idx_fifo_periode' sudah ada." . PHP_EOL;
}

echo "=== MIGRASI ADDON SELESAI ===" . PHP_EOL;
