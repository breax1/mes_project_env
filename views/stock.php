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

// Rafları çek ve bir diziye kaydet
$rafQuery = "SELECT id, shelf_name FROM stock_shelf";
$rafResult = $baglanti->query($rafQuery);
$rafList = [];
if ($rafResult->num_rows > 0) {
    while ($row = $rafResult->fetch_assoc()) {
        $rafList[] = $row;
    }
}

// Cinsleri çek ve bir diziye kaydet
$cinsQuery = "SELECT id, cins_adi FROM stock_cins";
$cinsResult = $baglanti->query($cinsQuery);
$cinsList = [];
if ($cinsResult->num_rows > 0) {
    while ($row = $cinsResult->fetch_assoc()) {
        $cinsList[] = $row;
    }
}

// Materyalleri çek ve bir diziye kaydet
$materyalQuery = "SELECT id, materyal_adi FROM stock_materyal";
$materyalResult = $baglanti->query($materyalQuery);
$materyalList = [];
if ($materyalResult->num_rows > 0) {
    while ($row = $materyalResult->fetch_assoc()) {
        $materyalList[] = $row;
    }
}

$logsQuery = "
    SELECT 
        l.islem AS islem, 
        CONCAT(u.name, ' ', u.surname) AS user_name, 
        s.urun_adi AS stock_name, 
        l.eklenen_stok_miktari AS stock_amount, 
        l.created_at AS created_at
    FROM stock_log l
    LEFT JOIN users u ON l.user_id = u.id
    LEFT JOIN stock s ON l.stock_id = s.id
    ORDER BY l.created_at DESC
";
$logsResult = $baglanti->query($logsQuery);

// Malzeme adlarını çek
$materialQuery = "SELECT id, urun_adi FROM stock";
$materialResult = $baglanti->query($materialQuery);
$materialList = [];
if ($materialResult->num_rows > 0) {
    while ($row = $materialResult->fetch_assoc()) {
        $materialList[] = $row;
    }
}


// Personel adlarını çek
$personnelQuery = "SELECT id, personnel_main_name FROM personnel";
$personnelResult = $baglanti->query($personnelQuery);

// Proje adlarını çek
$projectQuery = "SELECT id, project_name FROM projects";
$projectResult = $baglanti->query($projectQuery);

// Markaları çek
$markaQuery = "SELECT id, marka_adi FROM stock_marka";
$markaResult = $baglanti->query($markaQuery);
$markaList = [];
if ($markaResult->num_rows > 0) {
    while ($row = $markaResult->fetch_assoc()) {
        $markaList[] = $row;
    }
}

// Birimleri çek
$unitQuery = "SELECT id, unit_name FROM units";
$unitResult = $baglanti->query($unitQuery);

// Tedarikcileri cek
$tedarikciQuery = "SELECT id, name FROM customers WHERE type = '3' ";
$tedarikciResult = $baglanti->query($tedarikciQuery);


?>

