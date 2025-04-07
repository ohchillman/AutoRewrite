<?php
/**
 * Класс для отображения изображений в постах
 */
class PostImageHelper {
    private $db;
    private $imageStorageManager;
    
    /**
     * Конструктор класса
     * 
     * @param Database $db Экземпляр класса базы данных
     */
    public function __construct($db) {
        $this->db = $db;
        $this->imageStorageManager = new ImageStorageManager($db);
    }
    
    /**
     * Получение HTML-кода для отображения изображения поста
     * 
     * @param int $postId ID поста
     * @param string $cssClass Дополнительные CSS-классы для изображения
     * @return string HTML-код изображения или пустая строка
     */
    public function getPostImageHtml($postId, $cssClass = 'img-fluid') {
        try {
            // Получаем информацию о посте
            $post = $this->db->fetchOne("SELECT image_id FROM posts WHERE id = ?", [$postId]);
            
            if (!$post || !isset($post['image_id']) || !$post['image_id']) {
                return '';
            }
            
            // Получаем информацию об изображении
            $image = $this->imageStorageManager->getImageById($post['image_id']);
            
            if (!$image) {
                return '';
            }
            
            // Формируем URL изображения
            $imageUrl = $this->imageStorageManager->getImageUrl($image['image_path']);
            
            // Получаем заголовок поста для alt-текста
            $postTitle = $this->db->fetchColumn("SELECT title FROM posts WHERE id = ?", [$postId]);
            $altText = htmlspecialchars($postTitle ?: 'Изображение поста');
            
            // Формируем HTML-код
            return '<div class="post-image"><img src="' . $imageUrl . '" alt="' . $altText . '" class="' . $cssClass . '"></div>';
        } catch (Exception $e) {
            Logger::error('Error getting post image HTML: ' . $e->getMessage(), 'post_image');
            return '';
        }
    }
    
    /**
     * Прикрепление изображения к посту
     * 
     * @param int $postId ID поста
     * @param int $imageId ID изображения
     * @return bool Результат операции
     */
    public function attachImageToPost($postId, $imageId) {
        try {
            // Проверяем существование изображения
            $image = $this->imageStorageManager->getImageById($imageId);
            
            if (!$image) {
                Logger::error('Image not found: ' . $imageId, 'post_image');
                return false;
            }
            
            // Обновляем запись поста
            $result = $this->db->update('posts', ['image_id' => $imageId], 'id = ?', [$postId]);
            
            return $result > 0;
        } catch (Exception $e) {
            Logger::error('Error attaching image to post: ' . $e->getMessage(), 'post_image');
            return false;
        }
    }
}
