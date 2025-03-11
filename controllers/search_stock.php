<?php
include("../includes/db_config.php");

$urunAdi = isset($_GET['urunAdi']) ? $_GET['urunAdi'] : '';
$urunRafi = isset($_GET['urunRafi']) ? $_GET['urunRafi'] : '';

$query = "SELECT s.*, ss.shelf_name 
          FROM stock s 
          JOIN stock_shelf ss ON s.shelf_id = ss.id 
          WHERE 1=1";

if (!empty($urunAdi)) {
    $query .= " AND s.urun_adi LIKE '%" . $baglanti->real_escape_string($urunAdi) . "%'";
}
if (!empty($urunRafi)) {
    $query .= " AND ss.id = '" . $baglanti->real_escape_string($urunRafi) . "'";
}

$result = $baglanti->query($query);

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>" . htmlspecialchars($row['urun_adi']) . "</td>
            <td>" . htmlspecialchars($row['shelf_name']) . "</td>
            <td>" . htmlspecialchars($row['urun_stok_miktari']) . "</td>
            <td>" . htmlspecialchars($row['urun_kritik_stok']) . "</td>
          </tr>";
}
?>