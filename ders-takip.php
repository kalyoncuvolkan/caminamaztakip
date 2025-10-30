<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$ders_id = $_GET['ders'] ?? 0;

// Ders tamamla
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ders_tamamla'])) {
    $ogrenci_id = $_POST['ogrenci_id'];
    $ders_id = $_POST['ders_id'];
    $puan = $_POST['puan'];

    // Önce kayıt var mı kontrol et, yoksa oluştur
    $check = $pdo->prepare("SELECT id FROM ogrenci_dersler WHERE ogrenci_id = ? AND ders_id = ?");
    $check->execute([$ogrenci_id, $ders_id]);

    if($check->fetch()) {
        // Kayıt varsa güncelle
        $stmt = $pdo->prepare("UPDATE ogrenci_dersler SET durum = 'Tamamlandi', tamamlanma_tarihi = CURDATE(), puan_verildi = 1 WHERE ogrenci_id = ? AND ders_id = ?");
        $stmt->execute([$ogrenci_id, $ders_id]);
    } else {
        // Kayıt yoksa oluştur
        $stmt = $pdo->prepare("INSERT INTO ogrenci_dersler (ogrenci_id, ders_id, durum, tamamlanma_tarihi, puan_verildi) VALUES (?, ?, 'Tamamlandi', CURDATE(), 1)");
        $stmt->execute([$ogrenci_id, $ders_id]);
    }

    // İlave puan ekle
    $pdo->prepare("INSERT INTO ilave_puanlar (ogrenci_id, puan, aciklama, veren_kullanici, tarih) VALUES (?, ?, ?, ?, CURDATE())")->execute([$ogrenci_id, $puan, 'Ders tamamlama', getLoggedInUser()]);

    header("Location: ders-takip.php?ders=$ders_id");
    exit;
}

$ders = $pdo->prepare("SELECT d.*, dk.kategori_adi FROM dersler d JOIN ders_kategorileri dk ON d.kategori_id = dk.id WHERE d.id = ?");
$ders->execute([$ders_id]);
$ders_bilgi = $ders->fetch();

$ogrenciler = $pdo->prepare("
    SELECT o.*, IFNULL(od.durum, 'Beklemede') as durum, od.tamamlanma_tarihi
    FROM ogrenciler o
    LEFT JOIN ogrenci_dersler od ON o.id = od.ogrenci_id AND od.ders_id = ?
    WHERE o.aktif = 1
    ORDER BY IFNULL(od.durum, 'Beklemede'), o.ad_soyad
");
$ogrenciler->execute([$ders_id]);
$ogr_list = $ogrenciler->fetchAll();

$aktif_sayfa = 'dersler';
$sayfa_basligi = 'Ders Takip - Cami Namaz Takip';
require_once 'config/header.php';
?>

        <div style="padding: 30px;">
            <h2>📖 <?php echo $ders_bilgi['ders_adi']; ?> - Ders Takibi</h2>
            <p><strong>Kategori:</strong> <?php echo $ders_bilgi['kategori_adi']; ?> | <strong>Puan:</strong> +<?php echo $ders_bilgi['puan']; ?></p>

            <table>
                <thead><tr><th>Öğrenci</th><th>Durum</th><th>Tamamlanma Tarihi</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php foreach($ogr_list as $o): ?>
                    <tr style="background: <?php echo $o['durum'] == 'Tamamlandi' ? '#d4edda' : '#f8d7da'; ?>">
                        <td><?php echo $o['ad_soyad']; ?></td>
                        <td><strong><?php echo $o['durum'] == 'Tamamlandi' ? '✅ Tamamlandı' : '❌ Beklemede'; ?></strong></td>
                        <td><?php echo $o['tamamlanma_tarihi'] ? date('d.m.Y', strtotime($o['tamamlanma_tarihi'])) : '-'; ?></td>
                        <td>
                            <?php if($o['durum'] != 'Tamamlandi'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="ogrenci_id" value="<?php echo $o['id']; ?>">
                                <input type="hidden" name="ders_id" value="<?php echo $ders_id; ?>">
                                <input type="hidden" name="puan" value="<?php echo $ders_bilgi['puan']; ?>">
                                <button type="submit" name="ders_tamamla" class="btn-sm" style="background: #28a745; color: white;">✓ Tamamlandı</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php require_once 'config/footer.php'; ?>