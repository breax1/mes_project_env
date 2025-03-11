<?php
session_start();
include("../includes/db_config.php");

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Kullanıcı oturumu açık değil.']);
    exit;
}

$username = $_SESSION['username'];

if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['profilePicture']['tmp_name'];
    $fileName = $_FILES['profilePicture']['name'];
    $fileSize = $_FILES['profilePicture']['size'];
    $fileType = $_FILES['profilePicture']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
    if (in_array($fileExtension, $allowedfileExtensions)) {
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $uploadFileDir = '../assets/images/pp/';
        $dest_path = $uploadFileDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $pp_location = 'assets/images/pp/' . $newFileName;

            $stmt = $baglanti->prepare("UPDATE users SET pp_location = ? WHERE username = ?");
            $stmt->bind_param("ss", $pp_location, $username);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Profil fotoğrafı başarıyla güncellendi.', 'newImagePath' => '../' . $pp_location]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Veritabanı güncelleme hatası.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Dosya yükleme hatası.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz dosya türü.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Dosya yüklenemedi.']);
}
?>