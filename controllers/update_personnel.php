<?php
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $personnel_number = $_POST['personnel_number'];
    $identity = $_POST['identity'];
    $kart_id = $_POST['kart_id'];
    $start_date = $_POST['start_date'];
    $active = isset($_POST['active']) ? 1 : 0;

    // Ad ve soyadı birleştirip Türkçe karakterleri kaldırarak büyük harfe çevirme
    $personnel_name = strtoupper(str_replace(['ç', 'ğ', 'ı', 'ö', 'ş', 'ü'], ['c', 'g', 'i', 'o', 's', 'u'], $name . ' ' . $surname));
    $personnel_main_name = strtoupper($name . ' ' . $surname);

    $query = "UPDATE personnel SET personnel_name = ?, personnel_main_name = ?, personnel_number = ?, kart_id = ?, identity = ?, start_date = ?, active = ? WHERE id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("ssssssii", $personnel_name, $personnel_main_name, $personnel_number, $kart_id, $identity, $start_date, $active, $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Personel bilgileri başarıyla güncellendi!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Personel bilgileri güncellenirken bir hata oluştu.']);
    }

    $stmt->close();
    $baglanti->close();
}
?>