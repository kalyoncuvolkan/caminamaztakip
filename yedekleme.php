<?php
// Debug mode - hatalar hem ekranda hem log dosyasında gösterilir
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/yedekleme-errors.log');

// Log klasörünü oluştur
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Özel hata logger fonksiyonu
function logBackupError($message, $context = []) {
    $logFile = __DIR__ . '/logs/yedekleme-errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logMessage = "[{$timestamp}] {$message}{$contextStr}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

logBackupError('=== YEDEKLEME SCRIPT STARTED ===', ['user' => $_SERVER['REMOTE_ADDR'] ?? 'N/A']);

try {
    require_once 'config/auth.php';
    checkAuth();
    require_once 'config/db.php';

    $mesaj = '';
    $hata_detay = '';

    // Manuel yedekleme
    if(isset($_GET['yedek'])) {
        logBackupError('Backup process started');

        $tarih = date('Y-m-d_H-i-s');
        $dosya = __DIR__ . "/backup/cami_yedek_$tarih.sql";

        if(!is_dir(__DIR__ . '/backup')) {
            mkdir(__DIR__ . '/backup', 0755, true);
            logBackupError('Backup directory created');
        }

        // config/db.php'den global değişkenleri kullan
        global $host, $dbname, $username, $password;

        logBackupError('Database config loaded', [
            'host' => $host ?? 'N/A',
            'dbname' => $dbname ?? 'N/A',
            'username' => $username ?? 'N/A'
        ]);

        // exec() fonksiyonu devre dışı mı kontrol et
        $disabled_functions = explode(',', ini_get('disable_functions'));
        $exec_disabled = in_array('exec', $disabled_functions);

        if ($exec_disabled) {
            logBackupError('ERROR: exec() function is disabled on this server');
            throw new Exception('exec() fonksiyonu bu sunucuda devre dışı. Shared hosting\'de mysqldump kullanılamaz.');
        }

        // mysqldump komutu oluştur (global değişkenler zaten var)
        $db_host = $host ?? 'localhost';
        $db_user = $username ?? 'root';
        $db_pass = $password ?? '';
        $db_name = $dbname ?? 'cami_namaz_takip';

        // Şifre boşsa -p kullanma
        $pass_param = !empty($db_pass) ? "-p'" . addslashes($db_pass) . "'" : '';

        $cmd = "mysqldump -h {$db_host} -u {$db_user} {$pass_param} {$db_name} > {$dosya} 2>&1";

        logBackupError('Executing mysqldump', ['command' => str_replace($db_pass, '***', $cmd)]);

        exec($cmd, $output, $return);

        logBackupError('mysqldump completed', [
            'return_code' => $return,
            'output' => implode("\n", $output),
            'file_exists' => file_exists($dosya),
            'file_size' => file_exists($dosya) ? filesize($dosya) : 0
        ]);

        if($return === 0 && file_exists($dosya) && filesize($dosya) > 0) {
            $mesaj = "✅ Yedekleme başarılı: " . basename($dosya) . " (" . round(filesize($dosya)/1024, 2) . " KB)";
            logBackupError('Backup successful');
        } else {
            $hata_detay = implode("\n", $output);
            $mesaj = "❌ Yedekleme hatası! Detaylar: logs/yedekleme-errors.log";
            logBackupError('Backup failed', ['output' => $hata_detay]);
        }
    }

    // Mevcut yedekler
    $yedekler = glob(__DIR__ . '/backup/*.sql');
    rsort($yedekler);

    $aktif_sayfa = 'yedekleme';
    $sayfa_basligi = 'Yedekleme - Cami Namaz Takip';

    logBackupError('=== SCRIPT COMPLETED SUCCESSFULLY ===');

} catch (Exception $e) {
    // Hata yakalama
    logBackupError('!!! EXCEPTION !!!', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);

    $mesaj = "❌ Hata: " . htmlspecialchars($e->getMessage());
    $hata_detay = $e->getMessage();
    $yedekler = [];
    $aktif_sayfa = 'yedekleme';
    $sayfa_basligi = 'Yedekleme - Cami Namaz Takip';
}

require_once 'config/header.php';
?>

        <div style="padding: 30px;">
            <h2>💾 Sistem Yedekleme</h2>
            <?php if($mesaj): ?>
                <div class="alert <?php echo strpos($mesaj, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo $mesaj; ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($hata_detay)): ?>
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 5px solid #856404;">
                    <h4 style="margin-top: 0;">📋 Hata Detayları</h4>
                    <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;"><?php echo htmlspecialchars($hata_detay); ?></pre>
                    <p><strong>Log dosyası:</strong> <code>logs/yedekleme-errors.log</code></p>
                </div>
            <?php endif; ?>

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
<?php require_once 'config/footer.php'; ?>