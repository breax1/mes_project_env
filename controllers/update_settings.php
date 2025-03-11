<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;

    // Kullanıcı ayarlarını güncelle
    $query = "UPDATE settings SET notifications_enabled = ? WHERE user_id = ?";
    $stmt = $baglanti->prepare($query);
    if (!$stmt) {
        die("Sorgu hazırlama hatası: " . $baglanti->error);
    }
    $stmt->bind_param("ii", $notifications_enabled, $user_id);
    if ($stmt->execute()) {
        echo "Ayarlar başarıyla güncellendi!";
    } else {
        echo "Ayarlar güncellenirken bir hata oluştu: " . $stmt->error;
    }
    $stmt->close();
}
?>