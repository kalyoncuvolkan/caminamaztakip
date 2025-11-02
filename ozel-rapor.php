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
    
    // Tüm namaz kayıtlarını çek (gruplamadan)
    $gunlukStmt = $pdo->prepare("
        SELECT
            tarih,
            namaz_vakti,
            kiminle_geldi,
            saat
        FROM namaz_kayitlari
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ?
        ORDER BY tarih DESC, FIELD(namaz_vakti, 'Sabah', 'Öğlen', 'İkindi', 'Akşam', 'Yatsı')
    ");
    $gunlukStmt->execute([$ogrenci_id, $yil, $ay]);
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

    // Tüm namaz kayıtlarını çek (yıllık - gruplamadan)
    $aylikStmt = $pdo->prepare("
        SELECT
            tarih,
            namaz_vakti,
            kiminle_geldi,
            saat
        FROM namaz_kayitlari
        WHERE ogrenci_id = ? AND YEAR(tarih) = ?
        ORDER BY tarih DESC, FIELD(namaz_vakti, 'Sabah', 'Öğlen', 'İkindi', 'Akşam', 'Yatsı')
    ");
    $aylikStmt->execute([$ogrenci_id, $yil]);
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
                            <th>Namaz Vakti</th>
                            <th>Kiminle Geldi</th>
                            <th>Saat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($detayliRapor as $satir): ?>
                        <tr>
                            <td>
                                <?php
                                // Her iki durumda da (aylık ve yıllık) aynı format
                                $gun_adi = gunAdi($satir['tarih']);
                                $tarih_formatted = date('d.m.Y', strtotime($satir['tarih']));
                                echo '<strong>' . $gun_adi . '</strong><br>';
                                echo '<small style="color: #666;">' . $tarih_formatted . '</small>';
                                ?>
                            </td>
                            <td>
                                <span class="vakit-badge aktif"><?php echo $satir['namaz_vakti']; ?></span>
                            </td>
                            <td>
                                <?php
                                $kiminle = $satir['kiminle_geldi'];
                                $icon = '';
                                $color = '';
                                switch($kiminle) {
                                    case 'Kendisi':
                                        $icon = '🧒';
                                        $color = '#666';
                                        break;
                                    case 'Babası':
                                        $icon = '👨';
                                        $color = '#28a745';
                                        break;
                                    case 'Annesi':
                                        $icon = '👩';
                                        $color = '#28a745';
                                        break;
                                    case 'Anne-Babası':
                                        $icon = '👨‍👩';
                                        $color = '#28a745';
                                        break;
                                }
                                echo '<span style="color: ' . $color . '; font-weight: 600;">' . $icon . ' ' . $kiminle . '</span>';
                                ?>
                            </td>
                            <td>
                                <small style="color: #666;"><?php echo date('H:i', strtotime($satir['saat'])); ?></small>
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
    </script>
<?php require_once 'config/footer.php'; ?>