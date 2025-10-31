-- Migration v2.2: VIEW'lara toplam_puan kolonu ekleme
-- Tarih: 2025-10-31
-- Açıklama: yillik_ozetler ve aylik_ozetler VIEW'larına toplam puan hesaplaması ekleniyor

-- Yıllık özetler VIEW'ını güncelle
DROP VIEW IF EXISTS yillik_ozetler;

CREATE VIEW yillik_ozetler AS
SELECT
    temp.ogrenci_id,
    temp.ad_soyad,
    temp.yil,
    temp.kendisi_sayisi,
    temp.babasi_sayisi,
    temp.annesi_sayisi,
    temp.anne_babasi_sayisi,
    temp.toplam_namaz,
    (
        temp.toplam_namaz +
        COALESCE((
            SELECT SUM(ip.puan)
            FROM ilave_puanlar ip
            WHERE ip.ogrenci_id = temp.ogrenci_id
            AND YEAR(ip.tarih) = temp.yil
            AND ip.kategori = 'Namaz'
        ), 0)
    ) AS toplam_puan
FROM (
    SELECT
        o.id AS ogrenci_id,
        o.ad_soyad,
        YEAR(n.tarih) AS yil,
        SUM(CASE WHEN n.kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END) AS kendisi_sayisi,
        SUM(CASE WHEN n.kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) AS babasi_sayisi,
        SUM(CASE WHEN n.kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) AS annesi_sayisi,
        SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) AS anne_babasi_sayisi,
        COUNT(n.id) AS toplam_namaz
    FROM ogrenciler o
    LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id
    GROUP BY o.id, YEAR(n.tarih)
) AS temp;

-- Aylık özetler VIEW'ını güncelle
DROP VIEW IF EXISTS aylik_ozetler;

CREATE VIEW aylik_ozetler AS
SELECT
    temp.ogrenci_id,
    temp.ad_soyad,
    temp.yil,
    temp.ay,
    temp.kendisi_sayisi,
    temp.babasi_sayisi,
    temp.annesi_sayisi,
    temp.anne_babasi_sayisi,
    temp.toplam_namaz,
    (
        temp.toplam_namaz +
        COALESCE((
            SELECT SUM(ip.puan)
            FROM ilave_puanlar ip
            WHERE ip.ogrenci_id = temp.ogrenci_id
            AND YEAR(ip.tarih) = temp.yil
            AND MONTH(ip.tarih) = temp.ay
            AND ip.kategori = 'Namaz'
        ), 0)
    ) AS toplam_puan
FROM (
    SELECT
        o.id AS ogrenci_id,
        o.ad_soyad,
        YEAR(n.tarih) AS yil,
        MONTH(n.tarih) AS ay,
        SUM(CASE WHEN n.kiminle_geldi = 'Kendisi' THEN 1 ELSE 0 END) AS kendisi_sayisi,
        SUM(CASE WHEN n.kiminle_geldi = 'Babası' THEN 1 ELSE 0 END) AS babasi_sayisi,
        SUM(CASE WHEN n.kiminle_geldi = 'Annesi' THEN 1 ELSE 0 END) AS annesi_sayisi,
        SUM(CASE WHEN n.kiminle_geldi = 'Anne-Babası' THEN 1 ELSE 0 END) AS anne_babasi_sayisi,
        COUNT(n.id) AS toplam_namaz
    FROM ogrenciler o
    LEFT JOIN namaz_kayitlari n ON o.id = n.ogrenci_id
    GROUP BY o.id, YEAR(n.tarih), MONTH(n.tarih)
) AS temp;
