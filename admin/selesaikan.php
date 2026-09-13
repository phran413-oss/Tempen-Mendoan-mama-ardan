<?php
/**
 * admin/selesaikan.php
 * Aksi: ubah status order dari 'siap_diambil' -> 'selesai'
 * Dipakai admin buat nandain kalau pesanan udah beneran diambil sama customer.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if ($id && ctype_digit((string) $id)) {
    $stmt = $koneksi->prepare("UPDATE orders SET status = 'selesai' WHERE id = ? AND status = 'siap_diambil'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: dashboard.php');
exit;
