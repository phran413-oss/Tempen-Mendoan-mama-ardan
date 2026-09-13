-- =========================================================
-- Migrasi: Tambah kolom bukti_bayar ke tabel orders
-- Jalankan ini di phpMyAdmin (tab SQL) kalau tabel `orders`
-- sudah ada isinya.
-- =========================================================

USE umkm_mendoan;

ALTER TABLE orders
    ADD COLUMN bukti_bayar VARCHAR(255) NULL AFTER metode_bayar;
