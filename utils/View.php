<?php
/**
 * Класс для работы с представлениями
 */
class View {
    // Данные для представления
    private $data = [];
    
    /**
     * Установить данные для представления
     * 
     * @param string $key Ключ данных
     * @param mixed $value Значение
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
    }
    
    /**
     * Получить данные из представления
     * 
     * @param string $key Ключ данных
     * @param mixed $default Значение по умолчанию
     * @return mixed Данные
     */
    public function get($key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
    
    /**
     * Отобразить представление
     * 
     * @param string $view Имя представления
     * @param array $data Данные для представления
     */
    public function render($view, $data = []) {
        // Проверяем, является ли запрос AJAX
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Логируем начало рендеринга для AJAX-запросов
        if ($isAjax && function_exists('logOutputBuffer')) {
            logOutputBuffer('before_view_render');
        }
        
        // Объединяем данные
        $this->data = array_merge($this->data, $data);
        
        // Извлекаем данные в переменные
        extract($this->data);
        
        // Определяем путь к файлу представления
        $viewFile = VIEWS_PATH . '/' . $view . '.php';
        
        // Проверяем, существует ли файл представления
        if (!file_exists($viewFile)) {
            die("Представление {$view} не найдено");
        }
        
        // Начинаем буферизацию вывода
        ob_start();
        
        // Подключаем файл представления
        include $viewFile;
        
        // Получаем содержимое буфера
        $content = ob_get_clean();
        
        // Логируем после рендеринга представления для AJAX-запросов
        if ($isAjax && function_exists('logOutputBuffer')) {
            logOutputBuffer('after_view_render');
        }
        
        // Для AJAX-запросов проверяем, является ли содержимое JSON
        if ($isAjax) {
            // Проверяем, является ли содержимое JSON
            $json = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Если это валидный JSON, отправляем его с правильными заголовками
                header('Content-Type: application/json; charset=utf-8');
                echo $content;
            } else {
                // Если это не JSON, преобразуем содержимое в JSON-ответ
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Ошибка формата ответа',
                    'html_content' => $content
                ], JSON_UNESCAPED_UNICODE);
            }
            return;
        }
        
        // Проверяем, нужно ли подключать шаблон
        if (isset($this->data['layout'])) {
            // Определяем путь к файлу шаблона
            $layoutFile = VIEWS_PATH . '/layouts/' . $this->data['layout'] . '.php';
            
            // Проверяем, существует ли файл шаблона
            if (!file_exists($layoutFile)) {
                die("Шаблон {$this->data['layout']} не найден");
            }
            
            // Устанавливаем содержимое в данные для шаблона
            $this->data['content'] = $content;
            
            // Извлекаем данные в переменные
            extract($this->data);
            
            // Подключаем файл шаблона
            include $layoutFile;
        } else {
            // Выводим содержимое без шаблона
            echo $content;
        }
    }
}
