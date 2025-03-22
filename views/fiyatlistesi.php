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

// Keşifleri çekmek için sorgu
$queryKesifler = "SELECT k.id, p.project_no FROM kesif k JOIN projects p ON k.project_id = p.id ORDER BY k.id DESC";
$resultKesifler = $baglanti->query($queryKesifler);

// Birimleri çekmek için sorgu
$queryUnits = "SELECT id, unit_name FROM units";
$resultUnits = $baglanti->query($queryUnits);
$units = [];
while ($unit = $resultUnits->fetch_assoc()) {
    $units[$unit['id']] = $unit['unit_name'];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiyat Listesi</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>

        /* Fiyat Listesi sayfasına özel stiller */
.container {
    max-width: 1200px;
    min-width: 1800px;
    min-height: 950px;
    margin: 0 auto;
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 25px;
    background: #f5f7fa;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.container .kesif-list {
    background: #ffffff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-left: 5px solid #673ab7; /* Mor şerit ile dikkat çekici */
}

.container .kesif-list h1 {
    font-size: 22px;
    color: #673ab7;
    margin-bottom: 20px;
    font-weight: 700;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.container .kesif-list #kesifList {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 600px; /* Maksimum yükseklik */
    min-width: 340px;
    overflow-y: auto; /* Taşma durumunda kaydırma çubuğu */
}

.container .kesif-list .kesif-button {
    background: #f8f9fa;
    color: #444;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
    transition: background 0.3s ease, border-color 0.3s ease, transform 0.2s ease;
}

.container .kesif-list .kesif-button:hover {
    background: #e9ecef;
    border-color: #673ab7;
}

.container .kesif-list .kesif-button.selected {
    background: #673ab7;
    color: #fff;
    border-color: #673ab7;
    transform: translateX(5px);
}

.container .kesif-details {
    background: #ffffff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-right: 5px solid #673ab7;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.container .kesif-details h1 {
    font-size: 22px;
    color: #673ab7;
    margin-bottom: 20px;
    font-weight: 700;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.container .kesif-details #kesifDetailsTable {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    color: #555;
}

.container .kesif-details #kesifDetailsTable thead {
    background: #f8f9fa;
    color: #333;
    font-weight: 600;
}

.container .kesif-details #kesifDetailsTable th,
.container .kesif-details #kesifDetailsTable td {
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    text-align: left;
}

.container .kesif-details #kesifDetailsTable th {
    border-top: 1px solid #e9ecef;
}

.container .kesif-details #kesifDetailsTable tbody tr:hover {
    background: #f1f3f5;
}

.container .kesif-details #kesifDetailsTable input[type="number"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    color: #333;
    background: #fafafa;
    transition: border-color 0.3s ease;
}

.container .kesif-details #kesifDetailsTable input[type="number"]:focus {
    border-color: #673ab7;
    outline: none;
}

.container .kesif-details #savePrices {
    background: linear-gradient(135deg, #673ab7, #512da8);
    color: #fff;
    padding: 12px;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease;
    align-self: flex-end;
}

.container .kesif-details #savePrices:hover {
    background: linear-gradient(135deg, #512da8, #4527a0);
    transform: translateY(-2px);
}

/* Responsive Tasarım */
@media (max-width: 900px) {
    .container {
        grid-template-columns: 1fr;
        padding: 15px;
    }

    .container .kesif-list,
    .container .kesif-details {
        border-left: none;
        border-right: none;
        border-top: 5px solid #673ab7;
    }
}

@media (max-width: 600px) {
    .container .kesif-list,
    .container .kesif-details {
        padding: 20px;
    }

    .container .kesif-list h1,
    .container .kesif-details h1 {
        font-size: 20px;
    }

    .container .kesif-list .kesif-button {
        padding: 10px 12px;
        font-size: 13px;
    }

    .container .kesif-details #kesifDetailsTable th,
    .container .kesif-details #kesifDetailsTable td {
        padding: 10px 12px;
        font-size: 13px;
    }

    .container .kesif-details #kesifDetailsTable input[type="number"] {
        padding: 6px;
        font-size: 12px;
    }

    .container .kesif-details #savePrices {
        padding: 10px;
        font-size: 14px;
    }
}

@media (max-width: 400px) {
    .container .kesif-details #kesifDetailsTable {
        display: block;
        overflow-x: auto;
    }

    .container .kesif-details #kesifDetailsTable th,
    .container .kesif-details #kesifDetailsTable td {
        min-width: 100px;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <div class="kesif-list">
            <h1>Keşifler</h1>
            <div id="kesifList">
                <?php while ($kesif = $resultKesifler->fetch_assoc()) { 
                    // Kesif_raw_materials tablosunda malzeme olup olmadığını kontrol et
                    $queryMaterials = "SELECT COUNT(*) as count FROM kesif_raw_materials WHERE kesif_id = ?";
                    $stmt = $baglanti->prepare($queryMaterials);
                    $stmt->bind_param("i", $kesif['id']);
                    $stmt->execute();
                    $resultMaterials = $stmt->get_result();
                    $rowMaterials = $resultMaterials->fetch_assoc();
                    
                    if ($rowMaterials['count'] > 0) { ?>
                        <button class="kesif-button" data-id="<?php echo $kesif['id']; ?>">
                            <?php echo $kesif['project_no']; ?>
                        </button>
                    <?php }
                    $stmt->close();
                } ?>
            </div>
        </div>
        <div class="kesif-details">
            <h1>Keşif Detayları</h1>
            <table id="kesifDetailsTable">
                <thead>
                    <tr>
                        <th>Malzeme Adı</th>
                        <th>Birim</th>
                        <th>Adet</th>
                        <th>Fiyat</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Malzeme detayları burada gösterilecek -->
                </tbody>
            </table>
            <button id="savePrices">Fiyatları Kaydet</button>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Keşif seçildiğinde detayları getir
            $('#kesifList').on('click', '.kesif-button', function() {
                var kesifId = $(this).data('id');
                $.ajax({
                    url: '../controllers/get_kesif_details.php',
                    method: 'GET',
                    data: { kesif_id: kesifId },
                    success: function(data) {
                        $('#kesifDetailsTable tbody').html(data);
                    },
                    error: function() {
                        showNotification('Keşif detayları alınırken bir hata oluştu.', 'error');
                    }
                });
            });

            // Fiyatları kaydet
            $('#savePrices').on('click', function() {
                var kesifId = $('#kesifList .kesif-button.selected').data('id');
                var prices = [];
                $('#kesifDetailsTable tbody tr').each(function() {
                    var rawMaterialId = $(this).data('id');
                    var price = $(this).find('.price input').val();
                    prices.push({ raw_material_id: rawMaterialId, price: price });
                });

                $.ajax({
                    url: '../controllers/save_prices.php',
                    method: 'POST',
                    data: { kesif_id: kesifId, prices: prices },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            showNotification(data.message, 'success');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function() {
                        showNotification('Fiyatlar kaydedilirken bir hata oluştu.', 'error');
                    }
                });
            });

            // Keşif listesinde seçili öğeyi işaretle
            $('#kesifList').on('click', '.kesif-button', function() {
                $('#kesifList .kesif-button').removeClass('selected');
                $(this).addClass('selected');
            });
        });
    </script>
</body>
</html>