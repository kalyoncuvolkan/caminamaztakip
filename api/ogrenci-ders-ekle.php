<?php
session_start();
require_once '../config/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı!']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ogrenci_id = $_POST['ogrenci_id'] ?? 0;
    $ders_id = $_POST['ders_id'] ?? 0;

    // Öğrenci kontrolü
    $ogrenci = $pdo->prepare("SELECT * FROM ogrenciler WHERE id = ?");
    $ogrenci->execute([$ogrenci_id]);
    if(!$ogrenci->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Öğrenci bulunamadı!']);
        exit;
    }

    // Ders kontrolü
    $ders = $pdo->prepare("SELECT * FROM dersler WHERE id = ?");
    $ders->execute([$ders_id]);
    if(!$ders->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ders bulunamadı!']);
        exit;
    }

    // Zaten ekli mi kontrol et
    $check = $pdo->prepare("SELECT id FROM ogrenci_dersler WHERE ogrenci_id = ? AND ders_id = ?");
    $check->execute([$ogrenci_id, $ders_id]);
    if($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu ders zaten öğrenciye ekli!']);
        exit;
    }

    try {
        // Dersi öğrenciye ekle
        $stmt = $pdo->prepare("INSERT INTO ogrenci_dersler (ogrenci_id, ders_id, durum) VALUES (?, ?, 'Beklemede')");
        $stmt->execute([$ogrenci_id, $ders_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Ders başarıyla öğrenciye eklendi!'
        ]);

    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek!']);
}
