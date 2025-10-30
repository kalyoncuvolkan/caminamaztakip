<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$yil = $_GET['yil'] ?? date('Y');
$ay = $_GET['ay'] ?? date('n');

$aylikRapor = $pdo->prepare("
    SELECT 
        o.id,
        o.ad_soyad,
        COALESCE(SUM(CASE WHEN n.kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END), 0) as kendisi_sayisi,
        COALESCE(SUM(CASE WHEN n.kiminle_geldi = 'Babası' THEN 1 ELSE 0 END), 0) as babasi_sayisi,
        COALESCE(SUM(CASE WHEN n.kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END), 0) as annesi_sayisi,
        COALESCE(SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END), 0) as anne_babasi_sayisi,
        COALESCE(COUNT(n.id), 0) as toplam_namaz
    FROM 
        ogrenciler o
        LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id 
            AND YEAR(n.tarih) = ? AND MONTH(n.tarih) = ?
    GROUP BY 
        o.id, o.ad_soyad
    HAVING toplam_namaz > 0
    ORDER BY 
        toplam_namaz DESC, o.ad_soyad
");
$aylikRapor->execute([$yil, $ay]);
$raporlar = $aylikRapor->fetchAll();

$toplamVakit = 0;
foreach($raporlar as $rapor) {
    $toplamVakit += $rapor['toplam_namaz'];
}

$yillar = $pdo->query("SELECT DISTINCT YEAR(tarih) as yil FROM namaz_kayitlari ORDER BY yil DESC")->fetchAll();

$aktif_sayfa = 'raporlar';
$sayfa_basligi = 'Genel Rapor - Cami Namaz Takip';
require_once 'config/header.php';
?>

        <div class="rapor-container">
            <h2>📊 Genel Aylık Rapor</h2>
            
            <form method="GET" action="" class="rapor-filtre">
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
                        <?php for($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $ay ? 'selected' : ''; ?>><?php echo ayAdi($m); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </form>

            <div class="rapor-baslik">
                <h3><?php echo strtoupper(ayAdi($ay)) . ' ' . $yil; ?> AYI ÖĞRENCİ NAMAZA KATILIM RAPORU</h3>
            </div>

            <?php if(count($raporlar) > 0): ?>
            <div class="rapor-tablo">
                <table>
                    <thead>
                        <tr>
                            <th>Sıra</th>
                            <th>Öğrenci Adı</th>
                            <th>Kendisi</th>
                            <th>Babası</th>
                            <th>Annesi</th>
                            <th>Anne-Babası</th>
                            <th>Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($raporlar as $index => $rapor): ?>
                        <tr class="<?php echo $index < 3 ? 'siralama-' . ($index + 1) : ''; ?>">
                            <td><?php echo $index + 1; ?>.</td>
                            <td><?php echo $rapor['ad_soyad']; ?></td>
                            <td><?php echo $rapor['kendisi_sayisi']; ?></td>
                            <td><?php echo $rapor['babasi_sayisi']; ?></td>
                            <td><?php echo $rapor['annesi_sayisi']; ?></td>
                            <td><?php echo $rapor['anne_babasi_sayisi']; ?></td>
                            <td class="toplam"><?php echo $rapor['toplam_namaz']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="rapor-ozet">
                <p><strong>Programa katılan öğrenci sayısı:</strong> <?php echo count($raporlar); ?></p>
                <p><strong>Toplam vakit sayısı:</strong> <?php echo $toplamVakit; ?></p>
                
                <div class="aylik-siralama">
                    <h4>🏆 <?php echo ayAdi($ay); ?> Ayı Sıralaması</h4>
                    <?php for($i = 0; $i < min(3, count($raporlar)); $i++): ?>
                    <p class="siralama-satir">
                        <span class="siralama-badge badge-<?php echo $i + 1; ?>">
                            <?php echo ayAdi($ay); ?> Ayının <?php echo siralama($i + 1); ?>:
                        </span>
                        <strong><?php echo $raporlar[$i]['ad_soyad']; ?></strong> 
                        (<?php echo $raporlar[$i]['toplam_namaz']; ?> Vakit)
                    </p>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="rapor-butonlar">
                <button onclick="window.print()" class="btn-print">🖨️ Yazdır</button>
                <button onclick="exportExcel()" class="btn-export">📊 Excel'e Aktar</button>
            </div>
            <?php else: ?>
            <div class="alert info">Bu ay için henüz kayıt bulunmamaktadır.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function exportExcel() {
            var table = document.querySelector('.rapor-tablo table');
            var html = table.outerHTML;
            var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            var downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            downloadLink.href = url;
            downloadLink.download = 'namaz_raporu_<?php echo $ay; ?>_<?php echo $yil; ?>.xls';
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
<?php require_once 'config/footer.php'; ?>