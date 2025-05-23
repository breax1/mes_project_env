<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

// Filtreleme parametrelerini al
$filterAd = $_GET['filterAd'] ?? '';
$filterRaf = $_GET['filterRaf'] ?? '';
$filterMarka = $_GET['filterMarka'] ?? '';
$filterCins = $_GET['filterCins'] ?? '';
$filterOlcu = $_GET['filterOlcu'] ?? '';
$filterMateryal = $_GET['filterMateryal'] ?? '';


// SQL sorgusunu oluştur
$query = "SELECT
            s.id AS id,
            s.urun_adi AS ad, 
            sh.shelf_name AS raf, 
            sm.marka_adi AS marka, -- stock_marka tablosundan marka_adi çekiliyor
            c.cins_adi AS cins, 
            s.urun_olcu AS olcu, 
            m.materyal_adi AS materyal, 
            s.urun_stok_miktari AS stok_miktari 
          FROM stock s
          LEFT JOIN stock_shelf sh ON s.shelf_id = sh.id
          LEFT JOIN stock_cins c ON s.urun_cins = c.id
          LEFT JOIN stock_materyal m ON s.urun_materyal = m.id
          LEFT JOIN stock_marka sm ON s.urun_marka = sm.id -- stock_marka tablosu ile ilişkilendirildi
          WHERE 1=1";

if (!empty($filterAd)) {
    $query .= " AND s.urun_adi LIKE '%$filterAd%'";
}
if (!empty($filterRaf)) {
    $query .= " AND s.shelf_id = '$filterRaf'";
}
if (!empty($filterMarka)) {
    $query .= " AND s.urun_marka LIKE '%$filterMarka%'";
}
if (!empty($filterCins)) {
    $query .= " AND s.urun_cins = '$filterCins'";
}
if (!empty($filterOlcu)) {
    $query .= " AND s.urun_olcu LIKE '%$filterOlcu%'";
}
if (!empty($filterMateryal)) {
    $query .= " AND s.urun_materyal = '$filterMateryal'";
}

// Sorguyu çalıştır
$result = $baglanti->query($query);

// Sonuçları döndür
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr data-id='{$row['id']}'>
                <td>{$row['ad']}</td>
                <td>{$row['raf']}</td>
                <td>{$row['marka']}</td>
                <td>{$row['cins']}</td>
                <td>{$row['olcu']}</td>
                <td>{$row['materyal']}</td>
                <td>{$row['stok_miktari']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7'>Sonuç bulunamadı.</td></tr>";
}
?>