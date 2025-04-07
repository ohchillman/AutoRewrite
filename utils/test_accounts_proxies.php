<?php
/**
 * Тестовый скрипт для проверки функциональности аккаунтов с прокси
 * 
 * Этот скрипт проверяет:
 * 1. Работу ProxyManager
 * 2. Работу AccountVerifier
 * 3. Работу SocialMediaClientFactory
 */

// Подключение необходимых файлов
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/proxy/ProxyManager.php';
require_once __DIR__ . '/../utils/proxy/AccountVerifier.php';
require_once __DIR__ . '/../utils/proxy/SocialMediaClientFactory.php';
require_once __DIR__ . '/../utils/Logger.php';

// Инициализация логгера
$logger = new Logger();
$logger->log('info', 'Starting account and proxy testing');

try {
    // Подключение к базе данных
    $db = Database::getInstance();
    
    // Инициализация менеджера прокси
    $proxyManager = new ProxyManager($db, $logger);
    
    // Инициализация верификатора аккаунтов
    $accountVerifier = new AccountVerifier($db, $proxyManager, $logger);
    
    // Инициализация фабрики клиентов социальных сетей
    $clientFactory = new SocialMediaClientFactory($db, $proxyManager, $logger);
    
    // Получение всех активных прокси
    $proxies = $proxyManager->getAllActiveProxies();
    $logger->log('info', 'Found ' . count($proxies) . ' active proxies');
    
    // Тестирование каждого прокси
    foreach ($proxies as $proxy) {
        $logger->log('info', "Testing proxy: {$proxy['ip']}:{$proxy['port']}");
        
        $proxyCheck = $proxyManager->checkProxy($proxy);
        
        if ($proxyCheck['success']) {
            $logger->log('info', "Proxy working: {$proxy['ip']}:{$proxy['port']} - External IP: {$proxyCheck['details']}");
            
            // Обновляем статус прокси
            $proxyManager->updateProxyStatus($proxy['id'], true, $proxyCheck['details']);
        } else {
            $logger->log('warning', "Proxy not working: {$proxy['ip']}:{$proxy['port']} - {$proxyCheck['details']}");
            
            // Обновляем статус прокси
            $proxyManager->updateProxyStatus($proxy['id'], false, $proxyCheck['details']);
        }
    }
    
    // Получение всех активных аккаунтов
    $accounts = $db->fetchAll("
        SELECT a.*, at.name as account_type_name 
        FROM accounts a
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.is_active = 1
    ");
    
    $logger->log('info', 'Found ' . count($accounts) . ' active accounts');
    
    // Тестирование каждого аккаунта
    foreach ($accounts as $account) {
        $logger->log('info', "Testing account: {$account['name']} (Type: {$account['account_type_name']})");
        
        // Получаем прокси для аккаунта
        $proxy = $proxyManager->getProxyForAccount($account);
        
        if ($proxy) {
            $logger->log('info', "Account uses proxy: {$proxy['ip']}:{$proxy['port']}");
        } else {
            $logger->log('info', "Account does not use proxy");
        }
        
        // Проверяем аккаунт
        $verifyResult = $accountVerifier->verifyAccount($account);
        
        if ($verifyResult['success']) {
            $logger->log('info', "Account verification successful: {$verifyResult['message']}");
        } else {
            $logger->log('warning', "Account verification failed: {$verifyResult['message']}");
        }
        
        // Создаем клиент для аккаунта
        $client = $clientFactory->createClientForAccount($account);
        
        if ($client) {
            $logger->log('info', "Client created successfully for account: {$account['name']}");
        } else {
            $logger->log('warning', "Failed to create client for account: {$account['name']}");
        }
    }
    
    $logger->log('info', 'Account and proxy testing completed successfully');
} catch (Exception $e) {
    $logger->log('error', 'Error during testing: ' . $e->getMessage());
}
