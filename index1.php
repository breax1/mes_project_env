<!-- index.php -->
<?php 
session_start();
include("includes/db_config.php");

date_default_timezone_set('Europe/Istanbul'); // Saat dilimini ayarla

// Oturumda kullanıcı adı varsa, kullanıcı giriş yapmış demektir
if(isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    // Kullanıcı adına göre ad, soyad ve rol bilgisini veritabanından al
    $stmt = $baglanti->prepare("SELECT username, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    // Sorgu sonucunda bir satır varsa, ad, soyad ve rolü alın
    if($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $username = $row['username'];
        $role = $row['role'];
        // Oturum değişkenlerine ad, soyad ve rolü kaydet
        $_SESSION['role'] = $role;
        include('views/menu.php'); 
    } else {
        // Kullanıcı bilgileri alınamadıysa, hata oluştuğunu belirtin
        echo "Kullanıcı bilgileri alınamadı.";
        exit; // Hata olduğunda betiği sonlandırın
    }
} else {
    // Kullanici giris yapmamissa giris sayfasina yonlendir
    header("Location: views/login.php");
    echo "Giriş yapmadınız. Giriş sayfasına yönlendiriliyorsunuz.";
    exit;
}
?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Yönetim Sistemi</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style1.css">
    <link rel="stylesheet" href="assets/css/views.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/script.js"></script>
</head>
<body>
<div id="notification-container">
    <div id="notification-icon">
        Bildirimler (<span id="notification-count">0</span>)
    </div>
    <div id="notification-dropdown" style="display: none;">
        <ul id="notification-list">
            <!-- Bildirimler burada gösterilecek -->
        </ul>
    </div>
</div>
    <!-- İçerik yükleme alanı -->
<div id="content-area">

</div>
</body>
</html>
