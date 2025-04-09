<?php
/**
 * SocialMediaClientFactory - класс для создания клиентов социальных сетей с поддержкой прокси
 * 
 * Отвечает за:
 * - Создание клиентов для различных социальных платформ
 * - Интеграцию прокси с API-клиентами
 * - Унифицированный интерфейс для работы с разными платформами
 */
class SocialMediaClientFactory {
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
        $this->logger = $logger ?: new Logger('social_media_client_factory');
    }
    
    /**
     * Создание клиента для социальной сети на основе аккаунта
     * 
     * @param int|array $account ID аккаунта или массив с данными аккаунта
     * @return object|null Клиент для работы с API социальной сети или null в случае ошибки
     */
    public function createClientForAccount($account) {
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
                    return null;
                }
            } else {
                $accountData = $account;
            }
            
            // Получаем прокси для аккаунта
            $proxy = $this->proxyManager->getProxyForAccount($accountData);
            
            // Создаем клиент в зависимости от типа аккаунта
            $accountType = strtolower($accountData['account_type_name']);
            
            switch ($accountType) {
                case 'twitter':
                    return $this->createTwitterClient($accountData, $proxy);
                    
                case 'linkedin':
                    return $this->createLinkedInClient($accountData, $proxy);
                    
                case 'youtube':
                    return $this->createYouTubeClient($accountData, $proxy);
                    
                case 'threads':
                    return $this->createThreadsClient($accountData, $proxy);
                    
                default:
                    $this->logger->warning("Неизвестный тип аккаунта: $accountType");
                    return null;
            }
        } catch (Exception $e) {
            $this->logger->error("Ошибка при создании клиента: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создание клиента для Twitter
     * 
     * @param array $account Данные аккаунта
     * @param array|null $proxy Данные прокси (опционально)
     * @return TwitterApiClient|null Клиент для работы с API Twitter или null в случае ошибки
     */
    private function createTwitterClient($account, $proxy = null) {
        // Проверяем наличие необходимых данных
        if (empty($account['api_key']) || empty($account['api_secret']) || 
            empty($account['access_token']) || empty($account['access_token_secret'])) {
            $this->logger->error("Отсутствуют необходимые данные API для Twitter");
            return null;
        }
        
        try {
            // Создаем клиент Twitter
            require_once __DIR__ . '/../clients/TwitterApiClient.php';
            
            $client = new TwitterApiClient(
                $account['api_key'],
                $account['api_secret'],
                $account['access_token'],
                $account['access_token_secret']
            );
            
            // Устанавливаем прокси, если он указан
            if ($proxy) {
                $client->setProxy($proxy);
            }
            
            $this->logger->debug("Создан клиент Twitter для аккаунта {$account['name']}");
            return $client;
        } catch (Exception $e) {
            $this->logger->error("Ошибка при создании клиента Twitter: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создание клиента для LinkedIn
     * 
     * @param array $account Данные аккаунта
     * @param array|null $proxy Данные прокси (опционально)
     * @return LinkedInApiClient|null Клиент для работы с API LinkedIn или null в случае ошибки
     */
    private function createLinkedInClient($account, $proxy = null) {
        // Проверяем наличие необходимых данных
        if (empty($account['access_token'])) {
            $this->logger->error("Отсутствует токен доступа для LinkedIn");
            return null;
        }
        
        try {
            // Создаем клиент LinkedIn
            require_once __DIR__ . '/../clients/LinkedInApiClient.php';
            
            // Получаем настройки приложения из базы данных
            $settings = $this->getSettings();
            
            $client = new LinkedInApiClient(
                $settings['linkedin_client_id'] ?? '',
                $settings['linkedin_client_secret'] ?? '',
                $account['access_token']
            );
            
            // Устанавливаем refresh token, если он указан
            if (!empty($account['refresh_token'])) {
                $client->setRefreshToken($account['refresh_token']);
            }
            
            // Устанавливаем прокси, если он указан
            if ($proxy) {
                $client->setProxy($proxy);
            }
            
            $this->logger->debug("Создан клиент LinkedIn для аккаунта {$account['name']}");
            return $client;
        } catch (Exception $e) {
            $this->logger->error("Ошибка при создании клиента LinkedIn: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создание клиента для YouTube
     * 
     * @param array $account Данные аккаунта
     * @param array|null $proxy Данные прокси (опционально)
     * @return YouTubeApiClient|null Клиент для работы с API YouTube или null в случае ошибки
     */
    private function createYouTubeClient($account, $proxy = null) {
        // Проверяем наличие необходимых данных
        if (empty($account['api_key'])) {
            $this->logger->error("Отсутствует API ключ для YouTube");
            return null;
        }
        
        try {
            // Создаем клиент YouTube
            require_once __DIR__ . '/../clients/YouTubeApiClient.php';
            
            // Получаем дополнительные данные
            $additionalData = !empty($account['additional_data']) ? json_decode($account['additional_data'], true) : [];
            
            $client = new YouTubeApiClient(
                $account['api_key'],
                $additionalData['client_id'] ?? '',
                $additionalData['client_secret'] ?? ''
            );
            
            // Устанавливаем токены, если они указаны
            if (!empty($account['access_token'])) {
                $client->setAccessToken($account['access_token']);
            }
            
            if (!empty($account['refresh_token'])) {
                $client->setRefreshToken($account['refresh_token']);
            }
            
            // Устанавливаем прокси, если он указан
            if ($proxy) {
                $client->setProxy($proxy);
            }
            
            $this->logger->debug("Создан клиент YouTube для аккаунта {$account['name']}");
            return $client;
        } catch (Exception $e) {
            $this->logger->error("Ошибка при создании клиента YouTube: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создание клиента для Threads
     * 
     * @param array $account Данные аккаунта
     * @param array|null $proxy Данные прокси (опционально)
     * @return ThreadsClient|null Клиент для работы с Threads или null в случае ошибки
     */
    private function createThreadsClient($account, $proxy = null) {
        // Проверяем наличие необходимых данных
        if (empty($account['username']) || empty($account['password'])) {
            $this->logger->error("Отсутствуют учетные данные для Threads");
            return null;
        }
        
        try {
            // Создаем клиент Threads
            require_once __DIR__ . '/../clients/ThreadsSeleniumClient.php';
            
            // Получаем дополнительные данные
            $additionalData = !empty($account['additional_data']) ? json_decode($account['additional_data'], true) : [];
            
            $client = new ThreadsSeleniumClient(
                $account['username'],
                $account['password']
            );
            
            // Устанавливаем User-Agent, если он указан
            if (!empty($additionalData['user_agent'])) {
                $client->setUserAgent($additionalData['user_agent']);
            }
            
            // Устанавливаем прокси, если он указан
            if ($proxy) {
                $client->setProxy($proxy);
            }
            
            $this->logger->debug("Создан клиент Threads для аккаунта {$account['name']}");
            return $client;
        } catch (Exception $e) {
            $this->logger->error("Ошибка при создании клиента Threads: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Получение настроек приложения из базы данных
     * 
     * @return array Массив настроек
     */
    private function getSettings() {
        try {
            $settings = [];
            $settingsData = $this->db->fetchAll("SELECT * FROM settings");
            
            foreach ($settingsData as $setting) {
                $settings[$setting['name']] = $setting['value'];
            }
            
            return $settings;
        } catch (Exception $e) {
            $this->logger->error("Ошибка при получении настроек: " . $e->getMessage());
            return [];
        }
    }
}
