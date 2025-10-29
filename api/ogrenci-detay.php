<?php
require_once '../config/auth.php';
checkAuth();
require_once '../config/db.php';

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM ogrenciler WHERE id = ?");
$stmt->execute([$id]);
$ogrenci = $stmt->fetch();

if($ogrenci):
?>
<div class="kimlik-karti">
    <h2>Öğrenci Kimlik Kartı</h2>
    <div class="kimlik-bilgiler">
        <div class="bilgi-satir">
            <label>Ad Soyad:</label>
            <span><?php echo $ogrenci['ad_soyad']; ?></span>
        </div>
        <div class="bilgi-satir">
            <label>Doğum Tarihi:</label>
            <span><?php echo date('d.m.Y', strtotime($ogrenci['dogum_tarihi'])); ?></span>
        </div>
        <div class="bilgi-satir">
            <label>Yaş:</label>
            <span><?php echo yasHesapla($ogrenci['dogum_tarihi']); ?></span>
        </div>
        <div class="bilgi-satir">
            <label>Baba Adı:</label>
            <span><?php echo $ogrenci['baba_adi'] ?: '-'; ?></span>
        </div>
        <div class="bilgi-satir">
            <label>Anne Adı:</label>
            <span><?php echo $ogrenci['anne_adi'] ?: '-'; ?></span>
        </div>
        <div class="bilgi-satir">
            <label>Baba Telefonu:</label>
            <span><?php echo $ogrenci['baba_telefonu'] ?: '-'; ?></span>
        </div>
        <div class="bilgi-satir">
            <label>Anne Telefonu:</label>
            <span><?php echo $ogrenci['anne_telefonu'] ?: '-'; ?></span>
        </div>
        <div class="bilgi-satir">
            <label>Kayıt Tarihi:</label>
            <span><?php echo date('d.m.Y H:i', strtotime($ogrenci['kayit_tarihi'])); ?></span>
        </div>
    </div>
</div>
<?php else: ?>
<p>Öğrenci bulunamadı.</p>
<?php endif; ?>