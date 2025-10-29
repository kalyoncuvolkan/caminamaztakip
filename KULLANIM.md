# 🕌 Cami Namaz Takip Programı - Kullanım Kılavuzu

## 🚀 Sistem Başarıyla Kuruldu!

Site şu anda **http://localhost:8082** adresinde çalışıyor.

## 📊 Test Verileri
Sisteme aşağıdaki test verileri eklendi:
- 5 öğrenci kaydı
- Son 30 gün için namaz kayıtları

## 🔐 Veritabanı Bilgileri
- **Veritabanı Adı:** cami_namaz_takip
- **Kullanıcı:** root
- **Şifre:** (boş)

## 📁 Dosya Yapısı
```
/cami/
├── index.php              # Ana sayfa
├── ogrenci-ekle.php       # Öğrenci kayıt
├── namaz-ekle.php         # Namaz kaydı
├── genel-rapor.php        # Genel rapor
├── ozel-rapor.php         # Öğrenci raporu
├── config/
│   └── db.php            # Veritabanı bağlantısı
├── api/
│   └── ogrenci-detay.php # Öğrenci detay API
└── assets/
    └── style.css         # CSS dosyası
```

## 🌐 Sayfa Adresleri
- **Ana Sayfa:** http://localhost:8082/index.php
- **Öğrenci Ekle:** http://localhost:8082/ogrenci-ekle.php
- **Namaz Ekle:** http://localhost:8082/namaz-ekle.php
- **Genel Rapor:** http://localhost:8082/genel-rapor.php

## 💡 Kullanım
1. Tarayıcınızda http://localhost:8082 adresine gidin
2. Ana sayfada güncel sıralama ve öğrenci listesini göreceksiniz
3. Üst menüden istediğiniz bölüme geçebilirsiniz

## 🛠️ Sunucuyu Yeniden Başlatma
Eğer sunucu durursa:
```bash
php -S 0.0.0.0:8082
```

## ✨ Özellikler
- ✅ Öğrenci kaydı ve otomatik yaş hesaplama
- ✅ 5 vakit namaz takibi
- ✅ Anne/baba ile gelme durumu kaydı
- ✅ Aylık ve yıllık raporlama
- ✅ Sıralama sistemi (1., 2., 3.)
- ✅ Excel'e aktarma
- ✅ Yazdırma desteği
- ✅ Responsive tasarım