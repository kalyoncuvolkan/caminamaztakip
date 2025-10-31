<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$ogrenci_id = $_GET['id'] ?? 0;
$mesaj = '';

// Öğrenci seçilmemişse, öğrenci listesini göster
if(!$ogrenci_id) {
    // Öğrenci listesi
    $ogrenciler = $pdo->query("SELECT * FROM ogrenciler WHERE aktif = 1 ORDER BY ad_soyad")->fetchAll();

    $aktif_sayfa = 'puan';
    $sayfa_basligi = 'Puan Yönetimi - Öğrenci Seçin';
    require_once 'config/header.php';
    ?>
    <div style="padding: 30px;">
        <h2>⭐ Puan Yönetimi - Öğrenci Seçin</h2>
        <p style="color: #666; margin-bottom: 20px;">İlave puan eklemek veya puan silmek için bir öğrenci seçin:</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach($ogrenciler as $ogr): ?>
            <a href="puan-yonetimi.php?id=<?php echo $ogr['id']; ?>" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)';">
                <div style="font-size: 24px; margin-bottom: 10px;">👤</div>
                <div style="font-weight: 600; font-size: 18px;"><?php echo htmlspecialchars($ogr['ad_soyad']); ?></div>
                <div style="opacity: 0.9; font-size: 14px; margin-top: 5px;">Yaş: <?php echo yasHesapla($ogr['dogum_tarihi']); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    require_once 'config/footer.php';
    exit;
}

// Öğrenci bilgileri
$ogrenci_stmt = $pdo->prepare("SELECT * FROM ogrenciler WHERE id = ?");
$ogrenci_stmt->execute([$ogrenci_id]);
$ogrenci = $ogrenci_stmt->fetch();

if(!$ogrenci) {
    header('Location: puan-yonetimi.php');
    exit;
}

