<?php
// Veritabanı bağlantısını başlat
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

// Teklif bekleyen projeleri çekmek için sorgu
$queryTeklifler = "SELECT p.id, p.project_no, p.project_name 
                   FROM projects p 
                   JOIN kesif k ON p.id = k.project_id 
                   JOIN kesif_approvals ka ON k.id = ka.kesif_id 
                   WHERE ka.uretim_mudur_approved = TRUE AND ka.satin_alma_approved = TRUE 
                   ORDER BY p.project_no DESC";
$resultTeklifler = $baglanti->query($queryTeklifler);

// Müşterileri çekmek için sorgu
$queryCustomers = "SELECT id, name FROM customers";
$resultCustomers = $baglanti->query($queryCustomers);

// Para birimlerini çekmek için sorgu
$queryPriceUnits = "SELECT id, unit FROM price_unit";
$resultPriceUnits = $baglanti->query($queryPriceUnits);

// Projeleri çekmek için sorgu
$queryProjects = "SELECT id, project_name FROM projects";
$resultProjects = $baglanti->query($queryProjects);

// Taslak teklifleri çekmek için sorgu
$queryTaslaklar = "SELECT t.id, p.project_name, c.name as customer_name, t.proposal_date, t.validity_period, t.amount, u.username as author, t.status 
                   FROM propal t 
                   JOIN projects p ON t.project_id = p.id 
                   JOIN customers c ON t.customer_id = c.id 
                   JOIN users u ON t.author = u.id 
                   WHERE t.status = 0 
                   ORDER BY t.proposal_date DESC";
$resultTaslaklar = $baglanti->query($queryTaslaklar);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teklif Bekleyen Projeler</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>

        /* Teklif Bekleyen Projeler sayfasına özel stiller */
.container {
    min-width: 2200px;
    margin: 0 auto;
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr 2fr;
    gap: 25px;
    background: #f5f7fa;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.container .project-list-container {
    background: #ffffff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-left: 5px solid #2196f3; /* Mavi şerit */
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.container .project-list-container h1 {
    font-size: 20px;
    color: #2196f3;
    margin-bottom: 15px;
    font-weight: 700;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.container .project-list-container #projectList {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 300px;
    overflow-y: auto;
}

.container .project-list-container .project-button {
    background: #f8f9fa;
    color: #444;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
    transition: background 0.3s ease, border-color 0.3s ease;
}

.container .project-list-container .project-button:hover {
    background: #e9ecef;
    border-color: #2196f3;
}

.container .project-list-container #projectDetailsContainer {
    font-size: 14px;
    color: #555;
    background: #fafafa;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    overflow-y: auto;
}

.container .form-container {
    max-width: 350px;
    background: #ffffff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-left: 5px solid #4caf50; /* Yeşil şerit */
}

