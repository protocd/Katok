<?php
/**
 * Класс для работы с пользователями
 */

require_once __DIR__ . '/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Зарегистрировать нового пользователя
     * 
     * @param string $email Email пользователя
     * @param string $password Пароль (будет захеширован)
     * @param string $name Имя пользователя
     * @return int ID созданного пользователя
     * @throws Exception Если email уже существует или ошибка валидации
     */
    public function register($email, $password, $name) {
        // Валидация
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Некорректный email");
        }
        
        if (empty($password) || strlen($password) < 6) {
            throw new Exception("Пароль должен содержать минимум 6 символов");
        }
        
        if (empty($name) || strlen($name) < 2) {
            throw new Exception("Имя должно содержать минимум 2 символа");
        }
        
        // Проверка, существует ли пользователь с таким email
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );
        
        if ($existing) {
            throw new Exception("Пользователь с таким email уже существует");
        }
        
        // Хешируем пароль
        $passwordHash = $this->hashPassword($password);
        
        // Получаем IP-адрес пользователя
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // Вставляем пользователя в базу данных
        $userId = $this->db->insert(
            "INSERT INTO users (email, password_hash, name, ip_address) VALUES (?, ?, ?, ?)",
            [$email, $passwordHash, $name, $ipAddress]
        );
        
        return $userId;
    }
    
    /**
     * Авторизовать пользователя
     * 
     * @param string $email Email пользователя
     * @param string $password Пароль
     * @return array Данные пользователя
     * @throws Exception Если неверные учетные данные
     */
    public function login($email, $password) {
        // Находим пользователя по email
        $user = $this->db->fetchOne(
            "SELECT id, email, password_hash, name, role FROM users WHERE email = ?",
            [$email]
        );
        
        if (!$user) {
            throw new Exception("Неверный email или пароль");
        }
        
        // Проверяем пароль
        if (!$this->verifyPassword($password, $user['password_hash'])) {
            throw new Exception("Неверный email или пароль");
        }
        
        // Обновляем время последнего входа и IP
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->db->query(
            "UPDATE users SET last_login = NOW(), ip_address = ? WHERE id = ?",
            [$ipAddress, $user['id']]
        );
        
        // Возвращаем данные пользователя (без пароля)
        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ];
    }
    
    /**
     * Получить пользователя по ID
     * 
     * @param int $userId ID пользователя
     * @return array|null Данные пользователя или null
     */
    public function getUserById($userId) {
        $user = $this->db->fetchOne(
            "SELECT id, email, name, role, created_at, last_login FROM users WHERE id = ?",
            [$userId]
        );
        
        if ($user) {
            // Убираем пароль из результата
            unset($user['password_hash']);
        }
        
        return $user;
    }
    
    /**
     * Хешировать пароль
     * Использует password_hash() с алгоритмом PASSWORD_DEFAULT (bcrypt)
     * 
     * @param string $password Пароль в открытом виде
     * @return string Хеш пароля
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Проверить пароль
     * Использует password_verify() для безопасной проверки
     * 
     * @param string $password Пароль в открытом виде
     * @param string $hash Хеш пароля из базы данных
     * @return bool true если пароль верный, false если нет
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Проверить, существует ли пользователь с таким email
     * 
     * @param string $email Email для проверки
     * @return bool
     */
    public function emailExists($email) {
        $user = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );
        
        return $user !== false;
    }
}
