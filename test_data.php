<?php
require_once 'config/db.php';

try {
    // Test öğrencileri ekle
    $ogrenciler = [
        ['Mehmet Yılmaz', '2015-03-15', 'Ahmet Yılmaz', 'Ayşe Yılmaz', '05321234567', '05337654321'],
        ['Fatma Demir', '2014-06-20', 'Ali Demir', 'Zeynep Demir', '05421234567', '05437654321'],
        ['Mustafa Kaya', '2016-01-10', 'Hasan Kaya', 'Fatma Kaya', '05521234567', '05537654321'],
        ['Zeynep Öztürk', '2015-08-25', 'Mehmet Öztürk', 'Elif Öztürk', '05551234567', '05557654321'],
        ['Ali Can', '2014-12-05', 'Murat Can', 'Sema Can', '05361234567', '05367654321']
    ];
    
    foreach ($ogrenciler as $ogrenci) {
        $yas = yasHesapla($ogrenci[1]);
        $stmt = $pdo->prepare("INSERT INTO ogrenciler (ad_soyad, dogum_tarihi, yas, baba_adi, anne_adi, baba_telefonu, anne_telefonu) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ogrenci[0], $ogrenci[1], $yas, $ogrenci[2], $ogrenci[3], $ogrenci[4], $ogrenci[5]]);
    }
    echo "5 test öğrencisi eklendi.\n";
    
    // Test namaz kayıtları ekle
    $vakitler = ['Sabah', 'Öğlen', 'İkindi', 'Akşam', 'Yatsı'];
    $kiminle = ['Kendisi', 'Babası', 'Annesi', 'Anne-Babası'];
    
    // Son 30 gün için rastgele namaz kayıtları
    for ($gun = 30; $gun >= 0; $gun--) {
        $tarih = date('Y-m-d', strtotime("-$gun days"));
        
        // Her öğrenci için
        for ($ogrenci_id = 1; $ogrenci_id <= 5; $ogrenci_id++) {
            // Günde 1-3 vakit namaz kılmış olsun
            $vakit_sayisi = rand(1, 3);
            $secilen_vakitler = array_rand(array_flip($vakitler), $vakit_sayisi);
            if (!is_array($secilen_vakitler)) {
                $secilen_vakitler = [$secilen_vakitler];
            }
            
            foreach ($secilen_vakitler as $vakit) {
                $kiminle_geldi = $kiminle[array_rand($kiminle)];
                $stmt = $pdo->prepare("INSERT INTO namaz_kayitlari (ogrenci_id, namaz_vakti, kiminle_geldi, tarih) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([$ogrenci_id, $vakit, $kiminle_geldi, $tarih]);
            }
        }
    }
    
    echo "Test namaz kayıtları eklendi.\n";
    echo "\nSistem kullanıma hazır!\n";
    
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>