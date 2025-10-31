-- ==========================================
-- Migration v2.1 - Ders ve Puan Sistemi Revizyonu
-- Tarih: 2025-10-31
-- ==========================================

-- Öğrenci Dersler tablosuna yeni alanlar ekle
ALTER TABLE `ogrenci_dersler`
ADD COLUMN `verme_tarihi` DATETIME NULL COMMENT 'Dersi verdiği tarih ve saat' AFTER `tamamlanma_tarihi`,
ADD COLUMN `aktif_edilme_sayisi` INT DEFAULT 0 COMMENT 'Kaç kez tekrar aktif edildi' AFTER `verme_tarihi`,
ADD COLUMN `onceki_puan` INT NULL COMMENT 'Aktif etmeden önceki puan' AFTER `aktif_edilme_sayisi`,
ADD COLUMN `son_aktif_edilme` DATETIME NULL COMMENT 'Son aktif edilme zamanı' AFTER `onceki_puan`;

-- İlave Puanlar tablosuna kategori ekle
ALTER TABLE `ilave_puanlar`
ADD COLUMN `kategori` ENUM('Namaz', 'Ders') DEFAULT 'Namaz' COMMENT 'Puan kategorisi' AFTER `puan`,
ADD INDEX `idx_kategori` (`kategori`);

-- Mevcut ilave puanları "Namaz" kategorisine ata (varsayılan)
UPDATE `ilave_puanlar` SET `kategori` = 'Namaz' WHERE `kategori` IS NULL;

-- İlave puan silme geçmişine de kategori ekle
ALTER TABLE `ilave_puan_silme_gecmisi`
ADD COLUMN `kategori` ENUM('Namaz', 'Ders') DEFAULT 'Namaz' COMMENT 'Puan kategorisi' AFTER `puan`;

-- Mevcut silinen ilave puanları da "Namaz" kategorisine ata
UPDATE `ilave_puan_silme_gecmisi` SET `kategori` = 'Namaz' WHERE `kategori` IS NULL;

-- Migration tamamlandı
-- Bu scripti sadece bir kez çalıştırın!
