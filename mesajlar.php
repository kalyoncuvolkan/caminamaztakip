<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$mesaj_bildirim = '';

// Mesaj gönderme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mesaj_gonder'])) {
    $ogrenci_id = $_POST['ogrenci_id'];
    $mesaj_metni = $_POST['mesaj'];
    $oncelik = $_POST['oncelik'];

    $stmt = $pdo->prepare("INSERT INTO ogrenci_mesajlari (ogrenci_id, mesaj, oncelik, gonderen_kullanici) VALUES (?, ?, ?, ?)");
    $stmt->execute([$ogrenci_id, $mesaj_metni, $oncelik, getLoggedInUser()]);

    $mesaj_bildirim = "Mesaj başarıyla gönderildi!";
}

// Toplu mesaj gönderme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toplu_mesaj_gonder'])) {
    $mesaj_metni = $_POST['mesaj'];
    $oncelik = $_POST['oncelik'];
    $hedef = $_POST['hedef'];

    if($hedef == 'tum_ogrenciler') {
        $ogrenciler = $pdo->query("SELECT id FROM ogrenciler WHERE aktif = 1")->fetchAll();
    } else {
        $ogrenciler = $pdo->query("SELECT id FROM ogrenciler WHERE aktif = 0")->fetchAll();
    }

    $stmt = $pdo->prepare("INSERT INTO ogrenci_mesajlari (ogrenci_id, mesaj, oncelik, gonderen_kullanici) VALUES (?, ?, ?, ?)");
    foreach($ogrenciler as $ogr) {
        $stmt->execute([$ogr['id'], $mesaj_metni, $oncelik, getLoggedInUser()]);
    }

    $mesaj_bildirim = count($ogrenciler) . " öğrenciye mesaj gönderildi!";
}

// Mesaj silme
if(isset($_GET['sil'])) {
    $id = $_GET['sil'];
    $stmt = $pdo->prepare("DELETE FROM ogrenci_mesajlari WHERE id = ?");
    $stmt->execute([$id]);
    $mesaj_bildirim = "Mesaj silindi!";
    header("Location: mesajlar.php");
    exit;
}

// Aktif öğrenciler
$ogrenciler = $pdo->query("SELECT id, ad_soyad FROM ogrenciler WHERE aktif = 1 ORDER BY ad_soyad")->fetchAll();

