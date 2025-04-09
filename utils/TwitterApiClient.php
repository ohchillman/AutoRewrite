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
     * Конструктор класса
     * 
     * @param string $apiKey API ключ (consumer key)
     * @param string $apiSecret API секрет (consumer secret)
     * @param string $accessToken Токен доступа
     * @param string $accessTokenSecret Секрет токена доступа
     */
    public function __construct($apiKey, $apiSecret, $accessToken, $accessTokenSecret) {
        // Инициализируем соединение с Twitter API через библиотеку
        $this->connection = new TwitterOAuth($apiKey, $apiSecret, $accessToken, $accessTokenSecret);
        
        // Устанавливаем версию API
        $this->connection->setApiVersion('2');
    }
    
    /**
     * Метод для публикации твита
     * 
     * @param string $content Содержимое твита
     * @return array Ответ от API
     */
    public function postTweet($content) {
        try {
            // Данные для отправки
            $data = [
                'text' => $content
            ];
            
            // Отправка запроса через библиотеку TwitterOAuth
            $response = $this->connection->post('tweets', $data, true);
            
            // Проверяем наличие ошибок
            if ($this->connection->getLastHttpCode() != 201) {
                return [
                    'error' => isset($response->detail) ? $response->detail : 'Unknown error',
                    'code' => $this->connection->getLastHttpCode()
                ];
            }
            
            // Преобразуем объект в массив для совместимости с существующим кодом
            return json_decode(json_encode($response), true);
        } catch (\Exception $e) {
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
            $response = $this->connection->get("users/{$userId}/tweets", $params);
            
            // Проверяем наличие ошибок
            if ($this->connection->getLastHttpCode() != 200) {
                return [
                    'error' => isset($response->detail) ? $response->detail : 'Unknown error',
                    'code' => $this->connection->getLastHttpCode()
                ];
            }
            
            // Преобразуем объект в массив для совместимости с существующим кодом
            return json_decode(json_encode($response), true);
        } catch (\Exception $e) {
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
            // Отправка запроса через библиотеку TwitterOAuth
            $response = $this->connection->get("users/by/username/{$username}");
            
            // Проверяем наличие ошибок
            if ($this->connection->getLastHttpCode() != 200) {
                return null;
            }
            
            // Возвращаем ID пользователя
            if (isset($response->data->id)) {
                return $response->data->id;
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
