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

$yil = date('Y');
$ay = date('n');

// İstatistikler
$stats = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM namaz_kayitlari WHERE ogrenci_id = ?) as toplam_namaz,
        (SELECT COUNT(*) FROM ogrenci_dersler WHERE ogrenci_id = ? AND durum = 'Tamamlandi') as tamamlanan_ders,
        (SELECT COUNT(*) FROM sertifikalar WHERE ogrenci_id = ?) as toplam_sertifika,
        (SELECT COALESCE(SUM(puan), 0) FROM ilave_puanlar WHERE ogrenci_id = ?) as toplam_ilave_puan,
        (SELECT COUNT(*) FROM puan_silme_gecmisi WHERE ogrenci_id = ?) as silinen_puan_sayisi
");
$stats->execute([$ogrenci_id, $ogrenci_id, $ogrenci_id, $ogrenci_id, $ogrenci_id]);
$istatistikler = $stats->fetch();

// Yıllık sıralama
$yillik_siralama = $pdo->prepare("
    SELECT ad_soyad, toplam_namaz,
           (SELECT COUNT(*) + 1 FROM yillik_ozetler y2
            WHERE y2.yil = y1.yil AND y2.toplam_namaz > y1.toplam_namaz) as siralama
    FROM yillik_ozetler y1
    WHERE ogrenci_id = ? AND yil = ?
");
$yillik_siralama->execute([$ogrenci_id, $yil]);
$yillik = $yillik_siralama->fetch();

// Aylık sıralama
$aylik_siralama = $pdo->prepare("
    SELECT ad_soyad, toplam_namaz,
           (SELECT COUNT(*) + 1 FROM aylik_ozetler a2
            WHERE a2.yil = a1.yil AND a2.ay = a1.ay AND a2.toplam_namaz > a1.toplam_namaz) as siralama
    FROM aylik_ozetler a1
    WHERE ogrenci_id = ? AND yil = ? AND ay = ?
");
$aylik_siralama->execute([$ogrenci_id, $yil, $ay]);
$aylik = $aylik_siralama->fetch();

// Toplam öğrenci sayısı
$toplam_ogrenci = $pdo->query("SELECT COUNT(*) FROM ogrenciler WHERE aktif = 1")->fetchColumn();

// Okunmamış mesajlar
$mesajlar = $pdo->prepare("SELECT * FROM ogrenci_mesajlari WHERE ogrenci_id = ? AND okundu = 0 ORDER BY gonderim_zamani DESC LIMIT 5");
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

// İlave puanlar
$ilave_puanlar = $pdo->prepare("SELECT * FROM ilave_puanlar WHERE ogrenci_id = ? ORDER BY tarih DESC LIMIT 10");
$ilave_puanlar->execute([$ogrenci_id]);
$ilave_puan_listesi = $ilave_puanlar->fetchAll();

// Cezalar (Silinen puanlar)
$cezalar = $pdo->prepare("SELECT * FROM puan_silme_gecmisi WHERE ogrenci_id = ? ORDER BY silme_zamani DESC LIMIT 10");
$cezalar->execute([$ogrenci_id]);
$ceza_listesi = $cezalar->fetchAll();

// Yıllara göre namaz sayıları
$yillara_gore = $pdo->prepare("SELECT yil, toplam_namaz FROM yillik_ozetler WHERE ogrenci_id = ? ORDER BY yil DESC LIMIT 5");
$yillara_gore->execute([$ogrenci_id]);
$yillik_namazlar = $yillara_gore->fetchAll();

// Bu yılın aylara göre namaz sayıları
$aylara_gore = $pdo->prepare("SELECT ay, toplam_namaz FROM aylik_ozetler WHERE ogrenci_id = ? AND yil = ? ORDER BY ay DESC");
$aylara_gore->execute([$ogrenci_id, $yil]);
$aylik_namazlar = $aylara_gore->fetchAll();

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
                <a href="sifre-degistir.php">🔒 Şifre Değiştir</a>
                <a href="logout.php" style="margin-left: auto">Çıkış</a>
            </nav>
        </header>

        <div style="padding: 30px;">
            <h2>Hoşgeldin, <?php echo $ogrenci['ad_soyad']; ?>! 👋</h2>

            <!-- Okunmamış Mesajlar -->
            <?php if(count($okunmamis_mesajlar) > 0): ?>
            <div style="margin: 20px 0;">
                <h3>📬 Mesajlarım (<?php echo count($okunmamis_mesajlar); ?> Okunmamış)</h3>
                <?php foreach($okunmamis_mesajlar as $msg): ?>
                <div class="alert" style="background: <?php echo $msg['oncelik']=='Acil'?'#f8d7da':($msg['oncelik']=='Önemli'?'#fff3cd':'#d1ecf1'); ?>; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid <?php echo $msg['oncelik']=='Acil'?'#dc3545':($msg['oncelik']=='Önemli'?'#ffc107':'#17a2b8'); ?>;">
                    <strong><?php echo $msg['oncelik']=='Acil'?'🚨':($msg['oncelik']=='Önemli'?'⚠️':'📝'); ?> <?php echo $msg['oncelik']; ?> Mesaj:</strong><br>
                    <?php echo nl2br(htmlspecialchars($msg['mesaj'])); ?><br>
                    <small style="color: #666;"><?php echo date('d.m.Y H:i', strtotime($msg['gonderim_zamani'])); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- İstatistik Kartları -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
                    <h3 style="margin: 0; font-size: 42px;"><?php echo $istatistikler['toplam_namaz']; ?></h3>
                    <p style="margin: 10px 0 0 0;">🕌 Toplam Namaz</p>
                </div>
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
                    <h3 style="margin: 0; font-size: 42px;"><?php echo $istatistikler['tamamlanan_ders']; ?></h3>
                    <p style="margin: 10px 0 0 0;">📚 Tamamlanan Ders</p>
                </div>
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
                    <h3 style="margin: 0; font-size: 42px;"><?php echo $istatistikler['toplam_sertifika']; ?></h3>
                    <p style="margin: 10px 0 0 0;">📜 Sertifika</p>
                </div>
                <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
                    <h3 style="margin: 0; font-size: 42px;">+<?php echo $istatistikler['toplam_ilave_puan']; ?></h3>
                    <p style="margin: 10px 0 0 0;">⭐ İlave Puan</p>
                </div>
                <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
                    <h3 style="margin: 0; font-size: 42px;"><?php echo $istatistikler['silinen_puan_sayisi']; ?></h3>
                    <p style="margin: 10px 0 0 0;">⚠️ Ceza Sayısı</p>
                </div>
            </div>

            <!-- Sıralama Bilgileri -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0;">
                <div style="background: #f8f9fa; padding: 25px; border-radius: 12px; border: 2px solid #28a745;">
                    <h3 style="margin: 0 0 15px 0; color: #28a745;">🏆 <?php echo $yil; ?> Yılı Sıralaman</h3>
                    <?php if($yillik): ?>
                    <p style="font-size: 48px; margin: 10px 0; font-weight: bold; color: #28a745;">
                        #<?php echo $yillik['siralama']; ?>
                    </p>
                    <p style="color: #666; margin: 5px 0;">
                        <?php echo $toplam_ogrenci; ?> öğrenci arasında<br>
                        <strong><?php echo $yillik['toplam_namaz']; ?> vakit namaz</strong> kıldın
                    </p>
                    <?php else: ?>
                    <p style="color: #999;">Bu yıl için henüz namaz kaydın yok.</p>
                    <?php endif; ?>
                </div>

                <div style="background: #f8f9fa; padding: 25px; border-radius: 12px; border: 2px solid #ffc107;">
                    <h3 style="margin: 0 0 15px 0; color: #ffc107;">🎯 <?php echo ayAdi($ay); ?> Ayı Sıralaman</h3>
                    <?php if($aylik): ?>
                    <p style="font-size: 48px; margin: 10px 0; font-weight: bold; color: #ffc107;">
                        #<?php echo $aylik['siralama']; ?>
                    </p>
                    <p style="color: #666; margin: 5px 0;">
                        <?php echo $toplam_ogrenci; ?> öğrenci arasında<br>
                        <strong><?php echo $aylik['toplam_namaz']; ?> vakit namaz</strong> kıldın
                    </p>
                    <?php else: ?>
                    <p style="color: #999;">Bu ay için henüz namaz kaydın yok.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Yıllara Göre Namaz Sayıları -->
            <?php if(count($yillik_namazlar) > 0): ?>
            <div style="background: white; padding: 25px; border-radius: 12px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3>📅 Yıllara Göre Namaz Sayılarım</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                    <?php foreach($yillik_namazlar as $yn): ?>
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                        <p style="margin: 0; font-size: 24px; font-weight: bold;"><?php echo $yn['yil']; ?></p>
                        <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;"><?php echo $yn['toplam_namaz']; ?></p>
                        <p style="margin: 5px 0 0 0; font-size: 14px;">vakit</p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Aylara Göre Namaz Sayıları -->
            <?php if(count($aylik_namazlar) > 0): ?>
            <div style="background: white; padding: 25px; border-radius: 12px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3>📊 <?php echo $yil; ?> Yılı - Aylara Göre Namaz Sayılarım</h3>
                <table style="width: 100%; margin-top: 15px;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; text-align: left;">Ay</th>
                            <th style="padding: 12px; text-align: center;">Namaz Sayısı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($aylik_namazlar as $an): ?>
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 12px;"><?php echo ayAdi($an['ay']) . ' ' . $yil; ?></td>
                            <td style="padding: 12px; text-align: center; font-weight: bold; font-size: 18px; color: #667eea;">
                                <?php echo $an['toplam_namaz']; ?> vakit
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- İlave Puanlar -->
            <?php if(count($ilave_puan_listesi) > 0): ?>
            <div style="background: white; padding: 25px; border-radius: 12px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3>⭐ Aldığım İlave Puanlar</h3>
                <table style="width: 100%; margin-top: 15px;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; text-align: left;">Tarih</th>
                            <th style="padding: 12px; text-align: left;">Açıklama</th>
                            <th style="padding: 12px; text-align: center;">Puan</th>
                            <th style="padding: 12px; text-align: left;">Veren</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ilave_puan_listesi as $ip): ?>
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 12px;"><?php echo date('d.m.Y', strtotime($ip['tarih'])); ?></td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($ip['aciklama']); ?></td>
                            <td style="padding: 12px; text-align: center; font-weight: bold; color: #28a745; font-size: 18px;">
                                +<?php echo $ip['puan']; ?>
                            </td>
                            <td style="padding: 12px; color: #666;"><?php echo htmlspecialchars($ip['veren_kullanici']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Cezalar (Silinen Puanlar) -->
            <?php if(count($ceza_listesi) > 0): ?>
            <div style="background: #fff3cd; padding: 25px; border-radius: 12px; margin: 20px 0; border: 2px solid #ffc107;">
                <h3>⚠️ Aldığım Cezalar (Silinen Puanlar)</h3>
                <table style="width: 100%; margin-top: 15px;">
                    <thead>
                        <tr style="background: rgba(255,193,7,0.2);">
                            <th style="padding: 12px; text-align: left;">Tarih</th>
                            <th style="padding: 12px; text-align: left;">Namaz Vakti</th>
                            <th style="padding: 12px; text-align: left;">Kiminle Geldi</th>
                            <th style="padding: 12px; text-align: left;">Silme Nedeni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ceza_listesi as $c): ?>
                        <tr style="border-bottom: 1px solid #ffc107;">
                            <td style="padding: 12px;"><?php echo date('d.m.Y', strtotime($c['tarih'])); ?></td>
                            <td style="padding: 12px; font-weight: bold;"><?php echo $c['namaz_vakti']; ?></td>
                            <td style="padding: 12px;"><?php echo $c['kiminle_geldi']; ?></td>
                            <td style="padding: 12px; color: #dc3545;"><?php echo htmlspecialchars($c['silme_nedeni']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Derslerim -->
            <div style="background: white; padding: 25px; border-radius: 12px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3>📚 Derslerim</h3>
                <table style="width: 100%; margin-top: 15px;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; text-align: left;">Kategori</th>
                            <th style="padding: 12px; text-align: left;">Ders</th>
                            <th style="padding: 12px; text-align: center;">Durum</th>
                            <th style="padding: 12px; text-align: center;">Tamamlanma</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ders_listesi as $d): ?>
                        <tr style="background: <?php echo $d['durum']=='Tamamlandi'?'#d4edda':'#f8d7da'; ?>; border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 12px;"><?php echo $d['kategori_adi']; ?></td>
                            <td style="padding: 12px; font-weight: bold;"><?php echo $d['ders_adi']; ?></td>
                            <td style="padding: 12px; text-align: center; font-weight: bold;">
                                <?php echo $d['durum']=='Tamamlandi'?'✅ Tamamlandı':'⏳ Devam Ediyor'; ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php echo $d['tamamlanma_tarihi']?date('d.m.Y',strtotime($d['tamamlanma_tarihi'])):'-'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>