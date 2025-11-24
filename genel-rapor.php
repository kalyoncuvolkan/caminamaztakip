<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$yil = $_GET['yil'] ?? date('Y');
$ay = $_GET['ay'] ?? date('n');

$aylikRapor = $pdo->prepare("
    SELECT
        o.id,
        o.ad_soyad,
        COALESCE(SUM(CASE WHEN n.kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END), 0) as kendisi_sayisi,
        COALESCE(SUM(CASE WHEN n.kiminle_geldi = 'Babası' THEN 1 ELSE 0 END), 0) as babasi_sayisi,
        COALESCE(SUM(CASE WHEN n.kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END), 0) as annesi_sayisi,
        COALESCE(SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END), 0) as anne_babasi_sayisi,
        COALESCE(COUNT(n.id), 0) as toplam_namaz,
        COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND kategori = 'Namaz' AND YEAR(tarih) = ? AND MONTH(tarih) = ?), 0) as ilave_namaz_puan,
        COALESCE((SELECT SUM(CASE WHEN od.durum = 'Tamamlandi' AND od.puan_verildi = 1 THEN d.puan ELSE 0 END)
                  FROM ogrenci_dersler od
                  JOIN dersler d ON od.ders_id = d.id
                  WHERE od.ogrenci_id = o.id
                      AND YEAR(od.verme_tarihi) = ?
                      AND MONTH(od.verme_tarihi) = ?), 0) as normal_ders_puan,
        COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND kategori = 'Ders' AND YEAR(tarih) = ? AND MONTH(tarih) = ?), 0) as ilave_ders_puan,
        (COALESCE(COUNT(n.id), 0) +
         COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND kategori = 'Namaz' AND YEAR(tarih) = ? AND MONTH(tarih) = ?), 0) +
         COALESCE((SELECT SUM(CASE WHEN od.durum = 'Tamamlandi' AND od.puan_verildi = 1 THEN d.puan ELSE 0 END)
                   FROM ogrenci_dersler od
                   JOIN dersler d ON od.ders_id = d.id
                   WHERE od.ogrenci_id = o.id
                       AND YEAR(od.verme_tarihi) = ?
                       AND MONTH(od.verme_tarihi) = ?), 0) +
         COALESCE((SELECT SUM(puan) FROM ilave_puanlar WHERE ogrenci_id = o.id AND kategori = 'Ders' AND YEAR(tarih) = ? AND MONTH(tarih) = ?), 0)) as toplam_puan
    FROM
        ogrenciler o
        LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id
            AND YEAR(n.tarih) = ? AND MONTH(n.tarih) = ?
    GROUP BY
        o.id, o.ad_soyad
    HAVING toplam_puan > 0
    ORDER BY
        toplam_puan DESC, toplam_namaz DESC, o.ad_soyad
");
$aylikRapor->execute([$yil, $ay, $yil, $ay, $yil, $ay, $yil, $ay, $yil, $ay, $yil, $ay, $yil, $ay]);
$raporlar = $aylikRapor->fetchAll();

$toplamVakit = 0;
foreach($raporlar as $rapor) {
    $toplamVakit += $rapor['toplam_namaz'];
}

$yillar = $pdo->query("SELECT DISTINCT YEAR(tarih) as yil FROM namaz_kayitlari ORDER BY yil DESC")->fetchAll();

