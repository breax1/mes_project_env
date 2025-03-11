<?php
// Veritabanı bağlantısı
include("../includes/db_config.php");

// Oturumdaki kullanıcı ID'sini alın
session_start();
$currentUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // POST isteği ile bildirimi okundu olarak işaretle
    $data = json_decode(file_get_contents("php://input"), true);
    $notificationId = $data['id'];

    // Bildirimi okundu olarak işaretle
    $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("ii", $notificationId, $currentUserId);

    if ($stmt->execute()) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => "Bildirim okundu olarak işaretlenemedi."));
    }

    $stmt->close();
} else {
    // GET isteği ile bildirimleri sorgula
    $query = "SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at ";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Bildirimleri JSON olarak döndür
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    echo json_encode([
        'notifications' => $notifications,
        'unreadCount' => count(array_filter($notifications, fn($n) => $n['is_read'] == 0)) // Okunmamışları say
    ]);

    $stmt->close();
}

$baglanti->close();
?>