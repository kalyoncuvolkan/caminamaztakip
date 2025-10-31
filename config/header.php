<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="googlebot" content="noindex, nofollow">
    <meta name="bingbot" content="noindex, nofollow">
    <title><?php echo $sayfa_basligi ?? 'Cami Namaz Takip Programı'; ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Mobil uyumlu navigasyon */
        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            padding: 15px;
        }

        nav a {
            padding: 10px 15px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 14px;
            white-space: nowrap;
        }

        nav a:hover {
            background: rgba(255,255,255,0.35);
            transform: translateY(-2px);
        }

        nav a.active {
            background: rgba(255,255,255,0.4);
            font-weight: bold;
        }

        .menu-toggle {
            display: none;
            background: rgba(255,255,255,0.3);
            border: none;
            color: white;
            padding: 10px 15px;
            font-size: 20px;
            cursor: pointer;
            border-radius: 8px;
            margin-left: auto;
        }

        @media (max-width: 768px) {
            nav {
                position: relative;
            }

            .menu-toggle {
                display: block;
                order: 1;
            }

            .nav-links {
                display: none;
                width: 100%;
                flex-direction: column;
                order: 2;
            }

            .nav-links.active {
                display: flex;
            }

            nav a {
                width: 100%;
                text-align: center;
            }

            .logout-link {
                order: 3;
                width: 100%;
            }
        }

        @media (min-width: 769px) {
            .nav-links {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                flex: 1;
            }

            .logout-link {
                margin-left: auto !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🕌 Cami Namaz Takip Programı</h1>
            <nav>
                <button class="menu-toggle" onclick="toggleMenu()">☰</button>
                <div class="nav-links" id="navLinks">
                    <a href="index.php" class="<?php echo ($aktif_sayfa ?? '') == 'index' ? 'active' : ''; ?>">🏠 Ana Sayfa</a>
                    <a href="ogrenciler.php" class="<?php echo ($aktif_sayfa ?? '') == 'ogrenciler' ? 'active' : ''; ?>">👥 Öğrenciler</a>
                    <a href="namaz-ekle-yeni.php" class="<?php echo ($aktif_sayfa ?? '') == 'namaz' ? 'active' : ''; ?>">🕌 Namaz Ekle</a>
                    <a href="puan-yonetimi.php" class="<?php echo ($aktif_sayfa ?? '') == 'puan' ? 'active' : ''; ?>">⭐ Puan Yönetimi</a>
                    <a href="ders-kategorileri.php" class="<?php echo ($aktif_sayfa ?? '') == 'dersler' ? 'active' : ''; ?>">📚 Dersler</a>
                    <a href="sertifikalar.php" class="<?php echo ($aktif_sayfa ?? '') == 'sertifikalar' ? 'active' : ''; ?>">📜 Sertifikalar</a>
                    <a href="mesajlar.php" class="<?php echo ($aktif_sayfa ?? '') == 'mesajlar' ? 'active' : ''; ?>">💬 Mesajlar</a>
                    <a href="genel-rapor.php" class="<?php echo ($aktif_sayfa ?? '') == 'raporlar' ? 'active' : ''; ?>">📊 Raporlar</a>
                    <a href="update.php" class="<?php echo ($aktif_sayfa ?? '') == 'update' ? 'active' : ''; ?>">🔄 Güncellemeler</a>
                    <a href="yedekleme.php" class="<?php echo ($aktif_sayfa ?? '') == 'yedekleme' ? 'active' : ''; ?>">💾 Yedekleme</a>
                </div>
                <a href="logout.php" class="logout-link" style="background: rgba(255,255,255,0.3);">👤 <?php echo getLoggedInUser(); ?> - Çıkış</a>
            </nav>
        </header>

        <script>
            function toggleMenu() {
                const navLinks = document.getElementById('navLinks');
                navLinks.classList.toggle('active');
            }

            // Mobilde link tıklandığında menüyü kapat
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        document.getElementById('navLinks').classList.remove('active');
                    }
                });
            });
        </script>
