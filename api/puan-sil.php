<?php
require_once '../config/auth.php';
checkAuth();
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$kayit_id = $_POST['kayit_id'] ?? 0;
$nedeni = $_POST['nedeni'] ?? '';

if(!$kayit_id) {
    echo json_encode(['success' => false, 'message' => 'Kayıt ID gerekli']);
    exit;
}

try {
    // Önce kayıt bilgilerini al
    $stmt = $pdo->prepare("SELECT * FROM namaz_kayitlari WHERE id = ?");
    $stmt->execute([$kayit_id]);
    $kayit = $stmt->fetch();

    if(!$kayit) {
        echo json_encode(['success' => false, 'message' => 'Kayıt bulunamadı']);
        exit;
    }

    // Silme geçmişine kaydet
    $log_stmt = $pdo->prepare("
        INSERT INTO puan_silme_gecmisi
        (ogrenci_id, namaz_kayit_id, namaz_vakti, kiminle_geldi, tarih, silme_nedeni, silen_kullanici)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $log_stmt->execute([
        $kayit['ogrenci_id'],
        $kayit['id'],
        $kayit['namaz_vakti'],
        $kayit['kiminle_geldi'],
        $kayit['tarih'],
        $nedeni,
        getLoggedInUser()
    ]);

    // Kaydı sil
    $delete_stmt = $pdo->prepare("DELETE FROM namaz_kayitlari WHERE id = ?");
    $delete_stmt->execute([$kayit_id]);

    echo json_encode(['success' => true, 'message' => 'Puan silindi ve geçmişe kaydedildi']);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>