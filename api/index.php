<?php
/**
 * API для публикации твитов в Twitter
 * 
 * Принимает POST-запросы на http://localhost:5000/
 * с данными для подключения, прокси и контентом для публикации
 */

// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Устанавливаем заголовки для JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Функция для логирования
function logMessage($message, $type = 'INFO') {
    $logFile = __DIR__ . '/twitter_api.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed. Only POST requests are supported.'
    ]);
    exit;
}

// Получаем данные из тела запроса
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Проверяем корректность данных
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON: ' . json_last_error_msg()
    ]);
    exit;
}

// Проверяем наличие необходимых полей
if (!isset($data['credentials']) || 
    !isset($data['credentials']['api_key']) || 
    !isset($data['credentials']['api_secret']) || 
    !isset($data['credentials']['access_token']) || 
    !isset($data['credentials']['access_secret']) || 
    !isset($data['text'])) {
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields. Required: credentials (api_key, api_secret, access_token, access_secret) and text.'
    ]);
    exit;
}

// Извлекаем данные
$apiKey = $data['credentials']['api_key'];
$apiSecret = $data['credentials']['api_secret'];
$accessToken = $data['credentials']['access_token'];
$accessSecret = $data['credentials']['access_secret'];
$text = $data['text'];
$hasImage = isset($data['has_image']) ? $data['has_image'] : false;
$proxySettings = isset($data['proxy_settings']) ? $data['proxy_settings'] : null;

// Логируем запрос (без конфиденциальных данных)
logMessage("Received request to post tweet: " . substr($text, 0, 50) . "...");
logMessage("Has image: " . ($hasImage ? 'Yes' : 'No'));
logMessage("Using proxy: " . ($proxySettings ? 'Yes' : 'No'));

try {
    // Публикация твита
    $result = postTweet($apiKey, $apiSecret, $accessToken, $accessSecret, $text, $hasImage, $proxySettings);
    
    // Возвращаем результат
    echo json_encode($result);
} catch (Exception $e) {
    // Логируем ошибку
    logMessage("Error posting tweet: " . $e->getMessage(), 'ERROR');
    
    // Возвращаем ошибку
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error posting tweet: ' . $e->getMessage()
    ]);
}

/**
 * Функция для публикации твита
 * 
 * @param string $apiKey API ключ
 * @param string $apiSecret API секрет
 * @param string $accessToken Токен доступа
 * @param string $accessSecret Секрет токена доступа
 * @param string $text Текст твита
 * @param bool $hasImage Флаг наличия изображения
 * @param array|null $proxySettings Настройки прокси
 * @return array Результат публикации
 */
function postTweet($apiKey, $apiSecret, $accessToken, $accessSecret, $text, $hasImage = false, $proxySettings = null) {
    // URL для публикации твита (Twitter API v1.1)
    $url = 'https://api.twitter.com/1.1/statuses/update.json';
    
    // Параметры запроса
    $params = [
        'status' => $text
    ];
    
    // Создаем подпись OAuth для запроса
    $oauth = [
        'oauth_consumer_key' => $apiKey,
        'oauth_nonce' => md5(uniqid(rand(), true)),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => time(),
        'oauth_token' => $accessToken,
        'oauth_version' => '1.0'
    ];
    
    // Объединяем параметры для создания подписи
    $signParams = array_merge($oauth, $params);
    
    // Сортируем параметры по ключу
    ksort($signParams);
    
    // Создаем строку для подписи
    $baseString = 'POST&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($signParams, '', '&', PHP_QUERY_RFC3986));
    
    // Создаем ключ для подписи
    $signingKey = rawurlencode($apiSecret) . '&' . rawurlencode($accessSecret);
    
    // Вычисляем подпись
    $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
    
    // Создаем заголовок авторизации
    $authHeader = 'OAuth ';
    foreach ($oauth as $key => $value) {
        $authHeader .= $key . '="' . rawurlencode($value) . '", ';
    }
    $authHeader = rtrim($authHeader, ', ');
    
    // Инициализируем cURL
    $ch = curl_init();
    
    // Настраиваем cURL
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: ' . $authHeader],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    // Если есть прокси, настраиваем его
    if ($proxySettings) {
        if (isset($proxySettings['host']) && isset($proxySettings['port'])) {
            $proxyUrl = $proxySettings['host'] . ':' . $proxySettings['port'];
            curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
            
            // Если есть аутентификация прокси
            if (isset($proxySettings['username']) && isset($proxySettings['password'])) {
                $proxyAuth = $proxySettings['username'] . ':' . $proxySettings['password'];
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
            }
            
            // Если указан тип прокси
            if (isset($proxySettings['type'])) {
                switch (strtolower($proxySettings['type'])) {
                    case 'http':
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                        break;
                    case 'socks4':
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
                        break;
                    case 'socks5':
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                        break;
                }
            }
        }
    }
    
    // Выполняем запрос
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    
    // Закрываем соединение
    curl_close($ch);
    
    // Проверяем результат
    if ($error) {
        logMessage("cURL error: $error", 'ERROR');
        throw new Exception("cURL error: $error");
    }
    
    // Декодируем ответ
    $responseData = json_decode($response, true);
    
    if ($info['http_code'] != 200) {
        $errorMessage = isset($responseData['errors'][0]['message']) 
            ? $responseData['errors'][0]['message'] 
            : "HTTP code: {$info['http_code']}";
        
        logMessage("Twitter API error: $errorMessage", 'ERROR');
        throw new Exception("Twitter API error: $errorMessage");
    }
    
    // Формируем URL твита
    $tweetUrl = "https://twitter.com/user/status/{$responseData['id_str']}";
    
    // Логируем успешную публикацию
    logMessage("Tweet posted successfully, ID: {$responseData['id_str']}", 'SUCCESS');
    
    // Возвращаем результат
    return [
        'status' => 'success',
        'tweet_id' => $responseData['id_str'],
        'tweet_type' => $hasImage ? 'with_image' : 'text_only',
        'tweet_url' => $tweetUrl
    ];
}
