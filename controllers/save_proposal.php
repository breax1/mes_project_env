<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer'];
    $proposal_date = $_POST['proposal_date'];
    $validity_period = $_POST['validity_period'];
    $payment_terms = $_POST['payment_terms'];
    $payment_method = $_POST['payment_method'];
    $delivery_time = $_POST['delivery_time'];
    $project_id = $_POST['project'];
    $price_unit_id = $_POST['price_unit'];
    $general_note = $_POST['general_note'];
    $special_note = $_POST['special_note'];
    
    $amount = 0.0; // Varsayılan float değer
    $author = $_SESSION['user_id']; // Oturum açmış kullanıcının ID'si

    $query = "INSERT INTO propal (customer_id, proposal_date, validity_period, payment_terms, payment_method, delivery_time, project_id, price_unit_id, general_note, special_note, status, amount, author)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)";

    $stmt = $baglanti->prepare($query);

    // Veritabanı sorgusunun veri türleri ile uyumlu parametreleri belirtmek için "s" (string), "i" (integer) kullanılır.
    $stmt->bind_param("isisssiissdi", $customer_id, $proposal_date, $validity_period, $payment_terms, $payment_method, $delivery_time, $project_id, $price_unit_id, $general_note, $special_note, $amount, $author);

    if ($stmt->execute()) {
        echo "Teklif başarıyla kaydedildi!";
    } else {
        echo "Bir hata oluştu: " . $stmt->error;
    }

    $stmt->close();
    $baglanti->close();
}
?>
