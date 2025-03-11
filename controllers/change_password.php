<?php
session_start();
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    $username = $_SESSION['username'];

    // Kullanıcı bilgilerini al
    $stmt = $baglanti->prepare("SELECT pass FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hashedCurrentPassword = hash('sha256', $currentPassword);

        // Mevcut şifreyi kontrol et
        if ($hashedCurrentPassword === $row['pass']) {
            // Yeni şifre ve onay şifresi eşleşiyor mu?
            if ($newPassword === $confirmPassword) {
                // Yeni şifreyi hashle ve güncelle
                $hashedNewPassword = hash('sha256', $newPassword);
                $updateStmt = $baglanti->prepare("UPDATE users SET pass = ? WHERE username = ?");
                $updateStmt->bind_param("ss", $hashedNewPassword, $username);

                if ($updateStmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Şifre başarıyla güncellendi.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Şifre güncellenirken bir hata oluştu.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Yeni şifre ve onay şifresi eşleşmiyor.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Mevcut şifre yanlış.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Kullanıcı bulunamadı.']);
    }
    exit;
}
?>