<?php
// Debug mode - hatalar ekranda gösterilir
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

try {
    require_once 'config/auth.php';
    checkAuth();
    require_once 'config/db.php';

    $ogrenci_id = $_GET['id'] ?? 0;

// Öğrenci bilgisi
$ogrenci_stmt = $pdo->prepare("SELECT * FROM ogrenciler WHERE id = ?");
$ogrenci_stmt->execute([$ogrenci_id]);
$ogrenci = $ogrenci_stmt->fetch();

if(!$ogrenci) {
    header('Location: ogrenciler.php');
    exit;
}

// Kategorilere göre dersleri çek
$kategoriler = $pdo->query("
    SELECT dk.*,
           (SELECT COUNT(*) FROM dersler WHERE kategori_id = dk.id AND aktif = 1) as ders_sayisi
    FROM ders_kategorileri dk
    WHERE dk.aktif = 1
    ORDER BY dk.sira, dk.kategori_adi
")->fetchAll();

// Her kategori için dersleri çek
$kategori_dersler = [];
foreach($kategoriler as $kat) {
    $dersler = $pdo->prepare("
        SELECT d.*, od.id as ogrenci_ders_id, od.durum, od.verme_tarihi,
               od.aktif_edilme_sayisi, od.puan_verildi, od.notlar
        FROM dersler d
        LEFT JOIN ogrenci_dersler od ON d.id = od.ders_id AND od.ogrenci_id = ?
        WHERE d.kategori_id = ? AND d.aktif = 1
        ORDER BY d.sira, d.ders_adi
    ");
    $dersler->execute([$ogrenci_id, $kat['id']]);
    $kategori_dersler[$kat['id']] = $dersler->fetchAll();
}

// Ders puanı istatistikleri
$ders_stats = $pdo->prepare("
    SELECT
        COUNT(*) as toplam_ders,
        SUM(CASE WHEN od.durum = 'Tamamlandi' THEN 1 ELSE 0 END) as tamamlanan,
        SUM(CASE WHEN od.durum = 'Tamamlandi' AND od.puan_verildi = 1 THEN d.puan ELSE 0 END) as toplam_puan
    FROM ogrenci_dersler od
    JOIN dersler d ON od.ders_id = d.id
    WHERE od.ogrenci_id = ?
");
$ders_stats->execute([$ogrenci_id]);
$stats = $ders_stats->fetch();

// İlave ders puanları
$ilave_ders_puanlari = $pdo->prepare("
    SELECT * FROM ilave_puanlar
    WHERE ogrenci_id = ? AND kategori = 'Ders'
    ORDER BY tarih DESC
");
$ilave_ders_puanlari->execute([$ogrenci_id]);
$ilave_puanlar = $ilave_ders_puanlari->fetchAll();

$ilave_puan_toplam = array_sum(array_column($ilave_puanlar, 'puan'));

// Toplam ders puanı
$toplam_ders_puani = ($stats['toplam_puan'] ?? 0) + $ilave_puan_toplam;

// Sıralama hesapla (ders puanına göre)
$siralama_query = $pdo->prepare("
    SELECT COUNT(*) + 1 as siralama
    FROM (
        SELECT o.id,
               (SELECT SUM(CASE WHEN od.durum = 'Tamamlandi' AND od.puan_verildi = 1 THEN d.puan ELSE 0 END)
                FROM ogrenci_dersler od
                JOIN dersler d ON od.ders_id = d.id
                WHERE od.ogrenci_id = o.id) +
               COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND kategori = 'Ders'), 0) as toplam
        FROM ogrenciler o
        WHERE o.aktif = 1
    ) as puanlar
    WHERE toplam > ?
");
$siralama_query->execute([$toplam_ders_puani]);
$siralama = $siralama_query->fetchColumn();

$toplam_ogrenci = $pdo->query("SELECT COUNT(*) FROM ogrenciler WHERE aktif = 1")->fetchColumn();

// Eklenebilir ders sayısını hesapla
$eklenebilir_ders_sayisi = $pdo->prepare("
    SELECT COUNT(*) FROM dersler d
    LEFT JOIN ogrenci_dersler od ON d.id = od.ders_id AND od.ogrenci_id = ?
    WHERE d.aktif = 1 AND od.id IS NULL
");
$eklenebilir_ders_sayisi->execute([$ogrenci_id]);
$eklenebilir_toplam = $eklenebilir_ders_sayisi->fetchColumn();

// Silinen dersleri çek
$silinen_dersler_query = $pdo->prepare("
    SELECT * FROM ogrenci_ders_silme_gecmisi
    WHERE ogrenci_id = ?
    ORDER BY silme_zamani DESC
    LIMIT 20
");
$silinen_dersler_query->execute([$ogrenci_id]);
$silinen_dersler = $silinen_dersler_query->fetchAll();

$aktif_sayfa = 'ogrenciler';
$sayfa_basligi = 'Dersler - ' . $ogrenci['ad_soyad'] . ' - Cami Namaz Takip';
require_once 'config/header.php';
?>

<div style="padding: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin: 0;">📚 <?php echo htmlspecialchars($ogrenci['ad_soyad']); ?> - Dersler</h2>
        <a href="ogrenciler.php" class="btn-primary" style="text-decoration: none; display: inline-block; padding: 10px 20px;">
            ← Öğrenci Listesi
        </a>
    </div>

    <!-- İstatistikler -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; text-align: center;">
            <h3 style="margin: 0; font-size: 42px;"><?php echo $stats['toplam_ders'] ?? 0; ?></h3>
            <p style="margin: 10px 0 0 0;">📖 Toplam Ders</p>
        </div>
        <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 25px; border-radius: 12px; text-align: center;">
            <h3 style="margin: 0; font-size: 42px;"><?php echo $stats['tamamlanan'] ?? 0; ?></h3>
            <p style="margin: 10px 0 0 0;">✅ Tamamlanan</p>
        </div>
        <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 25px; border-radius: 12px; text-align: center;">
            <h3 style="margin: 0; font-size: 42px;"><?php echo $toplam_ders_puani; ?></h3>
            <p style="margin: 10px 0 0 0;">⭐ Toplam Puan</p>
        </div>
        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 25px; border-radius: 12px; text-align: center;">
            <h3 style="margin: 0; font-size: 42px;">#<?php echo $siralama; ?></h3>
            <p style="margin: 10px 0 0 0;">🏆 Sıralama</p>
            <p style="margin: 5px 0 0 0; font-size: 12px; opacity: 0.9;"><?php echo $toplam_ogrenci; ?> öğrenci arasında</p>
        </div>
    </div>

    <!-- Ders Ekleme Butonu -->
    <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
        <h3 style="margin: 0 0 15px 0;">➕ Yeni Ders Ekle</h3>
        <?php if($eklenebilir_toplam > 0): ?>
        <form id="dersEkleForm" style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Ders Seçin:</label>
                <select name="ders_id" id="ders_id" required style="width: 100%; padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <option value="">Kategori ve ders seçin...</option>
                    <?php
                    foreach($kategoriler as $kat):
                        $tum_dersler = $pdo->prepare("
                            SELECT d.* FROM dersler d
                            LEFT JOIN ogrenci_dersler od ON d.id = od.ders_id AND od.ogrenci_id = ?
                            WHERE d.kategori_id = ? AND d.aktif = 1 AND od.id IS NULL
                            ORDER BY d.sira, d.ders_adi
                        ");
                        $tum_dersler->execute([$ogrenci_id, $kat['id']]);
                        $eklenebilir_dersler = $tum_dersler->fetchAll();

                        if(count($eklenebilir_dersler) > 0):
                    ?>
                        <optgroup label="<?php echo htmlspecialchars($kat['kategori_adi']); ?>">
                            <?php foreach($eklenebilir_dersler as $ders): ?>
                            <option value="<?php echo $ders['id']; ?>"><?php echo htmlspecialchars($ders['ders_adi']); ?> (<?php echo $ders['puan']; ?> puan)</option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </select>
            </div>
            <button type="button" onclick="dersEkle()" class="btn-primary" style="padding: 10px 30px; height: fit-content;">
                💾 Ekle
            </button>
        </form>
        <?php else: ?>
        <div class="alert info" style="background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8;">
            <strong>ℹ️ Bilgi:</strong> Bu öğrenciye eklenebilecek yeni ders bulunmamaktadır. Tüm aktif dersler zaten öğrenciye atanmış durumda.
            <div style="margin-top: 10px;">
                <a href="ders-kategorileri.php" style="color: #0c5460; text-decoration: underline;">📚 Yeni ders eklemek için tıklayın</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Dersler (Kategorilere göre) -->
    <?php foreach($kategoriler as $kat): ?>
        <?php
        $dersler = $kategori_dersler[$kat['id']];
        if(empty($dersler)) continue;

        // Verilen ve verilmeyen dersleri ayır (sadece öğrenciye atanmış olanlar)
        $verilen = array_filter($dersler, function($d) {
            return $d['ogrenci_ders_id'] !== null && $d['durum'] == 'Tamamlandi';
        });
        $verilmeyen = array_filter($dersler, function($d) {
            return $d['ogrenci_ders_id'] !== null && $d['durum'] != 'Tamamlandi';
        });
        ?>

        <div style="background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px;">
            <h3 style="margin: 0 0 20px 0; color: #667eea;">
                📚 <?php echo htmlspecialchars($kat['kategori_adi']); ?>
                <span style="font-size: 14px; font-weight: normal; color: #666;">
                    (<?php echo count($verilen); ?>/<?php echo count($dersler); ?> tamamlandı)
                </span>
            </h3>

            <!-- Verilen Dersler -->
            <?php if(!empty($verilen)): ?>
            <div style="margin-bottom: 25px;">
                <h4 style="color: #28a745; margin: 0 0 15px 0;">✅ Verilen Dersler</h4>
                <?php foreach($verilen as $ders): ?>
                <div id="ders-<?php echo $ders['ogrenci_ders_id']; ?>" style="background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; border: 2px solid #c3e6cb;">
                    <div style="flex: 1;">
                        <strong style="color: #155724;"><?php echo htmlspecialchars($ders['ders_adi']); ?></strong>
                        <span style="margin-left: 10px; padding: 4px 8px; background: #28a745; color: white; border-radius: 5px; font-size: 12px;">
                            <?php echo $ders['puan']; ?> puan
                        </span>
                        <?php if($ders['verme_tarihi']): ?>
                        <div style="font-size: 13px; color: #666; margin-top: 5px;">
                            📅 <?php echo date('d.m.Y l - H:i', strtotime($ders['verme_tarihi'])); ?>
                        </div>
                        <?php endif; ?>
                        <?php if($ders['aktif_edilme_sayisi'] > 0): ?>
                        <div style="font-size: 12px; color: #856404; margin-top: 3px;">
                            ⚠️ <?php echo $ders['aktif_edilme_sayisi']; ?> kez tekrar aktif edildi
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 5px;">
                        <button onclick="dersAktifEt(<?php echo $ders['ogrenci_ders_id']; ?>)" class="btn-sm" style="background: #ffc107; color: #000; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
                            🔄 Tekrar Aktif Et
                        </button>
                        <button onclick="dersSil(<?php echo $ders['ogrenci_ders_id']; ?>, '<?php echo htmlspecialchars($ders['ders_adi']); ?>')" class="btn-sm" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
                            🗑️ Sil
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Verilmeyen Dersler -->
            <?php if(!empty($verilmeyen)): ?>
            <div>
                <h4 style="color: #dc3545; margin: 0 0 15px 0;">❌ Verilmeyen Dersler</h4>
                <?php foreach($verilmeyen as $ders): ?>
                <div id="ders-<?php echo $ders['ogrenci_ders_id']; ?>" style="background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; border: 2px solid #f5c6cb;">
                    <div style="flex: 1;">
                        <strong style="color: #721c24;"><?php echo htmlspecialchars($ders['ders_adi']); ?></strong>
                        <span style="margin-left: 10px; padding: 4px 8px; background: #6c757d; color: white; border-radius: 5px; font-size: 12px;">
                            <?php echo $ders['puan']; ?> puan
                        </span>
                        <?php if($ders['aktif_edilme_sayisi'] > 0): ?>
                        <div style="font-size: 12px; color: #856404; margin-top: 3px;">
                            ⚠️ <?php echo $ders['aktif_edilme_sayisi']; ?> kez aktif edildi, henüz verilmedi
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 5px;">
                        <button onclick="dersVer(<?php echo $ders['ogrenci_ders_id']; ?>)" class="btn-sm btn-success" style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
                            ✅ Verdi
                        </button>
                        <button onclick="dersSil(<?php echo $ders['ogrenci_ders_id']; ?>, '<?php echo htmlspecialchars($ders['ders_adi']); ?>')" class="btn-sm" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
                            🗑️ Sil
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <!-- İlave Ders Puanları -->
    <?php if(count($ilave_puanlar) > 0): ?>
    <div style="background: #fff3cd; padding: 25px; border-radius: 12px; margin-top: 30px;">
        <h3 style="margin: 0 0 20px 0;">⭐ Aldığınız İlave Ders Puanları</h3>
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Tarih</th>
                    <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Puan</th>
                    <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Açıklama</th>
                    <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Veren</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ilave_puanlar as $ip): ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo date('d.m.Y', strtotime($ip['tarih'])); ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><strong style="color: #28a745;">+<?php echo $ip['puan']; ?></strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($ip['aciklama']); ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo $ip['veren_kullanici']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="padding: 15px; font-weight: bold; font-size: 16px; background: #ffc107; color: #000; text-align: center;">
                        Toplam İlave Puan: +<?php echo $ilave_puan_toplam; ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- Silinen Dersler -->
    <?php if(!empty($silinen_dersler)): ?>
    <div style="background: #fff3cd; padding: 25px; border-radius: 12px; margin-top: 30px; border-left: 5px solid #ffc107;">
        <h3 style="margin: 0 0 20px 0; color: #856404;">🗑️ Silinen Dersler Geçmişi (Son 20)</h3>
        <div style="max-height: 400px; overflow-y: auto;">
            <?php foreach($silinen_dersler as $silinen): ?>
            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #ffc107;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                    <div style="flex: 1;">
                        <strong style="color: #856404; font-size: 16px;"><?php echo htmlspecialchars($silinen['ders_adi']); ?></strong>
                        <span style="margin-left: 10px; padding: 3px 8px; background: #6c757d; color: white; border-radius: 5px; font-size: 12px;">
                            <?php echo htmlspecialchars($silinen['kategori_adi']); ?>
                        </span>
                        <span style="margin-left: 5px; padding: 3px 8px; background: #ffc107; color: #000; border-radius: 5px; font-size: 12px;">
                            <?php echo $silinen['puan']; ?> puan
                        </span>
                    </div>
                    <button onclick="dersYenidenAta(<?php echo $silinen['ders_id']; ?>, '<?php echo htmlspecialchars($silinen['ders_adi']); ?>')"
                            class="btn-sm" style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; white-space: nowrap;">
                        🔄 Yeniden Ata
                    </button>
                </div>
                <div style="font-size: 13px; color: #666; margin-top: 8px;">
                    <div>📅 Silinme: <?php echo date('d.m.Y H:i', strtotime($silinen['silme_zamani'])); ?></div>
                    <?php if($silinen['verme_tarihi']): ?>
                    <div>✅ Verilme: <?php echo date('d.m.Y H:i', strtotime($silinen['verme_tarihi'])); ?> (<?php echo $silinen['durum']; ?>)</div>
                    <?php else: ?>
                    <div>⏸️ Durum: <?php echo $silinen['durum']; ?> (verilmeden silindi)</div>
                    <?php endif; ?>
                    <div>👤 Silen: <?php echo htmlspecialchars($silinen['silen_kullanici']); ?></div>
                    <?php if($silinen['silme_nedeni']): ?>
                    <div style="background: #f8f9fa; padding: 8px; border-radius: 5px; margin-top: 5px;">
                        <strong>Silme Nedeni:</strong> <?php echo htmlspecialchars($silinen['silme_nedeni']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Toplam Özet -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-top: 30px; text-align: center;">
        <h3 style="margin: 0 0 15px 0;">📊 Genel Özet</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
            <div>
                <p style="margin: 0; font-size: 14px; opacity: 0.9;">Ders Puanı</p>
                <p style="margin: 5px 0 0 0; font-size: 32px; font-weight: bold;"><?php echo $stats['toplam_puan'] ?? 0; ?></p>
            </div>
            <div>
                <p style="margin: 0; font-size: 14px; opacity: 0.9;">İlave Puan</p>
                <p style="margin: 5px 0 0 0; font-size: 32px; font-weight: bold;">+<?php echo $ilave_puan_toplam; ?></p>
            </div>
            <div>
                <p style="margin: 0; font-size: 14px; opacity: 0.9;">Toplam Ders Puanı</p>
                <p style="margin: 5px 0 0 0; font-size: 32px; font-weight: bold;"><?php echo $toplam_ders_puani; ?></p>
            </div>
            <div>
                <p style="margin: 0; font-size: 14px; opacity: 0.9;">Sıralama</p>
                <p style="margin: 5px 0 0 0; font-size: 32px; font-weight: bold;"><?php echo $toplam_ogrenci; ?> öğrenci arasından <?php echo $siralama; ?>. oldunuz</p>
            </div>
        </div>
    </div>
</div>

<script>
function dersVer(ogrenciDersId) {
    if(!confirm('Bu ders tamamlandı olarak işaretlenecek. Onaylıyor musunuz?')) return;

    fetch('api/ders-ver.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ogrenci_ders_id=' + ogrenciDersId
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            alert('✅ ' + d.message);
            location.reload();
        } else {
            alert('❌ Hata: ' + d.message);
        }
    });
}

function dersAktifEt(ogrenciDersId) {
    const nedeni = prompt('⚠️ Ders tekrar aktif edilecek ve önceki puan silinecek.\n\nNeden tekrar aktif ediyorsunuz? (opsiyonel)');
    if(nedeni === null) return;

    fetch('api/ders-aktif-et.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ogrenci_ders_id=' + ogrenciDersId + '&nedeni=' + encodeURIComponent(nedeni)
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            alert('✅ ' + d.message);
            location.reload();
        } else {
            alert('❌ Hata: ' + d.message);
        }
    });
}

function dersEkle() {
    const dersId = document.getElementById('ders_id').value;
    if(!dersId) {
        alert('Lütfen bir ders seçin!');
        return;
    }

    fetch('api/ogrenci-ders-ekle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ogrenci_id=<?php echo $ogrenci_id; ?>&ders_id=' + dersId
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            alert('✅ ' + d.message);
            location.reload();
        } else {
            alert('❌ Hata: ' + d.message);
        }
    });
}

function dersSil(ogrenciDersId, dersAdi) {
    const nedeni = prompt('❓ "' + dersAdi + '" dersini öğrenciden silmek istediğinize emin misiniz?\n\nLütfen silme nedenini belirtin:', '');

    if(nedeni === null) {
        return; // İptal
    }

    if(nedeni.trim() === '') {
        alert('❌ Silme nedeni boş bırakılamaz!');
        return;
    }

    fetch('api/ogrenci-ders-sil.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ogrenci_ders_id=' + ogrenciDersId + '&nedeni=' + encodeURIComponent(nedeni)
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            alert('✅ ' + d.message);
            location.reload();
        } else {
            alert('❌ Hata: ' + d.message);
        }
    });
}

