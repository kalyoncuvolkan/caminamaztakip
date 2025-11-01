# 🚀 Deployment Kılavuzu

Bu proje GitHub Actions kullanarak FTP üzerinden otomatik deploy edilmektedir.

## 📋 Gereksinimler

GitHub repository'nizde aşağıdaki **Secrets**'ların tanımlı olması gerekir:

### Secrets Ayarları
Repository → Settings → Secrets and variables → Actions → New repository secret

| Secret Adı | Açıklama | Örnek |
|------------|----------|-------|
| `FTP_SERVER` | FTP sunucu adresi | `ftp.example.com` veya `192.168.1.100` |
| `FTP_USERNAME` | FTP kullanıcı adı | `username@example.com` |
| `FTP_PASSWORD` | FTP şifresi | `your-secure-password` |

## 🔧 Deployment Nasıl Çalışır?

### Otomatik Deployment
1. `main` branch'e kod push edildiğinde
2. GitHub Actions otomatik olarak tetiklenir
3. Kod FTP sunucusuna yüklenir
4. `/public_html/` klasörüne deploy edilir

### Manuel Deployment
GitHub repository'de:
1. **Actions** sekmesine gidin
2. **FTP Deploy to Production** workflow'unu seçin
3. **Run workflow** butonuna tıklayın
4. Branch seçin (varsayılan: main)
5. **Run workflow** ile başlatın

## 📁 Yüklenmeyen Dosyalar

Aşağıdaki dosyalar/klasörler sunucuya **yüklenmez**:

```
.git/
.github/
node_modules/
vendor/
.env.local
.gitignore
README.md
composer.json
package.json
```

## ⚙️ Özelleştirme

### Hedef Klasörü Değiştirme
`.github/workflows/deploy.yml` dosyasında:

```yaml
server-dir: /public_html/  # Burası değiştirilebilir
```

### Exclude Listesine Ekleme
Daha fazla dosya eklemek için:

```yaml
exclude: |
  **/.git*
  **/your-folder/**
  **/your-file.txt
```

## 🔍 Deployment Logları

Deployment durumunu kontrol etmek için:
1. GitHub repository → **Actions** sekmesi
2. Son deployment'ı seçin
3. Her adımın detaylarını görün

## ⚠️ Önemli Notlar

### Güvenlik
- ❌ **ASLA** `config/db.php` gibi hassas dosyaları GitHub'a yüklemeyin
- ✅ `.env` dosyalarını `.gitignore`'a ekleyin
- ✅ FTP şifrelerini sadece GitHub Secrets'ta saklayın

### Database
- ❌ Veritabanı dosyaları otomatik deploy **edilmez**
- ✅ Migration'lar manuel çalıştırılmalıdır
- ✅ İlk kurulumda `install_schema.sql` çalıştırın

### İlk Kurulum
Sunucuya ilk deploy'dan sonra:

```bash
# 1. Veritabanını oluştur
mysql -u root -p -e "CREATE DATABASE cami_namaz_takip"

# 2. Schema'yı yükle
mysql -u root -p cami_namaz_takip < install_schema.sql

# 3. Migration'ları uygula
mysql -u root -p cami_namaz_takip < migrations/v2.1_ders_puan_revize.sql
mysql -u root -p cami_namaz_takip < migrations/v2.2_view_toplam_puan.sql
mysql -u root -p cami_namaz_takip < migrations/v2.3_ders_silme_gecmisi.sql

# 4. config/db.php dosyasını düzenle (sunucu bilgileriyle)
```

## 🐛 Sorun Giderme

### Deployment Başarısız
1. **Secrets**'ları kontrol edin
2. FTP sunucu erişimini test edin
3. Hedef klasör iznini kontrol edin
4. GitHub Actions loglarına bakın

### Dosyalar Yüklenmiyor
1. `.gitignore` dosyasını kontrol edin
2. `exclude` listesini kontrol edin
3. Dosya boyutunu kontrol edin (max 100MB)

### Sunucuda 500 Hatası
1. PHP version kontrolü (min. 7.4)
2. Database connection kontrolü
3. File permissions kontrolü (755/644)
4. Migration'ların uygulanıp uygulanmadığını kontrol edin

## 📞 Destek

Sorun yaşarsanız:
1. GitHub Issues'a bakın
2. Actions loglarını kontrol edin
3. Yeni issue açın

---

**Son Güncelleme:** 2025-11-01
