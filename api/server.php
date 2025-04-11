<?php
/**
 * Простой сервер для запуска API на порту 5000
 */

// Устанавливаем порт
$port = 5000;
$host = '0.0.0.0';

// Запускаем встроенный веб-сервер PHP
$command = sprintf('php -S %s:%d -t %s', $host, $port, __DIR__);

echo "Запуск сервера на http://{$host}:{$port}/" . PHP_EOL;
echo "Для остановки нажмите Ctrl+C" . PHP_EOL;

// Запускаем сервер
passthru($command);
