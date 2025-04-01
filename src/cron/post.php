<?php
/**
 * Скрипт для автоматической публикации контента в социальных сетях
 * 
 * Запускается через cron для регулярной публикации реврайтнутого контента
 */

// Подключение необходимых файлов
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/TwitterApiClient.php';
require_once __DIR__ . '/../utils/LinkedInApiClient.php';
require_once __DIR__ . '/../utils/YouTubeApiClient.php';
require_once __DIR__ . '/../utils/ThreadsSeleniumClient.php';
require_once __DIR__ . '/../utils/Logger.php';

// Инициализация логгера
$logger = new Logger();
$logger->log('info', 'Starting content posting process');

try {
    // Подключение к базе данных
    $db = Database::getInstance();
    
    // Получение настроек API
    $settings = $db->fetchOne("SELECT * FROM settings WHERE id = 1");
    
    if (empty($settings)) {
        $logger->log('error', 'API settings not configured');
        exit;
    }
    
    // Получение активных аккаунтов
    $accounts = $db->fetchAll("SELECT * FROM accounts WHERE active = 1");
    
    if (empty($accounts)) {
        $logger->log('warning', 'No active social media accounts found');
        exit;
    }
    
    // Получение реврайтнутого контента для публикации
    $content = $db->fetchAll("SELECT * FROM content WHERE status = 'rewritten' AND posted = 0 LIMIT 10");
    
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
                $result = false;
                
                switch ($account['type']) {
                    case 'twitter':
                        if (empty($settings['twitter_api_key']) || empty($settings['twitter_api_secret']) || 
                            empty($account['access_token']) || empty($account['access_token_secret'])) {
                            $logger->log('error', 'Twitter API credentials not configured for account ID: ' . $account['id']);
                            continue;
                        }
                        
                        $twitterClient = new TwitterApiClient(
                            $settings['twitter_api_key'],
                            $settings['twitter_api_secret'],
                            $account['access_token'],
                            $account['access_token_secret']
                        );
                        
                        $response = $twitterClient->postTweet($twitterContent);
                        $result = !isset($response['error']);
                        break;
                        
                    case 'linkedin':
                        if (empty($settings['linkedin_client_id']) || empty($settings['linkedin_client_secret']) || 
                            empty($account['access_token'])) {
                            $logger->log('error', 'LinkedIn API credentials not configured for account ID: ' . $account['id']);
                            continue;
                        }
                        
                        $linkedinClient = new LinkedInApiClient(
                            $settings['linkedin_client_id'],
                            $settings['linkedin_client_secret'],
                            $account['access_token']
                        );
                        
                        $response = $linkedinClient->postTextContent($fullContent);
                        $result = !isset($response['error']);
                        break;
                        
                    case 'youtube':
                        // Для YouTube нужно видео, поэтому пропускаем текстовый контент
                        $logger->log('info', 'Skipping YouTube posting for text content');
                        continue;
                        
                    case 'threads':
                        if (empty($account['username']) || empty($account['password'])) {
                            $logger->log('error', 'Threads credentials not configured for account ID: ' . $account['id']);
                            continue;
                        }
                        
                        $threadsClient = new ThreadsSeleniumClient(
                            $account['username'],
                            $account['password']
                        );
                        
                        $result = $threadsClient->login() && $threadsClient->postContent($fullContent);
                        $threadsClient->close();
                        break;
                        
                    default:
                        $logger->log('error', 'Unknown account type: ' . $account['type']);
                        continue;
                }
                
                if ($result) {
                    $logger->log('info', 'Content posted successfully to ' . $account['type'] . ' account: ' . $account['name']);
                    $postResults[$account['id']] = true;
                } else {
                    $logger->log('error', 'Failed to post content to ' . $account['type'] . ' account: ' . $account['name']);
                    $postResults[$account['id']] = false;
                }
            } catch (Exception $e) {
                $logger->log('error', 'Error posting to ' . $account['type'] . ' account ' . $account['name'] . ': ' . $e->getMessage());
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
