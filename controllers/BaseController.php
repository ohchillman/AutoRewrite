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
        $this->view->render($view, $data);
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
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
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
