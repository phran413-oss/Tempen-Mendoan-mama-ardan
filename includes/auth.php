<?php
/**
 * includes/auth.php
 * Dipasang di ATAS setiap halaman admin (kecuali login.php) buat mastiin
 * yang buka halaman itu memang admin yang sudah login.
 *
 * Cara pakai di halaman admin, taruh di baris paling atas:
 *   require_once __DIR__ . '/../includes/auth.php';
 */

// session_start() harus dipanggil sebelum ada output apapun ke browser
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kalau belum login (session admin_id belum ada), tendang ke halaman login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
