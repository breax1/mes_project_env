<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

// Günlük kullanılan stok miktarını al
$query = "
    SELECT DATE(l.created_at) AS day, SUM(l.eklenen_stok_miktari) AS total_used
    FROM stock_log l
    WHERE l.islem = 'Çıkış'
    GROUP BY DATE(l.created_at)
    ORDER BY day ASC
";
$result = $baglanti->query($query);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'day' => $row['day'],
            'total_used' => $row['total_used']
        ];
    }
}

echo json_encode($data);
?>