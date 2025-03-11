<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

if (isset($_GET['kesif_id'])) {
    $kesifId = $_GET['kesif_id'];

    // Keşif detaylarını çekmek için sorgu
    $query = "SELECT krm.id, krm.raw_material_name, krm.unit, krm.amount, u.unit_name 
              FROM kesif_raw_materials krm 
              JOIN units u ON krm.unit = u.id 
              WHERE krm.kesif_id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $kesifId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Son fiyatı çekmek için sorgu
        $queryPrice = "SELECT price FROM raw_material_prices WHERE kesif_raw_material_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmtPrice = $baglanti->prepare($queryPrice);
        $stmtPrice->bind_param("i", $row['id']);
        $stmtPrice->execute();
        $resultPrice = $stmtPrice->get_result();
        $price = $resultPrice->fetch_assoc();

        $lastPrice = $price ? $price['price'] : '';

        echo "<tr data-id='{$row['id']}'>
                <td class='raw-material'>{$row['raw_material_name']}</td>
                <td class='unit'>{$row['unit_name']}</td>
                <td class='amount'>{$row['amount']}</td>
                <td class='price'><input type='number' step='0.01' value='{$lastPrice}'></td>
              </tr>";

        $stmtPrice->close();
    }

    $stmt->close();
    $baglanti->close();
}
?>