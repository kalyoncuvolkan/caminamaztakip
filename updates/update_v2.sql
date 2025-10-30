-- Versiyon 2.0 Güncellemeleri
-- Öğrenci Yönetimi Geliştirmeleri

USE cami_namaz_takip;

-- Öğrenci tablosuna yeni alanlar ekle
ALTER TABLE ogrenciler
ADD COLUMN aktif TINYINT(1) DEFAULT 1 COMMENT '1=Aktif, 0=Pasif',
ADD COLUMN guncelleme_tarihi TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN silinme_tarihi TIMESTAMP NULL,
ADD COLUMN silindi TINYINT(1) DEFAULT 0;

-- Puan silme geçmişi tablosu
CREATE TABLE IF NOT EXISTS puan_silme_gecmisi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT NOT NULL,
    namaz_kayit_id INT NOT NULL,
    namaz_vakti ENUM('Sabah', 'Öğlen', 'İkindi', 'Akşam', 'Yatsı') NOT NULL,
    kiminle_geldi ENUM('Kendisi', 'Babası', 'Annesi', 'Anne-Babası') NOT NULL,
    tarih DATE NOT NULL,
    silme_nedeni TEXT,
    silen_kullanici VARCHAR(50),
    silme_zamani TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
    INDEX idx_ogrenci (ogrenci_id),
    INDEX idx_tarih (tarih)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- İlave puan tablosu
CREATE TABLE IF NOT EXISTS ilave_puanlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT NOT NULL,
    puan INT NOT NULL,
    aciklama TEXT,
    veren_kullanici VARCHAR(50),
    tarih DATE NOT NULL,
    olusturma_zamani TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
    INDEX idx_ogrenci (ogrenci_id),
    INDEX idx_tarih (tarih)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Ders kategorileri tablosu
CREATE TABLE IF NOT EXISTS ders_kategorileri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_adi VARCHAR(100) NOT NULL,
    aciklama TEXT,
    aktif TINYINT(1) DEFAULT 1,
    sira INT DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_kategori_adi (kategori_adi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Dersler tablosu
CREATE TABLE IF NOT EXISTS dersler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL,
    ders_adi VARCHAR(200) NOT NULL,
    aciklama TEXT,
    puan INT DEFAULT 1,
    aktif TINYINT(1) DEFAULT 1,
    sira INT DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES ders_kategorileri(id) ON DELETE CASCADE,
    INDEX idx_kategori (kategori_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Öğrenci ders atamaları
CREATE TABLE IF NOT EXISTS ogrenci_dersler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT NOT NULL,
    ders_id INT NOT NULL,
    durum ENUM('Beklemede', 'Tamamlandi') DEFAULT 'Beklemede',
    tamamlanma_tarihi DATE NULL,
    puan_verildi TINYINT(1) DEFAULT 0,
    notlar TEXT,
    atama_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
    FOREIGN KEY (ders_id) REFERENCES dersler(id) ON DELETE CASCADE,
    UNIQUE KEY idx_ogrenci_ders (ogrenci_id, ders_id),
    INDEX idx_durum (durum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Sertifikalar tablosu
CREATE TABLE IF NOT EXISTS sertifikalar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT NOT NULL,
    sertifika_tipi ENUM('Namaz', 'Ders') NOT NULL,
    baslik VARCHAR(200) NOT NULL,
    aciklama TEXT,
    donem VARCHAR(50),
    derece VARCHAR(50),
    tarih DATE NOT NULL,
    dosya_adi VARCHAR(255),
    olusturan_kullanici VARCHAR(50),
    olusturma_zamani TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
    INDEX idx_ogrenci (ogrenci_id),
    INDEX idx_tip (sertifika_tipi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Öğrenci mesajları tablosu
CREATE TABLE IF NOT EXISTS ogrenci_mesajlari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT NOT NULL,
    mesaj TEXT NOT NULL,
    oncelik ENUM('Normal', 'Önemli', 'Acil') DEFAULT 'Normal',
    okundu TINYINT(1) DEFAULT 0,
    okunma_zamani TIMESTAMP NULL,
    gonderen_kullanici VARCHAR(50),
    gonderim_zamani TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
    INDEX idx_ogrenci (ogrenci_id),
    INDEX idx_okundu (okundu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Öğrenci login bilgileri
CREATE TABLE IF NOT EXISTS ogrenci_kullanicilar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT NOT NULL UNIQUE,
    kullanici_adi VARCHAR(50) UNIQUE NOT NULL,
    parola_hash VARCHAR(255) NOT NULL,
    aktif TINYINT(1) DEFAULT 1,
    ilk_giris TINYINT(1) DEFAULT 1,
    son_giris TIMESTAMP NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Varsayılan ders kategorilerini ekle
INSERT INTO ders_kategorileri (kategori_adi, aciklama, sira) VALUES
('Kuranı Kerim', 'Kuran-ı Kerim okuma ve ezber çalışmaları', 1),
('İlmihal', 'İslam dini ibadet ve muamelat bilgisi', 2),
('Siyer', 'Hz. Muhammed (SAV) ve İslam tarihi', 3),
('Ahlak', 'İslam ahlakı ve güzel davranışlar', 4)
ON DUPLICATE KEY UPDATE kategori_adi=kategori_adi;

-- Mevcut öğrencileri aktif yap
UPDATE ogrenciler SET aktif = 1 WHERE aktif IS NULL;