<?php
session_start();
require_once '../config/db.php';

// Öğrenci login kontrolü
if(!isset($_SESSION['ogrenci_id'])) {
    header('Location: login.php');
    exit;
}

$ogrenci_id = $_SESSION['ogrenci_id'];
$mesaj = '';
$hata = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eski_sifre = $_POST['eski_sifre'];
    $yeni_sifre = $_POST['yeni_sifre'];
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'];

    // Mevcut şifreyi kontrol et
    $stmt = $pdo->prepare("SELECT parola_hash FROM ogrenci_kullanicilar WHERE ogrenci_id = ?");
    $stmt->execute([$ogrenci_id]);
    $kullanici = $stmt->fetch();

    if(!password_verify($eski_sifre, $kullanici['parola_hash'])) {
        $hata = 'Mevcut şifreniz hatalı!';
    } elseif($yeni_sifre !== $yeni_sifre_tekrar) {
        $hata = 'Yeni şifreler eşleşmiyor!';
    } elseif(strlen($yeni_sifre) < 6) {
        $hata = 'Yeni şifre en az 6 karakter olmalıdır!';
    } else {
        // Şifreyi güncelle
        $sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE ogrenci_kullanicilar SET parola_hash = ? WHERE ogrenci_id = ?");
        $update->execute([$sifre_hash, $ogrenci_id]);

        $mesaj = 'Şifreniz başarıyla değiştirildi!';
    }
}

// Öğrenci bilgileri
$ogr = $pdo->prepare("SELECT ad_soyad FROM ogrenciler WHERE id = ?");
$ogr->execute([$ogrenci_id]);
$ogrenci = $ogr->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Değiştir - Öğrenci Paneli</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .form-container {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-primary {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info-box {
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Öğrenci Paneli</h1>
            <nav>
                <a href="index.php">Panel</a>
                <a href="raporlarim.php">Raporlarım</a>
                <a href="sertifikalarim.php">Sertifikalarım</a>
                <a href="sifre-degistir.php" class="active">Şifre Değiştir</a>
                <a href="logout.php" style="margin-left: auto">Çıkış</a>
            </nav>
        </header>

        <div class="form-container">
            <h2>🔒 Şifre Değiştir</h2>
            <p style="color: #666; margin-bottom: 20px;">Merhaba, <?php echo htmlspecialchars($ogrenci['ad_soyad']); ?>!</p>

            <?php if($mesaj): ?>
            <div class="alert success">
                ✅ <?php echo $mesaj; ?>
            </div>
            <?php endif; ?>

            <?php if($hata): ?>
            <div class="alert error">
                ❌ <?php echo $hata; ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="eski_sifre">🔑 Mevcut Şifre</label>
                    <input type="password" id="eski_sifre" name="eski_sifre" required>
                </div>

                <div class="form-group">
                    <label for="yeni_sifre">🔐 Yeni Şifre (en az 6 karakter)</label>
                    <input type="password" id="yeni_sifre" name="yeni_sifre" minlength="6" required>
                </div>

                <div class="form-group">
                    <label for="yeni_sifre_tekrar">✓ Yeni Şifre Tekrar</label>
                    <input type="password" id="yeni_sifre_tekrar" name="yeni_sifre_tekrar" minlength="6" required>
                </div>

                <button type="submit" class="btn-primary">
                    💾 Şifreyi Değiştir
                </button>
            </form>

            <div class="info-box">
                <strong>ℹ️ Güvenlik İpuçları:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Şifreniz en az 6 karakter olmalıdır</li>
                    <li>Güçlü bir şifre için harf ve rakam kullanın</li>
                    <li>Şifrenizi kimseyle paylaşmayın</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
