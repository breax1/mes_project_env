<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../../includes/db_config.php");

date_default_timezone_set('Europe/Istanbul'); // Saat dilimini ayarla

// Çalışmayan personelleri çekmek için sorgu
$queryCalismayanPersoneller = "
    SELECT personnel.id, personnel.personnel_name 
    FROM personnel 
    LEFT JOIN giris_cikis ON personnel.id = giris_cikis.personnel_id 
    WHERE giris_cikis.islem IS NULL OR giris_cikis.islem = 'cikis'
";
$resultCalismayanPersoneller = $baglanti->query($queryCalismayanPersoneller);

$calismayanPersoneller = [];
while ($row = $resultCalismayanPersoneller->fetch_assoc()) {
    $calismayanPersoneller[] = $row['personnel_name'];
}

// Eğer çalışmayan personel yoksa, scripti sonlandır
if (empty($calismayanPersoneller)) {
    exit;
}

// Üretim müdürlerini çekmek için sorgu
$queryUretimMudurlari = "
    SELECT id 
    FROM users 
    WHERE FIND_IN_SET('uretim_mudur', role)
";
$resultUretimMudurlari = $baglanti->query($queryUretimMudurlari);

$uretimMudurlari = [];
while ($row = $resultUretimMudurlari->fetch_assoc()) {
    $uretimMudurlari[] = $row['id'];
}

// Bildirim mesajı
$message = "Aşağıdaki personeller şu anda çalışmıyor:\n\n" . implode("\n", $calismayanPersoneller);

// Bildirimi notifications tablosuna ekle
$queryNotification = "INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)";
$stmtNotification = $baglanti->prepare($queryNotification);

foreach ($uretimMudurlari as $userId) {
    $stmtNotification->bind_param("is", $userId, $message);
    if ($stmtNotification->execute()) {
        echo "Bildirim başarıyla gönderildi.\n";
    } else {
        echo "Bildirim gönderilemedi: " . $stmtNotification->error . "\n";
    }
}

$stmtNotification->close();
$baglanti->close();
?>