// Namaz kayıtları
$namazlar = $pdo->prepare("
    SELECT * FROM namaz_kayitlari
    WHERE ogrenci_id = ?
    ORDER BY tarih DESC, saat DESC
");
$namazlar->execute([$ogrenci_id]);
$kayitlar = $namazlar->fetchAll();

// İlave puanlar - Namaz
$ilaveler_namaz = $pdo->prepare("SELECT * FROM ilave_puanlar WHERE ogrenci_id = ? AND kategori = 'Namaz' ORDER BY tarih DESC");
$ilaveler_namaz->execute([$ogrenci_id]);
$ilave_puanlar_namaz = $ilaveler_namaz->fetchAll();

// İlave puanlar - Ders
$ilaveler_ders = $pdo->prepare("SELECT * FROM ilave_puanlar WHERE ogrenci_id = ? AND kategori = 'Ders' ORDER BY tarih DESC");
$ilaveler_ders->execute([$ogrenci_id]);
$ilave_puanlar_ders = $ilaveler_ders->fetchAll();

// Silinen namaz kayıtları
$silinenler = $pdo->prepare("SELECT * FROM puan_silme_gecmisi WHERE ogrenci_id = ? ORDER BY silme_zamani DESC");
$silinenler->execute([$ogrenci_id]);
$silinen_kayitlar = $silinenler->fetchAll();

// Silinen ilave puanlar - Namaz
$silinen_ilaveler_namaz = $pdo->prepare("SELECT * FROM ilave_puan_silme_gecmisi WHERE ogrenci_id = ? AND kategori = 'Namaz' ORDER BY silme_zamani DESC");
$silinen_ilaveler_namaz->execute([$ogrenci_id]);
$silinen_ilave_puanlar_namaz = $silinen_ilaveler_namaz->fetchAll();

// Silinen ilave puanlar - Ders
$silinen_ilaveler_ders = $pdo->prepare("SELECT * FROM ilave_puan_silme_gecmisi WHERE ogrenci_id = ? AND kategori = 'Ders' ORDER BY silme_zamani DESC");
$silinen_ilaveler_ders->execute([$ogrenci_id]);
$silinen_ilave_puanlar_ders = $silinen_ilaveler_ders->fetchAll();

// İlave puan ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ilave_puan_ekle'])) {
    $puan = $_POST['puan'];
    $kategori = $_POST['kategori'];
    $aciklama = $_POST['aciklama'];
    $tarih = $_POST['tarih'];

    $stmt = $pdo->prepare("INSERT INTO ilave_puanlar (ogrenci_id, puan, kategori, aciklama, veren_kullanici, tarih) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$ogrenci_id, $puan, $kategori, $aciklama, getLoggedInUser(), $tarih]);
    $mesaj = "İlave puan başarıyla eklendi!";
    header("Location: puan-yonetimi.php?id=$ogrenci_id");
    exit;
}

$aktif_sayfa = 'puan';
$sayfa_basligi = 'Puan Yönetimi - ' . $ogrenci['ad_soyad'] . ' - Cami Namaz Takip';
require_once 'config/header.php';
?>

        <div style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">⭐ Puan Yönetimi: <?php echo $ogrenci['ad_soyad']; ?></h2>
                <a href="puan-yonetimi.php" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    ← Öğrenci Listesi
                </a>
            </div>

            <!-- İlave Puan Ekle -->
            <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h3>➕ İlave Puan Ekle</h3>
                <form method="POST" style="display: grid; gap: 15px; max-width: 600px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Kategori:</label>
                        <select name="kategori" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;">
                            <option value="">Kategori Seçin</option>
                            <option value="Namaz" selected>🕌 Namaz</option>
                            <option value="Ders">📚 Ders</option>
                        </select>
                    </div>
                    <input type="number" name="puan" placeholder="Puan miktarı" required min="1" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <input type="date" name="tarih" value="<?php echo date('Y-m-d'); ?>" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <textarea name="aciklama" placeholder="Açıklama (opsiyonel)" rows="3" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;"></textarea>
                    <button type="submit" name="ilave_puan_ekle" class="btn-primary" style="width: auto;">💾 Puan Ekle</button>
                </form>
            </div>

            <!-- Namaz Kayıtları -->
            <h3>🕌 Namaz Kayıtları</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Vakit</th>
                        <th>Kiminle Geldi</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($kayitlar as $kayit): ?>
                    <tr id="kayit-<?php echo $kayit['id']; ?>">
                        <td><?php echo date('d.m.Y', strtotime($kayit['tarih'])); ?></td>
                        <td><?php echo $kayit['namaz_vakti']; ?></td>
                        <td><?php echo $kayit['kiminle_geldi']; ?></td>
                        <td><button onclick="puanSil(<?php echo $kayit['id']; ?>)" class="btn-sm btn-delete">🗑️ Sil</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- İlave Namaz Puanları -->
            <?php if(count($ilave_puanlar_namaz) > 0): ?>
            <h3 style="margin-top: 30px;">⭐ İlave Namaz Puanları</h3>
            <table>
                <thead>
                    <tr><th>Tarih</th><th>Puan</th><th>Açıklama</th><th>Veren</th><th>İşlem</th></tr>
                </thead>
                <tbody>
                    <?php
                    $toplam_namaz_ilave = 0;
                    foreach($ilave_puanlar_namaz as $ip):
                        $toplam_namaz_ilave += $ip['puan'];
                    ?>
                    <tr id="ilave-puan-<?php echo $ip['id']; ?>">
                        <td><?php echo date('d.m.Y', strtotime($ip['tarih'])); ?></td>
                        <td><strong style="color: #28a745;">+<?php echo $ip['puan']; ?></strong></td>
                        <td><?php echo htmlspecialchars($ip['aciklama']); ?></td>
                        <td><?php echo $ip['veren_kullanici']; ?></td>
                        <td><button onclick="ilavePuanSil(<?php echo $ip['id']; ?>)" class="btn-sm btn-delete">🗑️ Sil</button></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background: #d4edda; font-weight: bold;">
                        <td colspan="4" style="text-align: right; padding: 10px;">Toplam İlave Namaz Puanı:</td>
                        <td style="color: #28a745; font-size: 18px;">+<?php echo $toplam_namaz_ilave; ?></td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Silinen Namaz Kayıtları -->
            <?php if(count($silinen_kayitlar) > 0): ?>
            <h3 style="margin-top: 30px;">❌ Silinen Namaz Kayıtları</h3>
            <table>
                <thead>
                    <tr><th>Tarih</th><th>Vakit</th><th>Kiminle</th><th>Silme Nedeni</th><th>Silen</th><th>Silme Zamanı</th></tr>
                </thead>
                <tbody>
                    <?php foreach($silinen_kayitlar as $s): ?>
                    <tr style="background: #f8d7da;"><td><?php echo date('d.m.Y', strtotime($s['tarih'])); ?></td>
                    <td><?php echo $s['namaz_vakti']; ?></td>
                    <td><?php echo $s['kiminle_geldi']; ?></td>
                    <td><?php echo htmlspecialchars($s['silme_nedeni']); ?></td>
                    <td><?php echo $s['silen_kullanici']; ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($s['silme_zamani'])); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- İlave Ders Puanları -->
            <?php if(count($ilave_puanlar_ders) > 0): ?>
            <h3 style="margin-top: 30px;">📚 İlave Ders Puanları</h3>
            <table>
                <thead>
                    <tr><th>Tarih</th><th>Puan</th><th>Açıklama</th><th>Veren</th><th>İşlem</th></tr>
                </thead>
                <tbody>
                    <?php
                    $toplam_ders_ilave = 0;
                    foreach($ilave_puanlar_ders as $ip):
                        $toplam_ders_ilave += $ip['puan'];
                    ?>
                    <tr id="ilave-puan-<?php echo $ip['id']; ?>">
                        <td><?php echo date('d.m.Y', strtotime($ip['tarih'])); ?></td>
                        <td><strong style="color: #007bff;">+<?php echo $ip['puan']; ?></strong></td>
                        <td><?php echo htmlspecialchars($ip['aciklama']); ?></td>
                        <td><?php echo $ip['veren_kullanici']; ?></td>
                        <td><button onclick="ilavePuanSil(<?php echo $ip['id']; ?>)" class="btn-sm btn-delete">🗑️ Sil</button></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background: #cce5ff; font-weight: bold;">
                        <td colspan="4" style="text-align: right; padding: 10px;">Toplam İlave Ders Puanı:</td>
                        <td style="color: #007bff; font-size: 18px;">+<?php echo $toplam_ders_ilave; ?></td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Silinen İlave Namaz Puanları -->
            <?php if(count($silinen_ilave_puanlar_namaz) > 0): ?>
            <h3 style="margin-top: 30px;">❌ Silinen İlave Namaz Puanları</h3>
            <table>
                <thead>
                    <tr><th>Tarih</th><th>Puan</th><th>Açıklama</th><th>Veren</th><th>Silme Nedeni</th><th>Silen</th><th>Silme Zamanı</th></tr>
                </thead>
                <tbody>
                    <?php foreach($silinen_ilave_puanlar_namaz as $sip): ?>
                    <tr style="background: #fff3cd;">
                        <td><?php echo date('d.m.Y', strtotime($sip['tarih'])); ?></td>
                        <td><strong>+<?php echo $sip['puan']; ?></strong></td>
                        <td><?php echo htmlspecialchars($sip['aciklama']); ?></td>
                        <td><?php echo $sip['veren_kullanici']; ?></td>
                        <td><?php echo htmlspecialchars($sip['silme_nedeni']); ?></td>
                        <td><?php echo $sip['silen_kullanici']; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($sip['silme_zamani'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Silinen İlave Ders Puanları -->
            <?php if(count($silinen_ilave_puanlar_ders) > 0): ?>
            <h3 style="margin-top: 30px;">❌ Silinen İlave Ders Puanları</h3>
            <table>
                <thead>
                    <tr><th>Tarih</th><th>Puan</th><th>Açıklama</th><th>Veren</th><th>Silme Nedeni</th><th>Silen</th><th>Silme Zamanı</th></tr>
                </thead>
                <tbody>
                    <?php foreach($silinen_ilave_puanlar_ders as $sip): ?>
                    <tr style="background: #fff3cd;">
                        <td><?php echo date('d.m.Y', strtotime($sip['tarih'])); ?></td>
                        <td><strong>+<?php echo $sip['puan']; ?></strong></td>
                        <td><?php echo htmlspecialchars($sip['aciklama']); ?></td>
                        <td><?php echo $sip['veren_kullanici']; ?></td>
                        <td><?php echo htmlspecialchars($sip['silme_nedeni']); ?></td>
                        <td><?php echo $sip['silen_kullanici']; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($sip['silme_zamani'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function puanSil(kayitId) {
            const nedeni = prompt('❓ Namaz kaydı silme nedeni (opsiyonel):');
            if(nedeni !== null) {
                fetch('api/puan-sil.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'kayit_id=' + kayitId + '&nedeni=' + encodeURIComponent(nedeni)
                })
                .then(r => r.json())
                .then(d => {
                    if(d.success) {
                        alert('✅ Namaz kaydı silindi ve geçmişe kaydedildi');
                        location.reload();
                    } else {
                        alert('❌ Hata: ' + d.message);
                    }
                });
            }
        }

        function ilavePuanSil(ilavePuanId) {
            const nedeni = prompt('❓ İlave puan silme nedeni (opsiyonel):');
            if(nedeni !== null) {
                fetch('api/ilave-puan-sil.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'ilave_puan_id=' + ilavePuanId + '&nedeni=' + encodeURIComponent(nedeni)
                })
                .then(r => r.json())
                .then(d => {
                    if(d.success) {
                        alert('✅ İlave puan silindi ve geçmişe kaydedildi');
                        location.reload();
                    } else {
                        alert('❌ Hata: ' + d.message);
                    }
                });
            }
        }
    </script>
<?php require_once 'config/footer.php'; ?>