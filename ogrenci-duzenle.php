<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$ogrenci_id = $_GET['id'] ?? 0;
$mesaj = '';
$hata = '';

// Öğrenci bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM ogrenciler WHERE id = ?");
$stmt->execute([$ogrenci_id]);
$ogrenci = $stmt->fetch();

if(!$ogrenci) {
    header('Location: ogrenciler.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad_soyad = $_POST['ad_soyad'];
    $dogum_tarihi = $_POST['dogum_tarihi'];
    $baba_adi = $_POST['baba_adi'];
    $anne_adi = $_POST['anne_adi'];
    $baba_telefonu = $_POST['baba_telefonu'];
    $anne_telefonu = $_POST['anne_telefonu'];
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $yas = yasHesapla($dogum_tarihi);

    $update_stmt = $pdo->prepare("
        UPDATE ogrenciler
        SET ad_soyad = ?, dogum_tarihi = ?, yas = ?, baba_adi = ?,
            anne_adi = ?, baba_telefonu = ?, anne_telefonu = ?, aktif = ?
        WHERE id = ?
    ");

    if($update_stmt->execute([$ad_soyad, $dogum_tarihi, $yas, $baba_adi, $anne_adi, $baba_telefonu, $anne_telefonu, $aktif, $ogrenci_id])) {
        $mesaj = "Öğrenci bilgileri başarıyla güncellendi!";
        // Güncel bilgileri tekrar çek
        $stmt->execute([$ogrenci_id]);
        $ogrenci = $stmt->fetch();
    } else {
        $hata = "Güncelleme sırasında bir hata oluştu!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Düzenle - Cami Namaz Takip</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Cami Namaz Takip Programı</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="ogrenciler.php">Öğrenciler</a>
                <a href="ogrenci-ekle.php">Öğrenci Ekle</a>
                <a href="namaz-ekle-yeni.php">Namaz Ekle</a>
                <a href="genel-rapor.php">Genel Rapor</a>
                <a href="logout.php" style="margin-left: auto; background: rgba(255,255,255,0.3);">👤 <?php echo getLoggedInUser(); ?> - Çıkış</a>
            </nav>
        </header>

        <div class="form-container">
            <h2>✏️ Öğrenci Düzenle</h2>

            <?php if($mesaj): ?>
            <div class="alert success"><?php echo $mesaj; ?></div>
            <?php endif; ?>

            <?php if($hata): ?>
            <div class="alert" style="background: #f8d7da; color: #721c24;"><?php echo $hata; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Ad Soyad:</label>
                    <input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($ogrenci['ad_soyad']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Doğum Tarihi:</label>
                    <input type="date" name="dogum_tarihi" value="<?php echo $ogrenci['dogum_tarihi']; ?>" required onchange="yasHesapla()">
                    <span id="yas-goster">(Yaş: <?php echo yasHesapla($ogrenci['dogum_tarihi']); ?>)</span>
                </div>

                <div class="form-group">
                    <label>Baba Adı:</label>
                    <input type="text" name="baba_adi" value="<?php echo htmlspecialchars($ogrenci['baba_adi']); ?>">
                </div>

                <div class="form-group">
                    <label>Anne Adı:</label>
                    <input type="text" name="anne_adi" value="<?php echo htmlspecialchars($ogrenci['anne_adi']); ?>">
                </div>

                <div class="form-group">
                    <label>Baba Telefonu:</label>
                    <input type="tel" name="baba_telefonu" value="<?php echo htmlspecialchars($ogrenci['baba_telefonu']); ?>" pattern="[0-9]{10,11}" placeholder="05XX XXX XX XX">
                </div>

                <div class="form-group">
                    <label>Anne Telefonu:</label>
                    <input type="tel" name="anne_telefonu" value="<?php echo htmlspecialchars($ogrenci['anne_telefonu']); ?>" pattern="[0-9]{10,11}" placeholder="05XX XXX XX XX">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="aktif" <?php echo $ogrenci['aktif'] ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                        Öğrenci Aktif
                    </label>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        İşaretli: Aktif öğrenci | İşaretsiz: Pasif öğrenci
                    </small>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-primary" style="flex: 1;">💾 Değişiklikleri Kaydet</button>
                    <a href="ogrenciler.php" class="btn-primary" style="flex: 1; background: #6c757d; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        ← Geri Dön
                    </a>
                </div>
            </form>

            <div style="background: #fff3cd; padding: 15px; border-radius: 10px; margin-top: 30px; border: 1px solid #ffeeba;">
                <h4 style="margin-top: 0; color: #856404;">⚠️ Önemli Bilgiler</h4>
                <ul style="margin: 10px 0; color: #856404;">
                    <li>Öğrenci bilgilerini değiştirmek geçmiş kayıtları etkilemez</li>
                    <li>Pasif öğrenciler raporlarda görünmez ancak geçmiş verileri korunur</li>
                    <li>Öğrenciyi tamamen silmek için "Öğrenciler" sayfasını kullanın</li>
                </ul>
            </div>
        </div>
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
    </script>
</body>
</html>