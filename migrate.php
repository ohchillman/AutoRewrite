<?php
/**
 * Скрипт для миграции данных в новую структуру
 */

// Подключаем конфигурацию
require_once __DIR__ . '/config/config.php';

try {
    // Установление соединения с базой данных напрямую
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $transactionStarted = false;
    
    echo "<h1>Миграция структуры базы данных</h1>";
    
    // Начинаем транзакцию
    $pdo->beginTransaction();
    $transactionStarted = true;
    
    // 1. Добавляем новые колонки в таблицу rewritten_content
    echo "Добавление колонок в таблицу rewritten_content...<br>";
    try {
        $pdo->exec("ALTER TABLE rewritten_content ADD COLUMN version_number INT DEFAULT 1");
        echo "- Колонка version_number добавлена успешно<br>";
    } catch (PDOException $e) {
        echo "- Колонка version_number уже существует: " . $e->getMessage() . "<br>";
    }
    
    try {
        $pdo->exec("ALTER TABLE rewritten_content ADD COLUMN is_current_version BOOLEAN DEFAULT TRUE");
        echo "- Колонка is_current_version добавлена успешно<br>";
    } catch (PDOException $e) {
        echo "- Колонка is_current_version уже существует: " . $e->getMessage() . "<br>";
    }
    
    // 2. Создаем новую таблицу для версий
    echo "<br>Создание таблицы rewrite_versions...<br>";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rewrite_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                rewritten_id INT NOT NULL,
                version_number INT NOT NULL,
                title VARCHAR(255),
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (rewritten_id) REFERENCES rewritten_content(id) ON DELETE CASCADE
            )
        ");
        echo "- Таблица rewrite_versions создана успешно<br>";
    } catch (PDOException $e) {
        echo "- Ошибка при создании таблицы rewrite_versions: " . $e->getMessage() . "<br>";
    }
    
    // 3. Добавляем колонку version_id в таблицу posts, если её ещё нет
    echo "<br>Добавление колонки version_id в таблицу posts...<br>";
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN version_id INT");
        echo "- Колонка version_id добавлена успешно<br>";
    } catch (PDOException $e) {
        echo "- Колонка version_id уже существует: " . $e->getMessage() . "<br>";
    }
    
    // Эта операция может вызвать ошибку, обрабатываем её отдельно
    try {
        $pdo->exec("ALTER TABLE posts ADD FOREIGN KEY (version_id) REFERENCES rewrite_versions(id) ON DELETE SET NULL");
        echo "- Внешний ключ для version_id добавлен успешно<br>";
    } catch (PDOException $e) {
        echo "- Не удалось добавить внешний ключ для version_id: " . $e->getMessage() . "<br>";
    }
    
    // Фиксируем транзакцию
    if ($transactionStarted) {
        $pdo->commit();
        $transactionStarted = false;
    }
    
    echo "<br><h2>Миграция схемы базы данных успешно завершена!</h2>";
    echo "Теперь вы можете использовать новую структуру реврайтов.<br>";
    echo "Существующие реврайты будут перенесены автоматически при первом обращении к ним.";
    
} catch (PDOException $e) {
    // Отменить транзакцию только если она активна
    if (isset($pdo) && $transactionStarted) {
        $pdo->rollBack();
    }
    echo "<h1>Ошибка при миграции</h1>";
    echo "Произошла ошибка: " . $e->getMessage() . "<br>";
}