// İlk 3 öğrenci için ilave puan detaylarını çek (hem eklenen hem silinen)
$ilavePuanDetaylari = [];
for($i = 0; $i < min(3, count($raporlar)); $i++) {
    $detayStmt = $pdo->prepare("
        SELECT aciklama, puan
        FROM ilave_puanlar
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Namaz'
        AND aciklama NOT LIKE '%(bonus)%'
        UNION ALL
        SELECT CONCAT(aciklama, ' (Silindi: ', silme_nedeni, ')') as aciklama, -puan as puan
        FROM ilave_puan_silme_gecmisi
        WHERE ogrenci_id = ? AND YEAR(tarih) = ? AND MONTH(tarih) = ? AND kategori = 'Namaz'
        ORDER BY puan DESC
        LIMIT 5
    ");
    $detayStmt->execute([$raporlar[$i]['id'], $yil, $ay, $raporlar[$i]['id'], $yil, $ay]);
    $ilavePuanDetaylari[$raporlar[$i]['id']] = $detayStmt->fetchAll();
}

$aktif_sayfa = 'raporlar';
$sayfa_basligi = 'Genel Rapor - Cami Namaz Takip';
require_once 'config/header.php';
?>
        <style>
            /* Öğrenci adı link hover efekti */
            table td a:hover {
                text-decoration: underline;
                color: #5568d3;
            }

            .siralama-satir {
                margin-bottom: 15px;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 8px;
                border-left: 4px solid #667eea;
            }

            .siralama-satir a:hover {
                text-decoration: underline;
            }

            /* Yazdırma stilleri */
            @media print {
                /* Sayfayı gizle */
                body > div.container > header,
                .rapor-filtre,
                .rapor-butonlar,
                nav,
                .btn-print,
                .btn-export,
                .no-print {
                    display: none !important;
                }

                /* Sayfa düzeni */
                body {
                    margin: 0;
                    padding: 20px;
                    background: white;
                }

                .rapor-container {
                    width: 100%;
                    max-width: 100%;
                    padding: 0;
                    box-shadow: none;
                }

                /* Başlık */
                .rapor-baslik h3 {
                    font-size: 18px;
                    margin: 0 0 20px 0;
                    padding: 0;
                    text-align: center;
                    border: 2px solid #000;
                    padding: 10px;
                    background: #f0f0f0;
                }

                /* Tablo stilleri */
                .rapor-tablo table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 10px;
                    margin-bottom: 20px;
                }

                .rapor-tablo table th,
                .rapor-tablo table td {
                    border: 1px solid #000;
                    padding: 5px 3px;
                    text-align: center;
                }

                .rapor-tablo table th {
                    background: #e0e0e0;
                    font-weight: bold;
                }

                /* Link stillerini kaldır */
                .rapor-tablo table td a {
                    color: #000 !important;
                    text-decoration: none !important;
                    font-weight: normal !important;
                }

                /* Sıralama satırları */
                .siralama-satir {
                    border: 1px solid #000;
                    border-radius: 0;
                    background: white !important;
                    padding: 8px;
                    margin-bottom: 8px;
                    page-break-inside: avoid;
                }

                .siralama-satir a {
                    color: #000 !important;
                    text-decoration: none !important;
                }

                /* Özel sıralama stilleri */
                tr.siralama-1 td {
                    background: #ffd700 !important;
                    font-weight: bold;
                }

                tr.siralama-2 td {
                    background: #c0c0c0 !important;
                    font-weight: bold;
                }

                tr.siralama-3 td {
                    background: #cd7f32 !important;
                    font-weight: bold;
                }

                /* Renkli metinleri siyah yap */
                * {
                    color: #000 !important;
                }

                /* Özet bölümü */
                .rapor-ozet {
                    border-top: 2px solid #000;
                    padding-top: 15px;
                    font-size: 11px;
                }

                .rapor-ozet h4 {
                    font-size: 14px;
                    margin-bottom: 10px;
                    text-align: center;
                    border-bottom: 1px solid #000;
                    padding-bottom: 5px;
                }

                /* Sayfa sonu kontrolü */
                .rapor-tablo,
                .aylik-siralama {
                    page-break-inside: avoid;
                }

                /* İkonları gizle - sadece metin */
                .rapor-baslik h3::before,
                .aylik-siralama h4::before {
                    content: '' !important;
                }

                /* Emoji ve ikonları temizle */
                span[style*="font-size: 12px"]::before,
                span[style*="font-size: 14px"]::before {
                    content: '' !important;
                }
            }
        </style>

        <div class="rapor-container">
            <h2 class="no-print">📊 Genel Aylık Rapor</h2>
            
            <form method="GET" action="" class="rapor-filtre">
                <div class="form-group inline">
                    <label>Yıl:</label>
                    <select name="yil" onchange="this.form.submit()">
                        <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $yil ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group inline">
                    <label>Ay:</label>
                    <select name="ay" onchange="this.form.submit()">
                        <?php for($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $ay ? 'selected' : ''; ?>><?php echo ayAdi($m); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </form>

            <div class="rapor-baslik">
                <h3 class="print-title"><?php echo strtoupper(ayAdi($ay)) . ' ' . $yil; ?> AYI ÖĞRENCİ NAMAZA KATILIM RAPORU</h3>
            </div>

            <?php if(count($raporlar) > 0): ?>
            <div class="rapor-tablo">
                <table>
                    <thead>
                        <tr>
                            <th>Sıra</th>
                            <th>Öğrenci Adı</th>
                            <th>Kendisi</th>
                            <th>Babası</th>
                            <th>Annesi</th>
                            <th>Anne-Babası</th>
                            <th>Toplam Vakit</th>
                            <th>İlave Namaz Puanı</th>
                            <th>Ders Puanı</th>
                            <th>Toplam Puan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($raporlar as $index => $rapor): ?>
                        <tr class="<?php echo $index < 3 ? 'siralama-' . ($index + 1) : ''; ?>">
                            <td><?php echo $index + 1; ?>.</td>
                            <td>
                                <a href="ozel-rapor.php?id=<?php echo $rapor['id']; ?>&yil=<?php echo $yil; ?>&ay=<?php echo $ay; ?>" style="color: #667eea; text-decoration: none; font-weight: 600;">
                                    <?php echo $rapor['ad_soyad']; ?>
                                </a>
                            </td>
                            <td><?php echo $rapor['kendisi_sayisi']; ?></td>
                            <td><?php echo $rapor['babasi_sayisi']; ?></td>
                            <td><?php echo $rapor['annesi_sayisi']; ?></td>
                            <td><?php echo $rapor['anne_babasi_sayisi']; ?></td>
                            <td><?php echo $rapor['toplam_namaz']; ?></td>
                            <td style="color: #28a745; font-weight: bold;"><?php echo $rapor['ilave_namaz_puan'] > 0 ? '+' . $rapor['ilave_namaz_puan'] : '0'; ?></td>
                            <td style="color: #2196F3; font-weight: bold;">
                                <?php
                                $toplamDers = ($rapor['normal_ders_puan'] ?? 0) + ($rapor['ilave_ders_puan'] ?? 0);
                                echo $toplamDers > 0 ? $toplamDers : '0';
                                if($rapor['normal_ders_puan'] > 0 && $rapor['ilave_ders_puan'] > 0) {
                                    echo '<br><small style="font-size: 10px; font-weight: normal;">(' . $rapor['normal_ders_puan'] . '+' . $rapor['ilave_ders_puan'] . ')</small>';
                                } elseif($rapor['normal_ders_puan'] > 0) {
                                    echo '<br><small style="font-size: 10px; font-weight: normal;">(normal)</small>';
                                } elseif($rapor['ilave_ders_puan'] > 0) {
                                    echo '<br><small style="font-size: 10px; font-weight: normal;">(ilave)</small>';
                                }
                                ?>
                            </td>
                            <td class="toplam" style="background: #e8f5e9; font-weight: bold; font-size: 16px;"><?php echo $rapor['toplam_puan']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="rapor-ozet">
                <p><strong>Programa katılan öğrenci sayısı:</strong> <?php echo count($raporlar); ?></p>
                <p><strong>Toplam vakit sayısı:</strong> <?php echo $toplamVakit; ?></p>
                
                <div class="aylik-siralama">
                    <h4><span class="no-print">🏆 </span><?php echo ayAdi($ay); ?> Ayı Sıralaması</h4>
                    <?php for($i = 0; $i < min(3, count($raporlar)); $i++): ?>
                    <p class="siralama-satir">
                        <span class="siralama-badge badge-<?php echo $i + 1; ?>">
                            <?php echo ayAdi($ay); ?> Ayının <?php echo siralama($i + 1); ?>:
                        </span>
                        <a href="ozel-rapor.php?id=<?php echo $raporlar[$i]['id']; ?>&yil=<?php echo $yil; ?>&ay=<?php echo $ay; ?>" style="color: #333; text-decoration: none;">
                            <strong><?php echo $raporlar[$i]['ad_soyad']; ?></strong>
                        </a>
                        <br>
                        <span style="color: #666; font-size: 14px; margin-left: 10px;">
                            <span class="no-print">📿 </span><?php echo $raporlar[$i]['toplam_namaz']; ?> Vakit
                            <?php if($raporlar[$i]['ilave_puan'] > 0): ?>
                                <span style="color: #28a745; font-weight: 600;"> + <?php echo $raporlar[$i]['ilave_puan']; ?> İlave Puan</span>
                            <?php endif; ?>
                            <span style="color: #667eea; font-weight: bold; font-size: 16px;"> = <?php echo $raporlar[$i]['toplam_puan']; ?> Toplam Puan</span>
                        </span>
                        <br>
                        <span style="color: #999; font-size: 12px; margin-left: 10px;">
                            <?php
                            $detaylar = [];
                            if($raporlar[$i]['kendisi_sayisi'] > 0) {
                                $detaylar[] = $raporlar[$i]['kendisi_sayisi'] . ' Kendisi';
                            }
                            if($raporlar[$i]['babasi_sayisi'] > 0) {
                                $detaylar[] = $raporlar[$i]['babasi_sayisi'] . ' Babası';
                            }
                            if($raporlar[$i]['annesi_sayisi'] > 0) {
                                $detaylar[] = $raporlar[$i]['annesi_sayisi'] . ' Annesi';
                            }
                            if($raporlar[$i]['anne_babasi_sayisi'] > 0) {
                                $detaylar[] = $raporlar[$i]['anne_babasi_sayisi'] . ' Anne-Babası';
                            }
                            echo implode(' • ', $detaylar);
                            ?>
                        </span>
                        <?php if(!empty($ilavePuanDetaylari[$raporlar[$i]['id']])): ?>
                        <br>
                        <span style="font-size: 12px; margin-left: 10px; font-weight: 500;">
                            <span class="no-print">💰 </span>İlave Puan:
                            <?php
                            $ilave_detaylar = [];
                            foreach($ilavePuanDetaylari[$raporlar[$i]['id']] as $detay) {
                                $renk = $detay['puan'] < 0 ? '#dc3545' : '#28a745';
                                $isaret = $detay['puan'] > 0 ? '+' : '';
                                if($detay['aciklama']) {
                                    $ilave_detaylar[] = '<span style="color: ' . $renk . ';">' . htmlspecialchars($detay['aciklama']) . ' (' . $isaret . $detay['puan'] . ')</span>';
                                } else {
                                    $ilave_detaylar[] = '<span style="color: ' . $renk . ';">' . $isaret . $detay['puan'] . ' puan</span>';
                                }
                            }
                            echo implode(' • ', $ilave_detaylar);
                            ?>
                        </span>
                        <?php endif; ?>
                    </p>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="rapor-butonlar">
                <button onclick="window.print()" class="btn-print">🖨️ Yazdır</button>
                <button onclick="exportExcel()" class="btn-export">📊 Excel'e Aktar</button>
            </div>
            <?php else: ?>
            <div class="alert info">Bu ay için henüz kayıt bulunmamaktadır.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function exportExcel() {
            var table = document.querySelector('.rapor-tablo table');
            var html = table.outerHTML;

            // UTF-8 BOM (Byte Order Mark) ekle - Türkçe karakterler için gerekli
            var bom = "\uFEFF";
            var htmlWithBom = bom + '<html><head><meta charset="UTF-8"></head><body>' + html + '</body></html>';

            // Excel için doğru MIME type ve encoding
            var blob = new Blob([htmlWithBom], {
                type: 'application/vnd.ms-excel;charset=utf-8'
            });

            var url = URL.createObjectURL(blob);
            var downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            downloadLink.href = url;
            downloadLink.download = 'namaz_raporu_<?php echo $ay; ?>_<?php echo $yil; ?>.xls';
            downloadLink.click();
            document.body.removeChild(downloadLink);

            // URL'i temizle
            setTimeout(function() {
                URL.revokeObjectURL(url);
            }, 100);
        }
    </script>
<?php require_once 'config/footer.php'; ?>