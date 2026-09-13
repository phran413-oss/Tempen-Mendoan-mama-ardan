<?php
/**
 * user/cek_status.php
 * Endpoint AJAX (dipanggil pakai fetch dari riwayat.php).
 * Terima daftar order_code, balikin data ringkas tiap pesanan dalam format JSON.
 *
 * Contoh pemanggilan dari JS:
 *   fetch('cek_status.php?codes=ORD-123,ORD-456')
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$codesParam = $_GET['codes'] ?? '';
$codes = array_filter(array_map('trim', explode(',', $codesParam)));

if (empty($codes)) {
    echo json_encode([]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($codes), '?'));
$types = str_repeat('s', count($codes));

$stmt = $koneksi->prepare(
    "SELECT order_code, nama_pemesan, status, total_harga, created_at
     FROM orders
     WHERE order_code IN ($placeholders)
     ORDER BY created_at DESC"
);
$stmt->bind_param($types, ...$codes);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Tambahin label status yang enak dibaca, biar JS di frontend nggak perlu mapping ulang
foreach ($orders as &$o) {
    $o['status_label'] = labelStatus($o['status']);
    $o['total_formatted'] = formatRupiah($o['total_harga']);
}

echo json_encode($orders);
