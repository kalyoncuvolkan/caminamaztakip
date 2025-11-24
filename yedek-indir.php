<?php
require_once 'config/auth.php';
checkAuth();

$dosya = $_GET['file'] ?? '';

// Güvenlik kontrolü - sadece SQL dosyaları
if (empty($dosya) || !preg_match('/^cami_yedek_[\d\-_]+\.sql$/', $dosya)) {
    die('Geçersiz dosya adı!');
}

$dosya_yolu = __DIR__ . '/backup/' . $dosya;

// Dosya var mı kontrol et
if (!file_exists($dosya_yolu)) {
    die('Dosya bulunamadı!');
}

// Dosya boyutu kontrol et
$dosya_boyutu = filesize($dosya_yolu);

// HTTP headers
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $dosya . '"');
header('Content-Length: ' . $dosya_boyutu);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Büyük dosyalar için chunk'lar halinde oku
$handle = fopen($dosya_yolu, 'rb');
if ($handle) {
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
    exit;
} else {
    die('Dosya okunamadı!');
}
