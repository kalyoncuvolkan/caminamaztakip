<?php
require_once 'config/db.php';

try {
    // Kullanıcılar tablosunu oluştur
    $sql = "CREATE TABLE IF NOT EXISTS kullanicilar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_adi VARCHAR(50) UNIQUE NOT NULL,
        parola_hash VARCHAR(255) NOT NULL,
        aktif TINYINT(1) DEFAULT 1,
        son_giris TIMESTAMP NULL,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci";
    
    $pdo->exec($sql);
    echo "Kullanıcılar tablosu oluşturuldu.\n";
    
    // mehmetuzun kullanıcısını oluştur
    $kullanici_adi = 'mehmetuzun';
    $parola = 'CamiAdmin2024!@#$'; // Güçlü parola
    $parola_hash = password_hash($parola, PASSWORD_DEFAULT);
    
    // Önce mevcut kullanıcıyı kontrol et
    $check_stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ?");
    $check_stmt->execute([$kullanici_adi]);
    
    if($check_stmt->fetch()) {
        echo "mehmetuzun kullanıcısı zaten mevcut.\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO kullanicilar (kullanici_adi, parola_hash) VALUES (?, ?)");
        $stmt->execute([$kullanici_adi, $parola_hash]);
        echo "mehmetuzun kullanıcısı oluşturuldu.\n";
        echo "Parola: $parola\n";
    }
    
    echo "\nLogin sistemi hazır!\n";
    echo "Kullanıcı Adı: mehmetuzun\n";
    echo "Parola: CamiAdmin2024!@#$\n";
    
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>