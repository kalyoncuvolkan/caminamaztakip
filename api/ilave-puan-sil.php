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
    $ilave_puan_id = $_POST['ilave_puan_id'] ?? 0;
    $nedeni = $_POST['nedeni'] ?? '';

    // İlave puan kaydını getir
    $stmt = $pdo->prepare("SELECT * FROM ilave_puanlar WHERE id = ?");
    $stmt->execute([$ilave_puan_id]);
    $ilave_puan = $stmt->fetch();

    if(!$ilave_puan) {
        echo json_encode(['success' => false, 'message' => 'İlave puan kaydı bulunamadı!']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Geçmişe kaydet - Tablo yoksa oluştur
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ilave_puan_silme_gecmisi` (
            `id` int NOT NULL AUTO_INCREMENT,
            `ogrenci_id` int NOT NULL,
            `puan` int NOT NULL,
            `aciklama` text COLLATE utf8mb4_turkish_ci,
            `veren_kullanici` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
            `tarih` date NOT NULL,
            `silme_nedeni` text COLLATE utf8mb4_turkish_ci,
            `silen_kullanici` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
            `silme_zamani` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_ogrenci_id` (`ogrenci_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $stmt = $pdo->prepare("INSERT INTO ilave_puan_silme_gecmisi
            (ogrenci_id, puan, aciklama, veren_kullanici, tarih, silme_nedeni, silen_kullanici)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $ilave_puan['ogrenci_id'],
            $ilave_puan['puan'],
            $ilave_puan['aciklama'],
            $ilave_puan['veren_kullanici'],
            $ilave_puan['tarih'],
            $nedeni,
            getLoggedInUser()
        ]);

        // İlave puanı sil
        $stmt = $pdo->prepare("DELETE FROM ilave_puanlar WHERE id = ?");
        $stmt->execute([$ilave_puan_id]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'İlave puan silindi ve geçmişe kaydedildi.']);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek!']);
}
