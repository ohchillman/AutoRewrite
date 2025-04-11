<?php
/**
 * Скрипт для остановки сервера API
 */

// Получаем PID сервера из файла
$pidFile = '/tmp/twitter_api_server.pid';

if (file_exists($pidFile)) {
    $pid = trim(file_get_contents($pidFile));
    
    if ($pid) {
        // Останавливаем процесс
        exec("kill $pid");
        echo "Сервер с PID $pid остановлен\n";
        
        // Удаляем файл с PID
        unlink($pidFile);
    } else {
        echo "Не удалось получить PID сервера\n";
    }
} else {
    echo "Файл с PID сервера не найден\n";
    
    // Пытаемся найти процесс PHP, слушающий порт 5000
    exec("ps aux | grep 'php -S.*5000' | grep -v grep", $output);
    
    if (!empty($output)) {
        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            $processPid = $parts[1];
            
            exec("kill $processPid");
            echo "Найден и остановлен процесс PHP с PID $processPid\n";
        }
    } else {
        echo "Не найдено процессов PHP, слушающих порт 5000\n";
    }
}
