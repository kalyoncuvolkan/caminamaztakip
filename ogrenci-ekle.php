<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$olusturulan_kullanici = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad_soyad = $_POST['ad_soyad'];
    $dogum_tarihi = $_POST['dogum_tarihi'];
    $baba_adi = $_POST['baba_adi'];
    $anne_adi = $_POST['anne_adi'];
    $baba_telefonu = $_POST['baba_telefonu'];
    $anne_telefonu = $_POST['anne_telefonu'];
    $yas = yasHesapla($dogum_tarihi);

    // Öğrenciyi ekle
    $stmt = $pdo->prepare("INSERT INTO ogrenciler (ad_soyad, dogum_tarihi, yas, baba_adi, anne_adi, baba_telefonu, anne_telefonu) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$ad_soyad, $dogum_tarihi, $yas, $baba_adi, $anne_adi, $baba_telefonu, $anne_telefonu]);

    $ogrenci_id = $pdo->lastInsertId();

    // Otomatik kullanıcı adı oluştur (ad soyad + son 4 rakam)
    $ad_parts = explode(' ', $ad_soyad);
    $isim = strtolower(turkishToEnglish($ad_parts[0]));
    $soyisim = isset($ad_parts[1]) ? strtolower(turkishToEnglish($ad_parts[1])) : '';

    // Benzersiz kullanıcı adı oluştur
    $kullanici_adi_base = $isim . $soyisim;
    $kullanici_adi = $kullanici_adi_base;
    $counter = 1;

    // Kullanıcı adı varsa sayı ekle
    while(true) {
        $check = $pdo->prepare("SELECT id FROM ogrenci_kullanicilar WHERE kullanici_adi = ?");
        $check->execute([$kullanici_adi]);
        if(!$check->fetch()) break;
        $kullanici_adi = $kullanici_adi_base . $counter;
        $counter++;
    }

    // Rastgele şifre oluştur (8 karakter)
    $sifre = generateRandomPassword();
    $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

    // Öğrenci kullanıcısı oluştur
    $stmt = $pdo->prepare("INSERT INTO ogrenci_kullanicilar (ogrenci_id, kullanici_adi, sifre) VALUES (?, ?, ?)");
    $stmt->execute([$ogrenci_id, $kullanici_adi, $sifre_hash]);

    // Aktif dersleri öğrenciye ata
    $dersler = $pdo->query("SELECT id FROM dersler WHERE aktif = 1")->fetchAll();
    foreach($dersler as $ders) {
        $pdo->prepare("INSERT IGNORE INTO ogrenci_dersler (ogrenci_id, ders_id) VALUES (?, ?)")->execute([$ogrenci_id, $ders['id']]);
    }

    $olusturulan_kullanici = [
        'ad_soyad' => $ad_soyad,
        'kullanici_adi' => $kullanici_adi,
        'sifre' => $sifre
    ];

    $mesaj = "Öğrenci başarıyla eklendi ve otomatik kullanıcı oluşturuldu!";
}

// Türkçe karakterleri İngilizce'ye çevir
function turkishToEnglish($text) {
    $turkish = array('ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç');
    $english = array('s','S','i','I','g','G','u','U','o','O','c','C');
    return str_replace($turkish, $english, $text);
}

// Rastgele şifre oluştur
function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

