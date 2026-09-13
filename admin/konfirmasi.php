<?php
/**
 * admin/konfirmasi.php
 * Aksi: ubah status order dari 'pending' -> 'dikonfirmasi'
 * Diakses lewat link tombol "Konfirmasi" di dashboard.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if ($id && ctype_digit((string) $id)) {
    // Hanya boleh update kalau status SAAT INI masih 'pending'
    // (jaga-jaga kalau link diakses dobel / status sudah berubah duluan)
    $stmt = $koneksi->prepare("UPDATE orders SET status = 'dikonfirmasi' WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: dashboard.php');
exit;
