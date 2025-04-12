<?php
/**
 * ProxyManager - класс для управления прокси и их интеграции с аккаунтами
 * 
 * Отвечает за:
 * - Получение прокси для конкретного аккаунта
 * - Проверку работоспособности прокси
 * - Настройку cURL с использованием прокси
 */
class ProxyManager {
    /**
     * @var Database Экземпляр класса для работы с базой данных
     */
    private $db;
    
    /**
     * @var Logger Экземпляр класса для логирования
     */
    private $logger;
    
    /**
     * Конструктор класса
     * 
     * @param Database $db Экземпляр класса для работы с базой данных
     * @param Logger $logger Экземпляр класса для логирования (опционально)
     */
    public function __construct($db, $logger = null) {
        $this->db = $db;
        $this->logger = $logger ?: new Logger('proxy_manager');
    }
    
    /**
     * Получение прокси для указанного аккаунта
     * 
     * @param int|array $account ID аккаунта или массив с данными аккаунта
     * @return array|null Данные прокси или null, если прокси не найден или не активен
     */
    public function getProxyForAccount($account) {
        try {
            // Если передан ID аккаунта, получаем данные аккаунта
            if (is_numeric($account)) {
                $accountData = $this->db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$account]);
                if (!$accountData) {
                    $this->logger->error("Аккаунт с ID $account не найден");
                    return null;
                }
            } else {
                $accountData = $account;
            }
            
            // Проверяем, есть ли у аккаунта привязанный прокси
            if (empty($accountData['proxy_id'])) {
                $this->logger->debug("У аккаунта {$accountData['name']} (ID: {$accountData['id']}) нет привязанного прокси");
                return null;
            }
            
