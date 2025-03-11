<?php
include("../includes/db_config.php");

$queryAllStock = "SELECT s.*, ss.shelf_name 
                  FROM stock s 
                  JOIN stock_shelf ss ON s.shelf_id = ss.id";
$resultAllStock = $baglanti->query($queryAllStock);

while ($row = $resultAllStock->fetch_assoc()) {
    echo "<tr>
            <td>" . htmlspecialchars($row['urun_adi']) . "</td>
            <td>" . htmlspecialchars($row['shelf_name']) . "</td>
            <td>" . htmlspecialchars($row['urun_stok_miktari']) . "</td>
            <td>" . htmlspecialchars($row['urun_kritik_stok']) . "</td>
          </tr>";
}
?>