<?php
session_start();
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['project_id']) && isset($_POST['status'])) {
    $projectId = $_POST['project_id'];
    $status = $_POST['status'];

    $query = "UPDATE projects SET status = ? WHERE id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("si", $status, $projectId);

    if ($stmt->execute()) {
        echo "Proje durumu başarıyla güncellendi.";
    } else {
        echo "Bir hata oluştu: " . $stmt->error;
    }

    $stmt->close();
    $baglanti->close();
}
?>