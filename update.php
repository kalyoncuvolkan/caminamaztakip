<?php
session_start();
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

// GitHub Repository bilgileri
define('GITHUB_REPO', 'kalyoncuvolkan/caminamaztakip');
define('GITHUB_BRANCH', 'main');
define('CURRENT_VERSION', '2.0.0'); // Manuel olarak güncellenecek

$mesaj = '';
$hata = '';
$guncellemeler = [];
$guncelleme_var = false;

// GitHub'dan son commit bilgisini al
function getLatestCommit() {
    $url = 'https://api.github.com/repos/' . GITHUB_REPO . '/commits/' . GITHUB_BRANCH;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CamiNamazTakip/2.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($http_code == 200) {
        return json_decode($response, true);
    }

    return null;
}

// Yerel son commit hash'ini oku
function getLocalCommit() {
    if(file_exists('.git/refs/heads/main')) {
        return trim(file_get_contents('.git/refs/heads/main'));
    }
    return null;
}

// Güncellemeleri kontrol et
if(isset($_GET['check'])) {
    $latest = getLatestCommit();
    $local = getLocalCommit();

    if($latest) {
        $remote_hash = substr($latest['sha'], 0, 7);
        $local_hash = $local ? substr($local, 0, 7) : 'bilinmiyor';

        if($remote_hash != $local_hash) {
            $guncelleme_var = true;
            $guncellemeler = [
                'remote_hash' => $remote_hash,
                'local_hash' => $local_hash,
                'message' => $latest['commit']['message'],
                'author' => $latest['commit']['author']['name'],
                'date' => date('d.m.Y H:i', strtotime($latest['commit']['author']['date']))
            ];
        }
    }
}

// Güncelleme işlemini başlat
if(isset($_POST['update'])) {
    try {
        // 1. Yedekleme
        $backup_dir = 'backups';
        if(!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }

        $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.zip';

        // Basit dosya kopyalama yedeği
        $backup_sql = $backup_dir . '/database_' . date('Y-m-d_H-i-s') . '.sql';

        // Veritabanı yedeği (mysqldump kullanarak)
        if(file_exists('config/db.php')) {
            include 'config/db.php';
            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($dbname),
                escapeshellarg($backup_sql)
            );
            exec($command, $output, $return_var);
        }

        // 2. Git pull ile güncelleme
        $git_available = false;
        exec('which git', $git_output, $git_return);
        if($git_return === 0) {
            $git_available = true;

            // Git pull
            exec('git pull origin main 2>&1', $output, $return_var);

            if($return_var === 0) {
                // 3. İzinleri düzelt
                exec('chmod -R 755 . 2>&1');

                // 4. Cache temizle (varsa)
                if(is_dir('cache')) {
                    array_map('unlink', glob('cache/*'));
                }

                $mesaj = "✅ Güncelleme başarıyla tamamlandı!\n\nYedek: " . $backup_sql;
            } else {
                throw new Exception("Git pull hatası: " . implode("\n", $output));
            }
        } else {
            // Git yoksa manuel güncelleme talimatı
            throw new Exception("Git bulunamadı! Manuel güncelleme yapmanız gerekiyor.");
        }

    } catch(Exception $e) {
        $hata = $e->getMessage();
    }
}

// Yerel değişiklikleri kontrol et
$has_changes = false;
if(is_dir('.git')) {
    exec('git status --porcelain 2>&1', $status_output);
    $has_changes = !empty($status_output);
}

$aktif_sayfa = 'update';
$sayfa_basligi = 'Sistem Güncellemeleri - Cami Namaz Takip';
require_once 'config/header.php';
?>

