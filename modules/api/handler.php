<?php
namespace Mobiliz\WebService;

class Handler {
    private $url = '';
    private $token;
    private $baseUrl;

    public function __construct() {
        $this->token = getenv('MOBILIZ_API_TOKEN');
        $this->baseUrl = getenv('MOBILIZ_API_URL');
        
        if (!$this->token || !$this->baseUrl) {
            throw new \Exception('API yapılandırması eksik. Lütfen .env dosyasını kontrol edin.');
        }
    }

    public function generateURL($serviceName, $options = []) {
        $this->url = $this->baseUrl . $serviceName;
        if (!empty($options)) {
            $this->url .= '?' . urldecode(http_build_query($options));
        }
        return $this;
    }

    public function sendQuery() {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Mobiliz-Token: ' . $this->token
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
?>
