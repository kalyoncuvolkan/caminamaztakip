<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$filtre = $_GET['filtre'] ?? 'aktif';
$arama = $_GET['arama'] ?? '';

// Öğrencileri filtrele
$sql = "SELECT o.*,
        (SELECT COUNT(*) FROM namaz_kayitlari WHERE ogrenci_id = o.id) as toplam_namaz,
        (SELECT COUNT(*) FROM ilave_puanlar WHERE ogrenci_id = o.id) as ilave_puan_sayisi
        FROM ogrenciler o WHERE 1=1";

$params = [];

if($filtre === 'aktif') {
    $sql .= " AND o.aktif = 1";
} elseif($filtre === 'pasif') {
    $sql .= " AND o.aktif = 0";
}

if($arama) {
    $sql .= " AND o.ad_soyad LIKE ?";
    $params[] = "%$arama%";
}

$sql .= " ORDER BY o.ad_soyad";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ogrenciler = $stmt->fetchAll();

// İstatistikler
$istatistikler = $pdo->query("
    SELECT
        COUNT(*) as toplam,
        SUM(CASE WHEN aktif = 1 THEN 1 ELSE 0 END) as aktif_sayi,
        SUM(CASE WHEN aktif = 0 THEN 1 ELSE 0 END) as pasif_sayi
    FROM ogrenciler
")->fetch();

$aktif_sayfa = 'ogrenciler';
$sayfa_basligi = 'Öğrenci Listesi - Cami Namaz Takip';
require_once 'config/header.php';
?>
        <style>
            .filter-bar {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                display: flex;
                gap: 15px;
                align-items: center;
                flex-wrap: wrap;
            }

            .filter-btn {
                padding: 10px 20px;
                border: 2px solid #e1e8ed;
                border-radius: 20px;
                background: white;
                cursor: pointer;
                transition: all 0.3s;
                text-decoration: none;
                color: #333;
                font-weight: 500;
            }

            .filter-btn.active {
                background: #667eea;
                color: white;
                border-color: #667eea;
            }

            .filter-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }

            .search-box {
                flex: 1;
                min-width: 250px;
            }

            .search-box input {
                width: 100%;
                padding: 10px 15px;
                border: 2px solid #e1e8ed;
                border-radius: 20px;
                font-size: 14px;
            }

            .stats-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
            }

            .stat-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
            }

            .stat-card h3 {
                margin: 0 0 10px 0;
                font-size: 2em;
            }

            .stat-card p {
                margin: 0;
                opacity: 0.9;
            }

            .action-buttons {
                display: flex;
                gap: 5px;
            }

            .btn-sm {
                padding: 5px 10px;
                font-size: 12px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s;
            }

            .btn-edit {
                background: #ffc107;
                color: #000;
            }

            .btn-delete {
                background: #dc3545;
                color: white;
            }

            .btn-sm:hover {
                transform: translateY(-2px);
                box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            }

            .status-badge {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }

            .status-aktif {
                background: #d4edda;
                color: #155724;
            }

            .status-pasif {
                background: #f8d7da;
                color: #721c24;
            }
        </style>

        <div style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">👥 Öğrenci Listesi</h2>
                <a href="ogrenci-ekle.php" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(102,126,234,0.3);">
                    ➕ Yeni Öğrenci Ekle
                </a>
            </div>

            <div class="stats-cards">
                <div class="stat-card">
                    <h3><?php echo $istatistikler['toplam']; ?></h3>
                    <p>Toplam Öğrenci</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h3><?php echo $istatistikler['aktif_sayi']; ?></h3>
                    <p>Aktif Öğrenci</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                    <h3><?php echo $istatistikler['pasif_sayi']; ?></h3>
                    <p>Pasif Öğrenci</p>
                </div>
            </div>

            <div class="filter-bar">
                <a href="?filtre=tumu" class="filter-btn <?php echo $filtre === 'tumu' ? 'active' : ''; ?>">
                    📋 Tümü
                </a>
                <a href="?filtre=aktif" class="filter-btn <?php echo $filtre === 'aktif' ? 'active' : ''; ?>">
                    ✅ Aktif
                </a>
                <a href="?filtre=pasif" class="filter-btn <?php echo $filtre === 'pasif' ? 'active' : ''; ?>">
                    ⏸️ Pasif
                </a>

                <div class="search-box">
                    <form method="GET" action="">
                        <input type="hidden" name="filtre" value="<?php echo $filtre; ?>">
                        <input type="text" name="arama" placeholder="🔍 Öğrenci ara..." value="<?php echo htmlspecialchars($arama); ?>">
                    </form>
                </div>
            </div>

            <?php if(count($ogrenciler) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Durum</th>
                        <th>Ad Soyad</th>
                        <th>Yaş</th>
                        <th>Baba Adı</th>
                        <th>Anne Adı</th>
                        <th>Toplam Namaz</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ogrenciler as $ogrenci): ?>
                    <tr>
                        <td>
                            <span class="status-badge <?php echo $ogrenci['aktif'] ? 'status-aktif' : 'status-pasif'; ?>">
                                <?php echo $ogrenci['aktif'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($ogrenci['ad_soyad']); ?></td>
                        <td><?php echo yasHesapla($ogrenci['dogum_tarihi']); ?></td>
                        <td><?php echo htmlspecialchars($ogrenci['baba_adi']); ?></td>
                        <td><?php echo htmlspecialchars($ogrenci['anne_adi']); ?></td>
                        <td><strong><?php echo $ogrenci['toplam_namaz']; ?></strong></td>
                        <td>
                            <div class="action-buttons">
                                <button onclick="ogrenciDetay(<?php echo $ogrenci['id']; ?>)" class="btn-sm" style="background: #17a2b8; color: white;">👁️ Görüntüle</button>
                                <a href="ogrenci-duzenle.php?id=<?php echo $ogrenci['id']; ?>" class="btn-sm btn-edit">✏️ Düzenle</a>
                                <a href="ogrenci-dersler.php?id=<?php echo $ogrenci['id']; ?>" class="btn-sm" style="background: #28a745; color: white;">📚 Dersler</a>
                                <a href="ozel-rapor.php?id=<?php echo $ogrenci['id']; ?>" class="btn-sm" style="background: #007bff; color: white;">🕌 Namaz Raporu</a>
                                <a href="donem-rapor.php?id=<?php echo $ogrenci['id']; ?>" class="btn-sm" style="background: #6f42c1; color: white;">📚 Ders Raporu</a>
                                <button onclick="sifreSifirla(<?php echo $ogrenci['id']; ?>, '<?php echo htmlspecialchars($ogrenci['ad_soyad']); ?>')" class="btn-sm" style="background: #ffc107; color: #000;">🔒 Şifre Sıfırla</button>
                                <button onclick="ogrenciSil(<?php echo $ogrenci['id']; ?>, '<?php echo htmlspecialchars($ogrenci['ad_soyad']); ?>', <?php echo $ogrenci['aktif']; ?>)" class="btn-sm btn-delete">🗑️ Sil</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="alert info">
                Bu filtrede öğrenci bulunamadı.
            </div>
            <?php endif; ?>
        </div>
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

        function ogrenciSil(id, adSoyad, aktif) {
            // Eğer öğrenci zaten pasifse, direkt tamamen silme seçeneği sun
            if(aktif == 0) {
                if(confirm('⚠️ UYARI: ' + adSoyad + ' zaten pasif durumda.\n\nÖğrenciyi TAMAMEN silmek istiyor musunuz?\n\nBu işlem:\n- Tüm namaz kayıtlarını\n- Tüm ders kayıtlarını\n- Tüm sertifikaları\n- Tüm ilave puanları\nKALICI OLARAK SİLECEKTİR!\n\nBu işlem GERİ ALINAMAZ!')) {
                    fetch('api/ogrenci-sil.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'ogrenci_id=' + id + '&tamamen_sil=true'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            alert('✅ ' + data.message);
                            location.reload();
                        } else {
                            alert('❌ ' + data.message);
                        }
                    });
                }
            } else {
                // Aktif öğrenci için önce pasif etme seçeneği sun
                if(confirm('❓ ' + adSoyad + ' isimli öğrenciyi silmek istediğinize emin misiniz?\n\n⚠️ Bu işlem öğrenciyi pasif duruma getirecektir. Tamamen silmek için "İptal" sonrası "Tamamen Sil" seçeneğini kullanın.')) {
                    fetch('api/ogrenci-sil.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'ogrenci_id=' + id
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            alert('✅ ' + data.message);
                            location.reload();
                        } else {
                            alert('❌ ' + data.message);
                        }
                    });
                } else {
                    if(confirm('⚠️ UYARI: Öğrenciyi TAMAMEN silmek istiyor musunuz?\n\nBu işlem:\n- Tüm namaz kayıtlarını\n- Tüm ders kayıtlarını\n- Tüm sertifikaları\n- Tüm ilave puanları\nKALICI OLARAK SİLECEKTİR!\n\nBu işlem GERİ ALINAMAZ!')) {
                        fetch('api/ogrenci-sil.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'ogrenci_id=' + id + '&tamamen_sil=true'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.success) {
                                alert('✅ ' + data.message);
                                location.reload();
                            } else {
                                alert('❌ ' + data.message);
                            }
                        });
                    }
                }
            }
        }

        function sifreSifirla(id, adSoyad) {
            if(confirm('🔒 ' + adSoyad + ' için yeni şifre oluşturulsun mu?\n\nEski şifre geçersiz olacaktır.')) {
                fetch('api/sifre-sifirla.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'ogrenci_id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const message = `✅ Şifre başarıyla sıfırlandı!\n\n` +
                            `Öğrenci: ${data.ad_soyad}\n` +
                            `Kullanıcı Adı: ${data.kullanici_adi}\n` +
                            `Yeni Şifre: ${data.yeni_sifre}\n\n` +
                            `⚠️ Bu bilgileri öğrenciye iletiniz!`;
                        alert(message);

                        // Kopyala seçeneği sun
                        if(confirm('📋 Bilgileri panoya kopyalamak ister misiniz?')) {
                            const copyText = `Öğrenci: ${data.ad_soyad}\nKullanıcı Adı: ${data.kullanici_adi}\nYeni Şifre: ${data.yeni_sifre}`;
                            navigator.clipboard.writeText(copyText).then(() => {
                                alert('✅ Bilgiler kopyalandı!');
                            });
                        }
                    } else {
                        alert('❌ ' + data.message);
                    }
                });
            }
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