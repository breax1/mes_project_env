<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

if (isset($_GET['id'])) {
    $kesifId = $_GET['id'];

    // Proje detaylarını al
    $query = "SELECT k.id, k.project_id, k.location, k.start_date, k.end_date, k.description, u.username as created_by
              FROM kesif k
              JOIN users u ON k.created_by = u.id
              WHERE k.id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $kesifId);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();

    // Personel, ekipman ve araçları al
    $personnel = getKesifPersonnel($kesifId);
    $equipment = getKesifEquipment($kesifId);
    $vehicles = getKesifVehicles($kesifId);

    // HTML içeriği oluştur
    ob_start();
    ?>
    <h3>Proje ID: <?php echo $project['project_id']; ?></h3>
    <p>Konum: <?php echo $project['location']; ?></p>
    <p>Başlangıç Tarihi: <?php echo $project['start_date']; ?></p>
    <p>Bitiş Tarihi: <?php echo $project['end_date']; ?></p>
    <p>Açıklama: <?php echo $project['description']; ?></p>
    <p>Araçlar:</p>
    <?php foreach ($vehicles as $vehicle) { ?>
        <p>
            <span class="vehicle-label"><?php echo $vehicle; ?></span>
            <select name="vehicle[]" class="vehicle-select" style="display:none;">
                <?php foreach (getAllVehicles() as $allVehicle) { ?>
                    <option value="<?php echo $allVehicle; ?>" <?php echo $allVehicle == $vehicle ? 'selected' : ''; ?>>
                        <?php echo $allVehicle; ?>
                    </option>
                <?php } ?>
            </select>
            <button type="button" class="edit-btn" data-target="vehicle">Değiştir</button>
        </p>
    <?php } ?>
    <p>Oluşturan: <?php echo $project['created_by']; ?></p>
    <p>Personel:</p>
    <?php foreach ($personnel as $person) { ?>
        <p>
            <span class="personnel-label"><?php echo $person; ?></span>
            <select name="personnel[]" class="personnel-select" style="display:none;">
                <?php foreach (getAllPersonnel() as $allPerson) { ?>
                    <option value="<?php echo $allPerson; ?>" <?php echo $allPerson == $person ? 'selected' : ''; ?>>
                        <?php echo $allPerson; ?>
                    </option>
                <?php } ?>
            </select>
            <button type="button" class="edit-btn" data-target="personnel">Değiştir</button>
        </p>
    <?php } ?>
    <p>Ekipman:</p>
    <?php foreach ($equipment as $equip) { ?>
        <p>
            <span class="equipment-label"><?php echo $equip; ?></span>
            <select name="equipment[]" class="equipment-select" style="display:none;">
                <?php foreach (getAllEquipment() as $allEquip) { ?>
                    <option value="<?php echo $allEquip; ?>" <?php echo $allEquip == $equip ? 'selected' : ''; ?>>
                        <?php echo $allEquip; ?>
                    </option>
                <?php } ?>
            </select>
            <button type="button" class="edit-btn" data-target="equipment">Değiştir</button>
        </p>
    <?php } ?>
    <form class="approval-form">
        <input type="hidden" name="kesif_id" value="<?php echo $project['id']; ?>">
        <button type="button" class="approve-btn" data-action="approve">Onayla</button>
        <button type="button" class="reject-btn" data-action="reject">Reddet</button>
    </form>
    <?php
    $html = ob_get_clean();
    echo $html;
}

function getKesifPersonnel($kesifId) {
    global $baglanti;
    $query = "SELECT personnel_main_name FROM kesif_personnel WHERE kesif_id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $kesifId);
    $stmt->execute();
    $result = $stmt->get_result();
    $personnel = [];
    while ($row = $result->fetch_assoc()) {
        $personnel[] = $row['personnel_main_name'];
    }
    $stmt->close();
    return $personnel;
}

function getKesifEquipment($kesifId) {
    global $baglanti;
    $query = "SELECT equipment_name FROM kesif_equipment WHERE kesif_id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $kesifId);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipment = [];
    while ($row = $result->fetch_assoc()) {
        $equipment[] = $row['equipment_name'];
    }
    $stmt->close();
    return $equipment;
}

function getKesifVehicles($kesifId) {
    global $baglanti;
    $query = "SELECT vehicle_name FROM kesif_vehicles WHERE kesif_id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $kesifId);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row['vehicle_name'];
    }
    $stmt->close();
    return $vehicles;
}

function getAllPersonnel() {
    global $baglanti;
    $query = "SELECT DISTINCT personnel_main_name FROM kesif_personnel";
    $result = $baglanti->query($query);
    $personnel = [];
    while ($row = $result->fetch_assoc()) {
        $personnel[] = $row['personnel_main_name'];
    }
    return $personnel;
}

function getAllEquipment() {
    global $baglanti;
    $query = "SELECT DISTINCT equipment_name FROM kesif_equipment";
    $result = $baglanti->query($query);
    $equipment = [];
    while ($row = $result->fetch_assoc()) {
        $equipment[] = $row['equipment_name'];
    }
    return $equipment;
}

function getAllVehicles() {
    global $baglanti;
    $query = "SELECT DISTINCT vehicle_name FROM kesif_vehicles";
    $result = $baglanti->query($query);
    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row['vehicle_name'];
    }
    return $vehicles;
}
?>