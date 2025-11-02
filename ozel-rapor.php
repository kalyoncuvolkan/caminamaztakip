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
            SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 2 ELSE 0 END) as anne_babasi_bonus,
            COALESCE((SELECT SUM(puan) FROM ilave_puanlar
                WHERE ogrenci_id = ? AND tarih = n.tarih AND kategori = 'Namaz'), 0) as gunluk_ilave_puan
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
    
    // Öğrencinin toplam puanını hesapla (namaz + ilave puan)
    $ilavePuanStmt = $pdo->prepare("
        SELECT COALESCE(SUM(puan), 0) as ilave_puan
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Namaz'
    ");
    $ilavePuanStmt->execute([$ogrenci_id, $yil, $ay]);
    $ilavePuan = $ilavePuanStmt->fetchColumn();
    $toplamPuan = ($ozetRapor['toplam'] ?? 0) + $ilavePuan;

    // İlave puan detaylarını çek
    $ilavePuanDetayStmt = $pdo->prepare("
        SELECT puan, aciklama, tarih
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Namaz'
        ORDER BY tarih DESC
    ");
    $ilavePuanDetayStmt->execute([$ogrenci_id, $yil, $ay]);
    $ilavePuanDetaylar = $ilavePuanDetayStmt->fetchAll();

    // Sıralama hesaplama (aylik_ozetler VIEW ile aynı mantık)
    $siralamaStmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as sira
        FROM (
            SELECT
                o.id,
                COUNT(n.id) as toplam_namaz,
                (COUNT(n.id) + COALESCE((SELECT SUM(puan) FROM ilave_puanlar
                    WHERE ogrenci_id = o.id AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Namaz'), 0)) as toplam_puan
            FROM ogrenciler o
            LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id
                AND YEAR(n.tarih) = ? AND MONTH(n.tarih) = ?
            GROUP BY o.id
        ) as temp
        WHERE toplam_puan > ? OR (toplam_puan = ? AND toplam_namaz > ?)
    ");
    $siralamaStmt->execute([$yil, $ay, $yil, $ay, $toplamPuan, $toplamPuan, $ozetRapor['toplam'] ?? 0]);
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
            SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 2 ELSE 0 END) as anne_babasi_bonus,
            COALESCE((SELECT SUM(puan) FROM ilave_puanlar
                WHERE ogrenci_id = ? AND tarih = n.tarih AND kategori = 'Namaz'), 0) as gunluk_ilave_puan
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
    
    // Öğrencinin toplam puanını hesapla (namaz + ilave puan)
    $ilavePuanStmt = $pdo->prepare("
        SELECT COALESCE(SUM(puan), 0) as ilave_puan
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND kategori = 'Namaz'
    ");
    $ilavePuanStmt->execute([$ogrenci_id, $yil]);
    $ilavePuan = $ilavePuanStmt->fetchColumn();
    $toplamPuan = ($ozetRapor['toplam'] ?? 0) + $ilavePuan;

    // İlave puan detaylarını çek
    $ilavePuanDetayStmt = $pdo->prepare("
        SELECT puan, aciklama, tarih
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND kategori = 'Namaz'
        ORDER BY tarih DESC
    ");
    $ilavePuanDetayStmt->execute([$ogrenci_id, $yil]);
    $ilavePuanDetaylar = $ilavePuanDetayStmt->fetchAll();

    // Sıralama hesaplama (yillik_ozetler VIEW ile aynı mantık)
    $siralamaStmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as sira
        FROM (
            SELECT
                o.id,
                COUNT(n.id) as toplam_namaz,
                (COUNT(n.id) + COALESCE((SELECT SUM(puan) FROM ilave_puanlar
                    WHERE ogrenci_id = o.id AND YEAR(tarih) = ? AND kategori = 'Namaz'), 0)) as toplam_puan
            FROM ogrenciler o
            LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id AND YEAR(n.tarih) = ?
            GROUP BY o.id
        ) as temp
        WHERE toplam_puan > ? OR (toplam_puan = ? AND toplam_namaz > ?)
    ");
    $siralamaStmt->execute([$yil, $yil, $toplamPuan, $toplamPuan, $ozetRapor['toplam'] ?? 0]);
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
            /* Sadece rapor tablosunu göster */
            body * {
                visibility: hidden;
            }

            .rapor-baslik, .rapor-baslik * {
                visibility: visible;
            }

            .detayli-rapor, .detayli-rapor * {
                visibility: visible;
            }

            /* İlave puan detay tablosunu göster */
            #ilavePuanDetayDiv, #ilavePuanDetayDiv * {
                visibility: visible !important;
            }

            /* Başlık en üstte */
            .rapor-baslik {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            /* Tablo başlığın altında */
            .detayli-rapor {
                position: absolute;
                left: 0;
                top: 50px;
                width: 100%;
            }

            /* İlave puan detay tablosu en altta */
            #ilavePuanDetayDiv {
                position: relative;
                margin-top: 15px !important;
            }

            /* Yazdırma sırasında gizlenecekler */
            .rapor-filtre,
            .rapor-ozet,
            .ogrenci-bilgi-kutu,
            .siralama-bilgi,
            .rapor-butonlar,
            nav,
            .btn-print,
            .btn-geri,
            h2 {
                display: none !important;
            }

            /* Sayfa ayarları */
            @page {
                size: A4;
                margin: 15mm;
            }

            body {
                margin: 0;
                padding: 0;
            }

            /* Başlık stil */
            .rapor-baslik {
                margin: 0 0 15px 0 !important;
                padding: 0 !important;
                page-break-after: avoid;
            }

            .rapor-baslik h3 {
                font-size: 16px !important;
                margin: 0 !important;
                text-align: center;
                font-weight: bold;
            }

            /* İlave Puan Detay Tablosu */
            #ilavePuanDetayDiv {
                margin-top: 15px !important;
                padding: 10px !important;
                background: white !important;
                border: 1px solid #28a745 !important;
                page-break-inside: avoid;
            }

            #ilavePuanDetayDiv h4 {
                font-size: 12px !important;
                margin: 0 0 10px 0 !important;
                color: #28a745 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            #ilavePuanDetayDiv table {
                margin: 0 !important;
            }

            #ilavePuanDetayDiv th {
                background: #28a745 !important;
                color: white !important;
                padding: 6px !important;
                font-size: 9px !important;
            }

            #ilavePuanDetayDiv td {
                padding: 5px !important;
                font-size: 8px !important;
            }

            #ilavePuanDetayDiv tfoot td {
                background: #e8f5e9 !important;
                font-weight: bold !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Tablo stil */
            table {
                width: 100% !important;
                font-size: 10px !important;
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
                background: #667eea !important;
                color: white !important;
                padding: 6px 4px !important;
                font-size: 10px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                text-align: center !important;
            }

            /* İlk iki kolon sola hizalı */
            th:first-child,
            th:nth-child(2) {
                text-align: left !important;
            }

            td {
                padding: 5px 3px !important;
                border: 1px solid #ddd !important;
                font-size: 9px !important;
            }

            td strong {
                font-size: 10px !important;
            }

            td small {
                font-size: 7px !important;
            }

            /* Badge'ler */
            .vakit-badge {
                padding: 2px 4px !important;
                font-size: 7px !important;
                margin: 1px !important;
                border: 1px solid #ccc !important;
            }

            .vakit-badge.aktif {
                background: #28a745 !important;
                color: white !important;
                border-color: #28a745 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Renkler yazdırmada da görünsün */
            td[style*="background: #e8f5e9"] {
                background: #e8f5e9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
                <h4>📊 Özet Bilgiler</h4>
                <div class="ozet-kutular">
                    <div class="ozet-kutu">
                        <span class="etiket">Kendisi:</span>
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
                    <div class="ozet-kutu" style="background: #d4edda; border: 2px solid #28a745; cursor: pointer;" onclick="toggleIlavePuanDetay()">
                        <span class="etiket">İlave Puan:</span>
                        <span class="deger" style="color: #28a745;">+<?php echo $ilavePuan ?? 0; ?></span>
                        <?php if(!empty($ilavePuanDetaylar)): ?>
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

                <?php if(!empty($ilavePuanDetaylar)): ?>
                <div id="ilavePuanDetayDiv" style="display: none; margin-top: 20px; background: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px solid #28a745;">
                    <h4 style="margin-top: 0; color: #28a745;">💰 İlave Puan Detayları</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #28a745; color: white;">
                                <th style="padding: 10px; text-align: left;">Tarih</th>
                                <th style="padding: 10px; text-align: left;">Açıklama</th>
                                <th style="padding: 10px; text-align: center;">Puan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ilavePuanDetaylar as $detay): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px;"><?php echo date('d.m.Y', strtotime($detay['tarih'])); ?></td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars($detay['aciklama']); ?></td>
                                <td style="padding: 10px; text-align: center; font-weight: bold; color: #28a745;">
                                    +<?php echo $detay['puan']; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #e8f5e9; font-weight: bold;">
                                <td colspan="2" style="padding: 10px; text-align: right;">TOPLAM:</td>
                                <td style="padding: 10px; text-align: center; color: #28a745; font-size: 1.2em;">
                                    +<?php echo $ilavePuan; ?>
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
        function toggleIlavePuanDetay() {
            const detayDiv = document.getElementById('ilavePuanDetayDiv');
            if (detayDiv) {
                if (detayDiv.style.display === 'none') {
                    detayDiv.style.display = 'block';
                } else {
                    detayDiv.style.display = 'none';
                }
            }
        }

        // Yazdırma öncesi ilave puan detayını göster
        window.addEventListener('beforeprint', function() {
            const detayDiv = document.getElementById('ilavePuanDetayDiv');
            if (detayDiv) {
                detayDiv.setAttribute('data-was-hidden', detayDiv.style.display === 'none' ? 'true' : 'false');
                detayDiv.style.display = 'block';
            }
        });

        // Yazdırma sonrası önceki duruma döndür
        window.addEventListener('afterprint', function() {
            const detayDiv = document.getElementById('ilavePuanDetayDiv');
            if (detayDiv && detayDiv.getAttribute('data-was-hidden') === 'true') {
                detayDiv.style.display = 'none';
            }
        });
    </script>
<?php require_once 'config/footer.php'; ?>