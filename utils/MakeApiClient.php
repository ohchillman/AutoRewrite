<?php
/**
 * Класс для взаимодействия с Make.com API
 */
class MakeApiClient {
    private $apiKey;
    private $apiUrl;
    
    /**
     * Конструктор класса
     * 
     * @param string $apiKey API ключ для доступа к Make.com
     * @param string $apiUrl Базовый URL API Make.com
     */
    public function __construct($apiKey, $apiUrl = 'https://api.make.com/v1') {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }
    
    /**
     * Метод для реврайта контента
     * 
     * @param string $content Исходный контент для реврайта
     * @param string $template Шаблон для реврайта
     * @return array Ответ от API с реврайтнутым контентом
     */
    public function rewriteContent($content, $template) {
        $url = $this->apiUrl . '/scenarios/trigger';
        $data = [
            'content' => $content,
            'template' => $template
        ];
        
        $response = $this->sendRequest('POST', $url, $data);
        return $response;
    }
    
    /**
     * Метод для отправки запроса к API
     * 
     * @param string $method HTTP метод (GET, POST, PUT, DELETE)
     * @param string $url URL для запроса
     * @param array $data Данные для отправки
     * @return array Ответ от API
     */
    private function sendRequest($method, $url, $data = []) {
        $curl = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #:" . $err);
        }
        
        return json_decode($response, true);
    }
}
