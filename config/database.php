<?php
/**
 * Класс для работы с базой данных
 */
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEBUG) {
                die("Ошибка подключения к базе данных: " . $e->getMessage());
            } else {
                die("Ошибка подключения к базе данных. Пожалуйста, проверьте настройки.");
            }
        }
    }
    
    // Паттерн Singleton для соединения с БД
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Получить соединение с БД
    public function getConnection() {
        return $this->conn;
    }
    
    // Выполнить запрос с параметрами
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG) {
                die("Ошибка выполнения запроса: " . $e->getMessage() . "<br>SQL: " . $sql);
            } else {
                Logger::log('error', "Ошибка выполнения запроса: " . $e->getMessage() . " SQL: " . $sql);
                die("Ошибка выполнения запроса к базе данных.");
            }
        }
    }
    
    /**
     * Выполнить произвольный SQL-запрос с параметрами
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры запроса
     * @return int Количество затронутых строк
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            if (DEBUG) {
                die("Ошибка выполнения запроса: " . $e->getMessage() . "<br>SQL: " . $sql);
            } else {
                Logger::log('error', "Ошибка выполнения запроса: " . $e->getMessage() . " SQL: " . $sql);
                die("Ошибка выполнения запроса к базе данных.");
            }
        }
    }
    
    // Получить одну запись
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // Получить все записи
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Получить значение одного поля
    public function fetchColumn($sql, $params = [], $column = 0) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn($column);
    }
    
    // Вставить запись и вернуть ID
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return $this->conn->lastInsertId();
    }
    
    // Обновить запись
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = ?";
        }
        $setClause = implode(', ', $set);
        
        // Исправление: проверяем, является ли $where массивом, и конвертируем его в строку, если это так
        if (is_array($where)) {
            $whereConditions = [];
            foreach ($where as $column => $value) {
                $whereConditions[] = "{$column} = ?";
                $whereParams[] = $value;
            }
            $where = implode(' AND ', $whereConditions);
        }
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    // Удалить запись
    public function delete($table, $where, $params = []) {
        // Исправление: проверяем, является ли $where массивом, и конвертируем его в строку, если это так
        if (is_array($where)) {
            $whereConditions = [];
            foreach ($where as $column => $value) {
                $whereConditions[] = "{$column} = ?";
                $params[] = $value;
            }
            $where = implode(' AND ', $whereConditions);
        }
        
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Получить все записи из таблицы
     * 
     * @param string $table Имя таблицы
     * @param array $where Условия выборки в формате ['column' => 'value']
     * @param string $orderBy Сортировка (например, 'id DESC')
     * @param int $limit Ограничение количества записей
     * @return array Массив записей
     */
    public function getAll($table, $where = [], $orderBy = '', $limit = 0) {
        $sql = "SELECT * FROM {$table}";
        $params = [];
        
        // Добавляем условия WHERE, если они есть
        if (!empty($where)) {
            $whereConditions = [];
            foreach ($where as $column => $value) {
                $whereConditions[] = "{$column} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        // Добавляем сортировку, если она указана
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        // Добавляем ограничение, если оно указано
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Получить одну запись из таблицы
     * 
     * @param string $table Имя таблицы
     * @param array $where Условия выборки в формате ['column' => 'value']
     * @return array|false Запись или false, если запись не найдена
     */
    public function get($table, $where = []) {
        $sql = "SELECT * FROM {$table}";
        $params = [];
        
        // Добавляем условия WHERE, если они есть
        if (!empty($where)) {
            $whereConditions = [];
            foreach ($where as $column => $value) {
                $whereConditions[] = "{$column} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        // Ограничиваем выборку одной записью
        $sql .= " LIMIT 1";
        
        return $this->fetchOne($sql, $params);
    }
}
