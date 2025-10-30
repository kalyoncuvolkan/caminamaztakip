<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Öğrenci login kontrolü
if(!isset($_SESSION['ogrenci_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı!']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mesaj_id = $_POST['mesaj_id'] ?? 0;
    $ogrenci_id = $_SESSION['ogrenci_id'];

    // Mesajın bu öğrenciye ait olduğunu kontrol et
    $check = $pdo->prepare("SELECT id FROM ogrenci_mesajlari WHERE id = ? AND ogrenci_id = ?");
    $check->execute([$mesaj_id, $ogrenci_id]);

    if(!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Mesaj bulunamadı!']);
        exit;
    }

    // Mesajı okundu olarak işaretle
    $stmt = $pdo->prepare("UPDATE ogrenci_mesajlari SET okundu = 1, okunma_zamani = NOW() WHERE id = ?");
    $stmt->execute([$mesaj_id]);

    echo json_encode(['success' => true, 'message' => 'Mesaj okundu olarak işaretlendi.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek!']);
}
