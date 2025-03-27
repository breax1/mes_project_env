<?php
header('Content-Type: application/json');

// Veritabanı bağlantısını dahil et
include("../includes/db_config.php");

try {
    // POST verilerini al
    $type = $_POST['type'] ?? '';
    $value = $_POST['value'] ?? '';

    if ($type === 'mesbil_data' && $value === '0') {
        // Veritabanını güncelle
        $sql = "UPDATE controllers SET value = ? WHERE type = ?";
        $stmt = $baglanti->prepare($sql); // $baglanti, db_config.php'den gelen mysqli bağlantısı
        $stmt->bind_param("ss", $value, $type); // "ss" iki string parametre için
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Value updated to 0']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No rows were updated']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>