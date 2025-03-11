<?php
include("../includes/db_config.php");

$projectId = $_GET['project_id'];

$query = "SELECT location FROM projects WHERE id = ?";
$stmt = $baglanti->prepare($query);
$stmt->bind_param("i", $projectId);
$stmt->execute();
$stmt->bind_result($location);
$stmt->fetch();

echo $location;

$stmt->close();
$baglanti->close();
?>


