<?php
include("../includes/db_config.php");

date_default_timezone_set('Europe/Istanbul'); // Saat dilimini ayarla

$personnelId = filter_input(INPUT_GET, 'personnelId', FILTER_SANITIZE_STRING) ?? 'all';
$startDate = filter_input(INPUT_GET, 'startDate', FILTER_SANITIZE_STRING) ?? '';
$endDate = filter_input(INPUT_GET, 'endDate', FILTER_SANITIZE_STRING) ?? '';
$includeTime = filter_input(INPUT_GET, 'includeTime', FILTER_VALIDATE_BOOLEAN) ?? false;
$startTime = filter_input(INPUT_GET, 'startTime', FILTER_SANITIZE_STRING) ?? '';
$endTime = filter_input(INPUT_GET, 'endTime', FILTER_SANITIZE_STRING) ?? '';
$includeGiris = filter_input(INPUT_GET, 'includeGiris', FILTER_VALIDATE_BOOLEAN) ?? false;
$includeCikis = filter_input(INPUT_GET, 'includeCikis', FILTER_VALIDATE_BOOLEAN) ?? false;

$query = "SELECT personnel.personnel_main_name, giris_cikis.islem, giris_cikis.time 
          FROM giris_cikis 
          JOIN personnel ON giris_cikis.personnel_id = personnel.id 
          WHERE 1=1";

if ($personnelId !== 'all') {
    $query .= " AND personnel.id = '$personnelId'";
}

if ($startDate) {
    $query .= " AND DATE(giris_cikis.time) >= '$startDate'";
}

if ($endDate) {
    $query .= " AND DATE(giris_cikis.time) <= '$endDate'";
}

if ($includeTime && $startTime && $endTime) {
    $query .= " AND TIME(giris_cikis.time) BETWEEN '$startTime' AND '$endTime'";
}

// Filtreleme işlemi
if ($includeGiris && !$includeCikis) {
    $query .= " AND giris_cikis.islem = 'giris'";
} elseif (!$includeGiris && $includeCikis) {
    $query .= " AND giris_cikis.islem = 'cikis'";
} elseif ($includeGiris && $includeCikis) {
    $query .= " AND (giris_cikis.islem = 'giris' OR giris_cikis.islem = 'cikis')";
} else {
    // Eğer her iki checkbox da seçili değilse, hiçbir kayıt getirilmesin
    $query .= " AND 0";
}

$query .= " ORDER BY giris_cikis.time DESC";

$result = $baglanti->query($query);

$girisCikisData = [];
while ($row = $result->fetch_assoc()) {
    $girisCikisData[] = $row;
}

echo json_encode($girisCikisData);
?>