$aktif_sayfa = 'ogrenciler';
$sayfa_basligi = 'Öğrenci Ekle - Cami Namaz Takip';
require_once 'config/header.php';
?>

        <div class="form-container">
            <h2>👤 Yeni Öğrenci Ekle</h2>

            <?php if(isset($mesaj)): ?>
            <div class="alert success"><?php echo $mesaj; ?></div>
            <?php endif; ?>

            <?php if($olusturulan_kullanici): ?>
            <div style="background: #d4edda; border: 2px solid #28a745; padding: 25px; border-radius: 12px; margin: 20px 0;">
                <h3 style="margin: 0 0 15px 0; color: #155724;">🎉 Öğrenci Hesabı Oluşturuldu!</h3>
                <div style="background: white; padding: 20px; border-radius: 8px;">
                    <p style="margin: 10px 0;"><strong>👤 Öğrenci:</strong> <?php echo htmlspecialchars($olusturulan_kullanici['ad_soyad']); ?></p>
                    <p style="margin: 10px 0;"><strong>🔑 Kullanıcı Adı:</strong> <code style="background: #f8f9fa; padding: 5px 10px; border-radius: 5px; font-size: 16px;"><?php echo $olusturulan_kullanici['kullanici_adi']; ?></code></p>
                    <p style="margin: 10px 0;"><strong>🔒 Şifre:</strong> <code style="background: #f8f9fa; padding: 5px 10px; border-radius: 5px; font-size: 16px;"><?php echo $olusturulan_kullanici['sifre']; ?></code></p>
                </div>
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #ffc107;">
                    <strong>⚠️ Önemli:</strong> Bu bilgileri öğrenciye iletiniz. Şifre bir daha gösterilmeyecektir!
                </div>
                <div style="margin-top: 15px;">
                    <button onclick="printCredentials()" style="background: #17a2b8; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-right: 10px;">
                        🖨️ Yazdır
                    </button>
                    <button onclick="copyCredentials()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">
                        📋 Kopyala
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Ad Soyad:</label>
                    <input type="text" name="ad_soyad" required>
                </div>
                
                <div class="form-group">
                    <label>Doğum Tarihi:</label>
                    <input type="date" name="dogum_tarihi" required onchange="yasHesapla()">
                    <span id="yas-goster"></span>
                </div>
                
                <div class="form-group">
                    <label>Baba Adı:</label>
                    <input type="text" name="baba_adi">
                </div>
                
                <div class="form-group">
                    <label>Anne Adı:</label>
                    <input type="text" name="anne_adi">
                </div>
                
                <div class="form-group">
                    <label>Baba Telefonu:</label>
                    <input type="tel" name="baba_telefonu" pattern="[0-9]{10,11}" placeholder="05XX XXX XX XX">
                </div>
                
                <div class="form-group">
                    <label>Anne Telefonu:</label>
                    <input type="tel" name="anne_telefonu" pattern="[0-9]{10,11}" placeholder="05XX XXX XX XX">
                </div>
                
                <button type="submit" class="btn-primary">Öğrenciyi Kaydet</button>
            </form>
        </div>

    <script>
        function yasHesapla() {
            var dogumTarihi = document.getElementsByName('dogum_tarihi')[0].value;
            if(dogumTarihi) {
                var bugun = new Date();
                var dogum = new Date(dogumTarihi);
                var yas = bugun.getFullYear() - dogum.getFullYear();
                var ay = bugun.getMonth() - dogum.getMonth();

                if (ay < 0 || (ay === 0 && bugun.getDate() < dogum.getDate())) {
                    yas--;
                }

                document.getElementById('yas-goster').innerHTML = ' (Yaş: ' + yas + ')';
            }
        }

        function copyCredentials() {
            <?php if($olusturulan_kullanici): ?>
            const text = `Öğrenci Panel Giriş Bilgileri\n\nÖğrenci: <?php echo $olusturulan_kullanici['ad_soyad']; ?>\nKullanıcı Adı: <?php echo $olusturulan_kullanici['kullanici_adi']; ?>\nŞifre: <?php echo $olusturulan_kullanici['sifre']; ?>\n\nGiriş: http://<?php echo $_SERVER['HTTP_HOST']; ?>/ogrenci-panel/`;
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ Bilgiler kopyalandı!');
            });
            <?php endif; ?>
        }

        function printCredentials() {
            <?php if($olusturulan_kullanici): ?>
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Öğrenci Giriş Bilgileri</title>');
            printWindow.document.write('<style>body{font-family:Arial;padding:40px;} h1{color:#667eea;} .box{background:#f8f9fa;padding:20px;border-radius:10px;margin:20px 0;} code{background:#fff;padding:5px 10px;border-radius:5px;font-size:18px;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h1>🎓 Öğrenci Panel Giriş Bilgileri</h1>');
            printWindow.document.write('<div class="box">');
            printWindow.document.write('<p><strong>Öğrenci:</strong> <?php echo htmlspecialchars($olusturulan_kullanici['ad_soyad']); ?></p>');
            printWindow.document.write('<p><strong>Kullanıcı Adı:</strong> <code><?php echo $olusturulan_kullanici['kullanici_adi']; ?></code></p>');
            printWindow.document.write('<p><strong>Şifre:</strong> <code><?php echo $olusturulan_kullanici['sifre']; ?></code></p>');
            printWindow.document.write('<p><strong>Giriş Adresi:</strong> <code>http://<?php echo $_SERVER['HTTP_HOST']; ?>/ogrenci-panel/</code></p>');
            printWindow.document.write('</div>');
            printWindow.document.write('<p style="color:#dc3545;"><strong>⚠️ Bu bilgileri güvenli bir yerde saklayınız!</strong></p>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
            <?php endif; ?>
        }
    </script>
<?php require_once 'config/footer.php'; ?>