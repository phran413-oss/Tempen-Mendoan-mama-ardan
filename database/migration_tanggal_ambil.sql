-- =========================================================
-- Migrasi: Tambah kolom tanggal_ambil ke tabel orders
-- Jalankan ini di phpMyAdmin (tab SQL) kalau tabel `orders`
-- sudah ada isinya dari testing sebelumnya.
-- Kalau database masih baru/kosong, boleh skip file ini dan
-- import ulang schema.sql yang sudah diupdate.
-- =========================================================

USE umkm_mendoan;

-- 1. Tambah kolom dulu sebagai NULL (biar nggak nabrak data lama yang belum punya nilai)
ALTER TABLE orders ADD COLUMN tanggal_ambil DATE NULL AFTER no_hp_pemesan;

-- 2. Isi data lama (kalau ada) dengan tanggal pesanan dibuat, sebagai default sementara
UPDATE orders SET tanggal_ambil = DATE(created_at) WHERE tanggal_ambil IS NULL;

-- 3. Baru kunci kolomnya jadi wajib diisi (NOT NULL) buat pesanan-pesanan baru
ALTER TABLE orders MODIFY tanggal_ambil DATE NOT NULL;