function dersYenidenAta(dersId, dersAdi) {
    if(!confirm('🔄 "' + dersAdi + '" dersi öğrenciye yeniden atanacak.\n\nOnaylıyor musunuz?')) {
        return;
    }

    fetch('api/ogrenci-ders-ekle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ogrenci_id=<?php echo $ogrenci_id; ?>&ders_id=' + dersId
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            alert('✅ ' + d.message);
            location.reload();
        } else {
            alert('❌ Hata: ' + d.message);
        }
    });
}
</script>

<?php
} catch(PDOException $e) {
    // Veritabanı hatası - Detaylı hata göster
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hata</title>';
    echo '<style>body{font-family:Arial;padding:20px;background:#f5f5f5}';
    echo '.error{background:#fff;border-left:5px solid #dc3545;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}';
    echo 'h1{color:#dc3545;margin-top:0}pre{background:#f8f9fa;padding:15px;border-radius:5px;overflow-x:auto}';
    echo '.info{background:#d1ecf1;border-left:5px solid #0c5460;padding:15px;margin-top:20px;border-radius:5px}';
    echo '</style></head><body>';
    echo '<div class="error">';
    echo '<h1>🐛 Veritabanı Hatası</h1>';
    echo '<p><strong>Hata Mesajı:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>Hata Kodu:</strong> ' . $e->getCode() . '</p>';
    echo '<p><strong>Dosya:</strong> ' . $e->getFile() . ':' . $e->getLine() . '</p>';
    echo '<h3>Stack Trace:</h3>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';

    echo '<div class="info">';
    echo '<h3>💡 Olası Çözümler:</h3>';
    echo '<ul>';
    echo '<li>Eğer "Column not found" hatası alıyorsanız: Migration dosyalarının uygulandığından emin olun</li>';
    echo '<li>Eğer "Table doesn\'t exist" hatası alıyorsanız: Veritabanı schema\'sını kontrol edin</li>';
    echo '<li>Eğer "Unknown column" hatası alıyorsanız: VIEW\'ları yeniden oluşturun (migrations/v2.2_view_toplam_puan.sql)</li>';
    echo '</ul>';
    echo '<p><strong>Öğrenci ID:</strong> ' . htmlspecialchars($ogrenci_id ?? 'N/A') . '</p>';
    echo '</div>';
    echo '</body></html>';
    exit;
} catch(Exception $e) {
    // Genel hata
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hata</title>';
    echo '<style>body{font-family:Arial;padding:20px;background:#f5f5f5}';
    echo '.error{background:#fff;border-left:5px solid #dc3545;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}';
    echo 'h1{color:#dc3545;margin-top:0}pre{background:#f8f9fa;padding:15px;border-radius:5px;overflow-x:auto}';
    echo '</style></head><body>';
    echo '<div class="error">';
    echo '<h1>❌ Genel Hata</h1>';
    echo '<p><strong>Hata Mesajı:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>Dosya:</strong> ' . $e->getFile() . ':' . $e->getLine() . '</p>';
    echo '<h3>Stack Trace:</h3>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
    echo '</body></html>';
    exit;
}

require_once 'config/footer.php';
?>
