-- =========================================================
-- Migrasi: Tambah kolom metode_bayar ke tabel orders
-- Jalankan ini di phpMyAdmin (tab SQL) kalau tabel `orders`
-- sudah ada isinya. Kalau baru mulai dari nol, import ulang
-- schema.sql yang sudah diupdate aja, nggak perlu file ini.
-- =========================================================

USE umkm_mendoan;

ALTER TABLE orders
    ADD COLUMN metode_bayar ENUM('cod', 'qris') NOT NULL DEFAULT 'cod' AFTER tanggal_ambil;
