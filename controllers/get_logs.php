<?php
include("../includes/db_config.php");

$logsQuery = "
    SELECT 
        l.islem AS islem, 
        CONCAT(u.name, ' ', u.surname) AS user_name, 
        s.urun_adi AS stock_name, 
        l.eklenen_stok_miktari AS stock_amount, 
        l.created_at AS created_at
    FROM stock_log l
    LEFT JOIN users u ON l.user_id = u.id
    LEFT JOIN stock s ON l.stock_id = s.id
    ORDER BY l.created_at DESC
";
$logsResult = $baglanti->query($logsQuery);

if ($logsResult->num_rows > 0) {
    while ($row = $logsResult->fetch_assoc()) {
        echo "<tr>
                <td>{$row['islem']}</td>
                <td>{$row['user_name']}</td>
                <td>{$row['stock_name']}</td>
                <td>{$row['stock_amount']}</td>
                <td>{$row['created_at']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5'>Sonuç bulunamadı.</td></tr>";
}
?>