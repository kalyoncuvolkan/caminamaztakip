<?php
// Hata ayıklama için
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$aktif_sayfa = 'hediye-raporu';
$sayfa_basligi = 'Hediye Raporu - Cami Namaz Takip';

// Varsayılan değerler
$yil = isset($_GET['yil']) ? (int)$_GET['yil'] : date('Y');
$ay = isset($_GET['ay']) ? (int)$_GET['ay'] : date('m');
$hesaplama_turu = $_GET['hesaplama'] ?? '';
$odul_birimi = isset($_GET['odal_birimi']) ? (float)$_GET['odal_birimi'] : 0;

$sonuclar = [];
$toplam_odul = 0;
$hata_mesaji = '';

if (!empty($hesaplama_turu) && $odul_birimi > 0) {
    try {

    if ($hesaplama_turu === 'vakite_gore') {
        // Toplam namaz vakitine göre hesaplama
        $stmt = $pdo->prepare("
            SELECT
                o.id,
                o.ad_soyad,
                o.sinif,
                COUNT(n.id) as toplam_namaz,
                (COUNT(n.id) * ?) as odul_miktari
            FROM ogrenciler o
            LEFT JOIN namazlar n ON o.id = n.ogrenci_id
                AND YEAR(n.tarih) = ?
                AND MONTH(n.tarih) = ?
            WHERE o.durum = 'Aktif'
            GROUP BY o.id, o.ad_soyad, o.sinif
            HAVING toplam_namaz > 0
            ORDER BY toplam_namaz DESC
        ");
        $stmt->execute([$odul_birimi, $yil, $ay]);
        $sonuclar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($hesaplama_turu === 'ders_puani') {
        // Ders puanına göre hesaplama
        $stmt = $pdo->prepare("
            SELECT
                o.id,
                o.ad_soyad,
                o.sinif,
                COALESCE(SUM(ip.puan), 0) as ders_puani,
                (COALESCE(SUM(ip.puan), 0) * ?) as odul_miktari
            FROM ogrenciler o
            LEFT JOIN ilave_puanlar ip ON o.id = ip.ogrenci_id
                AND ip.kategori = 'Ders'
                AND YEAR(ip.tarih) = ?
                AND MONTH(ip.tarih) = ?
            WHERE o.durum = 'Aktif'
            GROUP BY o.id, o.ad_soyad, o.sinif
            HAVING ders_puani > 0
            ORDER BY ders_puani DESC
        ");
        $stmt->execute([$odul_birimi, $yil, $ay]);
        $sonuclar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($hesaplama_turu === 'toplam_puan') {
        // Toplam puana göre hesaplama (namazlar + ilave puanlar + ders puanları)
        $stmt = $pdo->prepare("
            SELECT
                o.id,
                o.ad_soyad,
                o.sinif,
                (
                    COALESCE(COUNT(DISTINCT n.id), 0) +
                    COALESCE(SUM(CASE WHEN ip.kategori = 'Namaz' AND ip.puan > 0 THEN ip.puan ELSE 0 END), 0) +
                    COALESCE(SUM(CASE WHEN ip.kategori = 'Namaz' AND ip.puan < 0 THEN ip.puan ELSE 0 END), 0) +
                    COALESCE(SUM(CASE WHEN ip.kategori = 'Ders' THEN ip.puan ELSE 0 END), 0)
                ) as toplam_puan,
                (
                    (COALESCE(COUNT(DISTINCT n.id), 0) +
                    COALESCE(SUM(CASE WHEN ip.kategori = 'Namaz' AND ip.puan > 0 THEN ip.puan ELSE 0 END), 0) +
                    COALESCE(SUM(CASE WHEN ip.kategori = 'Namaz' AND ip.puan < 0 THEN ip.puan ELSE 0 END), 0) +
                    COALESCE(SUM(CASE WHEN ip.kategori = 'Ders' THEN ip.puan ELSE 0 END), 0))
                    * ?
                ) as odul_miktari
            FROM ogrenciler o
            LEFT JOIN namazlar n ON o.id = n.ogrenci_id
                AND YEAR(n.tarih) = ?
                AND MONTH(n.tarih) = ?
            LEFT JOIN ilave_puanlar ip ON o.id = ip.ogrenci_id
                AND YEAR(ip.tarih) = ?
                AND MONTH(ip.tarih) = ?
            WHERE o.durum = 'Aktif'
            GROUP BY o.id, o.ad_soyad, o.sinif
            HAVING toplam_puan > 0
            ORDER BY toplam_puan DESC
        ");
        $stmt->execute([$odul_birimi, $yil, $ay, $yil, $ay]);
        $sonuclar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Toplam ödül miktarını hesapla
    foreach ($sonuclar as $sonuc) {
        $toplam_odul += $sonuc['odul_miktari'];
    }

    } catch (PDOException $e) {
        $hata_mesaji = "Veritabanı Hatası: " . $e->getMessage();
    } catch (Exception $e) {
        $hata_mesaji = "Genel Hata: " . $e->getMessage();
    }
}

require_once 'config/header.php';

// Türkçe ay isimleri
$aylar = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];
?>

<style>
    .hediye-container {
        padding: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .secim-panel {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .secim-panel h2 {
        color: white;
        margin-top: 0;
        font-size: 28px;
        text-align: center;
        margin-bottom: 25px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        color: white;
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-group select,
    .form-group input {
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        background: white;
    }

    .hesaplama-butonlari {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }

    .hesaplama-btn {
        padding: 15px 20px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
        text-decoration: none;
        display: inline-block;
    }

    .hesaplama-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }

    .btn-vakite {
        background: #28a745;
        color: white;
    }

    .btn-ders {
        background: #17a2b8;
        color: white;
    }

    .btn-toplam {
        background: #ffc107;
        color: #333;
    }

    .sonuc-panel {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .sonuc-baslik {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 3px solid #667eea;
    }

    .sonuc-baslik h3 {
        margin: 0;
        color: #667eea;
        font-size: 24px;
    }

    .toplam-odul {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        font-size: 20px;
        font-weight: bold;
    }

    .sonuc-tablo {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .sonuc-tablo thead {
        background: #667eea;
        color: white;
    }

    .sonuc-tablo th,
    .sonuc-tablo td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .sonuc-tablo tbody tr:hover {
        background: #f8f9fa;
    }

    .siralama {
        background: #667eea;
        color: white;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
    }

    .odul-badge {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 16px;
    }

    .yazdir-btn {
        background: #6c757d;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 20px;
    }

    .yazdir-btn:hover {
        background: #5a6268;
    }

    .bilgi-kutu {
        background: #e7f3ff;
        border-left: 4px solid #2196F3;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    @media print {
        .secim-panel,
        .yazdir-btn {
            display: none !important;
        }

        .hediye-container {
            padding: 20px;
        }

        .sonuc-tablo {
            page-break-inside: avoid;
        }
    }
</style>

<div class="hediye-container">
    <div class="secim-panel">
        <h2>🎁 Hediye/Ödül Hesaplama Sistemi</h2>

        <form method="GET" action="" id="hedifeForm">
            <div class="form-grid">
                <div class="form-group">
                    <label>📅 Yıl Seçin:</label>
                    <select name="yil" required>
                        <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $yil ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>📅 Ay Seçin:</label>
                    <select name="ay" required>
                        <?php foreach($aylar as $ay_no => $ay_adi): ?>
                        <option value="<?php echo $ay_no; ?>" <?php echo $ay_no == $ay ? 'selected' : ''; ?>><?php echo $ay_adi; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>💰 Ödül Birimi (1 Puan = ? TL):</label>
                    <input type="number" name="odal_birimi" step="0.01" min="0.01" value="<?php echo $odul_birimi > 0 ? $odul_birimi : ''; ?>" placeholder="Örn: 0.50" required>
                </div>
            </div>

            <div class="bilgi-kutu">
                <strong>💡 Hesaplama Türleri:</strong>
                <ul style="margin: 10px 0 0 20px; padding: 0;">
                    <li><strong>Toplam Vakite Göre:</strong> Öğrencinin o ay camiye geldiği toplam namaz sayısı × ödül birimi</li>
                    <li><strong>Ders Puanına Göre:</strong> Öğrencinin o ay aldığı ders puanları toplamı × ödül birimi</li>
                    <li><strong>Toplam Puana Göre:</strong> Öğrencinin o ay toplam puanı (namaz + ders + ilave - ceza) × ödül birimi</li>
                </ul>
            </div>

            <div class="hesaplama-butonlari">
                <button type="submit" name="hesaplama" value="vakite_gore" class="hesaplama-btn btn-vakite">
                    🕌 Toplam Vakite Göre Hesapla
                </button>

                <button type="submit" name="hesaplama" value="ders_puani" class="hesaplama-btn btn-ders">
                    📚 Ders Puanına Göre Hesapla
                </button>

                <button type="submit" name="hesaplama" value="toplam_puan" class="hesaplama-btn btn-toplam">
                    ⭐ Toplam Puana Göre Hesapla
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($hata_mesaji)): ?>
    <div class="sonuc-panel" style="background: #f8d7da; border: 2px solid #dc3545;">
        <h3 style="color: #dc3545; margin-top: 0;">⚠️ Hata Oluştu</h3>
        <p style="color: #721c24; font-weight: bold;"><?php echo htmlspecialchars($hata_mesaji); ?></p>
        <pre style="background: white; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px;"><?php
            echo "Yıl: $yil\n";
            echo "Ay: $ay\n";
            echo "Hesaplama Türü: $hesaplama_turu\n";
            echo "Ödül Birimi: $odul_birimi\n";
        ?></pre>
    </div>
    <?php endif; ?>

    <?php if (!empty($sonuclar)): ?>
    <div class="sonuc-panel" id="sonucPanel">
        <div class="sonuc-baslik">
            <div>
                <h3>
                    <?php
                    if ($hesaplama_turu === 'vakite_gore') echo '🕌 Toplam Vakite Göre';
                    elseif ($hesaplama_turu === 'ders_puani') echo '📚 Ders Puanına Göre';
                    elseif ($hesaplama_turu === 'toplam_puan') echo '⭐ Toplam Puana Göre';
                    ?>
                    Ödül Raporu
                </h3>
                <p style="margin: 5px 0 0 0; color: #666;">
                    <?php echo $aylar[$ay] . ' ' . $yil; ?> - Ödül Birimi: <?php echo number_format($odul_birimi, 2); ?> ₺
                </p>
            </div>
            <div class="toplam-odul">
                Toplam: <?php echo number_format($toplam_odul, 2); ?> ₺
            </div>
        </div>

        <table class="sonuc-tablo">
            <thead>
                <tr>
                    <th style="width: 60px;">Sıra</th>
                    <th>Öğrenci Adı</th>
                    <th>Sınıf</th>
                    <th>
                        <?php
                        if ($hesaplama_turu === 'vakite_gore') echo 'Toplam Namaz';
                        elseif ($hesaplama_turu === 'ders_puani') echo 'Ders Puanı';
                        elseif ($hesaplama_turu === 'toplam_puan') echo 'Toplam Puan';
                        ?>
                    </th>
                    <th>Ödül Miktarı</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sira = 1;
                foreach ($sonuclar as $sonuc):
                ?>
                <tr>
                    <td><span class="siralama"><?php echo $sira++; ?></span></td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($sonuc['ad_soyad']); ?></td>
                    <td><?php echo htmlspecialchars($sonuc['sinif']); ?></td>
                    <td style="font-weight: bold; color: #667eea;">
                        <?php
                        if ($hesaplama_turu === 'vakite_gore') echo $sonuc['toplam_namaz'];
                        elseif ($hesaplama_turu === 'ders_puani') echo $sonuc['ders_puani'];
                        elseif ($hesaplama_turu === 'toplam_puan') echo $sonuc['toplam_puan'];
                        ?>
                    </td>
                    <td>
                        <span class="odul-badge">
                            <?php echo number_format($sonuc['odul_miktari'], 2); ?> ₺
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button onclick="window.print()" class="yazdir-btn">🖨️ Yazdır</button>
    </div>
    <?php elseif (isset($_GET['hesaplama'])): ?>
    <div class="sonuc-panel">
        <div style="text-align: center; padding: 40px; color: #666;">
            <h3>ℹ️ Sonuç Bulunamadı</h3>
            <p>Seçilen dönem ve hesaplama türü için herhangi bir kayıt bulunamadı.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'config/footer.php'; ?>
