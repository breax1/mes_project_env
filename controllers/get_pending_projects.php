<?php
session_start();
include("../includes/db_config.php");

// Onay bekleyen keşifleri al
$query = "SELECT k.id, p.project_no
          FROM kesif k
          JOIN projects p ON k.project_id = p.id
          WHERE k.status = 'pending'";
$result = $baglanti->query($query);

while ($row = $result->fetch_assoc()) {
    echo '<div class="project-item" data-id="' . $row['id'] . '">' . $row['project_no'] . '</div>';
}
?>