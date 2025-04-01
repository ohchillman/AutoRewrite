<?php
/**
 * Класс для взаимодействия с Twitter API
 */
class TwitterApiClient {
    private $apiKey;
    private $apiSecret;
    private $accessToken;
    private $accessTokenSecret;
    
    /**
     * Конструктор класса
     * 
     * @param string $apiKey API ключ
     * @param string $apiSecret API секрет
     * @param string $accessToken Токен доступа
     * @param string $accessTokenSecret Секрет токена доступа
     */
    public function __construct($apiKey, $apiSecret, $accessToken, $accessTokenSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->accessToken = $accessToken;
        $this->accessTokenSecret = $accessTokenSecret;
    }
    
    /**
     * Метод для публикации твита
     * 
     * @param string $content Содержимое твита
     * @return array Ответ от API
     */
    public function postTweet($content) {
        // Базовый URL для Twitter API v2
        $url = 'https://api.twitter.com/2/tweets';
        
        // Данные для отправки
        $data = [
            'text' => $content
        ];
        
        // Формирование заголовков для OAuth 1.0a
        $headers = $this->getOAuthHeaders('POST', $url, $data);
        
        // Отправка запроса
        $response = $this->sendRequest('POST', $url, $data, $headers);
        
        return $response;
    }
    
    /**
     * Метод для получения ленты пользователя
     * 
     * @param string $username Имя пользователя
     * @param int $count Количество твитов
     * @return array Ответ от API
     */
    public function getTimeline($username, $count = 10) {
        // Получение ID пользователя по имени
        $userId = $this->getUserIdByUsername($username);
        
        if (!$userId) {
            return ['error' => 'User not found'];
        }
        
        // Базовый URL для получения твитов пользователя
        $url = "https://api.twitter.com/2/users/{$userId}/tweets";
        
        // Параметры запроса
        $params = [
            'max_results' => $count,
            'tweet.fields' => 'created_at,text'
        ];
        
        // Формирование заголовков для OAuth 1.0a
        $headers = $this->getOAuthHeaders('GET', $url, $params);
        
        // Отправка запроса
        $response = $this->sendRequest('GET', $url . '?' . http_build_query($params), [], $headers);
        
        return $response;
    }
    
    /**
     * Метод для получения ID пользователя по имени
     * 
     * @param string $username Имя пользователя
     * @return string|null ID пользователя или null
     */
    private function getUserIdByUsername($username) {
        $url = 'https://api.twitter.com/2/users/by/username/' . $username;
        
        // Формирование заголовков для OAuth 1.0a
        $headers = $this->getOAuthHeaders('GET', $url, []);
        
        // Отправка запроса
        $response = $this->sendRequest('GET', $url, [], $headers);
        
        if (isset($response['data']['id'])) {
            return $response['data']['id'];
        }
        
        return null;
    }
    
    /**
     * Метод для формирования заголовков OAuth 1.0a
     * 
     * @param string $method HTTP метод
     * @param string $url URL для запроса
     * @param array $params Параметры запроса
     * @return array Заголовки для запроса
     */
    private function getOAuthHeaders($method, $url, $params) {
        $oauth = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => md5(microtime() . mt_rand()),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0'
        ];
        
        // Объединение параметров OAuth и параметров запроса
        $baseParams = array_merge($oauth, $params);
        
        // Сортировка параметров по ключу
        ksort($baseParams);
        
        // Формирование строки параметров
        $paramString = '';
        foreach ($baseParams as $key => $value) {
            $paramString .= $key . '=' . rawurlencode($value) . '&';
        }
        $paramString = rtrim($paramString, '&');
        
        // Формирование базовой строки для подписи
        $baseString = $method . '&' . rawurlencode($url) . '&' . rawurlencode($paramString);
        
        // Формирование ключа для подписи
        $signingKey = rawurlencode($this->apiSecret) . '&' . rawurlencode($this->accessTokenSecret);
        
        // Формирование подписи
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
        
        // Добавление подписи в параметры OAuth
        $oauth['oauth_signature'] = $signature;
        
        // Формирование заголовка Authorization
        $authHeader = 'OAuth ';
        foreach ($oauth as $key => $value) {
            $authHeader .= $key . '="' . rawurlencode($value) . '", ';
        }
        $authHeader = rtrim($authHeader, ', ');
        
        return [
            'Authorization' => $authHeader,
            'Content-Type' => 'application/json'
        ];
    }
    
    /**
     * Метод для отправки запроса к API
     * 
     * @param string $method HTTP метод
     * @param string $url URL для запроса
     * @param array $data Данные для отправки
     * @param array $headers Заголовки для запроса
     * @return array Ответ от API
     */
    private function sendRequest($method, $url, $data = [], $headers = []) {
        $curl = curl_init();
        
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = $key . ': ' . $value;
        }
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $curlHeaders,
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
