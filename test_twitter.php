<?php
/**
 * Тестовый скрипт для проверки функциональности постинга в Twitter
 */

// Подключаем необходимые файлы
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/utils/TwitterApiClient.php';

// Загружаем конфигурацию из .env файла, если он существует
if (file_exists(__DIR__ . '/.env')) {
    $envVars = parse_ini_file(__DIR__ . '/.env');
    foreach ($envVars as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Проверяем наличие необходимых переменных окружения
$requiredVars = ['TWITTER_API_KEY', 'TWITTER_API_SECRET', 'TWITTER_ACCESS_TOKEN', 'TWITTER_ACCESS_TOKEN_SECRET'];
$missingVars = [];

foreach ($requiredVars as $var) {
    if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
        $missingVars[] = $var;
    }
}

if (!empty($missingVars)) {
    echo "Ошибка: Отсутствуют необходимые переменные окружения: " . implode(', ', $missingVars) . "\n";
    echo "Пожалуйста, создайте файл .env на основе .env.example и заполните его необходимыми данными.\n";
    exit(1);
}

// Создаем экземпляр TwitterApiClient
$twitterClient = new TwitterApiClient(
    $_ENV['TWITTER_API_KEY'],
    $_ENV['TWITTER_API_SECRET'],
    $_ENV['TWITTER_ACCESS_TOKEN'],
    $_ENV['TWITTER_ACCESS_TOKEN_SECRET']
);

// Тестовый твит с временной меткой для уникальности
$tweetText = "Тестовый твит от AutoRewrite. Время: " . date('Y-m-d H:i:s');

echo "Отправка твита: $tweetText\n";

// Отправляем твит
$result = $twitterClient->postTweet($tweetText);

// Проверяем результат
if (isset($result['error'])) {
    echo "Ошибка при отправке твита: " . $result['error'] . "\n";
    if (isset($result['code'])) {
        echo "Код ошибки: " . $result['code'] . "\n";
    }
} else {
    echo "Твит успешно отправлен!\n";
    echo "ID твита: " . ($result['data']['id'] ?? 'неизвестно') . "\n";
}

echo "\nДетали ответа:\n";
print_r($result);
