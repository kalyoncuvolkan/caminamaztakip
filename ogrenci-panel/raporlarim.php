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

// Yıl ve ay seçimi
$yil = $_GET['yil'] ?? date('Y');
$ay = $_GET['ay'] ?? null;

// Rapor başlığı
if($ay) {
    $raporBaslik = ayAdi($ay) . ' ' . $yil . ' Raporu';
} else {
    $raporBaslik = $yil . ' Yılı Raporu';
}

// Detaylı günlük rapor
if($ay) {
    $aylikStmt = $pdo->prepare("
        SELECT n.tarih,
            GROUP_CONCAT(n.namaz_vakti ORDER BY FIELD(n.namaz_vakti, 'Sabah', 'Öğlen', 'İkindi', 'Akşam', 'Yatsı')) as vakitler,
            COUNT(n.id) as toplam,
            SUM(CASE WHEN n.kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) as babasi_sayisi,
            SUM(CASE WHEN n.kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) as annesi_sayisi,
            SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) as anne_babasi_bonus,
            COALESCE((SELECT SUM(puan) FROM ilave_puanlar
                WHERE ogrenci_id = ? AND tarih = n.tarih AND kategori = 'Namaz'
                AND aciklama NOT LIKE '%(bonus)%'), 0) as gunluk_ilave_puan
        FROM namaz_kayitlari n
        WHERE n.ogrenci_id = ? AND YEAR(n.tarih) = ? AND MONTH(n.tarih) = ?
        GROUP BY n.tarih
        ORDER BY n.tarih DESC
    ");
    $aylikStmt->execute([$ogrenci_id, $ogrenci_id, $yil, $ay]);
    $detayliRapor = $aylikStmt->fetchAll();

    $ozetStmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END) as kendisi,
            SUM(CASE WHEN kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) as babasi,
            SUM(CASE WHEN kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) as annesi,
            SUM(CASE WHEN kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) as anne_babasi,
            COUNT(*) as toplam
        FROM namaz_kayitlari
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ?
    ");
    $ozetStmt->execute([$ogrenci_id, $yil, $ay]);
    $ozetRapor = $ozetStmt->fetch();

    // İlave puan detayları (ay bazlı)
    $ilavePuanDetayStmt = $pdo->prepare("
        SELECT puan, aciklama, tarih, 'eklendi' as durum
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Namaz'
        UNION ALL
        SELECT -puan as puan, CONCAT(aciklama, ' (Silindi: ', silme_nedeni, ')') as aciklama, tarih, 'silindi' as durum
        FROM ilave_puan_silme_gecmisi
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Namaz'
        ORDER BY tarih DESC
    ");
    $ilavePuanDetayStmt->execute([$ogrenci_id, $yil, $ay, $ogrenci_id, $yil, $ay]);
    $ilavePuanDetaylar = $ilavePuanDetayStmt->fetchAll();
} else {
    // Yıllık aylara göre özet
    $yillikStmt = $pdo->prepare("
        SELECT ay, toplam_namaz, toplam_puan
        FROM aylik_ozetler
        WHERE ogrenci_id = ? AND yil = ?
        ORDER BY ay
    ");
    $yillikStmt->execute([$ogrenci_id, $yil]);
    $yillikRapor = $yillikStmt->fetchAll();

    // Yıl özeti
    $yilOzetStmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END) as kendisi,
            SUM(CASE WHEN kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) as babasi,
            SUM(CASE WHEN kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) as annesi,
            SUM(CASE WHEN kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) as anne_babasi,
            COUNT(*) as toplam
        FROM namaz_kayitlari
        WHERE ogrenci_id = ? AND YEAR(tarih) = ?
    ");
    $yilOzetStmt->execute([$ogrenci_id, $yil]);
    $ozetRapor = $yilOzetStmt->fetch();
}

