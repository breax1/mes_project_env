<?php
// Veritabanı bağlantısını başlat
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

// Son eklenen malzemeleri çekmek için sorgu
$queryRecentStock = "SELECT s.urun_adi, sl.eklenen_stok_miktari, sl.created_at 
                     FROM stock_log sl 
                     JOIN stock s ON sl.stock_id = s.id 
                     ORDER BY sl.created_at DESC 
                     LIMIT 20";
$resultRecentStock = $baglanti->query($queryRecentStock);

// Tüm stokları çekmek için sorgu
$queryAllStock = "SELECT s.*, ss.shelf_name 
                  FROM stock s 
                  JOIN stock_shelf ss ON s.shelf_id = ss.id";
$resultAllStock = $baglanti->query($queryAllStock);

// Rafları çekmek için sorgu
$queryRaflar = "SELECT * FROM stock_shelf";
$resultRaflar = $baglanti->query($queryRaflar);

// Rafları stok ekleme kısmı için tekrar çekmek
$resultRaflarForAdd = $baglanti->query($queryRaflar);

// Ürün adlarını çekmek için sorgu
$queryUrunAdlari = "SELECT DISTINCT urun_adi FROM stock";
$resultUrunAdlari = $baglanti->query($queryUrunAdlari);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depo Stok</title>
    <style>
        /* Depo Stok sayfasına özel stiller */
.container {
    min-width: 2200px;
    margin: 0 auto;
    padding: 20px;
    display: flex;
    gap: 25px;
    background: #f5f7fa;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.container .column {
    flex: 1;
    padding: 25px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.container .column:first-child {
    border-left: 5px solid #ff5722; /* Turuncu şerit */
}

.container .column:nth-child(2) {
    border-left: 5px solid #03a9f4; /* Mavi şerit */
}

.container .column:last-child {
    border-left: 5px solid #8bc34a; /* Yeşil şerit */
}

.container .column h2 {
    font-size: 20px;
    margin-bottom: 20px;
    font-weight: 700;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.container .column:first-child h2 {
    color: #ff5722;
}

.container .column:nth-child(2) h2 {
    color: #03a9f4;
}

.container .column:last-child h2 {
    color: #8bc34a;
}

/* Tablolar */
.container .column table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    color: #555;
}

.container .column table thead {
    background: #f8f9fa;
    color: #333;
    font-weight: 600;
}

.container .column table th,
.container .column table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    text-align: left;
}

.container .column table th {
    border-top: 1px solid #e9ecef;
}

.container .column table tbody tr:hover {
    background: #f1f3f5;
}

.container .scrollable-table {
    max-height: 553px; /* Mevcut maksimum yükseklik korundu */
    overflow-y: auto;
}

/* Stok Arama */
.container .column:nth-child(2) > div {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.container .column:nth-child(2) label {
    font-size: 14px;
    color: #444;
    font-weight: 600;
    margin-bottom: 5px;
}

.container .column:nth-child(2) input,
.container .column:nth-child(2) select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    color: #333;
    background: #fafafa;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.container .column:nth-child(2) input:focus,
.container .column:nth-child(2) select:focus {
    border-color: #03a9f4;
    outline: none;
}

.container .column:nth-child(2) button#searchButton {
    background: linear-gradient(135deg, #03a9f4, #0288d1);
    color: #fff;
    padding: 10px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}

.container .column:nth-child(2) button#searchButton:hover {
    background: linear-gradient(135deg, #0288d1, #0277bd);
}

/* Stok Ekleme */
.container .stock-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.container .form-row {
    flex: 1 1 30%;
    min-width: 200px;
    margin: 0;
}

.container .form-row label {
    display: block;
    font-size: 14px;
    color: #444;
    font-weight: 600;
    margin-bottom: 5px;
}

.container .form-row input,
.container .form-row select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    color: #333;
    background: #fafafa;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.container .form-row input:focus,
.container .form-row select:focus {
    border-color: #8bc34a;
    outline: none;
}

.container .form-row button#addStockButton {
    background: linear-gradient(135deg, #8bc34a, #689f38);
    color: #fff;
    padding: 10px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}

.container .form-row button#addStockButton:hover {
    background: linear-gradient(135deg, #689f38, #558b2f);
}

/* Responsive Tasarım */
@media (max-width: 1000px) {
    .container {
        flex-direction: column;
        padding: 15px;
    }

    .container .column {
        border-left: none;
        border-top: 5px solid #ff5722; /* Turuncu */
    }

    .container .column:nth-child(2) {
        border-top: 5px solid #03a9f4; /* Mavi */
    }

    .container .column:last-child {
        border-top: 5px solid #8bc34a; /* Yeşil */
    }

    .container .form-row {
        flex: 1 1 100%;
    }
}

@media (max-width: 600px) {
    .container .column {
        padding: 20px;
    }

    .container .column h2 {
        font-size: 18px;
    }

    .container .column table th,
    .container .column table td {
        padding: 10px 12px;
        font-size: 12px;
    }

    .container .column:nth-child(2) label,
    .container .form-row label {
        font-size: 13px;
    }

    .container .column:nth-child(2) input,
    .container .column:nth-child(2) select,
    .container .form-row input,
    .container .form-row select {
        padding: 8px;
        font-size: 13px;
    }

    .container .column:nth-child(2) button#searchButton,
    .container .form-row button#addStockButton {
        padding: 8px;
        font-size: 13px;
    }
}

