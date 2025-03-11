<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

$response = array('status' => '', 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Form verilerini al
    $projectId = $_POST['project_id'];
    $location = $_POST['location'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $description = $_POST['description'];
    $vehicle = $_POST['vehicle'];
    $created_by = $_SESSION['user_id']; // Kullanıcı ID'sini oturumdan al

    // Keşif bilgilerini veritabanına kaydet
    $query = "INSERT INTO kesif (project_id, location, start_date, end_date, description, created_by)
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("issssi", $projectId, $location, $startDate, $endDate, $description, $created_by);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Keşif başarıyla kaydedildi!';

        // Keşif ID'sini al
        $kesifId = $stmt->insert_id;

        // Görselleri yükle ve veritabanına kaydet
        if (!empty($_FILES['drawings_photos']['name'][0])) {
            $uploadDir = '../uploads/';
            foreach ($_FILES['drawings_photos']['name'] as $key => $imageName) {
                $imageTmpName = $_FILES['drawings_photos']['tmp_name'][$key];
                $imagePath = $uploadDir . basename($imageName);
                if (move_uploaded_file($imageTmpName, $imagePath)) {
                    // Görsel yolunu veritabanına kaydet
                    $queryImage = "INSERT INTO kesif_images (kesif_id, image_path) VALUES (?, ?)";
                    $stmtImage = $baglanti->prepare($queryImage);
                    $stmtImage->bind_param("is", $kesifId, $imagePath);
                    $stmtImage->execute();
                    $stmtImage->close();
                }
            }
        }

        // Personel bilgilerini kaydet
        if (!empty($_POST['personnel'])) {
            foreach ($_POST['personnel'] as $personnel) {
                $queryPersonnel = "INSERT INTO kesif_personnel (kesif_id, personnel_main_name) VALUES (?, ?)";
                $stmtPersonnel = $baglanti->prepare($queryPersonnel);
                $stmtPersonnel->bind_param("is", $kesifId, $personnel);
                $stmtPersonnel->execute();
                $stmtPersonnel->close();
            }
        }

        // Ekipman bilgilerini kaydet
        if (!empty($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equipment) {
                $queryEquipment = "INSERT INTO kesif_equipment (kesif_id, equipment_name) VALUES (?, ?)";
                $stmtEquipment = $baglanti->prepare($queryEquipment);
                $stmtEquipment->bind_param("is", $kesifId, $equipment);
                $stmtEquipment->execute();
                $stmtEquipment->close();
            }
        }

        // Araç bilgilerini kaydet
        if (!empty($vehicle)) {
            $queryVehicle = "INSERT INTO kesif_vehicles (kesif_id, vehicle_name) VALUES (?, ?)";
            $stmtVehicle = $baglanti->prepare($queryVehicle);
            $stmtVehicle->bind_param("is", $kesifId, $vehicle);
            $stmtVehicle->execute();
            $stmtVehicle->close();
        }

        // Ham madde bilgilerini kaydet
        if (!empty($_POST['raw_materials'])) {
            for ($i = 0; $i < count($_POST['raw_materials']); $i++) {
                $rawMaterial = $_POST['raw_materials'][$i];
                $unit = $_POST['units'][$i];
                $quantity = $_POST['quantities'][$i];
                $queryRawMaterial = "INSERT INTO kesif_raw_materials (kesif_id, raw_material_name, unit, amount) VALUES (?, ?, ?, ?)";
                $stmtRawMaterial = $baglanti->prepare($queryRawMaterial);
                $stmtRawMaterial->bind_param("isid", $kesifId, $rawMaterial, $unit, $quantity);
                $stmtRawMaterial->execute();
                $stmtRawMaterial->close();
            }
        }

        // Uretim mudurlerine bildirim gönder
        $queryUsers = "SELECT id FROM users WHERE FIND_IN_SET('uretim_mudur', role)";
        $resultUsers = $baglanti->query($queryUsers);
        $message = "Yeni bir keşif kaydedildi. Onayınızı bekliyor.";
        while ($user = $resultUsers->fetch_assoc()) {
            $queryNotification = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $stmtNotification = $baglanti->prepare($queryNotification);
            $stmtNotification->bind_param("is", $user['id'], $message);
            $stmtNotification->execute();   
            $stmtNotification->close();
        }

        // Satin almaya bildirim gonder
        $queryUsers = "SELECT id FROM users WHERE FIND_IN_SET('satin_alma', role)";
        $resultUsers = $baglanti->query($queryUsers);
        $message = "Fiyat teklifi bekleyen yeni bir keşif var.";
        while ($user = $resultUsers->fetch_assoc()) {
            $queryNotification = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $stmtNotification = $baglanti->prepare($queryNotification);
            $stmtNotification->bind_param("is", $user['id'], $message);
            $stmtNotification->execute();   
            $stmtNotification->close();
        }

        // Keşif onay durumunu kesif_approvals tablosuna ekle
        $queryApproval = "INSERT INTO kesif_approvals (kesif_id, uretim_mudur_approved, satin_alma_approved) VALUES (?, FALSE, FALSE)";
        $stmtApproval = $baglanti->prepare($queryApproval);
        $stmtApproval->bind_param("i", $kesifId);
        $stmtApproval->execute();
        $stmtApproval->close();

    } else {
        $response['status'] = 'error';
        $response['message'] = 'Bir hata oluştu: ' . $stmt->error;
    }

    $stmt->close();
    $baglanti->close();
    echo json_encode($response);
}
?>