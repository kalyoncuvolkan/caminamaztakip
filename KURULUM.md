# 🛠️ Detaylı Kurulum Kılavuzu

Bu döküman Cami Namaz Takip Programı'nın adım adım kurulumunu açıklar.

## 📋 Sistem Gereksinimleri

### Minimum Gereksinimler
- **PHP**: 7.4 veya üzeri (önerilen: 8.1+)
- **MySQL**: 5.7 veya üzeri (önerilen: 8.0+)
- **Web Sunucu**: Apache 2.4+ veya Nginx 1.18+
- **RAM**: 512MB (önerilen: 1GB+)
- **Disk Alanı**: 100MB

### PHP Eklentileri
```bash
php-mysql
php-pdo
php-json
php-mbstring
php-xml
```

## 🚀 Kurulum Seçenekleri

### Seçenek 1: XAMPP ile Kurulum (Windows/macOS/Linux)

1. **XAMPP İndirin ve Kurun**
   ```
   https://www.apachefriends.org/download.html
   ```

2. **XAMPP'ı Başlatın**
   - Apache ve MySQL servislerini açın

3. **Projeyi Kopyalayın**
   ```bash
   # Windows
   C:\xampp\htdocs\cami\

   # macOS
   /Applications/XAMPP/htdocs/cami/

   # Linux
   /opt/lampp/htdocs/cami/
   ```

4. **Veritabanını Oluşturun**
   - Tarayıcıda `http://localhost/phpmyadmin` açın
   - "Yeni" tıklayarak `cami_namaz_takip` adında veritabanı oluşturun
   - UTF8_turkish_ci karakter setini seçin

5. **SQL Dosyasını İçe Aktarın**
   - Oluşturduğunuz veritabanını seçin
   - "İçe Aktar" sekmesine gidin
   - `database.sql` dosyasını seçin ve çalıştırın

### Seçenek 2: Linux Sunucu Kurulumu

1. **Gerekli Paketleri Kurun**
   ```bash
   # Ubuntu/Debian
   sudo apt update
   sudo apt install apache2 mysql-server php php-mysql php-pdo php-json php-mbstring

   # CentOS/RHEL
   sudo yum install httpd mysql-server php php-mysql php-pdo php-json php-mbstring
   ```

2. **MySQL'i Yapılandırın**
   ```bash
   sudo mysql_secure_installation
   
   # MySQL'e giriş yapın
   sudo mysql -u root -p
   
   # Veritabanını oluşturun
   CREATE DATABASE cami_namaz_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
   CREATE USER 'cami_user'@'localhost' IDENTIFIED BY 'güçlü_parola';
   GRANT ALL PRIVILEGES ON cami_namaz_takip.* TO 'cami_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

3. **Projeyi Yerleştirin**
   ```bash
   cd /var/www/html
   sudo git clone https://github.com/kalyoncuvolkan/caminamaztakip.git cami
   sudo chown -R www-data:www-data cami/
   sudo chmod -R 755 cami/
   ```

4. **Veritabanını İçe Aktarın**
   ```bash
   mysql -u cami_user -p cami_namaz_takip < /var/www/html/cami/database.sql
   ```

### Seçenek 3: Docker ile Kurulum

1. **Docker Compose Dosyası**
   ```yaml
   version: '3.8'
   services:
     web:
       image: php:8.1-apache
       ports:
         - "8080:80"
       volumes:
         - ./:/var/www/html
       depends_on:
         - db
       environment:
         - DB_HOST=db
         - DB_NAME=cami_namaz_takip
         - DB_USER=root
         - DB_PASS=rootpassword

     db:
       image: mysql:8.0
       environment:
         - MYSQL_ROOT_PASSWORD=rootpassword
         - MYSQL_DATABASE=cami_namaz_takip
       volumes:
         - db_data:/var/lib/mysql

   volumes:
     db_data:
   ```

2. **Çalıştır**
   ```bash
   docker-compose up -d
   ```

## ⚙️ Yapılandırma

### 1. Veritabanı Ayarları

`config/db.php` dosyasını düzenleyin:

```php
<?php
$host = 'localhost';           // Veritabanı sunucu adresi
$dbname = 'cami_namaz_takip';  // Veritabanı adı
$username = 'root';            // Kullanıcı adı
$password = '';                // Parola
```

### 2. İlk Kullanıcıyı Oluşturun

```bash
php create_users.php
```

Bu komut varsayılan yönetici kullanıcısını oluşturur.

### 3. Test Verilerini Ekleyin (Opsiyonel)

```bash
php test_data.php
```

Bu komut örnek öğrenciler ve namaz kayıtları ekler.

## 🔧 Gelişmiş Yapılandırma

### Apache Virtual Host

`/etc/apache2/sites-available/cami.conf`:

```apache
<VirtualHost *:80>
    ServerName cami.local
    DocumentRoot /var/www/html/cami
    
    <Directory /var/www/html/cami>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/cami_error.log
    CustomLog ${APACHE_LOG_DIR}/cami_access.log combined
