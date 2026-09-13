<?php
/**
 * user/riwayat.php
 * Halaman "Pesanan Saya" — baca daftar order_code dari localStorage browser,
 * lalu fetch status tiap pesanan ke cek_status.php dan tampilkan sebagai list.
 *
 * Nggak butuh login/akun karena identifikasi murni dari localStorage device customer.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Dapur Mama Ardan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #e9e7e2;
            color: #2c2a26;
            padding: 1.25rem 1.25rem 100px;
        }
        header { margin-bottom: 1.25rem; }
        header a { font-size: 0.85rem; color: #4a5a35; text-decoration: none; }
        h1 { font-size: 1.3rem; margin-top: 0.5rem; }

        .order-item {
            background: #fff;
            border-radius: 12px;
            padding: 1rem 1.1rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.3rem;
        }
        .order-code { font-weight: 700; font-size: 0.95rem; }
        .order-time { font-size: 0.75rem; color: #888; }
        .order-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.4rem;
        }
        .total { font-size: 0.85rem; color: #555; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .badge-pending { background: #fff4e0; color: #9a6b00; }
        .badge-dikonfirmasi { background: #e3f0ff; color: #0b5cad; }
        .badge-siap_diambil { background: #e6f7e9; color: #1f8a3d; }
        .badge-selesai { background: #ececec; color: #555; }
        .badge-dibatalkan { background: #fdecea; color: #b3261e; }

        .empty {
            text-align: center;
            color: #888;
            padding: 3rem 1rem;
        }
        .empty a {
            display: inline-block;
            margin-top: 1rem;
            background: #4a5a35;
            color: #fff;
            padding: 0.6rem 1.4rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .loading { text-align: center; color: #999; padding: 2rem; font-size: 0.9rem; }

        /* ===== BOTTOM TAB BAR (floating, sama kayak index.php) ===== */
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
    <header>
        <a href="index.php">&larr; Beranda</a>
        <h1>Pesanan Saya</h1>
    </header>

    <div id="content" class="loading">Memuat...</div>

    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Home
        </a>
        <a href="riwayat.php" class="nav-item active">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Pesanan
        </a>
    </nav>

    <script>
        const STATUS_LABEL_ID = {
            pending: 'Menunggu Konfirmasi',
            dikonfirmasi: 'Dikonfirmasi',
            siap_diambil: 'Siap Diambil',
            selesai: 'Selesai',
            dibatalkan: 'Dibatalkan',
        };

        function formatTanggal(iso) {
            const d = new Date(iso.replace(' ', 'T'));
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) +
                   ', ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }

        async function muatRiwayat() {
            const content = document.getElementById('content');
            const daftarKode = JSON.parse(localStorage.getItem('pesanan_saya') || '[]');

            if (daftarKode.length === 0) {
                content.innerHTML = `
                    <div class="empty">
                        Belum ada pesanan di device ini.
                        <br><a href="index.php">Buat Pesanan</a>
                    </div>`;
                return;
            }

            try {
                const res = await fetch('cek_status.php?codes=' + encodeURIComponent(daftarKode.join(',')));
                const orders = await res.json();

                if (orders.length === 0) {
                    content.innerHTML = `<div class="empty">Data pesanan tidak ditemukan.</div>`;
                    return;
                }

                content.innerHTML = orders.map(o => `
                    <a class="order-item" href="status.php?code=${encodeURIComponent(o.order_code)}">
                        <div class="order-top">
                            <span class="order-code">${o.order_code}</span>
                            <span class="badge badge-${o.status}">${o.status_label}</span>
                        </div>
                        <div class="order-time">${formatTanggal(o.created_at)}</div>
                        <div class="order-bottom">
                            <span class="total">${o.total_formatted}</span>
                        </div>
                    </a>
                `).join('');
            } catch (err) {
                content.innerHTML = `<div class="empty">Gagal memuat data. Coba refresh halaman.</div>`;
            }
        }

        muatRiwayat();
    </script>
</body>
</html>
