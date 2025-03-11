<?php
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $personnel_id = $_POST['personnel_id'];
    $islem = $_POST['islem'];
    $tarih = $_POST['tarih'];

    $query = "INSERT INTO giris_cikis (personnel_id, islem, time) VALUES (?, ?, ?)";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("iss", $personnel_id, $islem, $tarih);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Veri başarıyla eklendi!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Veri eklenirken bir hata zoluştu.']);
    }

    $stmt->close();
    $baglanti->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
}
?>
