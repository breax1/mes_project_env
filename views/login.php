<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['kullaniciadi'];
    $password = hash('sha256', $_POST['parola']);
    $ip_address = $_SERVER['REMOTE_ADDR']; // Get the IP address

    // Kullanıcıyı kontrol et
    $query = "SELECT id, username, role FROM users WHERE username = ? AND pass = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Oturum değişkenlerini ata
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Giriş işlemini logs tablosuna kaydet
        $logQuery = "INSERT INTO logs (user_id, action, description, created_at) VALUES (?, 'login', 'Giriş', NOW())";
        $logStmt = $baglanti->prepare($logQuery);
        $logStmt->bind_param("i", $user['id']);
        $logStmt->execute();

        // Başarılı yanıt gönder
        echo json_encode(['status' => 'success', 'message' => 'Giriş başarılı!']);
    } else {
        // Başarısız giriş işlemini logs tablosuna kaydet
        $logQuery = "INSERT INTO logs (user_id, action, description, created_at) VALUES (NULL, 'login', ?, NOW())";
        $description = $username . ', hatalı şifre, IP: ' . $ip_address;
        $logStmt = $baglanti->prepare($logQuery);
        $logStmt->bind_param("s", $description);
        $logStmt->execute();

        // Başarısız yanıt gönder
        echo json_encode(['status' => 'error', 'message' => 'Kullanıcı adı veya şifre yanlış!']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Design by foolishdeveloper.com -->
    <title>MES PORTAL</title>
 
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/script.js"></script>
    <!--Stylesheet-->
    <style media="screen">
      *,
*:before,
*:after{
    padding: 0;
    margin: 0;
    box-sizing: border-box;
}
body{
    background-color: #080710 !important;
}
.background {
    width: 430px !important;
    height: 520px !important;
    position: absolute !important;
    transform: translate(-50%, -50%) !important;
    left: 50% !important;
    top: 50% !important;
}
.background .shape{
    height: 200px;
    width: 200px;
    position: absolute;
    border-radius: 50%;
}
.shape:first-child{
    background: linear-gradient(
        #1845ad,
        #23a2f6
    );
    left: -80px;
    top: -80px;
}
.shape:last-child{
    background: linear-gradient(
        to right,
        #ff512f,
        #f09819
    );
    right: -30px;
    bottom: -80px;
}
form{
    height: 520px;
    width: 400px;
    background-color: rgba(255,255,255,0.13);
    position: absolute;
    transform: translate(-50%,-50%);
    top: 50%;
    left: 50%;
    border-radius: 10px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255,255,255,0.1);
    box-shadow: 0 0 40px rgba(8,7,16,0.6);
    padding: 50px 35px;
}
form *{
    font-family: 'Poppins',sans-serif;
    color: #ffffff;
    letter-spacing: 0.5px;
    outline: none;
    border: none;
}
form h3{
    font-size: 32px;
    font-weight: 500;
    line-height: 42px;
    text-align: center;
}

label{
    display: block;
    margin-top: 30px;
    font-size: 16px;
    font-weight: 500;
}
input{
    display: block;
    height: 50px;
    width: 100%;
    background-color: rgba(255,255,255,0.07);
    border-radius: 3px;
    padding: 0 10px;
    margin-top: 8px;
    font-size: 14px;
    font-weight: 300;
}
::placeholder{
    color: #e5e5e5;
}
.btn.btn-primary{
    margin-top: 50px;
    width: 100%;
    background-color: #ffffff;
    color: #080710;
    padding: 15px 0;
    font-size: 18px;
    font-weight: 600;
    border-radius: 5px;
    cursor: pointer;
}

.notification-box {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    color: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    transition: opacity 0.5s, transform 0.5s;
}

    </style>
</head>
<body>
    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <form id="loginForm">
        <h3>MES PORTAL GİRİŞ PANELİ</h3>

        <label for="username">Kullanıcı Adı</label>
        <input type="text" placeholder="Kullanıcı Adı" id="username" name="kullaniciadi">

        <label for="password">Şifre</label>
        <input type="password" placeholder="Şifre" id="password" name="parola">

        <button type="submit" class="btn btn-primary">GİRİŞ</button>
    </form>

    <div id="custom-notification-wrapper"></div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Formun normal submit işlemini engelle

            var formData = new FormData(this);

            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = '../index.php'; // Başarılı girişten sonra yönlendir
                    }, 2000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showNotification('Bir hata oluştu.', 'error');
            });
        });
    </script>
</body>
</html>