# FAZ 2 - PUANLAMA SİSTEMİ REVİZYONU

**Proje:** Cami Namaz Takip Sistemi
**Tarih:** 22 Kasım 2025
**Durum:** Planlandı - Beklemede

---

## 📋 MÜŞTERİ TALEBİ

### Mevcut Durum
Sistemde tek bir "İlave Puan" kategorisi var ve tüm puanlar burada toplanıyor.

### İstenen Durum
Puanlama sistemi **3 ana kategoriye** ayrılacak ve her kategori ayrı hesaplanacak:

1. **Namaz Puanı**
2. **Ders Puanı**
3. **Güzel Davranış Puanı**

Ayrıca, ilave puan ve ceza seçenekleri önceden tanımlanabilecek ve yönetilebilecek.

---

## 🎯 PUAN KATEGORİLERİ

### 1. Namaz Puanı
- Kendisi (tek başına gelen namaz vakitleri)
- Annesi ile gelen (bonus +1)
- Babası ile gelen (bonus +1)
- Anne-Babası ile gelen (bonus +1)
- İlave namaz puanı (yönetici tarafından verilen ek puanlar)
- **Toplam Namaz Puanı** = Tüm yukarıdakiler toplamı

### 2. Ders Puanı
- Tamamlanan derslerden otomatik alınan puan
- İlave ders puanı (yönetici tarafından verilen ek puanlar)
- **Toplam Ders Puanı** = Tüm yukarıdakiler toplamı

### 3. Güzel Davranış Puanı
- Önceden tanımlı seçeneklerden verilen ödüller
- Örnek: "Camiye erken geldi (+5)", "Abdest aldırdı (+3)", vs.
- **Toplam Güzel Davranış Puanı** = Tüm ödüller toplamı

---

## ⚠️ CEZA KATEGORİLERİ

### 1. Namaz Cezası
- Öğrenciden silinen namaz kayıtları
- Her silinen namaz = -1 puan (veya bonus varsa daha fazla)

### 2. Ders Cezası
- Öğrenciye atanmış dersi tekrar aktif ettiğimizde
- Örnek: Ders tamamlandı ama geri alındı = eksi puan

### 3. Kötü Davranış Cezası
- Önceden tanımlı seçeneklerden verilen cezalar
- Örnek: "Camide gürültü yaptı (-5)", "Kavga etti (-10)", vs.
- **Toplam Ceza Puanı** = Tüm cezalar toplamı

---

## 🗄️ VERİTABANI TASARIMI

### Yeni Tablolar

#### 1. `puan_secenekleri` - İlave Puan Seçenekleri
Yöneticinin önceden tanımlayacağı puan seçenekleri.

```sql
CREATE TABLE puan_secenekleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori ENUM('Namaz', 'Ders', 'Guzel_Davranis') NOT NULL,
    baslik VARCHAR(200) NOT NULL,
    puan INT NOT NULL,
    aktif TINYINT(1) DEFAULT 1,
    aciklama TEXT,
    olusturan_kullanici VARCHAR(50),
    olusturma_zamani TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kategori (kategori),
    INDEX idx_aktif (aktif)
);
```

**Örnek Veriler:**
```sql
INSERT INTO puan_secenekleri (kategori, baslik, puan) VALUES
('Guzel_Davranis', 'Camiye erken geldi', 5),
('Guzel_Davranis', 'Abdest aldırdı', 3),
('Guzel_Davranis', 'Caminin temizliğine yardım etti', 10),
('Namaz', 'Teravih namazına geldi', 2),
('Ders', 'Sınıf birincisi oldu', 50);
```

---

#### 2. `ceza_secenekleri` - Ceza Seçenekleri
Yöneticinin önceden tanımlayacağı ceza seçenekleri.

```sql
CREATE TABLE ceza_secenekleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori ENUM('Namaz', 'Ders', 'Kotu_Davranis') NOT NULL,
    baslik VARCHAR(200) NOT NULL,
    ceza_puani INT NOT NULL,
    aktif TINYINT(1) DEFAULT 1,
    aciklama TEXT,
    olusturan_kullanici VARCHAR(50),
    olusturma_zamani TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kategori (kategori),
    INDEX idx_aktif (aktif)
);
```

