<?php
include("../includes/db_config.php");

if (isset($_GET['stock_id'])) {
    $stockId = intval($_GET['stock_id']);

    $query = "
        SELECT 
            su.uses_personnel_id,
            su.uses_projects,
            su.note,
            su.value,
            p.personnel_main_name,
            pr.project_name,
            pr.project_no
        FROM stock_uses su
        LEFT JOIN personnel p ON su.uses_personnel_id = p.id
        LEFT JOIN projects pr ON su.uses_projects = pr.id
        WHERE su.stock_id = ?
    ";

    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $stockId);
    $stmt->execute();
    $result = $stmt->get_result();

    $stockUses = [];
    while ($row = $result->fetch_assoc()) {
        $stockUses[] = $row;
    }

    echo json_encode($stockUses);
} else {
    echo json_encode(["error" => "Malzeme ID'si belirtilmedi."]);
}
?>