<?php
/**
 * Скрипт для автоматической публикации контента в социальных сетях
 * 
 * Запускается через cron для регулярной публикации реврайтнутого контента
 * Использует прокси для всех взаимодействий с аккаунтами
 */

// Подключение необходимых файлов
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/proxy/ProxyManager.php';
require_once __DIR__ . '/../utils/proxy/SocialMediaClientFactory.php';
require_once __DIR__ . '/../utils/Logger.php';

// Инициализация логгера
$logger = new Logger();
$logger->log('info', 'Starting content posting process with proxy integration');

try {
    // Подключение к базе данных
    $db = Database::getInstance();
    
    // Инициализация менеджера прокси
    $proxyManager = new ProxyManager($db, $logger);
    
    // Инициализация фабрики клиентов социальных сетей
    $clientFactory = new SocialMediaClientFactory($db, $proxyManager, $logger);
    
    // Получение настроек API
    $settings = $db->fetchOne("SELECT * FROM settings WHERE id = 1");
    
    if (empty($settings)) {
        $logger->log('error', 'API settings not configured');
        exit;
    }
    
    // Получение активных аккаунтов с информацией о типе и прокси
    $accounts = $db->fetchAll("
        SELECT a.*, at.name as account_type_name, p.ip as proxy_ip, p.port as proxy_port, 
               p.protocol as proxy_protocol, p.username as proxy_username, p.password as proxy_password
        FROM accounts a
        JOIN account_types at ON a.account_type_id = at.id
        LEFT JOIN proxies p ON a.proxy_id = p.id
        WHERE a.is_active = 1
    ");
    
    if (empty($accounts)) {
        $logger->log('warning', 'No active social media accounts found');
        exit;
    }
    
    $logger->log('info', 'Found ' . count($accounts) . ' active accounts');
    
    // Получение реврайтнутого контента для публикации
    $content = $db->fetchAll("
        SELECT * FROM content 
        WHERE status = 'rewritten' AND posted = 0 
        ORDER BY id ASC
        LIMIT 10
    ");
    
    if (empty($content)) {
        $logger->log('info', 'No rewritten content to post');
        exit;
    }
    
    $logger->log('info', 'Found ' . count($content) . ' items to post');
    
    // Обработка каждого элемента контента
    foreach ($content as $item) {
        $logger->log('info', 'Processing content ID: ' . $item['id'] . ' - ' . $item['rewritten_title']);
        
        // Подготовка контента для публикации
        $postTitle = $item['rewritten_title'];
        $postContent = $item['rewritten_description'];
        $fullContent = $postTitle . "\n\n" . $postContent;
        
        // Сокращение контента для Twitter
        $twitterContent = strlen($fullContent) > 280 ? substr($fullContent, 0, 277) . '...' : $fullContent;
        
        // Публикация в каждый активный аккаунт
        $postResults = [];
        
        foreach ($accounts as $account) {
            try {
                $logger->log('info', "Attempting to post to {$account['account_type_name']} account: {$account['name']} (ID: {$account['id']})");
                
                // Проверяем наличие прокси для аккаунта
                if (!empty($account['proxy_id'])) {
                    $logger->log('info', "Account uses proxy: {$account['proxy_ip']}:{$account['proxy_port']}");
                } else {
                    $logger->log('info', "Account does not use proxy");
                }
                
                // Создаем клиент для аккаунта с использованием фабрики
                $client = $clientFactory->createClientForAccount($account);
                
                if (!$client) {
                    $logger->log('error', "Failed to create client for {$account['account_type_name']} account: {$account['name']}");
                    $postResults[$account['id']] = false;
                    continue;
                }
                
                $result = false;
                $accountType = strtolower($account['account_type_name']);
                
                // Публикация контента в зависимости от типа аккаунта
                switch ($accountType) {
                    case 'twitter':
                        $response = $client->postTweet($twitterContent);
                        $result = !isset($response['error']);
                        if (!$result && isset($response['error'])) {
                            $logger->log('error', "Twitter API error: " . json_encode($response['error']));
                        }
                        break;
                        
                    case 'linkedin':
                        $response = $client->postTextContent($fullContent);
                        $result = !isset($response['error']);
                        if (!$result && isset($response['error'])) {
                            $logger->log('error', "LinkedIn API error: " . json_encode($response['error']));
                        }
                        break;
                        
                    case 'youtube':
                        // Для YouTube нужно видео, поэтому пропускаем текстовый контент
                        $logger->log('info', 'Skipping YouTube posting for text content');
                        continue;
                        
                    case 'threads':
                        // Для Threads используем Selenium клиент
                        $result = $client->login() && $client->postContent($fullContent);
                        $client->close();
                        break;
                        
                    default:
                        $logger->log('error', "Unknown account type: {$accountType}");
                        continue;
                }
                
                if ($result) {
                    $logger->log('info', "Content posted successfully to {$accountType} account: {$account['name']}");
                    $postResults[$account['id']] = true;
                } else {
                    $logger->log('error', "Failed to post content to {$accountType} account: {$account['name']}");
                    $postResults[$account['id']] = false;
                }
            } catch (Exception $e) {
                $logger->log('error', "Error posting to {$account['account_type_name']} account {$account['name']}: " . $e->getMessage());
                $postResults[$account['id']] = false;
            }
        }
        
        // Обновление статуса контента
        $successCount = count(array_filter($postResults));
        
        if ($successCount > 0) {
            // Сохранение результатов публикации
            $data = [
                'posted' => 1,
                'posted_at' => date('Y-m-d H:i:s'),
                'post_results' => json_encode($postResults)
            ];
            
            $db->update('content', $data, 'id = ?', [$item['id']]);
            $logger->log('info', 'Content ID ' . $item['id'] . ' posted to ' . $successCount . ' accounts');
        } else {
            $logger->log('warning', 'Content ID ' . $item['id'] . ' failed to post to any account');
        }
    }
    
    $logger->log('info', 'Content posting process completed successfully');
} catch (Exception $e) {
    $logger->log('error', 'Error during content posting: ' . $e->getMessage());
}
