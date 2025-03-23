<?php
include("../includes/db_config.php");

date_default_timezone_set('Europe/Istanbul'); // Saat dilimini ayarla

// Personelleri çekmek için sorgu
$queryPersonel = "SELECT * FROM personnel WHERE active = 1";
$resultPersonel = $baglanti->query($queryPersonel);

// Personel giriş çıkış verilerini çekmek için sorgu
$queryGirisCikis = "SELECT * FROM giris_cikis ORDER BY time DESC";
$resultGirisCikis = $baglanti->query($queryGirisCikis);

// Personel durumlarını belirlemek için bir dizi oluştur
$personelDurum = [];
$personelGirisZamani = [];
while ($girisCikis = $resultGirisCikis->fetch_assoc()) {
    if (!isset($personelDurum[$girisCikis['personnel_id']])) {
        $personelDurum[$girisCikis['personnel_id']] = $girisCikis['islem'];
        if ($girisCikis['islem'] == 'giris') {
            $personelGirisZamani[$girisCikis['personnel_id']] = $girisCikis['time'];
        }
    }
}

// Çalışma süresini hesaplamak için bir fonksiyon   
function calismaSuresi($girisZamani) {
    $giris = new DateTime($girisZamani);
    $simdi = new DateTime();
    $fark = $simdi->diff($giris);

    // Toplam saat ve dakikayı hesapla
    $toplamSaat = ($fark->days * 24) + $fark->h; // Günleri saatlere çevir ve ekle
    $toplamDakika = $fark->i;

    return sprintf('%02d:%02d', $toplamSaat, $toplamDakika);
}

$calismayanPersoneller = [];
$calisanPersoneller = [];

while ($personel = $resultPersonel->fetch_assoc()) {
    if (!isset($personelDurum[$personel['id']]) || $personelDurum[$personel['id']] == 'cikis') {
        $calismayanPersoneller[] = [
            'id' => $personel['id'],
            'personnel_main_name' => $personel['personnel_main_name'],
            'status' => 'Çalışmıyor'
        ];
    } else if ($personelDurum[$personel['id']] == 'giris') {
        $calisanPersoneller[] = [
            'id' => $personel['id'],
            'personnel_main_name' => $personel['personnel_main_name'],
            'status' => 'Çalışıyor',
            'calisma_suresi' => calismaSuresi($personelGirisZamani[$personel['id']])
        ];
    }
}

// Son yapılan giriş-çıkış işlemlerini çekmek için sorgu
$queryLogs = "SELECT p.id, p.personnel_main_name, l.islem, l.time 
              FROM giris_cikis l 
              JOIN personnel p ON l.personnel_id = p.id 
              ORDER BY l.time DESC 
              LIMIT 40";
$resultLogs = $baglanti->query($queryLogs);

$logs = [];
while ($log = $resultLogs->fetch_assoc()) {
    $logs[] = [
        'id' => $log['id'],
        'personnel_main_name' => $log['personnel_main_name'],
        'islem' => $log['islem'],
        'time' => $log['time']
    ];
}

echo json_encode([
    'calismayanPersoneller' => $calismayanPersoneller,
    'calisanPersoneller' => $calisanPersoneller,
    'logs' => $logs
]);
?>