</VirtualHost>
```

Etkinleştirin:
```bash
sudo a2ensite cami.conf
sudo systemctl reload apache2
```

### Nginx Konfigürasyonu

`/etc/nginx/sites-available/cami`:

```nginx
server {
    listen 80;
    server_name cami.local;
    root /var/www/html/cami;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### SSL Sertifikası (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

## 🔒 Güvenlik Yapılandırması

### 1. Dosya İzinleri

```bash
# Dosya sahipliği
sudo chown -R www-data:www-data /var/www/html/cami/

# Dizin izinleri
sudo find /var/www/html/cami/ -type d -exec chmod 755 {} \;

# Dosya izinleri
sudo find /var/www/html/cami/ -type f -exec chmod 644 {} \;

# Konfigürasyon dosyaları
sudo chmod 600 /var/www/html/cami/config/db.php
```

### 2. MySQL Güvenliği

```sql
-- Gereksiz kullanıcıları silin
DROP USER ''@'localhost';
DROP USER ''@'hostname';

-- Test veritabanını silin
DROP DATABASE IF EXISTS test;

-- Root uzaktan erişimini kısıtlayın
UPDATE mysql.user SET Host='localhost' WHERE User='root';
FLUSH PRIVILEGES;
```

### 3. PHP Güvenlik Ayarları

`php.ini` düzenlemeleri:

```ini
; Hata gösterimini kapatın (production)
display_errors = Off
log_errors = On

; Güvenlik ayarları
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; Session güvenliği
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_only_cookies = 1
```

## 🧪 Test ve Doğrulama

### 1. Kurulum Testi

```bash
# PHP versiyonu kontrolü
php -v

# MySQL bağlantısı testi
mysql -u cami_user -p -e "SELECT 1"

# Web sunucu testi
curl -I http://localhost/cami/
```

### 2. Fonksiyon Testleri

1. **Giriş Testi**
   - `http://localhost/cami/login.php` adresine gidin
   - Varsayılan kullanıcı bilgileriyle giriş yapın

2. **Öğrenci Ekleme Testi**
   - Yeni öğrenci ekleyin
   - Bilgilerin doğru kaydedildiğini kontrol edin

3. **Namaz Kayıt Testi**
   - Wizard ile namaz kaydı yapın
   - Puanlamanın doğru çalıştığını kontrol edin

4. **Rapor Testi**
   - Genel ve özel raporları kontrol edin
   - Excel export özelliğini test edin

## 🆘 Sorun Giderme

### Yaygın Sorunlar

1. **"Database connection failed"**
   ```bash
   # MySQL servisini kontrol edin
   sudo systemctl status mysql
   
   # config/db.php ayarlarını kontrol edin
   # Kullanıcı adı ve parolayı doğrulayın
   ```

2. **"Permission denied" hatası**
   ```bash
   # Dosya izinlerini düzeltin
   sudo chown -R www-data:www-data /var/www/html/cami/
   sudo chmod -R 755 /var/www/html/cami/
   ```

3. **Session sorunları**
   ```bash
   # PHP session dizinini kontrol edin
   ls -la /var/lib/php/sessions/
   
   # Session ayarlarını kontrol edin
   php -i | grep session
   ```

4. **"Class not found" hatası**
   ```bash
   # PHP eklentilerini kontrol edin
   php -m | grep -E 'pdo|mysql'
   
   # Eksik eklentileri kurun
   sudo apt install php-mysql php-pdo
   ```

### Log Dosyaları

```bash
# Apache hata logları
tail -f /var/log/apache2/error.log

# MySQL logları
tail -f /var/log/mysql/error.log

# PHP hata logları
tail -f /var/log/apache2/error.log
```

## 📈 Performans Optimizasyonu

### 1. MySQL Optimizasyonu

```sql
-- İndeks optimizasyonu
OPTIMIZE TABLE ogrenciler, namaz_kayitlari;

-- Query cache (MySQL 5.7 ve öncesi)
SET GLOBAL query_cache_size = 268435456;
```

### 2. PHP Optimizasyonu

```ini
; OPcache aktifleştir
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000

; Memory limit
memory_limit = 256M
```

### 3. Apache/Nginx Optimizasyonu

```apache
# Apache mod_deflate
LoadModule deflate_module modules/mod_deflate.so
<Location />
    SetOutputFilter DEFLATE
</Location>

# Gzip compression
LoadModule headers_module modules/mod_headers.so
Header append Vary User-Agent env=!dont-vary
```

## 🔄 Güncelleme

### Git ile Güncelleme

```bash
cd /var/www/html/cami/
git fetch origin
git pull origin main

# Veritabanı güncellemeleri varsa
mysql -u cami_user -p cami_namaz_takip < updates/update_v2.sql
```

### Manuel Güncelleme

1. Mevcut dosyaları yedekleyin
2. Yeni dosyaları kopyalayın
3. `config/db.php` ayarlarını koruyun
4. Veritabanı güncellemelerini çalıştırın

## 📞 Destek

Kurulum sırasında sorun yaşarsanız:

1. **GitHub Issues**: [Sorun bildirin](https://github.com/kalyoncuvolkan/caminamaztakip/issues)
2. **Dokümantasyon**: README.md dosyasını inceleyin
3. **Community**: Discussions bölümünde yardım isteyin

---

**✅ Kurulum tamamlandığında sisteminiz tamamen işlevsel olacaktır!**