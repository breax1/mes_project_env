<?php
session_start();
include("../includes/db_config.php");

if (isset($_GET['project_id'])) {
    $projectId = $_GET['project_id'];

    // Proje detaylarını çekmek için sorgu
    $queryProject = "SELECT * FROM projects WHERE id = ?";
    $stmtProject = $baglanti->prepare($queryProject);
    $stmtProject->bind_param("i", $projectId);
    $stmtProject->execute();
    $resultProject = $stmtProject->get_result();
    $project = $resultProject->fetch_assoc();

    // Keşif detaylarını çekmek için sorgu
    $queryKesif = "SELECT * FROM kesif WHERE project_id = ?";
    $stmtKesif = $baglanti->prepare($queryKesif);
    $stmtKesif->bind_param("i", $projectId);
    $stmtKesif->execute();
    $resultKesif = $stmtKesif->get_result();
    $kesif = $resultKesif->fetch_assoc();

    // Hammaddeleri çekmek için sorgu
    $queryRawMaterials = "SELECT krm.*, s.urun_adi 
                          FROM kesif_raw_materials krm 
                          JOIN stock s ON krm.raw_material_id = s.id 
                          WHERE krm.kesif_id = ?";
    $stmtRawMaterials = $baglanti->prepare($queryRawMaterials);
    $stmtRawMaterials->bind_param("i", $kesif['id']);
    $stmtRawMaterials->execute();
    $resultRawMaterials = $stmtRawMaterials->get_result();

    // Personelleri çekmek için sorgu
    $queryPersonnel = "SELECT * FROM kesif_personnel WHERE kesif_id = ?";
    $stmtPersonnel = $baglanti->prepare($queryPersonnel);
    $stmtPersonnel->bind_param("i", $kesif['id']);
    $stmtPersonnel->execute();
    $resultPersonnel = $stmtPersonnel->get_result();

    // Ekipmanları çekmek için sorgu
    $queryEquipment = "SELECT * FROM kesif_equipment WHERE kesif_id = ?";
    $stmtEquipment = $baglanti->prepare($queryEquipment);
    $stmtEquipment->bind_param("i", $kesif['id']);
    $stmtEquipment->execute();
    $resultEquipment = $stmtEquipment->get_result();

    // Araçları çekmek için sorgu
    $queryVehicles = "SELECT * FROM kesif_vehicles WHERE kesif_id = ?";
    $stmtVehicles = $baglanti->prepare($queryVehicles);
    $stmtVehicles->bind_param("i", $kesif['id']);
    $stmtVehicles->execute();
    $resultVehicles = $stmtVehicles->get_result();

    // Proje detaylarını göster
    echo "<h2>Proje Detayları</h2>";
    echo "<p>Proje No: " . $project['project_no'] . "</p>";
    echo "<p>Proje Adı: " . $project['project_name'] . "</p>";
    echo "<p>Proje Açıklaması: " . $project['description'] . "</p>";

    // Keşif detaylarını göster
    echo "<h2>Keşif Detayları</h2>";
    echo "<p>Konum: " . $kesif['location'] . "</p>";
    echo "<p>Başlangıç Tarihi: " . $kesif['start_date'] . "</p>";
    echo "<p>Bitiş Tarihi: " . $kesif['end_date'] . "</p>";
    echo "<p>Açıklama: " . $kesif['description'] . "</p>";

    // Hammaddeleri göster
    echo "<h2>Hammaddeler</h2>";
    echo "<table>";
    echo "<thead><tr><th>Malzeme Adı</th><th>Birim</th><th>Miktar</th><th>Birim Fiyat</th><th>Toplam Fiyat</th></tr></thead>";
    echo "<tbody>";
    while ($rawMaterial = $resultRawMaterials->fetch_assoc()) {
        // Birim adını units tablosundan çek
        $queryUnit = "SELECT unit_name FROM units WHERE id = ?";
        $stmtUnit = $baglanti->prepare($queryUnit);
        $stmtUnit->bind_param("i", $rawMaterial['unit']);
        $stmtUnit->execute();
        $resultUnit = $stmtUnit->get_result();
        $unit = $resultUnit->fetch_assoc();

        // Son fiyatı çekmek için sorgu
        $queryPrice = "SELECT price FROM raw_material_prices WHERE kesif_raw_material_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmtPrice = $baglanti->prepare($queryPrice);
        $stmtPrice->bind_param("i", $rawMaterial['id']);
        $stmtPrice->execute();
        $resultPrice = $stmtPrice->get_result();
        $price = $resultPrice->fetch_assoc();

        $lastPrice = $price ? $price['price'] : 0;
        $totalPrice = $rawMaterial['amount'] * $lastPrice;

        echo "<tr>";
        echo "<td>" . $rawMaterial['urun_adi'] . "</td>"; // raw_material_id yerine urun_adi yazdırılıyor
        echo "<td>" . $unit['unit_name'] . "</td>";
        echo "<td>" . $rawMaterial['amount'] . "</td>";
        echo "<td>" . $lastPrice . "</td>";
        echo "<td>" . $totalPrice . "</td>";
        echo "</tr>";

        $stmtUnit->close();
        $stmtPrice->close();
    }
    echo "</tbody>";
    echo "</table>";

    // Personelleri göster
    echo "<h2>Personeller</h2>";
    echo "<ul>";
    while ($personnel = $resultPersonnel->fetch_assoc()) {
        echo "<li>" . $personnel['personnel_main_name'] . "</li>";
    }
    echo "</ul>";

    // Ekipmanları göster
    echo "<h2>Ekipmanlar</h2>";
    echo "<ul>";
    while ($equipment = $resultEquipment->fetch_assoc()) {
        echo "<li>" . $equipment['equipment_name'] . "</li>";
    }
    echo "</ul>";

    // Araçları göster
    echo "<h2>Araçlar</h2>";
    echo "<ul>";
    while ($vehicle = $resultVehicles->fetch_assoc()) {
        echo "<li>" . $vehicle['vehicle_name'] . "</li>";
    }
    echo "</ul>";

    $stmtProject->close();
    $stmtKesif->close();
    $stmtRawMaterials->close();
    $stmtPersonnel->close();
    $stmtEquipment->close();
    $stmtVehicles->close();
    $baglanti->close();
}
?>