@media (max-width: 400px) {
    .container .column table {
        display: block;
        overflow-x: auto;
    }

    .container .column table th,
    .container .column table td {
        min-width: 100px;
    }

    .container .scrollable-table {
        max-height: 400px;
    }
}
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            function searchStock() {
                var urunAdi = $('#urunAdi').val();
                var urunRafi = $('#urunRafi').val();

                $.ajax({
                    url: '../controllers/search_stock.php',
                    method: 'GET',
                    data: {
                        urunAdi: urunAdi,
                        urunRafi: urunRafi
                    },
                    success: function(data) {
                        $('#stockTableBody').html(data);
                    }
                });
            }

            $('#searchButton').click(function() {
                searchStock();
            });

            $('#newUrunAdi').on('input', function() {
                var urunAdi = $(this).val();
                if (urunAdi) {
                    $.ajax({
                        url: '../controllers/check_stock.php',
                        method: 'GET',
                        data: { urunAdi: urunAdi },
                        success: function(response) {
                            if (response.exists) {
                                $('#newUrunKritikStok').prop('disabled', true);
                            } else {
                                $('#newUrunKritikStok').prop('disabled', false);
                            }
                        }
                    });
                } else {
                    $('#newUrunKritikStok').prop('disabled', false);
                }
            });

            $('#addStockButton').click(function() {
                var urunAdi = $('#newUrunAdi').val();
                var urunRafi = $('#newUrunRafi').val();
                var urunStokMiktari = $('#newUrunStokMiktari').val();
                var urunKritikStok = $('#newUrunKritikStok').val();

                $.ajax({
                    url: '../controllers/add_stock.php',
                    method: 'POST',
                    data: {
                        urunAdi: urunAdi,
                        urunRafi: urunRafi,
                        urunStokMiktari: urunStokMiktari,
                        urunKritikStok: urunKritikStok
                    },
                    success: function(data) {
                        alert(data);
                        // Son eklenen malzemeleri güncelle
                        updateRecentStock();
                        // Mevcut filtrelerle stok listesini güncelle
                        searchStock();
                    }
                });
            });

            function updateRecentStock() {
                $.ajax({
                    url: '../controllers/recent_stock.php',
                    method: 'GET',
                    success: function(data) {
                        $('#recentStockTableBody').html(data);
                    }
                });
            }

            $(document).ready(function() {
                $('#newUrunAdi').on('input', function() {
                    var urunAdi = $(this).val();
                    if (urunAdi) {
                        $.ajax({
                            url: '../controllers/check_stock.php',
                            method: 'GET',
                            data: { urunAdi: urunAdi },
                            dataType: 'json',
                            success: function(response) {
                                if (response.exists) {
                                    $('#newUrunRafi').val(response.urunRafi);
                                    $('#newUrunKritikStok').val(response.urunKritikStok);
                                } else {
                                    $('#newUrunRafi').val('');
                                    $('#newUrunKritikStok').val('');
                                }
                            }
                        });
                    } else {
                        $('#newUrunRafi').val('');
                        $('#newUrunKritikStok').val('');
                    }
                });
            });


        });
    </script>
</head>
<body>
    <div class="container">
        <div class="column">
            <h2>Son Eklenen Malzemeler</h2>
            <table>
                <thead>
                    <tr>
                        <th>Ürün Adı</th>
                        <th>Eklenen Stok Miktarı</th>
                        <th>Eklenme Zamanı</th>
                    </tr>
                </thead>
                <tbody id="recentStockTableBody">
                    <?php while ($row = $resultRecentStock->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['urun_adi']); ?></td>
                            <td><?php echo htmlspecialchars($row['eklenen_stok_miktari']); ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="column">
            <h2>Stok Arama</h2>
            <div>
                <label for="urunAdi">Ürün Adı:</label>
                <input type="text" id="urunAdi">
                <label for="urunRafi">Ürün Rafı:</label>
                <select id="urunRafi">
                    <option value="">Hepsi</option>
                    <?php while ($row = $resultRaflar->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['shelf_name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <button id="searchButton">Ara</button>
            </div>
            <h2>Stok Listesi</h2>
            <div class="scrollable-table">
                <table>
                    <thead>
                        <tr>
                            <th>Ürün Adı</th>
                            <th>Ürün Rafı</th>
                            <th>Stok Miktarı</th>
                            <th>Kritik Stok</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody">
                        <?php while ($row = $resultAllStock->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['urun_adi']); ?></td>
                                <td><?php echo htmlspecialchars($row['shelf_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['urun_stok_miktari']); ?></td>
                                <td><?php echo htmlspecialchars($row['urun_kritik_stok']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="column">
            <h2>Stok Ekleme</h2>
            <div class="stock-form">
                <div class="form-row">
                    <label for="newUrunAdi">Ürün Adı:</label>
                    <input list="urunAdlari" id="newUrunAdi" required>
                    <datalist id="urunAdlari">
                        <?php while ($row = $resultUrunAdlari->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['urun_adi']); ?>">
                        <?php endwhile; ?>
                    </datalist>
                <div class="form-row">
                    <label for="newUrunRafi">Ürün Rafı:</label>
                    <select id="newUrunRafi">
                        <?php while ($row = $resultRaflarForAdd->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['shelf_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="newUrunStokMiktari">Stok Miktarı:</label>
                    <input type="number" id="newUrunStokMiktari">
                </div>
                <div class="form-row">
                    <label for="newUrunKritikStok">Kritik Stok:</label>
                    <input type="number" id="newUrunKritikStok">
                </div>
                <div class="form-row">
                    <button id="addStockButton">Ekle</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>