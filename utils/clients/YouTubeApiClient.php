<?php
/**
 * YouTubeApiClient с поддержкой прокси
 */
class YouTubeApiClient extends BaseApiClient {
    /**
     * @var string API ключ
     */
    private $apiKey;
    
    /**
     * @var string Client ID
     */
    private $clientId;
    
    /**
     * @var string Client Secret
     */
    private $clientSecret;
    
    /**
     * @var string Токен доступа
     */
    private $accessToken;
    
    /**
     * @var string Refresh токен
     */
    private $refreshToken;
    
    /**
     * Конструктор класса
     * 
     * @param string $apiKey API ключ
     * @param string $clientId Client ID (опционально)
     * @param string $clientSecret Client Secret (опционально)
     * @param Logger $logger Экземпляр класса для логирования (опционально)
     */
    public function __construct($apiKey, $clientId = '', $clientSecret = '', $logger = null) {
        parent::__construct($logger);
        
        $this->apiKey = $apiKey;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }
    
    /**
     * Установка токена доступа
     * 
     * @param string $accessToken Токен доступа
     * @return self
     */
    public function setAccessToken($accessToken) {
        $this->accessToken = $accessToken;
        return $this;
    }
    
    /**
     * Установка refresh токена
     * 
     * @param string $refreshToken Refresh токен
     * @return self
     */
    public function setRefreshToken($refreshToken) {
        $this->refreshToken = $refreshToken;
        return $this;
    }
    
    /**
     * Публикация видео на YouTube
     * 
     * @param string $videoPath Путь к видеофайлу
     * @param string $title Заголовок видео
     * @param string $description Описание видео
     * @param array $tags Теги видео
     * @param string $privacyStatus Статус приватности (public, unlisted, private)
     * @return array Результат публикации
     */
    public function uploadVideo($videoPath, $title, $description, $tags = [], $privacyStatus = 'public') {
        $this->logger->debug("Uploading video to YouTube: $title");
        
        if (empty($this->accessToken)) {
            $this->logger->error("Access token is required for video upload");
            return [
                'error' => 'Access token is required for video upload'
            ];
        }
        
        if (!file_exists($videoPath)) {
            $this->logger->error("Video file not found: $videoPath");
            return [
                'error' => 'Video file not found'
            ];
        }
        
        try {
            // URL для загрузки видео
            $url = 'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status';
            
            // Формируем данные для загрузки
            $postData = [
                'snippet' => [
                    'title' => $title,
                    'description' => $description,
                    'tags' => $tags,
                    'categoryId' => '22' // Категория "People & Blogs"
                ],
                'status' => [
                    'privacyStatus' => $privacyStatus,
                    'selfDeclaredMadeForKids' => false
                ]
            ];
            
            // Инициализируем cURL для создания сессии загрузки
            $ch = curl_init();
            
            // Настраиваем cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'X-Upload-Content-Type: video/*',
                'X-Upload-Content-Length: ' . filesize($videoPath)
            ]);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            // Если есть прокси, настраиваем его
            if ($this->proxy) {
                $this->setupCurlWithProxy($ch);
            }
            
            // Выполняем запрос
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            
            // Закрываем соединение
            curl_close($ch);
            
            // Проверяем результат
            if ($error) {
                $this->logger->error("cURL error: $error");
                return [
                    'error' => $error
                ];
            }
            
            if ($info['http_code'] != 200) {
                $this->logger->error("YouTube API error: HTTP code {$info['http_code']}");
                return [
                    'error' => "HTTP code: {$info['http_code']}"
                ];
            }
            
            // Получаем URL для загрузки видео из заголовка Location
            $uploadUrl = '';
            $headers = explode("\n", $response);
            foreach ($headers as $header) {
                if (strpos($header, 'Location:') === 0) {
                    $uploadUrl = trim(substr($header, 9));
                    break;
                }
            }
            
            if (empty($uploadUrl)) {
                $this->logger->error("Upload URL not found in response");
                return [
                    'error' => 'Upload URL not found in response'
                ];
            }
            
            // Загружаем видео
            $ch = curl_init();
            
            // Настраиваем cURL
            curl_setopt($ch, CURLOPT_URL, $uploadUrl);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_INFILE, fopen($videoPath, 'r'));
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($videoPath));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: video/*'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0); // Без ограничения времени
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            // Если есть прокси, настраиваем его
            if ($this->proxy) {
                $this->setupCurlWithProxy($ch);
            }
            
            // Выполняем запрос
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            
            // Закрываем соединение
            curl_close($ch);
            
            // Проверяем результат
            if ($error) {
                $this->logger->error("cURL error during upload: $error");
                return [
                    'error' => $error
                ];
            }
            
            if ($info['http_code'] != 200 && $info['http_code'] != 201) {
                $this->logger->error("YouTube API error during upload: HTTP code {$info['http_code']}");
                return [
                    'error' => "HTTP code: {$info['http_code']}"
                ];
            }
            
            // Декодируем ответ
            $responseData = json_decode($response, true);
            
            $this->logger->info("Video uploaded successfully to YouTube, ID: " . ($responseData['id'] ?? 'unknown'));
            return $responseData;
        } catch (Exception $e) {
            $this->logger->error("Exception during video upload: " . $e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
