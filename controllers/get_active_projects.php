<?php
// Veritabanı bağlantısını başlat
include("../includes/db_config.php");

// Aktif projeleri çekmek için sorgu
$queryActiveProjects = "SELECT project_no, project_name, status FROM projects WHERE status != 'completed' ORDER BY project_no DESC";
$resultActiveProjects = $baglanti->query($queryActiveProjects);

$activeProjects = array();
while ($project = $resultActiveProjects->fetch_assoc()) {
    $activeProjects[] = $project;
}

// JSON formatında döndür
header('Content-Type: application/json');
echo json_encode($activeProjects);
?>