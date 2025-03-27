<?php
date_default_timezone_set('Europe/Istanbul'); // Saat dilimini ayarla

// Veritabanı bağlantısını include ile ekle
include("../includes/db_config.php");

// MAC adresini al
$mac = $_SERVER['HTTP_MAC_ADDRESS'] ?? '';

// MAC adresini doğrula
$allowed_macs = array("DE:AD:BE:EF:FE:ED"); // İzin verilen MAC adreslerini buraya ekleyin
if (!in_array($mac, $allowed_macs)) {
    mysqli_close($baglanti);
    echo "ERR";
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data) && is_array($data)) {
    // Toplu işlem
    foreach ($data as $entry) {
        if (isset($entry['card_id']) && isset($entry['time'])) {
            $card_id = $entry['card_id'];
            $time = $entry['time'];

            // Kart ID ile personel bilgisi al
            $sql = "SELECT id, personnel_name FROM personnel WHERE kart_id = ?";
            $stmt = $baglanti->prepare($sql);
            $stmt->bind_param("s", $card_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $personnel_id = $row["id"];
                $personnel_name = $row["personnel_name"];

                // Son giriş-çıkış işlemini kontrol et
                $check_sql = "SELECT islem, time FROM giris_cikis WHERE personnel_id = ? ORDER BY id DESC LIMIT 1";
                $stmt_check = $baglanti->prepare($check_sql);
                $stmt_check->bind_param("i", $personnel_id);
                $stmt_check->execute();
                $check_result = $stmt_check->get_result();

                if ($check_result->num_rows > 0) {
                    $last_row = $check_result->fetch_assoc();
                    $last_islem = $last_row["islem"];
                    $last_time = strtotime($last_row["time"]);
                    $current_time = strtotime($time);

                    // Son işlem 1 dakikadan daha kısa bir süre önce yapıldıysa işlem yapma
                    if (($current_time - $last_time) < 60) {
                        continue;
                    }

                    // Son işlem "giris" ise "cikis" yap, değilse "giris" yap
                    $new_islem = ($last_islem == 'giris') ? 'cikis' : 'giris';
                } else {
                    // Hiçbir işlem yoksa "giris" yap
                    $new_islem = 'giris';
                }

                // Giriş-çıkış tablosuna kayıt ekle
                $insert_sql = "INSERT INTO giris_cikis (personnel_id, islem, time) VALUES (?, ?, ?)";
                $stmt_insert = $baglanti->prepare($insert_sql);
                $stmt_insert->bind_param("iss", $personnel_id, $new_islem, $time);
                $stmt_insert->execute();
            }
        }
    }
    echo "BASARILI";
} elseif (isset($_GET['card_id']) && isset($_GET['time'])) {
    // Tekil işlem
    $card_id = $_GET['card_id'];
    $time = $_GET['time'];

    // Kart ID ile personel bilgisi al
    $sql = "SELECT id, personnel_name FROM personnel WHERE kart_id = ?";
    $stmt = $baglanti->prepare($sql);
    $stmt->bind_param("s", $card_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $personnel_id = $row["id"];
        $personnel_name = $row["personnel_name"];

        // Son giriş-çıkış işlemini kontrol et
        $check_sql = "SELECT islem, time FROM giris_cikis WHERE personnel_id = ? ORDER BY id DESC LIMIT 1";
        $stmt_check = $baglanti->prepare($check_sql);
        $stmt_check->bind_param("i", $personnel_id);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();

        if ($check_result->num_rows > 0) {
            $last_row = $check_result->fetch_assoc();
            $last_islem = $last_row["islem"];
            $last_time = strtotime($last_row["time"]);
            $current_time = strtotime($time);

            // Son işlem 1 dakikadan daha kısa bir süre önce yapıldıysa işlem yapma
            if (($current_time - $last_time) < 60) {
                mysqli_close($baglanti);
                echo "1 dakika gecmedi";
                exit();
            }

            // Son işlem "giris" ise "cikis" yap, değilse "giris" yap
            $new_islem = ($last_islem == 'giris') ? 'cikis' : 'giris';
        } else {
            // Hiçbir işlem yoksa "giris" yap
            $new_islem = 'giris';
        }

        // Giriş-çıkış tablosuna kayıt ekle
        $insert_sql = "INSERT INTO giris_cikis (personnel_id, islem, time) VALUES (?, ?, ?)";
        $stmt_insert = $baglanti->prepare($insert_sql);
        $stmt_insert->bind_param("iss", $personnel_id, $new_islem, $time);
        $stmt_insert->execute();

        echo $personnel_name;
    } else {
        echo "Bilinmeyen Kart";
    }
} else {
    echo "Eksik parametreler";
}

mysqli_close($baglanti);
?>