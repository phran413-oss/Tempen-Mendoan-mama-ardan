<?php
/**
 * user/index.php
 * Landing page sesuai mockup: hero banner (foto + logo), nama & alamat usaha,
 * grid 4 menu, dan bottom tab bar (Home / Pesanan).
 *
 * GAMBAR ASLI:
 * - Taruh foto banner di: user/assets/img/banner.jpg
 * - Taruh logo di:         user/assets/img/logo.png
 * - Foto menu (4 kotak) masih placeholder, tinggal diganti nanti di array $products
 *   dengan kolom foto dari database kalau sudah siap.
 */

require_once __DIR__ . '/../config/database.php';

$admin = $koneksi->query('SELECT nama_lengkap, no_wa FROM admin LIMIT 1')->fetch_assoc();
$products = $koneksi->query('SELECT * FROM products WHERE aktif = 1 ORDER BY is_paket ASC, id ASC')
                     ->fetch_all(MYSQLI_ASSOC);

// Data usaha (nanti bisa dipindah ke tabel settings kalau mau bisa diedit dari admin)
$namaUsaha = 'Dapur Mama Ardan';
$alamatUsaha = 'Jl. Panca Warga Satu, RT.2/RW.3, Cipinang Besar Sel., Kecamatan Jatinegara, Kota Jakarta Timur';
$linkMaps = 'https://maps.app.goo.gl/zbN4LM6BwwtwjgbW7';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($namaUsaha) ?> - Pesan Online</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #e9e7e2;
            color: #2c2a26;
            padding-bottom: 100px; /* ruang buat floating bottom nav */
        }

        /* ===== HERO ===== */
        /* position: fixed bikin hero BENERAN diem di belakang, nggak ikut alur
           scroll sama sekali — jadi nggak akan pernah "kehabisan ruang" dan
           nongol balik di ujung halaman. Tinggi hero ngikutin rasio ASLI foto
           (height: auto), jadi full size, nggak di-crop DAN nggak nyusut kecil.
           Jarak buat .content-sheet di-set otomatis lewat JS di bawah, soalnya
           tinggi hero baru ketauan setelah fotonya kelar dimuat browser. */
        .hero {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 0;
            line-height: 0; /* buang gap kecil di bawah <img> */
            background: linear-gradient(135deg, #4a5a35 0%, #2e3a20 55%, #24301a 100%);
        }
        .hero-img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Sheet konten utama, nutupin hero permanen begitu discroll lewatin dia.
           margin-top default di bawah ini cuma fallback sebelum JS jalan / kalau
           foto belum ke-upload; nanti otomatis disesuaikan pas foto asli kepasang. */
        .content-sheet {
            position: relative;
            z-index: 1;
            background: #e9e7e2;
            border-radius: 22px 22px 0 0;
            margin-top: 220px;
            padding-top: 22px;
        }

        /* Logo bulat, nongkrong di perbatasan hero & content-sheet */
        .logo-wrap {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            margin-top: -70px;
        }
        .logo-circle {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: #fff url('assets/img/logo.png') center/cover no-repeat;
            border: 4px solid #fff;
            box-shadow: 0 4px 14px rgba(0,0,0,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 700;
            color: #4a5a35;
            text-align: center;
            overflow: hidden;
        }

        /* ===== INFO USAHA ===== */
        .business-info {
            text-align: center;
            padding: 0.6rem 1.5rem 1.5rem;
        }
        .business-name {
            font-size: 1.15rem;
            font-weight: 700;
        }
        .business-address {
            font-size: 0.78rem;
            color: #6b6860;
            margin-top: 0.35rem;
            line-height: 1.4;
        }

        /* ===== TOMBOL PESAN ===== */
        .cta-wrap { padding: 0 1.5rem 1.25rem; }
        .btn-cta {
            display: block;
            text-align: center;
            background: #4a5a35;
            color: #fff;
            padding: 0.85rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: background 0.15s ease;
        }
        .btn-cta.disabled {
            background: #c7c4bb;
            color: #8a877e;
            pointer-events: none;
        }

        /* ===== GRID MENU ===== */
        .menu-section { padding: 0 1.5rem 1.5rem; }
        .menu-heading {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.9rem;
        }
        .menu-card {
            position: relative;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            cursor: pointer;
            user-select: none;
            transition: transform 0.1s ease;
        }
        .menu-card:active { transform: scale(0.97); }
        .menu-card-remove {
            position: absolute;
            top: 0;
            right: 0;
            width: 42px;
            height: 42px;
            padding: 8px;
            background: transparent;
            border: none;
            display: none; /* dimunculin JS pas qty > 0, jadi flex */
            align-items: flex-start;
            justify-content: flex-end;
            z-index: 5;
            cursor: pointer;
            touch-action: manipulation; /* biar tap langsung diproses, nggak nunggu delay double-tap-zoom */
        }
        .menu-card-remove-icon {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(20, 20, 15, 0.68);
            color: #fff;
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .menu-qty-badge {
            position: absolute;
            top: 6px;
            left: 6px;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 11px;
            background: #4a5a35;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            align-items: center;
            justify-content: center;
            display: none; /* dimunculin JS pas qty > 0 */
            z-index: 2;
        }
        .menu-photo {
            aspect-ratio: 1 / 1;
            background: #dcdad4;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a8a59c;
            font-size: 1.6rem;
            overflow: hidden;
        }
        .menu-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .menu-card-body { padding: 0.6rem 0.7rem 0.75rem; }
        .menu-card-name {
            font-size: 0.85rem;
            font-weight: 700;
        }
        .menu-card-desc {
            font-size: 0.7rem;
            color: #8a877e;
            margin: 0.15rem 0 0.35rem;
        }
        .menu-card-price {
            font-size: 0.8rem;
            font-weight: 700;
            color: #4a5a35;
        }

        .info-note {
            margin: 0 1.5rem 1.5rem;
            background: #fff;
            border-radius: 12px;
            padding: 0.9rem 1rem;
            font-size: 0.78rem;
            color: #6b6860;
            text-align: center;
            line-height: 1.5;
        }

        /* ===== BOTTOM TAB BAR (floating layer di atas konten) ===== */
        .bottom-nav {
            position: fixed;
            bottom: 16px;
            left: 16px;
            right: 16px;
            z-index: 10;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            display: flex;
        }
        .nav-item {
            flex: 1;
            text-align: center;
            padding: 0.7rem 0;
            text-decoration: none;
            color: #9a978d;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .nav-item svg { display: block; margin: 0 auto 0.2rem; }
        .nav-item.active { color: #4a5a35; }
    </style>
</head>
<body>
    <div class="hero">
        <img class="hero-img" src="assets/img/banner.jpg" alt="<?= htmlspecialchars($namaUsaha) ?>">
    </div>

    <div class="content-sheet">
        <div class="logo-wrap">
            <div class="logo-circle">M&amp;TA</div>
        </div>

        <div class="business-info">
            <div class="business-name"><?= htmlspecialchars($namaUsaha) ?></div>
            <div class="business-address">
                <a href="<?= htmlspecialchars($linkMaps) ?>" target="_blank" style="color:inherit;text-decoration:underline;">
                    <?= htmlspecialchars($alamatUsaha) ?>
                </a>
            </div>
        </div>

        <div class="cta-wrap">
            <a href="checkout.php" id="btnPesan" class="btn-cta disabled">Pilih menu dulu</a>
        </div>

        <div class="menu-section">
            <div class="menu-heading">Menu</div>
            <div class="menu-grid">
                <?php foreach ($products as $p): ?>
                    <div class="menu-card" data-id="<?= $p['id'] ?>" data-harga="<?= $p['harga'] ?>">
                        <button type="button" class="menu-card-remove" data-id="<?= $p['id'] ?>"><span class="menu-card-remove-icon">&times;</span></button>
                        <div class="menu-qty-badge">0</div>
                        <div class="menu-photo">
                            <?php if (!empty($p['foto'])): ?>
                                <img src="assets/img/<?= htmlspecialchars($p['foto']) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                            <?php else: ?>
                                🍢
                            <?php endif; ?>
                        </div>
                        <div class="menu-card-body">
                            <div class="menu-card-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
                            <div class="menu-card-desc"><?= htmlspecialchars($p['deskripsi']) ?></div>
                            <div class="menu-card-price">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="info-note">
            Pesanan diambil sendiri di lokasi &mdash; bukan diantar.<br>
            Setelah pesan, kamu akan dapat info kapan pesanan siap diambil.
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="index.php" class="nav-item active">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Home
        </a>
        <a href="riwayat.php" class="nav-item">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Pesanan
        </a>
    </nav>

    <script>
        // Set jarak .content-sheet biar pas sama tinggi ASLI foto banner
        // (bukan tinggi yang di-hardcode), jadi banner selalu full size utuh.
        (function () {
            const heroImg = document.querySelector('.hero-img');
            const sheet = document.querySelector('.content-sheet');
            const OVERLAP = 22; // dikit nutup ke hero biar sudut bulatnya keliatan nempel

            function sesuaikanJarak() {
                const tinggiHero = document.querySelector('.hero').offsetHeight;
                sheet.style.marginTop = Math.max(tinggiHero - OVERLAP, 0) + 'px';
            }

            if (heroImg.complete && heroImg.naturalWidth > 0) {
                sesuaikanJarak();
            } else {
                heroImg.addEventListener('load', sesuaikanJarak);
                heroImg.addEventListener('error', sesuaikanJarak); // tetap jalan walau foto belum ada
            }
            window.addEventListener('resize', sesuaikanJarak);
        })();
    </script>

    <script>
        // Keranjang disimpan di localStorage: { "1": 2, "3": 1 } artinya
        // product_id 1 sebanyak 2, product_id 3 sebanyak 1.
        const CART_KEY = 'keranjang_pesanan';

        function getCart() {
            return JSON.parse(localStorage.getItem(CART_KEY) || '{}');
        }
        function saveCart(cart) {
            localStorage.setItem(CART_KEY, JSON.stringify(cart));
        }

        function tambahItem(id) {
            const cart = getCart();
            cart[id] = (cart[id] || 0) + 1;
            saveCart(cart);
            renderCart();
        }

        function kurangiItem(id) {
            const cart = getCart();
            if (!cart[id]) return;
            cart[id] -= 1;
            if (cart[id] <= 0) delete cart[id];
            saveCart(cart);
            renderCart();
        }

        // Klik tombol X: pasang listener LANGSUNG ke tiap tombol (bukan delegation),
        // biar nggak gantung sama closest()/bubbling yang kadang bermasalah.
        document.querySelectorAll('.menu-card-remove').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                kurangiItem(this.dataset.id);
            });
        });

        // Klik card (selain tombol X) buat nambah item
        document.querySelectorAll('.menu-card').forEach(function (card) {
            card.addEventListener('click', function () {
                tambahItem(this.dataset.id);
            });
        });

        function renderCart() {
            const cart = getCart();
            let totalItem = 0;
            let totalHarga = 0;

            document.querySelectorAll('.menu-card').forEach(card => {
                const id = card.dataset.id;
                const harga = parseInt(card.dataset.harga);
                const qty = cart[id] || 0;
                const badge = card.querySelector('.menu-qty-badge');
                const removeBtn = card.querySelector('.menu-card-remove');

                if (qty > 0) {
                    badge.textContent = qty;
                    badge.style.display = 'flex';
                    removeBtn.style.display = 'flex';
                    totalItem += qty;
                    totalHarga += qty * harga;
                } else {
                    badge.style.display = 'none';
                    removeBtn.style.display = 'none';
                }
            });

            const btn = document.getElementById('btnPesan');
            if (totalItem > 0) {
                btn.textContent = `Pesan Sekarang · Rp${totalHarga.toLocaleString('id-ID')} (${totalItem} item)`;
                btn.classList.remove('disabled');
            } else {
                btn.textContent = 'Pilih menu dulu';
                btn.classList.add('disabled');
            }
        }

        renderCart();
    </script>
</body>
</html>