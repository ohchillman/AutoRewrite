<?php
/**
 * Контроллер для управления настройками генерации изображений
 */
class ImageSettingsController extends BaseController {
    
    /**
     * Отображение страницы настроек генерации изображений
     */
    public function index() {
        // Получение текущих настроек
        $settings = $this->getSettings();
        
        // Отображаем представление
        $this->render('image-settings/index', [
            'title' => 'Настройки генерации изображений - AutoRewrite',
            'pageTitle' => 'Настройки генерации изображений',
            'currentPage' => 'image-settings',
            'layout' => 'main',
            'settings' => $settings
        ]);
    }
    
    /**
     * Метод для очистки временных изображений
     */
    public function clearTempImages() {
        try {
            // Путь к папке с временными изображениями
            $tempDir = __DIR__ . '/../uploads/temp/';
            
            // Проверяем существование директории
            if (!file_exists($tempDir)) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Папка с временными изображениями не существует или уже пуста'
                ]);
            }
            
            // Получаем список файлов из директории
            $files = glob($tempDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            $countDeleted = 0;
            
            // Удаляем каждый файл
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        $countDeleted++;
                    }
                }
            }
            
            Logger::info("Удалено {$countDeleted} временных изображений", 'image_settings');
            
            return $this->jsonResponse([
                'success' => true,
                'message' => "Успешно удалено {$countDeleted} временных изображений"
            ]);
        } catch (Exception $e) {
            Logger::error('Ошибка при очистке временных изображений: ' . $e->getMessage(), 'image_settings');
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Ошибка при очистке папки: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Сохранение настроек генерации изображений
     */
    public function save() {
        try {
            // Проверяем метод запроса
            if (!$this->isMethod('POST')) {
                return $this->handleAjaxError('Метод не поддерживается');
            }
            
            // Получаем данные из POST
            $settings = $this->post('settings', []);
            
            if (empty($settings)) {
                return $this->handleAjaxError('Пустые данные');
            }
            
            // Начинаем транзакцию
            $this->db->getConnection()->beginTransaction();
            
            try {
                // Сохраняем каждую настройку
                foreach ($settings as $key => $value) {
                    // Проверяем существует ли настройка
                    $exists = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM settings WHERE setting_key = ?", 
                        [$key]
                    );
                    
                    if ($exists) {
                        // Обновляем существующую настройку
                        $this->db->update(
                            'settings', 
                            ['setting_value' => $value], 
                            'setting_key = ?', 
                            [$key]
                        );
                    } else {
                        // Создаем новую настройку
                        $this->db->insert(
                            'settings', 
                            [
                                'setting_key' => $key,
                                'setting_value' => $value
                            ]
                        );
                    }
                }
                
                // Фиксируем транзакцию
                $this->db->getConnection()->commit();
                
                // Сохраняем сообщение об успехе и перенаправляем
                $_SESSION['success'] = 'Настройки успешно сохранены';
                
                return $this->handleSuccess('Настройки успешно сохранены', '/image-settings');
            } catch (Exception $e) {
                // Отменяем транзакцию в случае ошибки
                $this->db->getConnection()->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при сохранении настроек: ' . $e->getMessage(), 'settings');
            return $this->handleAjaxError('Ошибка при сохранении настроек: ' . $e->getMessage());
        }
    }
    
    /**
     * Получение настроек
     * 
     * @return array Массив настроек
     */
    private function getSettings() {
        try {
            $settings = $this->db->fetchAll("SELECT setting_key, setting_value FROM settings");
            
            // Преобразуем в ассоциативный массив
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting['setting_key']] = $setting['setting_value'];
            }
            
            return $result;
        } catch (Exception $e) {
            Logger::error('Ошибка при получении настроек: ' . $e->getMessage(), 'settings');
            return [];
        }
    }
}
