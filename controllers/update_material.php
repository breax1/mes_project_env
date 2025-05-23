<?php
// Veritabanı bağlantısını başlat
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materialId = $_POST['updateMaterialSelect'];
    $brandId = $_POST['updateMaterialBrand'];
    $unitId = $_POST['updateMaterialUnit'];
    $size = $_POST['updateMaterialSize'];
    $stock = $_POST['updateMaterialStock'];
    $criticalStock = $_POST['updateCriticalStock'];
    $shelfId = $_POST['updateMaterialShelf'];
    $typeId = $_POST['updateMaterialType'];
    $materialIdField = $_POST['updateMaterialMaterial'];
    $supplierId = $_POST['updateMaterialTedarikci'];

    // Önce mevcut stok miktarını al
    $currentStockQuery = "SELECT urun_stok_miktari FROM stock WHERE id = ?";
    $stmt = $baglanti->prepare($currentStockQuery);
    $stmt->bind_param("i", $materialId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentStock = 0;

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentStock = $row['urun_stok_miktari'];
    }

    // Stok değişikliğini hesapla
    $stockDifference = $stock - $currentStock;

    // Güncelleme sorgusu
    $query = "UPDATE stock SET urun_marka = ?, urun_unit = ?, urun_olcu = ?, urun_stok_miktari = ?, urun_kritik_stok = ?, shelf_id = ?, urun_cins = ?, urun_materyal = ?, urun_supplier = ? WHERE id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("iisiiiiiii", $brandId, $unitId, $size, $stock, $criticalStock, $shelfId, $typeId, $materialIdField, $supplierId, $materialId);

    if ($stmt->execute()) {
        // Stock log tablosuna kayıt ekle
        $logQuery = "INSERT INTO stock_log (islem, user_id, stock_id, eklenen_stok_miktari, created_at) VALUES (?, ?, ?, ?, NOW())";
        $logStmt = $baglanti->prepare($logQuery);
        $islem = "guncelleme";
        $userId = $_SESSION['user_id'];
        $logStmt->bind_param("siii", $islem, $userId, $materialId, $stockDifference);

        if ($logStmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Log kaydı başarısız.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Güncelleme başarısız.']);
    }
}
?>