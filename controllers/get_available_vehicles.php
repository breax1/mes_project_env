<?php
include("../includes/db_config.php");

$startDate = $_GET['start_date'];
$endDate = $_GET['end_date'];

$query = "SELECT vehicle_name FROM vehicles WHERE id NOT IN (
            SELECT vehicle_id FROM vehicle_assignments WHERE (start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?)
          )";
$stmt = $baglanti->prepare($query);
$stmt->bind_param("ssss", $endDate, $startDate, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo '<option value="' . $row['vehicle_name'] . '">' . $row['vehicle_name'] . '</option>';
}

$stmt->close();
$baglanti->close();
?>