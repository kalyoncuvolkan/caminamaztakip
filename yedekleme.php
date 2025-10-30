<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$mesaj = '';

// Manuel yedekleme
if(isset($_GET['yedek'])) {
    $tarih = date('Y-m-d_H-i-s');
    $dosya = "backup/cami_yedek_$tarih.sql";

    if(!is_dir('backup')) mkdir('backup', 0755, true);

    $cmd = "mysqldump -u root cami_namaz_takip > $dosya";
    exec($cmd, $output, $return);

    if($return === 0) {
        $mesaj = "Yedekleme başarılı: $dosya";
    } else {
        $mesaj = "Yedekleme hatası!";
    }
}

// Mevcut yedekler
$yedekler = glob('backup/*.sql');
rsort($yedekler);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yedekleme - Cami Namaz Takip</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Cami Namaz Takip Programı</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="yedekleme.php" class="active">Yedekleme</a>
                <a href="logout.php" style="margin-left: auto">👤 <?php echo getLoggedInUser(); ?> - Çıkış</a>
            </nav>
        </header>

        <div style="padding: 30px;">
            <h2>💾 Sistem Yedekleme</h2>
            <?php if($mesaj): ?><div class="alert success"><?php echo $mesaj; ?></div><?php endif; ?>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h3>📥 Yeni Yedek Al</h3>
                <p>Veritabanının tam yedeğini alır (tüm öğrenciler, namazlar, dersler, sertifikalar)</p>
                <a href="?yedek=1" class="btn-primary" style="text-decoration: none; display: inline-block;">💾 Yedekle</a>
            </div>

            <h3>📋 Mevcut Yedekler</h3>
            <?php if(count($yedekler) > 0): ?>
            <table>
                <thead><tr><th>Dosya Adı</th><th>Boyut</th><th>Tarih</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php foreach($yedekler as $y): ?>
                    <tr>
                        <td><?php echo basename($y); ?></td>
                        <td><?php echo round(filesize($y)/1024, 2); ?> KB</td>
                        <td><?php echo date('d.m.Y H:i', filemtime($y)); ?></td>
                        <td><a href="<?php echo $y; ?>" download class="btn-sm" style="background: #28a745; color: white;">⬇️ İndir</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="alert info">Henüz yedek alınmamış.</div>
            <?php endif; ?>

            <div style="background: #fff3cd; padding: 20px; border-radius: 10px; margin-top: 30px; border: 1px solid #ffeeba;">
                <h4 style="margin-top: 0; color: #856404;">⚠️ Önemli Notlar</h4>
                <ul style="color: #856404;">
                    <li>Yedekler <code>backup/</code> klasörüne kaydedilir</li>
                    <li>Düzenli olarak yedek alın (haftada 1 önerilir)</li>
                    <li>Yedekleri güvenli bir yere kopyalayın</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>