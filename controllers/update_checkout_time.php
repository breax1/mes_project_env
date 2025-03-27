<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // POST verilerini kontrol edin
    if (!isset($_POST['checkoutTimes']) || empty($_POST['checkoutTimes']) || !isset($_POST['dataDates'])) {
        echo json_encode(['status' => 'error', 'message' => 'Gönderilen veriler eksik.']);
        exit;
    }

    $checkoutTimes = $_POST['checkoutTimes']; // Güncellenmek istenen checkout saatleri
    $dataDates = $_POST['dataDates']; // Gönderilen data-date değerleri

    foreach ($checkoutTimes as $personnelId => $realCheckoutTime) {
        if (empty($realCheckoutTime) || empty($dataDates[$personnelId])) {
            echo json_encode(['status' => 'error', 'message' => 'Çıkış saati veya data-date eksik.']);
            exit;
        }

        $dataDate = $dataDates[$personnelId]; // İlgili personelin data-date değeri
        $originalCheckoutTime = date('Y-m-d H:i:s', strtotime($dataDate . ' +1 day 05:00:00')); // data-date + 1 gün ve 05:00:00

        // Çıkış saatini sadece orijinal checkout_time ile eşleşen kayıt için güncelle
        $query = "UPDATE giris_cikis 
                  SET time = ? 
                  WHERE personnel_id = ? 
                  AND islem = 'cikis' 
                  AND time = ?";
        $stmt = $baglanti->prepare($query);
        $stmt->bind_param("sis", $realCheckoutTime, $personnelId, $originalCheckoutTime);

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