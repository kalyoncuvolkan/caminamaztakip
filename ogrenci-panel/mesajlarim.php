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
$ogr = $pdo->prepare("SELECT ad_soyad FROM ogrenciler WHERE id = ?");
$ogr->execute([$ogrenci_id]);
$ogrenci = $ogr->fetch();

// Filtre
$filtre = $_GET['filtre'] ?? 'tumu';

// Mesajları çek
$sql = "SELECT * FROM ogrenci_mesajlari WHERE ogrenci_id = ?";
$params = [$ogrenci_id];

if($filtre === 'okunmamis') {
    $sql .= " AND okundu = 0";
} elseif($filtre === 'okunmus') {
    $sql .= " AND okundu = 1";
}

$sql .= " ORDER BY gonderim_zamani DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mesajlar = $stmt->fetchAll();

// İstatistikler
$stats = $pdo->prepare("
    SELECT
        COUNT(*) as toplam,
        SUM(CASE WHEN okundu = 0 THEN 1 ELSE 0 END) as okunmamis,
        SUM(CASE WHEN okundu = 1 THEN 1 ELSE 0 END) as okunmus
    FROM ogrenci_mesajlari
    WHERE ogrenci_id = ?
");
$stats->execute([$ogrenci_id]);
$istatistikler = $stats->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesajlarım - Öğrenci Paneli</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .message-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 5px solid #667eea;
            position: relative;
        }

        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .message-card.unread {
            background: linear-gradient(135deg, #fff9e6 0%, #fff 100%);
            border-left-color: #ffc107;
        }

        .message-card.acil {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #ffe6e6 0%, #fff 100%);
        }

        .message-card.onemli {
            border-left-color: #ffc107;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .message-priority {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .priority-acil {
            background: #dc3545;
            color: white;
        }

        .priority-onemli {
            background: #ffc107;
            color: #000;
        }

        .priority-normal {
            background: #17a2b8;
            color: white;
        }

        .message-date {
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .message-body {
            color: #333;
            font-size: 16px;
            line-height: 1.6;
            margin: 15px 0;
            padding: 15px;
            background: rgba(0,0,0,0.02);
            border-radius: 8px;
        }

        .message-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .message-sender {
            color: #666;
            font-size: 14px;
        }

        .unread-badge {
            background: #ffc107;
            color: #000;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            position: absolute;
            top: 15px;
            right: 15px;
        }

        .filter-bar {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            font-weight: 500;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 2em;
        }

        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }

        .btn-mark-read {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-mark-read:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-mark-read:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .message-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .unread-badge {
                position: static;
                margin-top: 10px;
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
                <a href="mesajlarim.php" class="active">💬 Mesajlarım</a>
                <a href="raporlarim.php">📊 Raporlarım</a>
                <a href="sertifikalarim.php">🏆 Sertifikalarım</a>
                <a href="logout.php" style="margin-left: auto">Çıkış</a>
            </nav>
        </header>

        <div style="padding: 30px;">
            <h2>💬 Mesajlarım</h2>
            <p style="color: #666; margin-bottom: 20px;">Merhaba, <?php echo htmlspecialchars($ogrenci['ad_soyad']); ?>! Öğretmeninizden gelen mesajlarınız burada.</p>

            <div class="stats-cards">
                <div class="stat-card">
                    <h3><?php echo $istatistikler['toplam']; ?></h3>
                    <p>📬 Toplam Mesaj</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                    <h3><?php echo $istatistikler['okunmamis']; ?></h3>
                    <p>📩 Okunmamış</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h3><?php echo $istatistikler['okunmus']; ?></h3>
                    <p>✅ Okunmuş</p>
                </div>
            </div>

            <div class="filter-bar">
                <a href="?filtre=tumu" class="filter-btn <?php echo $filtre === 'tumu' ? 'active' : ''; ?>">
                    📋 Tümü
                </a>
                <a href="?filtre=okunmamis" class="filter-btn <?php echo $filtre === 'okunmamis' ? 'active' : ''; ?>">
                    📩 Okunmamış (<?php echo $istatistikler['okunmamis']; ?>)
                </a>
                <a href="?filtre=okunmus" class="filter-btn <?php echo $filtre === 'okunmus' ? 'active' : ''; ?>">
                    ✅ Okunmuş
                </a>
            </div>

            <?php if(count($mesajlar) > 0): ?>
                <?php foreach($mesajlar as $mesaj): ?>
                <div class="message-card <?php echo !$mesaj['okundu'] ? 'unread' : ''; ?> <?php echo strtolower($mesaj['oncelik']); ?>" id="mesaj-<?php echo $mesaj['id']; ?>">
                    <?php if(!$mesaj['okundu']): ?>
                    <span class="unread-badge">🆕 Yeni</span>
                    <?php endif; ?>

                    <div class="message-header">
                        <span class="message-priority priority-<?php echo strtolower($mesaj['oncelik']); ?>">
                            <?php
                            if($mesaj['oncelik'] == 'Acil') {
                                echo '🚨 Acil';
                            } elseif($mesaj['oncelik'] == 'Önemli') {
                                echo '⚠️ Önemli';
                            } else {
                                echo '📝 Normal';
                            }
                            ?>
                        </span>
                        <span class="message-date">
                            🕐 <?php echo date('d.m.Y H:i', strtotime($mesaj['gonderim_zamani'])); ?>
                        </span>
                    </div>

                    <div class="message-body">
                        <?php echo nl2br(htmlspecialchars($mesaj['mesaj'])); ?>
                    </div>

                    <div class="message-footer">
                        <span class="message-sender">
                            👤 Gönderen: <strong><?php echo htmlspecialchars($mesaj['gonderen_kullanici']); ?></strong>
                        </span>
                        <?php if(!$mesaj['okundu']): ?>
                        <button class="btn-mark-read" onclick="mesajiOkunduIsaretle(<?php echo $mesaj['id']; ?>)">
                            ✓ Okundu İşaretle
                        </button>
                        <?php else: ?>
                        <span style="color: #28a745; font-size: 14px; font-weight: 600;">
                            ✅ Okundu
                            <?php if($mesaj['okunma_zamani']): ?>
                            <small style="color: #666; font-weight: normal;">
                                (<?php echo date('d.m.Y H:i', strtotime($mesaj['okunma_zamani'])); ?>)
                            </small>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>Mesaj bulunamadı</h3>
                <p>
                    <?php
                    if($filtre === 'okunmamis') {
                        echo 'Okunmamış mesajınız yok. Harika! 🎉';
                    } elseif($filtre === 'okunmus') {
                        echo 'Henüz okunmuş mesajınız yok.';
                    } else {
                        echo 'Henüz size gönderilen bir mesaj yok.';
                    }
                    ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function mesajiOkunduIsaretle(mesajId) {
            if(!confirm('Bu mesajı okundu olarak işaretlemek istediğinize emin misiniz?')) {
                return;
            }

            fetch('../api/mesaj-okundu.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'mesaj_id=' + mesajId
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Sayfayı yenile
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Bir hata oluştu: ' + error);
            });
        }
    </script>
</body>
</html>
