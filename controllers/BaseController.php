<?php
/**
 * Базовый контроллер
 */
class BaseController {
    protected $db;
    protected $view;
    
    /**
     * Конструктор
     */
    public function __construct() {
        // Инициализируем соединение с базой данных
        $this->db = Database::getInstance();
        
        // Инициализируем представление
        $this->view = new View();
    }
    
    /**
     * Отображение представления
     * 
     * @param string $view Имя представления
     * @param array $data Данные для представления
     */
    protected function render($view, $data = []) {
        // Если это AJAX запрос к основному эндпоинту (index), проверяем
        // Но не блокируем автоматически - лучше всегда давать осмысленный ответ
        if ($this->isAjax() && $this->isIndexEndpoint()) {
            // Вместо возврата ошибки, просто логируем
            Logger::debug('AJAX запрос к index эндпоинту: ' . $_SERVER['REQUEST_URI'], 'ajax');
        }
        
        $this->view->render($view, $data);
    }
    
    /**
     * Проверяет, является ли текущий запрос запросом к основному эндпоинту (index)
     * 
     * @return bool Является ли запрос к основному эндпоинту
     */
    protected function isIndexEndpoint() {
        $uri = $_SERVER['REQUEST_URI'];
        $segments = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
        
        // Если URL содержит только один сегмент (например, 'proxies'), 
        // или второй сегмент пустой, то это запрос к index
        return count($segments) <= 1 || 
               (count($segments) == 2 && empty($segments[1]));
    }
    
    /**
     * Перенаправление на другую страницу
     * 
     * @param string $url URL для перенаправления
     */
    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Проверка AJAX запроса
     * 
     * @return bool Является ли запрос AJAX
     */
    protected function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Отправка JSON ответа
     * 
     * @param mixed $data Данные для отправки
     * @param int $statusCode HTTP код ответа
     */
    protected function jsonResponse($data, $statusCode = 200) {
        // Очищаем все предыдущие буферы вывода
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Логирование для отладки
        Logger::debug('Отправляется JSON ответ: ' . json_encode($data), 'ajax');
        
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Получение данных из POST запроса
     * 
     * @param string $key Ключ данных
     * @param mixed $default Значение по умолчанию
     * @return mixed Данные из POST запроса
     */
    protected function post($key, $default = null) {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    
    /**
     * Получение данных из GET запроса
     * 
     * @param string $key Ключ данных
     * @param mixed $default Значение по умолчанию
     * @return mixed Данные из GET запроса
     */
    protected function get($key, $default = null) {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
    
    /**
     * Получение данных из JSON тела запроса
     * 
     * @return array Данные из JSON тела запроса
     */
    protected function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Ошибка декодирования JSON: ' . json_last_error_msg() . '. Входящие данные: ' . $input, 'ajax');
            return [];
        }
        
        return $data ?: [];
    }
    
    /**
     * Проверяет текущий HTTP метод
     * 
     * @param string $method Метод для проверки (GET, POST, PUT, DELETE и т.д.)
     * @return bool Соответствует ли текущий метод запроса указанному
     */
    protected function isMethod($method) {
        return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
    }
    
    /**
     * Обработка ошибок для AJAX запросов
     * 
     * @param string $message Сообщение об ошибке
     * @param int $statusCode HTTP код ошибки
     */
    protected function handleAjaxError($message, $statusCode = 400) {
        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => false,
                'message' => $message
            ], $statusCode);
        } else {
            $_SESSION['error'] = $message;
            $this->redirect($this->getReferer());
        }
    }
    
    /**
     * Обработка успешных операций
     * 
     * @param string $message Сообщение об успехе
     * @param string $redirectUrl URL для перенаправления
     * @param bool $refresh Обновить текущую страницу
     */
    protected function handleSuccess($message, $redirectUrl = null, $refresh = false) {
        if ($this->isAjax()) {
            $response = [
                'success' => true,
                'message' => $message
            ];
            
            if ($redirectUrl) {
                $response['redirect'] = $redirectUrl;
            }
            
            if ($refresh) {
                $response['refresh'] = true;
            }
            
            $this->jsonResponse($response);
        } else {
            $_SESSION['success'] = $message;
            if ($redirectUrl) {
                $this->redirect($redirectUrl);
            }
        }
    }
    
    /**
     * Получает URL страницы, с которой был осуществлен переход
     * 
     * @return string URL страницы
     */
    protected function getReferer() {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
    }
}