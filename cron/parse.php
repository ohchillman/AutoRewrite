<?php
/**
 * Скрипт для автоматического парсинга контента
 * 
 * Запускается через cron для регулярного сбора контента из настроенных источников
 */

// Подключение необходимых файлов
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/ContentParser.php';
require_once __DIR__ . '/../utils/Logger.php';

// Инициализация логгера
$logger = new Logger();
$logger->log('info', 'Starting content parsing process');

try {
    // Подключение к базе данных
    $db = Database::getInstance();
    
    // Получение списка источников контента
    $sources = $db->fetchAll("SELECT * FROM sources WHERE active = 1");
    
    if (empty($sources)) {
        $logger->log('warning', 'No active content sources found');
        exit;
    }
    
    // Инициализация парсера
    $parser = new ContentParser();
    
    // Обработка каждого источника
    foreach ($sources as $source) {
        $logger->log('info', 'Processing source: ' . $source['name'] . ' (' . $source['url'] . ')');
        
        $items = [];
        
        // Парсинг в зависимости от типа источника
        switch ($source['type']) {
            case 'rss':
                $items = $parser->parseRss($source['url'], $source['limit']);
                break;
            case 'blog':
                $selectors = json_decode($source['selectors'], true) ?: [];
                $items = $parser->parseBlog($source['url'], $selectors, $source['limit']);
                break;
            case 'twitter':
            case 'facebook':
            case 'instagram':
            case 'linkedin':
                $items = $parser->parseSocialMedia($source['url'], $source['type'], $source['limit']);
                break;
            default:
                $logger->log('error', 'Unknown source type: ' . $source['type']);
                continue;
        }
        
        $logger->log('info', 'Found ' . count($items) . ' items from source: ' . $source['name']);
        
        // Сохранение полученных записей в базу данных
        foreach ($items as $item) {
            // Проверка, существует ли уже такая запись
            $exists = $db->fetchOne(
                "SELECT id FROM content WHERE source_id = ? AND link = ?",
                [$source['id'], $item['link']]
            );
            
            if ($exists) {
                $logger->log('debug', 'Item already exists: ' . $item['title']);
                continue;
            }
            
            // Сохранение новой записи
            $data = [
                'source_id' => $source['id'],
                'title' => $item['title'],
                'description' => $item['description'],
                'link' => $item['link'],
                'pub_date' => date('Y-m-d H:i:s', $item['timestamp']),
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'new'
            ];
            
            $id = $db->insert('content', $data);
            $logger->log('info', 'Saved new item with ID: ' . $id);
        }
    }
    
    $logger->log('info', 'Content parsing process completed successfully');
} catch (Exception $e) {
    $logger->log('error', 'Error during content parsing: ' . $e->getMessage());
}
