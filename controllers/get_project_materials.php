<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../includes/db_config.php");

// in_progress olan projeleri al
$queryProjects = "SELECT id, project_name FROM projects WHERE status = 'in_progress'";
$resultProjects = $baglanti->query($queryProjects);

$projects = [];
while ($project = $resultProjects->fetch_assoc()) {
    $projectId = $project['id'];

    // Bu projeye ait malzemeleri al
    $queryMaterials = "SELECT pm.material_id, pm.prepared, s.urun_adi 
                       FROM project_materials pm 
                       JOIN stock s ON pm.material_id = s.id 
                       WHERE pm.project_id = ?";
    $stmtMaterials = $baglanti->prepare($queryMaterials);
    $stmtMaterials->bind_param("i", $projectId);
    $stmtMaterials->execute();
    $resultMaterials = $stmtMaterials->get_result();

    $materials = [];
    while ($material = $resultMaterials->fetch_assoc()) {
        $materials[] = [
            'material_id' => $material['material_id'],
            'material_name' => $material['urun_adi'],
            'prepared' => $material['prepared']
        ];
    }

    $projects[] = [
        'project_id' => $projectId,
        'project_name' => $project['project_name'],
        'materials' => $materials
    ];
}

echo json_encode($projects);
?>