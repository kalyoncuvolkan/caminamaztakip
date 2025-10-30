<?php
session_start();
require_once '../config/db.php';

$hata = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];

    $stmt = $pdo->prepare("SELECT * FROM ogrenci_kullanicilar WHERE kullanici_adi = ?");
    $stmt->execute([$kullanici_adi]);
    $kullanici = $stmt->fetch();

    if($kullanici && password_verify($sifre, $kullanici['parola_hash'])) {
        $_SESSION['ogrenci_id'] = $kullanici['ogrenci_id'];
        $_SESSION['ogrenci_kullanici_adi'] = $kullanici['kullanici_adi'];
        header('Location: index.php');
        exit;
    } else {
        $hata = 'Kullanıcı adı veya şifre hatalı!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="googlebot" content="noindex, nofollow">
    <meta name="bingbot" content="noindex, nofollow">
    <title>Öğrenci Girişi - Cami Namaz Takip</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
        }

        .logo {
            text-align: center;
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .info-box {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🎓</div>
        <h1>Öğrenci Girişi</h1>
        <p class="subtitle">Cami Namaz Takip Sistemi</p>

        <?php if($hata): ?>
        <div class="error">
            <strong>❌ Hata!</strong><br>
            <?php echo $hata; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="kullanici_adi">👤 Kullanıcı Adı</label>
                <input type="text" id="kullanici_adi" name="kullanici_adi" required autofocus>
            </div>

            <div class="form-group">
                <label for="sifre">🔒 Şifre</label>
                <input type="password" id="sifre" name="sifre" required>
            </div>

            <button type="submit" class="btn-login">
                🚪 Giriş Yap
            </button>
        </form>

        <div class="info-box">
            <strong>ℹ️ Bilgi:</strong><br>
            Kullanıcı adı ve şifrenizi öğretmeninizden alabilirsiniz.
        </div>

        <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 2px solid rgba(255,255,255,0.2);">
            <p style="color: rgba(255,255,255,0.8); margin-bottom: 12px; font-size: 13px;">Yönetici misiniz?</p>
            <a href="../login.php" style="display: inline-block; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); color: white; padding: 10px 25px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: all 0.3s; border: 2px solid rgba(255,255,255,0.3);" onmouseover="this.style.background='rgba(255,255,255,0.25)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='rgba(255,255,255,0.15)'; this.style.transform='translateY(0)';">
                🔑 Yönetici Girişi
            </a>
        </div>
    </div>
</body>
</html>
