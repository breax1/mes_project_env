<?php
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $value = $_POST['value'];

    $query = "INSERT INTO stock_materyal (materyal_adi) VALUES (?)";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("s", $value);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Materyal başarıyla eklendi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Materyal eklenemedi.']);
    }
}
?>