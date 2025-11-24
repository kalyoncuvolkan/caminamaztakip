<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$ogrenci_id = $_GET['id'] ?? 0;
$mesaj = '';

// Öğrenci seçilmemişse, öğrenci listesini göster
if(!$ogrenci_id) {
    // Arama parametresi
    $arama = $_GET['arama'] ?? '';

    // Öğrenci listesi
    $sql = "SELECT * FROM ogrenciler WHERE aktif = 1";
    $params = [];

    if($arama) {
        $sql .= " AND ad_soyad LIKE ?";
        $params[] = "%$arama%";
    }

    $sql .= " ORDER BY ad_soyad";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ogrenciler = $stmt->fetchAll();

    $aktif_sayfa = 'puan';
    $sayfa_basligi = 'Puan Yönetimi - Öğrenci Seçin';
    require_once 'config/header.php';
    ?>
    <div style="padding: 30px;">
        <h2>⭐ Puan Yönetimi - Öğrenci Seçin</h2>
        <p style="color: #666; margin-bottom: 20px;">İlave puan eklemek veya puan silmek için bir öğrenci seçin:</p>

        <!-- Arama Formu -->
        <form method="GET" action="" style="margin-bottom: 20px;">
            <div style="display: flex; gap: 10px; align-items: center; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <input type="text" name="arama" placeholder="🔍 Öğrenci ara..." value="<?php echo htmlspecialchars($arama); ?>"
                       style="flex: 1; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                <button type="submit" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
                    Ara
                </button>
                <?php if($arama): ?>
                <a href="puan-yonetimi.php" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-weight: 600;">
                    Temizle
                </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if(count($ogrenciler) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach($ogrenciler as $ogr): ?>
            <a href="puan-yonetimi.php?id=<?php echo $ogr['id']; ?>" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)';">
                <div style="font-size: 24px; margin-bottom: 10px;">👤</div>
                <div style="font-weight: 600; font-size: 18px;"><?php echo htmlspecialchars($ogr['ad_soyad']); ?></div>
                <div style="opacity: 0.9; font-size: 14px; margin-top: 5px;">Yaş: <?php echo yasHesapla($ogr['dogum_tarihi']); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 5px;">
            <strong>⚠️ Sonuç bulunamadı!</strong> "<?php echo htmlspecialchars($arama); ?>" araması için öğrenci bulunamadı.
        </div>
        <?php endif; ?>
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

// Ön tanımlı puan şablonlarını çek
$puan_sablonlari = $pdo->query("SELECT * FROM puan_sablon WHERE aktif = 1 ORDER BY kategori, sira, baslik")->fetchAll();
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

// İlave puan ekleme (pozitif veya negatif)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ilave_puan_ekle'])) {
    $puan = intval($_POST['puan']);
    $kategori = $_POST['kategori'];
    $aciklama = trim($_POST['aciklama']);
    $tarih = $_POST['tarih'];

    // Açıklama kontrolü
    if(empty($aciklama)) {
        $mesaj = "Hata: Açıklama zorunludur!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO ilave_puanlar (ogrenci_id, puan, kategori, aciklama, veren_kullanici, tarih) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ogrenci_id, $puan, $kategori, $aciklama, getLoggedInUser(), $tarih]);

        if($puan > 0) {
            $mesaj = "✅ İlave puan (+{$puan}) başarıyla eklendi!";
        } else {
            $mesaj = "⚠️ Ceza puanı ({$puan}) başarıyla eklendi!";
        }
        header("Location: puan-yonetimi.php?id=$ogrenci_id");
        exit;
    }
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

            <!-- İlave Puan Ekle / Ceza Puanı Ekle -->
            <div style="background: linear-gradient(135deg, #e8f5e9 0%, #fff3cd 100%); padding: 20px; border-radius: 10px; margin: 20px 0; border: 2px solid #28a745;">
                <h3>➕ İlave Puan Ekle / ⚠️ Ceza Puanı Ekle</h3>
                <p style="color: #666; margin-bottom: 15px; font-size: 14px;">
                    💡 <strong>İpucu:</strong> Ödül puanı için pozitif (+), ceza puanı için negatif (-) değer girin.
                    <br>Örnek: <span style="color: #28a745;">+5</span> (ödül) veya <span style="color: #dc3545;">-3</span> (ceza)
                </p>
                <form method="POST" style="display: grid; gap: 15px; max-width: 600px;" id="puanForm">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Ön Tanımlı Puan Seç (İsteğe Bağlı):</label>
                        <select id="puanSablon" style="padding: 10px; border-radius: 5px; border: 2px solid #667eea; width: 100%; font-weight: 600; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            <option value="">-- Veya Manuel Gir --</option>
                            <?php
                            $onceki_kategori = '';
                            foreach($puan_sablonlari as $sablon):
                                if($onceki_kategori != $sablon['kategori']) {
                                    if($onceki_kategori != '') echo '</optgroup>';
                                    echo '<optgroup label="' . ($sablon['kategori'] == 'Namaz' ? '🕌 Namaz' : '📚 Ders') . '">';
                                    $onceki_kategori = $sablon['kategori'];
                                }
                            ?>
                                <option value='<?php echo json_encode(['kategori' => $sablon['kategori'], 'puan' => $sablon['puan'], 'aciklama' => $sablon['baslik']]); ?>'>
                                    <?php echo $sablon['baslik']; ?> (<?php echo $sablon['puan'] > 0 ? '+' : ''; ?><?php echo $sablon['puan']; ?> puan)
                                </option>
                            <?php endforeach; ?>
                            <?php if($onceki_kategori != '') echo '</optgroup>'; ?>
                        </select>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Hızlı işlem için hazır puan şablonlarından birini seçin
                        </small>
                    </div>
                    <div style="border-top: 2px dashed #ddd; padding-top: 15px;">
                        <small style="color: #999; display: block; margin-bottom: 10px; text-align: center;">VEYA MANUEL GİRİŞ YAP</small>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Kategori:</label>
                        <select name="kategori" id="kategoriSelect" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;">
                            <option value="">Kategori Seçin</option>
                            <option value="Namaz" selected>🕌 Namaz</option>
                            <option value="Ders">📚 Ders</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Puan Miktarı:</label>
                        <input type="number" name="puan" id="puanInput" placeholder="Pozitif (+5) veya Negatif (-3)" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%; font-size: 16px;">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Ödül için pozitif sayı (+5), ceza için negatif sayı (-3) girin
                        </small>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Tarih:</label>
                        <input type="date" name="tarih" value="<?php echo date('Y-m-d'); ?>" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Açıklama:</label>
                        <textarea name="aciklama" id="aciklamaInput" placeholder="Örnek: Güzel davranış için ödül (+5) veya Kurallara uymadığı için ceza (-3)" rows="3" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;"></textarea>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            ⚠️ Açıklama zorunludur - özellikle ceza puanları için nedeni belirtiniz
                        </small>
                    </div>
                    <button type="submit" name="ilave_puan_ekle" class="btn-primary" style="width: auto; padding: 12px 30px; font-size: 16px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        💾 Puanı Kaydet
                    </button>
                </form>

                <script>
                // Ön tanımlı puan seçildiğinde form alanlarını doldur
                document.getElementById('puanSablon').addEventListener('change', function() {
                    if(this.value) {
                        const data = JSON.parse(this.value);
                        document.getElementById('kategoriSelect').value = data.kategori;
                        document.getElementById('puanInput').value = data.puan;
                        document.getElementById('aciklamaInput').value = data.aciklama;

                        // Form alanlarını vurgula
                        document.getElementById('kategoriSelect').style.border = '2px solid #28a745';
                        document.getElementById('puanInput').style.border = '2px solid #28a745';
                        document.getElementById('aciklamaInput').style.border = '2px solid #28a745';

                        setTimeout(() => {
                            document.getElementById('kategoriSelect').style.border = '2px solid #ddd';
                            document.getElementById('puanInput').style.border = '2px solid #ddd';
                            document.getElementById('aciklamaInput').style.border = '2px solid #ddd';
                        }, 1000);
                    }
                });
                </script>
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
                    <tr id="ilave-puan-<?php echo $ip['id']; ?>" style="<?php echo $ip['puan'] < 0 ? 'background: #fff3cd;' : ''; ?>">
                        <td><?php echo date('d.m.Y', strtotime($ip['tarih'])); ?></td>
                        <td><strong style="color: <?php echo $ip['puan'] < 0 ? '#dc3545' : '#28a745'; ?>;">
                            <?php echo $ip['puan'] > 0 ? '+' : ''; ?><?php echo $ip['puan']; ?>
                        </strong></td>
                        <td><?php echo htmlspecialchars($ip['aciklama']); ?></td>
                        <td><?php echo $ip['veren_kullanici']; ?></td>
                        <td><button onclick="ilavePuanSil(<?php echo $ip['id']; ?>)" class="btn-sm btn-delete">🗑️ Sil</button></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background: <?php echo $toplam_namaz_ilave < 0 ? '#fff3cd' : '#d4edda'; ?>; font-weight: bold;">
                        <td colspan="4" style="text-align: right; padding: 10px;">Toplam İlave Namaz Puanı:</td>
                        <td style="color: <?php echo $toplam_namaz_ilave < 0 ? '#dc3545' : '#28a745'; ?>; font-size: 18px;">
                            <?php echo $toplam_namaz_ilave > 0 ? '+' : ''; ?><?php echo $toplam_namaz_ilave; ?>
                        </td>
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
                    <tr id="ilave-puan-<?php echo $ip['id']; ?>" style="<?php echo $ip['puan'] < 0 ? 'background: #fff3cd;' : ''; ?>">
                        <td><?php echo date('d.m.Y', strtotime($ip['tarih'])); ?></td>
                        <td><strong style="color: <?php echo $ip['puan'] < 0 ? '#dc3545' : '#007bff'; ?>;">
                            <?php echo $ip['puan'] > 0 ? '+' : ''; ?><?php echo $ip['puan']; ?>
                        </strong></td>
                        <td><?php echo htmlspecialchars($ip['aciklama']); ?></td>
                        <td><?php echo $ip['veren_kullanici']; ?></td>
                        <td><button onclick="ilavePuanSil(<?php echo $ip['id']; ?>)" class="btn-sm btn-delete">🗑️ Sil</button></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background: <?php echo $toplam_ders_ilave < 0 ? '#fff3cd' : '#cce5ff'; ?>; font-weight: bold;">
                        <td colspan="4" style="text-align: right; padding: 10px;">Toplam İlave Ders Puanı:</td>
                        <td style="color: <?php echo $toplam_ders_ilave < 0 ? '#dc3545' : '#007bff'; ?>; font-size: 18px;">
                            <?php echo $toplam_ders_ilave > 0 ? '+' : ''; ?><?php echo $toplam_ders_ilave; ?>
                        </td>
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
            const nedeni = prompt('❓ İlave puan silme nedeni:\n\n(Bu alan zorunludur)');

            if(nedeni === null) {
                return; // İptal edildi
            }

            if(nedeni.trim() === '') {
                alert('❌ Silme nedeni boş bırakılamaz!');
                return;
            }

            if(!confirm('⚠️ Bu ilave puanı silmek istediğinize emin misiniz?\n\nSilme nedeni: ' + nedeni)) {
                return;
            }

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
    </script>
<?php require_once 'config/footer.php'; ?>