<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

$response = array('status' => '', 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Form verilerini al
    $projectNo = $_POST['project_no'];
    $projectName = $_POST['project_name'];
    $muhendisId = $_POST['muhendis_id'];
    $karsiYetkili = $_POST['karsi_yetkili'];
    $workplaceName = $_POST['workplace'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $created_by = $_SESSION['user_id']; // Kullanıcı ID'sini oturumdan al

    // Proje numarasının zaten mevcut olup olmadığını kontrol et
    $queryCheckProjectNo = "SELECT * FROM projects WHERE project_no = ?";
    $stmtCheck = $baglanti->prepare($queryCheckProjectNo);
    $stmtCheck->bind_param("s", $projectNo);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        // Proje numarası zaten mevcut
        $response['status'] = 'error';
        $response['message'] = 'Proje numarası zaten mevcut!';
        echo json_encode($response);
        exit;
    }

    // Proje Firması ID'sini al veya yeni firma ekle
    $queryWorkplace = "SELECT id FROM workplace WHERE name = ?";
    $stmtWorkplace = $baglanti->prepare($queryWorkplace);
    $stmtWorkplace->bind_param("s", $workplaceName);
    $stmtWorkplace->execute();
    $stmtWorkplace->bind_result($workplaceId);
    $stmtWorkplace->fetch();
    $stmtWorkplace->close();

    if (!$workplaceId) {
        // Yeni firma ekle
        $queryInsertWorkplace = "INSERT INTO workplace (name, location) VALUES (?, ?)";
        $stmtInsertWorkplace = $baglanti->prepare($queryInsertWorkplace);
        $stmtInsertWorkplace->bind_param("ss", $workplaceName, $location);
        $stmtInsertWorkplace->execute();
        $workplaceId = $stmtInsertWorkplace->insert_id;
        $stmtInsertWorkplace->close();
    }

    // Projeyi veritabanına kaydet
    $query = "INSERT INTO projects (project_no, project_name, muhendis_id, karsi_yetkili, workplace_id, location, description, status, created_by)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("ssisssssi", $projectNo, $projectName, $muhendisId, $karsiYetkili, $workplaceId, $location, $description, $status, $created_by);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Proje başarıyla kaydedildi!';

        // Proje ID'sini al
        $projectId = $stmt->insert_id;

        // Görselleri yükle ve veritabanına kaydet
        if (!empty($_FILES['image_path']['name'][0])) {
            $uploadDir = '../uploads/images/';
            foreach ($_FILES['image_path']['name'] as $key => $imageName) {
                $imageTmpName = $_FILES['image_path']['tmp_name'][$key];
                $imagePath = $uploadDir . basename($imageName);
                if (move_uploaded_file($imageTmpName, $imagePath)) {
                    // Görsel yolunu veritabanına kaydet
                    $queryImage = "INSERT INTO project_images (project_id, image_path) VALUES (?, ?)";
                    $stmtImage = $baglanti->prepare($queryImage);
                    $stmtImage->bind_param("is", $projectId, $imagePath);
                    $stmtImage->execute();
                    $stmtImage->close();
                }
            }
        }

        // Bildirim mesajını oluştur
        $message = "Size yeni bir proje atandı: $projectNo";

        // Bildirimi notifications tablosuna ekle
        $queryNotification = "INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)";
        $stmtNotification = $baglanti->prepare($queryNotification);
        $stmtNotification->bind_param("is", $muhendisId, $message);

        if ($stmtNotification->execute()) {
            $response['status'] = 'success';
            $response['message'] .= ' Bildirim başarıyla gönderildi.';
        } else {
            $response['status'] = 'error';
            $response['message'] .= ' Bildirim gönderilemedi: ' . $stmtNotification->error;
        }

        $stmtNotification->close();
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Bir hata oluştu: ' . $stmt->error;
    }

    $stmt->close();
    $baglanti->close();
    echo json_encode($response);
}
?>