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
       if (!$this->isMethod('POST')) {
           return $this->handleAjaxError('Метод не поддерживается', 405);
       }
       
       try {
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
               return $this->handleAjaxError('Необходимо заполнить поля Тип аккаунта и Название');
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
               return $this->handleSuccess('Аккаунт успешно добавлен', null, true);
           } else {
               return $this->handleAjaxError('Ошибка при добавлении аккаунта');
           }
       } catch (Exception $e) {
           Logger::error('Ошибка при добавлении аккаунта: ' . $e->getMessage(), 'accounts');
           return $this->handleAjaxError('Ошибка при добавлении аккаунта: ' . $e->getMessage(), 500);
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
           return $this->handleAjaxError('ID аккаунта не указан', 400);
       }
       
       try {
           // Проверяем, что запрос отправлен методом POST
           if (!$this->isMethod('POST')) {
               return $this->handleAjaxError('Метод не поддерживается', 405);
           }
           
           // Удаляем аккаунт из базы данных
           $result = $this->db->delete('accounts', 'id = ?', [$id]);
           
           // Проверяем результат
           if ($result) {
               return $this->handleSuccess('Аккаунт успешно удален', null, true);
           } else {
               return $this->handleAjaxError('Ошибка при удалении аккаунта');
           }
       } catch (Exception $e) {
           Logger::error('Ошибка при удалении аккаунта: ' . $e->getMessage(), 'accounts');
           return $this->handleAjaxError('Ошибка при удалении аккаунта: ' . $e->getMessage(), 500);
       }
   }
   
   /**
    * Массовое удаление аккаунтов
    */
   public function bulkDelete() {
       try {
           // Проверяем, что запрос отправлен методом POST
           if (!$this->isMethod('POST')) {
               return $this->handleAjaxError('Метод не поддерживается', 405);
           }
           
           // Получаем данные из JSON тела запроса
           $data = $this->getJsonInput();
           $ids = $data['ids'] ?? [];
           
           if (empty($ids) || !is_array($ids)) {
               return $this->handleAjaxError('Не указаны ID для удаления', 400);
           }
           
           // Начинаем транзакцию
           $this->db->getConnection()->beginTransaction();
           
           try {
               // Подготавливаем плейсхолдеры для запроса
               $placeholders = implode(',', array_fill(0, count($ids), '?'));
               
               // Удаляем аккаунты
               $result = $this->db->execute(
                   "DELETE FROM accounts WHERE id IN ($placeholders)",
                   $ids
               );
               
               // Фиксируем транзакцию
               $this->db->getConnection()->commit();
               
               // Проверяем результат
               if ($result !== false) {
                   return $this->handleSuccess('Выбранные аккаунты успешно удалены', '/accounts');
               } else {
                   $this->db->getConnection()->rollBack();
                   return $this->handleAjaxError('Ошибка при удалении аккаунтов');
               }
           } catch (Exception $e) {
               $this->db->getConnection()->rollBack();
               throw $e;
           }
       } catch (Exception $e) {
           Logger::error('Ошибка при массовом удалении аккаунтов: ' . $e->getMessage(), 'accounts');
           return $this->handleAjaxError('Ошибка при массовом удалении аккаунтов: ' . $e->getMessage(), 500);
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
                return $this->handleAjaxError('ID аккаунта не указан', 400);
            } else {
                $_SESSION['error'] = 'ID аккаунта не указан';
                $this->redirect('/accounts');
                return;
            }
        }
        
        try {
            // Получаем текущий статус
            $account = $this->db->fetchOne("SELECT is_active FROM accounts WHERE id = ?", [$id]);
            
            if (!$account) {
                if ($this->isAjax()) {
                    return $this->handleAjaxError('Аккаунт не найден', 404);
                } else {
                    $_SESSION['error'] = 'Аккаунт не найден';
                    $this->redirect('/accounts');
                    return;
                }
            }
            
            // Инвертируем статус
            $newStatus = $account['is_active'] ? 0 : 1;
            
            // Обновляем статус в базе данных
            $result = $this->db->update('accounts', ['is_active' => $newStatus], 'id = ?', [$id]);
            
            // Проверяем результат
            if ($result) {
                if ($this->isAjax()) {
                    return $this->jsonResponse([
                        'success' => true,
                        'message' => 'Статус аккаунта изменен',
                        'refresh' => true
                    ]);
                } else {
                    $_SESSION['success'] = 'Статус аккаунта изменен';
                    $this->redirect('/accounts');
                    return;
                }
            } else {
                if ($this->isAjax()) {
                    return $this->handleAjaxError('Ошибка при изменении статуса аккаунта');
                } else {
                    $_SESSION['error'] = 'Ошибка при изменении статуса аккаунта';
                    $this->redirect('/accounts');
                    return;
                }
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при изменении статуса аккаунта: ' . $e->getMessage(), 'accounts');
            if ($this->isAjax()) {
                return $this->handleAjaxError('Ошибка при изменении статуса аккаунта: ' . $e->getMessage(), 500);
            } else {
                $_SESSION['error'] = 'Ошибка при изменении статуса аккаунта: ' . $e->getMessage();
                $this->redirect('/accounts');
                return;
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
       if ($this->isMethod('POST')) {
           try {
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
                   return $this->handleAjaxError('Необходимо заполнить поле Название');
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
                       return $this->jsonResponse([
                           'success' => true,
                           'message' => 'Аккаунт успешно обновлен',
                           'redirect' => '/accounts'
                       ]);
                   } else {
                       $_SESSION['success'] = 'Аккаунт успешно обновлен';
                       $this->redirect('/accounts');
                   }
               } else {
                   return $this->handleAjaxError('Ошибка при обновлении аккаунта');
               }
           } catch (Exception $e) {
               Logger::error('Ошибка при обновлении аккаунта: ' . $e->getMessage(), 'accounts');
               return $this->handleAjaxError('Ошибка при обновлении аккаунта: ' . $e->getMessage(), 500);
           }
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
       try {
           return $this->db->fetchAll("
               SELECT a.*, at.name as account_type_name, p.ip as proxy_ip, p.port as proxy_port
               FROM accounts a
               JOIN account_types at ON a.account_type_id = at.id
               LEFT JOIN proxies p ON a.proxy_id = p.id
               ORDER BY a.is_active DESC, a.name ASC
           ");
       } catch (Exception $e) {
           Logger::error('Ошибка при получении списка аккаунтов: ' . $e->getMessage(), 'accounts');
           return [];
       }
   }
   
   /**
    * Получение всех типов аккаунтов
    * 
    * @return array Массив типов аккаунтов
    */
   private function getAccountTypes() {
       try {
           return $this->db->fetchAll("SELECT * FROM account_types ORDER BY name ASC");
       } catch (Exception $e) {
           Logger::error('Ошибка при получении типов аккаунтов: ' . $e->getMessage(), 'accounts');
           return [];
       }
   }
   
   /**
    * Получение всех активных прокси
    * 
    * @return array Массив прокси
    */
   private function getProxies() {
       try {
           return $this->db->fetchAll("
               SELECT * FROM proxies 
               WHERE is_active = 1
               ORDER BY status = 'working' DESC, ip ASC
           ");
       } catch (Exception $e) {
           Logger::error('Ошибка при получении списка прокси: ' . $e->getMessage(), 'accounts');
           return [];
       }
    }
    /**
     * Проверка работоспособности аккаунта
     * 
     * @param int $id ID аккаунта
     * @return void
     */
    public function verify($id) {
        try {
            // Проверяем, существует ли аккаунт
            $account = $this->db->fetchOne("
                SELECT a.*, at.name as account_type_name 
                FROM accounts a
                JOIN account_types at ON a.account_type_id = at.id
                WHERE a.id = ?
            ", [$id]);
            
            if (!$account) {
                return $this->handleAjaxError("Аккаунт не найден");
            }
            
            // Инициализируем менеджер прокси
            require_once __DIR__ . "/../utils/proxy/ProxyManager.php";
            $proxyManager = new ProxyManager($this->db, new Logger("accounts"));
            
            // Инициализируем верификатор аккаунтов
            require_once __DIR__ . "/../utils/proxy/AccountVerifier.php";
            $accountVerifier = new AccountVerifier($this->db, $proxyManager, new Logger("accounts"));
            
            // Проверяем аккаунт
            $result = $accountVerifier->verifyAccount($account);
            
            // Логируем результат
            if ($result["success"]) {
                Logger::info("Аккаунт {$account["name"]} (ID: {$id}) успешно проверен: {$result["message"]}", "accounts");
            } else {
                Logger::warning("Ошибка при проверке аккаунта {$account["name"]} (ID: {$id}): {$result["message"]}", "accounts");
            }
            
            // Возвращаем результат
            return $this->jsonResponse([
                "success" => $result["success"],
                "message" => $result["message"]
            ]);
        } catch (Exception $e) {
            Logger::error("Ошибка при проверке аккаунта: " . $e->getMessage(), "accounts");
            return $this->handleAjaxError("Ошибка при проверке аккаунта: " . $e->getMessage(), 500);
        }
    }
    
    /**
     * Массовая проверка аккаунтов
     * 
     * @return void
     */
    public function bulkVerify() {
        try {
            // Получаем ID аккаунтов из запроса
            $ids = $this->post("ids");
            
            if (empty($ids) || !is_array($ids)) {
                return $this->handleAjaxError("Не выбрано ни одного аккаунта для проверки");
            }
            
            // Инициализируем менеджер прокси
            require_once __DIR__ . "/../utils/proxy/ProxyManager.php";
            $proxyManager = new ProxyManager($this->db, new Logger("accounts"));
            
            // Инициализируем верификатор аккаунтов
            require_once __DIR__ . "/../utils/proxy/AccountVerifier.php";
            $accountVerifier = new AccountVerifier($this->db, $proxyManager, new Logger("accounts"));
            
            // Проверяем каждый аккаунт
            $results = [];
            foreach ($ids as $id) {
                // Получаем данные аккаунта
                $account = $this->db->fetchOne("
                    SELECT a.*, at.name as account_type_name 
                    FROM accounts a
                    JOIN account_types at ON a.account_type_id = at.id
                    WHERE a.id = ?
                ", [$id]);
                
                if (!$account) {
                    $results[$id] = [
                        "success" => false,
                        "message" => "Аккаунт не найден"
                    ];
                    continue;
                }
                
                // Проверяем аккаунт
                $result = $accountVerifier->verifyAccount($account);
                $results[$id] = $result;
                
                // Логируем результат
                if ($result["success"]) {
                    Logger::info("Аккаунт {$account["name"]} (ID: {$id}) успешно проверен: {$result["message"]}", "accounts");
                } else {
                    Logger::warning("Ошибка при проверке аккаунта {$account["name"]} (ID: {$id}): {$result["message"]}", "accounts");
                }
            }
            
            // Возвращаем результаты
            return $this->jsonResponse([
                "success" => true,
                "results" => $results
            ]);
        } catch (Exception $e) {
            Logger::error("Ошибка при массовой проверке аккаунтов: " . $e->getMessage(), "accounts");
            return $this->handleAjaxError("Ошибка при массовой проверке аккаунтов: " . $e->getMessage(), 500);
        }
    }
   }
