<?php
/**
 * admin/selesai.php
 * Aksi: ubah status order dari 'dikonfirmasi' -> 'siap_diambil'
 * Diakses lewat link tombol "Pesanan Jadi / Siap Diambil" di dashboard.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if ($id && ctype_digit((string) $id)) {
    $stmt = $koneksi->prepare("UPDATE orders SET status = 'siap_diambil' WHERE id = ? AND status = 'dikonfirmasi'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: dashboard.php');
exit;