// Gönderilen mesajlar
$mesajlar = $pdo->query("
    SELECT m.*, o.ad_soyad,
           CASE WHEN m.okundu = 1 THEN 'Okundu' ELSE 'Okunmadı' END as okunma_durumu
    FROM ogrenci_mesajlari m
    JOIN ogrenciler o ON m.ogrenci_id = o.id
    ORDER BY m.gonderim_zamani DESC
    LIMIT 100
")->fetchAll();

// İstatistikler
$stats = $pdo->query("
    SELECT
        COUNT(*) as toplam_mesaj,
        SUM(CASE WHEN okundu = 0 THEN 1 ELSE 0 END) as okunmamis,
        SUM(CASE WHEN okundu = 1 THEN 1 ELSE 0 END) as okunmus
    FROM ogrenci_mesajlari
")->fetch();

$aktif_sayfa = 'mesajlar';
$sayfa_basligi = 'Mesaj Yönetimi - Cami Namaz Takip';
require_once 'config/header.php';
?>

<div style="padding: 30px;">
    <h2>💬 Öğrenci Mesaj Yönetimi</h2>

    <?php if($mesaj_bildirim): ?>
    <div class="alert success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        ✅ <?php echo $mesaj_bildirim; ?>
    </div>
    <?php endif; ?>

    <!-- İstatistikler -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
            <h3 style="margin: 0; font-size: 36px;"><?php echo $stats['toplam_mesaj']; ?></h3>
            <p style="margin: 5px 0 0 0;">Toplam Mesaj</p>
        </div>
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
            <h3 style="margin: 0; font-size: 36px;"><?php echo $stats['okunmamis']; ?></h3>
            <p style="margin: 5px 0 0 0;">Okunmamış</p>
        </div>
        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
            <h3 style="margin: 0; font-size: 36px;"><?php echo $stats['okunmus']; ?></h3>
            <p style="margin: 5px 0 0 0;">Okunmuş</p>
        </div>
    </div>

    <!-- Mesaj Gönderme Formları -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">

        <!-- Tekli Mesaj -->
        <div style="background: #f8f9fa; padding: 25px; border-radius: 10px;">
            <h3>📧 Tek Öğrenciye Mesaj Gönder</h3>
            <form method="POST" style="display: grid; gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Öğrenci Seçin:</label>
                    <select name="ogrenci_id" required style="width: 100%; padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                        <option value="">-- Öğrenci Seçin --</option>
                        <?php foreach($ogrenciler as $o): ?>
                        <option value="<?php echo $o['id']; ?>"><?php echo $o['ad_soyad']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Öncelik:</label>
                    <select name="oncelik" required style="width: 100%; padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                        <option value="Normal">📝 Normal</option>
                        <option value="Önemli">⚠️ Önemli</option>
                        <option value="Acil">🚨 Acil</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Mesaj:</label>
                    <textarea name="mesaj" rows="5" required placeholder="Mesajınızı buraya yazın..." style="width: 100%; padding: 10px; border-radius: 5px; border: 2px solid #ddd; resize: vertical;"></textarea>
                </div>

                <button type="submit" name="mesaj_gonder" style="background: #28a745; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600;">
                    📤 Mesaj Gönder
                </button>
            </form>
        </div>

        <!-- Toplu Mesaj -->
        <div style="background: #fff3cd; padding: 25px; border-radius: 10px; border: 2px solid #ffc107;">
            <h3>📢 Toplu Mesaj Gönder</h3>
            <form method="POST" style="display: grid; gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Hedef Grup:</label>
                    <select name="hedef" required style="width: 100%; padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                        <option value="tum_ogrenciler">👥 Tüm Aktif Öğrenciler</option>
                        <option value="pasif_ogrenciler">💤 Pasif Öğrenciler</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Öncelik:</label>
                    <select name="oncelik" required style="width: 100%; padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                        <option value="Normal">📝 Normal</option>
                        <option value="Önemli">⚠️ Önemli</option>
                        <option value="Acil">🚨 Acil</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Mesaj:</label>
                    <textarea name="mesaj" rows="5" required placeholder="Toplu mesajınızı buraya yazın..." style="width: 100%; padding: 10px; border-radius: 5px; border: 2px solid #ddd; resize: vertical;"></textarea>
                </div>

                <button type="submit" name="toplu_mesaj_gonder" onclick="return confirm('Seçili gruba toplu mesaj göndermek istediğinizden emin misiniz?');" style="background: #ff9800; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600;">
                    📣 Toplu Mesaj Gönder
                </button>
            </form>
        </div>
    </div>

    <!-- Mesaj Listesi -->
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3>📋 Gönderilen Mesajlar</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                    <th style="padding: 12px; text-align: left;">Öğrenci</th>
                    <th style="padding: 12px; text-align: left;">Mesaj</th>
                    <th style="padding: 12px; text-align: center;">Öncelik</th>
                    <th style="padding: 12px; text-align: center;">Durum</th>
                    <th style="padding: 12px; text-align: center;">Gönderim</th>
                    <th style="padding: 12px; text-align: center;">Okunma</th>
                    <th style="padding: 12px; text-align: center;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($mesajlar) == 0): ?>
                <tr>
                    <td colspan="7" style="padding: 30px; text-align: center; color: #999;">
                        Henüz mesaj gönderilmemiş.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach($mesajlar as $m): ?>
                <tr style="border-bottom: 1px solid #dee2e6;">
                    <td style="padding: 12px;">
                        <strong><?php echo htmlspecialchars($m['ad_soyad']); ?></strong>
                    </td>
                    <td style="padding: 12px;">
                        <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($m['mesaj']); ?>">
                            <?php echo htmlspecialchars($m['mesaj']); ?>
                        </div>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <span style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; background: <?php
                            echo $m['oncelik'] == 'Acil' ? '#dc3545' : ($m['oncelik'] == 'Önemli' ? '#ffc107' : '#6c757d');
                        ?>; color: white;">
                            <?php
                            echo $m['oncelik'] == 'Acil' ? '🚨' : ($m['oncelik'] == 'Önemli' ? '⚠️' : '📝');
                            echo ' ' . $m['oncelik'];
                            ?>
                        </span>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <span style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; background: <?php echo $m['okundu'] ? '#28a745' : '#dc3545'; ?>; color: white;">
                            <?php echo $m['okundu'] ? '✓ Okundu' : '✗ Okunmadı'; ?>
                        </span>
                    </td>
                    <td style="padding: 12px; text-align: center; font-size: 13px; color: #666;">
                        <?php echo date('d.m.Y H:i', strtotime($m['gonderim_zamani'])); ?>
                    </td>
                    <td style="padding: 12px; text-align: center; font-size: 13px; color: #666;">
                        <?php echo $m['okunma_zamani'] ? date('d.m.Y H:i', strtotime($m['okunma_zamani'])) : '-'; ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <button onclick="if(confirm('Bu mesajı silmek istediğinizden emin misiniz?')) window.location.href='?sil=<?php echo $m['id']; ?>';" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 13px;">
                            🗑️ Sil
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'config/footer.php'; ?>