**Örnek Veriler:**
```sql
INSERT INTO ceza_secenekleri (kategori, baslik, ceza_puani) VALUES
('Kotu_Davranis', 'Camide gürültü yaptı', 5),
('Kotu_Davranis', 'Kavga etti', 10),
('Kotu_Davranis', 'Zamanında gelmedi', 3),
('Namaz', 'Yalan söyledi (gelmedi ama geldi dedi)', 10),
('Ders', 'Derse katılmadı', 5);
```

---

#### 3. `cezalar` - Verilen Cezalar
Öğrencilere verilen tüm cezaların kaydı.

```sql
CREATE TABLE cezalar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT NOT NULL,
    kategori ENUM('Namaz', 'Ders', 'Kotu_Davranis') NOT NULL,
    secenek_id INT NULL,
    ceza_puani INT NOT NULL,
    aciklama TEXT,
    veren_kullanici VARCHAR(50),
    tarih DATE NOT NULL,
    olusturma_zamani TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
    FOREIGN KEY (secenek_id) REFERENCES ceza_secenekleri(id) ON DELETE SET NULL,
    INDEX idx_ogrenci (ogrenci_id),
    INDEX idx_kategori (kategori),
    INDEX idx_tarih (tarih)
);
```

---

#### 4. Mevcut `ilave_puanlar` Tablosunu Güncelle

**Kategori ENUM'unu genişlet:**
```sql
ALTER TABLE ilave_puanlar
MODIFY COLUMN kategori ENUM('Namaz', 'Ders', 'Guzel_Davranis') NOT NULL DEFAULT 'Namaz';
```

**Seçenek referansı ekle:**
```sql
ALTER TABLE ilave_puanlar
ADD COLUMN secenek_id INT NULL AFTER kategori,
ADD FOREIGN KEY (secenek_id) REFERENCES puan_secenekleri(id) ON DELETE SET NULL;
```

---

## 📄 YENİ SAYFALAR

### 1. `puan-secenekleri.php`
**Amaç:** İlave puan seçeneklerini yönetme

**Özellikler:**
- Yeni puan seçeneği ekleme
- Mevcut seçenekleri listeleme
- Seçenek düzenleme (başlık, puan, kategori)
- Seçenek silme
- Aktif/Pasif yapma

**Form Alanları:**
- Kategori: Namaz / Ders / Güzel Davranış
- Başlık: "Camiye erken geldi"
- Puan: +5
- Açıklama: (opsiyonel)

---

### 2. `ceza-secenekleri.php`
**Amaç:** Ceza seçeneklerini yönetme

**Özellikler:**
- Yeni ceza seçeneği ekleme
- Mevcut cezaları listeleme
- Ceza düzenleme (başlık, puan, kategori)
- Ceza silme
- Aktif/Pasif yapma

**Form Alanları:**
- Kategori: Namaz / Ders / Kötü Davranış
- Başlık: "Camide gürültü yaptı"
- Ceza Puanı: -5
- Açıklama: (opsiyonel)

---

### 3. `ilave-puan-ver.php`
**Amaç:** Öğrencilere ilave puan verme (yeni arayüz)

**Özellikler:**
- Öğrenci seçimi
- Kategori seçimi (Namaz/Ders/Güzel Davranış)
- Önceden tanımlı seçeneklerden seçim (dropdown)
- Tarih seçimi
- Ek açıklama (opsiyonel)

**Akış:**
1. Öğrenci seç
2. Kategori seç → İlgili kategorinin seçenekleri yüklensin
3. Seçenek seç → Otomatik puan gelsin
4. Tarih seç
5. Kaydet

---

### 4. `ceza-ver.php`
**Amaç:** Öğrencilere ceza verme (yeni arayüz)

**Özellikler:**
- Öğrenci seçimi
- Kategori seçimi (Namaz/Ders/Kötü Davranış)
- Önceden tanımlı ceza seçeneklerinden seçim
- Tarih seçimi
- Ek açıklama (zorunlu - ceza nedeni)

**Akış:**
1. Öğrenci seç
2. Kategori seç → İlgili kategorinin cezaları yüklensin
3. Ceza seç → Otomatik ceza puanı gelsin
4. Tarih seç
5. Açıklama yaz (zorunlu)
6. Kaydet

---

### 5. `hediye-hesapla.php`
**Amaç:** Öğrencilerin puan kategorilerine göre hediye değerini hesaplama