<!-- filepath: /home/breax/Desktop/projects/metales/mes_project_env/views/stock.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depo Yönetimi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="stock-container">
        <div class="row">
            <!-- 1. Sütun -->
            <div class="col_left_stock">
                <!-- 1. Satır: Filtreleme Kısmı -->
                <div class="filter-section">
                    <h3>Filtreleme</h3>
                    <form id="filterForm">
                        <div class="filter-inputs">
                            <input type="text" id="filterAd" name="filterAd" placeholder="Ad">
                            
                            <!-- Raflar -->
                            <select id="filterRaf" name="filterRaf">
                                <option value="">Raf</option>
                                <?php
                                foreach ($rafList as $raf) {
                                    echo "<option value='{$raf['id']}'>{$raf['shelf_name']}</option>";
                                }
                                ?>
                            </select>
                            
                            <!-- Cinsler -->
                            <select id="filterMarka" name="filterMarka">
                                <option value="">Marka</option>
                                <?php
                                foreach ($markaList as $marka) {
                                    echo "<option value='{$marka['id']}'>{$marka['marka_adi']}</option>";
                                }
                                ?>
                            </select>
                            
                            <!-- Cinsler -->
                            <select id="filterCins" name="filterCins">
                                <option value="">Cins</option>
                                <?php
                                foreach ($cinsList as $cins) {
                                    echo "<option value='{$cins['id']}'>{$cins['cins_adi']}</option>";
                                }
                                ?>
                            </select>
                            
                            <input type="text" id="filterOlcu" name="filterOlcu" placeholder="Ölçü">
                            
                            <!-- Materyaller -->
                            <select id="filterMateryal" name="filterMateryal">
                                <option value="">Materyal</option>
                                <?php
                                foreach ($materyalList as $materyal) {
                                    echo "<option value='{$materyal['id']}'>{$materyal['materyal_adi']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </form>
                    <div class="filter-tbl">
                        <table id="filterTable">
                            <thead>
                                <tr>
                                    <th>Ad</th>
                                    <th>Raf</th>
                                    <th>Marka</th>
                                    <th>Cins</th>
                                    <th>Ölçü</th>
                                    <th>Materyal</th>
                                    <th>Stok Miktarı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- PHP ile filtrelenen ürünler buraya gelecek -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Satır: Malzeme Çıkartma -->
                <div class="material-out-section">
                    <h3>Malzeme Çıkartma</h3>
                    <form id="materialOutForm">
                        <div class="material-out-inputs">
                            <!-- Malzeme Adı -->
                            <select id="materialName" name="materialName" required>
                                <option value="">Malzeme Ad</option>
                                <?php
                                    foreach ($materialResult as $material) {
                                        echo "<option value='{$material['id']}'>{$material['urun_adi']}</option>";
                                    }
                                ?>
                            </select>
                            
                            <!-- Miktar -->
                            <input type="number" id="materialAmount" name="materialAmount" min="1" placeholder="Miktar" required>
                            
                            <!-- Personel -->
                            <select id="personnel" name="personnel" required>
                                <option value="">Personel</option>
                                <?php
                                if ($personnelResult->num_rows > 0) {
                                    while ($row = $personnelResult->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['personnel_main_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                            
                            <!-- Proje -->
                            <select id="project" name="project">
                                <option value="">Proje</option>
                                <?php
                                if ($projectResult->num_rows > 0) {
                                    while ($row = $projectResult->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['project_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                            
                            <!-- Not -->
                            <input type="text" id="note" name="note" placeholder="Not">
                            
                            <!-- Gönder Butonu -->
                            <button type="submit">Malzeme Çıkart</button>
                        </div>
                    </form>
                </div>

                <!-- Logs Tablosu -->
                <div class="logs-section">
                    <h3>Son İşlemler</h3>
                    <div class="logs-table">
                        <table id="logsTable">
                            <thead>
                                <tr>
                                    <th>İşlem</th>
                                    <th>Kullanıcı</th>
                                    <th>Ürün Adı</th>
                                    <th>Eklenen Stok Miktarı</th>
                                    <th>Oluşturulma Tarihi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($logsResult->num_rows > 0) {
                                    while ($row = $logsResult->fetch_assoc()) {
                                        echo "<tr>
                                                <td>{$row['islem']}</td>
                                                <td>{$row['user_name']}</td>
                                                <td>{$row['stock_name']}</td>
                                                <td>{$row['stock_amount']}</td>
                                                <td>{$row['created_at']}</td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5'>Sonuç bulunamadı.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. Sütun -->
            <div class="col_right_stock">
                <!-- 1. Satır: Grafikler -->
                <div class="charts-section">
                    <h3>Grafikler</h3>
                    <canvas id="chart1"></canvas>
                    <canvas id="chart2"></canvas>
                </div>

                <!-- 2. Satır: Proje Malzemeleri -->
                <div class="project-materials-section">
                    <h3>Proje Malzemeleri</h3>
                    <div id="projectMaterialsContainer">
                        <!-- Projeler ve malzemeler buraya yüklenecek -->
                    </div>
                </div>

                <!-- 3. Satır: Butonlar -->
                <div class="buttons-section">
                    <button id="addMaterialButton">Malzeme Ekle</button>
                    <button id="updateMaterialButton">Güncelle</button>
                    <button id="addStockButton">Stok Ekle</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Malzeme Ekle Popup -->
    <div id="addMaterialPopup" class="popup-overlay">
        <div class="popup-content">
            <button type="button" id="closePopupButton" class="close-button">&times;</button>
            <h3>Malzeme Ekle</h3>
            <form id="addMaterialForm">
                <label for="materialName">Adı:</label>
                <input type="text" id="materialName" name="materialName" required>
    
                <label for="materialBrand">Marka:</label>
                <div style="display: flex; align-items: center;">
                    <select id="filterMarka" name="filterMarka">
                        <option value="">Marka</option>
                        <?php
                        foreach ($markaList as $marka) {
                            echo "<option value='{$marka['id']}'>{$marka['marka_adi']}</option>";
                        }
                        ?>
                    </select>
                    <button type="button" id="addBrandButton" class="add-button">+</button>
                </div>
    
                <label for="materialUnit">Birim:</label>
                <select id="materialUnit" name="materialUnit" required>
                    <option value="">Birim</option>
                    <?php
                    if ($unitResult->num_rows > 0) {
                        while ($row = $unitResult->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['unit_name']}</option>";
                        }
                    }
                    ?>
                </select>
    
                <label for="materialSize">Ölçü:</label>
                <input type="text" id="materialSize" name="materialSize" required>
    
                <label for="materialStock">Stok:</label>
                <input type="number" id="materialStock" name="materialStock" min="0" required>
    
                <label for="criticalStock">Kritik Stok:</label>
                <input type="number" id="criticalStock" name="criticalStock" min="0" required>
    
                <!-- Raflar -->
                <label>Raf:</label>
                <select id="materialShelf" name="materialShelf" required>
                    <option value="">Raf</option>
                    <label>Raf:</label>
                    <?php
                    foreach ($rafList as $raf) {
                        echo "<option value='{$raf['id']}'>{$raf['shelf_name']}</option>";
                    }
                    ?>
                </select>

                <!-- Cinsler -->
                <label for="materialType">Cins:</label>
                <div style="display: flex; align-items: center;">
                    <select id="materialType" name="materialType" required>
                        <option value="">Cins</option>
                        <?php
                        foreach ($cinsList as $cins) {
                            echo "<option value='{$cins['id']}'>{$cins['cins_adi']}</option>";
                        }
                        ?>
                    </select>
                    <button type="button" id="addTypeButton" class="add-button">+</button>
                </div>

                <!-- Materyaller -->
                <label for="materialMaterial">Materyal:</label>
                <div style="display: flex; align-items: center;">
                    <select id="materialMaterial" name="materialMaterial" required>
                        <option value="">Materyal</option>
                        <?php
                        foreach ($materyalList as $materyal) {
                            echo "<option value='{$materyal['id']}'>{$materyal['materyal_adi']}</option>";
                        }
                        ?>
                    </select>
                    <button type="button" id="addMaterialButton" class="add-button">+</button>
                </div>

                <label for="materialTedarikci">Tedarikci:</label>
                <select id="materialTedarikci" name="materialTedarikci" required>
                    <option value="">Tedarikci</option>
                    <?php
                    if ($tedarikciResult->num_rows > 0) {
                        while ($row = $tedarikciResult->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                        }
                    }
                    ?>
                </select>

    
                <button type="submit">Ekle</button>
            </form>
        </div>
    </div> 

        <!-- Malzeme Güncelle Popup -->
    <div id="updateMaterialPopup" class="popup-overlay">
        <div class="popup-content">
            <button type="button" id="closeUpdatePopupButton" class="close-button">&times;</button>
            <h3>Malzeme Güncelle</h3>
            <form id="updateMaterialForm">
                <!-- Malzeme Seçimi -->
                <label for="updateMaterialSelect">Malzeme:</label>
                <select id="updateMaterialSelect" name="updateMaterialSelect" required>
                    <option value="">Malzeme Seç</option>
                    <?php
                    foreach ($materialList as $material) {
                        echo "<option value='{$material['id']}'>{$material['urun_adi']}</option>";
                    }
                    ?>
                </select>
    
                <!-- Diğer Alanlar -->
                <label for="updateMaterialBrand">Marka:</label>
                <select id="updateMaterialBrand" name="updateMaterialBrand" required>
                    <option value="">Marka</option>
                    <?php
                    foreach ($markaResult as $marka) {
                        echo "<option value='{$marka['id']}'>{$marka['marka_adi']}</option>";
                    }
                    ?>
                </select>
    
                <label for="updateMaterialUnit">Birim:</label>
                <select id="updateMaterialUnit" name="updateMaterialUnit" required>
                    <option value="">Birim</option>
                    <?php
                    foreach ($unitResult as $unit) {
                        echo "<option value='{$unit['id']}'>{$unit['unit_name']}</option>";
                    }
                    ?>
                </select>
    
                <label for="updateMaterialSize">Ölçü:</label>
                <input type="text" id="updateMaterialSize" name="updateMaterialSize" required>
    
                <label for="updateMaterialStock">Stok:</label>
                <input type="number" id="updateMaterialStock" name="updateMaterialStock" min="0" required>
    
                <label for="updateCriticalStock">Kritik Stok:</label>
                <input type="number" id="updateCriticalStock" name="updateCriticalStock" min="0" required>
    
                <label for="updateMaterialShelf">Raf:</label>
                <select id="updateMaterialShelf" name="updateMaterialShelf" required>
                    <option value="">Raf</option>
                    <?php
                    foreach ($rafList as $raf) {
                        echo "<option value='{$raf['id']}'>{$raf['shelf_name']}</option>";
                    }
                    ?>
                </select>
    
                <label for="updateMaterialType">Cins:</label>
                <select id="updateMaterialType" name="updateMaterialType" required>
                    <option value="">Cins</option>
                    <?php
                    foreach ($cinsList as $cins) {
                        echo "<option value='{$cins['id']}'>{$cins['cins_adi']}</option>";
                    }
                    ?>
                </select>
    
                <label for="updateMaterialMaterial">Materyal:</label>
                <select id="updateMaterialMaterial" name="updateMaterialMaterial" required>
                    <option value="">Materyal</option>
                    <?php
                    foreach ($materyalList as $materyal) {
                        echo "<option value='{$materyal['id']}'>{$materyal['materyal_adi']}</option>";
                    }
                    ?>
                </select>
    
                <label for="updateMaterialTedarikci">Tedarikçi:</label>
                <select id="updateMaterialTedarikci" name="updateMaterialTedarikci" required>
                    <option value="">Tedarikçi</option>
                    <?php
                    foreach ($tedarikciResult as $tedarikci) {
                        echo "<option value='{$tedarikci['id']}'>{$tedarikci['name']}</option>";
                    }
                    ?>
                </select>
    
                <!-- Güncelle Butonu -->
                <button type="submit">Güncelle</button>
            </form>
        </div>
    </div>

    <!-- Stok Ekle Popup -->
    <div id="addStockPopup" class="popup-overlay">
        <div class="popup-content">
            <button type="button" id="closeAddStockPopupButton" class="close-button">&times;</button>
            <h3>Stok Ekle</h3>
            <form id="addStockForm">
                <!-- Ürün Seçimi -->
                <label for="stockProduct">Ürün:</label>
                <select id="stockProduct" name="stockProduct" required>
                    <option value="">Ürün Seç</option>
                    <?php
                        foreach ($materialResult as $material) {
                            echo "<option value='{$material['id']}'>{$material['urun_adi']}</option>";
                        }
                    ?>
                </select>
    
                <!-- Miktar Girişi -->
                <label for="stockAmount">Miktar:</label>
                <input type="number" id="stockAmount" name="stockAmount" min="1" placeholder="Miktar" required>
    
                <!-- Gönder Butonu -->
                <button type="submit">Stok Ekle</button>
            </form>
        </div>
    </div>

    <div id="inlinePopup" class="inline-popup">
        <input type="text" id="popupInput" placeholder="Yeni Değer" />
        <button id="popupSaveButton">Kaydet</button>
    </div>

    <div id="purchaseHistoryPopup" class="popup-overlay">
        <div class="popup-content">
            <button type="button" id="closePurchaseHistoryPopup" class="close-button">&times;</button>
            
            
            <div class="popup-body">
                <!-- Sol taraf: Geçmiş satın alımlar -->
                <div class="left-section">
                    <h3>Geçmiş Satın Alımlar</h3>
                    <!-- Tarih Aralığı Filtreleme -->
                    <div class="date-filter">
                        <label for="startDate">Başlangıç Tarihi:</label>
                        <input type="date" id="startDate">
                        
                        <label for="endDate">Bitiş Tarihi:</label>
                        <input type="date" id="endDate">
                        
                        <button id="filterDateButton">Filtrele</button>
                    </div>
    
                    <table id="purchaseHistoryTable">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Tedarikçi</th>
                                <th>Miktar</th>
                                <th>Birim Fiyat</th>
                                <th>Birim</th>
                                <th>Toplam Fiyat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Geçmiş satın alım bilgileri buraya yüklenecek -->
                        </tbody>
                    </table>
                </div>
    
                <!-- Sağ taraf: Son işlemler -->
                <div class="right-section">
                    <h3>Son İşlemler</h3>
                    <table id="stockLogsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>İşlem</th>
                                <th>Kullanıcı</th>
                                <th>Eklenen Stok Miktarı</th>
                                <th>Oluşturulma Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Son işlemler buraya yüklenecek -->
                        </tbody>
                    </table>
                
                    <h3>Stok Kullanımları</h3>
                    <table id="stockUsesTable">
                        <thead>
                            <tr>
                                <th>Personel</th>
                                <th>Proje</th>
                                <th>Not</th>
                                <th>Miktar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Stok kullanımları buraya yüklenecek -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        
        $(document).ready(function () {
            // 1. Grafik: Gün/Kullanılan Stok
            $.ajax({
                url: '../controllers/get_stock_usage.php',
                method: 'GET',
                success: function (response) {
                    const data = JSON.parse(response);
                    const labels = data.map(item => item.day);
                    const values = data.map(item => item.total_used);

                    const ctx1 = document.getElementById('chart1').getContext('2d');
                    new Chart(ctx1, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Günlük Kullanılan Stok',
                                data: values,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            }
                        }
                    });
                },
                error: function () {
                    showNotification('Günlük kullanılan stok verileri yüklenirken bir hata oluştu.', 'error');
                }
            });

            // 2. Grafik: En Çok Kullanılan Malzeme Cinsleri
            $.ajax({
                url: '../controllers/get_stock_distribution.php',
                method: 'GET',
                success: function (response) {
                    const data = JSON.parse(response);
                    const labels = data.map(item => item.type); // Malzeme cinsleri
                    const values = data.map(item => item.total_used); // Kullanım miktarları
            
                    const ctx2 = document.getElementById('chart2').getContext('2d');
                    new Chart(ctx2, {
                        type: 'bar', // Bar grafiği
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'En Çok Kullanılan Malzeme Cinsleri',
                                data: values,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.2)',
                                    'rgba(54, 162, 235, 0.2)',
                                    'rgba(255, 206, 86, 0.2)',
                                    'rgba(75, 192, 192, 0.2)',
                                    'rgba(153, 102, 255, 0.2)',
                                    'rgba(255, 159, 64, 0.2)'
                                ],
                                borderColor: [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 206, 86, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(153, 102, 255, 1)',
                                    'rgba(255, 159, 64, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                },
                error: function () {
                    showNotification('En çok kullanılan malzeme cinsleri verileri yüklenirken bir hata oluştu.', 'error');
                }
            });

            $('#filterTable tbody').on('dblclick', 'tr', function () {
                const stockId = $(this).data('id'); // Satırdaki malzeme ID'sini al
            
                if (stockId) {
                    // Geçmiş satın alımları getir
                    $.ajax({
                        url: '../controllers/get_purchase_history.php',
                        method: 'GET',
                        data: { stock_id: stockId },
                        success: function (response) {
                            const data = JSON.parse(response);
                            const tbody = $('#purchaseHistoryTable tbody');
                            tbody.empty();
            
                            if (data.length > 0) {
                                data.forEach(item => {
                                    tbody.append(`
                                        <tr>
                                            <td>${item.tarih}</td>
                                            <td>${item.tedarikci}</td>
                                            <td>${item.miktar}</td>
                                            <td>${item.birim_fiyat}</td>
                                            <td>${item.birim}</td>
                                            <td>${item.toplam_fiyat}</td>
                                        </tr>
                                    `);
                                });
                            } else {
                                tbody.append('<tr><td colspan="6">Geçmiş satın alım bulunamadı.</td></tr>');
                            }
            
                            $('#purchaseHistoryPopup').fadeIn();
                        },
                        error: function () {
                            alert('Geçmiş satın alımlar alınırken bir hata oluştu.');
                        }
                    });
            
                    // Son işlemleri getir
                    $.ajax({
                        url: '../controllers/get_stock_logs.php',
                        method: 'GET',
                        data: { stock_id: stockId },
                        success: function (response) {
                            const data = JSON.parse(response);
                            const tbody = $('#stockLogsTable tbody');
                            tbody.empty();
            
                            if (data.length > 0) {
                                data.forEach(item => {
                                    tbody.append(`
                                        <tr>
                                            <td>${item.id}</td>
                                            <td>${item.islem}</td>
                                            <td>${item.user_name}</td>
                                            <td>${item.eklenen_stok_miktari}</td>
                                            <td>${item.created_at}</td>
                                        </tr>
                                    `);
                                });
                            } else {
                                tbody.append('<tr><td colspan="5">Son işlem bulunamadı.</td></tr>');
                            }
                        },
                        error: function () {
                            alert('Son işlemler alınırken bir hata oluştu.');
                        }
                    });

                    $.ajax({
                        url: '../controllers/get_stock_uses.php',
                        method: 'GET',
                        data: { stock_id: stockId },
                        success: function (response) {
                            const data = JSON.parse(response);
                            const tbody = $('#stockUsesTable tbody');
                            tbody.empty();

                            if (data.length > 0) {
                                data.forEach(item => {
                                    tbody.append(`
                                        <tr>
                                            <td>${item.personnel_main_name}</td>
                                            <td>${item.project_name} (${item.project_no})</td>
                                            <td>${item.note}</td>
                                            <td>${item.value}</td>
                                        </tr>
                                    `);
                                });
                            } else {
                                tbody.append('<tr><td colspan="4">Stok kullanımı bulunamadı.</td></tr>');
                            }
                        },
                        error: function () {
                            alert('Stok kullanımları alınırken bir hata oluştu.');
                        }
                    });
                }
            });

            // Tarih aralığı filtreleme
            $('#filterDateButton').click(function () {
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                const stockId = $('#filterTable tbody tr.selected').data('id'); // Seçilen malzeme ID'si
                
                if (stockId) {
                    // AJAX ile tarih aralığına göre verileri getir
                    $.ajax({
                        url: '../controllers/get_purchase_history.php',
                        method: 'GET',
                        data: { 
                            stock_id: stockId,
                            start_date: startDate,
                            end_date: endDate
                        },
                        success: function (response) {
                            const data = JSON.parse(response);
                            const tbody = $('#purchaseHistoryTable tbody');
                            tbody.empty();

                            if (data.length > 0) {
                                data.forEach(item => {
                                    tbody.append(`
                                        <tr>
                                            <td>${item.tarih}</td>
                                            <td>${item.tedarikci}</td>
                                            <td>${item.miktar}</td>
                                            <td>${item.birim_fiyat}</td>
                                            <td>${item.birim}</td>
                                            <td>${item.toplam_fiyat}</td>
                                        </tr>
                                    `);
                                });
                            } else {
                                tbody.append('<tr><td colspan="6">Seçilen tarih aralığında veri bulunamadı.</td></tr>');
                            }
                        },
                        error: function () {
                            alert('Veriler alınırken bir hata oluştu.');
                        }
                    });
                }
            });

            $('#filterTable tbody').on('click', 'tr', function () {
                $('#filterTable tbody tr').removeClass('selected'); // Önceki seçimi kaldır
                $(this).addClass('selected'); // Yeni seçimi ekle
            });

            // Popup'ı kapatma
            $('#closePurchaseHistoryPopup').click(function () {
                $('#purchaseHistoryPopup').fadeOut();
            });

            function loadProjectMaterials() {
                $.ajax({
                    url: '../controllers/get_project_materials.php',
                    method: 'GET',
                    success: function (response) {
                        const projects = JSON.parse(response);
                        const container = $('#projectMaterialsContainer');
                        container.empty();

                        projects.forEach(project => {
                            const projectId = project.project_id;
                            const projectName = project.project_name;

                            // Proje başlığı
                            const projectElement = $(`
                                <div class="project">
                                    <div class="project-header">
                                        <span>${projectName}</span>
                                        <button class="toggle-materials" data-project-id="${projectId}">▼</button>
                                    </div>
                                    <div class="materials" style="display: none;">
                                        <table>
                                            
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `);

                            // Malzemeleri ekle
                            const materialsContainer = projectElement.find('.materials tbody');
                            project.materials.forEach(material => {
                                const status = material.prepared == 0 ? 'Bekliyor' : 'Hazır';
                                const materialRow = $(`
                                    <tr>
                                        <td>${material.material_name}</td>
                                        <td>
                                            <button class="toggle-status" data-material-id="${material.material_id}" data-status="${material.prepared}">
                                                ${status}
                                            </button>
                                        </td>
                                    </tr>
                                `);
                                materialsContainer.append(materialRow);
                            });

                            container.append(projectElement);
                        });
                    },
                    error: function () {
                        showNotification('Proje malzemeleri yüklenirken bir hata oluştu..', 'error');
                    }
                });
            }

            // Popup'u aç
            $('#updateMaterialButton').click(function () {
                $('#updateMaterialPopup').css('display', 'flex');
            });

            // Popup'u kapat
            $('#closeUpdatePopupButton').click(function () {
                $('#updateMaterialPopup').css('display', 'none');
            });

            // Malzeme seçildiğinde verileri doldur
            $('#updateMaterialSelect').change(function () {
                const materialId = $(this).val();

                if (materialId) {
                    $.ajax({
                        url: '../controllers/get_material_details.php', // Malzeme detaylarını getiren backend
                        method: 'GET',
                        data: { id: materialId },
                        success: function (response) {
                            const data = JSON.parse(response);

                            // Alanları doldur
                            $('#updateMaterialBrand').val(data.urun_marka);
                            $('#updateMaterialUnit').val(data.urun_unit);
                            $('#updateMaterialSize').val(data.urun_olcu);
                            $('#updateMaterialStock').val(data.urun_stok_miktari);
                            $('#updateCriticalStock').val(data.urun_kritik_stok);
                            $('#updateMaterialShelf').val(data.shelf_id);
                            $('#updateMaterialType').val(data.urun_cins);
                            $('#updateMaterialMaterial').val(data.urun_materyal);
                            $('#updateMaterialTedarikci').val(data.urun_supplier);
                        },
                        error: function () {
                            showNotification('Malzeme bilgileri alınırken bir hata oluştu.', 'error');
                        }
                    });
                }
            });

            // Güncelleme formu gönderimi
            $('#updateMaterialForm').submit(function (event) {
                event.preventDefault();
                const formData = $(this).serialize();

                $.ajax({
                    url: '../controllers/update_material.php', // Güncelleme işlemi için backend
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            showNotification('Malzeme başarıyla güncellendi.', 'success');
                            $('#updateMaterialPopup').css('display', 'none');
                        } else {
                            showNotification('Güncelleme sırasında bir hata oluştu.', 'error');
                        }
                    },
                    error: function () {
                        showNotification('Güncelleme işlemi sırasında bir hata oluştu.', 'error');
                    }
                });
                updateLogsTable();
            });
        

            // Proje malzemelerini genişletme/daraltma
            $(document).on('click', '.toggle-materials', function () {
                const projectId = $(this).data('project-id');
                const materialsContainer = $(this).closest('.project').find('.materials');
                materialsContainer.toggle();
            });

            // Malzeme durumunu değiştirme
            $(document).on('click', '.toggle-status', function () {
                const materialId = $(this).data('material-id');
            
                $.ajax({
                    url: '../controllers/update_material_status.php',
                    method: 'POST',
                    data: { material_id: materialId },
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            showNotification('Durum başarıyla güncellendi.', 'success');
            
                            // Butonun durumunu güncelle
                            const button = $(`.toggle-status[data-material-id="${materialId}"]`);
                            const newStatus = data.new_prepared; // Backend'den dönen yeni durum
                            button.data('status', newStatus); // data-status değerini güncelle
                            button.text(newStatus == '0' ? 'Bekliyor' : 'Hazır'); // Buton metnini güncelle
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function () {
                        showNotification('Durum güncellenirken bir hata oluştu.', 'error');
                    }
                });
            });

            // Sayfa yüklendiğinde projeleri ve malzemeleri yükle
            loadProjectMaterials();

            let currentField = null;

            // Popup'u aç
            $('.add-button').click(function (event) {
                const button = $(this);
                const offset = button.offset(); // Butonun konumunu al
                const fieldType = button.attr('id'); // Butonun ID'sini al (addBrandButton, addTypeButton, addMaterialButton)

                currentField = fieldType; // Hangi alan için popup açıldığını kaydet

                // Popup'u ilgili butonun yanına yerleştir
                $('#inlinePopup').css({
                    top: offset.top,
                    left: offset.left + button.outerWidth() + 10, // Butonun sağında açılacak
                }).fadeIn();
            });

            // Popup'u kapat
            $(document).on('click', function (event) {
                if (!$(event.target).closest('.inline-popup, .add-button').length) {
                    $('#inlinePopup').fadeOut();
                }
            });

            // Kaydet butonuna tıklama
            $('#popupSaveButton').click(function () {
                const newValue = $('#popupInput').val(); // Kullanıcının girdiği değer

                if (!newValue) {
                    showNotification('Lütfen bir değer girin.', 'error');
                    return;
                }

                let url = '';
                if (currentField === 'addBrandButton') {
                    url = '../controllers/add_brand.php';
                } else if (currentField === 'addTypeButton') {
                    url = '../controllers/add_type.php';
                } else if (currentField === 'addMaterialButton') {
                    url = '../controllers/add_material.php';
                }

                // AJAX ile backend'e gönder
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: { value: newValue },
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            showNotification('Başarıyla eklendi!', 'success');
                            $('#inlinePopup').fadeOut();
                            $('#popupInput').val(''); // Input'u temizle
                        } else {
                            showNotification('Ekleme sırasında bir hata oluştu.', 'error');
                        }
                    },
                    error: function () {
                        showNotification('Bir hata oluştu.', 'error');
                    }
                });
            });

            // Popup'u aç
            $('#addStockButton').click(function () {
                $('#addStockPopup').css('display', 'flex');
            });

            // Popup'u kapat
            $('#closeAddStockPopupButton').click(function () {
                $('#addStockPopup').css('display', 'none');
            });

            // Form gönderimi
            $('#addStockForm').submit(function (event) {
                event.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: '../controllers/add_material_stock.php', // Backend dosyası
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            showNotification('Stok başarıyla eklendi.', 'success');
                            $('#addStockPopup').css('display', 'none');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function () {
                        showNotification('Stok ekleme sırasında bir hata oluştu.', 'error');
                    }
                });
                updateLogsTable();
            });



            // Popup'u aç
            $('#addMaterialButton').click(function () {
                $('#addMaterialPopup').css('display', 'flex');
            });

            // Popup'u kapat
            $('#closePopupButton').click(function () {
                $('#addMaterialPopup').css('display', 'none');
            });

            // Form gönderimi
            $('#addMaterialForm').submit(function (event) {
                event.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: '../controllers/add_stock.php', // Backend dosyası
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            showNotification('Malzeme başarıyla eklendi.', 'success');
                            $('#addMaterialPopup').css('display', 'none');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function () {
                        showNotification('Malzeme ekleme sırasında bir hata oluştu.', 'error');
                    }
                });
            });
            
            // Sayfa yüklendiğinde tüm stokları getir
            $.ajax({
                url: '../controllers/filter_stock.php', // Tüm stokları getiren backend
                method: 'GET',
                success: function (response) {
                    // Gelen veriyi tabloya ekle
                    $('#filterTable tbody').html(response);
                },
                error: function () {
                    showNotification('Stoklar yüklenirken bir hata oluştu.', 'error');
                }
            });

            // Filtreleme formundaki değişiklikleri dinle
            $('#filterForm input, #filterForm select').on('input change', function () {
                // Form verilerini al
                var formData = $('#filterForm').serialize();

                // AJAX isteği gönder
                $.ajax({
                    url: '../controllers/filter_stock.php', // Sunucu tarafında filtreleme işlemi
                    method: 'GET',
                    data: formData,
                    success: function (response) {
                        // Gelen veriyi tabloya ekle
                        $('#filterTable tbody').html(response);
                    },
                    error: function () {
                        showNotification('Filtreleme sırasında bir hata oluştu.', 'error');
                    }
                });
            });
        });

        // Malzeme çıkartma formu gönderimi
        $('#materialOutForm').submit(function(event) {
            event.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                url: '../controllers/material_out.php', // Malzeme çıkartma işlemi için backend
                method: 'POST',
                data: formData,
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.status === 'success') {
                        showNotification('Malzeme başarıyla çıkartıldı.', 'success');
                        // Logs tablosunu güncelle
                        updateLogsTable();
                    } else {
                        showNotification(data.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Malzeme çıkartma işlemi sırasında bir hata oluştu.', 'error');
                }
            });
        });

        // Logs tablosunu güncelleyen fonksiyon
        function updateLogsTable() {
            $.ajax({
                url: '../controllers/get_logs.php', // Logs verilerini getiren backend
                method: 'GET',
                success: function(response) {
                    $('#logsTable tbody').html(response); // Gelen veriyi tabloya ekle
                },
                error: function() {
                    showNotification('Logs tablosu güncellenirken bir hata oluştu.', 'error');
                }
            });
        }


    </script>
</body>
</html>