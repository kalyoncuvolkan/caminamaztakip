<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

// Sertifika oluştur
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sertifika_olustur'])) {
    $stmt = $pdo->prepare("INSERT INTO sertifikalar (ogrenci_id, sertifika_tipi, baslik, aciklama, donem, derece, tarih, olusturan_kullanici) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['ogrenci_id'], $_POST['tip'], $_POST['baslik'], $_POST['aciklama'], $_POST['donem'], $_POST['derece'], $_POST['tarih'], getLoggedInUser()]);
    $mesaj = "Sertifika oluşturuldu!";
}

$ogrenciler = $pdo->query("SELECT id, ad_soyad FROM ogrenciler WHERE aktif = 1 ORDER BY ad_soyad")->fetchAll();
$sertifikalar = $pdo->query("SELECT s.*, o.ad_soyad FROM sertifikalar s JOIN ogrenciler o ON s.ogrenci_id = o.id ORDER BY s.tarih DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sertifikalar - Cami Namaz Takip</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Cami Namaz Takip Programı</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="ogrenciler.php">Öğrenciler</a>
                <a href="sertifikalar.php" class="active">Sertifikalar</a>
                <a href="logout.php" style="margin-left: auto; background: rgba(255,255,255,0.3);">👤 <?php echo getLoggedInUser(); ?> - Çıkış</a>
            </nav>
        </header>

        <div style="padding: 30px;">
            <h2>📜 Sertifika Yönetimi</h2>
            <?php if(isset($mesaj)): ?><div class="alert success"><?php echo $mesaj; ?></div><?php endif; ?>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                <h3>➕ Yeni Sertifika Oluştur</h3>
                <form method="POST" style="display: grid; gap: 15px; max-width: 600px;">
                    <select name="ogrenci_id" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                        <option value="">Öğrenci Seçin</option>
                        <?php foreach($ogrenciler as $o): ?>
                        <option value="<?php echo $o['id']; ?>"><?php echo $o['ad_soyad']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tip" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                        <option value="Namaz">Namaz Sertifikası</option>
                        <option value="Ders">Ders Sertifikası</option>
                    </select>
                    <input type="text" name="baslik" placeholder="Başlık" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <input type="text" name="donem" placeholder="Dönem (örn: 2025 Ocak)" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <input type="text" name="derece" placeholder="Derece (örn: 1. Birincilik)" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <textarea name="aciklama" placeholder="Açıklama" rows="3" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;"></textarea>
                    <input type="date" name="tarih" value="<?php echo date('Y-m-d'); ?>" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <button type="submit" name="sertifika_olustur" class="btn-primary" style="width: auto;">📜 Sertifika Oluştur</button>
                </form>
            </div>

            <h3>📋 Sertifika Listesi</h3>
            <table>
                <thead><tr><th>Öğrenci</th><th>Tip</th><th>Başlık</th><th>Dönem</th><th>Tarih</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php foreach($sertifikalar as $s): ?>
                    <tr>
                        <td><?php echo $s['ad_soyad']; ?></td>
                        <td><span style="padding: 5px 10px; border-radius: 10px; background: <?php echo $s['sertifika_tipi']=='Namaz'?'#e3f2fd':'#fff3e0'; ?>;"><?php echo $s['sertifika_tipi']; ?></span></td>
                        <td><strong><?php echo $s['baslik']; ?></strong></td>
                        <td><?php echo $s['donem']; ?></td>
                        <td><?php echo date('d.m.Y', strtotime($s['tarih'])); ?></td>
                        <td><button onclick="yazdir(<?php echo $s['id']; ?>)" class="btn-sm" style="background: #17a2b8; color: white;">🖨️ Yazdır</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
    function yazdir(id) {
        window.open('sertifika-yazdir.php?id=' + id, '_blank');
    }
    </script>
</body>
</html>