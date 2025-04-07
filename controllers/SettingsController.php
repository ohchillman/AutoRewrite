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
    // public function save() {
    //     // Проверяем, что запрос отправлен методом POST
    //     if (!$this->isMethod('POST')) {
    //         return $this->handleAjaxError('Метод не поддерживается', 405);
    //     }
        
    //     try {
    //         // Получаем данные из POST
    //         $settings = $_POST['settings'] ?? [];
            
    //         // Проверяем наличие данных
    //         if (empty($settings)) {
    //             return $this->handleAjaxError('Нет данных для сохранения');
    //         }
            
    //         // Включаем режим отладки для этой операции
    //         Logger::debug('Начало сохранения настроек. Полученные данные: ' . print_r($settings, true), 'settings');
            
    //         // Сохраняем настройки
    //         $success = true;
    //         $errors = [];
            
    //         foreach ($settings as $key => $value) {
    //             // Проверяем безопасность ключа (используем только буквы, цифры и подчеркивания)
    //             $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
                
    //             // Проверяем, существует ли настройка
    //             $exists = $this->db->fetchColumn(
    //                 "SELECT COUNT(*) FROM settings WHERE setting_key = ?", 
    //                 [$key]
    //             );
                
    //             Logger::debug("Проверка существования настройки '{$key}': " . ($exists ? 'Существует' : 'Не существует'), 'settings');
                
    //             try {
    //                 if ($exists) {
    //                     // Обновляем существующую настройку
    //                     Logger::debug("Обновление настройки '{$key}' на значение '{$value}'", 'settings');
                        
    //                     $result = $this->db->query(
    //                         "UPDATE settings SET setting_value = ? WHERE setting_key = ?",
    //                         [$value, $key]
    //                     );
                        
    //                     if (!$result) {
    //                         $success = false;
    //                         $errors[] = "Не удалось сохранить настройку '{$key}'";
    //                         Logger::error("Не удалось обновить настройку '{$key}'", 'settings');
    //                     }
    //                 } else {
    //                     // Создаем новую настройку
    //                     Logger::debug("Создание новой настройки '{$key}' со значением '{$value}'", 'settings');
                        
    //                     $result = $this->db->query(
    //                         "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)",
    //                         [$key, $value]
    //                     );
                        
    //                     if (!$result) {
    //                         $success = false;
    //                         $errors[] = "Не удалось сохранить настройку '{$key}'";
    //                         Logger::error("Не удалось создать настройку '{$key}'", 'settings');
    //                     }
    //                 }
    //             } catch (Exception $e) {
    //                 $success = false;
    //                 $errors[] = "Ошибка для настройки '{$key}': " . $e->getMessage();
    //                 Logger::error("Ошибка при сохранении настройки '{$key}': " . $e->getMessage(), 'settings');
    //             }
    //         }
            
    //         // Отправляем ответ
    //         if ($success) {
    //             Logger::info('Настройки успешно сохранены', 'settings');
    //             return $this->handleSuccess('Настройки успешно сохранены', null, true);
    //         } else {
    //             $errorMessage = 'Ошибка при сохранении настроек: ' . implode(', ', $errors);
    //             Logger::error($errorMessage, 'settings');
    //             return $this->handleAjaxError($errorMessage);
    //         }
    //     } catch (Exception $e) {
    //         Logger::error('Ошибка при сохранении настроек: ' . $e->getMessage(), 'settings');
    //         return $this->handleAjaxError('Ошибка при сохранении настроек: ' . $e->getMessage(), 500);
    //     }
    // }
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
            
            // Получаем подключение к БД
            $conn = $this->db->getConnection();
            
            // Начинаем транзакцию
            $conn->beginTransaction();
            
            try {
                foreach ($settings as $key => $value) {
                    // Проверяем безопасность ключа
                    $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
                    
                    // Проверяем, существует ли настройка
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
                    $stmt->execute([$key]);
                    $exists = (int)$stmt->fetchColumn();
                    
                    if ($exists) {
                        // Обновляем существующую настройку
                        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                        $stmt->execute([$value, $key]);
                    } else {
                        // Создаем новую настройку
                        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                        $stmt->execute([$key, $value]);
                    }
                }
                
                // Фиксируем транзакцию
                $conn->commit();
                
                // Возвращаем успешный результат
                return $this->handleSuccess('Настройки успешно сохранены', null, true);
                
            } catch (Exception $e) {
                // Откатываем транзакцию в случае ошибки
                $conn->rollBack();
                throw $e;
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