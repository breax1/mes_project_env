<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // POST verilerini kontrol edin
    if (!isset($_POST['checkoutTimes']) || empty($_POST['checkoutTimes'])) {
        echo json_encode(['status' => 'error', 'message' => 'Gönderilen veriler eksik.']);
        exit;
    }

    $checkoutTimes = $_POST['checkoutTimes']; // Tüm checkout verilerini al

    foreach ($checkoutTimes as $personnelId => $realCheckoutTime) {
        if (empty($realCheckoutTime)) {
            echo json_encode(['status' => 'error', 'message' => 'Çıkış saati eksik.']);
            exit;
        }

        // Çıkış saatini güncelle
        $query = "UPDATE giris_cikis 
                  SET time = ? 
                  WHERE personnel_id = ? 
                  AND islem = 'cikis'";
        $stmt = $baglanti->prepare($query);
        $stmt->bind_param("si", $realCheckoutTime, $personnelId);

        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Çıkış saati güncellenemedi.']);
            exit;
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Çıkış saatleri başarıyla güncellendi.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
}
?>