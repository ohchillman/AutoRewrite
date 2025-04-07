<?php
/**
 * Скрипт для тестирования функциональности генерации изображений
 */

// Подключение необходимых файлов
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/ImageGenerationClient.php';
require_once __DIR__ . '/utils/ImageStorageManager.php';
require_once __DIR__ . '/utils/Logger.php';

// Инициализация логгера
Logger::info('Starting image generation test', 'test');

try {
    // Подключение к базе данных
    $db = Database::getInstance();
    
    // Получение настроек API
    $settings = [];
    $settingsRows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");

    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Проверка наличия API ключа Hugging Face
    $huggingfaceApiKey = $settings['huggingface_api_key'] ?? '';
    if (empty($huggingfaceApiKey)) {
        echo "Hugging Face API key not configured. Please add it to the settings table.\n";
        echo "INSERT INTO settings (setting_key, setting_value) VALUES ('huggingface_api_key', 'YOUR_API_KEY');\n";
        exit(1);
    }
    
    // Получение модели для генерации изображений
    $imageGenerationModel = $settings['image_generation_model'] ?? 'stabilityai/stable-diffusion-3-medium-diffusers';
    
    echo "Using model: $imageGenerationModel\n";
    
    // Инициализация клиента для генерации изображений
    $imageGenerationClient = new ImageGenerationClient($huggingfaceApiKey, $imageGenerationModel);
    $imageStorageManager = new ImageStorageManager($db);
    
    // Тестовый промпт для генерации изображения
    $testPrompt = "A beautiful landscape with mountains and a lake, digital art style";
    
    echo "Generating image with prompt: $testPrompt\n";
    
    // Опции для генерации изображения
    $imageOptions = [
        'width' => 512,
        'height' => 512,
        'guidance_scale' => 7.5,
        'num_inference_steps' => 30
    ];
    
    // Генерация изображения
    $startTime = microtime(true);
    $imageResult = $imageGenerationClient->generateImage($testPrompt, $imageOptions);
    $endTime = microtime(true);
    
    $duration = round($endTime - $startTime, 2);
    echo "Image generation took $duration seconds\n";
    
    if ($imageResult['success'] && isset($imageResult['image_data'])) {
        echo "Image generated successfully\n";
        
        // Сохранение изображения во временный файл для тестирования
        $tempImagePath = __DIR__ . '/uploads/test_image_' . time() . '.png';
        
        if (file_put_contents($tempImagePath, $imageResult['image_data'])) {
            echo "Image saved to: $tempImagePath\n";
            
            // Проверка, существует ли таблица generated_images
            $tableExists = $db->fetchColumn("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'generated_images'
            ");
            
            if ($tableExists) {
                echo "Table 'generated_images' exists\n";
                
                // Проверка, есть ли реврайтнутый контент для тестирования
                $rewrittenContent = $db->fetchOne("
                    SELECT id FROM rewritten_content 
                    ORDER BY id DESC 
                    LIMIT 1
                ");
                
                if ($rewrittenContent) {
                    $rewrittenId = $rewrittenContent['id'];
                    echo "Using rewritten content ID: $rewrittenId for testing\n";
                    
                    // Сохранение изображения в базу данных
                    $imageId = $imageStorageManager->saveGeneratedImage(
                        $rewrittenId,
                        $imageResult['image_data'],
                        $testPrompt,
                        $imageOptions['width'],
                        $imageOptions['height']
                    );
                    
                    if ($imageId) {
                        echo "Image saved to database with ID: $imageId\n";
                        
                        // Получение информации о сохраненном изображении
                        $savedImage = $imageStorageManager->getImageById($imageId);
                        if ($savedImage) {
                            echo "Retrieved image from database:\n";
                            echo "Path: " . $savedImage['image_path'] . "\n";
                            echo "Created at: " . $savedImage['created_at'] . "\n";
                            
                            // Удаление тестового изображения из базы данных
                            if ($imageStorageManager->deleteImage($imageId)) {
                                echo "Test image deleted from database\n";
                            } else {
                                echo "Failed to delete test image from database\n";
                            }
                        } else {
                            echo "Failed to retrieve image from database\n";
                        }
                    } else {
                        echo "Failed to save image to database\n";
                    }
                } else {
                    echo "No rewritten content found for testing\n";
                }
            } else {
                echo "Table 'generated_images' does not exist. Please run the database update script:\n";
                echo "mysql -u [username] -p [database] < database/update_schema.sql\n";
            }
            
            // Удаление временного файла
            unlink($tempImagePath);
            echo "Temporary test image file deleted\n";
        } else {
            echo "Failed to save image to file\n";
        }
    } else {
        echo "Image generation failed: " . ($imageResult['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "Image generation test completed\n";
} catch (Exception $e) {
    echo "Error during image generation test: " . $e->getMessage() . "\n";
    Logger::error('Error during image generation test: ' . $e->getMessage(), 'test');
}
