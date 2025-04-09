<?php
/**
 * Класс для взаимодействия с Twitter API
 * Использует библиотеку Abraham\TwitterOAuth для авторизации и отправки запросов
 */

// Подключаем автозагрузчик Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Импортируем класс TwitterOAuth
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterApiClient {
    /**
     * @var TwitterOAuth Экземпляр клиента TwitterOAuth
     */
    private $connection;
    
    /**
     * @var array Данные прокси
     */
    private $proxy = null;
    
    /**
     * @var Logger Экземпляр класса для логирования
     */
    private $logger = null;
    
    /**
     * Конструктор класса
     * 
     * @param string $apiKey API ключ (consumer key)
     * @param string $apiSecret API секрет (consumer secret)
     * @param string $accessToken Токен доступа
     * @param string $accessTokenSecret Секрет токена доступа
     * @param Logger $logger Экземпляр класса для логирования (опционально)
     */
    public function __construct($apiKey, $apiSecret, $accessToken, $accessTokenSecret, $logger = null) {
        // Инициализируем логгер
        $this->logger = $logger ?: new Logger('twitter_api_client');
        
        // Сохраняем учетные данные
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->accessToken = $accessToken;
        $this->accessTokenSecret = $accessTokenSecret;
        
        // Инициализация соединения будет выполнена при первом запросе
        // для возможности настройки прокси перед соединением
    }
    
    /**
     * Установка прокси для API клиента
     * 
     * @param array $proxy Данные прокси
     * @return self
     */
    public function setProxy($proxy) {
        $this->proxy = $proxy;
        if ($this->logger) {
            $this->logger->debug("Proxy set: {$proxy['ip']}:{$proxy['port']}");
        }
        return $this;
    }
    
    /**
     * Инициализация соединения с Twitter API
     * 
     * @return TwitterOAuth Экземпляр клиента TwitterOAuth
     */
    private function initConnection() {
        if (!$this->connection) {
            if ($this->logger) {
                $this->logger->debug("Initializing Twitter connection");
            }
            
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
                
                if ($this->logger) {
                    $this->logger->debug("Twitter connection configured with proxy: $proxyString");
                }
            }
        }
        
        return $this->connection;
    }
    
    /**
     * Метод для публикации твита
     * 
     * @param string $content Содержимое твита
     * @return array Ответ от API
     */
    public function postTweet($content) {
        if ($this->logger) {
            $this->logger->debug("Posting tweet: " . substr($content, 0, 50) . "...");
        }
        
        try {
            // Инициализируем соединение
            $connection = $this->initConnection();
            
            // Данные для отправки
            $data = [
                'text' => $content
            ];
            
            // Отправка запроса через библиотеку TwitterOAuth
            $response = $connection->post('tweets', $data, true);
            
            // Проверяем наличие ошибок
            if ($connection->getLastHttpCode() != 201) {
                $errorMessage = isset($response->detail) ? $response->detail : 'Unknown error';
                if ($this->logger) {
                    $this->logger->error("Twitter API error: $errorMessage");
                }
                
                return [
                    'error' => $errorMessage,
                    'code' => $connection->getLastHttpCode()
                ];
            }
            
            // Логируем успешную публикацию
            $tweetId = isset($response->data->id) ? $response->data->id : 'unknown';
            if ($this->logger) {
                $this->logger->info("Tweet posted successfully, ID: $tweetId");
            }
            
            // Преобразуем объект в массив для совместимости с существующим кодом
            return json_decode(json_encode($response), true);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Exception during tweet posting: " . $e->getMessage());
            }
            
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Метод для получения ленты пользователя
     * 
     * @param string $username Имя пользователя
     * @param int $count Количество твитов
     * @return array Ответ от API
     */
    public function getTimeline($username, $count = 10) {
        try {
            // Инициализируем соединение
            $connection = $this->initConnection();
            
            // Получение ID пользователя по имени
            $userId = $this->getUserIdByUsername($username);
            
            if (!$userId) {
                return ['error' => 'User not found'];
            }
            
            // Параметры запроса
            $params = [
                'max_results' => $count,
                'tweet.fields' => 'created_at,text'
            ];
            
            // Отправка запроса через библиотеку TwitterOAuth
            $response = $connection->get("users/{$userId}/tweets", $params);
            
            // Проверяем наличие ошибок
            if ($connection->getLastHttpCode() != 200) {
                $errorMessage = isset($response->detail) ? $response->detail : 'Unknown error';
                if ($this->logger) {
                    $this->logger->error("Twitter API error: $errorMessage");
                }
                
                return [
                    'error' => $errorMessage,
                    'code' => $connection->getLastHttpCode()
                ];
            }
            
            // Преобразуем объект в массив для совместимости с существующим кодом
            return json_decode(json_encode($response), true);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Exception during timeline retrieval: " . $e->getMessage());
            }
            
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Метод для получения ID пользователя по имени
     * 
     * @param string $username Имя пользователя
     * @return string|null ID пользователя или null
     */
    private function getUserIdByUsername($username) {
        try {
            // Инициализируем соединение
            $connection = $this->initConnection();
            
            // Отправка запроса через библиотеку TwitterOAuth
            $response = $connection->get("users/by/username/{$username}");
            
            // Проверяем наличие ошибок
            if ($connection->getLastHttpCode() != 200) {
                return null;
            }
            
            // Возвращаем ID пользователя
            if (isset($response->data->id)) {
                return $response->data->id;
            }
            
            return null;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Exception during user ID retrieval: " . $e->getMessage());
            }
            return null;
        }
    }
}
