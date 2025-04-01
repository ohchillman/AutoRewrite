<?php
/**
 * Контроллер для управления прокси
 */
class ProxiesController extends BaseController {
    
    /**
     * Отображение страницы управления прокси
     */
    public function index() {
        // Получаем список прокси из базы данных
        $proxies = $this->getAllProxies();
        
        // Отображаем представление
        $this->render('proxies/index', [
            'title' => 'Прокси - AutoRewrite',
            'pageTitle' => 'Управление прокси',
            'currentPage' => 'proxies',
            'layout' => 'main',
            'proxies' => $proxies
        ]);
    }
    
    /**
     * Добавление нового прокси
     */
    public function add() {
        Logger::info('Начало процесса добавления прокси', 'proxies');
        
        // Проверяем, что это AJAX запрос и устанавливаем заголовок Content-Type
        if ($this->isAjax()) {
            header('Content-Type: application/json');
        }
        
        try {
            // Проверяем, что запрос отправлен методом POST
            Logger::debug('Проверка метода запроса: ' . $_SERVER['REQUEST_METHOD'], 'proxies');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Logger::warning('Попытка доступа не через POST метод', 'proxies');
                
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Метод не поддерживается',
                        'debug' => ['method' => $_SERVER['REQUEST_METHOD']]
                    ]);
                } else {
                    $this->redirect('/proxies');
                }
                return;
            }
            
            // Получаем данные из POST
            Logger::debug('Получение данных из POST запроса', 'proxies');
            $ip = $this->post('ip');
            $port = $this->post('port');
            $username = $this->post('username');
            $password = $this->post('password');
            $protocol = $this->post('protocol');
            $country = $this->post('country');
            
            // Логируем полученные данные
            Logger::debug("Полученные данные: IP=$ip, Port=$port, Protocol=$protocol, Country=$country", 'proxies');
            if (!empty($username)) {
                Logger::debug("Username присутствует", 'proxies');
            }
            if (!empty($password)) {
                Logger::debug("Password присутствует", 'proxies');
            }
            
            // Проверяем обязательные поля
            Logger::debug('Проверка обязательных полей', 'proxies');
            if (empty($ip) || empty($port) || empty($protocol)) {
                $errorMsg = 'Необходимо заполнить поля IP, порт и протокол';
                Logger::warning($errorMsg, 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'ip' => $ip,
                            'port' => $port,
                            'protocol' => $protocol,
                            'stage' => 'validation_required_fields'
                        ]
                    ];
                    Logger::debug('Отправка AJAX ответа: ' . json_encode($response), 'proxies');
                    $this->jsonResponse($response);
                } else {
                    $_SESSION['error'] = $errorMsg;
                    $this->redirect('/proxies');
                }
                return;
            }
            
            // Проверяем, что порт является числом
            Logger::debug('Проверка, что порт является числом', 'proxies');
            if (!is_numeric($port)) {
                $errorMsg = 'Порт должен быть числом';
                Logger::warning($errorMsg . ": $port", 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'port' => $port,
                            'is_numeric' => is_numeric($port),
                            'stage' => 'validation_port_numeric'
                        ]
                    ];
                    Logger::debug('Отправка AJAX ответа: ' . json_encode($response), 'proxies');
                    $this->jsonResponse($response);
                } else {
                    $_SESSION['error'] = $errorMsg;
                    $this->redirect('/proxies');
                }
                return;
            }
            
            // Проверяем, что протокол допустимый
            Logger::debug('Проверка, что протокол допустимый', 'proxies');
            $validProtocols = ['http', 'https', 'socks4', 'socks5'];
            if (!in_array($protocol, $validProtocols)) {
                $errorMsg = 'Недопустимый протокол';
                Logger::warning($errorMsg . ": $protocol", 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'protocol' => $protocol,
                            'valid_protocols' => $validProtocols,
                            'stage' => 'validation_protocol'
                        ]
                    ];
                    Logger::debug('Отправка AJAX ответа: ' . json_encode($response), 'proxies');
                    $this->jsonResponse($response);
                } else {
                    $_SESSION['error'] = $errorMsg;
                    $this->redirect('/proxies');
                }
                return;
            }
            
            // Подготавливаем данные для вставки
            Logger::debug('Подготовка данных для вставки в базу данных', 'proxies');
            $proxyData = [
                'ip' => $ip,
                'port' => (int)$port,
                'username' => $username,
                'password' => $password,
                'protocol' => $protocol,
                'country' => $country,
                'is_active' => 1,
                'status' => 'unchecked'
            ];
            Logger::logVar($proxyData, 'proxyData', 'debug', 'proxies');
            
            // Добавляем прокси в базу данных
            Logger::debug('Попытка добавления прокси в базу данных', 'proxies');
            try {
                $proxyId = $this->db->insert('proxies', $proxyData);
                Logger::debug("Результат вставки: ID=$proxyId", 'proxies');
            } catch (Exception $e) {
                Logger::error('Ошибка при вставке в базу данных: ' . $e->getMessage(), 'proxies');
                throw $e;
            }
            
            // Проверяем результат
            if ($proxyId) {
                $successMsg = 'Прокси успешно добавлен';
                Logger::info($successMsg . " (ID: $proxyId)", 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => true,
                        'message' => $successMsg,
                        'refresh' => true,
                        'debug' => [
                            'proxy_id' => $proxyId,
                            'stage' => 'insert_success'
                        ]
                    ];
                    Logger::debug('Отправка AJAX ответа: ' . json_encode($response), 'proxies');
                    $this->jsonResponse($response);
                } else {
                    $_SESSION['success'] = $successMsg;
                    $this->redirect('/proxies');
                }
            } else {
                $errorMsg = 'Ошибка при добавлении прокси';
                Logger::error($errorMsg, 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'stage' => 'insert_failed',
                            'proxy_data' => $proxyData
                        ]
                    ];
                    Logger::debug('Отправка AJAX ответа: ' . json_encode($response), 'proxies');
                    $this->jsonResponse($response);
                } else {
                    $_SESSION['error'] = $errorMsg;
                    $this->redirect('/proxies');
                }
            }
        } catch (Exception $e) {
            // Обработка исключений
            $errorMsg = 'Ошибка при добавлении прокси: ' . $e->getMessage();
            Logger::error($errorMsg . "\n" . $e->getTraceAsString(), 'proxies');
            
            if ($this->isAjax()) {
                $response = [
                    'success' => false,
                    'message' => $errorMsg,
                    'debug' => [
                        'exception' => [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ],
                        'stage' => 'exception_caught'
                    ]
                ];
                Logger::debug('Отправка AJAX ответа с ошибкой: ' . json_encode($response), 'proxies');
                $this->jsonResponse($response);
            } else {
                $_SESSION['error'] = $errorMsg;
                $this->redirect('/proxies');
            }
        }
    }
    
    /**
     * Удаление прокси
     * 
     * @param int $id ID прокси
     */
    public function delete($id) {
        Logger::info("Начало процесса удаления прокси (ID: $id)", 'proxies');
        
        // Проверяем, что это AJAX запрос и устанавливаем заголовок Content-Type
        if ($this->isAjax()) {
            header('Content-Type: application/json');
        }
        
        try {
            // Проверяем, что запрос отправлен методом POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Logger::warning("Попытка удаления прокси (ID: $id) не через POST метод", 'proxies');
                
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Метод не поддерживается'
                    ]);
                } else {
                    $this->redirect('/proxies');
                }
                return;
            }
            
            // Удаляем прокси из базы данных
            Logger::debug("Попытка удаления прокси (ID: $id) из базы данных", 'proxies');
            $result = $this->db->delete('proxies', ['id' => $id]);
            
            // Проверяем результат
            if ($result) {
                $successMsg = 'Прокси успешно удален';
                Logger::info($successMsg . " (ID: $id)", 'proxies');
                
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => $successMsg
                    ]);
                } else {
                    $_SESSION['success'] = $successMsg;
                    $this->redirect('/proxies');
                }
            } else {
                $errorMsg = 'Ошибка при удалении прокси';
                Logger::error($errorMsg . " (ID: $id)", 'proxies');
                
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => $errorMsg
                    ]);
                } else {
                    $_SESSION['error'] = $errorMsg;
                    $this->redirect('/proxies');
                }
            }
        } catch (Exception $e) {
            // Обработка исключений
            $errorMsg = 'Ошибка при удалении прокси: ' . $e->getMessage();
            Logger::error($errorMsg . "\n" . $e->getTraceAsString(), 'proxies');
            
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $errorMsg
                ]);
            } else {
                $_SESSION['error'] = $errorMsg;
                $this->redirect('/proxies');
            }
        }
    }
    
    /**
     * Изменение статуса активности прокси
     * 
     * @param int $id ID прокси
     */
    public function toggle($id) {
        Logger::info("Начало процесса изменения статуса прокси (ID: $id)", 'proxies');
        
        try {
            // Получаем текущий статус прокси
            Logger::debug("Получение текущего статуса прокси (ID: $id)", 'proxies');
            $proxy = $this->db->get('proxies', ['id' => $id]);
            
            if (!$proxy) {
                $errorMsg = 'Прокси не найден';
                Logger::warning($errorMsg . " (ID: $id)", 'proxies');
                $_SESSION['error'] = $errorMsg;
                $this->redirect('/proxies');
                return;
            }
            
            // Инвертируем статус
            $newStatus = $proxy['is_active'] ? 0 : 1;
            $statusText = $newStatus ? 'активирован' : 'деактивирован';
            
            // Обновляем статус в базе данных
            Logger::debug("Обновление статуса прокси (ID: $id) на $statusText", 'proxies');
            $result = $this->db->update('proxies', ['is_active' => $newStatus], ['id' => $id]);
            
            // Проверяем результат
            if ($result) {
                $successMsg = 'Прокси успешно ' . $statusText;
                Logger::info($successMsg . " (ID: $id)", 'proxies');
                $_SESSION['success'] = $successMsg;
            } else {
                $errorMsg = 'Ошибка при изменении статуса прокси';
                Logger::error($errorMsg . " (ID: $id)", 'proxies');
                $_SESSION['error'] = $errorMsg;
            }
        } catch (Exception $e) {
            // Обработка исключений
            $errorMsg = 'Ошибка при изменении статуса прокси: ' . $e->getMessage();
            Logger::error($errorMsg . "\n" . $e->getTraceAsString(), 'proxies');
            $_SESSION['error'] = $errorMsg;
        }
        
        // Перенаправляем на страницу прокси
        $this->redirect('/proxies');
    }
    
    /**
     * Проверка прокси
     * 
     * @param int $id ID прокси
     */
    public function check($id) {
        Logger::info("Начало процесса проверки прокси (ID: $id)", 'proxies');
        
        // Проверяем, что это AJAX запрос и устанавливаем заголовок Content-Type
        if ($this->isAjax()) {
            header('Content-Type: application/json');
        }
        
        try {
            // Проверяем, что запрос отправлен методом POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Logger::warning("Попытка проверки прокси (ID: $id) не через POST метод", 'proxies');
                
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Метод не поддерживается'
                    ]);
                } else {
                    $this->redirect('/proxies');
                }
                return;
            }
            
            // Получаем информацию о прокси
            Logger::debug("Получение информации о прокси (ID: $id)", 'proxies');
            $proxy = $this->db->get('proxies', ['id' => $id]);
            
            if (!$proxy) {
                $errorMsg = 'Прокси не найден';
                Logger::warning($errorMsg . " (ID: $id)", 'proxies');
                
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => $errorMsg
                    ]);
                } else {
                    $_SESSION['error'] = $errorMsg;
                    $this->redirect('/proxies');
                }
                return;
            }
            
            // Проверяем прокси
            Logger::debug("Проверка прокси (ID: $id)", 'proxies');
            $proxyString = $proxy['ip'] . ':' . $proxy['port'];
            if (!empty($proxy['username']) && !empty($proxy['password'])) {
                $proxyString = $proxy['username'] . ':' . $proxy['password'] . '@' . $proxyString;
            }
            
            // Имитация проверки прокси (в реальном приложении здесь будет реальная проверка)
            $isWorking = $this->checkProxyConnection($proxyString, $proxy['protocol']);
            
            // Обновляем статус прокси в базе данных
            Logger::debug("Обновление статуса прокси (ID: $id) на " . ($isWorking ? 'working' : 'failed'), 'proxies');
            $this->db->update('proxies', [
                'status' => $isWorking ? 'working' : 'failed',
                'last_check' => date('Y-m-d H:i:s')
            ], ['id' => $id]);
            
            // Формируем ответ
            $message = $isWorking ? 'Прокси работает' : 'Прокси не работает';
            Logger::info($message . " (ID: $id)", 'proxies');
            
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => $isWorking,
                    'message' => $message
                ]);
            } else {
                $_SESSION[$isWorking ? 'success' : 'error'] = $message;
                $this->redirect('/proxies');
            }
        } catch (Exception $e) {
            // Обработка исключений
            $errorMsg = 'Ошибка при проверке прокси: ' . $e->getMessage();
            Logger::error($errorMsg . "\n" . $e->getTraceAsString(), 'proxies');
            
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $errorMsg
                ]);
            } else {
                $_SESSION['error'] = $errorMsg;
                $this->redirect('/proxies');
            }
        }
    }
    
    /**
     * Получение списка всех прокси из базы данных
     * 
     * @return array Список прокси
     */
    private function getAllProxies() {
        Logger::debug('Получение списка всех прокси из базы данных', 'proxies');
        try {
            $proxies = $this->db->getAll('proxies', [], 'id DESC');
            Logger::debug('Получено ' . count($proxies) . ' прокси', 'proxies');
            return $proxies;
        } catch (Exception $e) {
            Logger::error('Ошибка при получении списка прокси: ' . $e->getMessage(), 'proxies');
            return [];
        }
    }
    
    /**
     * Проверка соединения через прокси
     * 
     * @param string $proxyString Строка прокси (ip:port или username:password@ip:port)
     * @param string $protocol Протокол прокси (http, https, socks4, socks5)
     * @return bool Результат проверки
     */
    private function checkProxyConnection($proxyString, $protocol) {
        Logger::debug("Проверка соединения через прокси: $proxyString ($protocol)", 'proxies');
        
        // В реальном приложении здесь будет реальная проверка прокси
        // Для демонстрации просто возвращаем случайный результат
        $result = (rand(0, 1) == 1);
        
        Logger::debug("Результат проверки прокси: " . ($result ? 'успешно' : 'неуспешно'), 'proxies');
        return $result;
    }
}
