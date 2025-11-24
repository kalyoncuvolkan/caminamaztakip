-- ====================================================================
-- CAMI NAMAZ TAKİP SİSTEMİ - CLOUD VERİTABANI GÜNCELLEMESİ
-- Tarih: 2025-11-24
-- Açıklama: Ön tanımlı puan şablonları için yeni tablo
-- ====================================================================

-- 1. Ön tanımlı puan şablonları tablosu
CREATE TABLE IF NOT EXISTS `puan_sablon` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `baslik` VARCHAR(200) NOT NULL COMMENT 'Şablon başlığı (örn: Güzel namaz kıldı)',
    `puan` INT NOT NULL COMMENT 'Puan miktarı (pozitif veya negatif)',
    `kategori` ENUM('Namaz', 'Ders') NOT NULL COMMENT 'Puan kategorisi',
    `aciklama` TEXT COMMENT 'Detaylı açıklama',
    `aktif` TINYINT(1) DEFAULT 1 COMMENT '1=Aktif, 0=Pasif',
    `sira` INT DEFAULT 0 COMMENT 'Görüntüleme sırası',
    `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_kategori` (`kategori`),
    INDEX `idx_aktif` (`aktif`),
    INDEX `idx_sira` (`sira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci
COMMENT='Ön tanımlı puan şablonları';

-- 2. Örnek puan şablonlarını ekle (sadece yoksa)
INSERT IGNORE INTO `puan_sablon` (`id`, `baslik`, `puan`, `kategori`, `aciklama`, `aktif`, `sira`) VALUES
(1, 'Güzel namaz kıldı', 2, 'Namaz', 'Öğrenci camide güzel ve düzgün namaz kıldı', 1, 1),
(2, 'Sünneti kıldı', 1, 'Namaz', 'Öğrenci namazın sünnetini kıldı', 1, 2),
(3, 'İmama yardım etti', 2, 'Namaz', 'Öğrenci imama yardım etti', 1, 3),
(4, 'Derse aktif katıldı', 2, 'Ders', 'Öğrenci derse aktif olarak katıldı', 1, 1),
(5, 'Ödev yaptı', 1, 'Ders', 'Öğrenci verilen ödevi yaptı', 1, 2),
(6, 'Ezber yaptı', 3, 'Ders', 'Öğrenci ezber yaptı', 1, 3),
(7, 'Camide gürültü yaptı', -2, 'Namaz', 'Öğrenci camide gürültü yaptı', 1, 10),
(8, 'Derse geç kaldı', -1, 'Ders', 'Öğrenci derse geç geldi', 1, 10);

-- 3. Başarı mesajı
SELECT
    CONCAT('✅ puan_sablon tablosu oluşturuldu ve ', COUNT(*), ' adet örnek şablon eklendi!') as sonuc
FROM puan_sablon;

-- 4. Eklenen şablonları göster
SELECT
    id,
    baslik,
    CONCAT(IF(puan > 0, '+', ''), puan) as puan,
    kategori,
    IF(aktif = 1, '✅ Aktif', '❌ Pasif') as durum
FROM puan_sablon
ORDER BY kategori, sira, baslik;
