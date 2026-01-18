<?php
/**
 * Скрипт для создания базы данных и таблиц
 * Запускать один раз для настройки БД
 * Откройте в браузере: http://localhost/rinks-moscow-app/backend/test/setup_database.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройка базы данных</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        .warning {
            color: #ff9800;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        ul {
            line-height: 1.8;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>=== Настройка базы данных ===</h1>
        <?php
        // Настройки подключения (без указания базы данных)
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        
        $errors = [];
        $warnings = [];
        $success = [];
        
        try {
            // Подключаемся к MySQL (без выбора базы данных)
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $success[] = "Подключение к MySQL успешно";
            
            // Создаем базу данных
            echo "<p>Создание базы данных...</p>";
            $pdo->exec("CREATE DATABASE IF NOT EXISTS rinks_moscow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $success[] = "База данных 'rinks_moscow' создана";
            
            // Выбираем базу данных
            $pdo->exec("USE rinks_moscow");
            
            // Читаем SQL скрипт
            $sqlFile = __DIR__ . '/../sql/database_final.sql';
            
            if (!file_exists($sqlFile)) {
                throw new Exception("Файл $sqlFile не найден! Проверьте путь к файлу.");
            }
            
            $sql = file_get_contents($sqlFile);
            
            if (empty($sql)) {
                throw new Exception("SQL файл пустой!");
            }
            
            // Удаляем комментарии CREATE DATABASE и USE (они уже выполнены)
            $sql = preg_replace('/-- CREATE DATABASE.*?;/s', '', $sql);
            $sql = preg_replace('/-- USE.*?;/s', '', $sql);
            
            // Разбиваем на отдельные запросы
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && !preg_match('/^--/', $stmt) && strlen(trim($stmt)) > 10;
                }
            );
            
            echo "<p>Создание таблиц...</p>";
            $created = 0;
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) continue;
                
                try {
                    $pdo->exec($statement);
                    // Извлекаем название таблицы из CREATE TABLE
                    if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                        $success[] = "Таблица '{$matches[1]}' создана";
                        $created++;
                    }
                } catch (PDOException $e) {
                    // Игнорируем ошибки "таблица уже существует"
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                            $warnings[] = "Таблица '{$matches[1]}' уже существует";
                        }
                    } else {
                        $errors[] = "Ошибка при создании таблицы: " . htmlspecialchars($e->getMessage());
                    }
                }
            }
            
            $success[] = "Создано таблиц: $created";
            
        } catch (PDOException $e) {
            $errors[] = "Ошибка подключения: " . htmlspecialchars($e->getMessage());
        } catch (Exception $e) {
            $errors[] = htmlspecialchars($e->getMessage());
        }
        
        // Выводим результаты
        if (!empty($success)) {
            echo "<h2 class='success'>✅ Успешно:</h2><ul>";
            foreach ($success as $msg) {
                echo "<li class='success'>$msg</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($warnings)) {
            echo "<h2 class='warning'>⚠️ Предупреждения:</h2><ul>";
            foreach ($warnings as $msg) {
                echo "<li class='warning'>$msg</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($errors)) {
            echo "<h2 class='error'>❌ Ошибки:</h2><ul>";
            foreach ($errors as $msg) {
                echo "<li class='error'>$msg</li>";
            }
            echo "</ul>";
        }
        
        if (empty($errors)) {
            echo "<div class='info'>";
            echo "<h2>=== База данных настроена! ===</h2>";
            echo "<p><strong>Следующие шаги:</strong></p>";
            echo "<ol>";
            echo "<li>Скопируйте <code>config.example.php</code> в <code>config.php</code></li>";
            echo "<li>Проверьте настройки подключения к БД в <code>config.php</code></li>";
            echo "<li>Запустите <a href='test_connection.php'>test_connection.php</a> для проверки</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div class='info'>";
            echo "<h2>Проверьте:</h2>";
            echo "<ul>";
            echo "<li>Запущен ли MySQL сервер? (XAMPP Control Panel → Start для MySQL)</li>";
            echo "<li>Правильные ли логин и пароль? (по умолчанию: root, пустой пароль)</li>";
            echo "<li>Есть ли права на создание базы данных?</li>";
            echo "<li>Существует ли файл <code>sql/database_final.sql</code>?</li>";
            echo "</ul>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>
