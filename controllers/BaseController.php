<?php
/**
 * Базовый контроллер, от которого наследуются все остальные контроллеры
 */
class BaseController {
    protected $db;
    
    public function __construct() {
        // Инициализация соединения с базой данных
        $this->db = Database::getInstance();
    }
    
    /**
     * Отрисовка представления
     * 
     * @param string $view Имя представления
     * @param array $data Данные для передачи в представление
     */
    protected function render($view, $data = []) {
        // Извлекаем данные в локальные переменные
        extract($data);
        
        // Определяем путь к файлу представления
        $viewPath = VIEWS_PATH . '/' . $view . '.php';
        
        // Проверяем существование файла представления
        if (!file_exists($viewPath)) {
            die('Представление не найдено: ' . $viewPath);
        }
        
        // Запускаем буферизацию вывода
        ob_start();
        
        // Включаем файл представления
        include $viewPath;
        
        // Получаем содержимое буфера
        $content = ob_get_clean();
        
        // Определяем путь к файлу макета
        $layoutPath = VIEWS_PATH . '/layouts/' . ($layout ?? 'main') . '.php';
        
        // Проверяем существование файла макета
        if (!file_exists($layoutPath)) {
            die('Макет не найден: ' . $layoutPath);
        }
        
        // Включаем файл макета с передачей содержимого представления
        include $layoutPath;
    }

    /**
     * Отправка JSON-ответа
     * 
     * @param array $data Данные для отправки
     * @return void
     */
    protected function jsonResponse($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Перенаправление на указанный URL
     * 
     * @param string $url URL для перенаправления
     */
    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Проверка метода запроса
     * 
     * @param string $method Метод запроса (GET, POST, PUT, DELETE)
     * @return bool Результат проверки
     */
    protected function isMethod($method) {
        return $_SERVER['REQUEST_METHOD'] === strtoupper($method);
    }
    
    /**
     * Проверка, является ли запрос AJAX-запросом
     * 
     * @return bool Результат проверки
     */
    protected function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Получение данных из POST-запроса
     * 
     * @param string $key Ключ для получения конкретного значения
     * @param mixed $default Значение по умолчанию
     * @return mixed Данные из POST-запроса
     */
    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    
    /**
     * Получение данных из GET-запроса
     * 
     * @param string $key Ключ для получения конкретного значения
     * @param mixed $default Значение по умолчанию
     * @return mixed Данные из GET-запроса
     */
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
    
    /**
     * Получение данных из JSON-тела запроса
     * 
     * @return array Данные из JSON-тела запроса
     */
    protected function getJsonInput() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    /**
     * Обработка успешного результата
     * 
     * @param string $message Сообщение об успехе
     * @param string|null $redirect URL для перенаправления
     * @param bool $refresh Флаг необходимости обновления страницы
     * @return void
     */
    protected function handleSuccess($message, $redirect = null, $refresh = false) {
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'redirect' => $redirect,
                'refresh' => $refresh
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $_SESSION['success'] = $message;
            if ($redirect) {
                $this->redirect($redirect);
            } else if ($refresh) {
                $this->redirect($_SERVER['REQUEST_URI']);
            }
        }
    }
    
    /**
     * Обработка ошибки
     * 
     * @param string $message Сообщение об ошибке
     * @param int $code HTTP-код ошибки
     * @return void
     */
    protected function handleAjaxError($message, $code = 400) {
        if ($this->isAjax()) {
            http_response_code($code);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $_SESSION['error'] = $message;
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}