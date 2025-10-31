<?php
require_once 'config/auth.php';
checkAuth();
require_once 'config/db.php';

$id = $_GET['id'] ?? 0;

// Sertifika bilgilerini al
$stmt = $pdo->prepare("
    SELECT s.*, o.ad_soyad, o.dogum_tarihi, o.baba_adi, o.anne_adi
    FROM sertifikalar s
    JOIN ogrenciler o ON s.ogrenci_id = o.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$sertifika = $stmt->fetch();

if (!$sertifika) {
    die("Sertifika bulunamadı!");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifika - <?php echo $sertifika['ad_soyad']; ?></title>
    <style>
        @media print {
            .no-print {
                display: none;
            }
            @page {
                size: A4 landscape;
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #f5f5f5;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .certificate-container {
            width: 297mm;
            height: 210mm;
            background: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 50px rgba(0,0,0,0.2);
        }

        .certificate-border {
            position: absolute;
            top: 15mm;
            left: 15mm;
            right: 15mm;
            bottom: 15mm;
            border: 8px double #c19a6b;
            padding: 10mm;
        }

        .certificate-inner-border {
            position: absolute;
            top: 20mm;
            left: 20mm;
            right: 20mm;
            bottom: 20mm;
            border: 2px solid #c19a6b;
        }

        .ornament-corner {
            position: absolute;
            width: 60px;
            height: 60px;
            border: 3px solid #c19a6b;
        }

        .ornament-top-left {
            top: 25mm;
            left: 25mm;
            border-right: none;
            border-bottom: none;
        }

        .ornament-top-right {
            top: 25mm;
            right: 25mm;
            border-left: none;
            border-bottom: none;
        }

        .ornament-bottom-left {
            bottom: 25mm;
            left: 25mm;
            border-right: none;
            border-top: none;
        }

        .ornament-bottom-right {
            bottom: 25mm;
            right: 25mm;
            border-left: none;
            border-top: none;
        }

        .certificate-content {
            position: relative;
            z-index: 10;
            padding: 40mm 30mm;
            text-align: center;
        }

        .certificate-header {
            margin-bottom: 15mm;
        }

        .certificate-title {
            font-size: 36px;
            color: #c19a6b;
            text-transform: uppercase;
            letter-spacing: 6px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .certificate-body {
            margin: 20mm 0;
        }


        .recipient-name {
            font-size: 42px;
            color: #2c3e50;
            font-weight: bold;
            margin: 15px 0;
            text-decoration: underline;
            text-decoration-color: #c19a6b;
            text-underline-offset: 8px;
        }

        .achievement {
            font-size: 18px;
            color: #34495e;
            line-height: 1.8;
            margin: 20px auto;
            max-width: 600px;
        }

        .achievement strong {
            color: #c19a6b;
            font-size: 20px;
        }

        .period {
            font-size: 16px;
            color: #7f8c8d;
            margin: 15px 0;
            font-style: italic;
        }

        .degree {
            font-size: 22px;
            color: #c19a6b;
            font-weight: bold;
            margin: 10px 0;
        }

        .description {
            font-size: 14px;
            color: #7f8c8d;
            margin: 15px auto;
            max-width: 500px;
            font-style: italic;
        }

        .certificate-footer {
            margin-top: 20mm;
            display: flex;
            justify-content: space-around;
            padding: 0 50px;
        }

        .signature-block {
            text-align: center;
            flex: 1;
        }

        .signature-line {
            width: 200px;
            border-top: 2px solid #2c3e50;
            margin: 40px auto 10px;
        }

        .signature-title {
            font-size: 14px;
            color: #7f8c8d;
            font-style: italic;
        }


        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #3498db;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }

        .print-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .close-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #95a5a6;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
            text-decoration: none;
        }

        .close-button:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(193, 154, 107, 0.05);
            z-index: 1;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Yazdır</button>
    <a href="sertifikalar.php" class="close-button no-print">← Geri Dön</a>

    <div class="certificate-container">
        <div class="certificate-border"></div>
        <div class="certificate-inner-border"></div>

        <div class="ornament-corner ornament-top-left"></div>
        <div class="ornament-corner ornament-top-right"></div>
        <div class="ornament-corner ornament-bottom-left"></div>
        <div class="ornament-corner ornament-bottom-right"></div>

        <div class="certificate-content">
            <div class="certificate-header">
                <div class="certificate-title">ATAKÖY CAMİİ</div>
            </div>

            <div class="certificate-body">
                <div class="recipient-name"><?php echo htmlspecialchars($sertifika['ad_soyad']); ?></div>

                <div class="achievement">
                    <strong><?php echo htmlspecialchars($sertifika['baslik']); ?></strong>
                </div>

                <?php if ($sertifika['donem']): ?>
                <div class="period">📅 <?php echo htmlspecialchars($sertifika['donem']); ?></div>
                <?php endif; ?>

                <?php if ($sertifika['derece']): ?>
                <div class="degree">🏆 <?php echo htmlspecialchars($sertifika['derece']); ?></div>
                <?php endif; ?>

                <?php if ($sertifika['aciklama']): ?>
                <div class="description"><?php echo nl2br(htmlspecialchars($sertifika['aciklama'])); ?></div>
                <?php endif; ?>
            </div>

            <div class="certificate-footer">
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-title" style="font-weight: bold; font-size: 16px; color: #2c3e50; margin-top: 15px;">MEHMET TÜZÜN</div>
                    <div class="signature-title" style="font-size: 13px; margin-top: 5px;">ATAKÖY CAMİİ İMAM-HATİBİ</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
