<?php
require_once '../modules/api/handler.php'; // Mobiliz Handler sınıfı burada

use Mobiliz\WebService\Handler;

header('Content-Type: application/json');

try {
    $handler = new Handler();

    // Eğer filtre istersen GET parametresinden alabilirsin:
    $params = [];
    if (isset($_GET['startTime'])) {
        $params['startTime'] = $_GET['startTime'];
    }

    // API'ye istek at
    $response = $handler->generateURL('activity/last', $params)->sendQuery();

    echo $response;
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
