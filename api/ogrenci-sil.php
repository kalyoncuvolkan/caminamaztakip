<?php
require_once '../config/auth.php';
checkAuth();
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$ogrenci_id = $_POST['ogrenci_id'] ?? 0;
$tamamen_sil = $_POST['tamamen_sil'] ?? false;

if(!$ogrenci_id) {
    echo json_encode(['success' => false, 'message' => 'Öğrenci ID gerekli']);
    exit;
}

try {
    if($tamamen_sil) {
        // Tamamen sil (tüm ilişkili kayıtlar da silinecek - CASCADE)
        $stmt = $pdo->prepare("DELETE FROM ogrenciler WHERE id = ?");
        $stmt->execute([$ogrenci_id]);
        echo json_encode(['success' => true, 'message' => 'Öğrenci tamamen silindi']);
    } else {
        // Soft delete - pasif yap
        $stmt = $pdo->prepare("UPDATE ogrenciler SET aktif = 0, silinme_tarihi = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$ogrenci_id]);
        echo json_encode(['success' => true, 'message' => 'Öğrenci pasif duruma getirildi']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>