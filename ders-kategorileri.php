<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$mesaj = '';

// Kategori ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kategori_ekle'])) {
    $kategori_adi = $_POST['kategori_adi'];
    $aciklama = $_POST['aciklama'];
    $sira = $_POST['sira'];

    $stmt = $pdo->prepare("INSERT INTO ders_kategorileri (kategori_adi, aciklama, sira) VALUES (?, ?, ?)");
    $stmt->execute([$kategori_adi, $aciklama, $sira]);
    $mesaj = "Kategori başarıyla eklendi!";
}

// Kategori güncelleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kategori_guncelle'])) {
    $id = $_POST['id'];
    $kategori_adi = $_POST['kategori_adi'];
    $aciklama = $_POST['aciklama'];
    $sira = $_POST['sira'];
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE ders_kategorileri SET kategori_adi = ?, aciklama = ?, sira = ?, aktif = ? WHERE id = ?");
    $stmt->execute([$kategori_adi, $aciklama, $sira, $aktif, $id]);
    $mesaj = "Kategori güncellendi!";
}

// Kategori silme
if(isset($_GET['sil'])) {
    $id = $_GET['sil'];
    $stmt = $pdo->prepare("DELETE FROM ders_kategorileri WHERE id = ?");
    $stmt->execute([$id]);
    $mesaj = "Kategori silindi!";
    header("Location: ders-kategorileri.php");
    exit;
}

// Kategorileri listele
$kategoriler = $pdo->query("SELECT * FROM ders_kategorileri ORDER BY sira, kategori_adi")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ders Kategorileri - Cami Namaz Takip</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Cami Namaz Takip Programı</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="ogrenciler.php">Öğrenciler</a>
                <a href="ders-kategorileri.php" class="active">Ders Kategorileri</a>
                <a href="dersler.php">Dersler</a>
                <a href="logout.php" style="margin-left: auto; background: rgba(255,255,255,0.3);">👤 <?php echo getLoggedInUser(); ?> - Çıkış</a>
            </nav>
        </header>

        <div style="padding: 30px;">
            <h2>📚 Ders Kategorileri Yönetimi</h2>

            <?php if($mesaj): ?>
            <div class="alert success"><?php echo $mesaj; ?></div>
            <?php endif; ?>

            <!-- Yeni Kategori Ekle -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                <h3>➕ Yeni Kategori Ekle</h3>
                <form method="POST" style="display: grid; gap: 15px; max-width: 600px;">
                    <input type="text" name="kategori_adi" placeholder="Kategori Adı (örn: Kuranı Kerim)" required style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <textarea name="aciklama" placeholder="Açıklama" rows="3" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;"></textarea>
                    <input type="number" name="sira" placeholder="Sıra No" value="0" min="0" style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;">
                    <button type="submit" name="kategori_ekle" class="btn-primary" style="width: auto;">💾 Kategori Ekle</button>
                </form>
            </div>

            <!-- Kategori Listesi -->
            <h3>📋 Mevcut Kategoriler</h3>
            <?php if(count($kategoriler) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Sıra</th>
                        <th>Kategori Adı</th>
                        <th>Açıklama</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($kategoriler as $kat): ?>
                    <tr>
                        <td><?php echo $kat['sira']; ?></td>
                        <td><strong><?php echo htmlspecialchars($kat['kategori_adi']); ?></strong></td>
                        <td><?php echo htmlspecialchars($kat['aciklama']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $kat['aktif'] ? 'status-aktif' : 'status-pasif'; ?>">
                                <?php echo $kat['aktif'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="duzenle(<?php echo $kat['id']; ?>)" class="btn-sm btn-edit">✏️ Düzenle</button>
                            <a href="dersler.php?kategori=<?php echo $kat['id']; ?>" class="btn-sm" style="background: #17a2b8; color: white;">📖 Dersleri Gör</a>
                            <button onclick="sil(<?php echo $kat['id']; ?>, '<?php echo htmlspecialchars($kat['kategori_adi']); ?>')" class="btn-sm btn-delete">🗑️ Sil</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="alert info">Henüz kategori eklenmemiş.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Düzenleme Modal -->
    <div id="duzenle-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="duzenle-form"></div>
        </div>
    </div>

    <script>
        function duzenle(id) {
            fetch('api/kategori-getir.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('duzenle-form').innerHTML = `
                        <h3>✏️ Kategori Düzenle</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="id" value="${data.id}">
                            <div class="form-group">
                                <label>Kategori Adı:</label>
                                <input type="text" name="kategori_adi" value="${data.kategori_adi}" required>
                            </div>
                            <div class="form-group">
                                <label>Açıklama:</label>
                                <textarea name="aciklama" rows="3">${data.aciklama || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Sıra:</label>
                                <input type="number" name="sira" value="${data.sira}" min="0">
                            </div>
                            <div class="form-group">
                                <label style="display: flex; align-items: center;">
                                    <input type="checkbox" name="aktif" ${data.aktif ? 'checked' : ''} style="width: auto; margin-right: 10px;">
                                    Aktif
                                </label>
                            </div>
                            <button type="submit" name="kategori_guncelle" class="btn-primary">💾 Güncelle</button>
                        </form>
                    `;
                    document.getElementById('duzenle-modal').style.display = 'block';
                });
        }

        function sil(id, ad) {
            if(confirm(`"${ad}" kategorisini silmek istediğinize emin misiniz?\n\nBu kategoriye ait tüm dersler de silinecektir!`)) {
                window.location.href = '?sil=' + id;
            }
        }

        document.querySelector('.close').onclick = function() {
            document.getElementById('duzenle-modal').style.display = 'none';
        }
    </script>
</body>
</html>