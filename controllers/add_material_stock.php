<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

session_start(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stockProduct = $_POST['stockProduct'];
    $stockAmount = $_POST['stockAmount'];
    $userId = $_SESSION['user_id']; 

    // Stok miktarını artır
    $query = "UPDATE stock SET urun_stok_miktari = urun_stok_miktari + ? WHERE id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("ii", $stockAmount, $stockProduct);

    if ($stmt->execute()) {
        // Stok güncellemesi başarılıysa, stock_log tablosuna kayıt ekle
        $logQuery = "INSERT INTO stock_log (islem, user_id, stock_id, eklenen_stok_miktari) VALUES (?, ?, ?, ?)";
        $logStmt = $baglanti->prepare($logQuery);
        $islem = 'stok'; // İşlem türü
        $logStmt->bind_param("siii", $islem, $userId, $stockProduct, $stockAmount);

        if ($logStmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Stok başarıyla eklendi ve log kaydedildi.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Stok eklendi ancak log kaydedilemedi.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Stok eklenemedi.']);
    }
}
?>