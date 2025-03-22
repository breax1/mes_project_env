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

date_default_timezone_set('Europe/Istanbul'); // Saat dilimini ayarla

// Personelleri çekmek için sorgu
$queryPersonel = "SELECT * FROM personnel";
$resultPersonel = $baglanti->query($queryPersonel);

// Geçmişe dönük 1 ay boyunca her gün kaç farklı personelin giriş yaptığını çekmek için sorgu
$queryGirisSayisi = "
    SELECT DATE(time) as tarih, COUNT(DISTINCT personnel_id) as giris_sayisi
    FROM giris_cikis
    WHERE islem = 'giris' AND time >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    GROUP BY DATE(time)
    ORDER BY DATE(time)
";
$resultGirisSayisi = $baglanti->query($queryGirisSayisi);

$girisVerileri = [];
while ($row = $resultGirisSayisi->fetch_assoc()) {
    $girisVerileri[] = $row;
}

// Mesai saatlerini hesaplamak için sorgu
$queryMesaiSaatleri = "
    WITH giris_cikis AS (
        SELECT 
            personnel_id,
            DATE(time) AS tarih,
            MAX(CASE WHEN islem = 'giris' THEN time END) AS giris_zamani,
            MAX(CASE WHEN islem = 'cikis' THEN time END) AS cikis_zamani
        FROM giris_cikis
        GROUP BY personnel_id, DATE(time)
    )
    SELECT 
        tarih,
        SUM(
            CASE 
                WHEN WEEKDAY(tarih) >= 5 THEN TIMESTAMPDIFF(SECOND, giris_zamani, cikis_zamani) / 3600
                WHEN TIME(cikis_zamani) > '18:00:00' THEN TIMESTAMPDIFF(SECOND, GREATEST(giris_zamani, TIMESTAMP(tarih, '18:00:00')), cikis_zamani) / 3600
                ELSE 0
            END
        ) AS gunluk_toplam_mesai_saat
    FROM giris_cikis
    WHERE cikis_zamani IS NOT NULL
    GROUP BY tarih
    ORDER BY tarih;
";
$resultMesaiSaatleri = $baglanti->query($queryMesaiSaatleri);

