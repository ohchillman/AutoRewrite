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
     * @param string $action Имя метода в контроллере
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
        $route = isset($segments[0]) ? $segments[0] : '';
        
        // Проверяем, существует ли такой маршрут
        if (isset($this->routes[$route])) {
            $controller = $this->routes[$route]['controller'];
            $action = $this->routes[$route]['action'];
            
            // Проверяем, есть ли дополнительные параметры в URL
            $params = array_slice($segments, 1);
            
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
            if ($route === '') {
                // Перенаправляем на главную страницу
                header('Location: /');
                exit;
            }
            
            $this->handleError(404, "Маршрут {$route} не найден");
        }
    }
    
    /**
     * Обработка ошибок
     * 
     * @param int $code Код ошибки
     * @param string $message Сообщение об ошибке
     */
    private function handleError($code, $message) {
        if (DEBUG) {
            die("Ошибка {$code}: {$message}");
        } else {
            // В продакшене показываем пользовательскую страницу ошибки
            header("HTTP/1.0 {$code}");
            include VIEWS_PATH . '/layouts/error.php';
            exit;
        }
    }
}
