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
        // Проверяем, что запрос отправлен методом POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/proxies');
            return;
        }
        
        // Получаем данные из POST
        $ip = $this->post('ip');
        $port = $this->post('port');
        $username = $this->post('username');
        $password = $this->post('password');
        $protocol = $this->post('protocol');
        $country = $this->post('country');
        
        // Проверяем обязательные поля
        if (empty($ip) || empty($port) || empty($protocol)) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Необходимо заполнить поля IP, порт и протокол'
                ]);
            } else {
                $_SESSION['error'] = 'Необходимо заполнить поля IP, порт и протокол';
                $this->redirect('/proxies');
            }
            return;
        }
        
        // Добавляем прокси в базу данных
        $proxyId = $this->db->insert('proxies', [
            'ip' => $ip,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'protocol' => $protocol,
            'country' => $country,
            'is_active' => 1,
            'status' => 'unchecked'
        ]);
        
        // Проверяем результат
        if ($proxyId) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Прокси успешно добавлен',
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = 'Прокси успешно добавлен';
                $this->redirect('/proxies');
            }
        } else {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при добавлении прокси'
                ]);
            } else {
                $_SESSION['error'] = 'Ошибка при добавлении прокси';
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
        // Проверяем ID
        if (empty($id)) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'ID прокси не указан'
                ]);
            } else {
                $_SESSION['error'] = 'ID прокси не указан';
                $this->redirect('/proxies');
            }
            return;
        }
        
        // Удаляем прокси из базы данных
        $result = $this->db->delete('proxies', 'id = ?', [$id]);
        
        // Проверяем результат
        if ($result) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Прокси успешно удален',
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = 'Прокси успешно удален';
                $this->redirect('/proxies');
            }
        } else {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при удалении прокси'
                ]);
            } else {
                $_SESSION['error'] = 'Ошибка при удалении прокси';
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
        // Проверяем ID
        if (empty($id)) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'ID прокси не указан'
                ]);
            } else {
                $_SESSION['error'] = 'ID прокси не указан';
                $this->redirect('/proxies');
            }
            return;
        }
        
        // Получаем текущий статус
        $proxy = $this->db->fetchOne("SELECT is_active FROM proxies WHERE id = ?", [$id]);
        
        if (!$proxy) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Прокси не найден'
                ]);
            } else {
                $_SESSION['error'] = 'Прокси не найден';
                $this->redirect('/proxies');
            }
            return;
        }
        
        // Инвертируем статус
        $newStatus = $proxy['is_active'] ? 0 : 1;
        
        // Обновляем статус в базе данных
        $result = $this->db->update('proxies', ['is_active' => $newStatus], 'id = ?', [$id]);
        
        // Проверяем результат
        if ($result) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Статус прокси изменен',
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = 'Статус прокси изменен';
                $this->redirect('/proxies');
            }
        } else {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при изменении статуса прокси'
                ]);
            } else {
                $_SESSION['error'] = 'Ошибка при изменении статуса прокси';
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
        // Проверяем ID
        if (empty($id)) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'ID прокси не указан'
                ]);
            } else {
                $_SESSION['error'] = 'ID прокси не указан';
                $this->redirect('/proxies');
            }
            return;
        }
        
        // Получаем данные прокси
        $proxy = $this->db->fetchOne("
            SELECT ip, port, username, password, protocol 
            FROM proxies 
            WHERE id = ?
        ", [$id]);
        
        if (!$proxy) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Прокси не найден'
                ]);
            } else {
                $_SESSION['error'] = 'Прокси не найден';
                $this->redirect('/proxies');
            }
            return;
        }
        
        // Проверяем прокси
        $checkResult = $this->checkProxyConnection($proxy);
        
        // Обновляем статус в базе данных
        $status = $checkResult ? 'working' : 'failed';
        $this->db->update('proxies', [
            'status' => $status,
            'last_check' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
        
        // Отправляем ответ
        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => $checkResult,
                'message' => $checkResult ? 'Прокси работает' : 'Прокси не работает'
            ]);
        } else {
            if ($checkResult) {
                $_SESSION['success'] = 'Прокси работает';
            } else {
                $_SESSION['error'] = 'Прокси не работает';
            }
            $this->redirect('/proxies');
        }
    }
    
    /**
     * Проверка соединения через прокси
     * 
     * @param array $proxy Данные прокси
     * @return bool Результат проверки
     */
    private function checkProxyConnection($proxy) {
        // Формируем строку прокси для cURL
        $proxyString = $proxy['protocol'] . '://';
        
        if (!empty($proxy['username']) && !empty($proxy['password'])) {
            $proxyString .= $proxy['username'] . ':' . $proxy['password'] . '@';
        }
        
        $proxyString .= $proxy['ip'] . ':' . $proxy['port'];
        
        // Инициализируем cURL
        $ch = curl_init('https://www.google.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROXY, $proxyString);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        // Выполняем запрос
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Закрываем соединение
        curl_close($ch);
        
        // Проверяем результат
        return !empty($response) && empty($error) && $httpCode >= 200 && $httpCode < 400;
    }
    
    /**
     * Получение всех прокси
     * 
     * @return array Массив прокси
     */
    private function getAllProxies() {
        return $this->db->fetchAll("
            SELECT * FROM proxies 
            ORDER BY is_active DESC, status ASC, id DESC
        ");
    }
}
