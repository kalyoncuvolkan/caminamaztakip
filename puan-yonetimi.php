<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$ogrenci_id = $_GET['id'] ?? 0;
$mesaj = '';

// Öğrenci bilgileri
$ogrenci_stmt = $pdo->prepare("SELECT * FROM ogrenciler WHERE id = ?");
$ogrenci_stmt->execute([$ogrenci_id]);
$ogrenci = $ogrenci_stmt->fetch();

if(!$ogrenci) {
    header('Location: ogrenciler.php');
    exit;
}

// Namaz kayıtları
$namazlar = $pdo->prepare("
    SELECT * FROM namaz_kayitlari
    WHERE ogrenci_id = ?
    ORDER BY tarih DESC, saat DESC
");
$namazlar->execute([$ogrenci_id]);
$kayitlar = $namazlar->fetchAll();

// İlave puanlar
$ilaveler = $pdo->prepare("SELECT * FROM ilave_puanlar WHERE ogrenci_id = ? ORDER BY tarih DESC");
$ilaveler->execute([$ogrenci_id]);
$ilave_puanlar = $ilaveler->fetchAll();

// Silinen puanlar
$silinenler = $pdo->prepare("SELECT * FROM puan_silme_gecmisi WHERE ogrenci_id = ? ORDER BY silme_zamani DESC");
$silinenler->execute([$ogrenci_id]);
$silinen_kayitlar = $silinenler->fetchAll();

// İlave puan ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ilave_puan_ekle'])) {
    $puan = $_POST['puan'];
    $aciklama = $_POST['aciklama'];
    $tarih = $_POST['tarih'];

    $stmt = $pdo->prepare("INSERT INTO ilave_puanlar (ogrenci_id, puan, aciklama, veren_kullanici, tarih) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$ogrenci_id, $puan, $aciklama, getLoggedInUser(), $tarih]);
    $mesaj = "İlave puan başarıyla eklendi!";
    header("Location: puan-yonetimi.php?id=$ogrenci_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Puan Yönetimi - <?php echo $ogrenci['ad_soyad']; ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Cami Namaz Takip Programı</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="ogrenciler.php">Öğrenciler</a>
                <a href="logout.php" style="margin-left: auto">👤 <?php echo getLoggedInUser(); ?> - Çıkış</a>
            </nav>
        </header>

        <div style="padding: 30px;">
            <h2>⭐ Puan Yönetimi: <?php echo $ogrenci['ad_soyad']; ?></h2>

            <!-- İlave Puan Ekle -->
            <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h3>➕ İlave Puan Ekle</h3>
                <form method="POST" style="display: grid; gap: 15px; max-width: 600px;">
                    <input type="number" name="puan" placeholder="Puan miktarı" required min="1" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <input type="date" name="tarih" value="<?php echo date('Y-m-d'); ?>" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <textarea name="aciklama" placeholder="Açıklama (opsiyonel)" rows="3" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;"></textarea>
                    <button type="submit" name="ilave_puan_ekle" class="btn-primary" style="width: auto;">💾 Puan Ekle</button>
                </form>
            </div>

            <!-- Namaz Kayıtları -->
            <h3>🕌 Namaz Kayıtları</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Vakit</th>
                        <th>Kiminle Geldi</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($kayitlar as $kayit): ?>
                    <tr id="kayit-<?php echo $kayit['id']; ?>">
                        <td><?php echo date('d.m.Y', strtotime($kayit['tarih'])); ?></td>
                        <td><?php echo $kayit['namaz_vakti']; ?></td>
                        <td><?php echo $kayit['kiminle_geldi']; ?></td>
                        <td><button onclick="puanSil(<?php echo $kayit['id']; ?>)" class="btn-sm btn-delete">🗑️ Sil</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- İlave Puanlar -->
            <?php if(count($ilave_puanlar) > 0): ?>
            <h3 style="margin-top: 30px;">⭐ İlave Puanlar</h3>
            <table>
                <thead>
                    <tr><th>Tarih</th><th>Puan</th><th>Açıklama</th><th>Veren</th></tr>
                </thead>
                <tbody>
                    <?php foreach($ilave_puanlar as $ip): ?>
                    <tr><td><?php echo date('d.m.Y', strtotime($ip['tarih'])); ?></td>
                    <td><strong>+<?php echo $ip['puan']; ?></strong></td>
                    <td><?php echo htmlspecialchars($ip['aciklama']); ?></td>
                    <td><?php echo $ip['veren_kullanici']; ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Silinen Puanlar -->
            <?php if(count($silinen_kayitlar) > 0): ?>
            <h3 style="margin-top: 30px;">❌ Silinen Puanlar</h3>
            <table>
                <thead>
                    <tr><th>Tarih</th><th>Vakit</th><th>Kiminle</th><th>Silme Nedeni</th><th>Silen</th><th>Silme Zamanı</th></tr>
                </thead>
                <tbody>
                    <?php foreach($silinen_kayitlar as $s): ?>
                    <tr style="background: #f8d7da;"><td><?php echo date('d.m.Y', strtotime($s['tarih'])); ?></td>
                    <td><?php echo $s['namaz_vakti']; ?></td>
                    <td><?php echo $s['kiminle_geldi']; ?></td>
                    <td><?php echo htmlspecialchars($s['silme_nedeni']); ?></td>
                    <td><?php echo $s['silen_kullanici']; ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($s['silme_zamani'])); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function puanSil(kayitId) {
            const nedeni = prompt('❓ Silme nedeni (opsiyonel):');
            if(nedeni !== null) {
                fetch('api/puan-sil.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'kayit_id=' + kayitId + '&nedeni=' + encodeURIComponent(nedeni)
                })
                .then(r => r.json())
                .then(d => {
                    if(d.success) {
                        alert('✅ Puan silindi ve geçmişe kaydedildi');
                        location.reload();
                    } else {
                        alert('❌ Hata: ' + d.message);
                    }
                });
            }
        }
    </script>
</body>
</html>