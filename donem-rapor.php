<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$ogrenci_id = $_GET['id'] ?? 0;
$format = $_GET['format'] ?? 'html'; // html, excel, pdf

// Öğrenci bilgisi
$ogrenci_stmt = $pdo->prepare("SELECT * FROM ogrenciler WHERE id = ?");
$ogrenci_stmt->execute([$ogrenci_id]);
$ogrenci = $ogrenci_stmt->fetch();

if(!$ogrenci) {
    header('Location: ogrenciler.php');
    exit;
}

// Dönem bilgileri (şimdilik bu yıl)
$donem_baslangic = date('Y') . '-01-01';
$donem_bitis = date('Y-m-d');

// Ders istatistikleri
$ders_stats = $pdo->prepare("
    SELECT
        COUNT(od.id) as toplam_ders,
        SUM(CASE WHEN od.durum = 'Tamamlandi' THEN 1 ELSE 0 END) as tamamlanan,
        SUM(CASE WHEN od.durum = 'Beklemede' THEN 1 ELSE 0 END) as bekleyen,
        SUM(CASE WHEN od.durum = 'Tamamlandi' AND od.puan_verildi = 1 THEN d.puan ELSE 0 END) as toplam_puan
    FROM ogrenci_dersler od
    JOIN dersler d ON od.ders_id = d.id
    WHERE od.ogrenci_id = ?
        AND od.atama_tarihi >= ?
        AND od.atama_tarihi <= ?
");
$ders_stats->execute([$ogrenci_id, $donem_baslangic, $donem_bitis]);
$stats = $ders_stats->fetch();

// Tüm dersler detaylı
$dersler_detay = $pdo->prepare("
    SELECT
        dk.kategori_adi,
        d.ders_adi,
        d.puan,
        od.durum,
        od.verme_tarihi,
        od.atama_tarihi
    FROM ogrenci_dersler od
    JOIN dersler d ON od.ders_id = d.id
    JOIN ders_kategorileri dk ON d.kategori_id = dk.id
    WHERE od.ogrenci_id = ?
        AND od.atama_tarihi >= ?
        AND od.atama_tarihi <= ?
    ORDER BY dk.sira, dk.kategori_adi, d.sira, d.ders_adi
");
$dersler_detay->execute([$ogrenci_id, $donem_baslangic, $donem_bitis]);
$dersler = $dersler_detay->fetchAll();

// Kategorilere göre grupla
$kategoriler = [];
foreach($dersler as $ders) {
    $kat = $ders['kategori_adi'];
    if(!isset($kategoriler[$kat])) {
        $kategoriler[$kat] = [
            'tamamlanan' => [],
            'bekleyen' => []
        ];
    }

    if($ders['durum'] == 'Tamamlandi') {
        $kategoriler[$kat]['tamamlanan'][] = $ders;
    } else {
        $kategoriler[$kat]['bekleyen'][] = $ders;
    }
}

// İlave ders puanları
$ilave_puanlar = $pdo->prepare("
    SELECT SUM(puan) as toplam
    FROM ilave_puanlar
    WHERE ogrenci_id = ? AND kategori = 'Ders'
        AND tarih >= ? AND tarih <= ?
");
$ilave_puanlar->execute([$ogrenci_id, $donem_baslangic, $donem_bitis]);
$ilave_puan_toplam = $ilave_puanlar->fetchColumn() ?? 0;

// Toplam ders puanı
$toplam_ders_puani = ($stats['toplam_puan'] ?? 0) + $ilave_puan_toplam;

// Sıralama hesapla
$siralama_query = $pdo->prepare("
    SELECT COUNT(*) + 1 as siralama
    FROM (
        SELECT o.id,
               (SELECT SUM(CASE WHEN od.durum = 'Tamamlandi' AND od.puan_verildi = 1 THEN d.puan ELSE 0 END)
                FROM ogrenci_dersler od
                JOIN dersler d ON od.ders_id = d.id
                WHERE od.ogrenci_id = o.id
                    AND od.atama_tarihi >= ?
                    AND od.atama_tarihi <= ?) +
               COALESCE((SELECT SUM(puan)
                         FROM ilave_puanlar
                         WHERE ogrenci_id = o.id
                             AND kategori = 'Ders'
                             AND tarih >= ?
                             AND tarih <= ?), 0) as toplam
        FROM ogrenciler o
        WHERE o.aktif = 1
    ) as puanlar
    WHERE toplam > ?
");
$siralama_query->execute([$donem_baslangic, $donem_bitis, $donem_baslangic, $donem_bitis, $toplam_ders_puani]);
$siralama = $siralama_query->fetchColumn();

$toplam_ogrenci = $pdo->query("SELECT COUNT(*) FROM ogrenciler WHERE aktif = 1")->fetchColumn();

// Excel export
if($format == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="donem_raporu_' . $ogrenci['ad_soyad'] . '_' . date('Y') . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';
    echo '<table border="1">';
    echo '<tr><th colspan="6" style="background: #667eea; color: white; font-size: 16px; padding: 10px;">DÖNEM SONU RAPORU</th></tr>';
    echo '<tr><th colspan="6" style="background: #f8f9fa; padding: 10px;">ATAKÖY CAMİİ - ' . date('Y') . ' YILI</th></tr>';
    echo '<tr><td colspan="6">&nbsp;</td></tr>';

    echo '<tr><th colspan="2">Öğrenci Adı:</th><td colspan="4">' . htmlspecialchars($ogrenci['ad_soyad']) . '</td></tr>';
    echo '<tr><th colspan="2">Yaş:</th><td colspan="4">' . yasHesapla($ogrenci['dogum_tarihi']) . '</td></tr>';
    echo '<tr><th colspan="2">Baba Adı:</th><td colspan="4">' . htmlspecialchars($ogrenci['baba_adi']) . '</td></tr>';
    echo '<tr><th colspan="2">Anne Adı:</th><td colspan="4">' . htmlspecialchars($ogrenci['anne_adi']) . '</td></tr>';
    echo '<tr><td colspan="6">&nbsp;</td></tr>';

    echo '<tr><th colspan="2">Dönem:</th><td colspan="4">' . date('d.m.Y', strtotime($donem_baslangic)) . ' - ' . date('d.m.Y', strtotime($donem_bitis)) . '</td></tr>';
    echo '<tr><td colspan="6">&nbsp;</td></tr>';

    echo '<tr style="background: #d4edda;"><th colspan="2">Toplam Ders:</th><td colspan="4">' . ($stats['toplam_ders'] ?? 0) . '</td></tr>';
    echo '<tr style="background: #d4edda;"><th colspan="2">Tamamlanan Ders:</th><td colspan="4">' . ($stats['tamamlanan'] ?? 0) . '</td></tr>';
    echo '<tr style="background: #f8d7da;"><th colspan="2">Bekleyen Ders:</th><td colspan="4">' . ($stats['bekleyen'] ?? 0) . '</td></tr>';
    echo '<tr style="background: #cce5ff;"><th colspan="2">Ders Puanı:</th><td colspan="4">' . ($stats['toplam_puan'] ?? 0) . '</td></tr>';
    echo '<tr style="background: #cce5ff;"><th colspan="2">İlave Puan:</th><td colspan="4">+' . $ilave_puan_toplam . '</td></tr>';
    echo '<tr style="background: #fff3cd; font-weight: bold;"><th colspan="2">TOPLAM PUAN:</th><td colspan="4">' . $toplam_ders_puani . '</td></tr>';
    echo '<tr style="background: #e8f5e9; font-weight: bold;"><th colspan="2">SIRALAMA:</th><td colspan="4">' . $siralama . ' / ' . $toplam_ogrenci . '</td></tr>';
    echo '<tr><td colspan="6">&nbsp;</td></tr>';

    // Dersler detay
    foreach($kategoriler as $kat_adi => $kat_data) {
        echo '<tr><th colspan="6" style="background: #667eea; color: white; padding: 8px;">' . htmlspecialchars($kat_adi) . '</th></tr>';

        if(!empty($kat_data['tamamlanan'])) {
            echo '<tr><th colspan="6" style="background: #d4edda;">TamamlananDersler</th></tr>';
            echo '<tr><th>Ders Adı</th><th>Puan</th><th>Verme Tarihi</th><th>Atama Tarihi</th><th colspan="2">Durum</th></tr>';
            foreach($kat_data['tamamlanan'] as $ders) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($ders['ders_adi']) . '</td>';
                echo '<td>' . $ders['puan'] . '</td>';
                echo '<td>' . ($ders['verme_tarihi'] ? date('d.m.Y H:i', strtotime($ders['verme_tarihi'])) : '-') . '</td>';
                echo '<td>' . date('d.m.Y', strtotime($ders['atama_tarihi'])) . '</td>';
                echo '<td colspan="2" style="color: green; font-weight: bold;">VERDİ</td>';
                echo '</tr>';
            }
        }

        if(!empty($kat_data['bekleyen'])) {
            echo '<tr><th colspan="6" style="background: #f8d7da;">Bekleyen Dersler</th></tr>';
            echo '<tr><th>Ders Adı</th><th>Puan</th><th>Verme Tarihi</th><th>Atama Tarihi</th><th colspan="2">Durum</th></tr>';
            foreach($kat_data['bekleyen'] as $ders) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($ders['ders_adi']) . '</td>';
                echo '<td>' . $ders['puan'] . '</td>';
                echo '<td>-</td>';
                echo '<td>' . date('d.m.Y', strtotime($ders['atama_tarihi'])) . '</td>';
                echo '<td colspan="2" style="color: red; font-weight: bold;">VERMEDİ</td>';
                echo '</tr>';
            }
        }
        echo '<tr><td colspan="6">&nbsp;</td></tr>';
    }

    echo '<tr><td colspan="6">&nbsp;</td></tr>';
    echo '<tr><th colspan="6" style="background: #f8f9fa; padding: 10px;">MEHMET TÜZÜN - ATAKÖY CAMİİ İMAM-HATİBİ</th></tr>';
    echo '<tr><th colspan="6" style="background: #f8f9fa; padding: 10px;">Tarih: ' . date('d.m.Y') . '</th></tr>';

    echo '</table>';
    echo '</body></html>';
    exit;
}

// HTML view
$aktif_sayfa = 'donem_rapor';
$sayfa_basligi = 'Dönem Raporu - ' . $ogrenci['ad_soyad'] . ' - Cami Namaz Takip';
require_once 'config/header.php';
?>

<style>
    @media print {
        /* Hide navigation and buttons when printing */
        header, nav, .no-print {
            display: none !important;
        }

        /* Reset page margins */
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: none;
            box-shadow: none;
        }

        /* Prevent page breaks inside elements */
        .stat-card, table, .category-section {
            page-break-inside: avoid;
        }

        /* Improve print colors */
        .gradient-header {
            background: #667eea !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Print table borders */
        table {
            border-collapse: collapse;
        }

        table th, table td {
            border: 1px solid #000 !important;
        }
    }

    .print-button {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 50px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
        transition: all 0.3s;
        z-index: 1000;
    }

    .print-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 30px rgba(102, 126, 234, 0.6);
    }
</style>

<button class="print-button no-print" onclick="window.print()">🖨️ PDF Olarak Kaydet</button>

<div style="padding: 30px;">
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin: 0;">📊 Dönem Sonu Raporu</h2>
        <div>
            <a href="donem-rapor.php?id=<?php echo $ogrenci_id; ?>&format=excel" class="btn-primary" style="text-decoration: none; display: inline-block; padding: 10px 20px; margin-right: 10px;">
                📥 Excel İndir
            </a>
            <a href="ogrenci-dersler.php?id=<?php echo $ogrenci_id; ?>" class="btn-primary" style="text-decoration: none; display: inline-block; padding: 10px 20px; background: #6c757d;">
                ← Geri Dön
            </a>
        </div>
    </div>

    <!-- Öğrenci Bilgileri -->
    <div class="gradient-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1 style="margin: 0; font-size: 32px;">DÖNEM SONU RAPORU</h1>
            <p style="margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;">ATAKÖY CAMİİ - <?php echo date('Y'); ?> YILI</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
            <div>
                <p style="margin: 10px 0;"><strong>Öğrenci Adı:</strong> <?php echo htmlspecialchars($ogrenci['ad_soyad']); ?></p>
                <p style="margin: 10px 0;"><strong>Yaş:</strong> <?php echo yasHesapla($ogrenci['dogum_tarihi']); ?></p>
            </div>
            <div>
                <p style="margin: 10px 0;"><strong>Baba Adı:</strong> <?php echo htmlspecialchars($ogrenci['baba_adi']); ?></p>
                <p style="margin: 10px 0;"><strong>Anne Adı:</strong> <?php echo htmlspecialchars($ogrenci['anne_adi']); ?></p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.3);">
            <p style="margin: 0; opacity: 0.9;">
                <strong>Dönem:</strong> <?php echo date('d.m.Y', strtotime($donem_baslangic)); ?> - <?php echo date('d.m.Y', strtotime($donem_bitis)); ?>
            </p>
        </div>
    </div>

    <!-- İstatistikler -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: #f8f9fa; padding: 25px; border-radius: 12px; border-left: 5px solid #667eea;">
            <h3 style="margin: 0; color: #667eea;">📚 Toplam Ders</h3>
            <p style="margin: 15px 0 0 0; font-size: 36px; font-weight: bold;"><?php echo $stats['toplam_ders'] ?? 0; ?></p>
        </div>
        <div style="background: #d4edda; padding: 25px; border-radius: 12px; border-left: 5px solid #28a745;">
            <h3 style="margin: 0; color: #28a745;">✅ Tamamlanan</h3>
            <p style="margin: 15px 0 0 0; font-size: 36px; font-weight: bold;"><?php echo $stats['tamamlanan'] ?? 0; ?></p>
        </div>
        <div style="background: #f8d7da; padding: 25px; border-radius: 12px; border-left: 5px solid #dc3545;">
            <h3 style="margin: 0; color: #dc3545;">❌ Bekleyen</h3>
            <p style="margin: 15px 0 0 0; font-size: 36px; font-weight: bold;"><?php echo $stats['bekleyen'] ?? 0; ?></p>
        </div>
        <div style="background: #cce5ff; padding: 25px; border-radius: 12px; border-left: 5px solid #007bff;">
            <h3 style="margin: 0; color: #007bff;">⭐ Toplam Puan</h3>
            <p style="margin: 15px 0 0 0; font-size: 36px; font-weight: bold;"><?php echo $toplam_ders_puani; ?></p>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;"><?php echo $stats['toplam_puan'] ?? 0; ?> ders + <?php echo $ilave_puan_toplam; ?> ilave</p>
        </div>
        <div style="background: #fff3cd; padding: 25px; border-radius: 12px; border-left: 5px solid #ffc107;">
            <h3 style="margin: 0; color: #856404;">🏆 Sıralama</h3>
            <p style="margin: 15px 0 0 0; font-size: 36px; font-weight: bold;">#<?php echo $siralama; ?></p>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;"><?php echo $toplam_ogrenci; ?> öğrenci arasında</p>
        </div>
    </div>

    <!-- Dersler Detay -->
    <?php foreach($kategoriler as $kat_adi => $kat_data): ?>
    <div style="background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px;">
        <h3 style="margin: 0 0 20px 0; color: #667eea;">📚 <?php echo htmlspecialchars($kat_adi); ?></h3>

        <!-- Tamamlanan Dersler -->
        <?php if(!empty($kat_data['tamamlanan'])): ?>
        <h4 style="color: #28a745; margin: 20px 0 15px 0;">✅ Tamamlanan Dersler</h4>
        <table style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr style="background: #d4edda;">
                    <th style="padding: 10px; text-align: left;">Ders Adı</th>
                    <th style="padding: 10px; text-align: center;">Puan</th>
                    <th style="padding: 10px; text-align: center;">Verme Tarihi</th>
                    <th style="padding: 10px; text-align: center;">Atama Tarihi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($kat_data['tamamlanan'] as $ders): ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($ders['ders_adi']); ?></td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;"><strong><?php echo $ders['puan']; ?></strong></td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">
                        <?php echo $ders['verme_tarihi'] ? date('d.m.Y H:i', strtotime($ders['verme_tarihi'])) : '-'; ?>
                    </td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">
                        <?php echo date('d.m.Y', strtotime($ders['atama_tarihi'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Bekleyen Dersler -->
        <?php if(!empty($kat_data['bekleyen'])): ?>
        <h4 style="color: #dc3545; margin: 20px 0 15px 0;">❌ Bekleyen Dersler (Verilmedi)</h4>
        <table style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr style="background: #f8d7da;">
                    <th style="padding: 10px; text-align: left;">Ders Adı</th>
                    <th style="padding: 10px; text-align: center;">Puan</th>
                    <th style="padding: 10px; text-align: center;">Atama Tarihi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($kat_data['bekleyen'] as $ders): ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($ders['ders_adi']); ?></td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;"><strong><?php echo $ders['puan']; ?></strong></td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">
                        <?php echo date('d.m.Y', strtotime($ders['atama_tarihi'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- İmza -->
    <div style="text-align: center; margin-top: 50px; padding-top: 30px; border-top: 2px solid #ddd;">
        <div style="margin-bottom: 60px;"></div>
        <div style="width: 300px; margin: 0 auto; border-top: 2px solid #000; padding-top: 15px;">
            <p style="margin: 5px 0; font-weight: bold; font-size: 16px;">MEHMET TÜZÜN</p>
            <p style="margin: 5px 0; color: #666;">ATAKÖY CAMİİ İMAM-HATİBİ</p>
        </div>
        <p style="margin-top: 30px; color: #999;">Tarih: <?php echo date('d.m.Y'); ?></p>
    </div>
</div>

<?php require_once 'config/footer.php'; ?>
