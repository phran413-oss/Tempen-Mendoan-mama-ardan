<?php
/**
 * user/upload_bukti.php
 * Endpoint AJAX buat terima upload bukti bayar QRIS dari status.php.
 * Terima: order_code (POST) + file (POST, nama field 'bukti')
 * Balikin JSON: { success: true/false, message, filename }
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

function gagal($pesan) {
    echo json_encode(['success' => false, 'message' => $pesan]);
    exit;
}

$orderCode = $_POST['order_code'] ?? '';
if ($orderCode === '') {
    gagal('Kode pesanan tidak ada.');
}

// Pastikan pesanan itu beneran ada dan metode bayarnya QRIS
$stmt = $koneksi->prepare('SELECT id, metode_bayar, bukti_bayar FROM orders WHERE order_code = ?');
$stmt->bind_param('s', $orderCode);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    gagal('Pesanan tidak ditemukan.');
}
if ($order['metode_bayar'] !== 'qris') {
    gagal('Pesanan ini bukan metode QRIS.');
}

// --- Validasi file ---
if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
    gagal('File gagal diupload, coba lagi.');
}

$file = $_FILES['bukti'];
$tipeDiizinkan = ['image/jpeg', 'image/png', 'image/webp'];
$maxUkuran = 2 * 1024 * 1024; // 2MB

$tipeFile = mime_content_type($file['tmp_name']);
if (!in_array($tipeFile, $tipeDiizinkan, true)) {
    gagal('Format file harus JPG, PNG, atau WEBP.');
}
if ($file['size'] > $maxUkuran) {
    gagal('Ukuran file maksimal 2MB.');
}

// --- Simpan file dengan nama unik ---
$ekstensi = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
][$tipeFile];

$namaFile = 'bukti_' . $orderCode . '_' . time() . '.' . $ekstensi;
$folderTujuan = __DIR__ . '/../uploads/bukti_bayar/';

if (!is_dir($folderTujuan)) {
    mkdir($folderTujuan, 0755, true);
}

// Kalau sebelumnya udah pernah upload, hapus file lama biar nggak numpuk
if (!empty($order['bukti_bayar'])) {
    $fileLama = $folderTujuan . $order['bukti_bayar'];
    if (file_exists($fileLama)) {
        unlink($fileLama);
    }
}

if (!move_uploaded_file($file['tmp_name'], $folderTujuan . $namaFile)) {
    gagal('Gagal menyimpan file di server.');
}

// --- Update database ---
$stmtUpdate = $koneksi->prepare('UPDATE orders SET bukti_bayar = ? WHERE id = ?');
$stmtUpdate->bind_param('si', $namaFile, $order['id']);
$stmtUpdate->execute();
$stmtUpdate->close();

echo json_encode(['success' => true, 'message' => 'Bukti bayar berhasil diupload.', 'filename' => $namaFile]);
