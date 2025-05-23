<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../../includes/db_config.php");

date_default_timezone_set('Europe/Istanbul'); // Saat dilimini ayarla

// Koruma: Doğru API anahtarı kontrolü
$validKey = "0CoXXXK2hN"; // Güvenli bir anahtar belirleyin
if (!isset($_GET['key']) || $_GET['key'] !== $validKey) {
    http_response_code(403); // Yetkisiz erişim
    echo json_encode(["status" => "error", "message" => "Yetkisiz erişim."]);
    exit();
}

// Bugün ve bir gün önce giriş yapmış ancak çıkış yapmamış personelleri bul
$query = "
    SELECT personnel_id 
    FROM giris_cikis 
    WHERE islem = 'giris' 
    AND DATE(time) IN (CURDATE(), DATE_SUB(CURDATE(), INTERVAL 1 DAY))
    AND personnel_id NOT IN (
        SELECT personnel_id 
        FROM giris_cikis 
        WHERE islem = 'cikis' 
        AND DATE(time) IN (CURDATE(), DATE_SUB(CURDATE(), INTERVAL 1 DAY))
    )
";
$result = $baglanti->query($query);

while ($row = $result->fetch_assoc()) {
    $personnelId = $row['personnel_id'];

    // Çıkış kaydını ekle (bugünün tarihi ve saat 05:00:00)
    $checkoutTime = date('Y-m-d 05:00:00'); // Bugünün tarihi ve saat 05:00:00
    $queryInsert = "INSERT INTO giris_cikis (personnel_id, islem, time) VALUES (?, 'cikis', ?)";
    $stmt = $baglanti->prepare($queryInsert);
    $stmt->bind_param("is", $personnelId, $checkoutTime);
    $stmt->execute();
}

// Çıkış yapılan personellerin listesini al
$queryNotification = "
    SELECT p.personnel_main_name, g.time 
    FROM giris_cikis g
    JOIN personnel p ON g.personnel_id = p.id
    WHERE g.islem = 'cikis' AND g.time = ?
";
$stmtNotification = $baglanti->prepare($queryNotification);
$stmtNotification->bind_param("s", $checkoutTime);
$stmtNotification->execute();
$resultNotification = $stmtNotification->get_result();

$personnelList = [];
while ($row = $resultNotification->fetch_assoc()) {
    $personnelList[] = $row['personnel_main_name'] . " - Çıkış Saati: " . $row['time'];
}

// Üretim müdürlerine bildirim gönder
if (!empty($personnelList)) {
    $message = "çıkış saati otomatik ayarlanan personeller mevcut)";
    $dataType = "personnel_auto";

    $queryManagers = "SELECT id FROM users WHERE FIND_IN_SET('uretim_mudur', role)";
    $resultManagers = $baglanti->query($queryManagers);

    while ($manager = $resultManagers->fetch_assoc()) {
        $queryInsertNotification = "INSERT INTO notifications (user_id, message, is_read, type) VALUES (?, ?, 0, ?)";
        $stmtInsertNotification = $baglanti->prepare($queryInsertNotification);
        $stmtInsertNotification->bind_param("iss", $manager['id'], $message, $dataType);
        $stmtInsertNotification->execute();
    }
}
?>