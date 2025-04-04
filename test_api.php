<?php
/**
 * Тестовая страница для проверки работы с API
 */

// Подключаем конфигурационный файл
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Logger.php';

// Функция для отображения результатов
function displayResult($title, $result) {
    echo "<h3>{$title}</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест API - AutoRewrite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Тест API</h1>
        
        <form method="post" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="api_provider" class="form-label">API провайдер</label>
                        <select class="form-select" id="api_provider" name="api_provider">
                            <option value="gemini">Gemini API (Google)</option>
                            <option value="openrouter">OpenRouter</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="model" class="form-label">Модель</label>
                        <select class="form-select" id="model" name="model">
                            <option value="gemini-2.0-flash-thinking-exp:free">Gemini 2.0 Flash Thinking (Free)</option>
                            <option value="gemini-pro-2.0-exp:free">Gemini Pro 2.0 (Free)</option>
                            <option value="gemini-pro-1.5:free">Gemini Pro 1.5 (Free)</option>
                            <option value="gemini-1.5-flash:free">Gemini 1.5 Flash (Free)</option>
                            <option value="gemini-pro:free">Gemini Pro (Free)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="api_key" class="form-label">API Ключ</label>
                <input type="text" class="form-control" id="api_key" name="api_key" required>
                <div class="form-text">Введите ваш API ключ для выбранного провайдера</div>
            </div>
            
            <div class="mb-3">
                <label for="prompt" class="form-label">Текст для теста</label>
                <textarea class="form-control" id="prompt" name="prompt" rows="4">Напиши короткий текст о пользе здорового образа жизни.</textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Выполнить тест</button>
        </form>
        
        <?php
        // Обработка формы
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $apiProvider = $_POST['api_provider'] ?? 'gemini';
            $model = $_POST['model'] ?? 'gemini-pro';
            $apiKey = $_POST['api_key'] ?? '';
            $prompt = $_POST['prompt'] ?? '';
            
            if (empty($apiKey)) {
                echo '<div class="alert alert-danger">API ключ не указан</div>';
            } else {
                try {
                    // Подключаем GeminiApiClient
                    require_once __DIR__ . '/utils/GeminiApiClient.php';
                    
                    // Создаем экземпляр клиента
                    $client = new GeminiApiClient(
                        $apiKey,
                        $model,
                        $apiProvider === 'openrouter'
                    );
                    
                    // Получаем список моделей
                    $models = $client->listModels();
                    displayResult("Доступные модели:", $models);
                    
                    // Выполняем тестовый запрос
                    $response = $client->generateContent($prompt);
                    displayResult("Тестовый запрос:", $response);

                    // Отображаем сгенерированный контент
                    if ($response['success']) {
                        echo '<div class="alert alert-success mt-4">
                            <h4>Сгенерированный текст:</h4>
                            <p>' . nl2br(htmlspecialchars($response['content'])) . '</p>
                        </div>';
                    } else {
                        echo '<div class="alert alert-danger mt-4">
                            <h4>Ошибка:</h4>
                            <p>' . htmlspecialchars($response['error']) . '</p>
                        </div>';
                    }
                    } catch (Exception $e) {
                    echo '<div class="alert alert-danger mt-4">
                        <h4>Исключение:</h4>
                        <p>' . htmlspecialchars($e->getMessage()) . '</p>
                    </div>';
                    }
                    }
                    }
                    ?>

            <div class="mt-4">
                <a href="/" class="btn btn-secondary">Вернуться на главную</a>
            </div>
        </div>
    </body>
</html>