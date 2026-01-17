<?php
/**
 * Скрипт для создания базы данных и таблиц
 * Запускать один раз для настройки БД
 */

echo "=== Настройка базы данных ===\n\n";

// Настройки подключения (без указания базы данных)
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Подключаемся к MySQL (без выбора базы данных)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Подключение к MySQL успешно\n\n";
    
    // Создаем базу данных
    echo "Создание базы данных...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS rinks_moscow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ База данных 'rinks_moscow' создана\n\n";
    
    // Выбираем базу данных
    $pdo->exec("USE rinks_moscow");
    
    // Читаем SQL скрипт
    $sqlFile = __DIR__ . '/../sql/database_final.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Файл $sqlFile не найден!");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Удаляем комментарии CREATE DATABASE и USE (они уже выполнены)
    $sql = preg_replace('/-- CREATE DATABASE.*?;/s', '', $sql);
    $sql = preg_replace('/-- USE.*?;/s', '', $sql);
    
    // Разбиваем на отдельные запросы
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "Создание таблиц...\n";
    $created = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            // Извлекаем название таблицы из CREATE TABLE
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "  ✅ Таблица '{$matches[1]}' создана\n";
                $created++;
            }
        } catch (PDOException $e) {
            // Игнорируем ошибки "таблица уже существует"
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "  ⚠️  Предупреждение: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Создано таблиц: $created\n\n";
    
    echo "=== База данных настроена! ===\n";
    echo "\nСледующие шаги:\n";
    echo "1. Скопируйте config.example.php в config.php\n";
    echo "2. Заполните настройки подключения к БД в config.php\n";
    echo "3. Запустите test_connection.php для проверки\n";
    
} catch (PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "\nПроверьте:\n";
    echo "1. Запущен ли MySQL сервер?\n";
    echo "2. Правильные ли логин и пароль?\n";
    echo "3. Есть ли права на создание базы данных?\n";
}