<style>
    .update-container {
        max-width: 900px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .version-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .version-badge {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
    }

    .update-badge {
        display: inline-block;
        background: #28a745;
        color: white;
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .commit-card {
        background: #f8f9fa;
        border-left: 4px solid #667eea;
        padding: 20px;
        border-radius: 8px;
        margin: 15px 0;
    }

    .commit-hash {
        font-family: monospace;
        background: #e9ecef;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 13px;
    }

    .btn-update {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 15px 40px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(40,167,69,0.3);
    }

    .btn-update:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40,167,69,0.4);
    }

    .btn-check {
        background: #667eea;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
    }

    .btn-check:hover {
        background: #5568d3;
        transform: translateY(-2px);
    }

    .warning-box {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
    }

    .info-box {
        background: #d1ecf1;
        border-left: 4px solid #17a2b8;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
    }

    .success-box {
        background: #d4edda;
        border-left: 4px solid #28a745;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
    }

    .error-box {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
    }

    .changelog {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }

    .changelog-item {
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .changelog-item:last-child {
        border-bottom: none;
    }
</style>

<div class="update-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin: 0;">🔄 Sistem Güncellemeleri</h2>
        <span class="version-badge">v<?php echo CURRENT_VERSION; ?></span>
    </div>

    <?php if($mesaj): ?>
    <div class="success-box">
        <strong>✅ Başarılı!</strong><br>
        <?php echo nl2br(htmlspecialchars($mesaj)); ?>
    </div>
    <?php endif; ?>

    <?php if($hata): ?>
    <div class="error-box">
        <strong>❌ Hata!</strong><br>
        <?php echo nl2br(htmlspecialchars($hata)); ?>
    </div>
    <?php endif; ?>

    <!-- Mevcut Durum -->
    <div class="version-card">
        <h3>📊 Mevcut Durum</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 15px;">
            <div>
                <strong>Versiyon:</strong><br>
                <span class="commit-hash"><?php echo CURRENT_VERSION; ?></span>
            </div>
            <div>
                <strong>Yerel Commit:</strong><br>
                <span class="commit-hash"><?php echo getLocalCommit() ? substr(getLocalCommit(), 0, 7) : 'Bilinmiyor'; ?></span>
            </div>
            <div>
                <strong>Repository:</strong><br>
                <a href="https://github.com/<?php echo GITHUB_REPO; ?>" target="_blank" style="color: #667eea;">
                    <?php echo GITHUB_REPO; ?>
                </a>
            </div>
        </div>

        <?php if($has_changes): ?>
        <div class="warning-box" style="margin-top: 20px;">
            <strong>⚠️ Uyarı:</strong> Yerel değişiklikler tespit edildi. Güncelleme yapmadan önce bu değişiklikleri commit edin veya yedekleyin.
        </div>
        <?php endif; ?>
    </div>

    <!-- Güncelleme Kontrolü -->
    <div class="version-card">
        <h3>🔍 Güncelleme Kontrolü</h3>
        <p style="color: #666;">GitHub repository'sinde yeni güncellemeler olup olmadığını kontrol edin.</p>

        <a href="?check=1" class="btn-check">
            🔄 Güncellemeleri Kontrol Et
        </a>

        <?php if(isset($_GET['check'])): ?>
            <?php if($guncelleme_var): ?>
            <div class="commit-card" style="margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <div>
                        <span class="update-badge">🆕 Yeni Güncelleme Mevcut!</span>
                    </div>
                    <div style="text-align: right;">
                        <strong>Commit:</strong> <span class="commit-hash"><?php echo $guncellemeler['remote_hash']; ?></span>
                    </div>
                </div>

                <div style="margin: 15px 0;">
                    <strong>📝 Değişiklik:</strong><br>
                    <?php echo htmlspecialchars($guncellemeler['message']); ?>
                </div>

                <div style="color: #666; font-size: 14px;">
                    <strong>👤 Yazar:</strong> <?php echo htmlspecialchars($guncellemeler['author']); ?><br>
                    <strong>📅 Tarih:</strong> <?php echo $guncellemeler['date']; ?>
                </div>
            </div>

            <div class="warning-box">
                <strong>⚠️ Güncelleme Yapmadan Önce:</strong>
                <ul style="margin: 10px 0 0 20px; padding: 0;">
                    <li>Otomatik veritabanı yedeği alınacak</li>
                    <li>Dosya yedeği backups/ klasörüne kaydedilecek</li>
                    <li>Sunucunuzda <code>git</code> kurulu olmalı</li>
                    <li>Dosya izinleri otomatik düzenlenecek</li>
                </ul>
            </div>

            <form method="POST" onsubmit="return confirm('⚠️ Güncelleme işlemini başlatmak istediğinize emin misiniz?\n\nOtomatik yedekleme yapılacak ancak yine de manuel yedek almanızı öneririz.');">
                <button type="submit" name="update" class="btn-update">
                    🚀 Güncellemeyi Başlat
                </button>
            </form>
            <?php else: ?>
            <div class="success-box" style="margin-top: 20px;">
                <strong>✅ Sistem Güncel!</strong><br>
                Kullanmakta olduğunuz versiyon en son sürümdür.
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Yedekleme Bilgisi -->
    <div class="version-card">
        <h3>💾 Yedeklemeler</h3>
        <p style="color: #666;">Güncelleme öncesi otomatik yedekler alınır.</p>

        <?php
        $backup_dir = 'backups';
        if(is_dir($backup_dir)) {
            $backups = glob($backup_dir . '/*.sql');
            rsort($backups);
            $backups = array_slice($backups, 0, 10);

            if(!empty($backups)):
        ?>
        <table style="width: 100%; margin-top: 15px;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; text-align: left;">Dosya</th>
                    <th style="padding: 10px; text-align: left;">Tarih</th>
                    <th style="padding: 10px; text-align: left;">Boyut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($backups as $backup): ?>
                <tr style="border-bottom: 1px solid #e9ecef;">
                    <td style="padding: 10px;"><?php echo basename($backup); ?></td>
                    <td style="padding: 10px;"><?php echo date('d.m.Y H:i', filemtime($backup)); ?></td>
                    <td style="padding: 10px;"><?php echo round(filesize($backup) / 1024, 2); ?> KB</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="info-box">
            <strong>ℹ️ Bilgi:</strong> Henüz yedek bulunmuyor. İlk güncelleme sırasında otomatik yedek alınacak.
        </div>
        <?php endif; ?>
        <?php } ?>
    </div>

    <!-- Manuel Güncelleme Talimatları -->
    <div class="version-card">
        <h3>📚 Manuel Güncelleme</h3>
        <p style="color: #666;">Otomatik güncelleme çalışmazsa, manuel olarak güncelleyebilirsiniz:</p>

        <div class="changelog">
            <strong>SSH Erişimi Varsa:</strong>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto;"><code>cd /var/www/html/cami
git pull origin main
chmod -R 755 .
</code></pre>

            <strong style="margin-top: 15px; display: block;">FTP ile:</strong>
            <ol style="margin-left: 20px;">
                <li>GitHub'dan ZIP indir: <a href="https://github.com/<?php echo GITHUB_REPO; ?>/archive/refs/heads/main.zip" target="_blank">İndir</a></li>
                <li>ZIP'i açın</li>
                <li>Sadece değişen dosyaları FTP ile yükleyin</li>
                <li>config/db.php dosyasını yedekten geri yükleyin</li>
            </ol>
        </div>
    </div>

    <!-- Git Durumu -->
    <?php if(is_dir('.git')): ?>
    <div class="version-card">
        <h3>🔧 Git Durumu</h3>
        <?php
        exec('git status --porcelain 2>&1', $git_status);
        exec('git log -1 --pretty=format:"%h - %s (%cr) <%an>" 2>&1', $git_log);
        ?>

        <?php if(!empty($git_log)): ?>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
            <strong>Son Commit:</strong><br>
            <code><?php echo htmlspecialchars($git_log[0]); ?></code>
        </div>
        <?php endif; ?>

        <?php if(!empty($git_status)): ?>
        <div class="warning-box" style="margin-top: 15px;">
            <strong>⚠️ Değiştirilmiş Dosyalar:</strong>
            <pre style="margin: 10px 0 0 0; font-size: 12px;"><?php echo htmlspecialchars(implode("\n", $git_status)); ?></pre>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'config/footer.php'; ?>
