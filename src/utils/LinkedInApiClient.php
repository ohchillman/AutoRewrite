<?php
/**
 * Класс для взаимодействия с LinkedIn API
 */
class LinkedInApiClient {
    private $clientId;
    private $clientSecret;
    private $accessToken;
    private $apiUrl = 'https://api.linkedin.com/v2';
    
    /**
     * Конструктор класса
     * 
     * @param string $clientId ID клиента
     * @param string $clientSecret Секрет клиента
     * @param string $accessToken Токен доступа
     */
    public function __construct($clientId, $clientSecret, $accessToken) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->accessToken = $accessToken;
    }
    
    /**
     * Метод для публикации текстового поста
     * 
     * @param string $content Содержимое поста
     * @return array Ответ от API
     */
    public function postTextContent($content) {
        $url = $this->apiUrl . '/ugcPosts';
        
        // Получение ID пользователя
        $personId = $this->getPersonId();
        
        if (!$personId) {
            return ['error' => 'Failed to get person ID'];
        }
        
        // Формирование данных для публикации
        $data = [
            'author' => 'urn:li:person:' . $personId,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $content
                    ],
                    'shareMediaCategory' => 'NONE'
                ]
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
            ]
        ];
        
        // Отправка запроса
        $response = $this->sendRequest('POST', $url, $data);
        
        return $response;
    }
    
    /**
     * Метод для получения ID пользователя
     * 
     * @return string|null ID пользователя или null
     */
    private function getPersonId() {
        $url = $this->apiUrl . '/me';
        
        $response = $this->sendRequest('GET', $url);
        
        if (isset($response['id'])) {
            return $response['id'];
        }
        
        return null;
    }
    
    /**
     * Метод для отправки запроса к API
     * 
     * @param string $method HTTP метод
     * @param string $url URL для запроса
     * @param array $data Данные для отправки
     * @return array Ответ от API
     */
    private function sendRequest($method, $url, $data = []) {
        $curl = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'X-Restli-Protocol-Version: 2.0.0'
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
            return ['error' => "cURL Error #:" . $err];
        }
        
        return json_decode($response, true);
    }
}
