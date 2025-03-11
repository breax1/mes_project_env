<?php
// Veritabanı bağlantısını başlat
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

// Ülkeleri çekmek için sorgu
$queryCountries = "SELECT id, name FROM country";
$resultCountries = $baglanti->query($queryCountries);

// Para birimlerini çekmek için sorgu
$queryPriceUnits = "SELECT id, unit FROM price_unit";
$resultPriceUnits = $baglanti->query($queryPriceUnits);

// En yüksek müşteri numarasını çekmek için sorgu
$queryMaxCustomerCode = "SELECT MAX(customer_code) AS max_code FROM customers WHERE customer_code LIKE 'CU" . date('y') . date('m') . "-%'";
$resultMaxCustomerCode = $baglanti->query($queryMaxCustomerCode);
$rowMaxCustomerCode = $resultMaxCustomerCode->fetch_assoc();
$maxCustomerCode = $rowMaxCustomerCode['max_code'];

// Yeni müşteri numarasını oluştur
if ($maxCustomerCode) {
    $maxCodeNumber = (int)substr($maxCustomerCode, 7); // Mevcut en yüksek numarayı al
    $newCodeNumber = $maxCodeNumber + 1; // Bir artır
    $newCustomerCode = "CU" . date('y') . date('m') . "-" . str_pad($newCodeNumber, 5, '0', STR_PAD_LEFT); // Yeni müşteri kodunu oluştur
} else {
    $newCustomerCode = "CU" . date('y') . date('m') . "-00001"; // İlk müşteri kodu
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Üçüncü Parti Oluştur</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        
        <form id="newThirdPartyForm">
        <h1>Yeni Üçüncü Parti Oluştur</h1>
            <div>
                <label for="name">Üçüncü Parti Adı:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="nickname">Rumuz:</label>
                <input type="text" id="nickname" name="nickname">
            </div>
            <div>
                <label for="customer_code">Müşteri Kodu:</label>
                <input type="text" id="customer_code" name="customer_code" value="<?php echo $newCustomerCode; ?>" >
            </div>
            <div>
                <label for="type">Tipi:</label>
                <select id="type" name="type" required>
                    <option value="1">Aday</option>
                    <option value="2">Müşteri</option>
                    <option value="3">Tedarikçi</option>
                </select>
            </div>
            <div>
                <label for="status">Durum:</label>
                <select id="status" name="status" required>
                    <option value="1" selected>Açık</option>
                    <option value="0">Kapalı</option>
                </select>
            </div>
            <div>
                <label for="address">Adresi:</label>
                <textarea id="address" name="address"></textarea>
            </div>
            <div>
                <label for="country_id">Ülkesi:</label>
                <select id="country_id" name="country_id" required>
                    <?php while ($country = $resultCountries->fetch_assoc()) { ?>
                        <option value="<?php echo $country['id']; ?>" <?php echo $country['id'] == 217 ? 'selected' : ''; ?>>
                            <?php echo $country['name']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label for="phone">Telefonu:</label>
                <div style="display: flex;">
                    <input type="text" id="phone_code" name="phone_code" value="+90" maxlength="4" style="width: 60px; margin-right: 5px;">
                    <input type="text" id="phone" name="phone" maxlength="10" style="flex: 1;">
                </div>
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email">
            </div>
            <div>
                <label for="web">Web:</label>
                <input type="text" id="web" name="web">
            </div>
            <div>
                <label for="tax_office">Vergi Dairesi:</label>
                <input type="text" id="tax_office" name="tax_office">
            </div>
            <div>
                <label for="tax_number">Vergi Numarası:</label>
                <input type="text" id="tax_number" name="tax_number">
            </div>
            <div>
                <label for="price_unit_id">Para Birimi:</label>
                <select id="price_unit_id" name="price_unit_id" required>
                    <?php while ($priceUnit = $resultPriceUnits->fetch_assoc()) { ?>
                        <option value="<?php echo $priceUnit['id']; ?>"><?php echo $priceUnit['unit']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <button type="submit">Üçüncü Parti Oluştur</button>
        </form>
    </div>

    <script>
        document.getElementById("phone_code").addEventListener("input", function (e) {
            if (this.value.charAt(0) !== '+') {
                this.value = '+' + this.value.replace(/[^+\d]/g, ''); // Sadece + ve rakamlara izin verir
            } else {
                this.value = this.value.replace(/[^+\d]/g, ''); // Sadece + ve rakamlara izin verir
            }
        });

        document.getElementById("phone").addEventListener("input", function (e) {
            this.value = this.value.replace(/\D/g, ''); // Sadece rakamlara izin verir
        });

        document.getElementById("tax_number").addEventListener("input", function (e) {
            this.value = this.value.replace(/\D/g, ''); // Sadece rakamlara izin verir
        });


        $(document).ready(function() {
            // Yeni üçüncü parti formu gönderildiğinde
            $('#newThirdPartyForm').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: '../controllers/save_third_party.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        alert(response);
                        location.reload();
                    },
                    error: function() {
                        alert('Üçüncü parti kaydedilirken bir hata oluştu.');
                    }
                });
            });
        });
    </script>
</body>
</html>