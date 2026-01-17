<?php
/**
 * Простой тест подключения к базе данных
 */

// Подключаем конфигурацию
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

echo "=== Тест подключения к базе данных ===\n\n";

try {
    // Пытаемся подключиться
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "✅ Подключение к базе данных успешно!\n";
    
    // Проверяем версию MySQL
    $version = $conn->query("SELECT VERSION()")->fetchColumn();
    echo "Версия MySQL: $version\n\n";
    
    // Проверяем существование таблиц
    echo "=== Проверка таблиц ===\n";
    $tables = ['users', 'rinks', 'visits', 'reviews', 'checkins', 'votes', 'events', 'event_participants', 'suspicious_activity'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($result) {
            // Подсчитываем количество записей
            $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✅ Таблица '$table' существует (записей: $count)\n";
        } else {
            echo "❌ Таблица '$table' НЕ существует!\n";
        }
    }
    
    echo "\n=== Тест завершен ===\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "\nПроверьте:\n";
    echo "1. Создана ли база данных?\n";
    echo "2. Правильные ли настройки в config.php?\n";
    echo "3. Запущен ли MySQL сервер?\n";
}
