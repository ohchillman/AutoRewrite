<?php
/**
 * Основной конфигурационный файл
 */

// Режим отладки (true для разработки, false для продакшена)
define('DEBUG', true);

// Настройки базы данных
define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost');
define('DB_NAME', getenv('DB_NAME') ? getenv('DB_NAME') : 'autorewrite');
define('DB_USER', getenv('DB_USER') ? getenv('DB_USER') : 'root');
define('DB_PASS', getenv('DB_PASS') ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ? getenv('DB_CHARSET') : 'utf8mb4');

// Настройки путей
define('BASE_PATH', dirname(__DIR__));
define('CONTROLLERS_PATH', BASE_PATH . '/controllers');
define('MODELS_PATH', BASE_PATH . '/models');
define('VIEWS_PATH', BASE_PATH . '/views');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UTILS_PATH', BASE_PATH . '/utils');
define('LOGS_PATH', BASE_PATH . '/logs');

// URL сайта
define('BASE_URL', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://' . $_SERVER['HTTP_HOST']);

// Настройки логирования
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'debug'); // debug, info, warning, error

// Настройки сессии
ini_set('session.cookie_httponly', 1);
session_start();

// Обработка ошибок
if (DEBUG) {
    // В режиме отладки не выводим ошибки напрямую для AJAX-запросов
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
    } else {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
    }
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
}

// Функция автозагрузки классов
spl_autoload_register(function ($class) {
    // Преобразуем имя класса в путь к файлу
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    
    // Проверяем различные пути для поиска класса
    $paths = [
        BASE_PATH . '/models/',
        BASE_PATH . '/controllers/',
        BASE_PATH . '/utils/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
