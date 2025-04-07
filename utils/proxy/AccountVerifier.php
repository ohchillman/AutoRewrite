<?php
/**
 * AccountVerifier - класс для проверки работоспособности аккаунтов с использованием прокси
 * 
 * Отвечает за:
 * - Проверку учетных данных аккаунтов
 * - Тестирование соединения с API платформ через прокси
 * - Валидацию аккаунтов перед публикацией контента
 */
class AccountVerifier {
    /**
     * @var Database Экземпляр класса для работы с базой данных
     */
    private $db;
    
    /**
     * @var ProxyManager Экземпляр класса для работы с прокси
     */
    private $proxyManager;
    
    /**
     * @var Logger Экземпляр класса для логирования
     */
    private $logger;
    
    /**
     * Конструктор класса
     * 
     * @param Database $db Экземпляр класса для работы с базой данных
     * @param ProxyManager $proxyManager Экземпляр класса для работы с прокси
     * @param Logger $logger Экземпляр класса для логирования (опционально)
     */
    public function __construct($db, $proxyManager, $logger = null) {
        $this->db = $db;
        $this->proxyManager = $proxyManager;
        $this->logger = $logger ?: new Logger('account_verifier');
    }
    
    /**
     * Проверка работоспособности аккаунта
     * 
     * @param int|array $account ID аккаунта или массив с данными аккаунта
     * @return array Результат проверки [success => bool, message => string]
     */
    public function verifyAccount($account) {
        try {
            // Если передан ID аккаунта, получаем данные аккаунта
            if (is_numeric($account)) {
                $accountData = $this->db->fetchOne("
                    SELECT a.*, at.name as account_type_name 
                    FROM accounts a
                    JOIN account_types at ON a.account_type_id = at.id
                    WHERE a.id = ?
                ", [$account]);
                
                if (!$accountData) {
                    $this->logger->error("Аккаунт с ID $account не найден");
                    return [
                        'success' => false,
                        'message' => "Аккаунт не найден"
                    ];
                }
            } else {
                $accountData = $account;
            }
            
            // Получаем прокси для аккаунта
            $proxy = $this->proxyManager->getProxyForAccount($accountData);
            
            // Проверяем прокси, если он указан
            if ($proxy) {
                $proxyCheck = $this->proxyManager->checkProxy($proxy);
                if (!$proxyCheck['success']) {
                    $this->logger->warning("Прокси для аккаунта {$accountData['name']} не работает: {$proxyCheck['details']}");
                    return [
                        'success' => false,
                        'message' => "Ошибка прокси: {$proxyCheck['details']}"
                    ];
                }
            }
            
            // Проверяем аккаунт в зависимости от типа
            $accountType = strtolower($accountData['account_type_name']);
            
            switch ($accountType) {
                case 'twitter':
                    return $this->verifyTwitterAccount($accountData, $proxy);
                    
                case 'linkedin':
                    return $this->verifyLinkedInAccount($accountData, $proxy);
                    
                case 'youtube':
                    return $this->verifyYouTubeAccount($accountData, $proxy);
                    
                case 'threads':
                    return $this->verifyThreadsAccount($accountData, $proxy);
                    
                default:
                    $this->logger->warning("Неизвестный тип аккаунта: $accountType");
                    return [
                        'success' => false,
                        'message' => "Неподдерживаемый тип аккаунта: $accountType"
                    ];
            }
        } catch (Exception $e) {
            $this->logger->error("Ошибка при проверке аккаунта: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Проверка аккаунта Twitter
     * 
     * @param array $account Данные аккаунта
     * @param array|null $proxy Данные прокси (опционально)
     * @return array Результат проверки [success => bool, message => string]
     */
    private function verifyTwitterAccount($account, $proxy = null) {
        // Проверяем наличие необходимых данных
        if (empty($account['api_key']) || empty($account['api_secret']) || 
            empty($account['access_token']) || empty($account['access_token_secret'])) {
            return [
                'success' => false,
                'message' => "Отсутствуют необходимые данные API для Twitter"
            ];
        }
        
        try {
            // URL для проверки API Twitter
            $url = 'https://api.twitter.com/1.1/account/verify_credentials.json';
            
            // Создаем подпись OAuth для запроса
            $oauth = [
                'oauth_consumer_key' => $account['api_key'],
                'oauth_nonce' => md5(uniqid(rand(), true)),
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => time(),
                'oauth_token' => $account['access_token'],
                'oauth_version' => '1.0'
            ];
            
            // Создаем строку для подписи
            $baseString = 'GET&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($oauth, '', '&', PHP_QUERY_RFC3986));
            
            // Создаем ключ для подписи
            $signingKey = rawurlencode($account['api_secret']) . '&' . rawurlencode($account['access_token_secret']);
            
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
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $authHeader]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            // Если есть прокси, настраиваем его
            if ($proxy) {
                $this->proxyManager->setupCurlWithProxy($ch, $proxy);
            }
            
            // Выполняем запрос
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            
            // Закрываем соединение
            curl_close($ch);
            
            // Проверяем результат
            if ($error) {
                $this->logger->error("Ошибка cURL при проверке Twitter: $error");
                return [
                    'success' => false,
                    'message' => "Ошибка соединения: $error"
                ];
            }
            
            if ($info['http_code'] != 200) {
                $responseData = json_decode($response, true);
                $errorMessage = isset($responseData['errors'][0]['message']) 
                    ? $responseData['errors'][0]['message'] 
                    : "HTTP-код: {$info['http_code']}";
                
                $this->logger->error("Ошибка API Twitter: $errorMessage");
                return [
                    'success' => false,
                    'message' => "Ошибка API Twitter: $errorMessage"
                ];
            }
            
            // Декодируем ответ
            $userData = json_decode($response, true);
            
            if (!isset($userData['id'])) {
                $this->logger->error("Некорректный ответ от API Twitter");
                return [
                    'success' => false,
                    'message' => "Некорректный ответ от API Twitter"
                ];
            }
            
            $this->logger->info("Аккаунт Twitter успешно проверен: @{$userData['screen_name']}");
            return [
                'success' => true,
                'message' => "Аккаунт Twitter успешно проверен: @{$userData['screen_name']}"
            ];
        } catch (Exception $e) {
            $this->logger->error("Исключение при проверке Twitter: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Проверка аккаунта LinkedIn
     * 
     * @param array $account Данные аккаунта
     * @param array|null $proxy Данные прокси (опционально)
     * @return array Результат проверки [success => bool, message => string]
     */
    private function verifyLinkedInAccount($account, $proxy = null) {
        // Проверяем наличие необходимых данных
        if (empty($account['access_token'])) {
            return [
                'success' => false,
                'message' => "Отсутствует токен доступа для LinkedIn"
            ];
        }
        
        try {
            // URL для проверки API LinkedIn
            $url = 'https://api.linkedin.com/v2/me';
            
            // Инициализируем cURL
            $ch = curl_init();
            
            // Настраиваем cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $account['access_token'],
                'X-Restli-Protocol-Version: 2.0.0'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            // Если есть прокси, настраиваем его
            if ($proxy) {
                $this->proxyManager->setupCurlWithProxy($ch, $proxy);
            }
            
            // Выполняем запрос
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            
            // Закрываем соединение
            curl_close($ch);
            
            // Проверяем результат
            if ($error) {
                $this->logger->error("Ошибка cURL при проверке LinkedIn: $error");
                return [
                    'success' => false,
                    'message' => "Ошибка соединения: $error"
                ];
            }
            
            if ($info['http_code'] != 200) {
                $responseData = json_decode($response, true);
                $errorMessage = isset($responseData['message']) 
                    ? $responseData['message'] 
                    : "HTTP-код: {$info['http_code']}";
                
                $this->logger->error("Ошибка API LinkedIn: $errorMessage");
                return [
                    'success' => false,
                    'message' => "Ошибка API LinkedIn: $errorMessage"
                ];
            }
            
            // Декодируем ответ
            $userData = json_decode($response, true);
            
            if (!isset($userData['id'])) {
                $this->logger->error("Некорректный ответ от API LinkedIn");
                return [
                    'success' => false,
                    'message' => "Некорректный ответ от API LinkedIn"
                ];
            }
            
            $userName = isset($userData['localizedFirstName']) ? $userData['localizedFirstName'] : '';
            $userName .= isset($userData['localizedLastName']) ? ' ' . $userData['localizedLastName'] : '';
            
            $this->logger->info("Аккаунт LinkedIn успешно проверен: $userName");
            return [
                'success' => true,
                'message' => "Аккаунт LinkedIn успешно проверен" . ($userName ? ": $userName" : "")
            ];
        } catch (Exception $e) {
            $this->logger->error("Исключение при проверке LinkedIn: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Проверка аккаунта YouTube
     * 
     * @param array $account Данные аккаунта
     * @param array|null $proxy Данные прокси (опционально)
     * @return array Результат проверки [success => bool, message => string]
     */
    private function verifyYouTubeAccount($account, $proxy = null) {
        // Проверяем наличие необходимых данных
        if (empty($account['api_key'])) {
            return [
                'success' => false,
                'message' => "Отсутствует API ключ для YouTube"
            ];
        }
        
        try {
            // Получаем дополнительные данные
            $additionalData = !empty($account['additional_data']) ? json_decode($account['additional_data'], true) : [];
            
            // URL для проверки API YouTube
            $url = 'https://www.googleapis.com/youtube/v3/channels?part=snippet&mine=true&key=' . urlencode($account['api_key']);
            
            // Инициализируем cURL
            $ch = curl_init();
            
            // Настраиваем cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            // Если есть токен доступа, используем его
            if (!empty($account['access_token'])) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $account['access_token']
                ]);
            }
            
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            // Если есть прокси, настраиваем его
            if ($proxy) {
                $this->proxyManager->setupCurlWithProxy($ch, $proxy);
            }
            
            // Выполняем запрос
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            
            // Закрываем соединение
            curl_close($ch);
            
            // Проверяем результат
            if ($error) {
                $this->logger->error("Ошибка cURL при проверке YouTube: $error");
                return [
                    'success' => false,
                    'message' => "Ошибка соединения: $error"
                ];
            }
            
            if ($info['http_code'] != 200) {
                $responseData = json_decode($response, true);
                $errorMessage = isset($responseData['error']['message']) 
                    ? $responseData['error']['message'] 
                    : "HTTP-код: {$info['http_code']}";
                
                $this->logger->error("Ошибка API YouTube: $errorMessage");
                return [
                    'success' => false,
                    'message' => "Ошибка API YouTube: $errorMessage"
                ];
            }
            
            // Декодируем ответ
            $channelData = json_decode($response, true);
            
            if (!isset($channelData['items']) || empty($channelData['items'])) {
                $this->logger->error("Канал YouTube не найден");
                return [
                    'success' => false,
                    'message' => "Канал YouTube не найден"
                ];
            }
            
            $channelTitle = $channelData['items'][0]['snippet']['title'];
            
            $this->logger->info("Аккаунт YouTube успешно проверен: $channelTitle");
            return [
                'success' => true,
                'message' => "Аккаунт YouTube успешно проверен: $channelTitle"
            ];
        } catch (Exception $e) {
            $this->logger->error("Исключение при проверке YouTube: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Проверка аккаунта Threads
     * 
     * @param array $account Данные аккаунта
     * @param array|null $proxy Данные прокси (опционально)
     * @return array Результат проверки [success => bool, message => string]
     */
    private function verifyThreadsAccount($account, $proxy = null) {
        // Проверяем наличие необходимых данных
        if (empty($account['username']) || empty($account['password'])) {
            return [
                'success' => false,
                'message' => "Отсутствуют учетные данные для Threads"
            ];
        }
        
        try {
            // Для Threads используем простую проверку через запрос к странице профиля
            $url = 'https://www.threads.net/@' . urlencode($account['username']);
            
            // Инициализируем cURL
            $ch = curl_init();
            
            // Настраиваем cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            // Устанавливаем User-Agent
            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            
            // Если есть прокси, настраиваем его
            if ($proxy) {
                $this->proxyManager->setupCurlWithProxy($ch, $proxy);
            }
            
            // Выполняем запрос
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            
            // Закрываем соединение
            curl_close($ch);
            
            // Проверяем результат
            if ($error) {
                $this->logger->error("Ошибка cURL при проверке Threads: $error");
                return [
                    'success' => false,
                    'message' => "Ошибка соединения: $error"
                ];
            }
            
            if ($info['http_code'] != 200) {
                $this->logger->error("Ошибка при проверке Threads: HTTP-код {$info['http_code']}");
                return [
                    'success' => false,
                    'message' => "Ошибка: HTTP-код {$info['http_code']}"
                ];
            }
            
            // Проверяем наличие имени пользователя в ответе
            if (strpos($response, $account['username']) === false) {
                $this->logger->error("Профиль Threads не найден");
                return [
                    'success' => false,
                    'message' => "Профиль Threads не найден"
                ];
            }
            
            $this->logger->info("Аккаунт Threads успешно проверен: @{$account['username']}");
            return [
                'success' => true,
                'message' => "Аккаунт Threads успешно проверен: @{$account['username']}"
            ];
        } catch (Exception $e) {
            $this->logger->error("Исключение при проверке Threads: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка: " . $e->getMessage()
            ];
        }
    }
}
