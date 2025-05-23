<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proposalId = $_POST['proposal_id'];
    $action = $_POST['action'];

    // Teklifin durumunu güncelle
    $status = ($action === 'approve') ? 1 : 2;
    $query = "UPDATE propal SET status = ? WHERE id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("ii", $status, $proposalId);
    $stmt->execute();

    if ($action === 'approve') {
        // Teklif onaylandıysa kesif_id'yi bul
        $query = "SELECT project_id FROM propal WHERE id = ?";
        $stmt = $baglanti->prepare($query);
        $stmt->bind_param("i", $proposalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $proposal = $result->fetch_assoc();
        $projectId = $proposal['project_id'];

        // Kesif tablosundan kesif_id'yi al
        $query = "SELECT id FROM kesif WHERE project_id = ?";
        $stmt = $baglanti->prepare($query);
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $kesif = $result->fetch_assoc();
        $kesifId = $kesif['id'];

        // Kesif_raw_materials tablosundan malzeme bilgilerini al
        $query = "SELECT raw_material_id, amount FROM kesif_raw_materials WHERE kesif_id = ?";
        $stmt = $baglanti->prepare($query);
        $stmt->bind_param("i", $kesifId);
        $stmt->execute();
        $result = $stmt->get_result();

        // Project_materials tablosuna ekle
        while ($material = $result->fetch_assoc()) {
            $rawMaterialId = $material['raw_material_id'];
            $amount = $material['amount'];

            // raw_material_id'nin stock tablosunda olup olmadığını kontrol et
            $queryCheck = "SELECT id FROM stock WHERE id = ?";
            $stmtCheck = $baglanti->prepare($queryCheck);
            $stmtCheck->bind_param("i", $rawMaterialId);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();

            if ($resultCheck->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => "Malzeme ID'si ($rawMaterialId) stock tablosunda bulunamadı."]);
                exit();
            }

            // Eğer malzeme mevcutsa project_materials tablosuna ekle
            $query = "INSERT INTO project_materials (project_id, material_id, quantity_used) VALUES (?, ?, ?)";
            $stmt = $baglanti->prepare($query);
            $stmt->bind_param("iii", $projectId, $rawMaterialId, $amount);
            $stmt->execute();
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'İşlem başarıyla tamamlandı.']);
}
?>