<?php
/**
 * Класс для взаимодействия с Gemini API и OpenRouter
 */
class GeminiApiClient {
    private $apiKey;
    private $apiUrl;
    private $model;
    private $useOpenRouter;
    private $openRouterUrl = 'https://openrouter.ai/api/v1/chat/completions';
    
    /**
     * Конструктор класса
     * 
     * @param string $apiKey API ключ для доступа к Gemini или OpenRouter
     * @param string $model Модель Gemini (по умолчанию gemini-pro-2.5)
     * @param bool $useOpenRouter Использовать OpenRouter вместо прямого доступа
     */
    public function __construct($apiKey, $model = null, $useOpenRouter = false) {
        $this->apiKey = $apiKey;
        $this->useOpenRouter = $useOpenRouter;
        
        if ($this->useOpenRouter) {
            $this->apiUrl = $this->openRouterUrl;
            
            // Для OpenRouter
            if ($model) {
                // Если модель уже содержит префикс "google/", оставляем как есть
                if (strpos($model, 'google/') === 0) {
                    $this->model = $model;
                } else {
                    // Иначе добавляем префикс
                    $this->model = 'google/' . $model;
                }
            } else {
                $this->model = 'google/gemini-pro:free'; // Модель по умолчанию для OpenRouter
            }
        } else {
            $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta';
            // Для прямого использования API Gemini нужно удалить суффикс :free если он есть
            $modelName = $model ? $model : 'gemini-pro';
            $modelName = str_replace(':free', '', $modelName);
            $this->model = 'models/' . $modelName;
        }
    }
    
    /**
     * Получить список доступных моделей
     * 
     * @return array Ответ от API со списком моделей
     */
    public function listModels() {
        if ($this->useOpenRouter) {
            return [
                'models' => [
                    ['name' => 'google/gemini-2.5-pro-exp-03-25:free'],
                    ['name' => 'google/gemini-2.0-flash-thinking-exp:free'],
                    ['name' => 'google/gemini-pro-2.0-exp:free'],
                    ['name' => 'google/gemini-pro-1.5:free'],
                    ['name' => 'google/gemini-1.5-flash:free'],
                    ['name' => 'google/gemini-pro:free']
                ]
            ];
        } else {
            $url = "{$this->apiUrl}/models?key={$this->apiKey}";
            return $this->sendRequest('GET', $url);
        }
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
        if ($this->useOpenRouter) {
            return $this->generateContentViaOpenRouter($prompt);
        } else {
            return $this->generateContentViaGemini($prompt);
        }
    }
    
