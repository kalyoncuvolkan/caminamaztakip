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
        $disabled_functions = explode(',', str_replace(' ', '', ini_get('disable_functions')));
        $exec_disabled = in_array('exec', $disabled_functions);

        logBackupError('Checking exec() availability', ['disabled' => $exec_disabled]);

        // PHP-based backup (exec kullanmadan)
        try {
            $sql_dump = "";

            // Header
            $sql_dump .= "-- Cami Namaz Takip Veritabanı Yedekleme\n";
            $sql_dump .= "-- Tarih: " . date('Y-m-d H:i:s') . "\n";
            $sql_dump .= "-- Database: {$dbname}\n\n";
            $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $sql_dump .= "SET time_zone = \"+00:00\";\n\n";

            // Tüm tabloları al
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            logBackupError('Tables found', ['count' => count($tables), 'tables' => $tables]);

            foreach ($tables as $table) {
                // VIEW'ları atla (daha sonra ekleriz)
                $table_type = $pdo->query("SHOW FULL TABLES WHERE Tables_in_{$dbname} = '{$table}'")->fetch();
                if ($table_type && isset($table_type[1]) && $table_type[1] === 'VIEW') {
                    logBackupError('Skipping VIEW', ['table' => $table]);
                    continue;
                }

                logBackupError('Processing table', ['table' => $table]);

                // Tablo yapısını al
                $sql_dump .= "\n-- --------------------------------------------------------\n";
                $sql_dump .= "-- Tablo yapısı: `{$table}`\n";
                $sql_dump .= "-- --------------------------------------------------------\n\n";
                $sql_dump .= "DROP TABLE IF EXISTS `{$table}`;\n";

                $create_table = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
                $sql_dump .= $create_table['Create Table'] . ";\n\n";

                // Tablo verilerini al
                $row_count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                logBackupError('Table row count', ['table' => $table, 'rows' => $row_count]);

                if ($row_count > 0) {
                    $sql_dump .= "-- Veri dökümü: `{$table}` ({$row_count} satır)\n\n";

                    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($rows as $row) {
                        $values = array_map(function($value) use ($pdo) {
                            if ($value === null) return 'NULL';
                            return $pdo->quote($value);
                        }, $row);

                        $sql_dump .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql_dump .= "\n";
                }
            }

            // VIEW'ları ekle
            $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN);
            if (count($views) > 0) {
                $sql_dump .= "\n-- --------------------------------------------------------\n";
                $sql_dump .= "-- VIEW'lar\n";
                $sql_dump .= "-- --------------------------------------------------------\n\n";

                foreach ($views as $view) {
                    logBackupError('Processing VIEW', ['view' => $view]);
                    $sql_dump .= "DROP VIEW IF EXISTS `{$view}`;\n";
                    $create_view = $pdo->query("SHOW CREATE VIEW `{$view}`")->fetch();
                    $sql_dump .= $create_view['Create View'] . ";\n\n";
                }
            }

            $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Dosyaya yaz
            $bytes_written = file_put_contents($dosya, $sql_dump);

            logBackupError('Backup file written', [
                'bytes' => $bytes_written,
                'file_size' => filesize($dosya),
                'tables' => count($tables)
            ]);

            if ($bytes_written > 0 && file_exists($dosya)) {
                $mesaj = "✅ Yedekleme başarılı: " . basename($dosya) . " (" . round(filesize($dosya)/1024, 2) . " KB)";
                logBackupError('Backup successful');
            } else {
                throw new Exception('Yedek dosyası oluşturulamadı!');
            }

        } catch (PDOException $e) {
            $hata_detay = $e->getMessage();
            $mesaj = "❌ Veritabanı hatası: " . htmlspecialchars($e->getMessage());
            logBackupError('Backup failed - PDO Exception', ['error' => $e->getMessage()]);
        } catch (Exception $e) {
            $hata_detay = $e->getMessage();
            $mesaj = "❌ Yedekleme hatası: " . htmlspecialchars($e->getMessage());
            logBackupError('Backup failed - Exception', ['error' => $e->getMessage()]);
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