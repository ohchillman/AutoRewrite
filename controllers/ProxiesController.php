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
        
        try {
            // Проверяем, что запрос отправлен методом POST
            Logger::debug('Проверка метода запроса: ' . $_SERVER['REQUEST_METHOD'], 'proxies');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Logger::warning('Попытка доступа не через POST метод', 'proxies');
                $this->redirect('/proxies');
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
                Logger::debug('Отправка AJAX ответа с исключением: ' . json_encode($response), 'proxies');
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
    public function delete($id = null) {
        Logger::info("Начало процесса удаления прокси (ID: $id)", 'proxies');
        
        try {
            // Проверяем ID
            Logger::debug("Проверка ID прокси: $id", 'proxies');
            if (empty($id)) {
                $errorMsg = 'ID прокси не указан';
                Logger::warning($errorMsg, 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'id' => $id,
                            'stage' => 'validation_id'
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
            
            // Удаляем прокси из базы данных
            Logger::debug("Попытка удаления прокси из базы данных (ID: $id)", 'proxies');
            try {
                $result = $this->db->delete('proxies', 'id = ?', [$id]);
                Logger::debug("Результат удаления: $result", 'proxies');
            } catch (Exception $e) {
                Logger::error('Ошибка при удалении из базы данных: ' . $e->getMessage(), 'proxies');
                throw $e;
            }
            
            // Проверяем результат
            if ($result) {
                $successMsg = 'Прокси успешно удален';
                Logger::info($successMsg . " (ID: $id)", 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => true,
                        'message' => $successMsg,
                        'refresh' => true,
                        'debug' => [
                            'id' => $id,
                            'stage' => 'delete_success'
                        ]
                    ];
                    Logger::debug('Отправка AJAX ответа: ' . json_encode($response), 'proxies');
                    $this->jsonResponse($response);
                } else {
                    $_SESSION['success'] = $successMsg;
                    $this->redirect('/proxies');
                }
            } else {
                $errorMsg = 'Ошибка при удалении прокси';
                Logger::error($errorMsg . " (ID: $id)", 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'id' => $id,
                            'stage' => 'delete_failed'
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
            $errorMsg = 'Ошибка при удалении прокси: ' . $e->getMessage();
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
                Logger::debug('Отправка AJAX ответа с исключением: ' . json_encode($response), 'proxies');
                $this->jsonResponse($response);
            } else {
                $_SESSION['error'] = $errorMsg;
                $this->redirect('/proxies');
            }
        }
    }
    
    /**
     * Изменение статуса прокси (активен/неактивен)
     * 
     * @param int $id ID прокси
     */
    public function toggle($id = null) {
        Logger::info("Начало процесса изменения статуса прокси (ID: $id)", 'proxies');
        
        try {
            // Проверяем ID
            Logger::debug("Проверка ID прокси: $id", 'proxies');
            if (empty($id)) {
                $errorMsg = 'ID прокси не указан';
                Logger::warning($errorMsg, 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'id' => $id,
                            'stage' => 'validation_id'
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
            
            // Получаем текущий статус
            Logger::debug("Получение текущего статуса прокси (ID: $id)", 'proxies');
            try {
                $proxy = $this->db->fetchOne("SELECT is_active FROM proxies WHERE id = ?", [$id]);
                Logger::logVar($proxy, 'proxy', 'debug', 'proxies');
            } catch (Exception $e) {
                Logger::error('Ошибка при получении статуса прокси: ' . $e->getMessage(), 'proxies');
                throw $e;
            }
            
            if (!$proxy) {
                $errorMsg = 'Прокси не найден';
                Logger::warning($errorMsg . " (ID: $id)", 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'id' => $id,
                            'stage' => 'proxy_not_found'
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
            
            // Инвертируем статус
            $newStatus = $proxy['is_active'] ? 0 : 1;
            Logger::debug("Инвертирование статуса: {$proxy['is_active']} -> $newStatus", 'proxies');
            
            // Обновляем статус в базе данных
            Logger::debug("Обновление статуса в базе данных (ID: $id, Новый статус: $newStatus)", 'proxies');
            try {
                $result = $this->db->update('proxies', ['is_active' => $newStatus], 'id = ?', [$id]);
                Logger::debug("Результат обновления: $result", 'proxies');
            } catch (Exception $e) {
                Logger::error('Ошибка при обновлении статуса прокси: ' . $e->getMessage(), 'proxies');
                throw $e;
            }
            
            // Проверяем результат
            if ($result) {
                $successMsg = 'Статус прокси изменен';
                Logger::info($successMsg . " (ID: $id, Новый статус: $newStatus)", 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => true,
                        'message' => $successMsg,
                        'refresh' => true,
                        'debug' => [
                            'id' => $id,
                            'new_status' => $newStatus,
                            'stage' => 'toggle_success'
                        ]
                    ];
                    Logger::debug('Отправка AJAX ответа: ' . json_encode($response), 'proxies');
                    $this->jsonResponse($response);
                } else {
                    $_SESSION['success'] = $successMsg;
                    $this->redirect('/proxies');
                }
            } else {
                $errorMsg = 'Ошибка при изменении статуса прокси';
                Logger::error($errorMsg . " (ID: $id)", 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'id' => $id,
                            'new_status' => $newStatus,
                            'stage' => 'toggle_failed'
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
            $errorMsg = 'Ошибка при изменении статуса прокси: ' . $e->getMessage();
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
                Logger::debug('Отправка AJAX ответа с исключением: ' . json_encode($response), 'proxies');
                $this->jsonResponse($response);
            } else {
                $_SESSION['error'] = $errorMsg;
                $this->redirect('/proxies');
            }
        }
    }
    
    /**
     * Проверка прокси
     * 
     * @param int $id ID прокси
     */
    public function check($id = null) {
        Logger::info("Начало процесса проверки прокси (ID: $id)", 'proxies');
        
        try {
            // Проверяем ID
            Logger::debug("Проверка ID прокси: $id", 'proxies');
            if (empty($id)) {
                $errorMsg = 'ID прокси не указан';
                Logger::warning($errorMsg, 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'id' => $id,
                            'stage' => 'validation_id'
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
            
            // Получаем данные прокси
            Logger::debug("Получение данных прокси (ID: $id)", 'proxies');
            try {
                $proxy = $this->db->fetchOne("
                    SELECT ip, port, username, password, protocol 
                    FROM proxies 
                    WHERE id = ?
                ", [$id]);
                Logger::logVar($proxy, 'proxy', 'debug', 'proxies');
            } catch (Exception $e) {
                Logger::error('Ошибка при получении данных прокси: ' . $e->getMessage(), 'proxies');
                throw $e;
            }
            
            if (!$proxy) {
                $errorMsg = 'Прокси не найден';
                Logger::warning($errorMsg . " (ID: $id)", 'proxies');
                
                if ($this->isAjax()) {
                    $response = [
                        'success' => false,
                        'message' => $errorMsg,
                        'debug' => [
                            'id' => $id,
                            'stage' => 'proxy_not_found'
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
            
            // Проверяем прокси
            Logger::debug("Проверка соединения через прокси (ID: $id)", 'proxies');
            $checkResult = $this->checkProxyConnection($proxy);
            Logger::debug("Результат проверки: " . ($checkResult ? 'успешно' : 'неудачно'), 'proxies');
            
            // Обновляем статус в базе данных
            $status = $checkResult ? 'working' : 'failed';
            Logger::debug("Обновление статуса прокси в базе данных (ID: $id, Статус: $status)", 'proxies');
            try {
                $updateResult = $this->db->update('proxies', [
                    'status' => $status,
                    'last_check' => date('Y-m-d H:i:s')
                ], 'id = ?', [$id]);
                Logger::debug("Результат обновления: $updateResult", 'proxies');
            } catch (Exception $e) {
                Logger::error('Ошибка при обновлении статуса прокси: ' . $e->getMessage(), 'proxies');
                throw $e;
            }
            
            // Отправляем ответ
            if ($this->isAjax()) {
                $response = [
                    'success' => $checkResult,
                    'message' => $checkResult ? 'Прокси работает' : 'Прокси не работает',
                    'debug' => [
                        'id' => $id,
                        'status' => $status,
                        'check_result' => $checkResult,
                        'update_result' => $updateResult,
                        'stage' => 'check_completed'
                    ]
                ];
                Logger::debug('Отправка AJAX ответа: ' . json_encode($response), 'proxies');
                $this->jsonResponse($response);
            } else {
                if ($checkResult) {
                    $_SESSION['success'] = 'Прокси работает';
                } else {
                    $_SESSION['error'] = 'Прокси не работает';
                }
                $this->redirect('/proxies');
            }
        } catch (Exception $e) {
            // Обработка исключений
            $errorMsg = 'Ошибка при проверке прокси: ' . $e->getMessage();
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
                Logger::debug('Отправка AJAX ответа с исключением: ' . json_encode($response), 'proxies');
                $this->jsonResponse($response);
            } else {
                $_SESSION['error'] = $errorMsg;
                $this->redirect('/proxies');
            }
        }
    }
    
    /**
     * Проверка соединения через прокси
     * 
     * @param array $proxy Данные прокси
     * @return bool Результат проверки
     */
    private function checkProxyConnection($proxy) {
        Logger::debug('Начало проверки соединения через прокси', 'proxies');
        
        // Формируем строку прокси для cURL
        $proxyString = $proxy['protocol'] . '://';
        
        if (!empty($proxy['username']) && !empty($proxy['password'])) {
            $proxyString .= $proxy['username'] . ':' . $proxy['password'] . '@';
        }
        
        $proxyString .= $proxy['ip'] . ':' . $proxy['port'];
        Logger::debug("Строка прокси для cURL: $proxyString", 'proxies');
        
        // Инициализируем cURL
        $ch = curl_init('https://www.google.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROXY, $proxyString);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        // Выполняем запрос
        Logger::debug('Выполнение cURL запроса через прокси', 'proxies');
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        Logger::debug("Результат cURL запроса: HTTP код=$httpCode, Ошибка=" . ($error ? $error : 'нет'), 'proxies');
        if (!empty($response)) {
            Logger::debug("Получен ответ длиной " . strlen($response) . " байт", 'proxies');
        }
        
        // Закрываем соединение
        curl_close($ch);
        
        // Проверяем результат
        $result = !empty($response) && empty($error) && $httpCode >= 200 && $httpCode < 400;
        Logger::debug("Итоговый результат проверки: " . ($result ? 'успешно' : 'неудачно'), 'proxies');
        
        return $result;
    }
    
    /**
     * Получение всех прокси
     * 
     * @return array Массив прокси
     */
    private function getAllProxies() {
        Logger::debug('Получение списка всех прокси из базы данных', 'proxies');
        
        try {
            $proxies = $this->db->fetchAll("
                SELECT * FROM proxies 
                ORDER BY is_active DESC, status ASC, id DESC
            ");
            Logger::debug("Получено " . count($proxies) . " прокси", 'proxies');
            return $proxies;
        } catch (Exception $e) {
            Logger::error('Ошибка при получении списка прокси: ' . $e->getMessage(), 'proxies');
            throw $e;
        }
    }
}
