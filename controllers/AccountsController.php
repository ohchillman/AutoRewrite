<?php
/**
 * Контроллер для управления аккаунтами
 */
class AccountsController extends BaseController {
    
    /**
     * Отображение страницы управления аккаунтами
     */
    public function index() {
        // Получаем список аккаунтов из базы данных
        $accounts = $this->getAllAccounts();
        
        // Получаем типы аккаунтов
        $accountTypes = $this->getAccountTypes();
        
        // Получаем список прокси для выбора
        $proxies = $this->getProxies();
        
        // Отображаем представление
        $this->render('accounts/index', [
            'title' => 'Аккаунты - AutoRewrite',
            'pageTitle' => 'Управление аккаунтами',
            'currentPage' => 'accounts',
            'layout' => 'main',
            'accounts' => $accounts,
            'accountTypes' => $accountTypes,
            'proxies' => $proxies
        ]);
    }
    
    /**
     * Добавление нового аккаунта
     */
    public function add() {
        // Проверяем, что запрос отправлен методом POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/accounts');
            return;
        }
        
        // Получаем данные из POST
        $accountTypeId = $this->post('account_type_id');
        $name = $this->post('name');
        $username = $this->post('username');
        $password = $this->post('password');
        $apiKey = $this->post('api_key');
        $apiSecret = $this->post('api_secret');
        $accessToken = $this->post('access_token');
        $accessTokenSecret = $this->post('access_token_secret');
        $refreshToken = $this->post('refresh_token');
        $proxyId = $this->post('proxy_id');
        $additionalData = $this->post('additional_data');
        
