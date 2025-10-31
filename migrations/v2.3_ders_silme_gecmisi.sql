-- Migration v2.3: Ders Silme Geçmişi
-- Tarih: 2025-10-31
-- Açıklama: Öğrencilerden silinen derslerin kaydını tutmak için tablo

CREATE TABLE IF NOT EXISTS `ogrenci_ders_silme_gecmisi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int NOT NULL,
  `ders_id` int NOT NULL,
  `ders_adi` varchar(200) COLLATE utf8mb4_turkish_ci NOT NULL,
  `kategori_adi` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `puan` int NOT NULL,
  `durum` varchar(20) COLLATE utf8mb4_turkish_ci DEFAULT NULL COMMENT 'Silindiğindeki durum',
  `verme_tarihi` datetime DEFAULT NULL COMMENT 'Eğer verilmişse, verme tarihi',
  `atama_tarihi` datetime DEFAULT NULL COMMENT 'Ne zaman atanmıştı',
  `silme_nedeni` text COLLATE utf8mb4_turkish_ci COMMENT 'Neden silindi',
  `silen_kullanici` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `silme_zamani` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci_id` (`ogrenci_id`),
  KEY `idx_ders_id` (`ders_id`),
  KEY `idx_silme_zamani` (`silme_zamani`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
