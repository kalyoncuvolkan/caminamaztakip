<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$ogrenci_id = $_GET['id'] ?? 0;
$yil = $_GET['yil'] ?? date('Y');
$ay = $_GET['ay'] ?? '';

$ogrenciStmt = $pdo->prepare("SELECT * FROM ogrenciler WHERE id = ?");
$ogrenciStmt->execute([$ogrenci_id]);
$ogrenci = $ogrenciStmt->fetch();

if(!$ogrenci) {
    header('Location: index.php');
    exit;
}

$raporBaslik = '';
$detayliRapor = [];
$ozetRapor = [];
$toplamOgrenci = 0;
$siralama = 0;

if($ay) {
    $raporBaslik = $ogrenci['ad_soyad'] . ' ' . $yil . ' ' . ayAdi($ay) . ' ayı namaz kılma raporu';
    
    // Günlük gruplu namaz kayıtları
    $gunlukStmt = $pdo->prepare("
        SELECT
            n.tarih,
            GROUP_CONCAT(n.namaz_vakti ORDER BY
                FIELD(n.namaz_vakti, 'Sabah', 'Öğlen', 'İkindi', 'Akşam', 'Yatsı')
            ) as vakitler,
            COUNT(n.id) as toplam,
            GROUP_CONCAT(DISTINCT n.kiminle_geldi) as kiminle_list,
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
    $gunlukStmt->execute([$ogrenci_id, $ogrenci_id, $yil, $ay]);
    $detayliRapor = $gunlukStmt->fetchAll();
    
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
    
    // Öğrencinin toplam puanını hesapla (namaz + ilave namaz puan + ilave ders puan)
    $ilavePuanStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN kategori = 'Namaz' THEN puan ELSE 0 END), 0) as ilave_namaz_puan,
            COALESCE(SUM(CASE WHEN kategori = 'Ders' THEN puan ELSE 0 END), 0) as ilave_ders_puan
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ?
    ");
    $ilavePuanStmt->execute([$ogrenci_id, $yil, $ay]);
    $ilavePuanlar = $ilavePuanStmt->fetch();
    $ilaveNamazPuan = $ilavePuanlar['ilave_namaz_puan'] ?? 0;
    $ilaveDersPuan = $ilavePuanlar['ilave_ders_puan'] ?? 0;
    $toplamPuan = ($ozetRapor['toplam'] ?? 0) + $ilaveNamazPuan + $ilaveDersPuan;

    // İlave namaz puan detaylarını çek (hem eklenmiş hem silinmiş puanlar)
    $ilaveNamazPuanDetayStmt = $pdo->prepare("
        SELECT puan, aciklama, tarih, 'eklendi' as durum
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Namaz'
        UNION ALL
        SELECT -puan as puan, CONCAT(aciklama, ' (Silindi: ', silme_nedeni, ')') as aciklama, tarih, 'silindi' as durum
        FROM ilave_puan_silme_gecmisi
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Namaz'
        ORDER BY tarih DESC
    ");
    $ilaveNamazPuanDetayStmt->execute([$ogrenci_id, $yil, $ay, $ogrenci_id, $yil, $ay]);
    $ilaveNamazPuanDetaylar = $ilaveNamazPuanDetayStmt->fetchAll();

    // İlave ders puan detaylarını çek
    $ilaveDersPuanDetayStmt = $pdo->prepare("
        SELECT puan, aciklama, tarih, 'eklendi' as durum
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Ders'
        UNION ALL
        SELECT -puan as puan, CONCAT(aciklama, ' (Silindi: ', silme_nedeni, ')') as aciklama, tarih, 'silindi' as durum
        FROM ilave_puan_silme_gecmisi
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Ders'
        ORDER BY tarih DESC
    ");
    $ilaveDersPuanDetayStmt->execute([$ogrenci_id, $yil, $ay, $ogrenci_id, $yil, $ay]);
    $ilaveDersPuanDetaylar = $ilaveDersPuanDetayStmt->fetchAll();

    // Sıralama hesaplama (namaz + ilave namaz + ilave ders puanları dahil)
    $siralamaStmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as sira
        FROM (
            SELECT
                o.id,
                COUNT(n.id) as toplam_namaz,
                (COUNT(n.id) +
                 COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Namaz'), 0) +
                 COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Ders'), 0)) as toplam_puan
            FROM ogrenciler o
            LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id
                AND YEAR(n.tarih) = ? AND MONTH(n.tarih) = ?
            GROUP BY o.id
        ) as temp
        WHERE toplam_puan > ? OR (toplam_puan = ? AND toplam_namaz > ?)
    ");
    $siralamaStmt->execute([$yil, $ay, $yil, $ay, $yil, $ay, $toplamPuan, $toplamPuan, $ozetRapor['toplam'] ?? 0]);
    $siralama = $siralamaStmt->fetchColumn();

    // Toplam öğrenci sayısı
    $toplamOgrenciStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ogrenci_id) FROM namaz_kayitlari
        WHERE YEAR(tarih) = ? AND MONTH(tarih) = ?
    ");
    $toplamOgrenciStmt->execute([$yil, $ay]);
    $toplamOgrenci = $toplamOgrenciStmt->fetchColumn();
    
} else {
    $raporBaslik = $ogrenci['ad_soyad'] . ' ' . $yil . ' yılı namaz kılma raporu';

    // Günlük gruplu namaz kayıtları (yıllık)
    $aylikStmt = $pdo->prepare("
        SELECT
            n.tarih,
            GROUP_CONCAT(n.namaz_vakti ORDER BY
                FIELD(n.namaz_vakti, 'Sabah', 'Öğlen', 'İkindi', 'Akşam', 'Yatsı')
            ) as vakitler,
            COUNT(n.id) as toplam,
            GROUP_CONCAT(DISTINCT n.kiminle_geldi) as kiminle_list,
            SUM(CASE WHEN n.kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) as babasi_sayisi,
            SUM(CASE WHEN n.kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) as annesi_sayisi,
            SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) as anne_babasi_bonus,
            COALESCE((SELECT SUM(puan) FROM ilave_puanlar
                WHERE ogrenci_id = ? AND tarih = n.tarih AND kategori = 'Namaz'
                AND aciklama NOT LIKE '%(bonus)%'), 0) as gunluk_ilave_puan
        FROM namaz_kayitlari n
        WHERE n.ogrenci_id = ? AND YEAR(n.tarih) = ?
        GROUP BY n.tarih
        ORDER BY n.tarih DESC
    ");
    $aylikStmt->execute([$ogrenci_id, $ogrenci_id, $yil]);
    $detayliRapor = $aylikStmt->fetchAll();
    
    $ozetStmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END) as kendisi,
            SUM(CASE WHEN kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) as babasi,
            SUM(CASE WHEN kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) as annesi,
            SUM(CASE WHEN kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) as anne_babasi,
            COUNT(*) as toplam
        FROM namaz_kayitlari 
        WHERE ogrenci_id = ? AND YEAR(tarih) = ?
    ");
    $ozetStmt->execute([$ogrenci_id, $yil]);
    $ozetRapor = $ozetStmt->fetch();
    
    // Öğrencinin toplam puanını hesapla (namaz + ilave namaz puan + ilave ders puan)
    $ilavePuanStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN kategori = 'Namaz' THEN puan ELSE 0 END), 0) as ilave_namaz_puan,
            COALESCE(SUM(CASE WHEN kategori = 'Ders' THEN puan ELSE 0 END), 0) as ilave_ders_puan
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ?
    ");
    $ilavePuanStmt->execute([$ogrenci_id, $yil]);
    $ilavePuanlar = $ilavePuanStmt->fetch();
    $ilaveNamazPuan = $ilavePuanlar['ilave_namaz_puan'] ?? 0;
    $ilaveDersPuan = $ilavePuanlar['ilave_ders_puan'] ?? 0;
    $toplamPuan = ($ozetRapor['toplam'] ?? 0) + $ilaveNamazPuan + $ilaveDersPuan;

    // İlave namaz puan detaylarını çek
    $ilaveNamazPuanDetayStmt = $pdo->prepare("
        SELECT puan, aciklama, tarih
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND kategori = 'Namaz'
        ORDER BY tarih DESC
    ");
    $ilaveNamazPuanDetayStmt->execute([$ogrenci_id, $yil]);
    $ilaveNamazPuanDetaylar = $ilaveNamazPuanDetayStmt->fetchAll();

    // İlave ders puan detaylarını çek
    $ilaveDersPuanDetayStmt = $pdo->prepare("
        SELECT puan, aciklama, tarih
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND kategori = 'Ders'
        ORDER BY tarih DESC
    ");
    $ilaveDersPuanDetayStmt->execute([$ogrenci_id, $yil]);
    $ilaveDersPuanDetaylar = $ilaveDersPuanDetayStmt->fetchAll();

    // Sıralama hesaplama (namaz + ilave namaz + ilave ders puanları dahil)
    $siralamaStmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as sira
        FROM (
            SELECT
                o.id,
                COUNT(n.id) as toplam_namaz,
                (COUNT(n.id) +
                 COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND YEAR(tarih) = ? AND kategori = 'Namaz'), 0) +
                 COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND YEAR(tarih) = ? AND kategori = 'Ders'), 0)) as toplam_puan
            FROM ogrenciler o
            LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id AND YEAR(n.tarih) = ?
            GROUP BY o.id
        ) as temp
        WHERE toplam_puan > ? OR (toplam_puan = ? AND toplam_namaz > ?)
    ");
    $siralamaStmt->execute([$yil, $yil, $yil, $toplamPuan, $toplamPuan, $ozetRapor['toplam'] ?? 0]);
    $siralama = $siralamaStmt->fetchColumn();

    // Toplam öğrenci sayısı
    $toplamOgrenciStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ogrenci_id) FROM namaz_kayitlari WHERE YEAR(tarih) = ?
    ");
    $toplamOgrenciStmt->execute([$yil]);
    $toplamOgrenci = $toplamOgrenciStmt->fetchColumn();
}

