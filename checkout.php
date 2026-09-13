<?php
/**
 * user/checkout.php
 * Halaman konfirmasi pesanan. Item pesanan diambil dari localStorage
 * (diisi dari index.php pas customer tap-tap menu). Customer isi nama,
 * no HP, catatan, lalu submit ke simpan_pesanan.php (sama seperti sebelumnya).
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ambil semua produk aktif buat jadi "kamus" harga & nama di sisi JS
// (localStorage cuma nyimpen id & qty, bukan nama/harga)
$products = $koneksi->query('SELECT id, nama_produk, deskripsi, harga FROM products WHERE aktif = 1')
                     ->fetch_all(MYSQLI_ASSOC);
$productsById = array_column($products, null, 'id');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan - Dapur Mama Ardan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #e9e7e2;
            color: #2c2a26;
            padding: 1.25rem 1.25rem 2rem;
        }
        header { margin-bottom: 1.1rem; }
        header a { font-size: 0.85rem; color: #4a5a35; text-decoration: none; font-weight: 600; }
        h1 { font-size: 1.25rem; margin-top: 0.5rem; }

        .card {
            background: #fff;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .card-title {
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 0.7rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.4rem 0;
            font-size: 0.88rem;
        }
        .item-name { font-weight: 600; }
        .item-sub { font-size: 0.72rem; color: #8a877e; }
        .item-price { color: #4a5a35; font-weight: 700; white-space: nowrap; }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 0.6rem;
            padding-top: 0.6rem;
            border-top: 1px solid #eee;
        }

        .field { margin-bottom: 1rem; }
        .field:last-child { margin-bottom: 0; }
        label {
            display: block;
            font-size: 0.82rem;
            color: #666;
            margin-bottom: 0.3rem;
            font-weight: 600;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 0.65rem 0.8rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: #4a5a35;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-submit:disabled { background: #c7c4bb; cursor: not-allowed; }

        .payment-options {
            display: flex;
            gap: 0.6rem;
        }
        .payment-option {
            flex: 1;
            border: 1.5px solid #ddd;
            border-radius: 10px;
            padding: 0.65rem 0.5rem;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: #555;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        .payment-option input { accent-color: #4a5a35; }
        .payment-option.selected {
            border-color: #4a5a35;
            background: #eef2ea;
            color: #2c2a26;
        }
        #qrisBox {
            text-align: center;
            margin-top: 0.9rem;
            padding-top: 0.9rem;
            border-top: 1px solid #eee;
        }
        .qris-img {
            max-width: 220px;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #eee;
        }
        .qris-note {
            font-size: 0.75rem;
            color: #8a877e;
            margin-top: 0.5rem;
            line-height: 1.4;
        }

        .upload-field {
            margin-top: 0.9rem;
            text-align: left;
        }
        .upload-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #4a5a35;
            border: 2px solid #4a5a35;
            border-radius: 12px;
            padding: 1.4rem 1rem;
            cursor: pointer;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 700;
        }
        .upload-hint {
            font-size: 0.72rem;
            color: #a8a59c;
            text-align: center;
            margin-top: 0.4rem;
        }
        .upload-preview {
            display: none;
            margin-top: 0.7rem;
            align-items: center;
            gap: 0.7rem;
            background: #f5f4f1;
            border-radius: 10px;
            padding: 0.6rem 0.7rem;
        }
        .upload-preview img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .upload-preview-name {
            font-size: 0.78rem;
            color: #2c2a26;
            font-weight: 600;
            flex: 1;
            word-break: break-all;
        }
        .upload-preview-ganti {
            background: none;
            border: none;
            color: #4a5a35;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
            white-space: nowrap;
        }
        .upload-error {
            color: #c0392b;
            font-size: 0.78rem;
            margin-top: 0.4rem;
            display: none;
        }
        .upload-error.show { display: block; }

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
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php">&larr; Kembali &amp; ubah pesanan</a>
        <h1>Konfirmasi Pesanan</h1>
    </header>

    <div id="content">
        <!-- Diisi oleh JS di bawah, berdasarkan isi keranjang di localStorage -->
    </div>

    <script>
        const PRODUCTS = <?= json_encode($productsById) ?>;
        const CART_KEY = 'keranjang_pesanan';

        function formatRupiah(angka) {
            return 'Rp ' + angka.toLocaleString('id-ID');
        }

        function muatHalaman() {
            const cart = JSON.parse(localStorage.getItem(CART_KEY) || '{}');
            const ids = Object.keys(cart).filter(id => cart[id] > 0 && PRODUCTS[id]);
            const content = document.getElementById('content');

            if (ids.length === 0) {
                content.innerHTML = `
                    <div class="empty">
                        Belum ada menu yang dipilih.
                        <br><a href="index.php">Pilih Menu</a>
                    </div>`;
                return;
            }

            let totalHarga = 0;
            const itemsHtml = ids.map(id => {
                const p = PRODUCTS[id];
                const qty = cart[id];
                const subtotal = qty * parseInt(p.harga);
                totalHarga += subtotal;
                return `
                    <div class="item-row">
                        <div>
                            <div class="item-name">${qty}x ${p.nama_produk}</div>
                            <div class="item-sub">${p.deskripsi}</div>
                        </div>
                        <div class="item-price">${formatRupiah(subtotal)}</div>
                    </div>`;
            }).join('');

            content.innerHTML = `
                <div class="card">
                    <div class="card-title">Pesanan Kamu</div>
                    ${itemsHtml}
                    <div class="total-row">
                        <span>Total</span>
                        <span>${formatRupiah(totalHarga)}</span>
                    </div>
                </div>

                <form method="POST" action="simpan_pesanan.php" id="checkoutForm" enctype="multipart/form-data">
                    <div class="card">
                        <div class="card-title">Data Pemesan</div>
                        <div class="field">
                            <label for="nama_pemesan">Nama Kamu</label>
                            <input type="text" id="nama_pemesan" name="nama_pemesan" required placeholder="Contoh: Rina">
                        </div>
                        <div class="field">
                            <label for="no_hp_pemesan">Nomor HP / WA (opsional)</label>
                            <input type="text" id="no_hp_pemesan" name="no_hp_pemesan" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="field">
                            <label for="tanggal_ambil">Rencana Tanggal Ambil</label>
                            <input type="date" id="tanggal_ambil" name="tanggal_ambil" required>
                        </div>
                        <div class="field">
                            <label for="catatan">Catatan (opsional)</label>
                            <textarea id="catatan" name="catatan" rows="2" placeholder="Contoh: sambal dipisah"></textarea>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title">Metode Pembayaran</div>
                        <div class="payment-options">
                            <label class="payment-option" id="optCod">
                                <input type="radio" name="metode_bayar" value="cod" checked>
                                Bayar di Tempat
                            </label>
                            <label class="payment-option" id="optQris">
                                <input type="radio" name="metode_bayar" value="qris">
                                QRIS
                            </label>
                        </div>
                        <div id="qrisBox" style="display:none;">
                            <img src="assets/img/qris.jpeg" alt="QRIS Dapur Mama Ardan" class="qris-img">
                            <p class="qris-note">Scan &amp; bayar sesuai total pesanan. Bawa/tunjukkan bukti bayarnya pas ambil pesanan ya.</p>

                            <div class="upload-field">
                                <label for="bukti_bayar" class="upload-btn" id="uploadBtn">
                                    <span>Bukti Pembayaran di Sini</span>
                                </label>
                                <div class="upload-hint">JPG/PNG, maks 5MB</div>
                                <input type="file" id="bukti_bayar" name="bukti_bayar" accept="image/jpeg,image/png,image/jpg" style="display:none;">

                                <div class="upload-preview" id="uploadPreview">
                                    <img id="uploadPreviewImg" src="" alt="Preview bukti bayar">
                                    <span class="upload-preview-name" id="uploadPreviewName"></span>
                                    <button type="button" class="upload-preview-ganti" id="btnGantiFoto">Ganti</button>
                                </div>

                                <div class="upload-error" id="uploadError">Bukti pembayaran wajib diupload untuk metode QRIS.</div>
                            </div>
                        </div>
                    </div>

                    <div id="hiddenQtyFields"></div>

                    <button type="submit" class="btn-submit" id="btnSubmit">Konfirmasi Pesanan</button>
                </form>
            `;

            // Toggle tampilan QR code sesuai metode bayar yang dipilih
            const radios = document.querySelectorAll('input[name="metode_bayar"]');
            const qrisBox = document.getElementById('qrisBox');
            const optCod = document.getElementById('optCod');
            const optQris = document.getElementById('optQris');
            const uploadError = document.getElementById('uploadError');
            const buktiBayarInput = document.getElementById('bukti_bayar');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadPreview = document.getElementById('uploadPreview');
            const uploadPreviewImg = document.getElementById('uploadPreviewImg');
            const uploadPreviewName = document.getElementById('uploadPreviewName');
            const btnGantiFoto = document.getElementById('btnGantiFoto');

            function perbaruiTampilanBayar() {
                const dipilih = document.querySelector('input[name="metode_bayar"]:checked').value;
                qrisBox.style.display = dipilih === 'qris' ? 'block' : 'none';
                optCod.classList.toggle('selected', dipilih === 'cod');
                optQris.classList.toggle('selected', dipilih === 'qris');
                // Sembunyikan pesan error tiap kali ganti metode
                uploadError.classList.remove('show');
            }
            radios.forEach(r => r.addEventListener('change', perbaruiTampilanBayar));
            perbaruiTampilanBayar();

            // Begitu customer pilih file: tampilin preview + nama file, sembunyiin tombol besar
            buktiBayarInput.addEventListener('change', () => {
                const file = buktiBayarInput.files[0];
                if (!file) return;

                uploadError.classList.remove('show');

                const reader = new FileReader();
                reader.onload = (e) => {
                    uploadPreviewImg.src = e.target.result;
                    uploadPreviewName.textContent = file.name;
                    uploadPreview.style.display = 'flex';
                    uploadBtn.style.display = 'none';
                };
                reader.readAsDataURL(file);
            });

            // Tombol "Ganti": reset input, balik ke tampilan tombol besar
            btnGantiFoto.addEventListener('click', () => {
                buktiBayarInput.value = '';
                uploadPreview.style.display = 'none';
                uploadBtn.style.display = 'flex';
            });

            // Validasi wajib upload bukti bayar khusus metode QRIS
            document.getElementById('checkoutForm').addEventListener('submit', (e) => {
                const dipilih = document.querySelector('input[name="metode_bayar"]:checked').value;
                if (dipilih === 'qris' && buktiBayarInput.files.length === 0) {
                    e.preventDefault();
                    uploadError.classList.add('show');
                    uploadBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            // Bikin hidden input qty[id] buat tiap item di keranjang,
            // format ini sama persis kayak yang dibaca simpan_pesanan.php
            const hiddenWrap = document.getElementById('hiddenQtyFields');
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `qty[${id}]`;
                input.value = cart[id];
                hiddenWrap.appendChild(input);
            });

            // Batas minimal tanggal ambil = hari ini, dan default-nya juga hari ini
            const inputTanggal = document.getElementById('tanggal_ambil');
            const hariIni = new Date().toISOString().split('T')[0];
            inputTanggal.min = hariIni;
            inputTanggal.value = hariIni;
        }

        muatHalaman();
    </script>
</body>
</html>