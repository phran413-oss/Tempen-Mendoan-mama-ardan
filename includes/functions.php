<?php
/**
 * includes/functions.php
 * Kumpulan fungsi umum yang dipakai di web admin maupun web user.
 */

/**
 * Generate kode unik untuk order.
 * Format: ORD-YYYYMMDD-XXXX (4 karakter acak huruf besar & angka)
 * Contoh: ORD-20260819-A1B2
 */
function generateOrderCode() {
    $tanggal = date('Ymd');
    $acak = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    return "ORD-{$tanggal}-{$acak}";
}

/**
 * Format angka jadi format Rupiah.
 * Contoh: 14000 -> "Rp 14.000"
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Ubah status (dari database) jadi teks yang enak dibaca customer/admin.
 */
function labelStatus($status) {
    $label = [
        'pending'       => 'Menunggu Konfirmasi',
        'dikonfirmasi'  => 'Pesanan Dikonfirmasi',
        'siap_diambil'  => 'Pesanan Jadi, Harap Diambil di Lokasi',
        'selesai'       => 'Selesai',
        'dibatalkan'    => 'Dibatalkan',
    ];
    return $label[$status] ?? $status;
}

/**
 * Bersihkan input dari user sebelum diproses/disimpan.
 * Mencegah tag HTML/script nyelip (XSS) dan spasi berlebih.
 */
function bersihkanInput($data) {
    $data = trim($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Ubah nomor HP format lokal Indonesia jadi format internasional
 * yang dibutuhin link WhatsApp (wa.me).
 * Contoh: "081281151054" atau "0812-8115-1054" -> "6281281151054"
 */
function formatNomorWa($nomor) {
    $nomor = preg_replace('/[^0-9]/', '', $nomor); // buang semua karakter selain angka
    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }
    return $nomor;
}

/**
 * Redirect helper biar nggak nulis header() panjang-panjang berulang.
 */
function redirect($lokasi) {
    header("Location: {$lokasi}");
    exit;
}
