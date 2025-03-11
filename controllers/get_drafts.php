<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/db_config.php");

// Taslak teklifleri çekmek için sorgu
$queryTaslaklar = "SELECT t.id, p.project_name, c.name as customer_name, t.proposal_date, t.validity_period, t.amount, u.username as author, t.status 
                   FROM propal t 
                   JOIN projects p ON t.project_id = p.id 
                   JOIN customers c ON t.customer_id = c.id 
                   JOIN users u ON t.author = u.id 
                   WHERE t.status = 0 
                   ORDER BY t.proposal_date DESC";
$resultTaslaklar = $baglanti->query($queryTaslaklar);

// Taslak teklifleri tablo satırları olarak döndür
while ($taslak = $resultTaslaklar->fetch_assoc()) {
    echo "<tr>
            <td>{$taslak['project_name']}</td>
            <td>{$taslak['customer_name']}</td>
            <td>{$taslak['proposal_date']}</td>
            <td>" . date('Y-m-d', strtotime($taslak['proposal_date'] . ' + ' . $taslak['validity_period'] . ' days')) . "</td>
            <td>{$taslak['amount']}</td>
            <td>{$taslak['author']}</td>
            <td>" . ($taslak['status'] == 0 ? 'Taslak' : 'Tamamlandı') . "</td>
          </tr>";
}
?>