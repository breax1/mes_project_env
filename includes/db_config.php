<?php

// .env dosyasını yükle
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(sprintf('%s=%s', trim($key), trim($value)));
        }
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$vt = getenv('DB_NAME') ?: 'mes_management';

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
