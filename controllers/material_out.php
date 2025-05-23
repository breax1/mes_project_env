<?php
include("../includes/db_config.php");
session_start(); // Oturum başlat

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materialId = $_POST['materialName'];
    $amount = $_POST['materialAmount'];
    $personnelId = $_POST['personnel'];
    $projectId = !empty($_POST['project']) ? $_POST['project'] : NULL;
    $note = $_POST['note'];
    $userId = $_SESSION['user_id']; // Kullanıcı ID'sini oturumdan al

    // Stok miktarını kontrol et
    $query = "SELECT urun_stok_miktari FROM stock WHERE id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("i", $materialId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock = $result->fetch_assoc();

    if ($stock['urun_stok_miktari'] < $amount) {
        echo json_encode(['status' => 'error', 'message' => 'Yeterli stok yok.']);
        exit;
    }

    // Stok miktarını güncelle
    $query = "UPDATE stock SET urun_stok_miktari = urun_stok_miktari - ? WHERE id = ?";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("ii", $amount, $materialId);
    $stmt->execute();

    // Kullanım kaydını ekle
    $query = "INSERT INTO stock_uses (stock_id, uses_personnel_id, uses_projects,note, value) VALUES (?, ?, ?, ?, ?)";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("iiisi", $materialId, $personnelId, $projectId, $note, $amount);
    $stmt->execute();

    // Logs tablosuna kayıt ekle
    $negativeAmount = -1 * $amount; // Çıkarılan stok miktarını negatif yap
    $query = "INSERT INTO stock_log (islem, user_id, stock_id, eklenen_stok_miktari) VALUES ('personel', ?, ?, ?)";
    $stmt = $baglanti->prepare($query);
    $stmt->bind_param("iii", $userId, $materialId, $negativeAmount);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Malzeme başarıyla çıkartıldı.']);
}
?>