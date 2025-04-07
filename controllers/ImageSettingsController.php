<?php
/**
 * Контроллер для управления настройками генерации изображений
 */
class ImageSettingsController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Отображение страницы настроек генерации изображений
     */
    public function index() {
        // Получение текущих настроек
        $settings = [];
        $settingsRows = $this->db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'huggingface_%' OR setting_key LIKE 'image_%'");
        
        foreach ($settingsRows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Отображение страницы настроек
        include VIEWS_PATH . '/admin/image_settings.php';
    }
    
    /**
     * Сохранение настроек генерации изображений
     */
    public function save() {
        // Сохранение настроек
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingsToSave = [
                'huggingface_api_key',
                'image_generation_model',
                'image_generation_enabled',
                'image_width',
                'image_height',
                'image_prompt_template'
            ];
            
            foreach ($settingsToSave as $key) {
                if (isset($_POST[$key])) {
                    $value = $_POST[$key];
                    
                    // Проверка существования настройки
                    $exists = $this->db->fetchColumn("SELECT COUNT(*) FROM settings WHERE setting_key = ?", [$key]);
                    
                    if ($exists) {
                        $this->db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                    } else {
                        $this->db->insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
                    }
                }
            }
            
            // Перенаправление обратно на страницу настроек
            header('Location: /image-settings?success=1');
            exit;
        }
    }
}
