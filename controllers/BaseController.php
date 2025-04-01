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
        // Если это AJAX запрос к основному эндпоинту (index), то возвращаем ошибку в JSON
        // Но позволяем AJAX запросам к другим эндпоинтам (add, delete, check) проходить дальше
        if ($this->isAjax() && $this->isIndexEndpoint()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Неверный запрос. Этот эндпоинт не предназначен для AJAX запросов.'
            ], 400);
            return;
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
}
