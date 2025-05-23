<?php
include("../includes/db_config.php");

$query = "SELECT id, urun_adi FROM stock";
$result = $baglanti->query($query);

$stockList = [];
while ($row = $result->fetch_assoc()) {
    $stockList[] = $row;
}

echo json_encode($stockList);

$baglanti->close();
?>