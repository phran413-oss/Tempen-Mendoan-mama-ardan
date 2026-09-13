-- =========================================================
-- Skema Database: UMKM Tempe Mendoan & Tahu Aci
-- Import file ini lewat phpMyAdmin (tab "Import" atau "SQL")
-- =========================================================

CREATE DATABASE IF NOT EXISTS umkm_mendoan
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE umkm_mendoan;

-- ---------------------------------------------------------
-- Tabel: admin
-- Buat login sederhana ke web admin
-- ---------------------------------------------------------
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,  -- simpan hasil password_hash(), jangan plain text
    nama_lengkap VARCHAR(100),
    no_wa VARCHAR(20),               -- nomor WA yang ditampilkan ke customer
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: products
-- Daftar menu yang bisa dipesan
-- ---------------------------------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(100) NOT NULL,
    deskripsi VARCHAR(255),
    harga INT NOT NULL,
    is_paket BOOLEAN DEFAULT FALSE,
    aktif BOOLEAN DEFAULT TRUE,      -- buat nyembunyiin produk tanpa hapus data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed data sesuai menu
INSERT INTO products (nama_produk, deskripsi, harga, is_paket) VALUES
('Mendoan', '1 porsi isi 4', 14000, FALSE),
('Tahu Aci', '1 porsi isi 8 pcs', 10000, FALSE),
('Paket A', 'Mendoan 3 + Tahu Aci 4', 15000, TRUE),
('Paket B', 'Mendoan 4 + Tahu Aci 5', 20000, TRUE);

-- ---------------------------------------------------------
-- Tabel: orders
-- Satu baris = satu transaksi pesanan
-- ---------------------------------------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(20) NOT NULL UNIQUE,  -- kode unik, misal: ORD-20260819-A1B2
    nama_pemesan VARCHAR(100) NOT NULL,
    no_hp_pemesan VARCHAR(20),               -- opsional, buat admin hubungi balik
    tanggal_ambil DATE NOT NULL,             -- kapan customer rencana ambil pesanan
    metode_bayar ENUM('cod', 'qris') NOT NULL DEFAULT 'cod', -- cod = bayar di tempat
    bukti_bayar VARCHAR(255) NULL,           -- nama file bukti transfer QRIS (kalau ada)
    catatan TEXT,                            -- catatan tambahan dari customer
    total_harga INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'dikonfirmasi', 'siap_diambil', 'selesai', 'dibatalkan')
           NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: order_items
-- Detail item per pesanan (1 order bisa banyak item)
-- ---------------------------------------------------------
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    nama_produk VARCHAR(100) NOT NULL,   -- snapshot nama, biar aman kalau produk diedit/dihapus nanti
    harga_satuan INT NOT NULL,           -- snapshot harga saat itu
    jumlah INT NOT NULL DEFAULT 1,
    subtotal INT NOT NULL,               -- harga_satuan * jumlah

    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Index tambahan buat mempercepat query yang sering dipakai
-- ---------------------------------------------------------
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
