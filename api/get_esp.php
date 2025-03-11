<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ip'])) {
    $ip = $_POST['ip'];
    $data = array('ip' => $ip);
    $json_data = json_encode($data, JSON_PRETTY_PRINT);

    $file_path = __DIR__ . '/core/esp.json';
    if (file_put_contents($file_path, $json_data)) {
        echo "IP address saved successfully.";
    } else {
        echo "Failed to save IP address.";
    }
} else {
    echo "Invalid request.";
}
?>