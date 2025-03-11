<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

$response = array('status' => '', 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kesif_id']) && isset($_POST['action'])) {
    // Formun birden fazla kez gönderilmesini önlemek için bayrak kullan
    if (isset($_SESSION['form_submitted']) && $_SESSION['form_submitted'] === true) {
        $response['status'] = 'error';
        $response['message'] = 'Form zaten gönderildi.';
        echo json_encode($response);
        exit;
    }
    $_SESSION['form_submitted'] = true;

    $kesifId = $_POST['kesif_id'];
    $action = $_POST['action'];
    $personnel = isset($_POST['personnel']) ? $_POST['personnel'] : [];
    $equipment = isset($_POST['equipment']) ? $_POST['equipment'] : [];
    $vehicle = isset($_POST['vehicle']) ? $_POST['vehicle'] : '';

    if ($action == 'approve') {
        $status = 'approved';
    } elseif ($action == 'reject') {
        $status = 'rejected';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Geçersiz işlem.';
        echo json_encode($response);
        exit;
    }

    // Mevcut personel, ekipman ve araç bilgilerini al
    $queryCurrent = "SELECT vehicle FROM kesif WHERE id = ?";
    $stmtCurrent = $baglanti->prepare($queryCurrent);
    $stmtCurrent->bind_param("i", $kesifId);
    $stmtCurrent->execute();
    $resultCurrent = $stmtCurrent->get_result();
    $currentKesif = $resultCurrent->fetch_assoc();
    $currentVehicle = $currentKesif['vehicle'];

    $currentPersonnel = getKesifPersonnel($kesifId);
    $currentEquipment = getKesifEquipment($kesifId);

    // Değişiklik kontrolü
    $isChanged = ($vehicle != $currentVehicle) || !empty(array_diff($personnel, $currentPersonnel)) || !empty(array_diff($currentPersonnel, $personnel)) || !empty(array_diff($equipment, $currentEquipment)) || !empty(array_diff($currentEquipment, $equipment));

    // Keşif durumunu güncelle
    $query = "UPDATE kesif SET status = ?, vehicle = ? WHERE id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("ssi", $status, $vehicle, $kesifId);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Keşif durumu başarıyla güncellendi.';

        // Personel bilgilerini güncelle
        foreach ($personnel as $person) {
            $queryPersonnel = "INSERT INTO kesif_personnel (kesif_id, personnel_main_name) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE personnel_main_name = VALUES(personnel_main_name)";
            $stmtPersonnel = $baglanti->prepare($queryPersonnel);
            $stmtPersonnel->bind_param("is", $kesifId, $person);
            $stmtPersonnel->execute();
            $stmtPersonnel->close();
        }

        // Ekipman bilgilerini güncelle
        foreach ($equipment as $equip) {
            $queryEquipment = "INSERT INTO kesif_equipment (kesif_id, equipment_name) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE equipment_name = VALUES(equipment_name)";
            $stmtEquipment = $baglanti->prepare($queryEquipment);
            $stmtEquipment->bind_param("is", $kesifId, $equip);
            $stmtEquipment->execute();
            $stmtEquipment->close();
        }

        // Uretim mudur onayını güncelle
        $queryApproval = "UPDATE kesif_approvals SET uretim_mudur_approved = TRUE WHERE kesif_id = ?";
        $stmtApproval = $baglanti->prepare($queryApproval);
        $stmtApproval->bind_param("i", $kesifId);
        $stmtApproval->execute();
        $stmtApproval->close();

        // Onay durumunu kontrol et
        $queryCheck = "SELECT satin_alma_approved FROM kesif_approvals WHERE kesif_id = ?";
        $stmtCheck = $baglanti->prepare($queryCheck);
        $stmtCheck->bind_param("i", $kesifId);
        $stmtCheck->execute();
        $stmtCheck->bind_result($satinAlmaApproved);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($satinAlmaApproved) {
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

        // Bildirim durumunu güncelle
        $queryKesif = "SELECT created_by FROM kesif WHERE id = ?";
        $stmtKesif = $baglanti->prepare($queryKesif);
        $stmtKesif->bind_param("i", $kesifId);
        $stmtKesif->execute();
        $resultKesif = $stmtKesif->get_result();
        if ($resultKesif->num_rows > 0) {
            $rowKesif = $resultKesif->fetch_assoc();
            $createdBy = $rowKesif['created_by'];

            // Bildirim mesajını belirle
            if ($isChanged) {
                $message = "Keşif teknik mudur tarafindan düzenlendi.";
            } else {
                $message = "Keşif teknik mudur tarafindan onaylandı.";
            }

            $queryNotification = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $stmtNotification = $baglanti->prepare($queryNotification);
            $stmtNotification->bind_param("is", $createdBy, $message);
            $stmtNotification->execute();
            $stmtNotification->close();
        }
        $stmtKesif->close();
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Bir hata oluştu: ' . $stmt->error;
    }

    $stmt->close();
    $baglanti->close();

    // Formun tekrar gönderilmesini önlemek için bayrağı sıfırla
    $_SESSION['form_submitted'] = false;

    echo json_encode($response);
}

function getKesifPersonnel($kesifId) {
    global $baglanti;
    $query = "SELECT personnel_main_name FROM kesif_personnel WHERE kesif_id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $kesifId);
    $stmt->execute();
    $result = $stmt->get_result();
    $personnel = [];
    while ($row = $result->fetch_assoc()) {
        $personnel[] = $row['personnel_main_name'];
    }
    $stmt->close();
    return $personnel;
}

function getKesifEquipment($kesifId) {
    global $baglanti;
    $query = "SELECT equipment_name FROM kesif_equipment WHERE kesif_id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $kesifId);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipment = [];
    while ($row = $result->fetch_assoc()) {
        $equipment[] = $row['equipment_name'];
    }
    $stmt->close();
    return $equipment;
}
?>