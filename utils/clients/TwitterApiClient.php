<?php
/**
 * TwitterApiClient с поддержкой прокси
 * Использует библиотеку Abraham\TwitterOAuth для авторизации и отправки запросов
 */

// Подключаем автозагрузчик Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Импортируем класс TwitterOAuth
use Abraham\TwitterOAuth\TwitterOAuth;

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
     * @var TwitterOAuth Экземпляр клиента TwitterOAuth
     */
    private $connection;
    
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
        
        // Инициализация соединения будет выполнена при первом запросе
        // для возможности настройки прокси перед соединением
    }
    
    /**
     * Инициализация соединения с Twitter API
     * 
     * @return TwitterOAuth Экземпляр клиента TwitterOAuth
     */
    private function initConnection() {
        if (!$this->connection) {
            $this->logger->debug("Initializing Twitter connection");
            
            // Создаем экземпляр TwitterOAuth
            $this->connection = new TwitterOAuth(
                $this->apiKey,
                $this->apiSecret,
                $this->accessToken,
                $this->accessTokenSecret
            );
            
            // Устанавливаем версию API
            $this->connection->setApiVersion('2');
            
            // Если настроен прокси, применяем его
            if ($this->proxy) {
                $proxyString = "{$this->proxy['protocol']}://{$this->proxy['ip']}:{$this->proxy['port']}";
                
                // Настраиваем прокси для TwitterOAuth
                $this->connection->setProxy([
                    'CURLOPT_PROXY' => $proxyString,
                    'CURLOPT_HTTPPROXYTUNNEL' => true,
                    'CURLOPT_CONNECTTIMEOUT' => 30
                ]);
                
                // Если есть аутентификация, добавляем ее
                if (!empty($this->proxy['username']) && !empty($this->proxy['password'])) {
                    $proxyAuth = "{$this->proxy['username']}:{$this->proxy['password']}";
                    $this->connection->setProxy([
                        'CURLOPT_PROXYUSERPWD' => $proxyAuth
                    ]);
                }
                
                $this->logger->debug("Twitter connection configured with proxy: $proxyString");
            }
        }
        
        return $this->connection;
    }
    
    /**
     * Публикация твита
     * 
     * @param string $text Текст твита
     * @return array Результат публикации
     */
    public function postTweet($text) {
        $this->logger->debug("Posting tweet: " . substr($text, 0, 50) . "...");
        
        try {
            // Инициализируем соединение
            $connection = $this->initConnection();
            
            // Данные для отправки
            $data = [
                'text' => $text
            ];
            
            // Отправка запроса через библиотеку TwitterOAuth
            $response = $connection->post('tweets', $data, true);
            
            // Проверяем наличие ошибок
            if ($connection->getLastHttpCode() != 201) {
                $errorMessage = isset($response->detail) ? $response->detail : 'Unknown error';
                $this->logger->error("Twitter API error: $errorMessage");
                
                return [
                    'error' => $errorMessage,
                    'code' => $connection->getLastHttpCode()
                ];
            }
            
            // Логируем успешную публикацию
            $tweetId = isset($response->data->id) ? $response->data->id : 'unknown';
            $this->logger->info("Tweet posted successfully, ID: $tweetId");
            
            // Преобразуем объект в массив для совместимости с существующим кодом
            return json_decode(json_encode($response), true);
        } catch (\Exception $e) {
            $this->logger->error("Exception during tweet posting: " . $e->getMessage());
            
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
