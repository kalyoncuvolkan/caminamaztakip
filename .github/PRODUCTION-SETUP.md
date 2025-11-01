# 🔧 Production Sunucu Kurulum Kılavuzu

## ⚠️ ÖNEMLİ: config/db.php Dosyası Oluşturulmalı

GitHub Actions deployment **kod dosyalarını** otomatik olarak FTP'ye yükler, ancak **config/db.php** dosyası güvenlik nedeniyle `.gitignore`'da olduğu için **yüklenmez**.

Bu nedenle production sunucunuzda **manuel olarak** `config/db.php` dosyasını oluşturmalısınız.

---

## 📝 Adım Adım Kurulum

### 1️⃣ Veritabanı Bilgilerini Bulun

**cPanel → MySQL Databases** veya hosting panelinizden:

- **Veritabanı Adı:** `imammehmet_namazogrenci` (örnek)
- **Kullanıcı Adı:** `imammehmet_namazogrenci` (örnek)
- **Şifre:** MySQL şifreniz
- **Host:** Genellikle `localhost`

---

### 2️⃣ config/db.php Dosyasını Oluşturun

**Seçenek A: FTP ile Dosya Yükleme (Önerilen)**

1. Bilgisayarınızda yeni bir dosya oluşturun: `db.php`

2. İçeriği şu şekilde düzenleyin:

```php
<?php
$host = 'localhost';
$dbname = 'imammehmet_namazogrenci';  // BURAYA KENDİ DB ADI
$username = 'imammehmet_namazogrenci';  // BURAYA KENDİ KULLANICI ADI
$password = 'BURAYA_ŞİFRENİZİ_YAZIN';  // BURAYA KENDİ ŞİFRE

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_turkish_ci'");
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

function yasHesapla($dogumTarihi) {
    $bugun = new DateTime();
    $dogum = new DateTime($dogumTarihi);
    $yas = $bugun->diff($dogum);
    return $yas->y;
}

function turkceTarih($tarih) {
    $aylar = array(
        'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart',
        'April' => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran',
        'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül',
        'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'
    );
    return strtr($tarih, $aylar);
}

function ayAdi($ay) {
    $aylar = array(
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    );
    return $aylar[$ay];
}

function siralama($sayi) {
    if ($sayi == 1) return "Birincisi";
    if ($sayi == 2) return "İkincisi";
    if ($sayi == 3) return "Üçüncüsü";
    return $sayi . ".";
}
?>
```

3. FTP ile bağlanın (`ftp.atakoycamii.com`)

4. `public_html/config/` klasörüne gidin

5. `db.php` dosyasını yükleyin

6. Dosya izinlerini **644** yapın

---

**Seçenek B: cPanel File Manager ile**

1. cPanel → File Manager

2. `public_html/config/` klasörüne gidin

3. **+ File** → Yeni dosya oluştur: `db.php`

4. Sağ tık → **Edit**

5. Yukarıdaki kodu yapıştırın (bilgilerinizle güncelleyin)

6. **Save Changes**

7. Dosyaya sağ tık → **Change Permissions** → **644**

---

### 3️⃣ Test Edin

Tarayıcıda açın:
```
https://atakoycamii.com/
```

**Başarılı:** Ana sayfa açılmalı
**Hata varsa:** `logs/` klasöründeki hata loglarını kontrol edin

---

## 🔒 Güvenlik Notları

⚠️ **ÇOK ÖNEMLİ:**
- `config/db.php` dosyası **ASLA** GitHub'a yüklenmemeli
- `.gitignore` dosyasında `config/db.php` satırı **silinmemeli**
- Şifreler güçlü olmalı
- Dosya izinleri **644** olmalı (755 DEĞİL!)

---

## 🚨 Sorun Giderme

### "Access denied for user 'root'@'localhost'"
❌ **Sorun:** config/db.php yok veya yanlış bilgiler
✅ **Çözüm:** Yukarıdaki adımları uygulayın

### "config/db.php: failed to open stream"
❌ **Sorun:** Dosya sunucuda yok
✅ **Çözüm:** FTP ile dosyayı yükleyin

### "SQLSTATE[HY000] [2002] Connection refused"
❌ **Sorun:** Host yanlış (localhost değil başka bir şey olabilir)
✅ **Çözüm:** Hosting sağlayıcınızdan MySQL host bilgisini öğrenin

---

## 📞 Yardım

Sorun devam ederse:
1. FTP ile `logs/` klasörüne bakın
2. Hata loglarını kontrol edin
3. GitHub Issues'a detaylarıyla birlikte yazın

---

**Son Güncelleme:** 01 Kasım 2025
