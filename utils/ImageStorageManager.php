<?php
/**
 * Класс для управления хранением сгенерированных изображений
 */
class ImageStorageManager {
    private $db;
    private $uploadDir;
    
    /**
     * Конструктор класса
     * 
     * @param Database $db Экземпляр класса базы данных
     * @param string $uploadDir Директория для хранения изображений
     */
    public function __construct($db, $uploadDir = null) {
        $this->db = $db;
        
        // Если директория не указана, используем директорию по умолчанию
        if ($uploadDir === null) {
            $this->uploadDir = dirname(__DIR__) . '/uploads/images/';
        } else {
            $this->uploadDir = rtrim($uploadDir, '/') . '/';
        }
        
        // Создаем директорию, если она не существует
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Сохранение сгенерированного изображения
     * 
     * @param int $rewrittenId ID реврайтнутого контента
     * @param string $imageData Бинарные данные изображения
     * @param string $prompt Промпт, использованный для генерации
     * @param int $width Ширина изображения
     * @param int $height Высота изображения
     * @return int|false ID сохраненного изображения или false в случае ошибки
     */
    public function saveGeneratedImage($rewrittenId, $imageData, $prompt, $width = 512, $height = 512, $versionNumber = null) {
        try {
            // Генерируем уникальное имя файла
            $filename = 'img_' . $rewrittenId . '_' . ($versionNumber ? 'v' . $versionNumber . '_' : '') . time() . '_' . uniqid() . '.png';
            $filePath = $this->uploadDir . $filename;
            
            // Сохраняем изображение в файл
            if (file_put_contents($filePath, $imageData) === false) {
                Logger::error('Failed to save image file: ' . $filePath, 'image_storage');
                return false;
            }
            
            // Сохраняем информацию об изображении в базу данных
            $data = [
                'rewritten_id' => $rewrittenId,
                'image_path' => $filename,
                'prompt' => $prompt,
                'width' => $width,
                'height' => $height,
                'version_number' => $versionNumber,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $imageId = $this->db->insert('generated_images', $data);
            
            if (!$imageId) {
                Logger::error('Failed to save image data to database', 'image_storage');
                // Удаляем файл, если не удалось сохранить запись в БД
                @unlink($filePath);
                return false;
            }
            
            return $imageId;
        } catch (Exception $e) {
            Logger::error('Error saving generated image: ' . $e->getMessage(), 'image_storage');
            return false;
        }
    }
    
    /**
     * Получение информации об изображении по ID
     * 
     * @param int $imageId ID изображения
     * @return array|false Информация об изображении или false в случае ошибки
     */
    public function getImageById($imageId) {
        try {
            return $this->db->fetchOne("SELECT * FROM generated_images WHERE id = ?", [$imageId]);
        } catch (Exception $e) {
            Logger::error('Error getting image by ID: ' . $e->getMessage(), 'image_storage');
            return false;
        }
    }
    
    /**
     * Получение пути к файлу изображения
     * 
     * @param string $imagePath Относительный путь к изображению
     * @return string Полный путь к файлу изображения
     */
    public function getImageFilePath($imagePath) {
        return $this->uploadDir . $imagePath;
    }
    
    /**
     * Получение URL изображения
     * 
     * @param string $imagePath Относительный путь к изображению
     * @return string URL изображения
     */
    public function getImageUrl($imagePath) {
        // Формируем URL относительно корня сайта
        return '/uploads/images/' . $imagePath;
    }
    
    /**
     * Удаление изображения
     * 
     * @param int $imageId ID изображения
     * @return bool Результат удаления
     */
    public function deleteImage($imageId) {
        try {
            // Получаем информацию об изображении
            $image = $this->getImageById($imageId);
            
            if (!$image) {
                return false;
            }
            
            // Удаляем файл
            $filePath = $this->getImageFilePath($image['image_path']);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            // Удаляем запись из базы данных
            return $this->db->delete('generated_images', 'id = ?', [$imageId]);
        } catch (Exception $e) {
            Logger::error('Error deleting image: ' . $e->getMessage(), 'image_storage');
            return false;
        }
    }
    
    /**
     * Получение изображений для реврайтнутого контента
     * 
     * @param int $rewrittenId ID реврайтнутого контента
     * @return array Список изображений
     */
    public function getImagesForRewrittenContent($rewrittenId) {
        try {
            return $this->db->fetchAll("
                SELECT * FROM generated_images 
                WHERE rewritten_id = ? 
                ORDER BY version_number, created_at DESC
            ", [$rewrittenId]);
        } catch (Exception $e) {
            Logger::error('Error getting images for rewritten content: ' . $e->getMessage(), 'image_storage');
            return [];
        }
    }
}
