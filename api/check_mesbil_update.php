<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Veritabanı bağlantısını dahil et
include("../includes/db_config.php");

try {
    // Sorguyu çalıştır
    $sql = "SELECT value FROM controllers WHERE type = ?";
    $stmt = $conn->prepare($sql); // $conn, db_config.php'den gelen mysqli bağlantısı
    $type = 'mesbil_data';
    $stmt->bind_param("s", $type); // "s" string türünde parametre
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode(['status' => 'success', 'value' => $row['value']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No matching record found']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>