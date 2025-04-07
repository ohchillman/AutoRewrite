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
Logger::info('Starting content parsing process', 'parse_cron');

try {
    // Подключение к базе данных
    $db = Database::getInstance();
    
    // Получение списка источников контента
    $sources = $db->fetchAll("SELECT * FROM parsing_sources WHERE is_active = 1");
    
    if (empty($sources)) {
        Logger::warning('No active content sources found', 'parse_cron');
        exit;
    }
    
    Logger::info('Found ' . count($sources) . ' active sources', 'parse_cron');
    
    // Инициализация парсера
    $parser = new ContentParser();
    
    // Обработка каждого источника
    foreach ($sources as $source) {
        Logger::info("Processing source: {$source['name']} ({$source['url']})", 'parse_cron');
        
        // Декодируем дополнительные настройки
        $additionalSettings = !empty($source['additional_settings']) ? 
            json_decode($source['additional_settings'], true) : [];
        
        // Определяем настройки для парсинга
        $limit = $additionalSettings['items'] ?? 20;
        $fetchFullContent = isset($additionalSettings['full_content']) ? 
            (bool)$additionalSettings['full_content'] : false;
        
        // Настройка прокси, если он указан
        $proxyConfig = null;
        if (!empty($source['proxy_id'])) {
            $proxy = $db->fetchOne("SELECT * FROM proxies WHERE id = ? AND is_active = 1", [$source['proxy_id']]);
            if ($proxy) {
                $proxyAuth = '';
                if (!empty($proxy['username']) && !empty($proxy['password'])) {
                    $proxyAuth = $proxy['username'] . ':' . $proxy['password'] . '@';
                }
                $proxyConfig = $proxy['protocol'] . '://' . $proxyAuth . $proxy['ip'] . ':' . $proxy['port'];
                Logger::info("Using proxy: {$proxyConfig}", 'parse_cron');
            }
        }
        
        $items = [];
        
        // Парсинг в зависимости от типа источника
        switch ($source['source_type']) {
            case 'rss':
                Logger::info("Parsing RSS feed", 'parse_cron');
                $items = $parser->parseRss($source['url'], $limit, $fetchFullContent);
                break;
            case 'blog':
                Logger::info("Parsing blog/news website", 'parse_cron');
                $selectors = isset($additionalSettings['selectors']) ? 
                    $additionalSettings['selectors'] : [];
                $items = $parser->parseBlog($source['url'], $selectors, $limit, $fetchFullContent);
                break;
            default:
                Logger::warning("Unknown source type: {$source['source_type']}", 'parse_cron');
                continue;
        }
        
        // Проверяем на ошибки
        if (isset($items['error'])) {
            Logger::error("Error parsing source {$source['name']}: {$items['error']}", 'parse_cron');
            continue;
        }
        
        Logger::info('Found ' . count($items) . ' items from source: ' . $source['name'], 'parse_cron');
        
        // Сохранение полученных записей в базу данных
        $savedCount = 0;
        foreach ($items as $item) {
            // Проверка на пустые или невалидные данные
            if (empty($item['title']) || empty($item['link'])) {
                Logger::warning("Skipping item with empty title or link", 'parse_cron');
                continue;
            }
            
            // Проверка, существует ли уже такая запись
            $exists = $db->fetchColumn(
                "SELECT COUNT(*) FROM original_content WHERE url = ? OR (title = ? AND source_id = ?)", 
                [$item['link'], $item['title'], $source['id']]
            );
            
            if ($exists) {
                Logger::debug("Item already exists: " . $item['title'], 'parse_cron');
                continue;
            }
            
            try {
                // Подготовка данных для сохранения
                $data = [
                    'source_id' => $source['id'],
                    'title' => $item['title'],
                    'content' => $item['description'],
                    'url' => $item['link'],
                    'author' => $item['author'] ?? '',
                    'published_date' => date('Y-m-d H:i:s', $item['timestamp']),
                    'parsed_at' => date('Y-m-d H:i:s'),
                    'is_processed' => 0,
                    'media_urls' => $this->extractMediaUrls($item['description'])
                ];
                
                // Сохраняем контент
                $id = $db->insert('original_content', $data);
                
                if ($id) {
                    $savedCount++;
                    Logger::info("Saved new item with ID: {$id}, Title: {$item['title']}", 'parse_cron');
                } else {
                    Logger::warning("Failed to save item: {$item['title']}", 'parse_cron');
                }
            } catch (Exception $e) {
                Logger::error("Error saving item: " . $e->getMessage(), 'parse_cron');
            }
        }
        
        // Обновляем время последнего парсинга
        $db->update('parsing_sources', 
            ['last_parsed' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$source['id']]
        );
        
        Logger::info("Saved {$savedCount} new items from source {$source['name']}", 'parse_cron');
    }
    
    Logger::info('Content parsing process completed successfully', 'parse_cron');
} catch (Exception $e) {
    Logger::error('Error during content parsing: ' . $e->getMessage(), 'parse_cron');
}

/**
 * Извлекает URLs медиа-контента из HTML
 * 
 * @param string $content HTML-контент
 * @return string JSON-строка с URLs изображений
 */
function extractMediaUrls($content) {
    $mediaUrls = [];
    
    // Извлечение URL изображений
    preg_match_all('/<img[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', $content, $matches);
    if (isset($matches['src']) && !empty($matches['src'])) {
        $mediaUrls['images'] = array_values(array_unique($matches['src']));
    }
    
    // Можно добавить извлечение других типов медиа (видео, аудио)
    
    return !empty($mediaUrls) ? json_encode($mediaUrls) : null;
}