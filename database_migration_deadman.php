<?php
/**
 * Database Migration Script:
 * Adding Deadman System Configuration Columns to `kuota` table
 */

require_once __DIR__ . '/config/database.php';

echo "=== MEMULAI MIGRASI DEADMAN SYSTEM PADA KUOTA.PHP ===" . PHP_EOL;

$existing_cols = [];
$res = $koneksi->query("SHOW COLUMNS FROM `kuota`");
while ($row = $res->fetch_assoc()) {
    $existing_cols[] = $row['Field'];
}

// 1. Tambah kolom deadman_enabled jika belum ada
if (!in_array('deadman_enabled', $existing_cols)) {
    $sql = "ALTER TABLE `kuota` ADD COLUMN `deadman_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sisa_kuota`";
    if ($koneksi->query($sql)) {
        echo "[MIGRASI] Kolom 'deadman_enabled' berhasil ditambahkan." . PHP_EOL;
    } else {
        echo "[ERROR] Gagal menambah 'deadman_enabled': " . $koneksi->error . PHP_EOL;
    }
} else {
    echo "[INFO] Kolom 'deadman_enabled' sudah ada." . PHP_EOL;
}

// 2. Tambah kolom deadman_threshold_days jika belum ada
if (!in_array('deadman_threshold_days', $existing_cols)) {
    $sql = "ALTER TABLE `kuota` ADD COLUMN `deadman_threshold_days` INT NOT NULL DEFAULT 7 AFTER `deadman_enabled`";
    if ($koneksi->query($sql)) {
        echo "[MIGRASI] Kolom 'deadman_threshold_days' berhasil ditambahkan." . PHP_EOL;
    } else {
        echo "[ERROR] Gagal menambah 'deadman_threshold_days': " . $koneksi->error . PHP_EOL;
    }
} else {
    echo "[INFO] Kolom 'deadman_threshold_days' sudah ada." . PHP_EOL;
}

// 3. Tambah kolom deadman_action jika belum ada
if (!in_array('deadman_action', $existing_cols)) {
    $sql = "ALTER TABLE `kuota` ADD COLUMN `deadman_action` ENUM('AUDIT_ONLY', 'AUTO_REJECT_INVALID_ONLY', 'AUTO_ACCEPT_ELIGIBLE') NOT NULL DEFAULT 'AUDIT_ONLY' AFTER `deadman_threshold_days`";
    if ($koneksi->query($sql)) {
        echo "[MIGRASI] Kolom 'deadman_action' berhasil ditambahkan." . PHP_EOL;
    } else {
        echo "[ERROR] Gagal menambah 'deadman_action': " . $koneksi->error . PHP_EOL;
    }
} else {
    echo "[INFO] Kolom 'deadman_action' sudah ada." . PHP_EOL;
}

// 4. Tambah kolom deadman_updated_at jika belum ada
if (!in_array('deadman_updated_at', $existing_cols)) {
    $sql = "ALTER TABLE `kuota` ADD COLUMN `deadman_updated_at` DATETIME(3) NULL AFTER `deadman_action`";
    if ($koneksi->query($sql)) {
        echo "[MIGRASI] Kolom 'deadman_updated_at' berhasil ditambahkan." . PHP_EOL;
    } else {
        echo "[ERROR] Gagal menambah 'deadman_updated_at': " . $koneksi->error . PHP_EOL;
    }
} else {
    echo "[INFO] Kolom 'deadman_updated_at' sudah ada." . PHP_EOL;
}

echo "=== MIGRASI DEADMAN SELESAI ===" . PHP_EOL;