    /**
     * Генерация контента через прямой доступ к Gemini API
     * 
     * @param string $prompt Промпт для генерации
     * @return array Ответ от API
     */
    private function generateContentViaGemini($prompt) {
        $url = "{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}";
        
        // Общая структура запроса для всех моделей
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
                'maxOutputTokens' => 4096, // Увеличиваем максимальное количество токенов
                'stopSequences' => []
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_ONLY_HIGH'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_ONLY_HIGH'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_ONLY_HIGH'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_ONLY_HIGH'
                ]
            ]
        ];
        
        try {
            // Используем увеличенный таймаут для больших ответов
            $response = $this->sendRequest('POST', $url, $data, 60);
            
            // Проверяем наличие ошибок в ответе
            if (isset($response['error'])) {
                Logger::error('Gemini API Error: ' . json_encode($response['error']), 'rewrite');
                return [
                    'success' => false,
                    'error' => $response['error']['message'] ?? 'Unknown error'
                ];
            }
            
            // Логируем ответ для отладки
            Logger::debug('Gemini API response structure: ' . json_encode(array_keys($response)), 'rewrite');
            if (isset($response['candidates'])) {
                Logger::debug('Candidates count: ' . count($response['candidates']), 'rewrite');
            }
            
            // Извлекаем текст из ответа
            $content = '';
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $content = $response['candidates'][0]['content']['parts'][0]['text'];
            } else if (isset($response['candidates']) && 
                      isset($response['candidates'][0]) && 
                      isset($response['candidates'][0]['content']) && 
                      isset($response['candidates'][0]['content']['parts'])) {
                // Собираем текст из всех частей
                foreach ($response['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        $content .= $part['text'];
                    }
                }
            }
            
            // Если контент все еще пустой, попробуем собрать из всех кандидатов
            if (empty($content) && isset($response['candidates']) && is_array($response['candidates'])) {
                foreach ($response['candidates'] as $candidate) {
                    if (isset($candidate['content']['parts'])) {
                        foreach ($candidate['content']['parts'] as $part) {
                            if (isset($part['text'])) {
                                $content .= $part['text'];
                            }
                        }
                    }
                }
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
     * Генерация контента через OpenRouter
     * 
     * @param string $prompt Промпт для генерации
     * @return array Ответ от API
     */
    private function generateContentViaOpenRouter($prompt) {
        try {
            $data = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.5,
                'max_tokens' => 4096, // Увеличиваем максимальное количество токенов
                'stream' => false // Убедимся, что мы не используем поточную передачу данных
            ];
            
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'HTTP-Referer: https://autorewrite.com',
                'X-Title: AutoRewrite'
            ];
            
            // Увеличиваем таймаут для обработки больших запросов
            $response = $this->sendCustomRequest('POST', $this->apiUrl, $data, $headers, 60);
            
            // Проверяем наличие ошибок в ответе
            if (isset($response['error'])) {
                Logger::error('OpenRouter API Error: ' . json_encode($response['error']), 'rewrite');
                return [
                    'success' => false,
                    'error' => isset($response['error']) ? (is_string($response['error']) ? $response['error'] : json_encode($response['error'])) : 'Unknown error'
                ];
            }
            
            // Логируем ответ для отладки
            Logger::debug('OpenRouter API response: ' . json_encode($response), 'rewrite');
            
            // Извлекаем текст из ответа
            $content = '';
            if (isset($response['choices'][0]['message']['content'])) {
                $content = $response['choices'][0]['message']['content'];
            }
            
            if (empty($content) && isset($response['choices']) && is_array($response['choices'])) {
                // Пытаемся собрать контент из всех частей ответа
                foreach ($response['choices'] as $choice) {
                    if (isset($choice['message']['content'])) {
                        $content .= $choice['message']['content'];
                    }
                }
            }
            
            return [
                'success' => true,
                'content' => $content
            ];
        } catch (Exception $e) {
            Logger::error('Exception during OpenRouter API request: ' . $e->getMessage(), 'rewrite');
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
    private function sendRequest($method, $url, $data = [], $timeout = 30) {
        $curl = curl_init();
        
        $headers = [
            'Content-Type: application/json'
        ];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $timeout, // Используем переданный таймаут
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
            Logger::error('API HTTP Error: ' . $httpCode . ', Response: ' . $response, 'rewrite');
            return [
                'error' => [
                    'code' => $httpCode,
                    'message' => isset($responseData['error']['message']) ? $responseData['error']['message'] : 'HTTP Error: ' . $httpCode
                ]
            ];
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Метод для отправки запроса с кастомными заголовками
     * 
     * @param string $method HTTP метод (GET, POST)
     * @param string $url URL для запроса
     * @param array $data Данные для отправки
     * @param array $headers HTTP заголовки
     * @return array Ответ от API
     */
    private function sendCustomRequest($method, $url, $data = [], $headers = [], $timeout = 30) {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $timeout, // Используем переданный таймаут
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
        
        // Логирование для отладки
        Logger::debug('API response: ' . substr($response, 0, 500) . '...' . (strlen($response) > 500 ? '(truncated)' : ''), 'rewrite');
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #:" . $err);
        }
        
        if ($httpCode >= 400) {
            $responseData = json_decode($response, true);
            Logger::error('API HTTP Error: ' . $httpCode . ', Response: ' . $response, 'rewrite');
            return [
                'error' => [
                    'code' => $httpCode,
                    'message' => isset($responseData['error']) ? (is_string($responseData['error']) ? $responseData['error'] : json_encode($responseData['error'])) : 'HTTP Error: ' . $httpCode
                ]
            ];
        }
        
        return json_decode($response, true);
    }
}