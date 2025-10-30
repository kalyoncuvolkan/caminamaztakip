<?php
session_start();
require_once '../config/db.php';

// Öğrenci login kontrolü
if(!isset($_SESSION['ogrenci_id'])) {
    header('Location: login.php');
    exit;
}

$ogrenci_id = $_SESSION['ogrenci_id'];

// Öğrenci bilgileri
$ogr = $pdo->prepare("SELECT * FROM ogrenciler WHERE id = ?");
$ogr->execute([$ogrenci_id]);
$ogrenci = $ogr->fetch();

// İstatistikler
$stats = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM namaz_kayitlari WHERE ogrenci_id = ?) as toplam_namaz,
        (SELECT COUNT(*) FROM ogrenci_dersler WHERE ogrenci_id = ? AND durum = 'Tamamlandi') as tamamlanan_ders,
        (SELECT COUNT(*) FROM sertifikalar WHERE ogrenci_id = ?) as toplam_sertifika,
        (SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = ?) as toplam_ilave_puan
");
$stats->execute([$ogrenci_id, $ogrenci_id, $ogrenci_id, $ogrenci_id]);
$istatistikler = $stats->fetch();

// Okunmamış mesajlar
$mesajlar = $pdo->prepare("SELECT * FROM ogrenci_mesajlari WHERE ogrenci_id = ? AND okundu = 0 ORDER BY gonderim_zamani DESC");
$mesajlar->execute([$ogrenci_id]);
$okunmamis_mesajlar = $mesajlar->fetchAll();

// Dersler
$dersler = $pdo->prepare("
    SELECT d.ders_adi, dk.kategori_adi, od.durum, od.tamamlanma_tarihi
    FROM ogrenci_dersler od
    JOIN dersler d ON od.ders_id = d.id
    JOIN ders_kategorileri dk ON d.kategori_id = dk.id
    WHERE od.ogrenci_id = ?
    ORDER BY od.durum, dk.sira, d.sira
");
$dersler->execute([$ogrenci_id]);
$ders_listesi = $dersler->fetchAll();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Öğrenci Paneli - <?php echo $ogrenci['ad_soyad']; ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Öğrenci Paneli</h1>
            <nav>
                <a href="index.php" class="active">Panel</a>
                <a href="raporlarim.php">Raporlarım</a>
                <a href="sertifikalarim.php">Sertifikalarım</a>
                <a href="logout.php" style="margin-left: auto">Çıkış</a>
            </nav>
        </header>

        <div style="padding: 30px;">
            <h2>Hoşgeldin, <?php echo $ogrenci['ad_soyad']; ?>!</h2>

            <?php foreach($okunmamis_mesajlar as $msg): ?>
            <div class="alert" style="background: #fff3cd; color: #856404; margin: 20px 0;">
                <strong>📬 Yeni Mesaj:</strong> <?php echo nl2br(htmlspecialchars($msg['mesaj'])); ?>
            </div>
            <?php endforeach; ?>

            <div class="stats-cards">
                <div class="stat-card"><h3><?php echo $istatistikler['toplam_namaz']; ?></h3><p>Toplam Namaz</p></div>
                <div class="stat-card"><h3><?php echo $istatistikler['tamamlanan_ders']; ?></h3><p>Tamamlanan Ders</p></div>
                <div class="stat-card"><h3><?php echo $istatistikler['toplam_sertifika']; ?></h3><p>Sertifika</p></div>
                <div class="stat-card"><h3>+<?php echo $istatistikler['toplam_ilave_puan'] ?? 0; ?></h3><p>İlave Puan</p></div>
            </div>

            <h3>📚 Derslerim</h3>
            <table>
                <thead><tr><th>Kategori</th><th>Ders</th><th>Durum</th><th>Tamamlanma</th></tr></thead>
                <tbody>
                    <?php foreach($ders_listesi as $d): ?>
                    <tr style="background: <?php echo $d['durum']=='Tamamlandi'?'#d4edda':'#f8d7da'; ?>">
                        <td><?php echo $d['kategori_adi']; ?></td>
                        <td><?php echo $d['ders_adi']; ?></td>
                        <td><?php echo $d['durum']=='Tamamlandi'?'✅ Tamamlandı':'⏳ Devam Ediyor'; ?></td>
                        <td><?php echo $d['tamamlanma_tarihi']?date('d.m.Y',strtotime($d['tamamlanma_tarihi'])):'-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>