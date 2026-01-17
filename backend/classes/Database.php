<?php
/**
 * Класс для работы с базой данных
 * Использует паттерн Singleton и PDO с prepared statements
 */

class Database {
    private static $instance = null;
    private $connection = null;
    
    /**
     * Приватный конструктор (Singleton)
     */
    private function __construct() {
        try {
            // Загружаем конфигурацию
            require_once __DIR__ . '/../config/config.php';
            
            // Формируем DSN (Data Source Name)
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            // Опции для PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Выбрасывать исключения при ошибках
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // По умолчанию возвращать ассоциативные массивы
                PDO::ATTR_EMULATE_PREPARES => false, // Использовать нативные prepared statements
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            // Создаем подключение
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // В продакшене не показывать детали ошибки!
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                die("Ошибка подключения к базе данных: " . $e->getMessage());
            } else {
                die("Ошибка подключения к базе данных");
            }
        }
    }
    
    /**
     * Получить экземпляр класса (Singleton)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Получить подключение к базе данных
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Выполнить запрос с prepared statement
     * 
     * @param string $sql SQL-запрос с плейсхолдерами
     * @param array $params Параметры для подстановки
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                throw new Exception("Ошибка выполнения запроса: " . $e->getMessage());
            } else {
                throw new Exception("Ошибка выполнения запроса");
            }
        }
    }
    
    /**
     * Получить одну запись
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры
     * @return array|false
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Получить все записи
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Вставить запись и вернуть ID
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры
     * @return int ID вставленной записи
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }
    
    /**
     * Начать транзакцию
     */
    public function beginTransaction() {
        $this->connection->beginTransaction();
    }
    
    /**
     * Подтвердить транзакцию
     */
    public function commit() {
        $this->connection->commit();
    }
    
    /**
     * Откатить транзакцию
     */
    public function rollback() {
        $this->connection->rollBack();
    }
    
    /**
     * Предотвратить клонирование (Singleton)
     */
    private function __clone() {}
    
    /**
     * Предотвратить десериализацию (Singleton)
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
