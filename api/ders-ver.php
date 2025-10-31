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
    $ogrenci_ders_id = $_POST['ogrenci_ders_id'] ?? 0;

    // Öğrenci ders kaydını getir
    $stmt = $pdo->prepare("
        SELECT od.*, d.puan
        FROM ogrenci_dersler od
        JOIN dersler d ON od.ders_id = d.id
        WHERE od.id = ?
    ");
    $stmt->execute([$ogrenci_ders_id]);
    $ders = $stmt->fetch();

    if(!$ders) {
        echo json_encode(['success' => false, 'message' => 'Ders kaydı bulunamadı!']);
        exit;
    }

    if($ders['durum'] == 'Tamamlandi') {
        echo json_encode(['success' => false, 'message' => 'Bu ders zaten tamamlanmış!']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Dersi tamamla
        $stmt = $pdo->prepare("
            UPDATE ogrenci_dersler
            SET durum = 'Tamamlandi',
                tamamlanma_tarihi = CURDATE(),
                verme_tarihi = NOW(),
                puan_verildi = 1
            WHERE id = ?
        ");
        $stmt->execute([$ogrenci_ders_id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Ders başarıyla tamamlandı olarak işaretlendi!'
        ]);

    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek!']);
}