.container .form-container h1 {
    font-size: 20px;
    color: #4caf50;
    margin-bottom: 20px;
    font-weight: 700;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.container .form-container form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.container .form-container div {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.container .form-container label {
    font-size: 14px;
    color: #444;
    font-weight: 600;
}

.container .form-container input,
.container .form-container select,
.container .form-container textarea {
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

.container .form-container input:focus,
.container .form-container select:focus,
.container .form-container textarea:focus {
    border-color: #4caf50;
    outline: none;
}

.container .form-container input[readonly] {
    background: #f0f0f0;
    color: #666;
}

.container .form-container textarea {
    resize: vertical;
    min-height: 80px;
}

.container .form-container button[type="submit"] {
    background: linear-gradient(135deg, #4caf50, #388e3c);
    color: #fff;
    padding: 12px;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}

.container .form-container button[type="submit"]:hover {
    background: linear-gradient(135deg, #388e3c, #2e7d32);
}

.container .form-container button#addCustomerButton {
    background: #e0e0e0;
    color: #444;
    padding: 0 10px;
    border: none;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    transition: background 0.3s ease;
    margin-left: 10px;
    width: 30px;
    height: 30px;
    align-self: flex-end;
}

.container .form-container button#addCustomerButton:hover {
    background: #d0d0d0;
}

.container .list-container {
    background: #ffffff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-right: 5px solid #f44336; /* Kırmızı şerit */
}

.container .list-container h1 {
    font-size: 20px;
    color: #f44336;
    margin-bottom: 20px;
    font-weight: 700;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.container .list-container table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    color: #555;
}

.container .list-container table thead {
    background: #f8f9fa;
    color: #333;
    font-weight: 600;
}

.container .list-container table th,
.container .list-container table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    text-align: left;
}

.container .list-container table th {
    border-top: 1px solid #e9ecef;
}

.container .list-container table tbody tr:hover {
    background: #f1f3f5;
}

/* Responsive Tasarım */
@media (max-width: 1000px) {
    .container {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto;
    }

    .container .list-container {
        grid-column: 1 / 3;
    }

    .container .project-list-container,
    .container .form-container,
    .container .list-container {
        border-left: none;
        border-right: none;
        border-top: 5px solid #2196f3; /* Mavi */
    }

    .container .form-container {
        border-top: 5px solid #4caf50; /* Yeşil */
    }

    .container .list-container {
        border-top: 5px solid #f44336; /* Kırmızı */
    }
}

@media (max-width: 600px) {
    .container {
        grid-template-columns: 1fr;
        padding: 15px;
    }

    .container .list-container {
        grid-column: 1 / 2;
    }

    .container .project-list-container h1,
    .container .form-container h1,
    .container .list-container h1 {
        font-size: 18px;
    }

    .container .project-list-container .project-button {
        padding: 10px 12px;
        font-size: 13px;
    }

    .container .form-container label,
    .container .list-container table {
        font-size: 12px;
    }

    .container .form-container input,
    .container .form-container select,
    .container .form-container textarea {
        padding: 8px;
        font-size: 13px;
    }

    .container .form-container button[type="submit"] {
        padding: 10px;
        font-size: 14px;
    }

    .container .list-container table th,
    .container .list-container table td {
        padding: 10px 12px;
    }
}

@media (max-width: 400px) {
    .container .list-container table {
        display: block;
        overflow-x: auto;
    }

    .container .list-container table th,
    .container .list-container table td {
        min-width: 100px;
    }

    .container .form-container button#addCustomerButton {
        width: 25px;
        height: 25px;
        font-size: 16px;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <div class="project-list-container">
            <h1>Teklif Bekleyen Projeler</h1>
            <div id="projectList">
                <?php while ($teklif = $resultTeklifler->fetch_assoc()) { ?>
                    <button class="project-button" data-id="<?php echo $teklif['id']; ?>">
                        <?php echo $teklif['project_no'] . ' - ' . $teklif['project_name']; ?>
                    </button>
                <?php } ?>
            </div>
            <h1>Proje Detayları</h1>
            <div id="projectDetailsContainer">
                <!-- Proje detayları burada gösterilecek -->
            </div>
        </div>
        <div class="form-container">
            <h1>Yeni Teklif Oluştur</h1>
            <form id="newProposalForm">
                <div>
                    <label for="customer">Müşteri:</label>
                    <select id="customer" name="customer" required>
                        <?php while ($customer = $resultCustomers->fetch_assoc()) { ?>
                            <option value="<?php echo $customer['id']; ?>"><?php echo $customer['name']; ?></option>
                        <?php } ?>
                    </select>
                    <button type="button" id="addCustomerButton">+</button>
                </div>
                <div>
                    <label for="proposal_date">Teklif Tarihi:</label>
                    <input type="date" id="proposal_date" name="proposal_date" value="<?php echo date('Y-m-d'); ?>" readonly>
                </div>
                <div>
                    <label for="validity_period">Teklif Geçerlilik Süresi:</label>
                    <input type="number" id="validity_period" name="validity_period" required>
                </div>
                <div>
                    <label for="payment_terms">Ödeme Koşulları:</label>
                    <select id="payment_terms" name="payment_terms" required>
                        <option value="Peşin">Peşin</option>
                        <option value="Vadeli">Vadeli</option>
                    </select>
                </div>
                <div>
                    <label for="payment_method">Ödeme Yöntemi:</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="Banka Havalesi">Banka Havalesi</option>
                        <option value="Kredi Kartı">Kredi Kartı</option>
                    </select>
                </div>
                <div>
                    <label for="delivery_time">Teslim Süresi:</label>
                    <select id="delivery_time" name="delivery_time" required>
                        <option value="1 Hafta">1 Hafta</option>
                        <option value="2 Hafta">2 Hafta</option>
                    </select>
                </div>
                <div>
                    <label for="project">Proje:</label>
                    <select id="project" name="project" required>
                        <?php while ($project = $resultProjects->fetch_assoc()) { ?>
                            <option value="<?php echo $project['id']; ?>"><?php echo $project['project_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label for="price_unit">Para Birimi:</label>
                    <select id="price_unit" name="price_unit" required>
                        <?php while ($priceUnit = $resultPriceUnits->fetch_assoc()) { ?>
                            <option value="<?php echo $priceUnit['id']; ?>"><?php echo $priceUnit['unit']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label for="general_note">Not (Genel):</label>
                    <textarea id="general_note" name="general_note"></textarea>
                </div>
                <div>
                    <label for="special_note">Not (Özel):</label>
                    <textarea id="special_note" name="special_note"></textarea>
                </div>
                <button type="submit">Taslak Oluştur</button>
            </form>
        </div>
        <div class="list-container">
            <h1>Oluşturulan Teklifler</h1>
            <table>
                <thead>
                    <tr>
                        <th>Proje Adı</th>
                        <th>Müşteri Adı</th>
                        <th>Teklif Tarihi</th>
                        <th>Bitiş Tarihi</th>
                        <th>Tutar (KDV Hariç)</th>
                        <th>Yazan</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody id="draftsTableBody">
                    <?php while ($taslak = $resultTaslaklar->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $taslak['project_name']; ?></td>
                            <td><?php echo $taslak['customer_name']; ?></td>
                            <td><?php echo $taslak['proposal_date']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($taslak['proposal_date'] . ' + ' . $taslak['validity_period'] . ' days')); ?></td>
                            <td><?php echo $taslak['amount']; ?></td>
                            <td><?php echo $taslak['author']; ?></td>
                            <td><?php echo $taslak['status'] == 0 ? 'Taslak' : 'Tamamlandı'; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Proje seçildiğinde detayları getir
            $('#projectList').on('click', '.project-button', function() {
                var projectId = $(this).data('id');
                $.ajax({
                    url: '../controllers/get_fiyat_project_details.php',
                    method: 'GET',
                    data: { project_id: projectId },
                    success: function(data) {
                        $('#projectDetailsContainer').html(data);
                    },
                    error: function() {
                        alert('Proje detayları alınırken bir hata oluştu.');
                    }
                });
            });

            // Yeni teklif formu gönderildiğinde
            $('#newProposalForm').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: '../controllers/save_proposal.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        alert(response);
                        // Taslakları güncelle
                        updateDraftsTable();
                    },
                    error: function() {
                        alert('Teklif kaydedilirken bir hata oluştu.');
                    }
                });
            });

            // addCustomerButton butonuna tıklandığında ucuncu_parti.php sayfasını content-area içinde yükle
            $('#addCustomerButton').on('click', function () {
                $('#content-area').load('../views/ucuncu_parti.php', function(response, status, xhr) {
                    if (status == "error") {
                        alert("Sayfa yüklenirken bir hata oluştu: " + xhr.status + " " + xhr.statusText);
                    }
                });
            });

            // Taslakları güncelleyen fonksiyon
            function updateDraftsTable() {
                $.ajax({
                    url: '../controllers/get_drafts.php',
                    method: 'GET',
                    success: function(data) {
                        $('#draftsTableBody').html(data);
                    },
                    error: function() {
                        alert('Taslaklar güncellenirken bir hata oluştu.');
                    }
                });
            }
        });
    </script>
</body>
</html>