**Özellikler:**
- Dönem/Ay seçimi
- Puan türü seçimi:
  - Namaz Puanı (Kendisi)
  - Namaz Puanı (Annesi ile)
  - Namaz Puanı (Babası ile)
  - Namaz Puanı (Anne-Babası ile)
  - Toplam Namaz Puanı
  - Ders Puanı
  - Güzel Davranış Puanı
  - Toplam Puan
- Puan başına fiyat girişi (örn: 10 TL)
- Tüm öğrenciler için hesaplama
- Yazdırma özelliği

**Hesaplama Mantığı:**
```
Hediye Değeri = Öğrencinin Seçilen Puan Türü × Puan Başına Fiyat
```

**Örnek:**
- Ahmet'in Toplam Namaz Puanı: 120
- Puan Başına: 10 TL
- Hediye Değeri: 120 × 10 = 1,200 TL

---

## 🔄 GÜNCELLENECEK SAYFALAR

### 1. `puan-yonetimi.php` (Mevcut)
**Değişiklik:** Kategori bazlı puan verme/görüntüleme

**Yeni Özellikler:**
- 3 kategori sekmesi (Namaz/Ders/Güzel Davranış)
- Her kategoride ayrı puan listesi
- Kategori bazlı filtreleme

---

### 2. `genel-rapor.php` (Mevcut)
**Değişiklik:** Puan kategorilerine göre detaylı gösterim

**Yeni Kolonlar:**
- Namaz Puanı
- Ders Puanı
- Güzel Davranış Puanı
- Ceza Puanı
- Net Puan (Toplam - Ceza)

**Detay Gösterimi:**
- Her kategorinin detayına tıklayınca alt detaylar açılsın
- Örnek: Namaz Puanı → Kendisi: 50, Annesi: 10, Babası: 15, İlave: 5

---

### 3. `ozel-rapor.php` (Mevcut)
**Değişiklik:** Kategori bazlı puan gösterimi

**Yeni Bölümler:**
- **Namaz Puanları Özeti**
  - Tek Başına: 50 puan
  - Annesi ile: 10 puan
  - Babası ile: 15 puan
  - Anne-Babası ile: 5 puan
  - İlave Namaz Puanı: 5 puan
  - **Toplam Namaz Puanı:** 85 puan

- **Ders Puanları Özeti**
  - Tamamlanan Dersler: 30 puan
  - İlave Ders Puanı: 10 puan
  - **Toplam Ders Puanı:** 40 puan

- **Güzel Davranış Puanları**
  - Liste halinde (tarih, açıklama, puan)
  - **Toplam Güzel Davranış Puanı:** 25 puan

- **Cezalar**
  - Liste halinde (kategori, tarih, açıklama, ceza)
  - **Toplam Ceza Puanı:** -15 puan

- **GENEL TOPLAM:** 135 puan

---

### 4. `ogrenci-panel/index.php` (Mevcut)
**Değişiklik:** Öğrenci panelinde kategori gösterimi

**İstatistik Kartları:**
- 🕌 Namaz Puanım: 85
- 📚 Ders Puanım: 40
- ⭐ Güzel Davranış Puanım: 25
- ⚠️ Ceza Puanım: -15
- 🏆 Toplam Puanım: 135

---

### 5. `ogrenci-panel/raporlarim.php` (Mevcut)
**Değişiklik:** Kategori bazlı detaylı raporlar

**Sekmeler:**
- Genel Özet (tüm kategoriler)
- Namaz Raporlarım
- Ders Raporlarım
- Ödül ve Cezalarım

---

## 📊 HESAPLAMA MANTIĞI

### Namaz Puanı Hesaplama
```
NAMAZ_VAKIT_PUANI = (Tek başına gelen vakit sayısı × 1) +
                     (Annesi ile gelen vakit sayısı × 2) +  // 1 vakit + 1 bonus
                     (Babası ile gelen vakit sayısı × 2) +
                     (Anne-Babası ile gelen × 2)

ILAVE_NAMAZ_PUANI = SUM(ilave_puanlar WHERE kategori='Namaz')

NAMAZ_CEZASI = SUM(cezalar WHERE kategori='Namaz') +
                SUM(puan_silme_gecmisi WHERE kategori='Namaz')

TOPLAM_NAMAZ_PUANI = NAMAZ_VAKIT_PUANI + ILAVE_NAMAZ_PUANI - NAMAZ_CEZASI
```

