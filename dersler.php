<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$kategori_id = $_GET['kategori'] ?? 0;
$mesaj = '';

// Ders ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ders_ekle'])) {
    $stmt = $pdo->prepare("INSERT INTO dersler (kategori_id, ders_adi, aciklama, puan, sira) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['kategori_id'], $_POST['ders_adi'], $_POST['aciklama'], $_POST['puan'], $_POST['sira']]);

    // Tüm aktif öğrencilere otomatik ata
    $ders_id = $pdo->lastInsertId();
    $ogrenciler = $pdo->query("SELECT id FROM ogrenciler WHERE aktif = 1")->fetchAll();
    foreach($ogrenciler as $ogr) {
        $pdo->prepare("INSERT IGNORE INTO ogrenci_dersler (ogrenci_id, ders_id) VALUES (?, ?)")->execute([$ogr['id'], $ders_id]);
    }
    $mesaj = "Ders eklendi ve tüm aktif öğrencilere atandı!";
}

$kategoriler = $pdo->query("SELECT * FROM ders_kategorileri WHERE aktif = 1 ORDER BY sira")->fetchAll();

// Dersler ve tamamlanma istatistikleri
$dersler = $pdo->query("
    SELECT d.*, dk.kategori_adi,
           (SELECT COUNT(*) FROM ogrenci_dersler od WHERE od.ders_id = d.id AND od.durum = 'Tamamlandi') as tamamlanan,
           (SELECT COUNT(*) FROM ogrenci_dersler od WHERE od.ders_id = d.id) as toplam
    FROM dersler d
    JOIN ders_kategorileri dk ON d.kategori_id = dk.id
    ORDER BY dk.sira, d.sira
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Dersler - Cami Namaz Takip</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Cami Namaz Takip Programı</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="ogrenciler.php">Öğrenciler</a>
                <a href="ders-kategorileri.php">Ders Kategorileri</a>
                <a href="dersler.php" class="active">Dersler</a>
                <a href="logout.php" style="margin-left: auto; background: rgba(255,255,255,0.3);">👤 <?php echo getLoggedInUser(); ?> - Çıkış</a>
            </nav>
        </header>

        <div style="padding: 30px;">
            <h2>📖 Dersler</h2>
            <?php if($mesaj): ?><div class="alert success"><?php echo $mesaj; ?></div><?php endif; ?>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                <h3>➕ Yeni Ders Ekle</h3>
                <form method="POST" style="display: grid; gap: 15px; max-width: 600px;">
                    <select name="kategori_id" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                        <option value="">Kategori Seçin</option>
                        <?php foreach($kategoriler as $k): ?>
                        <option value="<?php echo $k['id']; ?>"><?php echo $k['kategori_adi']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="ders_adi" placeholder="Ders Adı" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <textarea name="aciklama" placeholder="Açıklama" rows="3" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;"></textarea>
                    <input type="number" name="puan" value="1" min="1" placeholder="Puan" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <input type="number" name="sira" value="0" min="0" placeholder="Sıra" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <button type="submit" name="ders_ekle" class="btn-primary" style="width: auto;">💾 Ders Ekle ve Öğrencilere Ata</button>
                </form>
            </div>

            <h3>📚 Ders Listesi</h3>
            <table>
                <thead><tr><th>Kategori</th><th>Ders Adı</th><th>Puan</th><th>Sıra</th><th>Durum</th><th>İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach($dersler as $d):
                        $tamamlanma_yuzdesi = $d['toplam'] > 0 ? round(($d['tamamlanan'] / $d['toplam']) * 100) : 0;
                        $durum_renk = $tamamlanma_yuzdesi == 100 ? '#28a745' : ($tamamlanma_yuzdesi > 0 ? '#ffc107' : '#dc3545');
                    ?>
                    <tr>
                        <td><?php echo $d['kategori_adi']; ?></td>
                        <td><strong><?php echo htmlspecialchars($d['ders_adi']); ?></strong></td>
                        <td>+<?php echo $d['puan']; ?></td>
                        <td><?php echo $d['sira']; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #e9ecef; border-radius: 10px; height: 20px; overflow: hidden;">
                                    <div style="width: <?php echo $tamamlanma_yuzdesi; ?>%; background: <?php echo $durum_renk; ?>; height: 100%; transition: width 0.3s;"></div>
                                </div>
                                <span style="min-width: 80px; font-weight: bold; color: <?php echo $durum_renk; ?>;">
                                    <?php echo $d['tamamlanan']; ?>/<?php echo $d['toplam']; ?> (%<?php echo $tamamlanma_yuzdesi; ?>)
                                </span>
                            </div>
                        </td>
                        <td>
                            <a href="ders-takip.php?ders=<?php echo $d['id']; ?>" class="btn-sm" style="background: #007bff; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; display: inline-block;" title="Ders Takibi">📋 Detay</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>