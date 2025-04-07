<?php
/**
 * Маршрутизатор приложения
 */
class Router {
    private $routes = [];
    
    /**
     * Добавить маршрут
     * 
     * @param string $route URL маршрута
     * @param string $controller Имя контроллера
     * @param string $action Имя метода в контроллере по умолчанию
     */
    public function addRoute($route, $controller, $action) {
        $this->routes[$route] = [
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    /**
     * Обработать запрос
     */
    public function dispatch() {
        // Получаем текущий URL
        $uri = $_SERVER['REQUEST_URI'];
        $uri = trim(parse_url($uri, PHP_URL_PATH), '/');
        
        // Разбиваем URL на части
        $segments = explode('/', $uri);
        $baseRoute = isset($segments[0]) ? $segments[0] : '';
        
        // Проверяем, существует ли такой маршрут
        if (isset($this->routes[$baseRoute])) {
            $controller = $this->routes[$baseRoute]['controller'];
            $defaultAction = $this->routes[$baseRoute]['action'];
            
            if (empty($action)) {
                $action = $defaultAction;
            }
            
            // Определяем действие из URL или используем действие по умолчанию
            $action = isset($segments[1]) && !empty($segments[1]) ? $segments[1] : $this->routes[$baseRoute]['action'];
            
            // Получаем параметры из URL (все после действия)
            $params = array_slice($segments, 2);
            
            // Подключаем контроллер
            $controllerFile = CONTROLLERS_PATH . '/' . $controller . '.php';
            
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                
                // Создаем экземпляр контроллера
                $controllerInstance = new $controller();
                
                // Проверяем, существует ли метод
                if (method_exists($controllerInstance, $action)) {
                    // Вызываем метод с параметрами
                    call_user_func_array([$controllerInstance, $action], $params);
                } else {
                    $this->handleError(404, "Метод {$action} не найден в контроллере {$controller}");
                }
            } else {
                $this->handleError(404, "Контроллер {$controller} не найден");
            }
        } else {
            // Если маршрут не найден, проверяем, может быть это главная страница
            if ($baseRoute === '') {
                // Перенаправляем на главную страницу
                header('Location: /');
                exit;
            }
            
            $this->handleError(404, "Маршрут {$baseRoute} не найден");
        }
    }
    
    /**
     * Обработка ошибок
     * 
     * @param int $code Код ошибки
     * @param string $message Сообщение об ошибке
     */
    private function handleError($code, $message) {
        // Проверяем, является ли запрос AJAX
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isAjax) {
            // Очищаем все буферы вывода
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Для AJAX запросов возвращаем JSON с ошибкой
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => "Ошибка {$code}: {$message}"], JSON_UNESCAPED_UNICODE);
            exit;
        } else if (DEBUG) {
            die("Ошибка {$code}: {$message}");
        } else {
            // В продакшене показываем пользовательскую страницу ошибки
            header("HTTP/1.0 {$code}");
            include VIEWS_PATH . '/layouts/error.php';
            exit;
        }
    }
}