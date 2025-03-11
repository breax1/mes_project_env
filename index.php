<?php 
session_start();
include("includes/db_config.php");




date_default_timezone_set('Europe/Istanbul'); // Saat dilimini ayarla

// Oturumda kullanıcı adı varsa, kullanıcı giriş yapmış demektir
if(isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $currentUserId = $_SESSION['user_id']; // currentUserId'yi oturumdan al

    // Kullanıcı adına göre ad, soyad ve rol bilgisini veritabanından al
    $stmt = $baglanti->prepare("SELECT username, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    // Sorgu sonucunda bir satır varsa, ad, soyad ve rolü alın
    if($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $username = $row['username'];
        $roles = explode(',', $row['role']); // Rolleri diziye dönüştür
        // Oturum değişkenlerine ad, soyad ve rolleri kaydet
        $_SESSION['roles'] = $roles;

        // Profil fotoğrafı ve isim soyisim için sorgu
        $query = "SELECT pp_location, name, surname FROM users WHERE id = ?";
        $stmt = $baglanti->prepare($query);
        $stmt->bind_param("i", $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $pp_location = $row['pp_location'];
        $name = $row['name'];
        $surname = $row['surname'];
    } else {
        // Kullanıcı bilgileri alınamadıysa, hata oluştuğunu belirtin
        echo "Kullanıcı bilgileri alınamadı.";
        exit; // Hata olduğunda betiği sonlandırın
        header("Location: views/login.php");
    }
} else {
    // Kullanici giris yapmamissa giris sayfasina yonlendir
    header("Location: views/login.php");
    echo "Giriş yapmadınız. Giriş sayfasına yönlendiriliyorsunuz.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://unpkg.com/feather-icons"></script> <!-- Feather Icons CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/script.js"></script>
    <title>METALES PORTAL</title>
</head>
<body>
    <header class="header">
        <img src="../assets/images/metalesdarkyatay.png" alt="Logo" class="logo" id="dashboard-logo" style="cursor: pointer;">
        <div class="sidebar">
            <nav class="menu">
                <ul>
                    <button href="#" class="menu-link" data-page="views/dashboard.php">DASHBOARD</button>
                    <?php if (array_intersect($roles, ['admin', 'engineer', 'technician'])): ?>
                        <button href="#" class="menu-link" data-page="views/project_entry.php">PROJELER</button>
                    <?php endif; ?>
                    <?php if (array_intersect($roles, ['admin', 'engineer'])): ?>
                        <button href="#" class="menu-link" data-page="views/kesif.php">KESIF</button>
                    <?php endif; ?>
                    <?php if (array_intersect($roles, ['superadmin'])): ?>
                        <button href="#" class="menu-link" data-page="views/approval.php">KESIF ONAY</button>
                    <?php endif; ?>
                    <?php if (array_intersect($roles, ['admin', 'warehouse'])): ?>
                        <button href="#" class="menu-link" data-page="views/fiyatlistesi.php">FIYAT LISTESI</button>
                    <?php endif; ?>
                    <?php if (array_intersect($roles, ['admin', 'satin_alma'])): ?>
                        <button href="#" class="menu-link" data-page="views/teklif.php">TEKLIF</button>
                    <?php endif; ?>
                    <?php if (array_intersect($roles, ['superadmin'])): ?>
                        <button href="#" class="menu-link" data-page="views/ucuncu_parti.php">UCUNCU PARTI</button>
                    <?php endif; ?>
                    <?php if (array_intersect($roles, ['admin', 'teklif_detay'])): ?>
                        <button href="#" class="menu-link" data-page="views/teklif_detay.php">TEKLIF DETAY</button>
                    <?php endif; ?>
                    <?php if (array_intersect($roles, ['admin', 'muhasebe'])): ?>
                        <button href="#" class="menu-link" data-page="views/personel.php">PERSONEL</button>
                    <?php endif; ?>
                    <?php if (array_intersect($roles, ['admin', 'depo'])): ?>
                        <button href="#" class="menu-link" data-page="views/depo.php">DEPO</button>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>

        <div class="profile-container">
            <img id="profile-img" src="<?php echo $pp_location; ?>" alt="Profil Fotoğrafı" class="profile-img">
            <div class="profile-dropdown">
                <a href="#"><?php echo $name . ' ' . $surname; ?></a>
                <a href="views/logout.php">Çıkış Yap</a>
            </div>
            <div class="notification-container" id="notification-container">
                <div id="notification-icon">
                    <i class="fas fa-bell"></i> <!-- Bildirim simgesi (Font Awesome kullanarak) -->
                    <span id="notification-count" class="notification-count">0</span> <!-- Daire içinde bildirim sayısı -->
                </div>
                <div id="notification-dropdown" style="display: none;">
                    <ul id="notification-list">
                        <!-- Bildirimler burada gösterilecek -->
                    </ul>
                </div>
            </div>
        </div>
        
    </header>
    <div id="content-area">
        <!-- İçerik yükleme alanı -->
    </div>
    
    <div id="notification-box" class="notification-box">
        <button class="close-btn">&times;</button>
        <div class="notification-message"></div>
    </div>

    <div id="custom-notification-wrapper" class="custom-notification-wrapper"></div>

    <footer class="footer">
        <p style="margin-left: 10px;"><em><strong>Powered by PACS</strong></em></p>
        <div class="menu-link settings-icon no-background" data-page="../config/settings.php" >⚙️</div> <!-- Ayarlar ikonu -->
    </footer>
    
    <script>
        document.getElementById('dashboard-logo').addEventListener('click', function() {
            document.querySelector('button[data-page="views/dashboard.php"]').click();
        });
        
        function updateProfileImage(newImagePath) {
        document.getElementById('profile-img').src = newImagePath;
        }
    </script>
</body>
</html>