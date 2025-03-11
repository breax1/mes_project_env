<?php
// Veritabanı bağlantısını başlat
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

$draft = null;

if (isset($_GET['draft_id'])) {
    $draftId = $_GET['draft_id'];

    // Teklif detaylarını çekmek için sorgu
    $queryDraft = "SELECT t.*, p.project_name, c.name as customer_name, u.username as author 
                   FROM propal t 
                   JOIN projects p ON t.project_id = p.id 
                   JOIN customers c ON t.customer_id = c.id 
                   JOIN users u ON t.author = u.id 
                   WHERE t.id = ?";
    $stmtDraft = $baglanti->prepare($queryDraft);
    $stmtDraft->bind_param("i", $draftId);
    $stmtDraft->execute();
    $resultDraft = $stmtDraft->get_result();
    $draft = $resultDraft->fetch_assoc();

    if (!$draft) {
        echo "Teklif bulunamadı.";
        exit;
    }

    // Hammaddeleri çekmek için sorgu
    $queryRawMaterials = "SELECT * FROM kesif_raw_materials WHERE kesif_id = ?";
    $stmtRawMaterials = $baglanti->prepare($queryRawMaterials);
    $stmtRawMaterials->bind_param("i", $draft['id']);
    $stmtRawMaterials->execute();
    $resultRawMaterials = $stmtRawMaterials->get_result();

    // KDV oranlarını çekmek için sorgu
    $queryKDV = "SELECT id, rate FROM kdv";
    $resultKDV = $baglanti->query($queryKDV);

    // Birimleri çekmek için sorgu
    $queryUnits = "SELECT id, unit_name FROM units";
    $resultUnits = $baglanti->query($queryUnits);
} else {
    echo "Geçersiz teklif ID.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teklif Detayları</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>Teklif Detayları</h1>
        <?php if ($draft): ?>
            <p>Proje Adı: <?php echo htmlspecialchars($draft['project_name']); ?></p>
            <p>Müşteri Adı: <?php echo htmlspecialchars($draft['customer_name']); ?></p>
            <p>Teklif Tarihi: <?php echo htmlspecialchars($draft['proposal_date']); ?></p>
            <p>Geçerlilik Süresi: <?php echo htmlspecialchars($draft['validity_period']); ?> gün</p>
            <p>Yazan: <?php echo htmlspecialchars($draft['author']); ?></p>
            <p>Durum: <?php echo $draft['status'] == 0 ? 'Taslak' : 'Tamamlandı'; ?></p>

            <h2>Hammaddeler</h2>
            <table>
                <thead>
                    <tr>
                        <th>Malzeme Adı</th>
                        <th>Birim</th>
                        <th>Miktar</th>
                        <th>Birim Fiyat</th>
                        <th>Toplam Fiyat</th>
                    </tr>
                </thead>
                <tbody id="materialsTableBody">
                    <?php while ($rawMaterial = $resultRawMaterials->fetch_assoc()): 
                        // Birim adını units tablosundan çek
                        $queryUnit = "SELECT unit_name FROM units WHERE id = ?";
                        $stmtUnit = $baglanti->prepare($queryUnit);
                        $stmtUnit->bind_param("i", $rawMaterial['unit']);
                        $stmtUnit->execute();
                        $resultUnit = $stmtUnit->get_result();
                        $unit = $resultUnit->fetch_assoc();

                        // Son fiyatı çekmek için sorgu
                        $queryPrice = "SELECT price FROM raw_material_prices WHERE kesif_raw_material_id = ? ORDER BY created_at DESC LIMIT 1";
                        $stmtPrice = $baglanti->prepare($queryPrice);
                        $stmtPrice->bind_param("i", $rawMaterial['id']);
                        $stmtPrice->execute();
                        $resultPrice = $stmtPrice->get_result();
                        $price = $resultPrice->fetch_assoc();

                        $lastPrice = $price ? $price['price'] : 0;
                        $totalPrice = $rawMaterial['amount'] * $lastPrice;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rawMaterial['raw_material_name']); ?></td>
                            <td><?php echo htmlspecialchars($unit['unit_name']); ?></td>
                            <td><?php echo htmlspecialchars($rawMaterial['amount']); ?></td>
                            <td><?php echo htmlspecialchars($lastPrice); ?></td>
                            <td><?php echo htmlspecialchars($totalPrice); ?></td>
                        </tr>
                    <?php 
                        $stmtUnit->close();
                        $stmtPrice->close();
                    endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Teklif detayları bulunamadı.</p>
        <?php endif; ?>
    </div>
</body>
</html>