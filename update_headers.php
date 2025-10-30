<?php
/**
 * Tüm sayfalara yeni header/footer sistemini uygulayan script
 */

$pages = [
    'ogrenciler.php' => ['aktif_sayfa' => 'ogrenciler', 'baslik' => 'Öğrenci Listesi'],
    'ogrenci-ekle.php' => ['aktif_sayfa' => 'ogrenciler', 'baslik' => 'Öğrenci Ekle'],
    'ogrenci-duzenle.php' => ['aktif_sayfa' => 'ogrenciler', 'baslik' => 'Öğrenci Düzenle'],
    'namaz-ekle-yeni.php' => ['aktif_sayfa' => 'namaz', 'baslik' => 'Namaz Kaydı Ekle'],
    'puan-yonetimi.php' => ['aktif_sayfa' => 'puan', 'baslik' => 'Puan Yönetimi'],
    'ders-kategorileri.php' => ['aktif_sayfa' => 'dersler', 'baslik' => 'Ders Kategorileri'],
    'dersler.php' => ['aktif_sayfa' => 'dersler', 'baslik' => 'Dersler'],
    'ders-takip.php' => ['aktif_sayfa' => 'dersler', 'baslik' => 'Ders Takibi'],
    'sertifikalar.php' => ['aktif_sayfa' => 'sertifikalar', 'baslik' => 'Sertifikalar'],
    'genel-rapor.php' => ['aktif_sayfa' => 'raporlar', 'baslik' => 'Genel Rapor'],
    'ozel-rapor.php' => ['aktif_sayfa' => 'raporlar', 'baslik' => 'Özel Rapor'],
    'yedekleme.php' => ['aktif_sayfa' => 'yedekleme', 'baslik' => 'Yedekleme'],
];

foreach ($pages as $file => $config) {
    $path = __DIR__ . '/' . $file;

    if (!file_exists($path)) {
        echo "❌ Dosya bulunamadı: $file\n";
        continue;
    }

    $content = file_get_contents($path);

    // Dosyanın zaten güncellenmiş olup olmadığını kontrol et
    if (strpos($content, "require_once 'config/header.php'") !== false) {
        echo "✅ Zaten güncellenmiş: $file\n";
        continue;
    }

    // <!DOCTYPE html> ile başlayan kısmı bul ve değiştir
    $pattern = '/^(.*?)(<!DOCTYPE html>.*?<\/header>)/s';

    if (preg_match($pattern, $content, $matches)) {
        $php_code = $matches[1];

        // Header include ekle
        $new_header = "\$aktif_sayfa = '{$config['aktif_sayfa']}';\n";
        $new_header .= "\$sayfa_basligi = '{$config['baslik']} - Cami Namaz Takip';\n";
        $new_header .= "require_once 'config/header.php';\n?>\n";

        $new_content = $php_code . $new_header;

        // HTML'i header'dan sonraki kısımla değiştir
        $rest_of_content = substr($content, strlen($matches[0]));
        $new_content .= $rest_of_content;

        // </body></html> kısmını footer ile değiştir
        $new_content = preg_replace(
            '/\s*<\/div>\s*<\/body>\s*<\/html>\s*$/s',
            "\n\n<?php require_once 'config/footer.php'; ?>",
            $new_content
        );

        // Dosyayı kaydet
        file_put_contents($path, $new_content);
        echo "✅ Güncellendi: $file\n";
    } else {
        echo "⚠️  Pattern eşleşmedi: $file\n";
    }
}

echo "\n✨ Tamamlandı!\n";
