<?php
/**
 * Входная точка приложения
 */

// Запускаем буферизацию вывода для предотвращения смешивания HTML и JSON
ob_start();

// Обработка ошибок для AJAX-запросов
function handleAjaxErrors() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        set_exception_handler(function($e) {
            // Очищаем все буферы вывода
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка сервера: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        });
        
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            // Очищаем все буферы вывода
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка сервера: ' . $errstr
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }, E_ALL);
    }
}

// Регистрируем обработчик ошибок для AJAX-запросов
handleAjaxErrors();

// Подключаем конфигурацию
require_once 'config/config.php';

// Подключаем базу данных
require_once 'config/database.php';

// Подключаем утилиты
require_once 'utils/Logger.php';
require_once 'utils/Router.php';

// Инициализируем маршрутизатор
$router = new Router();

// Определяем маршруты
$router->addRoute('', 'DashboardController', 'index');
$router->addRoute('settings', 'SettingsController', 'index');
$router->addRoute('proxies', 'ProxiesController', 'index');
$router->addRoute('accounts', 'AccountsController', 'index');
$router->addRoute('parsing', 'ParsingController', 'index');
$router->addRoute('rewrite', 'RewriteController', 'index');

// Обрабатываем запрос
$router->dispatch();
