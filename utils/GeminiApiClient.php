<?php
/**
 * Класс для взаимодействия с Gemini API
 */
class GeminiApiClient {
    private $apiKey;
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private $model = 'models/gemini-pro';
    
    /**
     * Конструктор класса
     * 
     * @param string $apiKey API ключ для доступа к Gemini
     * @param string $model Модель Gemini (по умолчанию gemini-pro)
     */
    public function __construct($apiKey, $model = null) {
        $this->apiKey = $apiKey;
        if ($model) {
            $this->model = 'models/' . $model;
        }
    }
    
    /**
     * Получить список доступных моделей
     * 
     * @return array Ответ от API со списком моделей
     */
    public function listModels() {
        $url = "{$this->apiUrl}/models?key={$this->apiKey}";
        return $this->sendRequest('GET', $url);
    }

    /**
     * Метод для реврайта контента
     * 
     * @param string $content Исходный контент для реврайта
     * @param string $template Шаблон для реврайта
     * @return array Ответ от API с реврайтнутым контентом
     */
    public function rewriteContent($content, $template) {
        // Заменяем плейсхолдер {content} на фактический контент
        $prompt = str_replace('{content}', $content, $template);
        
        return $this->generateContent($prompt);
    }
    
    /**
     * Метод для генерации контента
     * 
     * @param string $prompt Промпт для генерации
     * @return array Ответ от API
     */
    public function generateContent($prompt) {
        $url = "{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}";
        
        // Для новых моделей структура запроса может отличаться
        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
                'stopSequences' => []
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];
        
        try {
            $response = $this->sendRequest('POST', $url, $data);
            
            // Проверяем наличие ошибок в ответе
            if (isset($response['error'])) {
                Logger::error('Gemini API Error: ' . json_encode($response['error']), 'rewrite');
                return [
                    'success' => false,
                    'error' => $response['error']['message'] ?? 'Unknown error'
                ];
            }
            
            // Извлекаем текст из ответа
            $content = '';
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $content = $response['candidates'][0]['content']['parts'][0]['text'];
            }
            
            return [
                'success' => true,
                'content' => $content
            ];
        } catch (Exception $e) {
            Logger::error('Exception during Gemini API request: ' . $e->getMessage(), 'rewrite');
            return [
                'success' => false,
                'error' => 'Ошибка при запросе к API: ' . $e->getMessage()
            ];
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
            'Content-Type: application/json'
        ];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
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
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #:" . $err);
        }
        
        if ($httpCode >= 400) {
            $responseData = json_decode($response, true);
            Logger::error('Gemini API HTTP Error: ' . $httpCode . ', Response: ' . $response, 'rewrite');
            return [
                'error' => [
                    'code' => $httpCode,
                    'message' => isset($responseData['error']['message']) ? $responseData['error']['message'] : 'HTTP Error: ' . $httpCode
                ]
            ];
        }
        
        return json_decode($response, true);
    }
}