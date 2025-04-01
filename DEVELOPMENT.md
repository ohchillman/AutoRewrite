# Руководство по доработке AutoRewrite

Данный документ содержит информацию о структуре проекта и рекомендации по его дальнейшей доработке.

## Структура проекта

Проект построен по архитектуре MVC (Model-View-Controller):

- `controllers/` - контроллеры для обработки запросов
- `models/` - модели для работы с данными
- `views/` - представления для отображения интерфейса
- `utils/` - вспомогательные классы и функции
- `config/` - файлы конфигурации
- `database/` - файлы для работы с базой данных
- `assets/` - статические файлы (CSS, JavaScript, изображения)

## Необходимые доработки

### Интеграция с Make.com API для реврайта

В текущей версии приложения реализован только базовый функционал для реврайта. Для полноценной интеграции с Make.com API необходимо доработать класс для взаимодействия с API:

```php
// Создайте файл utils/MakeApiClient.php
<?php
class MakeApiClient {
    private $apiKey;
    private $apiUrl;
    
    public function __construct($apiKey, $apiUrl = 'https://api.make.com/v1') {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }
    
    public function rewriteContent($content, $template) {
        // Здесь должен быть код для отправки запроса к Make.com API
        // и получения реврайтнутого контента
        
        $url = $this->apiUrl . '/scenarios/trigger';
        $data = [
            'content' => $content,
            'template' => $template
        ];
        
        $response = $this->sendRequest('POST', $url, $data);
        return $response;
    }
    
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
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #:" . $err);
        }
        
        return json_decode($response, true);
    }
}
```

Затем обновите метод `process` в `RewriteController.php`, чтобы использовать этот класс.

### Интеграция с API социальных сетей

Для работы с Twitter, LinkedIn и YouTube API необходимо создать соответствующие классы:

```php
// Создайте файл utils/TwitterApiClient.php
<?php
class TwitterApiClient {
    private $apiKey;
    private $apiSecret;
    private $accessToken;
    private $accessTokenSecret;
    
    public function __construct($apiKey, $apiSecret, $accessToken, $accessTokenSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->accessToken = $accessToken;
        $this->accessTokenSecret = $accessTokenSecret;
    }
    
    public function postTweet($content) {
        // Код для публикации твита через API
    }
    
    public function getTimeline($username, $count = 10) {
        // Код для получения ленты пользователя
    }
}

// Аналогично создайте классы для LinkedIn и YouTube
```

### Selenium для Threads

Для работы с Threads через Selenium создайте класс:

```php
// Создайте файл utils/ThreadsSeleniumClient.php
<?php
class ThreadsSeleniumClient {
    private $webDriver;
    private $username;
    private $password;
    
    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
        
        // Инициализация WebDriver
        $capabilities = \Facebook\WebDriver\Remote\DesiredCapabilities::chrome();
        $this->webDriver = \Facebook\WebDriver\Remote\RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
    }
    
    public function login() {
        // Код для входа в аккаунт Threads
    }
    
    public function postContent($content) {
        // Код для публикации контента
    }
    
    public function close() {
        $this->webDriver->quit();
    }
}
```

### Парсинг контента

Для парсинга контента из различных источников реализуйте классы:

```php
// Создайте файл utils/ContentParser.php
<?php
class ContentParser {
    public function parseRss($url) {
        // Код для парсинга RSS-ленты
    }
    
    public function parseBlog($url) {
        // Код для парсинга блога
    }
    
    public function parseSocialMedia($url, $type) {
        // Код для парсинга социальных сетей
    }
}
```

### Cron-задачи для автоматизации

Создайте скрипты для выполнения автоматических задач:

```php
// Создайте файл cron/parse.php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/ContentParser.php';
require_once __DIR__ . '/../utils/Logger.php';

// Код для автоматического парсинга контента

// Создайте файл cron/rewrite.php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/MakeApiClient.php';
require_once __DIR__ . '/../utils/Logger.php';

// Код для автоматического реврайта контента

// Создайте файл cron/post.php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/TwitterApiClient.php';
require_once __DIR__ . '/../utils/LinkedInApiClient.php';
require_once __DIR__ . '/../utils/YouTubeApiClient.php';
require_once __DIR__ . '/../utils/ThreadsSeleniumClient.php';
require_once __DIR__ . '/../utils/Logger.php';

// Код для автоматической публикации контента
```

## Рекомендации по безопасности

1. Храните API ключи и токены в защищенном месте, не в коде
2. Используйте подготовленные запросы для работы с базой данных
3. Валидируйте все входные данные от пользователя
4. Используйте HTTPS для защиты передаваемых данных
5. Регулярно обновляйте зависимости и библиотеки

## Рекомендации по производительности

1. Используйте кэширование для часто запрашиваемых данных
2. Оптимизируйте запросы к базе данных
3. Минимизируйте и объединяйте CSS и JavaScript файлы
4. Используйте асинхронные задачи для длительных операций
5. Мониторьте производительность и оптимизируйте узкие места

## Дальнейшее развитие проекта

1. Добавьте систему уведомлений для отслеживания статуса задач
2. Реализуйте аналитику эффективности постов
3. Добавьте планирование публикаций
4. Реализуйте интеграцию с другими платформами
5. Добавьте возможность настройки шаблонов для реврайта
6. Реализуйте систему тегов и категорий для контента
