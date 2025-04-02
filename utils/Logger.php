<?php
/**
 * Класс для логирования
 */
class Logger {
    /**
     * Путь к директории с логами
     */
    private static $logDir = '/tmp/autorewrite_logs';
    
    /**
     * Записать сообщение в лог
     * 
     * @param string $message Сообщение для записи
     * @param string $level Уровень логирования (info, warning, error, debug)
     * @param string $logFile Имя файла лога (без расширения)
     */
    public static function log($message, $level = 'info', $logFile = 'app') {
        // Создаем директорию для логов, если она не существует
        if (!file_exists(self::$logDir)) {
            if (!@mkdir(self::$logDir, 0777, true)) {
                error_log("Не удалось создать директорию для логов: " . self::$logDir);
                return;
            }
        }
        
        // Формируем путь к файлу лога
        $logFilePath = self::$logDir . '/' . $logFile . '.log';
        
        // Формируем строку лога
        $logString = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;
        
        // Записываем в файл
        if (@file_put_contents($logFilePath, $logString, FILE_APPEND) === false) {
            error_log("Не удалось записать в лог-файл: " . $logFilePath);
        }
    }
    
    /**
     * Записать информационное сообщение
     * 
     * @param string $message Сообщение для записи
     * @param string $logFile Имя файла лога (без расширения)
     */
    public static function info($message, $logFile = 'app') {
        self::log($message, 'info', $logFile);
    }
    
    /**
     * Записать предупреждение
     * 
     * @param string $message Сообщение для записи
     * @param string $logFile Имя файла лога (без расширения)
     */
    public static function warning($message, $logFile = 'app') {
        self::log($message, 'warning', $logFile);
    }
    
    /**
     * Записать ошибку
     * 
     * @param string $message Сообщение для записи
     * @param string $logFile Имя файла лога (без расширения)
     */
    public static function error($message, $logFile = 'app') {
        self::log($message, 'error', $logFile);
    }
    
    /**
     * Записать отладочное сообщение
     * 
     * @param string $message Сообщение для записи
     * @param string $logFile Имя файла лога (без расширения)
     */
    public static function debug($message, $logFile = 'app', $context = []) {
        $logString = $message;
        
        if (!empty($context)) {
            $logString .= "\n" . print_r($context, true);
        }
        
        self::log($logString, 'debug', $logFile);
    }
    
    /**
     * Записать переменную в лог
     * 
     * @param mixed $var Переменная для записи
     * @param string $varName Имя переменной
     * @param string $level Уровень логирования (info, warning, error, debug)
     * @param string $logFile Имя файла лога (без расширения)
     */
    public static function logVar($var, $varName = 'var', $level = 'debug', $logFile = 'app') {
        $message = $varName . ' = ' . print_r($var, true);
        self::log($message, $level, $logFile);
    }
}
