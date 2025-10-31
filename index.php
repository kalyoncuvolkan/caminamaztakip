<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$yil = date('Y');
$ay = date('n');

$yillikBirinci = $pdo->prepare("
    SELECT ad_soyad, toplam_namaz, toplam_puan
    FROM yillik_ozetler
    WHERE yil = ?
    ORDER BY toplam_puan DESC, toplam_namaz DESC
    LIMIT 3
");
$yillikBirinci->execute([$yil]);
$yillikSiralama = $yillikBirinci->fetchAll();

$aylikBirinci = $pdo->prepare("
    SELECT ad_soyad, toplam_namaz, toplam_puan
    FROM aylik_ozetler
    WHERE yil = ? AND ay = ?
    ORDER BY toplam_puan DESC, toplam_namaz DESC
    LIMIT 3
");
$aylikBirinci->execute([$yil, $ay]);
$aylikSiralama = $aylikBirinci->fetchAll();

$ogrenciler = $pdo->query("SELECT * FROM ogrenciler WHERE aktif = 1 ORDER BY ad_soyad")->fetchAll();

$aktif_sayfa = 'index';
$sayfa_basligi = 'Ana Sayfa - Cami Namaz Takip';
require_once 'config/header.php';
?>

        <div class="dashboard">
            <div class="skor-tablosu">
                <h2>📊 <?php echo $yil; ?> Yılı Sıralaması</h2>
                <div class="siralama-listesi">
                    <?php foreach($yillikSiralama as $index => $ogrenci): ?>
                    <div class="siralama-item <?php echo $index == 0 ? 'birinci' : ($index == 1 ? 'ikinci' : 'ucuncu'); ?>">
                        <span class="sira"><?php echo siralama($index + 1); ?>:</span>
                        <span class="isim"><?php echo $ogrenci['ad_soyad'] ?? 'Henüz belirlenmedi'; ?></span>
                        <span class="puan"><?php echo $ogrenci['toplam_puan'] ?? '0'; ?> Puan (<?php echo $ogrenci['toplam_namaz'] ?? '0'; ?> Vakit)</span>
                    </div>
                    <?php endforeach; ?>
                    <?php for($i = count($yillikSiralama); $i < 3; $i++): ?>
                    <div class="siralama-item">
                        <span class="sira"><?php echo siralama($i + 1); ?>:</span>
                        <span class="isim">Henüz belirlenmedi</span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="skor-tablosu">
                <h2>🏆 <?php echo ayAdi($ay); ?> Ayı Sıralaması</h2>
                <div class="siralama-listesi">
                    <?php foreach($aylikSiralama as $index => $ogrenci): ?>
                    <div class="siralama-item <?php echo $index == 0 ? 'birinci' : ($index == 1 ? 'ikinci' : 'ucuncu'); ?>">
                        <span class="sira"><?php echo siralama($index + 1); ?>:</span>
                        <span class="isim"><?php echo $ogrenci['ad_soyad'] ?? 'Henüz belirlenmedi'; ?></span>
                        <span class="puan"><?php echo $ogrenci['toplam_puan'] ?? '0'; ?> Puan (<?php echo $ogrenci['toplam_namaz'] ?? '0'; ?> Vakit)</span>
                    </div>
                    <?php endforeach; ?>
                    <?php for($i = count($aylikSiralama); $i < 3; $i++): ?>
                    <div class="siralama-item">
                        <span class="sira"><?php echo siralama($i + 1); ?>:</span>
                        <span class="isim">Henüz belirlenmedi</span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <div class="ogrenci-listesi">
            <h2>👥 Öğrenci Listesi</h2>
            <table>
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>Yaş</th>
                        <th>Baba Adı</th>
                        <th>Anne Adı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ogrenciler as $ogrenci): ?>
                    <tr>
                        <td><?php echo $ogrenci['ad_soyad']; ?></td>
                        <td><?php echo yasHesapla($ogrenci['dogum_tarihi']); ?></td>
                        <td><?php echo $ogrenci['baba_adi']; ?></td>
                        <td><?php echo $ogrenci['anne_adi']; ?></td>
                        <td>
                            <button onclick="ogrenciDetay(<?php echo $ogrenci['id']; ?>)" class="btn-detay">Görüntüle</button>
                            <a href="ozel-rapor.php?id=<?php echo $ogrenci['id']; ?>" class="btn-rapor">Raporla</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

<div id="ogrenci-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="modal-body"></div>
    </div>
</div>

<script>
    function ogrenciDetay(id) {
        fetch('api/ogrenci-detay.php?id=' + id)
            .then(response => response.text())
            .then(html => {
                document.getElementById('modal-body').innerHTML = html;
                document.getElementById('ogrenci-modal').style.display = 'block';
            });
    }

    document.getElementsByClassName('close')[0].onclick = function() {
        document.getElementById('ogrenci-modal').style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('ogrenci-modal')) {
            document.getElementById('ogrenci-modal').style.display = 'none';
        }
    }
</script>

<?php require_once 'config/footer.php'; ?>