<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $nickname = $_POST['nickname'];
    $customer_code = $_POST['customer_code'];
    $type = $_POST['type'];
    $status = $_POST['status'];
    $address = $_POST['address'];
    $country_id = $_POST['country_id'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $web = $_POST['web'];
    $tax_office = $_POST['tax_office'];
    $tax_number = $_POST['tax_number'];
    $price_unit_id = $_POST['price_unit_id'];

    $query = "INSERT INTO customers (name, nickname, customer_code, type, status, address, country_id, phone, email, web, tax_office, tax_number, price_unit_id)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("sssississsssi", $name, $nickname, $customer_code, $type, $status, $address, $country_id, $phone, $email, $web, $tax_office, $tax_number, $price_unit_id);

    if ($stmt->execute()) {
        echo "Üçüncü parti başarıyla kaydedildi!";
    } else {
        echo "Bir hata oluştu: " . $stmt->error;
    }

    $stmt->close();
    $baglanti->close();
}
?>



