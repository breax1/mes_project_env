<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Kullanıcının oturum açıp açmadığını kontrol edin
if (!isset($_SESSION['user_id'])) {
    // Oturum açılmamışsa giriş sayfasına yönlendirin
    header("Location: ../login.php");
    exit();
}


include("../includes/db_config.php");

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['username']) || !in_array('uretim_mudur', explode(',', $_SESSION['role']))) {
    header("Location: login.php");
    exit;
}

// Onay bekleyen keşifleri al
$query = "SELECT k.id, k.project_id, k.location, k.start_date, k.end_date, k.description, k.vehicle, u.username as created_by, p.project_no
          FROM kesif k
          JOIN users u ON k.created_by = u.id
          JOIN projects p ON k.project_id = p.id
          WHERE k.status = 'pending'";
$result = $baglanti->query($query);

// Tüm personel, ekipman ve araçları al
$allPersonnel = getAllPersonnel();
$allEquipment = getAllEquipment();
$allVehicles = getAllVehicles();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keşif Onay Sayfası</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="project-list">
            <h2>Projeler</h2>
            <div id="project-list-content">
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <div class="project-item" data-id="<?php echo $row['id']; ?>">
                        <?php echo $row['project_no']; ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="project-details">
            <h2>Proje Detayları</h2>
            <div id="project-details-content">
                <!-- Proje detayları burada gösterilecek -->
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.project-item').on('click', function() {
                var projectId = $(this).data('id');
                $.ajax({
                    url: '../controllers/get_project_details.php',
                    method: 'GET',
                    data: { id: projectId },
                    success: function(response) {
                        $('#project-details-content').html(response);
                    },
                    error: function() {
                        showNotification('Bir hata oluştu.', 'error');
                    }
                });
            });

            $(document).on('click', '.edit-btn', function() {
                var target = $(this).data('target');
                $(this).siblings('.' + target + '-label').hide();
                $(this).siblings('.' + target + '-select').show();
                $(this).hide();
            });

            $(document).on('click', '.approve-btn, .reject-btn', function() {
                var form = $(this).closest('form');
                var action = $(this).data('action');
                var formData = form.serialize() + '&action=' + action;

                $.ajax({
                    url: '../controllers/approve_kesif.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            showNotification(data.message, 'success');
                            updateProjectList();
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function() {
                        showNotification('Bir hata oluştu.', 'error');
                    }
                });
            });

            function updateProjectList() {
                $.ajax({
                    url: '../controllers/get_pending_projects.php',
                    method: 'GET',
                    success: function(response) {
                        $('#project-list-content').html(response);
                    },
                    error: function() {
                        showNotification('Projeler güncellenirken bir hata oluştu.', 'error');
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
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
    $query = "SELECT DISTINCT vehicle FROM kesif";
    $result = $baglanti->query($query);
    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row['vehicle'];
    }
    return $vehicles;
}
?>