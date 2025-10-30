<?php
session_start();

// Kurulum tamamlandıysa engelleyelim
if(file_exists('config/db.php') && !isset($_GET['force'])) {
    $db_content = file_get_contents('config/db.php');
    if(strpos($db_content, '$host = \'localhost\'') !== false && strpos($db_content, '$dbname = \'cami_namaz_takip\'') === false) {
        die('⚠️ Sistem zaten kurulmuş! Tekrar kurmak için install.php?force=1 adresini ziyaret edin.');
    }
}

$step = $_GET['step'] ?? 1;
$hata = '';
$basari = '';

// Adım 1: Veritabanı bilgilerini test et
if($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 1) {
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = $_POST['db_pass'];

    try {
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Veritabanını oluştur
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci");
        $pdo->exec("USE `$db_name`");

        // Session'a kaydet
        $_SESSION['install'] = [
            'db_host' => $db_host,
            'db_name' => $db_name,
            'db_user' => $db_user,
            'db_pass' => $db_pass
        ];

        header('Location: install.php?step=2');
        exit;
    } catch(PDOException $e) {
        $hata = 'Veritabanı bağlantı hatası: ' . $e->getMessage();
    }
}

// Adım 2: Tabloları oluştur
if($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 2) {
    $install = $_SESSION['install'];

    try {
        $pdo = new PDO("mysql:host={$install['db_host']};dbname={$install['db_name']};charset=utf8mb4",
                       $install['db_user'], $install['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Tabloları oluştur
        $sql = file_get_contents(__DIR__ . '/install_schema.sql');
        $pdo->exec($sql);

        header('Location: install.php?step=3');
        exit;
    } catch(PDOException $e) {
        $hata = 'Tablo oluşturma hatası: ' . $e->getMessage();
    }
}

// Adım 3: Yönetici hesabı oluştur
if($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 3) {
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    if($sifre !== $sifre_tekrar) {
        $hata = 'Şifreler eşleşmiyor!';
    } elseif(strlen($sifre) < 6) {
        $hata = 'Şifre en az 6 karakter olmalıdır!';
    } else {
        $install = $_SESSION['install'];

        try {
            $pdo = new PDO("mysql:host={$install['db_host']};dbname={$install['db_name']};charset=utf8mb4",
                           $install['db_user'], $install['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Yönetici kullanıcı oluştur
            $parola_hash = password_hash($sifre, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO kullanicilar (kullanici_adi, parola_hash) VALUES (?, ?)");
            $stmt->execute([$kullanici_adi, $parola_hash]);

            // config/db.php dosyasını oluştur
            $config_content = "<?php\n";
            $config_content .= "\$host = '{$install['db_host']}';\n";
            $config_content .= "\$dbname = '{$install['db_name']}';\n";
            $config_content .= "\$username = '{$install['db_user']}';\n";
            $config_content .= "\$password = '" . addslashes($install['db_pass']) . "';\n\n";
            $config_content .= "try {\n";
            $config_content .= "    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);\n";
            $config_content .= "    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";
            $config_content .= "    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);\n";
            $config_content .= "    \$pdo->exec(\"SET NAMES 'utf8mb4' COLLATE 'utf8mb4_turkish_ci'\");\n";
            $config_content .= "} catch(PDOException \$e) {\n";
            $config_content .= "    die(\"Veritabanı bağlantı hatası: \" . \$e->getMessage());\n";
            $config_content .= "}\n\n";
            $config_content .= "function yasHesapla(\$dogumTarihi) {\n";
            $config_content .= "    \$bugun = new DateTime();\n";
            $config_content .= "    \$dogum = new DateTime(\$dogumTarihi);\n";
            $config_content .= "    \$yas = \$bugun->diff(\$dogum);\n";
            $config_content .= "    return \$yas->y;\n";
            $config_content .= "}\n\n";
            $config_content .= "function turkceTarih(\$tarih) {\n";
            $config_content .= "    \$aylar = array(\n";
            $config_content .= "        'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart',\n";
            $config_content .= "        'April' => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran',\n";
            $config_content .= "        'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül',\n";
            $config_content .= "        'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'\n";
            $config_content .= "    );\n";
            $config_content .= "    return strtr(\$tarih, \$aylar);\n";
            $config_content .= "}\n\n";
            $config_content .= "function ayAdi(\$ay) {\n";
            $config_content .= "    \$aylar = array(\n";
            $config_content .= "        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',\n";
            $config_content .= "        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',\n";
            $config_content .= "        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'\n";
            $config_content .= "    );\n";
            $config_content .= "    return \$aylar[\$ay];\n";
            $config_content .= "}\n\n";
            $config_content .= "function siralama(\$sayi) {\n";
            $config_content .= "    if (\$sayi == 1) return \"Birincisi\";\n";
            $config_content .= "    if (\$sayi == 2) return \"İkincisi\";\n";
            $config_content .= "    if (\$sayi == 3) return \"Üçüncüsü\";\n";
            $config_content .= "    return \$sayi . \".\";\n";
            $config_content .= "}\n";
            $config_content .= "?>\n";

            file_put_contents('config/db.php', $config_content);

            // Session'ı temizle
            unset($_SESSION['install']);

            header('Location: install.php?step=4');
            exit;
        } catch(PDOException $e) {
            $hata = 'Kullanıcı oluşturma hatası: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🕌 Cami Namaz Takip Programı - Kurulum</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .wizard-container {
            background: white;
            max-width: 700px;
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .wizard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .wizard-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .wizard-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .progress-bar {
            display: flex;
            justify-content: space-between;
            padding: 30px 40px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 10px;
        }

        .progress-step::before {
            content: attr(data-step);
            display: block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            font-weight: bold;
        }

        .progress-step.active::before {
            background: #667eea;
            color: white;
        }

        .progress-step.completed::before {
            background: #28a745;
            color: white;
            content: '✓';
        }

        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 60%;
            width: 80%;
            height: 2px;
            background: #e0e0e0;
            z-index: -1;
        }

        .progress-step.completed:not(:last-child)::after {
            background: #28a745;
        }

        .wizard-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 14px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40,167,69,0.4);
        }

        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }

        .info-box h3 {
            margin-bottom: 10px;
            color: #667eea;
        }

        .info-box ul {
            margin-left: 20px;
        }

        .info-box li {
            margin: 5px 0;
        }

        .success-icon {
            font-size: 64px;
            text-align: center;
            margin: 20px 0;
        }

        .text-center {
            text-align: center;
        }

        @media (max-width: 768px) {
            .progress-step span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-header">
            <h1>🕌 Cami Namaz Takip Programı</h1>
            <p>Kurulum Sihirbazı</p>
        </div>

        <div class="progress-bar">
            <div class="progress-step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>" data-step="1">
                <span>Veritabanı</span>
            </div>
            <div class="progress-step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>" data-step="2">
                <span>Tablolar</span>
            </div>
            <div class="progress-step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>" data-step="3">
                <span>Yönetici</span>
            </div>
            <div class="progress-step <?php echo $step >= 4 ? 'active' : ''; ?>" data-step="4">
                <span>Tamamlandı</span>
            </div>
        </div>

        <div class="wizard-body">
            <?php if($hata): ?>
            <div class="alert alert-error">
                <strong>❌ Hata:</strong> <?php echo htmlspecialchars($hata); ?>
            </div>
            <?php endif; ?>

            <?php if($step == 1): ?>
                <h2>Adım 1: Veritabanı Bilgileri</h2>
                <p style="color: #666; margin: 15px 0;">Lütfen MySQL veritabanı bağlantı bilgilerinizi girin.</p>

                <form method="POST">
                    <div class="form-group">
                        <label>🖥️ Veritabanı Sunucusu</label>
                        <input type="text" name="db_host" value="localhost" required>
                        <small>Genellikle "localhost" kullanılır</small>
                    </div>

                    <div class="form-group">
                        <label>🗄️ Veritabanı Adı</label>
                        <input type="text" name="db_name" value="cami_namaz_takip" required>
                        <small>Veritabanı yoksa otomatik oluşturulacak</small>
                    </div>

                    <div class="form-group">
                        <label>👤 Veritabanı Kullanıcı Adı</label>
                        <input type="text" name="db_user" value="root" required>
                    </div>

                    <div class="form-group">
                        <label>🔒 Veritabanı Şifresi</label>
                        <input type="password" name="db_pass">
                        <small>Boş bırakılabilir (önerilmez)</small>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            İleri: Tabloları Oluştur →
                        </button>
                    </div>
                </form>

            <?php elseif($step == 2): ?>
                <h2>Adım 2: Veritabanı Tablolarını Oluştur</h2>
                <p style="color: #666; margin: 15px 0;">Veritabanı bağlantısı başarılı! Şimdi gerekli tabloları oluşturacağız.</p>

                <div class="info-box">
                    <h3>📋 Oluşturulacak Tablolar:</h3>
                    <ul>
                        <li><strong>ogrenciler</strong> - Öğrenci bilgileri</li>
                        <li><strong>ogrenci_kullanicilar</strong> - Öğrenci giriş bilgileri</li>
                        <li><strong>kullanicilar</strong> - Yönetici giriş bilgileri</li>
                        <li><strong>namaz_kayitlari</strong> - Namaz takip kayıtları</li>
                        <li><strong>dersler</strong> & <strong>ders_kategorileri</strong> - Ders yönetimi</li>
                        <li><strong>sertifikalar</strong> - Sertifika bilgileri</li>
                        <li><strong>ilave_puanlar</strong> - İlave puan kayıtları</li>
                        <li><strong>ogrenci_mesajlari</strong> - Mesajlaşma sistemi</li>
                        <li><strong>aylik_ozetler</strong> & <strong>yillik_ozetler</strong> - Raporlar</li>
                    </ul>
                </div>

                <form method="POST">
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            Tabloları Oluştur ve Devam Et →
                        </button>
                    </div>
                </form>

            <?php elseif($step == 3): ?>
                <h2>Adım 3: Yönetici Hesabı Oluştur</h2>
                <p style="color: #666; margin: 15px 0;">Tablolar başarıyla oluşturuldu! Şimdi sisteme giriş yapabilmek için bir yönetici hesabı oluşturun.</p>

                <form method="POST">
                    <div class="form-group">
                        <label>👤 Kullanıcı Adı</label>
                        <input type="text" name="kullanici_adi" required minlength="3" autofocus>
                        <small>En az 3 karakter olmalıdır</small>
                    </div>

                    <div class="form-group">
                        <label>🔒 Şifre</label>
                        <input type="password" name="sifre" required minlength="6">
                        <small>En az 6 karakter olmalıdır</small>
                    </div>

                    <div class="form-group">
                        <label>🔒 Şifre Tekrar</label>
                        <input type="password" name="sifre_tekrar" required minlength="6">
                    </div>

                    <div class="alert alert-info">
                        <strong>ℹ️ Önemli:</strong> Bu bilgileri güvenli bir yerde saklayın. Sisteme giriş yapmak için bu bilgilere ihtiyacınız olacak.
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            Kurulumu Tamamla →
                        </button>
                    </div>
                </form>

            <?php elseif($step == 4): ?>
                <div class="success-icon">🎉</div>
                <h2 class="text-center" style="color: #28a745; margin-bottom: 20px;">Kurulum Başarıyla Tamamlandı!</h2>

                <div class="alert alert-success">
                    <strong>✅ Tebrikler!</strong> Cami Namaz Takip Programı başarıyla kuruldu.
                </div>

                <div class="info-box">
                    <h3>📝 Sonraki Adımlar:</h3>
                    <ul>
                        <li>✅ Veritabanı bağlantısı kuruldu</li>
                        <li>✅ Tüm tablolar oluşturuldu</li>
                        <li>✅ Yönetici hesabı oluşturuldu</li>
                        <li>✅ config/db.php dosyası oluşturuldu</li>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <strong>⚠️ Güvenlik Uyarısı:</strong> Kurulum tamamlandıktan sonra <code>install.php</code> dosyasını sunucudan silmeniz veya yeniden erişimi engelleyecek şekilde koruma altına almanız önerilir.
                </div>

                <div class="text-center" style="margin-top: 30px;">
                    <a href="index.php" class="btn btn-success">
                        🚀 Sisteme Giriş Yap
                    </a>
                </div>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
