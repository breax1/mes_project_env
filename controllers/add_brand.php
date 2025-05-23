<?php
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $value = $_POST['value'];

    $query = "INSERT INTO stock_marka (marka_adi) VALUES (?)";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("s", $value);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Marka başarıyla eklendi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Marka eklenemedi.']);
    }
}
?>