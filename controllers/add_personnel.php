<?php
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $personnel_number = $_POST['personnel_number'];
    $identity = $_POST['identity'];
    $kart_id = $_POST['kart_id'];
    $start_date = $_POST['start_date'];

    // Ad ve soyadı birleştirip Türkçe karakterleri kaldırarak büyük harfe çevirme
    $personnel_name = strtoupper(str_replace(['ç', 'ğ', 'ı', 'ö', 'ş', 'ü'], ['c', 'g', 'i', 'o', 's', 'u'], $name . ' ' . $surname));
    $personnel_main_name = strtoupper($name . ' ' . $surname);

    $query = "INSERT INTO personnel (personnel_name, personnel_main_name, personnel_number, kart_id, identity, start_date, available_from, available_to, active) 
              VALUES (?, ?, ?, ?, ?, ?, '2025-01-01', '2025-12-31', 1)";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("ssssss", $personnel_name, $personnel_main_name, $personnel_number, $kart_id, $identity, $start_date);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Personel başarıyla eklendi!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Personel eklenirken bir hata oluştu.']);
    }

    $stmt->close();
    $baglanti->close();
}
?>