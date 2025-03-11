<?php
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT * FROM personnel WHERE id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $personnel = $result->fetch_assoc();
        echo json_encode($personnel);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Personel bulunamadı.']);
    }

    $stmt->close();
    $baglanti->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
}
?>