<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

header('Content-Type: application/json; charset=utf-8'); // UTF-8 karakter seti

// Çıkış yapılan personellerin listesini al
$query = "
    SELECT p.id, p.personnel_main_name, g.time 
    FROM giris_cikis g
    JOIN personnel p ON g.personnel_id = p.id
    WHERE g.islem = 'cikis' AND TIME(g.time) = ?
";
$checkoutTime = '05:00:00'; // Sadece saat kısmını kontrol et
$stmt = $baglanti->prepare($query);
$stmt->bind_param("s", $checkoutTime);
$stmt->execute();
$result = $stmt->get_result();

$personnelList = [];
while ($row = $result->fetch_assoc()) {
    $personnelList[] = [
        'id' => $row['id'], // Personelin ID'si
        'name' => $row['personnel_main_name'], // Personelin adı
        'time' => $row['time'] // Çıkış saati
    ];
}

// JSON formatında döndür
if (!empty($personnelList)) {
    echo json_encode(['status' => 'success', 'personnel' => $personnelList], JSON_UNESCAPED_UNICODE); // UTF-8 karakterleri koru
} else {
    echo json_encode(['status' => 'error', 'message' => 'Çıkış yapılan personel bulunamadı.'], JSON_UNESCAPED_UNICODE);
}
?>