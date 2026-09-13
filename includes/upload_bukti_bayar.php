<?php
/**
 * Helper untuk upload & kompres foto bukti bayar QRIS.
 * Taruh file ini di folder includes/ atau functions/, lalu require di checkout.php.
 */

/**
 * Upload dan kompres foto bukti bayar.
 *
 * @param array  $file       Elemen dari $_FILES, misal $_FILES['bukti_bayar']
 * @param int    $orderId    ID order (dipakai buat nama file unik)
 * @param string $uploadDir  Folder tujuan penyimpanan (harus writable, tanpa trailing slash)
 * @param int    $maxWidth   Lebar maksimal hasil kompresi (px)
 * @param int    $quality    Kualitas JPEG hasil kompresi (1-100)
 *
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function uploadBuktiBayar(array $file, int $orderId, string $uploadDir, int $maxWidth = 1000, int $quality = 75): array
{
    // 1. Validasi dasar upload
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'filename' => null, 'error' => 'Upload tidak valid.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'Gagal upload file (kode error: ' . $file['error'] . ').'];
    }

    // 2. Validasi ukuran file (maks 5MB sebelum dikompres)
    $maxUploadSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxUploadSize) {
        return ['success' => false, 'filename' => null, 'error' => 'Ukuran file terlalu besar (maks 5MB).'];
    }

    // 3. Validasi tipe file berdasarkan konten asli (bukan cuma nama file, biar aman)
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'filename' => null, 'error' => 'File yang diupload bukan gambar yang valid.'];
    }

    $mimeType = $imageInfo['mime'];
    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array($mimeType, $allowedTypes, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Format file harus JPG atau PNG.'];
    }

    // 4. Buat gambar sumber sesuai tipenya
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($file['tmp_name']);
            break;
        default:
            return ['success' => false, 'filename' => null, 'error' => 'Format file tidak didukung.'];
    }

    if ($sourceImage === false) {
        return ['success' => false, 'filename' => null, 'error' => 'Gagal membaca gambar.'];
    }

    // 5. Hitung dimensi baru (resize kalau lebih lebar dari $maxWidth, jangan diperbesar kalau lebih kecil)
    $origWidth = imagesx($sourceImage);
    $origHeight = imagesy($sourceImage);

    if ($origWidth > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int) round($origHeight * ($maxWidth / $origWidth));
    } else {
        $newWidth = $origWidth;
        $newHeight = $origHeight;
    }

    // 6. Buat canvas baru & resize gambar ke sana
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

    // Background putih (jaga-jaga kalau PNG-nya transparan, biar nggak jadi hitam)
    $white = imagecolorallocate($resizedImage, 255, 255, 255);
    imagefill($resizedImage, 0, 0, $white);

    imagecopyresampled(
        $resizedImage, $sourceImage,
        0, 0, 0, 0,
        $newWidth, $newHeight, $origWidth, $origHeight
    );

    // 7. Pastikan folder tujuan ada
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 8. Nama file unik: order_<id>_<timestamp>.jpg
    $filename = 'order_' . $orderId . '_' . time() . '.jpg';
    $fullPath = $uploadDir . '/' . $filename;

    // 9. Simpan sebagai JPEG dengan kualitas yang dikompres
    $saved = imagejpeg($resizedImage, $fullPath, $quality);

    // 10. Bersihkan memory
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);

    if (!$saved) {
        return ['success' => false, 'filename' => null, 'error' => 'Gagal menyimpan file hasil kompresi.'];
    }

    return ['success' => true, 'filename' => $filename, 'error' => null];
}