---

### Ders Puanı Hesaplama
```
DERS_TAMAMLAMA_PUANI = SUM(ogrenci_dersler WHERE durum='Tamamlandi')

ILAVE_DERS_PUANI = SUM(ilave_puanlar WHERE kategori='Ders')

DERS_CEZASI = SUM(cezalar WHERE kategori='Ders')

TOPLAM_DERS_PUANI = DERS_TAMAMLAMA_PUANI + ILAVE_DERS_PUANI - DERS_CEZASI
```

---

### Güzel Davranış Puanı Hesaplama
```
GUZEL_DAVRANIS_PUANI = SUM(ilave_puanlar WHERE kategori='Guzel_Davranis')

KOTU_DAVRANIS_CEZASI = SUM(cezalar WHERE kategori='Kotu_Davranis')

TOPLAM_GUZEL_DAVRANIS_PUANI = GUZEL_DAVRANIS_PUANI - KOTU_DAVRANIS_CEZASI
```

---

### Genel Toplam
```
TOPLAM_PUAN = TOPLAM_NAMAZ_PUANI +
              TOPLAM_DERS_PUANI +
              TOPLAM_GUZEL_DAVRANIS_PUANI
```

---

## 🎁 HEDİYE HESAPLAMA SİSTEMİ

### Seçenekler
Yönetici hediye hesaplarken şu kategorilerden birini seçer:

**Namaz Kategorisi:**
- Tek Başına Gelen Vakit Sayısı
- Annesi ile Gelen Vakit Sayısı
- Babası ile Gelen Vakit Sayısı
- Anne-Babası ile Gelen Vakit Sayısı
- Toplam Namaz Puanı

**Diğer Kategoriler:**
- Ders Puanı
- Güzel Davranış Puanı
- Toplam Puan (Her şey dahil)

### Hesaplama Formülü
```
Hediye Değeri = Öğrencinin Seçilen Kategorideki Puanı × Puan Başına Fiyat
```

### Örnek Senaryo

**Ayarlar:**
- Dönem: Ocak 2025
- Puan Türü: Toplam Namaz Puanı
- Puan Başına: 10 TL

**Öğrenciler:**
| Öğrenci | Namaz Puanı | Hediye Değeri |
|---------|-------------|---------------|
| Ahmet   | 120         | 1,200 TL      |
| Mehmet  | 95          | 950 TL        |
| Ali     | 150         | 1,500 TL      |

**Yazdırma Çıktısı:**
```
OCAK 2025 - HEDİYE LİSTESİ
Hesaplama Türü: Toplam Namaz Puanı
Puan Başına: 10 TL

1. Ahmet YILMAZ      120 puan × 10 TL = 1,200 TL
2. Mehmet KAYA        95 puan × 10 TL = 950 TL
3. Ali DEMİR         150 puan × 10 TL = 1,500 TL
---------------------------------------------------
TOPLAM:                              3,650 TL
```

---

## 📱 KULLANICI ARAYÜZÜ

### Navigation Menüsü Güncellemesi

**Mevcut Durum:**
```
Ana Sayfa | Öğrenciler | Namaz | Dersler | Puan Yönetimi | Raporlar | Sertifikalar
```

**Yeni Durum:**
```
Ana Sayfa | Öğrenciler | Namaz | Dersler | Puanlama ▼ | Raporlar | Sertifikalar

Puanlama Alt Menüsü:
  - Puan Seçenekleri
  - Ceza Seçenekleri
  - İlave Puan Ver
  - Ceza Ver
  - Hediye Hesapla
```

---

## 🔐 YETKİLENDİRME

**Sadece Yöneticiler:**
- Puan/Ceza seçeneklerini yönetebilir
- İlave puan verebilir
- Ceza verebilir
- Hediye hesaplama yapabilir

**Öğrenciler:**
- Sadece kendi puanlarını görebilir
- Kategori bazlı detayları görebilir
- Ceza geçmişini görebilir

---

## ⚙️ TEKNİK DETAYLAR

### API Endpoints (Yeni)

