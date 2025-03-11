<?php

$host = "localhost";
$username = "root";
$pass = "1234";
$vt = "mes_management";

// Establish connection
$baglanti = mysqli_connect($host, $username, $pass, $vt);

// echo "Connected successfully";

// Check connection
if (!$baglanti) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set and collation to UTF-8 Turkish (ci)
mysqli_set_charset($baglanti, "utf8");
mysqli_query($baglanti, "SET NAMES 'utf8' COLLATE 'utf8_turkish_ci'");

// Now your connection is configured to use UTF-8 Turkish collation

?>
