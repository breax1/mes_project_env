<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

if (isset($_GET['urunAdi'])) {
    $urunAdi = $_GET['urunAdi'];
    
    $query = "SELECT shelf_id, urun_kritik_stok FROM stock WHERE urun_adi = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("s", $urunAdi);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'exists' => true,
            'urunRafi' => $row['shelf_id'],
            'urunKritikStok' => $row['urun_kritik_stok']
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }

    $stmt->close();
}
?>
