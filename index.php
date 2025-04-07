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

// Настройка логирования для отладки AJAX
Logger::debug('Начало выполнения запроса: ' . $_SERVER['REQUEST_URI'], 'ajax');
Logger::debug('Метод запроса: ' . $_SERVER['REQUEST_METHOD'], 'ajax');
Logger::debug('isAjax: ' . (isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? 'true' : 'false'), 'ajax');

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
$router->addRoute('settings/save', 'SettingsController', 'save');

// Маршруты для настроек генерации изображений
$router->addRoute('image-settings', 'ImageSettingsController', 'index');
$router->addRoute('image-settings/save', 'ImageSettingsController', 'save');
$router->addRoute('image-settings/clearTemp', 'ImageSettingsController', 'clearTempImages');

// Маршруты для тестирования генерации изображений
$router->addRoute('image-test', 'ImageTestController', 'index');
$router->addRoute('image-test/generate', 'ImageTestController', 'generate');

$router->addRoute('proxies', 'ProxiesController', 'index');
$router->addRoute('proxies/add', 'ProxiesController', 'add');
$router->addRoute('proxies/delete', 'ProxiesController', 'delete');
$router->addRoute('proxies/check', 'ProxiesController', 'check');
$router->addRoute('proxies/toggle', 'ProxiesController', 'toggle');
$router->addRoute('proxies/changeIp', 'ProxiesController', 'changeIp');
$router->addRoute('proxies/edit', 'ProxiesController', 'edit');

$router->addRoute('accounts', 'AccountsController', 'index');
$router->addRoute('test', 'TestController', 'index');

$router->addRoute('rewrite', 'RewriteController', 'index');
$router->addRoute('rewrite/view', 'RewriteController', 'view');
$router->addRoute('rewrite/process', 'RewriteController', 'process');
$router->addRoute('rewrite/post', 'RewriteController', 'publishPost');
$router->addRoute('rewrite/delete', 'RewriteController', 'delete');
$router->addRoute('rewrite/deleteVersion', 'RewriteController', 'deleteVersion');
$router->addRoute('rewrite/generateImage', 'RewriteController', 'generateImage');

$router->addRoute('parsing', 'ParsingController', 'index');
$router->addRoute('parsing/add', 'ParsingController', 'add');
$router->addRoute('parsing/edit', 'ParsingController', 'edit');
$router->addRoute('parsing/delete', 'ParsingController', 'delete');
$router->addRoute('parsing/toggle', 'ParsingController', 'toggle');
$router->addRoute('parsing/parse', 'ParsingController', 'parse');



// Обрабатываем запрос
$router->dispatch();