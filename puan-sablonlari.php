<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$mesaj = '';
$mesaj_tip = '';

// Yeni şablon ekle
if(isset($_POST['sablon_ekle'])) {
    $baslik = $_POST['baslik'] ?? '';
    $puan = $_POST['puan'] ?? 0;
    $kategori = $_POST['kategori'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';

    if($baslik && $puan && $kategori) {
        $stmt = $pdo->prepare("INSERT INTO puan_sablon (baslik, puan, kategori, aciklama) VALUES (?, ?, ?, ?)");
        if($stmt->execute([$baslik, $puan, $kategori, $aciklama])) {
            $mesaj = "✅ Yeni puan şablonu başarıyla eklendi!";
            $mesaj_tip = "success";
        } else {
            $mesaj = "❌ Hata: Şablon eklenemedi!";
            $mesaj_tip = "error";
        }
    }
}

// Şablon güncelle
if(isset($_POST['sablon_guncelle'])) {
    $id = $_POST['id'] ?? 0;
    $baslik = $_POST['baslik'] ?? '';
    $puan = $_POST['puan'] ?? 0;
    $kategori = $_POST['kategori'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    if($id && $baslik && $kategori) {
        $stmt = $pdo->prepare("UPDATE puan_sablon SET baslik = ?, puan = ?, kategori = ?, aciklama = ?, aktif = ? WHERE id = ?");
        if($stmt->execute([$baslik, $puan, $kategori, $aciklama, $aktif, $id])) {
            $mesaj = "✅ Puan şablonu başarıyla güncellendi!";
            $mesaj_tip = "success";
        } else {
            $mesaj = "❌ Hata: Şablon güncellenemedi!";
            $mesaj_tip = "error";
        }
    }
}

// Şablon sil
if(isset($_POST['sablon_sil'])) {
    $id = $_POST['id'] ?? 0;

    if($id) {
        $stmt = $pdo->prepare("DELETE FROM puan_sablon WHERE id = ?");
        if($stmt->execute([$id])) {
            $mesaj = "✅ Puan şablonu başarıyla silindi!";
            $mesaj_tip = "success";
        } else {
            $mesaj = "❌ Hata: Şablon silinemedi!";
            $mesaj_tip = "error";
        }
    }
}

// Sıralama güncelle
if(isset($_POST['sira_guncelle'])) {
    $id = $_POST['id'] ?? 0;
    $yon = $_POST['yon'] ?? '';

    if($id && $yon) {
        $sablon = $pdo->prepare("SELECT * FROM puan_sablon WHERE id = ?");
        $sablon->execute([$id]);
        $current = $sablon->fetch();

        if($current) {
            $yeni_sira = $current['sira'] + ($yon == 'yukari' ? -1 : 1);
            $stmt = $pdo->prepare("UPDATE puan_sablon SET sira = ? WHERE id = ?");
            $stmt->execute([$yeni_sira, $id]);
            $mesaj = "✅ Sıralama güncellendi!";
            $mesaj_tip = "success";
        }
    }
}

// Tüm şablonları çek
$sablonlar = $pdo->query("SELECT * FROM puan_sablon ORDER BY kategori, sira, baslik")->fetchAll();

// İstatistikler
$namaz_sayisi = $pdo->query("SELECT COUNT(*) FROM puan_sablon WHERE kategori = 'Namaz' AND aktif = 1")->fetchColumn();
$ders_sayisi = $pdo->query("SELECT COUNT(*) FROM puan_sablon WHERE kategori = 'Ders' AND aktif = 1")->fetchColumn();
$pasif_sayisi = $pdo->query("SELECT COUNT(*) FROM puan_sablon WHERE aktif = 0")->fetchColumn();

$aktif_sayfa = 'puan-sablon';
$sayfa_basligi = 'Puan Şablonları Yönetimi';
require_once 'config/header.php';
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}
.stat-number {
    font-size: 32px;
    font-weight: bold;
    margin: 10px 0;
}
.stat-label {
    font-size: 14px;
    opacity: 0.9;
}
.sablon-card {
    background: white;
    border: 2px solid #ddd;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}
.sablon-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.sablon-card.pasif {
    opacity: 0.6;
    background: #f8f9fa;
}
.puan-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 16px;
}
.puan-positive {
    background: #d4edda;
    color: #28a745;
}
.puan-negative {
    background: #f8d7da;
    color: #dc3545;
}
.kategori-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 10px;
}
.kategori-namaz {
    background: #e3f2fd;
    color: #1976d2;
}
.kategori-ders {
    background: #fff3e0;
    color: #f57c00;
}
.btn-group {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}
.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
    border: 2px solid #667eea;
}
</style>

