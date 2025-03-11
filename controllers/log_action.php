<?php
session_start();
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Verilerin varlığını ve geçerliliğini kontrol et
    if (isset($data['action'], $data['description']) && !empty($data['action']) && !empty($data['description'])) {
        $action = $data['action'];
        $description = $data['description'];
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        // SQL enjeksiyonuna karşı hazırlıklı sorgu kullan
        $logQuery = "INSERT INTO logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())";
        $logStmt = $baglanti->prepare($logQuery);
        $logStmt->bind_param("iss", $user_id, $action, $description);

        if ($logStmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Log err: ' . $logStmt->error]);
        }

        $logStmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$baglanti->close();
?>