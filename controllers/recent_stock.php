<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

$queryRecentStock = "SELECT s.urun_adi, sl.eklenen_stok_miktari, sl.created_at 
                     FROM stock_log sl 
                     JOIN stock s ON sl.stock_id = s.id 
                     ORDER BY sl.created_at DESC 
                     LIMIT 20";
$resultRecentStock = $baglanti->query($queryRecentStock);

while ($row = $resultRecentStock->fetch_assoc()) {
    echo "<tr>
            <td>" . htmlspecialchars($row['urun_adi']) . "</td>
            <td>" . htmlspecialchars($row['eklenen_stok_miktari']) . "</td>
            <td>" . htmlspecialchars($row['created_at']) . "</td>
          </tr>";
}
?>