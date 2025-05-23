<?php
include("../includes/db_config.php");

if (isset($_GET['id'])) {
    $materialId = $_GET['id'];

    $query = "SELECT * FROM stock WHERE id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $materialId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $material = $result->fetch_assoc();
        echo json_encode($material);
    } else {
        echo json_encode(['error' => 'Malzeme bulunamadı.']);
    }
}
?>