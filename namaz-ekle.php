<?php
require_once 'config/db.php';

$ogrenciler = $pdo->query("SELECT id, ad_soyad FROM ogrenciler ORDER BY ad_soyad")->fetchAll();
$bugun = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['toplu_kayit'])) {
        $namaz_vakti = $_POST['namaz_vakti'];
        $tarih = $_POST['tarih'] ?: $bugun;
        $basarili = 0;
        
        foreach($_POST['ogrenciler'] as $ogrenci_data) {
            $ogrenci_id = $ogrenci_data['id'];
            $kiminle_geldi = $ogrenci_data['kiminle'];

            if($kiminle_geldi) {
                $stmt = $pdo->prepare("INSERT INTO namaz_kayitlari (ogrenci_id, namaz_vakti, kiminle_geldi, tarih) VALUES (?, ?, ?, ?)");
                if($stmt->execute([$ogrenci_id, $namaz_vakti, $kiminle_geldi, $tarih])) {
                    $basarili++;

                    // Bonus puan ekle (aileleriyle gelenlere)
                    $bonus_puan = 0;
                    if($kiminle_geldi == 'Babası' || $kiminle_geldi == 'Annesi') {
                        $bonus_puan = 1;
                    } elseif($kiminle_geldi == 'Anne-Babası') {
                        $bonus_puan = 2;
                    }

                    if($bonus_puan > 0) {
                        $bonusStmt = $pdo->prepare("
                            INSERT INTO ilave_puanlar (ogrenci_id, puan, aciklama, kategori, tarih)
                            VALUES (?, ?, ?, 'Namaz', ?)
                        ");
                        $aciklama = "$kiminle_geldi ile $namaz_vakti namazına geldi (bonus)";
                        $bonusStmt->execute([$ogrenci_id, $bonus_puan, $aciklama, $tarih]);
                    }
                }
            }
        }
        
        $mesaj = "$basarili öğrencinin $namaz_vakti namazı kaydedildi!";
    }
}

$bugunKayitlar = $pdo->prepare("
    SELECT o.ad_soyad, n.namaz_vakti, n.kiminle_geldi, n.saat 
    FROM namaz_kayitlari n 
    JOIN ogrenciler o ON n.ogrenci_id = o.id 
    WHERE n.tarih = ? 
    ORDER BY n.saat DESC
");
$bugunKayitlar->execute([$bugun]);
$kayitlar = $bugunKayitlar->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Namaz Ekle - Cami Namaz Takip</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Cami Namaz Takip Programı</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="ogrenci-ekle.php">Öğrenci Ekle</a>
                <a href="namaz-ekle.php" class="active">Namaz Ekle</a>
                <a href="genel-rapor.php">Genel Rapor</a>
            </nav>
        </header>

        <div class="namaz-ekle-container">
            <h2>🕌 Namaz Kaydı Ekle</h2>
            
            <?php if(isset($mesaj)): ?>
            <div class="alert success"><?php echo $mesaj; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="toplu_kayit" value="1">
                
                <div class="form-header">
                    <div class="form-group inline">
                        <label>Namaz Vakti:</label>
                        <select name="namaz_vakti" required>
                            <option value="">Vakit Seçin</option>
                            <option value="Sabah">Sabah</option>
                            <option value="Öğlen">Öğlen</option>
                            <option value="İkindi">İkindi</option>
                            <option value="Akşam">Akşam</option>
                            <option value="Yatsı">Yatsı</option>
                        </select>
                    </div>
                    
                    <div class="form-group inline">
                        <label>Tarih:</label>
                        <input type="date" name="tarih" value="<?php echo $bugun; ?>">
                    </div>
                </div>

                <div class="ogrenci-namaz-listesi">
                    <table>
                        <thead>
                            <tr>
                                <th width="30">#</th>
                                <th>Öğrenci Adı</th>
                                <th>Kiminle Geldi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ogrenciler as $index => $ogrenci): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $ogrenci['ad_soyad']; ?></td>
                                <td>
                                    <input type="hidden" name="ogrenciler[<?php echo $index; ?>][id]" value="<?php echo $ogrenci['id']; ?>">
                                    <select name="ogrenciler[<?php echo $index; ?>][kiminle]" class="kiminle-select">
                                        <option value="">Gelmedi</option>
                                        <option value="Kendisi">Kendisi</option>
                                        <option value="Babası">Babası</option>
                                        <option value="Annesi">Annesi</option>
                                        <option value="Anne-Babası">Anne-Babası</option>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <button type="submit" class="btn-primary">Kayıtları Ekle</button>
            </form>
            
            <div class="bugun-kayitlar">
                <h3>📋 Bugünün Kayıtları</h3>
                <?php if(count($kayitlar) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Saat</th>
                            <th>Öğrenci</th>
                            <th>Vakit</th>
                            <th>Kiminle Geldi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($kayitlar as $kayit): ?>
                        <tr>
                            <td><?php echo date('H:i', strtotime($kayit['saat'])); ?></td>
                            <td><?php echo $kayit['ad_soyad']; ?></td>
                            <td><?php echo $kayit['namaz_vakti']; ?></td>
                            <td><?php echo $kayit['kiminle_geldi']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Bugün henüz kayıt yok.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.kiminle-select').forEach(function(select) {
            select.addEventListener('change', function() {
                if(this.value) {
                    this.closest('tr').style.backgroundColor = '#e8f5e9';
                } else {
                    this.closest('tr').style.backgroundColor = '';
                }
            });
        });
    </script>
</body>
</html>