<?php
/**
 * admin/dashboard.php
 * Menampilkan semua pesanan masuk, terbaru di atas.
 * Tiap pesanan ada tombol aksi sesuai statusnya.
 */

require_once __DIR__ . '/../includes/auth.php'; // wajib login
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Filter status (opsional) lewat query string, contoh: dashboard.php?status=pending
$filter = $_GET['status'] ?? 'semua';
$statusValid = ['pending', 'dikonfirmasi', 'siap_diambil', 'selesai', 'dibatalkan'];

if ($filter !== 'semua' && in_array($filter, $statusValid, true)) {
    $stmt = $koneksi->prepare('SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC');
    $stmt->bind_param('s', $filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $koneksi->query('SELECT * FROM orders ORDER BY created_at DESC');
}

$orders = $result->fetch_all(MYSQLI_ASSOC);

// Ambil item tiap order sekaligus, biar nggak query berkali-kali dalam loop
$itemsPerOrder = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $types = str_repeat('i', count($orderIds));

    $stmtItems = $koneksi->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders)");
    $stmtItems->bind_param($types, ...$orderIds);
    $stmtItems->execute();
    $itemsResult = $stmtItems->get_result();

    while ($row = $itemsResult->fetch_assoc()) {
        $itemsPerOrder[$row['order_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Dapur Mama Ardan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #e9e7e2;
            color: #2c2a26;
            padding: 1.5rem;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        header h1 { font-size: 1.4rem; }
        header a {
            color: #4a5a35;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .filter-bar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }
        .filter-bar a {
            padding: 0.4rem 0.9rem;
            background: #fff;
            border-radius: 20px;
            font-size: 0.85rem;
            text-decoration: none;
            color: #555;
            border: 1px solid #ddd;
        }
        .filter-bar a.active {
            background: #4a5a35;
            color: #fff;
            border-color: #4a5a35;
        }
        .order-card {
            background: #fff;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: start;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .order-code {
            font-weight: 600;
            font-size: 1rem;
        }
        .order-time {
            font-size: 0.75rem;
            color: #888;
        }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-pending { background: #fff4e0; color: #9a6b00; }
        .badge-dikonfirmasi { background: #e3f0ff; color: #0b5cad; }
        .badge-siap_diambil { background: #e6f7e9; color: #1f8a3d; }
        .badge-selesai { background: #ececec; color: #555; }
        .badge-dibatalkan { background: #fdecea; color: #b3261e; }
        .items-list {
            font-size: 0.9rem;
            color: #555;
            margin: 0.5rem 0;
            padding-left: 1rem;
        }
        .order-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.75rem;
            border-top: 1px solid #f0f0f0;
            padding-top: 0.75rem;
        }
        .total {
            font-weight: 600;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-confirm { background: #0b5cad; color: #fff; }
        .btn-ready { background: #1f8a3d; color: #fff; }
        .btn-done { background: #555; color: #fff; }
        .btn-wa { background: #25D366; color: #fff; }
        .empty {
            text-align: center;
            color: #888;
            padding: 3rem 1rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>Dashboard Pesanan</h1>
        <a href="logout.php">Logout</a>
    </header>

    <div class="filter-bar">
        <a href="?status=semua" class="<?= $filter === 'semua' ? 'active' : '' ?>">Semua</a>
        <a href="?status=pending" class="<?= $filter === 'pending' ? 'active' : '' ?>">Menunggu</a>
        <a href="?status=dikonfirmasi" class="<?= $filter === 'dikonfirmasi' ? 'active' : '' ?>">Dikonfirmasi</a>
        <a href="?status=siap_diambil" class="<?= $filter === 'siap_diambil' ? 'active' : '' ?>">Siap Diambil</a>
        <a href="?status=selesai" class="<?= $filter === 'selesai' ? 'active' : '' ?>">Selesai</a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty">Belum ada pesanan di kategori ini.</div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-top">
                    <div>
                        <div class="order-code"><?= htmlspecialchars($order['order_code']) ?></div>
                        <div class="order-time"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
                    </div>
                    <span class="badge badge-<?= $order['status'] ?>"><?= labelStatus($order['status']) ?></span>
                </div>

                <div><strong><?= htmlspecialchars($order['nama_pemesan']) ?></strong>
                    <?php if (!empty($order['no_hp_pemesan'])): ?>
                        &middot; <?= htmlspecialchars($order['no_hp_pemesan']) ?>
                    <?php endif; ?>
                </div>

                <?php
                    $tanggalAmbil = $order['tanggal_ambil'];
                    $isHariIni = $tanggalAmbil === date('Y-m-d');
                    $isLewat = $tanggalAmbil < date('Y-m-d');
                ?>
                <div style="font-size:0.85rem;margin-top:0.2rem;<?= $isHariIni ? 'color:#b3261e;font-weight:700;' : 'color:#555;' ?>">
                    Ambil: <?= date('d M Y', strtotime($tanggalAmbil)) ?>
                    <?= $isHariIni ? ' (Hari Ini)' : ($isLewat ? ' (Terlewat)' : '') ?>
                </div>
                <div style="margin-top:0.35rem;display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <span style="display:inline-block;padding:0.15rem 0.6rem;border-radius:20px;font-size:0.72rem;font-weight:700;
                        <?= $order['metode_bayar'] === 'qris' ? 'background:#e3f0ff;color:#0b5cad;' : 'background:#fff4e0;color:#9a6b00;' ?>">
                        <?= $order['metode_bayar'] === 'qris' ? 'QRIS' : 'Bayar di Tempat' ?>
                    </span>

                    <?php if ($order['metode_bayar'] === 'qris'): ?>
                        <?php if (!empty($order['bukti_bayar'])): ?>
                            <a href="../uploads/bukti_bayar/<?= htmlspecialchars($order['bukti_bayar']) ?>" target="_blank"
                               style="display:flex;align-items:center;gap:0.3rem;font-size:0.75rem;color:#1f8a3d;font-weight:600;text-decoration:none;">
                                <img src="../uploads/bukti_bayar/<?= htmlspecialchars($order['bukti_bayar']) ?>"
                                     style="width:26px;height:26px;object-fit:cover;border-radius:5px;border:1px solid #ddd;">
                                Lihat Bukti Bayar
                            </a>
                        <?php else: ?>
                            <span style="font-size:0.75rem;color:#b3261e;font-weight:600;">Belum ada bukti bayar</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <ul class="items-list">
                    <?php foreach ($itemsPerOrder[$order['id']] ?? [] as $item): ?>
                        <li><?= $item['jumlah'] ?>x <?= htmlspecialchars($item['nama_produk']) ?> — <?= formatRupiah($item['subtotal']) ?></li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!empty($order['catatan'])): ?>
                    <div style="font-size:0.85rem;color:#777;">Catatan: <?= htmlspecialchars($order['catatan']) ?></div>
                <?php endif; ?>

                <div class="order-bottom">
                    <div class="total">Total: <?= formatRupiah($order['total_harga']) ?></div>

                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                        <?php if (!empty($order['no_hp_pemesan'])): ?>
                            <a class="btn btn-wa"
                               href="https://wa.me/<?= formatNomorWa($order['no_hp_pemesan']) ?>?text=<?= urlencode('Halo ' . $order['nama_pemesan'] . ', ini dari Dapur Mama Ardan soal pesanan ' . $order['order_code']) ?>"
                               target="_blank">Chat WA</a>
                        <?php endif; ?>

                        <?php if ($order['status'] === 'pending'): ?>
                            <a class="btn btn-confirm" href="konfirmasi.php?id=<?= $order['id'] ?>"
                               onclick="return confirm('Konfirmasi pesanan ini?');">Konfirmasi</a>
                        <?php elseif ($order['status'] === 'dikonfirmasi'): ?>
                            <a class="btn btn-ready" href="selesai.php?id=<?= $order['id'] ?>"
                               onclick="return confirm('Tandai pesanan ini siap diambil?');">Pesanan Jadi / Siap Diambil</a>
                        <?php elseif ($order['status'] === 'siap_diambil'): ?>
                            <a class="btn btn-done" href="selesaikan.php?id=<?= $order['id'] ?>"
                               onclick="return confirm('Pesanan ini sudah diambil customer?');">Tandai Selesai</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>