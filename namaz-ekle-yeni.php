<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$bugun = date('Y-m-d');
$mesaj = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['namaz_kaydet'])) {
    $ogrenci_id = $_POST['ogrenci_id'];
    $namaz_vakti = $_POST['namaz_vakti'];
    $tarih = $_POST['tarih'] ?: $bugun;
    $kiminle_geldi_secimler = $_POST['kiminle_geldi'] ?? [];
    
    if(!empty($kiminle_geldi_secimler)) {
        foreach($kiminle_geldi_secimler as $kiminle) {
            $stmt = $pdo->prepare("INSERT INTO namaz_kayitlari (ogrenci_id, namaz_vakti, kiminle_geldi, tarih) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ogrenci_id, $namaz_vakti, $kiminle, $tarih]);
        }
        
        $ogrenci_stmt = $pdo->prepare("SELECT ad_soyad FROM ogrenciler WHERE id = ?");
        $ogrenci_stmt->execute([$ogrenci_id]);
        $ogrenci = $ogrenci_stmt->fetch();
        
        $kayit_sayisi = count($kiminle_geldi_secimler);
        $mesaj = $ogrenci['ad_soyad'] . " için $namaz_vakti namazı ($kayit_sayisi kayıt) başarıyla eklendi!";
    }
}

$bugunKayitlar = $pdo->prepare("
    SELECT o.ad_soyad, n.namaz_vakti, n.kiminle_geldi, n.saat 
    FROM namaz_kayitlari n 
    JOIN ogrenciler o ON n.ogrenci_id = o.id 
    WHERE n.tarih = ? 
    ORDER BY n.saat DESC 
    LIMIT 20
");
$bugunKayitlar->execute([$bugun]);
$kayitlar = $bugunKayitlar->fetchAll();

$aktif_sayfa = 'namaz';
$sayfa_basligi = 'Namaz Ekle - Cami Namaz Takip';
require_once 'config/header.php';
?>
    <style>
        .wizard-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .wizard-step {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .wizard-step.active {
            display: block;
        }
        
        .step-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .step-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .arama-kutusu {
            position: relative;
            margin-bottom: 20px;
        }
        
        .arama-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 16px;
        }
        
        .arama-sonuclari {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e1e8ed;
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .ogrenci-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .ogrenci-item:hover {
            background: #f8f9fa;
        }
        
        .ogrenci-item:last-child {
            border-bottom: none;
        }
        
        .secili-ogrenci {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 2px solid #28a745;
        }
        
        .secili-ogrenci h3 {
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .kiminle-secenekleri {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .kiminle-secenegi {
            background: #f8f9fa;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .kiminle-secenegi:hover {
            border-color: #667eea;
        }
        
        .kiminle-secenegi.secili {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .kiminle-secenegi input[type="checkbox"] {
            display: none;
        }
        
        .wizard-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }
        
        .btn-wizard {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-geri {
            background: #6c757d;
            color: white;
        }
        
        .btn-devam {
            background: #28a745;
            color: white;
        }
        
        .btn-kaydet {
            background: #667eea;
            color: white;
        }
        
        .btn-wizard:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-wizard:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
    </style>
    <div class="wizard-container">
            <?php if($mesaj): ?>
            <div class="alert success"><?php echo $mesaj; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="namazForm">
                <!-- Adım 1: Öğrenci Seçimi -->
                <div class="wizard-step active" id="step1">
                    <div class="step-header">
                        <div class="step-number">1</div>
                        <h2>👤 Öğrenci Seçin</h2>
                        <p>Namaz kılan öğrenciyi arayın ve seçin</p>
                    </div>
                    
                    <div class="arama-kutusu">
                        <input type="text" id="ogrenciArama" class="arama-input" 
                               placeholder="Öğrenci adını yazın..." autocomplete="off">
                        <div class="arama-sonuclari" id="aramaSonuclari"></div>
                    </div>
                    
                    <div id="seciliOgrenciDiv" style="display: none;">
                        <div class="secili-ogrenci">
                            <h3>✅ Seçilen Öğrenci</h3>
                            <div id="seciliOgrenciBilgi"></div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="selectedOgrenciId" name="ogrenci_id">
                    
                    <div class="wizard-navigation">
                        <div></div>
                        <button type="button" class="btn-wizard btn-devam" onclick="nextStep(1)" disabled id="step1Next">
                            Devam Et →
                        </button>
                    </div>
                </div>
                
                <!-- Adım 2: Namaz Vakti ve Tarih -->
                <div class="wizard-step" id="step2">
                    <div class="step-header">
                        <div class="step-number">2</div>
                        <h2>🕌 Namaz Vakti ve Tarih</h2>
                        <p>Namaz vaktini ve tarihini seçin</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Namaz Vakti:</label>
                        <select name="namaz_vakti" id="namazVakti" required>
                            <option value="">Vakit Seçin</option>
                            <option value="Sabah">Sabah</option>
                            <option value="Öğlen">Öğlen</option>
                            <option value="İkindi">İkindi</option>
                            <option value="Akşam">Akşam</option>
                            <option value="Yatsı">Yatsı</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tarih:</label>
                        <input type="date" name="tarih" id="namazTarihi" value="<?php echo $bugun; ?>">
                    </div>
                    
                    <div class="wizard-navigation">
                        <button type="button" class="btn-wizard btn-geri" onclick="prevStep(2)">
                            ← Geri
                        </button>
                        <button type="button" class="btn-wizard btn-devam" onclick="nextStep(2)" disabled id="step2Next">
                            Devam Et →
                        </button>
                    </div>
                </div>
                
                <!-- Adım 3: Kiminle Geldi -->
                <div class="wizard-step" id="step3">
                    <div class="step-header">
                        <div class="step-number">3</div>
                        <h2>👨‍👩‍👧‍👦 Kiminle Geldi?</h2>
                        <p>Öğrenci namaza kiminle geldi? (Birden fazla seçebilirsiniz)</p>
                    </div>
                    
                    <div id="secilenOgrenciInfo"></div>
                    
                    <div class="kiminle-secenekleri">
                        <label class="kiminle-secenegi" for="kendisi">
                            <input type="checkbox" id="kendisi" name="kiminle_geldi[]" value="Kendisi" checked>
                            <div>
                                <h4>🧒 Kendisi</h4>
                                <p>Öğrenci tek başına geldi</p>
                            </div>
                        </label>
                        
                        <label class="kiminle-secenegi" for="babasi">
                            <input type="checkbox" id="babasi" name="kiminle_geldi[]" value="Babası">
                            <div>
                                <h4>👨 Babası</h4>
                                <p>Babası ile birlikte geldi</p>
                            </div>
                        </label>
                        
                        <label class="kiminle-secenegi" for="annesi">
                            <input type="checkbox" id="annesi" name="kiminle_geldi[]" value="Annesi">
                            <div>
                                <h4>👩 Annesi</h4>
                                <p>Annesi ile birlikte geldi</p>
                            </div>
                        </label>
                        
                        <label class="kiminle-secenegi" for="anne_babasi">
                            <input type="checkbox" id="anne_babasi" name="kiminle_geldi[]" value="Anne-Babası">
                            <div>
                                <h4>👨‍👩 Anne-Babası</h4>
                                <p>Anne ve babası ile geldi</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="alert info" id="puanBilgisi">
                        <strong>💡 Puanlama:</strong> Her seçim için ayrı kayıt oluşturulacak ve öğrenci o kadar puan alacak.
                    </div>
                    
                    <div class="wizard-navigation">
                        <button type="button" class="btn-wizard btn-geri" onclick="prevStep(3)">
                            ← Geri
                        </button>
                        <button type="submit" name="namaz_kaydet" class="btn-wizard btn-kaydet" id="kaydetBtn">
                            💾 Kaydı Tamamla
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="bugun-kayitlar">
                <h3>📋 Bugünün Son Kayıtları</h3>
                <?php if(count($kayitlar) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Saat</th>
                            <th>Öğrenci</th>
                            <th>Vakit</th>
                            <th>Kiminle Geldi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($kayitlar as $kayit): ?>
                        <tr>
                            <td><?php echo date('H:i', strtotime($kayit['saat'])); ?></td>
                            <td><?php echo $kayit['ad_soyad']; ?></td>
                            <td><?php echo $kayit['namaz_vakti']; ?></td>
                            <td><?php echo $kayit['kiminle_geldi']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Bugün henüz kayıt yok.</p>
                <?php endif; ?>
            </div>
        </div>

    <script>
        let aramaTimeout;
        let selectedOgrenci = null;
        
        // Öğrenci arama
        document.getElementById('ogrenciArama').addEventListener('input', function() {
            clearTimeout(aramaTimeout);
            const query = this.value;
            
            if(query.length < 2) {
                document.getElementById('aramaSonuclari').style.display = 'none';
                return;
            }
            
            aramaTimeout = setTimeout(() => {
                fetch(`api/ogrenci-ara.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        showSearchResults(data.ogrenciler);
                    });
            }, 300);
        });
        
        function showSearchResults(ogrenciler) {
            const container = document.getElementById('aramaSonuclari');
            
            if(ogrenciler.length === 0) {
                container.style.display = 'none';
                return;
            }
            
            let html = '';
            ogrenciler.forEach(ogrenci => {
                html += `
                    <div class="ogrenci-item" onclick="selectOgrenci(${ogrenci.id}, '${ogrenci.ad_soyad}', '${ogrenci.baba_adi}', '${ogrenci.anne_adi}', ${ogrenci.yas})">
                        <strong>${ogrenci.ad_soyad}</strong><br>
                        <small>Yaş: ${ogrenci.yas} | Baba: ${ogrenci.baba_adi || '-'} | Anne: ${ogrenci.anne_adi || '-'}</small>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            container.style.display = 'block';
        }
        
        function selectOgrenci(id, adSoyad, babaAdi, anneAdi, yas) {
            selectedOgrenci = {id, adSoyad, babaAdi, anneAdi, yas};
            
            document.getElementById('selectedOgrenciId').value = id;
            document.getElementById('ogrenciArama').value = adSoyad;
            document.getElementById('aramaSonuclari').style.display = 'none';
            
            document.getElementById('seciliOgrenciBilgi').innerHTML = `
                <strong>${adSoyad}</strong><br>
                <small>Yaş: ${yas} | Baba: ${babaAdi || '-'} | Anne: ${anneAdi || '-'}</small>
            `;
            
            document.getElementById('seciliOgrenciDiv').style.display = 'block';
            document.getElementById('step1Next').disabled = false;
        }
        
        // Namaz vakti seçimi
        document.getElementById('namazVakti').addEventListener('change', function() {
            document.getElementById('step2Next').disabled = this.value === '';
        });
        
        // Wizard navigation
        function nextStep(currentStep) {
            if(currentStep === 1 && !selectedOgrenci) return;
            if(currentStep === 2 && !document.getElementById('namazVakti').value) return;
            
            document.getElementById(`step${currentStep}`).classList.remove('active');
            document.getElementById(`step${currentStep + 1}`).classList.add('active');
            
            if(currentStep === 2) {
                updateStep3Info();
            }
        }
        
        function prevStep(currentStep) {
            document.getElementById(`step${currentStep}`).classList.remove('active');
            document.getElementById(`step${currentStep - 1}`).classList.add('active');
        }
        
        function updateStep3Info() {
            const vakit = document.getElementById('namazVakti').value;
            const tarih = document.getElementById('namazTarihi').value;
            
            document.getElementById('secilenOgrenciInfo').innerHTML = `
                <div class="secili-ogrenci">
                    <h4>${selectedOgrenci.adSoyad}</h4>
                    <p><strong>Namaz:</strong> ${vakit} | <strong>Tarih:</strong> ${tarih}</p>
                </div>
            `;
        }
        
        // Kiminle geldi seçimleri
        document.querySelectorAll('input[name="kiminle_geldi[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const label = this.closest('.kiminle-secenegi');
                if(this.checked) {
                    label.classList.add('secili');
                } else {
                    label.classList.remove('secili');
                }
                
                updatePuanBilgisi();
            });
        });
        
        function updatePuanBilgisi() {
            const checkedBoxes = document.querySelectorAll('input[name="kiminle_geldi[]"]:checked');
            const sayisi = checkedBoxes.length;
            
            document.getElementById('puanBilgisi').innerHTML = `
                <strong>💡 Puanlama:</strong> ${sayisi} ayrı kayıt oluşturulacak ve öğrenci ${sayisi} puan alacak.
            `;
        }
        
        // İlk yüklemede kendisi seçili olsun
        document.getElementById('kendisi').closest('.kiminle-secenegi').classList.add('secili');
        
        // Sayfa dışına tıklanınca arama sonuçlarını gizle
        document.addEventListener('click', function(e) {
            if(!e.target.closest('.arama-kutusu')) {
                document.getElementById('aramaSonuclari').style.display = 'none';
            }
        });
    </script>
<?php require_once 'config/footer.php'; ?>