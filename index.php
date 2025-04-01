<?php
/**
 * Входная точка приложения
 */

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