// Toplam puan hesaplama
$ilavePuanStmt = $pdo->prepare("
    SELECT COALESCE(SUM(puan), 0) as ilave_puan
    FROM ilave_puanlar
    WHERE ogrenci_id = ? AND YEAR(tarih) = ? " . ($ay ? "AND MONTH(tarih) = ?" : "") . " AND kategori = 'Namaz'
");
$params = [$ogrenci_id, $yil];
if($ay) $params[] = $ay;
$ilavePuanStmt->execute($params);
$ilavePuan = $ilavePuanStmt->fetchColumn();
$toplamPuan = ($ozetRapor['toplam'] ?? 0) + $ilavePuan;

// Sıralama hesaplama
if($ay) {
    $siralamaStmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as sira
        FROM aylik_ozetler
        WHERE yil = ? AND ay = ? AND (toplam_puan > ? OR (toplam_puan = ? AND toplam_namaz > ?))
    ");
    $siralamaStmt->execute([$yil, $ay, $toplamPuan, $toplamPuan, $ozetRapor['toplam']]);
    $siralama = $siralamaStmt->fetchColumn();

    $toplamOgrenciStmt = $pdo->query("SELECT COUNT(DISTINCT ogrenci_id) FROM aylik_ozetler WHERE yil = $yil AND ay = $ay");
    $toplamOgrenci = $toplamOgrenciStmt->fetchColumn();
} else {
    $siralamaStmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as sira
        FROM yillik_ozetler
        WHERE yil = ? AND (toplam_puan > ? OR (toplam_puan = ? AND toplam_namaz > ?))
    ");
    $siralamaStmt->execute([$yil, $toplamPuan, $toplamPuan, $ozetRapor['toplam']]);
    $siralama = $siralamaStmt->fetchColumn();

    $toplamOgrenciStmt = $pdo->query("SELECT COUNT(DISTINCT ogrenci_id) FROM yillik_ozetler WHERE yil = $yil");
    $toplamOgrenci = $toplamOgrenciStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporlarım - <?php echo $ogrenci['ad_soyad']; ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        @media print {
            header nav, .no-print { display: none !important; }
            body { background: white; }
            .rapor-container { box-shadow: none !important; }
        }

        .rapor-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }

        .ozet-kutular {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .ozet-kutu {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .ozet-kutu .deger {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
        }

        .ozet-kutu .etiket {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .vakit-badge {
            display: inline-block;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 5px;
            font-size: 12px;
            background: #f0f0f0;
            color: #999;
        }

        .vakit-badge.aktif {
            background: #28a745;
            color: white;
            font-weight: bold;
        }

        .siralama-kutu {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
        }

        .siralama-kutu h3 {
            font-size: 3em;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Öğrenci Paneli</h1>
            <nav>
                <a href="index.php">Panel</a>
                <a href="mesajlarim.php">💬 Mesajlarım</a>
                <a href="raporlarim.php" class="active">📊 Raporlarım</a>
                <a href="sertifikalarim.php">🏆 Sertifikalarım</a>
                <a href="logout.php" style="margin-left: auto">Çıkış</a>
            </nav>
        </header>

        <div style="padding: 30px;">
            <h2>📊 Raporlarım</h2>

            <form method="GET" action="" class="no-print" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Yıl:</label>
                        <select name="yil" onchange="this.form.submit()" style="padding: 10px; border-radius: 8px; border: 2px solid #ddd;">
                            <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $yil ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Ay:</label>
                        <select name="ay" onchange="this.form.submit()" style="padding: 10px; border-radius: 8px; border: 2px solid #ddd;">
                            <option value="">Tüm Yıl</option>
                            <?php for($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m == $ay ? 'selected' : ''; ?>><?php echo ayAdi($m); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="button" onclick="window.print()" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        🖨️ Yazdır
                    </button>
                </div>
            </form>

            <div class="rapor-container">
                <h3 style="text-align: center; color: #667eea; margin-bottom: 20px;"><?php echo $raporBaslik; ?></h3>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">
                    <strong><?php echo $ogrenci['ad_soyad']; ?></strong> - Yaş: <?php echo yasHesapla($ogrenci['dogum_tarihi']); ?>
                </p>

                <!-- Sıralama -->
                <div class="siralama-kutu">
                    <h3>#<?php echo $siralama; ?></h3>
                    <p style="font-size: 1.2em; margin: 10px 0;"><?php echo $toplamOgrenci; ?> öğrenci arasında</p>
                    <p style="font-size: 1.5em; font-weight: bold; margin: 10px 0;"><?php echo $toplamPuan; ?> Toplam Puan</p>
                    <p style="opacity: 0.9;"><?php echo $ozetRapor['toplam']; ?> vakit namaz + <?php echo $ilavePuan; ?> ilave puan</p>
                </div>

                <!-- Özet Bilgiler -->
                <h4 style="margin: 30px 0 15px 0;">📈 Özet Bilgiler</h4>
                <div class="ozet-kutular">
                    <div class="ozet-kutu">
                        <div class="etiket">Tek Başına</div>
                        <div class="deger"><?php echo $ozetRapor['kendisi'] ?? 0; ?></div>
                    </div>
                    <div class="ozet-kutu" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="etiket">Babası</div>
                        <div class="deger"><?php echo $ozetRapor['babasi'] ?? 0; ?></div>
                    </div>
                    <div class="ozet-kutu" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <div class="etiket">Annesi</div>
                        <div class="deger"><?php echo $ozetRapor['annesi'] ?? 0; ?></div>
                    </div>
                    <div class="ozet-kutu" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <div class="etiket">Anne-Babası</div>
                        <div class="deger"><?php echo $ozetRapor['anne_babasi'] ?? 0; ?></div>
                    </div>
                    <div class="ozet-kutu" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
                        <div class="etiket">Toplam Vakit</div>
                        <div class="deger"><?php echo $ozetRapor['toplam'] ?? 0; ?></div>
                    </div>
                </div>

                <?php if($ay && count($detayliRapor) > 0): ?>
                <!-- Detaylı Aylık Rapor -->
                <h4 style="margin: 30px 0 15px 0;">📅 Günlük Detay</h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Tarih</th>
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Namaz Vakitleri</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Vakit</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Bonus</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($detayliRapor as $satir): ?>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">
                                <strong><?php echo gunAdi($satir['tarih']); ?></strong><br>
                                <small style="color: #666;"><?php echo date('d.m.Y', strtotime($satir['tarih'])); ?></small>
                            </td>
                            <td style="padding: 12px; border: 1px solid #ddd;">
                                <?php
                                $vakitler = ['Sabah', 'Öğlen', 'İkindi', 'Akşam', 'Yatsı'];
                                $gelenVakitler = explode(',', $satir['vakitler']);
                                foreach($vakitler as $vakit) {
                                    if(in_array($vakit, $gelenVakitler)) {
                                        echo '<span class="vakit-badge aktif">' . $vakit . '</span> ';
                                    } else {
                                        echo '<span class="vakit-badge">' . $vakit . '</span> ';
                                    }
                                }
                                ?>
                            </td>
                            <td style="padding: 12px; text-align: center; font-weight: bold; border: 1px solid #ddd;">
                                <?php echo $satir['toplam']; ?>
                            </td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">
                                <?php
                                $bonus = $satir['babasi_sayisi'] + $satir['annesi_sayisi'] + $satir['anne_babasi_bonus'] + $satir['gunluk_ilave_puan'];
                                echo $bonus > 0 ? '<span style="color: #28a745; font-weight: bold;">+' . $bonus . '</span>' : '-';
                                ?>
                            </td>
                            <td style="padding: 12px; text-align: center; font-weight: bold; font-size: 1.2em; color: #667eea; border: 1px solid #ddd;">
                                <?php echo $satir['toplam'] + $bonus; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- İlave Puan Detayları -->
                <?php if(!empty($ilavePuanDetaylar)): ?>
                <h4 style="margin: 30px 0 15px 0;">⭐ İlave Puan Detayları</h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Tarih</th>
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Açıklama</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Puan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ilavePuanDetaylar as $detay): ?>
                        <tr style="<?php if($detay['puan'] < 0) echo 'background: #fff3cd;'; ?>">
                            <td style="padding: 12px; border: 1px solid #ddd;"><?php echo date('d.m.Y', strtotime($detay['tarih'])); ?></td>
                            <td style="padding: 12px; border: 1px solid #ddd;"><?php echo htmlspecialchars($detay['aciklama']); ?></td>
                            <td style="padding: 12px; text-align: center; font-weight: bold; color: <?php echo $detay['puan'] < 0 ? '#dc3545' : '#28a745'; ?>; border: 1px solid #ddd;">
                                <?php echo $detay['puan'] > 0 ? '+' : ''; ?><?php echo $detay['puan']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <?php elseif(!$ay && count($yillikRapor) > 0): ?>
                <!-- Yıllık Aylara Göre Rapor -->
                <h4 style="margin: 30px 0 15px 0;">📊 Aylara Göre Namaz Sayıları</h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Ay</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Namaz Sayısı</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Toplam Puan</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;" class="no-print">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($yillikRapor as $ay_rapor): ?>
                        <tr>
                            <td style="padding: 12px; font-weight: bold; border: 1px solid #ddd;">
                                <?php echo ayAdi($ay_rapor['ay']) . ' ' . $yil; ?>
                            </td>
                            <td style="padding: 12px; text-align: center; font-size: 1.2em; color: #667eea; border: 1px solid #ddd;">
                                <?php echo $ay_rapor['toplam_namaz']; ?> vakit
                            </td>
                            <td style="padding: 12px; text-align: center; font-size: 1.2em; font-weight: bold; color: #28a745; border: 1px solid #ddd;">
                                <?php echo $ay_rapor['toplam_puan']; ?> puan
                            </td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;" class="no-print">
                                <a href="?yil=<?php echo $yil; ?>&ay=<?php echo $ay_rapor['ay']; ?>" style="background: #667eea; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; display: inline-block;">
                                    📄 Detay
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: #999;">
                    <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                    <h3>Henüz namaz kaydı yok</h3>
                    <p>Bu dönem için henüz namaz kaydınız bulunmuyor.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
