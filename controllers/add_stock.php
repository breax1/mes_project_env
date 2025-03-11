<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // Oturumu başlat

include("../includes/db_config.php");

$urunAdi = $_POST['urunAdi'];
$urunRafi = $_POST['urunRafi'];
$urunStokMiktari = intval($_POST['urunStokMiktari']);
$urunKritikStok = $_POST['urunKritikStok'];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id']; // Kullanıcı ID'sini oturumdan alın
} else {
    echo "Kullanıcı oturumu bulunamadı.";
    exit;
}

// Önce ürünün stokta olup olmadığını kontrol edelim
$queryCheckStock = "SELECT id, urun_stok_miktari, shelf_id, urun_kritik_stok FROM stock WHERE urun_adi = ?";
$stmtCheckStock = $baglanti->prepare($queryCheckStock);
$stmtCheckStock->bind_param("s", $urunAdi);
$stmtCheckStock->execute();
$resultCheckStock = $stmtCheckStock->get_result();

if ($row = $resultCheckStock->fetch_assoc()) {
    // Ürün stokta var, bilgileri güncelle
    $stockId = $row['id'];
    $oldStockAmount = $row['urun_stok_miktari']; // Eski stok miktarı
    $newStockAmount = $oldStockAmount + $urunStokMiktari;

    // Mevcut bilgilerle yeni girilen bilgiler aynı mı kontrol et
    $updateRequired = false;
    if ($row['shelf_id'] != $urunRafi || $row['urun_kritik_stok'] != $urunKritikStok) {
        $updateRequired = true;
    }

    // Ürün bilgilerini ve stok miktarını güncelle
    $queryUpdateStock = "UPDATE stock SET  shelf_id = ?, urun_kritik_stok = ?, urun_stok_miktari = ? WHERE id = ?";
    $stmtUpdateStock = $baglanti->prepare($queryUpdateStock);
    $stmtUpdateStock->bind_param("iiii", $urunRafi, $urunKritikStok, $newStockAmount, $stockId);

    if ($stmtUpdateStock->execute()) {
        // Eğer sadece stok miktarı değiştiyse loglara yaz
        if ($urunStokMiktari > 0) {
            $queryAddStockLog = "INSERT INTO stock_log (user_id, stock_id, eklenen_stok_miktari) VALUES (?, ?, ?)";
            $stmtAddStockLog = $baglanti->prepare($queryAddStockLog);
            $stmtAddStockLog->bind_param("iii", $userId, $stockId, $urunStokMiktari);
            $stmtAddStockLog->execute();
            $stmtAddStockLog->close();
        }

        echo "Stok bilgileri ve miktarı güncellendi.";
    } else {
        echo "Stok güncelleme başarısız: " . $stmtUpdateStock->error;
    }

    $stmtUpdateStock->close();
} else {
    // Ürün stokta yok, yeni kayıt ekle
    $queryAddStock = "INSERT INTO stock (urun_adi, shelf_id, urun_stok_miktari, urun_kritik_stok) 
                      VALUES (?, ?, ?, ?)";
    $stmtAddStock = $baglanti->prepare($queryAddStock);
    $stmtAddStock->bind_param("siii", $urunAdi, $urunRafi, $urunStokMiktari, $urunKritikStok);

    if ($stmtAddStock->execute()) {
        $stockId = $stmtAddStock->insert_id;

        // Stok ekleme işlemini logla
        $queryAddStockLog = "INSERT INTO stock_log (user_id, stock_id, eklenen_stok_miktari) VALUES (?, ?, ?)";
        $stmtAddStockLog = $baglanti->prepare($queryAddStockLog);
        $stmtAddStockLog->bind_param("iii", $userId, $stockId, $urunStokMiktari);
        $stmtAddStockLog->execute();
        $stmtAddStockLog->close();

        echo "Yeni stok başarıyla eklendi.";
    } else {
        echo "Stok ekleme başarısız: " . $stmtAddStock->error;
    }

    $stmtAddStock->close();
}

$stmtCheckStock->close();
$baglanti->close();
?>
