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

    if(empty($nedeni)) {
        echo json_encode(['success' => false, 'message' => 'Lütfen silme nedenini belirtin!']);
        exit;
    }

    // Ders kaydını getir
    $stmt = $pdo->prepare("
        SELECT od.*, d.ders_adi, d.puan, dk.kategori_adi
        FROM ogrenci_dersler od
        JOIN dersler d ON od.ders_id = d.id
        JOIN ders_kategorileri dk ON d.kategori_id = dk.id
        WHERE od.id = ?
    ");
    $stmt->execute([$ogrenci_ders_id]);
    $ders = $stmt->fetch();

    if(!$ders) {
        echo json_encode(['success' => false, 'message' => 'Ders kaydı bulunamadı!']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Geçmişe kaydet
        $stmt = $pdo->prepare("
            INSERT INTO ogrenci_ders_silme_gecmisi
            (ogrenci_id, ders_id, ders_adi, kategori_adi, puan, durum, verme_tarihi, atama_tarihi, silme_nedeni, silen_kullanici)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $ders['ogrenci_id'],
            $ders['ders_id'],
            $ders['ders_adi'],
            $ders['kategori_adi'],
            $ders['puan'],
            $ders['durum'],
            $ders['verme_tarihi'],
            $ders['atama_tarihi'],
            $nedeni,
            getLoggedInUser()
        ]);

        // Dersi sil
        $stmt = $pdo->prepare("DELETE FROM ogrenci_dersler WHERE id = ?");
        $stmt->execute([$ogrenci_ders_id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Ders öğrenciden silindi ve geçmişe kaydedildi.'
        ]);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek!']);
}
