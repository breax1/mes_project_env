<?php

session_start();
include("../includes/db_config.php");
$currentUserId = $_SESSION['user_id'];
$query = "SELECT pp_location FROM users WHERE id = ? ";
$stmt = $baglanti->prepare($query);
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$pp_location = $row['pp_location'];
?>

<!-- menu.php -->
<div class="floating-menu">
  

  <div class="profile-container">
    <img src="<?php echo $pp_location; ?>" alt="Profil Fotoğrafı" class="profile-img">
    <div class="menu-options">
      <ul>
        <li><a href="#" class="menu-link" data-page="../config/settings.php">Ayarlar</a></li>
        <li><a href="views/logout.php">Çıkış</a></li>
      </ul>
    </div>
  </div>

  <!-- Yeni Menü Eklemesi -->
  <div class="sidebar">
    <div class="logo-container">
      <img src="../assets/images/metaleswhiteyatay.png" alt="Logo" class="logo">
    </div>
    <ul>
      <li><a href="#" class="menu-link" data-page="views/dashboard.php">DASHBOARD</a></li>
      <li><a href="#" class="menu-link" data-page="views/project_entry.php">PROJELER</a></li>
      <li><a href="#" class="menu-link" data-page="views/kesif.php">KESIF</a></li>
      <li><a href="#" class="menu-link" data-page="views/approval.php">KESIF ONAY</a></li>
      <li><a href="#" class="menu-link" data-page="views/fiyatlistesi.php">FIYAT LISTESI</a></li>
      <li><a href="#" class="menu-link" data-page="views/teklif.php">TEKLIF</a></li>
      <li><a href="#" class="menu-link" data-page="views/ucuncu_parti.php">UCUNCU PARTI</a></li>
      <li><a href="#" class="menu-link" data-page="views/teklif_detay.php">TEKLIF DETAY</a></li>
      <li><a href="#" class="menu-link" data-page="views/personel.php">PERSONEL</a></li>
      <li><a href="#" class="menu-link" data-page="views/depo.php">DEPO</a></li>
    </ul>
  </div>
</div>
