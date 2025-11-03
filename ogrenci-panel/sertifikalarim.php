<?php
session_start();
require_once '../config/db.php';

// Öğrenci login kontrolü
if(!isset($_SESSION['ogrenci_id'])) {
    header('Location: login.php');
    exit;
}

$ogrenci_id = $_SESSION['ogrenci_id'];

// Öğrenci bilgileri
$ogr = $pdo->prepare("SELECT * FROM ogrenciler WHERE id = ?");
$ogr->execute([$ogrenci_id]);
$ogrenci = $ogr->fetch();

// Sertifikaları çek
$sertifikalar = $pdo->prepare("SELECT * FROM sertifikalar WHERE ogrenci_id = ? ORDER BY verilis_tarihi DESC");
$sertifikalar->execute([$ogrenci_id]);
$sertifika_listesi = $sertifikalar->fetchAll();

// İstatistikler
$stats = [
    'toplam' => count($sertifika_listesi),
    'bu_yil' => 0,
    'bu_ay' => 0
];

foreach($sertifika_listesi as $sert) {
    if(date('Y', strtotime($sert['verilis_tarihi'])) == date('Y')) {
        $stats['bu_yil']++;
        if(date('m', strtotime($sert['verilis_tarihi'])) == date('m')) {
            $stats['bu_ay']++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikalarım - <?php echo $ogrenci['ad_soyad']; ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 3em;
            margin: 0 0 10px 0;
        }

        .stat-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1em;
        }

        .sertifika-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 5px solid #ffc107;
            position: relative;
            overflow: hidden;
        }

        .sertifika-card::before {
            content: '🏆';
            position: absolute;
            right: -20px;
            top: -20px;
            font-size: 120px;
            opacity: 0.05;
        }

        .sertifika-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .sertifika-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 20px;
        }

        .sertifika-baslik {
            flex: 1;
        }

        .sertifika-baslik h3 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 1.5em;
        }

        .sertifika-tarih {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 8px;
            text-align: center;
        }

        .sertifika-tarih-label {
            font-size: 0.8em;
            color: #666;
            display: block;
        }

        .sertifika-tarih-value {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1em;
        }

        .sertifika-aciklama {
            color: #666;
            line-height: 1.6;
            margin: 15px 0;
            padding: 15px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 8px;
        }

        .sertifika-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .sertifika-veren {
            color: #999;
            font-size: 0.9em;
        }

        .btn-print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 100px;
            margin-bottom: 30px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .sertifika-header {
                flex-direction: column;
            }

            .sertifika-footer {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Öğrenci Paneli</h1>
            <nav>
                <a href="index.php">Panel</a>
                <a href="mesajlarim.php">💬 Mesajlarım</a>
                <a href="raporlarim.php">📊 Raporlarım</a>
                <a href="sertifikalarim.php" class="active">🏆 Sertifikalarım</a>
                <a href="logout.php" style="margin-left: auto">Çıkış</a>
            </nav>
        </header>

        <div style="padding: 30px;">
            <h2>🏆 Sertifikalarım</h2>
            <p style="color: #666; margin-bottom: 20px;">Merhaba, <?php echo htmlspecialchars($ogrenci['ad_soyad']); ?>! Kazandığınız tüm sertifikalar burada.</p>

            <!-- İstatistik Kartları -->
            <div class="stats-cards">
                <div class="stat-card">
                    <h3><?php echo $stats['toplam']; ?></h3>
                    <p>🏆 Toplam Sertifika</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h3><?php echo $stats['bu_yil']; ?></h3>
                    <p>📅 Bu Yıl</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h3><?php echo $stats['bu_ay']; ?></h3>
                    <p>📆 Bu Ay</p>
                </div>
            </div>

            <!-- Sertifika Listesi -->
            <?php if(count($sertifika_listesi) > 0): ?>
                <?php foreach($sertifika_listesi as $sertifika): ?>
                <div class="sertifika-card">
                    <div class="sertifika-header">
                        <div class="sertifika-baslik">
                            <h3>🏆 <?php echo htmlspecialchars($sertifika['sertifika_turu']); ?></h3>
                            <?php if($sertifika['ders_adi']): ?>
                            <p style="color: #666; margin: 5px 0;">
                                <strong>Ders:</strong> <?php echo htmlspecialchars($sertifika['ders_adi']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="sertifika-tarih">
                            <span class="sertifika-tarih-label">Veriliş Tarihi</span>
                            <div class="sertifika-tarih-value">
                                <?php echo date('d.m.Y', strtotime($sertifika['verilis_tarihi'])); ?>
                            </div>
                        </div>
                    </div>

                    <?php if($sertifika['aciklama']): ?>
                    <div class="sertifika-aciklama">
                        <?php echo nl2br(htmlspecialchars($sertifika['aciklama'])); ?>
                    </div>
                    <?php endif; ?>

                    <div class="sertifika-footer">
                        <div class="sertifika-veren">
                            👤 Veren: <strong><?php echo htmlspecialchars($sertifika['veren_kullanici']); ?></strong>
                        </div>
                        <a href="../sertifika-yazdir.php?id=<?php echo $sertifika['id']; ?>" target="_blank" class="btn-print">
                            🖨️ Sertifikayı Yazdır
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🏅</div>
                <h3>Henüz sertifikanız yok</h3>
                <p>Derslerinizi tamamladıkça ve başarılarınız arttıkça sertifikalar kazanacaksınız!</p>
                <p style="margin-top: 30px; color: #667eea; font-weight: 600;">
                    💪 Çalışmaya devam edin, başarılar sizinle!
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