<div style="padding: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>⚙️ Puan Şablonları Yönetimi</h2>
        <button onclick="document.getElementById('yeniSablonForm').style.display='block'; window.scrollTo(0,0);"
                style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px;">
            ➕ Yeni Şablon Ekle
        </button>
    </div>

    <?php if($mesaj): ?>
    <div style="background: <?php echo $mesaj_tip == 'success' ? '#d4edda' : '#f8d7da'; ?>;
                border-left: 4px solid <?php echo $mesaj_tip == 'success' ? '#28a745' : '#dc3545'; ?>;
                padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo $mesaj; ?>
    </div>
    <?php endif; ?>

    <!-- İstatistikler -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">🕌 Namaz Şablonları</div>
            <div class="stat-number"><?php echo $namaz_sayisi; ?></div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="stat-label">📚 Ders Şablonları</div>
            <div class="stat-number"><?php echo $ders_sayisi; ?></div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-label">📊 Toplam</div>
            <div class="stat-number"><?php echo $namaz_sayisi + $ders_sayisi; ?></div>
        </div>
        <?php if($pasif_sayisi > 0): ?>
        <div class="stat-card" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
            <div class="stat-label">⏸️ Pasif</div>
            <div class="stat-number"><?php echo $pasif_sayisi; ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Yeni Şablon Formu -->
    <div id="yeniSablonForm" class="form-section" style="display: none;">
        <h3>➕ Yeni Puan Şablonu Ekle</h3>
        <form method="POST" style="display: grid; gap: 15px;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Şablon Başlığı:</label>
                    <input type="text" name="baslik" placeholder="Örn: Güzel namaz kıldı" required
                           style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Puan:</label>
                    <input type="number" name="puan" placeholder="+2 veya -3" required
                           style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;">
                </div>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Kategori:</label>
                <select name="kategori" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;">
                    <option value="">Seçin...</option>
                    <option value="Namaz">🕌 Namaz</option>
                    <option value="Ders">📚 Ders</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Açıklama (İsteğe Bağlı):</label>
                <textarea name="aciklama" rows="2" placeholder="Detaylı açıklama..."
                          style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;"></textarea>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="sablon_ekle" class="btn-primary"
                        style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    💾 Kaydet
                </button>
                <button type="button" onclick="document.getElementById('yeniSablonForm').style.display='none'"
                        style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                    ❌ İptal
                </button>
            </div>
        </form>
    </div>

    <!-- Şablonlar Listesi -->
    <h3>📋 Mevcut Şablonlar</h3>

    <?php
    $onceki_kategori = '';
    foreach($sablonlar as $sablon):
        if($onceki_kategori != $sablon['kategori']) {
            if($onceki_kategori != '') echo '</div>';
            echo '<h4 style="margin-top: 30px; color: #667eea;">' . ($sablon['kategori'] == 'Namaz' ? '🕌 Namaz Şablonları' : '📚 Ders Şablonları') . '</h4>';
            echo '<div>';
            $onceki_kategori = $sablon['kategori'];
        }
    ?>

    <div class="sablon-card <?php echo $sablon['aktif'] ? '' : 'pasif'; ?>" id="sablon-<?php echo $sablon['id']; ?>">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div style="flex: 1;">
                <div style="font-size: 20px; font-weight: 600; margin-bottom: 10px;">
                    <?php echo htmlspecialchars($sablon['baslik']); ?>
                    <span class="kategori-badge kategori-<?php echo strtolower($sablon['kategori']); ?>">
                        <?php echo $sablon['kategori']; ?>
                    </span>
                    <?php if(!$sablon['aktif']): ?>
                    <span style="background: #6c757d; color: white; padding: 3px 10px; border-radius: 5px; font-size: 12px; margin-left: 10px;">
                        PASIF
                    </span>
                    <?php endif; ?>
                </div>
                <div class="puan-badge <?php echo $sablon['puan'] > 0 ? 'puan-positive' : 'puan-negative'; ?>">
                    <?php echo $sablon['puan'] > 0 ? '+' : ''; ?><?php echo $sablon['puan']; ?> puan
                </div>
                <?php if($sablon['aciklama']): ?>
                <p style="color: #666; margin-top: 10px; font-size: 14px;">
                    <?php echo htmlspecialchars($sablon['aciklama']); ?>
                </p>
                <?php endif; ?>
            </div>
            <div style="display: flex; gap: 5px; flex-direction: column;">
                <button onclick="duzenle(<?php echo $sablon['id']; ?>)"
                        style="background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px;">
                    ✏️ Düzenle
                </button>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Bu şablonu silmek istediğinize emin misiniz?');">
                    <input type="hidden" name="id" value="<?php echo $sablon['id']; ?>">
                    <button type="submit" name="sablon_sil"
                            style="background: #dc3545; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; width: 100%;">
                        🗑️ Sil
                    </button>
                </form>
            </div>
        </div>

        <!-- Düzenleme Formu (Gizli) -->
        <div id="duzenle-<?php echo $sablon['id']; ?>" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #ddd;">
            <form method="POST" style="display: grid; gap: 15px;">
                <input type="hidden" name="id" value="<?php echo $sablon['id']; ?>">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Şablon Başlığı:</label>
                        <input type="text" name="baslik" value="<?php echo htmlspecialchars($sablon['baslik']); ?>" required
                               style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Puan:</label>
                        <input type="number" name="puan" value="<?php echo $sablon['puan']; ?>" required
                               style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;">
                    </div>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Kategori:</label>
                    <select name="kategori" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;">
                        <option value="Namaz" <?php echo $sablon['kategori'] == 'Namaz' ? 'selected' : ''; ?>>🕌 Namaz</option>
                        <option value="Ders" <?php echo $sablon['kategori'] == 'Ders' ? 'selected' : ''; ?>>📚 Ders</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Açıklama:</label>
                    <textarea name="aciklama" rows="2" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd; width: 100%;"><?php echo htmlspecialchars($sablon['aciklama']); ?></textarea>
                </div>
                <div>
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="aktif" <?php echo $sablon['aktif'] ? 'checked' : ''; ?>
                               style="width: 20px; height: 20px;">
                        <span style="font-weight: 600;">Aktif (Puan verme formunda göster)</span>
                    </label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="sablon_guncelle"
                            style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
                        💾 Güncelle
                    </button>
                    <button type="button" onclick="document.getElementById('duzenle-<?php echo $sablon['id']; ?>').style.display='none'"
                            style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                        ❌ İptal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php endforeach; ?>
    <?php if($onceki_kategori != '') echo '</div>'; ?>

    <?php if(count($sablonlar) == 0): ?>
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 5px;">
        <strong>⚠️ Henüz hiç puan şablonu eklenmemiş!</strong>
        <br>Yukarıdaki "Yeni Şablon Ekle" butonuna tıklayarak ilk şablonunuzu ekleyin.
    </div>
    <?php endif; ?>
</div>

<script>
function duzenle(id) {
    // Tüm düzenleme formlarını kapat
    document.querySelectorAll('[id^="duzenle-"]').forEach(el => el.style.display = 'none');
    // İlgili formu aç
    document.getElementById('duzenle-' + id).style.display = 'block';
}
</script>

<?php require_once 'config/footer.php'; ?>
