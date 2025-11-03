<?php
require_once '../config/auth.php';
checkAuth();
require_once '../config/db.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sertifika_id = $_POST['sertifika_id'] ?? 0;

    // Sertifika bilgilerini al
    $sertifika = $pdo->prepare("SELECT s.*, o.ad_soyad FROM sertifikalar s JOIN ogrenciler o ON s.ogrenci_id = o.id WHERE s.id = ?");
    $sertifika->execute([$sertifika_id]);
    $sertifika_bilgi = $sertifika->fetch();

    if(!$sertifika_bilgi) {
        echo json_encode(['success' => false, 'message' => 'Sertifika bulunamadı!']);
        exit;
    }

    // Sertifikayı sil
    $delete = $pdo->prepare("DELETE FROM sertifikalar WHERE id = ?");
    $delete->execute([$sertifika_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Sertifika başarıyla silindi!',
        'ogrenci' => $sertifika_bilgi['ad_soyad'],
        'baslik' => $sertifika_bilgi['baslik']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek!']);
}
