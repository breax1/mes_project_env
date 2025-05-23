<?php
include("../includes/db_config.php");

if (isset($_GET['stock_id'])) {
    $stockId = intval($_GET['stock_id']);

    $query = "
        SELECT 
            l.id,
            l.islem,
            CONCAT(u.name, ' ', u.surname) AS user_name,
            l.eklenen_stok_miktari,
            l.created_at
        FROM stock_log l
        LEFT JOIN users u ON l.user_id = u.id
        WHERE l.stock_id = ?
        ORDER BY l.created_at DESC
        LIMIT 10
    ";

    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $stockId);
    $stmt->execute();
    $result = $stmt->get_result();

    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }

    echo json_encode($logs);
} else {
    echo json_encode(["error" => "Malzeme ID'si belirtilmedi."]);
}
?>