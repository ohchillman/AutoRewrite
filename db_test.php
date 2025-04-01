<?php
/**
 * Тестовая страница для проверки соединения с базой данных
 */

// Подключаем конфигурационный файл
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Устанавливаем заголовок для вывода HTML
header('Content-Type: text/html; charset=utf-8');

// Функция для вывода результатов в читаемом формате
function printResult($title, $success, $message = '', $details = '') {
    $statusClass = $success ? 'success' : 'danger';
    $statusText = $success ? 'Успешно' : 'Ошибка';
    
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-{$statusClass} text-white'>{$title}: {$statusText}</div>";
    echo "<div class='card-body'>";
    
    if (!empty($message)) {
        echo "<p>{$message}</p>";
    }
    
    if (!empty($details)) {
        echo "<pre>{$details}</pre>";
    }
    
    echo "</div></div>";
}

// Функция для получения информации о переменных окружения
function getEnvironmentInfo() {
    $envVars = [
        'DB_HOST' => getenv('DB_HOST') ?: 'Не установлено (используется значение по умолчанию)',
        'DB_NAME' => getenv('DB_NAME') ?: 'Не установлено (используется значение по умолчанию)',
        'DB_USER' => getenv('DB_USER') ?: 'Не установлено (используется значение по умолчанию)',
        'DB_CHARSET' => getenv('DB_CHARSET') ?: 'Не установлено (используется значение по умолчанию)',
    ];
    
    // Не показываем пароль, только факт его наличия
    $envVars['DB_PASS'] = getenv('DB_PASS') ? 'Установлено' : 'Не установлено (используется значение по умолчанию)';
    
    return $envVars;
}

// Функция для тестирования соединения с базой данных
function testDatabaseConnection() {
    try {
        // Создаем экземпляр класса Database
        $db = Database::getInstance();
        
        // Проверяем соединение, выполнив простой запрос
        $result = $db->query("SELECT 1 AS test")->fetch();
        
        if ($result && isset($result['test']) && $result['test'] == 1) {
            return [
                'success' => true,
                'message' => 'Соединение с базой данных установлено успешно.',
                'details' => 'Тестовый запрос выполнен успешно.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Не удалось выполнить тестовый запрос.',
                'details' => 'Соединение установлено, но тестовый запрос вернул неожиданный результат.'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Ошибка при подключении к базе данных.',
            'details' => $e->getMessage()
        ];
    }
}

// Функция для проверки таблиц в базе данных
function checkDatabaseTables() {
    try {
        $db = Database::getInstance();
        $tables = $db->fetchAll("SHOW TABLES");
        
        if (empty($tables)) {
            return [
                'success' => false,
                'message' => 'База данных не содержит таблиц.',
                'details' => 'Необходимо создать таблицы или проверить имя базы данных.'
            ];
        }
        
        $tableList = [];
        foreach ($tables as $table) {
            $tableName = reset($table); // Получаем первое значение из массива
            $tableList[] = $tableName;
            
            // Проверяем структуру таблицы
            $columns = $db->fetchAll("SHOW COLUMNS FROM `{$tableName}`");
            $tableList[$tableName] = array_column($columns, 'Field');
        }
        
        return [
            'success' => true,
            'message' => 'Найдено таблиц: ' . count($tableList),
            'details' => print_r($tableList, true)
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Ошибка при проверке таблиц базы данных.',
            'details' => $e->getMessage()
        ];
    }
}

// Функция для проверки записей в таблице proxies
function checkProxiesTable() {
    try {
        $db = Database::getInstance();
        
        // Проверяем существование таблицы
        $tables = $db->fetchAll("SHOW TABLES LIKE 'proxies'");
        if (empty($tables)) {
            return [
                'success' => false,
                'message' => 'Таблица proxies не найдена.',
                'details' => 'Необходимо создать таблицу proxies.'
            ];
        }
        
        // Получаем количество записей
        $count = $db->fetchColumn("SELECT COUNT(*) FROM proxies");
        
        // Получаем несколько записей для примера
        $proxies = $db->fetchAll("SELECT * FROM proxies LIMIT 5");
        
        return [
            'success' => true,
            'message' => 'Таблица proxies найдена. Количество записей: ' . $count,
            'details' => !empty($proxies) ? print_r($proxies, true) : 'Таблица пуста.'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Ошибка при проверке таблицы proxies.',
            'details' => $e->getMessage()
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест соединения с базой данных - AutoRewrite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .card { margin-bottom: 20px; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Тест соединения с базой данных</h1>
        
        <div class="card mb-4">
            <div class="card-header">Конфигурация базы данных</div>
            <div class="card-body">
                <p>Текущие настройки базы данных:</p>
                <ul>
                    <li><strong>Хост:</strong> <?php echo DB_HOST; ?></li>
                    <li><strong>Имя базы данных:</strong> <?php echo DB_NAME; ?></li>
                    <li><strong>Пользователь:</strong> <?php echo DB_USER; ?></li>
                    <li><strong>Пароль:</strong> <?php echo !empty(DB_PASS) ? '******' : 'Не установлен'; ?></li>
                    <li><strong>Кодировка:</strong> <?php echo DB_CHARSET; ?></li>
                </ul>
                
                <p>Переменные окружения:</p>
                <pre><?php print_r(getEnvironmentInfo()); ?></pre>
            </div>
        </div>
        
        <h2 class="mb-3">Результаты тестирования</h2>
        
        <?php
        // Тест соединения с базой данных
        $connectionTest = testDatabaseConnection();
        printResult('Соединение с базой данных', $connectionTest['success'], $connectionTest['message'], $connectionTest['details']);
        
        // Если соединение успешно, проверяем таблицы
        if ($connectionTest['success']) {
            // Проверка таблиц в базе данных
            $tablesTest = checkDatabaseTables();
            printResult('Проверка таблиц', $tablesTest['success'], $tablesTest['message'], $tablesTest['details']);
            
            // Проверка таблицы proxies
            $proxiesTest = checkProxiesTable();
            printResult('Проверка таблицы proxies', $proxiesTest['success'], $proxiesTest['message'], $proxiesTest['details']);
        }
        ?>
        
        <div class="mt-4">
            <a href="/" class="btn btn-primary">Вернуться на главную</a>
        </div>
    </div>
</body>
</html>