            // Получаем данные прокси
            $proxy = $this->db->fetchOne("
                SELECT * FROM proxies 
                WHERE id = ? AND is_active = 1
            ", [$accountData['proxy_id']]);
            
            if (!$proxy) {
                $this->logger->warning("Прокси с ID {$accountData['proxy_id']} не найден или не активен для аккаунта {$accountData['name']} (ID: {$accountData['id']})");
                return null;
            }
            
            $this->logger->debug("Получен прокси {$proxy['ip']}:{$proxy['port']} для аккаунта {$accountData['name']} (ID: {$accountData['id']})");
            return $proxy;
        } catch (Exception $e) {
            $this->logger->error("Ошибка при получении прокси для аккаунта: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Настройка cURL с использованием прокси
     * 
     * @param resource $ch Ресурс cURL
     * @param array $proxy Данные прокси
     * @return bool Успешность настройки
     */
    public function setupCurlWithProxy($ch, $proxy) {
        if (!$ch || !$proxy) {
            return false;
        }
        
        try {
            // Формируем строку прокси для cURL
            $proxyString = "{$proxy['protocol']}://{$proxy['ip']}:{$proxy['port']}";
            
            // Настраиваем cURL
            curl_setopt($ch, CURLOPT_PROXY, $proxyString);
            
            // Если есть аутентификация, добавляем ее
            if (!empty($proxy['username']) && !empty($proxy['password'])) {
                $proxyAuth = "{$proxy['username']}:{$proxy['password']}";
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
            }
            
            $this->logger->debug("cURL настроен с использованием прокси: $proxyString");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Ошибка при настройке cURL с прокси: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка работоспособности прокси
     * 
     * @param int|array $proxy ID прокси или массив с данными прокси
     * @return array Результат проверки [success => bool, details => string]
     */
    public function checkProxy($proxy) {
        try {
            // Если передан ID прокси, получаем данные прокси
            if (is_numeric($proxy)) {
                $proxyData = $this->db->fetchOne("SELECT * FROM proxies WHERE id = ?", [$proxy]);
                if (!$proxyData) {
                    $this->logger->error("Прокси с ID $proxy не найден");
                    return [
                        'success' => false,
                        'details' => "Прокси не найден"
                    ];
                }
            } else {
                $proxyData = $proxy;
            }
            
            $this->logger->debug("Проверка соединения через прокси: {$proxyData['ip']}:{$proxyData['port']} ({$proxyData['protocol']})");
            
            // Формируем строку прокси для cURL
            $proxyString = "{$proxyData['protocol']}://{$proxyData['ip']}:{$proxyData['port']}";
            if (!empty($proxyData['username']) && !empty($proxyData['password'])) {
                $proxyAuth = "{$proxyData['username']}:{$proxyData['password']}";
            } else {
                $proxyAuth = null;
            }
            
            // URL для проверки - сервис, который возвращает IP-адрес
            $checkUrl = 'https://api.ipify.org';
            
            // Инициализируем cURL
            $ch = curl_init();
            
            // Настраиваем cURL
            curl_setopt($ch, CURLOPT_URL, $checkUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Таймаут 10 секунд
            curl_setopt($ch, CURLOPT_PROXY, $proxyString);
            
            // Если есть аутентификация, добавляем ее
            if ($proxyAuth) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
            }
            
            // Опция для избегания проблем с SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            // Выполняем запрос
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            
            // Закрываем соединение
            curl_close($ch);
            
            // Проверяем результат
            if ($error) {
                $this->logger->debug("Ошибка cURL при проверке прокси: {$error}");
                return [
                    'success' => false,
                    'details' => "Ошибка соединения: {$error}"
                ];
            }
            
            if ($info['http_code'] != 200) {
                $this->logger->debug("Неуспешный HTTP-код при проверке прокси: {$info['http_code']}");
                return [
                    'success' => false,
                    'details' => "HTTP-код: {$info['http_code']}"
                ];
            }
            
            // Проверяем полученный IP (должен отличаться от локального)
            if (!$response || !filter_var($response, FILTER_VALIDATE_IP)) {
                $this->logger->debug("Некорректный IP в ответе: {$response}");
                return [
                    'success' => false,
                    'details' => "Некорректный ответ от сервера"
                ];
            }
            
            $this->logger->debug("Прокси работает, внешний IP: {$response}");
            return [
                'success' => true,
                'details' => $response
            ];
        } catch (Exception $e) {
            $this->logger->error("Исключение при проверке прокси: " . $e->getMessage());
            return [
                'success' => false,
                'details' => "Ошибка: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Обновление статуса прокси в базе данных
     * 
     * @param int $proxyId ID прокси
     * @param bool $isWorking Статус работоспособности
     * @param string $details Детали проверки
     * @return bool Успешность обновления
     */
    public function updateProxyStatus($proxyId, $isWorking, $details = '') {
        try {
            $status = $isWorking ? 'working' : 'failed';
            $result = $this->db->update('proxies', [
                'status' => $status,
                'last_check' => date('Y-m-d H:i:s')
                // Удалено поле external_ip, которого нет в базе данных
            ], 'id = ?', [$proxyId]);
            
            $this->logger->debug("Статус прокси ID $proxyId обновлен: $status");
            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error("Ошибка при обновлении статуса прокси: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение всех активных прокси
     * 
     * @return array Массив активных прокси
     */
    public function getAllActiveProxies() {
        try {
            $proxies = $this->db->fetchAll("
                SELECT * FROM proxies 
                WHERE is_active = 1
                ORDER BY status = 'working' DESC, ip ASC
            ");
            
            $this->logger->debug("Получено " . count($proxies) . " активных прокси");
            return $proxies;
        } catch (Exception $e) {
            $this->logger->error("Ошибка при получении списка активных прокси: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение рабочего прокси для указанного типа аккаунта
     * 
     * @param string $accountType Тип аккаунта
     * @return array|null Данные прокси или null, если подходящий прокси не найден
     */
    public function getWorkingProxyForAccountType($accountType) {
        try {
            // Получаем все активные и рабочие прокси
            $proxies = $this->db->fetchAll("
                SELECT * FROM proxies 
                WHERE is_active = 1 AND status = 'working'
                ORDER BY RAND()
                LIMIT 10
            ");
            
            if (empty($proxies)) {
                $this->logger->warning("Не найдено рабочих прокси для типа аккаунта: $accountType");
                return null;
            }
            
            // Для будущего расширения: можно добавить логику выбора прокси в зависимости от типа аккаунта
            // Например, для некоторых платформ могут требоваться прокси из определенных стран
            
            // Пока просто возвращаем первый рабочий прокси
            $this->logger->debug("Выбран прокси {$proxies[0]['ip']}:{$proxies[0]['port']} для типа аккаунта: $accountType");
            return $proxies[0];
        } catch (Exception $e) {
            $this->logger->error("Ошибка при получении рабочего прокси для типа аккаунта $accountType: " . $e->getMessage());
            return null;
        }
    }
}