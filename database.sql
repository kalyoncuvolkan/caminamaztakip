CREATE DATABASE IF NOT EXISTS cami_namaz_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;

USE cami_namaz_takip;

CREATE TABLE ogrenciler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(100) NOT NULL,
    dogum_tarihi DATE NOT NULL,
    yas INT GENERATED ALWAYS AS (YEAR(CURDATE()) - YEAR(dogum_tarihi) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(dogum_tarihi, '%m%d'))) STORED,
    baba_adi VARCHAR(100),
    anne_adi VARCHAR(100),
    baba_telefonu VARCHAR(20),
    anne_telefonu VARCHAR(20),
    kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ad_soyad (ad_soyad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE namaz_kayitlari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT NOT NULL,
    namaz_vakti ENUM('Sabah', 'Öğlen', 'İkindi', 'Akşam', 'Yatsı') NOT NULL,
    kiminle_geldi ENUM('Kendisi', 'Babası', 'Annesi', 'Anne-Babası') NOT NULL DEFAULT 'Kendisi',
    tarih DATE NOT NULL,
    saat TIME DEFAULT CURRENT_TIME,
    kayit_zamani TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
    INDEX idx_ogrenci_tarih (ogrenci_id, tarih),
    INDEX idx_tarih (tarih)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE VIEW aylik_ozetler AS
SELECT 
    o.id as ogrenci_id,
    o.ad_soyad,
    YEAR(n.tarih) as yil,
    MONTH(n.tarih) as ay,
    SUM(CASE WHEN n.kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END) as kendisi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) as babasi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) as annesi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) as anne_babasi_sayisi,
    COUNT(*) as toplam_namaz
FROM 
    ogrenciler o
    LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id
GROUP BY 
    o.id, YEAR(n.tarih), MONTH(n.tarih);

CREATE VIEW yillik_ozetler AS
SELECT 
    o.id as ogrenci_id,
    o.ad_soyad,
    YEAR(n.tarih) as yil,
    SUM(CASE WHEN n.kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END) as kendisi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) as babasi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) as annesi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) as anne_babasi_sayisi,
    COUNT(*) as toplam_namaz
FROM 
    ogrenciler o
    LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id
GROUP BY 
    o.id, YEAR(n.tarih);