1. **`api/puan-secenek-ekle.php`** - Puan seçeneği ekleme
2. **`api/puan-secenek-sil.php`** - Puan seçeneği silme
3. **`api/puan-secenek-guncelle.php`** - Puan seçeneği güncelleme
4. **`api/ceza-secenek-ekle.php`** - Ceza seçeneği ekleme
5. **`api/ceza-secenek-sil.php`** - Ceza seçeneği silme
6. **`api/ceza-secenek-guncelle.php`** - Ceza seçeneği güncelleme
7. **`api/ilave-puan-ver.php`** - İlave puan verme
8. **`api/ceza-ver.php`** - Ceza verme
9. **`api/hediye-hesapla.php`** - Hediye hesaplama

### VIEW'ler (Veritabanı)

Raporlama için performans optimize edilmiş VIEW'ler:

```sql
CREATE VIEW ogrenci_puan_detay AS
SELECT
    o.id as ogrenci_id,
    o.ad_soyad,

    -- Namaz Puanları
    COALESCE(SUM(CASE WHEN n.kiminle_geldi='Kendisi' THEN 1 ELSE 0 END), 0) as namaz_kendisi,
    COALESCE(SUM(CASE WHEN n.kiminle_geldi='Annesi' THEN 2 ELSE 0 END), 0) as namaz_annesi,
    COALESCE(SUM(CASE WHEN n.kiminle_geldi='Babası' THEN 2 ELSE 0 END), 0) as namaz_babasi,
    COALESCE(SUM(CASE WHEN n.kiminle_geldi='Anne-Babası' THEN 2 ELSE 0 END), 0) as namaz_anne_babasi,
    COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id=o.id AND kategori='Namaz'), 0) as ilave_namaz,
    COALESCE((SELECT SUM(ceza_puani) FROM cezalar WHERE ogrenci_id=o.id AND kategori='Namaz'), 0) as namaz_ceza,

    -- Ders Puanları
    COALESCE((SELECT COUNT(*) FROM ogrenci_dersler WHERE ogrenci_id=o.id AND durum='Tamamlandi'), 0) as ders_tamamlanan,
    COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id=o.id AND kategori='Ders'), 0) as ilave_ders,
    COALESCE((SELECT SUM(ceza_puani) FROM cezalar WHERE ogrenci_id=o.id AND kategori='Ders'), 0) as ders_ceza,

    -- Güzel Davranış Puanları
    COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id=o.id AND kategori='Guzel_Davranis'), 0) as guzel_davranis,
    COALESCE((SELECT SUM(ceza_puani) FROM cezalar WHERE ogrenci_id=o.id AND kategori='Kotu_Davranis'), 0) as kotu_davranis_ceza

FROM ogrenciler o
LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id
WHERE o.aktif = 1
GROUP BY o.id, o.ad_soyad;
```

---

## 📝 MIGRATION PLANI

### Adım 1: Veritabanı Güncellemeleri
1. Yeni tabloları oluştur (`puan_secenekleri`, `ceza_secenekleri`, `cezalar`)
2. `ilave_puanlar` tablosunu güncelle
3. VIEW'leri oluştur
4. Test verileri ekle

### Adım 2: Backend Sayfaları
1. Puan seçenekleri yönetim sayfası
2. Ceza seçenekleri yönetim sayfası
3. İlave puan verme sayfası
4. Ceza verme sayfası
5. Hediye hesaplama sayfası

### Adım 3: API Endpoint'leri
1. Tüm CRUD işlemleri için API'ler
2. JSON response standardizasyonu
3. Error handling

### Adım 4: Mevcut Sayfaları Güncelle
1. `puan-yonetimi.php` → Kategori bazlı yapı
2. `genel-rapor.php` → Yeni kolonlar ekle
3. `ozel-rapor.php` → Kategori detayları
4. Öğrenci paneli sayfaları

