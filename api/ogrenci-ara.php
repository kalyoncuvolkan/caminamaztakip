<?php
require_once '../config/auth.php';
checkAuth();
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$arama = $_GET['q'] ?? '';

if(strlen($arama) < 2) {
    echo json_encode(['ogrenciler' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, ad_soyad, baba_adi, anne_adi, yas 
    FROM ogrenciler 
    WHERE ad_soyad LIKE ? 
    ORDER BY ad_soyad 
    LIMIT 10
");
$stmt->execute(['%' . $arama . '%']);
$ogrenciler = $stmt->fetchAll();

// Yaş hesapla
foreach($ogrenciler as &$ogrenci) {
    $stmt2 = $pdo->prepare("SELECT dogum_tarihi FROM ogrenciler WHERE id = ?");
    $stmt2->execute([$ogrenci['id']]);
    $dogum = $stmt2->fetch();
    $ogrenci['yas'] = yasHesapla($dogum['dogum_tarihi']);
}

echo json_encode(['ogrenciler' => $ogrenciler], JSON_UNESCAPED_UNICODE);
?>