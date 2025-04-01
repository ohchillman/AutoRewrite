<?php
/**
 * Класс для логирования
 */
class Logger {
    // Уровни логирования
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    
    // Приоритеты уровней
    private static $levelPriorities = [
        self::LEVEL_DEBUG => 0,
        self::LEVEL_INFO => 1,
        self::LEVEL_WARNING => 2,
        self::LEVEL_ERROR => 3
    ];
    
    /**
     * Записать сообщение в лог
     * 
     * @param string $level Уровень логирования
     * @param string $message Сообщение
     * @param array $context Дополнительные данные
     * @return bool Результат записи
     */
    public static function log($level, $message, $context = []) {
        // Проверяем, включено ли логирование
        if (!LOG_ENABLED) {
            return false;
        }
        
        // Проверяем уровень логирования
        if (!isset(self::$levelPriorities[$level]) || 
            self::$levelPriorities[$level] < self::$levelPriorities[LOG_LEVEL]) {
            return false;
        }
        
        // Форматируем сообщение
        $logMessage = self::formatMessage($level, $message, $context);
        
        // Определяем имя файла лога
        $logFile = LOGS_PATH . '/' . date('Y-m-d') . '.log';
        
        // Создаем директорию для логов, если она не существует
        if (!is_dir(LOGS_PATH)) {
            mkdir(LOGS_PATH, 0777, true);
        }
        
        // Записываем сообщение в файл
        return file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Форматировать сообщение для лога
     * 
     * @param string $level Уровень логирования
     * @param string $message Сообщение
     * @param array $context Дополнительные данные
     * @return string Отформатированное сообщение
     */
    private static function formatMessage($level, $message, $context = []) {
        // Дата и время
        $dateTime = date('Y-m-d H:i:s');
        
        // Заменяем плейсхолдеры в сообщении
        $replacements = [];
        foreach ($context as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $replacements['{' . $key . '}'] = $value;
        }
        $message = strtr($message, $replacements);
        
        // Форматируем сообщение
        return "[{$dateTime}] [{$level}] {$message}";
    }
    
    /**
     * Записать отладочное сообщение
     * 
     * @param string $message Сообщение
     * @param array $context Дополнительные данные
     */
    public static function debug($message, $context = []) {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Записать информационное сообщение
     * 
     * @param string $message Сообщение
     * @param array $context Дополнительные данные
     */
    public static function info($message, $context = []) {
        self::log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Записать предупреждение
     * 
     * @param string $message Сообщение
     * @param array $context Дополнительные данные
     */
    public static function warning($message, $context = []) {
        self::log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Записать ошибку
     * 
     * @param string $message Сообщение
     * @param array $context Дополнительные данные
     */
    public static function error($message, $context = []) {
        self::log(self::LEVEL_ERROR, $message, $context);
    }
}