### Adım 5: Test
1. Birim testler (her kategori ayrı)
2. Entegrasyon testler (toplam puan hesaplama)
3. Kullanıcı kabul testleri
4. Performans testleri (VIEW'lerin hızı)

---

## ⏱️ TAHMİNİ SÜRE

| Aşama | Süre | Dosya Sayısı |
|-------|------|--------------|
| Veritabanı | 2 saat | 1 migration dosyası |
| Yeni Sayfalar | 8 saat | 5 sayfa |
| API'ler | 4 saat | 9 endpoint |
| Güncelleme | 6 saat | 8 sayfa |
| Test & Debug | 4 saat | - |
| **TOPLAM** | **24 saat** | **23 dosya** |

---

## ✅ KONTROL LİSTESİ

### Veritabanı
- [ ] `puan_secenekleri` tablosu oluşturuldu
- [ ] `ceza_secenekleri` tablosu oluşturuldu
- [ ] `cezalar` tablosu oluşturuldu
- [ ] `ilave_puanlar` tablosu güncellendi
- [ ] VIEW'ler oluşturuldu
- [ ] Test verileri eklendi

### Yeni Sayfalar
- [ ] `puan-secenekleri.php` oluşturuldu
- [ ] `ceza-secenekleri.php` oluşturuldu
- [ ] `ilave-puan-ver.php` oluşturuldu
- [ ] `ceza-ver.php` oluşturuldu
- [ ] `hediye-hesapla.php` oluşturuldu

### API Endpoint'leri
- [ ] Puan seçenek CRUD API'leri
- [ ] Ceza seçenek CRUD API'leri
- [ ] İlave puan verme API
- [ ] Ceza verme API
- [ ] Hediye hesaplama API

### Güncellenen Sayfalar
- [ ] `puan-yonetimi.php` kategori yapısı
- [ ] `genel-rapor.php` yeni kolonlar
- [ ] `ozel-rapor.php` kategori detayları
- [ ] `ogrenci-panel/index.php` kategori kartları
- [ ] `ogrenci-panel/raporlarim.php` kategori raporları

### Test
- [ ] Birim testler
- [ ] Entegrasyon testler
- [ ] Kullanıcı kabul testi
- [ ] Performans testi

---

## 📞 MÜŞTERİ İLETİŞİMİ

**Onay Bekleyen Konular:**
- [ ] Ders tamamlama puanı kaç olacak? (Şu an her ders 1 puan)
- [ ] Namaz silme cezası otomatik mi yoksa manuel mi? (Şu an otomatik -1)
- [ ] Hediye hesaplama yazdırma formatı nasıl olsun? (PDF/Excel/HTML?)
- [ ] Öğrenci panelinde cezaları gösterelim mi? (Şu an gösteriliyor)

**Teyit Edilecek Konular:**
- [ ] İlave puan seçenekleri aktif/pasif olabilecek mi?
- [ ] Eski puan kayıtları nasıl migrate olacak? (Hepsi "Namaz" kategorisinde mi kalacak?)
- [ ] Ceza seçenekleri silinebilir mi yoksa sadece pasif yapılabilir mi?

---

## 🚀 GELİŞİM AŞAMALARI

### Faz 2.1 - Temel Yapı (8 saat)
- Veritabanı tablolarını oluştur
- Puan/Ceza seçenekleri yönetim sayfaları
- Temel CRUD işlemleri

### Faz 2.2 - İşlevsellik (8 saat)
- İlave puan/ceza verme sayfaları
- Kategori bazlı puan hesaplama
- API endpoint'leri

### Faz 2.3 - Raporlama (6 saat)
- Mevcut sayfaları güncelle
- Kategori bazlı detaylı raporlar
- Öğrenci paneli güncellemeleri

### Faz 2.4 - Hediye Sistemi (2 saat)
- Hediye hesaplama sayfası
- Yazdırma formatı
- Excel/PDF export

---

## 📌 NOTLAR

1. **Geriye Dönük Uyumluluk:** Mevcut `ilave_puanlar` tablosundaki kayıtlar varsayılan olarak "Namaz" kategorisinde kalacak.

2. **Performans:** VIEW'ler raporlama sorgularını hızlandıracak ancak veri güncellemelerinde VIEW'lerin de güncellenmesi gerekecek.

3. **Ölçeklenebilirlik:** Kategori sistemi ENUM olarak tasarlandı. İleride yeni kategori eklemek için ALTER TABLE gerekecek.

4. **Yedekleme:** Bu kadar büyük bir değişiklik öncesi mutlaka veritabanı yedeği alınmalı.

5. **Test Ortamı:** Prod'a geçmeden önce staging ortamında tam test yapılmalı.

---

## 📄 EK DÖKÜMANLAR

- [ ] API Dokümantasyonu
- [ ] Veritabanı ER Diyagramı
- [ ] Kullanıcı Kılavuzu
- [ ] Admin Eğitim Dokümanı
- [ ] Migration Script

---

**Son Güncelleme:** 22 Kasım 2025
**Durum:** Müşteri onayı bekleniyor
**Versiyon:** 1.0
