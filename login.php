<?php
session_start();
require_once 'config/db.php';

$hata = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = $_POST['kullanici_adi'] ?? '';
    $parola = $_POST['parola'] ?? '';
    
    if(empty($kullanici_adi) || empty($parola)) {
        $hata = 'Kullanıcı adı ve parola gerekli!';
    } else {
        $stmt = $pdo->prepare("SELECT id, kullanici_adi, parola_hash FROM kullanicilar WHERE kullanici_adi = ? AND aktif = 1");
        $stmt->execute([$kullanici_adi]);
        $kullanici = $stmt->fetch();
        
        if($kullanici && password_verify($parola, $kullanici['parola_hash'])) {
            $_SESSION['user_id'] = $kullanici['id'];
            $_SESSION['kullanici_adi'] = $kullanici['kullanici_adi'];
            $_SESSION['login_time'] = time();
            
            // Son giriş zamanını güncelle
            $update_stmt = $pdo->prepare("UPDATE kullanicilar SET son_giris = CURRENT_TIMESTAMP WHERE id = ?");
            $update_stmt->execute([$kullanici['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $hata = 'Geçersiz kullanıcı adı veya parola!';
        }
    }
}

// Zaten giriş yapmışsa ana sayfaya yönlendir
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
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
    <title>Giriş Yap - Cami Namaz Takip</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .login-header {
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .logo {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }
        
        .hata {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .parola-bilgi {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 14px;
            border: 1px solid #bee5eb;
        }
        
        .parola-toggle {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 14px;
        }
        
        .security-info {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 14px;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🕌</div>
            <h1>Cami Namaz Takip</h1>
            <p>Yönetici Girişi</p>
        </div>
        
        <?php if($hata): ?>
        <div class="hata">
            <strong>⚠️ Hata:</strong> <?php echo $hata; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="kullanici_adi">👤 Kullanıcı Adı:</label>
                <input type="text" id="kullanici_adi" name="kullanici_adi" 
                       value="<?php echo htmlspecialchars($_POST['kullanici_adi'] ?? ''); ?>" 
                       required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="parola">🔒 Parola:</label>
                <div class="parola-toggle">
                    <input type="password" id="parola" name="parola" required autocomplete="current-password">
                    <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                🚪 Giriş Yap
            </button>
        </form>
        
        <div class="security-info">
            <strong>🔐 Güvenlik:</strong> Bu sistem sadece yetkili kullanıcılar için tasarlanmıştır. 
            Tüm giriş denemeleri kaydedilir.
        </div>
    </div>

    <script>
        function togglePassword() {
            const parolaInput = document.getElementById('parola');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (parolaInput.type === 'password') {
                parolaInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                parolaInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
        
        // Sayfa yüklendiğinde kullanıcı adı alanına odaklan
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('kullanici_adi').focus();
        });
        
        // Enter tuşu ile form gönder
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });
    </script>
</body>
</html>