<?php
require_once '../config/auth.php';
checkAuth();
require_once '../config/db.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ogrenci_id = $_POST['ogrenci_id'] ?? 0;

    // Öğrenci bilgilerini al
    $ogr = $pdo->prepare("SELECT ad_soyad FROM ogrenciler WHERE id = ?");
    $ogr->execute([$ogrenci_id]);
    $ogrenci = $ogr->fetch();

    if(!$ogrenci) {
        echo json_encode(['success' => false, 'message' => 'Öğrenci bulunamadı!']);
        exit;
    }

    // Öğrenci kullanıcısını kontrol et
    $kullanici = $pdo->prepare("SELECT id, kullanici_adi FROM ogrenci_kullanicilar WHERE ogrenci_id = ?");
    $kullanici->execute([$ogrenci_id]);
    $kullanici_bilgi = $kullanici->fetch();

    if(!$kullanici_bilgi) {
        echo json_encode(['success' => false, 'message' => 'Öğrenci kullanıcısı bulunamadı!']);
        exit;
    }

    // Yeni rastgele şifre oluştur
    function generateRandomPassword($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }

    $yeni_sifre = generateRandomPassword();
    $sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);

    // Şifreyi güncelle
    $update = $pdo->prepare("UPDATE ogrenci_kullanicilar SET parola_hash = ? WHERE ogrenci_id = ?");
    $update->execute([$sifre_hash, $ogrenci_id]);

    echo json_encode([
        'success' => true,
        'ad_soyad' => $ogrenci['ad_soyad'],
        'kullanici_adi' => $kullanici_bilgi['kullanici_adi'],
        'yeni_sifre' => $yeni_sifre
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek!']);
}
