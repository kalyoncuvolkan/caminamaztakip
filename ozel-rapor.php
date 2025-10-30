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
    
    $gunlukStmt = $pdo->prepare("
        SELECT 
            DAY(tarih) as gun,
            GROUP_CONCAT(namaz_vakti ORDER BY 
                FIELD(namaz_vakti, 'Sabah', 'Öğlen', 'İkindi', 'Akşam', 'Yatsı')
            ) as vakitler,
            COUNT(*) as toplam,
            GROUP_CONCAT(DISTINCT kiminle_geldi) as kiminle
        FROM namaz_kayitlari 
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ?
        GROUP BY tarih
        ORDER BY tarih
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
    
    $siralamaStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) + 1 as sira, 
               (SELECT COUNT(DISTINCT ogrenci_id) FROM namaz_kayitlari 
                WHERE YEAR(tarih) = ? AND MONTH(tarih) = ?) as toplam_ogrenci
        FROM ogrenciler o
        LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id 
            AND YEAR(n.tarih) = ? AND MONTH(n.tarih) = ?
        GROUP BY o.id
        HAVING COUNT(n.id) > ?
    ");
    $siralamaStmt->execute([$yil, $ay, $yil, $ay, $ozetRapor['toplam'] ?? 0]);
    $siralamaData = $siralamaStmt->fetch();
    $siralama = $siralamaData['sira'] ?? 1;
    $toplamOgrenci = $siralamaData['toplam_ogrenci'] ?? 0;
    
} else {
    $raporBaslik = $ogrenci['ad_soyad'] . ' ' . $yil . ' yılı namaz kılma raporu';
    
    $aylikStmt = $pdo->prepare("
        SELECT 
            MONTH(tarih) as ay,
            COUNT(*) as toplam,
            GROUP_CONCAT(DISTINCT namaz_vakti) as vakitler
        FROM namaz_kayitlari 
        WHERE ogrenci_id = ? AND YEAR(tarih) = ?
        GROUP BY MONTH(tarih)
        ORDER BY MONTH(tarih)
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
    
    $siralamaStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) + 1 as sira,
               (SELECT COUNT(DISTINCT ogrenci_id) FROM namaz_kayitlari WHERE YEAR(tarih) = ?) as toplam_ogrenci
        FROM ogrenciler o
        LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id AND YEAR(n.tarih) = ?
        GROUP BY o.id
        HAVING COUNT(n.id) > ?
    ");
    $siralamaStmt->execute([$yil, $yil, $ozetRapor['toplam'] ?? 0]);
    $siralamaData = $siralamaStmt->fetch();
    $siralama = $siralamaData['sira'] ?? 1;
    $toplamOgrenci = $siralamaData['toplam_ogrenci'] ?? 0;
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
                            <th><?php echo $ay ? 'Gün' : 'Ay'; ?></th>
                            <th>Namaz Vakitleri</th>
                            <th>Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($detayliRapor as $satir): ?>
                        <tr>
                            <td>
                                <?php 
                                if($ay) {
                                    echo str_pad($satir['gun'], 2, '0', STR_PAD_LEFT) . '.' . 
                                         str_pad($ay, 2, '0', STR_PAD_LEFT) . '.' . $yil;
                                } else {
                                    echo ayAdi($satir['ay']) . ' ' . $yil;
                                }
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
                            <td><strong><?php echo $satir['toplam']; ?></strong></td>
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
                    <div class="ozet-kutu toplam">
                        <span class="etiket">TOPLAM:</span>
                        <span class="deger"><?php echo $ozetRapor['toplam'] ?? 0; ?></span>
                    </div>
                </div>
                
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
<?php require_once 'config/footer.php'; ?>