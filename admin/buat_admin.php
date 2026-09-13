<?php
/**
 * admin/buat_admin.php
 * SCRIPT SEKALI PAKAI — buat generate akun admin pertama.
 *
 * Cara pakai:
 * 1. Ubah $username, $password, $nama_lengkap, $no_wa di bawah sesuai punya kamu.
 * 2. Buka file ini lewat browser (contoh: localhost/umkm-tempe-mendoan/admin/buat_admin.php)
 * 3. Kalau sudah muncul "Admin berhasil dibuat", HAPUS FILE INI dari server.
 *    (File ini nggak butuh login, jadi bahaya kalau dibiarkan nyala terus)
 */

require_once __DIR__ . '/../config/database.php';

// --- Ganti sesuai data admin (ibu UMKM / kamu) ---
$username     = 'admin';
$password     = 'CobaLagi';   // pakai password yang kuat
$nama_lengkap = 'Ibu Pemilik UMKM';
$no_wa        = '6285695889382';        // format: kode negara tanpa + atau spasi/strip

// Hash password sebelum disimpan — JANGAN PERNAH simpan password polos di database
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $koneksi->prepare('INSERT INTO admin (username, password, nama_lengkap, no_wa) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $username, $password_hash, $nama_lengkap, $no_wa);

if ($stmt->execute()) {
    echo 'Admin berhasil dibuat. Silakan login, lalu HAPUS file buat_admin.php ini dari server.';
} else {
    echo 'Gagal membuat admin: ' . htmlspecialchars($stmt->error);
}

$stmt->close();