$mesaiVerileri = [];
while ($row = $resultMesaiSaatleri->fetch_assoc()) {
    $mesaiVerileri[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personel Durumları</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function fetchPersonnelData() {
            $.ajax({
                url: '../controllers/get_personnel_data.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    var calismayanPersoneller = data.calismayanPersoneller;
                    var calisanPersoneller = data.calisanPersoneller;
                    var logs = data.logs;

                    var calismayanHtml = '';
                    for (var i = 0; i < calismayanPersoneller.length; i++) {
                        calismayanHtml += '<tr data-id="' + calismayanPersoneller[i].id + '">' +
                            '<td>' + calismayanPersoneller[i].personnel_main_name + '</td>' +
                            '<td><span class="status calismiyor">' + calismayanPersoneller[i].status + '</span></td>' +
                            '</tr>';
                    }
                    $('#calismayanPersoneller tbody').html(calismayanHtml);

                    var calisanHtml = '';
                    for (var i = 0; i < calisanPersoneller.length; i++) {
                        calisanHtml += '<tr data-id="' + calisanPersoneller[i].id + '">' +
                            '<td>' + calisanPersoneller[i].personnel_main_name + '</td>' +
                            '<td><span class="status calisiyor">' + calisanPersoneller[i].status + '</span></td>' +
                            '<td>' + calisanPersoneller[i].calisma_suresi + '</td>' +
                            '</tr>';
                    }
                    $('#calisanPersoneller tbody').html(calisanHtml);

                    var logsHtml = '';
                    for (var i = 0; i < logs.length; i++) {
                        var islem = logs[i].islem === 'giris' ? 'GİRİŞ' : 'ÇIKIŞ';
                        logsHtml += '<tr>' +
                            '<td>' + logs[i].personnel_main_name + '</td>' +
                            '<td>' + islem + '</td>' +
                            '<td>' + logs[i].time + '</td>' +
                            '</tr>';
                    }
                    $('#logsTableBody').html(logsHtml);
                },
                error: function() {
                    showNotification('Veriler alınırken bir hata oluştu.', 'error');
                }
            });
        }

        function fetchGirisCikisData() {
            var personnelId = $('#personnelSelect').val();
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var includeTime = $('#includeTime').is(':checked');
            var startTime = $('#startTime').val();
            var endTime = $('#endTime').val();
            var includeGiris = $('#includeGiris').is(':checked');
            var includeCikis = $('#includeCikis').is(':checked');

            $.ajax({
                url: '../controllers/get_giris_cikis_data.php',
                method: 'GET',
                data: {
                    personnelId: personnelId,
                    startDate: startDate,
                    endDate: endDate,
                    includeTime: includeTime,
                    startTime: startTime,
                    endTime: endTime,
                    includeGiris: includeGiris,
                    includeCikis: includeCikis
                },
                dataType: 'json',
                success: function(data) {
                    var girisCikisHtml = '';
                    for (var i = 0; i < data.length; i++) {
                        var islem = data[i].islem === 'giris' ? 'GİRİŞ' : 'ÇIKIŞ';
                        girisCikisHtml += '<tr>' +
                            '<td>' + data[i].personnel_main_name + '</td>' +
                            '<td>' + islem + '</td>' +
                            '<td>' + data[i].time + '</td>' +
                            '</tr>';
                    }
                    $('#girisCikisTable tbody').html(girisCikisHtml);
                }
            });
        }

        function logAction(description) {
            fetch('../controllers/log_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'personnel',
                    description: description
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'success') {
                    console.error('Log kaydı başarısız:', data.message);
                }
            })
            .catch(error => {
                console.error('Log kaydı sırasında hata oluştu:', error);
            });
        }


        function downloadTableAsCSV(tableId, filename) {
            var csv = [];
            var rows = document.querySelectorAll(tableId + " tr");

            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");

                for (var j = 0; j < cols.length; j++) {
                    row.push(cols[j].innerText);
                }

                csv.push(row.join(","));
            }

            var csvString = csv.join("\n");
            var downloadLink = document.createElement("a");
            downloadLink.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvString);
            downloadLink.download = filename;

            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        // Bugünün tarihini YYYY-MM-DD formatında ayarla
        function setCurrentDate() {
            var now = new Date();
            var year = now.getFullYear();
            var month = String(now.getMonth() + 1).padStart(2, '0'); // Ay 0'dan başlar, +1 ekliyoruz
            var day = String(now.getDate()).padStart(2, '0');
            
            var currentDate = `${year}-${month}-${day}`;
            $('#start_date').val(currentDate);
        }

        // Bugünün tarih ve saatini ISO 8601 formatında ayarla
        function setCurrentDateTime() {
            var now = new Date();
            // datetime-local için YYYY-MM-DDTHH:MM formatı gerekiyor
            var year = now.getFullYear();
            var month = String(now.getMonth() + 1).padStart(2, '0'); // Ay 0'dan başlar, +1 ekliyoruz
            var day = String(now.getDate()).padStart(2, '0');
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            
            var currentDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            $('#tarih').val(currentDateTime);
        }


        $(document).ready(function() {
            fetchPersonnelData();
            setInterval(fetchPersonnelData, 5000); // Her 5 saniyede bir verileri çek

            $('#includeTime').change(function() {
                if ($(this).is(':checked')) {
                    $('#timeSelectors').show();
                } else {
                    $('#timeSelectors').hide();
                }
            });

            $('#searchButton').click(function() {
                fetchGirisCikisData();
            });

            $('#downloadButton').click(function() {
                downloadTableAsCSV('#girisCikisTable', 'giris_cikis_verileri.csv');
            });

            $('#includeGiris').change(function() {
                if (!$(this).is(':checked')) {
                    $('#includeCikis').prop('checked', true);
                }
            });

            $('#includeCikis').change(function() {
                if (!$(this).is(':checked')) {
                    $('#includeGiris').prop('checked', true);
                }
            });

            // Pop-up açma ve kapama işlevleri
            $('#personnelEkleButton').click(function() {
                $('#popupTitle').text('Personel Ekle');
                $('#submitButton').text('Kaydet');
                $('#personnelForm')[0].reset(); // Formu temizle
                $('#active').hide(); // Aktif checkbox'ını gizle
                $('#activelabel').hide(); // Aktif checkbox'ını gizle
                setCurrentDate()
                $('#popupOverlay').show();
                $('#popup').show();
            });

            $('#popupClose, #popupOverlay').click(function() {
                $('#popupOverlay').hide();
                $('#popup').hide();
            });
            
            // Global olarak originalData tanımla
            var originalData = {};

            // Çalışan ve çalışmayan personeller tablosundaki bir personele çift tıklama işlevi
            $('#calisanPersoneller, #calismayanPersoneller').on('dblclick', 'tr', function() {
                var personnelId = $(this).data('id');
                $.ajax({
                    url: '../controllers/get_personnel.php',
                    method: 'GET',
                    data: { id: personnelId },
                    success: function(response) {
                        var data = JSON.parse(response);
                        var personnelMainName = data.personnel_main_name;
                        var lastSpaceIndex = personnelMainName.lastIndexOf(' ');
                        var name = personnelMainName.substring(0, lastSpaceIndex);
                        var surname = personnelMainName.substring(lastSpaceIndex + 1);

                        // originalData'yı güncelle
                        originalData = {
                            name: name,
                            surname: surname,
                            personnel_number: data.personnel_number,
                            identity: data.identity,
                            kart_id: data.kart_id,
                            start_date: data.start_date,
                            active: data.active
                        };

                        $('#id').val(data.id);
                        $('#name').val(name);
                        $('#surname').val(surname);
                        $('#personnel_number').val(data.personnel_number);
                        $('#identity').val(data.identity);
                        $('#kart_id').val(data.kart_id);
                        $('#start_date').val(data.start_date);
                        $('#active').prop('checked', data.active == 1);
                        $('#active').parent().show();
                        $('#popupTitle').text('Personel Güncelle');
                        $('#submitButton').text('Güncelle');
                        $('#active').show();
                        $('#activelabel').show();
                        $('#popupOverlay').show();
                        $('#popup').show();
                    },
                    error: function() {
                        showNotification('Personel bilgileri alınırken bir hata oluştu.', 'error');
                    }
                });
            });

            $('#personnelForm').on('change', 'input, select, textarea', function() {
                $('#personnelForm button[type="submit"]').prop('disabled', false);
            });

            // Form gönderme işlemi
            $('#personnelForm').submit(function(event) {
                event.preventDefault();
                var formData = $(this).serialize();
                var url = $('#submitButton').text() === 'Kaydet' ? '../controllers/add_personnel.php' : '../controllers/update_personnel.php';
                var name = $('#name').val();
                var surname = $('#surname').val();

                $('#personnelForm button[type="submit"]').prop('disabled', true);

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            if ($('#submitButton').text() === 'Kaydet') {
                                logAction('Personel eklendi: ' + name + ' ' + surname);
                            } else if ($('#submitButton').text() === 'Güncelle') {
                                // originalData boşsa hata mesajı göster
                                if (Object.keys(originalData).length === 0) {
                                    console.error('originalData boş, güncelleme kontrolü yapılamıyor.');
                                    showNotification('Güncelleme sırasında bir hata oluştu: Eski veriler alınamadı.', 'error');
                                    return;
                                }

                                var changedFields = [];
                                if ($('#name').val() !== originalData.name) changedFields.push('Adı: ' + $('#name').val());
                                if ($('#surname').val() !== originalData.surname) changedFields.push('Soyadı: ' + $('#surname').val());
                                if ($('#personnel_number').val() !== originalData.personnel_number) changedFields.push('Personel Numarası: ' + $('#personnel_number').val());
                                if ($('#identity').val() !== originalData.identity) changedFields.push('TC Kimlik Numarası: ' + $('#identity').val());
                                if ($('#kart_id').val() !== originalData.kart_id) changedFields.push('Kart ID: ' + $('#kart_id').val());
                                if ($('#start_date').val() !== originalData.start_date) changedFields.push('Başlangıç Tarihi: ' + $('#start_date').val());
                                if ($('#active').prop('checked') !== (originalData.active == 1)) changedFields.push('Aktiflik Durumu: ' + ($('#active').prop('checked') ? 'Aktif' : 'Pasif'));

                                if (changedFields.length > 0) {
                                    logAction('Personel güncellendi: ' + name + ' ' + surname + ' / ' + changedFields.join(', '));
                                }
                            }
                            showNotification(data.message, 'success');
                            setTimeout(() => {
                                $('#popupOverlay').hide();
                                $('#popup').hide();
                                $('#personnelForm')[0].reset();
                            }, 2000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function() {
                        showNotification('Personel eklenirken/güncellenirken bir hata oluştu.', 'error');
                    }
                });
            });

            $('#veriEkleButton').click(function() {
                $('#veriEklePopupOverlay').show();
                $('#veriEklePopup').show();
                setCurrentDateTime();
            });

            $('#veriEklePopupClose, #veriEklePopupOverlay').click(function() {
                $('#veriEklePopupOverlay').hide();
                $('#veriEklePopup').hide();
            });

            // Giriş ve çıkış checkbox'larının birbirini dışlaması
            $('#islemGiris').change(function() {
                if ($(this).is(':checked')) {
                    $('#islemCikis').prop('checked', false);
                }
            });

            $('#islemCikis').change(function() {
                if ($(this).is(':checked')) {
                    $('#islemGiris').prop('checked', false);
                }
            });

            // Formdaki herhangi bir veri değişirse submit butonunu tekrar etkinleştir
            $('#veriEkleForm').on('change', 'input, select, textarea', function() {
                $('#veriEkleForm button[type="submit"]').prop('disabled', false);
            });

            // Veri Ekle form gönderme işlemi
            $('#veriEkleForm').submit(function(event) {
                event.preventDefault();
                var formData = $(this).serialize();

                // Form verilerini ayrıştır
                var params = new URLSearchParams(formData);
                var islemc = params.get('islem');
                var islem = islemc === 'giris' ? 'GİRİŞ' : (islemc === 'cikis' ? 'ÇIKIŞ' : islemc);
                var personnel_name = $('#personnelSelectPopup option:selected').text();
                var islem_zamani = $('#tarih').val();

                $('#veriEkleForm button[type="submit"]').prop('disabled', true);

                $.ajax({
                    url: '../controllers/add_giris_cikis.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            logAction('Veri eklendi:' + personnel_name + ' / ' + islem + ' / ' + islem_zamani);
                            showNotification(data.message, 'success');
                            setTimeout(() => {
                                $('#veriEklePopupOverlay').hide();
                                $('#veriEklePopup').hide();
                                // Formu temizle
                                $('#veriEkleForm')[0].reset();
                            }, 2000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function() {
                        showNotification('Veri eklenirken bir hata oluştu.', 'error');
                    }
                });
            });

            // Popup açma
            $('#updateCheckoutPopupClose, #updateCheckoutPopupOverlay').click(function() {
                $('#updateCheckoutPopupOverlay').hide();
                $('#updateCheckoutPopup').hide();
            });

            $('#updateCheckoutForm').submit(function(event) {
                event.preventDefault(); // Formun varsayılan gönderimini engelle
            
                // Form verilerini manuel olarak oluştur
                var formData = {};
                $('.checkout-row').each(function() {
                    var personnelId = $(this).data('id'); // data-id'den personel ID'sini al
                    var checkoutDate = $(this).data('date'); // data-date'den tarihi al
                    var checkoutTime = $(this).find('input[type="time"]').val(); // input'tan zamanı al
                    var isDateAdjusted = $(this).find('.adjust-date-checkbox').is(':checked'); // Checkbox durumunu kontrol et
            
                    // Eğer personnelId veya checkoutDate eksikse hata ver
                    if (!personnelId || !checkoutDate) {
                        console.error('Eksik veri: personnelId veya checkoutDate tanımlı değil.');
                        return;
                    }
            
                    // Eğer checkbox işaretliyse tarihi bir gün ileri al
                    if (isDateAdjusted) {
                        var dateObj = new Date(checkoutDate);
                        dateObj.setDate(dateObj.getDate() + 1); // Tarihi bir gün ileri al
                        checkoutDate = dateObj.toISOString().split('T')[0]; // YYYY-MM-DD formatına dönüştür
                    }
            
                    // Sadece saat 05:00:00 dışında bir değer girilmişse ekle
                    if (checkoutTime !== '05:00') {
                        var datetime = checkoutDate + ' ' + checkoutTime + ':00'; // DATETIME formatına dönüştür (YYYY-MM-DD HH:MM:SS)
                        formData[personnelId] = datetime; // Form verisine ekle
                    }
                });
            
                console.log('Gönderilen Veriler:', formData); // Gönderilen verileri kontrol edin
            
                // Eğer formData boşsa işlem yapma
                if (Object.keys(formData).length === 0) {
                    showNotification('Değişiklik yapılmadı.', 'info');
                    return;
                }
            
                // AJAX isteği gönder
                $.ajax({
                    url: '../controllers/update_checkout_time.php', // Backend endpoint
                    method: 'POST',
                    data: { checkoutTimes: formData }, // Verileri gönder
                    success: function(response) {
                        console.log('Dönen Yanıt:', response); // Yanıtı kontrol edin
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            showNotification(data.message, 'success'); // Başarılı bildirim
                        } else {
                            showNotification(data.message, 'error'); // Hata bildirim
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Hata:', error);
                        console.error('Detaylar:', xhr.responseText); // Hata detaylarını kontrol edin
                        showNotification('Çıkış saati güncellenirken bir hata oluştu.', 'error'); // Hata bildirim
                    }
                });
            });


            // Tarih aralığını oluşturma fonksiyonu
            function getDateRange(startDate, endDate) {
                const dates = [];
                let currentDate = new Date(startDate);
                while (currentDate <= endDate) {
                    dates.push(currentDate.toISOString().split('T')[0]); // YYYY-MM-DD formatı
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                return dates;
            }

            // Giriş grafiği için
            var ctxGiris = document.getElementById('girisGrafik').getContext('2d');
            var girisVerileri = <?php echo json_encode($girisVerileri); ?>;

            // Tüm tarihleri kapsayan labels dizisi (örneğin son 30 gün)
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 29); // Son 30 gün
            const endDate = new Date();
            const labelsGiris = getDateRange(startDate, endDate);

            // Verileri eşleştirme ve eksik günleri 0 ile doldurma
            const dataGiris = labelsGiris.map(date => {
                const entry = girisVerileri.find(item => item.tarih === date);
                return entry ? entry.giris_sayisi : 0; // Veri yoksa 0
            });

            var girisGrafik = new Chart(ctxGiris, {
                type: 'line',
                data: {
                    labels: labelsGiris,
                    datasets: [{
                        label: 'Çalışan Personel Sayısı/Gün',
                        data: dataGiris,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2
                    }]
                },
                options: {
                    scales: {
                        x: {
                            ticks: {
                                color: function(context) {
                                    const date = new Date(labelsGiris[context.index]);
                                    const isWeekend = date.getDay() === 0 || date.getDay() === 6; // Pazar (0) veya Cumartesi (6)
                                    return isWeekend ? '#e74c3c' : '#333'; // Haftasonu kırmızı, diğer günler koyu gri
                                }
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Mesai grafiği için
            var ctxMesai = document.getElementById('mesaiGrafik').getContext('2d');
            var mesaiVerileri = <?php echo json_encode($mesaiVerileri); ?>;

            const labelsMesai = getDateRange(startDate, endDate); // Aynı tarih aralığını kullan

            // Verileri eşleştirme ve eksik günleri 0 ile doldurma
            const dataMesai = labelsMesai.map(date => {
                const entry = mesaiVerileri.find(item => item.tarih === date);
                return entry ? entry.gunluk_toplam_mesai_saat : 0; // Veri yoksa 0
            });

            var mesaiGrafik = new Chart(ctxMesai, {
                type: 'line',
                data: {
                    labels: labelsMesai,
                    datasets: [{
                        label: 'Mesai Saatleri/Gün',
                        data: dataMesai,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderWidth: 2
                    }]
                },
                options: {
                    scales: {
                        x: {
                            ticks: {
                                color: function(context) {
                                    const date = new Date(labelsGiris[context.index]);
                                    const isWeekend = date.getDay() === 0 || date.getDay() === 6; // Pazar (0) veya Cumartesi (6)
                                    return isWeekend ? '#e74c3c' : '#333'; // Haftasonu kırmızı, diğer günler koyu gri
                                }
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
    <style>
        
    </style>
</head>
<body>
    <div class="personnel-container">
        <div class="left-column">
            <div class="calismalar-column">
                <div class="calisan-column">
                    <div class="sub-column">
                        <h2>Çalışmayan Personeller</h2>
                        <div class="content">
                            <table id="calismayanPersoneller">
                                <thead>
                                    <tr>
                                        <th>Personel Adı</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dinamik olarak doldurulacak -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="calismayan-column">
                    <div class="sub-column">
                        <h2>Çalışan Personeller</h2>
                        <div class="content">
                            <table id="calisanPersoneller">
                                <thead>
                                    <tr>
                                        <th>Personel Adı</th>
                                        <th class="center-bold">Durum</th>
                                        <th class="center-bold">Çalışma Süresi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dinamik olarak doldurulacak -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="logs-column">
                <div class="sub-column">
                    <h2 class="songiriscikislabel">Son Giriş-Çıkış İşlemleri</h2>
                
                    <div class="content">
                        <table class="logs-table" id="logsTable">
                            <thead>
                                <tr>
                                    <th>Personel Adı</th>
                                    <th>İşlem</th>
                                    <th>Zaman</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <!-- Giriş-Çıkış işlemleri burada gösterilecek -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-column">
            <h2>Geçmiş Giriş Çıkış Verileri</h2>
            <div id="chart-container">
                <canvas id="girisGrafik" width="400" height="200"></canvas>
                <canvas id="mesaiGrafik" width="400" height="200"></canvas> <!-- Yeni grafik için canvas -->
            </div>
            <div class="filter-container">
                <div class="filter-item">
                    <label for="personnelSelect">Personel:</label>
                    <select id="personnelSelect">
                        <option value="all">Hepsi</option>
                        <?php while ($personel = $resultPersonel->fetch_assoc()) { ?>
                            <option value="<?php echo $personel['id']; ?>"><?php echo $personel['personnel_main_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="startDate">Başlangıç Tarihi:</label>
                    <input type="date" id="startDate">
                </div>
                <div class="filter-item">
                    <label for="endDate">Bitiş Tarihi:</label>
                    <input type="date" id="endDate">
                </div>
                <div class="filter-item">
                    <label for="includeTime">Saati Dahil Et:</label>
                    <input type="checkbox" id="includeTime">
                </div>
                <div id="timeSelectors" class="filter-item" style="display: none;">
                    <label for="startTime">Başlangıç Saati:</label>
                    <input type="time" id="startTime">
                    <label for="endTime">Bitiş Saati:</label>
                    <input type="time" id="endTime">
                </div>
                <div class="filter-item">
                    <label for="includeGiris">Giriş:</label>
                    <input type="checkbox" id="includeGiris" checked>
                </div>
                <div class="filter-item">
                    <label for="includeCikis">Çıkış:</label>
                    <input type="checkbox" id="includeCikis" checked>
                </div>
                <button id="searchButton" class="filter-item">Ara</button>
            </div>
            <div class="content">
                <table id="girisCikisTable">
                    <thead>
                        <tr>
                            <th>Personel Adı</th>
                            <th>İşlem</th>
                            <th>Zaman</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dinamik olarak doldurulacak -->
                    </tbody>
                </table>
            </div>
            <div class="sticky-buttons">
                <button id="downloadButton">Excel Formatında İndir</button>
                <button id="veriEkleButton">Veri Ekle</button>
                <button id="personnelEkleButton">Personel Ekle</button>
            </div>
        </div>
    </div>

    <!-- Pop-up pencere -->
    <div class="popup-overlay" id="popupOverlay"></div>
    <div class="popup" id="popup">
        <span class="popup-close" id="popupClose">&times;</span>
        <h2 id="popupTitle">Personel Ekle</h2>
        <form id="personnelForm">
            <input type="hidden" id="id" name="id">
            <label for="name">Adı:</label>
            <input type="text" id="name" name="name" required><br>
            <label for="surname">Soyadı:</label>
            <input type="text" id="surname" name="surname" required><br>
            <label for="personnel_number">Personel Numarası:</label>
            <input type="text" id="personnel_number" name="personnel_number" required><br>
            <label for="identity">TC Kimlik Numarası:</label>
            <input type="text" id="identity" name="identity" required><br>
            <label for="kart_id">Kart ID:</label>
            <input type="text" id="kart_id" name="kart_id" required><br>
            <label for="start_date">Başlangıç Tarihi:</label>
            <input type="date" id="start_date" name="start_date" required><br>
            <label for="active" id="activelabel">Aktif:</label>
            <input type="checkbox" id="active" name="active"><br>
            <button type="submit" id="submitButton">Kaydet</button>
        </form>
    </div>

    <!-- Veri Ekle Pop-up pencere -->
    <div class="popup-overlay" id="veriEklePopupOverlay"></div>
    <div class="popup" id="veriEklePopup">
        <span class="popup-close" id="veriEklePopupClose">×</span>
        <h2>Veri Ekle</h2>
        <form id="veriEkleForm">
            <label for="personnelSelectPopup">Personel:</label>
            <select id="personnelSelectPopup" name="personnel_id" required>
                <?php
                // Veritabanı bağlantısını başlat
                include("../includes/db_config.php");

                // Personelleri çekmek için sorgu
                $queryPersonel = "SELECT * FROM personnel";
                $resultPersonel = $baglanti->query($queryPersonel);

                while ($personel = $resultPersonel->fetch_assoc()) {
                    echo '<option value="' . $personel['id'] . '">' . $personel['personnel_main_name'] . '</option>';
                }
                ?>
            </select><br>
            <label for="islemGiris">Giriş:</label>
            <input type="checkbox" id="islemGiris" name="islem" value="giris">
            <label for="islemCikis">Çıkış:</label>
            <input type="checkbox" id="islemCikis" name="islem" value="cikis"><br>
            <label for="tarih">Tarih ve Saat:</label>
            <input type="datetime-local" id="tarih" name="tarih" required><br>
            <button type="submit" id="veriEkleButton">Veri Ekle</button>
        </form>
    </div>
    <div class="popup-overlay" id="updateCheckoutPopupOverlay"></div>
    <div class="popup" id="updateCheckoutPopup">
        <span class="popup-close" id="updateCheckoutPopupClose">×</span>
        <h2>Çıkış Saatlerini Güncelle</h2>
        <form id="updateCheckoutForm">
            <div id="checkoutPersonnelContainer">
                <!-- Dinamik olarak doldurulacak -->
            </div>
            <button type="submit" id="updateCheckoutSubmitButton">Güncelle</button>
        </form>
    </div>

</body>
</html>