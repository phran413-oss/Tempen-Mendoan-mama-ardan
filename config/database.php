<?php
/**
 * config/database.php
 * File koneksi ke database MySQL.
 * File ini di-include di halaman lain pakai: require_once __DIR__ . '/../config/database.php';
 */

// --- Ganti sesuai setup lokal kamu (XAMPP/Laragon dll) ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // default XAMPP: root
define('DB_PASS', '');           // default XAMPP: kosong
define('DB_NAME', 'umkm_mendoan');

// Buat koneksi pakai mysqli
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($koneksi->connect_error) {
    // Saat development boleh tampilkan detail error.
    // Nanti kalau sudah live/hosting, ganti jadi pesan generik biar aman.
    die('Koneksi database gagal: ' . $koneksi->connect_error);
}

// Set karakter set biar aman dari masalah encoding (emoji, karakter aneh, dll)
$koneksi->set_charset('utf8mb4');
