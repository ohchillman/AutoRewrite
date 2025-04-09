<?php
/**
 * Тестовый скрипт для проверки функциональности постинга в Twitter
 * с выбором аккаунта из базы данных
 */

// Определяем константу для CLI режима
define('CLI_MODE', true);

// Подключаем необходимые файлы
require_once __DIR__ . '/config/config.php';

// Явно подключаем класс Database
require_once __DIR__ . '/utils/Database.php';
require_once __DIR__ . '/utils/Logger.php';
require_once __DIR__ . '/utils/proxy/ProxyManager.php';
require_once __DIR__ . '/utils/proxy/SocialMediaClientFactory.php';

// Инициализация логгера
$logger = new Logger();
$logger->log('info', 'Starting Twitter posting test with account selection');

try {
    // Подключение к базе данных
    $db = Database::getInstance();
    
    // Проверяем наличие аккаунтов Twitter
    $twitterAccounts = $db->fetchAll("
        SELECT a.*, at.name as account_type_name 
        FROM accounts a
        JOIN account_types at ON a.account_type_id = at.id
        WHERE at.name = 'Twitter' AND a.is_active = 1
    ");
    
    if (empty($twitterAccounts)) {
        echo "Ошибка: Активные аккаунты Twitter не найдены.\n";
        echo "Пожалуйста, добавьте аккаунт Twitter через админку (http://localhost/accounts) и попробуйте снова.\n";
        exit(1);
    }
    
    echo "Найдено " . count($twitterAccounts) . " активных аккаунтов Twitter:\n";
    foreach ($twitterAccounts as $index => $account) {
        echo ($index + 1) . ". {$account['name']} (ID: {$account['id']})\n";
    }
    
    // Выбор аккаунта для тестирования
    if (isset($argv[1]) && is_numeric($argv[1]) && isset($twitterAccounts[$argv[1] - 1])) {
        $selectedAccount = $twitterAccounts[$argv[1] - 1];
    } else {
        // По умолчанию используем первый аккаунт
        $selectedAccount = $twitterAccounts[0];
    }
    
    echo "\nВыбран аккаунт: {$selectedAccount['name']} (ID: {$selectedAccount['id']})\n";
    
    // Инициализация менеджера прокси
    $proxyManager = new ProxyManager($db, $logger);
    
    // Инициализация фабрики клиентов социальных сетей
    $clientFactory = new SocialMediaClientFactory($db, $proxyManager, $logger);
    
    // Создаем клиент для выбранного аккаунта
    $twitterClient = $clientFactory->createClientForAccount($selectedAccount);
    
    if (!$twitterClient) {
        echo "Ошибка: Не удалось создать клиент Twitter для аккаунта {$selectedAccount['name']}.\n";
        exit(1);
    }
    
    // Тестовый твит с временной меткой для уникальности
    $tweetText = "Тестовый твит от AutoRewrite через аккаунт {$selectedAccount['name']}. Время: " . date('Y-m-d H:i:s');
    
    echo "\nОтправка твита: $tweetText\n";
    
    // Отправляем твит
    $result = $twitterClient->postTweet($tweetText);
    
    // Проверяем результат
    if (isset($result['error'])) {
        echo "Ошибка при отправке твита: " . $result['error'] . "\n";
        if (isset($result['code'])) {
            echo "Код ошибки: " . $result['code'] . "\n";
        }
    } else {
        echo "Твит успешно отправлен!\n";
        echo "ID твита: " . ($result['data']['id'] ?? 'неизвестно') . "\n";
    }
    
    echo "\nДетали ответа:\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "Произошла ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
