<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

// En çok kullanılan malzeme cinslerini al
$query = "
    SELECT c.cins_adi AS type, ABS(SUM(l.eklenen_stok_miktari)) AS total_used
    FROM stock_log l
    JOIN stock s ON l.stock_id = s.id
    JOIN stock_cins c ON s.urun_cins = c.id
    WHERE l.eklenen_stok_miktari < 0
    GROUP BY c.cins_adi
    ORDER BY total_used DESC
";
$result = $baglanti->query($query);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'type' => $row['type'],
            'total_used' => $row['total_used']
        ];
    }
}

echo json_encode($data);
?>