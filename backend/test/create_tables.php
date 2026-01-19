<?php
/**
 * Простой скрипт для создания таблиц
 * Откройте: http://localhost:8080/rinks-moscow-app/backend/test/create_tables.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Создание таблиц</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .ok { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Создание таблиц БД</h1>
    <?php
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=rinks_moscow;charset=utf8mb4", 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p class='ok'>✅ Подключение к БД успешно</p>";
        
        // Читаем SQL файл
        $sqlFile = dirname(__DIR__, 1) . '/../sql/database_final.sql';
        if (!file_exists($sqlFile)) {
            // Пробуем другой путь
            $sqlFile = __DIR__ . '/../../sql/database_final.sql';
        }
        
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL файл не найден: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Удаляем CREATE DATABASE и USE
        $sql = preg_replace('/-- CREATE DATABASE.*?;/s', '', $sql);
        $sql = preg_replace('/CREATE DATABASE.*?;/s', '', $sql);
        $sql = preg_replace('/-- USE.*?;/s', '', $sql);
        $sql = preg_replace('/USE.*?;/s', '', $sql);
        
        // Разбиваем на запросы
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt) && strlen(trim($stmt)) > 10;
            }
        );
        
        $created = 0;
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            try {
                $pdo->exec($statement);
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    echo "<p class='ok'>✅ Таблица '{$matches[1]}' создана</p>";
                    $created++;
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<p class='error'>⚠️ " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
        
        echo "<h2 class='ok'>✅ Готово! Создано таблиц: $created</h2>";
        echo "<p><a href='../api/rinks.php'>Проверить API</a></p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
</body>
</html>
