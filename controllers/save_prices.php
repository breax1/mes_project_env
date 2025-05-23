<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

$response = array('status' => '', 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kesifId = $_POST['kesif_id'];
    $prices = $_POST['prices'];

    foreach ($prices as $price) {
        $rawMaterialId = $price['raw_material_id'];
        $priceValue = $price['price'];

        // Fiyatı kaydet
        $query = "INSERT INTO raw_material_prices (kesif_raw_material_id, price) VALUES (?, ?)";
        $stmt = $baglanti->prepare($query);
        $stmt->bind_param("id", $rawMaterialId, $priceValue);
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Fiyatlar başarıyla kaydedildi!';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Fiyatlar kaydedilirken bir hata oluştu: ' . $stmt->error;
            echo json_encode($response);
            exit;
        }
        $stmt->close();
    }

    // Uretim mudur onayını güncelle
    $queryApproval = "UPDATE kesif_approvals SET satin_alma_approved = TRUE WHERE kesif_id = ?";
    $stmtApproval = $baglanti->prepare($queryApproval);
    $stmtApproval->bind_param("i", $kesifId);
    $stmtApproval->execute();
    $stmtApproval->close();
    
    // Onay durumunu kontrol et
    $queryCheck = "SELECT uretim_mudur_approved FROM kesif_approvals WHERE kesif_id = ?";
    $stmtCheck = $baglanti->prepare($queryCheck);
    $stmtCheck->bind_param("i", $kesifId);
    $stmtCheck->execute();
    $stmtCheck->bind_result($uretimMudurApproved);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($uretimMudurApproved) {
        // satin alma fiyat teklifi verdiyese teknik müdüre bildirim gönder
        $queryUsers = "SELECT id FROM users WHERE FIND_IN_SET('teknik_mudur', role)";
        $resultUsers = $baglanti->query($queryUsers);
        $message = "Yeni bir teklif bekleyen proje var.";
        while ($user = $resultUsers->fetch_assoc()) {
            $queryNotification = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $stmtNotification = $baglanti->prepare($queryNotification);
            $stmtNotification->bind_param("is", $user['id'], $message);
            $stmtNotification->execute();
            $stmtNotification->close();
        }

        // Proje durumunu güncelle
        $queryProject = "UPDATE projects p
                         JOIN kesif k ON p.id = k.project_id
                         SET p.status = 'teklif_bekliyor'
                         WHERE k.id = ?";
        $stmtProject = $baglanti->prepare($queryProject);
        $stmtProject->bind_param("i", $kesifId);
        $stmtProject->execute();
        $stmtProject->close();
    }

    $baglanti->close();
    echo json_encode($response);
}
?>