        // Проверяем обязательные поля
        if (empty($accountTypeId) || empty($name)) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Необходимо заполнить поля Тип аккаунта и Название'
                ]);
            } else {
                $_SESSION['error'] = 'Необходимо заполнить поля Тип аккаунта и Название';
                $this->redirect('/accounts');
            }
            return;
        }
        
        // Подготавливаем данные для вставки
        $accountData = [
            'account_type_id' => $accountTypeId,
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'access_token' => $accessToken,
            'access_token_secret' => $accessTokenSecret,
            'refresh_token' => $refreshToken,
            'proxy_id' => $proxyId ?: null,
            'additional_data' => $additionalData ? json_encode($additionalData) : null,
            'is_active' => 1
        ];
        
        // Добавляем аккаунт в базу данных
        $accountId = $this->db->insert('accounts', $accountData);
        
        // Проверяем результат
        if ($accountId) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Аккаунт успешно добавлен',
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = 'Аккаунт успешно добавлен';
                $this->redirect('/accounts');
            }
        } else {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при добавлении аккаунта'
                ]);
            } else {
                $_SESSION['error'] = 'Ошибка при добавлении аккаунта';
                $this->redirect('/accounts');
            }
        }
    }
    
    /**
     * Удаление аккаунта
     * 
     * @param int $id ID аккаунта
     */
    public function delete($id = null) {
        // Проверяем ID
        if (empty($id)) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'ID аккаунта не указан'
                ]);
            } else {
                $_SESSION['error'] = 'ID аккаунта не указан';
                $this->redirect('/accounts');
            }
            return;
        }
        
        // Удаляем аккаунт из базы данных
        $result = $this->db->delete('accounts', 'id = ?', [$id]);
        
        // Проверяем результат
        if ($result) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Аккаунт успешно удален',
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = 'Аккаунт успешно удален';
                $this->redirect('/accounts');
            }
        } else {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при удалении аккаунта'
                ]);
            } else {
                $_SESSION['error'] = 'Ошибка при удалении аккаунта';
                $this->redirect('/accounts');
            }
        }
    }
    
    /**
     * Изменение статуса аккаунта (активен/неактивен)
     * 
     * @param int $id ID аккаунта
     */
    public function toggle($id = null) {
        // Проверяем ID
        if (empty($id)) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'ID аккаунта не указан'
                ]);
            } else {
                $_SESSION['error'] = 'ID аккаунта не указан';
                $this->redirect('/accounts');
            }
            return;
        }
        
        // Получаем текущий статус
        $account = $this->db->fetchOne("SELECT is_active FROM accounts WHERE id = ?", [$id]);
        
        if (!$account) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Аккаунт не найден'
                ]);
            } else {
                $_SESSION['error'] = 'Аккаунт не найден';
                $this->redirect('/accounts');
            }
            return;
        }
        
        // Инвертируем статус
        $newStatus = $account['is_active'] ? 0 : 1;
        
        // Обновляем статус в базе данных
        $result = $this->db->update('accounts', ['is_active' => $newStatus], 'id = ?', [$id]);
        
        // Проверяем результат
        if ($result) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Статус аккаунта изменен',
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = 'Статус аккаунта изменен';
                $this->redirect('/accounts');
            }
        } else {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при изменении статуса аккаунта'
                ]);
            } else {
                $_SESSION['error'] = 'Ошибка при изменении статуса аккаунта';
                $this->redirect('/accounts');
            }
        }
    }
    
    /**
     * Редактирование аккаунта
     * 
     * @param int $id ID аккаунта
     */
    public function edit($id = null) {
        // Проверяем ID
        if (empty($id)) {
            $this->redirect('/accounts');
            return;
        }
        
        // Если POST запрос, обрабатываем форму
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Получаем данные из POST
            $name = $this->post('name');
            $username = $this->post('username');
            $password = $this->post('password');
            $apiKey = $this->post('api_key');
            $apiSecret = $this->post('api_secret');
            $accessToken = $this->post('access_token');
            $accessTokenSecret = $this->post('access_token_secret');
            $refreshToken = $this->post('refresh_token');
            $proxyId = $this->post('proxy_id');
            $additionalData = $this->post('additional_data');
            
            // Проверяем обязательные поля
            if (empty($name)) {
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Необходимо заполнить поле Название'
                    ]);
                } else {
                    $_SESSION['error'] = 'Необходимо заполнить поле Название';
                    $this->redirect('/accounts/edit/' . $id);
                }
                return;
            }
            
            // Подготавливаем данные для обновления
            $accountData = [
                'name' => $name,
                'username' => $username,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'access_token' => $accessToken,
                'access_token_secret' => $accessTokenSecret,
                'refresh_token' => $refreshToken,
                'proxy_id' => $proxyId ?: null,
                'additional_data' => $additionalData ? json_encode($additionalData) : null
            ];
            
            // Если пароль не пустой, обновляем его
            if (!empty($password)) {
                $accountData['password'] = $password;
            }
            
            // Обновляем аккаунт в базе данных
            $result = $this->db->update('accounts', $accountData, 'id = ?', [$id]);
            
            // Проверяем результат
            if ($result !== false) {
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Аккаунт успешно обновлен',
                        'redirect' => '/accounts'
                    ]);
                } else {
                    $_SESSION['success'] = 'Аккаунт успешно обновлен';
                    $this->redirect('/accounts');
                }
            } else {
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Ошибка при обновлении аккаунта'
                    ]);
                } else {
                    $_SESSION['error'] = 'Ошибка при обновлении аккаунта';
                    $this->redirect('/accounts/edit/' . $id);
                }
            }
            return;
        }
        
        // Получаем данные аккаунта
        $account = $this->db->fetchOne("
            SELECT a.*, at.name as account_type_name 
            FROM accounts a
            JOIN account_types at ON a.account_type_id = at.id
            WHERE a.id = ?
        ", [$id]);
        
        if (!$account) {
            $_SESSION['error'] = 'Аккаунт не найден';
            $this->redirect('/accounts');
            return;
        }
        
        // Получаем типы аккаунтов
        $accountTypes = $this->getAccountTypes();
        
        // Получаем список прокси для выбора
        $proxies = $this->getProxies();
        
        // Отображаем представление
        $this->render('accounts/edit', [
            'title' => 'Редактирование аккаунта - AutoRewrite',
            'pageTitle' => 'Редактирование аккаунта',
            'currentPage' => 'accounts',
            'layout' => 'main',
            'account' => $account,
            'accountTypes' => $accountTypes,
            'proxies' => $proxies
        ]);
    }
    
    /**
     * Получение всех аккаунтов с информацией о типе
     * 
     * @return array Массив аккаунтов
     */
    private function getAllAccounts() {
        return $this->db->fetchAll("
            SELECT a.*, at.name as account_type_name, p.ip as proxy_ip, p.port as proxy_port
            FROM accounts a
            JOIN account_types at ON a.account_type_id = at.id
            LEFT JOIN proxies p ON a.proxy_id = p.id
            ORDER BY a.is_active DESC, a.name ASC
        ");
    }
    
    /**
     * Получение всех типов аккаунтов
     * 
     * @return array Массив типов аккаунтов
     */
    private function getAccountTypes() {
        return $this->db->fetchAll("SELECT * FROM account_types ORDER BY name ASC");
    }
    
    /**
     * Получение всех активных прокси
     * 
     * @return array Массив прокси
     */
    private function getProxies() {
        return $this->db->fetchAll("
            SELECT * FROM proxies 
            WHERE is_active = 1
            ORDER BY status = 'working' DESC, ip ASC
        ");
    }
}
