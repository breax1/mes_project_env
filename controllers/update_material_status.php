<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materialId = $_POST['material_id'];

    // Mevcut prepared değerini al
    $queryGetPrepared = "SELECT prepared FROM project_materials WHERE material_id = ?";
    $stmtGetPrepared = $baglanti->prepare($queryGetPrepared);
    $stmtGetPrepared->bind_param("i", $materialId);
    $stmtGetPrepared->execute();
    $result = $stmtGetPrepared->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $currentPrepared = $row['prepared'];
        $newPrepared = $currentPrepared == '0' ? '1' : '0'; // SET değerleri string olarak işlenir

        // prepared değerini güncelle
        $queryUpdate = "UPDATE project_materials SET prepared = ? WHERE material_id = ?";
        $stmtUpdate = $baglanti->prepare($queryUpdate);
        $stmtUpdate->bind_param("si", $newPrepared, $materialId); // prepared string olarak gönderilir

        if ($stmtUpdate->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Durum başarıyla güncellendi.', 'new_prepared' => $newPrepared]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Durum güncellenemedi.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Malzeme bulunamadı.']);
    }
}
?>