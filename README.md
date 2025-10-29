# 🕌 Cami Namaz Takip Programı

Modern ve kullanıcı dostu PHP tabanlı öğrenci namaz takip sistemi. Camiye gelen öğrencilerin namaz kılma aktivitelerini takip etmek, puanlama yapmak ve detaylı raporlar almak için geliştirilmiştir.

## ✨ Özellikler

### 🔐 Güvenlik Sistemi
- **Yetkilendirme**: Sadece yetkili kullanıcılar erişebilir
- **Session Yönetimi**: Güvenli oturum kontrolü (8 saat timeout)
- **Şifreli Parolalar**: PHP'nin güvenli hash fonksiyonu kullanılır

### 👥 Öğrenci Yönetimi
- **Kapsamlı Kayıt**: Ad, soyad, doğum tarihi, anne-baba bilgileri
- **Otomatik Yaş Hesaplama**: Doğum tarihinden otomatik yaş hesaplama
- **Kimlik Kartı Görünümü**: Modal pencerede detaylı öğrenci bilgileri
- **Arama Sistemi**: Canlı arama ile hızlı öğrenci bulma

### 🕌 Namaz Takip Sistemi
- **5 Vakit Namaz**: Sabah, Öğlen, İkindi, Akşam, Yatsı
- **Wizard Arayüz**: 3 adımlı kullanıcı dostu namaz ekleme
- **Beraberlik Takibi**: Kiminle geldiği bilgisi (Kendisi, Babası, Annesi, Anne-Babası)
- **Çoklu Puanlama**: Her seçim için ayrı puan sistemi

### 📊 Raporlama Sistemi
- **Genel Raporlar**: Aylık toplu performans raporları
- **Özel Raporlar**: Öğrenci bazlı detaylı analiz
- **Sıralama Sistemi**: Yıllık ve aylık başarı sıralaması
- **Görsel İstatistikler**: Renkli ve anlaşılır grafikler
- **Export Desteği**: Excel'e aktarma ve yazdırma

### 🏆 Gamification (Oyunlaştırma)
- **Anlık Sıralama**: Ana sayfada güncel liderlik tablosu
- **Başarı Rozetleri**: Altın, gümüş, bronz madalyalar
- **Puan Sistemi**: Her namaz kaydı için puan kazanma
- **Motivasyon**: Görsel ödül sistemleri

## 🚀 Teknoloji Stack

- **Backend**: PHP 8.1+
- **Database**: MySQL 8.0+
- **Frontend**: Modern HTML5, CSS3, JavaScript
- **Design**: Responsive tasarım, gradient renkler
- **Security**: Session-based authentication, password hashing

## 📁 Proje Yapısı

```
cami-namaz-takip/
├── 📄 index.php                 # Ana sayfa - dashboard
├── 🔐 login.php                 # Giriş sayfası
├── 🚪 logout.php                # Çıkış işlemi
├── 👤 ogrenci-ekle.php          # Öğrenci kayıt formu
├── 🕌 namaz-ekle-yeni.php       # Wizard namaz ekleme
├── 📊 genel-rapor.php           # Genel raporlama
├── 📑 ozel-rapor.php            # Öğrenci özel raporu
├── 🗄️ database.sql             # Ana veritabanı şeması
├── ⚙️ setup.sql                 # Alternatif kurulum
├── 👥 create_users.php          # Kullanıcı oluşturma
├── 🧪 test_data.php             # Test verisi ekleme
├── 📁 config/
│   ├── 🔧 db.php               # Veritabanı bağlantısı
│   └── 🔐 auth.php             # Yetkilendirme fonksiyonları
├── 📁 api/
│   ├── 👤 ogrenci-detay.php    # Öğrenci detay API
│   └── 🔍 ogrenci-ara.php      # Öğrenci arama API
├── 📁 assets/
│   └── 🎨 style.css            # Ana stil dosyası
└── 📚 docs/
    ├── 📖 README.md            # Bu dosya
    └── 🛠️ KURULUM.md           # Detaylı kurulum kılavuzu
```

## ⚡ Hızlı Başlangıç

