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
     * Тестирование прокси
     * 
     * @param int $id ID прокси для тестирования
     * @return void
     */
    public function test($id = null) {
        Logger::info("Начало процесса тестирования прокси" . ($id ? " (ID: $id)" : ""), 'proxies');
        
        try {
            // Проверяем, что запрос отправлен методом POST или GET
            if (!$this->isMethod('POST') && !$this->isMethod('GET')) {
                Logger::warning("Попытка тестирования прокси не через POST или GET метод", 'proxies');
                return $this->handleAjaxError('Метод не поддерживается', 405);
            }
            
            // Инициализируем менеджер прокси
            require_once __DIR__ . '/../utils/proxy/ProxyManager.php';
            $proxyManager = new ProxyManager($this->db, new Logger('proxies'));
            
            // Если указан ID, тестируем конкретный прокси
            if ($id) {
                Logger::debug("Тестирование прокси с ID: $id", 'proxies');
                
                // Получаем данные прокси
                $proxy = $this->db->fetchOne("SELECT * FROM proxies WHERE id = ?", [$id]);
                
                if (!$proxy) {
                    $errorMsg = 'Прокси не найден';
                    Logger::warning($errorMsg . " (ID: $id)", 'proxies');
                    return $this->handleAjaxError($errorMsg, 404);
                }
                
                // Тестируем прокси
                $result = $proxyManager->checkProxy($proxy);
                
                // Обновляем статус прокси в базе данных
                $proxyManager->updateProxyStatus($id, $result['success'], $result['details']);
                
                // Возвращаем результат
                if ($result['success']) {
                    Logger::info("Прокси успешно протестирован: {$proxy['ip']}:{$proxy['port']}", 'proxies');
                    return $this->jsonResponse([
                        'success' => true,
                        'message' => 'Прокси работает: ' . $result['details'],
                        'status' => 'working'
                    ]);
                } else {
                    Logger::warning("Прокси не работает: {$proxy['ip']}:{$proxy['port']} - {$result['details']}", 'proxies');
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Прокси не работает: ' . $result['details'],
                        'status' => 'failed'
                    ]);
                }
            } else {
                // Если ID не указан, тестируем все активные прокси
                Logger::debug("Тестирование всех активных прокси", 'proxies');
                
                // Получаем все активные прокси
                $proxies = $proxyManager->getAllActiveProxies();
                
                if (empty($proxies)) {
                    Logger::warning("Активные прокси не найдены", 'proxies');
                    return $this->handleAjaxError('Активные прокси не найдены');
                }
                
                // Тестируем каждый прокси
                $results = [];
                foreach ($proxies as $proxy) {
                    // Тестируем прокси
                    $result = $proxyManager->checkProxy($proxy);
                    
                    // Обновляем статус прокси в базе данных
                    $proxyManager->updateProxyStatus($proxy['id'], $result['success'], $result['details']);
                    
                    // Добавляем результат в массив
                    $results[$proxy['id']] = [
                        'success' => $result['success'],
                        'message' => $result['success'] ? 'Прокси работает: ' . $result['details'] : 'Прокси не работает: ' . $result['details'],
                        'status' => $result['success'] ? 'working' : 'failed'
                    ];
                    
                    // Логируем результат
                    if ($result['success']) {
                        Logger::info("Прокси успешно протестирован: {$proxy['ip']}:{$proxy['port']}", 'proxies');
                    } else {
                        Logger::warning("Прокси не работает: {$proxy['ip']}:{$proxy['port']} - {$result['details']}", 'proxies');
                    }
                }
                
                // Возвращаем результаты
                return $this->jsonResponse([
                    'success' => true,
                    'results' => $results
                ]);
            }
        } catch (Exception $e) {
            // Обработка исключений
            $errorMsg = 'Ошибка при тестировании прокси: ' . $e->getMessage();
            Logger::error($errorMsg . "\n" . $e->getTraceAsString(), 'proxies');
            return $this->handleAjaxError($errorMsg, 500);
        }
    }
    
    /**
     * Добавление нового прокси
     */
    public function add() {
        Logger::info('Начало процесса добавления прокси', 'proxies');
        
        try {
            // Проверяем, что запрос отправлен методом POST
            Logger::debug('Проверка метода запроса: ' . $_SERVER['REQUEST_METHOD'], 'proxies');
            if (!$this->isMethod('POST')) {
                Logger::warning('Попытка доступа не через POST метод', 'proxies');
                return $this->handleAjaxError('Метод не поддерживается', 405);
            }
            
            // Получаем данные из POST
            Logger::debug('Получение данных из POST запроса', 'proxies');
            $name = $this->post('name');
            $ip = $this->post('ip');
            $port = $this->post('port');
            $username = $this->post('username');
            $password = $this->post('password');
            $protocol = $this->post('protocol');
            $country = $this->post('country');
            $ip_change_url = $this->post('ip_change_url');
            
            // Проверяем обязательные поля
            Logger::debug('Проверка обязательных полей', 'proxies');
            if (empty($name) || empty($ip) || empty($port) || empty($protocol)) {
                $errorMsg = 'Необходимо заполнить поля Название, IP, порт и протокол';
                Logger::warning($errorMsg, 'proxies');
                return $this->handleAjaxError($errorMsg);
            }
            
            // Проверяем, что порт является числом
            Logger::debug('Проверка, что порт является числом', 'proxies');
            if (!is_numeric($port)) {
                $errorMsg = 'Порт должен быть числом';
                Logger::warning($errorMsg . ": $port", 'proxies');
                return $this->handleAjaxError($errorMsg);
            }
            
            // Проверяем, что протокол допустимый
            Logger::debug('Проверка, что протокол допустимый', 'proxies');
            $validProtocols = ['http', 'https', 'socks4', 'socks5'];
            if (!in_array($protocol, $validProtocols)) {
                $errorMsg = 'Недопустимый протокол';
                Logger::warning($errorMsg . ": $protocol", 'proxies');
                return $this->handleAjaxError($errorMsg);
            }
            
            // Подготавливаем данные для вставки
            Logger::debug('Подготовка данных для вставки в базу данных', 'proxies');
            $proxyData = [
                'name' => $name,
                'ip' => $ip,
                'port' => (int)$port,
                'username' => $username,
                'password' => $password,
                'protocol' => $protocol,
                'country' => $country,
                'ip_change_url' => $ip_change_url,
                'is_active' => 1,
                'status' => 'unchecked'
            ];
            
            // Добавляем прокси в базу данных
            $proxyId = $this->db->insert('proxies', $proxyData);
            
            // Проверяем результат
            if ($proxyId) {
                $successMsg = 'Прокси успешно добавлен';
                Logger::info($successMsg . " (ID: $proxyId)", 'proxies');
                return $this->handleSuccess($successMsg, null, true);
            } else {
                $errorMsg = 'Ошибка при добавлении прокси';
                Logger::error($errorMsg, 'proxies');
                return $this->handleAjaxError($errorMsg);
            }
        } catch (Exception $e) {
            // Обработка исключений
            $errorMsg = 'Ошибка при добавлении прокси: ' . $e->getMessage();
            Logger::error($errorMsg . "\n" . $e->getTraceAsString(), 'proxies');
            return $this->handleAjaxError($errorMsg, 500);
        }
    }

    /**
     * Смена IP прокси
     * 
     * @param int $id ID прокси
     */
    public function changeIp($id) {
        Logger::info("Начало процесса смены IP для прокси (ID: $id)", 'proxies');
        
        try {
            // Проверяем, что запрос отправлен методом POST
            if (!$this->isMethod('POST') && !$this->isMethod('GET')) {
                Logger::warning("Попытка смены IP прокси (ID: $id) не через POST или GET метод", 'proxies');
                return $this->handleAjaxError('Метод не поддерживается', 405);
            }
            
            // Получаем информацию о прокси
            Logger::debug("Получение информации о прокси (ID: $id)", 'proxies');
            $proxy = $this->db->get('proxies', ['id' => $id]);
            
            if (!$proxy) {
                $errorMsg = 'Прокси не найден';
                Logger::warning($errorMsg . " (ID: $id)", 'proxies');
                return $this->handleAjaxError($errorMsg, 404);
            }
            
            // Проверяем наличие URL для смены IP
            if (empty($proxy['ip_change_url'])) {
                $errorMsg = 'URL для смены IP не указан';
                Logger::warning($errorMsg . " (ID: $id)", 'proxies');
                return $this->handleAjaxError($errorMsg, 400);
            }
            
            // Отправляем запрос на смену IP
            Logger::debug("Отправка запроса на смену IP для прокси (ID: $id)", 'proxies');
            $changeResult = $this->sendIpChangeRequest($proxy['ip_change_url']);
            
            if ($changeResult['success']) {
                // Обновляем статус прокси в базе данных
                $this->db->update('proxies', [
                    'status' => 'unchecked', // Сбрасываем статус, так как IP изменился
                    'last_check' => null
                ], ['id' => $id]);
                
                $message = 'IP прокси успешно изменен: ' . $changeResult['details'];
                Logger::info($message . " (ID: $id)", 'proxies');
                
                if ($this->isAjax()) {
                    return $this->jsonResponse([
                        'success' => true,
                        'message' => $message
                    ]);
                } else {
                    $_SESSION['success'] = $message;
                    $this->redirect('/proxies');
                    return;
                }
            } else {
                $errorMsg = 'Не удалось изменить IP: ' . $changeResult['details'];
                Logger::warning($errorMsg . " (ID: $id)", 'proxies');
                
                if ($this->isAjax()) {
                    return $this->handleAjaxError($errorMsg);
                } else {
                    $_SESSION['error'] = $errorMsg;
                    $this->redirect('/proxies');
                    return;
                }
            }
        } catch (Exception $e) {
            // Обработка исключений
            $errorMsg = 'Ошибка при смене IP: ' . $e->getMessage();
            Logger::error($errorMsg . "\n" . $e->getTraceAsString(), 'proxies');
            return $this->handleAjaxError($errorMsg, 500);
        }
    }

    /**
     * Редактирование прокси
     * 
     * @param int $id ID прокси
     */
    public function edit($id = null) {
        // Проверяем ID
        if (empty($id)) {
            $this->redirect('/proxies');
            return;
        }
        
        // Если POST запрос, обрабатываем форму
        if ($this->isMethod('POST')) {
            try {
                // Получаем данные из POST
                $name = $this->post('name');
                $ip = $this->post('ip');
                $port = $this->post('port');
                $username = $this->post('username');
                $password = $this->post('password');
                $protocol = $this->post('protocol');
                $country = $this->post('country');
                $ip_change_url = $this->post('ip_change_url');
                
                // Проверяем обязательные поля
                if (empty($name) || empty($ip) || empty($port) || empty($protocol)) {
                    return $this->handleAjaxError('Необходимо заполнить поля Название, IP, порт и протокол');
                }
                
                // Проверяем, что порт является числом
                if (!is_numeric($port)) {
                    return $this->handleAjaxError('Порт должен быть числом');
                }
                
                // Проверяем, что протокол допустимый
                $validProtocols = ['http', 'https', 'socks4', 'socks5'];
                if (!in_array($protocol, $validProtocols)) {
                    return $this->handleAjaxError('Недопустимый протокол');
                }
                
                // Подготавливаем данные для обновления
                $proxyData = [
                    'name' => $name,
                    'ip' => $ip,
                    'port' => (int)$port,
                    'username' => $username,
                    'protocol' => $protocol,
                    'country' => $country,
                    'ip_change_url' => $ip_change_url
                ];
                
                // Если пароль не пустой, обновляем его
                if (!empty($password)) {
                    $proxyData['password'] = $password;
                }
                
                // Обновляем прокси в базе данных
                $result = $this->db->update('proxies', $proxyData, 'id = ?', [$id]);
                
                // Проверяем результат
                if ($result !== false) {
                    if ($this->isAjax()) {
                        return $this->jsonResponse([
                            'success' => true,
                            'message' => 'Прокси успешно обновлен',
                            'redirect' => '/proxies'
                        ]);
                    } else {
                        $_SESSION['success'] = 'Прокси успешно обновлен';
                        $this->redirect('/proxies');
                    }
                } else {
                    return $this->handleAjaxError('Ошибка при обновлении прокси');
                }
            } catch (Exception $e) {
                Logger::error('Ошибка при обновлении прокси: ' . $e->getMessage(), 'proxies');
                return $this->handleAjaxError('Ошибка при обновлении прокси: ' . $e->getMessage(), 500);
            }
        }
        
        // Получаем данные прокси
        $proxy = $this->db->fetchOne("SELECT * FROM proxies WHERE id = ?", [$id]);
        
        if (!$proxy) {
            $_SESSION['error'] = 'Прокси не найден';
            $this->redirect('/proxies');
            return;
        }
        
        // Отображаем представление
        $this->render('proxies/edit', [
            'title' => 'Редактирование прокси - AutoRewrite',
            'pageTitle' => 'Редактирование прокси',
            'currentPage' => 'proxies',
            'layout' => 'main',
            'proxy' => $proxy
        ]);
    }

    /**
     * Отправка запроса на смену IP прокси
     * 
     * @param string $url URL для запроса смены IP
     * @return array Результат запроса [success => bool, details => string]
     */
    private function sendIpChangeRequest($url) {
        Logger::debug("Отправка запроса на смену IP на URL: $url", 'proxies');
        
        try {
            // Инициализируем cURL
            $ch = curl_init();
            
            // Настраиваем cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Таймаут 30 секунд
            
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
                Logger::debug("Ошибка cURL при смене IP: {$error}", 'proxies');
                return [
                    'success' => false,
                    'details' => "Ошибка соединения: {$error}"
                ];
            }
            
            // Проверяем код ответа
            if ($info['http_code'] >= 200 && $info['http_code'] < 300) {
                Logger::debug("Успешный ответ от сервиса смены IP: {$info['http_code']}", 'proxies');
                return [
                    'success' => true,
                    'details' => "HTTP-код: {$info['http_code']}, ответ: " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : '')
                ];
            } else {
                Logger::debug("Неуспешный HTTP-код при смене IP: {$info['http_code']}", 'proxies');
                return [
                    'success' => false,
                    'details' => "HTTP-код: {$info['http_code']}, ответ: " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : '')
                ];
            }
        } catch (Exception $e) {
            Logger::error("Исключение при смене IP: " . $e->getMessage(), 'proxies');
            return [
                'success' => false,
                'details' => "Ошибка: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удаление прокси
     * 
     * @param int $id ID прокси
     */
    public function delete($id) {
        Logger::info("Начало процесса удаления прокси (ID: $id)", 'proxies');
        
        try {
            // Проверяем, что запрос отправлен методом POST
            if (!$this->isMethod('POST')) {
                Logger::warning("Попытка удаления прокси (ID: $id) не через POST метод", 'proxies');
                return $this->handleAjaxError('Метод не поддерживается', 405);
            }
            
            // Удаляем прокси из базы данных
            Logger::debug("Попытка удаления прокси (ID: $id) из базы данных", 'proxies');
            $result = $this->db->delete('proxies', ['id' => $id]);
            
            // Проверяем результат
            if ($result) {
                $successMsg = 'Прокси успешно удален';
                Logger::info($successMsg . " (ID: $id)", 'proxies');
                return $this->handleSuccess($successMsg, null, true);
            } else {
                $errorMsg = 'Ошибка при удалении прокси';
                Logger::error($errorMsg . " (ID: $id)", 'proxies');
                return $this->handleAjaxError($errorMsg);
            }
        } catch (Exception $e) {
            // Обработка исключений
            $errorMsg = 'Ошибка при удалении прокси: ' . $e->getMessage();
            Logger::error($errorMsg . "\n" . $e->getTraceAsString(), 'proxies');
            return $this->handleAjaxError($errorMsg, 500);
        }
    }
    
    /**
     * Массовое удаление прокси
     */
    public function bulkDelete() {
        Logger::info("Начало процесса массового удаления прокси", 'proxies');
        
        try {
            // Проверяем, что запрос отправлен методом POST
            if (!$this->isMethod('POST')) {
                Logger::warning("Попытка массового удаления прокси не через POST метод", 'proxies');
                return $this->handleAjaxError('Метод не поддерживается', 405);
            }
            
            // Получаем данные из JSON тела запроса
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Проверяем наличие ID прокси
            if (empty($data['ids']) || !is_array($data['ids'])) {
                Logger::warning("Не указаны ID прокси для массового удаления", 'proxies');
                return $this->handleAjaxError('Не указаны ID прокси для удаления');
            }
            
            // Удаляем каждый прокси
            $success = 0;
            $failed = 0;
            
            foreach ($data['ids'] as $id) {
                // Удаляем прокси из базы данных
                $result = $this->db->delete('proxies', ['id' => $id]);
                
                // Проверяем результат
                if ($result) {
                    Logger::info("Прокси успешно удален (ID: $id)", 'proxies');
                    $success++;
                } else {
                    Logger::error("Ошибка при удалении прокси (ID: $id)", 'proxies');
                    $failed++;
                }
            }
            
            // Возвращаем результат
            $message = "Удалено прокси: $success";
            if ($failed > 0) {
                $message .= ", не удалось удалить: $failed";
            }
            
            Logger::info($message, 'proxies');
            return $this->jsonResponse([
                'success' => true,
                'message' => $message
            ]);
        } catch (Exception $e) {
            // Обработка исключений
            $errorMsg = 'Ошибка при массовом удалении прокси: ' . $e->getMessage();
            Logger::error($errorMsg . "\n" . $e->getTraceAsString(), 'proxies');
            return $this->handleAjaxError($errorMsg, 500);
        }
    }
    
    /**
     * Получение всех прокси
     * 
     * @return array Массив прокси
     */
    private function getAllProxies() {
        try {
            return $this->db->fetchAll("
                SELECT * FROM proxies 
                ORDER BY status = 'working' DESC, is_active DESC, name ASC
            ");
        } catch (Exception $e) {
            Logger::error('Ошибка при получении списка прокси: ' . $e->getMessage(), 'proxies');
            return [];
        }
    }
}
