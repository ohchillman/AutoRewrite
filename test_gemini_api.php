<?php
// Подключение необходимых файлов
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/GeminiApiClient.php';
require_once __DIR__ . '/utils/Logger.php';

// Подключаем базу данных
require_once __DIR__ . '/config/database.php'; // Добавлено подключение для Database

// Получение настроек из базы данных
$db = Database::getInstance();
$settings = [];
$settingsRows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Проверка API ключа
$apiKey = $settings['gemini_api_key'] ?? '';
if (empty($apiKey)) {
    die("API ключ Gemini не установлен в настройках");
}

// Проверка модели
$model = $settings['gemini_model'] ?? 'gemini-pro';
echo "Текущая модель: " . $model . "<br>";

// Создаем клиент
$geminiClient = new GeminiApiClient($apiKey, $model);

// Получаем список доступных моделей
echo "<h2>Доступные модели:</h2>";
try {
    $models = $geminiClient->listModels();
    echo "<pre>";
    print_r($models);
    echo "</pre>";
} catch (Exception $e) {
    echo "Ошибка при получении списка моделей: " . $e->getMessage();
}

// Тестовый запрос на генерацию контента
echo "<h2>Тестовый запрос:</h2>";
try {
    $response = $geminiClient->generateContent("Привет! Напиши короткое приветствие.");
    echo "<pre>";
    print_r($response);
    echo "</pre>";
} catch (Exception $e) {
    echo "Ошибка при генерации контента: " . $e->getMessage();
}
?>