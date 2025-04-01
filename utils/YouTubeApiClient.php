<?php
/**
 * Класс для взаимодействия с YouTube API
 */
class YouTubeApiClient {
    private $apiKey;
    private $clientId;
    private $clientSecret;
    private $accessToken;
    private $refreshToken;
    private $apiUrl = 'https://www.googleapis.com/youtube/v3';
    
    /**
     * Конструктор класса
     * 
     * @param string $apiKey API ключ
     * @param string $clientId ID клиента
     * @param string $clientSecret Секрет клиента
     * @param string $accessToken Токен доступа
     * @param string $refreshToken Токен обновления
     */
    public function __construct($apiKey, $clientId, $clientSecret, $accessToken = null, $refreshToken = null) {
        $this->apiKey = $apiKey;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }
    
    /**
     * Метод для публикации видео
     * 
     * @param string $videoPath Путь к видеофайлу
     * @param string $title Заголовок видео
     * @param string $description Описание видео
     * @param array $tags Теги видео
     * @param string $privacyStatus Статус приватности (public, unlisted, private)
     * @return array Ответ от API
     */
    public function uploadVideo($videoPath, $title, $description, $tags = [], $privacyStatus = 'public') {
        // Проверка наличия токена доступа
        if (!$this->accessToken) {
            return ['error' => 'Access token is required for video upload'];
        }
        
        // Проверка существования файла
        if (!file_exists($videoPath)) {
            return ['error' => 'Video file not found: ' . $videoPath];
        }
        
        // Формирование метаданных видео
        $metadata = [
            'snippet' => [
                'title' => $title,
                'description' => $description,
                'tags' => $tags,
                'categoryId' => '22' // Категория "People & Blogs" по умолчанию
            ],
            'status' => [
                'privacyStatus' => $privacyStatus,
                'embeddable' => true,
                'publicStatsViewable' => true
            ]
        ];
        
        // Инициализация загрузки
        $initResponse = $this->initiateUpload($metadata);
        
        if (isset($initResponse['error'])) {
            return $initResponse;
        }
        
        // Загрузка видеофайла
        $uploadResponse = $this->uploadVideoFile($initResponse['uploadUrl'], $videoPath);
        
        return $uploadResponse;
    }
    
    /**
     * Метод для инициализации загрузки видео
     * 
     * @param array $metadata Метаданные видео
     * @return array Ответ от API с URL для загрузки
     */
    private function initiateUpload($metadata) {
        $url = 'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status,contentDetails';
        
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'X-Upload-Content-Length: ' . filesize($videoPath)
        ];
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($metadata),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if ($err) {
            return ['error' => "cURL Error #:" . $err];
        }
        
        if ($httpCode != 200) {
            return ['error' => 'Failed to initiate upload. HTTP code: ' . $httpCode, 'response' => $response];
        }
        
        // Извлечение URL для загрузки из заголовков
        $headers = [];
        $headerText = substr($response, 0, strpos($response, "\r\n\r\n"));
        foreach (explode("\r\n", $headerText) as $i => $line) {
            if ($i === 0) {
                continue;
            }
            list($key, $value) = explode(': ', $line, 2);
            $headers[$key] = $value;
        }
        
        if (!isset($headers['Location'])) {
            return ['error' => 'Upload URL not found in response headers'];
        }
        
        return ['uploadUrl' => $headers['Location']];
    }
    
    /**
     * Метод для загрузки видеофайла
     * 
     * @param string $uploadUrl URL для загрузки
     * @param string $videoPath Путь к видеофайлу
     * @return array Ответ от API
     */
    private function uploadVideoFile($uploadUrl, $videoPath) {
        $fileSize = filesize($videoPath);
        $videoData = file_get_contents($videoPath);
        
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/octet-stream',
            'Content-Length: ' . $fileSize
        ];
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $uploadUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 600, // Увеличенный таймаут для больших файлов
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $videoData,
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if ($err) {
            return ['error' => "cURL Error #:" . $err];
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            return ['error' => 'Failed to upload video. HTTP code: ' . $httpCode, 'response' => $response];
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Метод для получения списка видео канала
     * 
     * @param string $channelId ID канала
     * @param int $maxResults Максимальное количество результатов
     * @return array Ответ от API
     */
    public function getChannelVideos($channelId, $maxResults = 10) {
        $url = $this->apiUrl . '/search?part=snippet&channelId=' . $channelId . '&maxResults=' . $maxResults . '&order=date&type=video&key=' . $this->apiKey;
        
        $response = $this->sendRequest('GET', $url);
        
        return $response;
    }
    
    /**
     * Метод для получения информации о видео
     * 
     * @param string $videoId ID видео
     * @return array Ответ от API
     */
    public function getVideoInfo($videoId) {
        $url = $this->apiUrl . '/videos?part=snippet,contentDetails,statistics&id=' . $videoId . '&key=' . $this->apiKey;
        
        $response = $this->sendRequest('GET', $url);
        
        return $response;
    }
    
    /**
     * Метод для обновления токена доступа
     * 
     * @return array Новый токен доступа или ошибка
     */
    public function refreshAccessToken() {
        if (!$this->refreshToken) {
            return ['error' => 'Refresh token is required'];
        }
        
        $url = 'https://oauth2.googleapis.com/token';
        
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token'
        ];
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            return ['error' => "cURL Error #:" . $err];
        }
        
        $responseData = json_decode($response, true);
        
        if (isset($responseData['access_token'])) {
            $this->accessToken = $responseData['access_token'];
        }
        
        return $responseData;
    }
    
    /**
     * Метод для отправки запроса к API
     * 
     * @param string $method HTTP метод
     * @param string $url URL для запроса
     * @param array $data Данные для отправки
     * @return array Ответ от API
     */
    private function sendRequest($method, $url, $data = []) {
        $curl = curl_init();
        
        $headers = [];
        
        if ($this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        
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
            $headers[] = 'Content-Type: application/json';
        }
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            return ['error' => "cURL Error #:" . $err];
        }
        
        return json_decode($response, true);
    }
}
