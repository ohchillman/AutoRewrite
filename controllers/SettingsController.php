<?php
/**
 * Контроллер для управления настройками
 */
class SettingsController extends BaseController {
    
    /**
     * Отображение страницы настроек
     */
    public function index() {
        // Получаем все настройки из базы данных
        $settings = $this->getAllSettings();
        
        // Отображаем представление
        $this->render('settings/index', [
            'title' => 'Настройки - AutoRewrite',
            'pageTitle' => 'Настройки системы',
            'currentPage' => 'settings',
            'layout' => 'main',
            'settings' => $settings
        ]);
    }
    
    /**
     * Сохранение настроек
     */
    public function save() {
        // Проверяем, что запрос отправлен методом POST
        if (!$this->isMethod('POST')) {
            return $this->handleAjaxError('Метод не поддерживается', 405);
        }
        
        try {
            // Получаем данные из POST
            $settings = $_POST['settings'] ?? [];
            
            // Проверяем наличие данных
            if (empty($settings)) {
                return $this->handleAjaxError('Нет данных для сохранения');
            }
            
            // Сохраняем настройки
            $success = true;
            $errors = [];
            
            foreach ($settings as $key => $value) {
                // Проверяем безопасность ключа
                $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
                
                // Проверяем, существует ли настройка
                $exists = $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM settings WHERE setting_key = ?", 
                    [$key]
                );
                
                if ($exists) {
                    // Обновляем существующую настройку
                    $result = $this->db->update(
                        'settings',
                        ['setting_value' => $value],
                        'setting_key = ?',
                        [$key]
                    );
                } else {
                    // Создаем новую настройку
                    $result = $this->db->insert(
                        'settings',
                        [
                            'setting_key' => $key,
                            'setting_value' => $value
                        ]
                    );
                }
                
                if (!$result) {
                    $success = false;
                    $errors[] = "Не удалось сохранить настройку '{$key}'";
                }
            }
            
            // Отправляем ответ
            if ($success) {
                return $this->handleSuccess('Настройки успешно сохранены', null, true);
            } else {
                return $this->handleAjaxError('Ошибка при сохранении настроек: ' . implode(', ', $errors));
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при сохранении настроек: ' . $e->getMessage(), 'settings');
            return $this->handleAjaxError('Ошибка при сохранении настроек: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Получение всех настроек
     * 
     * @return array Массив настроек
     */
    private function getAllSettings() {
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