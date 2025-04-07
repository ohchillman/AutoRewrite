<?php
/**
 * Контроллер для тестирования генерации изображений
 */
class ImageTestController extends BaseController {
    /**
     * Отображение страницы тестирования
     */
    public function index() {
        // Получаем текущие настройки
        $settings = $this->getSettings();
        
        // Отображаем представление
        $this->render('image-test/index', [
            'title' => 'Тестирование генерации изображений - AutoRewrite',
            'pageTitle' => 'Тестирование генерации изображений',
            'currentPage' => 'image-test',
            'layout' => 'main',
            'settings' => $settings
        ]);
    }

    /**
     * Метод для генерации тестового изображения
     */
    // В файле controllers/ImageTestController.php
public function generate() {

    Logger::info('Начало генерации изображения', 'image_test');
    error_log('Начало генерации изображения');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('JSON input: ' . file_get_contents('php://input'));
    try {
        if (!$this->isMethod('POST')) {
            return $this->handleAjaxError('Метод не поддерживается', 405);
        }
        
        // Получаем данные из JSON-запроса
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Если JSON некорректный, пробуем получить данные из POST
            $prompt = $this->post('prompt');
            $provider = $this->post('provider', $settings['image_api_provider'] ?? 'stable_diffusion');
        } else {
            $prompt = $data['prompt'] ?? '';
            $provider = $data['provider'] ?? ($settings['image_api_provider'] ?? 'stable_diffusion');
        }
        
        $settings = $this->getSettings();
        
        if (empty($prompt)) {
            return $this->handleAjaxError('Запрос для генерации изображения не может быть пустым');
        }
        
        // Загрузка соответствующего API клиента
        require_once UTILS_PATH . '/ImageGenerationClient.php';
        
        // Получаем API ключ в зависимости от провайдера
        $apiKey = '';
        if ($provider === 'stable_diffusion') {
            $apiKey = $settings['huggingface_api_key'] ?? '';
        } else if ($provider === 'dalle') {
            $apiKey = $settings['dalle_api_key'] ?? '';
        } else if ($provider === 'midjourney') {
            $apiKey = $settings['midjourney_api_key'] ?? '';
        }
        
        if (empty($apiKey)) {
            Logger::error('API ключ не настроен для провайдера: ' . $provider, 'image_test');
            return $this->handleAjaxError('API ключ не настроен. Пожалуйста, добавьте его в настройках.');
        }
        
        $client = new ImageGenerationClient($apiKey, $settings['image_generation_model'] ?? 'stabilityai/stable-diffusion-3-medium-diffusers');
        
        // Генерация изображения
        Logger::info('Генерация изображения с промптом: ' . $prompt, 'image_test');
        $result = $client->generateImage($prompt);
        
        if (!$result['success']) {
            Logger::error('Ошибка генерации: ' . ($result['error'] ?? 'Unknown error'), 'image_test');
            return $this->handleAjaxError($result['error'] ?? 'Ошибка при генерации изображения');
        }
        
        // Создаем директорию для временных файлов, если её нет
        $uploadDir = __DIR__ . '/../uploads/temp/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Сохраняем изображение во временный файл
        $filename = 'image_' . time() . '_' . uniqid() . '.png';
        $filePath = $uploadDir . $filename;
        
        if (file_put_contents($filePath, $result['image_data']) === false) {
            Logger::error('Не удалось сохранить изображение в файл: ' . $filePath, 'image_test');
            return $this->handleAjaxError('Не удалось сохранить изображение');
        }
        
        // Формируем URL для доступа к изображению
        $imageUrl = '/uploads/temp/' . $filename;
        Logger::info('Изображение сохранено: ' . $imageUrl, 'image_test');
        
        // Возвращаем успешный ответ с URL изображения
        return $this->jsonResponse([
            'success' => true,
            'message' => 'Изображение успешно сгенерировано',
            'imageUrl' => $imageUrl,
            'prompt' => $prompt
        ]);
    } catch (Exception $e) {
        Logger::error('Ошибка при генерации изображения: ' . $e->getMessage(), 'image_test');
        return $this->handleAjaxError('Ошибка при генерации изображения: ' . $e->getMessage());
    }
}

// Для отправки JSON ответа напрямую (в случае если родительский метод не работает)
protected function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

    /**
     * Получение настроек
     */
    private function getSettings() {
        try {
            $settings = $this->db->fetchAll("SELECT setting_key, setting_value FROM settings");
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