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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/settings');
            return;
        }
        
        // Получаем данные из POST
        $settings = $_POST['settings'] ?? [];
        
        // Проверяем наличие данных
        if (empty($settings)) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Нет данных для сохранения'
                ]);
            } else {
                $_SESSION['error'] = 'Нет данных для сохранения';
                $this->redirect('/settings');
            }
            return;
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
        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => $success,
                'message' => $success ? 'Настройки успешно сохранены' : 'Ошибка при сохранении настроек: ' . implode(', ', $errors),
                'refresh' => $success
            ]);
        } else {
            if ($success) {
                $_SESSION['success'] = 'Настройки успешно сохранены';
            } else {
                $_SESSION['error'] = 'Ошибка при сохранении настроек: ' . implode(', ', $errors);
            }
            $this->redirect('/settings');
        }
    }
    
    /**
     * Получение всех настроек
     * 
     * @return array Массив настроек
     */
    private function getAllSettings() {
        $settings = $this->db->fetchAll("SELECT setting_key, setting_value FROM settings");
        
        // Преобразуем в ассоциативный массив
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }
        
        return $result;
    }
}