$yillar = $pdo->query("SELECT DISTINCT YEAR(tarih) as yil FROM namaz_kayitlari ORDER BY yil DESC")->fetchAll();

$aktif_sayfa = 'raporlar';
$sayfa_basligi = 'Özel Rapor - Cami Namaz Takip';
require_once 'config/header.php';
?>

    <style>
        @media print {
            /* Yazdırma sırasında gizlenecekler */
            .rapor-filtre,
            .ogrenci-bilgi-kutu,
            .rapor-butonlar,
            nav,
            .btn-print,
            .btn-geri,
            h2 {
                display: none !important;
            }

            /* Gizli elementleri göster */
            #ilavePuanDetayDiv {
                display: block !important;
            }

            /* Emojileri gizle */
            .no-print {
                display: none !important;
            }

            /* Sayfa ayarları */
            @page {
                size: A4;
                margin: 15mm;
            }

            body {
                margin: 0;
                padding: 10px;
            }

            /* Başlık stil - kompakt */
            .rapor-baslik {
                margin: 0 0 10px 0 !important;
                padding: 5px !important;
                page-break-after: avoid;
                border-bottom: 2px solid #000;
            }

            .rapor-baslik h3 {
                font-size: 13px !important;
                margin: 0 !important;
                text-align: center;
                font-weight: bold;
            }

            /* Özet bilgileri - minimal ve kompakt */
            .rapor-ozet {
                display: block !important;
                margin: 10px 0 !important;
                padding: 8px !important;
                border: 1px solid #000 !important;
                background: #f8f9fa !important;
                page-break-inside: avoid;
            }

            .rapor-ozet h4 {
                display: none !important;
            }

            .ozet-kutular {
                display: block !important;
                margin: 0 !important;
                font-size: 9px !important;
            }

            .ozet-kutu {
                display: inline-block !important;
                margin: 0 8px 5px 0 !important;
                padding: 3px 6px !important;
                border: 1px solid #000 !important;
                background: white !important;
                font-size: 9px !important;
            }

            .ozet-kutu .etiket {
                font-weight: normal !important;
                font-size: 9px !important;
            }

            .ozet-kutu .deger {
                font-weight: bold !important;
                font-size: 10px !important;
                margin-left: 3px !important;
            }

            .ozet-kutu small {
                display: none !important;
            }

            /* Sıralama bilgisi - kompakt */
            .siralama-bilgi {
                display: block !important;
                margin: 8px 0 !important;
                padding: 5px !important;
                text-align: center !important;
                font-size: 10px !important;
                border-top: 1px solid #ddd !important;
            }

            .siralama-bilgi p {
                margin: 0 !important;
                font-size: 10px !important;
            }

            .siralama-vurgu {
                font-weight: bold !important;
                font-size: 11px !important;
            }

            /* İlave Puan Detay Tablosu - kompakt */
            #ilavePuanDetayDiv {
                margin-top: 10px !important;
                padding: 8px !important;
                background: white !important;
                border: 1px solid #000 !important;
                page-break-inside: avoid;
            }

            #ilavePuanDetayDiv h4 {
                font-size: 11px !important;
                margin: 0 0 5px 0 !important;
                color: #000 !important;
            }

            #ilavePuanDetayDiv table {
                margin: 0 !important;
                font-size: 9px !important;
            }

            #ilavePuanDetayDiv th {
                background: #e0e0e0 !important;
                color: #000 !important;
                padding: 4px !important;
                font-size: 9px !important;
                border: 1px solid #000 !important;
            }

            #ilavePuanDetayDiv td {
                padding: 3px !important;
                font-size: 8px !important;
                border: 1px solid #ddd !important;
            }

            #ilavePuanDetayDiv tfoot td {
                background: #f0f0f0 !important;
                font-weight: bold !important;
                font-size: 9px !important;
                border: 1px solid #000 !important;
            }

            /* Ana tablo stil - kompakt */
            .detayli-rapor {
                margin-bottom: 10px !important;
            }

            .detayli-rapor table {
                width: 100% !important;
                font-size: 9px !important;
                border-collapse: collapse !important;
                page-break-inside: auto;
            }

            thead {
                display: table-header-group;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            th {
                background: #e0e0e0 !important;
                color: #000 !important;
                padding: 4px 3px !important;
                font-size: 9px !important;
                border: 1px solid #000 !important;
                text-align: center !important;
            }

            /* İlk iki kolon sola hizalı */
            th:first-child,
            th:nth-child(2) {
                text-align: left !important;
            }

            td {
                padding: 3px 2px !important;
                border: 1px solid #ddd !important;
                font-size: 8px !important;
            }

            td strong {
                font-size: 9px !important;
            }

            td small {
                font-size: 7px !important;
            }

            /* Badge'ler - kompakt */
            .vakit-badge {
                padding: 1px 3px !important;
                font-size: 7px !important;
                margin: 0px !important;
                border: 1px solid #ccc !important;
                display: inline-block !important;
            }

            .vakit-badge.aktif {
                background: #ddd !important;
                color: #000 !important;
                border-color: #000 !important;
                font-weight: bold !important;
            }

            /* Toplam satırı vurgulu */
            td[style*="background: #e8f5e9"] {
                background: #f0f0f0 !important;
                font-weight: bold !important;
            }
        }
    </style>

        <div class="ozel-rapor-container">
            <h2>📑 Öğrenci Özel Rapor</h2>
            
            <div class="ogrenci-bilgi-kutu">
                <h3><?php echo $ogrenci['ad_soyad']; ?></h3>
                <p>Yaş: <?php echo yasHesapla($ogrenci['dogum_tarihi']); ?> | Baba: <?php echo $ogrenci['baba_adi']; ?> | Anne: <?php echo $ogrenci['anne_adi']; ?></p>
            </div>
            
            <form method="GET" action="" class="rapor-filtre">
                <input type="hidden" name="id" value="<?php echo $ogrenci_id; ?>">
                
                <div class="form-group inline">
                    <label>Yıl:</label>
                    <select name="yil" onchange="this.form.submit()">
                        <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $yil ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group inline">
                    <label>Ay:</label>
                    <select name="ay" onchange="this.form.submit()">
                        <option value="">Tüm Yıl</option>
                        <?php for($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $ay ? 'selected' : ''; ?>><?php echo ayAdi($m); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </form>

            <div class="rapor-baslik">
                <h3><?php echo $raporBaslik; ?></h3>
            </div>

            <?php if(count($detayliRapor) > 0): ?>
            <div class="detayli-rapor">
                <table>
                    <thead>
                        <tr>
                            <th>Gün / Tarih</th>
                            <th>Namaz Vakitleri</th>
                            <th style="text-align: center;">Vakit</th>
                            <th style="text-align: center;">Bonus</th>
                            <th style="text-align: center;">Toplam Puan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($detayliRapor as $satir): ?>
                        <tr>
                            <td>
                                <?php
                                $gun_adi = gunAdi($satir['tarih']);
                                $tarih_formatted = date('d.m.Y', strtotime($satir['tarih']));
                                echo '<strong>' . $gun_adi . '</strong><br>';
                                echo '<small style="color: #666;">' . $tarih_formatted . '</small>';
                                ?>
                            </td>
                            <td>
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
                            <td style="text-align: center;"><strong><?php echo $satir['toplam']; ?></strong></td>
                            <td style="text-align: center;">
                                <?php
                                // Bonus puan hesapla (namaz ile gelen + puan yönetiminden eklenen)
                                $namaz_bonus = $satir['babasi_sayisi'] + $satir['annesi_sayisi'] + $satir['anne_babasi_bonus'];
                                $yonetim_bonus = $satir['gunluk_ilave_puan'] ?? 0;
                                $bonus = $namaz_bonus + $yonetim_bonus;

                                if($bonus > 0) {
                                    echo '<span style="color: #28a745; font-weight: bold;">+' . $bonus . '</span>';
                                } else {
                                    echo '<span style="color: #999;">-</span>';
                                }
                                ?>
                            </td>
                            <td style="text-align: center; background: #e8f5e9;">
                                <?php
                                $toplam_puan = $satir['toplam'] + $bonus;
                                echo '<strong style="color: #667eea; font-size: 1.1em;">' . $toplam_puan . '</strong>';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="rapor-ozet">
                <h4><span class="no-print">📊 </span>Özet Bilgiler</h4>
                <div class="ozet-kutular">
                    <div class="ozet-kutu">
                        <span class="etiket">Tek Başına:</span>
                        <span class="deger"><?php echo $ozetRapor['kendisi'] ?? 0; ?></span>
                    </div>
                    <div class="ozet-kutu">
                        <span class="etiket">Babası:</span>
                        <span class="deger"><?php echo $ozetRapor['babasi'] ?? 0; ?></span>
                    </div>
                    <div class="ozet-kutu">
                        <span class="etiket">Annesi:</span>
                        <span class="deger"><?php echo $ozetRapor['annesi'] ?? 0; ?></span>
                    </div>
                    <div class="ozet-kutu">
                        <span class="etiket">Anne-Babası:</span>
                        <span class="deger"><?php echo $ozetRapor['anne_babasi'] ?? 0; ?></span>
                    </div>
                    <div class="ozet-kutu" style="background: #fff3cd; border: 2px solid #ffc107;">
                        <span class="etiket">Toplam Vakit:</span>
                        <span class="deger"><?php echo $ozetRapor['toplam'] ?? 0; ?></span>
                    </div>
                    <div class="ozet-kutu" style="background: #d4edda; border: 2px solid #28a745; cursor: pointer;" onclick="toggleIlaveNamazPuanDetay()">
                        <span class="etiket">İlave Namaz Puanı:</span>
                        <span class="deger" style="color: #28a745;">+<?php echo $ilaveNamazPuan ?? 0; ?></span>
                        <?php if(!empty($ilaveNamazPuanDetaylar)): ?>
                        <small style="display: block; margin-top: 5px; color: #666; font-size: 11px;">
                            ▼ Detay için tıklayın
                        </small>
                        <?php endif; ?>
                    </div>
                    <div class="ozet-kutu" style="background: #e3f2fd; border: 2px solid #2196F3; cursor: pointer;" onclick="toggleIlaveDersPuanDetay()">
                        <span class="etiket">İlave Ders Puanı:</span>
                        <span class="deger" style="color: #2196F3;">+<?php echo $ilaveDersPuan ?? 0; ?></span>
                        <?php if(!empty($ilaveDersPuanDetaylar)): ?>
                        <small style="display: block; margin-top: 5px; color: #666; font-size: 11px;">
                            ▼ Detay için tıklayın
                        </small>
                        <?php endif; ?>
                    </div>
                    <div class="ozet-kutu toplam" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <span class="etiket">TOPLAM PUAN:</span>
                        <span class="deger" style="font-size: 1.5em;"><?php echo $toplamPuan ?? 0; ?></span>
                    </div>
                </div>

                <?php if(!empty($ilaveNamazPuanDetaylar)): ?>
                <div id="ilaveNamazPuanDetayDiv" style="display: none; margin-top: 20px; background: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px solid #28a745;">
                    <h4 style="margin-top: 0; color: #28a745;"><span class="no-print">💰 </span>İlave Namaz Puanı Detayları</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #28a745; color: white;">
                                <th style="padding: 10px; text-align: left;">Tarih</th>
                                <th style="padding: 10px; text-align: left;">Açıklama</th>
                                <th style="padding: 10px; text-align: center;">Puan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ilaveNamazPuanDetaylar as $detay): ?>
                            <tr style="border-bottom: 1px solid #ddd;<?php if($detay['puan'] < 0) echo ' background: #fff3cd;'; ?>">
                                <td style="padding: 10px;"><?php echo date('d.m.Y', strtotime($detay['tarih'])); ?></td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars($detay['aciklama']); ?></td>
                                <td style="padding: 10px; text-align: center; font-weight: bold; color: <?php echo $detay['puan'] < 0 ? '#dc3545' : '#28a745'; ?>;">
                                    <?php echo $detay['puan'] > 0 ? '+' : ''; ?><?php echo $detay['puan']; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #e8f5e9; font-weight: bold;">
                                <td colspan="2" style="padding: 10px; text-align: right;">TOPLAM:</td>
                                <td style="padding: 10px; text-align: center; color: #28a745; font-size: 1.2em;">
                                    +<?php echo $ilaveNamazPuan; ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>

                <?php if(!empty($ilaveDersPuanDetaylar)): ?>
                <div id="ilaveDersPuanDetayDiv" style="display: none; margin-top: 20px; background: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px solid #2196F3;">
                    <h4 style="margin-top: 0; color: #2196F3;"><span class="no-print">📚 </span>İlave Ders Puanı Detayları</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #2196F3; color: white;">
                                <th style="padding: 10px; text-align: left;">Tarih</th>
                                <th style="padding: 10px; text-align: left;">Açıklama</th>
                                <th style="padding: 10px; text-align: center;">Puan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ilaveDersPuanDetaylar as $detay): ?>
                            <tr style="border-bottom: 1px solid #ddd;<?php if($detay['puan'] < 0) echo ' background: #fff3cd;'; ?>">
                                <td style="padding: 10px;"><?php echo date('d.m.Y', strtotime($detay['tarih'])); ?></td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars($detay['aciklama']); ?></td>
                                <td style="padding: 10px; text-align: center; font-weight: bold; color: <?php echo $detay['puan'] < 0 ? '#dc3545' : '#2196F3'; ?>;">
                                    <?php echo $detay['puan'] > 0 ? '+' : ''; ?><?php echo $detay['puan']; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #e3f2fd; font-weight: bold;">
                                <td colspan="2" style="padding: 10px; text-align: right;">TOPLAM:</td>
                                <td style="padding: 10px; text-align: center; color: #2196F3; font-size: 1.2em;">
                                    +<?php echo $ilaveDersPuan; ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>

                <div class="siralama-bilgi">
                    <p class="siralama-metin">
                        <?php 
                        if($ay) {
                            echo $yil . ' ' . ayAdi($ay) . ' ayında namaz kılma programında ';
                        } else {
                            echo $yil . ' yılı namaz kılma programında ';
                        }
                        echo '<strong>' . $toplamOgrenci . '</strong> öğrenci arasından ';
                        echo '<span class="siralama-vurgu">' . $siralama . '. oldunuz</span>';
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="rapor-butonlar">
                <button onclick="window.print()" class="btn-print">🖨️ Yazdır</button>
                <a href="index.php" class="btn-geri">← Geri Dön</a>
            </div>
            <?php else: ?>
            <div class="alert info">Bu dönem için kayıt bulunmamaktadır.</div>
            <?php endif; ?>
        </div>

    <script>
        function toggleIlaveNamazPuanDetay() {
            const detayDiv = document.getElementById('ilaveNamazPuanDetayDiv');
            if (detayDiv) {
                if (detayDiv.style.display === 'none') {
                    detayDiv.style.display = 'block';
                } else {
                    detayDiv.style.display = 'none';
                }
            }
        }

        function toggleIlaveDersPuanDetay() {
            const detayDiv = document.getElementById('ilaveDersPuanDetayDiv');
            if (detayDiv) {
                if (detayDiv.style.display === 'none') {
                    detayDiv.style.display = 'block';
                } else {
                    detayDiv.style.display = 'none';
                }
            }
        }

        // Yazdırma öncesi ilave puan detaylarını göster
        window.addEventListener('beforeprint', function() {
            const namazDetayDiv = document.getElementById('ilaveNamazPuanDetayDiv');
            if (namazDetayDiv) {
                namazDetayDiv.setAttribute('data-was-hidden', namazDetayDiv.style.display === 'none' ? 'true' : 'false');
                namazDetayDiv.style.display = 'block';
            }
            const dersDetayDiv = document.getElementById('ilaveDersPuanDetayDiv');
            if (dersDetayDiv) {
                dersDetayDiv.setAttribute('data-was-hidden', dersDetayDiv.style.display === 'none' ? 'true' : 'false');
                dersDetayDiv.style.display = 'block';
            }
        });

        // Yazdırma sonrası önceki duruma döndür
        window.addEventListener('afterprint', function() {
            const namazDetayDiv = document.getElementById('ilaveNamazPuanDetayDiv');
            if (namazDetayDiv && namazDetayDiv.getAttribute('data-was-hidden') === 'true') {
                namazDetayDiv.style.display = 'none';
            }
            const dersDetayDiv = document.getElementById('ilaveDersPuanDetayDiv');
            if (dersDetayDiv && dersDetayDiv.getAttribute('data-was-hidden') === 'true') {
                dersDetayDiv.style.display = 'none';
            }
        });
    </script>
<?php require_once 'config/footer.php'; ?>