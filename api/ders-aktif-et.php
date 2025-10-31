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
    $nedeni = $_POST['nedeni'] ?? '';

    // Öğrenci ders kaydını getir
    $stmt = $pdo->prepare("
        SELECT od.*, d.puan, d.ders_adi
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

    if($ders['durum'] != 'Tamamlandi') {
        echo json_encode(['success' => false, 'message' => 'Bu ders zaten beklemede durumda!']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Dersi tekrar aktif et (beklemede yap)
        $stmt = $pdo->prepare("
            UPDATE ogrenci_dersler
            SET durum = 'Beklemede',
                tamamlanma_tarihi = NULL,
                verme_tarihi = NULL,
                onceki_puan = ?,
                aktif_edilme_sayisi = aktif_edilme_sayisi + 1,
                son_aktif_edilme = NOW(),
                puan_verildi = 0,
                notlar = CONCAT(COALESCE(notlar, ''), '\n[', NOW(), '] Tekrar aktif edildi: ', ?)
            WHERE id = ?
        ");
        $stmt->execute([$ders['puan'], $nedeni, $ogrenci_ders_id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Ders tekrar aktif edildi! Öğrenci dersi tekrar verebilir.'
        ]);

    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek!']);
}
