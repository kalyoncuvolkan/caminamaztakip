<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$mesaj = '';
$hata = '';

// Migration uygulama
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['migrate'])) {
    try {
        // Migration dosyasını oku
        $migration_sql = file_get_contents('migrations/v2.1_ders_puan_revize.sql');

        // SQL komutlarını ayır ve çalıştır
        $statements = array_filter(
            array_map('trim',
                explode(';', $migration_sql)
            ),
            function($stmt) {
                // Boş satırları ve yorumları atla
                return !empty($stmt) &&
                       strpos($stmt, '--') !== 0 &&
                       $stmt !== '';
            }
        );

        $pdo->beginTransaction();

        foreach($statements as $statement) {
            // Yorum satırlarını temizle
            $lines = explode("\n", $statement);
            $clean_lines = array_filter($lines, function($line) {
                return strpos(trim($line), '--') !== 0;
            });
            $clean_statement = implode("\n", $clean_lines);

            if(trim($clean_statement)) {
                $pdo->exec($clean_statement);
            }
        }

        $pdo->commit();
        $mesaj = "✅ Migration başarıyla uygulandı! Veritabanı güncellendi.";

    } catch(Exception $e) {
        $pdo->rollBack();
        $hata = "❌ Migration hatası: " . $e->getMessage();
    }
}

// Mevcut tablo yapısını kontrol et
$check_columns = [];
try {
    // ogrenci_dersler kontrolü
    $cols = $pdo->query("SHOW COLUMNS FROM ogrenci_dersler LIKE 'verme_tarihi'")->fetchAll();
    $check_columns['ogrenci_dersler_verme_tarihi'] = count($cols) > 0;

    $cols = $pdo->query("SHOW COLUMNS FROM ogrenci_dersler LIKE 'aktif_edilme_sayisi'")->fetchAll();
    $check_columns['ogrenci_dersler_aktif_edilme'] = count($cols) > 0;

    // ilave_puanlar kontrolü
    $cols = $pdo->query("SHOW COLUMNS FROM ilave_puanlar LIKE 'kategori'")->fetchAll();
    $check_columns['ilave_puanlar_kategori'] = count($cols) > 0;

    // ilave_puan_silme_gecmisi kontrolü
    $cols = $pdo->query("SHOW COLUMNS FROM ilave_puan_silme_gecmisi LIKE 'kategori'")->fetchAll();
    $check_columns['ilave_puan_silme_kategori'] = count($cols) > 0;

} catch(Exception $e) {
    $hata = "Kontrol hatası: " . $e->getMessage();
}

$migration_gerekli = in_array(false, $check_columns, true);

$aktif_sayfa = 'index';
$sayfa_basligi = 'Veritabanı Migration - Cami Namaz Takip';
require_once 'config/header.php';
?>

<div style="padding: 30px; max-width: 800px; margin: 0 auto;">
    <h2>🔄 Veritabanı Migration v2.1</h2>
    <p style="color: #666; margin-bottom: 30px;">
        Ders ve puan sistemi revizyonu için veritabanı güncellemesi
    </p>

    <?php if($mesaj): ?>
    <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;">
        <strong><?php echo $mesaj; ?></strong>
    </div>
    <?php endif; ?>

    <?php if($hata): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #f5c6cb;">
        <strong><?php echo $hata; ?></strong>
    </div>
    <?php endif; ?>

    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin: 20px 0;">
        <h3>📋 Migration Durumu</h3>

        <table style="width: 100%; margin-top: 15px;">
            <tr>
                <td style="padding: 10px;">ogrenci_dersler.verme_tarihi</td>
                <td style="padding: 10px; text-align: right;">
                    <?php echo $check_columns['ogrenci_dersler_verme_tarihi'] ? '✅ Mevcut' : '❌ Eksik'; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px;">ogrenci_dersler.aktif_edilme_sayisi</td>
                <td style="padding: 10px; text-align: right;">
                    <?php echo $check_columns['ogrenci_dersler_aktif_edilme'] ? '✅ Mevcut' : '❌ Eksik'; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px;">ilave_puanlar.kategori</td>
                <td style="padding: 10px; text-align: right;">
                    <?php echo $check_columns['ilave_puanlar_kategori'] ? '✅ Mevcut' : '❌ Eksik'; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px;">ilave_puan_silme_gecmisi.kategori</td>
                <td style="padding: 10px; text-align: right;">
                    <?php echo $check_columns['ilave_puan_silme_kategori'] ? '✅ Mevcut' : '❌ Eksik'; ?>
                </td>
            </tr>
        </table>
    </div>

    <?php if($migration_gerekli): ?>
    <div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffeaa7;">
        <h4 style="margin: 0 0 10px 0;">⚠️ Migration Gerekli</h4>
        <p style="margin: 0;">Bazı tablo alanları eksik. Migration'ı uygulamak için aşağıdaki butona tıklayın.</p>
    </div>

    <form method="POST" onsubmit="return confirm('Migration uygulanacak. Devam etmek istediğinize emin misiniz?');">
        <button type="submit" name="migrate" class="btn-primary" style="width: 100%; padding: 15px; font-size: 18px;">
            🚀 Migration Uygula
        </button>
    </form>
    <?php else: ?>
    <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;">
        <h4 style="margin: 0 0 10px 0;">✅ Veritabanı Güncel</h4>
        <p style="margin: 0;">Tüm migration'lar uygulandı. Veritabanınız güncel.</p>
    </div>
    <?php endif; ?>

    <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #b3d9ff;">
        <h4 style="margin: 0 0 10px 0;">📝 Migration İçeriği</h4>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>ogrenci_dersler:</strong> verme_tarihi, aktif_edilme_sayisi, onceki_puan, son_aktif_edilme alanları</li>
            <li><strong>ilave_puanlar:</strong> kategori (Namaz/Ders) alanı</li>
            <li><strong>ilave_puan_silme_gecmisi:</strong> kategori alanı</li>
            <li>Mevcut ilave puanlar "Namaz" kategorisine atanıyor</li>
        </ul>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="index.php" class="btn-primary" style="display: inline-block; padding: 12px 30px; text-decoration: none;">
            ← Ana Sayfaya Dön
        </a>
    </div>
</div>

<?php require_once 'config/footer.php'; ?>
