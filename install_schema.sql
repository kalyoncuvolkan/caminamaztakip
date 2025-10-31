-- Cami Namaz Takip Programı - Veritabanı Şeması
-- Versiyon: 2.0

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- Öğrenciler Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `ogrenciler` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ad_soyad` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `dogum_tarihi` date NOT NULL,
  `yas` int DEFAULT NULL,
  `baba_adi` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `anne_adi` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `baba_telefonu` varchar(20) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `anne_telefonu` varchar(20) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `kayit_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `aktif` tinyint(1) DEFAULT '1' COMMENT '1=Aktif, 0=Pasif',
  `guncelleme_tarihi` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `silinme_tarihi` timestamp NULL DEFAULT NULL,
  `silindi` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_ad_soyad` (`ad_soyad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- Öğrenci Kullanıcıları Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `ogrenci_kullanicilar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int NOT NULL,
  `kullanici_adi` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `parola_hash` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `aktif` tinyint(1) DEFAULT '1',
  `ilk_giris` tinyint(1) DEFAULT '1',
  `son_giris` timestamp NULL DEFAULT NULL,
  `olusturma_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ogrenci_id` (`ogrenci_id`),
  UNIQUE KEY `kullanici_adi` (`kullanici_adi`),
  CONSTRAINT `ogrenci_kullanicilar_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- Yönetici Kullanıcıları Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kullanici_adi` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `parola_hash` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `aktif` tinyint(1) DEFAULT '1',
  `son_giris` timestamp NULL DEFAULT NULL,
  `olusturma_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kullanici_adi` (`kullanici_adi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- Namaz Kayıtları Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `namaz_kayitlari` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int NOT NULL,
  `namaz_vakti` enum('Sabah','Öğlen','İkindi','Akşam','Yatsı') COLLATE utf8mb4_turkish_ci NOT NULL,
  `kiminle_geldi` enum('Kendisi','Babası','Annesi','Anne-Babası') COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT 'Kendisi',
  `tarih` date NOT NULL,
  `saat` time DEFAULT (curtime()),
  `kayit_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci_tarih` (`ogrenci_id`,`tarih`),
  KEY `idx_tarih` (`tarih`),
  CONSTRAINT `namaz_kayitlari_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- Ders Kategorileri Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `ders_kategorileri` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kategori_adi` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `aciklama` text COLLATE utf8mb4_turkish_ci,
  `aktif` tinyint(1) DEFAULT '1',
  `sira` int DEFAULT '0',
  `olusturma_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_kategori_adi` (`kategori_adi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- Dersler Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `dersler` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kategori_id` int NOT NULL,
  `ders_adi` varchar(200) COLLATE utf8mb4_turkish_ci NOT NULL,
  `aciklama` text COLLATE utf8mb4_turkish_ci,
  `puan` int DEFAULT '1',
  `aktif` tinyint(1) DEFAULT '1',
  `sira` int DEFAULT '0',
  `olusturma_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kategori` (`kategori_id`),
  CONSTRAINT `dersler_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `ders_kategorileri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- Öğrenci Dersler Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `ogrenci_dersler` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int NOT NULL,
  `ders_id` int NOT NULL,
  `durum` enum('Beklemede','Tamamlandi') COLLATE utf8mb4_turkish_ci DEFAULT 'Beklemede',
  `tamamlanma_tarihi` date DEFAULT NULL,
  `verme_tarihi` datetime DEFAULT NULL COMMENT 'Dersi verdiği tarih ve saat',
  `aktif_edilme_sayisi` int DEFAULT '0' COMMENT 'Kaç kez tekrar aktif edildi',
  `onceki_puan` int DEFAULT NULL COMMENT 'Aktif etmeden önceki puan',
  `son_aktif_edilme` datetime DEFAULT NULL COMMENT 'Son aktif edilme zamanı',
  `puan_verildi` tinyint(1) DEFAULT '0',
  `notlar` text COLLATE utf8mb4_turkish_ci,
  `atama_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ogrenci_ders` (`ogrenci_id`,`ders_id`),
  KEY `ders_id` (`ders_id`),
  KEY `idx_durum` (`durum`),
  CONSTRAINT `ogrenci_dersler_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ogrenci_dersler_ibfk_2` FOREIGN KEY (`ders_id`) REFERENCES `dersler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- Sertifikalar Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `sertifikalar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int NOT NULL,
  `sertifika_tipi` enum('Namaz','Ders') COLLATE utf8mb4_turkish_ci NOT NULL,
  `baslik` varchar(200) COLLATE utf8mb4_turkish_ci NOT NULL,
  `aciklama` text COLLATE utf8mb4_turkish_ci,
  `donem` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `derece` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `tarih` date NOT NULL,
  `dosya_adi` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `olusturan_kullanici` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `olusturma_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci` (`ogrenci_id`),
  KEY `idx_tip` (`sertifika_tipi`),
  CONSTRAINT `sertifikalar_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- İlave Puanlar Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `ilave_puanlar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int NOT NULL,
  `puan` int NOT NULL,
  `kategori` enum('Namaz','Ders') COLLATE utf8mb4_turkish_ci DEFAULT 'Namaz' COMMENT 'Puan kategorisi',
  `aciklama` text COLLATE utf8mb4_turkish_ci,
  `veren_kullanici` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `tarih` date NOT NULL,
  `olusturma_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci` (`ogrenci_id`),
  KEY `idx_tarih` (`tarih`),
  KEY `idx_kategori` (`kategori`),
  CONSTRAINT `ilave_puanlar_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- Puan Silme Geçmişi Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `puan_silme_gecmisi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int NOT NULL,
  `namaz_kayit_id` int NOT NULL,
  `namaz_vakti` enum('Sabah','Öğlen','İkindi','Akşam','Yatsı') COLLATE utf8mb4_turkish_ci NOT NULL,
  `kiminle_geldi` enum('Kendisi','Babası','Annesi','Anne-Babası') COLLATE utf8mb4_turkish_ci NOT NULL,
  `tarih` date NOT NULL,
  `silme_nedeni` text COLLATE utf8mb4_turkish_ci,
  `silen_kullanici` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `silme_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci` (`ogrenci_id`),
  KEY `idx_tarih` (`tarih`),
  CONSTRAINT `puan_silme_gecmisi_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- İlave Puan Silme Geçmişi Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `ilave_puan_silme_gecmisi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int NOT NULL,
  `puan` int NOT NULL,
  `kategori` enum('Namaz','Ders') COLLATE utf8mb4_turkish_ci DEFAULT 'Namaz' COMMENT 'Puan kategorisi',
  `aciklama` text COLLATE utf8mb4_turkish_ci,
  `veren_kullanici` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `tarih` date NOT NULL,
  `silme_nedeni` text COLLATE utf8mb4_turkish_ci,
  `silen_kullanici` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `silme_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci_id` (`ogrenci_id`),
  KEY `idx_tarih` (`tarih`),
  CONSTRAINT `ilave_puan_silme_gecmisi_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- Öğrenci Mesajları Tablosu
-- ==========================================
CREATE TABLE IF NOT EXISTS `ogrenci_mesajlari` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int NOT NULL,
  `mesaj` text COLLATE utf8mb4_turkish_ci NOT NULL,
  `oncelik` enum('Normal','Önemli','Acil') COLLATE utf8mb4_turkish_ci DEFAULT 'Normal',
  `okundu` tinyint(1) DEFAULT '0',
  `okunma_zamani` timestamp NULL DEFAULT NULL,
  `gonderen_kullanici` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `gonderim_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci` (`ogrenci_id`),
  KEY `idx_okundu` (`okundu`),
  CONSTRAINT `ogrenci_mesajlari_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ==========================================
-- Yıllık Özetler View
-- ==========================================
CREATE OR REPLACE VIEW `yillik_ozetler` AS
SELECT
    o.id AS ogrenci_id,
    o.ad_soyad,
    YEAR(n.tarih) AS yil,
    SUM(CASE WHEN n.kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END) AS kendisi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) AS babasi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) AS annesi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) AS anne_babasi_sayisi,
    COUNT(*) AS toplam_namaz,
    (COUNT(*) + COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND YEAR(tarih) = YEAR(n.tarih)), 0)) AS toplam_puan
FROM ogrenciler o
LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id
GROUP BY o.id, YEAR(n.tarih);

-- ==========================================
-- Aylık Özetler View
-- ==========================================
CREATE OR REPLACE VIEW `aylik_ozetler` AS
SELECT
    o.id AS ogrenci_id,
    o.ad_soyad,
    YEAR(n.tarih) AS yil,
    MONTH(n.tarih) AS ay,
    SUM(CASE WHEN n.kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END) AS kendisi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) AS babasi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) AS annesi_sayisi,
    SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) AS anne_babasi_sayisi,
    COUNT(*) AS toplam_namaz,
    (COUNT(*) + COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND YEAR(tarih) = YEAR(n.tarih) AND MONTH(tarih) = MONTH(n.tarih)), 0)) AS toplam_puan
FROM ogrenciler o
LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id
GROUP BY o.id, YEAR(n.tarih), MONTH(n.tarih);

SET FOREIGN_KEY_CHECKS = 1;
