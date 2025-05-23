<?php
// Veritabanı bağlantısını başlat
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

// Son projeleri çekmek için sorgu
$queryActiveProjects = "SELECT id, project_no, project_name FROM projects WHERE status = 'kesif_bekleniyor' ORDER BY project_no DESC";
$resultActiveProjects = $baglanti->query($queryActiveProjects);

// Birimleri çekmek için sorgu
$queryUnits = "SELECT id, unit_name FROM units";
$resultUnits = $baglanti->query($queryUnits);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keşif Girişi</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Keşif Girişi sayfasına özel stiller */
.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f7fa;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.container .form-container {
    background: #ffffff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-left: 5px solid #ff9800; /* Turuncu şerit ile dikkat çekici */
}

.container .form-container h1 {
    font-size: 24px;
    color: #ff9800;
    margin-bottom: 25px;
    font-weight: 700;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.container .form-container form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.container .form-container label {
    font-size: 14px;
    color: #444;
    font-weight: 600;
    margin-bottom: 5px;
}

.container .form-container input[type="text"],
.container .form-container input[type="date"],
.container .form-container input[type="number"],
.container .form-container input[type="file"],
.container .form-container select,
.container .form-container textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    color: #333;
    background: #fafafa;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.container .form-container input[type="text"]:focus,
.container .form-container input[type="date"]:focus,
.container .form-container input[type="number"]:focus,
.container .form-container input[type="file"]:focus,
.container .form-container select:focus,
.container .form-container textarea:focus {
    border-color: #ff9800;
    outline: none;
}

.container .form-container input[readonly] {
    background: #f0f0f0;
    color: #666;
}

.container .form-container textarea {
    resize: vertical;
    min-height: 100px;
}

/* Dinamik satırlar için stiller */
.container .form-container .personnel_row,
.container .form-container .equipment_row,
.container .form-container .raw_material_row {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
}

.container .form-container .personnel_row input,
.container .form-container .equipment_row input {
    flex: 1;
}

.container .form-container .raw_material_row .row_number {
    width: 25px;
    text-align: center;
    font-size: 14px;
    color: #666;
    font-weight: 600;
}

.container .form-container .raw_material_row input[list="stock_list"] {
    flex: 2;
}

.container .form-container .raw_material_row select {
    flex: 1;
}

.container .form-container .raw_material_row input[type="number"] {
    flex: 1;
    min-width: 80px;
}

/* Butonlar */
.container .form-container button {
    background: linear-gradient(135deg, #ff9800, #f57c00);
    color: #fff;
    padding: 12px;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease;
}

.container .form-container button:hover {
    background: linear-gradient(135deg, #f57c00, #e64a19);
    transform: translateY(-2px);
}

.container .form-container button[type="button"] {
    background: #e0e0e0;
    color: #444;
    padding: 10px;
    font-size: 14px;
}

.container .form-container button[type="button"]:hover {
    background: #d0d0d0;
    transform: translateY(-2px);
}

.container .form-container button[type="submit"] {
    margin-top: 20px;
}

/* Responsive Tasarım */
@media (max-width: 600px) {
    .container {
        padding: 15px;
    }

    .container .form-container {
        padding: 20px;
    }

    .container .form-container h1 {
        font-size: 20px;
    }

    .container .form-container label {
        font-size: 13px;
    }

    .container .form-container input[type="text"],
    .container .form-container input[type="date"],
    .container .form-container input[type="number"],
    .container .form-container input[type="file"],
    .container .form-container select,
    .container .form-container textarea {
        padding: 10px;
        font-size: 13px;
    }

    .container .form-container .personnel_row,
    .container .form-container .equipment_row,
    .container .form-container .raw_material_row {
        flex-direction: column;
        gap: 8px;
    }

    .container .form-container .raw_material_row .row_number {
        align-self: flex-start;
    }

    .container .form-container button {
        padding: 10px;
        font-size: 14px;
    }
}

@media (max-width: 400px) {
    .container .form-container button[type="button"] {
        padding: 8px;
        font-size: 13px;
    }

    .container .form-container .raw_material_row input[type="number"] {
        min-width: 60px;
    }
}
</style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1>Keşif Girişi</h1>
            <form id="kesifForm" enctype="multipart/form-data">
                <!-- Proje Seçimi -->
                <label for="project_id">Proje Seçimi:</label>
                <select id="project_id" name="project_id" required>
                    <option value="">Proje seçin</option>
                    <?php while ($project = $resultActiveProjects->fetch_assoc()) { ?>
                        <option value="<?php echo $project['id']; ?>"><?php echo $project['project_no'] . ' - ' . $project['project_name']; ?></option>
                    <?php } ?>
                </select>

                <!-- Proje Konumu -->
                <label for="location">Proje Konumu:</label>
                <input type="text" id="location" name="location" readonly>

                <!-- Başlangıç - Bitiş Tarihi -->
                <label for="start_date">Başlangıç Tarihi:</label>
                <input type="date" id="start_date" name="start_date" required>
                
                <label for="end_date">Bitiş Tarihi:</label>
                <input type="date" id="end_date" name="end_date" required>

                <!-- Proje Notları -->
                <label for="description">Proje Notları:</label>
                <textarea id="description" name="description" rows="4" cols="50" placeholder="Proje notlarını girin"></textarea>

                <!-- Proje Çizimleri ve Fotoğraflar -->
                <label for="drawings_photos">Proje Çizimleri ve Fotoğraflar:</label>
                <input type="file" id="drawings_photos" name="drawings_photos[]" accept="image/*" multiple>

                <!-- Personel -->
                <label for="personnel">Personel:</label>
                <div id="personnel_container">
                    <div class="personnel_row">
                        <input list="personnel_list" name="personnel[]" placeholder="Personel seçin veya girin">
                        <datalist id="personnel_list"></datalist>
                    </div>
                </div>
                <button type="button" id="add_personnel">Personel Ekle</button>

                <!-- Ekipman -->
                <label for="equipment">Ekipman:</label>
                <div id="equipment_container">
                    <div class="equipment_row">
                        <input list="equipment_list" name="equipment[]" placeholder="Ekipman seçin veya girin">
                        <datalist id="equipment_list"></datalist>
                    </div>
                </div>
                <button type="button" id="add_equipment">Ekipman Ekle</button>

                <!-- Araç -->
                <label for="vehicle">Araç:</label>
                <select id="vehicle" name="vehicle">
                    <option value="">Araç seçin</option>
                </select>

                <!-- Ham Madde -->
                <label for="raw_materials">Ham Madde:</label>
                <div id="raw_materials_container">
                    <div class="raw_material_row">
                        <span class="row_number">1</span>
                        <input list="stock_list" name="raw_material_names[]" placeholder="Ham madde seçin veya girin" oninput="updateRawMaterialId(this)">
                        <input type="hidden" name="raw_materials[]" value=""> <!-- ID değeri buraya yazılacak -->
                        <datalist id="stock_list"></datalist>
                        <select name="units[]">
                            <?php while ($unit = $resultUnits->fetch_assoc()) { ?>
                                <option value="<?php echo $unit['id']; ?>"><?php echo $unit['unit_name']; ?></option>
                            <?php } ?>
                        </select>
                        <input type="number" name="quantities[]" placeholder="Miktar">
                    </div>
                </div>
                <button type="button" id="add_raw_material" class="add-button">Ham Madde Ekle</button>

                <button type="submit">Keşfi Kaydet</button>
            </form>
        </div>
    </div>

    <script>
        
        function updateRawMaterialId(inputElement) {
                    var selectedOption = $('#stock_list option[value="' + inputElement.value + '"]');
                    var rawMaterialId = selectedOption.data('id') || ''; // Eğer ID yoksa boş bırak
                    $(inputElement).siblings('input[type="hidden"]').val(rawMaterialId); // Hidden input'a ID'yi yaz
        }
        
        $(document).ready(function() {
            // Proje seçildiğinde proje konumunu güncelle
            $('#project_id').on('change', function() {
                var projectId = $(this).val();
                if (projectId) {
                    $.ajax({
                        url: '../controllers/get_project_location.php',
                        method: 'GET',
                        data: { project_id: projectId },
                        success: function(data) {
                            $('#location').val(data);
                        },
                        error: function() {
                            showNotification('Proje konumu alınırken bir hata oluştu.', 'error');
                        }
                    });
                } else {
                    $('#location').val('');
                }
            });

            // Başlangıç ve bitiş tarihleri değiştiğinde personel, ekipman ve araçları güncelle
            $('#start_date, #end_date').on('change', function() {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                if (startDate && endDate) {
                    updatePersonnel(startDate, endDate);
                    updateEquipment(startDate, endDate);
                    updateVehicles(startDate, endDate);
                }
            });

            // Personel güncelleme fonksiyonu
            function updatePersonnel(startDate, endDate) {
                $.ajax({
                    url: '../controllers/get_available_personnel.php',
                    method: 'GET',
                    data: { start_date: startDate, end_date: endDate },
                    success: function(data) {
                        $('#personnel_list').html(data);
                    },
                    error: function() {
                        showNotification('Personel bilgileri alınırken bir hata oluştu.', 'error');
                    }
                });
            }

            // Ekipman güncelleme fonksiyonu
            function updateEquipment(startDate, endDate) {
                $.ajax({
                    url: '../controllers/get_available_equipment.php',
                    method: 'GET',
                    data: { start_date: startDate, end_date: endDate },
                    success: function(data) {
                        $('#equipment_list').html(data);
                    },
                    error: function() {
                        showNotification('Ekipman bilgileri alınırken bir hata oluştu.', 'error');
                    }
                });
            }

            // Araç güncelleme fonksiyonu
            function updateVehicles(startDate, endDate) {
                $.ajax({
                    url: '../controllers/get_available_vehicles.php',
                    method: 'GET',
                    data: { start_date: startDate, end_date: endDate },
                    success: function(data) {
                        $('#vehicle').html(data);
                    },
                    error: function() {
                        showNotification('Araç bilgileri alınırken bir hata oluştu.', 'error');
                    }
                });
            }

            // Ham madde ekleme fonksiyonu
            $('#add_raw_material').on('click', function() {
                var rowNumber = $('#raw_materials_container .raw_material_row').length + 1;
                var newRow = `
                    <div class="raw_material_row">
                        <span class="row_number">${rowNumber}</span>
                        <input list="stock_list" name="raw_material_names[]" placeholder="Ham madde seçin veya girin" oninput="updateRawMaterialId(this)">
                        <input type="hidden" name="raw_materials[]" value="">
                        <select name="units[]">
                            <?php
                            $resultUnits->data_seek(0); // Birim listesini yeniden başlat
                            while ($unit = $resultUnits->fetch_assoc()) { ?>
                                <option value="<?php echo $unit['id']; ?>"><?php echo $unit['unit_name']; ?></option>
                            <?php } ?>
                        </select>
                        <input type="number" name="quantities[]" placeholder="Miktar">
                    </div>
                `;
                $('#raw_materials_container').append(newRow);
            });

            // Personel ekleme fonksiyonu
            $('#add_personnel').on('click', function() {
                var newRow = `
                    <div class="personnel_row">
                        <input list="personnel_list" name="personnel[]" placeholder="Personel seçin veya girin">
                    </div>
                `;
                $('#personnel_container').append(newRow);
            });

            // Ekipman ekleme fonksiyonu
            $('#add_equipment').on('click', function() {
                var newRow = `
                    <div class="equipment_row">
                        <input list="equipment_list" name="equipment[]" placeholder="Ekipman seçin veya girin">
                    </div>
                `;
                $('#equipment_container').append(newRow);
            });

            function loadStockList() {
                $.ajax({
                    url: '../controllers/get_stock_list.php',
                    method: 'GET',
                    success: function(data) {
                        var stockList = JSON.parse(data);
                        var datalist = $('#stock_list');
                        datalist.empty();
                        stockList.forEach(function(item) {
                            datalist.append('<option value="' + item.urun_adi + ' (' + item.urun_kodu + ')" data-id="' + item.id + '"></option>');
                        });
                    },
                    error: function() {
                        showNotification('Stok listesi alınırken bir hata oluştu.', 'error');
                    }
                });
            }

            // Sayfa yüklendiğinde stok listesini doldur
            loadStockList();


            // Formu AJAX ile gönder
            $('#kesifForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);

                $.ajax({
                    url: '../controllers/save_kesif.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            showNotification(data.message, 'success');
                        } else {
                            showNotification(data.message, 'error');
                        }
                        // Proje durumunu güncelle
                        var projectId = $('#project_id').val();
                        if (projectId) {
                            $.ajax({
                                url: '../controllers/update_project_status.php',
                                method: 'POST',
                                data: { project_id: projectId, status: 'kesif' },
                                success: function(response) {
                                    console.log('Proje durumu güncellendi: ' + response);
                                },
                                error: function() {
                                    showNotification('Proje durumu güncellenirken bir hata oluştu.', 'error');
                                }
                            });
                        }
                    },
                    error: function() {
                        showNotification('Keşif kaydedilirken bir hata oluştu.', 'error');
                    }
                });

            });
        });
    </script>
</body>
</html>