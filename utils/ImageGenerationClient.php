<?php
/**
 * Класс для генерации изображений на основе текста с использованием Hugging Face API
 */
class ImageGenerationClient {
    private $apiKey;
    private $model;
    private $apiUrl = 'https://api-inference.huggingface.co/models/';
    
    /**
     * Конструктор класса
     * 
     * @param string $apiKey API ключ для доступа к Hugging Face
     * @param string $model Модель для генерации изображений (по умолчанию stabilityai/stable-diffusion-3-medium-diffusers)
     */
    public function __construct($apiKey, $model = null) {
        $this->apiKey = $apiKey;
        $this->model = $model ? $model : 'stabilityai/stable-diffusion-3-medium-diffusers';
    }
    
    /**
     * Генерация изображения на основе текста
     * 
     * @param string $prompt Текстовый промпт для генерации изображения
     * @param array $options Дополнительные параметры для генерации
     * @return array Результат генерации изображения
     */
    public function generateImage($prompt, $options = []) {
        // Формируем URL для запроса
        $url = $this->apiUrl . $this->model;
        
        // Подготавливаем данные для запроса
        $data = [
            'inputs' => $prompt,
            'parameters' => [
                'guidance_scale' => $options['guidance_scale'] ?? 7.5,
                'negative_prompt' => $options['negative_prompt'] ?? '',
                'num_inference_steps' => $options['num_inference_steps'] ?? 30,
                'width' => $options['width'] ?? 512,
                'height' => $options['height'] ?? 512
            ]
        ];
        
        try {
            // Отправляем запрос к API
            $response = $this->sendRequest('POST', $url, $data);
            
            // Если получили бинарные данные изображения
            if (isset($response['binary_data'])) {
                return [
                    'success' => true,
                    'image_data' => $response['binary_data']
                ];
            } else if (isset($response['error'])) {
                Logger::error('Image Generation API Error: ' . json_encode($response['error']), 'image_generation');
                return [
                    'success' => false,
                    'error' => $response['error']
                ];
            } else {
                Logger::error('Unknown Image Generation API response: ' . json_encode($response), 'image_generation');
                return [
                    'success' => false,
                    'error' => 'Unknown API response'
                ];
            }
        } catch (Exception $e) {
            Logger::error('Exception during Image Generation API request: ' . $e->getMessage(), 'image_generation');
            return [
                'success' => false,
                'error' => 'Ошибка при запросе к API: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Сохранение изображения в файл
     * 
     * @param string $imageData Бинарные данные изображения
     * @param string $filePath Путь для сохранения файла
     * @return bool Результат сохранения
     */
    public function saveImageToFile($imageData, $filePath) {
        try {
            $result = file_put_contents($filePath, $imageData);
            return $result !== false;
        } catch (Exception $e) {
            Logger::error('Error saving image to file: ' . $e->getMessage(), 'image_generation');
            return false;
        }
    }
    
    /**
     * Метод для отправки запроса к API
     * 
     * @param string $method HTTP метод (GET, POST)
     * @param string $url URL для запроса
     * @param array $data Данные для отправки
     * @return array Ответ от API
     */
    private function sendRequest($method, $url, $data = []) {
        $curl = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60, // Увеличенный таймаут для генерации изображений
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #:" . $err);
        }
        
        // Проверяем, является ли ответ изображением
        if (strpos($contentType, 'image/') === 0) {
            return [
                'binary_data' => $response
            ];
        }
        
        // Если ответ не является изображением, пробуем декодировать JSON
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            Logger::error('API HTTP Error: ' . $httpCode . ', Response: ' . $response, 'image_generation');
            return [
                'error' => [
                    'code' => $httpCode,
                    'message' => isset($decodedResponse['error']) ? 
                        (is_string($decodedResponse['error']) ? $decodedResponse['error'] : json_encode($decodedResponse['error'])) : 
                        'HTTP Error: ' . $httpCode
                ]
            ];
        }
        
        return $decodedResponse !== null ? $decodedResponse : ['raw_response' => $response];
    }
}
