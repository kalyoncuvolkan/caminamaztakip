<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad_soyad = $_POST['ad_soyad'];
    $dogum_tarihi = $_POST['dogum_tarihi'];
    $baba_adi = $_POST['baba_adi'];
    $anne_adi = $_POST['anne_adi'];
    $baba_telefonu = $_POST['baba_telefonu'];
    $anne_telefonu = $_POST['anne_telefonu'];
    $yas = yasHesapla($dogum_tarihi);
    
    $stmt = $pdo->prepare("INSERT INTO ogrenciler (ad_soyad, dogum_tarihi, yas, baba_adi, anne_adi, baba_telefonu, anne_telefonu) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$ad_soyad, $dogum_tarihi, $yas, $baba_adi, $anne_adi, $baba_telefonu, $anne_telefonu]);
    
    $mesaj = "Öğrenci başarıyla eklendi!";
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
    </script>
<?php require_once 'config/footer.php'; ?>