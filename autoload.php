<?php

spl_autoload_register(function ($class) {
    // Определяем базовые директории для поиска классов
    $directories = [
        __DIR__ . '/controllers/',
        __DIR__ . '/models/',
        __DIR__ . '/parsers/',
        __DIR__ . '/core/'
    ];

    // Преобразуем имя класса в путь к файлу
    $classFile = str_replace('\\', '/', $class) . '.php';

    // Ищем файл класса в каждой директории
    foreach ($directories as $directory) {
        $file = $directory . $classFile;
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}); 