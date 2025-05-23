<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['materialName'];
    $brand = $_POST['materialBrand'];
    $unit = $_POST['materialUnit'];
    $size = $_POST['materialSize'];
    $stock = $_POST['materialStock'];
    $criticalStock = $_POST['criticalStock'];
    $shelfId = $_POST['materialShelf'];
    $typeId = $_POST['materialType'];
    $materialId = $_POST['materialMaterial'];
    $tedarikciId = $_POST['materialTedarikci'];

    $query = "INSERT INTO stock (urun_adi, urun_marka, urun_unit, urun_olcu, urun_stok_miktari, urun_kritik_stok, shelf_id, urun_cins, urun_materyal, urun_supplier) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("ssssiiiiii", $name, $brand, $unit, $size, $stock, $criticalStock, $shelfId, $typeId, $materialId, $tedarikciId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Malzeme eklenemedi.']);
    }
}
?>