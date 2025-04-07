<?php
/**
 * Скрипт для автоматического реврайта контента и генерации изображений
 * 
 * Запускается через cron для регулярного реврайта собранного контента
 */

// Подключение необходимых файлов
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/GeminiApiClient.php';
require_once __DIR__ . '/../utils/ImageGenerationClient.php';
require_once __DIR__ . '/../utils/ImageStorageManager.php';
require_once __DIR__ . '/../utils/Logger.php';

// Инициализация логгера
Logger::info('Starting content rewriting process', 'rewrite_cron');

try {
    // Подключение к базе данных
    $db = Database::getInstance();
    
    // Получение настроек API Gemini
    $settings = [];
    $settingsRows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");

    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // Определяем провайдера API и проверяем API-ключ
    $aiProvider = $settings['ai_provider'] ?? 'gemini';
    $apiKey = '';

    if ($aiProvider === 'gemini') {
        $apiKey = $settings['gemini_api_key'] ?? '';
        if (empty($apiKey)) {
            Logger::error('Gemini API key not configured', 'rewrite_cron');
            exit;
        }
    } else if ($aiProvider === 'openrouter') {
        $apiKey = $settings['openrouter_api_key'] ?? '';
        if (empty($apiKey)) {
            Logger::error('OpenRouter API key not configured', 'rewrite_cron');
            exit;
        }
    } else {
        Logger::error('Unknown API provider: ' . $aiProvider, 'rewrite_cron');
        exit;
    }

    // Инициализация клиента API
    $geminiClient = new GeminiApiClient(
        $apiKey,
        $settings['gemini_model'] ?? 'gemini-pro',
        $aiProvider === 'openrouter' // Используем OpenRouter, если выбран этот провайдер
    );
    
    // Получение шаблонов для реврайта
    $rewriteTemplate = $settings['rewrite_template'] ?? 'Перепиши следующий текст, сохраняя смысл, но изменяя формулировки: {content}';
    
    // Получение минимальной длины контента для обработки
    $minContentLength = isset($settings['min_content_length']) ? (int)$settings['min_content_length'] : 100;
    
    // Получение максимального количества потоков для реврайта
    $maxThreads = isset($settings['max_rewrite_threads']) ? (int)$settings['max_rewrite_threads'] : 2;
    
    // Проверяем, включена ли генерация изображений
    $imageGenerationEnabled = isset($settings['image_generation_enabled']) && $settings['image_generation_enabled'] == '1';
    
    // Инициализация клиента для генерации изображений, если функция включена
    $imageGenerationClient = null;
    $imageStorageManager = null;
    
    if ($imageGenerationEnabled) {
        $huggingfaceApiKey = $settings['huggingface_api_key'] ?? '';
        if (empty($huggingfaceApiKey)) {
            Logger::warning('Hugging Face API key not configured, image generation will be skipped', 'rewrite_cron');
            $imageGenerationEnabled = false;
        } else {
            $imageGenerationModel = $settings['image_generation_model'] ?? 'stabilityai/stable-diffusion-3-medium-diffusers';
            $imageGenerationClient = new ImageGenerationClient($huggingfaceApiKey, $imageGenerationModel);
            $imageStorageManager = new ImageStorageManager($db);
            Logger::info('Image generation is enabled', 'rewrite_cron');
        }
    }
    
    // Получение контента для реврайта
    $content = $db->fetchAll("
        SELECT * FROM original_content 
        WHERE is_processed = 0 
        AND LENGTH(content) >= ? 
        ORDER BY parsed_at ASC 
        LIMIT ?
    ", [$minContentLength, $maxThreads]);
    
    if (empty($content)) {
        Logger::info('No new content to rewrite', 'rewrite_cron');
        exit;
    }
    
    Logger::info('Found ' . count($content) . ' items to rewrite', 'rewrite_cron');
    
    // Обработка каждого элемента контента
    foreach ($content as $item) {
        Logger::info('Processing content ID: ' . $item['id'] . ' - ' . $item['title'], 'rewrite_cron');
        
        try {
            // Подготовка контента для реврайта
            $contentToRewrite = $item['title'] . "\n\n" . $item['content'];
            
            // Отправка запроса на реврайт
            $response = $geminiClient->rewriteContent($contentToRewrite, $rewriteTemplate);
            
            if (!$response['success']) {
                Logger::error('Rewrite API error: ' . ($response['error'] ?? 'Unknown error'), 'rewrite_cron');
                
                // Пометка контента как проблемного, но не обработанного
                $db->update('original_content', [
                    'last_error' => 'API Error: ' . ($response['error'] ?? 'Unknown error'),
                    'error_count' => $item['error_count'] + 1 ?? 1
                ], 'id = ?', [$item['id']]);
                
                continue;
            }
            
            // Обработка ответа от API
            $rewrittenContent = $response['content'];
            
            // Если ответ пустой, пропускаем этот элемент
            if (empty($rewrittenContent)) {
                Logger::error('Empty API response', 'rewrite_cron');
                
                // Пометка контента как проблемного
                $db->update('original_content', [
                    'last_error' => 'Empty API response',
                    'error_count' => $item['error_count'] + 1 ?? 1
                ], 'id = ?', [$item['id']]);
                
                continue;
            }
            
            // Разделение заголовка и описания
            $parts = explode("\n\n", $rewrittenContent, 2);
            
            $rewrittenTitle = trim($parts[0]);
            $rewrittenDescription = isset($parts[1]) ? trim($parts[1]) : '';
            
            // Если не удалось разделить, используем оригинальный заголовок
            if (empty($rewrittenDescription)) {
                $rewrittenDescription = $rewrittenTitle;
                $rewrittenTitle = $item['title'];
            }
            
            // Сохранение реврайтнутого контента
            $data = [
                'original_id' => $item['id'],
                'title' => $rewrittenTitle,
                'content' => $rewrittenDescription,
                'rewrite_date' => date('Y-m-d H:i:s'),
                'status' => 'rewritten'
            ];
            
            $rewrittenId = $db->insert('rewritten_content', $data);
            
            if ($rewrittenId) {
                // Обновление статуса оригинального контента
                $db->update('original_content', [
                    'is_processed' => 1,
                    'last_error' => null,
                    'error_count' => 0
                ], 'id = ?', [$item['id']]);
                
                Logger::info('Content rewritten successfully, ID: ' . $rewrittenId, 'rewrite_cron');
                
                // Генерация изображения, если функция включена
                if ($imageGenerationEnabled && $imageGenerationClient && $imageStorageManager) {
                    try {
                        // Получаем шаблон для промпта изображения
                        $imagePromptTemplate = $settings['image_prompt_template'] ?? 'Create a professional, high-quality image that represents the following content: {content}';
                        
                        // Создаем промпт для генерации изображения на основе реврайтнутого контента
                        $imagePrompt = str_replace('{content}', $rewrittenTitle . '. ' . substr($rewrittenDescription, 0, 500), $imagePromptTemplate);
                        
                        // Получаем настройки размера изображения
                        $imageWidth = isset($settings['image_width']) ? (int)$settings['image_width'] : 512;
                        $imageHeight = isset($settings['image_height']) ? (int)$settings['image_height'] : 512;
                        
                        // Опции для генерации изображения
                        $imageOptions = [
                            'width' => $imageWidth,
                            'height' => $imageHeight,
                            'guidance_scale' => 7.5,
                            'num_inference_steps' => 30
                        ];
                        
                        Logger::info('Generating image for content ID: ' . $rewrittenId, 'rewrite_cron');
                        
                        // Генерируем изображение
                        $imageResult = $imageGenerationClient->generateImage($imagePrompt, $imageOptions);
                        
                        if ($imageResult['success'] && isset($imageResult['image_data'])) {
                            // Сохраняем изображение
                            $imageId = $imageStorageManager->saveGeneratedImage(
                                $rewrittenId,
                                $imageResult['image_data'],
                                $imagePrompt,
                                $imageWidth,
                                $imageHeight
                            );
                            
                            if ($imageId) {
                                Logger::info('Image generated and saved successfully, ID: ' . $imageId, 'rewrite_cron');
                            } else {
                                Logger::error('Failed to save generated image', 'rewrite_cron');
                            }
                        } else {
                            Logger::error('Image generation failed: ' . ($imageResult['error'] ?? 'Unknown error'), 'rewrite_cron');
                        }
                    } catch (Exception $e) {
                        Logger::error('Error during image generation: ' . $e->getMessage(), 'rewrite_cron');
                    }
                }
                
                // Автоматический постинг, если включен
                if (isset($settings['auto_posting']) && $settings['auto_posting'] == '1') {
                    schedulePostingIfEnabled($rewrittenId);
                }
            } else {
                Logger::error('Failed to save rewritten content', 'rewrite_cron');
            }
        } catch (Exception $e) {
            Logger::error('Error rewriting content ID ' . $item['id'] . ': ' . $e->getMessage(), 'rewrite_cron');
            
            // Обновление статуса на ошибку
            $db->update('original_content', [
                'last_error' => 'Exception: ' . $e->getMessage(),
                'error_count' => $item['error_count'] + 1 ?? 1
            ], 'id = ?', [$item['id']]);
        }
    }
    
    Logger::info('Content rewriting process completed successfully', 'rewrite_cron');
} catch (Exception $e) {
    Logger::error('Error during content rewriting: ' . $e->getMessage(), 'rewrite_cron');
}

/**
 * Планирует публикацию контента, если включен автопостинг
 * 
 * @param int $rewrittenId ID реврайтнутого контента
 */
function schedulePostingIfEnabled($rewrittenId) {
    global $db, $settings;
    
    try {
        // Получение активного аккаунта для постинга
        $account = $db->fetchOne("
            SELECT a.* FROM accounts a
            JOIN account_types at ON a.account_type_id = at.id
            WHERE a.is_active = 1
            ORDER BY a.last_used ASC
            LIMIT 1
        ");
        
        if (!$account) {
            Logger::warning('No active accounts available for auto-posting', 'rewrite_cron');
            return;
        }
        
        // Создание записи в таблице задач
        $taskId = $db->insert('scheduled_tasks', [
            'task_type' => 'posting',
            'entity_id' => $rewrittenId,
            'status' => 'pending',
            'scheduled_time' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($taskId) {
            Logger::info("Scheduled auto-posting for content ID: $rewrittenId to account ID: {$account['id']}", 'rewrite_cron');
        } else {
            Logger::error("Failed to schedule auto-posting for content ID: $rewrittenId", 'rewrite_cron');
        }
    } catch (Exception $e) {
        Logger::error('Error scheduling auto-posting: ' . $e->getMessage(), 'rewrite_cron');
    }
}
