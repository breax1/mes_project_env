<?php

class MobilizHandler {
    const WEBSERVICE_URL = 'https://ng.mobiliz.com.tr/su0/api/vehicle/laststate';
    const CLIENT_TOKEN = '14d01696962e6d244b66e1cec1f6d12b577c162956ae5706c75ac44605d3f7e3';

    public function getLastVehicleState($vehicleIds) {
        $responses = [];
        $mh = curl_multi_init();
        $curlHandles = [];

        foreach ($vehicleIds as $vehicleId) {
            $url = self::WEBSERVICE_URL;
            $payload = json_encode(['intParam' => $vehicleId]);
            
            echo "Gönderilen URL: " . $url . " | Payload: " . $payload . "\n";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json;charset=UTF-8',
                'Mobiliz-Token: ' . self::CLIENT_TOKEN,
                'Accept: application/json, text/plain, */*'
            ]);

            curl_multi_add_handle($mh, $ch);
            $curlHandles[$vehicleId] = $ch;
        }

        // Paralel sorguları çalıştır
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        // Sonuçları topla
        foreach ($curlHandles as $vehicleId => $ch) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $result = curl_multi_getcontent($ch);
            $responses[$vehicleId] = [
                'status' => $httpCode,
                'response' => $result
            ];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        return $responses;
    }

    public function processResponses($responses) {
        foreach ($responses as $vehicleId => $response) {
            echo "Araç ID: $vehicleId için sorgu sonucu:\n";
            
            if ($response['status'] === 200) {
                $data = json_decode($response['response'], true);
                // Yanıt yapısını bilmiyoruz, varsayılan olarak önceki yapıya benzer olduğunu düşünüyoruz
                if (isset($data['success']) && $data['success'] && !empty($data['result'])) {
                    $vehicle = $data['result'][0];
                    echo "Plaka: " . ($vehicle['plate'] ?? 'Bilinmiyor') . "\n";
                    echo "Hız: " . ($vehicle['speed'] ?? 'Bilinmiyor') . " km/h\n";
                    echo "Konum: " . ($vehicle['latitude'] ?? 'Bilinmiyor') . ", " . ($vehicle['longitude'] ?? 'Bilinmiyor') . "\n";
                    echo "Son Veri Zamanı: " . ($vehicle['dataTime'] ?? 'Bilinmiyor') . "\n";
                    echo "Filo: " . ($vehicle['fleetName'] ?? 'Bilinmiyor') . "\n";
                    echo "Grup: " . ($vehicle['groupName'] ?? 'Bilinmiyor') . "\n";
                    echo "Şehir: " . ($vehicle['city'] ?? 'Bilinmiyor') . "\n";
                    echo "Kontak: " . ($vehicle['ignition'] ?? 'Bilinmiyor') . "\n";
                    echo "-------------------\n";
                } else {
                    echo "Veri: " . print_r($data, true) . "\n"; // Yanıt yapısını görmek için
                }
            } else {
                echo "HTTP Hata Kodu: " . $response['status'] . " (Araç ID: $vehicleId)\n";
                echo "Yanıt: " . $response['response'] . "\n";
            }
        }
    }
}

// Kullanım
try {
    $handler = new MobilizHandler();
    
    $vehicleIds = [
        4459481
    ];
    
    // Tüm araçları aynı anda sorgula
    $responses = $handler->getLastVehicleState($vehicleIds);
    
    // Sonuçları işle
    $handler->processResponses($responses);
    
} catch (Exception $e) {
    echo "Bir hata oluştu: " . $e->getMessage() . "\n";
}

?>