<?php
/**
 * LinkedInApiClient с поддержкой прокси
 */
class LinkedInApiClient extends BaseApiClient {
    /**
     * @var string Client ID
     */
    private $clientId;
    
    /**
     * @var string Client Secret
     */
    private $clientSecret;
    
    /**
     * @var string Токен доступа
     */
    private $accessToken;
    
    /**
     * @var string Refresh токен
     */
    private $refreshToken;
    
    /**
     * Конструктор класса
     * 
     * @param string $clientId Client ID
     * @param string $clientSecret Client Secret
     * @param string $accessToken Токен доступа
     * @param Logger $logger Экземпляр класса для логирования (опционально)
     */
    public function __construct($clientId, $clientSecret, $accessToken, $logger = null) {
        parent::__construct($logger);
        
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->accessToken = $accessToken;
    }
    
    /**
     * Установка refresh токена
     * 
     * @param string $refreshToken Refresh токен
     * @return self
     */
    public function setRefreshToken($refreshToken) {
        $this->refreshToken = $refreshToken;
        return $this;
    }
    
    /**
     * Публикация текстового контента в LinkedIn
     * 
     * @param string $text Текст публикации
     * @return array Результат публикации
     */
    public function postTextContent($text) {
        $this->logger->debug("Posting content to LinkedIn: " . substr($text, 0, 50) . "...");
        
        // URL для публикации контента
        $url = 'https://api.linkedin.com/v2/ugcPosts';
        
        // Получаем ID пользователя
        $userId = $this->getUserId();
        
        if (isset($userId['error'])) {
            return $userId;
        }
        
        // Формируем данные для публикации
        $postData = [
            'author' => 'urn:li:person:' . $userId,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $text
                    ],
                    'shareMediaCategory' => 'NONE'
                ]
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
            ]
        ];
        
        // Инициализируем cURL
        $ch = curl_init();
        
        // Настраиваем cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'X-Restli-Protocol-Version: 2.0.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // Если есть прокси, настраиваем его
        if ($this->proxy) {
            $this->setupCurlWithProxy($ch);
        }
        
        // Выполняем запрос
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        // Закрываем соединение
        curl_close($ch);
        
        // Проверяем результат
        if ($error) {
            $this->logger->error("cURL error: $error");
            return [
                'error' => $error
            ];
        }
        
        // Декодируем ответ
        $responseData = json_decode($response, true);
        
        if ($info['http_code'] != 201) {
            $errorMessage = isset($responseData['message']) 
                ? $responseData['message'] 
                : "HTTP code: {$info['http_code']}";
            
            $this->logger->error("LinkedIn API error: $errorMessage");
            return [
                'error' => $errorMessage
            ];
        }
        
        $this->logger->info("Content posted successfully to LinkedIn");
        return $responseData;
    }
    
    /**
     * Получение ID пользователя
     * 
     * @return string|array ID пользователя или массив с ошибкой
     */
    private function getUserId() {
        $this->logger->debug("Getting LinkedIn user ID");
        
        // URL для получения данных пользователя
        $url = 'https://api.linkedin.com/v2/me';
        
        // Инициализируем cURL
        $ch = curl_init();
        
        // Настраиваем cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'X-Restli-Protocol-Version: 2.0.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // Если есть прокси, настраиваем его
        if ($this->proxy) {
            $this->setupCurlWithProxy($ch);
        }
        
        // Выполняем запрос
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        // Закрываем соединение
        curl_close($ch);
        
        // Проверяем результат
        if ($error) {
            $this->logger->error("cURL error: $error");
            return [
                'error' => $error
            ];
        }
        
        // Декодируем ответ
        $responseData = json_decode($response, true);
        
        if ($info['http_code'] != 200) {
            $errorMessage = isset($responseData['message']) 
                ? $responseData['message'] 
                : "HTTP code: {$info['http_code']}";
            
            $this->logger->error("LinkedIn API error: $errorMessage");
            return [
                'error' => $errorMessage
            ];
        }
        
        if (!isset($responseData['id'])) {
            $this->logger->error("LinkedIn user ID not found in response");
            return [
                'error' => 'User ID not found in response'
            ];
        }
        
        $this->logger->debug("LinkedIn user ID: {$responseData['id']}");
        return $responseData['id'];
    }
}
