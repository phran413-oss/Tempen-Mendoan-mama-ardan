<?php
/**
 * user/simpan_pesanan.php
 * Proses submit form dari checkout.php:
 * 1. Validasi input
 * 2. Insert ke tabel orders
 * 3. Insert tiap item ke order_items
 * 4. Redirect ke status.php bawa order_code (nanti disimpan ke localStorage di sana)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload_bukti_bayar.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('checkout.php');
}

$nama_pemesan   = bersihkanInput($_POST['nama_pemesan'] ?? '');
$no_hp_pemesan  = bersihkanInput($_POST['no_hp_pemesan'] ?? '');
$tanggal_ambil  = bersihkanInput($_POST['tanggal_ambil'] ?? '');
$metode_bayar   = $_POST['metode_bayar'] ?? 'cod';
$catatan        = bersihkanInput($_POST['catatan'] ?? '');
$qtyInput       = $_POST['qty'] ?? []; // format: [product_id => jumlah]

// --- Validasi dasar ---
if ($nama_pemesan === '') {
    die('Nama wajib diisi. <a href="checkout.php">Kembali</a>');
}

// Validasi tanggal ambil: wajib diisi, format bener, dan nggak boleh tanggal yang udah lewat
$tanggalValid = DateTime::createFromFormat('Y-m-d', $tanggal_ambil);
$hariIni = new DateTime('today');
if ($tanggal_ambil === '' || !$tanggalValid || $tanggalValid < $hariIni) {
    die('Tanggal ambil tidak valid, pilih hari ini atau setelahnya. <a href="checkout.php">Kembali</a>');
}

// Validasi metode bayar: cuma boleh salah satu dari dua opsi yang disediain
if (!in_array($metode_bayar, ['cod', 'qris'], true)) {
    $metode_bayar = 'cod';
}

// Kalau metode QRIS, bukti bayar WAJIB ada dan valid (dicek sebelum order dibuat,
// biar kalau uploadnya gagal, nggak ada order "nyangkut" tanpa bukti)
if ($metode_bayar === 'qris') {
    if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] === UPLOAD_ERR_NO_FILE) {
        die('Bukti pembayaran wajib diupload untuk metode QRIS. <a href="checkout.php">Kembali</a>');
    }
}

// Ambil product_id yang jumlahnya > 0
$productIds = [];
foreach ($qtyInput as $productId => $jumlah) {
    $jumlah = (int) $jumlah;
    if ($jumlah > 0) {
        $productIds[(int) $productId] = $jumlah;
    }
}

if (empty($productIds)) {
    die('Pilih minimal 1 item. <a href="checkout.php">Kembali</a>');
}

// --- Ambil data produk asli dari database (JANGAN percaya harga dari form!) ---
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$types = str_repeat('i', count($productIds));
$ids = array_keys($productIds);

$stmt = $koneksi->prepare("SELECT id, nama_produk, harga FROM products WHERE id IN ($placeholders) AND aktif = 1");
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($products)) {
    die('Item tidak valid. <a href="checkout.php">Kembali</a>');
}

// --- Hitung total & siapkan data item ---
$items = [];
$totalHarga = 0;

foreach ($products as $p) {
    $jumlah = $productIds[$p['id']];
    $subtotal = $p['harga'] * $jumlah;
    $totalHarga += $subtotal;

    $items[] = [
        'product_id'   => $p['id'],
        'nama_produk'  => $p['nama_produk'],
        'harga_satuan' => $p['harga'],
        'jumlah'       => $jumlah,
        'subtotal'     => $subtotal,
    ];
}

// --- Generate kode order unik (cek jangan sampai bentrok) ---
do {
    $orderCode = generateOrderCode();
    $cek = $koneksi->prepare('SELECT id FROM orders WHERE order_code = ?');
    $cek->bind_param('s', $orderCode);
    $cek->execute();
    $adaBentrok = $cek->get_result()->num_rows > 0;
    $cek->close();
} while ($adaBentrok);

// --- Simpan ke database (pakai transaction biar aman: orders & order_items sukses bareng) ---
$koneksi->begin_transaction();

try {
    $stmtOrder = $koneksi->prepare(
        'INSERT INTO orders (order_code, nama_pemesan, no_hp_pemesan, tanggal_ambil, metode_bayar, catatan, total_harga, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $status = 'pending';
    $stmtOrder->bind_param('ssssssis', $orderCode, $nama_pemesan, $no_hp_pemesan, $tanggal_ambil, $metode_bayar, $catatan, $totalHarga, $status);
    $stmtOrder->execute();
    $orderId = $koneksi->insert_id;
    $stmtOrder->close();

    // Kalau metode QRIS, proses & simpan foto bukti bayarnya sekarang
    // (butuh $orderId buat nama filenya, makanya baru bisa dilakuin di sini)
    if ($metode_bayar === 'qris') {
        $uploadDir = __DIR__ . '/../uploads/bukti_bayar';
        $hasil = uploadBuktiBayar($_FILES['bukti_bayar'], $orderId, $uploadDir);

        if (!$hasil['success']) {
            throw new Exception($hasil['error']); // bakal ke-catch di bawah -> rollback semua
        }

        $stmtBukti = $koneksi->prepare('UPDATE orders SET bukti_bayar = ? WHERE id = ?');
        $stmtBukti->bind_param('si', $hasil['filename'], $orderId);
        $stmtBukti->execute();
        $stmtBukti->close();
    }

    $stmtItem = $koneksi->prepare(
        'INSERT INTO order_items (order_id, product_id, nama_produk, harga_satuan, jumlah, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($items as $item) {
        $stmtItem->bind_param(
            'iisiii',
            $orderId,
            $item['product_id'],
            $item['nama_produk'],
            $item['harga_satuan'],
            $item['jumlah'],
            $item['subtotal']
        );
        $stmtItem->execute();
    }
    $stmtItem->close();

    $koneksi->commit();
} catch (Exception $e) {
    $koneksi->rollback();
    die('Gagal menyimpan pesanan: ' . htmlspecialchars($e->getMessage()) . ' <a href="checkout.php">Kembali</a>');
}

// Redirect ke halaman status, bawa order_code + tanda "baru" biar disimpan ke localStorage
redirect('status.php?code=' . urlencode($orderCode) . '&baru=1');