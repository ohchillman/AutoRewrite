<?php
/**
 * Скрипт для автоматического реврайта контента
 * 
 * Запускается через cron для регулярного реврайта собранного контента
 */

// Подключение необходимых файлов
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/MakeApiClient.php';
require_once __DIR__ . '/../utils/Logger.php';

// Инициализация логгера
$logger = new Logger();
$logger->log('info', 'Starting content rewriting process');

try {
    // Подключение к базе данных
    $db = Database::getInstance();
    
    // Получение настроек API Make.com
    $settings = $db->fetchOne("SELECT * FROM settings WHERE id = 1");
    
    if (empty($settings) || empty($settings['make_api_key'])) {
        $logger->log('error', 'Make.com API key not configured');
        exit;
    }
    
    // Инициализация клиента Make.com API
    $makeClient = new MakeApiClient($settings['make_api_key']);
    
    // Получение шаблонов для реврайта
    $templates = $db->fetchAll("SELECT * FROM templates WHERE active = 1");
    
    if (empty($templates)) {
        $logger->log('warning', 'No active rewrite templates found');
        exit;
    }
    
    // Получение контента для реврайта
    $content = $db->fetchAll("SELECT * FROM content WHERE status = 'new' LIMIT 10");
    
    if (empty($content)) {
        $logger->log('info', 'No new content to rewrite');
        exit;
    }
    
    $logger->log('info', 'Found ' . count($content) . ' items to rewrite');
    
    // Обработка каждого элемента контента
    foreach ($content as $item) {
        $logger->log('info', 'Processing content ID: ' . $item['id'] . ' - ' . $item['title']);
        
        // Выбор случайного шаблона для реврайта
        $template = $templates[array_rand($templates)];
        
        try {
            // Подготовка контента для реврайта
            $contentToRewrite = $item['title'] . "\n\n" . $item['description'];
            
            // Отправка запроса на реврайт
            $response = $makeClient->rewriteContent($contentToRewrite, $template['template']);
            
            if (isset($response['error'])) {
                $logger->log('error', 'Rewrite API error: ' . $response['error']);
                continue;
            }
            
            // Обработка ответа от API
            if (isset($response['content'])) {
                // Разделение заголовка и описания
                $rewrittenContent = $response['content'];
                $parts = explode("\n\n", $rewrittenContent, 2);
                
                $rewrittenTitle = $parts[0];
                $rewrittenDescription = isset($parts[1]) ? $parts[1] : '';
                
                // Сохранение реврайтнутого контента
                $data = [
                    'rewritten_title' => $rewrittenTitle,
                    'rewritten_description' => $rewrittenDescription,
                    'template_id' => $template['id'],
                    'status' => 'rewritten',
                    'rewritten_at' => date('Y-m-d H:i:s')
                ];
                
                $db->update('content', $data, 'id = ?', [$item['id']]);
                $logger->log('info', 'Content rewritten successfully, ID: ' . $item['id']);
            } else {
                $logger->log('error', 'Invalid API response format');
            }
        } catch (Exception $e) {
            $logger->log('error', 'Error rewriting content ID ' . $item['id'] . ': ' . $e->getMessage());
            
            // Обновление статуса на ошибку
            $db->update('content', ['status' => 'error'], 'id = ?', [$item['id']]);
        }
    }
    
    $logger->log('info', 'Content rewriting process completed successfully');
} catch (Exception $e) {
    $logger->log('error', 'Error during content rewriting: ' . $e->getMessage());
}
