-- Cami Namaz Takip Veritabanı Yedekleme
-- Tarih: 2025-11-03 10:04:08
-- Database: imammehmet_namazogrenci

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


-- --------------------------------------------------------
-- Tablo yapısı: `aylik_ozetler`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `aylik_ozetler`;

-- --------------------------------------------------------
-- Tablo yapısı: `ders_kategorileri`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ders_kategorileri`;
CREATE TABLE `ders_kategorileri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori_adi` varchar(100) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `sira` int(11) DEFAULT 0,
  `olusturma_tarihi` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_kategori_adi` (`kategori_adi`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Veri dökümü: `ders_kategorileri` (1 satır)

INSERT INTO `ders_kategorileri` VALUES ('2', 'Görgü Kuralları', '', '1', '0', '2025-11-01 10:12:21');


-- --------------------------------------------------------
-- Tablo yapısı: `dersler`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dersler`;
CREATE TABLE `dersler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori_id` int(11) NOT NULL,
  `ders_adi` varchar(200) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `puan` int(11) DEFAULT 1,
  `aktif` tinyint(1) DEFAULT 1,
  `sira` int(11) DEFAULT 0,
  `olusturma_tarihi` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kategori` (`kategori_id`),
  CONSTRAINT `dersler_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `ders_kategorileri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Veri dökümü: `dersler` (2 satır)

INSERT INTO `dersler` VALUES ('4', '2', 'İhlas Süresi', '', '1', '1', '0', '2025-11-01 10:12:28');
INSERT INTO `dersler` VALUES ('5', '2', 'fatiha süresi', '', '1', '1', '0', '2025-11-02 14:52:14');


-- --------------------------------------------------------
-- Tablo yapısı: `ilave_puan_silme_gecmisi`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ilave_puan_silme_gecmisi`;
CREATE TABLE `ilave_puan_silme_gecmisi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int(11) NOT NULL,
  `puan` int(11) NOT NULL,
  `kategori` enum('Namaz','Ders') DEFAULT 'Namaz' COMMENT 'Puan kategorisi',
  `aciklama` text DEFAULT NULL,
  `veren_kullanici` varchar(50) DEFAULT NULL,
  `tarih` date NOT NULL,
  `silme_nedeni` text DEFAULT NULL,
  `silen_kullanici` varchar(50) DEFAULT NULL,
  `silme_zamani` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci_id` (`ogrenci_id`),
  KEY `idx_tarih` (`tarih`),
  CONSTRAINT `ilave_puan_silme_gecmisi_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;


-- --------------------------------------------------------
-- Tablo yapısı: `ilave_puanlar`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ilave_puanlar`;
CREATE TABLE `ilave_puanlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int(11) NOT NULL,
  `puan` int(11) NOT NULL,
  `kategori` enum('Namaz','Ders') DEFAULT 'Namaz' COMMENT 'Puan kategorisi',
  `aciklama` text DEFAULT NULL,
  `veren_kullanici` varchar(50) DEFAULT NULL,
  `tarih` date NOT NULL,
  `olusturma_zamani` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci` (`ogrenci_id`),
  KEY `idx_tarih` (`tarih`),
  KEY `idx_kategori` (`kategori`),
  CONSTRAINT `ilave_puanlar_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Veri dökümü: `ilave_puanlar` (30 satır)

INSERT INTO `ilave_puanlar` VALUES ('1', '1', '5', 'Namaz', 'Güzel namaz', 'mehmetuzun', '2025-10-31', '2025-10-31 22:14:53');
INSERT INTO `ilave_puanlar` VALUES ('2', '1', '2', 'Namaz', 'Güzel namaz kıldın', 'mehmetuzun', '2025-11-01', '2025-11-01 10:00:20');
INSERT INTO `ilave_puanlar` VALUES ('3', '3', '2', 'Namaz', 'test puanı', 'mehmetuzun', '2025-11-01', '2025-11-01 15:23:44');
INSERT INTO `ilave_puanlar` VALUES ('4', '1', '1', 'Namaz', 'ffff', 'mehmetuzun', '2025-11-02', '2025-11-02 14:52:48');
INSERT INTO `ilave_puanlar` VALUES ('5', '5', '1', 'Namaz', 'Babası ile Öğlen namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-02 15:17:49');
INSERT INTO `ilave_puanlar` VALUES ('6', '5', '1', 'Namaz', 'Güzel namaz kıldığı için', 'mehmetuzun', '2025-11-02', '2025-11-02 15:21:21');
INSERT INTO `ilave_puanlar` VALUES ('7', '5', '1', 'Namaz', 'dertt', 'mehmetuzun', '2025-11-02', '2025-11-02 15:28:50');
INSERT INTO `ilave_puanlar` VALUES ('8', '5', '1', 'Namaz', '', 'mehmetuzun', '2025-11-02', '2025-11-02 15:33:14');
INSERT INTO `ilave_puanlar` VALUES ('9', '5', '1', 'Ders', '', 'mehmetuzun', '2025-11-02', '2025-11-02 15:54:18');
INSERT INTO `ilave_puanlar` VALUES ('10', '5', '1', 'Namaz', '', 'mehmetuzun', '2025-11-02', '2025-11-02 15:58:00');
INSERT INTO `ilave_puanlar` VALUES ('11', '5', '1', 'Namaz', '', 'mehmetuzun', '2025-11-02', '2025-11-02 16:00:12');
INSERT INTO `ilave_puanlar` VALUES ('12', '5', '5', 'Namaz', 'test', 'mehmetuzun', '2025-11-02', '2025-11-02 16:02:01');
INSERT INTO `ilave_puanlar` VALUES ('13', '5', '1', 'Ders', '', 'mehmetuzun', '2025-11-02', '2025-11-02 16:12:48');
INSERT INTO `ilave_puanlar` VALUES ('14', '10', '1', 'Namaz', 'Babası ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-02 19:25:31');
INSERT INTO `ilave_puanlar` VALUES ('15', '14', '1', 'Namaz', 'Annesi ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-02 19:26:02');
INSERT INTO `ilave_puanlar` VALUES ('16', '26', '1', 'Namaz', 'Babası ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-02 19:26:29');
INSERT INTO `ilave_puanlar` VALUES ('17', '13', '1', 'Namaz', 'Annesi ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-02 19:26:55');
INSERT INTO `ilave_puanlar` VALUES ('18', '23', '1', 'Namaz', 'Babası ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-02 19:27:22');
INSERT INTO `ilave_puanlar` VALUES ('19', '6', '1', 'Namaz', 'Babası ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-02 19:27:54');
INSERT INTO `ilave_puanlar` VALUES ('20', '8', '1', 'Namaz', 'Babası ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-02 19:28:15');
INSERT INTO `ilave_puanlar` VALUES ('21', '28', '1', 'Namaz', 'Annesi ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-02 19:29:16');
INSERT INTO `ilave_puanlar` VALUES ('22', '7', '1', 'Namaz', 'Babası ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-02 19:30:01');
INSERT INTO `ilave_puanlar` VALUES ('23', '26', '2', 'Namaz', 'Anne-Babası ile Yatsı namazına geldi (bonus)', NULL, '2025-11-01', '2025-11-03 05:42:33');
INSERT INTO `ilave_puanlar` VALUES ('24', '22', '1', 'Namaz', 'Babası ile Sabah namazına geldi (bonus)', NULL, '2025-11-01', '2025-11-03 05:43:01');
INSERT INTO `ilave_puanlar` VALUES ('25', '7', '1', 'Namaz', 'Babası ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-03 05:51:08');
INSERT INTO `ilave_puanlar` VALUES ('26', '8', '1', 'Namaz', 'Annesi ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-03 05:52:51');
INSERT INTO `ilave_puanlar` VALUES ('27', '10', '1', 'Namaz', 'Annesi ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-03 05:53:39');
INSERT INTO `ilave_puanlar` VALUES ('28', '28', '1', 'Namaz', 'Annesi ile Yatsı namazına geldi (bonus)', NULL, '2025-11-02', '2025-11-03 05:54:18');
INSERT INTO `ilave_puanlar` VALUES ('29', '25', '1', 'Namaz', 'Güzel Namaz Kıldı', 'mehmetuzun', '2025-11-01', '2025-11-03 05:56:45');
INSERT INTO `ilave_puanlar` VALUES ('30', '19', '1', 'Namaz', 'Güzel Namaz Kıldın', 'mehmetuzun', '2025-11-01', '2025-11-03 05:58:04');


-- --------------------------------------------------------
-- Tablo yapısı: `kullanicilar`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `kullanicilar`;
CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_adi` varchar(50) NOT NULL,
  `parola_hash` varchar(255) NOT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `son_giris` timestamp NULL DEFAULT NULL,
  `olusturma_tarihi` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kullanici_adi` (`kullanici_adi`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Veri dökümü: `kullanicilar` (1 satır)

INSERT INTO `kullanicilar` VALUES ('1', 'mehmetuzun', '$2y$10$NK95/EubSjJl9qR/qmoaUuTy6KnZTy0mOdyjbVS0W6gIGIsp9uvlO', '1', '2025-11-03 10:03:53', '2025-10-31 20:06:34');


-- --------------------------------------------------------
-- Tablo yapısı: `namaz_kayitlari`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `namaz_kayitlari`;
CREATE TABLE `namaz_kayitlari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int(11) NOT NULL,
  `namaz_vakti` enum('Sabah','Öğlen','İkindi','Akşam','Yatsı') NOT NULL,
  `kiminle_geldi` enum('Kendisi','Babası','Annesi','Anne-Babası') NOT NULL DEFAULT 'Kendisi',
  `tarih` date NOT NULL,
  `saat` time DEFAULT curtime(),
  `kayit_zamani` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci_tarih` (`ogrenci_id`,`tarih`),
  KEY `idx_tarih` (`tarih`),
  CONSTRAINT `namaz_kayitlari_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Veri dökümü: `namaz_kayitlari` (111 satır)

INSERT INTO `namaz_kayitlari` VALUES ('1', '1', 'Sabah', 'Kendisi', '2025-10-31', '22:14:25', '2025-10-31 22:14:25');
INSERT INTO `namaz_kayitlari` VALUES ('2', '1', 'Akşam', 'Kendisi', '2025-11-01', '09:59:49', '2025-11-01 09:59:49');
INSERT INTO `namaz_kayitlari` VALUES ('3', '1', 'Akşam', 'Anne-Babası', '2025-11-01', '09:59:49', '2025-11-01 09:59:49');
INSERT INTO `namaz_kayitlari` VALUES ('4', '2', 'Sabah', 'Kendisi', '2025-11-01', '10:09:33', '2025-11-01 10:09:33');
INSERT INTO `namaz_kayitlari` VALUES ('5', '2', 'Sabah', 'Annesi', '2025-11-01', '10:09:33', '2025-11-01 10:09:33');
INSERT INTO `namaz_kayitlari` VALUES ('6', '3', 'Öğlen', 'Kendisi', '2025-11-01', '10:10:57', '2025-11-01 10:10:57');
INSERT INTO `namaz_kayitlari` VALUES ('7', '1', 'Öğlen', 'Kendisi', '2025-11-01', '12:17:56', '2025-11-01 12:17:56');
INSERT INTO `namaz_kayitlari` VALUES ('8', '1', 'Öğlen', 'Babası', '2025-11-01', '12:17:56', '2025-11-01 12:17:56');
INSERT INTO `namaz_kayitlari` VALUES ('9', '3', 'İkindi', 'Kendisi', '2025-11-01', '15:24:02', '2025-11-01 15:24:02');
INSERT INTO `namaz_kayitlari` VALUES ('10', '3', 'İkindi', 'Anne-Babası', '2025-11-01', '15:24:02', '2025-11-01 15:24:02');
INSERT INTO `namaz_kayitlari` VALUES ('11', '2', 'Sabah', 'Kendisi', '2025-11-02', '14:54:55', '2025-11-02 14:54:55');
INSERT INTO `namaz_kayitlari` VALUES ('12', '2', 'Sabah', 'Anne-Babası', '2025-11-02', '14:54:55', '2025-11-02 14:54:55');
INSERT INTO `namaz_kayitlari` VALUES ('13', '2', 'İkindi', 'Kendisi', '2025-11-02', '14:55:19', '2025-11-02 14:55:19');
INSERT INTO `namaz_kayitlari` VALUES ('14', '2', 'İkindi', 'Anne-Babası', '2025-11-02', '14:55:19', '2025-11-02 14:55:19');
INSERT INTO `namaz_kayitlari` VALUES ('15', '4', 'Sabah', 'Kendisi', '2025-11-02', '15:05:46', '2025-11-02 15:05:46');
INSERT INTO `namaz_kayitlari` VALUES ('16', '4', 'Öğlen', 'Kendisi', '2025-11-02', '15:06:11', '2025-11-02 15:06:11');
INSERT INTO `namaz_kayitlari` VALUES ('17', '4', 'Öğlen', 'Anne-Babası', '2025-11-02', '15:06:11', '2025-11-02 15:06:11');
INSERT INTO `namaz_kayitlari` VALUES ('18', '4', 'İkindi', 'Kendisi', '2025-11-02', '15:06:56', '2025-11-02 15:06:56');
INSERT INTO `namaz_kayitlari` VALUES ('19', '4', 'İkindi', 'Anne-Babası', '2025-11-02', '15:06:56', '2025-11-02 15:06:56');
INSERT INTO `namaz_kayitlari` VALUES ('20', '5', 'Sabah', 'Kendisi', '2025-11-01', '15:17:24', '2025-11-02 15:17:24');
INSERT INTO `namaz_kayitlari` VALUES ('21', '5', 'Öğlen', 'Babası', '2025-11-02', '15:17:49', '2025-11-02 15:17:49');
INSERT INTO `namaz_kayitlari` VALUES ('22', '5', 'İkindi', 'Kendisi', '2025-11-02', '15:32:46', '2025-11-02 15:32:46');
INSERT INTO `namaz_kayitlari` VALUES ('23', '5', 'Öğlen', 'Kendisi', '2025-11-01', '15:35:12', '2025-11-02 15:35:12');
INSERT INTO `namaz_kayitlari` VALUES ('24', '5', 'İkindi', 'Kendisi', '2025-11-02', '15:36:22', '2025-11-02 15:36:22');
INSERT INTO `namaz_kayitlari` VALUES ('25', '10', 'Yatsı', 'Babası', '2025-11-02', '19:25:31', '2025-11-02 19:25:31');
INSERT INTO `namaz_kayitlari` VALUES ('26', '27', 'Yatsı', 'Kendisi', '2025-11-02', '19:25:45', '2025-11-02 19:25:45');
INSERT INTO `namaz_kayitlari` VALUES ('27', '14', 'Yatsı', 'Annesi', '2025-11-02', '19:26:02', '2025-11-02 19:26:02');
INSERT INTO `namaz_kayitlari` VALUES ('28', '26', 'Yatsı', 'Babası', '2025-11-02', '19:26:29', '2025-11-02 19:26:29');
INSERT INTO `namaz_kayitlari` VALUES ('29', '20', 'Yatsı', 'Kendisi', '2025-11-02', '19:26:40', '2025-11-02 19:26:40');
INSERT INTO `namaz_kayitlari` VALUES ('30', '13', 'Yatsı', 'Annesi', '2025-11-02', '19:26:55', '2025-11-02 19:26:55');
INSERT INTO `namaz_kayitlari` VALUES ('31', '23', 'Yatsı', 'Babası', '2025-11-02', '19:27:22', '2025-11-02 19:27:22');
INSERT INTO `namaz_kayitlari` VALUES ('32', '9', 'Yatsı', 'Kendisi', '2025-11-02', '19:27:38', '2025-11-02 19:27:38');
INSERT INTO `namaz_kayitlari` VALUES ('33', '6', 'Yatsı', 'Babası', '2025-11-02', '19:27:54', '2025-11-02 19:27:54');
INSERT INTO `namaz_kayitlari` VALUES ('34', '8', 'Yatsı', 'Babası', '2025-11-02', '19:28:15', '2025-11-02 19:28:15');
INSERT INTO `namaz_kayitlari` VALUES ('35', '22', 'Yatsı', 'Kendisi', '2025-11-02', '19:28:59', '2025-11-02 19:28:59');
INSERT INTO `namaz_kayitlari` VALUES ('36', '28', 'Yatsı', 'Annesi', '2025-11-02', '19:29:16', '2025-11-02 19:29:16');
INSERT INTO `namaz_kayitlari` VALUES ('37', '19', 'Yatsı', 'Kendisi', '2025-11-02', '19:29:52', '2025-11-02 19:29:52');
INSERT INTO `namaz_kayitlari` VALUES ('38', '7', 'Yatsı', 'Babası', '2025-11-02', '19:30:01', '2025-11-02 19:30:01');
INSERT INTO `namaz_kayitlari` VALUES ('39', '23', 'Öğlen', 'Kendisi', '2025-11-01', '05:36:19', '2025-11-03 05:36:19');
INSERT INTO `namaz_kayitlari` VALUES ('40', '23', 'İkindi', 'Kendisi', '2025-11-01', '05:36:37', '2025-11-03 05:36:37');
INSERT INTO `namaz_kayitlari` VALUES ('41', '23', 'Akşam', 'Kendisi', '2025-11-01', '05:36:55', '2025-11-03 05:36:55');
INSERT INTO `namaz_kayitlari` VALUES ('42', '6', 'Öğlen', 'Kendisi', '2025-11-01', '05:37:20', '2025-11-03 05:37:20');
INSERT INTO `namaz_kayitlari` VALUES ('43', '6', 'İkindi', 'Kendisi', '2025-11-01', '05:37:33', '2025-11-03 05:37:33');
INSERT INTO `namaz_kayitlari` VALUES ('44', '6', 'Akşam', 'Kendisi', '2025-11-01', '05:37:48', '2025-11-03 05:37:48');
INSERT INTO `namaz_kayitlari` VALUES ('45', '6', 'Yatsı', 'Kendisi', '2025-11-01', '05:38:09', '2025-11-03 05:38:09');
INSERT INTO `namaz_kayitlari` VALUES ('46', '11', 'Öğlen', 'Kendisi', '2025-11-01', '05:38:27', '2025-11-03 05:38:27');
INSERT INTO `namaz_kayitlari` VALUES ('47', '11', 'İkindi', 'Kendisi', '2025-11-01', '05:38:40', '2025-11-03 05:38:40');
INSERT INTO `namaz_kayitlari` VALUES ('48', '24', 'Öğlen', 'Kendisi', '2025-11-01', '05:38:58', '2025-11-03 05:38:58');
INSERT INTO `namaz_kayitlari` VALUES ('49', '24', 'İkindi', 'Kendisi', '2025-11-01', '05:39:12', '2025-11-03 05:39:12');
INSERT INTO `namaz_kayitlari` VALUES ('50', '24', 'Akşam', 'Kendisi', '2025-11-01', '05:39:28', '2025-11-03 05:39:28');
INSERT INTO `namaz_kayitlari` VALUES ('51', '25', 'Öğlen', 'Kendisi', '2025-11-01', '05:39:45', '2025-11-03 05:39:45');
INSERT INTO `namaz_kayitlari` VALUES ('52', '25', 'İkindi', 'Kendisi', '2025-11-01', '05:39:57', '2025-11-03 05:39:57');
INSERT INTO `namaz_kayitlari` VALUES ('53', '25', 'Akşam', 'Kendisi', '2025-11-01', '05:40:12', '2025-11-03 05:40:12');
INSERT INTO `namaz_kayitlari` VALUES ('54', '25', 'Yatsı', 'Kendisi', '2025-11-01', '05:40:24', '2025-11-03 05:40:24');
INSERT INTO `namaz_kayitlari` VALUES ('55', '14', 'Öğlen', 'Kendisi', '2025-11-01', '05:40:39', '2025-11-03 05:40:39');
INSERT INTO `namaz_kayitlari` VALUES ('56', '14', 'İkindi', 'Kendisi', '2025-11-01', '05:40:50', '2025-11-03 05:40:50');
INSERT INTO `namaz_kayitlari` VALUES ('57', '14', 'Akşam', 'Kendisi', '2025-11-01', '05:41:05', '2025-11-03 05:41:05');
INSERT INTO `namaz_kayitlari` VALUES ('58', '13', 'Öğlen', 'Kendisi', '2025-11-01', '05:41:18', '2025-11-03 05:41:18');
INSERT INTO `namaz_kayitlari` VALUES ('59', '13', 'İkindi', 'Kendisi', '2025-11-01', '05:41:29', '2025-11-03 05:41:29');
INSERT INTO `namaz_kayitlari` VALUES ('60', '26', 'Öğlen', 'Kendisi', '2025-11-01', '05:41:44', '2025-11-03 05:41:44');
INSERT INTO `namaz_kayitlari` VALUES ('61', '26', 'İkindi', 'Kendisi', '2025-11-01', '05:41:57', '2025-11-03 05:41:57');
INSERT INTO `namaz_kayitlari` VALUES ('62', '26', 'Akşam', 'Kendisi', '2025-11-01', '05:42:12', '2025-11-03 05:42:12');
INSERT INTO `namaz_kayitlari` VALUES ('63', '26', 'Yatsı', 'Anne-Babası', '2025-11-01', '05:42:33', '2025-11-03 05:42:33');
INSERT INTO `namaz_kayitlari` VALUES ('64', '22', 'Sabah', 'Babası', '2025-11-01', '05:43:01', '2025-11-03 05:43:01');
INSERT INTO `namaz_kayitlari` VALUES ('65', '22', 'Öğlen', 'Kendisi', '2025-11-01', '05:43:14', '2025-11-03 05:43:14');
INSERT INTO `namaz_kayitlari` VALUES ('66', '22', 'İkindi', 'Kendisi', '2025-11-01', '05:43:27', '2025-11-03 05:43:27');
INSERT INTO `namaz_kayitlari` VALUES ('67', '22', 'Akşam', 'Kendisi', '2025-11-01', '05:43:40', '2025-11-03 05:43:40');
INSERT INTO `namaz_kayitlari` VALUES ('68', '22', 'Yatsı', 'Kendisi', '2025-11-01', '05:43:56', '2025-11-03 05:43:56');
INSERT INTO `namaz_kayitlari` VALUES ('69', '22', 'İkindi', 'Kendisi', '2025-11-02', '05:44:13', '2025-11-03 05:44:13');
INSERT INTO `namaz_kayitlari` VALUES ('70', '9', 'Öğlen', 'Kendisi', '2025-11-01', '05:44:29', '2025-11-03 05:44:29');
INSERT INTO `namaz_kayitlari` VALUES ('71', '9', 'İkindi', 'Kendisi', '2025-11-01', '05:44:39', '2025-11-03 05:44:39');
INSERT INTO `namaz_kayitlari` VALUES ('72', '9', 'Akşam', 'Kendisi', '2025-11-01', '05:44:54', '2025-11-03 05:44:54');
INSERT INTO `namaz_kayitlari` VALUES ('73', '9', 'Öğlen', 'Kendisi', '2025-11-02', '05:45:07', '2025-11-03 05:45:07');
INSERT INTO `namaz_kayitlari` VALUES ('74', '9', 'İkindi', 'Kendisi', '2025-11-02', '05:45:20', '2025-11-03 05:45:20');
INSERT INTO `namaz_kayitlari` VALUES ('75', '20', 'Öğlen', 'Kendisi', '2025-11-01', '05:45:41', '2025-11-03 05:45:41');
INSERT INTO `namaz_kayitlari` VALUES ('76', '20', 'İkindi', 'Kendisi', '2025-11-01', '05:45:53', '2025-11-03 05:45:53');
INSERT INTO `namaz_kayitlari` VALUES ('77', '20', 'Akşam', 'Kendisi', '2025-11-01', '05:46:09', '2025-11-03 05:46:09');
INSERT INTO `namaz_kayitlari` VALUES ('78', '20', 'Yatsı', 'Kendisi', '2025-11-01', '05:46:24', '2025-11-03 05:46:24');
INSERT INTO `namaz_kayitlari` VALUES ('79', '20', 'Öğlen', 'Kendisi', '2025-11-02', '05:46:36', '2025-11-03 05:46:36');
INSERT INTO `namaz_kayitlari` VALUES ('80', '20', 'İkindi', 'Kendisi', '2025-11-02', '05:46:50', '2025-11-03 05:46:50');
INSERT INTO `namaz_kayitlari` VALUES ('81', '20', 'Akşam', 'Kendisi', '2025-11-02', '05:47:03', '2025-11-03 05:47:03');
INSERT INTO `namaz_kayitlari` VALUES ('82', '19', 'Öğlen', 'Kendisi', '2025-11-01', '05:47:24', '2025-11-03 05:47:24');
INSERT INTO `namaz_kayitlari` VALUES ('83', '19', 'İkindi', 'Kendisi', '2025-11-01', '05:47:36', '2025-11-03 05:47:36');
INSERT INTO `namaz_kayitlari` VALUES ('84', '19', 'Akşam', 'Kendisi', '2025-11-01', '05:47:47', '2025-11-03 05:47:47');
INSERT INTO `namaz_kayitlari` VALUES ('85', '19', 'Yatsı', 'Kendisi', '2025-11-01', '05:48:00', '2025-11-03 05:48:00');
INSERT INTO `namaz_kayitlari` VALUES ('86', '19', 'Öğlen', 'Kendisi', '2025-11-02', '05:48:13', '2025-11-03 05:48:13');
INSERT INTO `namaz_kayitlari` VALUES ('87', '19', 'İkindi', 'Kendisi', '2025-11-02', '05:48:25', '2025-11-03 05:48:25');
INSERT INTO `namaz_kayitlari` VALUES ('88', '19', 'Yatsı', 'Kendisi', '2025-11-02', '05:48:38', '2025-11-03 05:48:38');
INSERT INTO `namaz_kayitlari` VALUES ('89', '12', 'Öğlen', 'Kendisi', '2025-11-01', '05:48:54', '2025-11-03 05:48:54');
INSERT INTO `namaz_kayitlari` VALUES ('90', '12', 'İkindi', 'Kendisi', '2025-11-01', '05:49:08', '2025-11-03 05:49:08');
INSERT INTO `namaz_kayitlari` VALUES ('91', '7', 'Öğlen', 'Kendisi', '2025-11-01', '05:49:38', '2025-11-03 05:49:38');
INSERT INTO `namaz_kayitlari` VALUES ('92', '7', 'İkindi', 'Kendisi', '2025-11-01', '05:49:50', '2025-11-03 05:49:50');
INSERT INTO `namaz_kayitlari` VALUES ('93', '7', 'Akşam', 'Kendisi', '2025-11-01', '05:50:01', '2025-11-03 05:50:01');
INSERT INTO `namaz_kayitlari` VALUES ('94', '7', 'Yatsı', 'Kendisi', '2025-11-01', '05:50:13', '2025-11-03 05:50:13');
INSERT INTO `namaz_kayitlari` VALUES ('95', '7', 'Öğlen', 'Kendisi', '2025-11-02', '05:50:30', '2025-11-03 05:50:30');
INSERT INTO `namaz_kayitlari` VALUES ('96', '7', 'İkindi', 'Kendisi', '2025-11-02', '05:50:40', '2025-11-03 05:50:40');
INSERT INTO `namaz_kayitlari` VALUES ('97', '7', 'Akşam', 'Kendisi', '2025-11-02', '05:50:52', '2025-11-03 05:50:52');
INSERT INTO `namaz_kayitlari` VALUES ('98', '7', 'Yatsı', 'Babası', '2025-11-02', '05:51:08', '2025-11-03 05:51:08');
INSERT INTO `namaz_kayitlari` VALUES ('99', '18', 'Öğlen', 'Kendisi', '2025-11-01', '05:51:28', '2025-11-03 05:51:28');
INSERT INTO `namaz_kayitlari` VALUES ('100', '27', 'Öğlen', 'Kendisi', '2025-11-01', '05:51:42', '2025-11-03 05:51:42');
INSERT INTO `namaz_kayitlari` VALUES ('101', '27', 'İkindi', 'Kendisi', '2025-11-01', '05:51:55', '2025-11-03 05:51:55');
INSERT INTO `namaz_kayitlari` VALUES ('102', '27', 'Akşam', 'Kendisi', '2025-11-01', '05:52:07', '2025-11-03 05:52:07');
INSERT INTO `namaz_kayitlari` VALUES ('103', '8', 'Öğlen', 'Kendisi', '2025-11-01', '05:52:22', '2025-11-03 05:52:22');
INSERT INTO `namaz_kayitlari` VALUES ('104', '8', 'İkindi', 'Kendisi', '2025-11-01', '05:52:34', '2025-11-03 05:52:34');
INSERT INTO `namaz_kayitlari` VALUES ('105', '8', 'Yatsı', 'Annesi', '2025-11-02', '05:52:51', '2025-11-03 05:52:51');
INSERT INTO `namaz_kayitlari` VALUES ('106', '10', 'Öğlen', 'Kendisi', '2025-11-01', '05:53:05', '2025-11-03 05:53:05');
INSERT INTO `namaz_kayitlari` VALUES ('107', '10', 'İkindi', 'Kendisi', '2025-11-01', '05:53:16', '2025-11-03 05:53:16');
INSERT INTO `namaz_kayitlari` VALUES ('108', '10', 'Yatsı', 'Annesi', '2025-11-02', '05:53:39', '2025-11-03 05:53:39');
INSERT INTO `namaz_kayitlari` VALUES ('109', '28', 'Öğlen', 'Kendisi', '2025-11-01', '05:53:53', '2025-11-03 05:53:53');
INSERT INTO `namaz_kayitlari` VALUES ('110', '28', 'İkindi', 'Kendisi', '2025-11-01', '05:54:04', '2025-11-03 05:54:04');
INSERT INTO `namaz_kayitlari` VALUES ('111', '28', 'Yatsı', 'Annesi', '2025-11-02', '05:54:18', '2025-11-03 05:54:18');


-- --------------------------------------------------------
-- Tablo yapısı: `ogrenci_ders_silme_gecmisi`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ogrenci_ders_silme_gecmisi`;
CREATE TABLE `ogrenci_ders_silme_gecmisi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int(11) NOT NULL,
  `ders_id` int(11) NOT NULL,
  `ders_adi` varchar(200) NOT NULL,
  `kategori_adi` varchar(100) NOT NULL,
  `puan` int(11) NOT NULL,
  `durum` varchar(20) DEFAULT NULL COMMENT 'Silindiğindeki durum',
  `verme_tarihi` datetime DEFAULT NULL COMMENT 'Eğer verilmişse, verme tarihi',
  `atama_tarihi` datetime DEFAULT NULL COMMENT 'Ne zaman atanmıştı',
  `silme_nedeni` text DEFAULT NULL COMMENT 'Neden silindi',
  `silen_kullanici` varchar(50) DEFAULT NULL,
  `silme_zamani` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci_id` (`ogrenci_id`),
  KEY `idx_ders_id` (`ders_id`),
  KEY `idx_silme_zamani` (`silme_zamani`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;


-- --------------------------------------------------------
-- Tablo yapısı: `ogrenci_dersler`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ogrenci_dersler`;
CREATE TABLE `ogrenci_dersler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int(11) NOT NULL,
  `ders_id` int(11) NOT NULL,
  `durum` enum('Beklemede','Tamamlandi') DEFAULT 'Beklemede',
  `tamamlanma_tarihi` date DEFAULT NULL,
  `verme_tarihi` datetime DEFAULT NULL COMMENT 'Dersi verdiği tarih ve saat',
  `aktif_edilme_sayisi` int(11) DEFAULT 0 COMMENT 'Kaç kez tekrar aktif edildi',
  `onceki_puan` int(11) DEFAULT NULL COMMENT 'Aktif etmeden önceki puan',
  `son_aktif_edilme` datetime DEFAULT NULL COMMENT 'Son aktif edilme zamanı',
  `puan_verildi` tinyint(1) DEFAULT 0,
  `notlar` text DEFAULT NULL,
  `atama_tarihi` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ogrenci_ders` (`ogrenci_id`,`ders_id`),
  KEY `ders_id` (`ders_id`),
  KEY `idx_durum` (`durum`),
  CONSTRAINT `ogrenci_dersler_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ogrenci_dersler_ibfk_2` FOREIGN KEY (`ders_id`) REFERENCES `dersler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Veri dökümü: `ogrenci_dersler` (56 satır)

INSERT INTO `ogrenci_dersler` VALUES ('10', '1', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-01 10:12:28');
INSERT INTO `ogrenci_dersler` VALUES ('11', '2', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-01 10:12:28');
INSERT INTO `ogrenci_dersler` VALUES ('12', '3', '4', 'Tamamlandi', '2025-11-01', '2025-11-01 15:24:47', '0', NULL, NULL, '1', NULL, '2025-11-01 10:12:28');
INSERT INTO `ogrenci_dersler` VALUES ('13', '1', '5', 'Tamamlandi', '2025-11-02', '2025-11-02 14:53:33', '0', NULL, NULL, '1', NULL, '2025-11-02 14:52:14');
INSERT INTO `ogrenci_dersler` VALUES ('14', '2', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 14:52:14');
INSERT INTO `ogrenci_dersler` VALUES ('15', '3', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 14:52:14');
INSERT INTO `ogrenci_dersler` VALUES ('16', '4', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 15:05:32');
INSERT INTO `ogrenci_dersler` VALUES ('17', '4', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 15:05:32');
INSERT INTO `ogrenci_dersler` VALUES ('18', '5', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 15:16:45');
INSERT INTO `ogrenci_dersler` VALUES ('19', '5', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 15:16:45');
INSERT INTO `ogrenci_dersler` VALUES ('20', '6', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:27:37');
INSERT INTO `ogrenci_dersler` VALUES ('21', '6', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:27:37');
INSERT INTO `ogrenci_dersler` VALUES ('22', '7', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:28:31');
INSERT INTO `ogrenci_dersler` VALUES ('23', '7', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:28:31');
INSERT INTO `ogrenci_dersler` VALUES ('24', '8', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:30:34');
INSERT INTO `ogrenci_dersler` VALUES ('25', '8', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:30:34');
INSERT INTO `ogrenci_dersler` VALUES ('26', '9', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:31:53');
INSERT INTO `ogrenci_dersler` VALUES ('27', '9', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:31:53');
INSERT INTO `ogrenci_dersler` VALUES ('28', '10', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:32:56');
INSERT INTO `ogrenci_dersler` VALUES ('29', '10', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:32:56');
INSERT INTO `ogrenci_dersler` VALUES ('30', '11', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:34:03');
INSERT INTO `ogrenci_dersler` VALUES ('31', '11', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:34:03');
INSERT INTO `ogrenci_dersler` VALUES ('32', '12', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:35:02');
INSERT INTO `ogrenci_dersler` VALUES ('33', '12', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:35:02');
INSERT INTO `ogrenci_dersler` VALUES ('34', '13', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:36:27');
INSERT INTO `ogrenci_dersler` VALUES ('35', '13', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:36:27');
INSERT INTO `ogrenci_dersler` VALUES ('36', '14', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:37:42');
INSERT INTO `ogrenci_dersler` VALUES ('37', '14', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 16:37:42');
INSERT INTO `ogrenci_dersler` VALUES ('38', '15', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:44:07');
INSERT INTO `ogrenci_dersler` VALUES ('39', '15', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:44:07');
INSERT INTO `ogrenci_dersler` VALUES ('40', '16', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:45:09');
INSERT INTO `ogrenci_dersler` VALUES ('41', '16', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:45:09');
INSERT INTO `ogrenci_dersler` VALUES ('42', '17', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:46:04');
INSERT INTO `ogrenci_dersler` VALUES ('43', '17', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:46:04');
INSERT INTO `ogrenci_dersler` VALUES ('44', '18', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:47:10');
INSERT INTO `ogrenci_dersler` VALUES ('45', '18', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:47:10');
INSERT INTO `ogrenci_dersler` VALUES ('46', '19', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:48:27');
INSERT INTO `ogrenci_dersler` VALUES ('47', '19', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:48:27');
INSERT INTO `ogrenci_dersler` VALUES ('48', '20', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:50:03');
INSERT INTO `ogrenci_dersler` VALUES ('49', '20', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:50:03');
INSERT INTO `ogrenci_dersler` VALUES ('50', '21', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:51:01');
INSERT INTO `ogrenci_dersler` VALUES ('51', '21', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:51:01');
INSERT INTO `ogrenci_dersler` VALUES ('52', '22', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:53:03');
INSERT INTO `ogrenci_dersler` VALUES ('53', '22', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:53:03');
INSERT INTO `ogrenci_dersler` VALUES ('54', '23', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:53:26');
INSERT INTO `ogrenci_dersler` VALUES ('55', '23', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:53:26');
INSERT INTO `ogrenci_dersler` VALUES ('56', '24', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:53:48');
INSERT INTO `ogrenci_dersler` VALUES ('57', '24', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:53:48');
INSERT INTO `ogrenci_dersler` VALUES ('58', '25', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:54:00');
INSERT INTO `ogrenci_dersler` VALUES ('59', '25', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:54:00');
INSERT INTO `ogrenci_dersler` VALUES ('60', '26', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:54:26');
INSERT INTO `ogrenci_dersler` VALUES ('61', '26', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:54:26');
INSERT INTO `ogrenci_dersler` VALUES ('62', '27', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:54:56');
INSERT INTO `ogrenci_dersler` VALUES ('63', '27', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:54:56');
INSERT INTO `ogrenci_dersler` VALUES ('64', '28', '4', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:55:19');
INSERT INTO `ogrenci_dersler` VALUES ('65', '28', '5', 'Beklemede', NULL, NULL, '0', NULL, NULL, '0', NULL, '2025-11-02 18:55:19');


-- --------------------------------------------------------
-- Tablo yapısı: `ogrenci_kullanicilar`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ogrenci_kullanicilar`;
CREATE TABLE `ogrenci_kullanicilar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int(11) NOT NULL,
  `kullanici_adi` varchar(50) NOT NULL,
  `parola_hash` varchar(255) NOT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `ilk_giris` tinyint(1) DEFAULT 1,
  `son_giris` timestamp NULL DEFAULT NULL,
  `olusturma_tarihi` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ogrenci_id` (`ogrenci_id`),
  UNIQUE KEY `kullanici_adi` (`kullanici_adi`),
  CONSTRAINT `ogrenci_kullanicilar_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Veri dökümü: `ogrenci_kullanicilar` (28 satır)

INSERT INTO `ogrenci_kullanicilar` VALUES ('1', '1', 'mehmettuzun', '$2y$10$aRp803qCAPPNWxyXkDaZA.2uRWIHvjArWmHB1XLaYdb5oK7OhyLi.', '1', '1', NULL, '2025-10-31 22:11:29');
INSERT INTO `ogrenci_kullanicilar` VALUES ('2', '2', 'mertcankizilkaya', '$2y$10$RpKaR9.dD3GZDzLDxfJzRuiu5FKIbdZd/KU9iWI1YM5wnISZ3ns76', '1', '1', NULL, '2025-11-01 10:06:13');
INSERT INTO `ogrenci_kullanicilar` VALUES ('3', '3', 'alican', '$2y$10$PC4p9epI.tJ6JYg.5Iv8A.l3bSr4oCEfHaRazdcsFEKhrl97sa.tm', '1', '1', NULL, '2025-11-01 10:10:44');
INSERT INTO `ogrenci_kullanicilar` VALUES ('4', '4', 'mirzabora', '$2y$10$gIC.vqFQVRFcuyAJiUW8U.GJx9fZrzuscVI8.yartyMwHUN9VWhaG', '1', '1', NULL, '2025-11-02 15:05:32');
INSERT INTO `ogrenci_kullanicilar` VALUES ('5', '5', 'volkankalyoncu', '$2y$10$QEB8eoeYNz3uRnmxmVvW/eoLKfds1iEJcTk8sXo38o9.bJDXDBOHS', '1', '1', NULL, '2025-11-02 15:16:45');
INSERT INTO `ogrenci_kullanicilar` VALUES ('6', '6', 'eflinnisa', '$2y$10$1dguQmqSnZtYSBwJtlhmnO4/rLvPF7xjbih.UkL9fXfZNUqeaPvZ6', '1', '1', NULL, '2025-11-02 16:27:37');
INSERT INTO `ogrenci_kullanicilar` VALUES ('7', '7', 'yigitefe', '$2y$10$sndcPHCEhBYzrdMZB.3VgeHlCmD85/yt092.PiTPrbdPYNrVAoDPK', '1', '1', NULL, '2025-11-02 16:28:31');
INSERT INTO `ogrenci_kullanicilar` VALUES ('8', '8', 'azraustun', '$2y$10$W2pf0xToxGTajAJHFsiDZ.AgekPnPwM32It12tT2eKeu3Kbp6sK52', '1', '1', NULL, '2025-11-02 16:30:34');
INSERT INTO `ogrenci_kullanicilar` VALUES ('9', '9', 'berrinkabadayi', '$2y$10$9d6AQSjDHq63nEEC//vlN.bUP9CEY0DQWbSGOFOhv4w3hDU43eWue', '1', '1', NULL, '2025-11-02 16:31:53');
INSERT INTO `ogrenci_kullanicilar` VALUES ('10', '10', 'alparslanustun', '$2y$10$xo28C/1Ygwf8zhAqJogym.p12GvZCrTb1JOEHDxQHvsb3F3JgVsTO', '1', '1', NULL, '2025-11-02 16:32:56');
INSERT INTO `ogrenci_kullanicilar` VALUES ('11', '11', 'esilakabadayi', '$2y$10$3qPT.Nm15WbXhAeVoZgAXedSbfTY6DGAEHf61kN4A303vBi82KH4q', '1', '1', NULL, '2025-11-02 16:34:03');
INSERT INTO `ogrenci_kullanicilar` VALUES ('12', '12', 'elanurkabadayi', '$2y$10$vxsOD8rlKfZBzZ2obllEtON8oUZQfcwjb7Ub.aTW5l65jo8y91.5a', '1', '1', NULL, '2025-11-02 16:35:02');
INSERT INTO `ogrenci_kullanicilar` VALUES ('13', '13', 'busemiray', '$2y$10$ADRTmlB1Vp.AxKFfbL8SI.QjILghmaP0Msn.NBBsd1alb3QJCOglS', '1', '1', NULL, '2025-11-02 16:36:27');
INSERT INTO `ogrenci_kullanicilar` VALUES ('14', '14', 'burakmirza', '$2y$10$vUR.hUTwNauMzPM5QYxJN.vSbubX/6zL7xQaGJ9H6ONG.eNDlV2TO', '1', '1', NULL, '2025-11-02 16:37:42');
INSERT INTO `ogrenci_kullanicilar` VALUES ('15', '15', 'enginyagiz', '$2y$10$P9/X5LFJ7QEbNok/nqzQ..8t9Gn3qGbDrlyHXOL7NPsYXLbT0JvkW', '1', '1', NULL, '2025-11-02 18:44:07');
INSERT INTO `ogrenci_kullanicilar` VALUES ('16', '16', 'rumeysagul', '$2y$10$SY4xGV.ULLdhKNbVpJfK2.73njXSusSheXwHJNNg3D4fMug9moto6', '1', '1', NULL, '2025-11-02 18:45:09');
INSERT INTO `ogrenci_kullanicilar` VALUES ('17', '17', 'cemalagah', '$2y$10$uyGWRfpLm8jhAK0H6Tirxu3D3aLEHdvU/l2tCrH.li053uMCc9qJ6', '1', '1', NULL, '2025-11-02 18:46:04');
INSERT INTO `ogrenci_kullanicilar` VALUES ('18', '18', 'melekustun', '$2y$10$usgMPpOLuuLSzn6LktJnbupeDXSYNWLHG2ESn41aSVng/LSx9cKWW', '1', '1', NULL, '2025-11-02 18:47:10');
INSERT INTO `ogrenci_kullanicilar` VALUES ('19', '19', 'omerasaf', '$2y$10$MYPD5ZyREDLsrhKBNLRzjO.a4W6aHdhT8r2IskhvFNayvq37VeXgu', '1', '1', NULL, '2025-11-02 18:48:27');
INSERT INTO `ogrenci_kullanicilar` VALUES ('20', '20', 'kdamla', '$2y$10$A475Kfz0GeGL4AROJAb4s.gMrAwV2JC7XGA29JjARzNY6LyVqVU82', '1', '1', NULL, '2025-11-02 18:50:03');
INSERT INTO `ogrenci_kullanicilar` VALUES ('21', '21', 'aliaybars', '$2y$10$WXhRpm3uWcUINU8XL7Hiout7VlJAC5ytR5fUCt0UNCI.kNK99LUMe', '1', '1', NULL, '2025-11-02 18:51:01');
INSERT INTO `ogrenci_kullanicilar` VALUES ('22', '22', 'ravzakabadayi', '$2y$10$.DQIKADdoqzMKU7dLwWrXu3Mpse3BKtUCClnw0ntBK38Wy075osG.', '1', '1', NULL, '2025-11-02 18:53:03');
INSERT INTO `ogrenci_kullanicilar` VALUES ('23', '23', 'elifbora', '$2y$10$HoYSEuTIVVoS1zbse90NS.8DUas811FVJyg7V7kODTHHX.cVSxMTq', '1', '1', NULL, '2025-11-02 18:53:26');
INSERT INTO `ogrenci_kullanicilar` VALUES ('24', '24', 'damlakabadayi', '$2y$10$F0bKP1o4tvigXrPaACmmveGB9.px1R3PETtuKM9t2Ek3Wk9v3I93.', '1', '1', NULL, '2025-11-02 18:53:48');
INSERT INTO `ogrenci_kullanicilar` VALUES ('25', '25', 'yusufkabadayi', '$2y$10$rTS/O/6uEKp8OEwLpUfITu9EuNR7OUoZRsJwXW5utav4aJdG0bUhe', '1', '1', NULL, '2025-11-02 18:54:00');
INSERT INTO `ogrenci_kullanicilar` VALUES ('26', '26', 'hayrunnisabora', '$2y$10$iN3CRBIeF.FB1UwFjcvz0u9N7xXeJE6QuNRos/xP0yah4/XNCY34m', '1', '1', NULL, '2025-11-02 18:54:26');
INSERT INTO `ogrenci_kullanicilar` VALUES ('27', '27', 'oykukabadayi', '$2y$10$tRS1AsO6ewA/MxbduRtG0eE1w/MGwfTKXqSgL0hmOtdBuoMedLzR6', '1', '1', NULL, '2025-11-02 18:54:56');
INSERT INTO `ogrenci_kullanicilar` VALUES ('28', '28', 'aselsare', '$2y$10$jmWdWRsZPvIvs4lcBa6o7.FvO8n.eTWwEekvqQdVHbCLEQSliI9v6', '1', '1', NULL, '2025-11-02 18:55:19');


-- --------------------------------------------------------
-- Tablo yapısı: `ogrenci_mesajlari`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ogrenci_mesajlari`;
CREATE TABLE `ogrenci_mesajlari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int(11) NOT NULL,
  `mesaj` text NOT NULL,
  `oncelik` enum('Normal','Önemli','Acil') DEFAULT 'Normal',
  `okundu` tinyint(1) DEFAULT 0,
  `okunma_zamani` timestamp NULL DEFAULT NULL,
  `gonderen_kullanici` varchar(50) DEFAULT NULL,
  `gonderim_zamani` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci` (`ogrenci_id`),
  KEY `idx_okundu` (`okundu`),
  CONSTRAINT `ogrenci_mesajlari_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Veri dökümü: `ogrenci_mesajlari` (2 satır)

INSERT INTO `ogrenci_mesajlari` VALUES ('1', '1', '5555', 'Normal', '0', NULL, 'mehmetuzun', '2025-11-01 10:03:38');
INSERT INTO `ogrenci_mesajlari` VALUES ('2', '1', 'toplu mesaj deneme', 'Önemli', '0', NULL, 'mehmetuzun', '2025-11-01 10:03:57');


-- --------------------------------------------------------
-- Tablo yapısı: `ogrenciler`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ogrenciler`;
CREATE TABLE `ogrenciler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_soyad` varchar(100) NOT NULL,
  `dogum_tarihi` date NOT NULL,
  `yas` int(11) DEFAULT NULL,
  `baba_adi` varchar(100) DEFAULT NULL,
  `anne_adi` varchar(100) DEFAULT NULL,
  `baba_telefonu` varchar(20) DEFAULT NULL,
  `anne_telefonu` varchar(20) DEFAULT NULL,
  `kayit_tarihi` timestamp NULL DEFAULT current_timestamp(),
  `aktif` tinyint(1) DEFAULT 1 COMMENT '1=Aktif, 0=Pasif',
  `guncelleme_tarihi` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `silinme_tarihi` timestamp NULL DEFAULT NULL,
  `silindi` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ad_soyad` (`ad_soyad`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Veri dökümü: `ogrenciler` (28 satır)

INSERT INTO `ogrenciler` VALUES ('1', 'Mehmet TÜZÜN', '2004-10-31', '21', 'İsmet', 'Fatma', '05555555555', '05321478478', '2025-10-31 22:11:29', '0', '2025-11-02 18:59:14', '2025-11-02 18:59:14', '0');
INSERT INTO `ogrenciler` VALUES ('2', 'Mertcan Kızılkaya', '2019-02-01', '6', 'İDRİS', 'NERMİN', '05321414141', '05367654321', '2025-11-01 10:06:13', '0', '2025-11-02 16:24:37', '2025-11-02 16:24:37', '0');
INSERT INTO `ogrenciler` VALUES ('3', 'Ali Can', '2025-11-01', '0', 'İDRİS', 'NERMİN', '05321414141', '05321478478', '2025-11-01 10:10:44', '0', '2025-11-02 16:24:29', '2025-11-02 16:24:29', '0');
INSERT INTO `ogrenciler` VALUES ('4', 'Mirza BORA', '2018-02-02', '7', 'ddd', 'ddd', '', '', '2025-11-02 15:05:32', '0', '2025-11-03 05:57:17', '2025-11-03 05:57:17', '0');
INSERT INTO `ogrenciler` VALUES ('5', 'VOLKAN KALYONCU', '1987-02-15', '38', 'zafer', 'kadriye', '05327044076', '05327044076', '2025-11-02 15:16:45', '0', '2025-11-02 18:59:31', '2025-11-02 18:59:31', '0');
INSERT INTO `ogrenciler` VALUES ('6', 'Eflin Nisa ÜSTÜN', '2013-05-19', '12', 'Tolga', 'Ebru', '05414202547', '05432455769', '2025-11-02 16:27:37', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('7', 'Yiğit Efe ÜSTÜN', '2016-09-21', '9', 'Tolga', 'Ebru', '05414202547', '05432455769', '2025-11-02 16:28:31', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('8', 'Azra ÜSTÜN', '2014-05-26', '11', 'Volkan', 'Bahar', '05422735699', '05427124226', '2025-11-02 16:30:34', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('9', 'Berrin KABADAYI', '2016-08-19', '9', 'Musa', 'Nurten', '05448485931', '05452656255', '2025-11-02 16:31:53', '1', '2025-11-02 18:55:58', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('10', 'Alparslan ÜSTÜN', '2019-03-18', '6', 'Volkan', 'Bahar', '05422735699', '05427124226', '2025-11-02 16:32:56', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('11', 'Esila KABADAYI', '2019-04-22', '6', 'Emre', 'Leyla', '05425036155', '05436409969', '2025-11-02 16:34:03', '1', '2025-11-02 18:56:30', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('12', 'Elanur KABADAYI', '2017-12-07', '7', 'Emre', 'Leyla', '05425036155', '05436409969', '2025-11-02 16:35:02', '1', '2025-11-02 18:56:15', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('13', 'Buse Miray BORA', '2017-11-10', '7', 'Serdar', 'Burcu', '05443638095', '05438813794', '2025-11-02 16:36:27', '1', '2025-11-02 18:56:54', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('14', 'Burak Mirza BORA', '2016-02-05', '9', 'Serdar', 'Burcu', '05443638095', '05438813794', '2025-11-02 16:37:42', '1', '2025-11-02 18:56:42', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('15', 'Engin Yağız BORA', '2013-08-09', '12', 'Engin', 'Sema', '05438186111', '05352151768', '2025-11-02 18:44:07', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('16', 'Rümeysa Gül BORA', '2012-03-19', '13', 'Ümit', 'Ayşe', '05444933955', '05459523276', '2025-11-02 18:45:09', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('17', 'Cemal Agah STÜN', '2013-05-02', '12', 'Fatih', 'Ayşegül', '05433085511', '05376999819', '2025-11-02 18:46:04', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('18', 'Melek ÜSTÜN', '2016-12-27', '8', 'Halit', 'Seda', '05468718976', '05433944916', '2025-11-02 18:47:10', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('19', 'Ömer Asaf GÜNDÜZ', '2014-09-18', '11', 'Hasan', 'Ayşe', '05352724076', '05385079940', '2025-11-02 18:48:27', '1', '2025-11-02 19:02:23', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('20', 'K Damla KABADAYI', '2014-10-10', '11', 'Kemal', 'Melek', '05467387650', '05538664955', '2025-11-02 18:50:03', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('21', 'Ali Aybars ÜSTÜN', '2015-10-13', '10', 'Fatih', 'Ayşegül', '05433085511', '05376999819', '2025-11-02 18:51:01', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('22', 'Ravza KABADAYI', '2025-11-02', '0', 'Refik', '', '', '', '2025-11-02 18:53:03', '1', '2025-11-03 06:00:20', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('23', 'Elif BORA', '2025-11-02', '0', '', '', '', '', '2025-11-02 18:53:26', '1', NULL, NULL, '0');
INSERT INTO `ogrenciler` VALUES ('24', 'Damla KABADAYI', '2025-11-02', '0', 'Orhan', '', '', '', '2025-11-02 18:53:48', '1', '2025-11-02 18:57:58', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('25', 'Yusuf KABADAYI', '2025-11-02', '0', 'Orhan', '', '', '', '2025-11-02 18:54:00', '1', '2025-11-02 18:57:33', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('26', 'Hayrunnisa BORA', '2017-08-17', '8', 'Ümit', 'Ayşe', '05444933955', '05459523276', '2025-11-02 18:54:26', '1', '2025-11-02 19:01:51', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('27', 'Öykü KABADAYI', '2025-11-02', '0', '', '', '', '', '2025-11-02 18:54:55', '1', '2025-11-02 18:58:28', NULL, '0');
INSERT INTO `ogrenciler` VALUES ('28', 'Asel Sare YILMAZ', '2025-11-02', '0', 'Osman', 'Özlem', '', '', '2025-11-02 18:55:19', '1', '2025-11-02 18:57:46', NULL, '0');


-- --------------------------------------------------------
-- Tablo yapısı: `puan_silme_gecmisi`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `puan_silme_gecmisi`;
CREATE TABLE `puan_silme_gecmisi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int(11) NOT NULL,
  `namaz_kayit_id` int(11) NOT NULL,
  `namaz_vakti` enum('Sabah','Öğlen','İkindi','Akşam','Yatsı') NOT NULL,
  `kiminle_geldi` enum('Kendisi','Babası','Annesi','Anne-Babası') NOT NULL,
  `tarih` date NOT NULL,
  `silme_nedeni` text DEFAULT NULL,
  `silen_kullanici` varchar(50) DEFAULT NULL,
  `silme_zamani` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci` (`ogrenci_id`),
  KEY `idx_tarih` (`tarih`),
  CONSTRAINT `puan_silme_gecmisi_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;


-- --------------------------------------------------------
-- Tablo yapısı: `sertifikalar`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sertifikalar`;
CREATE TABLE `sertifikalar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ogrenci_id` int(11) NOT NULL,
  `sertifika_tipi` enum('Namaz','Ders') NOT NULL,
  `baslik` varchar(200) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `donem` varchar(50) DEFAULT NULL,
  `derece` varchar(50) DEFAULT NULL,
  `tarih` date NOT NULL,
  `dosya_adi` varchar(255) DEFAULT NULL,
  `olusturan_kullanici` varchar(50) DEFAULT NULL,
  `olusturma_zamani` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci` (`ogrenci_id`),
  KEY `idx_tip` (`sertifika_tipi`),
  CONSTRAINT `sertifikalar_ibfk_1` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Veri dökümü: `sertifikalar` (1 satır)

INSERT INTO `sertifikalar` VALUES ('1', '1', 'Namaz', 'CAMİYE GELİYORUM HEDİYEMİ ALIYORUM', '', '', '', '2025-11-01', NULL, 'mehmetuzun', '2025-11-01 10:02:56');


-- --------------------------------------------------------
-- Tablo yapısı: `yillik_ozetler`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `yillik_ozetler`;

-- --------------------------------------------------------
-- VIEW'lar
-- --------------------------------------------------------

DROP VIEW IF EXISTS `aylik_ozetler`;
CREATE ALGORITHM=UNDEFINED DEFINER=`imammehmet_system`@`localhost` SQL SECURITY DEFINER VIEW `aylik_ozetler` AS select `o`.`id` AS `ogrenci_id`,`o`.`ad_soyad` AS `ad_soyad`,year(`n`.`tarih`) AS `yil`,month(`n`.`tarih`) AS `ay`,sum(case when `n`.`kiminle_geldi` = 'Kendisi' then 1 else 0 end) AS `kendisi_sayisi`,sum(case when `n`.`kiminle_geldi` = 'Babası' then 1 else 0 end) AS `babasi_sayisi`,sum(case when `n`.`kiminle_geldi` = 'Annesi' then 1 else 0 end) AS `annesi_sayisi`,sum(case when `n`.`kiminle_geldi` = 'Anne-Babası' then 1 else 0 end) AS `anne_babasi_sayisi`,count(0) AS `toplam_namaz`,count(0) + coalesce((select sum(`ilave_puanlar`.`puan`) from `ilave_puanlar` where `ilave_puanlar`.`ogrenci_id` = `o`.`id` and year(`ilave_puanlar`.`tarih`) = year(`n`.`tarih`) and month(`ilave_puanlar`.`tarih`) = month(`n`.`tarih`)),0) AS `toplam_puan` from (`ogrenciler` `o` left join `namaz_kayitlari` `n` on(`o`.`id` = `n`.`ogrenci_id`)) group by `o`.`id`,year(`n`.`tarih`),month(`n`.`tarih`);

DROP VIEW IF EXISTS `yillik_ozetler`;
CREATE ALGORITHM=UNDEFINED DEFINER=`imammehmet_system`@`localhost` SQL SECURITY DEFINER VIEW `yillik_ozetler` AS select `o`.`id` AS `ogrenci_id`,`o`.`ad_soyad` AS `ad_soyad`,year(`n`.`tarih`) AS `yil`,sum(case when `n`.`kiminle_geldi` = 'Kendisi' then 1 else 0 end) AS `kendisi_sayisi`,sum(case when `n`.`kiminle_geldi` = 'Babası' then 1 else 0 end) AS `babasi_sayisi`,sum(case when `n`.`kiminle_geldi` = 'Annesi' then 1 else 0 end) AS `annesi_sayisi`,sum(case when `n`.`kiminle_geldi` = 'Anne-Babası' then 1 else 0 end) AS `anne_babasi_sayisi`,count(0) AS `toplam_namaz`,count(0) + coalesce((select sum(`ilave_puanlar`.`puan`) from `ilave_puanlar` where `ilave_puanlar`.`ogrenci_id` = `o`.`id` and year(`ilave_puanlar`.`tarih`) = year(`n`.`tarih`)),0) AS `toplam_puan` from (`ogrenciler` `o` left join `namaz_kayitlari` `n` on(`o`.`id` = `n`.`ogrenci_id`)) group by `o`.`id`,year(`n`.`tarih`);

SET FOREIGN_KEY_CHECKS=1;
