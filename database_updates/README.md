# Veritabanı Güncellemeleri

Bu klasör, cloud sunucuya uygulanması gereken veritabanı güncellemelerini içerir.

## 📋 Güncelleme Listesi

### 2025-11-24: Puan Şablonları
**Dosya:** `2025-11-24_puan_sablon.sql`

**Ne Ekliyor:**
- `puan_sablon` tablosu (ön tanımlı puan şablonları için)
- 8 adet örnek puan şablonu (Namaz ve Ders kategorilerinde)

**Gerekli mi:** ✅ **EVET** - puan-yonetimi.php sayfası bu tabloya ihtiyaç duyuyor

---

## 🚀 Cloud Sunucuda Nasıl Çalıştırılır?

### Yöntem 1: phpMyAdmin (Önerilen)

1. **cPanel'e giriş yapın:** `https://atakoycamii.com:2083`
2. **phpMyAdmin'i açın**
3. Sol taraftan `imammehmet_namazogrenci` veritabanını seçin
4. Üst menüden **SQL** sekmesine tıklayın
5. SQL dosyasının içeriğini kopyalayın ve yapıştırın
6. **Go/Çalıştır** butonuna tıklayın
7. Başarılı mesajını görmelisiniz

### Yöntem 2: SSH (Terminal Erişimi Varsa)

```bash
# SSH ile sunucuya bağlanın
ssh kullanici@atakoycamii.com

# SQL dosyasını yükleyin
mysql -u imammehmet_dbuser -p imammehmet_namazogrenci < 2025-11-24_puan_sablon.sql

# Şifre soracak, veritabanı şifrenizi girin
```

### Yöntem 3: cPanel MySQL Remote

1. cPanel'de **Remote MySQL** açın
2. Kendi IP adresinizi ekleyin
3. Yerel bilgisayarınızdan bağlanın:

```bash
mysql -h atakoycamii.com -u imammehmet_dbuser -p imammehmet_namazogrenci < 2025-11-24_puan_sablon.sql
```

---

## ✅ Kontrol

Güncelleme başarılı oldu mu kontrol edin:

**phpMyAdmin'de SQL sekmesinde çalıştırın:**

```sql
-- Tablo oluşturuldu mu?
SHOW TABLES LIKE 'puan_sablon';

-- Kaç şablon var?
SELECT COUNT(*) as toplam_sablon FROM puan_sablon;

-- Şablonları listele
SELECT * FROM puan_sablon ORDER BY kategori, sira;
```

Beklenen sonuç: **8 adet puan şablonu** görmeli siniz.

---

## ⚠️ Önemli Notlar

1. **Yedek Alın:** Güncelleme öncesi mutlaka veritabanı yedeği alın!
2. **Test Edin:** Güncellemeden sonra puan-yonetimi.php sayfasını test edin
3. **Hata Durumunda:** SQL hatası alırsanız, hatayı not edin ve bildirin
4. **Tekrar Çalıştırma:** SQL güvenli, birden fazla çalıştırılabilir (INSERT IGNORE kullanıyor)

---

## 📞 Destek

Sorun yaşarsanız:
- Hata mesajının ekran görüntüsünü alın
- Hangi yöntemi kullandığınızı belirtin
- Hatanın tam metnini paylaşın
