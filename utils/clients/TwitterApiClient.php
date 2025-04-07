<?php
/**
 * TwitterApiClient с поддержкой прокси
 */
class TwitterApiClient extends BaseApiClient {
    /**
     * @var string API ключ
     */
    private $apiKey;
    
    /**
     * @var string API секрет
     */
    private $apiSecret;
    
    /**
     * @var string Токен доступа
     */
    private $accessToken;
    
    /**
     * @var string Секрет токена доступа
     */
    private $accessTokenSecret;
    
    /**
     * Конструктор класса
     * 
     * @param string $apiKey API ключ
     * @param string $apiSecret API секрет
     * @param string $accessToken Токен доступа
     * @param string $accessTokenSecret Секрет токена доступа
     * @param Logger $logger Экземпляр класса для логирования (опционально)
     */
    public function __construct($apiKey, $apiSecret, $accessToken, $accessTokenSecret, $logger = null) {
        parent::__construct($logger);
        
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->accessToken = $accessToken;
        $this->accessTokenSecret = $accessTokenSecret;
    }
    
    /**
     * Публикация твита
     * 
     * @param string $text Текст твита
     * @return array Результат публикации
     */
    public function postTweet($text) {
        $this->logger->debug("Posting tweet: " . substr($text, 0, 50) . "...");
        
        // URL для публикации твита
        $url = 'https://api.twitter.com/1.1/statuses/update.json';
        
        // Параметры запроса
        $params = [
            'status' => $text
        ];
        
        // Создаем подпись OAuth для запроса
        $oauth = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => md5(uniqid(rand(), true)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0'
        ];
        
        // Объединяем параметры для создания подписи
        $signParams = array_merge($oauth, $params);
        
        // Создаем строку для подписи
        $baseString = 'POST&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($signParams, '', '&', PHP_QUERY_RFC3986));
        
        // Создаем ключ для подписи
        $signingKey = rawurlencode($this->apiSecret) . '&' . rawurlencode($this->accessTokenSecret);
        
        // Вычисляем подпись
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
        
        // Создаем заголовок авторизации
        $authHeader = 'OAuth ' . implode(', ', array_map(function($key, $value) {
            return "$key=\"" . rawurlencode($value) . "\"";
        }, array_keys($oauth), $oauth));
        
        // Инициализируем cURL
        $ch = curl_init();
        
        // Настраиваем cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $authHeader]);
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
            $errorMessage = isset($responseData['errors'][0]['message']) 
                ? $responseData['errors'][0]['message'] 
                : "HTTP code: {$info['http_code']}";
            
            $this->logger->error("Twitter API error: $errorMessage");
            return [
                'error' => $errorMessage
            ];
        }
        
        $this->logger->info("Tweet posted successfully, ID: " . ($responseData['id'] ?? 'unknown'));
        return $responseData;
    }
}