### 1. Gereksinimler
- PHP 8.1 veya üzeri
- MySQL 8.0 veya üzeri
- Apache/Nginx web sunucusu
- Modern web tarayıcı

### 2. Kurulum
```bash
# Projeyi klonlayın
git clone https://github.com/kalyoncuvolkan/caminamaztakip.git

# Web sunucunuzun dizinine taşıyın
cp -r caminamaztakip /var/www/html/cami

# Veritabanını oluşturun
mysql -u root -p < database.sql

# Kullanıcı oluşturun
php create_users.php

# Test verilerini ekleyin (opsiyonel)
php test_data.php
```

### 3. Yapılandırma
`config/db.php` dosyasında veritabanı ayarlarınızı güncelleyin:

```php
$host = 'localhost';
$dbname = 'cami_namaz_takip';
$username = 'your_username';
$password = 'your_password';
```

### 4. Erişim
- Tarayıcınızda `http://localhost/cami` adresine gidin
- Varsayılan kullanıcı bilgileriyle giriş yapın

## 🎯 Kullanım Rehberi

### Öğrenci Kaydı
1. **Öğrenci Ekle** menüsüne tıklayın
2. Gerekli bilgileri doldurun
3. Doğum tarihi girilince yaş otomatik hesaplanır
4. **Kaydet** butonuna tıklayın

### Namaz Kaydı
1. **Namaz Ekle** menüsüne tıklayın
2. **Adım 1**: Öğrenci adını arayın ve seçin
3. **Adım 2**: Namaz vakti ve tarihi seçin
4. **Adım 3**: Kiminle geldiğini işaretleyin (çoklu seçim mümkün)
5. **Kaydet** ile işlemi tamamlayın

### Raporlama
- **Genel Rapor**: Aylık performans tabloları
- **Özel Rapor**: Öğrenci sayfasında "Raporla" butonuna tıklayın
- **Excel Export**: Raporları Excel formatında indirin

## 🔧 Konfigürasyon

### Veritabanı Ayarları
```php
// config/db.php
$host = 'localhost';        // MySQL sunucu adresi
$dbname = 'cami_namaz_takip';  // Veritabanı adı
$username = 'root';         // MySQL kullanıcı adı
$password = '';             // MySQL parolası
```

### Güvenlik Ayarları
```php
// config/auth.php
// Session timeout (saniye): 28800 = 8 saat
define('SESSION_TIMEOUT', 28800);
```

## 🛡️ Güvenlik

- **SQL Injection Koruması**: Prepared statements kullanılır
- **XSS Koruması**: HTML escaping yapılır
- **Session Güvenliği**: Güvenli session yönetimi
- **Parola Hashleme**: PHP'nin password_hash() fonksiyonu

## 📱 Responsive Tasarım

Sistem tüm cihazlarda mükemmel çalışır:
- 💻 **Desktop**: Tam özellikli arayüz
- 📱 **Tablet**: Dokunmatik optimizasyonu
- 📱 **Mobile**: Mobil-first tasarım

## 🔄 Güncellemeler

Sistem sürekli geliştirilmektedir. Yeni özellikler:
- [ ] SMS bildirimleri
- [ ] WhatsApp entegrasyonu
- [ ] Mobil uygulama
- [ ] Çoklu cami desteği
- [ ] Backup sistemi

## 🤝 Katkıda Bulunma

1. Fork edin
2. Feature branch oluşturun (`git checkout -b feature/AmazingFeature`)
3. Commit edin (`git commit -m 'Add some AmazingFeature'`)
4. Push edin (`git push origin feature/AmazingFeature`)
5. Pull Request açın

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için `LICENSE` dosyasına bakın.

## 👨‍💻 Geliştirici

**Volkan Kalyoncu**
- GitHub: [@kalyoncuvolkan](https://github.com/kalyoncuvolkan)
- Email: volkan@example.com

## 🙏 Teşekkürler

Bu projenin geliştirilmesinde emeği geçen herkese teşekkürler.

---
**⭐ Projeyi beğendiyseniz yıldız vermeyi unutmayın!**