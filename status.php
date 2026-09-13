<?php
/**
 * user/status.php
 * Menampilkan detail status 1 pesanan berdasarkan order_code.
 * Diakses dari: simpan_pesanan.php (?code=...&baru=1) atau dari riwayat.php (?code=...)
 *
 * Kalau ?baru=1, halaman ini juga yang nyimpen order_code ke localStorage
 * lewat JS di bagian bawah (biar muncul di "Pesanan Saya" nanti).
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$code = $_GET['code'] ?? '';
$isBaru = isset($_GET['baru']) && $_GET['baru'] == '1';

if ($code === '') {
    redirect('pesan.php');
}

$stmt = $koneksi->prepare('SELECT * FROM orders WHERE order_code = ?');
$stmt->bind_param('s', $code);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die('Pesanan tidak ditemukan. <a href="pesan.php">Buat pesanan baru</a>');
}

// Ambil item pesanan
$stmtItems = $koneksi->prepare('SELECT * FROM order_items WHERE order_id = ?');
$stmtItems->bind_param('i', $order['id']);
$stmtItems->execute();
$items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItems->close();

// Ambil nomor WA admin (buat kontak kalau mau ubah pesanan / jadwal ambil)
$admin = $koneksi->query('SELECT nama_lengkap, no_wa FROM admin LIMIT 1')->fetch_assoc();

// Urutan tahapan, buat progress step visual
$tahapan = ['pending', 'dikonfirmasi', 'siap_diambil', 'selesai'];
$stepSekarang = array_search($order['status'], $tahapan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan <?= htmlspecialchars($order['order_code']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #e9e7e2;
            color: #2c2a26;
            padding: 1.25rem;
        }
        header { margin-bottom: 1.25rem; }
        header a { font-size: 0.85rem; color: #4a5a35; text-decoration: none; }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .order-code { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem; }
        .order-time { font-size: 0.8rem; color: #888; margin-bottom: 1rem; }

        /* Progress steps */
        .steps { display: flex; margin-bottom: 0.5rem; }
        .step { flex: 1; text-align: center; position: relative; }
        .step-dot {
            width: 28px; height: 28px; border-radius: 50%;
            background: #eee; color: #999;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 0.4rem; font-size: 0.8rem; font-weight: 700;
        }
        .step.done .step-dot { background: #1f8a3d; color: #fff; }
        .step.current .step-dot { background: #4a5a35; color: #fff; }
        .step-label { font-size: 0.7rem; color: #888; }
        .step.done .step-label, .step.current .step-label { color: #2c2a26; font-weight: 600; }
        .step-line {
            position: absolute; top: 14px; left: -50%; width: 100%;
            height: 2px; background: #eee; z-index: -1;
        }
        .step.done .step-line { background: #1f8a3d; }
        .step:first-child .step-line { display: none; }

        .status-text {
            text-align: center;
            font-weight: 600;
            padding: 0.6rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .status-pending { background: #fff4e0; color: #9a6b00; }
        .status-dikonfirmasi { background: #e3f0ff; color: #0b5cad; }
        .status-siap_diambil { background: #e6f7e9; color: #1f8a3d; }
        .status-dibatalkan { background: #fdecea; color: #b3261e; }

        .item-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            padding: 0.3rem 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
        }

        .contact-box {
            background: #fff;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            text-align: center;
        }
        .contact-box p { font-size: 0.85rem; color: #666; margin-bottom: 0.6rem; }
        .btn-wa {
            display: inline-block;
            background: #25D366;
            color: #fff;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0.3rem 0.3rem;
        }
        .btn-lokasi {
            display: inline-block;
            background: #eef0ea;
            color: #4a5a35;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0.3rem 0.3rem;
        }
    </style>
</head>
<body>
    <header>
        <a href="riwayat.php">&larr; Pesanan Saya</a>
    </header>

    <div class="card">
        <div class="order-code"><?= htmlspecialchars($order['order_code']) ?></div>
        <div class="order-time">Dipesan <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>

        <?php if (in_array($order['status'], $tahapan, true)): ?>
        <div class="steps">
            <?php foreach ($tahapan as $i => $t): ?>
                <div class="step <?= $i < $stepSekarang ? 'done' : ($i === $stepSekarang ? 'current' : '') ?>">
                    <div class="step-line"></div>
                    <div class="step-dot"><?= $i < $stepSekarang ? '&check;' : $i + 1 ?></div>
                    <div class="step-label">
                        <?php
                            $labelStep = [
                                'pending' => 'Menunggu',
                                'dikonfirmasi' => 'Dikonfirmasi',
                                'siap_diambil' => 'Siap Diambil',
                                'selesai' => 'Selesai',
                            ];
                            echo $labelStep[$t];
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="status-text status-<?= $order['status'] ?>">
            <?= labelStatus($order['status']) ?>
        </div>
    </div>

    <div class="card">
        <strong>Detail Pesanan</strong>
        <div style="margin-top:0.5rem;">
            <?php foreach ($items as $item): ?>
                <div class="item-row">
                    <span><?= $item['jumlah'] ?>x <?= htmlspecialchars($item['nama_produk']) ?></span>
                    <span><?= formatRupiah($item['subtotal']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="total-row">
            <span>Total</span>
            <span><?= formatRupiah($order['total_harga']) ?></span>
        </div>
        <div style="font-size:0.85rem;color:#2c2a26;margin-top:0.5rem;font-weight:600;">
            Rencana ambil: <?= date('d M Y', strtotime($order['tanggal_ambil'])) ?>
        </div>
        <div style="font-size:0.85rem;color:#2c2a26;margin-top:0.3rem;font-weight:600;">
            Bayar: <?= $order['metode_bayar'] === 'qris' ? 'QRIS' : 'Bayar di Tempat' ?>
        </div>
        <?php if (!empty($order['catatan'])): ?>
            <div style="font-size:0.85rem;color:#777;margin-top:0.5rem;">Catatan: <?= htmlspecialchars($order['catatan']) ?></div>
        <?php endif; ?>
    </div>

    <?php if ($order['metode_bayar'] === 'qris' && $order['status'] !== 'dibatalkan'): ?>
    <div class="card" style="text-align:center;">
        <div style="font-weight:700;font-size:0.9rem;margin-bottom:0.6rem;">Scan QRIS buat Bayar</div>
        <img src="assets/img/qris.jpeg" alt="QRIS Dapur Mama Ardan" style="max-width:220px;width:100%;border-radius:10px;border:1px solid #eee;">
        <p style="font-size:0.75rem;color:#8a877e;margin-top:0.6rem;line-height:1.4;">
            Total yang perlu dibayar: <strong><?= formatRupiah($order['total_harga']) ?></strong>
        </p>

        <div id="uploadWrap" style="margin-top:1rem;text-align:left;">
            <?php if (!empty($order['bukti_bayar'])): ?>
                <!-- Sudah pernah upload -->
                <div id="statusSudahUpload">
                    <div style="display:flex;align-items:center;gap:0.6rem;background:#e6f7e9;border-radius:10px;padding:0.7rem 0.9rem;">
                        <span style="font-size:1.3rem;">✅</span>
                        <div style="font-size:0.82rem;color:#1f8a3d;font-weight:600;">Bukti bayar sudah diupload</div>
                    </div>
                    <img src="../uploads/bukti_bayar/<?= htmlspecialchars($order['bukti_bayar']) ?>"
                         style="max-width:160px;border-radius:8px;margin-top:0.6rem;border:1px solid #eee;display:block;">
                    <button type="button" id="btnGantiFoto" style="margin-top:0.5rem;background:none;border:none;color:#4a5a35;font-size:0.78rem;font-weight:600;text-decoration:underline;cursor:pointer;padding:0;">
                        Ganti Foto
                    </button>
                </div>
            <?php else: ?>
                <div style="font-size:0.78rem;color:#8a877e;margin-bottom:0.5rem;">
                    Sudah transfer? Upload screenshot/foto bukti bayarnya di sini ya, biar Ibu langsung tau pesananmu sudah dibayar.
                </div>
            <?php endif; ?>

            <!-- Upload area, disembunyikan kalau sudah ada bukti (muncul lagi kalau klik "Ganti Foto") -->
            <div id="uploadArea" style="<?= !empty($order['bukti_bayar']) ? 'display:none;' : '' ?>">
                <label for="inputBukti" id="dropArea" style="
                    display:flex;align-items:center;justify-content:center;gap:0.5rem;
                    background:#4a5a35;color:#fff;border-radius:12px;padding:0.9rem 1rem;
                    cursor:pointer;text-align:center;font-size:0.95rem;font-weight:700;">
                    <span style="font-size:1.2rem;">📷</span>
                    <span>Bukti Pembayaran di Sini</span>
                </label>
                <div style="font-size:0.72rem;color:#a8a59c;text-align:center;margin-top:0.4rem;">JPG/PNG/WEBP, maks 2MB</div>
                <input type="file" id="inputBukti" accept="image/jpeg,image/png,image/webp" style="display:none;">

                <div id="previewWrap" style="display:none;margin-top:0.7rem;">
                    <img id="previewImg" style="max-width:160px;border-radius:8px;border:1px solid #eee;display:block;">
                    <div style="display:flex;gap:0.5rem;margin-top:0.6rem;">
                        <button type="button" id="btnUpload" style="flex:1;background:#4a5a35;color:#fff;border:none;border-radius:8px;padding:0.55rem;font-size:0.85rem;font-weight:600;cursor:pointer;">
                            Upload Sekarang
                        </button>
                        <button type="button" id="btnBatal" style="background:#eee;color:#666;border:none;border-radius:8px;padding:0.55rem 0.9rem;font-size:0.85rem;cursor:pointer;">
                            Batal
                        </button>
                    </div>
                </div>

                <div id="uploadMsg" style="font-size:0.78rem;margin-top:0.5rem;"></div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const inputBukti = document.getElementById('inputBukti');
            const previewWrap = document.getElementById('previewWrap');
            const previewImg = document.getElementById('previewImg');
            const dropArea = document.getElementById('dropArea');
            const btnUpload = document.getElementById('btnUpload');
            const btnBatal = document.getElementById('btnBatal');
            const btnGantiFoto = document.getElementById('btnGantiFoto');
            const uploadArea = document.getElementById('uploadArea');
            const uploadMsg = document.getElementById('uploadMsg');
            const statusSudahUpload = document.getElementById('statusSudahUpload');

            let fileTerpilih = null;

            inputBukti.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;
                fileTerpilih = file;

                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewWrap.style.display = 'block';
                    dropArea.style.display = 'none';
                    uploadMsg.textContent = '';
                };
                reader.readAsDataURL(file);
            });

            btnBatal.addEventListener('click', function () {
                fileTerpilih = null;
                inputBukti.value = '';
                previewWrap.style.display = 'none';
                dropArea.style.display = 'flex';
            });

            if (btnGantiFoto) {
                btnGantiFoto.addEventListener('click', function () {
                    statusSudahUpload.style.display = 'none';
                    uploadArea.style.display = 'block';
                });
            }

            btnUpload.addEventListener('click', function () {
                if (!fileTerpilih) return;

                btnUpload.disabled = true;
                btnUpload.textContent = 'Mengupload...';
                uploadMsg.style.color = '#888';
                uploadMsg.textContent = '';

                const formData = new FormData();
                formData.append('order_code', <?= json_encode($order['order_code']) ?>);
                formData.append('bukti', fileTerpilih);

                fetch('upload_bukti.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        btnUpload.disabled = false;
                        btnUpload.textContent = 'Upload Sekarang';
                        if (data.success) {
                            location.reload(); // reload biar nampilin status "sudah diupload" dari server
                        } else {
                            uploadMsg.style.color = '#b3261e';
                            uploadMsg.textContent = data.message || 'Gagal upload, coba lagi.';
                        }
                    })
                    .catch(() => {
                        btnUpload.disabled = false;
                        btnUpload.textContent = 'Upload Sekarang';
                        uploadMsg.style.color = '#b3261e';
                        uploadMsg.textContent = 'Gagal upload, cek koneksi kamu.';
                    });
            });
        })();
    </script>
    <?php endif; ?>

    <?php if ($admin && !empty($admin['no_wa'])): ?>
    <div class="contact-box">
        <p>Mau ubah pesanan atau atur jadwal ambil? Hubungi langsung ya.</p>
        <a class="btn-wa" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $admin['no_wa']) ?>?text=<?= urlencode('Halo, saya mau tanya soal pesanan ' . $order['order_code']) ?>" target="_blank">
            Chat WA <?= htmlspecialchars($admin['nama_lengkap'] ?? '') ?>
        </a>
        <a class="btn-lokasi" href="https://maps.app.goo.gl/zbN4LM6BwwtwjgbW7" target="_blank">
            Lihat Lokasi
        </a>
    </div>
    <?php endif; ?>

    <script>
        // Kalau ini pesanan baru (?baru=1), simpan order_code ke localStorage
        // biar muncul di halaman "Pesanan Saya" (riwayat.php), sekalian kosongin
        // keranjang belanja karena pesanannya udah berhasil dikonfirmasi.
        <?php if ($isBaru): ?>
        (function () {
            const kode = <?= json_encode($order['order_code']) ?>;
            let daftar = JSON.parse(localStorage.getItem('pesanan_saya') || '[]');
            if (!daftar.includes(kode)) {
                daftar.unshift(kode); // taruh di paling depan (terbaru duluan)
                localStorage.setItem('pesanan_saya', JSON.stringify(daftar));
            }
            localStorage.removeItem('keranjang_pesanan');
        })();
        <?php endif; ?>
    </script>
</body>
</html>