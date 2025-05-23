<?php
include("../includes/db_config.php");

if (isset($_GET['stock_id'])) {
    $stockId = intval($_GET['stock_id']);
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

    $query = "
        SELECT 
            ph.purchase_date AS tarih,
            c.name AS tedarikci,
            ph.quantity AS miktar,
            ph.price AS birim_fiyat,
            ph.total_price AS toplam_fiyat,
            pu.unit AS birim
        FROM purchase_history ph
        LEFT JOIN customers c ON ph.supplier_id = c.id
        LEFT JOIN price_unit pu ON ph.price_unit = pu.id
        WHERE ph.stock_id = ?
    ";

    // Tarih aralığı kontrolü
    if ($startDate && $endDate) {
        $query .= " AND ph.purchase_date BETWEEN ? AND ?";
    }

    $query .= " ORDER BY ph.purchase_date DESC";

    $stmt = $baglanti->prepare($query);

    if ($startDate && $endDate) {
        $stmt->bind_param("iss", $stockId, $startDate, $endDate);
    } else {
        $stmt->bind_param("i", $stockId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $purchaseHistory = [];
    while ($row = $result->fetch_assoc()) {
        $purchaseHistory[] = $row;
    }

    echo json_encode($purchaseHistory);
} else {
    echo json_encode(["error" => "Malzeme ID'si belirtilmedi."]);
}
?>