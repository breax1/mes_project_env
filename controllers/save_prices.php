<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

$response = array('status' => '', 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kesifId = $_POST['kesif_id'];
    $prices = $_POST['prices'];

    foreach ($prices as $price) {
        $rawMaterialId = $price['raw_material_id'];
        $priceValue = $price['price'];

        // Fiyatı kaydet
        $query = "INSERT INTO raw_material_prices (kesif_raw_material_id, price) VALUES (?, ?)";
        $stmt = $baglanti->prepare($query);
        $stmt->bind_param("id", $rawMaterialId, $priceValue);
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Fiyatlar başarıyla kaydedildi!';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Fiyatlar kaydedilirken bir hata oluştu: ' . $stmt->error;
            echo json_encode($response);
            exit;
        }
        $stmt->close();
    }

    $baglanti->close();
    echo json_encode($response);
}
?>